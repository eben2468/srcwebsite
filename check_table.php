<?php
// Include database configuration
require_once 'db_config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Events Table Structure</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4b6cb7; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Events Table Structure</h1>";

// Test database connection
if (!$conn) {
    echo "<p class='error'>Database connection failed!</p>";
    exit;
}

// Check if events table exists
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'events'");

if (mysqli_num_rows($tableExists) == 0) {
    echo "<p class='error'>Events table does not exist.</p>";
    exit;
}

// Show table structure
echo "<h2>Table Structure</h2>";
$tableInfo = mysqli_query($conn, "DESCRIBE events");
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = mysqli_fetch_assoc($tableInfo)) {
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

// Show first few rows of data
echo "<h2>Sample Data</h2>";
$data = mysqli_query($conn, "SELECT * FROM events LIMIT 3");
echo "<pre>";
while ($row = mysqli_fetch_assoc($data)) {
    print_r($row);
}
echo "</pre>";

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "srcwebsite";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Show the structure of the election_candidates table
$sql = "SHOW COLUMNS FROM election_candidates";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        echo "<h2>ELECTION_CANDIDATES TABLE STRUCTURE</h2>";
        while($row = $result->fetch_assoc()) {
            echo "Field: " . $row["Field"] . ", Type: " . $row["Type"] . ", Null: " . $row["Null"] . "\n";
        }
    } else {
        echo "<p class='error'>No columns found in election_candidates table.</p>";
    }
} else {
    echo "<p class='error'>Error: " . $conn->error . "</p>";
}

// Close connection
$conn->close();

echo "</body></html>";
?> 