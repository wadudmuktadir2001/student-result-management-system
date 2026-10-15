<?php
require_once __DIR__ . '/config/bootstrap.php';

$u = require_role('admin');

redirect('admin/dashboard.php');
