<?php
// Comprehensive debug for public chat
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Require login
requireLogin();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Chat Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { border: 1px solid #ccc; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        button { padding: 10px 15px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .result { margin: 10px 0; padding: 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Comprehensive Public Chat Debug</h1>";

// Test 1: Database connection and tables
echo "<div class='test-section'>
    <h3>1. Database Test</h3>";
    
if ($conn) {
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['public_chat_messages', 'public_chat_reactions'];
    foreach ($tables as $table) {
        $sql = "SHOW TABLES LIKE '$table'";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            echo "<p class='success'>✓ Table '$table' exists</p>";
        } else {
            echo "<p class='error'>✗ Table '$table' does not exist</p>";
        }
    }
} else {
    echo "<p class='error'>✗ Database connection failed</p>";
}

echo "</div>";

// Test 2: File existence
echo "<div class='test-section'>
    <h3>2. File Structure Test</h3>";
    
$files = [
    'pages_php/public_chat.php',
    'pages_php/public_chat_api.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p class='success'>✓ File '$file' exists</p>";
    } else {
        echo "<p class='error'>✗ File '$file' does not exist</p>";
    }
}

echo "</div>";

// Test 3: API endpoint test
echo "<div class='test-section'>
    <h3>3. API Endpoint Test</h3>
    <button onclick='testApiEndpoint()'>Test API Endpoint</button>
    <div id='apiResult'></div>
</div>";

// Test 4: JavaScript functionality test
echo "<div class='test-section'>
    <h3>4. JavaScript Test</h3>
    <button onclick='testJavaScript()'>Test JavaScript</button>
    <div id='jsResult'></div>
</div>";

// Test 5: Form submission test
echo "<div class='test-section'>
    <h3>5. Form Submission Test</h3>
    <form id='testForm'>
        <input type='text' id='testInput' placeholder='Test message' style='padding: 10px; width: 300px; margin: 5px;'>
        <button type='submit' id='testSubmit'>Submit Form</button>
        <button type='button' id='testButton'>Click Button</button>
    </form>
    <div id='formResult'></div>
</div>";

// Test 6: Direct links
echo "<div class='test-section'>
    <h3>6. Navigation Links</h3>
    <p><a href='pages_php/public_chat.php'>Go to Public Chat</a></p>
    <p><a href='debug_public_chat.php'>Go to Debug Chat</a></p>
    <p><a href='test_fetch_api.html'>Test Fetch API</a></p>
</div>";

echo "
<script>
function showResult(elementId, message, type) {
    const element = document.getElementById(elementId);
    element.innerHTML = `<div class='result ${type}'>${message}</div>`;
}

async function testApiEndpoint() {
    try {
        showResult('apiResult', 'Testing API endpoint...', 'info');
        
        const response = await fetch('test_chat_api_endpoint.php');
        const result = await response.json();
        
        if (result.success) {
            showResult('apiResult', 'SUCCESS: ' + result.message, 'success');
        } else {
            showResult('apiResult', 'ERROR: ' + result.error, 'error');
        }
    } catch (error) {
        showResult('apiResult', 'EXCEPTION: ' + error.message, 'error');
    }
}

function testJavaScript() {
    try {
        // Test if basic JavaScript is working
        if (typeof document !== 'undefined' && typeof window !== 'undefined') {
            showResult('jsResult', 'SUCCESS: JavaScript and DOM are available', 'success');
            
            // Test event listeners
            const testForm = document.getElementById('testForm');
            const testSubmit = document.getElementById('testSubmit');
            const testButton = document.getElementById('testButton');
            
            if (testForm && testSubmit && testButton) {
                showResult('jsResult', showResult('jsResult', 'SUCCESS: JavaScript and DOM are available<br>SUCCESS: Form elements found', 'success'), 'success');
            } else {
                showResult('jsResult', 'ERROR: Some form elements not found', 'error');
            }
        } else {
            showResult('jsResult', 'ERROR: JavaScript or DOM not available', 'error');
        }
    } catch (error) {
        showResult('jsResult', 'EXCEPTION: ' + error.message, 'error');
    }
}

// Set up form test
document.addEventListener('DOMContentLoaded', function() {
    const testForm = document.getElementById('testForm');
    const testSubmit = document.getElementById('testSubmit');
    const testButton = document.getElementById('testButton');
    const testInput = document.getElementById('testInput');
    
    if (testForm) {
        testForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = testInput.value.trim();
            if (message) {
                showResult('formResult', 'SUCCESS: Form submitted with message: ' + message, 'success');
            } else {
                showResult('formResult', 'ERROR: Empty message', 'error');
            }
        });
    }
    
    if (testSubmit) {
        testSubmit.addEventListener('click', function(e) {
            e.preventDefault();
            const message = testInput.value.trim();
            if (message) {
                showResult('formResult', 'SUCCESS: Submit button clicked with message: ' + message, 'success');
            } else {
                showResult('formResult', 'ERROR: Empty message', 'error');
            }
        });
    }
    
    if (testButton) {
        testButton.addEventListener('click', function(e) {
            e.preventDefault();
            const message = testInput.value.trim();
            if (message) {
                showResult('formResult', 'SUCCESS: Direct button clicked with message: ' + message, 'success');
            } else {
                showResult('formResult', 'ERROR: Empty message', 'error');
            }
        });
    }
});
</script>
</body>
</html>";
?>