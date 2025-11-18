<!DOCTYPE html>
<html>
<head>
    <title>Minimal Send Button Test</title>
</head>
<body>
    <h1>Minimal Send Button Test</h1>
    
    <form id="testForm">
        <input type="text" id="messageInput" placeholder="Type message here">
        <button type="submit" id="sendBtn">Send</button>
    </form>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;"></div>
    
    <script>
        // Minimal test - nothing else
        const form = document.getElementById('testForm');
        const input = document.getElementById('messageInput');
        const button = document.getElementById('sendBtn');
        const result = document.getElementById('result');
        
        if (!form || !input || !button) {
            result.innerHTML = '<p style="color: red;">ERROR: Form elements not found!</p>';
        } else {
            result.innerHTML = '<p style="color: green;">SUCCESS: All elements found</p>';
            
            // Test 1: Form submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                result.innerHTML += '<p style="color: blue;">FORM SUBMIT EVENT TRIGGERED</p>';
                result.innerHTML += '<p>Message: ' + input.value + '</p>';
            });
            
            // Test 2: Button click
            button.addEventListener('click', function(e) {
                e.preventDefault();
                result.innerHTML += '<p style="color: purple;">BUTTON CLICK EVENT TRIGGERED</p>';
            });
        }
    </script>
</body>
</html>