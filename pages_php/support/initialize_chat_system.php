<?php
/**
 * Chat System Initialization Script
 * Run this script once to set up the chat system database tables
 */

require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Check if user is super admin
requireLogin();
$currentUser = getCurrentUser();

if ($currentUser['role'] !== 'super_admin') {
    die('Error: Only super admins can initialize the chat system.');
}

// Set page title
$pageTitle = "Initialize Chat System - VVU SRC";
$bodyClass = "page-chat-init";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .init-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 3rem;
        }
        .log-entry {
            padding: 0.5rem 1rem;
            margin: 0.25rem 0;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.875rem;
        }
        .log-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .log-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .log-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .btn-init {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 500;
        }
        .btn-init:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="init-container">
        <div class="text-center mb-4">
            <i class="fas fa-comments display-1 text-primary mb-3"></i>
            <h2>Chat System Initialization</h2>
            <p class="text-muted">This will set up all necessary database tables for the live chat system</p>
        </div>

        <div id="logContainer" class="mb-4" style="max-height: 400px; overflow-y: auto;">
            <!-- Log entries will appear here -->
        </div>

        <div class="text-center">
            <button id="initBtn" class="btn btn-init btn-lg" onclick="initializeChatSystem()">
                <i class="fas fa-rocket me-2"></i>Initialize Chat System
            </button>
        </div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const logEntry = document.createElement('div');
            logEntry.className = `log-entry log-${type}`;
            
            const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle';
            logEntry.innerHTML = `<i class="fas fa-${icon} me-2"></i>${message}`;
            
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        async function initializeChatSystem() {
            const btn = document.getElementById('initBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Initializing...';

            document.getElementById('logContainer').innerHTML = '';
            addLog('Starting chat system initialization...', 'info');

            try {
                const response = await fetch('setup_chat_database.php');
                const text = await response.text();
                
                // Parse the response
                if (text.includes('✓')) {
                    const lines = text.split('\n').filter(line => line.trim());
                    lines.forEach(line => {
                        if (line.includes('✓')) {
                            addLog(line.replace(/<[^>]*>/g, '').replace('✓', ''), 'success');
                        } else if (line.includes('✗')) {
                            addLog(line.replace(/<[^>]*>/g, '').replace('✗', ''), 'error');
                        } else if (line.includes('Creating') || line.includes('Inserting') || line.includes('Initializing')) {
                            addLog(line.replace(/<[^>]*>/g, ''), 'info');
                        }
                    });
                    
                    if (text.includes('completed')) {
                        addLog('Chat system initialization completed successfully!', 'success');
                        btn.innerHTML = '<i class="fas fa-check me-2"></i>Initialization Complete';
                        btn.className = 'btn btn-success btn-lg';
                        
                        setTimeout(() => {
                            window.location.href = 'chat-management.php';
                        }, 2000);
                    }
                } else {
                    addLog('Initialization completed with some warnings. Please check the logs.', 'info');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-rocket me-2"></i>Initialize Chat System';
                }
            } catch (error) {
                addLog('Error during initialization: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-rocket me-2"></i>Try Again';
            }
        }
    </script>
</body>
</html>
