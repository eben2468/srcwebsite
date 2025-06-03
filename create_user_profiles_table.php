<?php
// Include database configuration
require_once 'db_config.php';

// SQL to create user_profiles table if it doesn't exist
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
    echo "<p>User profiles table created successfully or already exists.</p>";
} else {
    echo "<p>Error creating user profiles table: " . mysqli_error($conn) . "</p>";
}

// Check if the table was created successfully
$checkTable = fetchAll("SHOW TABLES LIKE 'user_profiles'");
if (count($checkTable) > 0) {
    echo "<p>User profiles table exists in the database.</p>";
} else {
    echo "<p>Failed to create user profiles table.</p>";
}

// Add a link to the registration page
echo "<p><a href='pages_php/register.php'>Go to Registration Page</a></p>";
?> 