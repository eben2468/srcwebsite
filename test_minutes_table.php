<?php
require_once 'db_config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful!<br>";

// SQL to check if the minutes table exists
$checkTableSQL = "SHOW TABLES LIKE 'minutes'";
$result = $conn->query($checkTableSQL);

if ($result && $result->num_rows > 0) {
    echo "Minutes table exists.<br>";
    
    // Check table structure
    $describeTableSQL = "DESCRIBE minutes";
    $describeResult = $conn->query($describeTableSQL);
    
    if ($describeResult) {
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $foundCreatedBy = false;
        while ($row = $describeResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
            
            if ($row['Field'] == 'created_by') {
                $foundCreatedBy = true;
            }
        }
        
        echo "</table>";
        
        if (!$foundCreatedBy) {
            echo "<h3 style='color:red'>ERROR: The 'created_by' column is missing!</h3>";
        }
    } else {
        echo "Error describing table: " . $conn->error;
    }
} else {
    echo "Minutes table does not exist!<br>";
    echo "Error (if any): " . $conn->error . "<br>";
    
    // Show all tables in the database
    echo "<h3>Available Tables:</h3>";
    $showTablesSQL = "SHOW TABLES";
    $tablesResult = $conn->query($showTablesSQL);
    
    if ($tablesResult) {
        echo "<ul>";
        while ($row = $tablesResult->fetch_row()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "Error showing tables: " . $conn->error;
    }
}

// Try running the create table script again to see if there's an error
echo "<h3>Attempting to create the minutes table:</h3>";

$createTableSQL = "CREATE TABLE IF NOT EXISTS minutes (
    minutes_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    committee VARCHAR(100) NOT NULL,
    meeting_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    attendees TEXT NOT NULL,
    apologies TEXT NULL,
    agenda TEXT NOT NULL,
    summary TEXT NOT NULL,
    decisions TEXT NOT NULL,
    actions TEXT NOT NULL,
    next_meeting_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Draft',
    file_path VARCHAR(255) NULL,
    file_size INT NULL,
    file_type VARCHAR(20) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute the query
try {
    if ($conn->query($createTableSQL)) {
        echo "Minutes table created or already exists!<br>";
        
        // Check if the table now exists
        $checkTableSQL = "SHOW TABLES LIKE 'minutes'";
        $result = $conn->query($checkTableSQL);
        
        if ($result && $result->num_rows > 0) {
            echo "Confirmed: Minutes table exists.<br>";
            
            // Check table structure
            $describeTableSQL = "DESCRIBE minutes";
            $describeResult = $conn->query($describeTableSQL);
            
            if ($describeResult) {
                echo "<h3>Table Structure:</h3>";
                echo "<table border='1'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                $foundCreatedBy = false;
                while ($row = $describeResult->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['Field'] . "</td>";
                    echo "<td>" . $row['Type'] . "</td>";
                    echo "<td>" . $row['Null'] . "</td>";
                    echo "<td>" . $row['Key'] . "</td>";
                    echo "<td>" . $row['Default'] . "</td>";
                    echo "<td>" . $row['Extra'] . "</td>";
                    echo "</tr>";
                    
                    if ($row['Field'] == 'created_by') {
                        $foundCreatedBy = true;
                    }
                }
                
                echo "</table>";
                
                if (!$foundCreatedBy) {
                    echo "<h3 style='color:red'>ERROR: The 'created_by' column is missing!</h3>";
                }
            } else {
                echo "Error describing table: " . $conn->error;
            }
        } else {
            echo "Error: Minutes table still does not exist!<br>";
        }
    } else {
        echo "Error creating minutes table: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "Exception occurred: " . $e->getMessage() . "<br>";
}

// Close the connection
$conn->close();
?> 