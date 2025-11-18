<?php
// Include required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login
requireLogin();

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Handle different actions
switch ($action) {
    case 'submit_request':
        handleSubmitRequest();
        break;
    case 'create_announcement':
        handleCreateAnnouncement();
        break;
    case 'update_status':
        handleUpdateStatus();
        break;
    case 'get_request':
        handleGetRequest();
        break;
    case 'get_my_requests':
        handleGetMyRequests();
        break;
    case 'generate_report':
        handleGenerateReport();
        break;
    case 'export_data':
        handleExportData();
        break;
    case 'export_settings':
        handleExportSettings();
        break;
    case 'export_all_data':
        handleExportAllData();
        break;
    case 'create_backup':
        handleCreateBackup();
        break;
    case 'clear_old_requests':
        handleClearOldRequests();
        break;
    default:
        $_SESSION['error'] = "Invalid action.";
        header("Location: welfare.php");
        exit();
}

function handleSubmitRequest() {
    // Check if user is student - use unified admin interface check for super admin
    if (shouldUseAdminInterface() || isMember()) {
        $_SESSION['error'] = "Only students can submit welfare requests.";
        header("Location: welfare.php");
        exit();
    }
    
    $userId = $_SESSION['user_id'];
    $requestType = $_POST['request_type'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $description = $_POST['description'] ?? '';
    $urgency = $_POST['urgency'] ?? 'medium';
    
    // Validate required fields
    if (empty($requestType) || empty($subject) || empty($description)) {
        $_SESSION['error'] = "All required fields must be filled.";
        header("Location: welfare.php");
        exit();
    }
    
    // Handle file uploads
    $uploadedFiles = [];
    if (isset($_FILES['supporting_documents']) && !empty($_FILES['supporting_documents']['name'][0])) {
        $uploadDir = '../uploads/welfare/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['supporting_documents']['name'] as $key => $filename) {
            if (!empty($filename)) {
                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                
                if (in_array(strtolower($fileExtension), $allowedExtensions)) {
                    $newFilename = uniqid() . '_' . $filename;
                    $uploadPath = $uploadDir . $newFilename;
                    
                    if (move_uploaded_file($_FILES['supporting_documents']['tmp_name'][$key], $uploadPath)) {
                        $uploadedFiles[] = $newFilename;
                    }
                }
            }
        }
    }
    
    // Insert request into database
    $sql = "INSERT INTO welfare_requests (user_id, request_type, subject, description, urgency, supporting_documents, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $documentsJson = json_encode($uploadedFiles);
    
    if (execute($sql, [$userId, $requestType, $subject, $description, $urgency, $documentsJson])) {
        $_SESSION['success'] = "Your welfare request has been submitted successfully.";

        // Send notification to admins and members about new welfare request
        autoNotifyWelfareRequest($subject, $requestType, $userId, getLastInsertId());
    } else {
        $_SESSION['error'] = "Error submitting your request. Please try again.";
    }
    
    header("Location: welfare.php");
    exit();
}

function handleCreateAnnouncement() {
    // Check if user is admin or member - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        $_SESSION['error'] = "You don't have permission to create announcements.";
        header("Location: welfare.php");
        exit();
    }
    
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $priority = $_POST['priority'] ?? 'normal';
    $createdBy = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Title and content are required.";
        header("Location: welfare.php");
        exit();
    }
    
    // Insert announcement into database
    $sql = "INSERT INTO welfare_announcements (title, content, priority, created_by, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())";
    
    if (execute($sql, [$title, $content, $priority, $createdBy])) {
        $_SESSION['success'] = "Announcement created successfully.";
    } else {
        $_SESSION['error'] = "Error creating announcement. Please try again.";
    }
    
    header("Location: welfare.php");
    exit();
}

function handleUpdateStatus() {
    // Check if user is admin or member - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
    
    $requestId = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $updatedBy = $_SESSION['user_id'];
    
    // Validate inputs
    $allowedStatuses = ['pending', 'approved', 'rejected', 'in_progress'];
    if (empty($requestId) || !in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }
    
    // Update request status
    $sql = "UPDATE welfare_requests SET status = ?, updated_by = ?, updated_at = NOW() WHERE request_id = ?";
    
    if (execute($sql, [$status, $updatedBy, $requestId])) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
    }
    exit();
}

function handleGetRequest() {
    $requestId = $_GET['id'] ?? '';
    
    if (empty($requestId)) {
        echo json_encode(['success' => false, 'message' => 'Request ID required']);
        exit();
    }
    
    // Get request details
    $sql = "SELECT wr.*, CONCAT(u.first_name, ' ', u.last_name) as requester_name, u.email as requester_email,
                   CONCAT(ub.first_name, ' ', ub.last_name) as updated_by_name
            FROM welfare_requests wr
            LEFT JOIN users u ON wr.user_id = u.user_id
            LEFT JOIN users ub ON wr.updated_by = ub.user_id
            WHERE wr.request_id = ?";
    
    $request = fetchOne($sql, [$requestId]);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit();
    }
    
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember() && $request['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
    
    // Generate HTML content
    $html = generateRequestDetailsHTML($request);
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}

function handleGetMyRequests() {
    // Only for students - use unified admin interface check for super admin
    if (shouldUseAdminInterface() || isMember()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }
    
    $userId = $_SESSION['user_id'];
    
    // Get user's requests
    $sql = "SELECT * FROM welfare_requests WHERE user_id = ? ORDER BY created_at DESC";
    $requests = fetchAll($sql, [$userId]);
    
    // Generate HTML content
    $html = generateMyRequestsHTML($requests);
    
    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}

function handleGenerateReport() {
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        $_SESSION['error'] = "Permission denied.";
        header("Location: welfare.php");
        exit();
    }
    
    $type = $_GET['type'] ?? '';
    
    if ($type === 'statistics') {
        generateStatisticsReport();
    } else {
        $_SESSION['error'] = "Invalid report type.";
        header("Location: welfare.php");
        exit();
    }
}

function handleExportData() {
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        $_SESSION['error'] = "Permission denied.";
        header("Location: welfare.php");
        exit();
    }
    
    // Get all welfare requests
    $sql = "SELECT wr.*, CONCAT(u.first_name, ' ', u.last_name) as requester_name, u.email as requester_email
            FROM welfare_requests wr
            LEFT JOIN users u ON wr.user_id = u.user_id
            ORDER BY wr.created_at DESC";
    $requests = fetchAll($sql);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="welfare_requests_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['ID', 'Requester', 'Email', 'Type', 'Subject', 'Status', 'Urgency', 'Created Date']);
    
    // CSV data
    foreach ($requests as $request) {
        fputcsv($output, [
            $request['request_id'],
            $request['requester_name'],
            $request['requester_email'],
            $request['request_type'],
            $request['subject'],
            $request['status'],
            $request['urgency'],
            $request['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

function generateRequestDetailsHTML($request) {
    $supportingDocs = json_decode($request['supporting_documents'], true) ?: [];
    
    $html = '<div class="row">';
    $html .= '<div class="col-md-6">';
    $html .= '<h6>Request Information</h6>';
    $html .= '<p><strong>ID:</strong> #' . $request['request_id'] . '</p>';
    $html .= '<p><strong>Type:</strong> ' . htmlspecialchars($request['request_type']) . '</p>';
    $html .= '<p><strong>Subject:</strong> ' . htmlspecialchars($request['subject']) . '</p>';
    $html .= '<p><strong>Urgency:</strong> <span class="badge bg-warning">' . ucfirst($request['urgency']) . '</span></p>';
    $html .= '<p><strong>Status:</strong> <span class="badge bg-info">' . ucfirst($request['status']) . '</span></p>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6">';
    $html .= '<h6>Requester Information</h6>';
    $html .= '<p><strong>Name:</strong> ' . htmlspecialchars($request['requester_name']) . '</p>';
    $html .= '<p><strong>Email:</strong> ' . htmlspecialchars($request['requester_email']) . '</p>';
    $html .= '<p><strong>Submitted:</strong> ' . date('M j, Y g:i A', strtotime($request['created_at'])) . '</p>';
    if ($request['updated_at']) {
        $html .= '<p><strong>Last Updated:</strong> ' . date('M j, Y g:i A', strtotime($request['updated_at'])) . '</p>';
    }
    $html .= '</div>';
    
    $html .= '</div>';
    
    $html .= '<div class="mt-3">';
    $html .= '<h6>Description</h6>';
    $html .= '<p>' . nl2br(htmlspecialchars($request['description'])) . '</p>';
    $html .= '</div>';
    
    if (!empty($supportingDocs)) {
        $html .= '<div class="mt-3">';
        $html .= '<h6>Supporting Documents</h6>';
        foreach ($supportingDocs as $doc) {
            $html .= '<a href="../uploads/welfare/' . $doc . '" target="_blank" class="btn btn-sm btn-outline-primary me-2 mb-2">';
            $html .= '<i class="fas fa-file me-1"></i>' . $doc . '</a>';
        }
        $html .= '</div>';
    }
    
    return $html;
}

function generateMyRequestsHTML($requests) {
    if (empty($requests)) {
        return '<div class="alert alert-info">You have not submitted any welfare requests yet.</div>';
    }
    
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-hover">';
    $html .= '<thead><tr><th>ID</th><th>Type</th><th>Subject</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($requests as $request) {
        $statusClass = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'in_progress' => 'info'
        ];
        $class = $statusClass[$request['status']] ?? 'secondary';
        
        $html .= '<tr>';
        $html .= '<td>#' . $request['request_id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($request['request_type']) . '</td>';
        $html .= '<td>' . htmlspecialchars($request['subject']) . '</td>';
        $html .= '<td><span class="badge bg-' . $class . '">' . ucfirst($request['status']) . '</span></td>';
        $html .= '<td>' . date('M j, Y', strtotime($request['created_at'])) . '</td>';
        $html .= '<td><button class="btn btn-sm btn-outline-primary" onclick="viewRequest(' . $request['request_id'] . ')"><i class="fas fa-eye"></i></button></td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></div>';
    
    return $html;
}

function generateStatisticsReport() {
    // Get statistics data
    $stats = [
        'total' => fetchOne("SELECT COUNT(*) as count FROM welfare_requests")['count'],
        'pending' => fetchOne("SELECT COUNT(*) as count FROM welfare_requests WHERE status = 'pending'")['count'],
        'approved' => fetchOne("SELECT COUNT(*) as count FROM welfare_requests WHERE status = 'approved'")['count'],
        'rejected' => fetchOne("SELECT COUNT(*) as count FROM welfare_requests WHERE status = 'rejected'")['count']
    ];
    
    // Generate simple HTML report
    header('Content-Type: text/html');
    echo '<html><head><title>Welfare Statistics Report</title></head><body>';
    echo '<h1>Welfare Statistics Report</h1>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<ul>';
    echo '<li>Total Requests: ' . $stats['total'] . '</li>';
    echo '<li>Pending: ' . $stats['pending'] . '</li>';
    echo '<li>Approved: ' . $stats['approved'] . '</li>';
    echo '<li>Rejected: ' . $stats['rejected'] . '</li>';
    echo '</ul>';
    echo '</body></html>';
    exit();
}

function handleExportSettings() {
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        $_SESSION['error'] = "Permission denied.";
        header("Location: welfare_settings.php");
        exit();
    }

    // Get all settings
    $settings = fetchAll("SELECT * FROM welfare_settings ORDER BY setting_key");

    // Set headers for JSON download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="welfare_settings_' . date('Y-m-d') . '.json"');

    echo json_encode($settings, JSON_PRETTY_PRINT);
    exit();
}

function handleExportAllData() {
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        $_SESSION['error'] = "Permission denied.";
        header("Location: welfare_settings.php");
        exit();
    }

    // Get all welfare data
    $data = [
        'requests' => fetchAll("SELECT wr.*, CONCAT(u.first_name, ' ', u.last_name) as requester_name, u.email as requester_email FROM welfare_requests wr LEFT JOIN users u ON wr.user_id = u.user_id ORDER BY wr.created_at DESC"),
        'announcements' => fetchAll("SELECT wa.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name FROM welfare_announcements wa LEFT JOIN users u ON wa.created_by = u.user_id ORDER BY wa.created_at DESC"),
        'categories' => fetchAll("SELECT * FROM welfare_categories ORDER BY category_name"),
        'settings' => fetchAll("SELECT * FROM welfare_settings ORDER BY setting_key"),
        'comments' => fetchAll("SELECT wrc.*, CONCAT(u.first_name, ' ', u.last_name) as commenter_name FROM welfare_request_comments wrc LEFT JOIN users u ON wrc.user_id = u.user_id ORDER BY wrc.created_at DESC")
    ];

    // Set headers for JSON download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="welfare_complete_data_' . date('Y-m-d') . '.json"');

    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function handleCreateBackup() {
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        $_SESSION['error'] = "Permission denied.";
        header("Location: welfare_settings.php");
        exit();
    }

    // Generate SQL backup
    $tables = ['welfare_requests', 'welfare_announcements', 'welfare_categories', 'welfare_settings', 'welfare_request_comments'];
    $backup = "-- Welfare System Backup\n";
    $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $backup .= "-- Table: $table\n";
        $backup .= "DROP TABLE IF EXISTS `{$table}_backup_" . date('Ymd') . "`;\n";
        $backup .= "CREATE TABLE `{$table}_backup_" . date('Ymd') . "` LIKE `$table`;\n";
        $backup .= "INSERT INTO `{$table}_backup_" . date('Ymd') . "` SELECT * FROM `$table`;\n\n";
    }

    // Set headers for SQL download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="welfare_backup_' . date('Y-m-d_H-i-s') . '.sql"');

    echo $backup;
    exit();
}

function handleClearOldRequests() {
    // Check permissions - use unified admin interface check for super admin
    if (!shouldUseAdminInterface() && !isMember()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit();
    }

    $days = $_POST['days'] ?? 90;

    if (!is_numeric($days) || $days <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid number of days']);
        exit();
    }

    // Delete old requests
    $sql = "DELETE FROM welfare_requests WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND status IN ('approved', 'rejected')";

    if (execute($sql, [$days])) {
        global $conn;
        $deletedCount = mysqli_affected_rows($conn);
        echo json_encode(['success' => true, 'deleted_count' => $deletedCount]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting old requests']);
    }
    exit();
}
?>
