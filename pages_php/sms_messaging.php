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
$pageTitle = "SMS Messaging";

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
        'Viewed SMS Messaging page',
        $_SERVER['REQUEST_URI']
    );
}

// Initialize variables
$message = '';
$selectedUsers = [];
$userGroups = [];
$successCount = 0;
$errorMessage = '';
$successMessage = '';
$userGroup = ''; // Initialize userGroup variable

// Helper function to check if a user group is selected
function isGroupSelected($group) {
    global $userGroup;
    return isset($userGroup) && $userGroup === $group;
}

// Get all users with phone numbers
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, 
        COALESCE(NULLIF(u.phone, ''), NULLIF(up.phone, '')) as phone, u.role 
        FROM users u 
        LEFT JOIN user_profiles up ON u.user_id = up.user_id 
        WHERE (u.phone IS NOT NULL AND u.phone != '') 
           OR (up.phone IS NOT NULL AND up.phone != '')
        ORDER BY u.last_name, u.first_name";
$users = fetchAll($sql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $message = trim($_POST['message'] ?? '');
    $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $userGroup = $_POST['user_group'] ?? '';
    
    // Validate
    if (empty($message)) {
        $errorMessage = "Please enter a message to send.";
    } elseif (empty($selectedUsers) && empty($userGroup)) {
        $errorMessage = "Please select at least one recipient or user group.";
    } else {
        // If a user group is selected, get all users in that group
        if (!empty($userGroup)) {
            $selectedUsers = []; // Reset selected users when a group is selected
            
            // Initialize $userGroup to avoid undefined variable warnings
            if (!isset($userGroup)) {
                $userGroup = '';
            }
            
            switch ($userGroup) {
                case 'all':
                    // Get all users with phone numbers
                    foreach ($users as $user) {
                        if (!empty($user['phone'])) {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'students':
                    // Get all student users with phone numbers
                    foreach ($users as $user) {
                        if ($user['role'] === 'student' && !empty($user['phone'])) {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'members':
                    // Get all member users with phone numbers
                    foreach ($users as $user) {
                        if ($user['role'] === 'member' && !empty($user['phone'])) {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                case 'admins':
                    // Get all admin users with phone numbers
                    foreach ($users as $user) {
                        if ($user['role'] === 'admin' && !empty($user['phone'])) {
                            $selectedUsers[] = (int)$user['user_id'];
                        }
                    }
                    break;
                default:
                    // No valid group selected
                    break;
            }
        }
        
        // Ensure selected users are integers
        $selectedUsers = array_map('intval', $selectedUsers);
        
        // Get phone numbers for selected users
        $phoneNumbers = [];
        $recipientNames = [];
        
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
                    $recipientNames[] = $user['first_name'] . ' ' . $user['last_name'];
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
                        
                        $successCount = count($phoneNumbers);
                        $successMessage = "SMS message sent successfully to $successCount recipients.";
                        
                        // Log the activity
                        logUserActivity(
                            $currentUser['user_id'],
                            $currentUser['email'],
                            'sms_sent',
                            "Sent SMS message to $successCount recipients",
                            $_SERVER['REQUEST_URI']
                        );
                        
                        // Reset form
                        $message = '';
                        $selectedUsers = [];
                    } else {
                        $errorMessage = "Error logging SMS: " . mysqli_error($conn);
                    }
                    
                    mysqli_stmt_close($stmt);
                } else {
                    $errorMessage = "Error preparing statement: " . mysqli_error($conn);
                }
            } else {
                $errorMessage = "Error sending SMS: " . $smsResult['message'];
            }
        } else {
            $errorMessage = "No valid phone numbers found for the selected users.";
        }
    }
}
?>

<!-- Custom SMS Messaging Header -->
<div class="sms-messaging-header animate__animated animate__fadeInDown">
    <div class="sms-messaging-header-content">
        <div class="sms-messaging-header-main">
            <h1 class="sms-messaging-title">
                <i class="fas fa-sms me-3"></i>
                SMS Messaging
            </h1>
            <p class="sms-messaging-description">Send SMS messages to users and groups</p>
        </div>
        <div class="sms-messaging-header-actions">
            <a href="messaging.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Messaging
            </a>
        </div>
    </div>
</div>

<style>
.sms-messaging-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.sms-messaging-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.sms-messaging-header-main {
    flex: 1;
    text-align: center;
}

.sms-messaging-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.sms-messaging-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.sms-messaging-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.sms-messaging-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-header-action {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

@media (max-width: 768px) {
    .sms-messaging-header {
        padding: 2rem 1.5rem;
    }

    .sms-messaging-header-content {
        flex-direction: column;
        align-items: center;
    }

    .sms-messaging-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .sms-messaging-title i {
        font-size: 1.8rem;
    }

    .sms-messaging-description {
        font-size: 1.1rem;
    }

    .sms-messaging-header-actions {
        width: 100%;
        justify-content: center;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
}

/* Animation classes */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -100%, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate__animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}
</style>

<div class="container-fluid px-4">

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-sms me-1"></i>
                    Send SMS Messages
                </div>
                <div class="card-body">
                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success"><?php echo $successMessage; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required><?php echo htmlspecialchars($message); ?></textarea>
                            <div class="form-text">Character count: <span id="char-count">0</span>/160 characters</div>
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
                                            <div class="mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAllUsers" onclick="toggleAllCheckboxes(this.checked)">
                                                    <label class="form-check-label fw-bold" for="selectAllUsers">ALL</label>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped" id="usersTable">
                                                    <thead>
                                                        <tr>
                                                            <th>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                                    <label class="form-check-label" for="selectAll">All</label>
                                                                </div>
                                                            </th>
                                                            <th>Name</th>
                                                            <th>Email</th>
                                                            <th>Phone</th>
                                                            <th>Role</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($users as $user): ?>
                                                            <?php if (!empty($user['phone'])): ?>
                                                                <tr>
                                                                    <td>
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox" name="selected_users[]" value="<?php echo $user['user_id']; ?>" id="user-<?php echo $user['user_id']; ?>" <?php echo in_array($user['user_id'], $selectedUsers) ? 'checked' : ''; ?>>
                                                                        </div>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                                    <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                                                </tr>
                                                            <?php endif; ?>
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
                                                    <option value="all" <?php echo isGroupSelected('all') ? 'selected' : ''; ?>>All Users</option>
                                                    <option value="students" <?php echo isGroupSelected('students') ? 'selected' : ''; ?>>Students</option>
                                                    <option value="members" <?php echo isGroupSelected('members') ? 'selected' : ''; ?>>Members</option>
                                                    <option value="admins" <?php echo isGroupSelected('admins') ? 'selected' : ''; ?>>Administrators</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="messaging.php" class="btn btn-secondary me-md-2">Back to Messaging Center</a>
                            <button type="submit" class="btn btn-primary">Send SMS</button>
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
                    Recent SMS Logs
                </div>
                <div class="card-body">
                    <?php
                    // Check if sms_logs table exists
                    $checkTableSql = "SHOW TABLES LIKE 'sms_logs'";
                    $tableExists = mysqli_query($conn, $checkTableSql);
                    
                    if (mysqli_num_rows($tableExists) > 0) {
                        // Get recent SMS logs
                        $recentLogsSql = "SELECT sl.*, u.first_name, u.last_name 
                                        FROM sms_logs sl
                                        JOIN users u ON sl.sender_id = u.user_id
                                        ORDER BY sl.sent_at DESC
                                        LIMIT 10";
                        
                        $recentLogs = fetchAll($recentLogsSql);
                        
                        if (!empty($recentLogs)) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead><tr><th>Message</th><th>Sent By</th><th>Date</th><th>Recipients</th></tr></thead>';
                            echo '<tbody>';
                            
                            foreach ($recentLogs as $log) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars(substr($log['message'], 0, 50)) . (strlen($log['message']) > 50 ? '...' : '') . '</td>';
                                echo '<td>' . htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) . '</td>';
                                echo '<td>' . date('M j, Y g:i A', strtotime($log['sent_at'])) . '</td>';
                                echo '<td>' . $log['recipient_count'] . '</td>';
                                echo '</tr>';
                            }
                            
                            echo '</tbody></table>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">No SMS messages have been sent yet.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-info">SMS logs will be initialized when you send your first message.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Direct function to toggle all checkboxes - called from the onclick attribute
function toggleAllCheckboxes(checked) {
    // Get all checkboxes by name
    var checkboxes = document.getElementsByName('selected_users[]');
    
    // Loop through each checkbox and set its checked state
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = checked;
    }
    
    // Also update the table header checkbox
    var selectAllHeader = document.getElementById('selectAll');
    if (selectAllHeader) {
        selectAllHeader.checked = checked;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    const table = $('#usersTable').DataTable({
        responsive: true,
        order: [[1, 'asc']],
        stateSave: true,
        // Initialize complete callback
        initComplete: function() {
            // Check the initial state of checkboxes
            updateCheckboxStates();
        }
    });
    
    // Character counter for SMS
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('char-count');
    
    messageTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count > 160) {
            charCount.classList.add('text-danger');
        } else {
            charCount.classList.remove('text-danger');
        }
    });
    
    // Initialize character count on page load
    if (messageTextarea.value.length > 0) {
        charCount.textContent = messageTextarea.value.length;
        if (messageTextarea.value.length > 160) {
            charCount.classList.add('text-danger');
        }
    }
    
    // Function to update all checkbox states
    function updateCheckboxStates() {
        var allChecked = true;
        var anyChecked = false;
        
        $('input[name="selected_users[]"]').each(function() {
            if (this.checked) {
                anyChecked = true;
            } else {
                allChecked = false;
            }
        });
        
        // Update ALL checkbox
        $('#selectAllUsers').prop('checked', allChecked);
        $('#selectAllUsers').prop('indeterminate', anyChecked && !allChecked);
        
        // Update table header checkbox
        $('#selectAll').prop('checked', allChecked);
        $('#selectAll').prop('indeterminate', anyChecked && !allChecked);
    }
    
    // Set up individual checkbox click handlers
    $(document).on('change', 'input[name="selected_users[]"]', function() {
        updateCheckboxStates();
    });
    
    // Handle the table header checkbox
    $('#selectAll').on('click', function() {
        var isChecked = this.checked;
        toggleAllCheckboxes(isChecked);
    });
    
    // DataTable search event
    table.on('search.dt', function() {
        updateCheckboxStates();
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
