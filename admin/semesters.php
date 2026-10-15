<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('admin');
$admin_page = 'semesters';

require dirname(__DIR__) . '/includes/admin_handler.php';

header_ui($u, 'Semesters');
?>

<section class="panel">
    <h2>Add semester</h2>

    <form method="post">
        <?= csrf() ?>

        <input type="hidden" name="action" value="semester">

        <label for="semester-name">
            Semester name
            <input
                id="semester-name"
                name="name"
                type="text"
                placeholder="Example: Spring 2026"
                required>
        </label>

        <button type="submit">Add semester</button>
    </form>
</section>

<?php footer_ui(); ?>