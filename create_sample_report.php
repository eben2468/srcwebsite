<?php
/**
 * Create Sample Report Script
 * This script creates a sample report file and adds it to the database.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'db_config.php';
require_once 'auth_functions.php';

echo "<h1>Creating Sample Report</h1>";

// Define uploads directory
$uploadsDir = 'uploads/reports';

// Create directory if it doesn't exist
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        echo "<p>Created reports uploads directory.</p>";
    } else {
        echo "<p>Failed to create reports uploads directory.</p>";
        exit;
    }
} else {
    echo "<p>Reports uploads directory already exists.</p>";
}

// Check if the sample file exists in the directory
$sampleFileName = 'sample_report.pdf';
$sampleFilePath = $uploadsDir . '/' . $sampleFileName;

// If the sample file doesn't exist, create one
if (!file_exists($sampleFilePath)) {
    // Check if we have a dummy report HTML file
    $dummyHtmlPath = 'dummy_report.html';
    
    if (!file_exists($dummyHtmlPath)) {
        echo "<p>Error: The dummy report HTML file does not exist.</p>";
        
        // Create a basic dummy HTML file
        $dummyHtml = '<!DOCTYPE html>
<html>
<head>
    <title>Sample Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        h1 { color: #2c3e50; text-align: center; }
        .content { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>SRC Sample Report</h1>
    <div class="content">
        <h2>Overview</h2>
        <p>This is a sample report for testing the download functionality of the SRC Management System.</p>
        <p>In a real report, this section would contain detailed information about SRC activities, events, and other relevant information.</p>
        
        <h2>Key Points</h2>
        <ul>
            <li>Sample point 1</li>
            <li>Sample point 2</li>
            <li>Sample point 3</li>
        </ul>
        
        <h2>Conclusion</h2>
        <p>This sample report concludes with a summary of the information presented.</p>
    </div>
    <footer>
        <p>SRC Management System - Sample Report</p>
    </footer>
</body>
</html>';
        
        file_put_contents($dummyHtmlPath, $dummyHtml);
        echo "<p>Created a basic dummy HTML file.</p>";
    }
    
    // Try to convert HTML to PDF if a conversion tool is available
    $conversionMethod = '';
    
    // Try using wkhtmltopdf if available
    if (shell_exec('which wkhtmltopdf')) {
        $command = "wkhtmltopdf $dummyHtmlPath $sampleFilePath";
        shell_exec($command);
        $conversionMethod = 'wkhtmltopdf';
    } 
    // If no conversion tool is available, create a simple PDF using PHP
    else {
        // Create a simple PDF file using basic PHP
        $pdf = "
%PDF-1.4
1 0 obj
<</Type /Catalog /Pages 2 0 R>>
endobj
2 0 obj
<</Type /Pages /Kids [3 0 R] /Count 1>>
endobj
3 0 obj
<</Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 500 800] /Contents 6 0 R>>
endobj
4 0 obj
<</Font <</F1 5 0 R>>>>
endobj
5 0 obj
<</Type /Font /Subtype /Type1 /BaseFont /Helvetica>>
endobj
6 0 obj
<</Length 44>>
stream
BT /F1 24 Tf 50 700 Td (SRC Sample Report) Tj ET
BT /F1 12 Tf 50 650 Td (This is a sample report file for testing download functionality.) Tj ET
BT /F1 12 Tf 50 630 Td (In a real scenario, this would be a proper PDF document with SRC information.) Tj ET
endstream
endobj
xref
0 7
0000000000 65535 f
0000000009 00000 n
0000000056 00000 n
0000000111 00000 n
0000000212 00000 n
0000000250 00000 n
0000000317 00000 n
trailer
<</Size 7/Root 1 0 R>>
startxref
406
%%EOF
";
        file_put_contents($sampleFilePath, $pdf);
        $conversionMethod = 'basic PHP';
    }
    
    if (file_exists($sampleFilePath)) {
        echo "<p>Created sample PDF file using $conversionMethod.</p>";
    } else {
        echo "<p>Failed to create sample PDF file.</p>";
        exit;
    }
} else {
    echo "<p>Sample PDF file already exists.</p>";
}

// Add sample report to database if it doesn't exist
$checkSql = "SELECT * FROM reports WHERE file_path = ?";
$existing = fetchOne($checkSql, [$sampleFileName]);

if (!$existing) {
    // Sample data
    $title = "SRC Sample Report";
    $author = "SRC Admin";
    $date = date('Y-m-d');
    $type = "Sample";
    $portfolio = "General";
    $summary = "This is a sample report for testing the download functionality of the SRC Management System.";
    $categories = json_encode(["Sample", "Test"]);
    $featured = 1;
    $uploadedBy = 1; // Assuming admin user has ID 1
    
    // Insert query
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
        $sampleFileName,
        $featured,
        $uploadedBy,
        $summary, // description
        $summary, // content
        $uploadedBy // author_id
    ];
    
    $insertId = insert($sql, $params);
    
    if ($insertId) {
        echo "<p>Added sample report to database with ID: $insertId</p>";
        
        // Generate URLs
        $viewUrl = "pages_php/reports.php";
        $downloadUrl = "pages_php/report_handler.php?action=download&id=$insertId";
        
        echo "<p>You can now:</p>";
        echo "<ul>";
        echo "<li><a href='$viewUrl'>View all reports</a></li>";
        echo "<li><a href='$downloadUrl'>Download the sample report</a></li>";
        echo "</ul>";
    } else {
        echo "<p>Failed to add sample report to database: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Sample report already exists in database with ID: " . $existing['report_id'] . "</p>";
    
    // Generate URLs
    $viewUrl = "pages_php/reports.php";
    $downloadUrl = "pages_php/report_handler.php?action=download&id=" . $existing['report_id'];
    
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='$viewUrl'>View all reports</a></li>";
    echo "<li><a href='$downloadUrl'>Download the sample report</a></li>";
    echo "</ul>";
}

// Close database connection
mysqli_close($conn);
?> 