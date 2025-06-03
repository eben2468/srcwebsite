<?php
/**
 * Auth Bridge - Links authentication system with URL parameter admin status
 * This file provides functions to help maintain admin status when navigating
 * between regular pages and department pages.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Determines admin status based on URL parameters and auth system
 * 
 * @return bool True if admin, false otherwise
 */
function getBridgedAdminStatus() {
    // Check URL parameter first
    if (isset($_GET['admin']) && $_GET['admin'] == 1) {
        // Store in session for other pages
        $_SESSION['url_admin_status'] = true;
        return true;
    }
    
    // Check session storage
    if (isset($_SESSION['url_admin_status']) && $_SESSION['url_admin_status'] === true) {
        return true;
    }
    
    // Check auth system if available
    if (file_exists('auth_functions.php')) {
        require_once 'auth_functions.php';
        if (function_exists('isAdmin') && isAdmin()) {
            // Store in session for other pages
            $_SESSION['url_admin_status'] = true;
            return true;
        }
    }
    
    return false;
}

/**
 * Generates URL parameter for admin status
 * 
 * @param bool $isAdmin Admin status
 * @return string URL parameter string including "?"
 */
function getAdminUrlParameter($isAdmin = null) {
    // If admin status not provided, determine it
    if ($isAdmin === null) {
        $isAdmin = getBridgedAdminStatus();
    }
    
    return $isAdmin ? '?admin=1' : '';
}

/**
 * Updates link to include admin parameter if needed
 * 
 * @param string $url The URL to update
 * @param bool $isAdmin Admin status
 * @return string Updated URL with admin parameter if needed
 */
function addAdminToUrl($url, $isAdmin = null) {
    // If admin status not provided, determine it
    if ($isAdmin === null) {
        $isAdmin = getBridgedAdminStatus();
    }
    
    if (!$isAdmin) {
        return $url;
    }
    
    // Check if URL already has parameters
    if (strpos($url, '?') !== false) {
        return $url . '&admin=1';
    } else {
        return $url . '?admin=1';
    }
}
?> 