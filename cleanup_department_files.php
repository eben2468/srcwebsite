<?php
// Script to delete all sample department images and documents
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Set maximum execution time
ini_set('max_execution_time', 60);

echo "<h1>Department Files Cleanup</h1>";

// Get department codes (lowercase for file operations)
$departmentCodes = ['nursa', 'themsa', 'edsa', 'cossa', 'dessa', 'sobsa'];

// Count of deleted files
$deletedFiles = 0;
$errors = 0;
$preservedFolders = 0;

// ========== STEP 1: Delete gallery images ==========
echo "<h2>Step 1: Deleting gallery images</h2>";

$galleryDir = 'images/departments/gallery/';

// Make sure gallery directory exists
if (file_exists($galleryDir)) {
    echo "<p>Gallery directory found: $galleryDir</p>";
    
    // Delete all files in the gallery directory
    $galleryFiles = glob($galleryDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    echo "<p>Found " . count($galleryFiles) . " gallery images.</p>";
    
    echo "<ul>";
    foreach ($galleryFiles as $file) {
        echo "<li>Deleting: " . basename($file) . "... ";
        if (@unlink($file)) {
            echo "<span style='color:green'>Success</span>";
            $deletedFiles++;
        } else {
            echo "<span style='color:red'>Failed</span>";
            $errors++;
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Keep the directory
    $preservedFolders++;
} else {
    echo "<p style='color:orange'>Gallery directory not found. Creating it now.</p>";
    if (@mkdir($galleryDir, 0777, true)) {
        echo "<p style='color:green'>Created gallery directory: $galleryDir</p>";
        $preservedFolders++;
    } else {
        echo "<p style='color:red'>Failed to create gallery directory!</p>";
        $errors++;
    }
}

// ========== STEP 2: Delete main department images ==========
echo "<h2>Step 2: Deleting main department images</h2>";

$departmentsDir = 'images/departments/';

if (file_exists($departmentsDir)) {
    echo "<p>Departments directory found: $departmentsDir</p>";
    
    // Delete all department images
    $departmentImages = glob($departmentsDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
    echo "<p>Found " . count($departmentImages) . " department images.</p>";
    
    echo "<ul>";
    foreach ($departmentImages as $file) {
        echo "<li>Deleting: " . basename($file) . "... ";
        if (@unlink($file)) {
            echo "<span style='color:green'>Success</span>";
            $deletedFiles++;
        } else {
            echo "<span style='color:red'>Failed</span>";
            $errors++;
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Keep the directory
    $preservedFolders++;
} else {
    echo "<p style='color:orange'>Departments directory not found. Creating it now.</p>";
    if (@mkdir($departmentsDir, 0777, true)) {
        echo "<p style='color:green'>Created departments directory: $departmentsDir</p>";
        $preservedFolders++;
    } else {
        echo "<p style='color:red'>Failed to create departments directory!</p>";
        $errors++;
    }
}

// ========== STEP 3: Delete department documents ==========
echo "<h2>Step 3: Deleting department documents</h2>";

$documentsDir = 'documents/departments/';

if (file_exists($documentsDir)) {
    echo "<p>Documents directory found: $documentsDir</p>";
    
    // Delete all department documents
    $departmentDocs = glob($documentsDir . '*.{pdf,doc,docx,ppt,pptx,txt}', GLOB_BRACE);
    echo "<p>Found " . count($departmentDocs) . " department documents.</p>";
    
    echo "<ul>";
    foreach ($departmentDocs as $file) {
        echo "<li>Deleting: " . basename($file) . "... ";
        if (@unlink($file)) {
            echo "<span style='color:green'>Success</span>";
            $deletedFiles++;
        } else {
            echo "<span style='color:red'>Failed</span>";
            $errors++;
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Keep the directory
    $preservedFolders++;
} else {
    echo "<p style='color:orange'>Documents directory not found. Creating it now.</p>";
    if (@mkdir($documentsDir, 0777, true)) {
        echo "<p style='color:green'>Created documents directory: $documentsDir</p>";
        $preservedFolders++;
    } else {
        echo "<p style='color:red'>Failed to create documents directory!</p>";
        $errors++;
    }
}

// ========== STEP 4: Reset directory permissions ==========
echo "<h2>Step 4: Setting directory permissions</h2>";

$allDirs = [
    'images',
    'images/departments',
    'images/departments/gallery',
    'documents',
    'documents/departments'
];

foreach ($allDirs as $dir) {
    if (file_exists($dir)) {
        echo "<p>Setting permissions for: $dir... ";
        if (@chmod($dir, 0777)) {
            echo "<span style='color:green'>Success</span></p>";
        } else {
            echo "<span style='color:red'>Failed</span></p>";
            $errors++;
        }
    } else {
        echo "<p>Creating directory: $dir... ";
        if (@mkdir($dir, 0777, true)) {
            echo "<span style='color:green'>Success</span></p>";
            $preservedFolders++;
        } else {
            echo "<span style='color:red'>Failed</span></p>";
            $errors++;
        }
    }
}

// ========== Summary ==========
echo "<h2>Cleanup Summary</h2>";
echo "<p><strong>Files deleted:</strong> $deletedFiles</p>";
echo "<p><strong>Directories preserved/created:</strong> $preservedFolders</p>";
echo "<p><strong>Errors encountered:</strong> $errors</p>";

if ($errors > 0) {
    echo "<div style='padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px; margin: 20px 0;'>";
    echo "<p><strong>Warning!</strong> Some errors occurred during cleanup. You may need to manually delete some files or fix permissions.</p>";
    echo "</div>";
} else {
    echo "<div style='padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px; margin: 20px 0;'>";
    echo "<p><strong>Success!</strong> All sample files have been deleted. The directories are ready for your new uploads.</p>";
    echo "</div>";
}

// Next steps
echo "<h2>Next Steps</h2>";
echo "<p>You can now upload your own images and documents:</p>";
echo "<ol>";
echo "<li>Go to <a href='departments.php'>Departments Page</a> to manage department images</li>";
echo "<li>Go to <a href='gallery_uploader.php'>Gallery Uploader</a> to manage gallery images</li>";
echo "</ol>";
?> 