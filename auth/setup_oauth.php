<?php
// Include the OAuth configuration
require_once 'oauth_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Setup Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">OAuth Setup Guide</h1>
        
        <div class="alert alert-info">
            <p><strong>Note:</strong> This page provides instructions on how to set up OAuth credentials for social login.</p>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Current OAuth Configuration</h2>
            </div>
            <div class="card-body">
                <h3 class="h5">Google OAuth</h3>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Client ID
                        <span class="badge bg-<?php echo (strpos($googleConfig['client_id'], 'YOUR_') !== false || strpos($googleConfig['client_id'], 'example') !== false) ? 'danger' : 'success'; ?>">
                            <?php echo htmlspecialchars(substr($googleConfig['client_id'], 0, 10) . '...'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Client Secret
                        <span class="badge bg-<?php echo (strpos($googleConfig['client_secret'], 'YOUR_') !== false || strpos($googleConfig['client_secret'], 'example') !== false) ? 'danger' : 'success'; ?>">
                            <?php echo htmlspecialchars(substr($googleConfig['client_secret'], 0, 5) . '...'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Redirect URI
                        <span class="badge bg-secondary">
                            <?php echo htmlspecialchars($googleConfig['redirect_uri']); ?>
                        </span>
                    </li>
                </ul>
                
                <h3 class="h5">Facebook OAuth</h3>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        App ID
                        <span class="badge bg-<?php echo (strpos($facebookConfig['app_id'], 'YOUR_') !== false || strpos($facebookConfig['app_id'], 'example') !== false) ? 'danger' : 'success'; ?>">
                            <?php echo htmlspecialchars(substr($facebookConfig['app_id'], 0, 10) . '...'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        App Secret
                        <span class="badge bg-<?php echo (strpos($facebookConfig['app_secret'], 'YOUR_') !== false || strpos($facebookConfig['app_secret'], 'example') !== false) ? 'danger' : 'success'; ?>">
                            <?php echo htmlspecialchars(substr($facebookConfig['app_secret'], 0, 5) . '...'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Redirect URI
                        <span class="badge bg-secondary">
                            <?php echo htmlspecialchars($facebookConfig['redirect_uri']); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Google OAuth Setup</h2>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                    <li>Create a new project or select an existing one</li>
                    <li>Navigate to "APIs & Services" > "Credentials"</li>
                    <li>Click "Create Credentials" > "OAuth client ID"</li>
                    <li>Configure the OAuth consent screen:
                        <ul>
                            <li>Add your app name, user support email, and developer contact information</li>
                            <li>Add the necessary scopes (email, profile)</li>
                        </ul>
                    </li>
                    <li>Create the OAuth client ID:
                        <ul>
                            <li>Select "Web application" as the application type</li>
                            <li>Add a name for your client</li>
                            <li>Add your redirect URI: <code><?php echo htmlspecialchars($googleConfig['redirect_uri']); ?></code></li>
                        </ul>
                    </li>
                    <li>Google will provide you with a Client ID and Client Secret</li>
                    <li>Update the <code>oauth_config.php</code> file with these values</li>
                </ol>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Facebook OAuth Setup</h2>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Go to the <a href="https://developers.facebook.com/" target="_blank">Facebook Developer Portal</a></li>
                    <li>Create a new app or select an existing one</li>
                    <li>Navigate to "Settings" > "Basic"</li>
                    <li>Find your App ID and App Secret</li>
                    <li>Under "Products", add "Facebook Login":
                        <ul>
                            <li>Configure the settings for Facebook Login</li>
                            <li>Add your redirect URI: <code><?php echo htmlspecialchars($facebookConfig['redirect_uri']); ?></code></li>
                            <li>Enable the necessary permissions (email, public_profile)</li>
                        </ul>
                    </li>
                    <li>Update the <code>oauth_config.php</code> file with these values</li>
                </ol>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="h5 mb-0">Updating the Configuration</h2>
            </div>
            <div class="card-body">
                <p>To update your OAuth configuration, edit the following file:</p>
                <pre class="bg-light p-3"><code><?php echo htmlspecialchars(dirname(__FILE__) . '/oauth_config.php'); ?></code></pre>
                
                <p>The file should look like this (with your actual credentials):</p>
                <pre class="bg-light p-3"><code>&lt;?php
// Base URL for your application
$baseUrl = 'http://localhost/srcwebsite';

// Google OAuth Configuration
$googleConfig = [
    'client_id'     => '123456789012-abcdefghijklmnopqrst.apps.googleusercontent.com',
    'client_secret' => 'GOCSPX-abcdefghijklmnopqrstuvwxyz',
    'redirect_uri'  => $baseUrl . '/auth/google_auth.php'
];

// Facebook OAuth Configuration
$facebookConfig = [
    'app_id'        => '1234567890123456',
    'app_secret'    => 'abcdefghijklmnopqrstuvwxyz123456',
    'redirect_uri'  => $baseUrl . '/auth/facebook_auth.php'
];
?&gt;</code></pre>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="../pages_php/login.php" class="btn btn-primary">Back to Login Page</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 