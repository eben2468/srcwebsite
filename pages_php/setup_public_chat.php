<?php
/**
 * Public Chat Database Setup
 * Creates required tables for public chat functionality
 */

require_once __DIR__ . '/../includes/db_config.php';

// Create public_chat_messages table
$createMessagesTable = "CREATE TABLE IF NOT EXISTS public_chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    message_text TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    INDEX idx_sender_id (sender_id),
    INDEX idx_sent_at (sent_at),
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create public_chat_reactions table
$createReactionsTable = "CREATE TABLE IF NOT EXISTS public_chat_reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_reaction (message_id, user_id, reaction_type),
    FOREIGN KEY (message_id) REFERENCES public_chat_messages(message_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Add last_activity column to users table if it doesn't exist
$addLastActivityColumn = "ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP NULL DEFAULT NULL,
    ADD INDEX IF NOT EXISTS idx_last_activity (last_activity)";

$success = true;
$errors = [];

// Execute queries
if (mysqli_query($conn, $createMessagesTable)) {
    echo "✓ public_chat_messages table created successfully<br>";
} else {
    $errors[] = "Error creating public_chat_messages table: " . mysqli_error($conn);
    $success = false;
}

if (mysqli_query($conn, $createReactionsTable)) {
    echo "✓ public_chat_reactions table created successfully<br>";
} else {
    $errors[] = "Error creating public_chat_reactions table: " . mysqli_error($conn);
    $success = false;
}

// Try to add last_activity column (may fail if already exists, which is okay)
if (mysqli_query($conn, $addLastActivityColumn)) {
    echo "✓ last_activity column added to users table<br>";
} else {
    // Check if error is because column already exists
    $error = mysqli_error($conn);
    if (strpos($error, 'Duplicate column') === false && strpos($error, 'already exists') === false) {
        echo "⚠ Warning: Could not add last_activity column: " . $error . "<br>";
    } else {
        echo "✓ last_activity column already exists<br>";
    }
}

if ($success) {
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<a href='public_chat.php'>Go to Public Chat</a>";
} else {
    echo "<br><strong>Database setup encountered errors:</strong><br>";
    foreach ($errors as $error) {
        echo "✗ " . htmlspecialchars($error) . "<br>";
    }
}

mysqli_close($conn);
?>
