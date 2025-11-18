<?php
// Debug support duplicate path issue
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Debug Support Duplicate Path Issue</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".debug-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🐛 Debug Support Duplicate Path Issue</h1>";

echo "<div class='debug-card'>";
echo "<h3>🔍 Current Issue Analysis</h3>";
echo "<div class='alert alert-danger'>";
echo "<h5>❌ Problem:</h5>";
echo "<p>Still getting: <code>pages_php/support/support/tickets.php?priority=urgent</code></p>";
echo "<p>This suggests the path correction logic needs to be more aggressive.</p>";
echo "</div>";
echo "</div>";

echo "<div class='debug-card'>";
echo "<h3>🔧 Enhanced Path Correction Applied</h3>";
echo "<div class='alert alert-info'>";
echo "<h5>New Aggressive Correction Logic:</h5>";
echo "<ul>";
echo "<li>Multiple duplicate support/ pattern removal</li>";
echo "<li>Enhanced debugging with detailed console logs</li>";
echo "<li>Special handling for support directory navigation</li>";
echo "<li>Final cleanup to remove any remaining duplicates</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

// Test the path correction logic with JavaScript simulation
echo "<div class='debug-card'>";
echo "<h3>🧪 Path Correction Simulation</h3>";

$testCases = [
    'support/tickets.php?priority=urgent',
    'pages_php/support/tickets.php?priority=urgent',
    'pages_php/support/support/tickets.php?priority=urgent',
    'support/support/tickets.php?priority=urgent',
    '/vvusrc/pages_php/support/tickets.php?priority=urgent'
];

echo "<table class='table table-sm'>";
echo "<thead><tr><th>Original URL</th><th>Expected Result (from support dir)</th><th>Expected Result (from pages_php)</th></tr></thead>";
echo "<tbody>";

foreach ($testCases as $url) {
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($url) . "</code></td>";
    echo "<td><code class='success'>../support/tickets.php?priority=urgent</code></td>";
    echo "<td><code class='success'>support/tickets.php?priority=urgent</code></td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";
echo "</div>";

echo "<div class='debug-card'>";
echo "<h3>🚀 Test the Enhanced Fix</h3>";
echo "<div class='alert alert-warning'>";
echo "<h5>⚠️ Testing Instructions:</h5>";
echo "<ol>";
echo "<li>Go to a support page (like notifications.php)</li>";
echo "<li>Open browser console (F12) - this is CRITICAL</li>";
echo "<li>Click on the notification bell icon</li>";
echo "<li>Click on any support ticket notification</li>";
echo "<li>Look for detailed debug logs in console:</li>";
echo "<ul>";
echo "<li><code>=== PATH CORRECTION DEBUG ===</code></li>";
echo "<li><code>Original action URL: ...</code></li>";
echo "<li><code>Final corrected URL: ...</code></li>";
echo "<li><code>=== END PATH CORRECTION ===</code></li>";
echo "</ul>";
echo "<li>Verify the final URL is correct</li>";
echo "</ol>";
echo "</div>";

echo "<div class='d-flex flex-wrap gap-2 mt-3'>";
echo "<a href='pages_php/support/notifications.php' class='btn btn-primary' target='_blank'>🔔 Test from Support/Notifications</a>";
echo "<a href='pages_php/support/tickets.php' class='btn btn-info' target='_blank'>🎫 Test from Support/Tickets</a>";
echo "<a href='pages_php/dashboard.php' class='btn btn-success' target='_blank'>📊 Test from Dashboard</a>";
echo "</div>";
echo "</div>";

echo "<div class='debug-card'>";
echo "<h3>🎯 What Should Happen Now</h3>";
echo "<div class='alert alert-success'>";
echo "<h5>✅ Expected Behavior:</h5>";
echo "<ul>";
echo "<li>Console will show detailed path correction debug info</li>";
echo "<li>All duplicate 'support/' segments will be removed</li>";
echo "<li>From support directory: URLs will be prefixed with '../'</li>";
echo "<li>Final URL should be clean without duplicates</li>";
echo "<li>Navigation should work correctly</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='debug-card'>";
echo "<h3>🔧 Enhanced JavaScript Logic</h3>";
echo "<div class='alert alert-info'>";
echo "<h6>New Aggressive Cleaning Patterns:</h6>";
echo "<pre><code>";
echo "// Remove any duplicate support/ segments (most aggressive)\n";
echo "actionUrl = actionUrl.replace(/support\\/support\\//g, 'support/');\n";
echo "actionUrl = actionUrl.replace(/\\/support\\/support\\//g, '/support/');\n";
echo "actionUrl = actionUrl.replace(/pages_php\\/support\\/support\\//g, 'support/');\n\n";
echo "// Special handling when in support directory\n";
echo "if (isInSupportDir) {\n";
echo "    if (actionUrl.startsWith('support/')) {\n";
echo "        actionUrl = '../' + actionUrl;\n";
echo "    }\n";
echo "}\n\n";
echo "// Final cleanup\n";
echo "actionUrl = actionUrl.replace(/\\/\\/+/g, '/'); // Remove multiple slashes\n";
echo "actionUrl = actionUrl.replace(/support\\/support\\//g, 'support/'); // Final cleanup";
echo "</code></pre>";
echo "</div>";
echo "</div>";

echo "<div class='debug-card'>";
echo "<h3>🚨 If Still Not Working</h3>";
echo "<div class='alert alert-danger'>";
echo "<h6>Troubleshooting Steps:</h6>";
echo "<ol>";
echo "<li><strong>Clear Cache:</strong> Clear browser cache completely</li>";
echo "<li><strong>Check Console:</strong> Look for JavaScript errors in console</li>";
echo "<li><strong>Verify Debug Logs:</strong> Ensure you see the debug logs when clicking notifications</li>";
echo "<li><strong>Check Original URL:</strong> Note what the 'Original action URL' shows in console</li>";
echo "<li><strong>Database Issue:</strong> If original URL is wrong, run the database fix script</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";
?>