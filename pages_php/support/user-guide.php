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
$isStudent = isStudent();

// Get current user role
$userRole = $currentUser['role'] ?? 'student';

// Fetch user guide sections based on role
$guideSections = [];
try {
    $roleCondition = '';
    if ($userRole === 'student') {
        $roleCondition = "WHERE role_access = 'all'";
    } elseif ($userRole === 'member') {
        $roleCondition = "WHERE role_access IN ('all', 'member_admin')";
    } else { // admin
        $roleCondition = "WHERE role_access IN ('all', 'member_admin', 'admin_only')";
    }

    $guideSections = fetchAll("
        SELECT * FROM user_guide_sections
        $roleCondition AND is_active = 1
        ORDER BY sort_order ASC, title ASC
    ");
} catch (Exception $e) {
    // If table doesn't exist, use default sections
    $guideSections = [];
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "User Guide - " . $siteName;
$bodyClass = "page-user-guide";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "User Guide";
$pageIcon = "fa-book";
$pageDescription = "Complete guide to using the VVUSRC system";
$actions = [
    [
        'url' => 'index.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Support',
        'class' => 'btn-outline-light'
    ],
    [
        'url' => 'help-center.php',
        'icon' => 'fa-question-circle',
        'text' => 'Help Center',
        'class' => 'btn-secondary'
    ],
    [
        'url' => 'live-chat.php',
        'icon' => 'fa-comments',
        'text' => 'Live Chat',
        'class' => 'btn-secondary'
    ]
];

// Include the modern page header
include_once '../includes/modern_page_header.php';
?>

<style>
.user-guide-container {
    padding: 2rem 0;
}

.guide-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.guide-header .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
}

.guide-header h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 600;
}

.guide-header .lead {
    text-align: center;
    margin-bottom: 0;
    opacity: 0.9;
}

.guide-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.guide-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.guide-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.5rem;
    color: white;
    font-size: 1.5rem;
}

.guide-section h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
    font-weight: 600;
}

.guide-list {
    list-style: none;
    padding: 0;
}

.guide-list li {
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
    position: relative;
    padding-left: 2rem;
}

.guide-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #28a745;
    font-weight: bold;
}

.guide-list li:last-child {
    border-bottom: none;
}

.search-box {
    background: white;
    border-radius: 50px;
    padding: 1rem 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.btn-guide {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-guide:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.quick-links {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
}

.quick-links a {
    color: white;
    text-decoration: none;
    display: block;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.quick-links a:hover {
    padding-left: 1rem;
    transition: all 0.3s ease;
}

/* Tutorial-specific styles */
.tutorial-tooltip {
    position: fixed;
    z-index: 1051;
    animation: fadeInScale 0.3s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.tutorial-highlight {
    outline: 3px solid #007bff !important;
    outline-offset: 2px !important;
    position: relative !important;
    z-index: 1050 !important;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        outline-color: #007bff;
    }
    50% {
        outline-color: #0056b3;
    }
    100% {
        outline-color: #007bff;
    }
}

/* Enhanced FAQ styles */
.accordion-button {
    background-color: #f8f9fa;
    border: none;
    border-radius: 8px !important;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f3ff;
    color: #0066cc;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.accordion-body {
    background-color: #ffffff;
    border-radius: 0 0 8px 8px;
    border: 1px solid #e9ecef;
    border-top: none;
}

/* Enhanced tutorial modal styles */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.modal-header {
    border-radius: 15px 15px 0 0;
    border-bottom: none;
    padding: 1.5rem 2rem;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    border-radius: 0 0 15px 15px;
    padding: 1.5rem 2rem;
}

/* Toast container positioning */
.toast-container {
    z-index: 1055;
}

.toast {
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .guide-header {
        padding: 2rem 0;
    }

    .guide-card {
        padding: 1.5rem;
    }

    .search-box {
        padding: 0.75rem 1.5rem;
    }

    .tutorial-tooltip {
        max-width: 90vw !important;
        left: 5vw !important;
        right: 5vw !important;
    }

    .modal-dialog {
        margin: 1rem;
    }
}

/* Mobile Full-Width Optimization for User Guide Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .guide-header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    .card, .guide-card, .quick-links {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<!-- Main Content -->
<div class="container-fluid px-4" style="margin-top: 2rem;">
<div class="row">
        <!-- Search Box -->
        <div class="search-box">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-0" placeholder="Search for help topics..." id="guideSearch">
            </div>
        </div>

        <div class="row">
            <!-- Main Guide Content -->
            <div class="col-lg-8">
                <?php if (empty($guideSections)): ?>
                <!-- Default sections when database is not set up -->

                <!-- Getting Started -->
                <div class="guide-card guide-section">
                    <div class="guide-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Getting Started</h3>
                    <p class="text-muted mb-3"><?php echo $isStudent ? 'Learn the basics of using your student account.' : 'Learn the basics of navigating and using the SRC Management System.'; ?></p>
                    <ul class="guide-list">
                        <li><?php echo $isStudent ? 'Logging in with your student credentials' : 'Creating your account and logging in'; ?></li>
                        <li>Understanding the dashboard layout</li>
                        <li>Navigating the sidebar menu</li>
                        <li>Updating your profile information</li>
                        <?php if (!$isStudent): ?>
                        <li>Understanding user roles and permissions</li>
                        <?php endif; ?>
                        <li>Setting up your preferences</li>
                    </ul>
                    <a href="video-tutorials.php" class="btn btn-guide mt-3">
                        <i class="fas fa-play me-2"></i>Start Tutorial
                    </a>
                </div>

                <?php if (!$isStudent): ?>
                <!-- User Management - Members and Admins only -->
                <div class="guide-card guide-section" id="user-management">
                    <div class="guide-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>User Management</h3>
                    <p class="text-muted mb-3">Learn how to manage users and permissions in the system.</p>
                    <ul class="guide-list">
                        <li>Adding new users to the system</li>
                        <li>Assigning roles and permissions</li>
                        <li>Managing user profiles</li>
                        <li>Deactivating or removing users</li>
                        <?php if ($shouldUseAdminInterface): ?>
                        <li>Bulk user operations</li>
                        <li>System-wide user settings</li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-guide mt-3" data-action="open" data-target="../users.php">
                        <i class="fas fa-external-link-alt me-2"></i>Go to User Management
                    </button>
                </div>

                <!-- Events & News -->
                <div class="guide-card guide-section" id="events-news">
                    <div class="guide-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Events & News Management</h3>
                    <p class="text-muted mb-3">Create and manage events, news, and announcements.</p>
                    <ul class="guide-list">
                        <li>Creating and editing events</li>
                        <li>Publishing news articles</li>
                        <li>Managing event attendance</li>
                        <li>Setting up event notifications</li>
                        <?php if ($shouldUseAdminInterface): ?>
                        <li>Archiving old content</li>
                        <li>Content moderation</li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-guide mt-3" data-action="open" data-target="../events.php">
                        <i class="fas fa-external-link-alt me-2"></i>Go to Events
                    </button>
                </div>

                <!-- Financial Management -->
                <div class="guide-card guide-section" id="financial-management">
                    <div class="guide-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Financial Management</h3>
                    <p class="text-muted mb-3">Track budgets, expenses, and financial reports.</p>
                    <ul class="guide-list">
                        <li>Creating budget categories</li>
                        <li>Recording income and expenses</li>
                        <li>Generating financial reports</li>
                        <?php if ($shouldUseAdminInterface): ?>
                        <li>Setting up approval workflows</li>
                        <li>Exporting financial data</li>
                        <li>Financial system configuration</li>
                        <?php endif; ?>
                    </ul>
                    <button class="btn btn-guide mt-3" data-action="open" data-target="../finance.php">
                        <i class="fas fa-external-link-alt me-2"></i>Go to Finance
                    </button>
                </div>
                <?php else: ?>
                <!-- Student-specific sections -->
                <div class="guide-card guide-section" id="profile-management">
                    <div class="guide-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3>Profile Management</h3>
                    <p class="text-muted mb-3">Keep your profile information up to date.</p>
                    <ul class="guide-list">
                        <li>Updating personal information</li>
                        <li>Changing your password</li>
                        <li>Uploading a profile picture</li>
                        <li>Managing privacy settings</li>
                    </ul>
                    <button class="btn btn-guide mt-3" data-action="open" data-target="../profile.php">
                        <i class="fas fa-external-link-alt me-2"></i>Go to Profile
                    </button>
                </div>

                <div class="guide-card guide-section" id="events-participation">
                    <div class="guide-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Event Participation</h3>
                    <p class="text-muted mb-3">Learn how to participate in SRC events and activities.</p>
                    <ul class="guide-list">
                        <li>Viewing upcoming events</li>
                        <li>Registering for events</li>
                        <li>Checking event details</li>
                        <li>Receiving event notifications</li>
                    </ul>
                    <button class="btn btn-guide mt-3" data-action="open" data-target="../events.php">
                        <i class="fas fa-external-link-alt me-2"></i>View Events
                    </button>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Dynamic content from database -->
                <?php foreach ($guideSections as $section): ?>
                <div class="guide-card guide-section" id="section-<?php echo $section['section_id']; ?>">
                    <div class="guide-icon">
                        <i class="<?php echo htmlspecialchars($section['icon']); ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($section['title']); ?></h3>
                    <div class="text-muted mb-3">
                        <?php echo nl2br(htmlspecialchars($section['content'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Links -->
                <div class="quick-links">
                    <h4 class="mb-3">
                        <i class="fas fa-bolt me-2"></i>Quick Links
                    </h4>
                    <a href="../dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="help-center.php">
                        <i class="fas fa-question-circle me-2"></i>Help Center
                    </a>
                    <a href="contact-support.php">
                        <i class="fas fa-headset me-2"></i>Contact Support
                    </a>
                    <a href="../settings.php">
                        <i class="fas fa-cog me-2"></i>Settings
                    </a>
                </div>

                <!-- Video Tutorials -->
                <div class="guide-card mt-4">
                    <h4 class="mb-3">
                        <i class="fas fa-play-circle me-2"></i><?php echo $isStudent ? 'Quick Tutorials' : 'Video Tutorials'; ?>
                    </h4>
                    <div class="list-group list-group-flush">
                        <a href="video-tutorials.php" class="list-group-item list-group-item-action border-0 tutorial-link" data-tutorial="overview">
                            <i class="fas fa-play text-primary me-2"></i>System Overview (5:30)
                        </a>
                        <?php if ($isStudent): ?>
                        <a href="video-tutorials.php#profile" class="list-group-item list-group-item-action border-0 tutorial-link" data-tutorial="profile">
                            <i class="fas fa-play text-primary me-2"></i>Profile Setup (3:15)
                        </a>
                        <a href="video-tutorials.php#events" class="list-group-item list-group-item-action border-0 tutorial-link" data-tutorial="events">
                            <i class="fas fa-play text-primary me-2"></i>Viewing Events (4:20)
                        </a>
                        <?php else: ?>
                        <a href="video-tutorials.php#users" class="list-group-item list-group-item-action border-0 tutorial-link" data-tutorial="users">
                            <i class="fas fa-play text-primary me-2"></i>User Management (8:15)
                        </a>
                        <a href="video-tutorials.php#events" class="list-group-item list-group-item-action border-0 tutorial-link" data-tutorial="events">
                            <i class="fas fa-play text-primary me-2"></i>Event Creation (6:45)
                        </a>
                        <a href="video-tutorials.php#finance" class="list-group-item list-group-item-action border-0 tutorial-link" data-tutorial="finance">
                            <i class="fas fa-play text-primary me-2"></i>Financial Reports (7:20)
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="guide-card">
                    <h4 class="mb-3">
                        <i class="fas fa-question me-2"></i>Frequently Asked Questions
                    </h4>
                    <div class="accordion" id="faqAccordion">
                        <!-- Password Reset FAQ -->
                        <div class="accordion-item border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    <i class="fas fa-key me-2 text-warning"></i>How do I reset my password?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>You can reset your password in several ways:</p>
                                    <ol>
                                        <li><strong>From Login Page:</strong> Click "Forgot Password" link and enter your email</li>
                                        <li><strong>Contact Admin:</strong> Ask an administrator to reset it for you</li>
                                        <li><strong>Profile Settings:</strong> Change it from your profile if you're logged in</li>
                                    </ol>
                                    <div class="mt-3">
                                        <a href="../forgot_password.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-key me-1"></i>Reset Password
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Update FAQ -->
                        <div class="accordion-item border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    <i class="fas fa-user-edit me-2 text-info"></i>How do I update my profile?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>To update your profile information:</p>
                                    <ol>
                                        <li>Click on your name in the top-right corner</li>
                                        <li>Select "Profile" from the dropdown menu</li>
                                        <li>Edit your information and upload a profile picture</li>
                                        <li>Click "Save Changes" to update</li>
                                    </ol>
                                    <div class="mt-3">
                                        <a href="../profile.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-user me-1"></i>Edit Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$isStudent): ?>
                        <!-- Event Creation FAQ -->
                        <div class="accordion-item border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    <i class="fas fa-calendar-plus me-2 text-success"></i>How do I create an event?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>To create a new event:</p>
                                    <ol>
                                        <li>Navigate to the "Events" section from the sidebar</li>
                                        <li>Click the "Add New Event" button</li>
                                        <li>Fill in event details (title, description, date, location)</li>
                                        <li>Set event visibility and RSVP options</li>
                                        <li>Click "Create Event" to publish</li>
                                    </ol>
                                    <div class="mt-3">
                                        <a href="../events.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-calendar me-1"></i>Manage Events
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Notifications FAQ -->
                        <div class="accordion-item border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    <i class="fas fa-bell me-2 text-primary"></i>How do notifications work?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>The notification system keeps you updated with:</p>
                                    <ul>
                                        <li><strong>System Updates:</strong> Important announcements and changes</li>
                                        <li><strong>Event Notifications:</strong> New events and reminders</li>
                                        <li><strong>Administrative Messages:</strong> Official communications</li>
                                        <li><strong>Personal Alerts:</strong> Account-related notifications</li>
                                    </ul>
                                    <p>You can access notifications from the bell icon in the header or visit the notifications page.</p>
                                    <div class="mt-3">
                                        <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-bell me-1"></i>View Notifications
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Support FAQ -->
                        <div class="accordion-item border-0 mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    <i class="fas fa-life-ring me-2 text-danger"></i>How do I get help or support?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>There are several ways to get help:</p>
                                    <ul>
                                        <li><strong>Help Center:</strong> Browse our knowledge base and FAQs</li>
                                        <li><strong>Contact Support:</strong> Submit a support ticket for technical issues</li>
                                        <li><strong>User Guide:</strong> Follow step-by-step tutorials</li>
                                        <li><strong>Live Chat:</strong> Get real-time assistance (when available)</li>
                                    </ul>
                                    <div class="mt-3">
                                        <a href="contact-support.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-headset me-1"></i>Contact Support
                                        </a>
                                        <a href="help-center.php" class="btn btn-sm btn-outline-secondary ms-2">
                                            <i class="fas fa-question-circle me-1"></i>Help Center
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>
</div>

        </div> <!-- Close container-fluid -->
    </div> <!-- Close main-content -->

<script src="js/user-guide.js"></script>
<script>
// Initialize event handlers for buttons and tutorial links
document.addEventListener('DOMContentLoaded', function() {
    // Handle guide buttons
    const guideButtons = document.querySelectorAll('.btn-guide[data-action]');
    guideButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            const target = this.getAttribute('data-target');
            handleGuideButton(action, target);
        });
    });

    // Handle tutorial links
    const tutorialLinks = document.querySelectorAll('.tutorial-link[data-tutorial]');
    tutorialLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tutorialType = this.getAttribute('data-tutorial');
            showTutorial(tutorialType);
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
