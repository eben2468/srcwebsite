<?php
// Generate a placeholder image for events
header('Content-Type: image/png');

// Set image dimensions
$width = 600;
$height = 400;

// Create image and allocate colors
$image = imagecreatetruecolor($width, $height);
$bg_color = imagecolorallocate($image, 0, 97, 242); // #0061f2 - primary color
$text_color = imagecolorallocate($image, 255, 255, 255); // White

// Fill the background
imagefill($image, 0, 0, $bg_color);

// Add text
$text = "Upcoming Event";
$font_size = 5; // GD built-in font size (1-5)

// Calculate position to center the text
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$text_x = ($width - $text_width) / 2;
$text_y = ($height - $text_height) / 2;

// Draw text
imagestring($image, $font_size, $text_x, $text_y, $text, $text_color);

// Output the image
imagepng($image);

// Free memory
imagedestroy($image); 