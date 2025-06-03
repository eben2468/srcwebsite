<?php
require_once 'db_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create Minutes Table</h1>";

// Drop the existing table if it exists
$dropTableSQL = "DROP TABLE IF EXISTS minutes";
try {
    if ($conn->query($dropTableSQL)) {
        echo "<p>Dropped existing minutes table.</p>";
    } 
} catch (Exception $e) {
    echo "<p>Error dropping table: " . $e->getMessage() . "</p>";
}

// SQL to create the minutes table
$createTableSQL = "CREATE TABLE minutes (
    minutes_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    committee VARCHAR(100) NOT NULL,
    meeting_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    attendees TEXT NOT NULL,
    apologies TEXT NULL,
    agenda TEXT NOT NULL,
    summary TEXT NOT NULL,
    decisions TEXT NOT NULL,
    actions TEXT NOT NULL,
    next_meeting_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Draft',
    file_path VARCHAR(255) NULL,
    file_size INT NULL,
    file_type VARCHAR(20) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute the query
try {
    if ($conn->query($createTableSQL)) {
        echo "<p style='color:green'>Minutes table created successfully!</p>";
    } else {
        echo "<p style='color:red'>Error creating minutes table: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Exception occurred: " . $e->getMessage() . "</p>";
}

// Show the table structure
echo "<h2>Minutes Table Structure:</h2>";
$describeTableSQL = "DESCRIBE minutes";
$describeResult = $conn->query($describeTableSQL);

if ($describeResult) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $describeResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red'>Error describing table: " . $conn->error . "</p>";
}

// Close the connection
$conn->close();

echo "<p><a href='insert_sample_minutes.php'>Insert Sample Data</a> | <a href='pages_php/minutes.php'>Go to Minutes Page</a></p>";
?> 