<?php
// Include database configuration
require_once 'db_config.php';

// Function to display table structure
function displayTableStructure($tableName) {
    global $conn;
    
    echo "<h2>Table Structure: $tableName</h2>";
    
    $sql = "SHOW COLUMNS FROM $tableName";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        echo "<p>Error: " . mysqli_error($conn) . "</p>";
        return;
    }
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . (isset($row['Default']) ? $row['Default'] : 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Get table name from URL or default to 'users'
$tableName = isset($_GET['table']) ? $_GET['table'] : 'users';

// Display table structure
displayTableStructure($tableName);

// Add links
echo "<p><a href='list_tables.php'>View All Tables</a> | <a href='pages_php/register.php'>Go to Registration Page</a></p>";
?> 