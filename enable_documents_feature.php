<?php
// Include database configuration
require_once 'db_config.php';
require_once 'settings_functions.php';

// Try to update the setting in the database
try {
    // Check if the setting exists
    $sql = "SELECT * FROM settings WHERE setting_key = 'enable_documents'";
    $result = fetchOne($sql);
    
    if ($result) {
        // Update existing setting
        $updateSql = "UPDATE settings SET setting_value = 'true' WHERE setting_key = 'enable_documents'";
        $updateResult = update($updateSql, []);
        
        if ($updateResult !== false) {
            echo "Documents feature has been enabled successfully.";
        } else {
            echo "Failed to update setting.";
        }
    } else {
        // Insert new setting
        $insertSql = "INSERT INTO settings (setting_key, setting_value, setting_group) VALUES ('enable_documents', 'true', 'features')";
        $insertResult = insert($insertSql, []);
        
        if ($insertResult !== false) {
            echo "Documents feature has been enabled successfully.";
        } else {
            echo "Failed to insert setting.";
        }
    }
    
    // Also update the session to enable the feature immediately
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['features'])) {
        $_SESSION['features'] = [];
    }
    
    $_SESSION['features']['enable_documents'] = true;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 