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
$pageTitle = "Email Messaging";

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
        'Viewed Email Messaging page',
        $_SERVER['REQUEST_URI']
    );
}

// Initialize variables
$subject = '';
$message = '';
$selectedUsers = [];
$userGroups = [];
$successCount = 0;
$errorMessage = '';
$successMessage = '';
$attachments = [];

// Get all users with email addresses
$sql = "SELECT user_id, first_name, last_name, email, role 
        FROM users 
        WHERE email IS NOT NULL AND email != '' 
        ORDER BY last_name, first_name";
$users = fetchAll($sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $userGroup = $_POST['user_group'] ?? '';
    
    // Handle file uploads
    if (!empty($_FILES['attachments']['name'][0])) {
        $uploadDir = '../uploads/email_attachments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                $name = basename($name);
                $filePath = $uploadDir . time() . '_' . $name;
                
                if (move_uploaded_file($tmp_name, $filePath)) {
                    $attachments[] = [
                        'path' => $filePath,
                        'name' => $name
                    ];
                }
            }
        }
    }
    
    // Validate
    if (empty($subject)) {
        $errorMessage = "Please enter an email subject.";
    } elseif (empty($message)) {
        $errorMessage = "Please enter a message to send.";
    } elseif (empty($selectedUsers) && $userGroup === '') {
        $errorMessage = "Please select at least one recipient or user group.";
    } else {
        // If a user group is selected, get all users in that group
        if (!empty($userGroup)) {
            $selectedUsers = []; // Reset selected users when a group is selected
            
            switch ($userGroup) {
                case 'all':
                    // Get all users with email addresses
                    foreach ($users as $user) {
                        $selectedUsers[] = (int)$user['user_id'];
                    }
                    break;
                case 'students':
                    // Get all student users with email addresses
                    foreach ($users as $user) {
                        if ($user['role'] === 'student') {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'members':
                    // Get all member users with email addresses
                    foreach ($users as $user) {
                        if ($user['role'] === 'member') {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'admins':
                    // Get all admin users with email addresses
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
        
        // Get email addresses for selected users
        $emailAddresses = [];
        $recipientNames = [];
        
        foreach ($users as $user) {
            if (in_array($user['user_id'], $selectedUsers)) {
                $emailAddresses[] = $user['email'];
                $recipientNames[] = $user['first_name'] . ' ' . $user['last_name'];
            }
        }
        
        // If there are recipients, send the email
        if (!empty($emailAddresses)) {
            // Send the email using our messaging service
            $emailResult = sendEmail($emailAddresses, $subject, $message, $attachments);
            
            if ($emailResult['success']) {
                // Check if we have an email_logs table, if not create it
                $checkTableSql = "SHOW TABLES LIKE 'email_logs'";
                $tableExists = mysqli_query($conn, $checkTableSql);
                
                if (mysqli_num_rows($tableExists) == 0) {
                    // Create email_logs table
                    $createTableSql = "CREATE TABLE `email_logs` (
                        `log_id` int(11) NOT NULL AUTO_INCREMENT,
                        `subject` varchar(255) NOT NULL,
                        `message` text NOT NULL,
                        `sender_id` int(11) NOT NULL,
                        `recipient_count` int(11) NOT NULL,
                        `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`log_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    
                    mysqli_query($conn, $createTableSql);
                    
                    // Create email_recipients junction table
                    $createJunctionTableSql = "CREATE TABLE `email_recipients` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `log_id` int(11) NOT NULL,
                        `user_id` int(11) NOT NULL,
                        `email` varchar(255) NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `log_id` (`log_id`),
                        KEY `user_id` (`user_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    
                    mysqli_query($conn, $createJunctionTableSql);
                }
                
                // Log the email in the database
                $insertLogSql = "INSERT INTO email_logs (subject, message, sender_id, recipient_count) 
                                VALUES (?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $insertLogSql);
                
                if ($stmt) {
                    $recipientCount = count($emailAddresses);
                    
                    mysqli_stmt_bind_param(
                        $stmt, 
                        'ssii', 
                        $subject, 
                        $message, 
                        $currentUser['user_id'], 
                        $recipientCount
                    );
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $logId = mysqli_insert_id($conn);
                        
                        // Log individual recipients
                        foreach ($selectedUsers as $userId) {
                            $user = array_filter($users, function($u) use ($userId) {
                                return $u['user_id'] == $userId;
                            });
                            
                            if (!empty($user)) {
                                $user = reset($user);
                                
                                $insertRecipientSql = "INSERT INTO email_recipients (log_id, user_id, email) 
                                                    VALUES (?, ?, ?)";
                                
                                $recipientStmt = mysqli_prepare($conn, $insertRecipientSql);
                                
                                if ($recipientStmt) {
                                    mysqli_stmt_bind_param(
                                        $recipientStmt, 
                                        'iis', 
                                        $logId, 
                                        $userId, 
                                        $user['email']
                                    );
                                    
                                    mysqli_stmt_execute($recipientStmt);
                                    mysqli_stmt_close($recipientStmt);
                                }
                            }
                        }
                        
                        $successCount = count($emailAddresses);
                        $successMessage = "Email sent successfully to $successCount recipients.";
                        
                        // Log the activity
                        logUserActivity(
                            $currentUser['user_id'],
                            $currentUser['email'],
                            'email_sent',
                            "Sent email to $successCount recipients",
                            $_SERVER['REQUEST_URI']
                        );
                        
                        // Reset form fields
                        $subject = '';
                        $message = '';
                        $selectedUsers = [];
                    } else {
                        $errorMessage = "Error logging email: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($stmt);
                } else {
                    $errorMessage = "Error preparing statement: " . mysqli_error($conn);
                }
            } else {
                $errorMessage = "Error sending email: " . $emailResult['message'];
            }
        } else {
            $errorMessage = "No valid email addresses found for the selected users.";
        }
    }
}

// Define page title, icon, and actions for the modern header
$pageTitle = "Email Messaging";
$pageIcon = "fa-envelope";
$pageDescription = "Send email messages to users";
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
                    <i class="fas fa-envelope me-1"></i>
                    Send Email Messages
                </div>
                <div class="card-body">
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Email Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Email Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($message); ?></textarea>
                            <div class="form-text">You can use HTML formatting in your message.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attachments (Optional)</label>
                            <input class="form-control" type="file" id="attachment" name="attachments[]" multiple>
                            <div class="form-text">Maximum file size: 5MB. Allowed file types: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG.</div>
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
                            <button type="submit" class="btn btn-danger">Send Email</button>
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
                    Recent Email Logs
                </div>
                <div class="card-body">
                    <?php
                    // Check if email_logs table exists
                    $checkTableSql = "SHOW TABLES LIKE 'email_logs'";
                    $tableExists = mysqli_query($conn, $checkTableSql);
                    
                    if (mysqli_num_rows($tableExists) > 0) {
                        // Get recent email logs
                        $recentLogsSql = "SELECT el.*, u.first_name, u.last_name 
                                        FROM email_logs el
                                        JOIN users u ON el.sender_id = u.user_id
                                        ORDER BY el.sent_at DESC
                                        LIMIT 10";
                        
                        $recentLogs = fetchAll($recentLogsSql);
                        
                        if (!empty($recentLogs)) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead><tr><th>Subject</th><th>Sent By</th><th>Date</th><th>Recipients</th></tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($recentLogs as $log) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($log['subject']) . '</td>';
                                echo '<td>' . htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) . '</td>';
                                echo '<td>' . date('M j, Y g:i A', strtotime($log['sent_at'])) . '</td>';
                                echo '<td>' . $log['recipient_count'] . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">No emails have been sent yet.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">Email logs will be initialized when you send your first email.</div>';
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
    
    // Initialize rich text editor if available
    if (typeof ClassicEditor !== 'undefined') {
        ClassicEditor
            .create(document.querySelector('#message'))
            .catch(error => {
                console.error(error);
            });
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
