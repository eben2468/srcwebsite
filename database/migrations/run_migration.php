<?php
/**
 * Database Migration Script
 * Adds student_id, level, and department columns to user_profiles table
 * 
 * Run this file once by accessing it in your browser:
 * http://localhost/vvusrc/database/migrations/run_migration.php
 */

require_once __DIR__ . '/../../includes/db_config.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Migration</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    h1 { color: #333; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";
echo "</head><body>";

echo "<h1>Database Migration - Add Student Fields</h1>";
echo "<div class='info'><strong>Migration:</strong> Adding student_id, level, and department columns to user_profiles table</div>";

try {
    // Check if connection exists
    if (!isset($conn)) {
        throw new Exception("Database connection not established.");
    }
    
    echo "<h2>Migration Steps:</h2>";
    
    // Step 1: Check if user_profiles table exists
    echo "<p>1. Checking if user_profiles table exists...</p>";
    $checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'user_profiles'");
    if (mysqli_num_rows($checkTable) == 0) {
        throw new Exception("Table 'user_profiles' does not exist. Please create it first.");
    }
    echo "<div class='success'>✓ Table user_profiles exists</div>";
    
    // Step 2: Check current table structure
    echo "<p>2. Checking current table structure...</p>";
    $result = mysqli_query($conn, "DESCRIBE user_profiles");
    $existingColumns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $existingColumns[] = $row['Field'];
    }
    echo "<div class='info'>Current columns: " . implode(', ', $existingColumns) . "</div>";
    
    // Step 3: Add student_id column
    echo "<p>3. Adding student_id column...</p>";
    if (in_array('student_id', $existingColumns)) {
        echo "<div class='info'>⚠ Column 'student_id' already exists - skipping</div>";
    } else {
        $sql = "ALTER TABLE user_profiles ADD COLUMN student_id VARCHAR(50) NULL AFTER address";
        if (mysqli_query($conn, $sql)) {
            echo "<div class='success'>✓ Successfully added student_id column</div>";
        } else {
            throw new Exception("Error adding student_id: " . mysqli_error($conn));
        }
    }
    
    // Step 4: Add level column
    echo "<p>4. Adding level column...</p>";
    if (in_array('level', $existingColumns)) {
        echo "<div class='info'>⚠ Column 'level' already exists - skipping</div>";
    } else {
        $sql = "ALTER TABLE user_profiles ADD COLUMN level VARCHAR(20) NULL AFTER " . 
               (in_array('student_id', $existingColumns) ? "student_id" : "address");
        if (mysqli_query($conn, $sql)) {
            echo "<div class='success'>✓ Successfully added level column</div>";
        } else {
            throw new Exception("Error adding level: " . mysqli_error($conn));
        }
    }
    
    // Step 5: Add department column
    echo "<p>5. Adding department column...</p>";
    if (in_array('department', $existingColumns)) {
        echo "<div class='info'>⚠ Column 'department' already exists - skipping</div>";
    } else {
        $sql = "ALTER TABLE user_profiles ADD COLUMN department VARCHAR(255) NULL";
        if (mysqli_query($conn, $sql)) {
            echo "<div class='success'>✓ Successfully added department column</div>";
        } else {
            throw new Exception("Error adding department: " . mysqli_error($conn));
        }
    }
    
    // Step 6: Verify final structure
    echo "<p>6. Verifying final table structure...</p>";
    $result = mysqli_query($conn, "DESCRIBE user_profiles");
    $finalColumns = [];
    echo "<pre>";
    printf("%-20s %-20s %-10s\n", "Column", "Type", "Null");
    echo str_repeat("-", 50) . "\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $finalColumns[] = $row['Field'];
        printf("%-20s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
    }
    echo "</pre>";
    
    // Success message
    echo "<div class='success'>";
    echo "<h2>✓ Migration Completed Successfully!</h2>";
    echo "<p>The following columns are now available in the user_profiles table:</p>";
    echo "<ul>";
    echo "<li><strong>student_id</strong> - VARCHAR(50) - For storing student ID numbers</li>";
    echo "<li><strong>level</strong> - VARCHAR(20) - For storing student level (Level 100-400)</li>";
    echo "<li><strong>department</strong> - VARCHAR(255) - For storing department names</li>";
    echo "</ul>";
    echo "<p><strong>Total columns:</strong> " . count($finalColumns) . "</p>";
    echo "</div>";
    
    echo "<p style='margin-top: 30px;'><a href='../../pages_php/profile.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Profile Page</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Migration Failed</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Close connection
if (isset($conn)) {
    mysqli_close($conn);
}

echo "</body></html>";
?>
