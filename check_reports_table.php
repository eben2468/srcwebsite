<?php
/**
 * Check Reports Table Structure
 * This script displays the current structure of the reports table.
 */

// Include database configuration
require_once 'db_config.php';

// Check if the table exists
$checkTableSql = "SHOW TABLES LIKE 'reports'";
$tableExists = mysqli_query($conn, $checkTableSql)->num_rows > 0;

if (!$tableExists) {
    echo "Reports table doesn't exist.<br>";
} else {
    echo "Reports table exists. Checking structure...<br>";
    
    // Get the table structure
    $structureSql = "DESCRIBE reports";
    $structureResult = mysqli_query($conn, $structureSql);
    
    echo "<pre>";
    echo "REPORTS TABLE STRUCTURE:\n";
    echo "------------------------\n";
    echo "Field\t\tType\t\tNull\tKey\tDefault\tExtra\n";
    echo "---------------------------------------------------------------\n";
    
    while ($column = mysqli_fetch_assoc($structureResult)) {
        echo $column['Field'] . "\t\t" . 
             $column['Type'] . "\t\t" . 
             $column['Null'] . "\t" . 
             $column['Key'] . "\t" . 
             $column['Default'] . "\t" . 
             $column['Extra'] . "\n";
    }
    
    echo "</pre>";
    
    // Get foreign keys
    $fkSql = "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
              WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
              AND TABLE_NAME = 'reports' 
              AND REFERENCED_TABLE_NAME IS NOT NULL";
    
    $fkResult = mysqli_query($conn, $fkSql);
    
    if (mysqli_num_rows($fkResult) > 0) {
        echo "<pre>";
        echo "FOREIGN KEYS:\n";
        echo "-------------\n";
        echo "Column\t\tReferenced Table\tReferenced Column\n";
        echo "---------------------------------------------------------------\n";
        
        while ($fk = mysqli_fetch_assoc($fkResult)) {
            echo $fk['COLUMN_NAME'] . "\t\t" . 
                 $fk['REFERENCED_TABLE_NAME'] . "\t\t" . 
                 $fk['REFERENCED_COLUMN_NAME'] . "\n";
        }
        
        echo "</pre>";
    } else {
        echo "No foreign keys found.<br>";
    }
}

// Close connection
mysqli_close($conn);
?> 