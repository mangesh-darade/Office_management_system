<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/Office_management_system/index.php';
$_SERVER['REQUEST_URI'] = '/Office_management_system/my-works/quick-add';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'off';

define('ENVIRONMENT', 'development');
chdir(__DIR__);
require_once __DIR__ . '/index.php';
