<?php
// MODIFIED FOR DEBUGGING ON 2025-05-31 - DEBUG FLAG
/**
 * Authentication and Authorization Functions
 * Provides functions for handling user authentication, session management,
 * and role-based access control.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_config.php';
require_once 'functions.php'; // Include general functions
require_once 'activity_functions.php'; // Include activity tracking functions

/**
 * Authenticate a user based on username and password
 *
 * @param string $username The username
 * @param string $password The password
 * @return array|bool User data array on success, false on failure
 */
function authenticateUser($username, $password) {
    $sql = "SELECT * FROM users WHERE username = ? AND (status = 'active' OR status = 'Active') LIMIT 1";
    $user = fetchOne($sql, [$username]);
    
    if ($user && password_verify($password, $user['password'])) {
        // Remove password from user data before storing in session
        unset($user['password']);
        
        // Set user in session
        $_SESSION['user'] = $user;
        $_SESSION['is_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Log login activity
        logUserActivity($user['user_id'], $user['username'], 'login', 'User logged in successfully');
        
        return $user;
    }
    
    return false;
}

/**
 * Check if the current user is logged in
 *
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    // Check if user is logged in and session hasn't expired
    if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
        // Check for session timeout (30 minutes of inactivity)
        $timeout = 30 * 60; // 30 minutes in seconds
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            // Session has expired, log user out
            logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    return false;
}

/**
 * Log out the current user
 */
function logout() {
    // Log logout activity if user is logged in
    if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
        $userId = $_SESSION['user']['user_id'];
        $username = $_SESSION['user']['username'];
        logUserActivity($userId, $username, 'logout', 'User logged out');
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if the current user has admin role
 *
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Check if the current user has member role
 *
 * @return bool True if user is a member, false otherwise
 */
function isMember() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'member';
}

/**
 * Check if the current user has student role
 *
 * @return bool True if user is a student, false otherwise
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student';
}

/**
 * Get the current logged in user data
 *
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Check if current user has permission to perform an action
 *
 * @param string $action The action (create, read, update, delete)
 * @param string $resource The resource (users, events, news, etc.)
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($action, $resource) {
    // If not logged in, no permissions
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['user']['role'];
    
    // Define permissions based on role and resource
    $permissions = [
        'admin' => [
            // Admin has full CRUD access to all resources
            'users' => ['create', 'read', 'update', 'delete'],
            'departments' => ['create', 'read', 'update', 'delete'],
            'portfolios' => ['create', 'read', 'update', 'delete'],
            'events' => ['create', 'read', 'update', 'delete'],
            'news' => ['create', 'read', 'update', 'delete'],
            'documents' => ['create', 'read', 'update', 'delete'],
            'minutes' => ['create', 'read', 'update', 'delete'],
            'elections' => ['create', 'read', 'update', 'delete'],
            'budget' => ['create', 'read', 'update', 'delete'],
            'reports' => ['create', 'read', 'update', 'delete'],
            'feedback' => ['read', 'update', 'delete'], // Admin can read, respond to, and delete feedback
            'settings' => ['read', 'update'],
            'gallery' => ['create', 'read', 'update', 'delete'],
        ],
        'member' => [
            // Members now have expanded access to specific resources
            'users' => ['read'],
            'departments' => ['read'],
            'portfolios' => ['read'],
            'events' => ['create', 'read', 'update', 'delete'], // Full access to events
            'news' => ['create', 'read', 'update', 'delete'],   // Full access to news
            'documents' => ['create', 'read', 'update', 'delete'], // Full access to documents
            'minutes' => ['create', 'read', 'update', 'delete'], // Full access to minutes
            'elections' => ['create', 'read', 'update', 'delete'], // Full access to elections
            'budget' => ['create', 'read', 'update', 'delete'], // Full access to budget
            'reports' => ['create', 'read', 'update', 'delete'], // Full access to reports
            'feedback' => ['create', 'read', 'update', 'delete'], // Can create, read, respond to, and delete feedback
            'settings' => [],
            'gallery' => ['create', 'read', 'update', 'delete'], // Full access to gallery
        ],
        'student' => [
            // Students have very limited access
            'users' => [],
            'departments' => ['read'],
            'portfolios' => ['read'],
            'events' => ['read'],
            'news' => ['read'],
            'documents' => ['read'],
            'minutes' => [],
            'elections' => ['read'],
            'budget' => [],
            'reports' => [],
            'feedback' => ['create'], // Students can only create feedback
            'settings' => [],
            'gallery' => ['read'],
        ],
    ];
    
    // Check if the role exists in permissions
    if (!isset($permissions[$userRole])) {
        return false;
    }
    
    // Check if the resource exists for the role
    if (!isset($permissions[$userRole][$resource])) {
        return false;
    }
    
    // Check if the action is allowed for the resource
    return in_array($action, $permissions[$userRole][$resource]);
}

/**
 * Redirect if user doesn't have permission
 *
 * @param string $action The action (create, read, update, delete)
 * @param string $resource The resource (users, events, news, etc.)
 * @param string $redirectUrl URL to redirect to if permission denied
 */
function requirePermission($action, $resource, $redirectUrl = 'login.php') {
    if (!hasPermission($action, $resource)) {
        // Store intended URL to redirect back after login
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: $redirectUrl");
            exit;
        } else {
            // User is logged in but doesn't have permission
            header("Location: access_denied.php");
            exit;
        }
    }
}

/**
 * Check if user owns a resource (for editing/deleting their own content)
 *
 * @param string $resource The resource type (feedback, etc.)
 * @param int $resourceId The ID of the resource
 * @return bool True if user owns the resource, false otherwise
 */
function ownsResource($resource, $resourceId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = $_SESSION['user']['user_id'];
    
    // Handle different resource types
    switch ($resource) {
        case 'feedback':
            $sql = "SELECT user_id FROM feedback WHERE feedback_id = ? LIMIT 1";
            $result = fetchOne($sql, [$resourceId]);
            return $result && $result['user_id'] == $userId;
            
        // Add more resource types as needed
        
        default:
            return false;
    }
}

/**
 * Check if user has permission for a specific resource instance
 *
 * @param string $action The action (create, read, update, delete)
 * @param string $resource The resource type
 * @param int $resourceId The ID of the resource
 * @return bool True if user has permission, false otherwise
 */
function hasResourcePermission($action, $resource, $resourceId) {
    // Admins always have access
    if (isAdmin()) {
        return true;
    }
    
    // Members have admin-like access to specific resources
    if (isMember()) {
        $memberAdminResources = [
            'events', 'news', 'documents', 'gallery', 
            'elections', 'minutes', 'reports', 'budget', 'feedback'
        ];
        
        if (in_array($resource, $memberAdminResources)) {
            return true;
        }
    }
    
    // For regular users, check general permission first
    if (!hasPermission($action, $resource)) {
        return false;
    }
    
    // For update and delete, users can only modify their own content
    if (in_array($action, ['update', 'delete'])) {
        return ownsResource($resource, $resourceId);
    }
    
    // For read, depends on the resource type
    if ($action === 'read') {
        // Add specific checks here if needed
        return true;
    }
    
    return false;
}
?> 