<?php
// Create a default placeholder image for departments
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Creating Default Department Image</h1>";

$defaultImagePath = 'images/departments/default.jpg';
$width = 800;
$height = 400;
$text = "No Image Available";

// Check if GD is available
if (extension_loaded('gd')) {
    echo "<p>GD Library is available. Creating image with GD.</p>";
    
    // Create the image
    $image = @imagecreatetruecolor($width, $height);
    
    if ($image) {
        // Create colors
        $bgColor = imagecolorallocate($image, 52, 152, 219); // Blue
        $textColor = imagecolorallocate($image, 255, 255, 255); // White
        
        // Fill background
        imagefill($image, 0, 0, $bgColor);
        
        // Add text
        $font = 5; // Built-in font
        $textWidth = strlen($text) * imagefontwidth($font);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save the image
        if (imagejpeg($image, $defaultImagePath, 90)) {
            chmod($defaultImagePath, 0666);
            echo "<p style='color:green'>✓ Default image created successfully!</p>";
            echo "<p><img src='$defaultImagePath' style='max-width:400px; border:1px solid #ccc;' alt='Default Image'></p>";
        } else {
            echo "<p style='color:red'>✗ Failed to save default image.</p>";
        }
        
        imagedestroy($image);
    } else {
        echo "<p style='color:red'>✗ Failed to create GD image resource.</p>";
    }
} else {
    echo "<p>GD Library is not available. Creating basic placeholder.</p>";
    
    // Create a simple JPEG-like file with a header
    $header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF";
    $content = $header . str_repeat('X', 2048) . $text;
    
    if (file_put_contents($defaultImagePath, $content)) {
        chmod($defaultImagePath, 0666);
        echo "<p style='color:green'>✓ Basic placeholder image created!</p>";
        echo "<p>(This is not a real image, but will work as a placeholder)</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to create placeholder file.</p>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to <a href='departments.php'>Departments Page</a> to manage department images</li>";
echo "<li>Go to <a href='gallery_uploader.php'>Gallery Uploader</a> to manage gallery images</li>";
echo "</ol>";
?> 