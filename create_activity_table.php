<?php
// Include database configuration
require_once 'db_config.php';

// Check if user_activities table already exists
$checkTable = "SHOW TABLES LIKE 'user_activities'";
$tableExists = mysqli_query($conn, $checkTable) && mysqli_num_rows(mysqli_query($conn, $checkTable)) > 0;

// Create user_activities table if it doesn't exist
if (!$tableExists) {
    $createTable = "CREATE TABLE IF NOT EXISTS user_activities (
        activity_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(255),
        activity_type VARCHAR(50) NOT NULL,
        activity_description TEXT,
        ip_address VARCHAR(45),
        page_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (activity_type),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    try {
        mysqli_query($conn, $createTable);
        echo "User activities table created successfully!<br>";
    } catch (Exception $e) {
        echo "Error creating user activities table: " . $e->getMessage() . "<br>";
    }
} else {
    echo "User activities table already exists.<br>";
}
?> 