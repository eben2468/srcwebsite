-- Create video_tutorials table for managing video tutorials in the support system
CREATE TABLE IF NOT EXISTS `video_tutorials` (
  `tutorial_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `video_type` enum('youtube','vimeo','upload','external') NOT NULL DEFAULT 'youtube',
  `video_url` varchar(500) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `thumbnail_image` varchar(255) DEFAULT NULL,
  `duration` varchar(20) NOT NULL,
  `difficulty` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `category` varchar(100) NOT NULL DEFAULT 'general',
  `target_roles` json DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tutorial_id`),
  KEY `idx_category` (`category`),
  KEY `idx_difficulty` (`difficulty`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample video tutorials
INSERT INTO `video_tutorials` (`title`, `description`, `video_type`, `video_url`, `duration`, `difficulty`, `category`, `target_roles`, `tags`, `sort_order`, `is_active`, `created_by`) VALUES
('System Overview', 'Get familiar with the VVUSRC Management System interface and basic navigation features', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '5:30', 'beginner', 'system-overview', '["student", "member", "admin", "super_admin", "finance"]', '["overview", "navigation", "interface"]', 1, 1, 1),
('Getting Started Guide', 'Essential tutorial for new users to understand the basic features and functionality', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '8:15', 'beginner', 'getting-started', '["student", "member", "super_admin", "finance"]', '["getting-started", "basics", "new-users"]', 2, 1, 1),
('Profile Management', 'Learn how to update your personal information, change password, and manage privacy settings', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '4:45', 'beginner', 'profile-setup', '["student", "member", "admin", "super_admin", "finance"]', '["profile", "settings", "privacy"]', 3, 1, 1),
('Event Participation', 'Discover how to view upcoming events, register for activities, and receive notifications', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '6:20', 'beginner', 'viewing-events', '["student", "member", "super_admin", "finance"]', '["events", "registration", "notifications"]', 4, 1, 1),
('User Management for Admins', 'Comprehensive guide to managing users, assigning roles, and handling permissions', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '12:30', 'intermediate', 'user-management', '["admin", "super_admin"]', '["users", "roles", "permissions", "admin"]', 5, 1, 1),
('Creating and Managing Events', 'Learn how to create events, set up RSVP, manage attendees, and handle event logistics', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '10:15', 'intermediate', 'event-creation', '["admin", "member", "super_admin"]', '["events", "creation", "management", "rsvp"]', 6, 1, 1),
('Financial Reports and Budgeting', 'Master the financial management features including budgets, transactions, and reporting', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '15:45', 'advanced', 'financial-reports', '["admin", "super_admin", "finance"]', '["finance", "reports", "budgets", "transactions"]', 7, 1, 1),
('Content Management System', 'Learn how to manage news articles, documents, gallery images, and other content', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '9:30', 'intermediate', 'content-management', '["admin", "member", "super_admin"]', '["content", "news", "documents", "gallery"]', 8, 1, 1),
('Notification System Guide', 'Understand how notifications work, manage preferences, and stay updated with announcements', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '5:15', 'beginner', 'notifications', '["student", "member", "admin", "super_admin", "finance"]', '["notifications", "alerts", "preferences"]', 9, 1, 1),
('Support System Features', 'Learn how to use help center, contact support, live chat, and get assistance', 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '4:30', 'beginner', 'support-system', '["student", "member", "admin", "super_admin", "finance"]', '["support", "help", "chat", "assistance"]', 10, 1, 1);