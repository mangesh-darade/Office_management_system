<?php
// Add email_settings permission to all roles
require_once 'database.php';

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'internal_portel';

$conn = new mysqli($host, $user, $pass, $dbname);

// Add email_settings permission for all roles
$sql = "INSERT INTO permissions (module, role_id, can_access) VALUES ('email_settings', 1, 1), ('email_settings', 2, 1), ('email_settings', 3, 1), ('email_settings', 4, 1) ON DUPLICATE KEY UPDATE (module, role_id)";

if ($conn->query($sql)) {
    echo "Email settings permission added successfully\n";
} else {
    echo "Error adding email settings permission: " . $conn->error . "\n";
}

$conn->close();
?>
