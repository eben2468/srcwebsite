<?php
/**
 * Icon Functions for SRC Management System
 * This file provides functions for working with system icons
 */

/**
 * Get available icons
 *
 * @return array Array of icon information
 */
function getAvailableIcons() {
    $icons = [
        ['value' => 'university', 'name' => 'University', 'path' => '../images/icons/university.svg'],
        ['value' => 'graduation_cap', 'name' => 'Graduation Cap', 'path' => '../images/icons/graduation_cap.svg'],
        ['value' => 'book', 'name' => 'Book', 'path' => '../images/icons/book.svg'],
        ['value' => 'students', 'name' => 'Students', 'path' => '../images/icons/students.svg'],
        ['value' => 'school', 'name' => 'School', 'path' => '../images/icons/school.svg'],
        ['value' => 'src_badge', 'name' => 'SRC Badge', 'path' => '../images/icons/src_badge.svg'],
        ['value' => 'campus', 'name' => 'Campus', 'path' => '../images/icons/campus.svg'],
        ['value' => 'bible', 'name' => 'Bible', 'path' => '../images/icons/bible.svg'],
        ['value' => 'church', 'name' => 'Church', 'path' => '../images/icons/church.svg'],
        ['value' => 'cross', 'name' => 'Cross', 'path' => '../images/icons/cross.svg'],
        ['value' => 'dove', 'name' => 'Dove', 'path' => '../images/icons/dove.svg'],
        ['value' => 'praying_hands', 'name' => 'Praying Hands', 'path' => '../images/icons/praying_hands.svg']
    ];
    
    return $icons;
}

/**
 * Get information about a specific icon
 *
 * @param string $iconValue The icon value to get information for
 * @return array|null Icon information or null if not found
 */
function getIconInfo($iconValue) {
    $icons = getAvailableIcons();
    
    // Directly return book icon if requested
    if ($iconValue === 'book') {
        $bookPath = '../images/icons/book.svg';
        return [
            'value' => 'book',
            'name' => 'Book',
            'path' => $bookPath
        ];
    }
    
    foreach ($icons as $icon) {
        if ($icon['value'] === $iconValue) {
            return $icon;
        }
    }
    
    // Default to book icon if not found
    foreach ($icons as $icon) {
        if ($icon['value'] === 'book') {
            return $icon;
        }
    }
    
    // If still not found, fallback to university
    foreach ($icons as $icon) {
        if ($icon['value'] === 'university') {
            return $icon;
        }
    }
    
    // If still not found, return the first icon
    return $icons[0] ?? null;
}

/**
 * Get the current icon from settings
 *
 * @return string The current icon value
 */
function getCurrentIcon() {
    // First check global constant (highest priority)
    if (defined('FORCE_ICON') && FORCE_ICON) {
        return 'book';
    }
    
    // Check if already in session
    if (isset($_SESSION['system_icon'])) {
        // Force book icon if requested
        if ($_SESSION['system_icon'] === 'book') {
            return 'book';
        }
        return $_SESSION['system_icon'];
    }
    
    // Get from settings
    if (function_exists('getSetting')) {
        $icon = getSetting('system_icon', 'book');
        
        // Store in session for future use
        $_SESSION['system_icon'] = $icon;
        
        return $icon;
    }
    
    // Default fallback to book
    return 'book';
}

/**
 * Get the current icon path
 *
 * @return string The path to the current icon
 */
function getCurrentIconPath() {
    $currentIcon = getCurrentIcon();
    $iconInfo = getIconInfo($currentIcon);
    
    if ($iconInfo) {
        return $iconInfo['path'];
    }
    
    // Fallback to default
    return '../images/icons/book.svg';
}

/**
 * Check if an icon exists in the file system
 *
 * @param string $iconValue The icon value to check
 * @return bool True if the icon exists, false otherwise
 */
function iconExists($iconValue) {
    $iconInfo = getIconInfo($iconValue);
    
    if (!$iconInfo) {
        return false;
    }
    
    $path = str_replace('../', '', $iconInfo['path']);
    return file_exists($path);
}

/**
 * Update the system icon
 * 
 * @param string $iconValue The icon value to set
 * @param int|null $userId The ID of the user making the change
 * @return bool True if successful, false otherwise
 */
function updateSystemIcon($iconValue, $userId = null) {
    // Validate icon exists
    $iconInfo = getIconInfo($iconValue);
    
    if (!$iconInfo) {
        return false;
    }
    
    // Update the setting
    return updateSetting('system_icon', $iconValue, 'appearance', $userId);
}
?> 