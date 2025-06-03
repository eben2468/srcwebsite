<?php
// Script to migrate data from profile_image to profile_picture and drop the profile_image column
require_once 'db_config.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting profile image migration...\n";

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Database connection successful.\n";

// Check if users table exists
$tableCheckSql = "SHOW TABLES LIKE 'users'";
$tableResult = mysqli_query($conn, $tableCheckSql);

if (!$tableResult) {
    die("Error checking tables: " . mysqli_error($conn));
}

if (mysqli_num_rows($tableResult) == 0) {
    die("Error: The users table does not exist!");
}

echo "Users table exists.\n";

// Check if both columns exist
$checkImageSql = "SHOW COLUMNS FROM users LIKE 'profile_image'";
$checkPictureSql = "SHOW COLUMNS FROM users LIKE 'profile_picture'";

$imageResult = mysqli_query($conn, $checkImageSql);
if (!$imageResult) {
    die("Error checking profile_image column: " . mysqli_error($conn));
}

$pictureResult = mysqli_query($conn, $checkPictureSql);
if (!$pictureResult) {
    die("Error checking profile_picture column: " . mysqli_error($conn));
}

$hasProfileImage = mysqli_num_rows($imageResult) > 0;
$hasProfilePicture = mysqli_num_rows($pictureResult) > 0;

echo "Profile image column exists: " . ($hasProfileImage ? "Yes" : "No") . "\n";
echo "Profile picture column exists: " . ($hasProfilePicture ? "Yes" : "No") . "\n";

if ($hasProfileImage && $hasProfilePicture) {
    // Copy data from profile_image to profile_picture where profile_picture is NULL
    echo "Copying data from profile_image to profile_picture...\n";
    $copySql = "UPDATE users SET profile_picture = profile_image WHERE profile_picture IS NULL AND profile_image IS NOT NULL";
    
    if (mysqli_query($conn, $copySql)) {
        $affectedRows = mysqli_affected_rows($conn);
        echo "Success: Copied data for $affectedRows users\n";
        
        // Drop the profile_image column
        echo "Dropping profile_image column...\n";
        $dropSql = "ALTER TABLE users DROP COLUMN profile_image";
        
        if (mysqli_query($conn, $dropSql)) {
            echo "Success: Dropped profile_image column\n";
        } else {
            echo "Error dropping column: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Error copying data: " . mysqli_error($conn) . "\n";
    }
} elseif ($hasProfileImage && !$hasProfilePicture) {
    // Rename profile_image to profile_picture
    echo "Renaming profile_image to profile_picture...\n";
    $renameSql = "ALTER TABLE users CHANGE COLUMN profile_image profile_picture VARCHAR(255) NULL";
    
    if (mysqli_query($conn, $renameSql)) {
        echo "Success: Renamed profile_image column to profile_picture\n";
    } else {
        echo "Error renaming column: " . mysqli_error($conn) . "\n";
    }
} elseif (!$hasProfileImage && !$hasProfilePicture) {
    // Add profile_picture column
    echo "Adding profile_picture column...\n";
    $addSql = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) NULL";
    
    if (mysqli_query($conn, $addSql)) {
        echo "Success: Added profile_picture column\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "No migration needed. Only profile_picture column exists.\n";
}

// List all columns in the users table
echo "\nListing all columns in users table:\n";
$columnsSql = "SHOW COLUMNS FROM users";
$columnsResult = mysqli_query($conn, $columnsSql);

if ($columnsResult) {
    if (mysqli_num_rows($columnsResult) > 0) {
        while ($column = mysqli_fetch_assoc($columnsResult)) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "No columns found in users table.\n";
    }
} else {
    echo "Error fetching columns: " . mysqli_error($conn) . "\n";
}

echo "\nMigration completed.\n";

// Close connection
mysqli_close($conn);
?> 