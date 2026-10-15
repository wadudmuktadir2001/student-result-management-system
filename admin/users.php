<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('admin');
$admin_page = 'users';

require dirname(__DIR__) . '/includes/admin_handler.php';

header_ui($u, 'User accounts');
?>

<section class="panel">
    <h2>Create account</h2>

    <form method="post">
        <?= csrf() ?>

        <input type="hidden" name="action" value="user">

        <label for="full-name">
            Full name
            <input
                id="full-name"
                name="name"
                type="text"
                maxlength="120"
                autocomplete="name"
                required>
        </label>

        <label for="email">
            Email
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                required>
        </label>

        <label for="password">
            Password (10+ characters)
            <input
                id="password"
                name="password"
                type="password"
                minlength="10"
                autocomplete="new-password"
                required>
        </label>

        <label for="role">
            Role
            <select id="role" name="role" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="parent">Parent</option>
                <option value="admin">Admin</option>
            </select>
        </label>

        <label for="roll">
            Roll number (required for student)
            <input
                id="roll"
                name="roll"
                type="text"
                maxlength="50">
        </label>

        <button type="submit">Create account</button>
    </form>
</section>

<section class="panel">
    <h2>Accounts</h2>

    <?php if (empty($users)): ?>
        <p class="notice">No accounts found.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['name']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e(ucfirst($user['role'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php footer_ui(); ?>