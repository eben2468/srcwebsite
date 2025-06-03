<?php
/**
 * Fix Reports Table Script
 * This script checks and fixes the structure of the 'reports' table.
 */

// Include database configuration
require_once 'db_config.php';

// First, check if the table exists and get its current structure
$checkTableSql = "SHOW TABLES LIKE 'reports'";
$tableExists = mysqli_query($conn, $checkTableSql)->num_rows > 0;

if (!$tableExists) {
    echo "Reports table doesn't exist. Creating it now...<br>";
    
    // Create the table with the correct structure
    $createTableSql = "
    CREATE TABLE reports (
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
    
    if (mysqli_query($conn, $createTableSql)) {
        echo "Reports table created successfully.<br>";
    } else {
        echo "Error creating reports table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Reports table exists. Checking columns...<br>";
    
    // Get the current columns
    $columnsSql = "SHOW COLUMNS FROM reports";
    $columnsResult = mysqli_query($conn, $columnsSql);
    
    $existingColumns = [];
    while ($column = mysqli_fetch_assoc($columnsResult)) {
        $existingColumns[] = $column['Field'];
    }
    
    echo "Existing columns: " . implode(", ", $existingColumns) . "<br>";
    
    // Define the expected columns
    $expectedColumns = [
        'report_id', 'title', 'author', 'date', 'type', 'portfolio', 
        'summary', 'categories', 'file_path', 'featured', 'uploaded_by',
        'created_at', 'updated_at'
    ];
    
    // Check for missing columns
    $missingColumns = array_diff($expectedColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
        echo "Missing columns: " . implode(", ", $missingColumns) . "<br>";
        
        // Add missing columns
        foreach ($missingColumns as $column) {
            $alterSql = "";
            
            switch ($column) {
                case 'author':
                    $alterSql = "ALTER TABLE reports ADD COLUMN author VARCHAR(100) NOT NULL AFTER title";
                    break;
                case 'date':
                    $alterSql = "ALTER TABLE reports ADD COLUMN date DATE NOT NULL AFTER author";
                    break;
                case 'type':
                    $alterSql = "ALTER TABLE reports ADD COLUMN type VARCHAR(50) NOT NULL AFTER date";
                    break;
                case 'portfolio':
                    $alterSql = "ALTER TABLE reports ADD COLUMN portfolio VARCHAR(100) NOT NULL AFTER type";
                    break;
                case 'summary':
                    $alterSql = "ALTER TABLE reports ADD COLUMN summary TEXT NOT NULL AFTER portfolio";
                    break;
                case 'categories':
                    $alterSql = "ALTER TABLE reports ADD COLUMN categories TEXT AFTER summary";
                    break;
                case 'file_path':
                    $alterSql = "ALTER TABLE reports ADD COLUMN file_path VARCHAR(255) NOT NULL AFTER categories";
                    break;
                case 'featured':
                    $alterSql = "ALTER TABLE reports ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER file_path";
                    break;
                case 'uploaded_by':
                    $alterSql = "ALTER TABLE reports ADD COLUMN uploaded_by INT AFTER featured";
                    break;
                case 'created_at':
                    $alterSql = "ALTER TABLE reports ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER uploaded_by";
                    break;
                case 'updated_at':
                    $alterSql = "ALTER TABLE reports ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
                    break;
            }
            
            if (!empty($alterSql)) {
                if (mysqli_query($conn, $alterSql)) {
                    echo "Added column '{$column}' successfully.<br>";
                } else {
                    echo "Error adding column '{$column}': " . mysqli_error($conn) . "<br>";
                }
            }
        }
        
        // Add foreign key constraint if uploaded_by was added
        if (in_array('uploaded_by', $missingColumns)) {
            $fkSql = "ALTER TABLE reports ADD CONSTRAINT fk_reports_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL";
            if (mysqli_query($conn, $fkSql)) {
                echo "Added foreign key constraint for 'uploaded_by' successfully.<br>";
            } else {
                echo "Error adding foreign key constraint: " . mysqli_error($conn) . "<br>";
            }
        }
    } else {
        echo "All expected columns exist in the reports table.<br>";
    }
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

echo "Fix process completed!";
?> 