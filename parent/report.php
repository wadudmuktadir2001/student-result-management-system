<?php
require_once dirname(__DIR__).'/config/bootstrap.php';
$u=require_role('parent');
$report_mode='report';
require dirname(__DIR__).'/includes/views/results.php';
