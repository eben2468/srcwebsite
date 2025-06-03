<?php
/**
 * Reset Logo Cache
 * This script updates the timestamp on icon and logo files to force browser cache refresh
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
}

if (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
}

/**
 * Function to touch all files in a directory (update their timestamps)
 * 
 * @param string $dir Directory to process
 * @param string $pattern File pattern to match
 * @return array Results of the operation
 */
function touchFiles($dir, $pattern = '*') {
    $results = [
        'success' => [],
        'error' => []
    ];
    
    // Get all matching files
    $files = glob($dir . '/' . $pattern);
    
    foreach ($files as $file) {
        if (is_file($file)) {
            // Update the file timestamp
            if (touch($file)) {
                $results['success'][] = $file;
            } else {
                $results['error'][] = $file;
            }
        }
    }
    
    return $results;
}

// Directories to process
$directories = [
    'images' => ['*.jpg', '*.jpeg', '*.png', '*.gif', '*.svg'],
    'images/icons' => ['*.svg', '*.png'],
];

// Store results
$results = [];

// Process each directory
foreach ($directories as $dir => $patterns) {
    if (file_exists($dir) && is_dir($dir)) {
        foreach ($patterns as $pattern) {
            $fileResults = touchFiles($dir, $pattern);
            $results[$dir][$pattern] = $fileResults;
        }
    } else {
        $results[$dir] = ['error' => 'Directory not found'];
    }
}

// Clear session cache
unset($_SESSION['logo_type']);
unset($_SESSION['logo_url']);
unset($_SESSION['system_icon']);

// Set cache-busting headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Get current settings
$logoType = function_exists('getSetting') ? getSetting('logo_type', 'icon') : 'icon';
$logoUrl = function_exists('getSetting') ? getSetting('logo_url', '../images/logo.png') : '../images/logo.png';
$systemIcon = function_exists('getSetting') ? getSetting('system_icon', 'university') : 'university';

// Create a version string for cache busting
$cacheVersion = time();

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Logo Cache</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        ul { list-style-type: none; padding-left: 0; }
        ul.files { max-height: 200px; overflow-y: auto; padding-left: 20px; }
        img { max-width: 100px; border: 1px solid #ddd; padding: 5px; }
    </style>
    <script>
        // Force reload of all images when page loads
        window.onload = function() {
            // Add timestamp to all images to force reload
            var images = document.getElementsByTagName('img');
            for (var i = 0; i < images.length; i++) {
                var originalSrc = images[i].src.split('?')[0];
                images[i].src = originalSrc + '?v=<?php echo $cacheVersion; ?>';
            }
            
            // Add message after images are loaded
            setTimeout(function() {
                document.getElementById('status').innerHTML = 'Cache reset completed successfully!';
            }, 1000);
        };
    </script>
</head>
<body>
    <h1>Reset Logo Cache</h1>
    
    <div class="section">
        <h2>Cache Reset Status</h2>
        <div id="status" class="success">
            <i>Working...</i>
        </div>
    </div>
    
    <div class="section">
        <h2>Current Settings</h2>
        <p><strong>Logo Type:</strong> <?php echo htmlspecialchars($logoType); ?></p>
        <p><strong>Logo URL:</strong> <?php echo htmlspecialchars($logoUrl); ?></p>
        <p><strong>System Icon:</strong> <?php echo htmlspecialchars($systemIcon); ?></p>
        
        <?php 
        // Display the current logo based on settings
        if ($logoType === 'custom') {
            $logoPath = str_replace('../', '', $logoUrl);
            if (file_exists($logoPath)) {
                echo '<p><strong>Current Logo:</strong></p>';
                echo '<p><img src="' . htmlspecialchars($logoPath) . '" alt="Logo"></p>';
            } else {
                echo '<p class="error">Logo file not found at: ' . htmlspecialchars($logoPath) . '</p>';
            }
        } else {
            $iconPath = 'images/icons/' . $systemIcon . '.svg';
            if (file_exists($iconPath)) {
                echo '<p><strong>Current System Icon:</strong></p>';
                echo '<p><img src="' . htmlspecialchars($iconPath) . '" alt="System Icon"></p>';
            } else {
                echo '<p class="error">System icon file not found at: ' . htmlspecialchars($iconPath) . '</p>';
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Files Processed</h2>
        <?php foreach ($results as $dir => $dirResults): ?>
            <h3><?php echo htmlspecialchars($dir); ?></h3>
            
            <?php if (is_array($dirResults)): ?>
                <?php foreach ($dirResults as $pattern => $patternResults): ?>
                    <h4><?php echo htmlspecialchars($pattern); ?></h4>
                    
                    <?php if (isset($patternResults['success']) && !empty($patternResults['success'])): ?>
                        <p class="success">Successfully updated <?php echo count($patternResults['success']); ?> files:</p>
                        <ul class="files">
                            <?php foreach ($patternResults['success'] as $file): ?>
                                <li><?php echo htmlspecialchars($file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No matching files found.</p>
                    <?php endif; ?>
                    
                    <?php if (isset($patternResults['error']) && !empty($patternResults['error'])): ?>
                        <p class="error">Failed to update <?php echo count($patternResults['error']); ?> files:</p>
                        <ul class="files">
                            <?php foreach ($patternResults['error'] as $file): ?>
                                <li><?php echo htmlspecialchars($file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error"><?php echo htmlspecialchars($dirResults['error']); ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="section">
        <h2>Session Cache</h2>
        <p>Session variables for logo settings have been cleared.</p>
        <p>When you visit any page, the settings will be loaded fresh from the database.</p>
    </div>
    
    <div class="section">
        <h2>Next Steps</h2>
        <ol>
            <li>Try refreshing the dashboard page with a hard refresh (Ctrl+F5 or Cmd+Shift+R)</li>
            <li>If the logo still doesn't update, try the <a href="direct_logo_fix.php">direct logo fix tool</a></li>
            <li>If all else fails, try restarting your web browser</li>
        </ol>
        
        <p><a href="pages_php/dashboard.php" class="btn">Go to Dashboard</a></p>
    </div>
</body>
</html> 