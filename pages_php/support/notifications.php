<?php
// Include simple authentication
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';

// Include settings functions
require_once __DIR__ . '/../../includes/settings_functions.php';

// Include notification helper
require_once __DIR__ . '/notification_helper.php';

// Require login
requireLogin();

// Check if notifications feature is enabled
if (!hasFeaturePermission('enable_notifications')) {
    $_SESSION['error'] = "The notifications feature is currently disabled.";
    header("Location: ../dashboard.php");
    exit();
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Notifications - " . $siteName;
$bodyClass = "page-notifications";

// Get current user info
$currentUser = getCurrentUser();
$shouldUseAdminInterface = shouldUseAdminInterface();
$isAdmin = $shouldUseAdminInterface;
$isMember = isMember();

// Handle notification creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_notification'])) {
    if ($shouldUseAdminInterface || $isMember) {
        $title = trim($_POST['notification_title'] ?? '');
        $message = trim($_POST['notification_message'] ?? '');
        $type = $_POST['notification_type'] ?? 'info';
        $audience = $_POST['target_audience'] ?? 'all';
        $action_url = trim($_POST['action_url'] ?? '');

        if (!empty($title) && !empty($message)) {
            // Get target users
            $target_users = getTargetUsers($audience, $currentUser['user_id']);

            if (!empty($target_users)) {
                // Create notifications for all target users
                $success_count = createBulkNotifications(
                    $target_users,
                    $title,
                    $message,
                    $type,
                    !empty($action_url) ? $action_url : null
                );

                if ($success_count > 0) {
                    $_SESSION['success_message'] = "Notification sent successfully to {$success_count} users!";
                } else {
                    $_SESSION['error_message'] = "Failed to send notifications. Please try again.";
                }
            } else {
                $_SESSION['error_message'] = "No target users found for the selected audience.";
            }
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields.";
        }
    } else {
        $_SESSION['error_message'] = "You don't have permission to create notifications.";
    }

    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Function to create notifications table if it doesn't exist
function createNotificationsTable($conn, $user_id) {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error', 'system', 'events') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at),
        INDEX idx_type (type)
    )";

    if (mysqli_query($conn, $sql)) {
        // Add sample notifications for the current user
        // Use the user_id parameter passed to the function

        $sampleNotifications = [
            ['Welcome to the System', 'Welcome to the VVU SRC Management System! Get started by exploring the dashboard.', 'success'],
            ['System Maintenance', 'Scheduled maintenance will occur this weekend from 2 AM to 4 AM.', 'warning'],
            ['New Feature Available', 'Check out the new reporting features in the Finance section.', 'info'],
            ['Event Reminder', 'Don\'t forget about the upcoming SRC meeting tomorrow at 3 PM.', 'events'],
            ['Profile Update Required', 'Please update your profile information to ensure accurate records.', 'info']
        ];

        foreach ($sampleNotifications as $notification) {
            $insertSql = "INSERT IGNORE INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insertSql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isss", $user_id, $notification[0], $notification[1], $notification[2]);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        return true;
    }
    return false;
}

// Get user ID - check both possible field names
$user_id = isset($currentUser['user_id']) ? $currentUser['user_id'] : (isset($currentUser['id']) ? $currentUser['id'] : 0);

// Ensure notifications table exists
$table_check = "SHOW TABLES LIKE 'notifications'";
$table_result = mysqli_query($conn, $table_check);

if (!$table_result || mysqli_num_rows($table_result) == 0) {
    createNotificationsTable($conn, $user_id);
}

// Handle notification actions
if ($_POST) {

    if ($user_id > 0) {
        if (isset($_POST['mark_read'])) {
            $notification_id = (int)$_POST['notification_id'];
            $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } elseif (isset($_POST['mark_all_read'])) {
            $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } elseif (isset($_POST['delete_notification'])) {
            $notification_id = (int)$_POST['notification_id'];
            $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get notifications for current user
$notifications = [];
$unread_count = 0;

// Get user ID - check both possible field names
$user_id = isset($currentUser['user_id']) ? $currentUser['user_id'] : (isset($currentUser['id']) ? $currentUser['id'] : 0);

// Check if notifications table exists (it should exist now after our setup)
$table_check = "SHOW TABLES LIKE 'notifications'";
$table_result = mysqli_query($conn, $table_check);

if ($table_result && mysqli_num_rows($table_result) > 0 && $user_id > 0) {
        // Get notifications for current user
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $notifications_result = mysqli_stmt_get_result($stmt);

            if ($notifications_result) {
                while ($row = mysqli_fetch_assoc($notifications_result)) {
                    $notifications[] = $row;
                }
            }
            mysqli_stmt_close($stmt);
        }

        // Get unread count
        $unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
        $unread_stmt = mysqli_prepare($conn, $unread_sql);
        if ($unread_stmt) {
            mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
            mysqli_stmt_execute($unread_stmt);
            $unread_result = mysqli_stmt_get_result($unread_stmt);

            if ($unread_result) {
                $unread_row = mysqli_fetch_assoc($unread_result);
                $unread_count = $unread_row['unread_count'];
            }
            mysqli_stmt_close($unread_stmt);
        }
} else {
    // Table doesn't exist, create some sample notifications for display
    $notifications = [
        [
            'id' => 1,
            'title' => 'Welcome to the Support System',
            'message' => 'Your support system has been set up successfully. You can now receive notifications here.',
            'type' => 'system',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'title' => 'Database Setup Required',
            'message' => 'Please run the database setup to enable full notification functionality.',
            'type' => 'system',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ]
    ];
    $unread_count = 2;
}

// Include header
require_once '../includes/header.php';
?>

<style>
.notifications-container {
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
    padding: 2rem 0;
}

.notifications-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.notifications-header .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
}

.notifications-header h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 600;
}

.notifications-header .lead {
    text-align: center;
    margin-bottom: 0;
    opacity: 0.9;
}

.notification-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.notification-card.unread {
    border-left: 4px solid #667eea;
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, white 10%);
}

.notification-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}

.notification-body {
    padding: 1.5rem;
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    margin-right: 1rem;
}

.notification-icon.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.notification-icon.success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.notification-icon.warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.notification-icon.error {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
}

.notification-icon.system {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.notification-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.notification-actions {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #f0f0f0;
}

.btn-notification {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 20px;
    padding: 0.5rem 1.5rem;
    color: white;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.btn-notification:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-outline-notification {
    border: 2px solid #667eea;
    color: #667eea;
    background: transparent;
    border-radius: 20px;
    padding: 0.5rem 1.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.btn-outline-notification:hover {
    background: #667eea;
    color: white;
}

.stats-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    padding: 2rem;
    text-align: center;
    margin-bottom: 2rem;
}

.stats-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.filter-tabs {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    padding: 1rem;
    margin-bottom: 2rem;
}

.filter-tab {
    background: transparent;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    color: #6c757d;
    font-weight: 500;
    transition: all 0.3s ease;
    margin-right: 0.5rem;
}

.filter-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .notifications-header {
        padding: 2rem 0;
    }
    
    .notification-header,
    .notification-body,
    .notification-actions {
        padding: 1rem;
    }
    
    .stats-card {
        padding: 1.5rem;
    }
    
    .filter-tab {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
}

/* Mobile Column Padding Override for Full-Width Cards */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .notifications-container {
        padding: 0 !important;
    }
    
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure page header extends full width */
    .notifications-header {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .notification-card,
    .stats-card,
    .filter-tabs,
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid">
<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error_message']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<!-- Page Header -->
<div class="notifications-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-bell me-3"></i>Notifications
                    <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </h1>
                <p class="lead">Stay updated with the latest activities and announcements</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
    <!-- Create Notification Section (Admin/Member Only) -->
    <?php if ($shouldUseAdminInterface || $isMember): ?>
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn me-2"></i>Create System Notification
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="createNotificationForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_title" class="form-label">Notification Title</label>
                                <input type="text" class="form-control" id="notification_title" name="notification_title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_type" class="form-label">Type</label>
                                <select class="form-select" id="notification_type" name="notification_type" required>
                                    <option value="info">Information</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Important</option>
                                    <option value="events">Event</option>
                                    <option value="system">System</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notification_message" class="form-label">Message</label>
                        <textarea class="form-control" id="notification_message" name="notification_message" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target_audience" class="form-label">Target Audience</label>
                                <select class="form-select" id="target_audience" name="target_audience" required>
                                    <option value="all">All Users</option>
                                    <option value="students_only">Students Only</option>
                                    <option value="members_admin">Members & Admins Only</option>
                                    <?php if ($shouldUseAdminInterface): ?>
                                    <option value="admin_only">Admins Only</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="action_url" class="form-label">Action URL (Optional)</label>
                                <input type="text" class="form-control" id="action_url" name="action_url" placeholder="e.g., ../news.php?id=123 or https://example.com">
                                <div class="form-text">Enter a relative path (e.g., ../news.php) or full URL (e.g., https://example.com)</div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="create_notification" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Notification
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

            <!-- Main Notifications -->
            <div class="col-lg-8">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterNotifications('all')">
                        <i class="fas fa-list me-2"></i>All Notifications
                    </button>
                    <button class="filter-tab" onclick="filterNotifications('unread')">
                        <i class="fas fa-envelope me-2"></i>Unread (<?php echo $unread_count; ?>)
                    </button>
                    <button class="filter-tab" onclick="filterNotifications('system')">
                        <i class="fas fa-cog me-2"></i>System
                    </button>
                    <button class="filter-tab" onclick="filterNotifications('events')">
                        <i class="fas fa-calendar me-2"></i>Events
                    </button>
                </div>

                <!-- Bulk Actions -->
                <?php if ($unread_count > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted"><?php echo count($notifications); ?> notifications</span>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-notification">
                            <i class="fas fa-check-double me-2"></i>Mark All as Read
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Notifications List -->
                <div id="notifications-list">
                    <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>No notifications yet</h4>
                        <p>You'll see notifications here when there are updates or announcements.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                             data-type="<?php echo $notification['type'] ?? 'system'; ?>" 
                             data-read="<?php echo $notification['is_read']; ?>">
                            <div class="notification-header">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon <?php echo $notification['type'] ?? 'system'; ?>">
                                        <i class="fas fa-<?php 
                                            switch($notification['type'] ?? 'system') {
                                                case 'info': echo 'info-circle'; break;
                                                case 'success': echo 'check-circle'; break;
                                                case 'warning': echo 'exclamation-triangle'; break;
                                                case 'error': echo 'times-circle'; break;
                                                default: echo 'bell';
                                            }
                                        ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($notification['title'] ?? 'System Notification'); ?></h5>
                                        <div class="notification-meta">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                            <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary ms-2">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="notification-body">
                                <p class="mb-2"><?php echo htmlspecialchars($notification['message'] ?? 'No message content'); ?></p>

                                <?php if (!empty($notification['action_url'])): ?>
                                <div class="notification-link">
                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>"
                                       class="btn btn-outline-primary btn-sm"
                                       target="_blank">
                                        <i class="fas fa-external-link-alt me-1"></i>View Content
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="notification-actions">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if (!$notification['is_read']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-notification btn-sm">
                                                <i class="fas fa-check me-1"></i>Mark as Read
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <?php if (!empty($notification['action_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($notification['action_url']); ?>"
                                           class="btn btn-primary btn-sm ms-2">
                                            <i class="fas fa-arrow-right me-1"></i>Go to Content
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="delete_notification" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Statistics -->
                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3><?php echo count($notifications); ?></h3>
                    <p class="text-muted mb-0">Total Notifications</p>
                </div>

                <div class="stats-card">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3><?php echo $unread_count; ?></h3>
                    <p class="text-muted mb-0">Unread Messages</p>
                </div>

                <!-- Notification Settings - Admin Only -->
                <?php if ($shouldUseAdminInterface): ?>
                <div class="stats-card text-start">
                    <h5 class="mb-3">
                        <i class="fas fa-cog me-2"></i>Notification Settings
                    </h5>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                        <label class="form-check-label" for="emailNotifications">
                            Email Notifications
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pushNotifications" checked>
                        <label class="form-check-label" for="pushNotifications">
                            Push Notifications
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="smsNotifications">
                        <label class="form-check-label" for="smsNotifications">
                            SMS Notifications
                        </label>
                    </div>
                    <button class="btn btn-notification btn-sm">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </div>
                <?php endif; ?>

                <!-- Quick Links - Admin Only -->
                <?php if ($shouldUseAdminInterface): ?>
                <div class="stats-card text-start">
                    <h5 class="mb-3">
                        <i class="fas fa-external-link-alt me-2"></i>Quick Links
                    </h5>
                    <div class="list-group list-group-flush">
                        <a href="../dashboard.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="../events.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-calendar me-2"></i>Events
                        </a>
                        <a href="../news.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-newspaper me-2"></i>News
                        </a>
                        <a href="../settings.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
</div>

<script src="js/notifications.js"></script>
</div>

        </div> <!-- Close container-fluid -->
    </div> <!-- Close main-content -->

<?php require_once '../includes/footer.php'; ?>
