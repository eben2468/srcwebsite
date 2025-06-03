<?php
/**
 * Direct Logo Fix
 * This script directly updates the database and bypasses any caching issues
 */

// Include database connection
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die("Database configuration file not found.");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if we can connect to the database
if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Function to execute a query safely
function executeQuery($sql, $params = []) {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        return [
            'success' => false,
            'error' => mysqli_error($conn)
        ];
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $success = mysqli_stmt_execute($stmt);
    $result = [];
    
    if ($success) {
        $result['success'] = true;
        $result['affected_rows'] = mysqli_stmt_affected_rows($stmt);
    } else {
        $result['success'] = false;
        $result['error'] = mysqli_stmt_error($stmt);
    }
    
    mysqli_stmt_close($stmt);
    return $result;
}

// Function to fetch a single value from the database
function fetchSetting($key, $default = null) {
    global $conn;
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    
    return $default;
}

// Function to insert or update a setting
function updateSetting($key, $value, $group = 'appearance') {
    // Check if setting exists
    $exists = fetchSetting($key) !== null;
    
    if ($exists) {
        // Update existing setting
        $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        return executeQuery($sql, [$value, $key]);
    } else {
        // Insert new setting
        $sql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)";
        return executeQuery($sql, [$key, $value, $group]);
    }
}

// Get form submission
$action = $_POST['action'] ?? '';
$results = [];

if ($action === 'update') {
    // Get values from form
    $logoType = $_POST['logo_type'] ?? 'icon';
    $systemIcon = $_POST['system_icon'] ?? 'university';
    
    // Update logo type
    $results[] = updateSetting('logo_type', $logoType);
    
    // Update system icon if icon type is selected
    if ($logoType === 'icon') {
        $results[] = updateSetting('system_icon', $systemIcon);
    }
    
    // Clear session variables to force refresh
    unset($_SESSION['logo_type']);
    unset($_SESSION['logo_url']);
    unset($_SESSION['system_icon']);
    
    // Set new values in session
    $_SESSION['logo_type'] = $logoType;
    $_SESSION['system_icon'] = $systemIcon;
    
    // Backup the current header.php file
    $headerFile = 'pages_php/includes/header.php';
    $backupFile = 'pages_php/includes/backups/header_' . time() . '.php';
    
    if (file_exists($headerFile)) {
        copy($headerFile, $backupFile);
    }
}

// Get current settings
$currentLogoType = fetchSetting('logo_type', 'icon');
$currentSystemIcon = fetchSetting('system_icon', 'university');
$currentLogoUrl = fetchSetting('logo_url', '../images/logo.png');

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Logo Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .panel { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        img { max-width: 100px; height: auto; }
    </style>
</head>
<body>
    <h1>Direct Logo Fix</h1>
    
    <?php if ($action === 'update'): ?>
    <div class="panel">
        <h2>Update Results</h2>
        <?php foreach ($results as $result): ?>
            <?php if ($result['success']): ?>
                <p class="success">Database updated successfully. Rows affected: <?php echo $result['affected_rows']; ?></p>
            <?php else: ?>
                <p class="error">Error: <?php echo $result['error']; ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <p>Session variables cleared and reset.</p>
        <?php if (file_exists($backupFile)): ?>
            <p>Header file backed up to: <?php echo $backupFile; ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="panel">
        <h2>Current Settings</h2>
        <p><strong>Logo Type:</strong> <?php echo htmlspecialchars($currentLogoType); ?></p>
        <p><strong>System Icon:</strong> <?php echo htmlspecialchars($currentSystemIcon); ?></p>
        <p><strong>Logo URL:</strong> <?php echo htmlspecialchars($currentLogoUrl); ?></p>
        
        <?php if ($currentLogoType === 'custom' && file_exists(str_replace('../', '', $currentLogoUrl))): ?>
            <p><img src="<?php echo htmlspecialchars(str_replace('../', '', $currentLogoUrl)); ?>" alt="Current Logo"></p>
        <?php elseif ($currentLogoType === 'icon'): ?>
            <p>Using system icon: <?php echo htmlspecialchars($currentSystemIcon); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="panel">
        <h2>Fix Logo Settings</h2>
        <form method="post">
            <input type="hidden" name="action" value="update">
            
            <p>
                <strong>Select Logo Type:</strong><br>
                <label>
                    <input type="radio" name="logo_type" value="icon" <?php echo $currentLogoType === 'icon' ? 'checked' : ''; ?>> 
                    Use System Icon
                </label><br>
                <label>
                    <input type="radio" name="logo_type" value="custom" <?php echo $currentLogoType === 'custom' ? 'checked' : ''; ?>> 
                    Use Custom Logo
                </label>
            </p>
            
            <p>
                <strong>Select System Icon:</strong><br>
                <select name="system_icon">
                    <option value="university" <?php echo $currentSystemIcon === 'university' ? 'selected' : ''; ?>>University</option>
                    <option value="graduation_cap" <?php echo $currentSystemIcon === 'graduation_cap' ? 'selected' : ''; ?>>Graduation Cap</option>
                    <option value="book" <?php echo $currentSystemIcon === 'book' ? 'selected' : ''; ?>>Book</option>
                    <option value="students" <?php echo $currentSystemIcon === 'students' ? 'selected' : ''; ?>>Students</option>
                    <option value="school" <?php echo $currentSystemIcon === 'school' ? 'selected' : ''; ?>>School</option>
                    <option value="src_badge" <?php echo $currentSystemIcon === 'src_badge' ? 'selected' : ''; ?>>SRC Badge</option>
                    <option value="campus" <?php echo $currentSystemIcon === 'campus' ? 'selected' : ''; ?>>Campus</option>
                </select>
            </p>
            
            <p>
                <button type="submit">Update Settings</button>
            </p>
        </form>
    </div>
    
    <div class="panel">
        <h2>Next Steps</h2>
        <ol>
            <li>Update the settings using the form above</li>
            <li><a href="clear_cache.php">Clear the cache</a></li>
            <li><a href="pages_php/dashboard.php">Go to the dashboard</a> to see the changes</li>
            <li>If the logo still doesn't update, restart your web browser and try again</li>
        </ol>
    </div>
</body>
</html> 