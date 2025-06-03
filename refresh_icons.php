<?php
/**
 * Refresh Icons Cache
 * This script refreshes the icon cache and updates the system to recognize new icons
 */

// Start session
session_start();

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die('Database configuration file not found.');
}

if (file_exists('auth_functions.php')) {
    require_once 'auth_functions.php';
} else {
    die('Auth functions file not found.');
}

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = '';

// Get all SVG files in the icons directory
$iconDir = 'images/icons/';
$iconFiles = [];
$allSvgFiles = glob($iconDir . '*.svg');

foreach ($allSvgFiles as $svgFile) {
    $fileName = basename($svgFile);
    $iconValue = pathinfo($fileName, PATHINFO_FILENAME);
    $iconFiles[] = [
        'value' => $iconValue,
        'path' => $svgFile,
        'name' => ucwords(str_replace('_', ' ', $iconValue))
    ];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'refresh') {
        // Clear session variables
        unset($_SESSION['logo_type']);
        unset($_SESSION['logo_url']);
        unset($_SESSION['system_icon']);
        
        // Touch all icon files to update timestamps
        foreach ($allSvgFiles as $svgFile) {
            touch($svgFile);
        }
        
        // Update icon_functions.php file
        $iconFunctionsFile = 'icon_functions.php';
        if (file_exists($iconFunctionsFile)) {
            // Read the file
            $content = file_get_contents($iconFunctionsFile);
            
            // Find the getAvailableIcons function
            if (preg_match('/function getAvailableIcons\(\) {[\s\S]*?return \$icons;[\s\S]*?}/m', $content, $matches)) {
                $originalFunction = $matches[0];
                
                // Create new function content
                $newFunction = "function getAvailableIcons() {\n    \$icons = [\n";
                
                // Add each icon
                foreach ($iconFiles as $icon) {
                    $newFunction .= "        ['value' => '" . addslashes($icon['value']) . "', 'name' => '" . addslashes(ucwords(str_replace('_', ' ', $icon['value']))) . "', 'path' => '../images/icons/" . addslashes($icon['value']) . ".svg'],\n";
                }
                
                $newFunction .= "    ];\n    \n    return \$icons;\n}";
                
                // Replace the function in the content
                $newContent = str_replace($originalFunction, $newFunction, $content);
                
                // Write the updated content back to the file
                if (file_put_contents($iconFunctionsFile, $newContent)) {
                    $message = 'Icons refreshed successfully! The icon_functions.php file has been updated with all available icons.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update icon_functions.php file. Please check file permissions.';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Could not find getAvailableIcons function in icon_functions.php. Please check the file structure.';
                $messageType = 'danger';
            }
        } else {
            $message = 'icon_functions.php file not found.';
            $messageType = 'danger';
        }
    }
}

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refresh Icons Cache - SRC Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .icon-preview {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }
        .icon-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            background-color: white;
        }
        .icon-name {
            margin-top: 5px;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .icon-value {
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Refresh Icons Cache</h1>
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="pages_php/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="pages_php/settings.php">Settings</a></li>
                <li class="breadcrumb-item active" aria-current="page">Refresh Icons Cache</li>
            </ol>
        </nav>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Refresh Icons</h5>
            </div>
            <div class="card-body">
                <p>
                    This tool will refresh the icon cache and update the <code>icon_functions.php</code> file to include all icons in the <code>images/icons/</code> directory.
                    Use this after adding new icons to make them available in the system.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="refresh">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i> Refresh Icons
                        </button>
                        <a href="pages_php/settings.php" class="btn btn-outline-secondary">Back to Settings</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Available Icons (<?php echo count($iconFiles); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="icon-grid">
                    <?php foreach ($iconFiles as $icon): ?>
                    <div class="icon-card">
                        <img src="<?php echo $icon['path']; ?>" alt="<?php echo htmlspecialchars($icon['name']); ?>" class="icon-preview">
                        <div class="icon-name"><?php echo htmlspecialchars($icon['name']); ?></div>
                        <div class="icon-value"><?php echo htmlspecialchars($icon['value']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="mt-4 d-flex gap-2 justify-content-center">
            <a href="add_icon.php" class="btn btn-outline-primary">
                <i class="fas fa-plus me-2"></i> Add Custom Icon
            </a>
            <a href="create_fa_icon.php" class="btn btn-outline-primary">
                <i class="fab fa-font-awesome me-2"></i> Create From Font Awesome
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 