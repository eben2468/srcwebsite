<?php
// Include database configuration
require_once 'db_config.php';

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($conn) {
    echo "<p>Successfully connected to the database!</p>";
} else {
    echo "<p>Failed to connect to the database.</p>";
    exit;
}

// Check if users table exists and has records
echo "<h2>Users Table Check</h2>";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($result) > 0) {
    echo "<p>Users table exists.</p>";
    
    // Check if there are any users
    $userResult = mysqli_query($conn, "SELECT user_id, username, email, first_name, last_name, role FROM users LIMIT 5");
    if (mysqli_num_rows($userResult) > 0) {
        echo "<p>Users found in the database:</p>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th><th>Role</th></tr>";
        
        while ($row = mysqli_fetch_assoc($userResult)) {
            echo "<tr>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No users found in the database.</p>";
        
        // Insert a test user if no users exist
        echo "<h3>Creating test user...</h3>";
        $username = "testuser";
        $password = password_hash("password123", PASSWORD_DEFAULT);
        $email = "testuser@example.com";
        $firstName = "Test";
        $lastName = "User";
        $role = "student";
        
        $sql = "INSERT INTO users (username, password, email, first_name, last_name, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $username, $password, $email, $firstName, $lastName, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            $userId = mysqli_insert_id($conn);
            echo "<p>Test user created with ID: " . $userId . "</p>";
        } else {
            echo "<p>Error creating test user: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p>Users table does not exist. Please run the database setup script.</p>";
}

// Check feedback table structure
echo "<h2>Feedback Table Check</h2>";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'feedback'");
if (mysqli_num_rows($result) > 0) {
    echo "<p>Feedback table exists.</p>";
    
    // Show feedback table structure
    $tableInfo = mysqli_query($conn, "DESCRIBE feedback");
    if (mysqli_num_rows($tableInfo) > 0) {
        echo "<p>Feedback table structure:</p>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = mysqli_fetch_assoc($tableInfo)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Check if there are any feedback entries
    $feedbackResult = mysqli_query($conn, "SELECT * FROM feedback LIMIT 5");
    echo "<p>Number of feedback entries: " . mysqli_num_rows($feedbackResult) . "</p>";
} else {
    echo "<p>Feedback table does not exist. Please run the database setup script.</p>";
}

// Display all tables in the database
echo "<h2>All Tables</h2>";
$result = mysqli_query($conn, "SHOW TABLES");
if (mysqli_num_rows($result) > 0) {
    echo "<ul>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No tables found in the database.</p>";
}

echo "<p><a href='pages_php/feedback.php'>Try the feedback page</a></p>";
?> 