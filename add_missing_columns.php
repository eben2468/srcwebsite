<?php
// Simple script to add missing columns
require_once 'db_config.php';

echo "Adding missing columns to users table...\n";

// Simple direct SQL to add the columns
$sql = "
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS bio TEXT NULL,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL
";

if (mysqli_query($conn, $sql)) {
    echo "Success: Added missing columns\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

// Check the current structure of the users table
$columnsSql = "DESCRIBE users";
$columnsResult = mysqli_query($conn, $columnsSql);

if ($columnsResult) {
    echo "\nCurrent structure of users table:\n";
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} else {
    echo "Error fetching table structure: " . mysqli_error($conn) . "\n";
}

echo "\nDone.\n";

// Close connection
mysqli_close($conn);
?> 