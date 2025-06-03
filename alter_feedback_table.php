<?php
// Include database configuration
require_once 'db_config.php';

// Display header
echo "<!DOCTYPE html>
<html>
<head>
    <title>SRC Management System - Update Feedback Table</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4b6cb7; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>SRC Management System - Update Feedback Table</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "<p class='success'>Successfully connected to the database!</p>";
} else {
    echo "<p class='error'>Failed to connect to the database. Please check your configuration.</p>";
    exit;
}

// Check if feedback table exists
echo "<h2>Checking Feedback Table</h2>";
$tableExists = mysqli_query($conn, "SHOW TABLES LIKE 'feedback'");

if (mysqli_num_rows($tableExists) > 0) {
    echo "<p class='success'>Feedback table exists. Proceeding with updates...</p>";
    
    // Check if columns already exist
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'submitter_name'");
    $nameColumnExists = mysqli_num_rows($columns) > 0;
    
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'submitter_email'");
    $emailColumnExists = mysqli_num_rows($columns) > 0;
    
    if ($nameColumnExists && $emailColumnExists) {
        echo "<p class='warning'>Columns submitter_name and submitter_email already exist in the feedback table.</p>";
    } else {
        // Add the new columns
        echo "<h2>Adding New Columns</h2>";
        
        $alterTableSQL = "ALTER TABLE feedback ";
        
        if (!$nameColumnExists) {
            $alterTableSQL .= "ADD COLUMN submitter_name VARCHAR(100) NULL AFTER user_id, ";
        }
        
        if (!$emailColumnExists) {
            $alterTableSQL .= "ADD COLUMN submitter_email VARCHAR(100) NULL AFTER " . 
                              ($nameColumnExists ? "submitter_name" : "user_id") . ", ";
        }
        
        // Remove trailing comma and space
        $alterTableSQL = rtrim($alterTableSQL, ", ");
        
        if (mysqli_query($conn, $alterTableSQL)) {
            echo "<p class='success'>Successfully added new columns to the feedback table.</p>";
        } else {
            echo "<p class='error'>Error adding columns: " . mysqli_error($conn) . "</p>";
            exit;
        }
    }
    
    // Verify the table structure
    echo "<h2>Verification</h2>";
    $tableInfo = mysqli_query($conn, "DESCRIBE feedback");
    if (mysqli_num_rows($tableInfo) > 0) {
        echo "<p>Updated feedback table structure:</p>";
        echo "<table border='1'>";
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
    }
} else {
    echo "<p class='error'>Feedback table does not exist. Please run the setup_database.php script first.</p>";
}

echo "<p><a href='pages_php/feedback.php'>Go to Feedback Page</a></p>";
echo "</body></html>";
?> 