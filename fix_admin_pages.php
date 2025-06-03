<?php
// This is a direct fix script that will apply immediate changes to fix member access
// No login required - run this script directly

// Set up error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Direct Member Access Fix</h1>";

// Function to check if a file exists and is writable
function checkFile($path) {
    if (!file_exists($path)) {
        echo "<p style='color: red;'>Error: File not found: $path</p>";
        return false;
    }
    
    if (!is_writable($path)) {
        echo "<p style='color: red;'>Error: File is not writable: $path</p>";
        return false;
    }
    
    return true;
}

// Fix 1: Modify events.php to explicitly check for member role
$eventsPath = 'pages_php/events.php';
if (checkFile($eventsPath)) {
    $content = file_get_contents($eventsPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageEvents = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember; // Allow both admins and members to manage events',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageEvents)',
            $content
        );
        
        file_put_contents($eventsPath, $content);
        echo "<p style='color: green;'>✓ Fixed events.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ events.php already has the correct code</p>";
    }
}

// Fix 2: Modify news.php to explicitly check for member role
$newsPath = 'pages_php/news.php';
if (checkFile($newsPath)) {
    $content = file_get_contents($newsPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageNews = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageNews = $isAdmin || $isMember; // Allow both admins and members to manage news',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageNews)',
            $content
        );
        
        file_put_contents($newsPath, $content);
        echo "<p style='color: green;'>✓ Fixed news.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ news.php already has the correct code</p>";
    }
}

// Fix 3: Modify documents.php to explicitly check for member role
$docsPath = 'pages_php/documents.php';
if (checkFile($docsPath)) {
    $content = file_get_contents($docsPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageDocuments = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageDocuments = $isAdmin || $isMember; // Allow both admins and members to manage documents',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageDocuments)',
            $content
        );
        
        file_put_contents($docsPath, $content);
        echo "<p style='color: green;'>✓ Fixed documents.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ documents.php already has the correct code</p>";
    }
}

// Fix 4: Modify gallery.php to explicitly check for member role
$galleryPath = 'pages_php/gallery.php';
if (checkFile($galleryPath)) {
    $content = file_get_contents($galleryPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageGallery = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageGallery = $isAdmin || $isMember; // Allow both admins and members to manage gallery',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageGallery)',
            $content
        );
        
        file_put_contents($galleryPath, $content);
        echo "<p style='color: green;'>✓ Fixed gallery.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ gallery.php already has the correct code</p>";
    }
}

// Fix 5: Modify elections.php to explicitly check for member role
$electionsPath = 'pages_php/elections.php';
if (checkFile($electionsPath)) {
    $content = file_get_contents($electionsPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageElections = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageElections = $isAdmin || $isMember; // Allow both admins and members to manage elections',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageElections)',
            $content
        );
        
        file_put_contents($electionsPath, $content);
        echo "<p style='color: green;'>✓ Fixed elections.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ elections.php already has the correct code</p>";
    }
}

// Fix 6: Modify minutes.php to explicitly check for member role
$minutesPath = 'pages_php/minutes.php';
if (checkFile($minutesPath)) {
    $content = file_get_contents($minutesPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageMinutes = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageMinutes = $isAdmin || $isMember; // Allow both admins and members to manage minutes',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageMinutes)',
            $content
        );
        
        file_put_contents($minutesPath, $content);
        echo "<p style='color: green;'>✓ Fixed minutes.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ minutes.php already has the correct code</p>";
    }
}

// Fix 7: Modify reports.php to explicitly check for member role
$reportsPath = 'pages_php/reports.php';
if (checkFile($reportsPath)) {
    $content = file_get_contents($reportsPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageReports = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageReports = $isAdmin || $isMember; // Allow both admins and members to manage reports',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageReports)',
            $content
        );
        
        file_put_contents($reportsPath, $content);
        echo "<p style='color: green;'>✓ Fixed reports.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ reports.php already has the correct code</p>";
    }
}

// Fix 8: Modify budget.php to explicitly check for member role
$budgetPath = 'pages_php/budget.php';
if (checkFile($budgetPath)) {
    $content = file_get_contents($budgetPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageBudget = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageBudget = $isAdmin || $isMember; // Allow both admins and members to manage budget',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageBudget)',
            $content
        );
        
        file_put_contents($budgetPath, $content);
        echo "<p style='color: green;'>✓ Fixed budget.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ budget.php already has the correct code</p>";
    }
}

// Fix 9: Modify feedback.php to explicitly check for member role
$feedbackPath = 'pages_php/feedback.php';
if (checkFile($feedbackPath)) {
    $content = file_get_contents($feedbackPath);
    
    // Replace the admin check with admin OR member check
    if (strpos($content, '$canManageFeedback = $isAdmin || $isMember;') === false) {
        // Add the member variable and canManage variable
        $content = preg_replace(
            '/\$isAdmin = isAdmin\(\);/',
            '$isAdmin = isAdmin();
$isMember = isMember();
$canManageFeedback = $isAdmin || $isMember; // Allow both admins and members to manage feedback',
            $content
        );
        
        // Replace any admin-only checks with the canManage variable
        $content = preg_replace(
            '/if \(\!?\$isAdmin\)/',
            'if (!$canManageFeedback)',
            $content
        );
        
        file_put_contents($feedbackPath, $content);
        echo "<p style='color: green;'>✓ Fixed feedback.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ feedback.php already has the correct code</p>";
    }
}

// Fix 10: Update the admin_template.php file to properly handle member access
$adminTemplatePath = 'pages_php/admin_template.php';
if (checkFile($adminTemplatePath)) {
    $content = file_get_contents($adminTemplatePath);
    
    // Make sure the isMember function is being checked
    if (strpos($content, '$isMember = isMember();') === false) {
        // Add the isMember variable
        $content = preg_replace(
            '/\$currentUser = getCurrentUser\(\);/',
            '$currentUser = getCurrentUser();
$isMember = isMember(); // Explicitly check for member role',
            $content
        );
        
        file_put_contents($adminTemplatePath, $content);
        echo "<p style='color: green;'>✓ Fixed admin_template.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ admin_template.php already has the correct code</p>";
    }
}

// Fix 11: Update the header.php file to show admin menu items for members too
$headerPath = 'pages_php/includes/header.php';
if (checkFile($headerPath)) {
    $content = file_get_contents($headerPath);
    
    // Make sure member access is checked for management section
    if (strpos($content, '<?php if ($isAdmin || (isset($isMember) && $isMember)): ?>') === false) {
        // Replace admin-only checks with admin OR member checks
        $content = preg_replace(
            '/\<\?php if \(\$isAdmin\): \?\>/',
            '<?php if ($isAdmin || (isset($isMember) && $isMember)): ?>',
            $content
        );
        
        file_put_contents($headerPath, $content);
        echo "<p style='color: green;'>✓ Fixed header.php</p>";
    } else {
        echo "<p style='color: blue;'>✓ header.php already has the correct code</p>";
    }
}

// Fix 12: Create a session refresh script to ensure session has correct role
$refreshPath = 'session_refresh.php';
$refreshContent = <<<'EOD'
<?php
// Start session
session_start();

// Include necessary files
require_once 'db_config.php';

// Function to refresh user session data
function refreshUserSession() {
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

// Refresh the session
$result = refreshUserSession();

// Output as HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Refresh</title>
    <meta http-equiv='refresh' content='2;url=" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php') . "'>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Session Refresh</h1>
        <p class='success'>" . $result . "</p>
        <p>Redirecting back to previous page...</p>
        <p><a href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php') . "'>Click here if not redirected</a></p>
    </div>
</body>
</html>";
EOD;

file_put_contents($refreshPath, $refreshContent);
echo "<p style='color: green;'>✓ Created session_refresh.php</p>";

// Summary
echo "<h2>Fix Summary</h2>";
echo "<p>The following files have been updated to properly handle member access:</p>";
echo "<ul>";
echo "<li>pages_php/events.php</li>";
echo "<li>pages_php/news.php</li>";
echo "<li>pages_php/documents.php</li>";
echo "<li>pages_php/gallery.php</li>";
echo "<li>pages_php/elections.php</li>";
echo "<li>pages_php/minutes.php</li>";
echo "<li>pages_php/reports.php</li>";
echo "<li>pages_php/budget.php</li>";
echo "<li>pages_php/feedback.php</li>";
echo "<li>pages_php/admin_template.php</li>";
echo "<li>pages_php/includes/header.php</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li><a href='session_refresh.php' style='color: blue;'>Refresh your session</a> to ensure your member role is recognized</li>";
echo "<li>Try accessing the admin pages again</li>";
echo "<li>If you still have issues, log out and log back in</li>";
echo "</ol>";

echo "<p style='margin-top: 30px;'><a href='session_refresh.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Refresh Session Now</a></p>";
?> 