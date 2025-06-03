<?php
// Include required files
require_once 'db_config.php';
require_once 'settings_functions.php';

// Check if icon_functions.php exists
echo "Checking for icon_functions.php... ";
if (file_exists('icon_functions.php')) {
    echo "FOUND\n";
    require_once 'icon_functions.php';
} else {
    echo "NOT FOUND\n";
    exit("Icon functions file not found. Please ensure icon_functions.php exists in the root directory.");
}

// Get current icon settings
$logoType = getSetting('logo_type', 'icon');
$systemIcon = getSetting('system_icon', 'university');
$logoUrl = getSetting('logo_url', './images/logo.png');

echo "\nCurrent Settings:\n";
echo "Logo Type: $logoType\n";
echo "System Icon: $systemIcon\n";
echo "Logo URL: $logoUrl\n";

// Check if icons directory exists
echo "\nChecking for icons directory... ";
if (is_dir('images/icons')) {
    echo "FOUND\n";
} else {
    echo "NOT FOUND\n";
    exit("Icons directory not found. Please ensure the 'images/icons' directory exists.");
}

// Get available icons
$icons = getAvailableIcons();
echo "\nAvailable Icons: " . count($icons) . "\n";

// Check for each icon file
echo "\nChecking icon files:\n";
foreach ($icons as $icon) {
    $path = str_replace('../', '', $icon['path']);
    echo $icon['name'] . " (" . $icon['value'] . "): ";
    
    if (file_exists($path)) {
        echo "FOUND (" . filesize($path) . " bytes)\n";
    } else {
        echo "NOT FOUND\n";
    }
}

// Check current icon
echo "\nChecking current icon:\n";
$currentIconInfo = getIconInfo($systemIcon);

if ($currentIconInfo) {
    $currentIconPath = str_replace('../', '', $currentIconInfo['path']);
    echo "Current Icon Path: $currentIconPath\n";
    
    if (file_exists($currentIconPath)) {
        echo "Current Icon File: FOUND (" . filesize($currentIconPath) . " bytes)\n";
    } else {
        echo "Current Icon File: NOT FOUND\n";
    }
} else {
    echo "Current Icon Info: NOT FOUND\n";
}

// Check if files can be created in the icons directory
echo "\nChecking write permissions for icons directory... ";
$testFile = 'images/icons/test_write.txt';
if (is_writable('images/icons')) {
    echo "WRITABLE\n";
    // Try to create a test file
    if (file_put_contents($testFile, 'Test write')) {
        echo "Successfully created test file\n";
        // Clean up
        unlink($testFile);
    } else {
        echo "Failed to create test file\n";
    }
} else {
    echo "NOT WRITABLE\n";
}

echo "\nDiagnostic complete. If issues persist, run the create_education_icons.php script to regenerate icon files.\n";
?> 