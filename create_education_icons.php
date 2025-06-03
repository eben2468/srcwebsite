<?php
/**
 * Education Icon Generator
 * This script creates placeholder icons for education/university purposes
 */

// Define the icon directories
$iconDir = 'images/icons';
$relativeIconDir = '../images/icons'; // For relative paths used in pages_php

// Make sure the directories exist
if (!is_dir($iconDir)) {
    if (!mkdir($iconDir, 0755, true)) {
        die("Failed to create directory: $iconDir");
    }
    echo "Created directory: $iconDir\n";
}

// List of education-related icons to create
$icons = [
    'university' => [
        'name' => 'University',
        'color' => '#3366cc',
        'symbol' => '🏛️'
    ],
    'graduation_cap' => [
        'name' => 'Graduation Cap',
        'color' => '#cc3366',
        'symbol' => '🎓'
    ],
    'book' => [
        'name' => 'Book',
        'color' => '#66cc33',
        'symbol' => '📚'
    ],
    'students' => [
        'name' => 'Students',
        'color' => '#cc9933',
        'symbol' => '👨‍🎓'
    ],
    'school' => [
        'name' => 'School',
        'color' => '#9933cc',
        'symbol' => '🏫'
    ],
    'src_badge' => [
        'name' => 'SRC Badge',
        'color' => '#33cc99',
        'symbol' => '🔰'
    ],
    'campus' => [
        'name' => 'Campus',
        'color' => '#cc6633',
        'symbol' => '🏢'
    ]
];

// Create simple SVG files for each icon
foreach ($icons as $key => $icon) {
    // Create a simple SVG placeholder
    $svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    <rect width="100" height="100" fill="' . $icon['color'] . '" />
    <text x="50" y="60" font-family="Arial" font-size="40" text-anchor="middle" fill="white">' . $icon['symbol'] . '</text>
</svg>';

    // Save the SVG file
    $svgPath = "$iconDir/$key.svg";
    file_put_contents($svgPath, $svgContent);
    echo "Created SVG icon: $svgPath\n";
    
    // Also create a simple HTML fallback
    $htmlContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>' . $icon['name'] . ' Icon</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: ' . $icon['color'] . ';
        }
        .icon {
            font-size: 50px;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="icon">' . $icon['symbol'] . '</div>
</body>
</html>';

    // Save the HTML file
    $htmlPath = "$iconDir/$key.html";
    file_put_contents($htmlPath, $htmlContent);
    
    // Create a PNG placeholder - just copy logo.png if it exists
    if (file_exists('images/logo.png')) {
        copy('images/logo.png', "$iconDir/$key.png");
        echo "Created PNG placeholder: $iconDir/$key.png\n";
    }
    
    echo "Created HTML fallback: $htmlPath\n";
}

// Set university as the default icon in settings
try {
    require_once 'db_config.php';
    require_once 'settings_functions.php';
    
    // Update system_icon setting directly in database
    $updateSql = "UPDATE settings SET setting_value = 'university' WHERE setting_key = 'system_icon'";
    if (mysqli_query($conn, $updateSql)) {
        echo "Updated default icon to 'university' in settings\n";
    } else {
        echo "Failed to update default icon in settings\n";
    }
} catch (Exception $e) {
    echo "Error updating settings: " . $e->getMessage() . "\n";
}

echo "All education icon placeholders created successfully!\n";
?> 