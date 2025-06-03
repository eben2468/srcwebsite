<?php
// Simple direct approach to fix the issue
require_once 'db_config.php';

echo "Starting fix...\n";

// First add the profile_picture column directly
$sql1 = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL";
echo "Adding profile_picture column...\n";
mysqli_query($conn, $sql1);
echo "Done (errors are ok if column already exists).\n";

// Then add bio column
$sql2 = "ALTER TABLE users ADD COLUMN bio TEXT NULL";
echo "Adding bio column...\n";
mysqli_query($conn, $sql2);
echo "Done (errors are ok if column already exists).\n";

// Then add phone column
$sql3 = "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL";
echo "Adding phone column...\n";
mysqli_query($conn, $sql3);
echo "Done (errors are ok if column already exists).\n";

echo "Fix completed.\n";

// Close connection
mysqli_close($conn);
?> 