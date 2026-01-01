<?php
// Add email_settings permission to all roles
// This script can be accessed via browser: http://your-domain/add_email_permission.php

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'internal_portel';

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    // Add email_settings permission for all roles
    $sql = "INSERT INTO permissions (module, role_id, can_access) VALUES 
            ('email_settings', 1, 1),
            ('email_settings', 2, 1),
            ('email_settings', 3, 1),
            ('email_settings', 4, 1)
            ON DUPLICATE KEY UPDATE (module, role_id)";
    
    if ($conn->query($sql)) {
        echo "<h3 style='color: green;'>✅ Email settings permission added successfully!</h3>";
        echo "<p>All roles (Admin, Manager, Lead, Staff) now have access to Email Settings.</p>";
        echo "<p><a href='/'>Go to Dashboard</a> | <a href='/email-settings'>Go to Email Settings</a></p>";
    } else {
        echo "<h3 style='color: red;'>❌ Error adding email settings permission</h3>";
        echo "<p>Error: " . $conn->error . "</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings.</p>";
}
?>
