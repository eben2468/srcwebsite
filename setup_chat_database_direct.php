<?php
/**
 * Direct Chat Database Setup
 * Simple script to create chat tables without complex dependencies
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

echo "<h2>Chat System Database Setup</h2>";
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

// 1. Chat Sessions Table
$chatSessionsSQL = "
CREATE TABLE IF NOT EXISTS chat_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_agent_id INT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    subject VARCHAR(255) DEFAULT 'General Support Request',
    department VARCHAR(50) DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('waiting', 'active', 'ended') DEFAULT 'waiting',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    rating INT NULL,
    feedback TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

executeSQL($conn, $chatSessionsSQL, "chat_sessions table");

// 2. Chat Messages Table
$chatMessagesSQL = "
CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    sender_id INT NOT NULL DEFAULT 0,
    message_text TEXT NOT NULL,
    message_type ENUM('text', 'system', 'file', 'image') DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

executeSQL($conn, $chatMessagesSQL, "chat_messages table");

// 3. Chat Participants Table
$chatParticipantsSQL = "
CREATE TABLE IF NOT EXISTS chat_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('customer', 'agent', 'supervisor') DEFAULT 'customer',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
)";

executeSQL($conn, $chatParticipantsSQL, "chat_participants table");

// 4. Chat Agent Status Table
$chatAgentStatusSQL = "
CREATE TABLE IF NOT EXISTS chat_agent_status (
    agent_id INT PRIMARY KEY,
    status ENUM('online', 'busy', 'away', 'offline') DEFAULT 'offline',
    max_concurrent_chats INT DEFAULT 5,
    current_chat_count INT DEFAULT 0,
    auto_assign BOOLEAN DEFAULT TRUE,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

executeSQL($conn, $chatAgentStatusSQL, "chat_agent_status table");

// 5. Chat Quick Responses Table
$chatQuickResponsesSQL = "
CREATE TABLE IF NOT EXISTS chat_quick_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

executeSQL($conn, $chatQuickResponsesSQL, "chat_quick_responses table");

// 6. Insert default quick responses
echo "\nInserting default quick responses...\n";

$defaultResponses = [
    ['Welcome', 'Hello! Welcome to VVU SRC Support. How can I assist you today?', 'greeting'],
    ['Please Wait', 'Thank you for your patience. I\'m looking into your request and will get back to you shortly.', 'general'],
    ['More Information', 'Could you please provide more details about the issue you\'re experiencing?', 'general'],
    ['Technical Issue', 'I understand you\'re experiencing a technical issue. Let me help you resolve this.', 'technical'],
    ['Account Help', 'I\'ll be happy to help you with your account-related question.', 'account'],
    ['Closing', 'Thank you for contacting VVU SRC Support. Is there anything else I can help you with today?', 'closing']
];

foreach ($defaultResponses as $response) {
    $title = $conn->real_escape_string($response[0]);
    $message = $conn->real_escape_string($response[1]);
    $category = $conn->real_escape_string($response[2]);
    
    $insertSQL = "INSERT IGNORE INTO chat_quick_responses (title, message, category, created_by) 
                  VALUES ('$title', '$message', '$category', 1)";
    
    if ($conn->query($insertSQL) === TRUE) {
        echo "✓ Added quick response: $title\n";
    } else {
        echo "✗ Error adding quick response: " . $conn->error . "\n";
    }
}

// 7. Initialize agent status for admin users
echo "\nInitializing agent status for admin users...\n";

// First, check if users table exists and get admin users
$checkUsersSQL = "SHOW TABLES LIKE 'users'";
$result = $conn->query($checkUsersSQL);

if ($result->num_rows > 0) {
    // Get admin and member users
    $getUsersSQL = "SELECT user_id FROM users WHERE role IN ('admin', 'super_admin', 'member')";
    $usersResult = $conn->query($getUsersSQL);
    
    if ($usersResult && $usersResult->num_rows > 0) {
        while ($user = $usersResult->fetch_assoc()) {
            $userId = $user['user_id'];
            $initAgentSQL = "INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign) 
                            VALUES ($userId, 'offline', 5, 1)";
            
            if ($conn->query($initAgentSQL) === TRUE) {
                echo "✓ Initialized agent status for user ID: $userId\n";
            } else {
                echo "✗ Error initializing agent status for user ID $userId: " . $conn->error . "\n";
            }
        }
    } else {
        echo "! No admin/member users found to initialize\n";
    }
} else {
    echo "! Users table not found - agent status will be initialized when users are created\n";
}

// 8. Add indexes for better performance
echo "\nAdding database indexes...\n";

$indexes = [
    "ALTER TABLE chat_sessions ADD INDEX IF NOT EXISTS idx_user_id (user_id)",
    "ALTER TABLE chat_sessions ADD INDEX IF NOT EXISTS idx_assigned_agent (assigned_agent_id)",
    "ALTER TABLE chat_sessions ADD INDEX IF NOT EXISTS idx_status (status)",
    "ALTER TABLE chat_messages ADD INDEX IF NOT EXISTS idx_session_id (session_id)",
    "ALTER TABLE chat_messages ADD INDEX IF NOT EXISTS idx_sender_id (sender_id)",
    "ALTER TABLE chat_participants ADD INDEX IF NOT EXISTS idx_session_id (session_id)",
    "ALTER TABLE chat_participants ADD INDEX IF NOT EXISTS idx_user_id (user_id)"
];

foreach ($indexes as $indexSQL) {
    if ($conn->query($indexSQL) === TRUE) {
        echo "✓ Index added successfully\n";
    } else {
        // Ignore errors for existing indexes
        if (strpos($conn->error, 'Duplicate key name') === false) {
            echo "✗ Error adding index: " . $conn->error . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ CHAT SYSTEM DATABASE SETUP COMPLETED!\n";
echo str_repeat("=", 50) . "\n";

echo "\nNext Steps:\n";
echo "1. Test the live chat at: pages_php/support/live-chat.php\n";
echo "2. Test chat management at: pages_php/support/chat-management.php\n";
echo "3. Ensure admin users can set their status to 'online'\n";
echo "4. Test real-time messaging between users and agents\n";

echo "</pre>";

$conn->close();
?>