<?php
/**
 * Update Users Table Script
 * 
 * This script updates the users table to add the necessary columns for the profile page
 */

// Include database configuration
require_once 'db_config.php';

echo "Starting database update...\n";

// SQL statements to execute
$sqlStatements = [
    // Add bio column if it doesn't exist
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER email",
    
    // Add phone column if it doesn't exist
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER bio",
    
    // Add profile_picture column if it doesn't exist
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER phone"
];

// Execute each SQL statement
foreach ($sqlStatements as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Success: " . $sql . "\n";
    } else {
        echo "Error: " . $sql . "\n" . mysqli_error($conn) . "\n";
    }
}

// Check if profile_image column exists and rename it to profile_picture
$checkColumnSql = "SELECT COUNT(*) as column_exists FROM information_schema.columns 
                  WHERE table_schema = '" . DB_NAME . "' 
                  AND table_name = 'users' 
                  AND column_name = 'profile_image'";

$result = mysqli_query($conn, $checkColumnSql);
$row = mysqli_fetch_assoc($result);

if ($row['column_exists'] > 0) {
    $renameSql = "ALTER TABLE users CHANGE COLUMN profile_image profile_picture VARCHAR(255) NULL";
    
    if (mysqli_query($conn, $renameSql)) {
        echo "Success: Renamed profile_image column to profile_picture\n";
    } else {
        echo "Error renaming column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Note: profile_image column does not exist, no need to rename\n";
}

echo "Database update completed.\n";

// Close connection
mysqli_close($conn);
?> 