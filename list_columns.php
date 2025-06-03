<?php
// Include database configuration
require_once 'db_config.php';

// Test database connection
if (!$conn) {
    echo "Database connection failed!\n";
    exit;
}

// List all columns in the events table
echo "COLUMNS IN EVENTS TABLE:\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM events");

if (!$result) {
    echo "Error: " . mysqli_error($conn) . "\n";
    exit;
}

$columns = array();
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nTotal columns: " . count($columns) . "\n";
echo "Column list: " . implode(", ", $columns) . "\n";

// Check for end_date column
if (in_array('end_date', $columns)) {
    echo "\nThe 'end_date' column EXISTS in the table.\n";
} else {
    echo "\nThe 'end_date' column DOES NOT EXIST in the table.\n";
}
?> 