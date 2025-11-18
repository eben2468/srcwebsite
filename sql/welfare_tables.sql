-- Welfare System Database Tables
-- Create tables for the VVUSRC Student Welfare System

-- Table for welfare requests
CREATE TABLE IF NOT EXISTS welfare_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_type ENUM('financial_assistance', 'medical_support', 'academic_support', 'accommodation', 'emergency_support', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    urgency ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    supporting_documents JSON,
    status ENUM('pending', 'approved', 'rejected', 'in_progress') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    admin_notes TEXT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_request_type (request_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Table for welfare announcements
CREATE TABLE IF NOT EXISTS welfare_announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table for welfare categories/types (for future expansion)
CREATE TABLE IF NOT EXISTS welfare_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
);

-- Table for welfare request comments/updates
CREATE TABLE IF NOT EXISTS welfare_request_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (request_id) REFERENCES welfare_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table for welfare settings
CREATE TABLE IF NOT EXISTS welfare_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Insert default welfare categories
INSERT IGNORE INTO welfare_categories (category_name, description) VALUES
('Financial Assistance', 'Support for students facing financial difficulties'),
('Medical Support', 'Healthcare and medical assistance for students'),
('Academic Support', 'Educational support and resources'),
('Accommodation', 'Housing and accommodation assistance'),
('Emergency Support', 'Urgent assistance for emergency situations'),
('Mental Health', 'Counseling and mental health support'),
('Food Security', 'Meal assistance and food support programs'),
('Transportation', 'Travel and transportation assistance'),
('Technology Support', 'Access to computers, internet, and educational technology'),
('Other', 'Other welfare-related requests');

-- Insert default welfare settings
INSERT IGNORE INTO welfare_settings (setting_key, setting_value, description) VALUES
('max_file_size', '5242880', 'Maximum file size for document uploads (in bytes)'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'Allowed file types for document uploads'),
('auto_approval_threshold', '1000', 'Amount threshold for automatic approval (in currency units)'),
('notification_email', 'welfare@vvusrc.edu', 'Email address for welfare notifications'),
('request_expiry_days', '30', 'Number of days after which pending requests expire'),
('enable_file_uploads', '1', 'Enable file uploads for welfare requests'),
('enable_public_announcements', '1', 'Enable public welfare announcements'),
('max_requests_per_month', '3', 'Maximum number of requests per student per month');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_welfare_requests_composite ON welfare_requests(status, request_type, created_at);
CREATE INDEX IF NOT EXISTS idx_welfare_announcements_active ON welfare_announcements(status, priority, created_at);

-- Create view for welfare statistics
CREATE OR REPLACE VIEW welfare_statistics AS
SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
    SUM(CASE WHEN urgency = 'urgent' THEN 1 ELSE 0 END) as urgent_requests,
    SUM(CASE WHEN urgency = 'high' THEN 1 ELSE 0 END) as high_priority_requests,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as requests_last_30_days,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as requests_last_7_days
FROM welfare_requests;

-- Create view for request type statistics
CREATE OR REPLACE VIEW welfare_request_type_stats AS
SELECT 
    request_type,
    COUNT(*) as total_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    ROUND(AVG(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) * 100, 2) as approval_rate
FROM welfare_requests 
GROUP BY request_type
ORDER BY total_count DESC;

-- Create view for monthly welfare trends
CREATE OR REPLACE VIEW welfare_monthly_trends AS
SELECT 
    YEAR(created_at) as year,
    MONTH(created_at) as month,
    MONTHNAME(created_at) as month_name,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
FROM welfare_requests 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY YEAR(created_at), MONTH(created_at)
ORDER BY year DESC, month DESC;
