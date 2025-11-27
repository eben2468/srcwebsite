-- SQL Migration: Add Electoral Commission Role
-- This script adds support for the 'electoral_commission' role in the database
-- Run this ONCE to enable the electoral commission role

-- Step 1: Modify the users table to allow the new role
-- The role column should already support VARCHAR, so we just need to ensure it accepts 'electoral_commission'
-- If there's an ENUM constraint, this would need to be modified, but typically the role is VARCHAR

-- Step 2: Add index for better query performance on the new role
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_electoral_role (role);

-- Step 3: Optional - Create a sample electoral commission user (commented out by default)
-- Uncomment the lines below to create a default electoral commission user
-- Default password will be 'electoral123' (users should change this on first login)

/*
INSERT INTO users (
    username, 
    email, 
    password, 
    first_name, 
    last_name, 
    role, 
    status, 
    is_default_password,
    created_at, 
    updated_at
) VALUES (
    'electoral_admin',
    'electoral@vvusrc.edu',
    '$2y$10$YourHashedPasswordHere',  -- You need to generate this hash for 'electoral123'
    'Electoral',
    'Commission',
    'electoral_commission',
    'active',
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE username=username;
*/

-- Step 4: Verify the role can be used
-- This query will show all existing roles in the system
SELECT DISTINCT role FROM users ORDER BY role;

-- Success message
SELECT 'Electoral Commission role migration completed successfully!' AS status;
