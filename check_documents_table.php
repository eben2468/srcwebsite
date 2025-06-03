<?php
require_once 'db_config.php';

// Check if documents table exists
$result = fetchAll("SHOW TABLES LIKE 'documents'");
$tableExists = count($result) > 0;

if (!$tableExists) {
    // Create documents table
    $createTableSql = "
    CREATE TABLE IF NOT EXISTS documents (
        document_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) NOT NULL,
        file_size INT,
        document_type VARCHAR(50),
        category VARCHAR(50),
        uploaded_by INT,
        status ENUM('active', 'archived') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
    )";
    
    $result = $conn->query($createTableSql);
    
    if ($result) {
        echo "Documents table created successfully!";
    } else {
        echo "Error creating documents table: " . $conn->error;
    }
} else {
    echo "Documents table already exists.";
}
?> 