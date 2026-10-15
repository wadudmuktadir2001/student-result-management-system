<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('admin');
$admin_page = 'courses';

require dirname(__DIR__) . '/includes/admin_handler.php';

$courseList = rows(
    'SELECT code, name, credits
     FROM courses
     ORDER BY code, name'
);

header_ui($u, 'Courses');
?>

<section class="panel">
    <h2>Add course</h2>

    <form method="post">
        <?= csrf() ?>

        <input type="hidden" name="action" value="course">

        <label for="course-code">
            Course code
            <input
                id="course-code"
                name="code"
                type="text"
                placeholder="Example: CSE101"
                required>
        </label>

        <label for="course-name">
            Course name
            <input
                id="course-name"
                name="name"
                type="text"
                placeholder="Example: Introduction to Programming"
                required>
        </label>

        <label for="course-credits">
            Credits
            <input
                id="course-credits"
                name="credits"
                type="number"
                min="0.1"
                max="30"
                step="0.1"
                value="3"
                required>
        </label>

        <button type="submit">Add course</button>
    </form>
</section>

<section class="panel">
    <div class="section-head">
        <h2>Course list</h2>

        <span class="badge">
            <?= count($courseList) ?> courses
        </span>
    </div>

    <?php if (empty($courseList)): ?>
        <p class="notice">No courses added yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course code</th>
                        <th>Course name</th>
                        <th>Credits</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($courseList as $index => $course): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= e($course['code']) ?></td>
                            <td><?= e($course['name']) ?></td>
                            <td><?= e($course['credits']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php footer_ui(); ?>