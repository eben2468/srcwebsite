<?php
// Include database configuration
require_once 'db_config.php';

// Test database connection
if (!$conn) {
    echo "Database connection failed!\n";
    exit;
}

// Check if events table exists
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'events'");

if (mysqli_num_rows($tableExists) == 0) {
    echo "Events table does not exist.\n";
    exit;
}

// Show table structure
echo "=== EVENTS TABLE STRUCTURE ===\n";
$tableInfo = mysqli_query($conn, "DESCRIBE events");

if (!$tableInfo) {
    echo "Error describing table: " . mysqli_error($conn) . "\n";
    exit;
}

while ($row = mysqli_fetch_assoc($tableInfo)) {
    echo "Field: " . $row['Field'] . 
         ", Type: " . $row['Type'] . 
         ", Null: " . $row['Null'] . 
         ", Key: " . $row['Key'] . 
         ", Default: " . ($row['Default'] === NULL ? 'NULL' : $row['Default']) . 
         ", Extra: " . $row['Extra'] . "\n";
}

// Show sample data
echo "\n=== SAMPLE DATA (First 2 rows) ===\n";
$data = mysqli_query($conn, "SELECT * FROM events LIMIT 2");

if (!$data) {
    echo "Error getting sample data: " . mysqli_error($conn) . "\n";
} else {
    if (mysqli_num_rows($data) > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
            print_r($row);
        }
    } else {
        echo "No data found in events table.\n";
    }
}

// Test specific query that's failing
echo "\n=== TESTING PROBLEMATIC QUERY ===\n";
$testQuery = "SELECT * FROM events ORDER BY date DESC LIMIT 1";
echo "Query: $testQuery\n";

$result = mysqli_query($conn, $testQuery);
if ($result) {
    echo "Query SUCCESS\n";
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        print_r($row);
    } else {
        echo "No rows returned.\n";
    }
} else {
    echo "Query ERROR: " . mysqli_error($conn) . "\n";
}

// Test INSERT query
echo "\n=== TESTING INSERT QUERY ===\n";
echo "Checking available columns for INSERT...\n";
$columns = array();
$tableInfo = mysqli_query($conn, "DESCRIBE events");
while ($row = mysqli_fetch_assoc($tableInfo)) {
    $columns[] = $row['Field'];
}
echo "Available columns: " . implode(", ", $columns) . "\n";
?> 