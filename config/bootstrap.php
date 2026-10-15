<?php

$scriptDir = str_replace(
    '\\',
    '/',
    dirname($_SERVER['SCRIPT_NAME'] ?? '/')
);

if (in_array(
    basename($scriptDir),
    ['admin', 'teacher', 'student', 'parent'],
    true
)) {
    $scriptDir = dirname($scriptDir);
}

define(
    'APP_BASE',
    rtrim($scriptDir === '.' ? '' : $scriptDir, '/')
);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
]);

$c = require __DIR__ . '/database.php';

try {
    $db = new PDO(
        "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
        $c['user'],
        $c['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(503);
    exit('Database connection failed. Import database.sql and check config/database.php.');
}

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function q($sql, $args = [])
{
    global $db;

    $statement = $db->prepare($sql);
    $statement->execute($args);

    return $statement;
}

function rows($sql, $args = [])
{
    return q($sql, $args)->fetchAll();
}

function url($path = '')
{
    return APP_BASE . '/' . ltrim($path, '/');
}

function redirect($to = 'index.php')
{
    header('Location: ' . url($to));
    exit;
}

function require_user()
{
    if (empty($_SESSION['uid'])) {
        redirect('login.php');
    }

    $user = q(
        'SELECT * FROM users WHERE id = ?',
        [$_SESSION['uid']]
    )->fetch();

    if (!$user) {
        unset($_SESSION['uid']);
        redirect('login.php');
    }

    return $user;
}

function require_role($role)
{
    $user = require_user();

    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('Forbidden');
    }

    return $user;
}

function csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf" value="'
        . e($_SESSION['csrf'])
        . '">';
}

function guard_csrf()
{
    $sessionToken = $_SESSION['csrf'] ?? '';
    $requestToken = $_POST['csrf'] ?? '';

    if (
        !is_string($sessionToken)
        || !is_string($requestToken)
        || $sessionToken === ''
        || !hash_equals($sessionToken, $requestToken)
    ) {
        http_response_code(403);
        exit('Invalid request token');
    }
}

function point($marks)
{
    if ($marks >= 80) return 4;
    if ($marks >= 75) return 3.75;
    if ($marks >= 70) return 3.5;
    if ($marks >= 65) return 3.25;
    if ($marks >= 60) return 3;
    if ($marks >= 55) return 2.75;
    if ($marks >= 50) return 2.5;
    if ($marks >= 45) return 2.25;
    if ($marks >= 40) return 2;

    return 0;
}

function grade($marks)
{
    if ($marks >= 80) return 'A';
    if ($marks >= 75) return 'A-';
    if ($marks >= 70) return 'B+';
    if ($marks >= 65) return 'B-';
    if ($marks >= 60) return 'B';
    if ($marks >= 55) return 'C+';
    if ($marks >= 50) return 'C';
    if ($marks >= 45) return 'D+';
    if ($marks >= 40) return 'D';

    return 'F';
}

function gpa($results)
{
    $totalPoints = 0;
    $totalCredits = 0;

    foreach ($results as $result) {
        $totalPoints += point($result['marks']) * $result['credits'];
        $totalCredits += $result['credits'];
    }

    return $totalCredits
        ? number_format($totalPoints / $totalCredits, 2)
        : '—';
}

function allowed_student($user, $id)
{
    if ($user['role'] === 'admin') {
        return true;
    }

    if ($user['role'] === 'student') {
        return $user['id'] == $id;
    }

    if ($user['role'] === 'parent') {
        return (bool) q(
            'SELECT 1 FROM students WHERE user_id = ? AND parent_id = ?',
            [$id, $user['id']]
        )->fetchColumn();
    }

    return false;
}

function teaching($user, $course, $semester)
{
    if ($user['role'] === 'admin') {
        return true;
    }

    if ($user['role'] !== 'teacher') {
        return false;
    }

    return (bool) q(
        'SELECT 1 FROM assignments
         WHERE teacher_id = ? AND course_id = ? AND semester_id = ?',
        [$user['id'], $course, $semester]
    )->fetchColumn();
}

function header_ui($user, $title)
{
    $menus = [
        'admin' => [
            'dashboard.php' => 'Overview',
            'users.php' => 'Users',
            'courses.php' => 'Courses',
            'semesters.php' => 'Semesters',
            'assignments.php' => 'Teacher assignments',
            'enrollments.php' => 'Enrollments',
            'parents.php' => 'Parent links',
            'results.php' => 'Results',
            'attendance.php' => 'Attendance',
            'audit.php' => 'Change history',
        ],
        'teacher' => [
            'dashboard.php' => 'Overview',
            'grades.php' => 'Grades',
            'attendance.php' => 'Attendance',
            'report.php' => 'Class report',
        ],
        'student' => [
            'dashboard.php' => 'Overview',
            'history.php' => 'Result history',
            'report.php' => 'Report card',
            'profile.php' => 'Profile',
        ],
        'parent' => [
            'dashboard.php' => 'Overview',
            'history.php' => 'Child result history',
            'report.php' => 'Report card',
            'notifications.php' => 'Notifications',
            'profile.php' => 'Profile',
        ],
    ];

    $role = $user['role'];

    if (!isset($menus[$role])) {
        http_response_code(403);
        exit('Forbidden');
    }

    echo '<!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>' . e($title) . '</title>
        <link rel="stylesheet" href="'
        . e(url('assets/style.css')) . '">
    </head>
    <body>
        <aside>
            <a class="brand" href="'
        . e(url($role . '/dashboard.php')) . '">
                <span>Welcome</span>
            </a>
            <small>ACADEMIC WORKSPACE</small>
            <nav>';

    foreach ($menus[$role] as $file => $label) {
        echo '<a href="'
            . e(url($role . '/' . $file))
            . '">'
            . e($label)
            . '</a>';
    }

    echo '</nav>
        <div class="profile">'
        . e($user['name']) . '<br>
            <small>' . e(ucfirst($role)) . '</small>
            <form action="' . e(url('logout.php')) . '" method="post">'
        . csrf() . '
                <button type="submit" class="text">Sign out</button>
            </form>
        </div>
    </aside>
    <main>
        <header>
            <div>
                <small>STUDENT RESULT MANAGEMENT</small>
                <h1>' . e($title) . '</h1>
            </div>
            <span class="badge">'
        . e(ucfirst($role)) . ' workspace
            </span>
        </header>';

    if (isset($_SESSION['flash'])) {
        echo '<p class="notice">' . e($_SESSION['flash']) . '</p>';
        unset($_SESSION['flash']);
    }
}

function footer_ui()
{
    echo '</main></body></html>';
}
