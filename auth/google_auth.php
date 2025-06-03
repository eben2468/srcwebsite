<?php
/**
 * Google OAuth Authentication Handler
 * 
 * This file handles the Google OAuth authentication flow.
 * In a real implementation, you would need to:
 * 1. Register your application with Google Developer Console
 * 2. Get client ID and client secret
 * 3. Configure OAuth consent screen
 * 4. Set up redirect URIs
 * 
 * For a complete implementation, you would use Google's PHP client library:
 * https://github.com/googleapis/google-api-php-client
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db_config.php';
require_once '../auth_functions.php';
require_once 'oauth_config.php';

// Configuration from oauth_config.php
$clientId = $googleConfig['client_id'];
$clientSecret = $googleConfig['client_secret'];
$redirectUri = $googleConfig['redirect_uri'];

// Check if we're in development mode
$devMode = isset($_GET['dev_mode']) && $_GET['dev_mode'] === 'true';

// If in development mode, show simulation page
if ($devMode) {
    simulateLoginPage();
    exit;
}

// Check if this is the OAuth callback (Google will redirect back with a 'code' parameter)
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
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    // Use cURL to make the token request
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
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
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    
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
    $googleUserId = $userInfo['id'];
    $email = $userInfo['email'] ?? '';
    $firstName = $userInfo['given_name'] ?? '';
    $lastName = $userInfo['family_name'] ?? '';
    $profilePicture = $userInfo['picture'] ?? '';
    
    // Check if user exists in our database
    $sql = "SELECT * FROM users WHERE oauth_provider = 'google' AND oauth_id = ? LIMIT 1";
    $user = fetchOne($sql, [$googleUserId]);
    
    if (!$user) {
        // Also check by email as a fallback
        if (!empty($email)) {
            $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
            $user = fetchOne($sql, [$email]);
        }
        
        if (!$user) {
            // User doesn't exist, create a new one
            $username = 'google_' . substr(md5($googleUserId), 0, 8);
            $role = 'user'; // Default role for social login users
            $status = 'Active';
            
            // Generate a random password (user won't need this as they'll login via Google)
            $password = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, oauth_provider, oauth_id, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'google', ?, NOW(), NOW())";
            
            $params = [$username, $hashedPassword, $email, $firstName, $lastName, $role, $status, $googleUserId];
            
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
                    'oauth_provider' => 'google',
                    'oauth_id' => $googleUserId
                ];
            } else {
                die("Error creating user account.");
            }
        } else {
            // User exists by email but not with Google OAuth, update their record
            $updateSql = "UPDATE users SET oauth_provider = 'google', oauth_id = ? WHERE user_id = ?";
            executeQuery($updateSql, [$googleUserId, $user['user_id']]);
            
            // Update user data with Google information if fields are empty
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
    if (strpos($clientId, 'YOUR_') !== false || strpos($clientId, 'example') !== false) {
        header("Location: ?dev_mode=true");
        exit;
    }
    
    // This is the initial OAuth request
    // Generate a random state parameter to prevent CSRF attacks
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    // Build the authorization URL
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'email profile',
        'state' => $state,
        'prompt' => 'select_account'
    ];
    
    // Redirect to Google's OAuth endpoint
    header('Location: ' . $authUrl . '?' . http_build_query($params));
    exit;
}

/**
 * Display a simulated Google login page for development
 */
function simulateLoginPage() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Google Login Simulation</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f8f9fa;
                font-family: 'Roboto', Arial, sans-serif;
            }
            .google-card {
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                max-width: 450px;
                margin: 100px auto;
                padding: 48px 40px;
            }
            .google-header {
                text-align: center;
                margin-bottom: 32px;
            }
            .google-logo {
                margin-bottom: 16px;
            }
            .google-logo span {
                font-size: 24px;
                font-weight: 500;
            }
            .google-logo .blue { color: #4285F4; }
            .google-logo .red { color: #EA4335; }
            .google-logo .yellow { color: #FBBC05; }
            .google-logo .green { color: #34A853; }
            h1 {
                font-size: 24px;
                font-weight: 400;
                margin-bottom: 8px;
            }
            .btn-google {
                background-color: #4285F4;
                color: white;
                font-weight: 500;
                border: none;
                border-radius: 4px;
                padding: 12px;
                width: 100%;
                font-size: 1rem;
            }
            .btn-google:hover {
                background-color: #3367D6;
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
            <div class="google-card">
                <div class="development-notice">
                    <strong>Development Mode</strong><br>
                    This is a simulated Google login for development purposes.
                </div>
                
                <div class="google-header">
                    <div class="google-logo">
                        <span class="blue">G</span>
                        <span class="red">o</span>
                        <span class="yellow">o</span>
                        <span class="blue">g</span>
                        <span class="green">l</span>
                        <span class="red">e</span>
                    </div>
                    <h1>Sign in</h1>
                    <p>to continue to SRC Management System</p>
                </div>
                
                <form action="?code=dev_simulation" method="post" class="mb-4">
                    <div class="mb-3">
                        <input type="email" class="form-control form-control-lg" placeholder="Email or phone" value="google_user@gmail.com">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control form-control-lg" placeholder="Password" value="••••••••">
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-google">Sign in</button>
                    </div>
                </form>
                
                <div class="text-center">
                    <p>This is a simulation. In a real implementation, you would be redirected to the actual Google login page.</p>
                    <p><a href="../pages_php/login.php">Back to Login</a> | <a href="../auth/setup_oauth.php">OAuth Setup Guide</a></p>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Simulate a successful login with Google
 */
function simulateSuccessfulLogin() {
    global $conn;
    
    // Create simulated Google user data
    $googleUserId = 'google_sim_' . substr(md5(time()), 0, 10);
    $email = 'google_user_' . rand(100, 999) . '@gmail.com';
    $firstName = 'Google';
    $lastName = 'User';
    
    // Check if user exists in our database
    $sql = "SELECT * FROM users WHERE oauth_provider = 'google' AND oauth_id = ? LIMIT 1";
    $user = fetchOne($sql, [$googleUserId]);
    
    if (!$user) {
        // User doesn't exist, create a new one
        $username = 'google_' . substr(md5($googleUserId), 0, 8);
        $role = 'user'; // Default role for social login users
        $status = 'Active';
        
        // Generate a random password (user won't need this as they'll login via Google)
        $password = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertSql = "INSERT INTO users (username, password, email, first_name, last_name, role, status, oauth_provider, oauth_id, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'google', ?, NOW(), NOW())";
        
        $params = [$username, $hashedPassword, $email, $firstName, $lastName, $role, $status, $googleUserId];
        
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
                'oauth_provider' => 'google',
                'oauth_id' => $googleUserId
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