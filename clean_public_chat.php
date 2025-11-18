<?php
// Include simple authentication and required files
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';
require_once __DIR__ . '/includes/db_functions.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean Public Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-window {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 600px;
            display: flex;
            flex-direction: column;
            margin: 20px auto;
            max-width: 800px;
        }
        .chat-window-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .chat-input-area {
            padding: 1rem 1.5rem;
            background: white;
            border-top: 1px solid #e9ecef;
        }
        .chat-input-form {
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }
        .chat-input {
            flex: 1;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            padding: 0.75rem 1rem;
            resize: none;
            max-height: 100px;
        }
        .chat-input:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.2rem rgba(79, 172, 254, 0.25);
            outline: none;
        }
        .chat-send-btn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .chat-send-btn:hover {
            transform: scale(1.05);
        }
        .result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="chat-window">
            <div class="chat-window-header">
                <div>Clean Public Chat Test</div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <p>Welcome to the clean chat test!</p>
                <p>This version avoids all header conflicts.</p>
            </div>

            <div class="chat-input-area">
                <form class="chat-input-form" id="messageForm">
                    <textarea class="chat-input" id="messageInput" placeholder="Type your message..." 
                              rows="1" required></textarea>
                    <button type="submit" class="chat-send-btn" id="sendBtn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="container mt-3">
            <button class="btn btn-primary" onclick="testElements()">Test Elements</button>
            <button class="btn btn-secondary" onclick="testEvents()">Test Events</button>
            <div id="testResult" class="mt-3"></div>
        </div>
    </div>

    <script>
        // Simple test functions
        function showResult(message, type) {
            const resultDiv = document.getElementById('testResult');
            resultDiv.innerHTML = `<div class="result ${type}">${message}</div>`;
        }
        
        function testElements() {
            const form = document.getElementById('messageForm');
            const input = document.getElementById('messageInput');
            const button = document.getElementById('sendBtn');
            
            let message = '';
            if (form) message += '✓ Form found<br>';
            else message += '✗ Form not found<br>';
            
            if (input) message += '✓ Input found<br>';
            else message += '✗ Input not found<br>';
            
            if (button) message += '✓ Button found<br>';
            else message += '✗ Button not found<br>';
            
            showResult(message, 'info');
        }
        
        function testEvents() {
            const form = document.getElementById('messageForm');
            const button = document.getElementById('sendBtn');
            
            if (!form || !button) {
                showResult('Form or button not found', 'error');
                return;
            }
            
            // Add test event listeners
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Form submit event fired');
                showResult('Form submit event fired - check console', 'success');
            });
            
            button.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Button click event fired');
                showResult('Button click event fired - check console', 'success');
            });
            
            showResult('Test event listeners added. Try clicking the send button.', 'info');
        }
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            showResult('Page loaded successfully', 'success');
            
            // Get form elements
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');
            const sendBtn = document.getElementById('sendBtn');
            
            if (!messageForm || !messageInput || !sendBtn) {
                showResult('ERROR: Some form elements not found', 'error');
                return;
            }
            
            // Add form submit event listener
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const message = messageInput.value.trim();
                if (!message) {
                    showResult('ERROR: Message cannot be empty', 'error');
                    return;
                }
                
                showResult('SUCCESS: Form submitted with message: ' + message, 'success');
                messageInput.value = '';
                
                return false;
            });
            
            // Add button click event listener
            sendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const message = messageInput.value.trim();
                if (!message) {
                    showResult('ERROR: Message cannot be empty', 'error');
                    return;
                }
                
                showResult('SUCCESS: Button clicked with message: ' + message, 'success');
                messageInput.value = '';
                
                return false;
            });
            
            // Add enter key support
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const message = messageInput.value.trim();
                    if (!message) {
                        showResult('ERROR: Message cannot be empty', 'error');
                        return;
                    }
                    
                    showResult('SUCCESS: Enter key pressed with message: ' + message, 'success');
                    messageInput.value = '';
                    
                    return false;
                }
            });
        });
    </script>
</body>
</html>