<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check for admin or member status (including super admin)
$isAdmin = isAdmin();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();
$shouldUseAdminInterface = shouldUseAdminInterface();

// Restrict access to super admin, admins and members only
if (!$shouldUseAdminInterface && !$isMember) {
    $_SESSION['error'] = "You do not have permission to access meeting minutes.";
    header("Location: senate.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No minutes ID provided.";
    header("Location: minutes.php");
    exit();
}

$minutesId = intval($_GET['id']);

// Get minutes data
$sql = "SELECT m.*, u.first_name, u.last_name 
        FROM minutes m 
        LEFT JOIN users u ON m.created_by = u.user_id 
        WHERE m.minutes_id = ?";
$minutes = fetchOne($sql, [$minutesId]);

// Check if minutes exists
if (!$minutes) {
    $_SESSION['error'] = "Minutes not found.";
    header("Location: minutes.php");
    exit();
}

// Set page title
$pageTitle = "Minutes Detail: " . $minutes['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Display success/error messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php
    // Define page title, icon, and actions for the modern header
    $pageTitle = "Minutes Detail";
    $pageIcon = "fa-clipboard";
    $pageDescription = "View detailed meeting minutes and records";
    $actions = [];

    if ($shouldUseAdminInterface || $isMember) {
        $actions[] = [
            'url' => 'minutes_edit.php?id=' . $minutesId,
            'icon' => 'fa-edit',
            'text' => 'Edit Minutes',
            'class' => 'btn-secondary'
        ];

        if (!empty($minutes['file_path'])) {
            $actions[] = [
                'url' => 'minutes_handler.php?action=download&id=' . $minutesId,
                'icon' => 'fa-download',
                'text' => 'Download File',
                'class' => 'btn-primary'
            ];
        }
    }

    $actions[] = [
        'url' => 'minutes.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Minutes',
        'class' => 'btn-outline-light'
    ];

    // Include the modern page header
    include_once 'includes/modern_page_header.php';
    ?>

    <div class="minutes-detail-container">
        <div class="minutes-detail-header">
            <h2><?php echo htmlspecialchars($minutes['title']); ?></h2>
            
            <div class="minutes-meta">
                <div class="minutes-meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F d, Y', strtotime($minutes['meeting_date'])); ?></span>
                </div>
                <div class="minutes-meta-item">
                    <i class="fas fa-users"></i>
                    <span><?php echo htmlspecialchars($minutes['committee']); ?></span>
                </div>
                <div class="minutes-meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($minutes['location']); ?></span>
                </div>
                <div class="minutes-meta-item">
                    <span class="badge <?php echo $minutes['status'] === 'Approved' ? 'status-badge-approved' : 'status-badge-draft'; ?>">
                        <?php echo htmlspecialchars($minutes['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="minutes-content">
                    <div class="minutes-section">
                        <h3>Summary</h3>
                        <p><?php echo nl2br(htmlspecialchars($minutes['summary'])); ?></p>
                    </div>
                    
                    <div class="minutes-section">
                        <h3>Attendees</h3>
                        <ul class="attendees-list">
                            <?php 
                            $attendeesList = explode(',', $minutes['attendees']);
                            foreach ($attendeesList as $attendee): 
                                $attendee = trim($attendee);
                                if (!empty($attendee)):
                            ?>
                            <li><?php echo htmlspecialchars($attendee); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                    
                    <?php if (!empty($minutes['apologies'])): ?>
                    <div class="minutes-section">
                        <h3>Apologies</h3>
                        <ul class="attendees-list">
                            <?php 
                            $apologiesList = explode(',', $minutes['apologies']);
                            foreach ($apologiesList as $apology): 
                                $apology = trim($apology);
                                if (!empty($apology)):
                            ?>
                            <li><?php echo htmlspecialchars($apology); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="minutes-section">
                        <h3>Agenda</h3>
                        <pre class="border rounded p-3 bg-light"><?php echo htmlspecialchars($minutes['agenda']); ?></pre>
                    </div>
                    
                    <div class="minutes-section">
                        <h3>Decisions</h3>
                        <pre class="border rounded p-3 bg-light"><?php echo htmlspecialchars($minutes['decisions']); ?></pre>
                    </div>
                    
                    <div class="minutes-section">
                        <h3>Action Items</h3>
                        <div class="action-items-list">
                            <?php 
                            $actionItems = explode("\n", $minutes['actions']);
                            foreach ($actionItems as $actionItem): 
                                $actionItem = trim($actionItem);
                                if (!empty($actionItem)):
                            ?>
                            <div class="action-item">
                                <i class="fas fa-tasks me-2"></i>
                                <?php echo htmlspecialchars($actionItem); ?>
                            </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="minutes-section">
                        <h3>Next Meeting</h3>
                        <div class="minutes-meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('F d, Y', strtotime($minutes['next_meeting_date'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Metadata</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <span class="fw-bold">Created by:</span> 
                                <?php 
                                if ($minutes['first_name'] && $minutes['last_name']) {
                                    echo htmlspecialchars($minutes['first_name'] . ' ' . $minutes['last_name']);
                                } else {
                                    echo 'Unknown';
                                }
                                ?>
                            </li>
                            <li class="list-group-item">
                                <span class="fw-bold">Created on:</span> 
                                <?php echo date('M d, Y H:i', strtotime($minutes['created_at'])); ?>
                            </li>
                            <?php if ($minutes['updated_at'] !== $minutes['created_at']): ?>
                            <li class="list-group-item">
                                <span class="fw-bold">Last updated:</span> 
                                <?php echo date('M d, Y H:i', strtotime($minutes['updated_at'])); ?>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($minutes['file_path'])): ?>
                            <li class="list-group-item">
                                <span class="fw-bold">Attached file:</span> 
                                <span class="d-block mt-1">
                                    <i class="fas fa-file-<?php echo getFileIcon($minutes['file_type']); ?>"></i>
                                    <?php echo strtoupper($minutes['file_type']); ?> 
                                    (<?php echo formatFileSize($minutes['file_size']); ?>)
                                </span>
                                <a href="minutes_handler.php?action=download&id=<?php echo $minutes['minutes_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-download me-1"></i> Download
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <?php if ($shouldUseAdminInterface || $isMember): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="minutes_edit.php?id=<?php echo $minutes['minutes_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit me-1"></i> Edit Minutes
                            </a>
                            <a href="minutes_handler.php?action=delete&id=<?php echo $minutes['minutes_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete these minutes?')">
                                <i class="fas fa-trash me-1"></i> Delete Minutes
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Get appropriate file icon based on file type
 *
 * @param string $fileType The file type/extension
 * @return string Icon name
 */
function getFileIcon($fileType) {
    switch ($fileType) {
        case 'pdf':
            return 'pdf';
        case 'doc':
        case 'docx':
            return 'word';
        case 'xls':
        case 'xlsx':
            return 'excel';
        case 'ppt':
        case 'pptx':
            return 'powerpoint';
        case 'txt':
            return 'alt';
        default:
            return 'document';
    }
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

require_once 'includes/footer.php';
?> 
