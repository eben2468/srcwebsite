<?php
/**
 * Facebook OAuth Authentication Handler
 * 
 * This file handles the Facebook OAuth authentication flow.
 * In a real implementation, you would need to:
 * 1. Register your application with Facebook Developer Portal
 * 2. Get app ID and app secret
 * 3. Configure OAuth settings
 * 4. Set up valid redirect URIs
 * 
 * For a complete implementation, you would use Facebook's PHP SDK:
 * https://github.com/facebook/php-graph-sdk
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db_config.php';
require_once '../auth_functions.php';
require_once 'oauth_config.php';

// Configuration from oauth_config.php
$appId = $facebookConfig['app_id'];
$appSecret = $facebookConfig['app_secret'];
$redirectUri = $facebookConfig['redirect_uri'];

// Check if we're in development mode
$devMode = isset($_GET['dev_mode']) && $_GET['dev_mode'] === 'true';

// If in development mode, show simulation page
if ($devMode) {
    simulateLoginPage();
    exit;
}

// Check if this is the OAuth callback (Facebook will redirect back with a 'code' parameter)
if (isset($_GET['code'])) {
    // This is the OAuth callback handling
    $code = $_GET['code'];
    
    // For development simulation
    if ($code === 'dev_simulation') {
        simulateSuccessfulLogin();
        exit;
    }
    
    // Verify state parameter to prevent CSRF attacks
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
        die("Invalid state parameter. Possible CSRF attack.");
    }
    
    // Exchange the authorization code for an access token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $tokenData = [
        'client_id' => $appId,
        'client_secret' => $appSecret,
        'redirect_uri' => $redirectUri,
        'code' => $code
    ];
    
    // Use cURL to make the token request
    $ch = curl_init($tokenUrl . '?' . http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        die("cURL Error: " . $error);
    }
    
    $tokenInfo = json_decode($response, true);
    
    if (!isset($tokenInfo['access_token'])) {
        die("Error getting access token: " . print_r($tokenInfo, true));
    }
    
    // Use the access token to get user information
    $accessToken = $tokenInfo['access_token'];
    $userInfoUrl = 'https://graph.facebook.com/v18.0/me';
    $fields = 'id,email,first_name,last_name,name,picture.type(large)';
    
    $ch = curl_init($userInfoUrl . '?fields=' . $fields . '&access_token=' . $accessToken);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        die("cURL Error: " . $error);
    }
    
    $userInfo = json_decode($response, true);
    
    if (!isset($userInfo['id'])) {
        die("Error getting user info: " . print_r($userInfo, true));
    }
    
    // Process the user information
    $facebookUserId = $userInfo['id'];
    $email = $userInfo['email'] ?? '';
    $firstName = $userInfo['first_name'] ?? '';
    $lastName = $userInfo['last_name'] ?? '';
    $profilePicture = $userInfo['picture']['data']['url'] ?? '';
    
    // Check if user exists in our database
    $sql = "SELECT * FROM users WHERE oauth_provider = 'facebook' AND oauth_id = ? LIMIT 1";
    $user = fetchOne($sql, [$facebookUserId]);
    
    if (!$user) {
        // Also check by email as a fallback
        if (!empty($email)) {
            $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
            $user = fetchOne($sql, [$email]);
        }
        
        if (!$user) {
            // User doesn't exist, create a new one
            $username = 'fb_' . substr(md5($facebookUserId), 0, 8);
            $role = 'user'; // Default role for social login users
            $status = 'Active';
            
            // Generate a random password (user won't need this as they'll login via Facebook)
            $password = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, oauth_provider, oauth_id, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'facebook', ?, NOW(), NOW())";
            
            $params = [$username, $hashedPassword, $email, $firstName, $lastName, $role, $status, $facebookUserId];
            
            if (executeQuery($insertSql, $params)) {
                global $conn;
                $userId = mysqli_insert_id($conn);
                $user = [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'role' => $role,
                    'status' => $status,
                    'oauth_provider' => 'facebook',
                    'oauth_id' => $facebookUserId
                ];
            } else {
                die("Error creating user account.");
            }
        } else {
            // User exists by email but not with Facebook OAuth, update their record
            $updateSql = "UPDATE users SET oauth_provider = 'facebook', oauth_id = ? WHERE user_id = ?";
            executeQuery($updateSql, [$facebookUserId, $user['user_id']]);
            
            // Update user data with Facebook information if fields are empty
            if (empty($user['first_name']) && !empty($firstName)) {
                executeQuery("UPDATE users SET first_name = ? WHERE user_id = ?", [$firstName, $user['user_id']]);
                $user['first_name'] = $firstName;
            }
            
            if (empty($user['last_name']) && !empty($lastName)) {
                executeQuery("UPDATE users SET last_name = ? WHERE user_id = ?", [$lastName, $user['user_id']]);
                $user['last_name'] = $lastName;
            }
        }
    }
    
    // Set user in session
    $_SESSION['user'] = $user;
    $_SESSION['is_logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    // Redirect to dashboard or to the page the user was trying to access
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
    } else {
        header("Location: ../pages_php/dashboard.php");
    }
    exit;
} else {
    // Check if we should use development simulation
    if (strpos($appId, 'YOUR_') !== false || strpos($appId, 'example') !== false) {
        header("Location: ?dev_mode=true");
        exit;
    }
    
    // This is the initial OAuth request
    // Generate a random state parameter to prevent CSRF attacks
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    // Build the authorization URL
    $authUrl = 'https://www.facebook.com/v18.0/dialog/oauth';
    $params = [
        'client_id' => $appId,
        'redirect_uri' => $redirectUri,
        'state' => $state,
        'scope' => 'email,public_profile'
    ];
    
    // Redirect to Facebook's OAuth endpoint
    header('Location: ' . $authUrl . '?' . http_build_query($params));
    exit;
}

/**
 * Display a simulated Facebook login page for development
 */
function simulateLoginPage() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Facebook Login Simulation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f0f2f5;
                font-family: Arial, sans-serif;
            }
            .facebook-card {
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                margin: 100px auto;
                padding: 20px;
            }
            .facebook-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .facebook-logo {
                color: #1877f2;
                font-size: 40px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .btn-facebook {
                background-color: #1877f2;
                color: white;
                font-weight: bold;
                border: none;
                border-radius: 6px;
                padding: 12px;
                width: 100%;
                font-size: 1.2rem;
            }
            .btn-facebook:hover {
                background-color: #166fe5;
                color: white;
            }
            .development-notice {
                background-color: #ffcc00;
                color: #333;
                padding: 10px;
                margin-bottom: 20px;
                border-radius: 4px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="facebook-card">
                <div class="development-notice">
                    <strong>Development Mode</strong><br>
                    This is a simulated Facebook login for development purposes.
                </div>
                
                <div class="facebook-header">
                    <div class="facebook-logo">facebook</div>
                    <h4>Log in to continue to SRC Management System</h4>
                </div>
                
                <form action="?code=dev_simulation" method="post" class="mb-4">
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-lg" placeholder="Email or phone number" value="facebook_user@example.com">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control form-control-lg" placeholder="Password" value="••••••••">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-facebook">Log In</button>
                    </div>
                </form>
                
                <div class="text-center">
                    <p>This is a simulation. In a real implementation, you would be redirected to the actual Facebook login page.</p>
                    <p><a href="../pages_php/login.php">Back to Login</a> | <a href="../auth/setup_oauth.php">OAuth Setup Guide</a></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Simulate a successful login with Facebook
 */
function simulateSuccessfulLogin() {
    global $conn;
    
    // Create simulated Facebook user data
    $facebookUserId = 'fb_sim_' . substr(md5(time()), 0, 10);
    $email = 'facebook_user_' . rand(100, 999) . '@example.com';
    $firstName = 'Facebook';
    $lastName = 'User';
    
    // Check if user exists in our database
    $sql = "SELECT * FROM users WHERE oauth_provider = 'facebook' AND oauth_id = ? LIMIT 1";
    $user = fetchOne($sql, [$facebookUserId]);
    
    if (!$user) {
        // User doesn't exist, create a new one
        $username = 'fb_' . substr(md5($facebookUserId), 0, 8);
        $role = 'user'; // Default role for social login users
        $status = 'Active';
        
        // Generate a random password (user won't need this as they'll login via Facebook)
        $password = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, oauth_provider, oauth_id, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'facebook', ?, NOW(), NOW())";
        
        $params = [$username, $hashedPassword, $email, $firstName, $lastName, $role, $status, $facebookUserId];
        
        if (executeQuery($insertSql, $params)) {
            $userId = mysqli_insert_id($conn);
            $user = [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => $role,
                'status' => $status,
                'oauth_provider' => 'facebook',
                'oauth_id' => $facebookUserId
            ];
        } else {
            die("Error creating user account.");
        }
    }
    
    // Set user in session
    $_SESSION['user'] = $user;
    $_SESSION['is_logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    // Redirect to dashboard or to the page the user was trying to access
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        header("Location: $redirect");
    } else {
        header("Location: ../pages_php/dashboard.php");
    }
    exit;
}
?> 