<?php
require_once 'db_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Minutes Table Fix Script</h1>";

// Check if the minutes table exists
$checkTableSQL = "SHOW TABLES LIKE 'minutes'";
$result = $conn->query($checkTableSQL);

if (!$result || $result->num_rows == 0) {
    die("<p style='color:red'>Minutes table does not exist. Please run create_minutes_table.php first.</p>");
}

echo "<p>Minutes table exists.</p>";

// Check if created_by column exists
$checkColumnSQL = "SHOW COLUMNS FROM minutes LIKE 'created_by'";
$columnResult = $conn->query($checkColumnSQL);

if ($columnResult && $columnResult->num_rows > 0) {
    echo "<p>The 'created_by' column already exists in the minutes table.</p>";
} else {
    echo "<p>The 'created_by' column is missing. Adding it now...</p>";
    
    // Get a valid user ID from the database (for default value)
    $userSql = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
    $userResult = $conn->query($userSql);
    
    if (!$userResult || $userResult->num_rows == 0) {
        echo "<p style='color:orange'>Warning: No admin user found in the database. Using NULL as default value.</p>";
        $defaultUser = "NULL";
    } else {
        $adminUser = $userResult->fetch_assoc();
        $defaultUser = $adminUser['user_id'];
    }
    
    // Add the missing column
    $alterTableSQL = "ALTER TABLE minutes 
                     ADD COLUMN created_by INT NULL,
                     ADD CONSTRAINT fk_minutes_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT";
    
    if ($conn->query($alterTableSQL)) {
        echo "<p style='color:green'>Successfully added 'created_by' column to minutes table.</p>";
        
        // Update existing records with the default user
        $updateSQL = "UPDATE minutes SET created_by = $defaultUser WHERE created_by IS NULL";
        if ($conn->query($updateSQL)) {
            echo "<p style='color:green'>Successfully updated existing records with default user ID.</p>";
        } else {
            echo "<p style='color:red'>Error updating existing records: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>Error adding 'created_by' column: " . $conn->error . "</p>";
    }
}

// Check table structure after fixes
echo "<h2>Current Minutes Table Structure:</h2>";
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

echo "<p><a href='pages_php/minutes.php'>Go to Minutes Page</a></p>";
?> 