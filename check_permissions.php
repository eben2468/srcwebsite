<?php
// Script to check and set up required directories
$output = "Starting directory permission check...\n\n";

$requiredDirs = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

$rootDir = __DIR__;

foreach ($requiredDirs as $dir) {
    $fullPath = $rootDir . '/' . $dir;
    $output .= "Checking $fullPath...\n";
    
    if (!file_exists($fullPath)) {
        $output .= "  - Directory does not exist, creating...\n";
        if (mkdir($fullPath, 0777, true)) {
            $output .= "  - Directory created successfully!\n";
        } else {
            $error = error_get_last();
            $output .= "  - FAILED to create directory. Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
        }
    } else {
        $output .= "  - Directory exists\n";
    }
    
    // Check if directory is writable
    if (is_writable($fullPath)) {
        $output .= "  - Directory is writable\n";
    } else {
        $output .= "  - Directory is NOT writable, attempting to fix...\n";
        if (chmod($fullPath, 0777)) {
            $output .= "  - Permissions updated successfully!\n";
        } else {
            $error = error_get_last();
            $output .= "  - FAILED to update permissions. Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
        }
    }
    
    $output .= "\n";
}

// Create a test file in each directory
foreach ($requiredDirs as $dir) {
    $testFile = $rootDir . '/' . $dir . '/test.txt';
    $output .= "Creating test file in $dir...\n";
    
    if (file_put_contents($testFile, "Test file created at " . date('Y-m-d H:i:s'))) {
        $output .= "  - Test file created successfully!\n";
        
        // Try to delete the test file
        if (unlink($testFile)) {
            $output .= "  - Test file deleted successfully!\n";
        } else {
            $error = error_get_last();
            $output .= "  - FAILED to delete test file. Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
        }
    } else {
        $error = error_get_last();
        $output .= "  - FAILED to create test file. Error: " . ($error ? $error['message'] : 'Unknown error') . "\n";
    }
    
    $output .= "\n";
}

// Check PHP upload limits
$output .= "PHP Upload Settings:\n";
$output .= "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
$output .= "post_max_size: " . ini_get('post_max_size') . "\n";
$output .= "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
$output .= "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";

$output .= "\nTMP Directory Information:\n";
$output .= "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'System default') . "\n";
$systemTmpDir = sys_get_temp_dir();
$output .= "System temp directory: " . $systemTmpDir . "\n";
$output .= "Is writable: " . (is_writable($systemTmpDir) ? 'Yes' : 'No') . "\n";

$output .= "\nDirectory permission check complete!\n";

// Write output to a file
file_put_contents('permission_check_results.txt', $output);

// Also output to browser
echo $output;
?> 