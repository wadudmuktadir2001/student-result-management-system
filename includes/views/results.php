<?php

if (!defined('APP_BASE')) {
    http_response_code(403);
    exit('Forbidden');
}

if ($u['role'] === 'parent') {
    $children = rows(
        'SELECT s.user_id, u.name
         FROM students s
         JOIN users u ON u.id = s.user_id
         WHERE s.parent_id = ?
         ORDER BY u.name',
        [$u['id']]
    );

    if (!$children) {
        header_ui($u, 'Child results');

        echo '<p class="notice">No child linked to your account.</p>';

        footer_ui();
        exit;
    }

    $default_id = $children[0]['user_id'];
} else {
    $default_id = $u['id'];
}

$id = (int) ($_GET['student'] ?? $default_id);

if (!allowed_student($u, $id)) {
    http_response_code(403);
    exit('Forbidden');
}

$s = q(
    'SELECT u.name, s.roll_no
     FROM students s
     JOIN users u ON u.id = s.user_id
     WHERE s.user_id = ?',
    [$id]
)->fetch();

if (!$s) {
    http_response_code(404);
    exit('Student not found');
}

$all = rows(
    'SELECT
        r.*,
        c.name course,
        c.code,
        c.credits,
        se.name semester
     FROM results r
     JOIN courses c ON c.id = r.course_id
     JOIN semesters se ON se.id = r.semester_id
     WHERE r.student_id = ?
       AND r.published = 1
     ORDER BY se.id, c.code',
    [$id]
);

$sem = (int) ($_GET['semester'] ?? 0);

$rs = $sem
    ? array_values(
        array_filter($all, fn($r) => $r['semester_id'] == $sem)
    )
    : $all;

if (
    $report_mode === 'report' &&
    ($_GET['download'] ?? '') === 'csv'
) {
    header('Content-Type: text/csv; charset=utf-8');

    header(
        'Content-Disposition: attachment; filename="report-card-'
            . $id
            . '.csv"'
    );

    $f = fopen('php://output', 'w');

    fputcsv($f, [
        'Student',
        $s['name'],
        'Roll',
        $s['roll_no'],
    ]);

    fputcsv($f, [
        'Semester',
        'Code',
        'Course',
        'Credits',
        'Marks',
        'Grade',
        'Point',
        'Teacher comment',
    ]);

    foreach ($rs as $r) {
        $safe = fn($v) => preg_match(
            '/^[=+@\-\t\r]/',
            (string) $v
        )
            ? "'" . $v
            : $v;

        fputcsv(
            $f,
            array_map($safe, [
                $r['semester'],
                $r['code'],
                $r['course'],
                $r['credits'],
                $r['marks'],
                grade($r['marks']),
                point($r['marks']),
                $r['comment'],
            ])
        );
    }

    fputcsv($f, [
        'Filtered GPA',
        gpa($rs),
        'CGPA',
        gpa($all),
    ]);

    fclose($f);
    exit;
}

header_ui(
    $u,
    $report_mode === 'history'
        ? 'Result history'
        : 'Academic report card'
);
?>

<section class="panel">
    <div class="section-head">
        <div>
            <h2><?= e($s['name']) ?></h2>
            <p><?= e($s['roll_no']) ?></p>
        </div>

        <?php if ($report_mode === 'report'): ?>
            <div class="actions">
                <a
                    class="button"
                    href="?student=<?= $id ?>&semester=<?= $sem ?>&download=csv">
                    Download CSV
                </a>

                <button onclick="window.print()">
                    Print / Save PDF
                </button>
            </div>
        <?php endif; ?>
    </div>

    <form method="get" class="filter">
        <?php if ($u['role'] === 'parent'): ?>
            <label>
                Child

                <select name="student">
                    <?php foreach ($children as $child): ?>
                        <option
                            value="<?= $child['user_id'] ?>"
                            <?= $id == $child['user_id'] ? 'selected' : '' ?>>
                            <?= e($child['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php else: ?>
            <input
                type="hidden"
                name="student"
                value="<?= $id ?>">
        <?php endif; ?>

        <label>
            Semester

            <select name="semester">
                <option value="0">All semesters</option>

                <?php foreach (
                    rows('SELECT * FROM semesters ORDER BY id') as $se
                ): ?>
                    <option
                        value="<?= $se['id'] ?>"
                        <?= $sem == $se['id'] ? 'selected' : '' ?>>
                        <?= e($se['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <button>Apply</button>
    </form>

    <div class="stats">
        <article>
            <small>
                <?= $sem ? 'Semester GPA' : 'Combined GPA' ?>
            </small>

            <strong><?= gpa($rs) ?></strong>
        </article>

        <article>
            <small>CGPA (all published results)</small>
            <strong><?= gpa($all) ?></strong>
        </article>
    </div>

    <div class="table-wrap">
        <table>
            <tr>
                <th>Semester / Course</th>
                <th>Credit</th>
                <th>Marks</th>
                <th>Grade</th>
                <th>Feedback</th>
                <th>Attendance</th>
            </tr>

            <?php foreach ($rs as $r): ?>
                <?php
                $a = q(
                    "SELECT
                        COUNT(*) total,
                        SUM(status IN ('present', 'late')) attended
                     FROM attendance
                     WHERE student_id = ?
                       AND course_id = ?
                       AND semester_id = ?",
                    [$id, $r['course_id'], $r['semester_id']]
                )->fetch();
                ?>

                <tr>
                    <td>
                        <b><?= e($r['course']) ?></b>

                        <small>
                            <?= e($r['semester']) ?>
                            ·
                            <?= e($r['code']) ?>
                        </small>
                    </td>

                    <td><?= e($r['credits']) ?></td>

                    <td><?= e($r['marks']) ?></td>

                    <td>
                        <span class="badge">
                            <?= grade($r['marks']) ?>
                        </span>
                    </td>

                    <td><?= e($r['comment']) ?></td>

                    <td>
                        <?=
                        $a['total']
                            ? round(
                                $a['attended'] / $a['total'] * 100
                            )
                            . '% ('
                            . $a['attended']
                            . '/'
                            . $a['total']
                            . ')'
                            : 'No records'
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <?php if (!$rs): ?>
        <p>No published results for this selection.</p>
    <?php endif; ?>

    <p class="muted">
        Credit-weighted GPA on a 4.00 scale.
        Attendance includes present and late.
        CGPA includes every published course entry across semesters;
        repeat-course replacement is not applied.
    </p>
</section>

<?php footer_ui(); ?>