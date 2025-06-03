<?php
/**
 * Test Report Upload Script
 * This script helps diagnose and test the report upload functionality.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'db_config.php';
require_once 'auth_functions.php';

// Output basic diagnostic information
echo "<h1>Report Upload Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>MySQL Connection: " . (isset($conn) ? "Connected" : "Not Connected") . "</p>";

// Check for reports table
$checkTableSql = "SHOW TABLES LIKE 'reports'";
$tableExists = mysqli_query($conn, $checkTableSql)->num_rows > 0;
echo "<p>Reports Table: " . ($tableExists ? "Exists" : "Does Not Exist") . "</p>";

if ($tableExists) {
    // Show report count
    $countSql = "SELECT COUNT(*) as count FROM reports";
    $countResult = mysqli_query($conn, $countSql);
    $count = mysqli_fetch_assoc($countResult)['count'];
    echo "<p>Total Reports: $count</p>";
    
    // Show most recent report
    if ($count > 0) {
        $recentSql = "SELECT * FROM reports ORDER BY created_at DESC LIMIT 1";
        $recentResult = mysqli_query($conn, $recentSql);
        $recentReport = mysqli_fetch_assoc($recentResult);
        
        echo "<h2>Most Recent Report</h2>";
        echo "<pre>";
        print_r($recentReport);
        echo "</pre>";
    }
}

// Function to create a sample report for testing
function createSampleReport() {
    global $conn;
    
    echo "<h2>Creating Sample Report</h2>";
    
    // Sample data
    $title = "Test Report " . date('Y-m-d H:i:s');
    $author = "Test Author";
    $date = date('Y-m-d');
    $type = "Test";
    $portfolio = "Testing";
    $summary = "This is a test report created for debugging purposes.";
    $categories = json_encode(["Test", "Debug"]);
    $filePath = "test_file.pdf"; // We're not actually uploading a file
    $featured = 0;
    $uploadedBy = 1; // Assuming user ID 1 exists
    
    // Construct and execute query
    $sql = "INSERT INTO reports (
                title, author, date, type, portfolio, summary, 
                categories, file_path, featured, uploaded_by,
                description, report_type, content, author_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, 'general', ?, ?
            )";
    
    $params = [
        $title,
        $author,
        $date,
        $type,
        $portfolio,
        $summary,
        $categories,
        $filePath,
        $featured,
        $uploadedBy,
        $summary, // description
        $summary, // content
        $uploadedBy // author_id
    ];
    
    // Prepare statement
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        // Bind parameters
        mysqli_stmt_bind_param(
            $stmt, 
            'ssssssssiisis',
            $title, $author, $date, $type, $portfolio, $summary,
            $categories, $filePath, $featured, $uploadedBy,
            $summary, $summary, $uploadedBy
        );
        
        // Execute
        $result = mysqli_stmt_execute($stmt);
        
        if ($result) {
            $insertId = mysqli_insert_id($conn);
            echo "<p>Sample report created successfully with ID: $insertId</p>";
        } else {
            echo "<p>Error creating sample report: " . mysqli_stmt_error($stmt) . "</p>";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "<p>Error preparing statement: " . mysqli_error($conn) . "</p>";
    }
}

// Create a sample report if requested
if (isset($_GET['create_sample']) && $_GET['create_sample'] == '1') {
    createSampleReport();
}

// Add a link to create a sample report
echo '<p><a href="' . $_SERVER['PHP_SELF'] . '?create_sample=1">Create Sample Report</a></p>';

// Close database connection
mysqli_close($conn);
?> 