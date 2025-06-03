<?php
// Script to list all tables in the database
require_once 'db_config.php';

echo "<h1>Database Tables</h1>";
echo "<pre>";

$sql = "SHOW TABLES";
$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "Error: " . mysqli_error($conn);
    exit;
}

if (mysqli_num_rows($result) > 0) {
    echo "Tables in database:\n\n";
    while ($row = mysqli_fetch_row($result)) {
        echo $row[0] . "\n";
    }
} else {
    echo "No tables found in the database.";
}

echo "</pre>";
?> 