<?php
/**
 * Create User Activities Table
 * This script creates the user_activities table to track user actions on the system
 */

// Include database configuration
require_once 'db_config.php';

// SQL to create user_activities table
$sql = "CREATE TABLE IF NOT EXISTS user_activities (
    activity_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_description TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    page_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
$result = mysqli_query($conn, $sql);

if ($result) {
    echo "User activities table created successfully.";
} else {
    echo "Error creating user activities table: " . mysqli_error($conn);
}
?> 