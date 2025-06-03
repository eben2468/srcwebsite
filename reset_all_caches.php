<?php
/**
 * Reset All Caches
 * This script forces all caches to be reset and applies the book icon
 */

// Include FORCE_ICON constant
define('FORCE_ICON', true);

// Start session
session_start();

// Include database config
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die("Database configuration file not found");
}

// Include required functions
if (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
}

if (file_exists('icon_functions.php')) {
    require_once 'icon_functions.php';
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Start a new session
session_start();

// Set critical session variables
$_SESSION['logo_type'] = 'icon';
$_SESSION['system_icon'] = 'book';

// Update database settings
$updateResults = [];

// Update system_icon setting
$sql = "UPDATE settings SET setting_value = 'book' WHERE setting_key = 'system_icon'";
$iconResult = mysqli_query($conn, $sql);
$updateResults['system_icon'] = mysqli_affected_rows($conn);

// Update logo_type setting
$sql = "UPDATE settings SET setting_value = 'icon' WHERE setting_key = 'logo_type'";
$logoTypeResult = mysqli_query($conn, $sql);
$updateResults['logo_type'] = mysqli_affected_rows($conn);

// Update all icon files
$iconFiles = [
    'images/icons/book.svg',
    '../images/icons/book.svg',
    'pages_php/images/icons/book.svg'
];

$fileResults = [];
foreach ($iconFiles as $file) {
    $fileExists = file_exists($file);
    if ($fileExists) {
        touch($file);
        $fileResults[$file] = [
            'exists' => true,
            'updated' => true
        ];
    } else {
        $fileResults[$file] = [
            'exists' => false,
            'updated' => false
        ];
    }
}

// Set headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset All Caches</title>
    <meta http-equiv="refresh" content="3;url=pages_php/dashboard.php">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        h1, h2 { color: #333; }
        .reload-button { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: #0066ff; 
            color: white; 
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: #0066ff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    <script>
        // Force a complete cache reset
        window.onload = function() {
            // Clear all browser storage
            try {
                localStorage.clear();
                sessionStorage.clear();
                
                // Clear cookies
                var cookies = document.cookie.split(";");
                for (var i = 0; i < cookies.length; i++) {
                    var cookie = cookies[i];
                    var eqPos = cookie.indexOf("=");
                    var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                    document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
                }
                
                // Set a flag to force reload icons
                localStorage.setItem('force_icon_refresh', 'true');
                localStorage.setItem('icon_timestamp', Date.now());
            } catch (e) {
                console.error("Error clearing cache:", e);
            }
            
            // Force reload all images
            document.querySelectorAll('img').forEach(function(img) {
                var src = img.src;
                img.src = src.split('?')[0] + '?v=' + Date.now();
            });
        };
    </script>
</head>
<body>
    <h1>Reset All Caches</h1>
    
    <div class="container">
        <h2>Cache Reset</h2>
        <p class="success">All browser caches have been cleared.</p>
        <p class="success">All session variables have been reset.</p>
        <p>You will be redirected to the dashboard in 3 seconds.</p>
        <div class="spinner"></div> <span>Resetting system...</span>
    </div>
    
    <div class="container">
        <h2>Database Updates</h2>
        <p>System Icon: <span class="<?php echo $iconResult ? 'success' : 'error'; ?>">
            <?php echo $iconResult ? 'Updated to book' : 'Failed to update'; ?>
        </span></p>
        <p>Logo Type: <span class="<?php echo $logoTypeResult ? 'success' : 'error'; ?>">
            <?php echo $logoTypeResult ? 'Updated to icon' : 'Failed to update'; ?>
        </span></p>
    </div>
    
    <div class="container">
        <h2>Icon Files</h2>
        <?php foreach ($fileResults as $file => $result): ?>
        <p><?php echo htmlspecialchars($file); ?>: 
            <span class="<?php echo $result['exists'] ? 'success' : 'error'; ?>">
                <?php echo $result['exists'] ? 'Exists and updated' : 'File not found'; ?>
            </span>
        </p>
        <?php endforeach; ?>
    </div>
    
    <div class="container">
        <h2>Next Steps</h2>
        <p>If the dashboard does not show the correct icon after redirect:</p>
        <ol>
            <li>Press <strong>Ctrl+F5</strong> to force a complete browser refresh</li>
            <li>Clear your browser cache manually</li>
            <li>Try using a different browser</li>
        </ol>
        <a href="pages_php/dashboard.php" class="reload-button">Go to Dashboard Now</a>
    </div>
</body>
</html> 