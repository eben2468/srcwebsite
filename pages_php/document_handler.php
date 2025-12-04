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

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if documents feature is enabled
if (!hasFeaturePermission('enable_documents')) {
    $_SESSION['error'] = "The document repository feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Create uploads directory if it doesn't exist
$uploadsDir = '../uploads/documents';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Handle file upload
if (isset($_POST['action']) && $_POST['action'] === 'upload') {
    // Check permission
    if (!hasPermission('create', 'documents')) {
        $_SESSION['error'] = "You don't have permission to upload documents.";
        header("Location: documents.php");
        exit();
    }

    // Get form data
    $title = $_POST['document_title'] ?? '';
    $category = $_POST['document_category'] ?? '';
    $description = $_POST['document_description'] ?? '';

    // Validate input
    if (empty($title) || empty($category)) {
        $_SESSION['error'] = "Title and category are required.";
        header("Location: documents.php");
        exit();
    }

    // Check if file was uploaded successfully
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document_file'];
        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        
        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        
        // Check file type
        if (!in_array($fileExtension, $allowedExtensions)) {
            $_SESSION['error'] = "Invalid file type. Allowed types: " . implode(', ', $allowedExtensions);
            header("Location: documents.php");
            exit();
        }
        
        // Check file size (15MB max)
        if ($fileSize > 15 * 1024 * 1024) {
            $_SESSION['error'] = "File is too large. Maximum size is 15MB.";
            header("Location: documents.php");
            exit();
        }
        
        // Create unique filename
        $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-]/', '_', $fileName);
        $uploadFilePath = $uploadsDir . '/' . $newFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
            // Format file size for display
            $formattedSize = formatFileSize($fileSize);
            
            // Get current user ID
            $currentUser = getCurrentUser();
            $uploadedBy = $currentUser['user_id'];
            
            // Insert into database
            $sql = "INSERT INTO documents (title, description, file_path, file_size, document_type, category, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $title,
                $description,
                $newFileName,
                $fileSize,
                $fileExtension,
                $category,
                $uploadedBy
            ];
            
            $insertId = insert($sql, $params);
            
            if ($insertId) {
                $_SESSION['success'] = "Document uploaded successfully.";

                // Send notification to all users about new document
                autoNotifyDocumentUploaded($title, $category, $uploadedBy, $insertId);
            } else {
                // Delete the file if database insert failed
                unlink($uploadFilePath);
                $_SESSION['error'] = "Failed to save document information.";
            }
        } else {
            $_SESSION['error'] = "Failed to upload file.";
        }
    } else {
        $_SESSION['error'] = "Please select a file to upload.";
    }
    
    header("Location: documents.php");
    exit();
}

// Handle file download
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['id'])) {
    $documentId = intval($_GET['id']);

    // Get document information first to check category
    $sql = "SELECT * FROM documents WHERE document_id = ? AND status = 'active'";
    $document = fetchOne($sql, [$documentId]);

    if (!$document) {
        $_SESSION['error'] = "Document not found.";
        header("Location: documents.php");
        exit();
    }

    // Define categories that students can access
    $studentAccessibleCategories = ['general', 'legal', 'bylaws', 'elections', 'events'];

    // Check permission based on user role and document category
    $canDownload = false;

    if (shouldUseAdminInterface() || isMember()) {
        // Admin interface users and members can download all documents
        $canDownload = true;
    } elseif (isLoggedIn()) {
        // Students can only download documents from specific categories
        $documentCategory = strtolower(trim($document['category']));
        if (in_array($documentCategory, $studentAccessibleCategories)) {
            $canDownload = true;
        }
    }

    if (!$canDownload) {
        $_SESSION['error'] = "You don't have permission to download documents.";
        header("Location: documents.php");
        exit();
    }

    $filePath = $uploadsDir . '/' . $document['file_path'];

    if (file_exists($filePath)) {
        // Set headers for download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($document['file_path']) . '"');
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
        header("Location: documents.php");
        exit();
    }
}

// Handle file deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $documentId = intval($_GET['id']);
    
    // Check permission
    if (!shouldUseAdminInterface()) {
        $_SESSION['error'] = "You don't have permission to delete documents.";
        header("Location: documents.php");
        exit();
    }
    
    // Get document information
    $sql = "SELECT * FROM documents WHERE document_id = ?";
    $document = fetchOne($sql, [$documentId]);
    
    if ($document) {
        // Delete file from server
        $filePath = $uploadsDir . '/' . $document['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $sql = "DELETE FROM documents WHERE document_id = ?";
        $result = delete($sql, [$documentId]);
        
        if ($result) {
            $_SESSION['success'] = "Document deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete document.";
        }
    } else {
        $_SESSION['error'] = "Document not found.";
    }
    
    header("Location: documents.php");
    exit();
}

/**
 * Format file size for display
 *
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
} 
