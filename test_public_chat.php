<?php
/**
 * Test Public Chat Functionality
 * This script tests the public chat functionality
 */

// Start output buffering to prevent header issues
ob_start();

// Include required files
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Flush output buffer
ob_end_clean();

echo "<h2>Public Chat Test</h2>";

// Test 1: Check if public chat tables exist
echo "<h3>Database Table Check</h3>";
$tables = ['public_chat_messages', 'public_chat_reactions'];

foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
    }
}

// Test 2: Check if last_activity column exists in users table
echo "<h3>Users Table Check</h3>";
$sql = "SHOW COLUMNS FROM users LIKE 'last_activity'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ 'last_activity' column exists in users table</p>";
} else {
    echo "<p style='color: red;'>✗ 'last_activity' column does not exist in users table</p>";
}

// Test 3: Try to insert a test message
echo "<h3>Message Insertion Test</h3>";
$message = "Test message from user " . $currentUser['user_id'] . " at " . date('Y-m-d H:i:s');
$sql = "INSERT INTO public_chat_messages (sender_id, message_text) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $currentUser['user_id'], $message);
    if (mysqli_stmt_execute($stmt)) {
        $messageId = mysqli_insert_id($conn);
        echo "<p style='color: green;'>✓ Successfully inserted test message (ID: $messageId)</p>";
        
        // Clean up test message
        $deleteSql = "DELETE FROM public_chat_messages WHERE message_id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteSql);
        if ($deleteStmt) {
            mysqli_stmt_bind_param($deleteStmt, "i", $messageId);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to insert test message: " . mysqli_error($conn) . "</p>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "<p style='color: red;'>✗ Failed to prepare statement: " . mysqli_error($conn) . "</p>";
}

echo "<h3>Test Complete</h3>";
echo "<p><a href='pages_php/public_chat.php'>Go to Public Chat</a></p>";
?>