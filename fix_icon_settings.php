<?php
/**
 * Fix Icon Settings
 * This script updates the system icon settings directly in the database
 */

// Include required files
require_once 'db_config.php';

// The icon to set as default
$defaultIcon = 'students'; // Change this to your preferred icon

// Check if settings table exists
$checkTableSQL = "SHOW TABLES LIKE 'settings'";
$tableExists = mysqli_query($conn, $checkTableSQL);

if ($tableExists && mysqli_num_rows($tableExists) > 0) {
    // Update system_icon setting
    $updateIconSQL = "UPDATE settings SET setting_value = ? WHERE setting_key = 'system_icon'";
    $stmt = mysqli_prepare($conn, $updateIconSQL);
    mysqli_stmt_bind_param($stmt, 's', $defaultIcon);
    
    if (mysqli_stmt_execute($stmt)) {
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        echo "Updated system_icon setting to '$defaultIcon'. Rows affected: $affectedRows\n";
    } else {
        echo "Failed to update system_icon setting: " . mysqli_error($conn) . "\n";
    }
    
    mysqli_stmt_close($stmt);
    
    // Check if logo_type setting exists
    $checkLogoTypeSQL = "SELECT COUNT(*) as count FROM settings WHERE setting_key = 'logo_type'";
    $logoTypeResult = mysqli_query($conn, $checkLogoTypeSQL);
    $logoTypeRow = mysqli_fetch_assoc($logoTypeResult);
    
    if ($logoTypeRow['count'] > 0) {
        // Update logo_type setting
        $updateLogoTypeSQL = "UPDATE settings SET setting_value = 'icon' WHERE setting_key = 'logo_type'";
        if (mysqli_query($conn, $updateLogoTypeSQL)) {
            echo "Updated logo_type setting to 'icon'\n";
        } else {
            echo "Failed to update logo_type setting: " . mysqli_error($conn) . "\n";
        }
    } else {
        // Insert logo_type setting
        $insertLogoTypeSQL = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES ('logo_type', 'icon', 'appearance')";
        if (mysqli_query($conn, $insertLogoTypeSQL)) {
            echo "Inserted logo_type setting with value 'icon'\n";
        } else {
            echo "Failed to insert logo_type setting: " . mysqli_error($conn) . "\n";
        }
    }
    
    // Clear any session variables that might be caching old values
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Update session values
    $_SESSION['system_icon'] = $defaultIcon;
    $_SESSION['logo_type'] = 'icon';
    
    echo "Updated session variables.\n";
} else {
    echo "Settings table does not exist. Please run initialize_settings.php first.\n";
}

echo "Script completed.\n";
?> 