<?php
// Script to add profile_picture, bio, and phone columns to the users table if they don't exist
require_once 'db_config.php';

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Add profile_picture column if it doesn't exist
addColumnIfNotExists('users', 'profile_picture', 'VARCHAR(255) NULL');

// Add bio column if it doesn't exist
addColumnIfNotExists('users', 'bio', 'TEXT NULL');

// Add phone column if it doesn't exist
addColumnIfNotExists('users', 'phone', 'VARCHAR(20) NULL');

echo "<p>Database update completed.</p>";
echo "<p><a href='pages_php/users.php'>Return to Users Page</a></p>";
?> 