<?php
/**
 * Test Chat System Functionality
 * Comprehensive test to verify all chat system components are working
 */

require_once 'includes/db_config.php';
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';

echo "<h2>Chat System Functionality Test</h2>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 5px;'>";

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Check if chat tables exist
echo "<h3>1. Database Tables Test</h3>";

$requiredTables = [
    'chat_sessions',
    'chat_messages', 
    'chat_participants',
    'chat_agent_status',
    'chat_quick_responses',
    'chat_files',
    'chat_session_tags'
];

foreach ($requiredTables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Table '$table' exists<br>";
        $passed++;
    } else {
        echo "✗ Table '$table' missing<br>";
        $failed++;
    }
}

// Test 2: Check if chat API files exist
echo "<h3>2. API Files Test</h3>";

$requiredFiles = [
    'pages_php/support/live-chat.php',
    'pages_php/support/chat-management.php',
    'pages_php/support/chat_api.php',
    'pages_php/support/chat_notifications.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✓ File '$file' exists<br>";
        $passed++;
    } else {
        echo "✗ File '$file' missing<br>";
        $failed++;
    }
}

// Test 3: Check quick responses data
echo "<h3>3. Quick Responses Test</h3>";

$quickResponsesResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM chat_quick_responses WHERE is_active = 1");
if ($quickResponsesResult) {
    $count = mysqli_fetch_assoc($quickResponsesResult)['count'];
    if ($count > 0) {
        echo "✓ Quick responses available ($count responses)<br>";
        $passed++;
    } else {
        echo "✗ No quick responses found<br>";
        $failed++;
    }
} else {
    echo "✗ Error checking quick responses: " . mysqli_error($conn) . "<br>";
    $failed++;
}

// Test 4: Check agent status initialization
echo "<h3>4. Agent Status Test</h3>";

$agentStatusResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM chat_agent_status");
if ($agentStatusResult) {
    $count = mysqli_fetch_assoc($agentStatusResult)['count'];
    if ($count > 0) {
        echo "✓ Agent status records exist ($count agents)<br>";
        $passed++;
    } else {
        echo "✗ No agent status records found<br>";
        $failed++;
    }
} else {
    echo "✗ Error checking agent status: " . mysqli_error($conn) . "<br>";
    $failed++;
}

// Test 5: Test admin/super admin access
echo "<h3>5. Admin Access Test</h3>";

// Check if there are admin users
$adminResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'member')");
if ($adminResult) {
    $count = mysqli_fetch_assoc($adminResult)['count'];
    if ($count > 0) {
        echo "✓ Admin/Member users available ($count users)<br>";
        $passed++;
    } else {
        echo "✗ No admin/member users found<br>";
        $failed++;
    }
} else {
    echo "✗ Error checking admin users: " . mysqli_error($conn) . "<br>";
    $failed++;
}

// Test 6: Test database foreign key constraints
echo "<h3>6. Database Constraints Test</h3>";

$constraintsQuery = "
    SELECT 
        TABLE_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE 'chat_%'
    ORDER BY TABLE_NAME
";

$constraintsResult = mysqli_query($conn, $constraintsQuery);
if ($constraintsResult) {
    $constraintCount = mysqli_num_rows($constraintsResult);
    if ($constraintCount > 0) {
        echo "✓ Database foreign key constraints exist ($constraintCount constraints)<br>";
        $passed++;
        
        while ($row = mysqli_fetch_assoc($constraintsResult)) {
            echo "&nbsp;&nbsp;- {$row['TABLE_NAME']}.{$row['CONSTRAINT_NAME']} → {$row['REFERENCED_TABLE_NAME']}<br>";
        }
    } else {
        echo "✗ No foreign key constraints found<br>";
        $failed++;
    }
} else {
    echo "✗ Error checking constraints: " . mysqli_error($conn) . "<br>";
    $failed++;
}

// Test 7: Test API endpoint accessibility (basic check)
echo "<h3>7. API Endpoints Test</h3>";

$apiEndpoints = [
    'pages_php/support/chat_api.php?action=get_quick_responses',
    'pages_php/support/chat_notifications.php?action=get_unread_count'
];

foreach ($apiEndpoints as $endpoint) {
    if (file_exists(str_replace('?action=get_quick_responses', '', str_replace('?action=get_unread_count', '', $endpoint)))) {
        echo "✓ API endpoint file accessible: $endpoint<br>";
        $passed++;
    } else {
        echo "✗ API endpoint file not accessible: $endpoint<br>";
        $failed++;
    }
}

// Test 8: Check table indexes
echo "<h3>8. Database Indexes Test</h3>";

$indexQuery = "
    SELECT 
        TABLE_NAME,
        INDEX_NAME,
        COLUMN_NAME
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME LIKE 'chat_%'
    AND INDEX_NAME != 'PRIMARY'
    ORDER BY TABLE_NAME, INDEX_NAME
";

$indexResult = mysqli_query($conn, $indexQuery);
if ($indexResult) {
    $indexCount = mysqli_num_rows($indexResult);
    if ($indexCount > 0) {
        echo "✓ Database indexes exist ($indexCount indexes)<br>";
        $passed++;
    } else {
        echo "✗ No database indexes found<br>";
        $failed++;
    }
} else {
    echo "✗ Error checking indexes: " . mysqli_error($conn) . "<br>";
    $failed++;
}

// Test 9: Test sample data insertion capability
echo "<h3>9. Data Insertion Test</h3>";

try {
    // Test inserting a sample quick response
    $testTitle = "Test Response " . time();
    $testMessage = "This is a test quick response";
    $testCategory = "test";
    
    $insertSQL = "INSERT INTO chat_quick_responses (title, message, category, created_by, is_active) 
                  VALUES ('$testTitle', '$testMessage', '$testCategory', 1, 0)";
    
    if (mysqli_query($conn, $insertSQL)) {
        $insertId = mysqli_insert_id($conn);
        echo "✓ Data insertion test successful (ID: $insertId)<br>";
        $passed++;
        
        // Clean up test data
        mysqli_query($conn, "DELETE FROM chat_quick_responses WHERE response_id = $insertId");
        echo "&nbsp;&nbsp;- Test data cleaned up<br>";
    } else {
        echo "✗ Data insertion test failed: " . mysqli_error($conn) . "<br>";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ Data insertion test error: " . $e->getMessage() . "<br>";
    $failed++;
}

// Test 10: Check chat system configuration
echo "<h3>10. System Configuration Test</h3>";

// Check if required PHP extensions are available
$requiredExtensions = ['mysqli', 'json', 'session'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ PHP extension '$ext' loaded<br>";
        $passed++;
    } else {
        echo "✗ PHP extension '$ext' not loaded<br>";
        $failed++;
    }
}

// Summary
echo "<h3>Test Summary</h3>";
echo "<div style='background: " . ($failed > 0 ? '#ffebee' : '#e8f5e8') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>Total Tests: " . ($passed + $failed) . "</strong><br>";
echo "<span style='color: green;'>✓ Passed: $passed</span><br>";
echo "<span style='color: red;'>✗ Failed: $failed</span><br>";

if ($failed == 0) {
    echo "<br><strong style='color: green;'>🎉 All tests passed! Chat system is ready to use.</strong>";
} else {
    echo "<br><strong style='color: red;'>⚠️ Some tests failed. Please review the issues above.</strong>";
}
echo "</div>";

// Recommendations
echo "<h3>Recommendations</h3>";
echo "<ul>";
echo "<li>Ensure all admin and member users can access chat management</li>";
echo "<li>Test live chat functionality with real user sessions</li>";
echo "<li>Verify real-time message delivery and notifications</li>";
echo "<li>Test file upload functionality if implemented</li>";
echo "<li>Configure proper backup procedures for chat data</li>";
echo "</ul>";

echo "</div>";

mysqli_close($conn);
?>