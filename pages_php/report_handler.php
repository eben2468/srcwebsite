<?php
// Include required files
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/reports';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle file upload
if (isset($_POST['upload_report'])) {
    // Check permission
    if (!hasPermission('create', 'reports')) {
        $_SESSION['error'] = "You don't have permission to upload reports.";
        header("Location: reports.php");
        exit();
    }

    // Get form data
    $title = $_POST['report_title'] ?? '';
    $author = $_POST['report_author'] ?? '';
    $date = $_POST['report_date'] ?? '';
    $type = $_POST['report_type'] ?? '';
    $portfolio = $_POST['report_portfolio'] ?? '';
    $summary = $_POST['report_summary'] ?? '';
    $categories = $_POST['report_categories'] ?? '';
    $featured = isset($_POST['report_featured']) ? 1 : 0;

    // Process categories into an array
    $categoriesArray = !empty($categories) ? array_map('trim', explode(',', $categories)) : [];
    $categoriesJson = json_encode($categoriesArray);

    // Validate input
    if (empty($title) || empty($author) || empty($date) || empty($type) || empty($portfolio) || empty($summary)) {
        $_SESSION['error'] = "All fields except categories are required.";
        header("Location: reports.php");
        exit();
    }

    // Check if file was uploaded successfully
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['report_file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed file types (only PDF for reports)
        $allowedExtensions = ['pdf'];
        
        // Check file type
        if (!in_array($fileExtension, $allowedExtensions)) {
            $_SESSION['error'] = "Invalid file type. Only PDF files are allowed.";
            header("Location: reports.php");
            exit();
        }
        
        // Check file size (10MB max)
        if ($fileSize > 10 * 1024 * 1024) {
            $_SESSION['error'] = "File is too large. Maximum size is 10MB.";
            header("Location: reports.php");
            exit();
        }
        
        // Create unique filename
        $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $fileName);
        $uploadFilePath = $uploadsDir . '/' . $newFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
            // Get current user ID
            $currentUser = getCurrentUser();
            $uploadedBy = $currentUser['user_id'];
            
            // Insert into database
            $sql = "INSERT INTO reports (title, author, date, type, portfolio, summary, categories, file_path, featured, uploaded_by, 
                                      description, report_type, content, author_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $title,
                $author,
                $date,
                $type,
                $portfolio,
                $summary,
                $categoriesJson,
                $newFileName,
                $featured,
                $uploadedBy,
                $summary, // Use summary for description as well
                'general', // Use 'general' as the default report_type
                $summary, // Use summary for content as well
                $uploadedBy // Use uploadedBy for author_id as well
            ];
            
            $insertId = insert($sql, $params);
            
            if ($insertId) {
                $_SESSION['success'] = "Report uploaded successfully.";
            } else {
                // Delete the file if database insert failed
                unlink($uploadFilePath);
                $_SESSION['error'] = "Failed to save report information: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error'] = "Failed to upload file.";
        }
    } else {
        $errorCode = $_FILES['report_file']['error'];
        $_SESSION['error'] = "File upload error (code: $errorCode). Please try again.";
    }
    
    header("Location: reports.php");
    exit();
}

// Handle file download
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $reportId = intval($_GET['id']);
    
    // Get report information
    $sql = "SELECT * FROM reports WHERE report_id = ?";
    $report = fetchOne($sql, [$reportId]);
    
    if ($report) {
        $filePath = $uploadsDir . '/' . $report['file_path'];
        
        if (file_exists($filePath)) {
            // Set headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($report['file_path']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            
            // Clear output buffer
            ob_clean();
            flush();
            
            // Output file
            readfile($filePath);
            exit;
        } else {
            $_SESSION['error'] = "File not found.";
            header("Location: reports.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Report not found.";
        header("Location: reports.php");
        exit();
    }
}

// Handle file deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $reportId = intval($_GET['id']);
    
    // Check permission
    if (!hasPermission('delete', 'reports')) {
        $_SESSION['error'] = "You don't have permission to delete reports.";
        header("Location: reports.php");
        exit();
    }
    
    // Get report information
    $sql = "SELECT * FROM reports WHERE report_id = ?";
    $report = fetchOne($sql, [$reportId]);
    
    if ($report) {
        // Delete file from server
        $filePath = $uploadsDir . '/' . $report['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $sql = "DELETE FROM reports WHERE report_id = ?";
        $result = delete($sql, [$reportId]);
        
        if ($result) {
            $_SESSION['success'] = "Report deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete report.";
        }
    } else {
        $_SESSION['error'] = "Report not found.";
    }
    
    header("Location: reports.php");
    exit();
}

// Handle update featured status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_featured' && isset($_GET['id'])) {
    $reportId = intval($_GET['id']);
    
    // Check permission
    if (!hasPermission('update', 'reports')) {
        $_SESSION['error'] = "You don't have permission to update reports.";
        header("Location: reports.php");
        exit();
    }
    
    // Get current featured status
    $sql = "SELECT featured FROM reports WHERE report_id = ?";
    $report = fetchOne($sql, [$reportId]);
    
    if ($report) {
        // Toggle featured status
        $newStatus = $report['featured'] ? 0 : 1;
        
        // Update database
        $sql = "UPDATE reports SET featured = ? WHERE report_id = ?";
        $result = update($sql, [$newStatus, $reportId]);
        
        if ($result) {
            $message = $newStatus ? "Report marked as featured." : "Report removed from featured.";
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['error'] = "Failed to update report status.";
        }
    } else {
        $_SESSION['error'] = "Report not found.";
    }
    
    header("Location: reports.php");
    exit();
} 