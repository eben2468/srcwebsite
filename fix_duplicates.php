<?php
/**
 * Fix Duplicates Script
 * This script removes duplicate CSS and JS includes from all PHP files
 */

// Directory to process
$directory = __DIR__ . '/pages_php';

// Get all PHP files
$files = glob($directory . '/*.php');

// Process each file
foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip certain files
    if (in_array($filename, ['auth.php', 'login.php', 'logout.php', 'includes/header.php', 'includes/footer.php', 'includes/sidebar.php'])) {
        echo "Skipping $filename\n";
        continue;
    }
    
    echo "Processing $filename...\n";
    
    // Read file content
    $content = file_get_contents($file);
    
    // Remove Bootstrap CSS links
    $patterns = [
        '/<link[^>]*bootstrap[^>]*>/' => '', 
        '/<link[^>]*font-awesome[^>]*>/' => '', 
        '/<link[^>]*style\.css[^>]*>/' => '',
        '/<script[^>]*bootstrap\.bundle\.min\.js[^>]*><\/script>/' => '',
        '/<!\-\- Bootstrap JS Bundle with Popper \-\->/' => ''
    ];
    
    $modified = false;
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $modified = true;
            echo "  Removed duplicate include in $filename\n";
        }
    }
    
    if ($modified) {
        // Write the updated content back to the file
        file_put_contents($file, $content);
        echo "  Updated $filename\n";
    } else {
        echo "  No duplicates found in $filename\n";
    }
}

echo "\nDuplicate cleanup complete!\n";
?> 