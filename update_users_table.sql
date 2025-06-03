-- SQL script to update the users table with bio and phone fields
-- Run this script to add the necessary columns for the profile page

USE src_management_system;

-- Check if bio column exists, if not add it
ALTER TABLE users ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER email;

-- Check if phone column exists, if not add it
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER bio;

-- Check if profile_picture column exists, if not add it
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER phone;

-- Rename profile_image to profile_picture if it exists
-- This is to ensure consistent naming with our new profile page
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'src_management_system' 
  AND table_name = 'users' 
  AND column_name = 'profile_image';

SET @query = IF(@column_exists > 0, 
               'ALTER TABLE users CHANGE COLUMN profile_image profile_picture VARCHAR(255) NULL',
               'SELECT 1');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 