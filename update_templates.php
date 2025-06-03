<?php
/**
 * Template Update Script
 * This script updates all PHP files in the pages_php directory to use the header and footer templates 
 * and removes duplicate sidebar HTML
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
    
    // Check if the file already uses header and footer includes
    $hasHeaderInclude = strpos($content, "require_once 'includes/header.php'") !== false;
    $hasFooterInclude = strpos($content, "require_once 'includes/footer.php'") !== false;
    
    // Check if the file has duplicate sidebar
    $hasDuplicateSidebar = strpos($content, '<div class="dashboard-sidebar">') !== false;
    
    if ($hasDuplicateSidebar) {
        echo "  Found duplicate sidebar in $filename\n";
        
        // Extract main content from between sidebar and end of body
        $mainContent = extractMainContent($content);
        
        if (!$mainContent) {
            echo "  WARNING: Could not extract main content from $filename\n";
            continue;
        }
        
        // Build new file content
        $newContent = buildUpdatedFileContent($content, $mainContent, $hasHeaderInclude, $hasFooterInclude);
        
        // Write the updated content back to the file
        file_put_contents($file, $newContent);
        echo "  Updated $filename\n";
    } else {
        echo "  No duplicate sidebar found in $filename\n";
    }
}

echo "\nTemplate update complete!\n";

/**
 * Extract the main content from a file that has a duplicate sidebar
 */
function extractMainContent($content) {
    // Find the start of the main content - after the sidebar
    $sidebarStart = strpos($content, '<div class="dashboard-sidebar">');
    $contentStart = strpos($content, '<div class="dashboard-content">', $sidebarStart);
    
    if ($contentStart === false) {
        return false;
    }
    
    $contentStart += strlen('<div class="dashboard-content">');
    
    // Find the end of the main content - before the closing divs and body
    $bodyEnd = strpos($content, '</body>', $contentStart);
    if ($bodyEnd === false) {
        $bodyEnd = strpos($content, '</html>', $contentStart);
    }
    
    if ($bodyEnd === false) {
        return false;
    }
    
    // Find the last closing div before the body end
    $lastDiv = strrpos(substr($content, $contentStart, $bodyEnd - $contentStart), '</div>');
    
    if ($lastDiv === false) {
        return false;
    }
    
    $contentEnd = $contentStart + $lastDiv;
    
    // Extract the main content
    return trim(substr($content, $contentStart, $contentEnd - $contentStart));
}

/**
 * Build updated file content with header and footer includes and main content
 */
function buildUpdatedFileContent($originalContent, $mainContent, $hasHeaderInclude, $hasFooterInclude) {
    // Extract PHP code before the HTML
    $phpEndPos = strpos($originalContent, '?>');
    $phpCode = substr($originalContent, 0, $phpEndPos + 2);
    
    // Modify PHP code to include correct header/footer
    $phpCode = str_replace('?>', '', $phpCode);
    
    // Add page title if not present
    if (strpos($phpCode, '$pageTitle') === false) {
        // Extract filename to use as page title
        preg_match('/\/\/\s*.*?\s*-\s*(.*?)\s/', $originalContent, $matches);
        $title = isset($matches[1]) ? $matches[1] : basename(str_replace('.php', '', $originalContent));
        $phpCode .= "\n// Set page title\n";
        $phpCode .= "\$pageTitle = \"$title - SRC Management System\";\n";
    }
    
    // Add header include if not present
    if (!$hasHeaderInclude) {
        $phpCode .= "\n// Include header\nrequire_once 'includes/header.php';\n";
    }
    
    $phpCode .= "?>\n\n";
    
    // Add main content
    $phpCode .= "<!-- Page Content -->\n";
    $phpCode .= $mainContent;
    $phpCode .= "\n\n";
    
    // Add footer include if not present
    if (!$hasFooterInclude) {
        $phpCode .= "<?php require_once 'includes/footer.php'; ?>";
    }
    
    return $phpCode;
}
?> 