<?php
// List all remaining files in department directories
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Remaining Department Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #0275d8; }
        h2 { color: #0275d8; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .path { font-family: monospace; color: #666; }
        .filesize { text-align: right; }
        .file-count { margin-bottom: 30px; }
        .no-files { color: green; font-style: italic; }
        .has-files { color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Department Files Listing</h1>
    <p>This tool shows all files remaining in department directories.</p>";

// Paths to check
$pathsToCheck = [
    'images/departments',
    'images/departments/gallery',
    'documents/departments'
];

// Track total files
$totalFiles = 0;

// Function to format file size
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes > 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Check each path
foreach ($pathsToCheck as $path) {
    echo "<h2>Directory: $path</h2>";
    
    if (!file_exists($path)) {
        echo "<p>Directory does not exist.</p>";
        continue;
    }
    
    // Get all files recursively
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $info) {
        if ($info->isFile()) {
            $files[] = [
                'path' => $info->getPathname(),
                'name' => $info->getFilename(),
                'size' => $info->getSize(),
                'modified' => date('Y-m-d H:i:s', $info->getMTime()),
                'permissions' => substr(sprintf('%o', $info->getPerms()), -4)
            ];
            $totalFiles++;
        }
    }
    
    // Display results
    if (count($files) > 0) {
        echo "<p class='has-files'>Found " . count($files) . " files:</p>";
        echo "<table>";
        echo "<tr><th>Filename</th><th>Path</th><th>Size</th><th>Modified</th><th>Permissions</th></tr>";
        
        foreach ($files as $file) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($file['name']) . "</td>";
            echo "<td class='path'>" . htmlspecialchars($file['path']) . "</td>";
            echo "<td class='filesize'>" . formatSize($file['size']) . "</td>";
            echo "<td>" . $file['modified'] . "</td>";
            echo "<td>" . $file['permissions'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p class='no-files'>No files found in this directory.</p>";
    }
}

// Summary
echo "<div class='file-count'>";
if ($totalFiles > 0) {
    echo "<h2>Summary</h2>";
    echo "<p class='has-files'>Total files found: $totalFiles</p>";
    echo "<p>To delete these files, run the <a href='force_cleanup.php'>Force Cleanup</a> script.</p>";
} else {
    echo "<h2>Summary</h2>";
    echo "<p class='no-files'>No files found in any department directories.</p>";
    echo "<p>You can now <a href='departments.php'>upload your own department images</a>.</p>";
}
echo "</div>";

// Links
echo "<p><a href='cleanup.html'>Back to Cleanup Tools</a></p>";
echo "</body></html>";
?> 