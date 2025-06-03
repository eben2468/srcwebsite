<?php
/**
 * Test Report Download Functionality
 * This script tests the download functionality of reports.
 */

// Include required files
require_once 'db_config.php';
require_once 'auth_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Report Download Test</h1>";

// Find a report to test with
$sql = "SELECT * FROM reports ORDER BY report_id DESC LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $report = mysqli_fetch_assoc($result);
    $reportId = $report['report_id'];
    $reportTitle = $report['title'];
    $reportFilePath = $report['file_path'];
    
    echo "<p>Found report: $reportTitle (ID: $reportId)</p>";
    
    // Check if file exists
    $uploadsDir = 'uploads/reports';
    $filePath = $uploadsDir . '/' . $reportFilePath;
    
    if (file_exists($filePath)) {
        echo "<p>File exists at: $filePath</p>";
        echo "<p>File size: " . filesize($filePath) . " bytes</p>";
        
        // Generate download URL
        $downloadUrl = "pages_php/report_handler.php?action=download&id=$reportId";
        
        echo "<p>Download link: <a href='$downloadUrl' target='_blank'>Download Report</a></p>";
        
        // Direct link to file
        $directLink = "$uploadsDir/$reportFilePath";
        echo "<p>Direct file link: <a href='$directLink' target='_blank'>View File Directly</a></p>";
        
        // Additional file information
        echo "<p>File information:</p>";
        echo "<ul>";
        echo "<li>MIME type: " . mime_content_type($filePath) . "</li>";
        echo "<li>Last modified: " . date("F d Y H:i:s", filemtime($filePath)) . "</li>";
        echo "</ul>";
    } else {
        echo "<p>Error: File does not exist at path: $filePath</p>";
        
        // Check if uploads directory exists
        if (!is_dir($uploadsDir)) {
            echo "<p>The uploads directory does not exist!</p>";
        } else {
            echo "<p>The uploads directory exists, but the file is missing.</p>";
            
            // List files in the directory
            echo "<p>Files in $uploadsDir directory:</p>";
            echo "<ul>";
            $files = scandir($uploadsDir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    echo "<li>$file</li>";
                }
            }
            echo "</ul>";
        }
    }
} else {
    echo "<p>No reports found in the database. Please create a report first.</p>";
}

echo "<h2>Testing Download Handler</h2>";
echo "<p>The report_handler.php script handles downloads with the following code:</p>";
echo "<pre>";
echo "if (isset(\$_GET['action']) && \$_GET['action'] === 'download' && isset(\$_GET['id'])) {
    \$reportId = intval(\$_GET['id']);
    
    // Get report information
    \$sql = \"SELECT * FROM reports WHERE report_id = ?\";
    \$report = fetchOne(\$sql, [\$reportId]);
    
    if (\$report) {
        \$filePath = \$uploadsDir . '/' . \$report['file_path'];
        
        if (file_exists(\$filePath)) {
            // Set headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename=\"' . basename(\$report['file_path']) . '\"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize(\$filePath));
            
            // Clear output buffer
            ob_clean();
            flush();
            
            // Output file
            readfile(\$filePath);
            exit;
        }
    }
}";
echo "</pre>";

// Close database connection
mysqli_close($conn);
?> 