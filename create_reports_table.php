<?php
/**
 * Create Reports Table Script
 * This script creates the 'reports' table in the database if it doesn't already exist.
 */

// Include database configuration
require_once 'db_config.php';

// Define SQL to create the reports table
$sql = "
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    author VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    portfolio VARCHAR(100) NOT NULL,
    summary TEXT NOT NULL,
    categories TEXT,
    file_path VARCHAR(255) NOT NULL,
    featured TINYINT(1) DEFAULT 0,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute the query
try {
    if (mysqli_query($conn, $sql)) {
        echo "Reports table created successfully or already exists.<br>";
    } else {
        echo "Error creating reports table: " . mysqli_error($conn) . "<br>";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
}

// Create uploads directory for reports if it doesn't exist
$uploadsDir = 'uploads/reports';
if (!file_exists($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "Reports uploads directory created successfully.<br>";
    } else {
        echo "Error creating reports uploads directory.<br>";
    }
} else {
    echo "Reports uploads directory already exists.<br>";
}

// Close connection
mysqli_close($conn);

echo "Setup complete!";
?> 