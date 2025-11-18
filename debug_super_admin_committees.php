<?php
// Debug super admin committees access
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

// Get current user info
$currentUser = getCurrentUser();
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface();

echo "<h2>🔍 Super Admin Committees Debug</h2>";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";

echo "<h3>User Information:</h3>";
echo "<p><strong>Current User:</strong> " . ($currentUser ? json_encode($currentUser) : "Not logged in") . "</p>";
echo "<p><strong>User Role:</strong> " . ($currentUser['role'] ?? 'Unknown') . "</p>";
echo "<p><strong>User ID:</strong> " . ($currentUser['id'] ?? 'Unknown') . "</p>";

echo "<h3>Permission Checks:</h3>";
echo "<p><strong>isSuperAdmin():</strong> " . ($isSuperAdmin ? "✅ TRUE" : "❌ FALSE") . "</p>";
echo "<p><strong>isAdmin():</strong> " . ($isAdmin ? "✅ TRUE" : "❌ FALSE") . "</p>";
echo "<p><strong>shouldUseAdminInterface():</strong> " . ($shouldUseAdminInterface ? "✅ TRUE" : "❌ FALSE") . "</p>";

echo "<h3>Expected Behavior:</h3>";
if ($shouldUseAdminInterface) {
    echo "<p style='color: green;'>✅ Admin interface buttons SHOULD be visible and clickable</p>";
} else {
    echo "<p style='color: red;'>❌ Admin interface buttons should NOT be visible</p>";
}

echo "</div>";

// Test the actual condition used in committees.php
echo "<h3>🧪 Testing Committees Page Condition:</h3>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";

if (shouldUseAdminInterface()) {
    echo "<p style='color: green; font-weight: bold;'>✅ shouldUseAdminInterface() returns TRUE</p>";
    echo "<p>The buttons should be rendered in the committees page.</p>";
    
    // Test if we can access the modals section
    echo "<h4>Modal Access Test:</h4>";
    $isAdminForModals = shouldUseAdminInterface(); // This is the same condition used for modals
    echo "<p><strong>Modal condition (\$isAdmin):</strong> " . ($isAdminForModals ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ shouldUseAdminInterface() returns FALSE</p>";
    echo "<p>The buttons will NOT be rendered in the committees page.</p>";
}

echo "</div>";

// Check session data
echo "<h3>📊 Session Data:</h3>";
echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Test button rendering simulation
echo "<h3>🎭 Button Rendering Simulation:</h3>";
echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 8px; margin: 20px 0;'>";

if (shouldUseAdminInterface()) {
    echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px;'>";
    echo "<h4 style='color: white; margin: 0 0 15px 0;'>SRC Committees</h4>";
    echo "<div style='display: flex; gap: 10px;'>";
    echo "<button style='background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 16px; border-radius: 6px; cursor: pointer;'>";
    echo "<i class='fas fa-plus'></i> Add Committee";
    echo "</button>";
    echo "<button style='background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 16px; border-radius: 6px; cursor: pointer;'>";
    echo "<i class='fas fa-list'></i> Manage";
    echo "</button>";
    echo "</div>";
    echo "</div>";
    echo "<p style='color: green; margin-top: 10px;'>✅ This is what should appear in the committees page header</p>";
} else {
    echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px;'>";
    echo "<h4 style='color: white; margin: 0;'>SRC Committees</h4>";
    echo "</div>";
    echo "<p style='color: orange; margin-top: 10px;'>⚠️ No buttons will appear (not admin/super admin)</p>";
}

echo "</div>";

// JavaScript test
echo "<h3>🔧 JavaScript Functionality Test:</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
?>

<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#testModal" id="testBtn">
    Test Modal Button
</button>

<!-- Test Modal -->
<div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testModalLabel">Test Modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>If this modal opens, Bootstrap JavaScript is working correctly!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 JavaScript Test Running...');
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    
    const testBtn = document.getElementById('testBtn');
    if (testBtn) {
        testBtn.addEventListener('click', function() {
            console.log('✅ Test button clicked - JavaScript is working!');
        });
    }
});
</script>

<?php
echo "<p>Click the test button above. If the modal opens, JavaScript is working correctly.</p>";
echo "</div>";

echo "<div style='background: #ffebee; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>🚨 If buttons still don't work for super admin:</h4>";
echo "<ol>";
echo "<li>Check if there are any JavaScript errors in the browser console</li>";
echo "<li>Verify that Bootstrap CSS and JS are loading correctly</li>";
echo "<li>Check if there are any CSS conflicts preventing clicks</li>";
echo "<li>Ensure the modal HTML is being rendered on the page</li>";
echo "</ol>";
echo "</div>";
?>