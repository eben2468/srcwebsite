-- Add column to track users with default passwords
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_default_password TINYINT(1) DEFAULT 0 AFTER password,
ADD INDEX idx_is_default_password (is_default_password);

-- Update existing users with default passwords based on their role
UPDATE users SET is_default_password = 1 
WHERE (
    (role = 'admin' AND password = '$2y$10$' AND password LIKE '%admin123%') OR
    (role = 'member' AND password = '$2y$10$' AND password LIKE '%member123%') OR
    (role = 'finance' AND password = '$2y$10$' AND password LIKE '%finance123%') OR
    (role = 'student' AND password = '$2y$10$' AND password LIKE '%student123%')
);
