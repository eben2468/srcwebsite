<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database directly
$conn = mysqli_connect("localhost", "root", "", "src_management_system");

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}

// Get foreign key constraints for events table
echo "FOREIGN KEY CONSTRAINTS FOR EVENTS TABLE:\n";
$result = mysqli_query($conn, "
    SELECT 
        CONSTRAINT_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME 
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE 
        TABLE_SCHEMA = 'src_management_system' AND 
        TABLE_NAME = 'events' AND 
        REFERENCED_TABLE_NAME IS NOT NULL
");

// Check if query was successful
if (!$result) {
    echo "Error: " . mysqli_error($conn);
    exit();
}

// Print constraints
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Constraint: " . $row['CONSTRAINT_NAME'] . 
             ", Column: " . $row['COLUMN_NAME'] . 
             ", References: " . $row['REFERENCED_TABLE_NAME'] . 
             "." . $row['REFERENCED_COLUMN_NAME'] . "\n";
    }
} else {
    echo "No foreign key constraints found.\n";
}

mysqli_close($conn);
?> 