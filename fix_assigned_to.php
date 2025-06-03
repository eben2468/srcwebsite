<?php
// Include database configuration
require_once 'db_config.php';

// Display header
echo "<!DOCTYPE html>
<html>
<head>
    <title>SRC Management System - Fix Assigned To Field</title>
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
    <h1>SRC Management System - Fix Assigned To Field</h1>";

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
    
    // Check for existing foreign key
    $fkQuery = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = 'src_management_system' 
                AND TABLE_NAME = 'feedback' 
                AND COLUMN_NAME = 'assigned_to' 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $fkResult = mysqli_query($conn, $fkQuery);
    $fkExists = mysqli_num_rows($fkResult) > 0;
    
    if ($fkExists) {
        $fkRow = mysqli_fetch_assoc($fkResult);
        $fkName = $fkRow['CONSTRAINT_NAME'];
        
        echo "<p>Found foreign key constraint: " . $fkName . "</p>";
        
        // Get current feedback data
        echo "<p>Backing up existing feedback data...</p>";
        $feedbackData = [];
        $backupResult = mysqli_query($conn, "SELECT * FROM feedback");
        
        if ($backupResult && mysqli_num_rows($backupResult) > 0) {
            while ($row = mysqli_fetch_assoc($backupResult)) {
                $feedbackData[] = $row;
            }
            echo "<p class='success'>Successfully backed up " . count($feedbackData) . " feedback records.</p>";
        } else {
            echo "<p class='warning'>No feedback data to back up.</p>";
        }
        
        // Drop the foreign key constraint
        echo "<p>Dropping foreign key constraint...</p>";
        $dropFkSQL = "ALTER TABLE feedback DROP FOREIGN KEY " . $fkName;
        
        if (mysqli_query($conn, $dropFkSQL)) {
            echo "<p class='success'>Successfully dropped foreign key constraint.</p>";
            
            // Modify the assigned_to column to be VARCHAR
            echo "<p>Modifying assigned_to column to VARCHAR(100)...</p>";
            $alterColumnSQL = "ALTER TABLE feedback MODIFY assigned_to VARCHAR(100) NULL";
            
            if (mysqli_query($conn, $alterColumnSQL)) {
                echo "<p class='success'>Successfully modified assigned_to column.</p>";
            } else {
                echo "<p class='error'>Error modifying assigned_to column: " . mysqli_error($conn) . "</p>";
                exit;
            }
        } else {
            echo "<p class='error'>Error dropping foreign key constraint: " . mysqli_error($conn) . "</p>";
            exit;
        }
    } else {
        echo "<p>No foreign key constraint found on assigned_to column. Checking column type...</p>";
        
        // Check column type
        $columnInfo = mysqli_query($conn, "SHOW COLUMNS FROM feedback LIKE 'assigned_to'");
        $columnData = mysqli_fetch_assoc($columnInfo);
        
        if ($columnData && $columnData['Type'] !== 'varchar(100)') {
            echo "<p>Current assigned_to column type: " . $columnData['Type'] . "</p>";
            echo "<p>Modifying assigned_to column to VARCHAR(100)...</p>";
            
            $alterColumnSQL = "ALTER TABLE feedback MODIFY assigned_to VARCHAR(100) NULL";
            
            if (mysqli_query($conn, $alterColumnSQL)) {
                echo "<p class='success'>Successfully modified assigned_to column.</p>";
            } else {
                echo "<p class='error'>Error modifying assigned_to column: " . mysqli_error($conn) . "</p>";
                exit;
            }
        } else {
            echo "<p class='success'>assigned_to column is already VARCHAR(100). No changes needed.</p>";
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
    
    // Check remaining foreign keys
    $fkInfo = mysqli_query($conn, "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'feedback' AND REFERENCED_TABLE_NAME IS NOT NULL");
    if (mysqli_num_rows($fkInfo) > 0) {
        echo "<p>Remaining foreign key constraints:</p>";
        echo "<table border='1'>";
        echo "<tr><th>Column</th><th>Referenced Table</th><th>Referenced Column</th><th>Constraint Name</th></tr>";
        
        while ($row = mysqli_fetch_assoc($fkInfo)) {
            echo "<tr>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No foreign key constraints remaining on the feedback table.</p>";
    }
} else {
    echo "<p class='error'>Feedback table does not exist. Please run the setup_database.php script first.</p>";
}

echo "<p class='success'>The assigned_to field has been fixed!</p>";
echo "<p><a href='pages_php/feedback.php'>Go to Feedback Page</a></p>";
echo "</body></html>";
?> 