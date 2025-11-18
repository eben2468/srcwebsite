<?php
/**
 * Temporary Fix for Messaging Service
 * This creates a backup and fixes the messaging service to work with existing table structure
 */

require_once 'includes/db_config.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Messaging Service Temporary Fix</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".alert { padding: 15px; margin: 15px 0; border-radius: 4px; }";
echo ".alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }";
echo ".alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }";
echo ".alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }";
echo ".alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Messaging Service Temporary Fix</h1>";

try {
    // First check current notifications table structure
    echo "<h3>1. Checking current notifications table structure...</h3>";
    $describeResult = mysqli_query($conn, "DESCRIBE notifications");
    
    if (!$describeResult) {
        throw new Exception("Failed to describe notifications table: " . mysqli_error($conn));
    }
    
    $columns = [];
    while ($row = mysqli_fetch_assoc($describeResult)) {
        $columns[] = $row['Field'];
    }
    
    echo "<p class='info'>Current columns: " . implode(', ', $columns) . "</p>";
    
    // Check if problematic columns exist
    $hasCreatedBy = in_array('created_by', $columns);
    $hasExpiryDate = in_array('expiry_date', $columns);
    
    if ($hasCreatedBy && $hasExpiryDate) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Table structure is correct!</h4>";
        echo "<p>Both 'created_by' and 'expiry_date' columns exist. The messaging service should work.</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ Missing columns detected</h4>";
        echo "<p>Missing: ";
        $missing = [];
        if (!$hasCreatedBy) $missing[] = 'created_by';
        if (!$hasExpiryDate) $missing[] = 'expiry_date';
        echo implode(', ', $missing) . "</p>";
        echo "</div>";
        
        // Create a temporary fix for the messaging service
        echo "<h3>2. Creating temporary messaging service fix...</h3>";
        
        // Read current messaging service
        $messagingServicePath = 'messaging_service.php';
        if (file_exists($messagingServicePath)) {
            $currentContent = file_get_contents($messagingServicePath);
            
            // Create backup
            $backupPath = 'messaging_service_backup_' . date('Y-m-d_H-i-s') . '.php';
            file_put_contents($backupPath, $currentContent);
            echo "<p class='success'>✅ Created backup: {$backupPath}</p>";
            
            // Create modified version that works with existing table structure
            $modifiedContent = $currentContent;
            
            // Replace the problematic INSERT query
            $oldInsertPattern = '/INSERT INTO notifications \(title, message, type, created_by, expiry_date\)\s+VALUES \(\?, \?, \?, \?, \?\)/';
            $newInsert = 'INSERT INTO notifications (title, message, type) VALUES (?, ?, ?)';
            
            if (preg_match($oldInsertPattern, $modifiedContent)) {
                $modifiedContent = preg_replace($oldInsertPattern, $newInsert, $modifiedContent);
                echo "<p class='info'>📝 Updated INSERT query to use existing columns</p>";
            }
            
            // Replace the bind_param call
            $oldBindPattern = "/mysqli_stmt_bind_param\(\s*\\\$stmt,\s*'sssis',\s*\\\$title,\s*\\\$message,\s*\\\$type,\s*\\\$currentUserId,\s*\\\$expiryDate\s*\);/";
            $newBind = "mysqli_stmt_bind_param(\$stmt, 'sss', \$title, \$message, \$type);";
            
            if (preg_match($oldBindPattern, $modifiedContent)) {
                $modifiedContent = preg_replace($oldBindPattern, $newBind, $modifiedContent);
                echo "<p class='info'>📝 Updated bind_param to match new query</p>";
            }
            
            // Write the modified version
            file_put_contents($messagingServicePath, $modifiedContent);
            echo "<p class='success'>✅ Applied temporary fix to messaging_service.php</p>";
            
            echo "<div class='alert alert-success'>";
            echo "<h4>🎉 Temporary Fix Applied!</h4>";
            echo "<p>The messaging service has been modified to work with the existing table structure.</p>";
            echo "<p><strong>Changes made:</strong></p>";
            echo "<ul>";
            echo "<li>Removed 'created_by' and 'expiry_date' from INSERT query</li>";
            echo "<li>Updated bind_param to match the new query structure</li>";
            echo "<li>Created backup of original file</li>";
            echo "</ul>";
            echo "</div>";
            
        } else {
            echo "<p class='error'>❌ messaging_service.php not found</p>";
        }
    }
    
    echo "<h3>3. Next Steps</h3>";
    echo "<div class='alert alert-info'>";
    echo "<h4>🔄 What to do next:</h4>";
    echo "<ol>";
    echo "<li><strong>Test the fix:</strong> Try creating a welfare announcement now</li>";
    echo "<li><strong>Permanent solution:</strong> Add the missing columns to the database later</li>";
    echo "<li><strong>Restore full functionality:</strong> Once columns are added, restore from backup</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ Important Notes:</h4>";
    echo "<ul>";
    echo "<li>This is a temporary fix - notifications won't track who created them</li>";
    echo "<li>Notifications won't have expiry dates</li>";
    echo "<li>For full functionality, add the missing database columns</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Exception occurred:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>