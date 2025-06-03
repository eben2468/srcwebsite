<?php
/**
 * Generate Educational Icons
 * This script creates SVG icons for educational purposes
 */

// Define icon directory
$iconDir = 'images/icons';

// Ensure directory exists
if (!is_dir($iconDir)) {
    if (!mkdir($iconDir, 0755, true)) {
        die("Failed to create directory: $iconDir");
    }
    echo "Created directory: $iconDir\n";
}

// Define educational icons with SVG content
$icons = [
    'university' => [
        'name' => 'University',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#3366cc" rx="8" ry="8"/>
            <path d="M24 4L4 14v4h40v-4L24 4zm-12 12h-4v16h4V16zm8 0h-4v16h4V16zm8 0h-4v16h4V16zm8 0h-4v16h4V16zm8 0h-4v16h4V16zM6 36v4h36v-4H6z" fill="#fff"/>
        </svg>'
    ],
    'graduation_cap' => [
        'name' => 'Graduation Cap',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#cc3366" rx="8" ry="8"/>
            <path d="M24 6l-20 10 20 10 16-8v11c0 0-7 5-16 5s-16-5-16-5v-11l4 2v9l2 2c3 2 10 3 10 3s7-1 10-3l2-2v-13l-12 6-16-8z" fill="#fff"/>
        </svg>'
    ],
    'book' => [
        'name' => 'Book',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#66cc33" rx="8" ry="8"/>
            <path d="M10 8c-1.1 0-2 .9-2 2v28c0 1.1.9 2 2 2h28c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2H10zm0 2h12v28H10V10zm14 0h14v28H24V10zm7 4c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5z" fill="#fff"/>
        </svg>'
    ],
    'students' => [
        'name' => 'Students',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#cc9933" rx="8" ry="8"/>
            <circle cx="16" cy="14" r="6" fill="#fff"/>
            <circle cx="32" cy="14" r="6" fill="#fff"/>
            <path d="M16 22c-5.5 0-10 4.5-10 10v6h20v-6c0-5.5-4.5-10-10-10zm16 0c-5.5 0-10 4.5-10 10v6h20v-6c0-5.5-4.5-10-10-10z" fill="#fff"/>
        </svg>'
    ],
    'school' => [
        'name' => 'School',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#9933cc" rx="8" ry="8"/>
            <path d="M24 4L6 14v2h36v-2L24 4zm-14 14h-2v18h2V18zm6 0h-2v18h2V18zm6 0h-2v18h2V18zm6 0h-2v18h2V18zm6 0h-2v18h2V18zm6 0h-2v18h2V18zM8 38v4h32v-4H8z" fill="#fff"/>
        </svg>'
    ],
    'src_badge' => [
        'name' => 'SRC Badge',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#33cc99" rx="8" ry="8"/>
            <path d="M24 4L8 12v12c0 8.8 6.8 16.9 16 18 9.2-1.1 16-9.2 16-18V12L24 4z" fill="#fff"/>
            <path d="M18 22c0-3.3 2.7-6 6-6s6 2.7 6 6c0 2-1 3.8-2.5 4.9L30 33h-4l-2-6h-4.1c-1.1-1.2-1.9-2.9-1.9-5zm6 2c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" fill="#33cc99"/>
            <path d="M15 18l-2 2 2 2h4v-4h-4zm14 0v4h4l2-2-2-2h-4z" fill="#33cc99"/>
        </svg>'
    ],
    'campus' => [
        'name' => 'Campus',
        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
            <rect width="48" height="48" fill="#cc6633" rx="8" ry="8"/>
            <path d="M8 10v28h10V10H8zm22 0v28h10V10H30zm-20 4h6v4h-6v-4zm22 0h6v4h-6v-4zm-22 8h6v4h-6v-4zm22 0h6v4h-6v-4zm-22 8h6v4h-6v-4zm22 0h6v4h-6v-4zM20 16v16h8V16h-8zm2 2h4v4h-4v-4zm0 8h4v4h-4v-4z" fill="#fff"/>
        </svg>'
    ]
];

// Generate icon files
foreach ($icons as $key => $icon) {
    // Save SVG file
    $svgPath = "$iconDir/$key.svg";
    file_put_contents($svgPath, $icon['svg']);
    echo "Generated SVG icon: $svgPath\n";
    
    // Create a simple PNG filename that's just a copy of a blank image
    // This is just so that files referenced in code will actually exist
    if (file_exists('images/logo.png')) {
        copy('images/logo.png', "$iconDir/$key.png");
        echo "Created PNG placeholder: $iconDir/$key.png\n";
    }
}

// Update settings to use 'students' as default icon
try {
    require_once 'db_config.php';
    
    // Update system_icon setting
    $defaultIcon = 'students';
    $updateSQL = "UPDATE settings SET setting_value = '$defaultIcon' WHERE setting_key = 'system_icon'";
    if (mysqli_query($conn, $updateSQL)) {
        echo "Updated default icon to '$defaultIcon' in settings\n";
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Update session value
        $_SESSION['system_icon'] = $defaultIcon;
        echo "Updated session system_icon value\n";
    } else {
        echo "Failed to update icon in settings: " . mysqli_error($conn) . "\n";
    }
    
    // Update logo_type setting
    $updateLogoTypeSQL = "UPDATE settings SET setting_value = 'icon' WHERE setting_key = 'logo_type'";
    if (mysqli_query($conn, $updateLogoTypeSQL)) {
        echo "Updated logo_type to 'icon' in settings\n";
        
        // Update session value
        $_SESSION['logo_type'] = 'icon';
        echo "Updated session logo_type value\n";
    } else {
        echo "Failed to update logo_type in settings: " . mysqli_error($conn) . "\n";
    }
} catch (Exception $e) {
    echo "Error updating settings: " . $e->getMessage() . "\n";
}

echo "All educational icons generated successfully!\n";
?> 