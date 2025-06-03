<?php
// Create a default department image without using GD library

// Target directory
$targetDir = 'images/departments/';
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Create a simple text file with a JPEG header
$defaultFile = $targetDir . 'default.jpg';

// JPEG header bytes (simple)
$header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x01\x00\x48\x00\x48\x00\x00\xFF\xDB\x00\x43\x00";

// Create a minimal valid JPEG file (this will be a small blue square)
$jpegData = file_get_contents('https://via.placeholder.com/800x400/0066cc/ffffff?text=Department+Image');

if ($jpegData) {
    // If we got data from the placeholder service, save it
    file_put_contents($defaultFile, $jpegData);
    echo "Default department image created at $defaultFile using placeholder service";
} else {
    // Fallback - create a minimal JPEG file
    $data = $header . str_repeat('X', 1024);
    file_put_contents($defaultFile, $data);
    echo "Fallback default department image created at $defaultFile";
}

// Make sure it's readable
chmod($defaultFile, 0666);
?> 