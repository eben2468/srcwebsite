<?php
/**
 * Setup script to create the video_tutorials table
 * Run this script once to set up the video tutorials functionality
 */

require_once 'includes/db_config.php';

echo "<h2>Setting up Video Tutorials Table</h2>\n";

try {
    // Read the SQL file
    $sqlFile = 'sql/video_tutorials_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>\n";
        
        if (mysqli_query($conn, $statement)) {
            echo "<p style='color: green;'>✓ Success</p>\n";
            $successCount++;
        } else {
            echo "<p style='color: red;'>✗ Error: " . mysqli_error($conn) . "</p>\n";
            $errorCount++;
        }
    }
    
    echo "<hr>\n";
    echo "<h3>Setup Complete!</h3>\n";
    echo "<p>Successfully executed: $successCount statements</p>\n";
    echo "<p>Errors: $errorCount statements</p>\n";
    
    if ($errorCount === 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Video tutorials table has been set up successfully!</p>\n";
        echo "<p>You can now:</p>\n";
        echo "<ul>\n";
        echo "<li>Visit <a href='pages_php/support/admin-video-tutorials.php'>Admin Video Tutorials</a> to manage tutorials</li>\n";
        echo "<li>Visit <a href='pages_php/support/video-tutorials.php'>Video Tutorials</a> to view tutorials</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p style='color: red; font-weight: bold;'>⚠ Some errors occurred during setup. Please check the database manually.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</p>\n";
}

mysqli_close($conn);
?>