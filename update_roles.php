<?php
/**
 * Script to update the users table to support Administrator, Member, and Student roles
 */

// Include database configuration
require_once 'db_config.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Starting database update...\n";
echo "Database: " . $dbname . "\n";

// Check if the users table exists
$checkTableSql = "SHOW TABLES LIKE 'users'";
$tableResult = mysqli_query($conn, $checkTableSql);

if (mysqli_num_rows($tableResult) == 0) {
    die("Error: users table does not exist in the database.\n");
}

echo "Users table exists. Proceeding with update...\n";

// Check if the role column exists
$checkColumnSql = "SHOW COLUMNS FROM users LIKE 'role'";
$result = mysqli_query($conn, $checkColumnSql);

if (mysqli_num_rows($result) > 0) {
    echo "Role column exists. Modifying to support new roles...\n";
    
    // Get current column definition
    $columnInfo = mysqli_fetch_assoc($result);
    echo "Current role column type: " . $columnInfo['Type'] . "\n";
    
    // Modify the role column to support the three roles
    $alterSql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'member', 'student') NOT NULL DEFAULT 'student'";
    if (mysqli_query($conn, $alterSql)) {
        echo "Role column modified successfully.\n";
    } else {
        echo "Error modifying role column: " . mysqli_error($conn) . "\n";
    }
    
    // Update any existing users with 'user' role to 'student'
    $updateSql = "UPDATE users SET role = 'student' WHERE role = 'user'";
    if (mysqli_query($conn, $updateSql)) {
        $rowsAffected = mysqli_affected_rows($conn);
        echo "Updated $rowsAffected users from 'user' role to 'student' role.\n";
    } else {
        echo "Error updating users: " . mysqli_error($conn) . "\n";
    }
    
    // Add a comment to the table to document the role types
    $commentSql = "ALTER TABLE users COMMENT = 'User accounts with roles: admin (Administrator), member (SRC Member), student (Regular Student)'";
    if (mysqli_query($conn, $commentSql)) {
        echo "Table comment added successfully.\n";
    } else {
        echo "Error adding table comment: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Role column does not exist. Creating it...\n";
    
    // Create the role column
    $createSql = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'member', 'student') NOT NULL DEFAULT 'student' AFTER email";
    if (mysqli_query($conn, $createSql)) {
        echo "Role column created successfully.\n";
    } else {
        echo "Error creating role column: " . mysqli_error($conn) . "\n";
    }
}

// Verify the changes
$verifySql = "SHOW COLUMNS FROM users LIKE 'role'";
$verifyResult = mysqli_query($conn, $verifySql);

if (mysqli_num_rows($verifyResult) > 0) {
    $columnInfo = mysqli_fetch_assoc($verifyResult);
    echo "Verification: role column type is now: " . $columnInfo['Type'] . "\n";
} else {
    echo "Verification failed: role column not found after update.\n";
}

echo "Database update completed.\n";

// Close connection
mysqli_close($conn);
?> 