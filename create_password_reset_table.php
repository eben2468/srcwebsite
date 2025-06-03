<?php
// Include database configuration
require_once 'db_config.php';

echo "<h1>Creating Password Reset Tokens Table</h1>";

// Create password_reset_tokens table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
if (mysqli_query($conn, $createTableSQL)) {
    echo "<p>Password reset tokens table created or already exists.</p>";
} else {
    echo "<p>Error creating password reset tokens table: " . mysqli_error($conn) . "</p>";
    exit;
}

// Add index on token for faster lookups
$addIndexSQL = "CREATE INDEX IF NOT EXISTS idx_token ON password_reset_tokens(token);";

// Execute the query to add index
if (mysqli_query($conn, $addIndexSQL)) {
    echo "<p>Index on token column created or already exists.</p>";
} else {
    echo "<p>Error creating index: " . mysqli_error($conn) . "</p>";
}

// Cleanup old tokens
$cleanupSQL = "DELETE FROM password_reset_tokens WHERE expires_at < NOW();";

// Execute the cleanup query
$cleanupResult = mysqli_query($conn, $cleanupSQL);
if ($cleanupResult) {
    $affectedRows = mysqli_affected_rows($conn);
    echo "<p>Cleaned up $affectedRows expired tokens.</p>";
} else {
    echo "<p>Error cleaning up expired tokens: " . mysqli_error($conn) . "</p>";
}

echo "<p><a href='list_tables.php'>View All Tables</a> | <a href='pages_php/dashboard.php'>Go to Dashboard</a></p>";
?> 