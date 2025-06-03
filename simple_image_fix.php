<?php
// Simple image and document fix script (no external dependencies)
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Simple Image and Document Fixer</h1>";

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

// Function to create a simple text placeholder with the correct extension
function createSimplePlaceholder($path, $text) {
    $content = "This is a placeholder file for: $text\nCreated: " . date('Y-m-d H:i:s');
    
    if (file_put_contents($path, $content)) {
        echo "<p style='color:green'>✓ Created placeholder: $path</p>";
        chmod($path, 0666); // Make sure the file is readable
        return true;
    } else {
        echo "<p style='color:red'>Failed to create placeholder: $path</p>";
        return false;
    }
}

// Directories to check and create
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
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
createSimplePlaceholder('images/departments/default.jpg', 'Default Department');

// Create department images
echo "<h2>Creating Department Images</h2>";
foreach ($departments as $dept) {
    $deptCode = strtolower($dept['code']);
    $imagePath = "images/departments/{$deptCode}.jpg";
    
    if (!file_exists($imagePath)) {
        createSimplePlaceholder($imagePath, $dept['name']);
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
            createSimplePlaceholder($galleryPath, "{$dept['name']} Gallery Image {$i}");
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
            createSimplePlaceholder($docPath, "{$dept['name']} {$type}");
        } else {
            echo "<p>Document already exists: $docPath</p>";
            // Fix permissions anyway
            chmod($docPath, 0666);
        }
    }
}

echo "<h2>Fix Complete!</h2>";
echo "<p>All necessary files have been created with basic placeholders and correct permissions.</p>";
echo "<p>Note: These are simple text files with the correct extensions to satisfy the file paths in the code.</p>";
echo "<p>The website will display default images for departments when the actual images can't be displayed.</p>";
echo "<p><a href='pages_php/departments.php'>Go to Departments Page</a></p>";
?> 