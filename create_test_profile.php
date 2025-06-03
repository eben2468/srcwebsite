<?php
// Script to add a profile picture to the admin user
require_once 'db_config.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Adding profile picture to admin user...\n";

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Database connection successful.\n";

// Check if admin user exists
$checkUserSql = "SELECT * FROM users WHERE role = 'admin' LIMIT 1";
$result = mysqli_query($conn, $checkUserSql);

if (!$result) {
    die("Error querying admin user: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) > 0) {
    $adminUser = mysqli_fetch_assoc($result);
    $userId = $adminUser['user_id'];
    
    echo "Found admin user with ID: " . $userId . "\n";
    echo "Username: " . $adminUser['username'] . "\n";
    echo "Name: " . $adminUser['first_name'] . " " . $adminUser['last_name'] . "\n";
    echo "Current profile picture: " . ($adminUser['profile_picture'] ?? 'None') . "\n";
    
    // Create profiles directory if it doesn't exist
    $profilesDir = 'images/profiles';
    if (!file_exists($profilesDir)) {
        if (mkdir($profilesDir, 0777, true)) {
            echo "Created profiles directory\n";
        } else {
            echo "Failed to create profiles directory\n";
        }
    } else {
        echo "Profiles directory already exists\n";
    }
    
    // Create images directory if it doesn't exist
    if (!file_exists('images')) {
        if (mkdir('images', 0777, true)) {
            echo "Created images directory\n";
        } else {
            echo "Failed to create images directory\n";
        }
    } else {
        echo "Images directory already exists\n";
    }
    
    // Copy a default profile picture
    $defaultImage = 'images/default_profile.jpg';
    $profileImage = 'images/profiles/admin_profile.jpg';
    
    // If default image doesn't exist, create a simple one
    if (!file_exists($defaultImage)) {
        echo "Default profile image doesn't exist, creating one...\n";
        
        // Check if GD is available
        if (!function_exists('imagecreatetruecolor')) {
            die("GD library is not available. Cannot create image.");
        }
        
        // Create a simple colored square image
        $image = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($image, 70, 104, 179); // Blue color matching the sidebar
        $textColor = imagecolorallocate($image, 255, 255, 255); // White text
        
        // Fill the background
        imagefilledrectangle($image, 0, 0, 200, 200, $bgColor);
        
        // Add text (initial)
        $initial = 'A'; // Admin
        $font = 5; // Built-in font
        $fontWidth = imagefontwidth($font);
        $fontHeight = imagefontheight($font);
        $textX = (200 - $fontWidth * strlen($initial)) / 2;
        $textY = (200 - $fontHeight) / 2;
        
        imagestring($image, $font, $textX, $textY, $initial, $textColor);
        
        // Save the image
        if (imagejpeg($image, $defaultImage)) {
            echo "Created default profile image at " . $defaultImage . "\n";
        } else {
            echo "Failed to save default profile image\n";
        }
        imagedestroy($image);
    } else {
        echo "Default profile image already exists at " . $defaultImage . "\n";
    }
    
    // Copy the default image to the profiles directory
    if (copy($defaultImage, $profileImage)) {
        echo "Copied profile image to " . $profileImage . "\n";
        
        // Update the user's profile_picture field
        $updateSql = "UPDATE users SET profile_picture = 'admin_profile.jpg' WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $updateSql);
        
        if (!$stmt) {
            die("Error preparing statement: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "Success: Updated admin user's profile picture\n";
        } else {
            echo "Error updating profile picture: " . mysqli_error($conn) . "\n";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "Error copying profile image from " . $defaultImage . " to " . $profileImage . "\n";
    }
} else {
    echo "No admin user found\n";
}

echo "Done.\n";

// Close connection
mysqli_close($conn);
?> 