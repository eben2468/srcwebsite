<?php
// Script to fix directory permissions
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Fixing permissions for upload directories...\n";

// Function to fix directory permissions
function fixDirectoryPermissions($dir) {
    echo "Checking directory: $dir\n";
    
    if (!file_exists($dir)) {
        echo "Creating directory: $dir\n";
        if (!mkdir($dir, 0777, true)) {
            echo "Failed to create directory: $dir\n";
            $error = error_get_last();
            echo "Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
            return false;
        }
    }
    
    // Set permissions to 0777 (rwxrwxrwx)
    echo "Setting permissions for: $dir\n";
    if (chmod($dir, 0777)) {
        echo "Successfully set permissions for: $dir\n";
    } else {
        echo "Failed to set permissions for: $dir\n";
        $error = error_get_last();
        echo "Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
        return false;
    }
    
    return true;
}

// Directories to fix
$directories = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments',
    'uploads'
];

// Fix permissions for each directory
foreach ($directories as $dir) {
    if (fixDirectoryPermissions($dir)) {
        echo "✓ Fixed permissions for: $dir\n";
    } else {
        echo "✗ Failed to fix permissions for: $dir\n";
    }
    echo "\n";
}

// Create test files to verify permissions
$testFiles = [
    'images/departments/test_perm.txt',
    'images/departments/gallery/test_perm.txt',
    'documents/departments/test_perm.txt'
];

echo "Creating test files to verify write permissions...\n";
foreach ($testFiles as $file) {
    echo "Creating test file: $file\n";
    if (file_put_contents($file, "This is a test file created at " . date('Y-m-d H:i:s'))) {
        echo "✓ Successfully created test file: $file\n";
    } else {
        echo "✗ Failed to create test file: $file\n";
        $error = error_get_last();
        echo "Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
    }
}

echo "\nPermission fixing completed.\n";
echo "Please run your upload test again.\n"; 