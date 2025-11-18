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

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Help Center - " . $siteName;
$bodyClass = "page-help-center";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Help Center";
$pageIcon = "fa-question-circle";
$pageDescription = "Find answers to frequently asked questions";
$actions = [
    [
        'url' => 'index.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Support',
        'class' => 'btn-outline-light'
    ],
    [
        'url' => 'user-guide.php',
        'icon' => 'fa-book',
        'text' => 'User Guide',
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
.help-center-container {
    padding: 2rem 0;
}

.help-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.help-header .container-fluid {
    max-width: 1200px;
    margin: 0 auto;
}

.help-header h1 {
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    margin-bottom: 1rem;
    font-weight: 600;
}

.help-header .lead {
    text-align: center;
    margin-bottom: 0;
    opacity: 0.9;
}

.help-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
}

.help-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.help-category {
    text-align: center;
    padding: 2rem;
    border-radius: 15px;
    background: white;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    cursor: pointer;
}

.help-category:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.help-icon {
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

.help-icon.getting-started {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.help-icon.account {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.help-icon.features {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.help-icon.troubleshooting {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.help-icon.admin {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.help-icon.billing {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #333;
}

.search-container {
    background: white;
    border-radius: 50px;
    padding: 1rem 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 3rem;
}

.popular-articles {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
}

.article-item {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.article-item:hover {
    background: rgba(255,255,255,0.2);
    transform: translateX(10px);
}

.contact-support {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
}

.btn-help {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-help:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

@media (max-width: 768px) {
    .help-header {
        padding: 2rem 0;
    }
    
    .help-card {
        padding: 1.5rem;
    }
    
    .help-category {
        padding: 1.5rem;
    }
    
    .help-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}
</style>

<!-- Main Content -->
<div class="container-fluid px-4" style="margin-top: 2rem;">
    <div class="row">
        <!-- Search Box -->
        <div class="search-container">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-0" placeholder="Search for help articles..." id="helpSearch">
                <button class="btn btn-help" type="button">Search</button>
            </div>
        </div>

        <div class="row">
            <!-- Help Categories -->
            <div class="col-lg-8">
                <div class="row">
                    <!-- Getting Started -->
                    <div class="col-md-6">
                        <div class="help-category" onclick="showCategory('getting-started')">
                            <div class="help-icon getting-started">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <h4>Getting Started</h4>
                            <p class="text-muted">Learn the basics of using the system</p>
                            <small class="text-muted">12 articles</small>
                        </div>
                    </div>

                    <!-- Account Management -->
                    <div class="col-md-6">
                        <div class="help-category" onclick="showCategory('account')">
                            <div class="help-icon account">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <h4>Account Management</h4>
                            <p class="text-muted">Manage your profile and settings</p>
                            <small class="text-muted">8 articles</small>
                        </div>
                    </div>

                    <!-- Features & Tools -->
                    <div class="col-md-6">
                        <div class="help-category" onclick="showCategory('features')">
                            <div class="help-icon features">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h4>Features & Tools</h4>
                            <p class="text-muted">Explore system features and capabilities</p>
                            <small class="text-muted">15 articles</small>
                        </div>
                    </div>

                    <!-- Troubleshooting -->
                    <div class="col-md-6">
                        <div class="help-category" onclick="showCategory('troubleshooting')">
                            <div class="help-icon troubleshooting">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <h4>Troubleshooting</h4>
                            <p class="text-muted">Solve common issues and problems</p>
                            <small class="text-muted">10 articles</small>
                        </div>
                    </div>

                    <!-- Administration -->
                    <div class="col-md-6">
                        <div class="help-category" onclick="showCategory('admin')">
                            <div class="help-icon admin">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h4>Administration</h4>
                            <p class="text-muted">Admin tools and management features</p>
                            <small class="text-muted">6 articles</small>
                        </div>
                    </div>

                    <!-- Billing & Finance -->
                    <div class="col-md-6">
                        <div class="help-category" onclick="showCategory('billing')">
                            <div class="help-icon billing">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h4>Finance & Budget</h4>
                            <p class="text-muted">Financial management and reporting</p>
                            <small class="text-muted">9 articles</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Articles -->
                <div class="help-card mt-4">
                    <h3 class="mb-4">
                        <i class="fas fa-clock me-2"></i>Recent Articles
                    </h3>
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">How to create and manage events</h6>
                                <small>2 days ago</small>
                            </div>
                            <p class="mb-1">Step-by-step guide to creating events in the system.</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Setting up user permissions</h6>
                                <small>5 days ago</small>
                            </div>
                            <p class="mb-1">Learn how to configure user roles and permissions.</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Financial reporting best practices</h6>
                                <small>1 week ago</small>
                            </div>
                            <p class="mb-1">Tips for generating accurate financial reports.</p>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Popular Articles -->
                <div class="popular-articles">
                    <h4 class="mb-3">
                        <i class="fas fa-fire me-2"></i>Popular Articles
                    </h4>
                    <div class="article-item">
                        <h6 class="mb-1">How to reset your password</h6>
                        <small>1,234 views</small>
                    </div>
                    <div class="article-item">
                        <h6 class="mb-1">Creating your first event</h6>
                        <small>987 views</small>
                    </div>
                    <div class="article-item">
                        <h6 class="mb-1">Understanding user roles</h6>
                        <small>756 views</small>
                    </div>
                    <div class="article-item">
                        <h6 class="mb-1">Budget management basics</h6>
                        <small>654 views</small>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="contact-support mt-4">
                    <h4 class="mb-3">
                        <i class="fas fa-headset me-2"></i>Need More Help?
                    </h4>
                    <p>Can't find what you're looking for? Our support team is here to help!</p>
                    <a href="contact-support.php" class="btn btn-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>Contact Support
                    </a>
                </div>

                <!-- Quick Links -->
                <div class="help-card">
                    <h5 class="mb-3">
                        <i class="fas fa-external-link-alt me-2"></i>Quick Links
                    </h5>
                    <div class="list-group list-group-flush">
                        <a href="user-guide.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-book me-2"></i>User Guide
                        </a>
                        <a href="../dashboard.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="../settings.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                        <a href="notifications.php" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> <!-- Close container-fluid -->

<script src="js/help-center.js"></script>

<?php require_once '../includes/footer.php'; ?>
