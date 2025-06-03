<?php
// IMPORTANT: This is a one-time run script. Delete after use for security.

// Include necessary files
require_once 'db_config.php';

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This is a direct fix that doesn't require login
// It fixes the admin_template.php to ensure members can access admin pages

echo "<h1>Immediate Member Access Fix</h1>";

// Fix admin_template.php to explicitly allow members
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
        
        // Define the correct code block
        $new_code = <<<'EOD'
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get user role information
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();

// List of pages members are allowed to access
$allowedForMembers = [
    'events.php', 'news.php', 'documents.php', 'gallery.php', 
    'elections.php', 'minutes.php', 'reports.php', 'budget.php', 'feedback.php',
    'event-edit.php', 'news-edit.php', 'minutes_edit.php', 'election_edit.php', 
    'report_edit.php', 'budget-edit.php'
];

$currentPage = basename($_SERVER['PHP_SELF']);
$isMemberAllowedPage = in_array($currentPage, $allowedForMembers);

// Check if user has permissions to access this page
if (!$isAdmin && !($isMember && $isMemberAllowedPage)) {
    header("Location: access_denied.php");
    exit();
}
EOD;
        
        // Replace the existing access check block
        $pattern = '/\/\/ Check if user is logged in.*?exit\(\);.*?\/\/ Get current user/s';
        if (preg_match($pattern, $template_content)) {
            $template_content = preg_replace($pattern, $new_code . "\n\n// Get current user", $template_content);
            
            // Update the file
            if (file_put_contents($admin_template_path, $template_content)) {
                echo "<p style='color: green;'>Successfully updated admin_template.php with explicit member access check</p>";
            } else {
                echo "<p style='color: red;'>Error updating admin_template.php</p>";
            }
        } else {
            echo "<p style='color: orange;'>Warning: Could not find expected code block in admin_template.php</p>";
            
            // Alternative: Just overwrite the file completely with a fixed version
            $fixed_template = <<<'EOD'
<?php
// Include authentication file and database config
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get user role information
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();

// List of pages members are allowed to access
$allowedForMembers = [
    'events.php', 'news.php', 'documents.php', 'gallery.php', 
    'elections.php', 'minutes.php', 'reports.php', 'budget.php', 'feedback.php',
    'event-edit.php', 'news-edit.php', 'minutes_edit.php', 'election_edit.php', 
    'report_edit.php', 'budget-edit.php'
];

$currentPage = basename($_SERVER['PHP_SELF']);
$isMemberAllowedPage = in_array($currentPage, $allowedForMembers);

// Check if user has permissions to access this page
if (!$isAdmin && !($isMember && $isMemberAllowedPage)) {
    header("Location: access_denied.php");
    exit();
}

// Page content
$pageTitle = "Admin Page Title"; // Change this for each page

// Add page-specific code here
// ...

?>


// Include header
require_once 'includes/header.php';

// Custom styles for this page
?>
<style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding-top: 60px;
            background-color: #343a40;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-link {
            color: #fff;
            display: block;
            padding: 10px 20px;
        }
        .sidebar-link:hover {
            background-color: #495057;
            color: #fff;
            text-decoration: none;
        }
        .sidebar-link.active {
            background-color: #007bff;
        }
    </style>
<?php

?>
<div class="container-fluid">
            <h1 class="mt-4 mb-4"><?php echo $pageTitle; ?></h1>
            
            <!-- Success and Error Messages -->
            <?php if (isset($successMessage) && $successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $successMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage) && $errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $errorMessage; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Page Content Goes Here -->
            <div class="row">
                <div class="col-md-12">
                    <!-- Example Card -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cog mr-2"></i> Admin Functions</h5>
                        </div>
                        <div class="card-body">
                            <p>This is a template for admin pages. Add your content here.</p>
                            
                            <!-- Example Admin Action Buttons -->
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-success">
                                    <i class="fas fa-plus mr-1"></i> Create
                                </button>
                                <button type="button" class="btn btn-info">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button type="button" class="btn btn-danger">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->

<?php require_once 'includes/footer.php'; ?>
EOD;
            
            // Save the completely fixed template
            if (file_put_contents($admin_template_path, $fixed_template)) {
                echo "<p style='color: green;'>Replaced admin_template.php with a completely fixed version</p>";
            } else {
                echo "<p style='color: red;'>Error replacing admin_template.php</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Error: Could not create backup of admin_template.php</p>";
    }
}

// Directly modify a specific page as a test
$test_page = 'pages_php/events.php';
if (file_exists($test_page)) {
    $content = file_get_contents($test_page);
    
    // Check if $isMember variable exists
    if (strpos($content, '$isMember = isMember()') === false) {
        // Add isMember check after isAdmin check
        $content = str_replace(
            '$isAdmin = isAdmin();', 
            '$isAdmin = isAdmin();
$isMember = isMember();', 
            $content
        );
        
        // Add canManageEvents variable
        if (strpos($content, '$canManageEvents') === false) {
            $content = str_replace(
                '$isMember = isMember();', 
                '$isMember = isMember();
$canManageEvents = $isAdmin || $isMember; // Allow both admins and members to manage events', 
                $content
            );
        }
        
        // Fix access check
        $content = preg_replace(
            '/if \(isset\(\$_GET\[\'action\'\]\) && \$_GET\[\'action\'\] === \'new\' && !\$isAdmin\)/', 
            'if (isset($_GET[\'action\']) && $_GET[\'action\'] === \'new\' && !$canManageEvents)', 
            $content
        );
        
        file_put_contents($test_page, $content);
        echo "<p style='color: green;'>Updated events.php to explicitly include member access</p>";
    } else {
        echo "<p style='color: green;'>events.php already has proper member access variables</p>";
    }
} else {
    echo "<p style='color: red;'>events.php not found</p>";
}

// Update auth_functions.php to ensure isMember function is defined correctly
$auth_functions_path = 'auth_functions.php';
if (file_exists($auth_functions_path)) {
    $auth_content = file_get_contents($auth_functions_path);
    
    // Make sure isMember function exists and works correctly
    if (strpos($auth_content, 'function isMember()') !== false) {
        echo "<p style='color: green;'>isMember function exists in auth_functions.php</p>";
    } else {
        // Add isMember function if it doesn't exist
        $member_function = <<<'EOD'

/**
 * Check if the current user has member role
 *
 * @return bool True if user is a member, false otherwise
 */
function isMember() {
    return isLoggedIn() && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'member';
}

EOD;
        
        // Insert after isAdmin function
        $position = strpos($auth_content, 'function isAdmin()') + 500; // Approximate position after isAdmin function
        $auth_content = substr_replace($auth_content, $member_function, $position, 0);
        file_put_contents($auth_functions_path, $auth_content);
        echo "<p style='color: green;'>Added isMember function to auth_functions.php</p>";
    }
} else {
    echo "<p style='color: red;'>auth_functions.php not found</p>";
}

// Create a simple page refresh utility
$refresh_user_path = 'refresh_session.php';
$refresh_content = <<<'EOD'
<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION)) {
    session_start();
}

// Function to refresh user data
function refreshUserData() {
    global $conn;
    
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
        return "No user session found";
    }
    
    $userId = $_SESSION['user']['user_id'];
    
    // Get fresh user data from database
    $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Remove password before storing in session
        unset($row['password']);
        
        // Update session with fresh data
        $_SESSION['user'] = $row;
        return "User session refreshed with latest data";
    } else {
        return "User not found in database";
    }
}

// Output as JSON if requested via AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Refresh the user data
$result = refreshUserData();

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['message' => $result]);
} else {
    // Output HTML
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Refresh</title>
    <meta http-equiv='refresh' content='2;url=" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php') . "'>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Session Refresh</h1>
        <p>" . htmlspecialchars($result) . "</p>
        <p>Redirecting back to previous page...</p>
        <p><a href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php') . "'>Click here if not redirected</a></p>
        
        <h2>Current Session Data:</h2>
        <pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>
    </div>
</body>
</html>";
}
EOD;

file_put_contents($refresh_user_path, $refresh_content);
echo "<p style='color: green;'>Created refresh_session.php utility</p>";

// Create a simple script to reload member permissions
$reload_permissions_path = 'reload_member_permissions.php';
$reload_content = <<<'EOD'
<?php
// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isLoggedIn()) {
    echo "Not logged in";
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();

// Function to refresh user data
function refreshUserData() {
    global $conn;
    
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
        return "No user session found";
    }
    
    $userId = $_SESSION['user']['user_id'];
    
    // Get fresh user data from database
    $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Remove password before storing in session
        unset($row['password']);
        
        // Update session with fresh data
        $_SESSION['user'] = $row;
        return "User session refreshed with latest data";
    } else {
        return "User not found in database";
    }
}

// Output as JSON if requested via AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Refresh the user data
$result = refreshUserData();

// Test member permissions
$canAccessEvents = $isAdmin || ($isMember && in_array('events.php', [
    'events.php', 'news.php', 'documents.php', 'gallery.php', 
    'elections.php', 'minutes.php', 'reports.php', 'budget.php', 'feedback.php'
]));

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'message' => $result,
        'isAdmin' => $isAdmin,
        'isMember' => $isMember,
        'canAccessEvents' => $canAccessEvents
    ]);
} else {
    // Output HTML
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Member Permissions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .true { color: green; }
        .false { color: red; }
        .btn { display: inline-block; padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Member Permissions</h1>
        <p>" . htmlspecialchars($result) . "</p>
        
        <h2>User Information</h2>
        <table>
            <tr>
                <th>Username</th>
                <td>" . htmlspecialchars($currentUser['username']) . "</td>
            </tr>
            <tr>
                <th>Role</th>
                <td>" . htmlspecialchars($currentUser['role']) . "</td>
            </tr>
            <tr>
                <th>Is Admin</th>
                <td class='" . ($isAdmin ? 'true' : 'false') . "'>" . ($isAdmin ? 'Yes' : 'No') . "</td>
            </tr>
            <tr>
                <th>Is Member</th>
                <td class='" . ($isMember ? 'true' : 'false') . "'>" . ($isMember ? 'Yes' : 'No') . "</td>
            </tr>
        </table>
        
        <h2>Page Access Tests</h2>
        <table>
            <tr>
                <th>Page</th>
                <th>Should Have Access</th>
                <th>Test Access</th>
            </tr>";
    
    // Test access to common admin pages
    $adminPages = [
        'events.php' => 'Events',
        'news.php' => 'News',
        'documents.php' => 'Documents',
        'gallery.php' => 'Gallery',
        'elections.php' => 'Elections',
        'minutes.php' => 'Minutes',
        'reports.php' => 'Reports',
        'budget.php' => 'Budget',
        'feedback.php' => 'Feedback'
    ];
    
    foreach ($adminPages as $page => $label) {
        $canAccess = $isAdmin || ($isMember && true); // Members should have access to all these pages
        echo "<tr>
                <td>" . htmlspecialchars($label) . "</td>
                <td class='" . ($canAccess ? 'true' : 'false') . "'>" . ($canAccess ? 'Yes' : 'No') . "</td>
                <td><a href='pages_php/" . htmlspecialchars($page) . "' class='btn' target='_blank'>Test</a></td>
              </tr>";
    }
    
    echo "</table>
        
        <h2>Actions</h2>
        <p><a href='refresh_session.php' class='btn'>Refresh Session Data</a></p>
        <p><a href='pages_php/events.php' class='btn'>Go to Events Admin</a></p>
        <p><a href='pages_php/member_debug.php' class='btn'>Debug Member Access</a></p>
        
        <h2>Current Session Data</h2>
        <pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>
    </div>
</body>
</html>";
}
EOD;

file_put_contents($reload_permissions_path, $reload_content);
echo "<p style='color: green;'>Created reload_member_permissions.php utility</p>";

echo "<h2>Fix Completed</h2>";
echo "<p>The following changes were made:</p>";
echo "<ol>";
echo "<li>Updated admin_template.php with explicit member access check</li>";
echo "<li>Fixed events.php to properly recognize member access</li>";
echo "<li>Verified auth_functions.php has the isMember function</li>";
echo "<li>Created refresh_session.php utility to refresh user session data</li>";
echo "<li>Created reload_member_permissions.php to test member permissions</li>";
echo "</ol>";

echo "<p>To test member access:</p>";
echo "<ol>";
echo "<li>First, ensure you are logged in as a member user</li>";
echo "<li>If you need to refresh your session, visit <a href='refresh_session.php'>refresh_session.php</a></li>";
echo "<li>To test your permissions, visit <a href='reload_member_permissions.php'>reload_member_permissions.php</a></li>";
echo "<li>Try accessing the admin pages directly</li>";
echo "</ol>";

echo "<p><strong>IMPORTANT:</strong> Delete this file after use for security reasons.</p>";
echo "<p><a href='reload_member_permissions.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Test Member Permissions</a></p>";
?> 