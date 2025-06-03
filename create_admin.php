<?php
// Script to create an admin user
require_once 'db_config.php';

// Check if the script is being run from the browser or command line
$isCli = (php_sapi_name() === 'cli');

// Function to output messages
function output($message) {
    global $isCli;
    if ($isCli) {
        echo $message . PHP_EOL;
    } else {
        echo $message . "<br>";
    }
}

output("Starting admin user creation...");

// Admin user details
$username = "admin";
$password = "admin123";
$email = "admin@src.com";
$firstName = "Admin";
$lastName = "User";
$role = "admin";
$status = "Active";

// Check if user already exists
$checkSql = "SELECT * FROM users WHERE username = ? OR email = ?";
$stmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($stmt, "ss", $username, $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    output("An admin user with this username or email already exists.");
    $user = mysqli_fetch_assoc($result);
    output("Username: " . $user['username']);
    output("Email: " . $user['email']);
    output("Password: " . $password . " (this is the default password, not the actual password)");
    
    // Update the password if requested
    if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $user['user_id']);
        
        if (mysqli_stmt_execute($updateStmt)) {
            output("Password has been reset to: " . $password);
        } else {
            output("Failed to reset password: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($updateStmt);
    }
} else {
    // Create new admin user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($insertStmt, "sssssss", $username, $hashedPassword, $email, $firstName, $lastName, $role, $status);
    
    if (mysqli_stmt_execute($insertStmt)) {
        $userId = mysqli_insert_id($conn);
        output("Admin user created successfully!");
        output("User ID: " . $userId);
        output("Username: " . $username);
        output("Email: " . $email);
        output("Password: " . $password);
        output("Role: " . $role);
    } else {
        output("Failed to create admin user: " . mysqli_error($conn));
    }
    
    mysqli_stmt_close($insertStmt);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$isCli) {
    echo '<p><a href="pages_php/login.php">Go to Login Page</a></p>';
}
?> 