<?php
/**
 * Direct User Activities Access
 * Validates and redirects to the user activities page
 */

// Check if the user-activities.php file exists
$activitiesFile = __DIR__ . '/pages_php/user-activities.php';
$fileExists = file_exists($activitiesFile);

// Output diagnostic information
echo "<!DOCTYPE html>
<html>
<head>
    <title>User Activities Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.6; }
        h1 { color: #4b6cb7; }
        .success { color: green; }
        .error { color: red; }
        .info { color: #4b6cb7; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; }
        .btn { display: inline-block; background-color: #4b6cb7; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>User Activities Diagnostic</h1>";

if ($fileExists) {
    echo "<p class='success'>The user-activities.php file exists at: <code>{$activitiesFile}</code></p>";
    
    // Get file size and last modified date
    $fileSize = filesize($activitiesFile);
    $lastModified = date("Y-m-d H:i:s", filemtime($activitiesFile));
    
    echo "<p class='info'>File size: {$fileSize} bytes</p>";
    echo "<p class='info'>Last modified: {$lastModified}</p>";
    
    // Check if the file is readable
    if (is_readable($activitiesFile)) {
        echo "<p class='success'>The file is readable.</p>";
        
        // Show the first 10 lines of the file
        $contents = file_get_contents($activitiesFile);
        $lines = explode("\n", $contents);
        $first10Lines = array_slice($lines, 0, 10);
        
        echo "<p class='info'>First 10 lines of the file:</p>";
        echo "<pre class='code'>" . htmlspecialchars(implode("\n", $first10Lines)) . "</pre>";
        
        // Provide a direct link to the file
        echo "<p><a href='pages_php/user-activities.php' class='btn'>Access User Activities Page</a></p>";
        
    } else {
        echo "<p class='error'>The file exists but is not readable!</p>";
    }
} else {
    echo "<p class='error'>ERROR: The user-activities.php file does not exist at the expected location!</p>";
    
    // Check parent directory
    $parentDir = dirname($activitiesFile);
    if (is_dir($parentDir)) {
        echo "<p class='info'>The parent directory <code>{$parentDir}</code> exists.</p>";
        
        // List files in the directory
        $files = scandir($parentDir);
        echo "<p class='info'>Files in the directory:</p>";
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                echo "<li>" . htmlspecialchars($file) . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>The parent directory <code>{$parentDir}</code> does not exist!</p>";
    }
}

echo "</body>
</html>";
?> 