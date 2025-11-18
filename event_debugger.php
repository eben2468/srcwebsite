<?php
require_once __DIR__ . '/includes/simple_auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Debugger</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-area { border: 1px solid #ccc; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .log { height: 200px; overflow-y: auto; background: #f5f5f5; padding: 10px; margin: 10px 0; }
        .log-entry { margin: 5px 0; padding: 5px; border-radius: 3px; }
        .event { background: #e3f2fd; }
        .error { background: #ffebee; color: #c62828; }
        .success { background: #e8f5e9; color: #2e7d32; }
        button { padding: 10px 15px; margin: 5px; }
    </style>
</head>
<body>
    <h1>Event Debugger</h1>
    
    <div class="test-area">
        <h3>Test Form</h3>
        <form id="testForm">
            <input type="text" id="testInput" placeholder="Enter test message" style="padding: 10px; width: 300px;">
            <button type="submit" id="submitBtn">Submit Form</button>
            <button type="button" id="clickBtn">Click Button</button>
        </form>
    </div>
    
    <div class="test-area">
        <h3>Controls</h3>
        <button onclick="attachListeners()">Attach Listeners</button>
        <button onclick="testSubmit()">Test Submit</button>
        <button onclick="testClick()">Test Click</button>
        <button onclick="clearLog()">Clear Log</button>
    </div>
    
    <div class="test-area">
        <h3>Event Log</h3>
        <div id="eventLog" class="log"></div>
    </div>
    
    <div class="test-area">
        <h3>Links</h3>
        <p><a href="pages_php/public_chat.php">Original Public Chat</a></p>
        <p><a href="clean_public_chat.php">Clean Public Chat</a></p>
        <p><a href="minimal_send_test.php">Minimal Test</a></p>
    </div>

    <script>
        // Event logging
        function log(message, type = 'info') {
            const logDiv = document.getElementById('eventLog');
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            entry.innerHTML = `[${new Date().toLocaleTimeString()}] ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
            
            // Also log to console
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
        
        // Test elements
        function testElements() {
            const form = document.getElementById('testForm');
            const input = document.getElementById('testInput');
            const submitBtn = document.getElementById('submitBtn');
            const clickBtn = document.getElementById('clickBtn');
            
            log('Testing elements:', 'info');
            log(`Form: ${form ? 'FOUND' : 'NOT FOUND'}`, form ? 'success' : 'error');
            log(`Input: ${input ? 'FOUND' : 'NOT FOUND'}`, input ? 'success' : 'error');
            log(`Submit Button: ${submitBtn ? 'FOUND' : 'NOT FOUND'}`, submitBtn ? 'success' : 'error');
            log(`Click Button: ${clickBtn ? 'FOUND' : 'NOT FOUND'}`, clickBtn ? 'success' : 'error');
        }
        
        // Attach event listeners
        function attachListeners() {
            log('Attaching event listeners...', 'info');
            
            const form = document.getElementById('testForm');
            const submitBtn = document.getElementById('submitBtn');
            const clickBtn = document.getElementById('clickBtn');
            
            if (!form || !submitBtn || !clickBtn) {
                log('ERROR: Some elements not found', 'error');
                testElements();
                return;
            }
            
            // Remove existing listeners first (if any)
            form.removeEventListener('submit', formSubmitHandler);
            submitBtn.removeEventListener('click', submitClickHandler);
            clickBtn.removeEventListener('click', buttonClickHandler);
            
            // Add new listeners
            form.addEventListener('submit', formSubmitHandler);
            submitBtn.addEventListener('click', submitClickHandler);
            clickBtn.addEventListener('click', buttonClickHandler);
            
            log('Event listeners attached successfully', 'success');
        }
        
        // Event handlers
        function formSubmitHandler(e) {
            log('FORM SUBMIT EVENT TRIGGERED', 'event');
            e.preventDefault();
            e.stopPropagation();
            
            const input = document.getElementById('testInput');
            const message = input.value.trim();
            
            if (!message) {
                log('ERROR: Empty message', 'error');
                return;
            }
            
            log(`SUCCESS: Form submitted with message: "${message}"`, 'success');
            input.value = '';
            
            return false;
        }
        
        function submitClickHandler(e) {
            log('SUBMIT BUTTON CLICK EVENT TRIGGERED', 'event');
            e.preventDefault();
            e.stopPropagation();
            
            const input = document.getElementById('testInput');
            const message = input.value.trim();
            
            if (!message) {
                log('ERROR: Empty message', 'error');
                return;
            }
            
            log(`SUCCESS: Submit button clicked with message: "${message}"`, 'success');
            input.value = '';
            
            return false;
        }
        
        function buttonClickHandler(e) {
            log('CLICK BUTTON EVENT TRIGGERED', 'event');
            e.preventDefault();
            e.stopPropagation();
            
            const input = document.getElementById('testInput');
            const message = input.value.trim();
            
            if (!message) {
                log('ERROR: Empty message', 'error');
                return;
            }
            
            log(`SUCCESS: Click button pressed with message: "${message}"`, 'success');
            
            return false;
        }
        
        // Test functions
        function testSubmit() {
            log('Testing form submission programmatically...', 'info');
            const form = document.getElementById('testForm');
            if (form) {
                const event = new Event('submit', { bubbles: true, cancelable: true });
                form.dispatchEvent(event);
                log('Form submit event dispatched', 'success');
            } else {
                log('ERROR: Form not found', 'error');
            }
        }
        
        function testClick() {
            log('Testing button click programmatically...', 'info');
            const button = document.getElementById('submitBtn');
            if (button) {
                const event = new MouseEvent('click', { bubbles: true, cancelable: true });
                button.dispatchEvent(event);
                log('Button click event dispatched', 'success');
            } else {
                log('ERROR: Button not found', 'error');
            }
        }
        
        function clearLog() {
            document.getElementById('eventLog').innerHTML = '';
            log('Log cleared', 'info');
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('Event Debugger loaded', 'success');
            testElements();
            attachListeners();
        });
        
        // Handle global errors
        window.addEventListener('error', function(e) {
            log(`GLOBAL ERROR: ${e.message}`, 'error');
        });
    </script>
</body>
</html>