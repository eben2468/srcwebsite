<?php
// Include simple authentication
require_once __DIR__ . '/../includes/simple_auth.php';
/**
 * User Activity Tracking Functions
 * This file contains functions for tracking user activities throughout the system
 */

// Include database configuration if not already included
if (!function_exists('fetchAll')) {
    require_once 'includes/db_config.php';
}

/**
 * Log user activity
 * 
 * @param int $userId User ID
 * @param string $userEmail User's email address
 * @param string $activityType Type of activity (login, logout, create, update, delete, view)
 * @param string $activityDescription Description of the activity
 * @param string $pageUrl URL of the page where activity occurred (optional)
 * @return int|false The activity ID or false on failure
 */
function logUserActivity($userId, $username, $activityType, $activityDetails) {
    try {
        $sql = "INSERT INTO user_activities (user_id, username, activity_type, activity_details) VALUES (?, ?, ?, ?)";
        $params = [$userId, $username, $activityType, $activityDetails];
        return insert($sql, $params);
    } catch (Exception $e) {
        // Log error but don't disrupt the user experience
        error_log("Failed to log user activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Track page view for a logged-in user
 * 
 * @param string $pageTitle Title or name of the page being viewed
 * @return void
 */
function trackPageView($pageTitle) {
    // Only track page views for logged-in users
    if (!function_exists('isLoggedIn') || !isLoggedIn() || !isset($_SESSION['user'])) {
        return;
    }
    
    // Get user information from session
    $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['user_id'] ?? null);
    $userEmail = $_SESSION['email'] ?? ($_SESSION['user']['email'] ?? 'unknown@example.com');

    // If no user ID found, skip logging
    if (!$userId) {
        return;
    }
    
    // Check if table exists before logging
    try {
        $tableCheckSql = "SHOW TABLES LIKE 'user_activities'";
        $tableExists = mysqli_query($GLOBALS['conn'], $tableCheckSql);
        
        if ($tableExists && mysqli_num_rows($tableExists) > 0) {
            // Get the current page URL
            $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
            
            // Log the page view
            logUserActivity(
                $userId,
                $userEmail,
                'view',
                'Viewed page: ' . $pageTitle
            );
        }
    } catch (Exception $e) {
        // Silently fail to not disrupt user experience
        error_log("Failed to track page view: " . $e->getMessage());
    }
}

/**
 * Get user activities with filters
 * 
 * @param array $filters Associative array of filters (user_id, activity_type, start_date, end_date)
 * @param int $limit Number of records to return (0 for all)
 * @param int $offset Offset for pagination
 * @return array Array of activities
 */
function getUserActivities($filters = [], $limit = 0, $offset = 0) {
    $sql = "SELECT a.*, u.first_name, u.last_name, u.role, u.email
            FROM user_activities a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE 1=1";
    $params = [];

    // Apply filters
    if (isset($filters['user_id']) && $filters['user_id'] > 0) {
        $sql .= " AND a.user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (isset($filters['user_email']) && !empty($filters['user_email'])) {
        $sql .= " AND u.email LIKE ?";
        $params[] = "%{$filters['user_email']}%";
    }
    
    if (isset($filters['activity_type']) && !empty($filters['activity_type'])) {
        $sql .= " AND a.activity_type = ?";
        $params[] = $filters['activity_type'];
    }
    
    if (isset($filters['start_date']) && !empty($filters['start_date'])) {
        $sql .= " AND DATE(a.created_at) >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (isset($filters['end_date']) && !empty($filters['end_date'])) {
        $sql .= " AND DATE(a.created_at) <= ?";
        $params[] = $filters['end_date'];
    }
    
    // Order by most recent first
    $sql .= " ORDER BY a.created_at DESC";
    
    // Apply limit and offset if specified
    if ($limit > 0) {
        $sql .= " LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
    }
    
    try {
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Failed to fetch user activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Count user activities with filters
 * 
 * @param array $filters Associative array of filters (user_id, activity_type, start_date, end_date)
 * @return int Number of activities matching the filters
 */
function countUserActivities($filters = []) {
    $sql = "SELECT COUNT(*) as count FROM user_activities a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE 1=1";
    $params = [];

    // Apply filters
    if (isset($filters['user_id']) && $filters['user_id'] > 0) {
        $sql .= " AND a.user_id = ?";
        $params[] = $filters['user_id'];
    }

    if (isset($filters['user_email']) && !empty($filters['user_email'])) {
        $sql .= " AND u.email LIKE ?";
        $params[] = "%{$filters['user_email']}%";
    }
    
    if (isset($filters['activity_type']) && !empty($filters['activity_type'])) {
        $sql .= " AND a.activity_type = ?";
        $params[] = $filters['activity_type'];
    }
    
    if (isset($filters['start_date']) && !empty($filters['start_date'])) {
        $sql .= " AND DATE(a.created_at) >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (isset($filters['end_date']) && !empty($filters['end_date'])) {
        $sql .= " AND DATE(a.created_at) <= ?";
        $params[] = $filters['end_date'];
    }
    
    try {
        $result = fetchOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log("Failed to count user activities: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get summary of activities by type
 * 
 * @param array $filters Associative array of filters (user_id, start_date, end_date)
 * @return array Summary of activities by type
 */
function getActivityTypeSummary($filters = []) {
    $sql = "SELECT activity_type, COUNT(*) as count 
            FROM user_activities 
            WHERE 1=1";
    $params = [];
    
    // Apply filters
    if (isset($filters['user_id']) && $filters['user_id'] > 0) {
        $sql .= " AND user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (isset($filters['start_date']) && !empty($filters['start_date'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (isset($filters['end_date']) && !empty($filters['end_date'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['end_date'];
    }
    
    $sql .= " GROUP BY activity_type ORDER BY count DESC";
    
    try {
        return fetchAll($sql, $params);
    } catch (Exception $e) {
        error_log("Failed to get activity type summary: " . $e->getMessage());
        return [];
    }
}

/**
 * Clear user activities from the database
 * 
 * @param array $filters Associative array of filters (user_id, activity_type, start_date, end_date)
 * @return bool True on success, false on failure
 */
function clearUserActivities($filters = []) {
    global $conn;

    // If filtering by user_email, we need to get user_ids first
    if (isset($filters['user_email']) && !empty($filters['user_email'])) {
        $userSql = "SELECT user_id FROM users WHERE email LIKE ?";
        $userParams = ["%{$filters['user_email']}%"];
        $users = fetchAll($userSql, $userParams);

        if (empty($users)) {
            return true; // No users found, nothing to delete
        }

        $userIds = array_column($users, 'user_id');
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $sql = "DELETE FROM user_activities WHERE user_id IN ($placeholders)";
        $params = $userIds;
    } else {
        $sql = "DELETE FROM user_activities WHERE 1=1";
        $params = [];

        // Apply other filters
        if (isset($filters['user_id']) && $filters['user_id'] > 0) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
    }

    // Apply remaining filters (only if not using user_email filter)
    if (!isset($filters['user_email']) || empty($filters['user_email'])) {
        if (isset($filters['activity_type']) && !empty($filters['activity_type'])) {
            $sql .= " AND activity_type = ?";
            $params[] = $filters['activity_type'];
        }

        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $filters['end_date'];
        }
    }
    
    try {
        return executeQuery($sql, $params);
    } catch (Exception $e) {
        error_log("Failed to clear user activities: " . $e->getMessage());
        return false;
    }
}

/**
 * Record user activity for specific content types
 * 
 * @param int $userId User ID
 * @param string $actionType Type of action (view, create, update, delete, comment, etc.)
 * @param string $contentType Type of content (budget, event, news, etc.)
 * @param int $contentId ID of the content item
 * @param string $description Description of the activity
 * @return bool True on success, false on failure
 */
function recordUserActivity($userId, $actionType, $contentType, $contentId, $description = '') {
    // Get the username if available
    $username = '';
    if (function_exists('isLoggedIn') && isLoggedIn() && isset($_SESSION['user'])) {
        $username = $_SESSION['user']['username'] ?? '';
    }
    
    // Get current page URL
    $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
    
    // Format activity description if not provided
    if (empty($description)) {
        $description = ucfirst($actionType) . ' ' . $contentType . ' #' . $contentId;
    }
    
    // Log the activity using the existing function
    try {
        if (function_exists('logUserActivity')) {
            return logUserActivity($userId, $username, $actionType, $description);
        }
        
        // Fallback if logUserActivity doesn't exist
        global $conn;
        if (isset($conn)) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $sql = "INSERT INTO user_activities (user_id, username, activity_type, activity_description, ip_address, page_url) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'isssss', $userId, $username, $actionType, $description, $ipAddress, $pageUrl);
                $result = mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                return $result;
            }
        }
        
        return false;
    } catch (Exception $e) {
        // Log error but don't disrupt the user experience
        error_log("Failed to record user activity: " . $e->getMessage());
        return false;
    }
}
?>
