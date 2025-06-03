<?php
/**
 * Fix Template Structure Script
 * This script scans PHP files in pages_php directory and ensures they
 * correctly use header.php and footer.php includes
 */

// Directory to process
$directory = __DIR__ . '/pages_php';

// Get all PHP files except those in includes directory
$files = [];
$directoryIterator = new RecursiveDirectoryIterator($directory);
$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
foreach ($iteratorIterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        // Skip files in the includes directory
        if (strpos($file->getPathname(), '/includes/') === false && strpos($file->getPathname(), '\\includes\\') === false) {
            $files[] = $file->getPathname();
        }
    }
}

echo "Found " . count($files) . " PHP files to check.\n";

// Skip specific files that are handled differently
$skip_files = ['login.php', 'logout.php', 'auth.php', 'register.php', 'reset_password.php'];

$fixed_count = 0;
$already_correct_count = 0;
$skipped_count = 0;

// Process each file
foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip certain files
    if (in_array($filename, $skip_files)) {
        echo "Skipping $filename (in skip list)\n";
        $skipped_count++;
        continue;
    }
    
    echo "Processing $filename...\n";
    
    // Read file content
    $content = file_get_contents($file);
    
    // Check if the file already correctly uses header.php and footer.php
    $has_header_include = preg_match('/require_once\s+[\'"]includes\/header\.php[\'"]\s*;/', $content);
    $has_footer_include = preg_match('/require_once\s+[\'"]includes\/footer\.php[\'"]\s*;/', $content);
    
    // Check for duplicate HTML structure
    $has_html_tag = preg_match('/<html/i', $content);
    $has_body_tag = preg_match('/<body/i', $content);
    $has_doctype = preg_match('/<!DOCTYPE/i', $content);
    $has_sidebar_div = preg_match('/<div\s+class="sidebar">/i', $content);
    
    // If file already has proper structure, skip it
    if ($has_header_include && $has_footer_include && !$has_html_tag && !$has_body_tag && !$has_doctype && !$has_sidebar_div) {
        echo "  $filename already has correct template structure. Skipping.\n";
        $already_correct_count++;
        continue;
    }
    
    // Detected issue - needs fixing
    echo "  $filename needs template structure fixed.\n";
    
    // Make a backup of the original file
    copy($file, $file . '.bak');
    echo "  Created backup at $filename.bak\n";
    
    // Extract basic auth check and variables from the beginning
    preg_match('/^(.*?)(?:require_once\s+[\'"]includes\/header\.php[\'"]\s*;|<\!DOCTYPE|<html)/is', $content, $beginningMatches);
    $beginning = isset($beginningMatches[1]) ? $beginningMatches[1] : '';
    
    // If beginning doesn't have page title, add it
    if (strpos($beginning, '$pageTitle') === false) {
        // Extract current title from content
        preg_match('/<title>(.*?)<\/title>/is', $content, $titleMatches);
        $title = isset($titleMatches[1]) ? trim(strip_tags($titleMatches[1])) : basename($filename, '.php') . ' - SRC Management System';
        
        // Replace any PHP echo in the title
        if (strpos($title, '<?php echo') !== false) {
            preg_match('/\$pageTitle|[\'"]([^\'"]*)[\'"]/', $title, $pageTitle);
            if (isset($pageTitle[1])) {
                $title = $pageTitle[1];
            } else {
                $title = basename($filename, '.php') . ' - SRC Management System';
            }
        }
        
        $beginning .= "\n// Set page title\n\$pageTitle = \"" . $title . "\";\n";
    }
    
    // Extract main content by looking for specific patterns
    $contentStart = 0;
    $contentEnd = strlen($content);
    
    // Find where main content starts (after opening body tag, after sidebar, or after main div)
    if (preg_match('/<div\s+class=[\'"](main-content|container|container-fluid)[\'"].*?>/is', $content, $mainMatch, PREG_OFFSET_CAPTURE)) {
        $contentStart = $mainMatch[0][1] + strlen($mainMatch[0][0]);
    } elseif (preg_match('/<main.*?>/is', $content, $mainMatch, PREG_OFFSET_CAPTURE)) {
        $contentStart = $mainMatch[0][1] + strlen($mainMatch[0][0]);
    } elseif (preg_match('/<body.*?>/is', $content, $bodyMatch, PREG_OFFSET_CAPTURE)) {
        $contentStart = $bodyMatch[0][1] + strlen($bodyMatch[0][0]);
    }
    
    // Find where main content ends (before closing body tag, before footer, or before closing main div)
    if (preg_match('/<\/main>/is', $content, $mainEndMatch, PREG_OFFSET_CAPTURE)) {
        $contentEnd = $mainEndMatch[0][1];
    } elseif (preg_match('/<footer/is', $content, $footerMatch, PREG_OFFSET_CAPTURE)) {
        $contentEnd = $footerMatch[0][1];
    } elseif (preg_match('/<\/div>\s*<\/div>\s*<\/body>/is', $content, $endMatch, PREG_OFFSET_CAPTURE)) {
        $contentEnd = $endMatch[0][1];
    } elseif (preg_match('/<\/body>/is', $content, $bodyEndMatch, PREG_OFFSET_CAPTURE)) {
        $contentEnd = $bodyEndMatch[0][1];
    }
    
    // Extract the main content
    $mainContent = substr($content, $contentStart, $contentEnd - $contentStart);
    
    // Clean up content to remove closing divs, containers, etc.
    $mainContent = preg_replace('/<\/div>\s*<\/div>\s*$/', '', trim($mainContent));
    
    // Remove any bootstrap/CSS includes that might be in the content part
    $mainContent = preg_replace('/<link[^>]*bootstrap[^>]*>\s*/', '', $mainContent);
    $mainContent = preg_replace('/<link[^>]*font-awesome[^>]*>\s*/', '', $mainContent);
    
    // Find custom style blocks
    $customStyles = '';
    if (preg_match('/<style[^>]*>(.*?)<\/style>/is', $content, $styleMatches)) {
        $customStyles = $styleMatches[0];
        // Remove from main content if it's there
        $mainContent = str_replace($customStyles, '', $mainContent);
    }
    
    // Construct the new file content
    $newContent = $beginning;
    $newContent .= "\n// Include header\nrequire_once 'includes/header.php';\n\n";
    
    // Add custom styles if found
    if (!empty($customStyles)) {
        $newContent .= "// Custom styles for this page\n?>\n" . $customStyles . "\n<?php\n\n";
    }
    
    // Add main content
    $newContent .= "?>\n" . trim($mainContent) . "\n\n";
    $newContent .= "<?php require_once 'includes/footer.php'; ?>";
    
    // Save the new content to the file
    file_put_contents($file, $newContent);
    echo "  Updated $filename with correct template structure\n";
    $fixed_count++;
}

echo "\nTemplate structure fix complete!\n";
echo "Files fixed: $fixed_count\n";
echo "Files already correct: $already_correct_count\n";
echo "Files skipped: $skipped_count\n";
?> 