<?php
// Include simple authentication and required files
require_once __DIR__ . '/../../includes/simple_auth.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../../includes/db_config.php';
require_once __DIR__ . '/../../includes/db_functions.php';
require_once __DIR__ . '/../../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$shouldUseAdminInterface = shouldUseAdminInterface();
$isAdmin = $shouldUseAdminInterface;
$isMember = isMember();

// Debug: Check user data structure
if (empty($currentUser)) {
    die("Error: User not found. Please log in again.");
}

// Handle form submission
$message = '';
$messageType = '';

if ($_POST && isset($_POST['submit_ticket'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // Get user ID with fallback options
    $user_id = 0;
    if (isset($currentUser['user_id'])) {
        $user_id = $currentUser['user_id'];
    } elseif (isset($currentUser['id'])) {
        $user_id = $currentUser['id'];
    } elseif (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }

    // Validate required fields
    if (empty($subject) || empty($description)) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } elseif (empty($user_id)) {
        $message = "User session error. Please log out and log in again.";
        $messageType = "danger";
    } else {
        // Insert support ticket
        $sql = "INSERT INTO support_tickets (user_id, title, priority, category, description, status, created_at)
                VALUES ('$user_id', '$subject', '$priority', '$category', '$description', 'open', NOW())";

        if (mysqli_query($conn, $sql)) {
            $ticket_id = mysqli_insert_id($conn);
            $message = "Your support ticket (#$ticket_id) has been submitted successfully. We'll get back to you soon!";
            $messageType = "success";

            // Clear form data after successful submission
            $_POST = array();
        } else {
            $message = "Error submitting ticket: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

// Define role-based access permissions for support features
$supportAccess = [
    'user_guide' => ['student' => true, 'member' => true, 'admin' => true],
    'help_center' => ['student' => true, 'member' => true, 'admin' => true],
    'contact_support' => ['student' => true, 'member' => true, 'admin' => true],
    'notifications' => ['student' => true, 'member' => true, 'admin' => true],
    'system_stats' => ['student' => false, 'member' => true, 'admin' => true],
    'admin_tools' => ['student' => false, 'member' => false, 'admin' => true],
    'support_tickets' => ['student' => true, 'member' => true, 'admin' => true],
    'knowledge_base' => ['student' => true, 'member' => true, 'admin' => true],
    'system_updates' => ['student' => false, 'member' => true, 'admin' => true],
    'user_management' => ['student' => false, 'member' => false, 'admin' => true]
];

// Get current user role
$userRole = $currentUser['role'] ?? 'student';

// Helper function to check if user has access to a feature
function hasAccess($feature, $role, $accessMatrix) {
    return isset($accessMatrix[$feature][$role]) && $accessMatrix[$feature][$role] === true;
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Contact Support - " . $siteName;
$bodyClass = "page-contact-support";

// Include header
require_once '../includes/header.php';
?>

<style>
.contact-support-container {
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
    padding: 2rem 0;
}

.support-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.support-header .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
}

.support-header h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 600;
}

.support-header .lead {
    text-align: center;
    margin-bottom: 0;
    opacity: 0.9;
}

.support-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    border: none;
}

.contact-method {
    text-align: center;
    padding: 2rem;
    border-radius: 15px;
    background: white;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    cursor: pointer;
}

.contact-method:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.contact-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
}

.contact-icon.email {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.contact-icon.phone {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.contact-icon.chat {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.contact-icon.ticket {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn-support {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-support:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.priority-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.875rem;
    font-weight: 500;
}

.priority-low {
    background: #d4edda;
    color: #155724;
}

.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-high {
    background: #f8d7da;
    color: #721c24;
}

.faq-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
}

.response-time {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
}

@media (max-width: 768px) {
    .support-header {
        padding: 2rem 0;
    }
    
    .support-card {
        padding: 1.5rem;
    }
    
    .contact-method {
        padding: 1.5rem;
    }
    
    .contact-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}

/* Mobile Full-Width Optimization for Contact Support Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .support-header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    .card, .support-card, .contact-method {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid">
<!-- Page Header -->
<div class="support-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 position-relative">
                <div class="text-center">
                    <h1 class="display-4 mb-3">
                        <i class="fas fa-headset me-3"></i>Contact Support
                    </h1>
                    <p class="lead">Get help from our support team - we're here to assist you</p>
                </div>

                <!-- Back Button - Centered Right -->
                <div class="position-absolute top-50 end-0 translate-middle-y">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Support
                    </a>
                </div>
            </div>

                <!-- Quick Access Navigation -->
                <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                    <?php if ($shouldUseAdminInterface || $isMember): ?>
                    <a href="tickets.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-ticket-alt me-2"></i>Support Tickets
                    </a>
                    <a href="chat-management.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-headset me-2"></i>Chat Management
                    </a>
                    <?php endif; ?>

                    <?php if (hasAccess('notifications', $userRole, $supportAccess)): ?>
                    <a href="notifications.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-bell me-2"></i>Notifications
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Contact Methods -->
            <div class="col-lg-8">
                <!-- Contact Options -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="contact-method">
                            <div class="contact-icon email">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4>Email Support</h4>
                            <p class="text-muted">Send us an email and we'll respond within 24 hours</p>
                            <strong>support@vvusrc.edu.gh</strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="contact-method">
                            <div class="contact-icon phone">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h4>Phone Support</h4>
                            <p class="text-muted">Call us during business hours for immediate assistance</p>
                            <strong>+233 XX XXX XXXX</strong>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="contact-method" onclick="startLiveChat()">
                            <div class="contact-icon chat">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h4>Live Chat</h4>
                            <p class="text-muted">Chat with our support team in real-time</p>
                            <button class="btn btn-support btn-sm" onclick="startLiveChat()">Start Chat</button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="contact-method">
                            <div class="contact-icon ticket">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <h4>Support Ticket</h4>
                            <p class="text-muted">Submit a detailed support request below</p>
                            <small class="text-muted">Recommended for complex issues</small>
                        </div>
                    </div>
                </div>

                <!-- Support Ticket Form -->
                <div class="support-card">
                    <h3 class="mb-4">
                        <i class="fas fa-ticket-alt me-2"></i>Submit Support Ticket
                    </h3>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <input type="text" class="form-control" id="subject" name="subject" required 
                                       placeholder="Brief description of your issue">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="priority" class="form-label">Priority *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="technical">Technical Issue</option>
                                <option value="account">Account Management</option>
                                <option value="billing">Billing & Finance</option>
                                <option value="feature">Feature Request</option>
                                <option value="bug">Bug Report</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="6" required
                                      placeholder="Please provide detailed information about your issue, including steps to reproduce if applicable..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                All fields marked with * are required
                            </small>
                            <button type="submit" name="submit_ticket" class="btn btn-support">
                                <i class="fas fa-paper-plane me-2"></i>Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Response Time -->
                <div class="response-time">
                    <h4 class="mb-3">
                        <i class="fas fa-clock me-2"></i>Response Times
                    </h4>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Email Support:</span>
                            <strong>24 hours</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Live Chat:</span>
                            <strong>5 minutes</strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Phone Support:</span>
                            <strong>Immediate</strong>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>Support Tickets:</span>
                            <strong>12 hours</strong>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="faq-section mt-4">
                    <h4 class="mb-3">
                        <i class="fas fa-question-circle me-2"></i>Quick Answers
                    </h4>
                    <div class="accordion" id="quickFAQ">
                        <div class="accordion-item bg-transparent border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-white border-0" 
                                        type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How do I reset my password?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#quickFAQ">
                                <div class="accordion-body text-white">
                                    Click "Forgot Password" on the login page and follow the instructions sent to your email.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item bg-transparent border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-white border-0" 
                                        type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How do I update my profile?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#quickFAQ">
                                <div class="accordion-body text-white">
                                    Go to your profile page and click the "Edit Profile" button to update your information.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item bg-transparent border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-white border-0" 
                                        type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How do I create an event?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#quickFAQ">
                                <div class="accordion-body text-white">
                                    Navigate to Events > Add New Event and fill in the required information.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="support-card">
                    <h5 class="mb-3">
                        <i class="fas fa-external-link-alt me-2"></i>Helpful Links
                    </h5>
                    <div class="list-group list-group-flush">
                        <a href="user-guide.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-book me-2"></i>User Guide
                        </a>
                        <a href="help-center.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-question-circle me-2"></i>Help Center
                        </a>
                        <a href="../dashboard.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="../settings.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </div>
                </div>
            </div>
</div>
</div>

        </div> <!-- Close container-fluid -->
    </div> <!-- Close main-content -->

<script>
function startLiveChat() {
    // Open live chat in a new window/tab
    window.open('live-chat.php', 'livechat', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

// Add click event to prevent form submission when clicking the chat method
document.addEventListener('DOMContentLoaded', function() {
    const chatMethod = document.querySelector('.contact-method .contact-icon.chat').closest('.contact-method');
    if (chatMethod) {
        chatMethod.addEventListener('click', function(e) {
            e.preventDefault();
            startLiveChat();
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
