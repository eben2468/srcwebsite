<?php
// Include database configuration
require_once 'includes/db_config.php';

// Define security token to prevent unauthorized access
$security_token = "SRC_ADMIN_RESET_2024";

// Check if token is provided and correct
$token_valid = isset($_GET['token']) && $_GET['token'] === $security_token;

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Account Reset Tool</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container py-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card shadow'>
                    <div class='card-header bg-primary text-white'>
                        <h3 class='mb-0'>Admin Account Reset Tool</h3>
                    </div>
                    <div class='card-body'>";

// If token is not provided or invalid, show the token form
if (!$token_valid) {
    echo "<div class='alert alert-warning'>
            <strong>Security Check:</strong> Please enter the security token to proceed.
          </div>
          <form method='get' action=''>
            <div class='mb-3'>
                <label for='token' class='form-label'>Security Token</label>
                <input type='password' class='form-control' id='token' name='token' required>
            </div>
            <button type='submit' class='btn btn-primary'>Submit</button>
          </form>";
} else {
    // Token is valid, show the admin reset options
    echo "<div class='alert alert-success'>
            <strong>Access Granted:</strong> You can now reset the admin account.
          </div>";
    
    // Process form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['reset_admin'])) {
            // Admin account details
            $email = 'ebenofficial0@gmail.com';
            $password = 'eben2468';
            $firstName = 'Ebenezer';
            $lastName = 'Admin';
            $fullName = $firstName . ' ' . $lastName;
            $role = 'admin';
            $status = 'Active';
            
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Check if user exists
                $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $user = $result->fetch_assoc();
                
                if ($user) {
                    // Update existing user
                    $userId = $user['user_id'];
                    $updateStmt = $conn->prepare("UPDATE users SET password = ?, first_name = ?, last_name = ?, role = ?, status = ? WHERE user_id = ?");
                    $updateStmt->bind_param("sssssi", $hashedPassword, $firstName, $lastName, $role, $status, $userId);
                    $updateStmt->execute();
                    
                    echo "<div class='alert alert-info'>
                            Admin user updated with ID: {$userId}
                          </div>";
                } else {
                    // Create new user
                    $insertUserStmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role, status, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $insertUserStmt->bind_param("ssssss", $email, $hashedPassword, $firstName, $lastName, $role, $status);
                    $insertUserStmt->execute();
                    $userId = $conn->insert_id;
                    
                    echo "<div class='alert alert-info'>
                            New admin user created with ID: {$userId}
                          </div>";
                }
                
                // Check if profile exists
                $checkProfileStmt = $conn->prepare("SELECT profile_id FROM user_profiles WHERE user_id = ?");
                $checkProfileStmt->bind_param("i", $userId);
                $checkProfileStmt->execute();
                $profileResult = $checkProfileStmt->get_result();
                $profile = $profileResult->fetch_assoc();
                
                if ($profile) {
                    // Update profile
                    $profileId = $profile['profile_id'];
                    $updateProfileStmt = $conn->prepare("UPDATE user_profiles SET full_name = ? WHERE profile_id = ?");
                    $updateProfileStmt->bind_param("si", $fullName, $profileId);
                    $updateProfileStmt->execute();
                    
                    echo "<div class='alert alert-info'>
                            User profile updated with ID: {$profileId}
                          </div>";
                } else {
                    // Create profile
                    $defaultPic = 'default.jpg';
                    $insertProfileStmt = $conn->prepare("INSERT INTO user_profiles (user_id, full_name, profile_picture, created_at) 
                                        VALUES (?, ?, ?, NOW())");
                    $insertProfileStmt->bind_param("iss", $userId, $fullName, $defaultPic);
                    $insertProfileStmt->execute();
                    $profileId = $conn->insert_id;
                    
                    echo "<div class='alert alert-info'>
                            New user profile created with ID: {$profileId}
                          </div>";
                }
                
                // Ensure user_profiles table exists
                $createTableSQL = "CREATE TABLE IF NOT EXISTS user_profiles (
                    profile_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    bio TEXT NULL,
                    phone VARCHAR(20) NULL,
                    address TEXT NULL,
                    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                $conn->query($createTableSQL);
                
                // Commit transaction
                $conn->commit();
                
                echo "<div class='alert alert-success'>
                        <strong>Success!</strong> Admin account has been reset successfully.
                        <p>Email: {$email}</p>
                        <p>Password: {$password}</p>
                      </div>";
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo "<div class='alert alert-danger'>
                        <strong>Error:</strong> " . $e->getMessage() . "
                      </div>";
            }
        }
    }
    
    // Show the reset form
    echo "<form method='post' action=''>
            <div class='mb-3'>
                <p>This will reset the admin account with the following details:</p>
                <ul>
                    <li><strong>Email:</strong> ebenofficial0@gmail.com</li>
                    <li><strong>Password:</strong> eben2468</li>
                    <li><strong>Role:</strong> admin</li>
                    <li><strong>Status:</strong> Active</li>
                </ul>
            </div>
            <button type='submit' name='reset_admin' class='btn btn-danger'>Reset Admin Account</button>
          </form>";
}

echo "      </div>
                <div class='card-footer'>
                    <a href='pages_php/login.php' class='btn btn-outline-primary'>Go to Login Page</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
?> 
