<?php
require_once __DIR__ . '/config/bootstrap.php';

$u = require_user();

$target = $u['role'] === 'admin'
    ? 'admin/audit.php'
    : $u['role'] . '/dashboard.php';

redirect($target);
