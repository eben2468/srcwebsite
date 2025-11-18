<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Include the main functions file if it exists
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}
    /**
     * Get current user information
     * This is a fallback function in case auth_functions.php is not loaded
     * 
     * @return array|null User data or null if not logged in
     */
    if (!function_exists('getCurrentUser')) {
        function getCurrentUser() {
            return isset($_SESSION['user']) ? $_SESSION['user'] : null;
        }
    }

    /**
     * Check if the current user is an admin
     * This is a fallback function in case auth_functions.php is not loaded
     * Updated to include super_admin for unified admin interface
     * 
     * @return bool True if user is admin or super_admin, false otherwise
     */
    if (!function_exists('isAdmin')) {
        function isAdmin() {
            return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
        }
    }

    /**
     * Check if user should use admin interface
     * This is a fallback function in case auth_functions.php is not loaded
     * 
     * @return bool True if user should see admin interface
     */
    if (!function_exists('shouldUseAdminInterface')) {
        function shouldUseAdminInterface() {
            return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
        }
    }

    /**
     * Check if the current user is a member
     * This is a fallback function in case auth_functions.php is not loaded
     * 
     * @return bool True if user is a member, false otherwise
     */
    if (!function_exists('isMember')) {
        function isMember() {
            return isset($_SESSION['role']) && $_SESSION['role'] === 'member';
        }
    }



    /**
     * Check if the current user is logged in
     * This is a fallback function in case auth_functions.php is not loaded
     * 
     * @return bool True if user is logged in, false otherwise
     */
    if (!function_exists('isLoggedIn')) {
        function isLoggedIn() {
            return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
        }
    }
}

/**
 * Safe database query function
 * This is a fallback function in case db_functions.php is not loaded
 * 
 * @param string $sql SQL query
 * @param array $params Parameters to bind
 * @return array|null Result row or null
 */
if (!function_exists('fetchOne')) {
    function fetchOne($sql, $params = []) {
        global $conn;
        
        if (!isset($conn) || !$conn) {
            return null;
        }
        
        try {
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt && !empty($params)) {
                $types = str_repeat('s', count($params));
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            
            if ($stmt) {
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                return $row;
            }
        } catch (Exception $e) {
            // Silently handle errors
        }
        
        return null;
    }
}
?> 