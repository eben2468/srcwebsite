<?php
// Simple test to verify navigation link
echo "<h2>Navigation Test</h2>";
echo "<p>Checking if Public Chat link exists in navigation...</p>";

// Read the header file
$headerContent = file_get_contents(__DIR__ . '/pages_php/includes/header.php');

if (strpos($headerContent, 'Public Chat') !== false) {
    echo "<p style='color: green;'>✓ Public Chat link found in navigation</p>";
} else {
    echo "<p style='color: red;'>✗ Public Chat link NOT found in navigation</p>";
}

echo "<p><a href='pages_php/public_chat.php'>Go to Public Chat</a></p>";
?>