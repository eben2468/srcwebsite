<?php
/**
 * Public Chat Database Setup
 * Script to create public chat tables
 */

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

echo "<h2>Public Chat Database Setup</h2>";
echo "<pre>";

// Function to execute SQL safely
function executeSQL($conn, $sql, $description) {
    echo "Creating $description...\n";
    if ($conn->query($sql) === TRUE) {
        echo "✓ $description created successfully\n";
        return true;
    } else {
        echo "✗ Error creating $description: " . $conn->error . "\n";
        return false;
    }
}

// 1. Public Chat Messages Table
$publicChatMessagesSQL = "
CREATE TABLE IF NOT EXISTS public_chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    message_text TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender_id (sender_id),
    INDEX idx_sent_at (sent_at)
)";

executeSQL($conn, $publicChatMessagesSQL, "public_chat_messages table");

// 2. Public Chat Reactions Table
$publicChatReactionsSQL = "
CREATE TABLE IF NOT EXISTS public_chat_reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (message_id, user_id, reaction_type),
    INDEX idx_message_id (message_id),
    INDEX idx_user_id (user_id)
)";

executeSQL($conn, $publicChatReactionsSQL, "public_chat_reactions table");

// 3. Add last_activity column to users table if it doesn't exist
echo "\nChecking if last_activity column exists in users table...\n";

$checkColumnSQL = "SHOW COLUMNS FROM users LIKE 'last_activity'";
$columnResult = $conn->query($checkColumnSQL);

if ($columnResult->num_rows === 0) {
    $addColumnSQL = "ALTER TABLE users ADD COLUMN last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    if ($conn->query($addColumnSQL) === TRUE) {
        echo "✓ last_activity column added to users table\n";
    } else {
        echo "✗ Error adding last_activity column: " . $conn->error . "\n";
    }
} else {
    echo "✓ last_activity column already exists in users table\n";
}

// 4. Update last_activity for all existing users
echo "\nUpdating last_activity for existing users...\n";

$updateActivitySQL = "UPDATE users SET last_activity = NOW() WHERE last_activity IS NULL";
if ($conn->query($updateActivitySQL) === TRUE) {
    echo "✓ last_activity updated for existing users\n";
} else {
    echo "✗ Error updating last_activity: " . $conn->error . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ PUBLIC CHAT DATABASE SETUP COMPLETED!\n";
echo str_repeat("=", 50) . "\n";

echo "\nNext Steps:\n";
echo "1. Test the public chat at: pages_php/public_chat.php\n";
echo "2. Ensure all users can access and send messages\n";
echo "3. Test emoji reactions feature\n";

echo "</pre>";

$conn->close();
?>