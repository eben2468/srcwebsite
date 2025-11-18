<?php
/**
 * Fix Notifications Table Schema
 * This script fixes the missing 'created_by' column in the notifications table
 */

require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';

// Require super admin access for this script
requireLogin();
if (!isSuperAdmin()) {
    die("Access denied. Super admin access required.");
}

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Fix Notifications Table Schema</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }";
echo ".result-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }";
echo ".success { color: #28a745; font-weight: bold; }";
echo ".error { color: #dc3545; font-weight: bold; }";
echo ".info { color: #17a2b8; font-weight: bold; }";
echo ".warning { color: #ffc107; font-weight: bold; }";
echo ".code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }";
echo "</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Fix Notifications Table Schema</h1>";

// Check if this is a POST request to actually run the fix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_fix'])) {
    echo "<div class='result-card'>";
    echo "<h3>🚀 Running Database Schema Fix...</h3>";
    
    try {
        // First, check current table structure
        echo "<h5>1. Checking current notifications table structure...</h5>";
        $describeResult = mysqli_query($conn, "DESCRIBE notifications");
        
        if ($describeResult) {
            $columns = [];
            while ($row = mysqli_fetch_assoc($describeResult)) {
                $columns[] = $row['Field'];
            }
            
            echo "<p class='info'>Current columns: " . implode(', ', $columns) . "</p>";
            
            // Check if created_by column exists
            if (!in_array('created_by', $columns)) {
                echo "<h5>2. Adding missing 'created_by' column...</h5>";
                
                $alterSql = "ALTER TABLE notifications ADD COLUMN created_by INT NULL AFTER type";
                $result = mysqli_query($conn, $alterSql);
                
                if ($result) {
                    echo "<p class='success'>✅ Successfully added 'created_by' column</p>";
                } else {
                    echo "<p class='error'>❌ Error adding 'created_by' column: " . mysqli_error($conn) . "</p>";
                }
            } else {
                echo "<p class='success'>✅ 'created_by' column already exists</p>";
            }
            
            // Check if expiry_date column exists
            if (!in_array('expiry_date', $columns)) {
                echo "<h5>3. Adding missing 'expiry_date' column...</h5>";
                
                $alterSql2 = "ALTER TABLE notifications ADD COLUMN expiry_date DATETIME NULL AFTER created_by";
                $result2 = mysqli_query($conn, $alterSql2);
                
                if ($result2) {
                    echo "<p class='success'>✅ Successfully added 'expiry_date' column</p>";
                } else {
                    echo "<p class='error'>❌ Error adding 'expiry_date' column: " . mysqli_error($conn) . "</p>";
                }
            } else {
                echo "<p class='success'>✅ 'expiry_date' column already exists</p>";
            }
            
            // Verify the final structure
            echo "<h5>4. Verifying updated table structure...</h5>";
            $finalDescribeResult = mysqli_query($conn, "DESCRIBE notifications");
            
            if ($finalDescribeResult) {
                echo "<table class='table table-sm'>";
                echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
                echo "<tbody>";
                
                while ($row = mysqli_fetch_assoc($finalDescribeResult)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
            }
            
            echo "<div class='alert alert-success'>";
            echo "<h4>🎉 Schema Fix Completed Successfully!</h4>";
            echo "<p>The notifications table now has the required columns for the messaging service.</p>";
            echo "</div>";
            
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Error checking table structure:</h4>";
            echo "<p>" . mysqli_error($conn) . "</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Exception occurred:</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} else {
    // Show current table structure and fix options
    echo "<div class='result-card'>";
    echo "<h3>🔍 Current Notifications Table Structure</h3>";
    
    try {
        $describeResult = mysqli_query($conn, "DESCRIBE notifications");
        
        if ($describeResult) {
            echo "<table class='table table-sm'>";
            echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
            echo "<tbody>";
            
            $columns = [];
            while ($row = mysqli_fetch_assoc($describeResult)) {
                $columns[] = $row['Field'];
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            
            // Check for missing columns
            $requiredColumns = ['created_by', 'expiry_date'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (!empty($missingColumns)) {
                echo "<div class='alert alert-warning'>";
                echo "<h5>⚠️ Missing Required Columns:</h5>";
                echo "<ul>";
                foreach ($missingColumns as $column) {
                    echo "<li><code>{$column}</code></li>";
                }
                echo "</ul>";
                echo "<p>These columns are required by the messaging service for in-app notifications.</p>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-success'>";
                echo "<h5>✅ All Required Columns Present</h5>";
                echo "<p>The notifications table has all the required columns.</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h5>❌ Error checking table structure:</h5>";
            echo "<p>" . mysqli_error($conn) . "</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h5>❌ Exception occurred:</h5>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Show what the fix will do
    echo "<div class='result-card'>";
    echo "<h3>🔧 What This Fix Will Do</h3>";
    echo "<div class='alert alert-info'>";
    echo "<h5>Database Schema Updates:</h5>";
    echo "<ol>";
    echo "<li><strong>Add 'created_by' column:</strong> <code>ALTER TABLE notifications ADD COLUMN created_by INT NULL</code></li>";
    echo "<li><strong>Add 'expiry_date' column:</strong> <code>ALTER TABLE notifications ADD COLUMN expiry_date DATETIME NULL</code></li>";
    echo "<li><strong>Verify structure:</strong> Check that all required columns are present</li>";
    echo "</ol>";
    echo "<p><strong>Purpose:</strong> These columns are required by the messaging service to track who created notifications and when they expire.</p>";
    echo "</div>";
    echo "</div>";
    
    // Fix button
    echo "<div class='result-card text-center'>";
    echo "<h3>🚀 Run Schema Fix</h3>";
    echo "<p>Click the button below to add the missing columns to the notifications table:</p>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='run_fix' class='btn btn-primary btn-lg' onclick='return confirm(\"Are you sure you want to modify the notifications table schema? This action will add missing columns.\")'>";
    echo "<i class='fas fa-database me-2'></i>Fix Table Schema";
    echo "</button>";
    echo "</form>";
    echo "<p class='text-muted mt-2'><small>This will add the missing columns required by the messaging service.</small></p>";
    echo "</div>";
}

echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "<script src='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js'></script>";
echo "</body></html>";
?>