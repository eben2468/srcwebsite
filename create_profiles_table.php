<?php
// Direct script to create the user_profiles table
// Include database configuration
require_once 'db_config.php';

// SQL to create user_profiles table
$sql = "CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query
if (mysqli_query($conn, $sql)) {
    echo "<h1>Success!</h1>";
    echo "<p>The user_profiles table has been created successfully.</p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Error creating user_profiles table: " . mysqli_error($conn) . "</p>";
}

// Add links
echo "<p><a href='list_tables.php'>View All Tables</a> | <a href='pages_php/register.php'>Go to Registration Page</a></p>";
?> 