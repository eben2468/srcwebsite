<?php
/**
 * Database Initialization Script
 * This script initializes the database using the SQL file
 */

// Database credentials (same as in db_config.php)
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');        // Default XAMPP username
define('DB_PASSWORD', '');            // Default XAMPP password (empty)

// Connect to MySQL server (without specifying database)
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("ERROR: Could not connect to MySQL server. " . mysqli_connect_error());
}

// Output status
echo "Connected to MySQL server successfully.<br>";

// Read SQL file
$sql_file = file_get_contents('src_database.sql');

if (!$sql_file) {
    die("ERROR: Could not read SQL file.");
}

// Split SQL file into individual queries
$queries = explode(';', $sql_file);

// Execute each query
$error = false;
foreach ($queries as $query) {
    $query = trim($query);
    
    // Skip empty queries
    if (empty($query)) {
        continue;
    }
    
    if (!mysqli_query($conn, $query . ';')) {
        echo "ERROR: Could not execute query: " . $query . "<br>";
        echo "MySQL Error: " . mysqli_error($conn) . "<br>";
        $error = true;
    }
}

// Output status
if (!$error) {
    echo "Database initialized successfully.<br>";
    echo "You can now use the SRC Management System.<br>";
} else {
    echo "Database initialization completed with errors. Please check the output above.<br>";
}

// Close connection
mysqli_close($conn);
?> 