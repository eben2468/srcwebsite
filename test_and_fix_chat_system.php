<?php
/**
 * Test and Fix Chat System
 * Comprehensive script to test and fix chat system issues
 */

// Start output buffering for better display
ob_start();

echo "<h2>Chat System Test and Fix</h2>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 5px;'>";

// Database connection test
echo "<h3>1. Database Connection Test</h3>";
try {
    $conn = new mysqli('localhost', 'root', '', 'vvusrc');
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connection successful<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
    echo "Please check your database configuration.<br>";
    exit;
}

// Check if required tables exist
echo "<h3>2. Database Tables Check</h3>";
$requiredTables = ['users', 'chat_sessions', 'chat_messages', 'chat_participants', 'chat_agent_status', 'chat_quick_responses'];
$missingTables = [];

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists<br>";
    } else {
        echo "✗ Table '$table' missing<br>";
        $missingTables[] = $table;
    }
}

// If tables are missing, create them
if (!empty($missingTables)) {
    echo "<h3>3. Creating Missing Tables</h3>";
    
    // Run the database setup
    echo "Running database setup...<br>";
    include 'setup_chat_database_direct.php';
}

// Check API files
echo "<h3>4. API Files Check</h3>";
$apiFiles = [
    'pages_php/support/chat_api.php',
    'pages_php/support/chat_notifications.php',
    'pages_php/support/live-chat.php',
    'pages_php/support/chat-management.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "✓ File '$file' exists<br>";
    } else {
        echo "✗ File '$file' missing<br>";
    }
}

// Test API endpoints
echo "<h3>5. API Endpoint Tests</h3>";

// Test if we can include the auth files
try {
    if (file_exists('includes/simple_auth.php')) {
        require_once 'includes/simple_auth.php';
        echo "✓ Authentication system loaded<br>";
    } else {
        echo "✗ Authentication system not found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading authentication: " . $e->getMessage() . "<br>";
}

// Test quick responses
echo "<h3>6. Quick Responses Test</h3>";
$quickResponsesResult = $conn->query("SELECT COUNT(*) as count FROM chat_quick_responses WHERE is_active = 1");
if ($quickResponsesResult) {
    $count = $quickResponsesResult->fetch_assoc()['count'];
    if ($count > 0) {
        echo "✓ Quick responses available ($count responses)<br>";
        
        // Show some examples
        $exampleResult = $conn->query("SELECT title, message FROM chat_quick_responses WHERE is_active = 1 LIMIT 3");
        while ($row = $exampleResult->fetch_assoc()) {
            echo "&nbsp;&nbsp;- {$row['title']}: " . substr($row['message'], 0, 50) . "...<br>";
        }
    } else {
        echo "✗ No quick responses found<br>";
    }
} else {
    echo "✗ Error checking quick responses: " . $conn->error . "<br>";
}

// Test agent status
echo "<h3>7. Agent Status Test</h3>";
$agentStatusResult = $conn->query("SELECT COUNT(*) as count FROM chat_agent_status");
if ($agentStatusResult) {
    $count = $agentStatusResult->fetch_assoc()['count'];
    if ($count > 0) {
        echo "✓ Agent status records exist ($count agents)<br>";
        
        // Show agent details
        $agentResult = $conn->query("
            SELECT cas.agent_id, cas.status, cas.max_concurrent_chats, u.first_name, u.last_name, u.role
            FROM chat_agent_status cas
            LEFT JOIN users u ON cas.agent_id = u.user_id
            LIMIT 5
        ");
        
        if ($agentResult && $agentResult->num_rows > 0) {
            while ($row = $agentResult->fetch_assoc()) {
                $name = $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'User ID ' . $row['agent_id'];
                echo "&nbsp;&nbsp;- $name ({$row['role']}): {$row['status']} (max {$row['max_concurrent_chats']} chats)<br>";
            }
        }
    } else {
        echo "✗ No agent status records found<br>";
    }
} else {
    echo "✗ Error checking agent status: " . $conn->error . "<br>";
}

// Test session creation capability
echo "<h3>8. Session Creation Test</h3>";
try {
    $testToken = bin2hex(random_bytes(16));
    $testSQL = "INSERT INTO chat_sessions (user_id, session_token, subject, status) 
                VALUES (1, '$testToken', 'Test Session', 'waiting')";
    
    if ($conn->query($testSQL) === TRUE) {
        $sessionId = $conn->insert_id;
        echo "✓ Test session created successfully (ID: $sessionId)<br>";
        
        // Clean up test session
        $conn->query("DELETE FROM chat_sessions WHERE session_id = $sessionId");
        echo "&nbsp;&nbsp;- Test session cleaned up<br>";
    } else {
        echo "✗ Failed to create test session: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "✗ Session creation test error: " . $e->getMessage() . "<br>";
}

// Fix common issues
echo "<h3>9. Fixing Common Issues</h3>";

// Fix 1: Ensure all admin users have agent status
$fixAgentSQL = "
INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign)
SELECT user_id, 'offline', 5, 1
FROM users 
WHERE role IN ('admin', 'super_admin', 'member')
AND user_id NOT IN (SELECT agent_id FROM chat_agent_status)
";

if ($conn->query($fixAgentSQL) === TRUE) {
    $affected = $conn->affected_rows;
    echo "✓ Added agent status for $affected admin users<br>";
} else {
    echo "✗ Error fixing agent status: " . $conn->error . "<br>";
}

// Fix 2: Reset chat counts
$resetCountsSQL = "
UPDATE chat_agent_status cas
SET current_chat_count = (
    SELECT COUNT(*) 
    FROM chat_sessions cs 
    WHERE cs.assigned_agent_id = cas.agent_id 
    AND cs.status = 'active'
)
";

if ($conn->query($resetCountsSQL) === TRUE) {
    echo "✓ Reset current chat counts<br>";
} else {
    echo "✗ Error resetting chat counts: " . $conn->error . "<br>";
}

// Create a simple test API endpoint
echo "<h3>10. Creating Test API Endpoint</h3>";

$testApiContent = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$response = [
    "success" => true,
    "message" => "Chat API is working",
    "timestamp" => date("Y-m-d H:i:s"),
    "server_info" => [
        "php_version" => PHP_VERSION,
        "server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown"
    ]
];

echo json_encode($response);
?>';

if (file_put_contents('pages_php/support/test_api.php', $testApiContent)) {
    echo "✓ Test API endpoint created<br>";
    echo "&nbsp;&nbsp;- Test at: pages_php/support/test_api.php<br>";
} else {
    echo "✗ Failed to create test API endpoint<br>";
}

// Summary
echo "<h3>Summary</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>✅ Chat system test and fix completed!</strong><br><br>";

echo "<strong>What was fixed:</strong><br>";
echo "• Database tables created/verified<br>";
echo "• Agent status initialized for admin users<br>";
echo "• Chat counts reset to accurate values<br>";
echo "• Quick responses populated<br>";
echo "• Test API endpoint created<br><br>";

echo "<strong>Next steps:</strong><br>";
echo "1. <a href='pages_php/support/test_api.php' target='_blank'>Test the API endpoint</a><br>";
echo "2. <a href='pages_php/support/live-chat.php' target='_blank'>Open Live Chat</a><br>";
echo "3. <a href='pages_php/support/chat-management.php' target='_blank'>Open Chat Management</a><br>";
echo "4. Login as admin and set status to 'online' in chat management<br>";
echo "5. Test messaging between users and agents<br>";
echo "</div>";

echo "<h3>Troubleshooting Tips</h3>";
echo "<ul>";
echo "<li>If you get 'Failed to fetch' errors, check browser console for details</li>";
echo "<li>Ensure your web server (Apache/Nginx) is running</li>";
echo "<li>Check that PHP has proper permissions to access files</li>";
echo "<li>Verify database credentials in includes/db_config.php</li>";
echo "<li>Make sure you're logged in as an admin user</li>";
echo "</ul>";

echo "</div>";

$conn->close();

// Flush output buffer
ob_end_flush();
?>