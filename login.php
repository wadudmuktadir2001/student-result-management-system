<?php
require_once __DIR__ . '/bootstrap.php';

if (!empty($_SESSION['uid'])) {
    redirect('index.php');
    exit;
}

$err = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    guard_csrf();

    $emailInput = $_POST['email'] ?? '';
    $passwordInput = $_POST['password'] ?? '';

    $email = is_string($emailInput) ? trim($emailInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    $retryAfter = (int) ($_SESSION['retry_after'] ?? 0);
    $remaining = max(0, $retryAfter - time());

    if ($remaining > 0) {
        $err = "Please wait {$remaining} seconds before trying again.";
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $password === ''
    ) {
        $err = 'Please enter a valid email and password.';
    } else {
        if ($retryAfter > 0) {
            unset($_SESSION['retry_after'], $_SESSION['attempts']);
        }

        $user = q(
            'SELECT id, password FROM users WHERE email = ? LIMIT 1',
            [$email]
        )->fetch();

        if (
            $user &&
            password_verify($password, (string) $user['password'])
        ) {
            session_regenerate_id(true);

            $_SESSION['uid'] = (int) $user['id'];

            unset(
                $_SESSION['csrf'],
                $_SESSION['attempts'],
                $_SESSION['retry_after']
            );

            redirect('index.php');
            exit;
        }

        $_SESSION['attempts'] =
            (int) ($_SESSION['attempts'] ?? 0) + 1;

        if ($_SESSION['attempts'] >= 5) {
            $_SESSION['retry_after'] = time() + 30;
            $_SESSION['attempts'] = 0;

            $err = 'Too many failed attempts. Please wait 30 seconds.';
        } else {
            $err = 'Email or password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title> Sign in</title>

    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/login.css">


</head>

<body class="login">

    <section class="login-form">
        <small>WELCOME BACK</small>

        <h1>Sign in to your workspace</h1>

        <p>Use the account provided by your administrator.</p>

        <?php if ($err !== ''): ?>
            <p class="notice error" role="alert">
                <?= e($err) ?>
            </p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrf() ?>

            <label for="email">
                Email address
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?= e($email) ?>"
                    placeholder="Enter your email"
                    maxlength="190"
                    autocomplete="username"
                    required>
            </label>

            <label for="password">
                Password
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required>
            </label>

            <button type="submit">
                Sign in →
            </button>
        </form>


    </section>

</body>

</html>