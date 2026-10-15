<?php

if (!defined('APP_BASE')) {
    http_response_code(403);
    exit('Forbidden');
}

header_ui($u, 'Good day, ' . explode(' ', $u['name'])[0]);

if (in_array($u['role'], ['admin', 'teacher'])) {
    $filter = $u['role'] === 'teacher'
        ? ' WHERE EXISTS(
                SELECT 1
                FROM assignments a
                WHERE a.teacher_id = ?
                  AND a.course_id = r.course_id
                  AND a.semester_id = r.semester_id
            )'
        : '';

    $args = $u['role'] === 'teacher' ? [$u['id']] : [];

    $rs = rows(
        'SELECT r.*, c.credits
         FROM results r
         JOIN courses c ON c.id = r.course_id' . $filter,
        $args
    );

    $published = array_filter($rs, fn($r) => $r['published']);

    $avg = count($rs)
        ? number_format(
            array_sum(array_column($rs, 'marks')) / count($rs),
            1
        )
        : '—';

    echo '<p class="subtitle">
            Your campus performance at a glance.
            A clear view of what matters.
          </p>';

    echo '<div class="stats">';

    foreach (
        [
            'Result entries' => count($rs),
            'Average marks' => $avg,
            'Published' => count($published),
            'Draft results' => count($rs) - count($published),
        ] as $k => $v
    ) {
        echo '<article>
                <small>' . e($k) . '</small>
                <strong>' . e($v) . '</strong>
              </article>';
    }

    echo '</div>';

    echo '<div class="grid">
            <section class="panel">
                <h2>Grade distribution</h2>';

    $bins = [];

    foreach ($rs as $r) {
        $g = grade($r['marks']);
        $bins[$g] = ($bins[$g] ?? 0) + 1;
    }

    foreach ($bins as $g => $n) {
        echo '<div class="bar-row">
                <span>' . e($g) . '</span>
                <div class="track">
                    <div style="width:'
            . ($n / max(1, count($rs)) * 100)
            . '%"></div>
                </div>
                <b>' . $n . '</b>
              </div>';
    }

    if (!$bins) {
        echo '<p>No results yet.</p>';
    }

    echo '</section>
          <section class="panel">
              <h2>Course averages</h2>';

    $sql = 'SELECT c.name, AVG(r.marks) average
            FROM results r
            JOIN courses c ON c.id = r.course_id'
        . $filter
        . ' GROUP BY c.id, c.name';

    foreach (rows($sql, $args) as $r) {
        echo '<div class="list-row">
                <span>' . e($r['name']) . '</span>
                <b>' . number_format($r['average'], 1) . '%</b>
              </div>';
    }

    echo '</section>
          </div>
          <section class="panel">
              <h2>Student directory</h2>
              <div class="table-wrap">
                  <table>
                      <tr>
                          <th>Student</th>
                          <th>Roll</th>
                          <th>Action</th>
                      </tr>';

    $ss = $u['role'] === 'admin'
        ? rows(
            'SELECT s.*, u.name
             FROM students s
             JOIN users u ON u.id = s.user_id'
        )
        : rows(
            'SELECT DISTINCT s.*, u.name
             FROM students s
             JOIN users u ON u.id = s.user_id
             JOIN enrollments en ON en.student_id = s.user_id
             JOIN assignments a
               ON a.course_id = en.course_id
              AND a.semester_id = en.semester_id
             WHERE a.teacher_id = ?',
            [$u['id']]
        );

    foreach ($ss as $s) {
        echo '<tr>
                <td>' . e($s['name']) . '</td>
                <td>' . e($s['roll_no']) . '</td>
                <td>
                    <a href="'
            . (
                $u['role'] === 'admin'
                ? url('admin/report.php?student=' . $s['user_id'])
                : url('teacher/grades.php')
            )
            . '">'
            . (
                $u['role'] === 'admin'
                ? 'View report'
                : 'Manage grades'
            )
            . '</a>
                </td>
              </tr>';
    }

    echo '</table>
          </div>
          </section>';
} else {
    $ss = $u['role'] === 'student'
        ? rows(
            'SELECT s.*, u.name
             FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.user_id = ?',
            [$u['id']]
        )
        : rows(
            'SELECT s.*, u.name
             FROM students s
             JOIN users u ON u.id = s.user_id
             WHERE s.parent_id = ?',
            [$u['id']]
        );

    foreach ($ss as $s) {
        $rs = rows(
            'SELECT r.*, c.credits
             FROM results r
             JOIN courses c ON c.id = r.course_id
             WHERE r.student_id = ?
               AND r.published = 1',
            [$s['user_id']]
        );

        echo '<section class="panel">
                <h2>' . e($s['name']) . '</h2>
                <p>'
            . e($s['roll_no'])
            . ' · Published academic history
                </p>
                <div class="stats">
                    <article>
                        <small>CGPA</small>
                        <strong>' . gpa($rs) . '</strong>
                    </article>
                    <article>
                        <small>Published courses</small>
                        <strong>' . count($rs) . '</strong>
                    </article>
                </div>
                <h3>Semester progress</h3>';

        foreach (
            rows(
                'SELECT DISTINCT se.id, se.name
             FROM semesters se
             JOIN results r ON r.semester_id = se.id
             WHERE r.student_id = ?
               AND r.published = 1
             ORDER BY se.id',
                [$s['user_id']]
            ) as $se
        ) {
            $sr = array_filter(
                $rs,
                fn($r) => $r['semester_id'] == $se['id']
            );

            $g = gpa($sr);

            echo '<div class="bar-row">
                    <span>' . e($se['name']) . '</span>
                    <div class="track">
                        <div style="width:'
                . ((float) $g / 4 * 100)
                . '%"></div>
                    </div>
                    <b>' . $g . '</b>
                  </div>';
        }

        echo '<a class="button" href="report.php?student='
            . $s['user_id']
            . '">View results & report card →</a>
              </section>';
    }

    if (!$ss) {
        echo '<p class="notice">
                No student has been linked to this account yet.
              </p>';
    }
}

footer_ui();
