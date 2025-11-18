<?php
// Exact replica of public chat page structure for testing
require_once __DIR__ . '/includes/simple_auth.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exact Chat Test</title>
    <style>
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
        .result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; border: 1px solid #ccc; border-radius: 15px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.5rem;">
            <h2>Exact Chat Test</h2>
        </div>
        
        <div class="chat-messages" id="chatMessages" style="height: 300px; overflow-y: auto; padding: 1rem; background: #f8f9fa;">
            <p>Welcome to the exact chat test!</p>
        </div>
        
        <div class="chat-input-area">
            <form class="chat-input-form" id="messageForm">
                <textarea class="chat-input" id="messageInput" placeholder="Type your message..." rows="1" required></textarea>
                <button type="submit" class="chat-send-btn" id="sendBtn">
                    <i class="fas fa-paper-plane">➤</i>
                </button>
            </form>
        </div>
        
        <div id="result" style="padding: 15px;"></div>
        
        <div style="padding: 15px; border-top: 1px solid #eee;">
            <button onclick="testFormElements()">Test Form Elements</button>
            <button onclick="testEventListeners()">Test Event Listeners</button>
        </div>
    </div>

    <script>
        // Test function to check if elements exist
        function testFormElements() {
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
        
        // Test event listeners
        function testEventListeners() {
            const form = document.getElementById('messageForm');
            const button = document.getElementById('sendBtn');
            
            if (!form || !button) {
                showResult('Form or button not found', 'error');
                return;
            }
            
            // Add test event listeners
            form.addEventListener('submit', function(e) {
                console.log('Form submit event fired');
            });
            
            button.addEventListener('click', function(e) {
                console.log('Button click event fired');
            });
            
            showResult('Test event listeners added. Check browser console for events.', 'info');
        }
        
        // Show result function
        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = `<div class="result ${type}">${message}</div>`;
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