<?php
/**
 * Debug Fetch Error
 * Simple script to test what's causing the fetch issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for AJAX requests
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Fetch Error</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:10px;}";
echo ".success{color:#28a745;} .error{color:#dc3545;} .info{color:#17a2b8;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Debug Fetch Error</h1>";

echo "<h3>Server Information:</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</p>";
echo "<p><strong>Request Method:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "</p>";

echo "<h3>File System Check:</h3>";

$files_to_check = [
    'simple_chat_setup.php',
    'setup_chat_database_direct.php',
    'pages_php/support/test_api.php',
    'pages_php/support/live-chat.php',
    'pages_php/support/chat-management.php',
    'includes/db_config.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ File exists: $file</p>";
        if (is_readable($file)) {
            echo "<p class='info'>&nbsp;&nbsp;📖 File is readable</p>";
        } else {
            echo "<p class='error'>&nbsp;&nbsp;❌ File is not readable</p>";
        }
    } else {
        echo "<p class='error'>❌ File missing: $file</p>";
    }
}

echo "<h3>Database Connection Test:</h3>";

try {
    $conn = new mysqli('localhost', 'root', '', 'vvusrc');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p class='success'>✅ Database connection successful</p>";
    echo "<p class='info'>Database: vvusrc</p>";
    
    // Test a simple query
    $result = $conn->query("SELECT 1 as test");
    if ($result) {
        echo "<p class='success'>✅ Database query test successful</p>";
    } else {
        echo "<p class='error'>❌ Database query test failed</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h3>Directory Permissions:</h3>";

$dirs_to_check = [
    '.',
    'pages_php',
    'pages_php/support',
    'includes'
];

foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        echo "<p class='success'>✅ Directory exists: $dir</p>";
        if (is_writable($dir)) {
            echo "<p class='info'>&nbsp;&nbsp;✏️ Directory is writable</p>";
        } else {
            echo "<p class='error'>&nbsp;&nbsp;❌ Directory is not writable</p>";
        }
    } else {
        echo "<p class='error'>❌ Directory missing: $dir</p>";
    }
}

echo "<h3>PHP Extensions:</h3>";

$required_extensions = ['mysqli', 'json', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ Extension loaded: $ext</p>";
    } else {
        echo "<p class='error'>❌ Extension missing: $ext</p>";
    }
}

echo "<h3>Test Links:</h3>";
echo "<p><a href='simple_chat_setup.php' target='_blank'>🔗 Test Simple Chat Setup</a></p>";
echo "<p><a href='pages_php/support/test_api.php' target='_blank'>🔗 Test API Endpoint</a></p>";

echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If files are missing, ensure all chat system files are uploaded</li>";
echo "<li>If database connection fails, check your MySQL server is running</li>";
echo "<li>If permissions are wrong, check your web server configuration</li>";
echo "<li>Try accessing the simple_chat_setup.php directly instead of via fetch</li>";
echo "</ul>";

echo "</div></body></html>";
?>