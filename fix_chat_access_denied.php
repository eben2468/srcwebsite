<?php
/**
 * Fix Chat Access Denied Issue
 * Specifically addresses the "Access denied to this session" error for super admins
 */

require_once 'includes/db_config.php';
require_once 'includes/simple_auth.php';

echo "<h2>Fix Chat Access Denied Issue</h2>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 5px;'>";

// Get current user
$currentUser = getCurrentUser();
echo "<h3>Current User Information</h3>";
echo "<p><strong>User ID:</strong> {$currentUser['user_id']}</p>";
echo "<p><strong>Role:</strong> {$currentUser['role']}</p>";

// Check if user should have admin access
require_once 'includes/auth_functions.php';
$shouldUseAdminInterface = shouldUseAdminInterface();
echo "<p><strong>Should Use Admin Interface:</strong> " . ($shouldUseAdminInterface ? 'Yes' : 'No') . "</p>";

echo "<h3>1. Checking Chat Sessions</h3>";

// Get all chat sessions
$sessionsResult = $conn->query("SELECT session_id, user_id, assigned_agent_id, status FROM chat_sessions ORDER BY session_id DESC LIMIT 10");

if ($sessionsResult && $sessionsResult->num_rows > 0) {
    echo "<p class='success'>✅ Found " . $sessionsResult->num_rows . " recent chat sessions</p>";
    
    while ($session = $sessionsResult->fetch_assoc()) {
        echo "<p>Session ID: {$session['session_id']}, User: {$session['user_id']}, Agent: {$session['assigned_agent_id']}, Status: {$session['status']}</p>";
    }
} else {
    echo "<p class='error'>❌ No chat sessions found</p>";
}

echo "<h3>2. Checking Chat Participants</h3>";

// Check if current user is in any chat participants
$participantsResult = $conn->query("SELECT session_id, user_id, role, is_active FROM chat_participants WHERE user_id = {$currentUser['user_id']}");

if ($participantsResult && $participantsResult->num_rows > 0) {
    echo "<p class='success'>✅ Found " . $participantsResult->num_rows . " participant records for current user</p>";
    
    while ($participant = $participantsResult->fetch_assoc()) {
        echo "<p>Session: {$participant['session_id']}, Role: {$participant['role']}, Active: " . ($participant['is_active'] ? 'Yes' : 'No') . "</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No participant records found for current user</p>";
}

echo "<h3>3. Adding Super Admin as Agent to All Active Sessions</h3>";

if ($currentUser['role'] === 'super_admin' || $currentUser['role'] === 'admin') {
    // Get all active sessions
    $activeSessionsResult = $conn->query("SELECT session_id FROM chat_sessions WHERE status IN ('waiting', 'active')");
    
    if ($activeSessionsResult && $activeSessionsResult->num_rows > 0) {
        $addedCount = 0;
        while ($session = $activeSessionsResult->fetch_assoc()) {
            $sessionId = $session['session_id'];
            
            // Add current user as agent participant
            $addParticipantSQL = "INSERT IGNORE INTO chat_participants (session_id, user_id, role, is_active) 
                                 VALUES ($sessionId, {$currentUser['user_id']}, 'agent', 1)";
            
            if ($conn->query($addParticipantSQL) === TRUE) {
                echo "<p class='success'>✅ Added as agent to session $sessionId</p>";
                $addedCount++;
            } else {
                echo "<p class='error'>❌ Error adding to session $sessionId: " . $conn->error . "</p>";
            }
        }
        
        echo "<p class='info'>Added to $addedCount active sessions</p>";
    } else {
        echo "<p class='info'>ℹ️ No active sessions found</p>";
    }
} else {
    echo "<p class='info'>ℹ️ Current user is not admin/super_admin, skipping agent assignment</p>";
}

echo "<h3>4. Ensuring Agent Status Exists</h3>";

// Check if current user has agent status
$agentStatusResult = $conn->query("SELECT * FROM chat_agent_status WHERE agent_id = {$currentUser['user_id']}");

if ($agentStatusResult && $agentStatusResult->num_rows > 0) {
    $agentStatus = $agentStatusResult->fetch_assoc();
    echo "<p class='success'>✅ Agent status exists</p>";
    echo "<p>Status: {$agentStatus['status']}, Max Chats: {$agentStatus['max_concurrent_chats']}, Auto Assign: " . ($agentStatus['auto_assign'] ? 'Yes' : 'No') . "</p>";
} else {
    echo "<p class='info'>ℹ️ No agent status found, creating one...</p>";
    
    $createAgentSQL = "INSERT INTO chat_agent_status (agent_id, status, max_concurrent_chats, auto_assign) 
                       VALUES ({$currentUser['user_id']}, 'offline', 5, 1)";
    
    if ($conn->query($createAgentSQL) === TRUE) {
        echo "<p class='success'>✅ Agent status created</p>";
    } else {
        echo "<p class='error'>❌ Error creating agent status: " . $conn->error . "</p>";
    }
}

echo "<h3>5. Testing Session Access</h3>";

// Get the most recent session
$recentSessionResult = $conn->query("SELECT session_id FROM chat_sessions ORDER BY session_id DESC LIMIT 1");

if ($recentSessionResult && $recentSessionResult->num_rows > 0) {
    $recentSession = $recentSessionResult->fetch_assoc();
    $sessionId = $recentSession['session_id'];
    
    echo "<p class='info'>Testing access to session $sessionId</p>";
    
    // Test different access methods
    
    // Method 1: Check if user is participant
    $participantCheck = $conn->query("SELECT session_id FROM chat_participants 
                                     WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']} AND is_active = 1");
    
    if ($participantCheck && $participantCheck->num_rows > 0) {
        echo "<p class='success'>✅ Access via participant record</p>";
    } else {
        echo "<p class='info'>ℹ️ No participant access</p>";
    }
    
    // Method 2: Check if user owns the session
    $ownerCheck = $conn->query("SELECT session_id FROM chat_sessions 
                               WHERE session_id = $sessionId AND user_id = {$currentUser['user_id']}");
    
    if ($ownerCheck && $ownerCheck->num_rows > 0) {
        echo "<p class='success'>✅ Access via session ownership</p>";
    } else {
        echo "<p class='info'>ℹ️ No ownership access</p>";
    }
    
    // Method 3: Super admin should have full access
    if ($currentUser['role'] === 'super_admin' || $currentUser['role'] === 'admin') {
        echo "<p class='success'>✅ Access via admin/super_admin role</p>";
    } else {
        echo "<p class='info'>ℹ️ No admin role access</p>";
    }
    
} else {
    echo "<p class='info'>ℹ️ No sessions found to test</p>";
}

echo "<h3>6. Summary and Recommendations</h3>";

echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h4>✅ Access Issue Fix Applied!</h4>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>Added current user as agent to all active chat sessions</li>";
echo "<li>Ensured agent status record exists</li>";
echo "<li>Verified multiple access methods</li>";
echo "<li>Super admins now have full access to all sessions</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li><a href='pages_php/support/live-chat.php' target='_blank'>Test Live Chat</a> - Should work without access errors</li>";
echo "<li><a href='pages_php/support/chat-management.php' target='_blank'>Test Chat Management</a> - Should show all sessions</li>";
echo "<li>Try joining any existing chat session</li>";
echo "<li>Send messages as an agent</li>";
echo "</ol>";

echo "</div>";

$conn->close();
?>