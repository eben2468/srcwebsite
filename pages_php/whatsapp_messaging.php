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

// Function to log user activity if the main function doesn't exist
if (!function_exists('logUserActivity')) {
    function logUserActivity($userId, $userEmail, $activityType, $activityDescription, $pageUrl = null) {
        global $conn;
        
        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        
        // If page URL is not provided, get current URL
        if ($pageUrl === null) {
            $pageUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        }
        
        // Insert activity into database
        $sql = "INSERT INTO user_activities (user_id, user_email, activity_type, activity_description, ip_address, page_url) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isssss', $userId, $userEmail, $activityType, $activityDescription, $ipAddress, $pageUrl);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        }
        
        return false;
    }
}

// Set page title
$pageTitle = "WhatsApp Messaging";

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
        'Viewed WhatsApp Messaging page',
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
$whatsappLinks = [];

// Get all users with phone numbers
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, 
        COALESCE(NULLIF(u.phone, ''), NULLIF(up.phone, '')) as phone, u.role 
        FROM users u 
        LEFT JOIN user_profiles up ON u.user_id = up.user_id 
        WHERE (u.phone IS NOT NULL AND u.phone != '') 
           OR (up.phone IS NOT NULL AND up.phone != '')
        ORDER BY u.last_name, u.first_name";
$users = fetchAll($sql);

// Debug the query results
if (count($users) === 0) {
    // If no users found, check both tables separately to debug
    echo '<div class="alert alert-warning">No users with phone numbers found. Checking database...</div>';
    
    $sqlUsers = "SELECT user_id, first_name, last_name, phone FROM users WHERE phone IS NOT NULL AND phone != ''";
    $usersWithPhones = fetchAll($sqlUsers);
    
    $sqlProfiles = "SELECT user_id, full_name, phone FROM user_profiles WHERE phone IS NOT NULL AND phone != ''";
    $profilesWithPhones = fetchAll($sqlProfiles);
    
    echo '<div class="alert alert-info">';
    echo 'Users table phone count: ' . count($usersWithPhones) . '<br>';
    echo 'User profiles table phone count: ' . count($profilesWithPhones);
    echo '</div>';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $message = trim($_POST['message'] ?? '');
    $selectedUsers = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $userGroup = $_POST['user_group'] ?? '';
    $specificProvider = $_POST['whatsapp_provider'] ?? null; // Get the selected provider
    
    // Validate
    if (empty($message)) {
        $errorMessage = "Please enter a message to send.";
    } elseif (empty($selectedUsers) && $userGroup === '') {
        $errorMessage = "Please select at least one recipient or user group.";
    } else {
        // If a user group is selected, get all users in that group
        if (!empty($userGroup)) {
            $selectedUsers = []; // Reset selected users when a group is selected
            
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
        
        // If there are recipients, send the WhatsApp message
        if (!empty($phoneNumbers)) {
            // First, check if WhatsApp API integration is set up
            $waConfig = getWhatsAppConfig();
            
            if (!empty($waConfig['service']) && 
                (($waConfig['service'] === 'twilio' && !empty($waConfig['twilio_account_sid'])) || 
                 ($waConfig['service'] === 'messagebird' && !empty($waConfig['messagebird_api_key'])) || 
                 ($waConfig['service'] === 'facebook' && !empty($waConfig['facebook_access_token'])) ||
                 ($waConfig['service'] === 'infobip' && !empty($waConfig['infobip_api_key'])))) {
                
                // Send WhatsApp message using our messaging service with specific provider if selected
                $waResult = sendWhatsApp($phoneNumbers, $message, [], $specificProvider);
                
                if ($waResult['success']) {
                    // Check if we have a whatsapp_logs table, if not create it
                    $checkTableSql = "SHOW TABLES LIKE 'whatsapp_logs'";
                    $tableExists = mysqli_query($conn, $checkTableSql);
                    
                    if (mysqli_num_rows($tableExists) == 0) {
                        // Create whatsapp_logs table
                        $createTableSql = "CREATE TABLE `whatsapp_logs` (
                            `log_id` int(11) NOT NULL AUTO_INCREMENT,
                            `message` text NOT NULL,
                            `sender_id` int(11) NOT NULL,
                            `recipient_count` int(11) NOT NULL,
                            `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`log_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                        
                        mysqli_query($conn, $createTableSql);
                        
                        // Create whatsapp_recipients junction table
                        $createJunctionTableSql = "CREATE TABLE `whatsapp_recipients` (
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
                    
                    // Log the WhatsApp message in the database
                    $insertLogSql = "INSERT INTO whatsapp_logs (message, sender_id, recipient_count) 
                                    VALUES (?, ?, ?)";
                    
                    $stmt = mysqli_prepare($conn, $insertLogSql);
                    
                    if ($stmt) {
                        $recipientCount = count($phoneNumbers);
                        
                        mysqli_stmt_bind_param(
                            $stmt, 
                            "sii", 
                            $message, 
                            $currentUser['user_id'], 
                            $recipientCount
                        );
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $logId = mysqli_insert_id($conn);
                            
                            // Log each recipient
                            $insertRecipientSql = "INSERT INTO whatsapp_recipients (log_id, user_id, phone) 
                                                  VALUES (?, ?, ?)";
                            
                            $recipientStmt = mysqli_prepare($conn, $insertRecipientSql);
                            
                            if ($recipientStmt) {
                                foreach ($selectedUsers as $index => $userId) {
                                    if (isset($phoneNumbers[$index])) {
                                        mysqli_stmt_bind_param(
                                            $recipientStmt, 
                                            "iis", 
                                            $logId, 
                                            $userId, 
                                            $phoneNumbers[$index]
                                        );
                                        
                                        mysqli_stmt_execute($recipientStmt);
                                    }
                                }
                                
                                mysqli_stmt_close($recipientStmt);
                            }
                        }
                        
                        mysqli_stmt_close($stmt);
                    }
                    
                    // Set success message
                    $successCount = count($phoneNumbers);
                    $successMessage = "Successfully sent WhatsApp message to {$successCount} recipients.";
                    
                    // Log the activity
                    logUserActivity(
                        $currentUser['user_id'],
                        $currentUser['email'],
                        'whatsapp_sent',
                        "Sent WhatsApp message to {$successCount} recipients" . ($specificProvider ? " using {$specificProvider}" : ""),
                        $_SERVER['REQUEST_URI']
                    );
                } else {
                    $errorMessage = "Error sending WhatsApp messages: " . $waResult['message'];
                    if (!empty($waResult['details'])) {
                        $errorMessage .= "<br>Details: " . htmlspecialchars(print_r($waResult['details'], true));
                    }
                }
            } else {
                // If WhatsApp API integration is not set up, generate WhatsApp links instead
                $whatsappLinks = [];
                foreach ($phoneNumbers as $index => $phone) {
                    $encodedMessage = urlencode($message);
                    $whatsappLinks[] = [
                        'name' => $recipientNames[$index],
                        'phone' => $phone,
                        'link' => "https://api.whatsapp.com/send?phone={$phone}&text={$encodedMessage}"
                    ];
                }
                
                $successCount = count($whatsappLinks);
                $successMessage = "Generated WhatsApp message links for {$successCount} recipients. Direct API integration is not configured.";
                
                // Log the activity
                logUserActivity(
                    $currentUser['user_id'],
                    $currentUser['email'],
                    'whatsapp_links_generated',
                    "Generated WhatsApp message links for {$successCount} recipients",
                    $_SERVER['REQUEST_URI']
                );
            }
        } else {
            $errorMessage = "No valid phone numbers found for the selected recipients.";
        }
    }
}
?>

<!-- Custom WhatsApp Messaging Header -->
<div class="whatsapp-messaging-header animate__animated animate__fadeInDown">
    <div class="whatsapp-messaging-header-content">
        <div class="whatsapp-messaging-header-main">
            <h1 class="whatsapp-messaging-title">
                <i class="fab fa-whatsapp me-3"></i>
                WhatsApp Messaging
            </h1>
            <p class="whatsapp-messaging-description">Send WhatsApp messages to users and groups</p>
        </div>
        <div class="whatsapp-messaging-header-actions">
            <a href="messaging.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Messaging
            </a>
        </div>
    </div>
</div>

<style>
.whatsapp-messaging-header {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.whatsapp-messaging-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.whatsapp-messaging-header-main {
    flex: 1;
    text-align: center;
}

.whatsapp-messaging-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.whatsapp-messaging-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.whatsapp-messaging-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.whatsapp-messaging-header-actions {
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
    .whatsapp-messaging-header {
        padding: 2rem 1.5rem;
    }

    .whatsapp-messaging-header-content {
        flex-direction: column;
        align-items: center;
    }

    .whatsapp-messaging-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .whatsapp-messaging-title i {
        font-size: 1.8rem;
    }

    .whatsapp-messaging-description {
        font-size: 1.1rem;
    }

    .whatsapp-messaging-header-actions {
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
    
    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($whatsappLinks)): ?>
        <div class="alert alert-info">
            <h5>WhatsApp Links</h5>
            <p>Click on the links below to send messages manually:</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Phone</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($whatsappLinks as $link): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($link['name']); ?></td>
                                <td><?php echo htmlspecialchars($link['phone']); ?></td>
                                <td>
                                    <a href="<?php echo $link['link']; ?>" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fab fa-whatsapp me-1"></i> Send Message
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($phoneNumbers)): ?>
    <div class="alert alert-warning">
        <h5>Debugging Information:</h5>
        <p>Selected Users: <?php echo implode(', ', $selectedUsers); ?></p>
        <p>User Group: <?php echo $userGroup; ?></p>
        <p>Total Users with Phone Numbers in Database: <?php echo count($users); ?></p>
        <p>Total Selected Users: <?php echo $debugInfo['totalSelected']; ?></p>
        <p>Valid Phone Numbers: <?php echo $debugInfo['validPhones']; ?></p>
        <p>Invalid Phone Numbers: <?php echo $debugInfo['invalidPhones']; ?></p>
        <p>User Details:</p>
        <pre><?php print_r($debugInfo['phoneDetails']); ?></pre>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-paper-plane me-1"></i>
                    Send WhatsApp Message
                </div>
                <div class="card-body">
                    <form method="post" id="whatsappForm">
                        <div class="mb-3">
                            <label for="message" class="form-label">Message Content</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required><?php echo htmlspecialchars($message); ?></textarea>
                            <small class="form-text text-muted">Enter the message you want to send to the selected recipients.</small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Select Recipients by Group</label>
                                <select class="form-select" name="user_group" id="user_group">
                                    <option value="">-- Select a group --</option>
                                    <option value="all">All Users</option>
                                    <option value="students">All Students</option>
                                    <option value="members">All Members</option>
                                    <option value="admins">All Admins</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="showIndividualSelection">
                                    <label class="form-check-label" for="showIndividualSelection">
                                        Select Individual Recipients
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div id="individualSelectionSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Select Individual Recipients</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                                        <label class="form-check-label" for="selectAll">Select All</label>
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
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input user-checkbox" type="checkbox" 
                                                               name="selected_users[]" value="<?php echo $user['user_id']; ?>"
                                                               <?php echo in_array($user['user_id'], $selectedUsers) ? 'checked' : ''; ?>>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Generate WhatsApp Links</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($successCount > 0 && isset($whatsappLinks)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fab fa-whatsapp me-1"></i>
                    WhatsApp Message Links
                </div>
                <div class="card-body">
                    <p>Click on each link below to send the message to the respective recipient:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Recipient</th>
                                    <th>Phone Number</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($whatsappLinks as $index => $link): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($link['name']); ?></td>
                                    <td><?php echo htmlspecialchars($link['phone']); ?></td>
                                    <td>
                                        <a href="<?php echo $link['link']; ?>" target="_blank" class="btn btn-success btn-sm">
                                            <i class="fab fa-whatsapp me-1"></i> Send Message
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button id="sendAllBtn" class="btn btn-primary" onclick="openAllLinks()">
                            <i class="fab fa-whatsapp me-1"></i> Open All Links (5 seconds apart)
                        </button>
                        <button id="directSendBtn" class="btn btn-success ms-2" onclick="directSend()">
                            <i class="fab fa-whatsapp me-1"></i> Direct Send (No Popups)
                        </button>
                        <button id="stopSendingBtn" class="btn btn-danger ms-2" style="display: none;" onclick="stopSending()">
                            <i class="fas fa-stop me-1"></i> Stop
                        </button>
                        <div id="progressContainer" class="mt-2" style="display: none;">
                            <div class="progress">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p id="progressStatus" class="mt-1 small text-muted">Opening links: <span id="currentLink">0</span> of <span id="totalLinks">0</span></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Global variables for use by inline onclick handlers
let countdownInterval;
let currentIndex = 0;
let whatsappLinks = <?php echo isset($whatsappLinks) ? json_encode(array_column($whatsappLinks, 'link')) : '[]'; ?>;

// Function to open all links (called by inline onclick)
function openAllLinks() {
    console.log("openAllLinks called");
    const links = whatsappLinks;
    
    if (links.length > 0) {
        // Show confirmation dialog
        const confirmMessage = `You are about to open ${links.length} WhatsApp message links (one every 5 seconds). Your browser may ask for permission. Continue?`;
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Show progress elements and stop button
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const currentLinkSpan = document.getElementById('currentLink');
        const totalLinksSpan = document.getElementById('totalLinks');
        const stopBtn = document.getElementById('stopSendingBtn');
        const sendAllBtn = document.getElementById('sendAllBtn');
        
        progressContainer.style.display = 'block';
        stopBtn.style.display = 'inline-block';
        totalLinksSpan.textContent = links.length;
        currentLinkSpan.textContent = '0';
        
        // Disable the button while processing
        sendAllBtn.disabled = true;
        sendAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Opening links...';
        
        currentIndex = 0;
        
        // Start opening links
        openNextLink();
    } else {
        alert('No WhatsApp links available to open.');
    }
}

// Function to open the next link
function openNextLink() {
    const links = whatsappLinks;
    if (currentIndex < links.length) {
        // Get UI elements
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const currentLinkSpan = document.getElementById('currentLink');
        const stopBtn = document.getElementById('stopSendingBtn');
        const sendAllBtn = document.getElementById('sendAllBtn');
        
        // Update progress
        currentLinkSpan.textContent = (currentIndex + 1).toString();
        const progressPercent = Math.round(((currentIndex + 1) / links.length) * 100);
        progressBar.style.width = progressPercent + '%';
        progressBar.setAttribute('aria-valuenow', progressPercent.toString());
        
        try {
            // Open link
            const newWindow = window.open(links[currentIndex], '_blank');
            
            // Check if popup was blocked
            if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                // Popup was blocked
                if (countdownInterval) clearInterval(countdownInterval);
                
                // Show error message
                progressContainer.innerHTML += `
                    <div class="alert alert-warning mt-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Popup blocked!</strong> Your browser blocked the popup windows. 
                        Please allow popups for this site and try again.
                    </div>
                `;
                
                // Reset button
                sendAllBtn.disabled = false;
                stopBtn.style.display = 'none';
                sendAllBtn.innerHTML = '<i class="fab fa-whatsapp me-1"></i> Try Again';
                return;
            }
            
            currentIndex++;
            
            if (currentIndex < links.length) {
                // Show countdown in button
                sendAllBtn.innerHTML = `<i class="fas fa-clock me-1"></i> Opening next in 5s...`;
                
                // Countdown timer
                let countdown = 5;
                if (countdownInterval) clearInterval(countdownInterval);
                countdownInterval = setInterval(() => {
                    countdown--;
                    sendAllBtn.innerHTML = `<i class="fas fa-clock me-1"></i> Opening next in ${countdown}s...`;
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                        setTimeout(openNextLink, 100); // Small additional delay
                    }
                }, 1000);
            } else {
                // All links opened
                sendAllBtn.disabled = false;
                stopBtn.style.display = 'none';
                sendAllBtn.innerHTML = '<i class="fas fa-check-circle me-1"></i> All links opened!';
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    sendAllBtn.innerHTML = '<i class="fab fa-whatsapp me-1"></i> Open All Links (5 seconds apart)';
                }, 3000);
            }
        } catch (error) {
            console.error("Error opening WhatsApp link:", error);
            progressContainer.innerHTML += `
                <div class="alert alert-danger mt-2">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error:</strong> ${error.message || 'Failed to open WhatsApp link'}
                </div>
            `;
            
            // Reset button
            sendAllBtn.disabled = false;
            stopBtn.style.display = 'none';
            sendAllBtn.innerHTML = '<i class="fab fa-whatsapp me-1"></i> Try Again';
        }
    }
}

// Function for direct send (called by inline onclick)
function directSend() {
    console.log("directSend called");
    const links = whatsappLinks;
    
    if (links.length > 0) {
        // Show confirmation dialog
        const confirmMessage = `You are about to be redirected to ${links.length} WhatsApp message links one after another. Continue?`;
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Disable buttons during process
        const directSendBtn = document.getElementById('directSendBtn');
        const sendAllBtn = document.getElementById('sendAllBtn');
        
        directSendBtn.disabled = true;
        if (sendAllBtn) sendAllBtn.disabled = true;
        
        directSendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Redirecting...';
        
        // Redirect to first link
        window.location.href = links[0];
        
        // Store remaining links in localStorage for sequential processing
        if (links.length > 1) {
            localStorage.setItem('whatsappLinks', JSON.stringify(links.slice(1)));
            localStorage.setItem('whatsappReturnUrl', window.location.href);
        }
    } else {
        alert('No WhatsApp links available.');
    }
}

// Function to stop sending (called by inline onclick)
function stopSending() {
    console.log("stopSending called");
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    
    // Reset UI
    const stopBtn = document.getElementById('stopSendingBtn');
    const sendAllBtn = document.getElementById('sendAllBtn');
    
    stopBtn.style.display = 'none';
    if (sendAllBtn) {
        sendAllBtn.disabled = false;
        sendAllBtn.innerHTML = '<i class="fab fa-whatsapp me-1"></i> Open All Links (5 seconds apart)';
    }
    
    // Show stopped message
    const progressContainer = document.getElementById('progressContainer');
    if (progressContainer) {
        progressContainer.innerHTML += `
            <div class="alert alert-info mt-2">
                <i class="fas fa-info-circle me-2"></i>
                Process stopped by user.
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // Show/hide individual selection section
    const showIndividualCheckbox = document.getElementById('showIndividualSelection');
    const individualSelectionSection = document.getElementById('individualSelectionSection');
    
    if (showIndividualCheckbox && individualSelectionSection) {
        showIndividualCheckbox.addEventListener('change', function() {
            individualSelectionSection.style.display = showIndividualCheckbox.checked ? 'block' : 'none';
        });
    }
    
    // Handle user group selection
    const userGroupSelect = document.getElementById('user_group');
    if (userGroupSelect) {
        userGroupSelect.addEventListener('change', function() {
            // If a group is selected, check if we should hide individual selection
            if (userGroupSelect.value) {
                if (showIndividualCheckbox) {
                    showIndividualCheckbox.checked = false;
                }
                if (individualSelectionSection) {
                    individualSelectionSection.style.display = 'none';
                }
            }
        });
    }
    
    // Initialize DataTable
    if ($.fn.DataTable && $('#usersTable').length) {
        $('#usersTable').DataTable({
            responsive: true,
            order: [[1, 'asc']]
        });
    }
    
    // Form submission
    const whatsappForm = document.getElementById('whatsappForm');
    if (whatsappForm) {
        whatsappForm.addEventListener('submit', function(e) {
            // Check if at least one user is selected or a group is selected
            const hasSelectedUsers = Array.from(userCheckboxes).some(checkbox => checkbox.checked);
            const hasSelectedGroup = userGroupSelect && userGroupSelect.value !== '';
            
            if (!hasSelectedUsers && !hasSelectedGroup) {
                e.preventDefault();
                alert('Please select at least one recipient or user group.');
            }
        });
    }
    
    // Check if we're returning from a WhatsApp redirect
    window.addEventListener('load', function() {
        const storedLinks = localStorage.getItem('whatsappLinks');
        const returnUrl = localStorage.getItem('whatsappReturnUrl');
        
        if (storedLinks && returnUrl) {
            const links = JSON.parse(storedLinks);
            
            if (links.length > 0) {
                // Process next link
                const nextLink = links[0];
                const remainingLinks = links.slice(1);
                
                if (remainingLinks.length > 0) {
                    // Store remaining links
                    localStorage.setItem('whatsappLinks', JSON.stringify(remainingLinks));
                } else {
                    // Clear storage when done
                    localStorage.removeItem('whatsappLinks');
                    localStorage.removeItem('whatsappReturnUrl');
                }
                
                // Redirect to next link after a short delay
                setTimeout(function() {
                    window.location.href = nextLink;
                }, 500);
            } else {
                // Clear storage when done
                localStorage.removeItem('whatsappLinks');
                localStorage.removeItem('whatsappReturnUrl');
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?> 
