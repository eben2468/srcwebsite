<?php
// Script to add profile_picture column to users table
require_once 'db_config.php';

echo "Starting database update...\n";

// List existing columns
echo "Checking existing columns in users table...\n";
$columnsSql = "SHOW COLUMNS FROM users";
$columnsResult = mysqli_query($conn, $columnsSql);

if ($columnsResult) {
    echo "Current columns in users table:\n";
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} else {
    echo "Error fetching columns: " . mysqli_error($conn) . "\n";
}

// Simple direct query to add the column
$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL";

if (mysqli_query($conn, $sql)) {
    echo "Success: Added profile_picture column to users table (or it already exists)\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
    
    // Check if column already exists with a different name (profile_image)
    $checkColumnSql = "SHOW COLUMNS FROM users LIKE 'profile_image'";
    $result = mysqli_query($conn, $checkColumnSql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "Found profile_image column. Renaming to profile_picture...\n";
        
        // Rename the column
        $renameSql = "ALTER TABLE users CHANGE COLUMN profile_image profile_picture VARCHAR(255) NULL";
        
        if (mysqli_query($conn, $renameSql)) {
            echo "Success: Renamed profile_image column to profile_picture\n";
        } else {
            echo "Error renaming column: " . mysqli_error($conn) . "\n";
        }
    }
}

// Also add bio and phone columns if they don't exist
$columns = [
    'bio' => 'ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT NULL',
    'phone' => 'ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL'
];

foreach ($columns as $column => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "Success: Added $column column to users table (or it already exists)\n";
    } else {
        echo "Error adding $column column: " . mysqli_error($conn) . "\n";
    }
}

// List columns after update
echo "\nColumns after update:\n";
$columnsResult = mysqli_query($conn, $columnsSql);

if ($columnsResult) {
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} else {
    echo "Error fetching columns: " . mysqli_error($conn) . "\n";
}

echo "\nDatabase update completed.\n";

// Close connection
mysqli_close($conn);
?> 