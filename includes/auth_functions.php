<?php
/**
 * Extended Authorization Functions
 * Additional permission checking functions (basic auth functions are in simple_auth.php)
 */

// Note: simple_auth.php should be included before this file

/**
 * Check if current user has permission to perform an action
 * @param string $action The action (create, read, update, delete)
 * @param string $resource The resource (users, events, news, etc.)
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($action, $resource) {
    // If not logged in, no permissions
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? 'student';
    
    // Define permissions for each role
    $permissions = [
        'super_admin' => [
            // Core resources
            'users' => ['create', 'read', 'update', 'delete'],
            'events' => ['create', 'read', 'update', 'delete'],
            'news' => ['create', 'read', 'update', 'delete'],
            'documents' => ['create', 'read', 'update', 'delete'],
            'gallery' => ['create', 'read', 'update', 'delete'],
            'elections' => ['create', 'read', 'update', 'delete'],
            'minutes' => ['create', 'read', 'update', 'delete'],
            'reports' => ['create', 'read', 'update', 'delete'],
            'budget' => ['create', 'read', 'update', 'delete'],
            'finance' => ['create', 'read', 'update', 'delete'],
            'feedback' => ['create', 'read', 'update', 'delete'],
            'settings' => ['create', 'read', 'update', 'delete'],
            
            // Resources specified in requirements
            'admin_feedback' => ['create', 'read', 'update', 'delete'],
            'senate' => ['create', 'read', 'update', 'delete'],
            'committees' => ['create', 'read', 'update', 'delete'],
            'welfare' => ['create', 'read', 'update', 'delete'],
            'support' => ['create', 'read', 'update', 'delete'],
            'messaging' => ['create', 'read', 'update', 'delete'],
            
            // Additional resources for completeness
            'welfare_settings' => ['create', 'read', 'update', 'delete'],
            'messaging_settings' => ['create', 'read', 'update', 'delete'],
            'support_notifications' => ['create', 'read', 'update', 'delete'],
            'support_chat' => ['create', 'read', 'update', 'delete'],
            'support_tutorials' => ['create', 'read', 'update', 'delete'],
            
            // Election-related resources
            'election_positions' => ['create', 'read', 'update', 'delete'],
            'election_candidates' => ['create', 'read', 'update', 'delete'],
            'election_votes' => ['create', 'read', 'update', 'delete'],
            'election_results' => ['create', 'read', 'update', 'delete'],
            'election_settings' => ['create', 'read', 'update', 'delete']
        ],
        'admin' => [
            'users' => [], // No access to users page
            'events' => ['create', 'read', 'update', 'delete'],
            'news' => ['create', 'read', 'update', 'delete'],
            'documents' => ['create', 'read', 'update', 'delete'],
            'gallery' => ['create', 'read', 'update', 'delete'],
            'elections' => ['read'], // Same as members and students
            'minutes' => ['create', 'read', 'update', 'delete'],
            'reports' => ['create', 'read', 'update', 'delete'],
            'budget' => ['create', 'read', 'update', 'delete'],
            'finance' => [], // No access to finance page
            'feedback' => ['create', 'read', 'update', 'delete'],
            'settings' => [], // No access to settings page
            'admin_feedback' => ['create', 'read', 'update', 'delete'], // Admin feedback dashboard access
            'senate' => ['create', 'read', 'update', 'delete'],
            'committees' => ['create', 'read', 'update', 'delete'],
            'welfare' => ['create', 'read', 'update', 'delete'],
            'support' => ['create', 'read', 'update', 'delete'],
            'messaging' => ['create', 'read', 'update', 'delete']
        ],
        'member' => [
            'users' => [],
            'events' => ['create', 'read', 'update', 'delete'],
            'news' => ['create', 'read', 'update', 'delete'],
            'documents' => ['create', 'read', 'update', 'delete'],
            'gallery' => ['create', 'read', 'update', 'delete'],
            'elections' => ['read'], // Same privilege as students
            'minutes' => ['create', 'read', 'update', 'delete'],
            'reports' => ['create', 'read', 'update', 'delete'],
            'budget' => ['create', 'read', 'update', 'delete'],
            'finance' => [], // No access to finance page
            'feedback' => ['create', 'read', 'update', 'delete'],
            'settings' => [],
            'admin_feedback' => []
        ],
        'finance' => [
            'users' => [],
            'events' => ['create', 'read', 'update', 'delete'],
            'news' => ['create', 'read', 'update', 'delete'],
            'documents' => ['create', 'read', 'update', 'delete'],
            'gallery' => ['create', 'read', 'update', 'delete'],
            'elections' => ['read'], // Same privilege as students
            'minutes' => ['create', 'read', 'update', 'delete'],
            'reports' => ['create', 'read', 'update', 'delete'],
            'budget' => ['create', 'read', 'update', 'delete'],
            'finance' => ['create', 'read', 'update', 'delete'], // Full finance access
            'feedback' => ['create', 'read', 'update', 'delete'],
            'settings' => [],
            'admin_feedback' => []
        ],
        'student' => [
            'users' => [],
            'events' => ['read'],
            'news' => ['read'],
            'documents' => ['read'],
            'gallery' => ['read'],
            'elections' => ['read'],
            'minutes' => ['read'],
            'reports' => ['read'],
            'budget' => ['read'],
            'finance' => [], // No access to finance page
            'feedback' => ['create', 'read'],
            'settings' => [],
            'admin_feedback' => []
        ]
    ];
    
    // Check if the resource exists for the role
    if (!isset($permissions[$userRole][$resource])) {
        return false;
    }
    
    // Check if the action is allowed for the resource
    return in_array($action, $permissions[$userRole][$resource]);
}

/**
 * Redirect if user doesn't have permission
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
 * Check if user owns a specific resource
 * @param string $resource The resource type
 * @param int $resourceId The ID of the resource
 * @return bool True if user owns the resource, false otherwise
 */
function ownsResource($resource, $resourceId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Super admins own everything
    if (isSuperAdmin()) {
        return true;
    }

    // Admins own everything they have access to
    if (isAdmin()) {
        return true;
    }
    
    // Include database config if not already included
    if (!function_exists('fetchOne')) {
        require_once __DIR__ . '/db_config.php';
    }
    
    $userId = $_SESSION['user_id'];
    
    // Define ownership queries for different resources
    $ownershipQueries = [
        'feedback' => "SELECT user_id FROM feedback WHERE feedback_id = ?",
        'news' => "SELECT author_id FROM news WHERE news_id = ?",
        'events' => "SELECT organizer_id FROM events WHERE event_id = ?",
        'documents' => "SELECT uploaded_by FROM documents WHERE document_id = ?",
        'reports' => "SELECT author_id FROM reports WHERE report_id = ?",
        'minutes' => "SELECT created_by FROM minutes WHERE minutes_id = ?"
    ];
    
    if (!isset($ownershipQueries[$resource])) {
        return false;
    }
    
    try {
        $result = fetchOne($ownershipQueries[$resource], [$resourceId]);
        if ($result) {
            $ownerField = array_keys($result)[0];
            return $result[$ownerField] == $userId;
        }
    } catch (Exception $e) {
        // If there's an error, deny access
        return false;
    }
    
    return false;
}

/**
 * Check if user has permission for a specific resource instance
 * @param string $action The action (create, read, update, delete)
 * @param string $resource The resource type
 * @param int $resourceId The ID of the resource
 * @return bool True if user has permission, false otherwise
 */
function hasResourcePermission($action, $resource, $resourceId) {
    // Super admins always have access to all resources
    if (isSuperAdmin()) {
        return true;
    }

    // Admins have access based on their permission matrix
    if (isAdmin()) {
        return hasPermission($action, $resource);
    }

    // Members have admin-like access to specific resources (except finance)
    if (isMember()) {
        $memberAdminResources = [
            'events', 'news', 'documents', 'gallery',
            'elections', 'minutes', 'reports', 'budget', 'feedback'
        ];

        if (in_array($resource, $memberAdminResources)) {
            return hasPermission($action, $resource);
        }
    }

    // Finance users have admin-like access to specific resources (including finance)
    if (isFinance()) {
        $financeAdminResources = [
            'events', 'news', 'documents', 'gallery',
            'elections', 'minutes', 'reports', 'budget', 'finance', 'feedback'
        ];

        if (in_array($resource, $financeAdminResources)) {
            return hasPermission($action, $resource);
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

/**
 * Check if user should use admin interface
 * Returns true for super admin, admin, and finance users
 * @return bool True if user should see admin interface
 */
function shouldUseAdminInterface() {
    return isSuperAdmin() || isAdmin() || isFinance();
}

/**
 * Check if user can manage specific resource type
 * @param string $resource The resource type (events, news, documents, etc.)
 * @return bool True if user can manage the resource
 */
function canManageResource($resource) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Super admin can manage all resources
    if (isSuperAdmin()) {
        return true;
    }
    
    // Admin can manage resources they have update permission for
    if (isAdmin()) {
        return hasPermission('update', $resource);
    }
    
    // Other roles follow existing permission structure
    return hasPermission('update', $resource);
}

/**
 * Check if user can manage elections (super admin only)
 * @return bool True if user can manage elections
 */
function canManageElections() {
    return isSuperAdmin();
}

/**
 * Check if user can manage finance (super admin or finance role)
 * @return bool True if user can manage finance
 */
function canManageFinance() {
    return isSuperAdmin() || isFinance();
}

// Note: requireLogin(), requireAdmin(), requireMemberOrAdmin(), and logout()
// functions are already defined in simple_auth.php
?>
