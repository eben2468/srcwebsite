<?php
/**
 * Force Logo Update
 * This script directly updates the logo settings in both database and session
 */

// Start session first
session_start();

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die("Database configuration file not found.");
}

if (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
} else {
    die("Settings functions file not found.");
}

// Function to directly update a setting in the database
function forceUpdateSetting($key, $value, $group = 'appearance') {
    global $conn;
    
    // First check if setting exists
    $checkSql = "SELECT setting_id FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing setting
        $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($stmt, 'ss', $value, $key);
        $success = mysqli_stmt_execute($stmt);
    } else {
        // Insert new setting
        $insertSql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertSql);
        mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $group);
        $success = mysqli_stmt_execute($stmt);
    }
    
    return $success;
}

// Display page header
echo "<!DOCTYPE html>
<html>
<head>
    <title>Force Logo Update</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        img { max-width: 100px; border: 1px solid #ddd; padding: 5px; }
    </style>
</head>
<body>
    <h1>Force Logo Update</h1>";

// Check which options are available
echo "<div class='section'>";
echo "<h2>Current Settings</h2>";
echo "<p>Logo Type: " . htmlspecialchars(getSetting('logo_type', 'icon')) . "</p>";
echo "<p>Logo URL: " . htmlspecialchars(getSetting('logo_url', '../images/logo.png')) . "</p>";
echo "<p>System Icon: " . htmlspecialchars(getSetting('system_icon', 'university')) . "</p>";
echo "</div>";

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='section'>";
    echo "<h2>Updating Settings</h2>";
    
    // Get form data
    $logoType = $_POST['logo_type'] ?? 'icon';
    $systemIcon = $_POST['system_icon'] ?? 'university';
    
    // Update logo type
    if (forceUpdateSetting('logo_type', $logoType)) {
        echo "<p class='success'>Logo type updated to: {$logoType}</p>";
    } else {
        echo "<p class='error'>Failed to update logo type</p>";
    }
    
    // Update system icon if using icon type
    if ($logoType === 'icon' && forceUpdateSetting('system_icon', $systemIcon)) {
        echo "<p class='success'>System icon updated to: {$systemIcon}</p>";
    }
    
    // Handle logo upload for custom type
    if ($logoType === 'custom' && isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'images/';
        $fileName = 'logo_' . time() . '.' . pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION);
        $targetFile = $uploadDir . $fileName;
        
        // Make sure the upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Move the uploaded file
        if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $targetFile)) {
            // Update logo URL in database
            $logoUrl = '../' . $targetFile;  // Add ../ prefix for proper path
            if (forceUpdateSetting('logo_url', $logoUrl)) {
                echo "<p class='success'>Custom logo uploaded and updated: {$logoUrl}</p>";
                echo "<p><img src='{$targetFile}' alt='New Logo'></p>";
            } else {
                echo "<p class='error'>Failed to update logo URL in database</p>";
            }
        } else {
            echo "<p class='error'>Failed to upload logo file</p>";
        }
    }
    
    // Clear session variables
    unset($_SESSION['logo_type']);
    unset($_SESSION['logo_url']);
    unset($_SESSION['system_icon']);
    
    // Set new session variables
    $_SESSION['logo_type'] = $logoType;
    if ($logoType === 'icon') {
        $_SESSION['system_icon'] = $systemIcon;
    } else if ($logoType === 'custom' && isset($logoUrl)) {
        $_SESSION['logo_url'] = $logoUrl;
    }
    
    echo "<p class='success'>Session variables updated</p>";
    echo "</div>";
    
    // Add cache-busting script
    echo "<script>
        // Function to add cache-busting parameter to all images
        function bustImageCache() {
            const timestamp = new Date().getTime();
            const images = document.getElementsByTagName('img');
            for (let i = 0; i < images.length; i++) {
                const originalSrc = images[i].src.split('?')[0];
                images[i].src = originalSrc + '?v=' + timestamp;
            }
        }
        
        // Execute when page loads
        window.onload = bustImageCache;
    </script>";
}

// Display the form
echo "<div class='section'>
    <h2>Update Logo Settings</h2>
    <form method='POST' enctype='multipart/form-data'>
        <p>
            <strong>Choose Logo Type:</strong><br>
            <label>
                <input type='radio' name='logo_type' value='icon' checked> 
                Use System Icon
            </label><br>
            <label>
                <input type='radio' name='logo_type' value='custom'> 
                Use Custom Logo
            </label>
        </p>
        
        <div id='system-icon-options'>
            <p><strong>Select System Icon:</strong></p>
            <select name='system_icon'>
                <option value='university'>University</option>
                <option value='graduation_cap'>Graduation Cap</option>
                <option value='book'>Book</option>
                <option value='students'>Students</option>
                <option value='school'>School</option>
                <option value='src_badge'>SRC Badge</option>
                <option value='campus'>Campus</option>
            </select>
        </div>
        
        <div id='custom-logo-options' style='display:none;'>
            <p><strong>Upload Custom Logo:</strong></p>
            <input type='file' name='logo_upload' accept='image/*'>
            <p><small>Recommended size: 48x48 pixels</small></p>
        </div>
        
        <p><input type='submit' value='Update Logo'></p>
    </form>
    
    <script>
        // Toggle form sections based on logo type selection
        document.addEventListener('DOMContentLoaded', function() {
            const logoTypeRadios = document.querySelectorAll('input[name=\"logo_type\"]');
            const systemIconOptions = document.getElementById('system-icon-options');
            const customLogoOptions = document.getElementById('custom-logo-options');
            
            logoTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'icon') {
                        systemIconOptions.style.display = 'block';
                        customLogoOptions.style.display = 'none';
                    } else {
                        systemIconOptions.style.display = 'none';
                        customLogoOptions.style.display = 'block';
                    }
                });
            });
        });
    </script>
</div>

<div class='section'>
    <h2>After Update</h2>
    <p>After updating the logo, you may need to:</p>
    <ol>
        <li>Clear your browser cache: Press Ctrl+F5 or Cmd+Shift+R</li>
        <li><a href='clear_cache.php'>Run the cache clearing script</a></li>
        <li><a href='pages_php/dashboard.php'>Go to the dashboard</a> to see the changes</li>
    </ol>
</div>";

echo "</body>
</html>";
?> 