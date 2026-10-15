<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('admin');
$admin_page = 'enrollments';

require dirname(__DIR__) . '/includes/admin_handler.php';

$students = array_filter(
    $users,
    fn($user) => $user['role'] === 'student'
);

header_ui($u, 'Student enrollments');
?>

<section class="panel">
    <h2>Enroll student</h2>

    <form method="post">
        <?= csrf() ?>

        <input type="hidden" name="action" value="enroll">

        <label for="student">
            Student
            <select id="student" name="student" required>
                <option value="" disabled selected>
                    Select a student
                </option>

                <?php options($students); ?>
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

        <button type="submit">Enroll student</button>
    </form>
</section>

<?php footer_ui(); ?>