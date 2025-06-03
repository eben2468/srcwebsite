<?php
/**
 * Create Test User Script
 * 
 * This script creates a test admin user if one doesn't exist
 */

// Include database configuration
require_once 'db_config.php';

echo "Checking for existing admin user...\n";

// Check if admin user exists
$checkUserSql = "SELECT COUNT(*) as user_exists FROM users WHERE role = 'admin' LIMIT 1";
$result = mysqli_query($conn, $checkUserSql);
$row = mysqli_fetch_assoc($result);

if ($row['user_exists'] > 0) {
    echo "Admin user already exists. No need to create one.\n";
} else {
    echo "No admin user found. Creating test admin user...\n";
    
    // Create admin user
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $firstName = 'Admin';
    $lastName = 'User';
    $email = 'admin@example.com';
    $role = 'admin';
    $status = 'active';
    
    $createUserSql = "INSERT INTO users (username, password, first_name, last_name, email, role, status, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = mysqli_prepare($conn, $createUserSql);
    mysqli_stmt_bind_param($stmt, 'sssssss', $username, $password, $firstName, $lastName, $email, $role, $status);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Success: Test admin user created with username 'admin' and password 'admin123'\n";
    } else {
        echo "Error creating test admin user: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
}

echo "Script completed.\n";

// Close connection
mysqli_close($conn);
?> 