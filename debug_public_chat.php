<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
require_once __DIR__ . '/../includes/auth_functions.php';
$shouldUseAdminInterface = shouldUseAdminInterface();

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Debug Public Chat - " . $siteName;
$bodyClass = "page-public-chat";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Public Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .debug-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .chat-input-area {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .chat-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .chat-send-btn {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .chat-send-btn:hover {
            background: #0056b3;
        }
        .debug-log {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        .log-entry {
            margin-bottom: 5px;
            padding: 5px;
            border-radius: 3px;
        }
        .log-info {
            background: #d1ecf1;
            border-left: 3px solid #0c5460;
        }
        .log-error {
            background: #f8d7da;
            border-left: 3px solid #721c24;
        }
        .log-success {
            background: #d4edda;
            border-left: 3px solid #155724;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>Debug Public Chat</h1>
        <p>This page helps diagnose issues with the public chat send button.</p>
        
        <div class="card">
            <div class="card-header">
                <h5>Test Chat Form</h5>
            </div>
            <div class="card-body">
                <form id="debugMessageForm">
                    <div class="mb-3">
                        <label for="debugMessageInput" class="form-label">Message:</label>
                        <input type="text" class="form-control" id="debugMessageInput" placeholder="Type your message...">
                    </div>
                    <button type="submit" class="btn btn-primary" id="debugSendBtn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                    <button type="button" class="btn btn-secondary" id="debugClickBtn">
                        <i class="fas fa-mouse-pointer"></i> Test Click
                    </button>
                </form>
            </div>
        </div>
        
        <div class="debug-log" id="debugLog">
            <h5>Debug Log:</h5>
            <div id="logEntries"></div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Test Results</h5>
            </div>
            <div class="card-body">
                <div id="testResults"></div>
                <button class="btn btn-success mt-2" id="runFullTest">Run Full Test</button>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="public_chat.php" class="btn btn-outline-primary">Go to Public Chat</a>
            <a href="../dashboard.php" class="btn btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    <script>
        // Debug logging function
        function log(message, type = 'info') {
            const logEntries = document.getElementById('logEntries');
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.innerHTML = `<strong>[${new Date().toLocaleTimeString()}]</strong> ${message}`;
            logEntries.appendChild(entry);
            logEntries.scrollTop = logEntries.scrollHeight;
            
            // Also log to console
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        // Test functions
        function testFormSubmission() {
            log('Testing form submission event...', 'info');
            
            const form = document.getElementById('debugMessageForm');
            if (!form) {
                log('ERROR: Form element not found!', 'error');
                return false;
            }
            
            // Check if event listener is already attached
            if (form.dataset.listenerAttached === 'true') {
                log('Form submission listener already attached', 'info');
                return true;
            }
            
            form.addEventListener('submit', function(e) {
                log('Form submit event triggered', 'info');
                e.preventDefault();
                e.stopPropagation();
                
                const messageInput = document.getElementById('debugMessageInput');
                if (!messageInput) {
                    log('ERROR: Message input not found!', 'error');
                    return false;
                }
                
                const message = messageInput.value.trim();
                log(`Message value: "${message}"`, 'info');
                
                if (!message) {
                    log('ERROR: Message is empty!', 'error');
                    messageInput.focus();
                    return false;
                }
                
                log(`SUCCESS: Form submitted with message: ${message}`, 'success');
                messageInput.value = '';
                messageInput.focus();
                
                return false;
            });
            
            form.dataset.listenerAttached = 'true';
            log('Form submission event listener attached successfully', 'success');
            return true;
        }
        
        function testButtonClick() {
            log('Testing button click event...', 'info');
            
            const button = document.getElementById('debugSendBtn');
            if (!button) {
                log('ERROR: Send button not found!', 'error');
                return false;
            }
            
            // Check if event listener is already attached
            if (button.dataset.listenerAttached === 'true') {
                log('Button click listener already attached', 'info');
                return true;
            }
            
            button.addEventListener('click', function(e) {
                log('Button click event triggered', 'info');
                e.preventDefault();
                e.stopPropagation();
                
                // Manually trigger form submission
                const form = document.getElementById('debugMessageForm');
                if (form) {
                    log('Manually triggering form submission', 'info');
                    const submitEvent = new Event('submit', {
                        bubbles: true,
                        cancelable: true
                    });
                    form.dispatchEvent(submitEvent);
                }
                
                return false;
            });
            
            button.dataset.listenerAttached = 'true';
            log('Button click event listener attached successfully', 'success');
            return true;
        }
        
        function testDirectClick() {
            log('Testing direct click button...', 'info');
            
            const button = document.getElementById('debugClickBtn');
            if (!button) {
                log('ERROR: Direct click button not found!', 'error');
                return false;
            }
            
            button.addEventListener('click', function(e) {
                log('Direct click button pressed', 'info');
                e.preventDefault();
                
                // Test form submission directly
                const messageInput = document.getElementById('debugMessageInput');
                if (!messageInput) {
                    log('ERROR: Message input not found!', 'error');
                    return;
                }
                
                const message = messageInput.value.trim();
                log(`Direct click test - Message: "${message}"`, 'info');
                
                if (!message) {
                    log('Direct click test - ERROR: Message is empty!', 'error');
                    return;
                }
                
                log(`Direct click test - SUCCESS: Message would be sent: ${message}`, 'success');
            });
            
            log('Direct click button event listener attached', 'success');
            return true;
        }
        
        function runFullTest() {
            log('=== STARTING FULL DEBUG TEST ===', 'info');
            
            const results = {
                formSubmission: testFormSubmission(),
                buttonClick: testButtonClick(),
                directClick: testDirectClick()
            };
            
            log('=== DEBUG TEST COMPLETE ===', 'info');
            
            // Display results
            const resultsDiv = document.getElementById('testResults');
            let html = '<h6>Test Results:</h6><ul>';
            html += `<li>Form Submission: <span class="${results.formSubmission ? 'text-success' : 'text-danger'}">${results.formSubmission ? 'PASS' : 'FAIL'}</span></li>`;
            html += `<li>Button Click: <span class="${results.buttonClick ? 'text-success' : 'text-danger'}">${results.buttonClick ? 'PASS' : 'FAIL'}</span></li>`;
            html += `<li>Direct Click: <span class="${results.directClick ? 'text-success' : 'text-danger'}">${results.directClick ? 'PASS' : 'FAIL'}</span></li>`;
            html += '</ul>';
            
            if (results.formSubmission && results.buttonClick && results.directClick) {
                html += '<div class="alert alert-success">All tests passed! The event system is working correctly.</div>';
            } else {
                html += '<div class="alert alert-warning">Some tests failed. Check the log for details.</div>';
            }
            
            resultsDiv.innerHTML = html;
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            log('DOM loaded, initializing debug tools...', 'info');
            
            // Run the full test automatically
            setTimeout(runFullTest, 1000);
            
            // Also set up manual test button
            const runTestBtn = document.getElementById('runFullTest');
            if (runTestBtn) {
                runTestBtn.addEventListener('click', runFullTest);
            }
        });
        
        // Log any global errors
        window.addEventListener('error', function(e) {
            log(`GLOBAL ERROR: ${e.message} at ${e.filename}:${e.lineno}`, 'error');
        });
    </script>
</body>
</html>