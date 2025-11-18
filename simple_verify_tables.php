<?php
// Simple verification of chat tables
ob_start(); // Start output buffering to prevent header issues

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'vvusrc';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Chat Tables Verification</h1>";

echo "<h2>Database Connection</h2>";
echo "<p style='color: green;'>✓ Database connection successful</p>";

// Check if tables exist
echo "<h2>Table Verification</h2>";
$tables = ['public_chat_messages', 'public_chat_reactions'];

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        
        // Show table structure
        $structureSql = "DESCRIBE $table";
        $structureResult = $conn->query($structureSql);
        
        if ($structureResult) {
            echo "<p>Structure for '$table':</p>";
            echo "<ul>";
            while ($row = $structureResult->fetch_assoc()) {
                echo "<li>{$row['Field']} ({$row['Type']})";
                if ($row['Key'] === 'PRI') echo " - PRIMARY KEY";
                echo "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>Both chat tables should now exist and be ready for use.</p>";
echo "<p><a href='pages_php/public_chat.php'>Try the Public Chat</a></p>";

$conn->close();
ob_end_flush();
?>