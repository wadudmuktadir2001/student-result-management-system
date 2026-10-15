<?php

if (!defined('APP_BASE')) {
    http_response_code(403);
    exit('Forbidden');
}

if ($u['role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

header_ui($u, 'Result change history');
?>

<section class="panel">
    <p>
        Every result save records its previous state,
        new state, author and time.
    </p>

    <div class="table-wrap">
        <table>
            <tr>
                <th>Time / changed by</th>
                <th>Student / course</th>
                <th>Before</th>
                <th>After</th>
            </tr>

            <?php foreach (
                rows(
                    'SELECT
                    l.*,
                    u.name actor,
                    s.name student,
                    c.name course
                 FROM audit_logs l
                 JOIN users u ON u.id = l.actor_id
                 JOIN results r ON r.id = l.result_id
                 JOIN users s ON s.id = r.student_id
                 JOIN courses c ON c.id = r.course_id
                 ORDER BY l.id DESC
                 LIMIT 200'
                ) as $r
            ): ?>
                <tr>
                    <td>
                        <?= e($r['created_at']) ?>
                        <small><?= e($r['actor']) ?></small>
                    </td>

                    <td>
                        <?= e($r['student']) ?>
                        <small><?= e($r['course']) ?></small>
                    </td>

                    <td>
                        <pre><?= e($r['old_data'] ?? 'New result') ?></pre>
                    </td>

                    <td>
                        <pre><?= e($r['new_data']) ?></pre>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <p class="muted">Showing the latest 200 changes.</p>
</section>

<?php footer_ui(); ?>