<?php
/**
 * Authentication Fix Script
 * This script updates all PHP files in the pages_php directory to use the correct authentication approach
 */

// Open a log file
$logFile = fopen('auth_fix_log.txt', 'w');

function log_message($message) {
    global $logFile;
    fwrite($logFile, $message . "\n");
    echo $message . "\n";
}

// Directory to process
$directory = __DIR__ . '/pages_php';
log_message("Starting to process files in $directory");

// Get all PHP files
$files = glob($directory . '/*.php');
log_message("Found " . count($files) . " PHP files");

// Process each file
foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip certain files
    if (in_array($filename, ['auth.php', 'login.php', 'logout.php', 'debug_session.php'])) {
        log_message("Skipping $filename");
        continue;
    }
    
    log_message("Processing $filename...");
    
    // Read file content
    $content = file_get_contents($file);
    
    // Debug
    $hasAuthInclude = strpos($content, "require_once 'auth.php'") !== false;
    $hasAuthInclude2 = strpos($content, "include 'auth.php'") !== false;
    log_message("  Has require_once: " . ($hasAuthInclude ? "YES" : "NO"));
    log_message("  Has include: " . ($hasAuthInclude2 ? "YES" : "NO"));
    
    // Check if the file includes auth.php
    if ($hasAuthInclude || $hasAuthInclude2) {
        // Replace the auth.php include with auth_functions.php
        $newContent = preg_replace("/(require_once|include)(\s+)'auth\.php';/", "require_once '../auth_functions.php';", $content);
        
        // Check if replacement happened
        if ($newContent === $content) {
            log_message("  No replacements made in $filename");
        } else {
            // Replace requireLogin() calls with direct check
            if (strpos($newContent, "requireLogin();") !== false) {
                $loginCheck = <<<'EOD'
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
EOD;
                $newContent = str_replace("requireLogin();", $loginCheck, $newContent);
            }
            
            // Write the updated content back to the file
            file_put_contents($file, $newContent);
            log_message("  Updated $filename");
        }
    } else {
        log_message("  No auth.php reference found in $filename");
    }
}

log_message("\nAuth reference update complete!");

// Close the log file
fclose($logFile);
?> 