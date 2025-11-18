<?php
/**
 * Direct Fix for Notifications Table - Add Missing Columns
 * This script directly adds the missing columns to fix the messaging service error
 */

require_once 'includes/db_config.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Direct Notifications Table Fix</title>";
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
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Direct Notifications Table Fix</h1>";

try {
    // Check current table structure
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
    
    $fixesApplied = [];
    $errors = [];
    
    // Check and add created_by column
    if (!in_array('created_by', $columns)) {
        echo "<h3>2. Adding 'created_by' column...</h3>";
        $sql1 = "ALTER TABLE notifications ADD COLUMN created_by INT NULL";
        $result1 = mysqli_query($conn, $sql1);
        
        if ($result1) {
            echo "<p class='success'>✅ Successfully added 'created_by' column</p>";
            $fixesApplied[] = "Added 'created_by' column";
        } else {
            $error = "Failed to add 'created_by' column: " . mysqli_error($conn);
            echo "<p class='error'>❌ " . $error . "</p>";
            $errors[] = $error;
        }
    } else {
        echo "<p class='info'>ℹ️ 'created_by' column already exists</p>";
    }
    
    // Check and add expiry_date column
    if (!in_array('expiry_date', $columns)) {
        echo "<h3>3. Adding 'expiry_date' column...</h3>";
        $sql2 = "ALTER TABLE notifications ADD COLUMN expiry_date DATETIME NULL";
        $result2 = mysqli_query($conn, $sql2);
        
        if ($result2) {
            echo "<p class='success'>✅ Successfully added 'expiry_date' column</p>";
            $fixesApplied[] = "Added 'expiry_date' column";
        } else {
            $error = "Failed to add 'expiry_date' column: " . mysqli_error($conn);
            echo "<p class='error'>❌ " . $error . "</p>";
            $errors[] = $error;
        }
    } else {
        echo "<p class='info'>ℹ️ 'expiry_date' column already exists</p>";
    }
    
    // Verify final structure
    echo "<h3>4. Verifying updated table structure...</h3>";
    $finalResult = mysqli_query($conn, "DESCRIBE notifications");
    
    if ($finalResult) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $finalColumns = [];
        while ($row = mysqli_fetch_assoc($finalResult)) {
            $finalColumns[] = $row['Field'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if required columns are now present
        $requiredColumns = ['created_by', 'expiry_date'];
        $stillMissing = array_diff($requiredColumns, $finalColumns);
        
        if (empty($stillMissing)) {
            echo "<div class='alert alert-success'>";
            echo "<h4>🎉 Fix Completed Successfully!</h4>";
            echo "<p>All required columns are now present in the notifications table.</p>";
            if (!empty($fixesApplied)) {
                echo "<p><strong>Applied fixes:</strong></p>";
                echo "<ul>";
                foreach ($fixesApplied as $fix) {
                    echo "<li>" . htmlspecialchars($fix) . "</li>";
                }
                echo "</ul>";
            }
            echo "<p><strong>The messaging service error should now be resolved!</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Some columns are still missing:</h4>";
            echo "<ul>";
            foreach ($stillMissing as $missing) {
                echo "<li>" . htmlspecialchars($missing) . "</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
    }
    
    if (!empty($errors)) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Errors encountered:</h4>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Exception occurred:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<h3>5. Next Steps</h3>";
echo "<div class='alert alert-info'>";
echo "<p>After running this fix:</p>";
echo "<ol>";
echo "<li>Try creating a welfare announcement or notification again</li>";
echo "<li>The 'Unknown column created_by' error should be resolved</li>";
echo "<li>In-app notifications should work properly</li>";
echo "</ol>";
echo "</div>";

echo "</div>";
echo "</body></html>";
?>