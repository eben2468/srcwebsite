<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/minutes';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle adding new minutes
if (isset($_POST['add_minutes'])) {
    // Check permission
    if (!hasPermission('create', 'minutes')) {
        $_SESSION['error'] = "You don't have permission to add meeting minutes.";
        header("Location: minutes.php");
        exit();
    }

    // Get form data
    $title = $_POST['title'] ?? '';
    $committee = $_POST['committee'] ?? '';
    $date = $_POST['meeting_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $attendees = $_POST['attendees'] ?? '';
    $apologies = $_POST['apologies'] ?? '';
    $agenda = $_POST['agenda'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $decisions = $_POST['decisions'] ?? '';
    $actions = $_POST['actions'] ?? '';
    $next_meeting = $_POST['next_meeting_date'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    // Validate input
    if (empty($title) || empty($committee) || empty($date)) {
        $_SESSION['error'] = "Title, committee and date are required.";
        header("Location: minutes.php");
        exit();
    }

    // Get current user ID
    $currentUser = getCurrentUser();
    $createdBy = $currentUser['user_id'];
    
    // Check if we need to upload a file
    $filePath = null;
    $fileSize = null;
    $fileType = null;
    
    if (isset($_FILES['minutes_file']) && $_FILES['minutes_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['minutes_file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Get file extension
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
        
        // Check file type
        if (!in_array($fileType, $allowedExtensions)) {
            $_SESSION['error'] = "Invalid file type. Allowed types: " . implode(', ', $allowedExtensions);
            header("Location: minutes.php");
            exit();
        }
        
        // Check file size (5MB max)
        if ($fileSize > 5 * 1024 * 1024) {
            $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
            header("Location: minutes.php");
            exit();
        }
        
        // Create unique filename
        $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $fileName);
        $uploadFilePath = $uploadsDir . '/' . $newFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
            $filePath = $newFileName;
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            header("Location: minutes.php");
            exit();
        }
    }

    // Insert into database
    $sql = "INSERT INTO minutes (title, committee, meeting_date, location, attendees, apologies, agenda, summary, decisions, actions, next_meeting_date, status, file_path, file_size, file_type, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $title,
        $committee,
        $date,
        $location,
        $attendees,
        $apologies,
        $agenda,
        $summary,
        $decisions,
        $actions,
        $next_meeting,
        $status,
        $filePath,
        $fileSize,
        $fileType,
        $createdBy
    ];
    
    $insertId = insert($sql, $params);
    
    if ($insertId) {
        $_SESSION['success'] = "Meeting minutes added successfully.";

        // Send notification to members and admins about new meeting minutes
        autoNotifyMinutesCreated($title, $date, $createdBy, $insertId);
    } else {
        // Delete the file if database insert failed
        if ($filePath && file_exists($uploadsDir . '/' . $filePath)) {
            unlink($uploadsDir . '/' . $filePath);
        }
        $_SESSION['error'] = "Failed to save meeting minutes.";
    }
    
    header("Location: minutes.php");
    exit();
}

// Handle edit minutes
if (isset($_POST['edit_minutes'])) {
    // Check permission
    if (!hasPermission('update', 'minutes')) {
        $_SESSION['error'] = "You don't have permission to edit meeting minutes.";
        header("Location: minutes.php");
        exit();
    }

    $minutesId = intval($_POST['minutes_id']) ?? 0;
    
    // Get form data
    $title = $_POST['title'] ?? '';
    $committee = $_POST['committee'] ?? '';
    $date = $_POST['meeting_date'] ?? '';
    $location = $_POST['location'] ?? '';
    $attendees = $_POST['attendees'] ?? '';
    $apologies = $_POST['apologies'] ?? '';
    $agenda = $_POST['agenda'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $decisions = $_POST['decisions'] ?? '';
    $actions = $_POST['actions'] ?? '';
    $next_meeting = $_POST['next_meeting_date'] ?? '';
    $status = $_POST['status'] ?? 'Draft';

    // Validate input
    if (empty($title) || empty($committee) || empty($date)) {
        $_SESSION['error'] = "Title, committee and date are required.";
        header("Location: minutes.php");
        exit();
    }
    
    // Get existing minutes information
    $sql = "SELECT * FROM minutes WHERE minutes_id = ?";
    $minutes = fetchOne($sql, [$minutesId]);
    
    if (!$minutes) {
        $_SESSION['error'] = "Minutes not found.";
        header("Location: minutes.php");
        exit();
    }
    
    // Check if we need to upload a new file
    $filePath = $minutes['file_path'];
    $fileSize = $minutes['file_size'];
    $fileType = $minutes['file_type'];
    
    if (isset($_FILES['minutes_file']) && $_FILES['minutes_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['minutes_file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Get file extension
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt'];
        
        // Check file type
        if (!in_array($fileType, $allowedExtensions)) {
            $_SESSION['error'] = "Invalid file type. Allowed types: " . implode(', ', $allowedExtensions);
            header("Location: minutes_edit.php?id=" . $minutesId);
            exit();
        }
        
        // Check file size (5MB max)
        if ($fileSize > 5 * 1024 * 1024) {
            $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
            header("Location: minutes_edit.php?id=" . $minutesId);
            exit();
        }
        
        // Create unique filename
        $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $fileName);
        $uploadFilePath = $uploadsDir . '/' . $newFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
            // Delete the old file if it exists
            if ($minutes['file_path'] && file_exists($uploadsDir . '/' . $minutes['file_path'])) {
                unlink($uploadsDir . '/' . $minutes['file_path']);
            }
            $filePath = $newFileName;
        } else {
            $_SESSION['error'] = "Failed to upload file.";
            header("Location: minutes_edit.php?id=" . $minutesId);
            exit();
        }
    }

    // Update the database
    $sql = "UPDATE minutes SET 
            title = ?, 
            committee = ?, 
            meeting_date = ?, 
            location = ?, 
            attendees = ?, 
            apologies = ?, 
            agenda = ?, 
            summary = ?, 
            decisions = ?, 
            actions = ?, 
            next_meeting_date = ?, 
            status = ?, 
            file_path = ?, 
            file_size = ?, 
            file_type = ?,
            updated_at = NOW()
            WHERE minutes_id = ?";
    
    $params = [
        $title,
        $committee,
        $date,
        $location,
        $attendees,
        $apologies,
        $agenda,
        $summary,
        $decisions,
        $actions,
        $next_meeting,
        $status,
        $filePath,
        $fileSize,
        $fileType,
        $minutesId
    ];
    
    $result = update($sql, $params);
    
    if ($result) {
        $_SESSION['success'] = "Meeting minutes updated successfully.";
        header("Location: minutes_detail.php?id=" . $minutesId);
    } else {
        $_SESSION['error'] = "Failed to update meeting minutes.";
        header("Location: minutes_edit.php?id=" . $minutesId);
    }
    
    exit();
}

// Handle file download
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $minutesId = intval($_GET['id']);
    
    // Check permission (include super admin)
    if (!(shouldUseAdminInterface() || isMember())) {
        $_SESSION['error'] = "You don't have permission to download minutes.";
        header("Location: minutes.php");
        exit();
    }
    
    // Get minutes information
    $sql = "SELECT * FROM minutes WHERE minutes_id = ? AND file_path IS NOT NULL";
    $minutes = fetchOne($sql, [$minutesId]);
    
    if ($minutes && !empty($minutes['file_path'])) {
        $filePath = $uploadsDir . '/' . $minutes['file_path'];
        
        if (file_exists($filePath)) {
            // Set headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($minutes['file_path']) . '"');
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
            header("Location: minutes.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "No file attached to these minutes.";
        header("Location: minutes.php");
        exit();
    }
}

// Handle minutes deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $minutesId = intval($_GET['id']);
    
    // Check permission (include super admin)
    if (!shouldUseAdminInterface()) {
        $_SESSION['error'] = "You don't have permission to delete minutes.";
        header("Location: minutes.php");
        exit();
    }
    
    // Get minutes information
    $sql = "SELECT * FROM minutes WHERE minutes_id = ?";
    $minutes = fetchOne($sql, [$minutesId]);
    
    if ($minutes) {
        // Delete file from server if it exists
        if (!empty($minutes['file_path'])) {
            $filePath = $uploadsDir . '/' . $minutes['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete from database
        $sql = "DELETE FROM minutes WHERE minutes_id = ?";
        $result = delete($sql, [$minutesId]);
        
        if ($result) {
            $_SESSION['success'] = "Minutes deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete minutes.";
        }
    } else {
        $_SESSION['error'] = "Minutes not found.";
    }
    
    header("Location: minutes.php");
    exit();
}

/**
 * Format file size for display
 *
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if (empty($bytes)) return 'N/A';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
} 
