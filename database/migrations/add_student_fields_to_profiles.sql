-- Migration: Add student fields to user_profiles table
-- Date: 2025-12-03

-- Add student_id column if it doesn't exist
ALTER TABLE user_profiles 
ADD COLUMN IF NOT EXISTS student_id VARCHAR(50) NULL AFTER address;

-- Add level column if it doesn't exist
ALTER TABLE user_profiles 
ADD COLUMN IF NOT EXISTS level VARCHAR(20) NULL AFTER student_id;

-- Add department column if it doesn't exist
ALTER TABLE user_profiles 
ADD COLUMN IF NOT EXISTS department VARCHAR(255) NULL AFTER level;

-- Display success message
SELECT 'Migration completed successfully: student_id, level, and department columns added to user_profiles table' AS status;
