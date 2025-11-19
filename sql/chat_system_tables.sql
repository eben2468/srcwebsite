-- Chat System Database Tables
-- Creates all necessary tables for the live chat and support ticket functionality

-- Chat Sessions Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Messages Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Participants Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Agent Status Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Quick Responses Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Files Table (for file attachments)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Session Tags Table (for categorization)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default quick responses
INSERT IGNORE INTO chat_quick_responses (title, message, category, created_by) VALUES
('Welcome', 'Hello! Welcome to VVU SRC Support. How can I assist you today?', 'greeting', 1),
('Please Wait', 'Thank you for your patience. I\'m looking into your request and will get back to you shortly.', 'general', 1),
('More Information', 'Could you please provide more details about the issue you\'re experiencing?', 'general', 1),
('Technical Issue', 'I understand you\'re experiencing a technical issue. Let me help you resolve this.', 'technical', 1),
('Account Help', 'I\'ll be happy to help you with your account-related question.', 'account', 1),
('Closing', 'Thank you for contacting VVU SRC Support. Is there anything else I can help you with today?', 'closing', 1),
('Escalation', 'I\'m going to escalate this issue to our technical team for further assistance.', 'escalation', 1),
('Follow Up', 'I\'ll follow up with you once I have more information about your request.', 'general', 1);

-- Initialize agent status for existing admin/member users
INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign)
SELECT user_id, 'offline', 5, 1 
FROM users 
WHERE role IN ('admin', 'member', 'super_admin');

-- Create view for chat session statistics
CREATE OR REPLACE VIEW chat_session_stats AS
SELECT 
    COUNT(*) as total_sessions,
    SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting_sessions,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_sessions,
    SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END) as ended_sessions,
    AVG(rating) as average_rating,
    COUNT(CASE WHEN started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as sessions_last_24h
FROM chat_sessions;

-- Create view for agent performance
CREATE OR REPLACE VIEW agent_performance AS
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    cas.status as current_status,
    cas.current_chat_count,
    cas.max_concurrent_chats,
    COUNT(cs.session_id) as total_handled_sessions,
    AVG(cs.rating) as average_rating,
    cas.last_seen
FROM users u
LEFT JOIN chat_agent_status cas ON u.user_id = cas.agent_id
LEFT JOIN chat_sessions cs ON u.user_id = cs.assigned_agent_id
WHERE u.role IN ('admin', 'member', 'super_admin')
GROUP BY u.user_id, u.first_name, u.last_name, u.email, cas.status, cas.current_chat_count, cas.max_concurrent_chats, cas.last_seen;
