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

echo "<h1>Member Access Fix Utility</h1>";

// List of files that need the $isMember variable added
$files_to_check = [
    'pages_php/events.php',
    'pages_php/news.php',
    'pages_php/documents.php',
    'pages_php/gallery.php',
    'pages_php/elections.php',
    'pages_php/minutes.php',
    'pages_php/reports.php',
    'pages_php/budget.php',
    'pages_php/feedback.php'
];

echo "<h2>Checking files for proper member access variables...</h2>";
echo "<ul>";

foreach ($files_to_check as $file) {
    echo "<li>Checking $file: ";
    
    if (!file_exists($file)) {
        echo "<span style='color: red;'>File not found</span>";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if file already has $isMember variable
    if (strpos($content, '$isMember = isMember()') !== false) {
        echo "<span style='color: green;'>OK - Already has isMember variable</span>";
    } else {
        echo "<span style='color: orange;'>Needs update - Missing isMember variable</span>";
        
        // Find the isAdmin variable declaration
        $pattern = '/\$isAdmin\s*=\s*isAdmin\(\);/';
        if (preg_match($pattern, $content)) {
            // Add the $isMember line after the $isAdmin line
            $content = preg_replace(
                $pattern,
                '$0' . "\n" . '$isMember = isMember();',
                $content
            );
            
            // Now find places that check only isAdmin and add isMember check
            $pattern = '/if\s*\(\s*!\s*\$isAdmin\s*\)\s*{/';
            if (preg_match($pattern, $content)) {
                $content = preg_replace(
                    $pattern,
                    'if (!$isAdmin && !$isMember) {',
                    $content
                );
                
                // Save the modified file
                file_put_contents($file, $content);
                echo " - <span style='color: green;'>Fixed</span>";
            } else {
                echo " - <span style='color: orange;'>No isAdmin check found to update</span>";
            }
        } else {
            echo " - <span style='color: red;'>Could not find isAdmin variable</span>";
        }
    }
    
    // Now check for $canManage variables
    if (strpos($content, '$canManage') === false && 
        strpos($content, '$isAdmin || $isMember') === false) {
        echo " - <span style='color: orange;'>Missing canManage variable</span>";
        
        // Try to add the canManage variable for the specific resource
        $resource = pathinfo($file, PATHINFO_FILENAME);
        $resourceUC = ucfirst($resource);
        
        // Check if there's a block after $isMember = isMember();
        if (preg_match('/\$isMember = isMember\(\);/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);
            $canManageLine = "\n" . '$canManage' . $resourceUC . 's = $isAdmin || $isMember; // Allow both admins and members to manage ' . $resource;
            
            // Insert the canManage line
            $content = substr($content, 0, $pos) . $canManageLine . substr($content, $pos);
            
            // Save the modified file
            file_put_contents($file, $content);
            echo " - <span style='color: green;'>Added canManage variable</span>";
        } else {
            echo " - <span style='color: red;'>Could not find insertion point for canManage variable</span>";
        }
    } else {
        echo " - <span style='color: green;'>Has canManage variable or equivalent</span>";
    }
    
    echo "</li>";
}

echo "</ul>";

// Now check admin_template.php to make sure member access is correctly implemented
$admin_template = 'pages_php/admin_template.php';
echo "<h2>Checking admin_template.php...</h2>";

if (!file_exists($admin_template)) {
    echo "<p style='color: red;'>Admin template file not found</p>";
} else {
    $content = file_get_contents($admin_template);
    
    // Check if it has the correct member access code
    if (strpos($content, '$isMemberAllowedPage = in_array($currentPage, $allowedForMembers)') !== false &&
        strpos($content, '!isAdmin() && !(isMember() && $isMemberAllowedPage)') !== false) {
        echo "<p style='color: green;'>Admin template already has correct member access code</p>";
    } else {
        echo "<p style='color: orange;'>Admin template needs updating</p>";
        
        // Define the correct code block
        $allowedPagesBlock = '// Check if user has admin privileges
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

        // Find the admin check pattern and replace it
        $pattern = '/\/\/ Check if user has admin privileges.*?exit\(\);/s';
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $allowedPagesBlock, $content);
            
            // Save the modified file
            file_put_contents($admin_template, $content);
            echo "<p style='color: green;'>Updated admin template with correct member access code</p>";
        } else {
            echo "<p style='color: red;'>Could not find admin privileges check block in admin template</p>";
        }
    }
}

// Check header.php to make sure it shows the correct menu items for members
$header_file = 'pages_php/includes/header.php';
echo "<h2>Checking header.php for member menu visibility...</h2>";

if (!file_exists($header_file)) {
    echo "<p style='color: red;'>Header file not found</p>";
} else {
    $content = file_get_contents($header_file);
    
    // Check if it already has the correct member-specific menu items
    if (strpos($content, '<?php if ($isAdmin || (isset($isMember) && $isMember)): ?>') !== false) {
        echo "<p style='color: green;'>Header file already has correct member menu visibility</p>";
    } else {
        echo "<p style='color: orange;'>Header file needs updating for member menu visibility</p>";
        
        // Find the management section
        $pattern = '/\<\?php if \(\$isAdmin\): \?\>\s*<hr class="mx-3">\s*<div class="sidebar-heading ms-3">MANAGEMENT<\/div>/';
        if (preg_match($pattern, $content)) {
            // Replace with the correct condition
            $content = preg_replace(
                $pattern,
                '<?php if ($isAdmin || (isset($isMember) && $isMember)): ?>' . "\n" . '        <hr class="mx-3">' . "\n" . '        <div class="sidebar-heading ms-3">MANAGEMENT</div>',
                $content
            );
            
            // Save the modified file
            file_put_contents($header_file, $content);
            echo "<p style='color: green;'>Updated header file with correct member menu visibility</p>";
        } else {
            echo "<p style='color: red;'>Could not find management section in header file</p>";
        }
    }
}

// Create a test login file to help test member privileges
$test_login_file = 'test_member_login.php';
echo "<h2>Creating test member login utility...</h2>";

$test_login_content = '<?php
// Include necessary files
require_once \'auth_functions.php\';
require_once \'db_config.php\';

// Set up error reporting
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
error_reporting(E_ALL);

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    echo "Access denied. You must be an admin to use this tool.";
    exit();
}

// Initialize message variable
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST[\'user_id\'])) {
    $userId = (int)$_POST[\'user_id\'];
    
    // Get user info
    $sql = "SELECT user_id, username, role FROM users WHERE user_id = ? LIMIT 1";
    $user = fetchOne($sql, [$userId]);
    
    if ($user) {
        // Save the original admin user session
        $_SESSION[\'admin_user\'] = $_SESSION[\'user\'];
        $_SESSION[\'is_impersonating\'] = true;
        
        // Update session to the selected user
        $_SESSION[\'user\'] = $user;
        
        $message = "Now logged in as " . htmlspecialchars($user[\'username\']) . " (Role: " . htmlspecialchars($user[\'role\']) . "). 
                    <a href=\'pages_php/member_debug.php\' class=\'btn btn-info\'>Test Access</a>
                    <a href=\'?restore=1\' class=\'btn btn-warning\'>Restore Admin</a>";
    } else {
        $message = "User not found";
    }
}

// Handle restore
if (isset($_GET[\'restore\']) && $_GET[\'restore\'] == 1 && isset($_SESSION[\'admin_user\'])) {
    $_SESSION[\'user\'] = $_SESSION[\'admin_user\'];
    unset($_SESSION[\'admin_user\']);
    unset($_SESSION[\'is_impersonating\']);
    $message = "Admin session restored";
}

// Get all users with member role
$sql = "SELECT user_id, username, role FROM users WHERE role = \'member\' ORDER BY username";
$members = fetchAll($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Member Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-user-shield me-2"></i>Test Member Login</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">Use this tool to login as a member to test access privileges.</p>
                        
                        <?php if ($message): ?>
                        <div class="alert alert-info">
                            <?php echo $message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Select Member</label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="">-- Select a Member --</option>
                                    <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member[\'user_id\']; ?>">
                                        <?php echo htmlspecialchars($member[\'username\']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login as Member
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <h5>Currently Logged in as:</h5>
                            <div class="card">
                                <div class="card-body">
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION[\'user\'][\'username\']); ?></p>
                                    <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION[\'user\'][\'role\']); ?></p>
                                    <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="promote_to_member.php" class="btn btn-outline-secondary">
                                <i class="fas fa-user-shield me-1"></i> Promote Users
                            </a>
                            <a href="pages_php/member_debug.php" class="btn btn-info">
                                <i class="fas fa-bug me-1"></i> Debug Member Access
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

file_put_contents($test_login_file, $test_login_content);
echo "<p style='color: green;'>Created test member login utility at $test_login_file</p>";

echo "<h2>Summary</h2>";
echo "<p>We've made the following changes:</p>";
echo "<ul>";
echo "<li>Checked all admin pages for proper member access variables</li>";
echo "<li>Verified admin_template.php allows member access to specified pages</li>";
echo "<li>Updated header.php for proper menu visibility for members</li>";
echo "<li>Created a test member login utility to easily test member access</li>";
echo "</ul>";

echo "<p>To test member access:</p>";
echo "<ol>";
echo "<li>Promote a user to member role using promote_to_member.php</li>";
echo "<li>Use the new test_member_login.php tool to login as that member</li>";
echo "<li>Visit pages_php/member_debug.php to verify permissions</li>";
echo "<li>Try accessing the admin pages directly to test actual access</li>";
echo "</ol>";

echo "<p><a href='test_member_login.php' class='btn btn-primary'>Go to Test Member Login Tool</a></p>";
?> 