<?php
/**
 * Icon Placeholder Generator
 * This script creates placeholder icons for demonstration purposes
 */

// Define the icon directory
$iconDir = 'images/icons';

// Make sure the directory exists
if (!is_dir($iconDir)) {
    if (!mkdir($iconDir, 0755, true)) {
        die("Failed to create directory: $iconDir");
    }
    echo "Created directory: $iconDir\n";
}

// List of icons to create
$icons = [
    'church' => [
        'name' => 'Church',
        'color' => '#3366cc',
        'symbol' => '⛪'
    ],
    'cross' => [
        'name' => 'Cross',
        'color' => '#cc3366',
        'symbol' => '✝️'
    ],
    'dove' => [
        'name' => 'Dove',
        'color' => '#66cc33',
        'symbol' => '🕊️'
    ],
    'praying_hands' => [
        'name' => 'Praying Hands',
        'color' => '#cc9933',
        'symbol' => '🙏'
    ],
    'bible' => [
        'name' => 'Bible',
        'color' => '#9933cc',
        'symbol' => '📖'
    ]
];

// Create simple HTML files for each icon
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
    
    // Create a simple PNG filename that's just a copy of a blank image
    // This is just so that files referenced in code will actually exist
    if (file_exists('images/logo.png')) {
        copy('images/logo.png', "$iconDir/$key.png");
        echo "Created PNG placeholder: $iconDir/$key.png\n";
    }
    
    echo "Created HTML fallback: $htmlPath\n";
}

echo "All icon placeholders created successfully!\n";
?> 