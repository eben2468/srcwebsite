-- Add status column to budget_transactions if it doesn't exist
ALTER TABLE `budget_transactions` 
ADD COLUMN IF NOT EXISTS `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `transaction_type`,
ADD COLUMN IF NOT EXISTS `approved_by` INT NULL AFTER `status`,
ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`,
ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT NULL AFTER `approved_at`;

-- Create budget_history table if it doesn't exist
CREATE TABLE IF NOT EXISTS `budget_history` (
    `history_id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`transaction_id`) REFERENCES `budget_transactions`(`transaction_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
