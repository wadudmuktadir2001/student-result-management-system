<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('teacher');

$courseId = (int) ($_GET['course'] ?? 0);
$semesterId = (int) ($_GET['semester'] ?? 0);
$day = $_GET['day'] ?? date('Y-m-d');

function valid_attendance_date($value)
{
    if (!is_string($value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date && $date->format('Y-m-d') === $value;
}

if (!valid_attendance_date($day)) {
    $day = date('Y-m-d');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    guard_csrf();

    $courseId = (int) ($_POST['course'] ?? 0);
    $semesterId = (int) ($_POST['semester'] ?? 0);
    $day = $_POST['day'] ?? '';

    if (!teaching($u, $courseId, $semesterId)) {
        http_response_code(403);
        exit('You are not assigned to this course and semester.');
    }

    if (!valid_attendance_date($day)) {
        $error = 'Please select a valid date.';
        $day = date('Y-m-d');
    } else {
        $enrolledStudents = rows(
            'SELECT student_id
             FROM enrollments
             WHERE course_id = ? AND semester_id = ?',
            [$courseId, $semesterId]
        );

        $submittedStatuses = $_POST['status'] ?? [];

        try {
            if (empty($enrolledStudents)) {
                throw new RuntimeException('No enrolled students found.');
            }

            if (!is_array($submittedStatuses)) {
                throw new RuntimeException('Invalid attendance entries.');
            }

            // Validate every enrolled student's status before saving.
            foreach ($enrolledStudents as $student) {
                $studentId = (int) $student['student_id'];
                $status = $submittedStatuses[$studentId] ?? '';

                if (!in_array(
                    $status,
                    ['present', 'absent', 'late'],
                    true
                )) {
                    throw new RuntimeException(
                        'Please select attendance for every student.'
                    );
                }
            }

            $db->beginTransaction();

            foreach ($enrolledStudents as $student) {
                $studentId = (int) $student['student_id'];

                q(
                    'INSERT INTO attendance
                        (student_id, course_id, semester_id, day, status)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE status = VALUES(status)',
                    [
                        $studentId,
                        $courseId,
                        $semesterId,
                        $day,
                        $submittedStatuses[$studentId],
                    ]
                );
            }

            $db->commit();

            $_SESSION['flash'] = 'Attendance saved successfully.';

            redirect(
                'teacher/attendance.php?' . http_build_query([
                    'course' => $courseId,
                    'semester' => $semesterId,
                    'day' => $day,
                ])
            );
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $error = $exception instanceof RuntimeException
                && !($exception instanceof PDOException)
                ? $exception->getMessage()
                : 'Unable to save attendance. Please try again.';
        }
    }
}

$assignedCourses = rows(
    'SELECT DISTINCT
        a.course_id,
        a.semester_id,
        c.name AS course,
        se.name AS semester
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     JOIN semesters se ON se.id = a.semester_id
     WHERE a.teacher_id = ?
     ORDER BY se.id DESC, c.name',
    [$u['id']]
);

$selectedAssignment = null;

foreach ($assignedCourses as $assignment) {
    if (
        (int) $assignment['course_id'] === $courseId &&
        (int) $assignment['semester_id'] === $semesterId
    ) {
        $selectedAssignment = $assignment;
        break;
    }
}

$students = [];

if ($selectedAssignment) {
    $students = rows(
        'SELECT
            en.student_id,
            u.name AS student,
            s.roll_no,
            att.status
         FROM enrollments en
         JOIN users u ON u.id = en.student_id
         LEFT JOIN students s ON s.user_id = en.student_id
         LEFT JOIN attendance att
            ON att.student_id = en.student_id
            AND att.course_id = en.course_id
            AND att.semester_id = en.semester_id
            AND att.day = ?
         WHERE en.course_id = ?
           AND en.semester_id = ?
         ORDER BY s.roll_no, u.name',
        [$day, $courseId, $semesterId]
    );
}

header_ui($u, 'Attendance');
?>

<section class="panel">
    <h2>Select class and date</h2>

    <?php if (empty($assignedCourses)): ?>
        <p class="notice">No courses assigned yet.</p>
    <?php else: ?>
        <form method="get">
            <label for="assignment">Course — Semester</label>

            <select
                id="assignment"
                required
                onchange="
                    const option = this.options[this.selectedIndex];
                    document.getElementById('selected-course').value =
                        option.dataset.course || '';
                    document.getElementById('selected-semester').value =
                        option.dataset.semester || '';
                ">
                <option value="">Select course and semester</option>

                <?php foreach ($assignedCourses as $assignment): ?>
                    <option
                        value="<?= e(
                                    $assignment['course_id'] . '-' .
                                        $assignment['semester_id']
                                ) ?>"
                        data-course="<?= (int) $assignment['course_id'] ?>"
                        data-semester="<?= (int) $assignment['semester_id'] ?>"
                        <?= (
                            (int) $assignment['course_id'] === $courseId &&
                            (int) $assignment['semester_id'] === $semesterId
                        ) ? 'selected' : '' ?>>
                        <?= e(
                            $assignment['course'] . ' — ' .
                                $assignment['semester']
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

            <label for="day">
                Date
                <input
                    id="day"
                    type="date"
                    name="day"
                    value="<?= e($day) ?>"
                    required>
            </label>

            <button type="submit">Show students</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($error !== ''): ?>
    <p class="notice error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($selectedAssignment): ?>
    <section class="panel">
        <div class="section-head">
            <div>
                <h2><?= e($selectedAssignment['course']) ?></h2>
                <p>
                    <?= e($selectedAssignment['semester']) ?>
                    · <?= e($day) ?>
                </p>
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
            <form method="post">
                <?= csrf() ?>

                <input
                    type="hidden"
                    name="course"
                    value="<?= $courseId ?>">
                <input
                    type="hidden"
                    name="semester"
                    value="<?= $semesterId ?>">
                <input
                    type="hidden"
                    name="day"
                    value="<?= e($day) ?>">

                <div class="actions" style="margin:16px 0">
                    <button
                        type="button"
                        onclick="
                            this.form.querySelectorAll('.attendance-status')
                                .forEach(select => select.value = 'present');
                        ">
                        Mark all present
                    </button>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Roll</th>
                                <th>Student</th>
                                <th>Attendance</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                                <?php
                                $studentId = (int) $student['student_id'];
                                $status = $student['status'] ?? '';

                                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                                    $posted = $_POST['status'] ?? [];

                                    if (
                                        is_array($posted) &&
                                        isset($posted[$studentId]) &&
                                        is_string($posted[$studentId])
                                    ) {
                                        $status = $posted[$studentId];
                                    }
                                }
                                ?>

                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <?= e($student['roll_no'] ?? '—') ?>
                                    </td>
                                    <td><?= e($student['student']) ?></td>

                                    <td>
                                        <select
                                            class="attendance-status"
                                            name="status[<?= $studentId ?>]"
                                            aria-label="<?= e(
                                                            'Attendance for ' .
                                                                $student['student']
                                                        ) ?>"
                                            required>
                                            <option
                                                value=""
                                                <?= $status === ''
                                                    ? 'selected' : '' ?>>
                                                Select status
                                            </option>

                                            <?php foreach (
                                                [
                                                    'present' => 'Present',
                                                    'absent' => 'Absent',
                                                    'late' => 'Late',
                                                ] as $value => $label
                                            ): ?>
                                                <option
                                                    value="<?= e($value) ?>"
                                                    <?= $status === $value
                                                        ? 'selected' : '' ?>>
                                                    <?= e($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" style="margin-top:20px">
                    Save all attendance
                </button>
            </form>
        <?php endif; ?>
    </section>

<?php elseif ($courseId || $semesterId): ?>
    <p class="notice error">
        Please select an assigned course and semester.
    </p>
<?php endif; ?>

<?php footer_ui(); ?>