<?php
/**
 * Fix Logo Cache Issues
 * This script clears logo-related cache and fixes any issues with logo loading
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'db_config.php';
if (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
}
if (file_exists('icon_functions.php')) {
    require_once 'icon_functions.php';
}

// Get logo settings from database
$logoType = getSetting('logo_type', 'icon');
$logoUrl = getSetting('logo_url', '../images/logo.png');
$systemIcon = getSetting('system_icon', 'university');

// Clear all logo-related session variables
unset($_SESSION['logo_type']);
unset($_SESSION['logo_url']);
unset($_SESSION['system_icon']);

// Re-set session variables with fresh data from database
$_SESSION['logo_type'] = $logoType;
$_SESSION['logo_url'] = $logoUrl;
$_SESSION['system_icon'] = $systemIcon;

// Add cache-busting parameter to force image reload
$cacheBuster = '?v=' . time();

// Verify logo file exists
$logoFileExists = false;
$logoOutput = '';
if ($logoType === 'custom' && !empty($logoUrl)) {
    $logoPath = str_replace('../', '', $logoUrl);
    $logoFileExists = file_exists($logoPath);
    
    if (!$logoFileExists) {
        $logoOutput .= "Warning: Custom logo file not found at {$logoPath}<br>";
    } else {
        $logoOutput .= "Custom logo file found at {$logoPath}<br>";
    }
}

// Verify system icon exists
$iconFileExists = false;
$iconOutput = '';
if ($logoType === 'icon' && !empty($systemIcon)) {
    $iconInfo = getIconInfo($systemIcon);
    if ($iconInfo) {
        $iconPath = str_replace('../', '', $iconInfo['path']);
        $iconFileExists = file_exists($iconPath);
        
        if (!$iconFileExists) {
            $iconOutput .= "Warning: System icon file not found at {$iconPath}<br>";
        } else {
            $iconOutput .= "System icon file found at {$iconPath}<br>";
        }
    }
}

// Now output all HTML
echo $logoOutput;
echo $iconOutput;

echo "<h1>Logo Cache Fixed</h1>";
echo "<p>Logo settings:</p>";
echo "<ul>";
echo "<li>Logo Type: {$logoType}</li>";
echo "<li>Logo URL: {$logoUrl}</li>";
echo "<li>System Icon: {$systemIcon}</li>";
echo "</ul>";

// Provide links to test the fix
echo "<p>Please try refreshing the page to see if the logo has been updated.</p>";
echo "<p>If the issue persists, you can:</p>";
echo "<ol>";
echo "<li><a href='clear_cache.php'>Clear all caches</a></li>";
echo "<li><a href='pages_php/settings.php'>Go back to settings</a></li>";
echo "<li><a href='pages_php/dashboard.php'>Go to dashboard</a></li>";
echo "</ol>";

// Force reload of images
echo "<script>
    // Force browser to reload images
    window.onload = function() {
        const images = document.getElementsByTagName('img');
        for (let i = 0; i < images.length; i++) {
            const originalSrc = images[i].src.split('?')[0];
            images[i].src = originalSrc + '{$cacheBuster}';
        }
    }
</script>";
?> 