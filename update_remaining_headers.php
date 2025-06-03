<?php
/**
 * This is a simplified script to update the remaining page headers
 */

// Define the pages that need updating
$pages = [
    'portfolio.php' => [
        'title' => 'Portfolios',
        'icon' => 'fa-user-tie'
    ],
    'departments.php' => [
        'title' => 'Departments',
        'icon' => 'fa-building'
    ],
    'users.php' => [
        'title' => 'Users',
        'icon' => 'fa-users'
    ],
    'user-activities.php' => [
        'title' => 'User Activities',
        'icon' => 'fa-history'
    ],
    'budget.php' => [
        'title' => 'Budget',
        'icon' => 'fa-money-bill-wave'
    ]
];

foreach ($pages as $filename => $pageInfo) {
    $filePath = 'pages_php/' . $filename;
    
    // Check if file exists
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }
    
    // Read file content
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Could not read file: $filePath\n";
        continue;
    }
    
    // Create the enhanced header code
    $headerCode = "
    <?php 
    // Define page title, icon, and actions for the enhanced header
    \$pageTitle = \"{$pageInfo['title']}\";
    \$pageIcon = \"{$pageInfo['icon']}\";
    \$actions = [];
    
    if (\$isAdmin || \$isMember || isset(\$canManage" . ucfirst(str_replace('.php', '', $filename)) . ")) {
        \$actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Add New',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle=\"modal\" data-bs-target=\"#createModal\"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>";
    
    // Find the page title or header
    $patterns = [
        '<h1 class="mt-4 mb-4">',
        '<h1 class="h3',
        '<div class="d-flex justify-content-between align-items-center mb-4">',
        '<h1 class="page-title'
    ];
    
    $headerPos = false;
    foreach ($patterns as $pattern) {
        $pos = strpos($content, $pattern);
        if ($pos !== false) {
            $headerPos = $pos;
            break;
        }
    }
    
    if ($headerPos === false) {
        echo "Could not find header position in: $filePath\n";
        continue;
    }
    
    // Find where the header section ends
    $headerEndPos = strpos($content, '</div>', $headerPos);
    if ($headerEndPos === false) {
        $headerEndPos = strpos($content, '<div class="', $headerPos + 100);
    }
    
    if ($headerEndPos === false) {
        echo "Could not determine header end in: $filePath\n";
        continue;
    }
    
    // Replace header with our enhanced header
    $newContent = substr($content, 0, $headerPos) . $headerCode . substr($content, $headerEndPos);
    
    // Update card classes throughout the file
    $newContent = str_replace('class="card mb-4"', 'class="content-card animate-fadeIn mb-4"', $newContent);
    $newContent = str_replace('class="card mb-3"', 'class="content-card animate-fadeIn mb-3"', $newContent);
    $newContent = str_replace('class="card"', 'class="content-card animate-fadeIn"', $newContent);
    $newContent = str_replace('class="card-header"', 'class="content-card-header"', $newContent);
    $newContent = str_replace('class="card-body"', 'class="content-card-body"', $newContent);
    $newContent = str_replace('class="card-title"', 'class="content-card-title"', $newContent);
    
    // Write updated content to file
    if (file_put_contents($filePath, $newContent) === false) {
        echo "Failed to write to file: $filePath\n";
        continue;
    }
    
    echo "Successfully updated: $filePath\n";
}

echo "\nAll remaining headers have been updated!\n";
?> 