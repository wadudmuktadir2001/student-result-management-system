<?php

if (!defined('APP_BASE')) {
    http_response_code(403);
    exit('Forbidden');
}

if ($u['role'] !== 'teacher') {
    http_response_code(403);
    exit('Forbidden');
}

$fields = [
    'class_test' => ['Class test', 20],
    'attendance_marks' => ['Attendance', 5],
    'assignment_marks' => ['Assignment', 5],
    'midterm' => ['Midterm', 30],
    'final_exam' => ['Final', 40],
];

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$source = $isPost ? $_POST : $_GET;

$courseId = (int) ($source['course'] ?? 0);
$semesterId = (int) ($source['semester'] ?? 0);
$error = '';
$submitted = [];
$publish = false;

$pagePath = 'teacher/grades.php';

$classes = rows(
    'SELECT a.course_id, a.semester_id,
            c.name AS course, se.name AS semester
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     JOIN semesters se ON se.id = a.semester_id
     WHERE a.teacher_id = ?
     ORDER BY se.id DESC, c.name',
    [$u['id']]
);

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
        'SELECT en.student_id, u.name AS student, s.roll_no,
                r.id AS result_id, r.marks, r.comment, r.published,
                r.class_test, r.attendance_marks,
                r.assignment_marks, r.midterm, r.final_exam
         FROM enrollments en
         JOIN users u ON u.id = en.student_id
         JOIN students s ON s.user_id = en.student_id
         LEFT JOIN results r
           ON r.student_id = en.student_id
          AND r.course_id = en.course_id
          AND r.semester_id = en.semester_id
         WHERE en.course_id = ? AND en.semester_id = ?
         ORDER BY s.roll_no, u.name',
        [$courseId, $semesterId]
    );
}

// Initially check Publish only when all listed results are published.
$publish = count($students) > 0;

foreach ($students as $student) {
    if (!(bool) $student['published']) {
        $publish = false;
        break;
    }
}

if ($isPost) {
    guard_csrf();

    if (
        !$selectedClass ||
        !teaching($u, $courseId, $semesterId)
    ) {
        http_response_code(403);
        exit('Not assigned to this class.');
    }

    $submitted = $_POST['entries'] ?? [];
    $publish = isset($_POST['published']);

    try {
        if (
            ($_POST['action'] ?? '') !== 'save_all_grades' ||
            !is_array($submitted) ||
            !$students
        ) {
            throw new RuntimeException('Invalid grade submission.');
        }

        if (count($submitted) !== count($students)) {
            throw new RuntimeException(
                'Student list is incomplete or changed. Reload the page.'
            );
        }

        $validated = [];

        foreach ($students as $student) {
            $studentId = (int) $student['student_id'];
            $entry = $submitted[$studentId] ?? null;

            if (!is_array($entry)) {
                throw new RuntimeException(
                    'Missing entries for ' . $student['student'] . '.'
                );
            }

            $clean = [];
            $totalCents = 0;

            foreach ($fields as $key => [$label, $maximum]) {
                $raw = $entry[$key] ?? '';

                // Accept nonnegative marks with up to two decimal places.
                if (
                    !is_string($raw) ||
                    !preg_match('/^\d+(?:\.\d{1,2})?$/D', trim($raw))
                ) {
                    throw new RuntimeException(
                        $student['student'] . ': Enter valid ' . $label . ' marks.'
                    );
                }

                $value = (float) trim($raw);

                if (!is_finite($value) || $value > $maximum) {
                    throw new RuntimeException(
                        $student['student'] . ': ' . $label .
                            ' must be between 0 and ' . $maximum . '.'
                    );
                }

                $cents = (int) round($value * 100);
                $clean[$key] = number_format($cents / 100, 2, '.', '');
                $totalCents += $cents;
            }

            $comment = $entry['comment'] ?? '';

            if (!is_string($comment) || strlen($comment) > 5000) {
                throw new RuntimeException(
                    $student['student'] . ': Invalid or too long feedback.'
                );
            }

            $clean['marks'] = number_format(
                $totalCents / 100,
                2,
                '.',
                ''
            );

            $clean['comment'] = trim($comment);
            $clean['published'] = $publish ? 1 : 0;

            $validated[$studentId] = $clean;
        }

        $db->beginTransaction();

        // Lock and recheck the roster before saving.
        $currentIds = array_map(
            'intval',
            array_column(
                rows(
                    'SELECT student_id FROM enrollments
                     WHERE course_id = ? AND semester_id = ?
                     ORDER BY student_id FOR UPDATE',
                    [$courseId, $semesterId]
                ),
                'student_id'
            )
        );

        $expectedIds = array_keys($validated);
        sort($expectedIds, SORT_NUMERIC);

        if (
            $currentIds !== $expectedIds ||
            !teaching($u, $courseId, $semesterId)
        ) {
            throw new RuntimeException(
                'Class assignments or enrollments changed. Reload the page.'
            );
        }

        foreach ($students as $student) {
            $studentId = (int) $student['student_id'];
            $new = $validated[$studentId];

            $old = q(
                'SELECT * FROM results
                 WHERE student_id = ?
                   AND course_id = ?
                   AND semester_id = ?
                 FOR UPDATE',
                [$studentId, $courseId, $semesterId]
            )->fetch();

            q(
                'INSERT INTO results (
                    student_id, course_id, semester_id,
                    class_test, attendance_marks, assignment_marks,
                    midterm, final_exam, marks,
                    comment, published, updated_by
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    class_test = VALUES(class_test),
                    attendance_marks = VALUES(attendance_marks),
                    assignment_marks = VALUES(assignment_marks),
                    midterm = VALUES(midterm),
                    final_exam = VALUES(final_exam),
                    marks = VALUES(marks),
                    comment = VALUES(comment),
                    published = VALUES(published),
                    updated_by = VALUES(updated_by)',
                [
                    $studentId,
                    $courseId,
                    $semesterId,
                    $new['class_test'],
                    $new['attendance_marks'],
                    $new['assignment_marks'],
                    $new['midterm'],
                    $new['final_exam'],
                    $new['marks'],
                    $new['comment'],
                    $new['published'],
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

            q(
                'INSERT INTO audit_logs
                 (result_id, actor_id, old_data, new_data)
                 VALUES (?, ?, ?, ?)',
                [
                    $resultId,
                    $u['id'],
                    $old ? json_encode($old, JSON_THROW_ON_ERROR) : null,
                    json_encode($new, JSON_THROW_ON_ERROR),
                ]
            );

            if ($publish && (!$old || !$old['published'])) {
                $parentId = q(
                    'SELECT parent_id FROM students WHERE user_id = ?',
                    [$studentId]
                )->fetchColumn();

                if ($parentId) {
                    q(
                        'INSERT INTO notifications (parent_id, message)
                         VALUES (?, ?)',
                        [
                            $parentId,
                            'A new result for ' . $student['student'] .
                                ' has been published.',
                        ]
                    );
                }
            }
        }

        $db->commit();

        $_SESSION['flash'] = $publish
            ? 'All results saved and published successfully.'
            : 'All results saved as drafts successfully.';

        redirect(
            $pagePath . '?' . http_build_query([
                'course' => $courseId,
                'semester' => $semesterId,
            ])
        );
    } catch (Throwable $ex) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $error = $ex instanceof RuntimeException
            && !($ex instanceof PDOException)
            ? $ex->getMessage()
            : 'Unable to save results. Check the database setup and entries.';
    }
}

header_ui($u, 'Grade entry');
?>

<section class="panel">
    <h2>Select course and semester</h2>

    <?php if (!$classes): ?>
        <p class="notice">No courses assigned yet.</p>
    <?php else: ?>
        <form method="get" action="<?= e(url($pagePath)) ?>">
            <label for="class-select">Course — Semester</label>

            <select id="class-select" required>
                <option value="">Select a class</option>

                <?php foreach ($classes as $class): ?>
                    <option
                        value="<?= (int) $class['course_id'] ?>-<?= (int) $class['semester_id'] ?>"
                        data-course="<?= (int) $class['course_id'] ?>"
                        data-semester="<?= (int) $class['semester_id'] ?>"
                        <?= (
                            (int) $class['course_id'] === $courseId &&
                            (int) $class['semester_id'] === $semesterId
                        ) ? 'selected' : '' ?>>
                        <?= e($class['course'] . ' — ' . $class['semester']) ?>
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

            <span class="badge"><?= count($students) ?> students</span>
        </div>

        <?php if (!$students): ?>
            <p class="notice">No students enrolled in this class.</p>
        <?php else: ?>
            <p>
                Enter all five components. Total marks are calculated
                automatically. Enter 0 where appropriate.
            </p>

            <form
                id="bulk-grades"
                method="post"
                action="<?= e(url($pagePath)) ?>">
                <?= csrf() ?>

                <input type="hidden" name="action" value="save_all_grades">
                <input type="hidden" name="course" value="<?= $courseId ?>">
                <input type="hidden" name="semester" value="<?= $semesterId ?>">

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Roll / Student</th>

                                <?php foreach ($fields as [$label, $maximum]): ?>
                                    <th><?= e($label) ?> / <?= $maximum ?></th>
                                <?php endforeach; ?>

                                <th>Total / 100</th>
                                <th>Feedback</th>
                                <th>Saved status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <?php
                                $studentId = (int) $student['student_id'];

                                $entry = $student;

                                if (
                                    $isPost &&
                                    is_array($submitted) &&
                                    isset($submitted[$studentId]) &&
                                    is_array($submitted[$studentId])
                                ) {
                                    $entry = $submitted[$studentId];
                                }
                                ?>

                                <tr class="grade-row">
                                    <td style="min-width:170px">
                                        <b><?= e($student['student']) ?></b>
                                        <small><?= e($student['roll_no']) ?></small>

                                        <?php if (
                                            $student['marks'] !== null &&
                                            $student['class_test'] === null
                                        ): ?>
                                            <small>
                                                Previous total:
                                                <?= e($student['marks']) ?>.
                                                Enter the component marks.
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <?php foreach ($fields as $key => [$label, $maximum]): ?>
                                        <?php
                                        $value = $entry[$key] ?? '';
                                        $value = is_scalar($value) ? $value : '';
                                        ?>

                                        <td style="min-width:110px">
                                            <input
                                                class="component-mark"
                                                type="number"
                                                name="entries[<?= $studentId ?>][<?= e($key) ?>]"
                                                min="0"
                                                max="<?= $maximum ?>"
                                                step="0.01"
                                                value="<?= e($value) ?>"
                                                aria-label="<?= e(
                                                                $label . ' for ' . $student['student']
                                                            ) ?>"
                                                required>
                                        </td>
                                    <?php endforeach; ?>

                                    <td>
                                        <strong class="total-mark">—</strong>
                                    </td>

                                    <td style="min-width:200px">
                                        <?php
                                        $comment = $entry['comment'] ?? '';
                                        $comment = is_string($comment) ? $comment : '';
                                        ?>

                                        <textarea
                                            name="entries[<?= $studentId ?>][comment]"
                                            maxlength="5000"
                                            placeholder="Teacher feedback"
                                            aria-label="<?= e(
                                                            'Feedback for ' . $student['student']
                                                        ) ?>"><?= e($comment) ?></textarea>
                                    </td>

                                    <td>
                                        <span class="badge">
                                            <?= $student['marks'] === null
                                                ? 'Not saved'
                                                : ($student['published'] ? 'Published' : 'Draft') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:24px">
                    <label class="check">
                        <input
                            type="checkbox"
                            name="published"
                            value="1"
                            <?= $publish ? 'checked' : '' ?>>
                        Publish all results for students & parents
                    </label>

                    <p class="muted">
                        Unchecked: all listed results will be saved as drafts,
                        including any previously published results.
                    </p>

                    <button type="submit">Save all results</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php else: ?>
    <p class="notice">Select an assigned course and semester.</p>
<?php endif; ?>

<script>
    (() => {
        const classSelect = document.getElementById('class-select');

        if (classSelect) {
            const syncClass = () => {
                const option = classSelect.selectedOptions[0];

                document.getElementById('selected-course').value =
                    option?.dataset.course || '';

                document.getElementById('selected-semester').value =
                    option?.dataset.semester || '';
            };

            classSelect.addEventListener('change', syncClass);
            syncClass();
        }

        document.querySelectorAll('.grade-row').forEach(row => {
            const inputs = [...row.querySelectorAll('.component-mark')];
            const output = row.querySelector('.total-mark');

            const updateTotal = () => {
                const complete = inputs.every(input =>
                    input.value !== '' && input.validity.valid
                );

                output.textContent = complete ?
                    (
                        inputs.reduce(
                            (sum, input) =>
                            sum + Math.round(Number(input.value) * 100),
                            0
                        ) / 100
                    ).toFixed(2) :
                    '—';
            };

            inputs.forEach(input =>
                input.addEventListener('input', updateTotal)
            );

            updateTotal();
        });
    })();
</script>

<?php footer_ui(); ?>