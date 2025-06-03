<?php
// Script to create placeholder files for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to create placeholder text files (instead of images)
function createPlaceholderFile($path, $content) {
    if (file_put_contents($path, $content) !== false) {
        echo "Created placeholder: $path<br>";
        return true;
    } else {
        echo "Failed to create placeholder: $path<br>";
        $error = error_get_last();
        echo "Error: " . ($error ? $error['message'] : 'Unknown error') . "<br>";
        return false;
    }
}

// Function to ensure directory exists
function ensureDirectoryExists($dir) {
    if (!file_exists($dir)) {
        echo "Creating directory: $dir<br>";
        if (!mkdir($dir, 0777, true)) {
            echo "Failed to create directory: $dir<br>";
            $error = error_get_last();
            echo "Error: " . ($error ? $error['message'] : 'Unknown error') . "<br>";
            return false;
        }
    }
    
    if (!is_writable($dir)) {
        echo "Directory is not writable: $dir<br>";
        return false;
    }
    
    return true;
}

// Set department data
$departments = [
    ['code' => 'NURSA', 'name' => 'School of Nursing and Midwifery'],
    ['code' => 'THEMSA', 'name' => 'School of Theology and Mission'],
    ['code' => 'EDSA', 'name' => 'School of Education'],
    ['code' => 'COSSA', 'name' => 'Faculty of Science'],
    ['code' => 'DESSA', 'name' => 'Development Studies'],
    ['code' => 'SOBSA', 'name' => 'School of Business']
];

// Create directories
$dirs = [
    'images/departments',
    'images/departments/gallery',
    'documents/departments'
];

foreach ($dirs as $dir) {
    if (!ensureDirectoryExists($dir)) {
        echo "Error with directory: $dir. Stopping script.<br>";
        exit;
    }
}

// Create placeholder department images
foreach ($departments as $dept) {
    $deptCode = strtolower($dept['code']);
    
    // Department image
    $imagePath = "images/departments/{$deptCode}.jpg";
    if (!file_exists($imagePath)) {
        // Download a placeholder image from placeholder.com
        $placeholderUrl = "https://via.placeholder.com/800x400.jpg/cccccc/333333?text={$dept['code']}";
        $imageContent = @file_get_contents($placeholderUrl);
        
        if ($imageContent !== false) {
            file_put_contents($imagePath, $imageContent);
            echo "Downloaded placeholder image for {$dept['code']}<br>";
        } else {
            // Create a text file as fallback
            createPlaceholderFile($imagePath, "Placeholder for {$dept['name']} Department Image");
        }
    } else {
        echo "Image already exists: $imagePath<br>";
    }
    
    // Gallery images
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/{$deptCode}{$i}.jpg";
        if (!file_exists($galleryPath)) {
            // Download a placeholder image
            $placeholderUrl = "https://via.placeholder.com/400x300.jpg/cccccc/333333?text={$dept['code']}_Gallery_{$i}";
            $imageContent = @file_get_contents($placeholderUrl);
            
            if ($imageContent !== false) {
                file_put_contents($galleryPath, $imageContent);
                echo "Downloaded gallery image {$i} for {$dept['code']}<br>";
            } else {
                // Create a text file as fallback
                createPlaceholderFile($galleryPath, "Placeholder for {$dept['name']} Gallery Image {$i}");
            }
        } else {
            echo "Gallery image already exists: $galleryPath<br>";
        }
    }
    
    // Document placeholders
    $docTypes = ['handbook', 'syllabus', 'guide'];
    foreach ($docTypes as $type) {
        $docPath = "documents/departments/{$deptCode}_{$type}.pdf";
        if (!file_exists($docPath)) {
            // Create a simple PDF-like text file
            $content = "%PDF-1.4\n% Placeholder PDF for {$dept['name']} {$type}\n% This is not a real PDF file\n";
            createPlaceholderFile($docPath, $content);
        } else {
            echo "Document already exists: $docPath<br>";
        }
    }
}

echo "<h2>Placeholder creation completed!</h2>";
?> 