<?php
/**
 * Chat System Database Setup
 * Creates all necessary tables for the live chat functionality
 */

require_once __DIR__ . '/../../includes/db_config.php';

// Function to execute SQL and handle errors
function executeSQL($conn, $sql, $description) {
    echo "Creating $description...\n";
    if (mysqli_query($conn, $sql)) {
        echo "✓ $description created successfully\n";
    } else {
        echo "✗ Error creating $description: " . mysqli_error($conn) . "\n";
    }
}

// Chat Sessions Table
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
    rating INT NULL CHECK (rating >= 1 AND rating <= 5),
    feedback TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_assigned_agent (assigned_agent_id),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at),
    INDEX idx_last_activity (last_activity),
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_agent_id) REFERENCES users(user_id) ON DELETE SET NULL
)";

// Chat Messages Table
$chatMessagesSQL = "
CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    sender_id INT NOT NULL DEFAULT 0,
    message_text TEXT NOT NULL,
    message_type ENUM('text', 'system', 'file', 'image') DEFAULT 'text',
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_is_read (is_read),
    
    FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// Chat Participants Table
$chatParticipantsSQL = "
CREATE TABLE IF NOT EXISTS chat_participants (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('customer', 'agent', 'supervisor') DEFAULT 'customer',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    
    UNIQUE KEY unique_session_user (session_id, user_id),
    FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// Chat Agent Status Table
$chatAgentStatusSQL = "
CREATE TABLE IF NOT EXISTS chat_agent_status (
    agent_id INT PRIMARY KEY,
    status ENUM('online', 'busy', 'away', 'offline') DEFAULT 'offline',
    max_concurrent_chats INT DEFAULT 5,
    current_chat_count INT DEFAULT 0,
    auto_assign BOOLEAN DEFAULT TRUE,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status),
    INDEX idx_last_seen (last_seen),
    INDEX idx_auto_assign (auto_assign),
    
    FOREIGN KEY (agent_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

// Chat Quick Responses Table
$chatQuickResponsesSQL = "
CREATE TABLE IF NOT EXISTS chat_quick_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by),
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
)";

// Chat Files Table (for file attachments)
$chatFilesSQL = "
CREATE TABLE IF NOT EXISTS chat_files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_message_id (message_id),
    INDEX idx_uploaded_at (uploaded_at),
    
    FOREIGN KEY (message_id) REFERENCES chat_messages(message_id) ON DELETE CASCADE
)";

// Chat Session Tags Table (for categorization)
$chatSessionTagsSQL = "
CREATE TABLE IF NOT EXISTS chat_session_tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    tag_name VARCHAR(50) NOT NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_session_id (session_id),
    INDEX idx_tag_name (tag_name),
    INDEX idx_added_by (added_by),
    
    UNIQUE KEY unique_session_tag (session_id, tag_name),
    FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(user_id) ON DELETE CASCADE
)";

// Execute all table creation queries
echo "<h2>Setting up Chat System Database Tables</h2>\n";
echo "<pre>\n";

executeSQL($conn, $chatSessionsSQL, "chat_sessions table");
executeSQL($conn, $chatMessagesSQL, "chat_messages table");
executeSQL($conn, $chatParticipantsSQL, "chat_participants table");
executeSQL($conn, $chatAgentStatusSQL, "chat_agent_status table");
executeSQL($conn, $chatQuickResponsesSQL, "chat_quick_responses table");
executeSQL($conn, $chatFilesSQL, "chat_files table");
executeSQL($conn, $chatSessionTagsSQL, "chat_session_tags table");

// Insert default quick responses
$defaultResponses = [
    ['Welcome', 'Hello! Welcome to VVU SRC Support. How can I assist you today?', 'greeting'],
    ['Please Wait', 'Thank you for your patience. I\'m looking into your request and will get back to you shortly.', 'general'],
    ['More Information', 'Could you please provide more details about the issue you\'re experiencing?', 'general'],
    ['Technical Issue', 'I understand you\'re experiencing a technical issue. Let me help you resolve this.', 'technical'],
    ['Account Help', 'I\'ll be happy to help you with your account-related question.', 'account'],
    ['Closing', 'Thank you for contacting VVU SRC Support. Is there anything else I can help you with today?', 'closing'],
    ['Escalation', 'I\'m going to escalate this issue to our technical team for further assistance.', 'escalation'],
    ['Follow Up', 'I\'ll follow up with you once I have more information about your request.', 'general']
];

echo "\nInserting default quick responses...\n";

foreach ($defaultResponses as $response) {
    $title = mysqli_real_escape_string($conn, $response[0]);
    $message = mysqli_real_escape_string($conn, $response[1]);
    $category = mysqli_real_escape_string($conn, $response[2]);
    
    $insertSQL = "INSERT IGNORE INTO chat_quick_responses (title, message, category, created_by) 
                  VALUES ('$title', '$message', '$category', 1)";
    
    if (mysqli_query($conn, $insertSQL)) {
        echo "✓ Added quick response: $title\n";
    } else {
        echo "✗ Error adding quick response: " . mysqli_error($conn) . "\n";
    }
}

// Initialize agent status for existing admin/member users
echo "\nInitializing agent status for admin and member users...\n";

$initAgentSQL = "INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign)
                 SELECT user_id, 'offline', 5, 1 
                 FROM users 
                 WHERE role IN ('admin', 'member', 'super_admin')";

if (mysqli_query($conn, $initAgentSQL)) {
    echo "✓ Agent status initialized for admin, member, and super_admin users\n";
} else {
    echo "✗ Error initializing agent status: " . mysqli_error($conn) . "\n";
}

// Update existing agent status to include super_admin users
$updateAgentSQL = "INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign)
                   SELECT user_id, 'offline', 5, 1 
                   FROM users 
                   WHERE role = 'super_admin' 
                   AND user_id NOT IN (SELECT agent_id FROM chat_agent_status)";

if (mysqli_query($conn, $updateAgentSQL)) {
    echo "✓ Super admin users added to agent status\n";
} else {
    echo "✗ Error adding super admin users: " . mysqli_error($conn) . "\n";
}

echo "\n✅ Chat system database setup completed!\n";
echo "</pre>\n";

// Close connection
mysqli_close($conn);
?>