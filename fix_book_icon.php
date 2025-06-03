<?php
/**
 * Fix Book Icon
 * This script directly sets the system icon to book and clears all caches
 */

// Start session first
session_start();

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die("Database configuration file not found.");
}

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Directly update the database
$logoType = 'icon';
$systemIcon = 'book';

// Update the settings in the database
$updateLogoType = "UPDATE settings SET setting_value = ? WHERE setting_key = 'logo_type'";
$stmtLogoType = mysqli_prepare($conn, $updateLogoType);
mysqli_stmt_bind_param($stmtLogoType, 's', $logoType);
$successLogoType = mysqli_stmt_execute($stmtLogoType);

$updateSystemIcon = "UPDATE settings SET setting_value = ? WHERE setting_key = 'system_icon'";
$stmtSystemIcon = mysqli_prepare($conn, $updateSystemIcon);
mysqli_stmt_bind_param($stmtSystemIcon, 's', $systemIcon);
$successSystemIcon = mysqli_stmt_execute($stmtSystemIcon);

// Clear all session variables related to logo
unset($_SESSION['logo_type']);
unset($_SESSION['logo_url']);
unset($_SESSION['system_icon']);

// Set fresh session variables
$_SESSION['logo_type'] = $logoType;
$_SESSION['system_icon'] = $systemIcon;

// Touch the icon file to update its timestamp
$iconFile = 'images/icons/book.svg';
$touchSuccess = false;
if (file_exists($iconFile)) {
    $touchSuccess = touch($iconFile);
}

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Book Icon</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        img { max-width: 100px; height: auto; }
    </style>
    <meta http-equiv="refresh" content="5;url=pages_php/dashboard.php">
    <script>
        // Force reload all images
        window.onload = function() {
            var cacheBreaker = '?v=' + new Date().getTime();
            var images = document.getElementsByTagName('img');
            for (var i = 0; i < images.length; i++) {
                var originalSrc = images[i].src.split('?')[0];
                images[i].src = originalSrc + cacheBreaker;
            }
        };
    </script>
</head>
<body>
    <h1>Fix Book Icon</h1>
    
    <div class="panel">
        <h2>Update Results</h2>
        
        <?php if ($successLogoType): ?>
            <p class="success">Logo type updated to: <?php echo htmlspecialchars($logoType); ?></p>
        <?php else: ?>
            <p class="error">Failed to update logo type</p>
        <?php endif; ?>
        
        <?php if ($successSystemIcon): ?>
            <p class="success">System icon updated to: <?php echo htmlspecialchars($systemIcon); ?></p>
        <?php else: ?>
            <p class="error">Failed to update system icon</p>
        <?php endif; ?>
        
        <p>Session variables cleared and reset.</p>
        
        <?php if ($touchSuccess): ?>
            <p class="success">Icon file timestamp updated.</p>
        <?php else: ?>
            <p class="warning">Could not update icon file timestamp.</p>
        <?php endif; ?>
    </div>
    
    <div class="panel">
        <h2>Current Book Icon</h2>
        <?php if (file_exists($iconFile)): ?>
            <img src="<?php echo htmlspecialchars($iconFile . '?v=' . time()); ?>" alt="Book Icon">
        <?php else: ?>
            <p class="error">Book icon file not found at: <?php echo htmlspecialchars($iconFile); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="panel">
        <h2>Next Steps</h2>
        <p>You will be automatically redirected to the dashboard in 5 seconds.</p>
        <p>If you're not redirected, <a href="pages_php/dashboard.php">click here</a>.</p>
        <p>After being redirected:</p>
        <ol>
            <li>If the icon still hasn't changed, press Ctrl+F5 to force refresh the page</li>
            <li>If the issue persists, try restarting your browser</li>
        </ol>
    </div>
</body>
</html> 