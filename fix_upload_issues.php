<?php
// Script to diagnose and fix upload issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

$issues = [];
$fixes = [];

// Check if the directories exist
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

echo "<h1>Upload System Diagnosis and Repair</h1>";

// Check and fix directories
echo "<h2>Checking directories...</h2>";
foreach ($directories as $dir) {
    echo "<p>Checking: $dir</p>";
    
    if (!file_exists($dir)) {
        $issues[] = "Directory '$dir' does not exist";
        
        // Create directory
        if (mkdir($dir, 0777, true)) {
            $fixes[] = "Created directory: $dir";
        } else {
            $issues[] = "Failed to create directory: $dir";
        }
    } else {
        echo "<p>✓ Directory exists</p>";
        
        // Check if directory is writable
        if (!is_writable($dir)) {
            $issues[] = "Directory '$dir' is not writable";
            
            // Try to make it writable
            if (chmod($dir, 0777)) {
                $fixes[] = "Set permissions to 0777 for: $dir";
            } else {
                $issues[] = "Failed to set permissions for: $dir";
            }
        } else {
            echo "<p>✓ Directory is writable</p>";
        }
    }
}

// Check for GD library
echo "<h2>Checking for GD library...</h2>";
if (!extension_loaded('gd')) {
    $issues[] = "GD library is not installed or enabled";
    $fixes[] = "To enable GD library: 1) Open php.ini, 2) Uncomment extension=gd, 3) Restart Apache";
} else {
    echo "<p>✓ GD library is available</p>";
}

// Check PHP configuration
echo "<h2>Checking PHP configuration...</h2>";
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');

echo "<p>upload_max_filesize: $uploadMaxFilesize</p>";
echo "<p>post_max_size: $postMaxSize</p>";
echo "<p>memory_limit: $memoryLimit</p>";

// Convert sizes to bytes for comparison
function convertToBytes($value) {
    $value = trim($value);
    $unit = strtolower($value[strlen($value)-1]);
    $value = (int)$value;
    
    switch($unit) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    
    return $value;
}

$uploadMaxBytes = convertToBytes($uploadMaxFilesize);
$postMaxBytes = convertToBytes($postMaxSize);

if ($uploadMaxBytes < 5 * 1024 * 1024) {
    $issues[] = "upload_max_filesize is less than 5MB";
    $fixes[] = "Increase upload_max_filesize in php.ini to at least 8M";
}

if ($postMaxBytes < 8 * 1024 * 1024) {
    $issues[] = "post_max_size is less than 8MB";
    $fixes[] = "Increase post_max_size in php.ini to at least 16M";
}

if ($postMaxBytes < $uploadMaxBytes) {
    $issues[] = "post_max_size is smaller than upload_max_filesize";
    $fixes[] = "Increase post_max_size to be larger than upload_max_filesize";
}

// Check temporary directory
echo "<h2>Checking temporary directory...</h2>";
$tempDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "<p>Temporary directory: $tempDir</p>";

if (!file_exists($tempDir)) {
    $issues[] = "Temporary directory does not exist";
} else {
    echo "<p>✓ Temporary directory exists</p>";
    
    if (!is_writable($tempDir)) {
        $issues[] = "Temporary directory is not writable";
    } else {
        echo "<p>✓ Temporary directory is writable</p>";
    }
}

// Check for sample files and create them if needed
echo "<h2>Creating sample files...</h2>";

// Create default.jpg
$defaultImagePath = 'images/departments/default.jpg';
if (!file_exists($defaultImagePath) || filesize($defaultImagePath) < 100) {
    $sampleContent = str_repeat('X', 1024); // 1KB of data
    if (file_put_contents($defaultImagePath, $sampleContent)) {
        chmod($defaultImagePath, 0666);
        $fixes[] = "Created sample default.jpg";
    } else {
        $issues[] = "Failed to create sample default.jpg";
    }
} else {
    echo "<p>✓ Default image exists</p>";
}

// Create department images
$departments = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];
foreach ($departments as $dept) {
    $imagePath = "images/departments/$dept.jpg";
    if (!file_exists($imagePath) || filesize($imagePath) < 100) {
        $sampleContent = str_repeat($dept, 256); // Random data
        if (file_put_contents($imagePath, $sampleContent)) {
            chmod($imagePath, 0666);
            $fixes[] = "Created sample image: $imagePath";
        } else {
            $issues[] = "Failed to create sample image: $imagePath";
        }
    } else {
        echo "<p>✓ Department image exists: $imagePath</p>";
    }
    
    // Create gallery images
    for ($i = 1; $i <= 4; $i++) {
        $galleryPath = "images/departments/gallery/$dept$i.jpg";
        if (!file_exists($galleryPath) || filesize($galleryPath) < 100) {
            $sampleContent = str_repeat($dept . $i, 256); // Random data
            if (file_put_contents($galleryPath, $sampleContent)) {
                chmod($galleryPath, 0666);
                $fixes[] = "Created sample gallery image: $galleryPath";
            } else {
                $issues[] = "Failed to create sample gallery image: $galleryPath";
            }
        } else {
            echo "<p>✓ Gallery image exists: $galleryPath</p>";
        }
    }
    
    // Create documents
    $docTypes = ['handbook', 'syllabus', 'guide'];
    foreach ($docTypes as $type) {
        $docPath = "documents/departments/{$dept}_$type.pdf";
        if (!file_exists($docPath) || filesize($docPath) < 100) {
            $sampleContent = "%PDF-1.4\nSample document for $dept $type\n";
            if (file_put_contents($docPath, $sampleContent)) {
                chmod($docPath, 0666);
                $fixes[] = "Created sample document: $docPath";
            } else {
                $issues[] = "Failed to create sample document: $docPath";
            }
        } else {
            echo "<p>✓ Document exists: $docPath</p>";
        }
    }
}

// Test file upload capability
echo "<h2>Testing file upload capability...</h2>";

// Try to create a test file in the upload directory
$testFilePath = 'images/departments/test_upload.txt';
if (file_put_contents($testFilePath, "This is a test file created at " . date('Y-m-d H:i:s'))) {
    chmod($testFilePath, 0666);
    echo "<p>✓ Test file created successfully</p>";
} else {
    $issues[] = "Failed to create test file in upload directory";
}

// Check user permissions
echo "<h2>Checking user permissions...</h2>";
echo "<p>Current user: " . get_current_user() . "</p>";
echo "<p>PHP process running as: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "</p>";

// Display results
echo "<h2>Results</h2>";

if (empty($issues)) {
    echo "<div style='padding: 10px; background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; margin-bottom: 20px;'>";
    echo "<h3>No issues found!</h3>";
    echo "<p>Your system appears to be configured correctly for file uploads.</p>";
    echo "</div>";
} else {
    echo "<div style='padding: 10px; background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; margin-bottom: 20px;'>";
    echo "<h3>Issues Found: " . count($issues) . "</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($fixes)) {
    echo "<div style='padding: 10px; background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; margin-bottom: 20px;'>";
    echo "<h3>Fixes Applied: " . count($fixes) . "</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>1. Try using the <a href='basic_uploader.php'>Basic Uploader</a> to test if file uploads now work</p>";
echo "<p>2. Check <a href='pages_php/departments.php'>Departments Page</a> to see if images display correctly</p>";
echo "<p>3. If issues persist, view your PHP configuration: <a href='phpinfo.php'>PHP Info</a></p>";

// Manual upload test form
echo "<h2>Manual Upload Test</h2>";
echo "<form action='minimal_upload.php' method='post' enctype='multipart/form-data'>";
echo "<input type='file' name='file'>";
echo "<button type='submit'>Test Upload</button>";
echo "</form>";
?> 