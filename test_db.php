<?php
$conn = new mysqli('localhost', 'root', '', 'admin_stadmin_internal_portal');

echo "EMPLOYEES:\n";
$res2 = $conn->query("SHOW COLUMNS FROM employees");
if ($res2) {
    while($row = $res2->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
echo "\nUSERS:\n";
$res3 = $conn->query("SHOW COLUMNS FROM users");
if ($res3) {
    while($row = $res3->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
}
