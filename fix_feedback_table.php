<?php
// Include database configuration
require_once 'db_config.php';

// Display header
echo "<!DOCTYPE html>
<html>
<head>
    <title>SRC Management System - Fix Feedback Table</title>
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
    <h1>SRC Management System - Fix Feedback Table</h1>";

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
    echo "<p>Feedback table exists. Checking structure...</p>";
    
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
    
    // Drop the feedback table
    echo "<p>Dropping existing feedback table...</p>";
    if (mysqli_query($conn, "DROP TABLE feedback")) {
        echo "<p class='success'>Successfully dropped feedback table.</p>";
    } else {
        echo "<p class='error'>Error dropping feedback table: " . mysqli_error($conn) . "</p>";
        exit;
    }
} else {
    echo "<p class='warning'>Feedback table does not exist. Creating new table...</p>";
}

// Create feedback table with proper constraints
echo "<h2>Creating Fixed Feedback Table</h2>";
$createTableSQL = "CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    feedback_type ENUM('suggestion', 'complaint', 'question', 'praise') NOT NULL,
    status ENUM('pending', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',
    assigned_to VARCHAR(100) NULL,
    resolution TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
)";

if (mysqli_query($conn, $createTableSQL)) {
    echo "<p class='success'>Successfully created fixed feedback table.</p>";
} else {
    echo "<p class='error'>Error creating feedback table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Restore feedback data if available
if (!empty($feedbackData)) {
    echo "<h2>Restoring Feedback Data</h2>";
    $restored = 0;
    $failed = 0;
    
    foreach ($feedbackData as $row) {
        $sql = "INSERT INTO feedback (feedback_id, user_id, subject, message, feedback_type, status, assigned_to, resolution, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iissssssss", 
            $row['feedback_id'],
            $row['user_id'],
            $row['subject'],
            $row['message'],
            $row['feedback_type'],
            $row['status'],
            $row['assigned_to'],
            $row['resolution'],
            $row['created_at'],
            $row['updated_at']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $restored++;
        } else {
            $failed++;
            echo "<p class='error'>Error restoring feedback #" . $row['feedback_id'] . ": " . mysqli_error($conn) . "</p>";
        }
    }
    
    echo "<p class='success'>Restored $restored feedback records. Failed to restore $failed records.</p>";
}

// Verify the table was created correctly
echo "<h2>Verification</h2>";
$tableInfo = mysqli_query($conn, "DESCRIBE feedback");
if (mysqli_num_rows($tableInfo) > 0) {
    echo "<p>Feedback table structure:</p>";
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

// Check foreign keys
$fkInfo = mysqli_query($conn, "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'feedback' AND REFERENCED_TABLE_NAME IS NOT NULL");
if (mysqli_num_rows($fkInfo) > 0) {
    echo "<p>Foreign key constraints:</p>";
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
    
    while ($row = mysqli_fetch_assoc($fkInfo)) {
        echo "<tr>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<p class='success'>The feedback table has been fixed!</p>";
echo "<p><a href='pages_php/feedback.php'>Try the feedback page</a></p>";
echo "</body></html>";
?> 