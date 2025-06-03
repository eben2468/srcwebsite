<?php
// Enhanced file upload handler patch for department_handler.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Department Handler Upload Fix</h1>";

// Define the target file to patch
$targetFile = 'pages_php/department_handler.php';

echo "<p>Checking if target file exists: $targetFile</p>";
if (!file_exists($targetFile)) {
    die("<p style='color:red'>Error: Target file not found: $targetFile</p>");
}

// Better function to handle file uploads with multiple fallback methods
$newHandleFileUploadFunction = <<<'EOD'
// Function to handle file upload with multiple fallback methods
function handleFileUpload($sourceFile, $targetPath) {
    // Get directory part of the target path
    $targetDir = dirname($targetPath);
    
    // Ensure target directory exists with proper permissions
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
        logDebug("Created directory: " . $targetDir);
    }
    
    if (!is_writable($targetDir)) {
        chmod($targetDir, 0777);
        logDebug("Updated permissions for directory: " . $targetDir);
    }
    
    // If the file already exists, try to remove it first
    if (file_exists($targetPath)) {
        if (!unlink($targetPath)) {
            logDebug("Warning: Could not remove existing file at " . $targetPath);
        }
    }
    
    // Method 1: Standard upload function
    if (move_uploaded_file($sourceFile, $targetPath)) {
        chmod($targetPath, 0666); // Make sure the file is readable
        logDebug("Success: File uploaded with move_uploaded_file to " . $targetPath);
        return true;
    }
    
    // Log the failure
    $error = error_get_last();
    logDebug("move_uploaded_file failed: " . ($error ? $error['message'] : 'Unknown error') . 
            ". Trying fallback methods.");
    
    // Method 2: Direct copy
    if (copy($sourceFile, $targetPath)) {
        chmod($targetPath, 0666); // Make sure the file is readable
        logDebug("Success: File uploaded with copy method to " . $targetPath);
        return true;
    }
    
    // Method 3: File put contents
    $fileContents = @file_get_contents($sourceFile);
    if ($fileContents !== false) {
        if (file_put_contents($targetPath, $fileContents)) {
            chmod($targetPath, 0666); // Make sure the file is readable
            logDebug("Success: File uploaded with file_put_contents to " . $targetPath);
            return true;
        }
    }
    
    // Method 4: Try using output buffering to read the file
    ob_start();
    readfile($sourceFile);
    $fileContents = ob_get_clean();
    
    if (!empty($fileContents)) {
        if (file_put_contents($targetPath, $fileContents)) {
            chmod($targetPath, 0666); // Make sure the file is readable
            logDebug("Success: File uploaded with readfile/ob method to " . $targetPath);
            return true;
        }
    }
    
    // All methods failed
    $error = error_get_last();
    logDebug("All upload methods failed: " . ($error ? $error['message'] : 'Unknown error') . 
             "\nSource: " . $sourceFile . "\nTarget: " . $targetPath);
    return false;
}
EOD;

// Better function to ensure directory exists
$newEnsureDirectoryFunction = <<<'EOD'
// General function to ensure directory exists and is writable
function ensureDirectoryExists($dir) {
    // Normalize directory path
    $dir = rtrim(str_replace(['\\', '//'], '/', $dir), '/') . '/';
    
    if (!file_exists($dir)) {
        logDebug("Directory does not exist, creating: " . $dir);
        if (!mkdir($dir, 0777, true)) {
            $error = error_get_last();
            return "Failed to create directory: $dir - " . ($error ? $error['message'] : 'Unknown error');
        }
        
        // Set permissions for the newly created directory
        chmod($dir, 0777);
        logDebug("Created directory with permissions 0777: " . $dir);
    } else {
        logDebug("Directory exists: " . $dir);
    }
    
    // Check if directory is writable
    if (!is_writable($dir)) {
        logDebug("Directory is not writable, changing permissions: " . $dir);
        if (!chmod($dir, 0777)) {
            $error = error_get_last();
            return "Directory exists but could not set permissions: $dir - " . ($error ? $error['message'] : 'Unknown error');
        }
        logDebug("Updated permissions to 0777 for existing directory: " . $dir);
    } else {
        logDebug("Directory is writable: " . $dir);
    }
    
    return true; // Directory exists and is writable
}
EOD;

// Read the content of the target file
$content = file_get_contents($targetFile);
if ($content === false) {
    die("<p style='color:red'>Error: Could not read the target file</p>");
}

// Replace the existing handleFileUpload function
$pattern = '/function\s+handleFileUpload\s*\([^)]*\)\s*\{.*?\}/s';
if (preg_match($pattern, $content, $matches)) {
    $newContent = str_replace($matches[0], $newHandleFileUploadFunction, $content);
    echo "<p>Replacing handleFileUpload function...</p>";
} else {
    die("<p style='color:red'>Error: Could not find handleFileUpload function in the target file</p>");
}

// Replace the existing ensureDirectoryExists function
$pattern = '/function\s+ensureDirectoryExists\s*\([^)]*\)\s*\{.*?\}/s';
if (preg_match($pattern, $newContent, $matches)) {
    $finalContent = str_replace($matches[0], $newEnsureDirectoryFunction, $newContent);
    echo "<p>Replacing ensureDirectoryExists function...</p>";
} else {
    die("<p style='color:red'>Error: Could not find ensureDirectoryExists function in the target file</p>");
}

// Create a backup of the original file
$backupFile = $targetFile . '.bak.' . date('Ymd_His');
if (file_put_contents($backupFile, $content)) {
    echo "<p style='color:green'>Created backup of original file: $backupFile</p>";
} else {
    echo "<p style='color:orange'>Warning: Could not create backup of original file</p>";
}

// Write the updated content to the target file
if (file_put_contents($targetFile, $finalContent)) {
    echo "<p style='color:green'>Successfully updated the file with improved upload handlers!</p>";
} else {
    die("<p style='color:red'>Error: Could not write to the target file</p>");
}

// Directory check
$directories = [
    '../images',
    '../images/departments',
    '../images/departments/gallery',
    '../documents',
    '../documents/departments'
];

echo "<h2>Checking and fixing directory permissions</h2>";
foreach ($directories as $dir) {
    $dir = str_replace('../', '', $dir);
    echo "<p>Checking directory: $dir</p>";
    
    if (!file_exists($dir)) {
        echo "<p>Creating directory: $dir</p>";
        if (mkdir($dir, 0777, true)) {
            echo "<p style='color:green'>Created directory: $dir</p>";
        } else {
            echo "<p style='color:red'>Failed to create directory: $dir</p>";
        }
    } else {
        echo "<p>Directory exists: $dir</p>";
    }
    
    if (is_writable($dir)) {
        echo "<p style='color:green'>Directory is writable: $dir</p>";
    } else {
        echo "<p>Setting permissions for: $dir</p>";
        if (chmod($dir, 0777)) {
            echo "<p style='color:green'>Updated permissions for: $dir</p>";
        } else {
            echo "<p style='color:red'>Failed to set permissions for: $dir</p>";
        }
    }
}

echo "<h2>Fix Complete!</h2>";
echo "<p>The department_handler.php file has been updated with improved file upload handling.</p>";
echo "<p>The following improvements were made:</p>";
echo "<ul>";
echo "<li>Multiple fallback upload methods if the primary method fails</li>";
echo "<li>Better directory handling and permission management</li>";
echo "<li>More detailed error logging</li>";
echo "<li>Automatic cleanup of existing files</li>";
echo "</ul>";

echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li><a href='basic_uploader.php'>Test basic uploading</a></li>";
echo "<li><a href='pages_php/departments.php'>Go to departments page</a></li>";
echo "<li><a href='phpinfo.php'>Check PHP configuration</a></li>";
echo "</ol>";
?> 