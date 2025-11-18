<?php
/**
 * Final Portfolio Debug
 * 
 * This test simulates the EXACT portfolio.php behavior to identify
 * why super admin users are not seeing CRUD functionality.
 */

// Start session
session_start();

// Include files in EXACT same order as portfolio.php
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/settings_functions.php';
// Require login for this page
requireLogin();
require_once __DIR__ . '/includes/functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Portfolio Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: white; padding: 20px; margin: 10px 0; border: 1px solid #ddd; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .admin-controls { background: #e7f3ff; padding: 10px; margin: 10px 0; border: 1px solid #2196F3; }
        .portfolio-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; position: relative; }
    </style>
</head>
<body>
    <h1>Final Portfolio Debug</h1>
    
    <div class="debug-section">
        <h2>1. Session Check</h2>
        <?php if (isLoggedIn()): ?>
            <p class="success">✅ User is logged in</p>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
            <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'Not set'; ?></p>
            <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Not set'; ?></p>
        <?php else: ?>
            <p class="error">❌ User is not logged in</p>
            <p>Please log in to test portfolio functionality.</p>
            <?php exit(); ?>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>2. Feature Permission Check</h2>
        <?php if (hasFeaturePermission('enable_portfolios')): ?>
            <p class="success">✅ Portfolio feature is enabled</p>
        <?php else: ?>
            <p class="error">❌ Portfolio feature is disabled</p>
            <p>This would cause a redirect to dashboard.php</p>
            <?php exit(); ?>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>3. Admin Interface Check (EXACT portfolio.php logic)</h2>
        <?php
        // EXACT same logic as portfolio.php
        $currentUser = getCurrentUser();
        $isAdmin = shouldUseAdminInterface();
        ?>
        
        <p><strong>Current User:</strong></p>
        <pre><?php print_r($currentUser); ?></pre>
        
        <p><strong>$isAdmin variable:</strong> <?php echo $isAdmin ? 'true' : 'false'; ?></p>
        
        <?php if ($isAdmin): ?>
            <p class="success">✅ User has admin interface access</p>
        <?php else: ?>
            <p class="error">❌ User does not have admin interface access</p>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>4. UI Elements Test (EXACT portfolio.php HTML)</h2>
        
        <h3>Create Portfolio Button (Header)</h3>
        <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd;">
            <h4>Portfolios</h4>
            <p>Meet the SRC leadership team and their responsibilities</p>
            
            <?php if ($isAdmin): ?>
            <div class="admin-controls success">
                ✅ CREATE PORTFOLIO BUTTON IS VISIBLE
                <br><br>
                <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#createPortfolioModal">
                    <i class="fas fa-plus me-2"></i>Create Portfolio
                </button>
            </div>
            <?php else: ?>
            <div class="admin-controls error">
                ❌ Create Portfolio button is hidden
            </div>
            <?php endif; ?>
        </div>

        <h3>Portfolio Cards with Admin Controls</h3>
        <div class="portfolio-card">
            <h4>Sample Portfolio: President</h4>
            <p>John Doe - President of SRC</p>
            
            <?php if ($isAdmin): ?>
            <div class="admin-controls success">
                ✅ ADMIN CONTROLS ARE VISIBLE
                <br><br>
                <a href="portfolio_edit.php?id=1" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="portfolio.php?action=delete&id=1" class="btn btn-sm btn-outline-danger" 
                   onclick="return confirm('Are you sure you want to delete this portfolio?');">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
            <?php else: ?>
            <div class="admin-controls error">
                ❌ Admin controls are hidden
            </div>
            <?php endif; ?>
        </div>

        <h3>Create Portfolio Modal</h3>
        <?php if ($isAdmin): ?>
        <div class="admin-controls success">
            ✅ CREATE PORTFOLIO MODAL IS AVAILABLE
            <div style="border: 1px solid #ccc; padding: 20px; background: #f9f9f9; margin-top: 10px;">
                <h5>Create New Portfolio</h5>
                <form method="POST" action="portfolio_handler.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div style="margin-bottom: 10px;">
                        <label>Portfolio Title:</label><br>
                        <input type="text" name="title" required style="width: 200px; padding: 5px;">
                    </div>
                    <div style="margin-bottom: 10px;">
                        <label>Holder's Name:</label><br>
                        <input type="text" name="name" required style="width: 200px; padding: 5px;">
                    </div>
                    <button type="submit" style="background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px;">
                        Create Portfolio
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="admin-controls error">
            ❌ Create Portfolio modal is not available
        </div>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>5. Delete Functionality Test</h2>
        <?php
        // Test delete functionality (same logic as portfolio.php)
        if ($isAdmin && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            echo "<p class='success'>✅ DELETE FUNCTIONALITY: Would execute delete logic</p>";
        } else {
            if ($isAdmin) {
                echo "<p class='success'>✅ DELETE FUNCTIONALITY: Available (admin check passed)</p>";
                echo "<p>Note: To test actual delete, add ?action=delete&id=1 to URL</p>";
            } else {
                echo "<p class='error'>❌ DELETE FUNCTIONALITY: Not available (not admin)</p>";
            }
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>6. Function Comparison</h2>
        <table border="1" style="border-collapse: collapse; width: 100%;">
            <tr>
                <th style="padding: 10px;">Function</th>
                <th style="padding: 10px;">Result</th>
                <th style="padding: 10px;">Expected for Super Admin</th>
            </tr>
            <tr>
                <td style="padding: 10px;">isLoggedIn()</td>
                <td style="padding: 10px;"><?php echo isLoggedIn() ? 'true' : 'false'; ?></td>
                <td style="padding: 10px;">true</td>
            </tr>
            <tr>
                <td style="padding: 10px;">isSuperAdmin()</td>
                <td style="padding: 10px;"><?php echo isSuperAdmin() ? 'true' : 'false'; ?></td>
                <td style="padding: 10px;">true</td>
            </tr>
            <tr>
                <td style="padding: 10px;">isAdmin()</td>
                <td style="padding: 10px;"><?php echo isAdmin() ? 'true' : 'false'; ?></td>
                <td style="padding: 10px;">false (correct)</td>
            </tr>
            <tr>
                <td style="padding: 10px;">shouldUseAdminInterface()</td>
                <td style="padding: 10px;"><?php echo shouldUseAdminInterface() ? 'true' : 'false'; ?></td>
                <td style="padding: 10px;">true</td>
            </tr>
            <tr>
                <td style="padding: 10px;">hasFeaturePermission('enable_portfolios')</td>
                <td style="padding: 10px;"><?php echo hasFeaturePermission('enable_portfolios') ? 'true' : 'false'; ?></td>
                <td style="padding: 10px;">true</td>
            </tr>
        </table>
    </div>

    <div class="debug-section">
        <h2>7. Final Verdict</h2>
        <?php
        $allGood = isLoggedIn() && 
                   hasFeaturePermission('enable_portfolios') && 
                   shouldUseAdminInterface();
        
        if ($allGood): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px;">
                <h3>🎉 ALL CHECKS PASS!</h3>
                <p>Super admin users SHOULD see all admin controls on the portfolio page.</p>
                <p><strong>If you still don't see them on the actual page, the issue is likely:</strong></p>
                <ul>
                    <li>Browser cache - Clear cache and hard refresh (Ctrl+F5)</li>
                    <li>JavaScript errors - Check browser console (F12)</li>
                    <li>CSS hiding elements - Inspect elements in dev tools</li>
                    <li>Session differences between this test and actual page</li>
                </ul>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px;">
                <h3>❌ SOME CHECKS FAILED!</h3>
                <p>There are still issues with the implementation.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="debug-section">
        <h2>8. Next Steps</h2>
        <ol>
            <li><strong>Compare this page with actual portfolio page</strong> - If this shows admin controls but the actual page doesn't, it's a caching/JavaScript issue</li>
            <li><strong>Check browser console</strong> - Look for JavaScript errors on the actual portfolio page</li>
            <li><strong>Inspect elements</strong> - Use browser dev tools to see if admin controls exist but are hidden</li>
            <li><strong>Clear all cache</strong> - Clear browser cache, cookies, and local storage</li>
            <li><strong>Test in incognito mode</strong> - Try accessing the portfolio page in a private/incognito window</li>
        </ol>
    </div>

</body>
</html>