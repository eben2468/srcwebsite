<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/activity_functions.php';

// Include our new messaging service
define('INCLUDED_FROM_APP', true);
require_once __DIR__ . '/../messaging_service.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user should use admin interface (super admin or admin)
if (!shouldUseAdminInterface()) {
    header("Location: dashboard.php");
    exit();
}

// Set page title
$pageTitle = "Unified Messaging Center";

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}

// Log user activity
if (function_exists('logUserActivity')) {
    logUserActivity(
        $currentUser['user_id'],
        $currentUser['email'],
        'page_view',
        'Viewed Unified Messaging page',
        $_SERVER['REQUEST_URI']
    );
}

// Initialize variables
$title = '';
$message = '';
$selectedUsers = [];
$userGroups = [];
$successCount = 0;
$errorMessage = '';
$successMessage = '';
$notificationType = 'standard';
$expiryDate = '';
$userGroup = '';
$sendSMS = false;
$sendEmail = false;
$sendInApp = true;

// Get all users with phone numbers and email
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, 
        COALESCE(NULLIF(u.phone, ''), NULLIF(up.phone, '')) as phone, u.role 
        FROM users u 
        LEFT JOIN user_profiles up ON u.user_id = up.user_id 
        ORDER BY u.last_name, u.first_name";
$users = fetchAll($sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $userGroup = $_POST['user_group'] ?? '';
    $notificationType = $_POST['notification_type'] ?? 'standard';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $sendSMS = isset($_POST['send_sms']);
    $sendEmail = isset($_POST['send_email']);
    $sendInApp = isset($_POST['send_in_app']);
    
    // Validate
    if (empty($title)) {
        $errorMessage = "Please enter a notification title.";
    } elseif (empty($message)) {
        $errorMessage = "Please enter a message to send.";
    } elseif (empty($selectedUsers) && $userGroup === '') {
        $errorMessage = "Please select at least one recipient or user group.";
    } elseif (!$sendSMS && !$sendEmail && !$sendInApp) {
        $errorMessage = "Please select at least one messaging channel (SMS, Email, or In-App).";
    } else {
        // If a user group is selected, get all users in that group
        if (!empty($userGroup)) {
            $selectedUsers = []; // Reset selected users when a group is selected
            
            switch ($userGroup) {
                case 'all':
                    // Get all users
                    foreach ($users as $user) {
                        $selectedUsers[] = (int)$user['user_id'];
                    }
                    break;
                case 'students':
                    // Get all student users
                    foreach ($users as $user) {
                        if ($user['role'] === 'student') {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'members':
                    // Get all member users
                    foreach ($users as $user) {
                        if ($user['role'] === 'member') {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'admins':
                    // Get all admin users
                    foreach ($users as $user) {
                        if ($user['role'] === 'admin') {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
            }
        }
        
        // Ensure selected users are integers
        $selectedUsers = array_map('intval', $selectedUsers);
        
        $successMessages = [];
        
        // Send In-App Notification if selected
        if ($sendInApp) {
            // Options for the notification
            $options = [
                'expiry_date' => !empty($expiryDate) ? $expiryDate : null
            ];
            
            // Send the in-app notification using our messaging service
            $notificationResult = sendInAppNotification($selectedUsers, $title, $message, $notificationType, $options);
            
            if ($notificationResult['success']) {
                $successMessages[] = "In-App: " . $notificationResult['message'];
            } else {
                $errorMessage = "Error sending in-app notification: " . $notificationResult['message'];
            }
        }
        
        // Send SMS if selected
        if ($sendSMS && empty($errorMessage)) {
            // Get phone numbers for selected users
            $phoneNumbers = [];
            
            foreach ($users as $user) {
                if (in_array($user['user_id'], $selectedUsers)) {
                    if (!empty($user['phone'])) {
                        // Format phone number (remove spaces, ensure it starts with +)
                        $phone = preg_replace('/\s+/', '', $user['phone']);
                        
                        // Check if the number starts with a plus sign
                        if (!str_starts_with($phone, '+')) {
                            // If it starts with 0, replace with +233 (Ghana code)
                            if (str_starts_with($phone, '0')) {
                                $phone = '+233' . substr($phone, 1);
                            } else {
                                // Otherwise just add the plus
                                $phone = '+' . $phone;
                            }
                        }
                        
                        $phoneNumbers[] = $phone;
                    }
                }
            }
            
            // If there are recipients, send the SMS
            if (!empty($phoneNumbers)) {
                // Send SMS using our messaging service
                $smsResult = sendSMS($phoneNumbers, $message);
                
                if ($smsResult['success']) {
                    // Check if we have an sms_logs table, if not create it
                    $checkTableSql = "SHOW TABLES LIKE 'sms_logs'";
                    $tableExists = mysqli_query($conn, $checkTableSql);
        
        if (mysqli_num_rows($tableExists) == 0) {
                        // Create sms_logs table
                        $createTableSql = "CREATE TABLE `sms_logs` (
                            `log_id` int(11) NOT NULL AUTO_INCREMENT,
                `message` text NOT NULL,
                            `sender_id` int(11) NOT NULL,
                            `recipient_count` int(11) NOT NULL,
                            `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`log_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            mysqli_query($conn, $createTableSql);
            
                        // Create sms_recipients junction table
                        $createJunctionTableSql = "CREATE TABLE `sms_recipients` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                            `log_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                            `phone` varchar(20) NOT NULL,
                PRIMARY KEY (`id`),
                            KEY `log_id` (`log_id`),
                KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            mysqli_query($conn, $createJunctionTableSql);
        }
        
                    // Log the SMS in the database
                    $insertLogSql = "INSERT INTO sms_logs (message, sender_id, recipient_count) 
                                    VALUES (?, ?, ?)";
                    
                    $stmt = mysqli_prepare($conn, $insertLogSql);
        
        if ($stmt) {
                        $recipientCount = count($phoneNumbers);
            
            mysqli_stmt_bind_param(
                $stmt, 
                            'sii', 
                $message, 
                $currentUser['user_id'], 
                            $recipientCount
            );
            
            if (mysqli_stmt_execute($stmt)) {
                            $logId = mysqli_insert_id($conn);
                            
                            // Log individual recipients
                            $recipientIndex = 0;
                            foreach ($selectedUsers as $userId) {
                                $user = array_filter($users, function($u) use ($userId) {
                                    return $u['user_id'] == $userId;
                                });
                                
                                if (!empty($user) && isset($phoneNumbers[$recipientIndex])) {
                                    $user = reset($user);
                                    
                                    $insertRecipientSql = "INSERT INTO sms_recipients (log_id, user_id, phone) 
                                                        VALUES (?, ?, ?)";
                                    
                                    $recipientStmt = mysqli_prepare($conn, $insertRecipientSql);
                                    
                                    if ($recipientStmt) {
                                        $phone = $phoneNumbers[$recipientIndex];
                                        
                                        mysqli_stmt_bind_param(
                                            $recipientStmt, 
                                            'iis', 
                                            $logId, 
                                            $userId, 
                                            $phone
                                        );
                                        
                                        mysqli_stmt_execute($recipientStmt);
                                        mysqli_stmt_close($recipientStmt);
                                    }
                                    
                                    $recipientIndex++;
                                }
                            }
                            
                            $smsCount = count($phoneNumbers);
                            $successMessages[] = "SMS: Sent successfully to $smsCount recipients.";
                        }
                    }
                } else {
                    $errorMessage = "Error sending SMS: " . $smsResult['message'];
                }
            } else {
                $successMessages[] = "SMS: No valid phone numbers found for the selected users.";
            }
        }
        
        // Send Email if selected
        if ($sendEmail && empty($errorMessage)) {
            // Get emails for selected users
            $emails = [];
            
            foreach ($users as $user) {
                if (in_array($user['user_id'], $selectedUsers) && !empty($user['email'])) {
                    $emails[] = $user['email'];
                }
            }
            
            // If there are email recipients, send the email
            if (!empty($emails)) {
                // Send email using our messaging service
                $emailResult = sendEmail($emails, $title, $message);
                
                if ($emailResult['success']) {
                    $emailCount = count($emails);
                    $successMessages[] = "Email: Sent successfully to $emailCount recipients.";
                } else {
                    $errorMessage = "Error sending email: " . $emailResult['message'];
                }
            } else {
                $successMessages[] = "Email: No valid email addresses found for the selected users.";
            }
        }
        
        if (empty($errorMessage) && !empty($successMessages)) {
            $successMessage = implode("<br>", $successMessages);
                
                // Reset form fields
                $title = '';
                $message = '';
                $selectedUsers = [];
                $notificationType = 'standard';
                $expiryDate = '';
            $sendSMS = false;
            $sendEmail = false;
            $sendInApp = true;
        }
    }
}

// Define page title, icon, and actions for the modern header
$pageTitle = "Unified Messaging Center";
$pageIcon = "fa-comment-alt";
$pageDescription = "Send messages to users via multiple channels";
$actions = [
    [
        'url' => 'messaging.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Messaging',
        'class' => 'btn-outline-light'
    ]
];

// Include the modern page header
include_once 'includes/modern_page_header.php';
?>

<div class="container-fluid px-4">

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-comment-alt me-1"></i>
                    Send Messages
                </div>
                <div class="card-body">
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Message Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="notification_type" class="form-label">Notification Type</label>
                                <select class="form-select" id="notification_type" name="notification_type">
                                    <option value="standard" <?php echo $notificationType === 'standard' ? 'selected' : ''; ?>>Standard</option>
                                    <option value="important" <?php echo $notificationType === 'important' ? 'selected' : ''; ?>>Important</option>
                                    <option value="alert" <?php echo $notificationType === 'alert' ? 'selected' : ''; ?>>Alert</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="expiry_date" class="form-label">Expiry Date (Optional)</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($expiryDate); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required><?php echo htmlspecialchars($message); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Messaging Channels</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_in_app" name="send_in_app" <?php echo $sendInApp ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="send_in_app">
                                    <i class="fas fa-bell me-1"></i> Send as In-App Notification
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_sms" name="send_sms" <?php echo $sendSMS ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="send_sms">
                                    <i class="fas fa-sms me-1"></i> Send as SMS
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send_email" name="send_email" <?php echo $sendEmail ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="send_email">
                                    <i class="fas fa-envelope me-1"></i> Send as Email
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Recipients</label>
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button" role="tab" aria-controls="individual" aria-selected="true">Individual Users</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups" type="button" role="tab" aria-controls="groups" aria-selected="false">User Groups</button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="individual" role="tabpanel" aria-labelledby="individual-tab">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped" id="usersTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Name</th>
                                                            <th>Email</th>
                                                            <th>Phone</th>
                                                            <th>Role</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($users as $user): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" name="selected_users[]" value="<?php echo $user['user_id']; ?>" id="user-<?php echo $user['user_id']; ?>" <?php echo in_array($user['user_id'], $selectedUsers) ? 'checked' : ''; ?>>
                                                                    </div>
                                                                </td>
                                                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                                <td><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></td>
                                                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="groups" role="tabpanel" aria-labelledby="groups-tab">
                                            <div class="mb-3">
                                                <label for="user_group" class="form-label">Select User Group</label>
                                                <select class="form-select" id="user_group" name="user_group">
                                                    <option value="">-- Select a group --</option>
                                                    <option value="all" <?php echo $userGroup === 'all' ? 'selected' : ''; ?>>All Users</option>
                                                    <option value="students" <?php echo $userGroup === 'students' ? 'selected' : ''; ?>>Students</option>
                                                    <option value="members" <?php echo $userGroup === 'members' ? 'selected' : ''; ?>>Members</option>
                                                    <option value="admins" <?php echo $userGroup === 'admins' ? 'selected' : ''; ?>>Administrators</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="messaging.php" class="btn btn-secondary me-md-2">Back to Messaging Center</a>
                            <button type="submit" class="btn btn-primary">Send Messages</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Recent Notifications
                </div>
                <div class="card-body">
                    <?php
                    // Check if notifications table exists
                    $checkTableSql = "SHOW TABLES LIKE 'notifications'";
                    $tableExists = mysqli_query($conn, $checkTableSql);
                    
                    if (mysqli_num_rows($tableExists) > 0) {
                        // Get recent notifications
                        $recentNotificationsSql = "SELECT n.*, 'System' as first_name, 'Admin' as last_name, 1 as recipient_count
                                                FROM notifications n
                                                ORDER BY n.created_at DESC
                                                LIMIT 10";
                        
                        $recentNotifications = fetchAll($recentNotificationsSql);
                        
                        if (!empty($recentNotifications)) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead><tr><th>Title</th><th>Type</th><th>Created By</th><th>Date</th><th>Recipients</th></tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($recentNotifications as $notification) {
                                $typeClass = '';
                                switch ($notification['type']) {
                                    case 'important':
                                        $typeClass = 'text-primary';
                                        break;
                                    case 'alert':
                                        $typeClass = 'text-danger';
                                        break;
                                    default:
                                        $typeClass = 'text-muted';
                                }
                                
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($notification['title']) . '</td>';
                                echo '<td><span class="' . $typeClass . '">' . ucfirst(htmlspecialchars($notification['type'])) . '</span></td>';
                                echo '<td>' . htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']) . '</td>';
                                echo '<td>' . date('M j, Y g:i A', strtotime($notification['created_at'])) . '</td>';
                                echo '<td>' . $notification['recipient_count'] . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">No notifications have been sent yet.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">Notifications system will be initialized when you send your first notification.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#usersTable').DataTable({
        responsive: true,
        order: [[1, 'asc']]
    });
    
    // Update form validation based on selected channels
    const inAppCheck = document.getElementById('send_in_app');
    const smsCheck = document.getElementById('send_sms');
    const emailCheck = document.getElementById('send_email');
    
    const form = document.querySelector('form');
    form.addEventListener('submit', function(event) {
        if (!inAppCheck.checked && !smsCheck.checked && !emailCheck.checked) {
            event.preventDefault();
            alert('Please select at least one messaging channel (In-App, SMS, or Email)');
        }
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
