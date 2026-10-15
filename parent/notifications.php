<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('parent');

$notifications = rows(
    'SELECT message, created_at
     FROM notifications
     WHERE parent_id = ?
     ORDER BY id DESC',
    [$u['id']]
);

header_ui($u, 'Notifications');
?>

<section class="panel">
    <h2>Notifications</h2>

    <?php if (empty($notifications)): ?>
        <p>No notifications yet.</p>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="list-row">
                <span>
                    <?= e($notification['message']) ?>

                    <small>
                        <?= e($notification['created_at']) ?>
                    </small>
                </span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php footer_ui(); ?>