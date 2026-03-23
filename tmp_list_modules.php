<?php
define('BASEPATH', 'tmp');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'application/config/database.php';
$db_cfg = $db['default'];
$mysqli = new mysqli($db_cfg['hostname'], $db_cfg['username'], $db_cfg['password'], $db_cfg['database']);
if ($mysqli->connect_error) { die("Connect Error: " . $mysqli->connect_error); }
$res = $mysqli->query("SELECT DISTINCT module FROM permissions");
if (!$res) { die("Query Error: " . $mysqli->error); }
$modules = [];
while ($row = $res->fetch_assoc()) { $modules[] = $row['module']; }
echo json_encode($modules);
