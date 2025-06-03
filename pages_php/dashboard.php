<?php
// Include authentication file and database config
header('Content-Type: text/html; charset=utf-8');
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../auth_bridge.php'; // Add bridge for admin status consistency
require_once '../activity_functions.php'; // Include activity functions
require_once '../settings_functions.php';

// Create user_profiles table if it doesn't exist
$createTableSQL = "CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Execute the query to create table
mysqli_query($conn, $createTableSQL);

// Check if end_date column exists in events table
$checkColumnSQL = "SHOW COLUMNS FROM events LIKE 'end_date'";
$result = mysqli_query($conn, $checkColumnSQL);
if ($result && mysqli_num_rows($result) == 0) {
    // end_date column doesn't exist, add it
    $addColumnSQL = "ALTER TABLE events ADD COLUMN end_date DATE NULL AFTER date";
    mysqli_query($conn, $addColumnSQL);
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin() || getBridgedAdminStatus(); // Check both auth system and bridge
$isMember = isMember(); // Add member check
$canManageContent = $isAdmin || $isMember; // Allow both admins and members to manage content

// Get user profile data including full name
$userId = $currentUser['user_id'] ?? 0;
$userProfile = null;
if ($userId > 0) {
    try {
        $userProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Silently handle any database errors
    }
}

// Get user's name and first initial for avatar
$fullName = $userProfile['full_name'] ?? $currentUser['username'] ?? 'User';
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

// Set page title
$pageTitle = "Dashboard - " . $siteName;

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}
?>

<div class="header">
    <h1 class="page-title">Dashboard</h1>
    
    <div class="header-actions">
        <div class="header-date">
            <i class="far fa-calendar-alt"></i>
            <span><?php echo $currentDate; ?></span>
            </div>
        
                    <?php if ($canManageContent): ?>
        <a href="settings.php" class="btn btn-icon btn-primary animate-pulse">
                            <i class="fas fa-cog"></i>
                        </a>
                    <?php endif; ?>
    </div>
                </div>
            
<!-- Stats Overview -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Events</h3>
            <div class="stat-card-icon primary">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['events']) ? $stats['events'] : 0; ?></h2>
        <a href="events.php" class="stat-card-link">View all events →</a>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="stat-card-title">News Articles</h3>
            <div class="stat-card-icon success">
                <i class="fas fa-newspaper"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['news']) ? $stats['news'] : 0; ?></h2>
        <a href="news.php" class="stat-card-link">View all news →</a>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Documents</h3>
            <div class="stat-card-icon warning">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['documents']) ? $stats['documents'] : 0; ?></h2>
        <a href="documents.php" class="stat-card-link">View all documents →</a>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Gallery Items</h3>
            <div class="stat-card-icon info">
                <i class="fas fa-images"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['gallery']) ? $stats['gallery'] : 0; ?></h2>
        <a href="gallery.php" class="stat-card-link">View gallery →</a>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3 class="stat-card-title">Active Elections</h3>
            <div class="stat-card-icon danger">
                <i class="fas fa-vote-yea"></i>
            </div>
        </div>
        <h2 class="stat-card-value"><?php echo isset($stats['elections']) ? $stats['elections'] : 0; ?></h2>
        <a href="elections.php" class="stat-card-link">View all elections →</a>
    </div>
</div>

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

<!-- Quick Actions and Activity -->
<div class="dashboard-section">
    <div class="row">
        <!-- Quick Actions -->
        <div class="col-lg-12">
            <div class="content-card animate-fadeIn">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="content-card-body">
                    <div class="row">
                        <div class="col text-center">
                            <a href="events.php" class="quick-action-btn" style="--btn-index: 1">
                                <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                <div>View Events</div>
                            </a>
                        </div>
                        
                        <?php if ($canManageContent): ?>
                        <div class="col text-center">
                            <a href="events.php?action=new" class="quick-action-btn" style="--btn-index: 2">
                                <i class="fas fa-calendar-plus fa-2x text-primary mb-2"></i>
                                <div>Create Event</div>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col text-center">
                            <a href="news.php" class="quick-action-btn" style="--btn-index: 3">
                                <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                                <div>View News</div>
                            </a>
                        </div>
                        
                        <?php if ($canManageContent): ?>
                        <div class="col text-center">
                            <a href="news.php?action=new" class="quick-action-btn" style="--btn-index: 4">
                                <i class="fas fa-pen fa-2x text-primary mb-2"></i>
                                <div>Post News</div>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col text-center">
                            <a href="documents.php" class="quick-action-btn" style="--btn-index: 5">
                                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                <div>View Documents</div>
                            </a>
                        </div>
                        
                        <?php if ($canManageContent): ?>
                        <div class="col text-center">
                            <a href="documents.php?action=new" class="quick-action-btn" style="--btn-index: 6">
                                <i class="fas fa-file-upload fa-2x text-primary mb-2"></i>
                                <div>Upload Document</div>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col text-center">
                            <a href="gallery.php" class="quick-action-btn" style="--btn-index: 7">
                                <i class="fas fa-images fa-2x text-primary mb-2"></i>
                                <div>View Gallery</div>
                            </a>
                        </div>
                        
                        <?php if ($canManageContent): ?>
                        <div class="col text-center">
                            <a href="gallery.php?action=upload" class="quick-action-btn" style="--btn-index: 8">
                                <i class="fas fa-camera fa-2x text-primary mb-2"></i>
                                <div>Upload to Gallery</div>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($isAdmin): ?>
                        <div class="col text-center">
                            <a href="user-activities.php" class="quick-action-btn" style="--btn-index: 9">
                                <i class="fas fa-history fa-2x text-primary mb-2"></i>
                                <div>User Activities</div>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col text-center">
                            <a href="feedback.php" class="quick-action-btn" style="--btn-index: 10">
                                <i class="fas fa-comment-alt fa-2x text-primary mb-2"></i>
                                <div><?php echo $canManageContent ? 'Manage Feedback' : 'Submit Feedback'; ?></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>