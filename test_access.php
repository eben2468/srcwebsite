<?php
// Test script to check if PHP files are accessible

// Function to check if a file is accessible
function checkFileAccess($file) {
    $fullPath = __DIR__ . '/' . $file;
    
    echo "<p>Checking file: <strong>$file</strong><br>";
    echo "Full path: $fullPath<br>";
    
    if (file_exists($fullPath)) {
        echo "Status: <span style='color:green'>File exists</span><br>";
        
        if (is_readable($fullPath)) {
            echo "Readable: <span style='color:green'>Yes</span><br>";
        } else {
            echo "Readable: <span style='color:red'>No</span><br>";
        }
        
        echo "File size: " . filesize($fullPath) . " bytes<br>";
        echo "Last modified: " . date("Y-m-d H:i:s", filemtime($fullPath)) . "</p>";
    } else {
        echo "Status: <span style='color:red'>File does not exist</span></p>";
    }
}

// Test specific files
$filesToCheck = [
    'pages_php/minutes.php',
    'pages_php/reports.php',
    'pages_php/portfolio.php',
    'pages_php/portfolio-detail.php'
];

echo "<h1>File Access Test</h1>";

foreach ($filesToCheck as $file) {
    checkFileAccess($file);
}

// Check for PHP errors
echo "<h2>PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Display Errors Setting: " . ini_get('display_errors') . "</p>";
echo "<p>Error Reporting Level: " . ini_get('error_reporting') . "</p>";

// Test direct URL access
echo "<h2>Direct URL Links</h2>";
foreach ($filesToCheck as $file) {
    echo "<p><a href='$file' target='_blank'>Test $file</a></p>";
}
?> 