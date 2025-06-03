<?php
// Debug script to check login issues
require_once 'db_config.php';

echo "<h2>Login Debug Information</h2>";

// Check if the admin user exists
$email = "admin@src.com";
$password = "admin123";

echo "<h3>Checking for admin user with email: $email</h3>";

// Get user by email
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    echo "<p>User found!</p>";
    echo "<p>User ID: " . $user['user_id'] . "</p>";
    echo "<p>Username: " . $user['username'] . "</p>";
    echo "<p>Email: " . $user['email'] . "</p>";
    echo "<p>Role: " . $user['role'] . "</p>";
    echo "<p>Status: " . $user['status'] . "</p>";
    
    // Test password verification
    echo "<h3>Testing password verification</h3>";
    if (password_verify($password, $user['password'])) {
        echo "<p style='color: green;'>Password verification successful!</p>";
    } else {
        echo "<p style='color: red;'>Password verification failed!</p>";
        echo "<p>Stored password hash: " . $user['password'] . "</p>";
        
        // Create a new hash for comparison
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "<p>New hash for '$password': $newHash</p>";
    }
    
    // Check if the user status is correct
    echo "<h3>Checking user status</h3>";
    if ($user['status'] === 'Active' || $user['status'] === 'active') {
        echo "<p style='color: green;'>User status is valid: " . $user['status'] . "</p>";
    } else {
        echo "<p style='color: red;'>User status is invalid: " . $user['status'] . "</p>";
        echo "<p>Expected: 'Active' or 'active'</p>";
    }
    
    // Reset password if requested
    if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
        echo "<h3>Resetting password</h3>";
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        
        $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "si", $newHash, $user['user_id']);
        
        if (mysqli_stmt_execute($updateStmt)) {
            echo "<p style='color: green;'>Password reset successfully!</p>";
            echo "<p>New password hash: $newHash</p>";
        } else {
            echo "<p style='color: red;'>Failed to reset password: " . mysqli_error($conn) . "</p>";
        }
        
        mysqli_stmt_close($updateStmt);
    }
} else {
    echo "<p style='color: red;'>No user found with email: $email</p>";
    
    // Create the admin user
    echo "<h3>Creating admin user</h3>";
    
    $username = "admin";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $firstName = "Admin";
    $lastName = "User";
    $role = "admin";
    $status = "Active";
    
    $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($insertStmt, "sssssss", $username, $hashedPassword, $email, $firstName, $lastName, $role, $status);
    
    if (mysqli_stmt_execute($insertStmt)) {
        $userId = mysqli_insert_id($conn);
        echo "<p style='color: green;'>Admin user created successfully!</p>";
        echo "<p>User ID: $userId</p>";
        echo "<p>Username: $username</p>";
        echo "<p>Email: $email</p>";
        echo "<p>Password: $password</p>";
        echo "<p>Role: $role</p>";
        echo "<p>Status: $status</p>";
    } else {
        echo "<p style='color: red;'>Failed to create admin user: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($insertStmt);
}

// Check login function
echo "<h3>Testing login function</h3>";

// Simulate the login process
$loginSql = "SELECT * FROM users WHERE email = ? LIMIT 1";
$loginStmt = mysqli_prepare($conn, $loginSql);
mysqli_stmt_bind_param($loginStmt, "s", $email);
mysqli_stmt_execute($loginStmt);
$loginResult = mysqli_stmt_get_result($loginStmt);

if ($loginResult && mysqli_num_rows($loginResult) > 0) {
    $loginUser = mysqli_fetch_assoc($loginResult);
    
    if (password_verify($password, $loginUser['password'])) {
        if ($loginUser['status'] === 'Active' || $loginUser['status'] === 'active') {
            echo "<p style='color: green;'>Login would be successful!</p>";
        } else {
            echo "<p style='color: red;'>Login would fail: User status is " . $loginUser['status'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Login would fail: Password verification failed</p>";
    }
} else {
    echo "<p style='color: red;'>Login would fail: User not found</p>";
}

mysqli_stmt_close($loginStmt);
mysqli_close($conn);

echo "<p><a href='pages_php/login.php'>Go to Login Page</a></p>";
echo "<p><a href='debug_login.php?reset=true'>Reset Password</a></p>";
?> 