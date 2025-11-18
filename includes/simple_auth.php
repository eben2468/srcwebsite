<?php
/**
 * Simple Authentication Functions
 * Basic session-based authentication for the SRC Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
}

/**
 * Check if current user is super admin (full access)
 */
function isSuperAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

/**
 * Check if current user is admin (limited admin access)
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is member
 */
function isMember() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'member';
}

/**
 * Check if current user is student
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

/**
 * Check if current user is finance manager
 */
function isFinance() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'finance';
}

/**
 * Check if current user has admin-level privileges (super admin or admin)
 */
function hasAdminPrivileges() {
    return isSuperAdmin() || isAdmin();
}

/**
 * Check if current user has member-level privileges (super admin, admin, member, or finance)
 */
function hasMemberPrivileges() {
    return isSuperAdmin() || isAdmin() || isMember() || isFinance();
}

/**
 * Get current user data from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
        'profile_picture' => $_SESSION['profile_picture'] ?? ''
    ];
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Require super admin access (full access)
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Require admin access (super admin or admin)
 */
function requireAdmin() {
    requireLogin();
    if (!hasAdminPrivileges()) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Require member or admin access
 */
function requireMemberOrAdmin() {
    requireLogin();
    if (!hasMemberPrivileges()) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Require finance access (super admin or finance)
 */
function requireFinanceAccess() {
    requireLogin();
    if (!isSuperAdmin() && !isFinance()) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Logout user
 */
function logout() {
    $_SESSION = array();
    session_destroy();
}
?>
