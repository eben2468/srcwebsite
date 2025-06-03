<?php
// Include database configuration
require_once 'db_config.php';

// Create gallery table
$createGalleryTableSQL = "CREATE TABLE IF NOT EXISTS gallery (
    gallery_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_name VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    file_size INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query
if (mysqli_query($conn, $createGalleryTableSQL)) {
    echo "Gallery table created successfully or already exists.";
} else {
    echo "Error creating gallery table: " . mysqli_error($conn);
}
?> 