<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('admin');
$admin_page = 'parents';

require dirname(__DIR__) . '/includes/admin_handler.php';

$students = array_filter(
    $users,
    fn($user) => $user['role'] === 'student'
);

$parents = array_filter(
    $users,
    fn($user) => $user['role'] === 'parent'
);

header_ui($u, 'Parent links');
?>

<section class="panel">
    <h2>Link parent</h2>

    <form method="post">
        <?= csrf() ?>

        <input type="hidden" name="action" value="parent">

        <label for="student">
            Student
            <select id="student" name="student" required>
                <option value="" disabled selected>
                    Select a student
                </option>

                <?php options($students); ?>
            </select>
        </label>

        <label for="parent">
            Parent
            <select id="parent" name="parent" required>
                <option value="" disabled selected>
                    Select a parent or unlink
                </option>

                <option value="0">No parent / unlink</option>

                <?php options($parents); ?>
            </select>
        </label>

        <button type="submit">Save parent link</button>
    </form>
</section>

<?php footer_ui(); ?>