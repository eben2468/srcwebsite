<?php
/**
 * Simple Test API Endpoint
 * Tests if the chat API system is working
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get action parameter
$action = $_GET['action'] ?? 'default';

try {
    // Test database connection
    $conn = new mysqli('localhost', 'root', '', 'vvusrc');
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    if ($action === 'check_tables') {
        // Check if specific tables exist
        $tables = ['chat_sessions', 'chat_messages', 'chat_participants', 'chat_agent_status', 
                   'chat_quick_responses', 'chat_files', 'chat_session_tags'];
        $existingTables = [];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                $existingTables[] = $table;
            }
        }

        $response = [
            'success' => true,
            'tables' => $existingTables,
            'all_exist' => count($existingTables) === count($tables),
            'missing' => array_diff($tables, $existingTables)
        ];

        echo json_encode($response);
        exit;
    }

    // Test if chat tables exist
    $tables = ['chat_sessions', 'chat_messages', 'chat_participants', 'chat_agent_status', 'chat_quick_responses'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $existingTables[] = $table;
        }
    }

    // Get quick responses count
    $quickResponsesCount = 0;
    if (in_array('chat_quick_responses', $existingTables)) {
        $result = $conn->query("SELECT COUNT(*) as count FROM chat_quick_responses WHERE is_active = 1");
        if ($result) {
            $quickResponsesCount = $result->fetch_assoc()['count'];
        }
    }

    // Get agent count
    $agentCount = 0;
    if (in_array('chat_agent_status', $existingTables)) {
        $result = $conn->query("SELECT COUNT(*) as count FROM chat_agent_status");
        if ($result) {
            $agentCount = $result->fetch_assoc()['count'];
        }
    }

    // Test session creation capability
    $canCreateSessions = false;
    if (in_array('chat_sessions', $existingTables)) {
        $testToken = bin2hex(random_bytes(16));
        $testSQL = "INSERT INTO chat_sessions (user_id, session_token, subject, status) 
                    VALUES (1, '$testToken', 'API Test Session', 'waiting')";
        
        if ($conn->query($testSQL) === TRUE) {
            $sessionId = $conn->insert_id;
            $canCreateSessions = true;
            
            // Clean up test session
            $conn->query("DELETE FROM chat_sessions WHERE session_id = $sessionId");
        }
    }

    $response = [
        'success' => true,
        'message' => 'Chat API is working correctly!',
        'timestamp' => date('Y-m-d H:i:s'),
        'system_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_connected' => true,
            'tables_exist' => count($existingTables) . '/' . count($tables),
            'existing_tables' => $existingTables,
            'quick_responses_count' => $quickResponsesCount,
            'agent_count' => $agentCount,
            'can_create_sessions' => $canCreateSessions
        ],
        'status' => [
            'database' => '✅ Connected',
            'tables' => count($existingTables) === count($tables) ? '✅ All tables exist' : '⚠️ Some tables missing',
            'quick_responses' => $quickResponsesCount > 0 ? "✅ $quickResponsesCount responses available" : '⚠️ No quick responses',
            'agents' => $agentCount > 0 ? "✅ $agentCount agents configured" : '⚠️ No agents configured',
            'sessions' => $canCreateSessions ? '✅ Can create sessions' : '⚠️ Cannot create sessions'
        ]
    ];

    $conn->close();

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'system_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_connected' => false
        ]
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>