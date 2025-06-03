<?php
// Script to delete department system files
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if directory is empty
function isDirEmpty($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = scandir($dir);
    return count($files) <= 2; // Only . and .. means directory is empty
}

// If deletion is complete and redirect requested
if (isset($_GET['complete']) && $_GET['complete'] == 1) {
    // Redirect to index.php with success message
    header("Location: index.php?deleted=success");
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Delete Department System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #dc3545; }
        h2 { color: #dc3545; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
        .summary { background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #f5c6cb; }
        .confirmation { margin: 20px 0; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 5px; }
        button {
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #c82333;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .continue-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            margin-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Delete Department System</h1>";

// Files to be deleted
$filesToDelete = [
    // Main department files
    'departments.php',
    'new_departments.php',
    'gallery_uploader.php',
    'new_gallery_uploader.php',
    'setup_departments.php',
    'check_gd.php',
    
    // Cleanup tools
    'cleanup.html',
    'cleanup_department_files.php',
    'force_cleanup.php',
    'list_remaining_files.php',
    'create_no_gd_placeholders.php',
    'html_placeholders.php',
    'delete_document_gallery_images.php',
    'fix_document_names.php',
    'directory_check.php',
    'add_placeholder_files.php',
    'force_create_files.php',
    'start_here.html',
    'delete_department_system.php',
    
    // Backup files
    'departments.php.bak'
];

// Directories to delete recursively
$directoriesToDelete = [
    'images/departments/gallery',
    'images/departments',
    'documents/departments',
    'documents',
    'images'
];

// Check if confirmation is received
if (isset($_POST['confirm_delete'])) {
    echo "<div class='summary'>";
    echo "<h2>Deletion Results</h2>";
    
    // Delete files
    echo "<h3>Deleting Files:</h3>";
    echo "<ul>";
    
    $filesDeleted = 0;
    $filesNotFound = 0;
    $filesFailed = 0;
    
    foreach ($filesToDelete as $file) {
        // Skip the current script until the end
        if ($file == 'delete_department_system.php') {
            continue;
        }
        
        echo "<li>" . htmlspecialchars($file) . ": ";
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<span class='success'>DELETED</span>";
                $filesDeleted++;
            } else {
                echo "<span class='error'>FAILED TO DELETE</span>";
                $filesFailed++;
            }
        } else {
            echo "<span class='warning'>NOT FOUND</span>";
            $filesNotFound++;
        }
        echo "</li>";
    }
    
    echo "</ul>";
    
    // Delete directories and their contents
    echo "<h3>Deleting Directories:</h3>";
    echo "<ul>";
    
    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    $dirsDeleted = 0;
    $dirsNotFound = 0;
    $dirsFailed = 0;
    $dirsNotEmpty = 0;
    
    foreach ($directoriesToDelete as $dir) {
        echo "<li>" . htmlspecialchars($dir) . ": ";
        if (file_exists($dir)) {
            // For top-level directories, only delete if empty (except for images/departments and documents/departments)
            if (in_array($dir, ['images', 'documents']) && !isDirEmpty($dir)) {
                echo "<span class='warning'>NOT EMPTY (SKIPPED)</span>";
                $dirsNotEmpty++;
                continue;
            }
            
            if (deleteDirectory($dir)) {
                echo "<span class='success'>DELETED</span>";
                $dirsDeleted++;
            } else {
                echo "<span class='error'>FAILED TO DELETE</span>";
                $dirsFailed++;
            }
        } else {
            echo "<span class='warning'>NOT FOUND</span>";
            $dirsNotFound++;
        }
        echo "</li>";
    }
    
    echo "</ul>";
    
    // Summary
    echo "<h3>Deletion Summary:</h3>";
    echo "<table>";
    echo "<tr><th>Item Type</th><th>Deleted</th><th>Not Found</th><th>Failed</th><th>Skipped (Not Empty)</th></tr>";
    echo "<tr><td>Files</td><td>$filesDeleted</td><td>$filesNotFound</td><td>$filesFailed</td><td>N/A</td></tr>";
    echo "<tr><td>Directories</td><td>$dirsDeleted</td><td>$dirsNotFound</td><td>$dirsFailed</td><td>$dirsNotEmpty</td></tr>";
    echo "</table>";
    
    if ($filesFailed > 0 || $dirsFailed > 0) {
        echo "<p class='error'>Some items could not be deleted. Please check file permissions or try again later.</p>";
    } else {
        echo "<p class='success'>Department system has been successfully deleted!</p>";
    }
    
    echo "</div>";
    
    echo "<div style='display: flex; justify-content: center;'>";
    echo "<a href='index.php' class='back-btn'>Return Without Completing</a>";
    echo "<a href='delete_department_system.php?complete=1' class='continue-btn'>Complete & Redirect</a>";
    echo "</div>";
} else {
    // Show confirmation form
    echo "<div class='confirmation'>";
    echo "<h2>Warning: This will permanently delete the department system</h2>";
    echo "<p>You are about to delete the following files and directories:</p>";
    
    echo "<h3>Files to be deleted:</h3>";
    echo "<ul>";
    foreach ($filesToDelete as $file) {
        echo "<li>" . htmlspecialchars($file) . (file_exists($file) ? " <span class='warning'>(exists)</span>" : " <span class='error'>(not found)</span>") . "</li>";
    }
    echo "</ul>";
    
    echo "<h3>Directories to be deleted:</h3>";
    echo "<ul>";
    foreach ($directoriesToDelete as $dir) {
        if (in_array($dir, ['images', 'documents'])) {
            echo "<li>" . htmlspecialchars($dir) . (file_exists($dir) ? (isDirEmpty($dir) ? " <span class='warning'>(exists, empty)</span>" : " <span class='warning'>(exists, will only be deleted if empty)</span>") : " <span class='error'>(not found)</span>") . "</li>";
        } else {
            echo "<li>" . htmlspecialchars($dir) . (file_exists($dir) ? " <span class='warning'>(exists)</span>" : " <span class='error'>(not found)</span>") . "</li>";
        }
    }
    echo "</ul>";
    
    echo "<p class='error'><strong>CAUTION:</strong> This action cannot be undone! All department data will be permanently deleted.</p>";
    
    echo "<form method='post' onsubmit=\"return confirm('Are you absolutely sure you want to delete the department system? This cannot be undone!')\">";
    echo "<input type='hidden' name='confirm_delete' value='1'>";
    echo "<button type='submit'>Confirm Deletion</button>";
    echo "</form>";
    
    echo "<a href='start_here.html' class='back-btn'>Cancel and Go Back</a>";
    echo "</div>";
}

echo "</body>
</html>";
?> 