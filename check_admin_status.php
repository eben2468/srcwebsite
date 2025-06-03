<?php
/**
 * Admin Status Check Utility
 * This script checks the user's admin status in various ways to diagnose issues
 */

// Include necessary files
require_once 'auth_functions.php';
require_once 'db_config.php';
require_once 'auth_bridge.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to check admin status in all possible ways
function checkAdminStatusComprehensive() {
    $results = [];
    
    // Method 1: Direct isAdmin() function
    $results['isAdmin()'] = isAdmin();
    
    // Method 2: Check via session
    $results['$_SESSION[\'user_role\']'] = isset($_SESSION['user_role']) ? $_SESSION['user_role'] === 'admin' : false;
    
    // Method 3: Check via current user object
    $currentUser = getCurrentUser();
    $results['getCurrentUser()[\'role\']'] = isset($currentUser['role']) ? $currentUser['role'] === 'admin' : false;
    
    // Method 4: Check via database directly
    $results['Database Check'] = false;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $query = "SELECT role FROM users WHERE user_id = ?";
        try {
            $stmt = $GLOBALS['conn']->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $results['Database Check'] = $row['role'] === 'admin';
            }
        } catch (Exception $e) {
            $results['Database Error'] = $e->getMessage();
        }
    }
    
    // Method 5: Check via the auth bridge
    $results['getBridgedAdminStatus()'] = function_exists('getBridgedAdminStatus') ? getBridgedAdminStatus() : 'Function not available';
    
    return $results;
}

// Function to fix admin status if possible
function fixAdminStatus() {
    $fixed = false;
    $message = '';
    
    // Get current user from database
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $query = "SELECT role FROM users WHERE user_id = ?";
        try {
            $stmt = $GLOBALS['conn']->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $dbRole = $row['role'];
                
                // Update session if it doesn't match database
                if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $dbRole) {
                    $_SESSION['user_role'] = $dbRole;
                    $fixed = true;
                    $message = "Updated session role from '{$_SESSION['user_role']}' to '{$dbRole}'";
                } else {
                    $message = "Session role already matches database role: '{$dbRole}'";
                }
            } else {
                $message = "User not found in database.";
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
        }
    } else {
        $message = "No user_id in session.";
    }
    
    return ['fixed' => $fixed, 'message' => $message];
}

// Check if fix was requested
$fixRequested = isset($_GET['fix']) && $_GET['fix'] === '1';
$fixResult = null;
if ($fixRequested) {
    $fixResult = fixAdminStatus();
}

// Get admin status check results
$adminStatusChecks = checkAdminStatusComprehensive();

// Check if logged in
$isLoggedIn = isLoggedIn();
$sessionInfo = $_SESSION;

// Sanitize session data for display
foreach ($sessionInfo as $key => $value) {
    if (is_array($value)) {
        $sessionInfo[$key] = '[Array]';
    } elseif (is_object($value)) {
        $sessionInfo[$key] = '[Object]';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Status Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; line-height: 1.6; }
        h1 { color: #4b6cb7; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .btn { display: inline-block; background-color: #4b6cb7; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Admin Status Check Utility</h1>
    
    <div class="info">
        <h2>Login Status</h2>
        <p>Logged in: <strong><?php echo $isLoggedIn ? '<span class="success">Yes</span>' : '<span class="error">No</span>'; ?></strong></p>
        <?php if (!$isLoggedIn): ?>
            <p class="error">You are not logged in. Please <a href="pages_php/login.php">login</a> first.</p>
        <?php endif; ?>
    </div>
    
    <?php if ($isLoggedIn): ?>
        <div class="info">
            <h2>Admin Status Check Results</h2>
            <table>
                <tr>
                    <th>Method</th>
                    <th>Result</th>
                </tr>
                <?php foreach ($adminStatusChecks as $method => $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($method); ?></td>
                        <td>
                            <?php 
                            if (is_bool($result)) {
                                echo $result 
                                    ? '<span class="success">True (Is Admin)</span>' 
                                    : '<span class="error">False (Not Admin)</span>';
                            } else {
                                echo htmlspecialchars($result);
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <?php if ($fixRequested): ?>
                <div class="<?php echo $fixResult['fixed'] ? 'success' : 'warning'; ?>">
                    <p><strong>Fix attempt result:</strong> <?php echo htmlspecialchars($fixResult['message']); ?></p>
                </div>
            <?php else: ?>
                <p><a href="?fix=1" class="btn">Attempt to Fix Admin Status</a></p>
            <?php endif; ?>
        </div>
        
        <div class="info">
            <h2>Session Information</h2>
            <table>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($sessionInfo as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php echo htmlspecialchars($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div>
            <a href="pages_php/dashboard.php" class="btn">Go to Dashboard</a>
            <a href="pages_php/user-activities.php" class="btn">Go to User Activities</a>
            <a href="test_sidebar.php" class="btn">Run Sidebar Test</a>
        </div>
    <?php endif; ?>
</body>
</html> 