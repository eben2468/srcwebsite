<?php
// Include database configuration
require_once 'db_config.php';

echo "Starting test report creation...<br>";

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

// Construct query
$sql = "INSERT INTO reports (
            title, author, date, type, portfolio, summary, 
            categories, file_path, featured, uploaded_by,
            description, report_type, content, author_id
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?,
            ?, 'general', ?, ?
        )";

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
        echo "Sample report created successfully with ID: $insertId<br>";
    } else {
        echo "Error creating sample report: " . mysqli_stmt_error($stmt) . "<br>";
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn) . "<br>";
}

// Close connection
mysqli_close($conn);

echo "Test completed!";
?> 