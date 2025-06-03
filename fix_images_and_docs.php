<?php
// Comprehensive fix script for images, documents, and permissions
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Website Image and Document Fixer</h1>";

// Function to ensure directory exists and has correct permissions
function ensureDirectory($dir) {
    echo "<p>Checking directory: $dir</p>";
    
    if (!file_exists($dir)) {
        echo "<p>Creating directory: $dir</p>";
        if (!mkdir($dir, 0777, true)) {
            echo "<p style='color:red'>Failed to create directory: $dir</p>";
            return false;
        }
    }
    
    if (!is_writable($dir)) {
        echo "<p>Setting permissions for: $dir</p>";
        if (!chmod($dir, 0777)) {
            echo "<p style='color:red'>Failed to set permissions for: $dir</p>";
            return false;
        }
    }
    
    echo "<p style='color:green'>✓ Directory ready: $dir</p>";
    return true;
}

// Function to create a placeholder image file
function createPlaceholderImage($path, $text) {
    // Try creating a simple solid color image with the GD library if available
    if (extension_loaded('gd')) {
        $width = 800;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Use a different color for each text to make them distinguishable
        $colorCode = substr(md5($text), 0, 6);
        $r = hexdec(substr($colorCode, 0, 2));
        $g = hexdec(substr($colorCode, 2, 2));
        $b = hexdec(substr($colorCode, 4, 2));
        
        // Create colors
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        $textColor = imagecolorallocate($image, 255, 255, 255);
        
        // Fill background
        imagefill($image, 0, 0, $bgColor);
        
        // Add text
        $font = 5; // Built-in font
        $textWidth = strlen($text) * imagefontwidth($font);
        $textHeight = imagefontheight($font);
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2;
        
        imagestring($image, $font, $x, $y, $text, $textColor);
        
        // Save image
        if (imagejpeg($image, $path, 90)) {
            echo "<p style='color:green'>✓ Created image with GD: $path</p>";
            chmod($path, 0666);
            imagedestroy($image);
            return true;
        }
        
        imagedestroy($image);
    }
    
    // Fallback: Create a simple HTML file with the department name as content
    $htmlContent = "<!DOCTYPE html><html><head><title>$text</title><style>body{margin:0;padding:0;display:flex;justify-content:center;align-items:center;height:100vh;background-color:#"
        . substr(md5($text), 0, 6) 
        . ";color:white;font-family:Arial,sans-serif;font-size:24px;}</style></head>"
        . "<body><div>$text</div></body></html>";
    
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $htmlPath = str_replace(".$ext", ".html", $path);
    
    if (file_put_contents($htmlPath, $htmlContent)) {
        echo "<p style='color:green'>✓ Created HTML placeholder: $htmlPath</p>";
        chmod($htmlPath, 0666);
        
        // Also create a simple text file with the original extension
        if (file_put_contents($path, "Placeholder for $text")) {
            echo "<p style='color:green'>✓ Created text placeholder with original extension: $path</p>";
            chmod($path, 0666);
            return true;
        }
    }
    
    echo "<p style='color:red'>Failed to create any placeholder for: $path</p>";
    return false;
}

// Function to create a placeholder PDF document
function createPlaceholderDocument($path, $text) {
    // Create a simple PDF-like text file as placeholder
    $content = "%PDF-1.4\n% Placeholder PDF for $text\n% This is not a real PDF file\n";
    
    if (file_put_contents($path, $content)) {
        echo "<p style='color:green'>✓ Created document: $path</p>";
        chmod($path, 0666); // Make sure the file is readable
        return true;
    } else {
        echo "<p style='color:red'>Failed to create document: $path</p>";
        return false;
    }
}

// Directories to check and create
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments',
    'uploads'
];

echo "<h2>Checking and Creating Directories</h2>";
foreach ($directories as $dir) {
    ensureDirectory($dir);
}

// Department data
$departments = [
    ['code' => 'NURSA', 'name' => 'School of Nursing and Midwifery'],
    ['code' => 'THEMSA', 'name' => 'School of Theology and Mission'],
    ['code' => 'EDSA', 'name' => 'School of Education'],
    ['code' => 'COSSA', 'name' => 'Faculty of Science'],
    ['code' => 'DESSA', 'name' => 'Development Studies'],
    ['code' => 'SOBSA', 'name' => 'School of Business']
];

// Create default department image
echo "<h2>Creating Default Department Image</h2>";
createPlaceholderImage('images/departments/default.jpg', 'Default_Department');

// Create department images
echo "<h2>Creating Department Images</h2>";
foreach ($departments as $dept) {
    $deptCode = strtolower($dept['code']);
    $imagePath = "images/departments/{$deptCode}.jpg";
    
    if (!file_exists($imagePath)) {
        createPlaceholderImage($imagePath, $dept['code']);
    } else {
        echo "<p>Image already exists: $imagePath</p>";
        // Fix permissions anyway
        chmod($imagePath, 0666);
    }
}

// Create gallery images
echo "<h2>Creating Gallery Images</h2>";
foreach ($departments as $dept) {
    $deptCode = strtolower($dept['code']);
    
    // Create gallery images (1-4 for each department)
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/{$deptCode}{$i}.jpg";
        
        if (!file_exists($galleryPath)) {
            createPlaceholderImage($galleryPath, "{$dept['code']}_Gallery_{$i}");
        } else {
            echo "<p>Gallery image already exists: $galleryPath</p>";
            // Fix permissions anyway
            chmod($galleryPath, 0666);
        }
    }
}

// Create department documents
echo "<h2>Creating Department Documents</h2>";
$docTypes = ['handbook', 'syllabus', 'guide'];

foreach ($departments as $dept) {
    $deptCode = strtolower($dept['code']);
    
    foreach ($docTypes as $type) {
        $docPath = "documents/departments/{$deptCode}_{$type}.pdf";
        
        if (!file_exists($docPath)) {
            createPlaceholderDocument($docPath, "{$dept['name']} {$type}");
        } else {
            echo "<p>Document already exists: $docPath</p>";
            // Fix permissions anyway
            chmod($docPath, 0666);
        }
    }
}

echo "<h2>Results</h2>";
echo "<h3>Department Images</h3>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
foreach (glob("images/departments/*.jpg") as $image) {
    echo "<div style='border: 1px solid #ccc; padding: 5px; text-align: center;'>";
    echo "<img src='$image' style='max-width: 150px; max-height: 100px;'><br>";
    echo basename($image);
    echo "</div>";
}
echo "</div>";

echo "<h3>Gallery Images</h3>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
foreach (glob("images/departments/gallery/*.jpg") as $image) {
    echo "<div style='border: 1px solid #ccc; padding: 5px; text-align: center;'>";
    echo "<img src='$image' style='max-width: 150px; max-height: 100px;'><br>";
    echo basename($image);
    echo "</div>";
}
echo "</div>";

echo "<h3>Documents</h3>";
echo "<ul>";
foreach (glob("documents/departments/*.pdf") as $document) {
    echo "<li><a href='$document' target='_blank'>" . basename($document) . "</a></li>";
}
echo "</ul>";

echo "<h2>Fix Complete!</h2>";
echo "<p>All necessary directories, images, and documents have been created and permissions have been set.</p>";
echo "<p><a href='pages_php/departments.php'>Go to Departments Page</a></p>";
?> 