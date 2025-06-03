<?php
// Include database configuration
require_once 'db_config.php';

// Output as plain text for clarity
header('Content-Type: text/plain');

echo "Starting database fix...\n\n";

// Check connection
if (!$conn) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

echo "Database connection successful.\n";

// Get the constraint name
$query = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
          WHERE TABLE_SCHEMA = 'src_management_system' 
          AND TABLE_NAME = 'feedback' 
          AND COLUMN_NAME = 'assigned_to' 
          AND REFERENCED_TABLE_NAME = 'users'";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $constraintName = $row['CONSTRAINT_NAME'];
    
    echo "Found foreign key constraint: $constraintName\n";
    
    // Drop the foreign key constraint
    $dropFkSQL = "ALTER TABLE feedback DROP FOREIGN KEY $constraintName";
    
    if (mysqli_query($conn, $dropFkSQL)) {
        echo "Successfully dropped foreign key constraint.\n";
    } else {
        echo "Error dropping foreign key constraint: " . mysqli_error($conn) . "\n";
        exit;
    }
} else {
    echo "No foreign key constraint found on assigned_to column.\n";
}

// Modify the column to be VARCHAR(100)
$alterColumnSQL = "ALTER TABLE feedback MODIFY assigned_to VARCHAR(100) NULL";

if (mysqli_query($conn, $alterColumnSQL)) {
    echo "Successfully modified assigned_to column to VARCHAR(100).\n";
} else {
    echo "Error modifying assigned_to column: " . mysqli_error($conn) . "\n";
    exit;
}

// Verify the changes
echo "\nVerifying table structure:\n";
$verifySQL = "DESCRIBE feedback";
$result = mysqli_query($conn, $verifySQL);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Field'] === 'assigned_to') {
            echo "assigned_to column type: " . $row['Type'] . ", Null: " . $row['Null'] . ", Key: " . $row['Key'] . "\n";
        }
    }
} else {
    echo "Error verifying table structure: " . mysqli_error($conn) . "\n";
}

echo "\nFix completed successfully.\n";
echo "You can now go back to the feedback page.\n";
?> 