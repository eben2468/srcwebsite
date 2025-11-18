<?php
/**
 * Check Session State
 * 
 * This simple script checks the current session state to verify
 * if the user is actually logged in as super_admin.
 */

// Start session
session_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session State Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Session State Check</h1>
    <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <div class="info">
        <h2>Current Session Data</h2>
        <?php if (isset($_SESSION) && !empty($_SESSION)): ?>
            <table>
                <tr><th>Session Key</th><th>Value</th></tr>
                <?php foreach ($_SESSION as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p><strong>No session data found.</strong> You may not be logged in.</p>
        <?php endif; ?>
    </div>
    
    <div class="info">
        <h2>Login Status Check</h2>
        <?php if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true): ?>
            <div class="success">✅ User is logged in</div>
        <?php else: ?>
            <div class="error">❌ User is not logged in</div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['role'])): ?>
            <p><strong>User Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?></p>
            
            <?php if ($_SESSION['role'] === 'super_admin'): ?>
                <div class="success">✅ User has super_admin role</div>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <div class="success">✅ User has admin role</div>
            <?php else: ?>
                <div class="error">❌ User does not have admin privileges (role: <?php echo htmlspecialchars($_SESSION['role']); ?>)</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="error">❌ No role found in session</div>
        <?php endif; ?>
    </div>
    
    <div class="info">
        <h2>Quick Actions</h2>
        <p>If you're not logged in or don't have the right role:</p>
        <ul>
            <li><a href="login.php">Log in to the system</a></li>
            <li><a href="register.php">Register if you don't have an account</a></li>
            <li>Make sure your account has super_admin or admin role</li>
        </ul>
        
        <p>If you are logged in with the correct role:</p>
        <ul>
            <li><a href="debug_super_admin_live.php">Run Live Debug Test</a></li>
            <li><a href="pages_php/portfolio.php">Go to Portfolio Page</a></li>
        </ul>
    </div>
    
    <div class="info">
        <h2>Browser Cache Clear Instructions</h2>
        <p>If you're logged in correctly but still don't see admin controls:</p>
        <ol>
            <li><strong>Chrome/Edge:</strong> Press Ctrl+Shift+Delete, select "All time", check all boxes, click "Clear data"</li>
            <li><strong>Firefox:</strong> Press Ctrl+Shift+Delete, select "Everything", check all boxes, click "Clear Now"</li>
            <li><strong>Or try incognito/private mode:</strong> Ctrl+Shift+N (Chrome) or Ctrl+Shift+P (Firefox)</li>
        </ol>
    </div>

</body>
</html>