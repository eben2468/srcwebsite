<?php
// Verify that the chat tables were created successfully
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Require login
requireLogin();

echo "<h1>Chat Tables Verification</h1>";

// Check database connection
echo "<h2>Database Connection</h2>";
if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Check if tables exist
echo "<h2>Table Verification</h2>";
$tables = ['public_chat_messages', 'public_chat_reactions'];

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        
        // Show table structure
        $structureSql = "DESCRIBE $table";
        $structureResult = mysqli_query($conn, $structureSql);
        
        if ($structureResult) {
            echo "<p>Structure for '$table':</p>";
            echo "<ul>";
            while ($row = mysqli_fetch_assoc($structureResult)) {
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

// Test inserting a message
echo "<h2>Functionality Test</h2>";
$currentUser = getCurrentUser();

// Insert a test message
$message = "Test message at " . date('Y-m-d H:i:s');
$sql = "INSERT INTO public_chat_messages (sender_id, message_text) 
        VALUES ({$currentUser['user_id']}, '" . mysqli_real_escape_string($conn, $message) . "')";

if (mysqli_query($conn, $sql)) {
    $messageId = mysqli_insert_id($conn);
    echo "<p style='color: green;'>✓ Successfully inserted test message (ID: $messageId)</p>";
    
    // Retrieve the message
    $sql = "SELECT * FROM public_chat_messages WHERE message_id = $messageId";
    $result = mysqli_query($conn, $sql);
    
    if ($result && $row = mysqli_fetch_assoc($result)) {
        echo "<p style='color: green;'>✓ Successfully retrieved message: " . htmlspecialchars($row['message_text']) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to retrieve message</p>";
    }
    
    // Clean up
    $sql = "DELETE FROM public_chat_messages WHERE message_id = $messageId";
    mysqli_query($conn, $sql);
} else {
    echo "<p style='color: red;'>✗ Failed to insert test message: " . mysqli_error($conn) . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p><a href='pages_php/public_chat.php'>Try the Public Chat</a></p>";
echo "<p><a href='clean_public_chat.php'>Try the Clean Public Chat</a></p>";
?>