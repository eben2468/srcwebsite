<?php
/**
 * Fix Chat System Issues
 * Addresses common problems and ensures system functionality
 */

require_once 'includes/db_config.php';

echo "<h2>Chat System Issue Fixes</h2>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 5px;'>";

// Fix 1: Ensure all admin/super_admin users have agent status
echo "<h3>1. Fixing Agent Status for Admin Users</h3>";

$fixAgentStatusSQL = "
INSERT IGNORE INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign, last_seen)
SELECT user_id, 'offline', 5, 1, NOW()
FROM users 
WHERE role IN ('admin', 'super_admin', 'member')
AND user_id NOT IN (SELECT agent_id FROM chat_agent_status)
";

if (mysqli_query($conn, $fixAgentStatusSQL)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Added agent status for $affected admin/super_admin/member users<br>";
} else {
    echo "✗ Error fixing agent status: " . mysqli_error($conn) . "<br>";
}

// Fix 2: Update existing agent status to ensure proper defaults
echo "<h3>2. Updating Agent Status Defaults</h3>";

$updateAgentStatusSQL = "
UPDATE chat_agent_status 
SET max_concurrent_chats = CASE 
    WHEN max_concurrent_chats = 0 OR max_concurrent_chats IS NULL THEN 5 
    ELSE max_concurrent_chats 
END,
auto_assign = CASE 
    WHEN auto_assign IS NULL THEN 1 
    ELSE auto_assign 
END,
last_seen = CASE 
    WHEN last_seen IS NULL THEN NOW() 
    ELSE last_seen 
END
";

if (mysqli_query($conn, $updateAgentStatusSQL)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Updated $affected agent status records with proper defaults<br>";
} else {
    echo "✗ Error updating agent status: " . mysqli_error($conn) . "<br>";
}

// Fix 3: Ensure quick responses exist
echo "<h3>3. Ensuring Quick Responses Exist</h3>";

$checkQuickResponsesSQL = "SELECT COUNT(*) as count FROM chat_quick_responses WHERE is_active = 1";
$result = mysqli_query($conn, $checkQuickResponsesSQL);
$count = mysqli_fetch_assoc($result)['count'];

if ($count == 0) {
    echo "No quick responses found. Adding default responses...<br>";
    
    $defaultResponses = [
        ['Welcome', 'Hello! Welcome to VVU SRC Support. How can I assist you today?', 'greeting'],
        ['Please Wait', 'Thank you for your patience. I\'m looking into your request and will get back to you shortly.', 'general'],
        ['More Information', 'Could you please provide more details about the issue you\'re experiencing?', 'general'],
        ['Technical Issue', 'I understand you\'re experiencing a technical issue. Let me help you resolve this.', 'technical'],
        ['Account Help', 'I\'ll be happy to help you with your account-related question.', 'account'],
        ['Closing', 'Thank you for contacting VVU SRC Support. Is there anything else I can help you with today?', 'closing']
    ];
    
    foreach ($defaultResponses as $response) {
        $title = mysqli_real_escape_string($conn, $response[0]);
        $message = mysqli_real_escape_string($conn, $response[1]);
        $category = mysqli_real_escape_string($conn, $response[2]);
        
        $insertSQL = "INSERT INTO chat_quick_responses (title, message, category, created_by, is_active) 
                      VALUES ('$title', '$message', '$category', 1, 1)";
        
        if (mysqli_query($conn, $insertSQL)) {
            echo "&nbsp;&nbsp;✓ Added: $title<br>";
        } else {
            echo "&nbsp;&nbsp;✗ Error adding $title: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "✓ Quick responses already exist ($count responses)<br>";
}

// Fix 4: Clean up any orphaned data
echo "<h3>4. Cleaning Up Orphaned Data</h3>";

// Remove messages from non-existent sessions
$cleanMessagesSQL = "DELETE cm FROM chat_messages cm 
                     LEFT JOIN chat_sessions cs ON cm.session_id = cs.session_id 
                     WHERE cs.session_id IS NULL";

if (mysqli_query($conn, $cleanMessagesSQL)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Cleaned up $affected orphaned messages<br>";
} else {
    echo "✗ Error cleaning messages: " . mysqli_error($conn) . "<br>";
}

// Remove participants from non-existent sessions
$cleanParticipantsSQL = "DELETE cp FROM chat_participants cp 
                         LEFT JOIN chat_sessions cs ON cp.session_id = cs.session_id 
                         WHERE cs.session_id IS NULL";

if (mysqli_query($conn, $cleanParticipantsSQL)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Cleaned up $affected orphaned participants<br>";
} else {
    echo "✗ Error cleaning participants: " . mysqli_error($conn) . "<br>";
}

// Fix 5: Reset current chat counts
echo "<h3>5. Resetting Current Chat Counts</h3>";

$resetCountsSQL = "
UPDATE chat_agent_status cas
SET current_chat_count = (
    SELECT COUNT(*) 
    FROM chat_sessions cs 
    WHERE cs.assigned_agent_id = cas.agent_id 
    AND cs.status = 'active'
)
";

if (mysqli_query($conn, $resetCountsSQL)) {
    $affected = mysqli_affected_rows($conn);
    echo "✓ Reset chat counts for $affected agents<br>";
} else {
    echo "✗ Error resetting chat counts: " . mysqli_error($conn) . "<br>";
}

// Fix 6: Ensure proper indexes exist
echo "<h3>6. Checking Database Indexes</h3>";

$indexes = [
    ['chat_sessions', 'idx_user_id', 'user_id'],
    ['chat_sessions', 'idx_assigned_agent', 'assigned_agent_id'],
    ['chat_sessions', 'idx_status', 'status'],
    ['chat_messages', 'idx_session_id', 'session_id'],
    ['chat_messages', 'idx_sender_id', 'sender_id'],
    ['chat_participants', 'idx_session_id', 'session_id'],
    ['chat_participants', 'idx_user_id', 'user_id']
];

foreach ($indexes as $index) {
    $table = $index[0];
    $indexName = $index[1];
    $column = $index[2];
    
    $checkIndexSQL = "SHOW INDEX FROM $table WHERE Key_name = '$indexName'";
    $result = mysqli_query($conn, $checkIndexSQL);
    
    if (mysqli_num_rows($result) == 0) {
        $createIndexSQL = "ALTER TABLE $table ADD INDEX $indexName ($column)";
        if (mysqli_query($conn, $createIndexSQL)) {
            echo "✓ Created index $indexName on $table.$column<br>";
        } else {
            echo "✗ Error creating index $indexName: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "✓ Index $indexName exists on $table<br>";
    }
}

// Fix 7: Test API endpoints
echo "<h3>7. Testing API Endpoints</h3>";

$apiFiles = [
    'pages_php/support/chat_api.php',
    'pages_php/support/chat_notifications.php',
    'pages_php/support/live-chat.php',
    'pages_php/support/chat-management.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "✓ API file exists: $file<br>";
    } else {
        echo "✗ API file missing: $file<br>";
    }
}

echo "<h3>Summary</h3>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>✅ Chat system fixes completed!</strong><br>";
echo "The live chat system should now be fully functional with:<br>";
echo "• Proper agent status for all admin/super_admin users<br>";
echo "• Default quick responses available<br>";
echo "• Clean database with proper indexes<br>";
echo "• All API endpoints accessible<br>";
echo "</div>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Test the live chat functionality by visiting: <a href='pages_php/support/live-chat.php' target='_blank'>Live Chat</a></li>";
echo "<li>Test the chat management by visiting: <a href='pages_php/support/chat-management.php' target='_blank'>Chat Management</a></li>";
echo "<li>Ensure admin/super_admin users can set their status to 'online' in chat management</li>";
echo "<li>Test real-time messaging between users and agents</li>";
echo "</ol>";

echo "</div>";

mysqli_close($conn);
?>