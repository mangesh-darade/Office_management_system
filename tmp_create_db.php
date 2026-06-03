<?php
mysqli_report(MYSQLI_REPORT_OFF);
$c = mysqli_connect('localhost', 'root', '');
if (!$c) {
    die('MySQL connect failed: ' . mysqli_connect_error() . "\n");
}
$db = 'siteadmin_eergicfoundation';
$sql = 'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $db) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';
if (mysqli_query($c, $sql)) {
    echo "Created database: $db\n";
} else {
    echo 'Error: ' . mysqli_error($c) . "\n";
    exit(1);
}
$check = mysqli_select_db($c, $db);
echo $check ? "Verified access to $db\n" : ('Verify failed: ' . mysqli_error($c) . "\n");
