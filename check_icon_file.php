<?php
/**
 * Check Icon File
 * This script checks if the book icon file exists and is accessible
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database config
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die("Database configuration file not found");
}

// Start session
session_start();

// Get current icon setting from database
try {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'system_icon'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $currentIcon = $row['setting_value'];
    } else {
        $currentIcon = 'book'; // Default if not found
    }
} catch (Exception $e) {
    $currentIcon = 'book'; // Default if error
}

// Get logo_type setting from database
try {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = 'logo_type'";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $logoType = $row['setting_value'];
    } else {
        $logoType = 'icon'; // Default if not found
    }
} catch (Exception $e) {
    $logoType = 'icon'; // Default if error
}

// Define icon paths to check
$iconPaths = [
    'direct' => "images/icons/{$currentIcon}.svg",
    'relative_to_root' => "images/icons/{$currentIcon}.svg",
    'relative_to_pages' => "pages_php/images/icons/{$currentIcon}.svg",
    'from_pages' => "../images/icons/{$currentIcon}.svg"
];

// Check all icon paths
$pathResults = [];
foreach ($iconPaths as $name => $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    $fileSize = $exists ? filesize($path) : 0;
    $lastModified = $exists ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A';
    
    $pathResults[$name] = [
        'path' => $path,
        'exists' => $exists,
        'readable' => $readable,
        'size' => $fileSize,
        'last_modified' => $lastModified
    ];
    
    // If file exists, touch it to update timestamp
    if ($exists) {
        touch($path);
    }
}

// Update all icon paths with the correct values
$updateResults = [];

// Fix database settings directly
$bookIconValue = 'book';
$iconLogoType = 'icon';

// Update icon setting
$sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'system_icon'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $bookIconValue);
$iconUpdateSuccess = mysqli_stmt_execute($stmt);
$updateResults['system_icon'] = [
    'success' => $iconUpdateSuccess,
    'affected_rows' => mysqli_stmt_affected_rows($stmt)
];
mysqli_stmt_close($stmt);

// Update logo type setting
$sql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'logo_type'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $iconLogoType);
$logoTypeUpdateSuccess = mysqli_stmt_execute($stmt);
$updateResults['logo_type'] = [
    'success' => $logoTypeUpdateSuccess,
    'affected_rows' => mysqli_stmt_affected_rows($stmt)
];
mysqli_stmt_close($stmt);

// Clear session variables
unset($_SESSION['logo_type']);
unset($_SESSION['system_icon']);
unset($_SESSION['logo_url']);

// Set new session variables
$_SESSION['logo_type'] = 'icon';
$_SESSION['system_icon'] = 'book';

// Force cache clearing headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Icon File</title>
    <meta http-equiv="refresh" content="5;url=pages_php/dashboard.php">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        h1, h2 { color: #333; }
        img { max-width: 50px; height: auto; }
    </style>
    <script>
        // Force reload all images on page load
        window.onload = function() {
            var cacheBreaker = '?v=' + new Date().getTime();
            var images = document.getElementsByTagName('img');
            for (var i = 0; i < images.length; i++) {
                var originalSrc = images[i].src.split('?')[0];
                images[i].src = originalSrc + cacheBreaker;
            }
            
            // Clear local storage and session storage
            localStorage.clear();
            sessionStorage.clear();
        };
    </script>
</head>
<body>
    <h1>Icon File Check</h1>
    
    <div class="container">
        <h2>Current Settings</h2>
        <p>Current icon from database: <strong><?php echo htmlspecialchars($currentIcon); ?></strong></p>
        <p>Logo type from database: <strong><?php echo htmlspecialchars($logoType); ?></strong></p>
        <p>These values have been updated to:</p>
        <ul>
            <li>Icon: <strong class="success">book</strong></li>
            <li>Logo type: <strong class="success">icon</strong></li>
        </ul>
    </div>
    
    <div class="container">
        <h2>Database Updates</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Success</th>
                <th>Rows Affected</th>
            </tr>
            <?php foreach ($updateResults as $setting => $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($setting); ?></td>
                <td class="<?php echo $result['success'] ? 'success' : 'error'; ?>">
                    <?php echo $result['success'] ? 'Yes' : 'No'; ?>
                </td>
                <td><?php echo $result['affected_rows']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Icon File Paths</h2>
        <table>
            <tr>
                <th>Location</th>
                <th>Path</th>
                <th>Exists</th>
                <th>Readable</th>
                <th>Size</th>
                <th>Last Modified</th>
            </tr>
            <?php foreach ($pathResults as $name => $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td><?php echo htmlspecialchars($result['path']); ?></td>
                <td class="<?php echo $result['exists'] ? 'success' : 'error'; ?>">
                    <?php echo $result['exists'] ? 'Yes' : 'No'; ?>
                </td>
                <td class="<?php echo $result['readable'] ? 'success' : 'error'; ?>">
                    <?php echo $result['readable'] ? 'Yes' : 'No'; ?>
                </td>
                <td><?php echo $result['size']; ?> bytes</td>
                <td><?php echo $result['last_modified']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Book Icon Test</h2>
        <p>Here's the book icon from different paths:</p>
        
        <?php foreach ($pathResults as $name => $result): ?>
            <?php if ($result['exists']): ?>
            <div style="margin-bottom: 10px;">
                <p><strong><?php echo htmlspecialchars($name); ?>:</strong></p>
                <img src="<?php echo htmlspecialchars($result['path']); ?>?v=<?php echo time(); ?>" alt="Book Icon">
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="container">
        <h2>Next Steps</h2>
        <p>You will be automatically redirected to the dashboard in 5 seconds.</p>
        <p>If you're not redirected, <a href="pages_php/dashboard.php">click here</a>.</p>
        <p>If the icon still doesn't appear correctly:</p>
        <ol>
            <li>Press <strong>Ctrl+F5</strong> on the dashboard page to force a complete refresh</li>
            <li>Try clearing your browser cache completely</li>
            <li>Check if the book.svg file exists in your images/icons folder</li>
        </ol>
    </div>
    
    <div class="container">
        <h2>Available Tools</h2>
        <p>
            <a href="fix_logo_in_database.php" class="btn">Fix Logo in Database</a> | 
            <a href="fix_book_icon.php" class="btn">Fix Book Icon</a> | 
            <a href="pages_php/settings.php" class="btn">Go to Settings</a>
        </p>
    </div>
</body>
</html> 