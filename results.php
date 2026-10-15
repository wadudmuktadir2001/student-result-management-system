<?php

require_once __DIR__ . '/config/bootstrap.php';

$u = require_user();

$target = $u['role'] . '/report.php';
$query = http_build_query($_GET);

if ($query !== '') {
    $target .= '?' . $query;
}

redirect($target);
