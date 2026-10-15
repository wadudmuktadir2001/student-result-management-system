<?php require_once __DIR__.'/config/bootstrap.php';$u=require_user();redirect($u['role'].'/dashboard.php');
