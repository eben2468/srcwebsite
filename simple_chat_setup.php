<?php
/**
 * Simple Chat Setup - Direct Database Creation
 * This script creates the chat system database tables directly
 */

// Simple error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Chat System Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .info{color:#17a2b8;}";
echo "pre{background:#f8f9fa;padding:15px;border-radius:5px;border-left:4px solid #007bff;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚀 Chat System Database Setup</h1>";

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'vvusrc';

echo "<h3>Step 1: Connecting to Database</h3>";

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p class='success'>✅ Connected to database successfully!</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Please check your database settings and make sure MySQL is running.</p>";
    echo "</div></body></html>";
    exit;
}

echo "<h3>Step 2: Creating Chat Tables</h3>";

// Create tables one by one with error handling
$tables = [
    'chat_sessions' => "
        CREATE TABLE IF NOT EXISTS chat_sessions (
            session_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL DEFAULT 1,
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
        )",
    
    'chat_messages' => "
        CREATE TABLE IF NOT EXISTS chat_messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            sender_id INT NOT NULL DEFAULT 0,
            message_text TEXT NOT NULL,
            message_type ENUM('text', 'system', 'file', 'image') DEFAULT 'text',
            is_read BOOLEAN DEFAULT FALSE,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
    
    'chat_participants' => "
        CREATE TABLE IF NOT EXISTS chat_participants (
            participant_id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('customer', 'agent', 'supervisor') DEFAULT 'customer',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            left_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE
        )",
    
    'chat_agent_status' => "
        CREATE TABLE IF NOT EXISTS chat_agent_status (
            agent_id INT PRIMARY KEY,
            status ENUM('online', 'busy', 'away', 'offline') DEFAULT 'offline',
            max_concurrent_chats INT DEFAULT 5,
            current_chat_count INT DEFAULT 0,
            auto_assign BOOLEAN DEFAULT TRUE,
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
    
    'chat_quick_responses' => "
        CREATE TABLE IF NOT EXISTS chat_quick_responses (
            response_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
];

$created = 0;
$errors = 0;

foreach ($tables as $tableName => $sql) {
    echo "<p class='info'>Creating table: <strong>$tableName</strong></p>";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Table '$tableName' created successfully</p>";
        $created++;
    } else {
        echo "<p class='error'>❌ Error creating table '$tableName': " . $conn->error . "</p>";
        $errors++;
    }
}

echo "<h3>Step 3: Adding Quick Responses</h3>";

$responses = [
    ['Welcome', 'Hello! Welcome to VVU SRC Support. How can I assist you today?', 'greeting'],
    ['Please Wait', 'Thank you for your patience. I\'m looking into your request and will get back to you shortly.', 'general'],
    ['More Information', 'Could you please provide more details about the issue you\'re experiencing?', 'general'],
    ['Technical Issue', 'I understand you\'re experiencing a technical issue. Let me help you resolve this.', 'technical'],
    ['Account Help', 'I\'ll be happy to help you with your account-related question.', 'account'],
    ['Closing', 'Thank you for contacting VVU SRC Support. Is there anything else I can help you with today?', 'closing']
];

$responseCount = 0;
foreach ($responses as $response) {
    $title = $conn->real_escape_string($response[0]);
    $message = $conn->real_escape_string($response[1]);
    $category = $conn->real_escape_string($response[2]);
    
    $sql = "INSERT IGNORE INTO chat_quick_responses (title, message, category, created_by) 
            VALUES ('$title', '$message', '$category', 1)";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Added: $title</p>";
        $responseCount++;
    } else {
        echo "<p class='error'>❌ Error adding '$title': " . $conn->error . "</p>";
    }
}

echo "<h3>Step 4: Setting Up Agent Status</h3>";

// Check if users table exists and get admin users
$checkUsers = $conn->query("SHOW TABLES LIKE 'users'");
if ($checkUsers && $checkUsers->num_rows > 0) {
    // First, check what columns exist in the users table
    $columnsResult = $conn->query("SHOW COLUMNS FROM users");
    $availableColumns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $availableColumns[] = $column['Field'];
    }
    
    echo "<p class='info'>Available columns in users table: " . implode(', ', $availableColumns) . "</p>";
    
    // Build the SELECT query based on available columns
    $selectColumns = ['user_id'];
    $nameColumns = [];
    
    // Check for common name column variations
    if (in_array('first_name', $availableColumns)) {
        $selectColumns[] = 'first_name';
        $nameColumns[] = 'first_name';
    }
    if (in_array('last_name', $availableColumns)) {
        $selectColumns[] = 'last_name';
        $nameColumns[] = 'last_name';
    }
    if (in_array('name', $availableColumns)) {
        $selectColumns[] = 'name';
        $nameColumns[] = 'name';
    }
    if (in_array('username', $availableColumns)) {
        $selectColumns[] = 'username';
        $nameColumns[] = 'username';
    }
    if (in_array('email', $availableColumns)) {
        $selectColumns[] = 'email';
        $nameColumns[] = 'email';
    }
    
    // Always include role if it exists
    if (in_array('role', $availableColumns)) {
        $selectColumns[] = 'role';
        $roleCondition = "WHERE role IN ('admin', 'super_admin', 'member')";
    } else {
        $roleCondition = ""; // No role filtering if role column doesn't exist
        echo "<p class='info'>ℹ️ No 'role' column found. Will set up agent status for all users.</p>";
    }
    
    $selectQuery = "SELECT " . implode(', ', $selectColumns) . " FROM users " . $roleCondition;
    
    $adminUsers = $conn->query($selectQuery);
    
    if ($adminUsers && $adminUsers->num_rows > 0) {
        $agentCount = 0;
        while ($user = $adminUsers->fetch_assoc()) {
            $userId = $user['user_id'];
            
            // Build name from available columns
            $name = 'User ID ' . $userId;
            if (!empty($nameColumns)) {
                $nameParts = [];
                foreach ($nameColumns as $col) {
                    if (!empty($user[$col])) {
                        $nameParts[] = $user[$col];
                    }
                }
                if (!empty($nameParts)) {
                    $name = implode(' ', $nameParts);
                }
            }
            
            $role = isset($user['role']) ? $user['role'] : 'user';
            
            $sql = "INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign) 
                    VALUES ($userId, 'offline', 5, 1)";
            
            if ($conn->query($sql) === TRUE) {
                echo "<p class='success'>✅ Setup agent status for: $name ($role)</p>";
                $agentCount++;
            } else {
                echo "<p class='error'>❌ Error setting up agent for $name: " . $conn->error . "</p>";
            }
        }
        echo "<p class='info'>Total agents configured: $agentCount</p>";
    } else {
        echo "<p class='info'>ℹ️ No users found to set up as agents.</p>";
    }
} else {
    echo "<p class='info'>ℹ️ Users table not found. This is normal if you haven't set up users yet.</p>";
}

echo "<h3>Step 5: Adding Database Indexes</h3>";

$indexes = [
    "ALTER TABLE chat_sessions ADD INDEX IF NOT EXISTS idx_user_id (user_id)",
    "ALTER TABLE chat_sessions ADD INDEX IF NOT EXISTS idx_status (status)",
    "ALTER TABLE chat_messages ADD INDEX IF NOT EXISTS idx_session_id (session_id)",
    "ALTER TABLE chat_participants ADD INDEX IF NOT EXISTS idx_session_user (session_id, user_id)"
];

$indexCount = 0;
foreach ($indexes as $indexSQL) {
    if ($conn->query($indexSQL) === TRUE) {
        $indexCount++;
    }
    // Ignore errors for existing indexes
}

echo "<p class='success'>✅ Added $indexCount database indexes</p>";

echo "<h3>🎉 Setup Complete!</h3>";

echo "<div style='background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:5px;margin:20px 0;'>";
echo "<h4>✅ Chat System Database Setup Successful!</h4>";
echo "<p><strong>Tables Created:</strong> $created</p>";
echo "<p><strong>Quick Responses Added:</strong> $responseCount</p>";
echo "<p><strong>Database Indexes:</strong> $indexCount</p>";

if ($errors > 0) {
    echo "<p><strong>Errors:</strong> $errors (check details above)</p>";
}

echo "</div>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='pages_php/support/live-chat.php' target='_blank'>Test Live Chat Interface</a></li>";
echo "<li><a href='pages_php/support/chat-management.php' target='_blank'>Test Chat Management (Admin)</a></li>";
echo "<li>Login as admin and set your status to 'online' in chat management</li>";
echo "<li>Test messaging between users and agents</li>";
echo "</ol>";

echo "<h3>Test API:</h3>";
echo "<p><a href='pages_php/support/test_api.php' target='_blank'>Click here to test the API</a></p>";

$conn->close();

echo "</div></body></html>";
?>