<?php
/**
 * Fix Logo Settings in Database
 * This script directly updates the database settings for the logo
 */

// Start session
session_start();

// Include database configuration
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die('Database configuration file not found.');
}

// Check database connection
if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}

// Define the settings to update
$settingsToUpdate = [
    [
        'key' => 'logo_type',
        'value' => 'icon',
        'group' => 'appearance'
    ],
    [
        'key' => 'system_icon',
        'value' => 'book',
        'group' => 'appearance'
    ]
];

// Update or insert settings
$results = [];
foreach ($settingsToUpdate as $setting) {
    // Check if setting already exists
    $sql = "SELECT setting_id FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $setting['key']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing setting
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ss', $setting['value'], $setting['key']);
        $success = mysqli_stmt_execute($stmt);
        
        $results[] = [
            'key' => $setting['key'],
            'action' => 'update',
            'success' => $success,
            'affected_rows' => mysqli_stmt_affected_rows($stmt)
        ];
    } else {
        // Insert new setting
        $sql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'sss', $setting['key'], $setting['value'], $setting['group']);
        $success = mysqli_stmt_execute($stmt);
        
        $results[] = [
            'key' => $setting['key'],
            'action' => 'insert',
            'success' => $success,
            'affected_rows' => mysqli_stmt_affected_rows($stmt)
        ];
    }
    
    mysqli_stmt_close($stmt);
}

// Clear session variables
unset($_SESSION['logo_type']);
unset($_SESSION['logo_url']);
unset($_SESSION['system_icon']);

// Set new session variables
$_SESSION['logo_type'] = 'icon';
$_SESSION['system_icon'] = 'book';

// Force update of icon files
$iconFile = 'images/icons/book.svg';
if (file_exists($iconFile)) {
    touch($iconFile);
}

// Add cache-busting headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Logo Settings</title>
    <meta http-equiv="refresh" content="5;url=pages_php/dashboard.php">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; background-color: #f9f9f9; }
        h1 { margin-top: 0; color: #333; }
        .action { margin-bottom: 5px; }
    </style>
    <script>
        // Force reload all images on page load
        window.onload = function() {
            // Force clear browser cache for all pages
            localStorage.clear();
            sessionStorage.clear();
            
            // Set a flag to force reload on dashboard
            localStorage.setItem('force_reload', 'true');
        };
    </script>
</head>
<body>
    <div class="container">
        <h1>Fix Logo Settings</h1>
        
        <h2>Database Updates</h2>
        <?php foreach ($results as $result): ?>
        <div class="action <?php echo $result['success'] ? 'success' : 'error'; ?>">
            <?php echo $result['action'] === 'update' ? 'Updated' : 'Inserted'; ?> setting: 
            <strong><?php echo htmlspecialchars($result['key']); ?></strong>
            (<?php echo $result['affected_rows']; ?> rows affected)
        </div>
        <?php endforeach; ?>
        
        <h2>Session Updates</h2>
        <div class="success">
            Session variables cleared and reset:
            <ul>
                <li>logo_type = icon</li>
                <li>system_icon = book</li>
            </ul>
        </div>
        
        <h2>File System</h2>
        <?php if (file_exists($iconFile)): ?>
        <div class="success">
            Found and updated timestamp for icon file: <?php echo htmlspecialchars($iconFile); ?>
        </div>
        <?php else: ?>
        <div class="error">
            Icon file not found: <?php echo htmlspecialchars($iconFile); ?>
        </div>
        <?php endif; ?>
        
        <h2>Next Steps</h2>
        <p>You will be automatically redirected to the dashboard in 5 seconds.</p>
        <p>If you're not redirected, <a href="pages_php/dashboard.php">click here</a>.</p>
        
        <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd;">
            <a href="pages_php/settings.php">Go to Settings</a> | 
            <a href="clear_cache.php">Clear Cache</a> | 
            <a href="index.php">Go to Home</a>
        </div>
    </div>
</body>
</html> 