<?php
// Script to reset the admin password
require_once 'db_config.php';

echo "<h2>Admin Password Reset</h2>";

// Admin user details
$username = "admin";
$email = "admin@src.com";
$newPassword = "admin123";

// Check if admin user exists by email or username
$sql = "SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    echo "<p>Admin user found with ID: " . $user['user_id'] . "</p>";
    echo "<p>Username: " . $user['username'] . "</p>";
    echo "<p>Email: " . $user['email'] . "</p>";
    
    // Update both email and username to ensure consistency
    $updateUserSql = "UPDATE users SET email = ?, username = ?, role = 'admin', status = 'Active' WHERE user_id = ?";
    $updateUserStmt = mysqli_prepare($conn, $updateUserSql);
    mysqli_stmt_bind_param($updateUserStmt, "ssi", $email, $username, $user['user_id']);
    
    if (mysqli_stmt_execute($updateUserStmt)) {
        echo "<p style='color: green;'>User information updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>Failed to update user information: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($updateUserStmt);
    
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the password
    $updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $user['user_id']);
    
    if (mysqli_stmt_execute($updateStmt)) {
        echo "<p style='color: green;'>Password reset successfully!</p>";
        echo "<p>Email: " . $email . "</p>";
        echo "<p>Username: " . $username . "</p>";
        echo "<p>New password: " . $newPassword . "</p>";
        
        // Verify the password was updated correctly
        $verifySql = "SELECT password FROM users WHERE user_id = ?";
        $verifyStmt = mysqli_prepare($conn, $verifySql);
        mysqli_stmt_bind_param($verifyStmt, "i", $user['user_id']);
        mysqli_stmt_execute($verifyStmt);
        $verifyResult = mysqli_stmt_get_result($verifyStmt);
        $verifyUser = mysqli_fetch_assoc($verifyResult);
        
        if (password_verify($newPassword, $verifyUser['password'])) {
            echo "<p style='color: green;'>Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>Password verification failed!</p>";
        }
        
        mysqli_stmt_close($verifyStmt);
    } else {
        echo "<p style='color: red;'>Failed to reset password: " . mysqli_error($conn) . "</p>";
    }
    
    mysqli_stmt_close($updateStmt);
} else {
    echo "<p style='color: red;'>No admin user found with email: " . $email . " or username: " . $username . "</p>";
    
    // Create admin user
    echo "<h3>Creating admin user</h3>";
    
    $firstName = "Admin";
    $lastName = "User";
    $role = "admin";
    $status = "Active";
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Try to create a unique username if 'admin' is taken
    $checkUsernameSql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $checkUsernameStmt = mysqli_prepare($conn, $checkUsernameSql);
    mysqli_stmt_bind_param($checkUsernameStmt, "s", $username);
    mysqli_stmt_execute($checkUsernameStmt);
    $checkUsernameResult = mysqli_stmt_get_result($checkUsernameStmt);
    $usernameCount = mysqli_fetch_assoc($checkUsernameResult)['count'];
    
    if ($usernameCount > 0) {
        $username = "admin_" . time(); // Add timestamp to make username unique
        echo "<p>Username 'admin' is already taken. Using '" . $username . "' instead.</p>";
    }
    
    mysqli_stmt_close($checkUsernameStmt);
    
    $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $insertStmt = mysqli_prepare($conn, $insertSql);
    mysqli_stmt_bind_param($insertStmt, "sssssss", $username, $hashedPassword, $email, $firstName, $lastName, $role, $status);
    
    if (mysqli_stmt_execute($insertStmt)) {
        $userId = mysqli_insert_id($conn);
        echo "<p style='color: green;'>Admin user created successfully!</p>";
        echo "<p>User ID: " . $userId . "</p>";
        echo "<p>Username: " . $username . "</p>";
        echo "<p>Email: " . $email . "</p>";
        echo "<p>Password: " . $newPassword . "</p>";
        echo "<p>Role: " . $role . "</p>";
    } else {
        echo "<p style='color: red;'>Failed to create admin user: " . mysqli_error($conn) . "</p>";
        echo "<p>Error code: " . mysqli_errno($conn) . "</p>";
        
        if (mysqli_errno($conn) == 1062) { // Duplicate entry error
            echo "<p>There seems to be a conflict with existing user data. Let's try to find and update the existing user.</p>";
            
            // Check for existing user by username
            $findUserSql = "SELECT * FROM users WHERE username = ?";
            $findUserStmt = mysqli_prepare($conn, $findUserSql);
            mysqli_stmt_bind_param($findUserStmt, "s", $username);
            mysqli_stmt_execute($findUserStmt);
            $findUserResult = mysqli_stmt_get_result($findUserStmt);
            
            if (mysqli_num_rows($findUserResult) > 0) {
                $existingUser = mysqli_fetch_assoc($findUserResult);
                echo "<p>Found existing user with username '" . $username . "', ID: " . $existingUser['user_id'] . "</p>";
                
                // Update the existing user
                $updateExistingSql = "UPDATE users SET 
                                     email = ?, 
                                     password = ?, 
                                     first_name = ?, 
                                     last_name = ?, 
                                     role = ?, 
                                     status = ? 
                                     WHERE user_id = ?";
                
                $updateExistingStmt = mysqli_prepare($conn, $updateExistingSql);
                mysqli_stmt_bind_param(
                    $updateExistingStmt, 
                    "ssssssi", 
                    $email, 
                    $hashedPassword, 
                    $firstName, 
                    $lastName, 
                    $role, 
                    $status, 
                    $existingUser['user_id']
                );
                
                if (mysqli_stmt_execute($updateExistingStmt)) {
                    echo "<p style='color: green;'>Existing user updated successfully!</p>";
                    echo "<p>Username: " . $username . "</p>";
                    echo "<p>Email: " . $email . "</p>";
                    echo "<p>Password: " . $newPassword . "</p>";
                } else {
                    echo "<p style='color: red;'>Failed to update existing user: " . mysqli_error($conn) . "</p>";
                }
                
                mysqli_stmt_close($updateExistingStmt);
            }
            
            mysqli_stmt_close($findUserStmt);
        }
    }
    
    mysqli_stmt_close($insertStmt);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

echo "<p><a href='pages_php/login.php'>Go to Login Page</a></p>";
?> 