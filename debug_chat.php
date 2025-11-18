<?php
// Debug the public chat functionality
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Require login
requireLogin();

echo "<h2>Public Chat Debug</h2>";

// Check database connection
echo "<h3>Database Connection</h3>";
if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit;
}

// Check if tables exist
echo "<h3>Table Verification</h3>";
$tables = ['public_chat_messages', 'public_chat_reactions'];
$allExist = true;

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        $allExist = false;
    }
}

if (!$allExist) {
    echo "<p><a href='setup_public_chat.php'>Run Setup Script</a></p>";
    exit;
}

// Test inserting a message
echo "<h3>Message Insertion Test</h3>";
$currentUser = getCurrentUser();
$message = "Debug test message at " . date('Y-m-d H:i:s');

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

echo "<h3>JavaScript Debug</h3>";
echo "<p>Testing form submission...</p>";

?>

<form id="testForm">
    <input type="text" id="testInput" placeholder="Type a message">
    <button type="submit">Submit</button>
</form>

<div id="testResult"></div>

<script>
document.getElementById('testForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const message = document.getElementById('testInput').value;
    document.getElementById('testResult').innerHTML = '<p style="color: green;">✓ Form submission works! Message: ' + message + '</p>';
});

// Test fetch API
fetch('debug_chat.php', {method: 'GET'})
    .then(() => {
        console.log('✓ Fetch API works');
    })
    .catch(error => {
        console.error('✗ Fetch API error:', error);
    });

console.log('✓ JavaScript loaded successfully');
</script>

<p><a href="pages_php/public_chat.php">Go to Public Chat</a></p>