<?php
// Include database configuration
require_once 'db_config.php';

// Display header
echo "<!DOCTYPE html>
<html>
<head>
    <title>SRC Management System - Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #4b6cb7; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>SRC Management System - Database Setup</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "<p class='success'>Successfully connected to the database!</p>";
} else {
    echo "<p class='error'>Failed to connect to the database. Please check your configuration.</p>";
    exit;
}

// Create database tables
echo "<h2>Creating Database Tables</h2>";

// Read the SQL file
$sqlFile = file_get_contents('src_database.sql');
if (!$sqlFile) {
    echo "<p class='error'>Could not read the SQL file. Please make sure 'src_database.sql' exists.</p>";
    exit;
}

// Split SQL commands
$sqlCommands = explode(';', $sqlFile);
$success = true;

foreach ($sqlCommands as $sql) {
    $sql = trim($sql);
    if (empty($sql)) continue;
    
    // Skip comments and USE statement
    if (strpos($sql, '--') === 0 || strpos($sql, 'USE ') === 0 || strpos($sql, 'CREATE DATABASE') === 0) {
        continue;
    }
    
    // Execute SQL command
    if (!mysqli_query($conn, $sql . ';')) {
        echo "<p class='error'>Error executing SQL: " . mysqli_error($conn) . "</p>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        $success = false;
    }
}

if ($success) {
    echo "<p class='success'>All database tables created successfully!</p>";
} else {
    echo "<p class='warning'>There were some errors while creating the database tables.</p>";
}

// Create test admin user if it doesn't exist
echo "<h2>Creating Test Users</h2>";
$checkAdmin = mysqli_query($conn, "SELECT * FROM users WHERE username = 'admin'");

if (mysqli_num_rows($checkAdmin) == 0) {
    $username = "admin";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $email = "admin@example.com";
    $firstName = "Admin";
    $lastName = "User";
    $role = "admin";
    
    $sql = "INSERT INTO users (username, password, email, first_name, last_name, role) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $password, $email, $firstName, $lastName, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p class='success'>Admin user created successfully. Username: admin, Password: admin123</p>";
    } else {
        echo "<p class='error'>Error creating admin user: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Admin user already exists.</p>";
}

// Create test student user if it doesn't exist
$checkStudent = mysqli_query($conn, "SELECT * FROM users WHERE username = 'student'");

if (mysqli_num_rows($checkStudent) == 0) {
    $username = "student";
    $password = password_hash("student123", PASSWORD_DEFAULT);
    $email = "student@example.com";
    $firstName = "Student";
    $lastName = "User";
    $role = "student";
    
    $sql = "INSERT INTO users (username, password, email, first_name, last_name, role) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $password, $email, $firstName, $lastName, $role);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<p class='success'>Student user created successfully. Username: student, Password: student123</p>";
    } else {
        echo "<p class='error'>Error creating student user: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Student user already exists.</p>";
}

// Check database tables
echo "<h2>Database Tables Check</h2>";
$result = mysqli_query($conn, "SHOW TABLES");

if (mysqli_num_rows($result) > 0) {
    echo "<ul>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>No tables found in the database.</p>";
}

// Display navigation links
echo "<h2>Next Steps</h2>";
echo "<p>The database has been set up successfully. You can now:</p>";
echo "<ul>
        <li><a href='test_database.php'>View Database Information</a></li>
        <li><a href='pages_php/login.php'>Go to Login Page</a></li>
        <li><a href='index.php'>Go to Homepage</a></li>
      </ul>";

echo "</body></html>";
?> 