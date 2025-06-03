<?php
// Force delete all sample images and documents
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Increase time limit to ensure completion
ini_set('max_execution_time', 300);
// Increase memory limit
ini_set('memory_limit', '256M');

// Add basic styling
echo "<!DOCTYPE html>
<html>
<head>
    <title>Force Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #d9534f; }
        h2 { color: #0275d8; margin-top: 30px; }
        ul { list-style-type: none; padding-left: 10px; }
        li { margin-bottom: 8px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .summary { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success-box { background-color: #dff0d8; border: 1px solid #d0e9c6; }
        .error-box { background-color: #f2dede; border: 1px solid #ebcccc; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>";

echo "<h1>FORCE CLEANUP: Department Files</h1>";
echo "<p>This script will aggressively delete all sample files from your system.</p>";

// Track deleted files and errors
$deletedFiles = 0;
$failedFiles = 0;
$deletedDirs = 0;

// List of paths to clean up completely
$cleanupPaths = [
    'images/departments/gallery',
    'images/departments',
    'documents/departments'
];

// Process each path
foreach ($cleanupPaths as $path) {
    echo "<h2>Cleaning up: $path</h2>";
    
    if (!file_exists($path)) {
        echo "<p class='warning'>Directory doesn't exist. Creating it...</p>";
        if (@mkdir($path, 0777, true)) {
            echo "<p class='success'>Directory created successfully.</p>";
        } else {
            echo "<p class='error'>Failed to create directory!</p>";
        }
        continue;
    }
    
    // Get all files in directory including subdirectories
    $directoryIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
    $recursiveIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
    
    echo "<ul>";
    $fileCount = 0;
    $deleteFailures = [];
    
    // Delete files first, then directories
    foreach ($recursiveIterator as $file) {
        $filePath = $file->getPathname();
        
        // Skip .htaccess and index files (optional)
        if (basename($filePath) == '.htaccess' || basename($filePath) == 'index.html' || basename($filePath) == 'index.php') {
            echo "<li>Skipping system file: " . basename($filePath) . "</li>";
            continue;
        }
        
        if ($file->isFile()) {
            $fileCount++;
            echo "<li>Deleting file: " . basename($filePath) . " ... ";
            
            // Try multiple deletion methods
            $deleted = false;
            
            // Method 1: Standard unlink
            if (@unlink($filePath)) {
                $deleted = true;
            } 
            // Method 2: Clear stat cache and try again
            else {
                clearstatcache(true, $filePath);
                if (@unlink($filePath)) {
                    $deleted = true;
                }
                // Method 3: Try with system command if allowed
                else if (function_exists('exec') && !in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
                    if (PHP_OS_FAMILY === 'Windows') {
                        @exec('del /F /Q "' . $filePath . '" 2>&1', $output, $return);
                    } else {
                        @exec('rm -f "' . $filePath . '" 2>&1', $output, $return);
                    }
                    $deleted = !file_exists($filePath);
                }
            }
            
            if ($deleted) {
                echo "<span class='success'>SUCCESS</span></li>";
                $deletedFiles++;
            } else {
                echo "<span class='error'>FAILED</span></li>";
                $failedFiles++;
                $deleteFailures[] = $filePath;
            }
        }
    }
    
    // Cleanup empty subdirectories (except the main directories)
    foreach ($recursiveIterator as $file) {
        if ($file->isDir() && $file->getPathname() != $path) {
            @rmdir($file->getPathname());
            $deletedDirs++;
        }
    }
    
    echo "</ul>";
    echo "<p>Processed $fileCount files in this directory.</p>";
    
    // Report any failures with more detail
    if (!empty($deleteFailures)) {
        echo "<div class='error-box'>";
        echo "<p class='error'>Failed to delete " . count($deleteFailures) . " files:</p>";
        echo "<pre>";
        foreach ($deleteFailures as $failedFile) {
            echo htmlspecialchars($failedFile) . "\n";
            
            // Get file permissions and information
            $perms = @fileperms($failedFile);
            $owner = @fileowner($failedFile);
            $group = @filegroup($failedFile);
            
            echo "  - Permissions: " . (($perms !== false) ? decoct($perms & 0777) : 'unknown') . "\n";
            echo "  - Owner/Group: " . (($owner !== false) ? $owner : 'unknown') . "/" . (($group !== false) ? $group : 'unknown') . "\n";
            echo "\n";
        }
        echo "</pre>";
        echo "</div>";
    }
    
    // Recreate directory structure and set permissions
    echo "<p>Setting directory permissions for: $path</p>";
    @chmod($path, 0777);
}

// Create required directories for departments
$requiredDirs = [
    'images',
    'images/departments', 
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        if (@mkdir($dir, 0777, true)) {
            echo "<p class='success'>Created directory: $dir</p>";
        } else {
            echo "<p class='error'>Failed to create directory: $dir</p>";
        }
    }
    @chmod($dir, 0777);
}

// Summary
echo "<div class='summary " . ($failedFiles > 0 ? 'error-box' : 'success-box') . "'>";
echo "<h2>Cleanup Summary</h2>";
echo "<p><strong>Files deleted:</strong> $deletedFiles</p>";
echo "<p><strong>Directories removed:</strong> $deletedDirs</p>";
echo "<p><strong>Failed deletions:</strong> $failedFiles</p>";

if ($failedFiles > 0) {
    echo "<p class='error'>Some files could not be deleted. Please check the logs above for details.</p>";
    echo "<p>Possible reasons:</p>";
    echo "<ul>";
    echo "<li>Files are in use by another process</li>";
    echo "<li>Insufficient permissions</li>";
    echo "<li>Files are read-only</li>";
    echo "</ul>";
    echo "<p>You may need to close all processes that might be using these files and try again, or delete them manually.</p>";
} else {
    echo "<p class='success'>All files were successfully deleted!</p>";
}
echo "</div>";

// Create default placeholder
echo "<h2>Creating Default Placeholder</h2>";
$defaultImagePath = 'images/departments/default.jpg';

// Check if GD is available
if (extension_loaded('gd')) {
    echo "<p>Using GD library to create placeholder image.</p>";
    
    $width = 800;
    $height = 400;
    $text = "No Image Available";
    
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
        
        // Save image
        if (imagejpeg($image, $defaultImagePath, 90)) {
            @chmod($defaultImagePath, 0666);
            echo "<p class='success'>Default placeholder image created successfully!</p>";
        } else {
            echo "<p class='error'>Failed to save default image.</p>";
        }
        
        imagedestroy($image);
    } else {
        echo "<p class='error'>Failed to create GD image resource.</p>";
    }
} else {
    echo "<p class='warning'>GD library not available. Creating basic placeholder.</p>";
    
    // Create a basic placeholder
    $header = "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46";
    $content = $header . str_repeat('X', 1024) . "No Image Available";
    
    if (@file_put_contents($defaultImagePath, $content)) {
        @chmod($defaultImagePath, 0666);
        echo "<p class='success'>Basic placeholder file created.</p>";
    } else {
        echo "<p class='error'>Failed to create placeholder file.</p>";
    }
}

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>Now that your directories are clean, you can:</p>";
echo "<ol>";
echo "<li><a href='departments.php'>Go to Departments Page</a> to manage department images</li>";
echo "<li><a href='gallery_uploader.php'>Go to Gallery Uploader</a> to manage gallery images</li>";
echo "</ol>";

echo "</body></html>";
?> 