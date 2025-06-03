<?php
/**
 * Update Page Headers Script
 * 
 * This script updates the page headers of the following pages to use the enhanced_page_header.php template:
 * - reports.php
 * - portfolio.php
 * - departments.php
 * - users.php
 * - user-activities.php
 * - budget.php
 */

$pages = [
    'reports.php' => [
        'title' => 'Reports',
        'icon' => 'fa-chart-bar',
        'admin_only' => false
    ],
    'portfolio.php' => [
        'title' => 'Portfolios',
        'icon' => 'fa-user-tie',
        'admin_only' => false
    ],
    'departments.php' => [
        'title' => 'Departments',
        'icon' => 'fa-building',
        'admin_only' => false
    ],
    'users.php' => [
        'title' => 'Users',
        'icon' => 'fa-users',
        'admin_only' => true
    ],
    'user-activities.php' => [
        'title' => 'User Activities',
        'icon' => 'fa-history',
        'admin_only' => true
    ],
    'budget.php' => [
        'title' => 'Budget',
        'icon' => 'fa-money-bill-wave',
        'admin_only' => false
    ]
];

// Directory with the PHP files
$directory = 'pages_php/';

// Path to the enhanced page header template
$headerTemplatePath = $directory . 'includes/enhanced_page_header.php';

// Check if the enhanced page header template exists
if (!file_exists($headerTemplatePath)) {
    echo "Error: Enhanced page header template file not found at $headerTemplatePath\n";
    exit(1);
}

// Iterate through each page
foreach ($pages as $filename => $pageInfo) {
    $filepath = $directory . $filename;
    
    // Check if the file exists
    if (!file_exists($filepath)) {
        echo "Warning: File $filepath not found. Skipping...\n";
        continue;
    }
    
    // Read the file content
    $content = file_get_contents($filepath);
    
    // Check if the content was read successfully
    if ($content === false) {
        echo "Error: Could not read file $filepath. Skipping...\n";
        continue;
    }
    
    // Check if the file already includes our enhanced header
    if (strpos($content, 'enhanced_page_header.php') !== false) {
        echo "Info: File $filepath already includes the enhanced page header. Skipping...\n";
        continue;
    }
    
    // Find the position after the header include
    $headerIncludePos = strpos($content, "require_once 'includes/header.php';");
    if ($headerIncludePos === false) {
        echo "Warning: Could not find header include in $filepath. Skipping...\n";
        continue;
    }
    
    // Find the position of the page title/header in the HTML
    $htmlHeaderPatterns = [
        '<h1 class="mt-4 mb-4">',
        '<h1 class="h3',
        '<div class="d-flex justify-content-between align-items-center mb-4">',
        '<h1 class="page-title'
    ];
    
    $headerPos = false;
    foreach ($htmlHeaderPatterns as $pattern) {
        $pos = strpos($content, $pattern);
        if ($pos !== false) {
            $headerPos = $pos;
            break;
        }
    }
    
    if ($headerPos === false) {
        echo "Warning: Could not find page header in $filepath. Skipping...\n";
        continue;
    }
    
    // Find the end of the header section
    $headerEndPos = strpos($content, '</div>', $headerPos);
    if ($headerEndPos === false) {
        // Try to find the next significant HTML element
        $headerEndPos = strpos($content, '<div class="', $headerPos);
    }
    
    if ($headerEndPos === false) {
        echo "Warning: Could not determine header end in $filepath. Skipping...\n";
        continue;
    }
    
    // Extract the action button if present
    $actionButton = '';
    if (preg_match('/<a href="[^"]*" class="btn[^>]*>(.*?)<\/a>/s', substr($content, $headerPos, $headerEndPos - $headerPos), $matches) ||
        preg_match('/<button[^>]*class="btn[^>]*>(.*?)<\/button>/s', substr($content, $headerPos, $headerEndPos - $headerPos), $matches)) {
        $actionButton = $matches[0];
    }
    
    // Create the enhanced header code
    $enhancedHeader = "
    <?php 
    // Define page title, icon, and actions for the enhanced header
    \$pageTitle = \"" . $pageInfo['title'] . "\";
    \$pageIcon = \"" . $pageInfo['icon'] . "\";
    \$actions = [];
    
    ";
    
    // Add action button if found and admin check if needed
    if (!empty($actionButton)) {
        // Extract URL, text and icon from action button
        $url = '#';
        if (preg_match('/href="([^"]*)"/', $actionButton, $urlMatches)) {
            $url = $urlMatches[1];
        }
        
        $text = 'Action';
        if (preg_match('/<i class="fas fa-([^"]*)[^<]*<\/i>\s*(.*?)\s*(?:<\/button>|<\/a>)/s', $actionButton, $textMatches)) {
            $text = trim($textMatches[2]);
        } elseif (preg_match('/<i class="fas fa-([^"]*)[^<]*<\/i>/s', $actionButton, $iconMatches) && 
                  preg_match('/(?:<button|<a)[^>]*>(.*?)(?:<\/button>|<\/a>)/s', $actionButton, $btnTextMatches)) {
            $text = trim(strip_tags($btnTextMatches[1]));
        }
        
        $icon = 'fa-plus';
        if (preg_match('/<i class="fas fa-([^"]*)[^<]*<\/i>/s', $actionButton, $iconMatches)) {
            $icon = 'fa-' . $iconMatches[1];
        }
        
        $class = 'btn-primary';
        if (preg_match('/class="([^"]*)"/', $actionButton, $classMatches)) {
            $classes = explode(' ', $classMatches[1]);
            foreach ($classes as $cls) {
                if (strpos($cls, 'btn-') === 0 && $cls !== 'btn-sm' && $cls !== 'btn-lg') {
                    $class = $cls;
                    break;
                }
            }
        }
        
        $attributes = '';
        if (preg_match('/data-bs-toggle="([^"]*)"/', $actionButton, $toggleMatches)) {
            $attributes .= "data-bs-toggle=\"{$toggleMatches[1]}\" ";
        }
        if (preg_match('/data-bs-target="([^"]*)"/', $actionButton, $targetMatches)) {
            $attributes .= "data-bs-target=\"{$targetMatches[1]}\" ";
        }
        
        if ($pageInfo['admin_only']) {
            $enhancedHeader .= "    if (\$isAdmin) {\n";
            $enhancedHeader .= "        \$actions[] = [\n";
            $enhancedHeader .= "            'url' => '" . $url . "',\n";
            $enhancedHeader .= "            'icon' => '" . $icon . "',\n";
            $enhancedHeader .= "            'text' => '" . $text . "',\n";
            $enhancedHeader .= "            'class' => '" . $class . "',\n";
            if (!empty($attributes)) {
                $enhancedHeader .= "            'attributes' => '" . trim($attributes) . "',\n";
            }
            $enhancedHeader .= "        ];\n";
            $enhancedHeader .= "    }\n";
        } else {
            $enhancedHeader .= "    if (\$canManage" . ucfirst(str_replace('.php', '', $filename)) . " ?? \$isAdmin) {\n";
            $enhancedHeader .= "        \$actions[] = [\n";
            $enhancedHeader .= "            'url' => '" . $url . "',\n";
            $enhancedHeader .= "            'icon' => '" . $icon . "',\n";
            $enhancedHeader .= "            'text' => '" . $text . "',\n";
            $enhancedHeader .= "            'class' => '" . $class . "',\n";
            if (!empty($attributes)) {
                $enhancedHeader .= "            'attributes' => '" . trim($attributes) . "',\n";
            }
            $enhancedHeader .= "        ];\n";
            $enhancedHeader .= "    }\n";
        }
    }
    
    $enhancedHeader .= "    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>";
    
    // Replace the old header with the new one
    $newContent = substr($content, 0, $headerPos) . $enhancedHeader . substr($content, $headerEndPos);
    
    // Update card classes throughout the file
    $newContent = str_replace('class="card mb-4"', 'class="content-card animate-fadeIn mb-4"', $newContent);
    $newContent = str_replace('class="card mb-3"', 'class="content-card animate-fadeIn mb-3"', $newContent);
    $newContent = str_replace('class="card"', 'class="content-card animate-fadeIn"', $newContent);
    $newContent = str_replace('class="card-header"', 'class="content-card-header"', $newContent);
    $newContent = str_replace('class="card-body"', 'class="content-card-body"', $newContent);
    $newContent = str_replace('class="card-title"', 'class="content-card-title"', $newContent);
    
    // Write the updated content back to the file
    if (file_put_contents($filepath, $newContent) === false) {
        echo "Error: Could not write to file $filepath. Skipping...\n";
        continue;
    }
    
    echo "Success: Updated page header in $filepath\n";
}

echo "\nHeader update process completed.\n";
?> 