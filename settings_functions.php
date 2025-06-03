<?php
/**
 * Settings Management Functions
 * This file contains functions for managing system settings
 */

// Include database configuration if not already included
if (!function_exists('fetchAll')) {
    require_once 'db_config.php';
}

/**
 * Get a single setting value by key
 * 
 * @param string $key The setting key to retrieve
 * @param mixed $default Default value if setting is not found
 * @return mixed The setting value or default if not found
 */
function getSetting($key, $default = null) {
    $setting = fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    
    if (!$setting) {
        return $default;
    }
    
    // Handle boolean values stored as strings
    if ($setting['setting_value'] === 'true' || $setting['setting_value'] === '1') {
        return true;
    } elseif ($setting['setting_value'] === 'false' || $setting['setting_value'] === '0') {
        return false;
    }
    
    return $setting['setting_value'];
}

/**
 * Get all settings, optionally filtered by group
 * 
 * @param string|null $group The setting group to filter by, or null for all settings
 * @return array Associative array of settings
 */
function getAllSettings($group = null) {
    $settings = [];
    $params = [];
    $sql = "SELECT setting_key, setting_value, setting_group FROM settings";
    
    if ($group) {
        $sql .= " WHERE setting_group = ?";
        $params = [$group];
    }
    
    $result = fetchAll($sql, $params);
    
    // Organize settings by group
    foreach ($result as $row) {
        $group = $row['setting_group'];
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        
        // Handle boolean values stored as strings
        if ($value === 'true' || $value === '1') {
            $value = true;
        } elseif ($value === 'false' || $value === '0') {
            $value = false;
        }
        
        if (!isset($settings[$group])) {
            $settings[$group] = [];
        }
        
        $settings[$group][$key] = $value;
    }
    
    return $settings;
}

/**
 * Update a single setting
 * 
 * @param string $key The setting key to update
 * @param mixed $value The new value
 * @param string $group The setting group (required for new settings)
 * @param int|null $userId The ID of the user making the change
 * @return bool True if successful, false otherwise
 */
function updateSetting($key, $value, $group = null, $userId = null) {
    // Convert boolean values to strings for storage
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }
    
    // Check if setting exists
    $exists = fetchOne("SELECT setting_id FROM settings WHERE setting_key = ?", [$key]);
    
    if ($exists) {
        // Update existing setting
        return update(
            "UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?",
            [$value, $userId, $key]
        ) !== false;
    } else {
        // Insert new setting (group is required for new settings)
        if (!$group) {
            return false;
        }
        
        return insert(
            "INSERT INTO settings (setting_key, setting_value, setting_group, updated_by) VALUES (?, ?, ?, ?)",
            [$key, $value, $group, $userId]
        ) !== false;
    }
}

/**
 * Update multiple settings at once
 * 
 * @param array $settings Associative array of settings to update (key => value)
 * @param string $group The setting group (for new settings)
 * @param int|null $userId The ID of the user making the change
 * @return bool True if all updates were successful, false otherwise
 */
function updateMultipleSettings($settings, $group = null, $userId = null) {
    if (!is_array($settings) || empty($settings)) {
        return false;
    }
    
    $success = true;
    
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value, $group, $userId)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Check if a feature is enabled
 * 
 * @param string $feature The feature key to check (e.g., 'enable_elections')
 * @param bool $default Default value if setting is not found
 * @return bool True if the feature is enabled, false otherwise
 */
function isFeatureEnabled($feature, $default = false) {
    // Always enable documents feature to prevent redirects
    if ($feature === 'enable_documents') {
        return true;
    }
    
    // First check if feature settings are in session for immediate effect
    if (isset($_SESSION['features']) && isset($_SESSION['features'][$feature])) {
        return $_SESSION['features'][$feature];
    }
    
    // Otherwise check the database - get from the features group
    $settings = getAllSettings('features');
    if (isset($settings['features'][$feature])) {
        $value = $settings['features'][$feature];
        // Handle different string representations of boolean values
        if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
            return true;
        } elseif ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
            return false;
        }
        return (bool)$value;
    }
    
    return $default;
}

/**
 * Check if the current user has permission to use a feature
 * 
 * @param string $feature The feature key to check (e.g., 'enable_elections')
 * @param bool $adminOnly Whether only admins can use this feature
 * @return bool True if the user has permission, false otherwise
 */
function hasFeaturePermission($feature, $adminOnly = false) {
    // Admin check if required
    if ($adminOnly && !isAdmin()) {
        return false;
    }
    
    // Check if the feature is enabled
    return isFeatureEnabled($feature);
}

/**
 * Get all available timezones grouped by region
 * 
 * @return array Associative array of timezones grouped by region
 */
function getAvailableTimezones() {
    $regions = [
        'Africa' => DateTimeZone::AFRICA,
        'America' => DateTimeZone::AMERICA,
        'Antarctica' => DateTimeZone::ANTARCTICA,
        'Asia' => DateTimeZone::ASIA,
        'Atlantic' => DateTimeZone::ATLANTIC,
        'Australia' => DateTimeZone::AUSTRALIA,
        'Europe' => DateTimeZone::EUROPE,
        'Indian' => DateTimeZone::INDIAN,
        'Pacific' => DateTimeZone::PACIFIC
    ];
    
    $timezones = [];
    
    foreach ($regions as $region => $mask) {
        $tzlist = DateTimeZone::listIdentifiers($mask);
        
        foreach ($tzlist as $timezone) {
            $timezones[$region][] = $timezone;
        }
    }
    
    return $timezones;
}
?> 