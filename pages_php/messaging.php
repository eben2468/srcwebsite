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
$pageTitle = "Messaging Center";

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
        'Viewed Messaging Center page',
        $_SERVER['REQUEST_URI']
    );
}

// Get messaging statistics
$emailCount = 0;
$smsCount = 0;
$whatsappCount = 0;
$inAppCount = 0;

// Check if email_logs table exists
$checkTableSql = "SHOW TABLES LIKE 'email_logs'";
$tableExists = mysqli_query($conn, $checkTableSql);
if (mysqli_num_rows($tableExists) > 0) {
    $countSql = "SELECT COUNT(*) as count FROM email_logs";
    $result = mysqli_query($conn, $countSql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $emailCount = $row['count'];
    }
}

// Check if sms_logs table exists
$checkTableSql = "SHOW TABLES LIKE 'sms_logs'";
$tableExists = mysqli_query($conn, $checkTableSql);
if (mysqli_num_rows($tableExists) > 0) {
    $countSql = "SELECT COUNT(*) as count FROM sms_logs";
    $result = mysqli_query($conn, $countSql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $smsCount = $row['count'];
    }
}

// Check if whatsapp_logs table exists
$checkTableSql = "SHOW TABLES LIKE 'whatsapp_logs'";
$tableExists = mysqli_query($conn, $checkTableSql);
if (mysqli_num_rows($tableExists) > 0) {
    $countSql = "SELECT COUNT(*) as count FROM whatsapp_logs";
    $result = mysqli_query($conn, $countSql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $whatsappCount = $row['count'];
    }
}

// Check if notifications table exists
$checkTableSql = "SHOW TABLES LIKE 'notifications'";
$tableExists = mysqli_query($conn, $checkTableSql);
if (mysqli_num_rows($tableExists) > 0) {
    $countSql = "SELECT COUNT(*) as count FROM notifications";
    $result = mysqli_query($conn, $countSql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        $inAppCount = $row['count'];
    }
}
?>

<!-- Custom Messaging Header -->
<div class="messaging-header animate__animated animate__fadeInDown">
    <div class="messaging-header-content">
        <div class="messaging-header-main">
            <h1 class="messaging-title">
                <i class="fas fa-comments me-3"></i>
                Messaging Center
            </h1>
            <p class="messaging-description">Send messages through multiple channels - WhatsApp, SMS, and Email</p>
        </div>
        <div class="messaging-header-actions">
            <a href="messaging_settings.php" class="btn btn-header-action">
                <i class="fas fa-cog me-2"></i>Settings
            </a>
        </div>
    </div>
</div>

<style>
.messaging-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.messaging-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.messaging-header-main {
    flex: 1;
    text-align: center;
}

.messaging-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.messaging-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.messaging-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.messaging-header-actions {
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
    .messaging-header {
        padding: 2rem 1.5rem;
    }

    .messaging-header-content {
        flex-direction: column;
        align-items: center;
    }

    .messaging-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .messaging-title i {
        font-size: 1.8rem;
    }

    .messaging-description {
        font-size: 1.1rem;
    }

    .messaging-header-actions {
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

    <div class="row mb-4">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-cog me-1"></i>
                    Messaging Configuration
                </div>
                <div class="card-body">
                    <p>Configure your messaging providers to enable sending messages through different channels.</p>
                    <a href="../messaging_settings.php" class="btn btn-primary">
                        <i class="fas fa-cog me-1"></i> Messaging Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-comment-dots me-1"></i>
                    Select Messaging Platform
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- WhatsApp Messaging Card -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fab fa-whatsapp fa-4x mb-3 text-success"></i>
                                    <h5 class="card-title">WhatsApp</h5>
                                    <p class="card-text">Send bulk messages to users via WhatsApp</p>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-center pb-3">
                                    <a href="whatsapp_messaging.php" class="btn btn-success">Open WhatsApp Messaging</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SMS Messaging Card -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-sms fa-4x mb-3 text-primary"></i>
                                    <h5 class="card-title">SMS</h5>
                                    <p class="card-text">Send SMS messages to users via mobile networks</p>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-center pb-3">
                                    <a href="sms_messaging.php" class="btn btn-primary">Open SMS Messaging</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Messaging Platform Card -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-comment-alt fa-4x mb-3 text-warning"></i>
                                    <h5 class="card-title">In-App Messaging</h5>
                                    <p class="card-text">Send in-app messages to users</p>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-center pb-3">
                                    <a href="in_app_messaging.php" class="btn btn-warning">Open In-App Messaging</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Email Messaging Card -->
                        <div class="col-md-6 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-envelope fa-4x mb-3 text-danger"></i>
                                    <h5 class="card-title">Email</h5>
                                    <p class="card-text">Send bulk emails to users</p>
                                </div>
                                <div class="card-footer bg-transparent border-0 text-center pb-3">
                                    <a href="email_messaging.php" class="btn btn-danger">Open Email Messaging</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messaging Statistics Section -->
    <div class="row mb-4 messaging-stats-container">
        <div class="col-12">
            <div class="card shadow-lg border-0">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Messaging Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- WhatsApp Messages -->
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card bg-success">
                                <div class="stat-card-body">
                                    <div class="stat-icon">
                                        <i class="fab fa-whatsapp"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo number_format($whatsappCount); ?></h3>
                                        <p class="stat-label">WhatsApp<br>Messages</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Messages -->
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card bg-primary">
                                <div class="stat-card-body">
                                    <div class="stat-icon">
                                        <i class="fas fa-sms"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo number_format($smsCount); ?></h3>
                                        <p class="stat-label">SMS<br>Messages</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- In-App Messages -->
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card bg-warning">
                                <div class="stat-card-body">
                                    <div class="stat-icon">
                                        <i class="fas fa-comment-alt"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo number_format($inAppCount); ?></h3>
                                        <p class="stat-label">In-App<br>Messages</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Messages -->
                        <div class="col-lg-3 col-md-6">
                            <div class="stat-card bg-danger">
                                <div class="stat-card-body">
                                    <div class="stat-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="stat-content">
                                        <h3 class="stat-number"><?php echo number_format($emailCount); ?></h3>
                                        <p class="stat-label">Email<br>Messages</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Messaging Statistics Styling */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
}

/* Messaging Statistics Container */
.messaging-stats-container {
    margin: 2rem 0;
}

.messaging-stats-container .card {
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.messaging-stats-container .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
}

.messaging-stats-container .card-body {
    padding: 2rem;
    background: #f8fafb;
}

/* Enhanced Statistics Cards */
.stat-card {
    border-radius: 20px;
    color: white;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    height: 100%;
    border: none;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    position: relative;
    cursor: pointer;
    transform: translateY(0);
}

/* Card Background Gradients */
.stat-card.bg-success {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
}

.stat-card.bg-primary {
    background: linear-gradient(135deg, #4285F4 0%, #1a73e8 100%);
}

.stat-card.bg-warning {
    background: linear-gradient(135deg, #FFC107 0%, #FF8F00 100%);
}

.stat-card.bg-danger {
    background: linear-gradient(135deg, #DC3545 0%, #B02A37 100%);
}

/* Animated Background Pattern */
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="20" cy="20" r="1" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.6;
    pointer-events: none;
}

/* Floating Animation */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

.stat-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: float 2s ease-in-out infinite;
}

.stat-card-body {
    padding: 2.5rem 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    position: relative;
    z-index: 1;
    min-height: 140px;
}

/* Enhanced Icon Styling */
.stat-icon {
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 50%;
    backdrop-filter: blur(15px);
    border: 2px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-icon::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.6s ease;
    opacity: 0;
}

.stat-card:hover .stat-icon::before {
    animation: shimmer 1.5s ease-in-out;
    opacity: 1;
}

@keyframes shimmer {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

.stat-icon i {
    font-size: 2.2rem;
    color: white;
    z-index: 1;
    position: relative;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    background: rgba(255, 255, 255, 0.35);
}

/* Content Styling */
.stat-content {
    flex: 1;
    text-align: left;
}

.stat-number {
    font-size: 3rem;
    font-weight: 800;
    margin: 0;
    line-height: 1;
    color: white;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    letter-spacing: -1px;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-number {
    transform: scale(1.05);
    text-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
}

.stat-label {
    font-size: 1rem;
    margin: 0.8rem 0 0 0;
    opacity: 0.95;
    font-weight: 600;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.2;
}

/* Pulse Animation for Numbers */
@keyframes pulse-number {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.stat-card:hover .stat-number {
    animation: pulse-number 2s ease-in-out infinite;
}

/* Background Decoration */
.stat-card::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 120px;
    height: 120px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    transition: all 0.4s ease;
}

.stat-card:hover::after {
    transform: scale(1.5);
    opacity: 0.15;
}

/* Responsive Design */
@media (max-width: 992px) {
    .stat-card-body {
        padding: 2rem 1.5rem;
        min-height: 120px;
    }
    
    .stat-icon {
        width: 70px;
        height: 70px;
    }
    
    .stat-icon i {
        font-size: 2rem;
    }
    
    .stat-number {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .stat-card-body {
        padding: 2rem 1.5rem;
        flex-direction: column;
        text-align: center;
        gap: 1.2rem;
        min-height: 160px;
    }

    .stat-content {
        text-align: center;
    }

    .stat-number {
        font-size: 2.8rem;
    }

    .stat-icon {
        width: 70px;
        height: 70px;
    }

    .stat-icon i {
        font-size: 2rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .row.g-4 {
        gap: 1.5rem !important;
    }

    .stat-card-body {
        padding: 1.8rem 1.2rem;
    }
    
    .messaging-stats-container .card-body {
        padding: 1.5rem;
    }
    
    .messaging-stats-container .card-header {
        padding: 1.2rem 1.5rem;
    }
}

/* Loading Animation */
@keyframes countUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.stat-number {
    animation: countUp 0.8s ease-out 0.2s both;
}

.stat-label {
    animation: countUp 0.8s ease-out 0.4s both;
}

.stat-icon {
    animation: countUp 0.8s ease-out both;
}

/* Card entrance animation */
@keyframes slideInUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.stat-card {
    animation: slideInUp 0.6s ease-out both;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }</style>
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
