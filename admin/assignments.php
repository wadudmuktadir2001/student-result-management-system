<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('admin');
$admin_page = 'assignments';

require dirname(__DIR__) . '/includes/admin_handler.php';

$teachers = array_filter(
    $users,
    fn($user) => $user['role'] === 'teacher'
);

$assignments = rows(
    'SELECT
        u.name AS teacher,
        c.name AS course,
        se.name AS semester
     FROM assignments AS a
     INNER JOIN users AS u ON u.id = a.teacher_id
     INNER JOIN courses AS c ON c.id = a.course_id
     INNER JOIN semesters AS se ON se.id = a.semester_id
     ORDER BY se.name, c.name, u.name'
);

$canAssign = !empty($teachers)
    && !empty($courses)
    && !empty($sems);

header_ui($u, 'Teacher assignments');
?>

<section class="panel">
    <h2>Assign teacher</h2>
    <p>Select a teacher, course and semester.</p>

    <?php if (!$canAssign): ?>
        <p class="notice error" role="alert">
            Please add at least one teacher, course and semester
            before creating an assignment.
        </p>
    <?php else: ?>
        <form method="post">
            <?= csrf() ?>

            <input type="hidden" name="action" value="assign">

            <label for="teacher">
                Teacher
                <select id="teacher" name="teacher" required>
                    <option value="" disabled selected>
                        Select a teacher
                    </option>
                    <?php options($teachers); ?>
                </select>
            </label>

            <label for="course">
                Course
                <select id="course" name="course" required>
                    <option value="" disabled selected>
                        Select a course
                    </option>
                    <?php options($courses); ?>
                </select>
            </label>

            <label for="semester">
                Semester
                <select id="semester" name="semester" required>
                    <option value="" disabled selected>
                        Select a semester
                    </option>
                    <?php options($sems); ?>
                </select>
            </label>

            <button type="submit">Assign teacher</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Teacher assignments</h2>

    <?php if (empty($assignments)): ?>
        <p>No teacher assignments yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Teacher</th>
                        <th>Course</th>
                        <th>Semester</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($assignments as $index => $assignment): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($assignment['teacher']) ?></td>
                            <td><?= e($assignment['course']) ?></td>
                            <td><?= e($assignment['semester']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php footer_ui(); ?>