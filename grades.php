<?php

require_once __DIR__ . '/config/bootstrap.php';

$u = require_user();

if ($u['role'] === 'admin') {
    $target = 'admin/results.php';
} elseif ($u['role'] === 'teacher') {
    $target = 'teacher/grades.php';
} else {
    $target = $u['role'] . '/dashboard.php';
}

redirect($target);
