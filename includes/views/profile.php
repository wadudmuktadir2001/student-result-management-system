<?php

if (!defined('APP_BASE')) {
    http_response_code(403);
    exit('Forbidden');
}

header_ui($u, 'My profile');
?>

<section class="panel">
    <h2><?= e($u['name']) ?></h2>

    <div class="list-row">
        Email
        <b><?= e($u['email']) ?></b>
    </div>

    <div class="list-row">
        Role
        <b><?= e(ucfirst($u['role'])) ?></b>
    </div>

    <?php if ($u['role'] === 'student'): ?>
        <?php
        $s = q(
            'SELECT s.roll_no, p.name parent
             FROM students s
             LEFT JOIN users p ON p.id = s.parent_id
             WHERE s.user_id = ?',
            [$u['id']]
        )->fetch();
        ?>

        <div class="list-row">
            Roll
            <b><?= e($s['roll_no'] ?? '—') ?></b>
        </div>

        <div class="list-row">
            Parent
            <b><?= e($s['parent'] ?? 'Not linked') ?></b>
        </div>
    <?php endif; ?>

    <p>Contact your administrator to update account details.</p>
</section>

<?php footer_ui(); ?>