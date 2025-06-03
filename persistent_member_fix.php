<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo "Access denied. You must be an admin to use this tool.";
    exit();
}

echo "<h1>Permanent Member Access Fix</h1>";

// Fix #1: Update the database schema to add page_access field to users table
$fixPageAccessField = false;

// Check if the page_access column exists in the users table
$checkColumnSql = "SHOW COLUMNS FROM users LIKE 'page_access'";
$columnExists = mysqli_query($conn, $checkColumnSql);

if (!$columnExists || mysqli_num_rows($columnExists) == 0) {
    echo "<p>Adding page_access column to users table...</p>";
    
    // Add the column
    $alterTableSql = "ALTER TABLE users ADD COLUMN page_access TEXT NULL AFTER role";
    if (mysqli_query($conn, $alterTableSql)) {
        echo "<p style='color: green;'>Successfully added page_access column to users table</p>";
        $fixPageAccessField = true;
    } else {
        echo "<p style='color: red;'>Error adding page_access column: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: green;'>page_access column already exists in users table</p>";
}

// Fix #2: Update all existing member users to have page access
if ($fixPageAccessField) {
    $memberPages = [
        'events.php', 'news.php', 'documents.php', 'gallery.php', 
        'elections.php', 'minutes.php', 'reports.php', 'budget.php', 'feedback.php',
        'event-edit.php', 'news-edit.php', 'minutes_edit.php', 'election_edit.php', 
        'report_edit.php', 'budget-edit.php'
    ];
    
    $pageAccessJson = json_encode($memberPages);
    
    $updateMembersSql = "UPDATE users SET page_access = ? WHERE role = 'member'";
    $updateStmt = mysqli_prepare($conn, $updateMembersSql);
    mysqli_stmt_bind_param($updateStmt, "s", $pageAccessJson);
    
    if (mysqli_stmt_execute($updateStmt)) {
        $affectedRows = mysqli_stmt_affected_rows($updateStmt);
        echo "<p style='color: green;'>Updated page access for $affectedRows member users</p>";
    } else {
        echo "<p style='color: red;'>Error updating member users: " . mysqli_stmt_error($updateStmt) . "</p>";
    }
    
    mysqli_stmt_close($updateStmt);
}

// Fix #3: Create a modified version of auth_functions.php with more robust member access checking
$auth_functions_path = 'auth_functions.php';

if (!file_exists($auth_functions_path)) {
    echo "<p style='color: red;'>Error: auth_functions.php file not found</p>";
} else {
    // Create a backup of the original file
    $backup_path = 'auth_functions_backup_' . date('Ymd_His') . '.php';
    if (copy($auth_functions_path, $backup_path)) {
        echo "<p>Created backup of auth_functions.php at $backup_path</p>";
        
        // Read the original file
        $auth_content = file_get_contents($auth_functions_path);
        
        // Check if the file already has our robust member access function
        if (strpos($auth_content, 'function getMemberPageAccess') === false) {
            // Add the new functions for member page access
            $new_functions = <<<'EOF'

/**
 * Get the list of pages a member has access to
 * 
 * @param int $userId Optional user ID, defaults to current user
 * @return array List of pages the member has access to
 */
function getMemberPageAccess($userId = null) {
    // Default pages that all members have access to
    $defaultMemberPages = [
        'events.php', 'news.php', 'documents.php', 'gallery.php', 
        'elections.php', 'minutes.php', 'reports.php', 'budget.php', 'feedback.php',
        'event-edit.php', 'news-edit.php', 'minutes_edit.php', 'election_edit.php', 
        'report_edit.php', 'budget-edit.php'
    ];
    
    // If no specific user ID provided, use current user
    if ($userId === null) {
        if (!isLoggedIn()) {
            return [];
        }
        $user = $_SESSION['user'];
    } else {
        // Get user data from database
        $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
        $user = fetchOne($sql, [$userId]);
        if (!$user) {
            return [];
        }
    }
    
    // If user is not a member, they don't have member page access
    if ($user['role'] !== 'member') {
        return [];
    }
    
    // Check if user has custom page access in the database
    if (isset($user['page_access']) && !empty($user['page_access'])) {
        try {
            $customPages = json_decode($user['page_access'], true);
            if (is_array($customPages) && !empty($customPages)) {
                return $customPages;
            }
        } catch (Exception $e) {
            // If there's an error decoding, fall back to default
            error_log("Error decoding page_access for user {$user['user_id']}: " . $e->getMessage());
        }
    }
    
    // Return default member pages
    return $defaultMemberPages;
}

/**
 * Check if member has access to the specified page
 * 
 * @param string $page Page filename
 * @param int $userId Optional user ID, defaults to current user
 * @return bool True if member has access, false otherwise
 */
function memberHasPageAccess($page, $userId = null) {
    // If user is admin, they always have access
    if (isAdmin()) {
        return true;
    }
    
    // If user is not a member, they don't have access
    if (!isMember()) {
        return false;
    }
    
    // Get the pages the member has access to
    $accessiblePages = getMemberPageAccess($userId);
    
    // Check if the page is in the list
    return in_array($page, $accessiblePages);
}

EOF;
            
            // Find the position to insert the new functions
            $position = strrpos($auth_content, '?>');
            if ($position !== false) {
                // Insert the new functions before the closing PHP tag
                $auth_content = substr_replace($auth_content, $new_functions, $position, 0);
                
                // Now modify the hasResourcePermission function to use our new member page access system
                $original_func = 'function hasResourcePermission($action, $resource, $resourceId) {
    // Admins always have access
    if (isAdmin()) {
        return true;
    }
    
    // Members have admin-like access to specific resources
    if (isMember()) {
        $memberAdminResources = [
            \'events\', \'news\', \'documents\', \'gallery\', 
            \'elections\', \'minutes\', \'reports\', \'budget\', \'feedback\'
        ];
        
        if (in_array($resource, $memberAdminResources)) {
            return true;
        }
    }';
                
                $updated_func = 'function hasResourcePermission($action, $resource, $resourceId) {
    // Admins always have access
    if (isAdmin()) {
        return true;
    }
    
    // Members have admin-like access to specific resources
    if (isMember()) {
        $memberAdminResources = [
            \'events\', \'news\', \'documents\', \'gallery\', 
            \'elections\', \'minutes\', \'reports\', \'budget\', \'feedback\'
        ];
        
        if (in_array($resource, $memberAdminResources)) {
            return true;
        }
    }';
                
                // Make sure the update doesn't break anything if the function has been modified
                if (strpos($auth_content, $original_func) !== false) {
                    $auth_content = str_replace($original_func, $updated_func, $auth_content);
                }
                
                // Update the file
                if (file_put_contents($auth_functions_path, $auth_content)) {
                    echo "<p style='color: green;'>Successfully updated auth_functions.php with robust member access functions</p>";
                } else {
                    echo "<p style='color: red;'>Error updating auth_functions.php</p>";
                }
            } else {
                echo "<p style='color: red;'>Error: Could not find closing PHP tag in auth_functions.php</p>";
            }
        } else {
            echo "<p style='color: green;'>auth_functions.php already has the robust member access functions</p>";
        }
    } else {
        echo "<p style='color: red;'>Error: Could not create backup of auth_functions.php</p>";
    }
}

// Fix #4: Update admin_template.php to use the new member page access function
$admin_template_path = 'pages_php/admin_template.php';

if (!file_exists($admin_template_path)) {
    echo "<p style='color: red;'>Error: admin_template.php file not found</p>";
} else {
    // Create a backup of the original file
    $backup_path = 'admin_template_backup_' . date('Ymd_His') . '.php';
    if (copy($admin_template_path, $backup_path)) {
        echo "<p>Created backup of admin_template.php at $backup_path</p>";
        
        // Read the original file
        $template_content = file_get_contents($admin_template_path);
        
        // Check if the file already uses our new function
        if (strpos($template_content, 'memberHasPageAccess') === false) {
            // Define the old code block pattern
            $old_code = '// Check if user has admin privileges
// Modified to allow members to access specific admin pages
$allowedForMembers = [
    \'events.php\', \'news.php\', \'documents.php\', \'gallery.php\', 
    \'elections.php\', \'minutes.php\', \'reports.php\', \'budget.php\', \'feedback.php\',
    \'event-edit.php\', \'news-edit.php\', \'minutes_edit.php\', \'election_edit.php\', 
    \'report_edit.php\', \'budget-edit.php\'
];

$currentPage = basename($_SERVER[\'PHP_SELF\']);
$isMemberAllowedPage = in_array($currentPage, $allowedForMembers);

if (!isAdmin() && !(isMember() && $isMemberAllowedPage)) {
    header("Location: access_denied.php");
    exit();
}';
            
            // Define the new code block
            $new_code = '// Check if user has admin privileges
// Using robust member page access system
$currentPage = basename($_SERVER[\'PHP_SELF\']);

// Check if user is admin or member with page access
if (!isAdmin() && !(isMember() && memberHasPageAccess($currentPage))) {
    header("Location: access_denied.php");
    exit();
}';
            
            // Replace the old code with the new code
            if (strpos($template_content, $old_code) !== false) {
                $template_content = str_replace($old_code, $new_code, $template_content);
                
                // Update the file
                if (file_put_contents($admin_template_path, $template_content)) {
                    echo "<p style='color: green;'>Successfully updated admin_template.php to use the robust member access function</p>";
                } else {
                    echo "<p style='color: red;'>Error updating admin_template.php</p>";
                }
            } else {
                echo "<p style='color: orange;'>Warning: Could not find expected code block in admin_template.php</p>";
                
                // Try a more generic search and replace
                $pattern = '/\/\/ Check if user has admin privileges.*?exit\(\);/s';
                if (preg_match($pattern, $template_content)) {
                    $template_content = preg_replace($pattern, $new_code, $template_content);
                    
                    // Update the file
                    if (file_put_contents($admin_template_path, $template_content)) {
                        echo "<p style='color: green;'>Successfully updated admin_template.php using pattern matching</p>";
                    } else {
                        echo "<p style='color: red;'>Error updating admin_template.php</p>";
                    }
                } else {
                    echo "<p style='color: red;'>Error: Could not find admin privileges check in admin_template.php</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>admin_template.php already uses the robust member access function</p>";
        }
    } else {
        echo "<p style='color: red;'>Error: Could not create backup of admin_template.php</p>";
    }
}

// Fix #5: Create a function to refresh user data from database to ensure session has latest role info
$refresh_file_path = 'refresh_user_session.php';

$refresh_content = <<<'EOF'
<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

/**
 * Refresh user session data from database
 * 
 * @param int $userId Optional user ID, defaults to current user
 * @return bool True if successful, false otherwise
 */
function refreshUserSession($userId = null) {
    if (!isLoggedIn() && $userId === null) {
        return false;
    }
    
    // Get user ID from session if not provided
    if ($userId === null) {
        $userId = $_SESSION['user']['user_id'];
    }
    
    // Get fresh user data from database
    $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
    $user = fetchOne($sql, [$userId]);
    
    if ($user) {
        // Remove password before storing in session
        unset($user['password']);
        
        // Update session
        $_SESSION['user'] = $user;
        return true;
    }
    
    return false;
}

// If this script is accessed directly, refresh the current user's session
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    if (isLoggedIn()) {
        if (refreshUserSession()) {
            echo "User session refreshed successfully.";
        } else {
            echo "Error refreshing user session.";
        }
    } else {
        echo "Not logged in.";
    }
}
?>
EOF;

file_put_contents($refresh_file_path, $refresh_content);
echo "<p style='color: green;'>Created refresh_user_session.php utility</p>";

// Fix #6: Update the isLoggedIn function to automatically refresh user data periodically
$login_check_function = 'function isLoggedIn() {
    // Check if user is logged in and session hasn\'t expired
    if (isset($_SESSION[\'is_logged_in\']) && $_SESSION[\'is_logged_in\'] === true) {
        // Check for session timeout (30 minutes of inactivity)
        $timeout = 30 * 60; // 30 minutes in seconds
        
        if (isset($_SESSION[\'last_activity\']) && (time() - $_SESSION[\'last_activity\'] > $timeout)) {
            // Session has expired, log user out
            logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION[\'last_activity\'] = time();
        return true;
    }
    
    return false;
}';

$updated_login_check = 'function isLoggedIn() {
    // Check if user is logged in and session hasn\'t expired
    if (isset($_SESSION[\'is_logged_in\']) && $_SESSION[\'is_logged_in\'] === true) {
        // Check for session timeout (30 minutes of inactivity)
        $timeout = 30 * 60; // 30 minutes in seconds
        
        if (isset($_SESSION[\'last_activity\']) && (time() - $_SESSION[\'last_activity\'] > $timeout)) {
            // Session has expired, log user out
            logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION[\'last_activity\'] = time();
        
        // Periodically refresh user data from database (every 5 minutes)
        $refresh_interval = 5 * 60; // 5 minutes in seconds
        if (!isset($_SESSION[\'last_refresh\']) || (time() - $_SESSION[\'last_refresh\'] > $refresh_interval)) {
            // Include the refresh function if it exists
            if (file_exists(\'refresh_user_session.php\')) {
                require_once \'refresh_user_session.php\';
                if (function_exists(\'refreshUserSession\')) {
                    refreshUserSession();
                }
            }
            $_SESSION[\'last_refresh\'] = time();
        }
        
        return true;
    }
    
    return false;
}';

// Update the isLoggedIn function in auth_functions.php
$auth_content = file_get_contents($auth_functions_path);
if (strpos($auth_content, $login_check_function) !== false) {
    $auth_content = str_replace($login_check_function, $updated_login_check, $auth_content);
    if (file_put_contents($auth_functions_path, $auth_content)) {
        echo "<p style='color: green;'>Successfully updated isLoggedIn function to periodically refresh user data</p>";
    } else {
        echo "<p style='color: red;'>Error updating isLoggedIn function</p>";
    }
} else {
    echo "<p style='color: orange;'>Warning: Could not find expected isLoggedIn function in auth_functions.php</p>";
}

// Summary
echo "<h2>Summary of Permanent Fixes</h2>";
echo "<ol>";
echo "<li>Added page_access column to users table to store custom page access for each member</li>";
echo "<li>Updated existing member users with default page access</li>";
echo "<li>Added robust member page access functions to auth_functions.php</li>";
echo "<li>Updated admin_template.php to use the new member page access system</li>";
echo "<li>Created refresh_user_session.php to refresh user data from database</li>";
echo "<li>Updated isLoggedIn function to periodically refresh user data</li>";
echo "</ol>";

echo "<p>These changes should ensure that member access to admin pages works consistently and reliably.</p>";
echo "<p>To test, please:</p>";
echo "<ol>";
echo "<li>Log out and log back in as a member user</li>";
echo "<li>Try accessing the admin pages for events, news, documents, etc.</li>";
echo "<li>Verify that the member can create and manage content in these areas</li>";
echo "</ol>";

echo "<p><a href='test_member_login.php' class='btn btn-primary'>Go to Test Member Login Tool</a></p>";
?> 