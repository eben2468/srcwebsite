<?php
// Setup script for department system
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Setting Up Department System</h1>";

// Department data
$departments = [
    ['code' => 'NURSA', 'name' => 'School of Nursing and Midwifery'],
    ['code' => 'THEMSA', 'name' => 'School of Theology and Mission'],
    ['code' => 'EDSA', 'name' => 'School of Education'],
    ['code' => 'COSSA', 'name' => 'Faculty of Science'],
    ['code' => 'DESSA', 'name' => 'Development Studies'],
    ['code' => 'SOBSA', 'name' => 'School of Business']
];

// Step 1: Check and create directories
echo "<h2>Step 1: Setting up directories</h2>";
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

foreach ($directories as $dir) {
    echo "<p>Checking directory: $dir</p>";
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<p style='color:green'>✓ Created directory: $dir</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p>Directory exists</p>";
        // Set permissions
        if (chmod($dir, 0777)) {
            echo "<p style='color:green'>✓ Set permissions to 0777 for: $dir</p>";
        } else {
            echo "<p style='color:red'>✗ Failed to set permissions for: $dir</p>";
        }
    }
}

// Step 2: Create default and placeholder images
echo "<h2>Step 2: Creating placeholder images</h2>";

// Function to create placeholder images
function createPlaceholderImage($path, $text, $color = [0, 102, 204]) {
    $result = '';
    
    // Check if GD is available
    if (extension_loaded('gd')) {
        // Create a sample image with GD
        $width = 800;
        $height = 400;
        $img = imagecreatetruecolor($width, $height);
        
        // Create colors
        $bgColor = imagecolorallocate($img, $color[0], $color[1], $color[2]);
        $textColor = imagecolorallocate($img, 255, 255, 255);
        
        // Fill background
        imagefill($img, 0, 0, $bgColor);
        
        // Add text
        $font = 5;
        $lines = wordwrap($text, 30, "\n", true);
        $lineheight = imagefontheight($font) + 4;
        
        $text_array = explode("\n", $lines);
        $text_height = count($text_array) * $lineheight;
        
        $y = ($height - $text_height) / 2;
        
        foreach ($text_array as $line) {
            $text_width = imagefontwidth($font) * strlen($line);
            $x = ($width - $text_width) / 2;
            imagestring($img, $font, $x, $y, $line, $textColor);
            $y += $lineheight;
        }
        
        // Save the image
        if (imagejpeg($img, $path, 90)) {
            chmod($path, 0666);
            $result = "<p style='color:green'>✓ Created image with GD: $path</p>";
        } else {
            $result = "<p style='color:red'>✗ Failed to create image with GD: $path</p>";
        }
        
        imagedestroy($img);
    } else {
        // Fallback: Create a simple JPEG-like file
        $header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF";
        $content = $header . str_repeat('X', 5000) . " Placeholder for: $text";
        
        if (file_put_contents($path, $content)) {
            chmod($path, 0666);
            $result = "<p style='color:blue'>✓ Created placeholder file without GD: $path</p>";
        } else {
            $result = "<p style='color:red'>✗ Failed to create placeholder file: $path</p>";
        }
    }
    
    return $result;
}

// Create default department image
$defaultImagePath = 'images/departments/default.jpg';
echo createPlaceholderImage($defaultImagePath, 'Default Department');

// Create department images with distinct colors
$departmentColors = [
    'NURSA' => [231, 76, 60],   // Red
    'THEMSA' => [52, 152, 219], // Blue
    'EDSA' => [243, 156, 18],   // Orange
    'COSSA' => [46, 204, 113],  // Green
    'DESSA' => [155, 89, 182],  // Purple
    'SOBSA' => [52, 73, 94]     // Dark blue
];

foreach ($departments as $dept) {
    $code = $dept['code'];
    $name = $dept['name'];
    $deptCode = strtolower($code);
    
    // Get color for this department
    $color = isset($departmentColors[$code]) ? $departmentColors[$code] : [0, 102, 204];
    
    // Create main department image
    $mainImagePath = "images/departments/{$deptCode}.jpg";
    echo createPlaceholderImage($mainImagePath, $name, $color);
    
    // Create gallery images
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/{$deptCode}{$i}.jpg";
        // Adjust color slightly for each gallery image
        $adjustedColor = [
            min(255, $color[0] + ($i * 20)),
            min(255, $color[1] + ($i * 10)),
            min(255, $color[2] + ($i * 5))
        ];
        echo createPlaceholderImage($galleryPath, "$name Gallery Image $i", $adjustedColor);
    }
}

// Step 3: Create simple document placeholders
echo "<h2>Step 3: Creating document placeholders</h2>";

// Function to create a document placeholder
function createDocumentPlaceholder($path, $text) {
    $content = "%PDF-1.4\n% Placeholder PDF document\n% $text\n";
    
    if (file_put_contents($path, $content)) {
        chmod($path, 0666);
        return "<p style='color:green'>✓ Created document placeholder: $path</p>";
    } else {
        return "<p style='color:red'>✗ Failed to create document placeholder: $path</p>";
    }
}

// Create document placeholders
$docTypes = ['handbook', 'syllabus', 'guide'];

foreach ($departments as $dept) {
    $code = $dept['code'];
    $name = $dept['name'];
    $deptCode = strtolower($code);
    
    foreach ($docTypes as $type) {
        $docPath = "documents/departments/{$deptCode}_{$type}.pdf";
        echo createDocumentPlaceholder($docPath, "$name $type");
    }
}

// Step 4: Copy our new department files to the correct location
echo "<h2>Step 4: Setting up new department pages</h2>";

$files = [
    'new_departments.php' => 'departments.php',
    'new_gallery_uploader.php' => 'gallery_uploader.php'
];

foreach ($files as $source => $destination) {
    echo "<p>Setting up $destination from $source</p>";
    
    if (!file_exists($source)) {
        echo "<p style='color:red'>✗ Source file not found: $source</p>";
        continue;
    }
    
    // Make a backup of existing file if needed
    if (file_exists($destination)) {
        $backupFile = $destination . '.bak.' . date('Ymd_His');
        if (copy($destination, $backupFile)) {
            echo "<p style='color:blue'>Created backup: $backupFile</p>";
        } else {
            echo "<p style='color:orange'>⚠ Warning: Could not create backup of $destination</p>";
        }
    }
    
    // Copy the new file
    if (copy($source, $destination)) {
        echo "<p style='color:green'>✓ Copied $source to $destination</p>";
    } else {
        echo "<p style='color:red'>✗ Failed to copy $source to $destination</p>";
    }
}

// Check for GD library
echo "<h2>System Check</h2>";

if (extension_loaded('gd')) {
    echo "<p style='color:green'>✓ GD library is available</p>";
    
    // Get GD info
    $gdInfo = gd_info();
    echo "<ul>";
    foreach ($gdInfo as $key => $value) {
        if (is_bool($value)) {
            echo "<li>$key: " . ($value ? 'Yes' : 'No') . "</li>";
        } else {
            echo "<li>$key: $value</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:orange'>⚠ GD library is not available. Basic image functionality will be limited.</p>";
    echo "<p>To enable GD library:</p>";
    echo "<ol>";
    echo "<li>Find your php.ini file</li>";
    echo "<li>Uncomment the line with 'extension=gd' by removing the semicolon</li>";
    echo "<li>Restart your web server</li>";
    echo "</ol>";
}

// Final step - Summary
echo "<h2>Setup Complete!</h2>";
echo "<p>The department system has been set up with the following components:</p>";
echo "<ul>";
echo "<li>Created required directories with proper permissions</li>";
echo "<li>Generated placeholder images for all departments</li>";
echo "<li>Created document placeholders</li>";
echo "<li>Installed new department pages</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='departments.php'>Go to Departments Page</a> to view the departments</li>";
echo "<li><a href='gallery_uploader.php'>Go to Gallery Uploader</a> to manage gallery images</li>";
echo "</ol>";
?> 