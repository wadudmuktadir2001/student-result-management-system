<?php

if (!defined('APP_BASE')) {
    http_response_code(403);
    exit('Forbidden');
}

if (!in_array($u['role'], ['teacher', 'admin'], true)) {
    http_response_code(403);
    exit('Forbidden');
}

$attendance_only = $attendance_only ?? false;

$courseId = (int) ($_GET['course'] ?? 0);
$semesterId = (int) ($_GET['semester'] ?? 0);
$error = '';
$submittedStudent = 0;
$submittedMarks = '';
$submittedComment = '';
$submittedPublished = false;

$pagePath = $u['role'] . '/' . (
    $attendance_only
    ? 'attendance.php'
    : ($u['role'] === 'admin' ? 'results.php' : 'grades.php')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    guard_csrf();

    $isAttendance = ($_POST['action'] ?? '') === 'attendance';

    if ($attendance_only !== $isAttendance) {
        http_response_code(400);
        exit('Invalid page action');
    }

    $studentId = (int) ($_POST['student'] ?? 0);
    $courseId = (int) ($_POST['course'] ?? 0);
    $semesterId = (int) ($_POST['semester'] ?? 0);

    if (
        !teaching($u, $courseId, $semesterId) ||
        !q(
            'SELECT 1 FROM enrollments
             WHERE student_id = ?
               AND course_id = ?
               AND semester_id = ?',
            [$studentId, $courseId, $semesterId]
        )->fetchColumn()
    ) {
        http_response_code(403);
        exit('Not assigned to this enrollment');
    }

    try {
        if ($attendance_only) {
            $day = $_POST['day'] ?? '';
            $status = $_POST['status'] ?? '';

            $date = is_string($day)
                ? DateTime::createFromFormat('!Y-m-d', $day)
                : false;

            if (
                !$date ||
                $date->format('Y-m-d') !== $day ||
                !in_array($status, ['present', 'absent', 'late'], true)
            ) {
                throw new RuntimeException('Invalid date or status.');
            }

            $db->beginTransaction();

            q(
                'INSERT INTO attendance
                    (student_id, course_id, semester_id, day, status)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status)',
                [$studentId, $courseId, $semesterId, $day, $status]
            );
        } else {
            $submittedStudent = $studentId;

            $marksInput = $_POST['marks'] ?? '';
            $commentInput = $_POST['comment'] ?? '';

            if (
                !is_string($marksInput) ||
                !is_string($commentInput)
            ) {
                throw new RuntimeException('Invalid result entries.');
            }

            $submittedMarks = $marksInput;
            $submittedComment = trim($commentInput);
            $submittedPublished = isset($_POST['published']);

            $marks = filter_var(
                $submittedMarks,
                FILTER_VALIDATE_FLOAT
            );

            if (
                $marks === false ||
                !is_finite((float) $marks) ||
                $marks < 0 ||
                $marks > 100
            ) {
                throw new RuntimeException(
                    'Marks must be between 0 and 100.'
                );
            }

            if (strlen($submittedComment) > 5000) {
                throw new RuntimeException('Feedback is too long.');
            }

            $published = $submittedPublished ? 1 : 0;

            $db->beginTransaction();

            $oldResult = q(
                'SELECT * FROM results
                 WHERE student_id = ?
                   AND course_id = ?
                   AND semester_id = ?
                 FOR UPDATE',
                [$studentId, $courseId, $semesterId]
            )->fetch();

            q(
                'INSERT INTO results
                    (
                        student_id,
                        course_id,
                        semester_id,
                        marks,
                        comment,
                        published,
                        updated_by
                    )
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    marks = VALUES(marks),
                    comment = VALUES(comment),
                    published = VALUES(published),
                    updated_by = VALUES(updated_by)',
                [
                    $studentId,
                    $courseId,
                    $semesterId,
                    $marks,
                    $submittedComment,
                    $published,
                    $u['id'],
                ]
            );

            $resultId = q(
                'SELECT id FROM results
                 WHERE student_id = ?
                   AND course_id = ?
                   AND semester_id = ?',
                [$studentId, $courseId, $semesterId]
            )->fetchColumn();

            $newResult = [
                'marks' => $marks,
                'comment' => $submittedComment,
                'published' => $published,
            ];

            q(
                'INSERT INTO audit_logs
                    (result_id, actor_id, old_data, new_data)
                 VALUES (?, ?, ?, ?)',
                [
                    $resultId,
                    $u['id'],
                    $oldResult ? json_encode($oldResult) : null,
                    json_encode($newResult),
                ]
            );

            if (
                $published &&
                (!$oldResult || !$oldResult['published'])
            ) {
                $student = q(
                    'SELECT s.parent_id, u.name
                     FROM students s
                     JOIN users u ON u.id = s.user_id
                     WHERE s.user_id = ?',
                    [$studentId]
                )->fetch();

                if ($student && !empty($student['parent_id'])) {
                    q(
                        'INSERT INTO notifications (parent_id, message)
                         VALUES (?, ?)',
                        [
                            $student['parent_id'],
                            'A new result for ' .
                                $student['name'] .
                                ' has been published.',
                        ]
                    );
                }
            }
        }

        $db->commit();

        $_SESSION['flash'] = 'Saved successfully.';

        redirect(
            $pagePath . '?' . http_build_query([
                'course' => $courseId,
                'semester' => $semesterId,
            ])
        );
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $exception instanceof RuntimeException
            && !($exception instanceof PDOException)
            ? $exception->getMessage()
            : 'Unable to save. Please check your entries.';
    }
}

// Available classes, restricted to the teacher's assignments.
$classSql = '
    SELECT DISTINCT
        en.course_id,
        en.semester_id,
        c.name AS course,
        se.name AS semester
    FROM enrollments en
    JOIN courses c ON c.id = en.course_id
    JOIN semesters se ON se.id = en.semester_id
';

$classArgs = [];

if ($u['role'] === 'teacher') {
    $classSql = '
        SELECT DISTINCT
            a.course_id,
            a.semester_id,
            c.name AS course,
            se.name AS semester
        FROM assignments a
        JOIN courses c ON c.id = a.course_id
        JOIN semesters se ON se.id = a.semester_id
        WHERE a.teacher_id = ?
    ';

    $classArgs = [$u['id']];
}

$classSql .= ' ORDER BY semester_id DESC, course';

$classes = rows($classSql, $classArgs);
$selectedClass = null;

foreach ($classes as $class) {
    if (
        (int) $class['course_id'] === $courseId &&
        (int) $class['semester_id'] === $semesterId
    ) {
        $selectedClass = $class;
        break;
    }
}

$students = [];

if ($selectedClass) {
    $students = rows(
        'SELECT
            en.student_id,
            u.name AS student,
            s.roll_no,
            r.marks,
            r.comment,
            r.published
         FROM enrollments en
         JOIN users u ON u.id = en.student_id
         LEFT JOIN students s ON s.user_id = en.student_id
         LEFT JOIN results r
            ON r.student_id = en.student_id
            AND r.course_id = en.course_id
            AND r.semester_id = en.semester_id
         WHERE en.course_id = ?
           AND en.semester_id = ?
         ORDER BY s.roll_no, u.name',
        [$courseId, $semesterId]
    );
}

header_ui($u, $attendance_only ? 'Attendance' : 'Grade entry');
?>

<section class="panel">
    <h2>Select course and semester</h2>

    <?php if (empty($classes)): ?>
        <p class="notice">
            No classes available. Ask the administrator to check
            course assignments and enrollments.
        </p>
    <?php else: ?>
        <form method="get" action="<?= e(url($pagePath)) ?>">
            <label for="class-select">Course — Semester</label>

            <select id="class-select" required>
                <option value="">Select a class</option>

                <?php foreach ($classes as $class): ?>
                    <option
                        value="<?= e(
                                    $class['course_id'] . '-' .
                                        $class['semester_id']
                                ) ?>"
                        data-course="<?= (int) $class['course_id'] ?>"
                        data-semester="<?= (int) $class['semester_id'] ?>"
                        <?= (
                            (int) $class['course_id'] === $courseId &&
                            (int) $class['semester_id'] === $semesterId
                        ) ? 'selected' : '' ?>>
                        <?= e(
                            $class['course'] . ' — ' . $class['semester']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input
                id="selected-course"
                type="hidden"
                name="course"
                value="<?= $courseId ?>">

            <input
                id="selected-semester"
                type="hidden"
                name="semester"
                value="<?= $semesterId ?>">

            <button type="submit" style="margin-top:16px">
                Show students
            </button>
        </form>

        <script>
            const classSelect = document.getElementById('class-select');

            function updateSelectedClass() {
                const option = classSelect.options[classSelect.selectedIndex];

                document.getElementById('selected-course').value =
                    option.dataset.course || '';

                document.getElementById('selected-semester').value =
                    option.dataset.semester || '';
            }

            classSelect.addEventListener('change', updateSelectedClass);
            updateSelectedClass();
        </script>
    <?php endif; ?>
</section>

<?php if ($error !== ''): ?>
    <p class="notice error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($selectedClass): ?>
    <section class="panel">
        <div class="section-head">
            <div>
                <h2><?= e($selectedClass['course']) ?></h2>
                <p><?= e($selectedClass['semester']) ?></p>
            </div>

            <span class="badge">
                <?= count($students) ?> students
            </span>
        </div>

        <?php if (empty($students)): ?>
            <p class="notice">
                No students enrolled in this course and semester.
            </p>
        <?php else: ?>

            <?php foreach ($students as $student): ?>
                <form
                    id="student-form-<?= (int) $student['student_id'] ?>"
                    method="post"
                    action="<?= e(url(
                                $pagePath . '?' . http_build_query([
                                    'course' => $courseId,
                                    'semester' => $semesterId,
                                ])
                            )) ?>">
                    <?= csrf() ?>

                    <input
                        type="hidden"
                        name="action"
                        value="<?= $attendance_only ? 'attendance' : 'grade' ?>">
                    <input
                        type="hidden"
                        name="student"
                        value="<?= (int) $student['student_id'] ?>">
                    <input
                        type="hidden"
                        name="course"
                        value="<?= $courseId ?>">
                    <input
                        type="hidden"
                        name="semester"
                        value="<?= $semesterId ?>">
                </form>
            <?php endforeach; ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Roll</th>
                            <th>Student</th>

                            <?php if ($attendance_only): ?>
                                <th>Date</th>
                                <th>Status</th>
                            <?php else: ?>
                                <th>Marks / 100</th>
                                <th>Saved grade</th>
                                <th>Feedback</th>
                                <th>Publish</th>
                            <?php endif; ?>

                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <?php
                            $studentId = (int) $student['student_id'];
                            $formId = 'student-form-' . $studentId;

                            $marks = $student['marks'];
                            $comment = $student['comment'];
                            $published = (bool) $student['published'];

                            if (
                                !$attendance_only &&
                                $submittedStudent === $studentId &&
                                $error !== ''
                            ) {
                                $marks = $submittedMarks;
                                $comment = $submittedComment;
                                $published = $submittedPublished;
                            }
                            ?>

                            <tr>
                                <td><?= $index + 1 ?></td>

                                <td>
                                    <?= e($student['roll_no'] ?? '—') ?>
                                </td>

                                <td><?= e($student['student']) ?></td>

                                <?php if ($attendance_only): ?>
                                    <td style="min-width:170px">
                                        <input
                                            form="<?= e($formId) ?>"
                                            type="date"
                                            name="day"
                                            value="<?= date('Y-m-d') ?>"
                                            aria-label="<?= e(
                                                            'Attendance date for ' .
                                                                $student['student']
                                                        ) ?>"
                                            required>
                                    </td>

                                    <td style="min-width:140px">
                                        <select
                                            form="<?= e($formId) ?>"
                                            name="status"
                                            aria-label="<?= e(
                                                            'Attendance for ' .
                                                                $student['student']
                                                        ) ?>"
                                            required>
                                            <option value="">Select</option>
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="late">Late</option>
                                        </select>
                                    </td>
                                <?php else: ?>
                                    <td style="min-width:130px">
                                        <input
                                            form="<?= e($formId) ?>"
                                            type="number"
                                            name="marks"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            value="<?= e($marks) ?>"
                                            aria-label="<?= e(
                                                            'Marks for ' . $student['student']
                                                        ) ?>"
                                            required>
                                    </td>

                                    <td>
                                        <span class="badge">
                                            <?= $student['marks'] !== null
                                                ? e(grade($student['marks']))
                                                : '—' ?>
                                        </span>
                                    </td>

                                    <td style="min-width:220px">
                                        <textarea
                                            form="<?= e($formId) ?>"
                                            name="comment"
                                            maxlength="5000"
                                            placeholder="Teacher feedback"
                                            aria-label="<?= e(
                                                            'Feedback for ' .
                                                                $student['student']
                                                        ) ?>"><?= e($comment) ?></textarea>
                                    </td>

                                    <td>
                                        <label class="check">
                                            <input
                                                form="<?= e($formId) ?>"
                                                type="checkbox"
                                                name="published"
                                                value="1"
                                                <?= $published ? 'checked' : '' ?>>
                                            Publish
                                        </label>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <button
                                        form="<?= e($formId) ?>"
                                        type="submit">
                                        Save
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php elseif ($courseId || $semesterId): ?>
    <p class="notice error">
        Please select an available course and semester.
    </p>
<?php else: ?>
    <p class="notice">
        Select a course and semester to view enrolled students.
    </p>
<?php endif; ?>

<?php footer_ui(); ?>