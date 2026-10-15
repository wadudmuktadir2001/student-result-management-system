
<?php

require_once dirname(__DIR__) . '/config/bootstrap.php';

$u = require_role('teacher');

require dirname(__DIR__) . '/includes/views/grade_components.php';
