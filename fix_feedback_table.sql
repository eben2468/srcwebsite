-- First check if the constraint exists
SELECT CONSTRAINT_NAME INTO @constraint_name
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = 'src_management_system' 
AND TABLE_NAME = 'feedback' 
AND COLUMN_NAME = 'assigned_to' 
AND REFERENCED_TABLE_NAME = 'users';

-- If constraint exists, drop it
SET @sql = CONCAT('ALTER TABLE feedback DROP FOREIGN KEY ', @constraint_name);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modify the column to be VARCHAR(100)
ALTER TABLE feedback MODIFY assigned_to VARCHAR(100) NULL;

-- Verify the change
DESCRIBE feedback; 