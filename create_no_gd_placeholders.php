<?php
// Create placeholder images without using GD library
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Placeholders (No GD)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #0275d8; }
        h2 { color: #0275d8; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .card-header { font-weight: bold; margin-bottom: 10px; }
        .placeholder-preview { 
            width: 400px; 
            height: 200px; 
            background-color: #3498db; 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-size: 18px;
            margin: 10px 0;
        }
        .html-placeholder {
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Creating Placeholder Images (No GD)</h1>
    <p>This script creates placeholder files for departments without requiring the GD library.</p>";

// Check if GD is available (just to inform the user)
if (extension_loaded('gd')) {
    echo "<div class='card'>
        <p class='warning'>Note: GD library is actually available on this server. You should use the standard placeholder creation tool instead.</p>
        <p>However, this script will still work if you want to continue.</p>
    </div>";
} else {
    echo "<div class='card'>
        <p>GD library is not available on this server. This tool will create basic placeholders.</p>
    </div>";
}

// Department codes
$departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];

// Create department directories if they don't exist
$directories = [
    'images/departments',
    'images/departments/gallery',
    'documents/departments'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<p class='success'>Created directory: $dir</p>";
        } else {
            echo "<p class='error'>Failed to create directory: $dir</p>";
        }
    }
    @chmod($dir, 0777);
}

// Basic JPEG header data
$jpegHeader = pack("C*", 
    0xFF, 0xD8,                    // SOI marker
    0xFF, 0xE0,                    // APP0 marker
    0x00, 0x10,                    // APP0 header size
    0x4A, 0x46, 0x49, 0x46, 0x00,  // JFIF identifier
    0x01, 0x01,                    // JFIF version
    0x00,                          // Density units
    0x00, 0x01,                    // X density
    0x00, 0x01,                    // Y density
    0x00, 0x00                     // Thumbnail
);

// Create the default department placeholder
echo "<h2>Creating Default Department Placeholder</h2>";
$defaultPath = 'images/departments/default.jpg';

$defaultContent = $jpegHeader . str_repeat('X', 2048) . "Default Department Image";

if (file_put_contents($defaultPath, $defaultContent)) {
    chmod($defaultPath, 0666);
    echo "<p class='success'>Default placeholder created: $defaultPath</p>";
    echo "<div class='card'>
        <div class='card-header'>Default Placeholder:</div>
        <div class='placeholder-preview'>No Image Available</div>
        <p>This is what will display on the website. The actual file is a binary JPEG-like file.</p>
    </div>";
} else {
    echo "<p class='error'>Failed to create default placeholder!</p>";
}

// Create department placeholders
echo "<h2>Creating Department Placeholders</h2>";
$successCount = 0;
$failureCount = 0;

foreach ($departmentCodes as $code) {
    $deptPath = "images/departments/{$code}.jpg";
    $deptContent = $jpegHeader . str_repeat('X', 2048) . "{$code} Department Image";
    
    echo "<p>Creating {$code} placeholder... ";
    if (file_put_contents($deptPath, $deptContent)) {
        chmod($deptPath, 0666);
        echo "<span class='success'>Success</span></p>";
        $successCount++;
    } else {
        echo "<span class='error'>Failed</span></p>";
        $failureCount++;
    }
}

// Create gallery placeholders
echo "<h2>Creating Gallery Placeholders</h2>";

foreach ($departmentCodes as $code) {
    echo "<p>Department: {$code}</p>";
    echo "<ul>";
    
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/{$code}{$i}.jpg";
        $galleryContent = $jpegHeader . str_repeat('X', 2048) . "{$code} Gallery Image {$i}";
        
        echo "<li>Gallery image {$i}: ";
        if (file_put_contents($galleryPath, $galleryContent)) {
            chmod($galleryPath, 0666);
            echo "<span class='success'>Created</span>";
            $successCount++;
        } else {
            echo "<span class='error'>Failed</span>";
            $failureCount++;
        }
        echo "</li>";
    }
    
    echo "</ul>";
}

// Summary
echo "<h2>Summary</h2>";
echo "<p><strong>Total placeholders created:</strong> $successCount</p>";
if ($failureCount > 0) {
    echo "<p><strong>Failed:</strong> <span class='error'>$failureCount</span></p>";
}

// Alternative HTML placeholders
echo "<h2>HTML Placeholder Alternative</h2>";
echo "<p>Since GD is not available, you might consider using HTML placeholders instead of image files.</p>";
echo "<p>Add this code to your departments.php file to display a colored box instead of trying to load an image:</p>";

echo "<div class='html-placeholder'>
<pre>
/* Add this CSS to your style section */
.html-placeholder {
    width: 100%;
    height: 200px;
    background-color: #3498db;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 15px;
}

/* Replace the department-image div with this */
&lt;div class=\"html-placeholder\"&gt;
    &lt;?php echo htmlspecialchars(\$department['name']); ?&gt;
&lt;/div&gt;
</pre>
</div>";

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>You can now:</p>";
echo "<ol>";
echo "<li><a href='departments.php'>Go to Departments Page</a> to see the placeholders in action</li>";
echo "<li><a href='gallery_uploader.php'>Go to Gallery Uploader</a> to upload your own images</li>";
echo "<li><a href='cleanup.html'>Return to Cleanup Tools</a></li>";
echo "</ol>";

echo "</body></html>";
?> 