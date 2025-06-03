<?php
// Script to create default department images
header('Content-Type: text/plain');

echo "Creating default department images...\n";

// Function to create a colored image with text
function createColoredImage($path, $text, $color, $width = 800, $height = 400) {
    if (extension_loaded('gd')) {
        // Create an image with the specified dimensions
        $image = imagecreatetruecolor($width, $height);
        
        // Convert hex color to RGB
        $hex = ltrim($color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Allocate colors
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Fill the background
        imagefill($image, 0, 0, $bgColor);
        
        // Add text
        $font = 5; // Built-in font
        $fontSize = 5;
        
        // Center the text
        $textWidth = strlen($text) * imagefontwidth($font);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        // Draw the text
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save the image
        imagejpeg($image, $path, 90);
        imagedestroy($image);
        
        echo "Created image: $path\n";
        return true;
    } else {
        echo "GD library not available. Cannot create image: $path\n";
        return false;
    }
}

// Directory for department images
$dir = 'images/departments/';
if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
    echo "Created directory: $dir\n";
}

// Create default image
createColoredImage($dir . 'default.jpg', 'Department', '#007BFF');

// Create department-specific images
$departments = [
    ['code' => 'NURSA', 'name' => 'School of Nursing and Midwifery', 'color' => '#4CAF50'],
    ['code' => 'THEMSA', 'name' => 'School of Theology and Mission', 'color' => '#3F51B5'],
    ['code' => 'EDSA', 'name' => 'School of Education', 'color' => '#FF9800'],
    ['code' => 'COSSA', 'name' => 'Faculty of Science', 'color' => '#2196F3'],
    ['code' => 'DESSA', 'name' => 'Development Studies', 'color' => '#9C27B0'],
    ['code' => 'SOBSA', 'name' => 'School of Business', 'color' => '#F44336']
];

foreach ($departments as $dept) {
    $departmentCode = strtolower($dept['code']);
    $imagePath = $dir . $departmentCode . '.jpg';
    
    // Only create if it doesn't exist
    if (!file_exists($imagePath)) {
        createColoredImage($imagePath, $dept['code'], $dept['color']);
    } else {
        echo "Image already exists: $imagePath\n";
    }
    
    // Create some gallery images for each department
    $galleryDir = $dir . 'gallery/';
    if (!file_exists($galleryDir)) {
        mkdir($galleryDir, 0755, true);
        echo "Created directory: $galleryDir\n";
    }
    
    // Create gallery images (up to 4) if they don't exist
    for ($i = 1; $i <= 4; $i++) {
        $galleryImagePath = $galleryDir . $departmentCode . $i . '.jpg';
        
        if (!file_exists($galleryImagePath)) {
            createColoredImage(
                $galleryImagePath,
                $dept['code'] . ' Gallery ' . $i,
                $dept['color'],
                400,
                300
            );
        } else {
            echo "Gallery image already exists: $galleryImagePath\n";
        }
    }
}

echo "Default images creation completed successfully!\n";
?> 