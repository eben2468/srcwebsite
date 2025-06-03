<?php
require_once 'db_config.php';

// Check if the seats column exists in the election_positions table
$columnCheckQuery = "SHOW COLUMNS FROM election_positions LIKE 'seats'";
$result = fetchAll($columnCheckQuery);
$columnExists = count($result) > 0;

if (!$columnExists) {
    // Add seats column to the election_positions table
    $alterTableQuery = "ALTER TABLE election_positions ADD COLUMN seats INT NOT NULL DEFAULT 1";
    
    $result = $conn->query($alterTableQuery);
    
    if ($result) {
        echo "The 'seats' column has been added to the election_positions table successfully!";
    } else {
        echo "Error adding 'seats' column: " . $conn->error;
    }
} else {
    echo "The 'seats' column already exists in the election_positions table.";
}
?> 