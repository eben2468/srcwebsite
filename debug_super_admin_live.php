<?php
/**
 * Debug Super Admin Live
 * 
 * This script will help debug why super admin users are not seeing
 * CRUD functionality in real-time by checking the actual session state.
 */

// Start session
session_start();

// Include all required files in the same order as portfolio.php
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/settings_functions.php';
require_once __DIR__ . '/includes/functions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Super Admin Live</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .debug-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; }
        .admin-controls { background: #e7f3ff; padding: 15px; margin: 10px 0; border: 1px solid #2196F3; border-radius: 4px; }
        .portfolio-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; position: relative; border-radius: 4px; }
    </style>
    <script>
        function refreshPage() {
            location.reload();
        }
        
        function checkElements() {
            console.log('=== ELEMENT CHECK ===');
            console.log('Create button:', document.querySelector('[data-bs-target="#createPortfolioModal"]'));
            console.log('Admin controls:', document.querySelectorAll('.admin-controls'));
            console.log('Edit buttons:', document.querySelectorAll('a[href*="portfolio_edit.php"]'));
            console.log('Delete buttons:', document.querySelectorAll('a[href*="action=delete"]'));
            console.log('Modal:', document.querySelector('#createPortfolioModal'));
        }
        
        window.onload = function() {
            setTimeout(checkElements, 1000);
        };
    </script>
</head>
<body>
    <h1>🔍 Super Admin Live Debug</h1>
    <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <div class="debug-box">
        <h2>1. Current Session State</h2>
        <?php if (isset($_SESSION) && !empty($_SESSION)): ?>
            <div class="success">✅ Session is active</div>
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
            <div class="error">❌ No active session - Please log in first</div>
            <p><a href="login.php">Click here to log in</a></p>
            <?php exit(); ?>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h2>2. Authentication Function Results</h2>
        <table>
            <tr><th>Function</th><th>Result</th><th>Expected for Super Admin</th><th>Status</th></tr>
            <?php
            $tests = [
                'isLoggedIn()' => [isLoggedIn(), true],
                'isSuperAdmin()' => [isSuperAdmin(), true],
                'isAdmin()' => [isAdmin(), false],
                'shouldUseAdminInterface()' => [shouldUseAdminInterface(), true],
                'hasFeaturePermission("enable_portfolios")' => [hasFeaturePermission('enable_portfolios'), true]
            ];
            
            foreach ($tests as $func => $data) {
                $result = $data[0];
                $expected = $data[1];
                $status = ($result === $expected) ? '✅ PASS' : '❌ FAIL';
                $resultText = $result ? 'true' : 'false';
                $expectedText = $expected ? 'true' : 'false';
                
                echo "<tr>";
                echo "<td>$func</td>";
                echo "<td>$resultText</td>";
                echo "<td>$expectedText</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>

    <div class="debug-box">
        <h2>3. Portfolio Page Logic Simulation</h2>
        <?php
        // Exact same logic as portfolio.php
        if (!isLoggedIn()) {
            echo "<div class='error'>❌ Would redirect to register.php</div>";
            exit();
        }
        
        if (!hasFeaturePermission('enable_portfolios')) {
            echo "<div class='error'>❌ Would redirect to dashboard.php (portfolios disabled)</div>";
            exit();
        }
        
        $currentUser = getCurrentUser();
        $isAdmin = shouldUseAdminInterface();
        ?>
        
        <div class="success">✅ All checks passed - proceeding to admin interface test</div>
        
        <h3>Current User Info:</h3>
        <pre><?php print_r($currentUser); ?></pre>
        
        <h3>Admin Interface Variable:</h3>
        <p><strong>$isAdmin = shouldUseAdminInterface():</strong> <?php echo $isAdmin ? 'true' : 'false'; ?></p>
        
        <?php if ($isAdmin): ?>
            <div class="success">✅ Super admin should see admin interface</div>
        <?php else: ?>
            <div class="error">❌ Super admin will NOT see admin interface</div>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h2>4. Live UI Elements Test</h2>
        
        <h3>Create Portfolio Button (Header Section)</h3>
        <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
            <h4>Portfolios</h4>
            <p>Meet the SRC leadership team and their responsibilities</p>
            
            <?php if ($isAdmin): ?>
            <div class="admin-controls success">
                ✅ CREATE PORTFOLIO BUTTON IS VISIBLE
                <br><br>
                <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#createPortfolioModal" style="background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-plus"></i> Create Portfolio
                </button>
            </div>
            <?php else: ?>
            <div class="admin-controls error">
                ❌ Create Portfolio button is hidden
            </div>
            <?php endif; ?>
        </div>

        <h3>Portfolio Card with Admin Controls</h3>
        <div class="portfolio-card">
            <h4>Sample Portfolio: President</h4>
            <p>John Doe - President of SRC</p>
            
            <?php if ($isAdmin): ?>
            <div class="admin-controls success">
                ✅ ADMIN CONTROLS ARE VISIBLE
                <br><br>
                <a href="portfolio_edit.php?id=1" style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="portfolio.php?action=delete&id=1" style="background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;" 
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
            <div id="createPortfolioModal" style="border: 1px solid #ccc; padding: 20px; background: #f9f9f9; margin-top: 10px; border-radius: 4px;">
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

    <div class="debug-box">
        <h2>5. Function Source Check</h2>
        <?php
        $reflection1 = new ReflectionFunction('isAdmin');
        $reflection2 = new ReflectionFunction('shouldUseAdminInterface');
        ?>
        <table>
            <tr><th>Function</th><th>File</th><th>Line</th></tr>
            <tr>
                <td>isAdmin()</td>
                <td><?php echo $reflection1->getFileName(); ?></td>
                <td><?php echo $reflection1->getStartLine(); ?></td>
            </tr>
            <tr>
                <td>shouldUseAdminInterface()</td>
                <td><?php echo $reflection2->getFileName(); ?></td>
                <td><?php echo $reflection2->getStartLine(); ?></td>
            </tr>
        </table>
    </div>

    <div class="debug-box">
        <h2>6. Browser Environment Check</h2>
        <p>Open browser developer tools (F12) and check the console for any JavaScript errors.</p>
        <button onclick="checkElements()" style="background: #17a2b8; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
            Check DOM Elements
        </button>
        <button onclick="refreshPage()" style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
            Refresh Page
        </button>
        
        <h3>Manual Checks:</h3>
        <ol>
            <li>Press F12 to open developer tools</li>
            <li>Go to Console tab</li>
            <li>Click "Check DOM Elements" button above</li>
            <li>Look for any JavaScript errors in red</li>
            <li>Go to Network tab and refresh page to check for failed requests</li>
        </ol>
    </div>

    <div class="debug-box">
        <h2>7. Final Diagnosis</h2>
        <?php
        $allGood = isLoggedIn() && 
                   hasFeaturePermission('enable_portfolios') && 
                   shouldUseAdminInterface();
        
        if ($allGood): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px;">
                <h3>🎉 ALL BACKEND CHECKS PASS!</h3>
                <p>The PHP code is working correctly. Super admin should see admin controls.</p>
                <p><strong>If you still don't see them on the actual portfolio page:</strong></p>
                <ul>
                    <li><strong>Compare this page with actual portfolio page</strong> - If this shows controls but actual page doesn't, it's a frontend issue</li>
                    <li><strong>Clear browser cache completely</strong> - Ctrl+Shift+Delete, clear everything</li>
                    <li><strong>Try incognito/private mode</strong> - Test in private browsing</li>
                    <li><strong>Check JavaScript console</strong> - Look for errors that might hide elements</li>
                    <li><strong>Inspect elements</strong> - Use browser dev tools to see if elements exist but are hidden by CSS</li>
                </ul>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 4px;">
                <h3>❌ BACKEND ISSUES DETECTED!</h3>
                <p>There are still problems with the PHP implementation.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h2>8. Next Steps</h2>
        <ol>
            <li><strong>If this page shows admin controls:</strong> The backend is working, issue is frontend/caching</li>
            <li><strong>If this page doesn't show admin controls:</strong> There's still a backend issue</li>
            <li><strong>Compare with actual portfolio page:</strong> <a href="pages_php/portfolio.php" target="_blank">Open Portfolio Page</a></li>
            <li><strong>Test with different user roles:</strong> Log in as regular admin and compare</li>
        </ol>
    </div>

</body>
</html>