<?php
require_once __DIR__ . '/includes/simple_auth.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Send Button Test</title>
    <style>
        .test-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
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
    </style>
</head>
<body>
    <div class="test-container">
        <h2>Send Button Test</h2>
        <p>Testing if the send button functionality works correctly.</p>
        
        <form id="testForm">
            <div class="form-group">
                <label for="messageInput">Message:</label>
                <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." required>
            </div>
            <button type="submit" id="sendBtn" class="btn">Send Message</button>
        </form>
        
        <div id="result"></div>
        
        <h3>Event Listeners Test</h3>
        <button id="testListeners" class="btn">Test Event Listeners</button>
        <div id="listenerResult"></div>
        
        <p><a href="pages_php/public_chat.php">Go to Public Chat</a></p>
    </div>

    <script>
        // Test form submission
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const message = document.getElementById('messageInput').value;
            
            if (message.trim() === '') {
                showResult('Message cannot be empty!', 'error');
                return;
            }
            
            showResult('Form submitted successfully! Message: ' + message, 'success');
            document.getElementById('messageInput').value = '';
        });
        
        // Test button click
        document.getElementById('sendBtn').addEventListener('click', function(e) {
            console.log('Send button clicked directly');
        });
        
        // Test event listeners
        document.getElementById('testListeners').addEventListener('click', function() {
            const form = document.getElementById('testForm');
            const button = document.getElementById('sendBtn');
            
            const formListeners = getEventListeners(form);
            const buttonListeners = getEventListeners(button);
            
            let result = '<p>Form listeners: ' + (formListeners.submit ? formListeners.submit.length : 0) + '</p>';
            result += '<p>Button listeners: ' + (buttonListeners.click ? buttonListeners.click.length : 0) + '</p>';
            
            document.getElementById('listenerResult').innerHTML = result;
        });
        
        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="result ' + type + '">' + message + '</div>';
            
            // Auto clear after 3 seconds
            setTimeout(() => {
                resultDiv.innerHTML = '';
            }, 3000);
        }
        
        // Simple function to get event listeners (for testing purposes)
        function getEventListeners(element) {
            // In real browsers, we could use getEventListeners(element) in dev tools
            // But for this test, we'll just return a mock object
            return {
                submit: element.addEventListener ? [{type: 'submit'}] : [],
                click: element.addEventListener ? [{type: 'click'}] : []
            };
        }
    </script>
</body>
</html>