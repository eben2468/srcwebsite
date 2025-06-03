<?php
/**
 * Check Logo Paths
 * This script checks all logo and icon paths to ensure they exist
 */

// Start session first
session_start();

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die("Database configuration file not found.");
}

if (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
} else {
    die("Settings functions file not found.");
}

if (file_exists('icon_functions.php')) {
    require_once 'icon_functions.php';
} else {
    echo "<p class='error'>Icon functions file not found.</p>";
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Check Logo Paths</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        img { max-width: 100px; border: 1px solid #ddd; padding: 5px; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Check Logo Paths</h1>';

// Function to check if a file exists
function checkFilePath($path) {
    // Handle both relative and absolute paths
    $normalizedPath = $path;
    
    // Remove the ../ prefix for file_exists check
    if (strpos($normalizedPath, '../') === 0) {
        $normalizedPath = substr($normalizedPath, 3);
    }
    
    return [
        'original_path' => $path,
        'normalized_path' => $normalizedPath,
        'exists' => file_exists($normalizedPath),
    ];
}

// Check current settings
$logoType = getSetting('logo_type', 'icon');
$logoUrl = getSetting('logo_url', '../images/logo.png');
$systemIcon = getSetting('system_icon', 'university');

echo '<div class="section">';
echo '<h2>Current Settings</h2>';
echo '<table>';
echo '<tr><th>Setting</th><th>Value</th></tr>';
echo '<tr><td>Logo Type</td><td>' . htmlspecialchars($logoType) . '</td></tr>';
echo '<tr><td>Logo URL</td><td>' . htmlspecialchars($logoUrl) . '</td></tr>';
echo '<tr><td>System Icon</td><td>' . htmlspecialchars($systemIcon) . '</td></tr>';
echo '</table>';
echo '</div>';

// Check session values
echo '<div class="section">';
echo '<h2>Session Values</h2>';
echo '<table>';
echo '<tr><th>Setting</th><th>Value</th></tr>';
echo '<tr><td>Logo Type</td><td>' . htmlspecialchars($_SESSION['logo_type'] ?? 'Not set') . '</td></tr>';
echo '<tr><td>Logo URL</td><td>' . htmlspecialchars($_SESSION['logo_url'] ?? 'Not set') . '</td></tr>';
echo '<tr><td>System Icon</td><td>' . htmlspecialchars($_SESSION['system_icon'] ?? 'Not set') . '</td></tr>';
echo '</table>';
echo '</div>';

// Check logo path
echo '<div class="section">';
echo '<h2>Logo Path Check</h2>';

$logoPathCheck = checkFilePath($logoUrl);
$logoClass = $logoPathCheck['exists'] ? 'success' : 'error';

echo '<table>';
echo '<tr><th>Path</th><th>Normalized Path</th><th>Status</th></tr>';
echo '<tr>';
echo '<td>' . htmlspecialchars($logoPathCheck['original_path']) . '</td>';
echo '<td>' . htmlspecialchars($logoPathCheck['normalized_path']) . '</td>';
echo '<td class="' . $logoClass . '">' . ($logoPathCheck['exists'] ? 'Exists' : 'Not Found') . '</td>';
echo '</tr>';
echo '</table>';

if ($logoPathCheck['exists']) {
    echo '<p class="success">Logo file found at: ' . htmlspecialchars($logoPathCheck['normalized_path']) . '</p>';
    echo '<p><img src="' . htmlspecialchars($logoPathCheck['normalized_path']) . '" alt="Logo"></p>';
} else {
    echo '<p class="error">Logo file not found. Please check the path and ensure the file exists.</p>';
}
echo '</div>';

// Check all icon paths
if (function_exists('getAvailableIcons')) {
    echo '<div class="section">';
    echo '<h2>Icon Paths Check</h2>';
    
    $icons = getAvailableIcons();
    
    echo '<table>';
    echo '<tr><th>Icon Name</th><th>Icon Path</th><th>Normalized Path</th><th>Status</th></tr>';
    
    foreach ($icons as $icon) {
        $iconPathCheck = checkFilePath($icon['path']);
        $iconClass = $iconPathCheck['exists'] ? 'success' : 'error';
        $currentIcon = ($icon['value'] === $systemIcon) ? ' (Current)' : '';
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($icon['name']) . $currentIcon . '</td>';
        echo '<td>' . htmlspecialchars($iconPathCheck['original_path']) . '</td>';
        echo '<td>' . htmlspecialchars($iconPathCheck['normalized_path']) . '</td>';
        echo '<td class="' . $iconClass . '">' . ($iconPathCheck['exists'] ? 'Exists' : 'Not Found') . '</td>';
        echo '</tr>';
        
        if ($icon['value'] === $systemIcon && !$iconPathCheck['exists']) {
            echo '<tr><td colspan="4" class="error">Current icon is set to "' . 
                htmlspecialchars($icon['name']) . '" but the file does not exist!</td></tr>';
        }
    }
    
    echo '</table>';
    
    // Display all found icons
    echo '<h3>Available Icons</h3>';
    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
    
    foreach ($icons as $icon) {
        $iconPathCheck = checkFilePath($icon['path']);
        if ($iconPathCheck['exists']) {
            echo '<div style="text-align: center; margin: 5px;">';
            echo '<img src="' . htmlspecialchars($iconPathCheck['normalized_path']) . '" alt="' . 
                htmlspecialchars($icon['name']) . '" style="height: 48px; object-fit: contain;">';
            echo '<div>' . htmlspecialchars($icon['name']) . '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
    echo '</div>';
}

// Check images directory
$imagesDir = 'images';
echo '<div class="section">';
echo '<h2>Images Directory Check</h2>';

if (file_exists($imagesDir) && is_dir($imagesDir)) {
    echo '<p class="success">Images directory exists.</p>';
    
    // List image files
    $imageFiles = glob($imagesDir . '/*.{jpg,jpeg,png,gif,svg}', GLOB_BRACE);
    
    if (count($imageFiles) > 0) {
        echo '<p>Found ' . count($imageFiles) . ' image files:</p>';
        echo '<ul>';
        foreach ($imageFiles as $imageFile) {
            echo '<li>' . htmlspecialchars($imageFile) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="warning">No image files found in the images directory.</p>';
    }
    
    // Check icons subdirectory
    $iconsDir = $imagesDir . '/icons';
    if (file_exists($iconsDir) && is_dir($iconsDir)) {
        echo '<p class="success">Icons directory exists.</p>';
        
        // List icon files
        $iconFiles = glob($iconsDir . '/*.{svg,png}', GLOB_BRACE);
        
        if (count($iconFiles) > 0) {
            echo '<p>Found ' . count($iconFiles) . ' icon files:</p>';
            echo '<ul>';
            foreach ($iconFiles as $iconFile) {
                echo '<li>' . htmlspecialchars($iconFile) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="error">No icon files found in the icons directory.</p>';
        }
    } else {
        echo '<p class="error">Icons directory does not exist. This may cause issues with system icons.</p>';
    }
} else {
    echo '<p class="error">Images directory does not exist or is not accessible.</p>';
}

echo '</div>';

// Recommendations
echo '<div class="section">';
echo '<h2>Recommendations</h2>';
echo '<ul>';

if ($logoType === 'custom' && !$logoPathCheck['exists']) {
    echo '<li class="error">Your custom logo file is missing. Please upload a new logo using the <a href="force_logo_update.php">Force Logo Update</a> tool.</li>';
}

if ($logoType === 'icon') {
    $currentIconFound = false;
    foreach ($icons as $icon) {
        if ($icon['value'] === $systemIcon) {
            $iconPathCheck = checkFilePath($icon['path']);
            $currentIconFound = $iconPathCheck['exists'];
            break;
        }
    }
    
    if (!$currentIconFound) {
        echo '<li class="error">Your selected system icon is missing. Please select a different icon using the <a href="force_logo_update.php">Force Logo Update</a> tool.</li>';
    }
}

if (isset($_SESSION['logo_type']) && $_SESSION['logo_type'] !== $logoType) {
    echo '<li class="warning">Your session logo type does not match the database setting. This may cause display issues. Consider clearing the session.</li>';
}

if (isset($_SESSION['system_icon']) && $_SESSION['system_icon'] !== $systemIcon && $logoType === 'icon') {
    echo '<li class="warning">Your session system icon does not match the database setting. This may cause display issues. Consider clearing the session.</li>';
}

echo '</ul>';

// Quick fixes
echo '<h3>Quick Fixes</h3>';
echo '<ul>';
echo '<li><a href="update_headers.php">Update Headers</a> - Ensure all header files include required files</li>';
echo '<li><a href="force_logo_update.php">Force Logo Update</a> - Update logo settings and upload new logos</li>';
echo '<li><a href="clear_cache.php">Clear Cache</a> - Clear browser and PHP cache</li>';
echo '</ul>';
echo '</div>';

echo '</body>
</html>';
?> 