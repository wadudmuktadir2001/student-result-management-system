<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('teacher');

$reports = rows(
    'SELECT
        c.name AS course,
        se.name AS semester,
        COUNT(r.id) AS entries,
        AVG(r.marks) AS average,
        SUM(r.marks >= 40) AS passed
     FROM assignments a
     JOIN courses c ON c.id = a.course_id
     JOIN semesters se ON se.id = a.semester_id
     LEFT JOIN results r
        ON r.course_id = a.course_id
        AND r.semester_id = a.semester_id
     WHERE a.teacher_id = ?
     GROUP BY c.id, c.name, se.id, se.name
     ORDER BY se.id DESC, c.name',
    [$u['id']]
);

header_ui($u, 'Class performance report');
?>

<section class="panel">
    <div class="actions">
        <button type="button" onclick="window.print()">
            Print / Save PDF
        </button>
    </div>

    <p>
        Includes draft and published marks for your assigned courses.
    </p>

    <?php if (empty($reports)): ?>
        <p class="notice">No courses assigned yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Entries</th>
                        <th>Average</th>
                        <th>Passed</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= e($report['course']) ?></td>
                            <td><?= e($report['semester']) ?></td>
                            <td><?= e($report['entries']) ?></td>

                            <td>
                                <?= $report['average'] !== null
                                    ? e(number_format(
                                        (float) $report['average'],
                                        2
                                    ))
                                    : '—' ?>
                            </td>

                            <td><?= e($report['passed'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php footer_ui(); ?>