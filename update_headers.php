<?php
/**
 * Update Headers Script
 * This script updates all header.php files to ensure they include the required settings_functions.php
 */

// Define the directories to scan
$directories = [
    'pages_php/includes/',
    'includes/',
    'pages_php/',
];

// The include code to add at the beginning of header files
$includeCode = '<?php
// Include required files
if (file_exists(\'../settings_functions.php\')) {
    require_once \'../settings_functions.php\';
} elseif (file_exists(\'settings_functions.php\')) {
    require_once \'settings_functions.php\';
}

';

// Function to update a header file
function updateHeaderFile($filePath) {
    // Read the file content
    $content = file_get_contents($filePath);
    
    // Skip if file already has the include code
    if (strpos($content, 'require_once \'../settings_functions.php\'') !== false) {
        return ['status' => 'skipped', 'message' => 'File already has required includes'];
    }
    
    // Replace the opening PHP tag with our include code
    $updatedContent = str_replace('<?php', $GLOBALS['includeCode'], $content);
    
    // Backup the original file
    $backupPath = $filePath . '.bak';
    file_put_contents($backupPath, $content);
    
    // Write the updated content
    $success = file_put_contents($filePath, $updatedContent);
    
    if ($success) {
        return ['status' => 'updated', 'message' => 'File updated successfully'];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update file'];
    }
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Update Headers</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .skipped { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Update Headers</h1>
    <p>This script updates all header.php files to ensure they include the required settings_functions.php file.</p>';

// Display the code that will be added
echo '<h2>Include Code</h2>';
echo '<pre>' . htmlspecialchars($includeCode) . '</pre>';

// Process each directory
echo '<h2>Processing Files</h2>';
echo '<ul>';

$filesProcessed = 0;
$filesUpdated = 0;
$filesSkipped = 0;
$filesErrored = 0;

foreach ($directories as $directory) {
    // Skip if directory doesn't exist
    if (!file_exists($directory)) {
        echo '<li class="error">Directory not found: ' . htmlspecialchars($directory) . '</li>';
        continue;
    }
    
    // Scan for header.php files
    $files = glob($directory . '*header*.php');
    
    foreach ($files as $file) {
        $filesProcessed++;
        $result = updateHeaderFile($file);
        
        switch ($result['status']) {
            case 'updated':
                $filesUpdated++;
                $class = 'success';
                break;
            case 'skipped':
                $filesSkipped++;
                $class = 'skipped';
                break;
            default:
                $filesErrored++;
                $class = 'error';
                break;
        }
        
        echo '<li class="' . $class . '">' . htmlspecialchars($file) . ': ' . htmlspecialchars($result['message']) . '</li>';
    }
}

echo '</ul>';

// Summary
echo '<h2>Summary</h2>';
echo '<p>Files processed: ' . $filesProcessed . '</p>';
echo '<p>Files updated: <span class="success">' . $filesUpdated . '</span></p>';
echo '<p>Files skipped: <span class="skipped">' . $filesSkipped . '</span></p>';
echo '<p>Files with errors: <span class="error">' . $filesErrored . '</span></p>';

// Next steps
echo '<h2>Next Steps</h2>';
echo '<p>After updating header files, you should:</p>';
echo '<ol>';
echo '<li>Clear the PHP opcode cache if enabled</li>';
echo '<li>Reload the website to ensure all files are loaded properly</li>';
echo '<li>Check for any remaining errors</li>';
echo '</ol>';

echo '<p><a href="force_logo_update.php">Go to Logo Update Tool</a></p>';
echo '<p><a href="pages_php/dashboard.php">Go to Dashboard</a></p>';

echo '</body>
</html>';
?> 