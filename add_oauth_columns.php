<?php
// Script to add OAuth columns to the users table
require_once 'db_config.php';

echo "<h2>Adding OAuth Columns to Users Table</h2>";

// Function to check if a column exists in a table
function columnExists($table, $column) {
    global $conn;
    
    $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = mysqli_query($conn, $sql);
    
    return (mysqli_num_rows($result) > 0);
}

// Function to add a column if it doesn't exist
function addColumnIfNotExists($table, $column, $definition) {
    global $conn;
    
    if (!columnExists($table, $column)) {
        $sql = "ALTER TABLE $table ADD COLUMN $column $definition";
        
        if (mysqli_query($conn, $sql)) {
            echo "<p>Column '$column' added to table '$table' successfully.</p>";
        } else {
            echo "<p>Error adding column '$column' to table '$table': " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p>Column '$column' already exists in table '$table'.</p>";
    }
}

// Add OAuth provider column
addColumnIfNotExists('users', 'oauth_provider', 'VARCHAR(50) NULL');

// Add OAuth ID column
addColumnIfNotExists('users', 'oauth_id', 'VARCHAR(255) NULL');

echo "<p>Database update completed.</p>";
echo "<p><a href='pages_php/login.php'>Go to Login Page</a></p>";
?> 