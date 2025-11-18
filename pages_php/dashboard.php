<?php
// Include simple authentication
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Include settings functions
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login
requireLogin();

// Check if end_date column exists in events table
$checkColumnSQL = "SHOW COLUMNS FROM events LIKE 'end_date'";
$result = mysqli_query($conn, $checkColumnSQL);
if ($result && mysqli_num_rows($result) == 0) {
    // end_date column doesn't exist, add it
    $addColumnSQL = "ALTER TABLE events ADD COLUMN end_date DATE NULL AFTER date";
    mysqli_query($conn, $addColumnSQL);
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Dashboard - " . $siteName;
$bodyClass = "page-dashboard";

// Get current user info
$currentUser = getCurrentUser();
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$isMember = isMember();
$isStudent = isStudent();
$isFinance = isFinance();
$hasAdminPrivileges = hasAdminPrivileges();
$hasMemberPrivileges = hasMemberPrivileges();
$canManageContent = $hasMemberPrivileges; // Allow super admin, admin, member, and finance to manage content

// Get feature settings for the dashboard
$featureSettings = [];
$features = [
    'enable_elections' => 'Elections',
    'enable_documents' => 'Documents',
    'enable_news' => 'News',
    'enable_budget' => 'Budget',
    'enable_about' => 'About',
    'enable_events' => 'Events',
    'enable_gallery' => 'Gallery',
    'enable_minutes' => 'Minutes',
    'enable_reports' => 'Reports',
    'enable_portfolios' => 'Portfolios',
    'enable_departments' => 'Departments',
    'enable_senate' => 'Senate',
    'enable_committees' => 'Committees',
    'enable_feedback' => 'Feedback',
    'enable_welfare' => 'Welfare',
    'enable_support' => 'Support',
    'enable_finance' => 'Finance'
];

foreach ($features as $key => $label) {
    $featureSettings[$key] = [
        'label' => $label,
        'enabled' => function_exists('isFeatureEnabled') ? isFeatureEnabled($key) : true
    ];
}

// Get user's name and first initial for avatar
$fullName = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
if (empty(trim($fullName))) {
    $fullName = $currentUser['username'] ?? 'User';
}
$userInitial = strtoupper(substr($fullName, 0, 1));
$userName = $fullName;
$userRole = ucfirst($currentUser['role'] ?? 'User');

// Get actual counts from database
$stats = [];

// Count events
$stats['events'] = 0;
try {
    $eventCountSql = "SELECT COUNT(*) as count FROM events";
    $eventCountResult = fetchOne($eventCountSql);
    $stats['events'] = $eventCountResult ? $eventCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count news articles
$stats['news'] = 0;
try {
    // Count all news articles without filtering by status
    $allNewsCountSql = "SELECT COUNT(*) as count FROM news";
    $allNewsCountResult = fetchOne($allNewsCountSql);
    $stats['news'] = $allNewsCountResult ? $allNewsCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count documents
$stats['documents'] = 0;
try {
    $docCountSql = "SELECT COUNT(*) as count FROM documents WHERE status = 'active'";
    $docCountResult = fetchOne($docCountSql);
    $stats['documents'] = $docCountResult ? $docCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count gallery items
$stats['gallery'] = 0;
try {
    $galleryCountSql = "SELECT COUNT(*) as count FROM gallery WHERE status = 'active'";
    $galleryCountResult = fetchOne($galleryCountSql);
    $stats['gallery'] = $galleryCountResult ? $galleryCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count active elections
$stats['elections'] = 0;
try {
    // First try to count active elections
    $electionCountSql = "SELECT COUNT(*) as count FROM elections WHERE status = 'active'";
    $electionCountResult = fetchOne($electionCountSql);
    $stats['elections'] = $electionCountResult ? $electionCountResult['count'] : 0;

    // If no active elections found, try other common statuses
    if ($stats['elections'] == 0) {
        $commonStatuses = ['ongoing', 'current', 'open', 'live'];
        foreach ($commonStatuses as $status) {
            $altStatusSql = "SELECT COUNT(*) as count FROM elections WHERE status = '$status'";
            $altStatusResult = fetchOne($altStatusSql);
            if ($altStatusResult && $altStatusResult['count'] > 0) {
                $stats['elections'] = $altStatusResult['count'];
                break;
            }
        }

        // If still no results, just count all elections
        if ($stats['elections'] == 0) {
            $allElectionsSql = "SELECT COUNT(*) as count FROM elections";
            $allElectionsResult = fetchOne($allElectionsSql);
            $stats['elections'] = $allElectionsResult ? $allElectionsResult['count'] : 0;
        }
    }
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count portfolios
$stats['portfolios'] = 0;
try {
    $portfolioCountSql = "SELECT COUNT(*) as count FROM portfolios";
    $portfolioCountResult = fetchOne($portfolioCountSql);
    $stats['portfolios'] = $portfolioCountResult ? $portfolioCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count minutes
$stats['minutes'] = 0;
try {
    $minutesCountSql = "SELECT COUNT(*) as count FROM minutes";
    $minutesCountResult = fetchOne($minutesCountSql);
    $stats['minutes'] = $minutesCountResult ? $minutesCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count reports
$stats['reports'] = 0;
try {
    $reportsCountSql = "SELECT COUNT(*) as count FROM reports";
    $reportsCountResult = fetchOne($reportsCountSql);
    $stats['reports'] = $reportsCountResult ? $reportsCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count departments
$stats['departments'] = 0;
try {
    $deptCountSql = "SELECT COUNT(*) as count FROM departments";
    $deptCountResult = fetchOne($deptCountSql);
    $stats['departments'] = $deptCountResult ? $deptCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Count feedback
$stats['feedback'] = 0;
try {
    $feedbackCountSql = "SELECT COUNT(*) as count FROM feedback";
    $feedbackCountResult = fetchOne($feedbackCountSql);
    $stats['feedback'] = $feedbackCountResult ? $feedbackCountResult['count'] : 0;
} catch (Exception $e) {
    // Table might not exist, keep default 0
}

// Fetch upcoming events
$upcomingEvents = [];
try {
    // Add a cache-busting parameter to ensure fresh data
    $cacheBuster = time();

    // First check if end_date column exists
    $checkColumnSQL = "SHOW COLUMNS FROM events LIKE 'end_date'";
    $result = mysqli_query($conn, $checkColumnSQL);
    $endDateExists = $result && mysqli_num_rows($result) > 0;

    if ($endDateExists) {
        // Use query with end_date if it exists
        $upcomingEventsSql = "SELECT event_id, title, date, location,
                         CASE
                             WHEN date > CURDATE() THEN 'Upcoming'
                             WHEN date = CURDATE() THEN 'Today'
                             WHEN end_date IS NOT NULL AND end_date >= CURDATE() THEN 'Ongoing'
                             ELSE 'Ongoing'
                         END as status
                         FROM events
                         WHERE date >= CURDATE() OR
                               (end_date IS NOT NULL AND end_date >= CURDATE())
                         ORDER BY date ASC
                         LIMIT 5";
    } else {
        // Use simpler query without end_date
        $upcomingEventsSql = "SELECT event_id, title, date, location,
                         CASE
                             WHEN date > CURDATE() THEN 'Upcoming'
                             WHEN date = CURDATE() THEN 'Today'
                             ELSE 'Ongoing'
                         END as status
                         FROM events
                         WHERE date >= CURDATE()
                         ORDER BY date ASC
                         LIMIT 5";
    }

    $upcomingEvents = fetchAll($upcomingEventsSql);
} catch (Exception $e) {
    // Table might not exist, keep default empty array
}

// Fetch recent news
$recentNews = [];
try {
    // Add a cache-busting parameter to ensure fresh data
    $cacheBuster = time();

    // Updated query to not filter by status and not reference non-existent column
    $recentNewsSql = "SELECT news_id as id, title, DATE_FORMAT(created_at, '%Y-%m-%d') as date,
                 COALESCE((SELECT username FROM users WHERE user_id = author_id), 'System') as author, status
                 FROM news
                 ORDER BY created_at DESC
                 LIMIT 3";
    $recentNews = fetchAll($recentNewsSql);
} catch (Exception $e) {
    // Table might not exist, keep default empty array
}

// Fetch recent activities from the database instead of mock data
$recentActivities = [];
try {
    // Check if user_activities table exists
    $tableCheckSql = "SHOW TABLES LIKE 'user_activities'";
    $tableExists = mysqli_query($conn, $tableCheckSql);

    if ($tableExists && mysqli_num_rows($tableExists) > 0) {
        // Table exists, fetch recent activities
        $recentActivitiesSql = "SELECT a.*, u.first_name, u.last_name, u.role
                               FROM user_activities a
                               LEFT JOIN users u ON a.user_id = u.user_id
                               ORDER BY a.created_at DESC
                               LIMIT 5";
        $activitiesData = fetchAll($recentActivitiesSql);

        foreach ($activitiesData as $activity) {
            $fullName = trim($activity['first_name'] . ' ' . $activity['last_name']);
            $userName = !empty($fullName) ? $fullName : $activity['username'];

            // Calculate time ago
            $activityTime = strtotime($activity['created_at']);
            $now = time();
            $timeDiff = $now - $activityTime;

            if ($timeDiff < 60) {
                $timeAgo = $timeDiff . " seconds ago";
            } elseif ($timeDiff < 3600) {
                $timeAgo = floor($timeDiff / 60) . " minutes ago";
            } elseif ($timeDiff < 86400) {
                $timeAgo = floor($timeDiff / 3600) . " hours ago";
            } else {
                $timeAgo = floor($timeDiff / 86400) . " days ago";
            }

            // Determine activity type
            $type = '';
            switch ($activity['activity_type']) {
                case 'login':
                    $type = 'user';
                    $action = 'logged in';
                    $title = 'to the system';
                    break;
                case 'logout':
                    $type = 'user';
                    $action = 'logged out';
                    $title = 'from the system';
                    break;
                case 'create':
                case 'update':
                case 'delete':
                case 'view':
                default:
                    $type = 'system';
                    $action = $activity['activity_type'];
                    $title = $activity['activity_description'];
                    break;
            }

            $recentActivities[] = [
                'type' => $type,
                'action' => $action,
                'title' => $title,
                'user' => $userName,
                'time' => $timeAgo
            ];
        }
    } else {
        // Table doesn't exist yet, use mock data
        $recentActivities = [
            ['type' => 'document', 'action' => 'uploaded', 'title' => 'SRC Constitution', 'user' => 'Admin', 'time' => '2 hours ago'],
            ['type' => 'event', 'action' => 'created', 'title' => 'Annual General Meeting', 'user' => 'Secretary', 'time' => '5 hours ago'],
            ['type' => 'news', 'action' => 'published', 'title' => 'Campus Newsletter Vol. 12', 'user' => 'Editor', 'time' => '1 day ago'],
            ['type' => 'feedback', 'action' => 'received', 'title' => 'New feedback from a student', 'user' => 'System', 'time' => '2 days ago']
        ];
    }
} catch (Exception $e) {
    // If there's an error, fall back to mock data
    $recentActivities = [
        ['type' => 'document', 'action' => 'uploaded', 'title' => 'SRC Constitution', 'user' => 'Admin', 'time' => '2 hours ago'],
        ['type' => 'event', 'action' => 'created', 'title' => 'Annual General Meeting', 'user' => 'Secretary', 'time' => '5 hours ago'],
        ['type' => 'news', 'action' => 'published', 'title' => 'Campus Newsletter Vol. 12', 'user' => 'Editor', 'time' => '1 day ago'],
        ['type' => 'feedback', 'action' => 'received', 'title' => 'New feedback from a student', 'user' => 'System', 'time' => '2 days ago']
    ];
}

// Get current date
$currentDate = date('l, F j, Y');

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}
?>

<script>
    document.body.classList.add('documents-page');
</script>

<style>
    .feature-status-card {
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
    }

    .feature-status-card.enabled {
        background-color: #e8f5e9;
        border-left: 4px solid #4caf50;
    }

    .feature-status-card.disabled {
        background-color: #ffebee;
        border-left: 4px solid #f44336;
    }

    .feature-status-icon {
        font-size: 24px;
        margin-right: 15px;
    }

    .feature-status-card.enabled .feature-status-icon {
        color: #4caf50;
    }

    .feature-status-card.disabled .feature-status-icon {
        color: #f44336;
    }

    .feature-status-info h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
    }

    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        margin-top: 5px;
    }

    .status-enabled {
        background-color: #4caf50;
        color: white;
    }

    .status-disabled {
        background-color: #f44336;
        color: white;
    }

    .content-card-actions {
        display: flex;
        align-items: center;
    }
</style>


<?php
// Custom Dashboard Header
?>

<!-- Custom Dashboard Header -->
<div class="dashboard-header animate__animated animate__fadeInDown" id="dashboard-header-mobile">
    <div class="dashboard-header-content">
        <div class="dashboard-header-main">
            <h1 class="dashboard-title">
                <i class="fas fa-tachometer-alt me-3"></i>
                Dashboard
            </h1>
            <p class="dashboard-description">Welcome to your SRC management dashboard</p>
        </div>
    </div>
</div>

<style>
/* === DASHBOARD HEADER STYLES === */
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2.5rem;
    border-radius: 20px;
    margin-top: 80px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.5;
}

.dashboard-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.dashboard-header-main {
    width: 100%;
    max-width: 600px;
}

.dashboard-title {
    font-size: 2.75rem;
    font-weight: 800;
    margin: 0 0 0.75rem 0;
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    letter-spacing: -0.5px;
    line-height: 1.2;
}

.dashboard-title i {
    font-size: 2.5rem;
    opacity: 0.95;
    animation: iconBounce 2s ease-in-out infinite;
    flex-shrink: 0;
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.dashboard-description {
    margin: 0;
    opacity: 0.98;
    font-size: 1.15rem;
    font-weight: 400;
    line-height: 1.5;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* === RESPONSIVE MEDIA QUERIES - FOLLOWING ABOUT.PHP PATTERN === */

/* Large Desktop (1200px+) */
@media (min-width: 1200px) {
    .dashboard-header {
        margin-top: 80px;
        padding: 3rem 2.5rem;
    }
    
    .dashboard-title {
        font-size: 2.75rem;
    }
    
    .dashboard-title i {
        font-size: 2.5rem;
    }
    
    .dashboard-description {
        font-size: 1.15rem;
    }
}

/* Tablet and Below (< 1200px) */
@media (max-width: 1199px) {
    .dashboard-header {
        margin-top: 70px;
        padding: 2.75rem 2.25rem;
    }
    
    .dashboard-title {
        font-size: 2.5rem;
    }
    
    .dashboard-title i {
        font-size: 2.25rem;
    }
}

/* Tablet (< 992px) */
@media (max-width: 991px) {
    .dashboard-header {
        margin-top: 60px;
        padding: 2.5rem 2rem;
        border-radius: 16px;
    }
    
    .dashboard-title {
        font-size: 2.25rem;
        gap: 0.75rem;
    }
    
    .dashboard-title i {
        font-size: 2rem;
    }
    
    .dashboard-description {
        font-size: 1.05rem;
    }
}

/* Small Tablet (< 768px) */
@media (max-width: 767px) {
    .dashboard-header {
        margin-top: 45px;
        margin-bottom: 1.5rem;
        padding: 2rem 1.5rem;
        border-radius: 14px;
    }
    
    .dashboard-title {
        font-size: 1.9rem;
        gap: 0.6rem;
        margin-bottom: 0.5rem;
    }
    
    .dashboard-title i {
        font-size: 1.7rem;
    }
    
    .dashboard-description {
        font-size: 0.95rem;
    }
}

/* Mobile (< 576px) */
@media (max-width: 575px) {
    .dashboard-header {
        margin-top: 35px;
        margin-bottom: 1.25rem;
        padding: 1.5rem 1.25rem;
        border-radius: 12px;
    }
    
    .dashboard-title {
        font-size: 1.6rem;
        gap: 0.5rem;
        margin-bottom: 0.4rem;
    }
    
    .dashboard-title i {
        font-size: 1.45rem;
    }
    
    .dashboard-description {
        font-size: 0.9rem;
    }
}

/* Small Mobile (< 480px) */
@media (max-width: 479px) {
    .dashboard-header {
        margin-top: 28px;
        margin-bottom: 1rem;
        padding: 1.25rem 1rem;
        border-radius: 10px;
    }
    
    .dashboard-title {
        font-size: 1.4rem;
        gap: 0.4rem;
        margin-bottom: 0.3rem;
    }
    
    .dashboard-title i {
        font-size: 1.25rem;
    }
    
    .dashboard-description {
        font-size: 0.85rem;
    }
}

/* Extra Small Mobile (< 375px) */
@media (max-width: 374px) {
    .dashboard-header {
        margin-top: 24px;
        margin-bottom: 0.9rem;
        padding: 1.1rem 0.9rem;
        border-radius: 10px;
    }
    
    .dashboard-title {
        font-size: 1.25rem;
        gap: 0.3rem;
        margin-bottom: 0.25rem;
    }
    
    .dashboard-title i {
        font-size: 1.1rem;
    }
    
    .dashboard-description {
        font-size: 0.8rem;
    }
}

/* Ultra Small Mobile (< 320px) */
@media (max-width: 319px) {
    .dashboard-header {
        margin-top: 20px;
        margin-bottom: 0.8rem;
        padding: 1rem 0.75rem;
        border-radius: 8px;
    }
    
    .dashboard-title {
        font-size: 1.1rem;
        gap: 0.25rem;
        margin-bottom: 0.2rem;
    }
    
    .dashboard-title i {
        font-size: 1rem;
    }
    
    .dashboard-description {
        font-size: 0.75rem;
    }
}

/* Animation classes */
/* === STATS CARDS STYLING === */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--card-color, #667eea) 0%, var(--card-color-light, #764ba2) 100%);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.stat-card:hover::before {
    transform: scaleX(1);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.stat-card-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.stat-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.stat-card:hover .stat-card-icon {
    transform: rotate(10deg) scale(1.1);
}

.stat-card-icon.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card-icon.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.stat-card-icon.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.stat-card-icon.info {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.stat-card-icon.danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.stat-card-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #1f2937;
    margin: 0.5rem 0 1rem 0;
    line-height: 1;
}

.stat-card-link {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.stat-card-link::after {
    content: '→';
    transition: transform 0.3s ease;
}

.stat-card-link:hover {
    color: #764ba2;
    gap: 0.75rem;
}

.stat-card-link:hover::after {
    transform: translateX(5px);
}

/* Responsive Stats Cards */
@media (max-width: 1199px) {
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
    }
    
    .stat-card {
        padding: 1.6rem;
    }
    
    .stat-card-value {
        font-size: 2.25rem;
    }
}

@media (max-width: 991px) {
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .stat-card {
        padding: 1.5rem;
    }
    
    .stat-card-title {
        font-size: 0.85rem;
    }
    
    .stat-card-value {
        font-size: 2rem;
    }
    
    .stat-card-link {
        font-size: 0.85rem;
    }
}

@media (max-width: 767px) {
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }
    
    .stat-card {
        padding: 1.25rem;
    }
    
    .stat-card-header {
        margin-bottom: 1rem;
    }
    
    .stat-card-title {
        font-size: 0.8rem;
    }
    
    .stat-card-icon {
        width: 45px;
        height: 45px;
        font-size: 1.25rem;
    }
    
    .stat-card-value {
        font-size: 1.75rem;
        margin: 0.4rem 0 0.8rem 0;
    }
    
    .stat-card-link {
        font-size: 0.8rem;
    }
}

@media (max-width: 575px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
        gap: 0.9rem;
    }
    
    .stat-card {
        padding: 1.1rem;
    }
    
    .stat-card-value {
        font-size: 1.6rem;
        margin: 0.3rem 0 0.6rem 0;
    }
}

@media (max-width: 479px) {
    .dashboard-stats {
        gap: 0.8rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-card-title {
        font-size: 0.75rem;
    }
    
    .stat-card-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }
    
    .stat-card-value {
        font-size: 1.4rem;
    }
    
    .stat-card-link {
        font-size: 0.75rem;
    }
}

@media (max-width: 374px) {
    .dashboard-stats {
        gap: 0.7rem;
    }
    
    .stat-card {
        padding: 0.9rem;
    }
    
    .stat-card-value {
        font-size: 1.25rem;
    }
}

/* === CONTENT CARDS === */
.content-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.content-card:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.content-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.content-card-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.content-card-title i {
    color: #667eea;
    font-size: 1.4rem;
}

.content-card-body {
    padding: 2rem;
}

/* Responsive Content Cards */
@media (max-width: 991px) {
    .content-card-header {
        padding: 1.25rem 1.5rem;
    }
    
    .content-card-title {
        font-size: 1.35rem;
    }
    
    .content-card-body {
        padding: 1.75rem;
    }
}

@media (max-width: 767px) {
    .content-card-header {
        padding: 1.1rem 1.25rem;
    }
    
    .content-card-title {
        font-size: 1.2rem;
    }
    
    .content-card-title i {
        font-size: 1.2rem;
    }
    
    .content-card-body {
        padding: 1.5rem;
    }
}

@media (max-width: 575px) {
    .content-card {
        margin-bottom: 1.5rem;
        border-radius: 12px;
    }
    
    .content-card-header {
        padding: 1rem 1.1rem;
        gap: 0.75rem;
    }
    
    .content-card-title {
        font-size: 1.1rem;
    }
    
    .content-card-body {
        padding: 1.25rem;
    }
}

@media (max-width: 479px) {
    .content-card-title {
        font-size: 1rem;
    }
    
    .content-card-title i {
        font-size: 1rem;
    }
    
    .content-card-body {
        padding: 1rem;
    }
}

/* === QUICK ACTION BUTTONS === */
.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 12px;
    text-decoration: none;
    color: #1f2937;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
    min-height: 120px;
    animation: fadeInUp 0.6s ease calc(var(--btn-index, 0) * 0.05s) both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.quick-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.quick-action-btn:hover i {
    color: white !important;
    transform: scale(1.1);
}

.quick-action-btn i {
    transition: all 0.3s ease;
    margin-bottom: 0.75rem;
    font-size: 1.75rem;
}

.quick-action-btn div {
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
}

/* Responsive Quick Action Buttons */
@media (max-width: 991px) {
    .quick-action-btn {
        padding: 1.25rem 0.9rem;
        min-height: 110px;
    }
    
    .quick-action-btn i {
        font-size: 1.6rem;
        margin-bottom: 0.6rem;
    }
    
    .quick-action-btn div {
        font-size: 0.85rem;
    }
}

@media (max-width: 767px) {
    .quick-action-btn {
        padding: 1.2rem 0.8rem;
        min-height: 100px;
        border-radius: 10px;
    }
    
    .quick-action-btn i {
        font-size: 1.4rem;
        margin-bottom: 0.5rem;
    }
    
    .quick-action-btn div {
        font-size: 0.8rem;
    }
}

@media (max-width: 575px) {
    .quick-action-btn {
        padding: 1rem 0.7rem;
        min-height: 90px;
    }
    
    .quick-action-btn i {
        font-size: 1.25rem;
    }
    
    .quick-action-btn div {
        font-size: 0.75rem;
    }
}

@media (max-width: 479px) {
    .quick-action-btn {
        padding: 0.9rem 0.6rem;
        min-height: 80px;
    }
    
    .quick-action-btn i {
        font-size: 1.1rem;
        margin-bottom: 0.4rem;
    }
}

/* === DASHBOARD SECTION SPACING === */
.dashboard-section {
    margin-bottom: 2rem;
}

@media (max-width: 991px) {
    .dashboard-section {
        margin-bottom: 1.75rem;
    }
}

@media (max-width: 767px) {
    .dashboard-section {
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 575px) {
    .dashboard-section {
        margin-bottom: 1.25rem;
    }
}

/* === ANIMATION CLASSES === */
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

.animate-fadeIn {
    animation: fadeIn 0.6s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* === COMPREHENSIVE RESPONSIVE IMPROVEMENTS === */

/* Extra Large Screens (1400px+) */
@media (min-width: 1400px) {
    .dashboard-header {
        padding: 3.5rem 3rem;
    }
    
    .content-card-header {
        padding: 1.75rem 2.25rem;
    }
    
    .content-card-body {
        padding: 2.25rem;
    }
}

/* Large Desktop (1200px - 1399px) */
@media (min-width: 1200px) and (max-width: 1399px) {
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    }
}

/* Medium Desktop (992px - 1199px) */
@media (min-width: 992px) and (max-width: 1199px) {
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.1rem;
    }
}

/* Landscape Tablets (768px - 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .dashboard-header {
        margin-top: 50px;
        padding: 2.25rem 1.75rem;
    }
    
    .dashboard-title {
        font-size: 2rem;
    }
    
    .stat-card-value {
        font-size: 1.85rem;
    }
}

/* Portrait Tablets (576px - 767px) */
@media (min-width: 576px) and (max-width: 767px) {
    .dashboard-stats {
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    
    .stat-card {
        padding: 1.1rem;
    }
}

/* Mobile (< 576px) - Graduated approach */
@media (max-width: 575px) {
    body {
        font-size: 14px;
    }
    
    .dashboard-stats {
        margin-bottom: 1.5rem;
    }
    
    .stat-card-header {
        margin-bottom: 0.85rem;
    }
    
    .stat-card-link {
        margin-top: 0.5rem;
    }
}

/* Small Mobile (320px - 479px) */
@media (max-width: 479px) {
    body {
        font-size: 13px;
    }
    
    .dashboard-header {
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
    }
    
    .stat-card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
}
</style>

<script>
// Update notification badge with actual count
document.addEventListener('DOMContentLoaded', function() {
    const notificationBadge = document.querySelector('.notification-badge');

    // Function to update notification count
    function updateNotificationCount() {
        // Try to get count from the main header notification badge
        const mainNotificationBadge = document.querySelector('.notification-count');
        if (mainNotificationBadge && notificationBadge) {
            const count = mainNotificationBadge.textContent || '0';
            notificationBadge.textContent = count;

            // Hide badge if count is 0
            if (count === '0' || count === '') {
                notificationBadge.style.display = 'none';
            } else {
                notificationBadge.style.display = 'inline-block';
            }
        }
    }

    // Update count initially
    updateNotificationCount();

    // Update count periodically
    setInterval(updateNotificationCount, 5000);
});
</script>

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

<!-- Stats Overview -->
<div class="dashboard-stats" data-aos="fade-up" data-aos-duration="600">
    <div class="stat-card" style="--card-color: #667eea; --card-color-light: #764ba2;" data-aos="fade-up" data-aos-delay="0">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Events</h3>
            <div class="stat-card-icon primary">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['events']) ? $stats['events'] : 0; ?></h2>
        <a href="events.php" class="stat-card-link">View all events</a>
    </div>

    <div class="stat-card" style="--card-color: #10b981; --card-color-light: #059669;" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card-header">
            <h3 class="stat-card-title">News Articles</h3>
            <div class="stat-card-icon success">
                <i class="fas fa-newspaper"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['news']) ? $stats['news'] : 0; ?></h2>
        <a href="news.php" class="stat-card-link">View all news</a>
    </div>

    <div class="stat-card" style="--card-color: #f59e0b; --card-color-light: #d97706;" data-aos="fade-up" data-aos-delay="200">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Documents</h3>
            <div class="stat-card-icon warning">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['documents']) ? $stats['documents'] : 0; ?></h2>
        <a href="documents.php" class="stat-card-link">View all documents</a>
    </div>

    <div class="stat-card" style="--card-color: #3b82f6; --card-color-light: #2563eb;" data-aos="fade-up" data-aos-delay="300">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Gallery Items</h3>
            <div class="stat-card-icon info">
                <i class="fas fa-images"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['gallery']) ? $stats['gallery'] : 0; ?></h2>
        <a href="gallery.php" class="stat-card-link">View gallery</a>
    </div>

    <div class="stat-card" style="--card-color: #ef4444; --card-color-light: #dc2626;" data-aos="fade-up" data-aos-delay="400">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Active Elections</h3>
            <div class="stat-card-icon danger">
                <i class="fas fa-vote-yea"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['elections']) ? $stats['elections'] : 0; ?></h2>
        <a href="elections.php" class="stat-card-link">View all elections</a>
    </div>
</div>

<!-- Include AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 600,
        easing: 'ease-in-out',
        once: true,
        offset: 50
    });
</script>

<!-- Announcements & Events Cards -->
<div class="dashboard-section">
    <?php require_once 'includes/announcement_cards.php'; ?>
</div>

<!-- Content Row -->
<div class="dashboard-section">
    <div class="row">
        <!-- Gallery Carousel (full width) -->
        <div class="col-lg-12">
            <?php include 'gallery_carousel.php'; ?>
        </div>
    </div>
</div>

<!-- Role-Based Dashboard Content -->
<?php if ($isSuperAdmin): ?>
<!-- Super Admin Dashboard: Quick Actions and Feature Status -->

<!-- Quick Actions (Super Admin Only) -->
<div class="dashboard-section">
    <div class="row">
        <div class="col-lg-12">
            <div class="content-card animate-fadeIn">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="content-card-body">
                    <div class="row">
                        <!-- View Events -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="events.php" class="quick-action-btn" style="--btn-index: 1">
                                <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                <div>View Events</div>
                            </a>
                        </div>

                        <!-- Create Event -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="events.php?action=new" class="quick-action-btn" style="--btn-index: 2">
                                <i class="fas fa-calendar-plus fa-2x text-primary mb-2"></i>
                                <div>Create Event</div>
                            </a>
                        </div>

                        <!-- View News -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="news.php" class="quick-action-btn" style="--btn-index: 3">
                                <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                                <div>View News</div>
                            </a>
                        </div>

                        <!-- Post News -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="news.php?action=new" class="quick-action-btn" style="--btn-index: 4">
                                <i class="fas fa-pen fa-2x text-primary mb-2"></i>
                                <div>Post News</div>
                            </a>
                        </div>

                        <!-- View Documents -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="documents.php" class="quick-action-btn" style="--btn-index: 5">
                                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                <div>View Documents</div>
                            </a>
                        </div>

                        <!-- Upload Document -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="documents.php?action=new" class="quick-action-btn" style="--btn-index: 6">
                                <i class="fas fa-file-upload fa-2x text-primary mb-2"></i>
                                <div>Upload Document</div>
                            </a>
                        </div>

                        <!-- View Gallery -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="gallery.php" class="quick-action-btn" style="--btn-index: 7">
                                <i class="fas fa-images fa-2x text-primary mb-2"></i>
                                <div>View Gallery</div>
                            </a>
                        </div>

                        <!-- Upload to Gallery -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="gallery.php?action=upload" class="quick-action-btn" style="--btn-index: 8">
                                <i class="fas fa-camera fa-2x text-primary mb-2"></i>
                                <div>Upload to Gallery</div>
                            </a>
                        </div>

                        <!-- User Activities -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="user-activities.php" class="quick-action-btn" style="--btn-index: 9">
                                <i class="fas fa-history fa-2x text-primary mb-2"></i>
                                <div>User Activities</div>
                            </a>
                        </div>

                        <!-- Manage Feedback -->
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 text-center mb-3">
                            <a href="feedback.php" class="quick-action-btn" style="--btn-index: 10">
                                <i class="fas fa-comment-alt fa-2x text-primary mb-2"></i>
                                <div>Manage Feedback</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feature Status Section (Super Admin Only) -->
<div class="dashboard-section">
    <div class="row">
        <div class="col-lg-12">
            <div class="content-card animate-fadeIn">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-toggle-on"></i> Feature Status</h3>
                    <div class="content-card-actions">
                        <a href="settings.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-cog"></i> Manage Features
                        </a>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="row">
                        <?php foreach ($featureSettings as $key => $feature): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="feature-status-card <?php echo $feature['enabled'] ? 'enabled' : 'disabled'; ?>">
                                    <div class="feature-status-icon">
                                        <i class="fas <?php echo $feature['enabled'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                    </div>
                                    <div class="feature-status-info">
                                        <h4><?php echo htmlspecialchars($feature['label']); ?></h4>
                                        <span class="status-badge <?php echo $feature['enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                            <?php echo $feature['enabled'] ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Other Roles Dashboard: Simplified View -->
<div class="dashboard-section">
    <div class="row">
        <div class="col-lg-12">
            <div class="content-card animate-fadeIn">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-info-circle"></i>
                        Welcome, <?php echo htmlspecialchars($userName); ?>
                    </h3>
                    <div class="content-card-actions">
                        <span class="badge badge-<?php echo $userRole === 'Admin' ? 'warning' : ($userRole === 'Member' ? 'info' : ($userRole === 'Finance' ? 'success' : 'secondary')); ?>">
                            <?php echo htmlspecialchars($userRole); ?>
                        </span>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <p class="mb-3">Welcome to the VVUSRC Management System. Use the sidebar navigation to access the features available to your role.</p>

                            <?php if ($hasAdminPrivileges): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Admin Access:</strong> You have administrative privileges to manage content, view reports, and access most system features.
                            </div>
                            <?php elseif ($isMember): ?>
                            <div class="alert alert-primary">
                                <i class="fas fa-users me-2"></i>
                                <strong>Member Access:</strong> You can manage content, view reports, and access member-specific features.
                            </div>
                            <?php elseif ($isFinance): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-chart-line me-2"></i>
                                <strong>Finance Access:</strong> You have access to financial management features and content management.
                            </div>
                            <?php else: ?>
                            <div class="alert alert-light">
                                <i class="fas fa-user me-2"></i>
                                <strong>Student Access:</strong> You can view content, submit feedback, and access student-specific features.
                            </div>
                            <?php endif; ?>

                            <div class="row mt-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                            <h6>Events</h6>
                                            <p class="small text-muted">View upcoming events</p>
                                            <a href="events.php" class="btn btn-sm btn-primary">View Events</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <i class="fas fa-newspaper fa-2x text-info mb-2"></i>
                                            <h6>News</h6>
                                            <p class="small text-muted">Read latest news</p>
                                            <a href="news.php" class="btn btn-sm btn-info">View News</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <i class="fas fa-comment-alt fa-2x text-success mb-2"></i>
                                            <h6>Feedback</h6>
                                            <p class="small text-muted"><?php echo $canManageContent ? 'Manage feedback' : 'Submit feedback'; ?></p>
                                            <a href="feedback.php" class="btn btn-sm btn-success">
                                                <?php echo $canManageContent ? 'Manage' : 'Submit'; ?>
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
    </div>
</div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
