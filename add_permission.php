<?php
// Connect to DB (using standard WAMP creds)
$link = mysqli_connect('localhost', 'root', '', 'official_internal_portel');
if (!$link) { die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error()); }

$role_id = 1; // Admin
$modules_to_add = ['expenses', 'expenses_approve', 'expenses_reimburse', 'expenses_reports'];

foreach ($modules_to_add as $module) {
    // Check if exists
    $query = "SELECT id FROM permissions WHERE role_id=$role_id AND module='$module'";
    $result = mysqli_query($link, $query);

    if (mysqli_num_rows($result) == 0) {
        echo "Inserting permission '$module' for Admin...\n";
        $sql = "INSERT INTO permissions (role_id, module, can_access) VALUES ($role_id, '$module', 1)";
        if (mysqli_query($link, $sql)) {
            echo "Success: Admin granted access to $module.\n";
        } else {
            echo "Error: " . mysqli_error($link) . "\n";
        }
    } else {
        echo "Permission '$module' already exists for Admin.\n";
        // Ensure it's set to 1
        mysqli_query($link, "UPDATE permissions SET can_access=1 WHERE role_id=$role_id AND module='$module'");
        echo "Ensured access is enabled.\n";
    }
}

mysqli_close($link);
?>
