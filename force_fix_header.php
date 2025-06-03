<?php
// Force fix header file with embedded book icon
$headerFile = 'pages_php/includes/header.php';
$backupFile = 'pages_php/includes/header.php.bak.' . time();

// Backup the header file first
if (file_exists($headerFile)) {
    copy($headerFile, $backupFile);
}

// Read the header file
$headerContent = file_get_contents($headerFile);

// Find the navbar-brand section and replace it
$pattern = '/<a class="navbar-brand".*?>(.*?)<\/a>/s';
$replacement = '<a class="navbar-brand" href="dashboard.php">
    <svg class="system-icon" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
    </svg>
    <?php echo $siteName; ?>
</a>';

$newHeaderContent = preg_replace($pattern, $replacement, $headerContent);

// Write the modified content back to the file
$success = file_put_contents($headerFile, $newHeaderContent);

// HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Fix Header</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
    <meta http-equiv="refresh" content="3;url=pages_php/dashboard.php">
</head>
<body>
    <h1>Force Fix Header</h1>
    
    <?php if ($success): ?>
        <p class="success">Successfully updated the header file with an embedded book icon.</p>
    <?php else: ?>
        <p class="error">Failed to update the header file.</p>
    <?php endif; ?>
    
    <p>You will be redirected to the dashboard in 3 seconds.</p>
    <p>If not redirected, <a href="pages_php/dashboard.php">click here</a>.</p>
</body>
</html>