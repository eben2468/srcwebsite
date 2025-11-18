<!DOCTYPE html>
<html>
<head>
    <title>Simple Chat Test</title>
    <style>
        .chat-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .chat-input-area {
            display: flex;
            gap: 10px;
            margin-top: 20px;
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
        .result {
            margin-top: 20px;
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
    <div class="chat-container">
        <h2>Simple Chat Test</h2>
        <p>This is a simplified test to check if the form submission works.</p>
        
        <form class="chat-input-area" id="messageForm">
            <input type="text" class="chat-input" id="messageInput" placeholder="Type your message..." required>
            <button type="submit" class="chat-send-btn" id="sendBtn">Send</button>
        </form>
        
        <div id="result"></div>
    </div>

    <script>
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const message = document.getElementById('messageInput').value;
            
            if (message.trim() === '') {
                showResult('Message cannot be empty!', 'error');
                return;
            }
            
            // Simulate sending message
            showResult('Message sent successfully: ' + message, 'success');
            document.getElementById('messageInput').value = '';
        });
        
        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="result ' + type + '">' + message + '</div>';
            
            // Auto clear after 3 seconds
            setTimeout(() => {
                resultDiv.innerHTML = '';
            }, 3000);
        }
        
        // Also test button click
        document.getElementById('sendBtn').addEventListener('click', function(e) {
            console.log('Send button clicked');
        });
    </script>
</body>
</html>