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

// Fetch video tutorials from database
$tutorials = [];
try {
    $sql = "SELECT * FROM video_tutorials WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Super admin and finance users get full access to all tutorials
            if ($userRole === 'super_admin' || $userRole === 'finance') {
                $tutorials[] = $row;
            } else {
                // Check if user role is in target roles for other users
                $targetRoles = json_decode($row['target_roles'], true) ?: [];
                if (empty($targetRoles) || in_array($userRole, $targetRoles)) {
                    $tutorials[] = $row;
                }
            }
        }
    }
} catch (Exception $e) {
    // If table doesn't exist, use default tutorials
    $tutorials = [];
}

// Get site name from settings with fallback
$siteName = 'VVU SRC Management System';
if (function_exists('getSetting')) {
    $siteName = getSetting('site_name', 'VVU SRC Management System');
}

// Set page title and body class
$pageTitle = "Video Tutorials - " . $siteName;
$bodyClass = "page-video-tutorials";

// Include header
require_once '../includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Video Tutorials";
$pageIcon = "fa-play-circle";
$pageDescription = "Learn how to use the VVUSRC system with step-by-step video guides";
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
        'url' => 'help-center.php',
        'icon' => 'fa-question-circle',
        'text' => 'Help Center',
        'class' => 'btn-secondary'
    ]
];

// Add create tutorial button for admin users
if ($shouldUseAdminInterface) {
    $actions[] = [
        'url' => 'admin-video-tutorials.php',
        'icon' => 'fa-plus',
        'text' => 'Add Tutorial',
        'class' => 'btn-primary'
    ];
}

// Include the modern page header
include_once '../includes/modern_page_header.php';
?>

<style>
.video-tutorials-container {
    padding: 2rem 0;
}

.tutorial-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 0;
    margin-bottom: 2rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}

.tutorial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.video-thumbnail {
    position: relative;
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.video-thumbnail:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
}

.video-thumbnail .play-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255,255,255,0.3);
}

.video-duration {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.tutorial-content {
    padding: 1.5rem;
}

.tutorial-title {
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.tutorial-description {
    color: #6c757d;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    line-height: 1.5;
}

.tutorial-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: #6c757d;
}

.difficulty-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.difficulty-beginner {
    background: #d4edda;
    color: #155724;
}

.difficulty-intermediate {
    background: #fff3cd;
    color: #856404;
}

.difficulty-advanced {
    background: #f8d7da;
    color: #721c24;
}

.tutorial-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.tutorial-tag {
    background: #e9ecef;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.btn-watch {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 25px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-watch:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    text-align: center;
}

.section-title {
    margin: 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.video-modal .modal-content {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.video-modal .modal-body {
    padding: 0;
    background: #000;
}

.video-placeholder {
    width: 100%;
    height: 400px;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

/* Admin Actions Bar */
.admin-actions-bar {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 2rem;
}

.admin-actions-bar h6 {
    color: #495057;
    font-weight: 600;
}

/* Floating Action Button for Mobile */
.fab-create {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
    z-index: 1000;
    transition: all 0.3s ease;
    display: none;
}

.fab-create:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
    color: white;
}

@media (max-width: 768px) {
    .tutorial-card {
        margin-bottom: 1.5rem;
    }
    
    .video-thumbnail {
        height: 150px;
        font-size: 2rem;
    }
    
    .play-icon {
        width: 60px !important;
        height: 60px !important;
    }
    
    .tutorial-content {
        padding: 1rem;
    }
    
    .section-header {
        padding: 1.5rem 1rem;
    }
    
    /* Show floating action button on mobile */
    .fab-create {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }
    
    /* Hide admin actions bar on mobile, show FAB instead */
    .admin-actions-bar {
        display: none;
    }
}
</style>

<!-- Main Content -->
<div class="container-fluid px-4" style="margin-top: 2rem;">
    <?php if ($shouldUseAdminInterface): ?>
    <!-- Admin Actions Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h6 class="text-muted mb-0">
                <i class="fas fa-shield-alt me-2"></i>Administrator Actions
            </h6>
        </div>
        <div>
            <a href="admin-video-tutorials.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Tutorial
            </a>
            <a href="admin-video-tutorials.php?action=manage" class="btn btn-outline-primary ms-2">
                <i class="fas fa-cog me-2"></i>Manage Tutorials
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($tutorials)): ?>
    <div class="text-center py-5">
        <i class="fas fa-video fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No video tutorials available</h5>
        <p class="text-muted">Video tutorials will appear here once they are added by administrators.</p>
        <?php if ($shouldUseAdminInterface): ?>
        <a href="admin-video-tutorials.php" class="btn btn-primary btn-lg">
            <i class="fas fa-plus me-2"></i>Add Your First Tutorial
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <!-- Group tutorials by category -->
    <?php
    $categorizedTutorials = [];
    foreach ($tutorials as $tutorial) {
        $category = $tutorial['category'] ?? 'general';
        if (!isset($categorizedTutorials[$category])) {
            $categorizedTutorials[$category] = [];
        }
        $categorizedTutorials[$category][] = $tutorial;
    }

    // Define category information
    $categoryInfo = [
        'system-overview' => ['title' => 'System Overview', 'icon' => 'fa-desktop', 'description' => 'Get familiar with the VVUSRC Management System interface and basic navigation'],
        'getting-started' => ['title' => 'Getting Started', 'icon' => 'fa-rocket', 'description' => 'Essential tutorials for new users'],
        'profile-setup' => ['title' => 'Profile Management', 'icon' => 'fa-user-edit', 'description' => 'Learn how to manage your profile and settings'],
        'viewing-events' => ['title' => 'Event Participation', 'icon' => 'fa-calendar-check', 'description' => 'Learn how to participate in SRC events and activities'],
        'user-management' => ['title' => 'User Management', 'icon' => 'fa-users-cog', 'description' => 'Administrative features for managing users'],
        'event-creation' => ['title' => 'Event Management', 'icon' => 'fa-calendar-plus', 'description' => 'Creating and managing events'],
        'financial-reports' => ['title' => 'Financial Management', 'icon' => 'fa-chart-line', 'description' => 'Financial features and reporting'],
        'content-management' => ['title' => 'Content Management', 'icon' => 'fa-edit', 'description' => 'Managing content and media'],
        'notifications' => ['title' => 'Notifications', 'icon' => 'fa-bell', 'description' => 'Understanding the notification system'],
        'support-system' => ['title' => 'Support Features', 'icon' => 'fa-life-ring', 'description' => 'Getting help and using support features'],
        'general' => ['title' => 'General Tutorials', 'icon' => 'fa-video', 'description' => 'Additional video tutorials']
    ];
    ?>

    <?php foreach ($categorizedTutorials as $category => $categoryTutorials): ?>
    <?php $catInfo = $categoryInfo[$category] ?? $categoryInfo['general']; ?>

    <div class="section-header" id="<?php echo htmlspecialchars($category); ?>">
        <h2 class="section-title">
            <i class="fas <?php echo $catInfo['icon']; ?>"></i>
            <?php echo $catInfo['title']; ?>
        </h2>
        <p class="mb-0 mt-2 opacity-90"><?php echo $catInfo['description']; ?></p>
    </div>

    <div class="row">
        <?php foreach ($categoryTutorials as $tutorial): ?>
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-tutorial-id="<?php echo $tutorial['tutorial_id']; ?>">
                    <?php if ($tutorial['thumbnail_image']): ?>
                    <img src="../../uploads/thumbnails/<?php echo htmlspecialchars($tutorial['thumbnail_image']); ?>"
                         alt="<?php echo htmlspecialchars($tutorial['title']); ?>"
                         style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration"><?php echo htmlspecialchars($tutorial['duration']); ?></div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title"><?php echo htmlspecialchars($tutorial['title']); ?></h5>
                    <p class="tutorial-description">
                        <?php echo htmlspecialchars($tutorial['description']); ?>
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($tutorial['duration']); ?></span>
                        <span class="difficulty-badge difficulty-<?php echo $tutorial['difficulty']; ?>">
                            <?php echo ucfirst($tutorial['difficulty']); ?>
                        </span>
                    </div>
                    <?php
                    $tags = json_decode($tutorial['tags'], true) ?: [];
                    if (!empty($tags)): ?>
                    <div class="tutorial-tags">
                        <?php foreach ($tags as $tag): ?>
                        <span class="tutorial-tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <button class="btn btn-watch" data-tutorial-id="<?php echo $tutorial['tutorial_id']; ?>">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($isStudent): ?>
    <!-- Student-specific tutorials -->
    <div class="section-header" id="profile">
        <h2 class="section-title">
            <i class="fas fa-user-graduate"></i>
            Student Features
        </h2>
        <p class="mb-0 mt-2 opacity-90">Learn how to use features available to students</p>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="profile-setup">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">3:15</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Profile Setup</h5>
                    <p class="tutorial-description">
                        Learn how to update your personal information, change your password, 
                        upload a profile picture, and manage your privacy settings.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>3 minutes 15 seconds</span>
                        <span class="difficulty-badge difficulty-beginner">Beginner</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Profile</span>
                        <span class="tutorial-tag">Settings</span>
                        <span class="tutorial-tag">Privacy</span>
                    </div>
                    <button class="btn btn-watch" data-video="profile-setup">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4" id="events">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="viewing-events">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">4:20</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Viewing Events</h5>
                    <p class="tutorial-description">
                        Discover how to view upcoming events, register for activities, 
                        check event details, and receive notifications about SRC events.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>4 minutes 20 seconds</span>
                        <span class="difficulty-badge difficulty-beginner">Beginner</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Events</span>
                        <span class="tutorial-tag">Registration</span>
                        <span class="tutorial-tag">Notifications</span>
                    </div>
                    <button class="btn btn-watch" data-video="viewing-events">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$isStudent): ?>
    <!-- Admin/Member-specific tutorials -->
    <div class="section-header" id="users">
        <h2 class="section-title">
            <i class="fas fa-users-cog"></i>
            Administrative Features
        </h2>
        <p class="mb-0 mt-2 opacity-90">Learn how to manage users, events, and system features</p>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="user-management">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">8:15</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">User Management</h5>
                    <p class="tutorial-description">
                        Comprehensive guide to managing users in the system. Learn how to add new users,
                        assign roles, manage permissions, and perform bulk operations.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>8 minutes 15 seconds</span>
                        <span class="difficulty-badge difficulty-intermediate">Intermediate</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Users</span>
                        <span class="tutorial-tag">Roles</span>
                        <span class="tutorial-tag">Permissions</span>
                        <span class="tutorial-tag">Bulk Operations</span>
                    </div>
                    <button class="btn btn-watch" data-video="user-management">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4" id="events">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="event-creation">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">6:45</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Event Creation</h5>
                    <p class="tutorial-description">
                        Learn how to create and manage events in the system. Cover event details,
                        RSVP settings, notifications, and event management best practices.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>6 minutes 45 seconds</span>
                        <span class="difficulty-badge difficulty-intermediate">Intermediate</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Events</span>
                        <span class="tutorial-tag">Creation</span>
                        <span class="tutorial-tag">RSVP</span>
                        <span class="tutorial-tag">Management</span>
                    </div>
                    <button class="btn btn-watch" data-video="event-creation">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4" id="finance">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="financial-reports">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">7:20</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Financial Reports</h5>
                    <p class="tutorial-description">
                        Master the financial management features. Learn how to track budgets,
                        record transactions, generate reports, and manage approval workflows.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>7 minutes 20 seconds</span>
                        <span class="difficulty-badge difficulty-advanced">Advanced</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Finance</span>
                        <span class="tutorial-tag">Reports</span>
                        <span class="tutorial-tag">Budgets</span>
                        <span class="tutorial-tag">Approvals</span>
                    </div>
                    <button class="btn btn-watch" data-video="financial-reports">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="content-management">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">5:50</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Content Management</h5>
                    <p class="tutorial-description">
                        Learn how to manage news articles, documents, gallery images,
                        and other content in the system with proper organization and permissions.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>5 minutes 50 seconds</span>
                        <span class="difficulty-badge difficulty-intermediate">Intermediate</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Content</span>
                        <span class="tutorial-tag">News</span>
                        <span class="tutorial-tag">Documents</span>
                        <span class="tutorial-tag">Gallery</span>
                    </div>
                    <button class="btn btn-watch" data-video="content-management">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Advanced Features Section -->
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-cogs"></i>
            Advanced Features
        </h2>
        <p class="mb-0 mt-2 opacity-90">Explore advanced system features and customization options</p>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="notifications">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">4:30</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Notification System</h5>
                    <p class="tutorial-description">
                        Understand how the notification system works, how to manage your notification
                        preferences, and how to stay updated with important announcements.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>4 minutes 30 seconds</span>
                        <span class="difficulty-badge difficulty-beginner">Beginner</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Notifications</span>
                        <span class="tutorial-tag">Alerts</span>
                        <span class="tutorial-tag">Preferences</span>
                    </div>
                    <button class="btn btn-watch" data-video="notifications">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4">
            <div class="tutorial-card">
                <div class="video-thumbnail" data-video="support-system">
                    <div class="play-icon">
                        <i class="fas fa-play"></i>
                    </div>
                    <div class="video-duration">3:45</div>
                </div>
                <div class="tutorial-content">
                    <h5 class="tutorial-title">Support System</h5>
                    <p class="tutorial-description">
                        Learn how to use the support features including help center,
                        contact support, live chat, and how to get assistance when needed.
                    </p>
                    <div class="tutorial-meta">
                        <span><i class="fas fa-clock me-1"></i>3 minutes 45 seconds</span>
                        <span class="difficulty-badge difficulty-beginner">Beginner</span>
                    </div>
                    <div class="tutorial-tags">
                        <span class="tutorial-tag">Support</span>
                        <span class="tutorial-tag">Help</span>
                        <span class="tutorial-tag">Chat</span>
                    </div>
                    <button class="btn btn-watch" data-video="support-system">
                        <i class="fas fa-play me-2"></i>Watch Tutorial
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($shouldUseAdminInterface): ?>
<!-- Floating Action Button for Mobile -->
<a href="admin-video-tutorials.php" class="fab-create" title="Create Tutorial">
    <i class="fas fa-plus"></i>
</a>
<?php endif; ?>

<!-- Video Modal -->
<div class="modal fade video-modal" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel">Tutorial Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="video-placeholder" id="videoContainer">
                    <div class="text-center">
                        <i class="fas fa-video fa-3x mb-3"></i>
                        <h5>Video Tutorial</h5>
                        <p class="mb-0">This is a placeholder for the video tutorial.</p>
                        <p class="small text-muted mt-2">In a real implementation, this would contain an embedded video player.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Video tutorial data from PHP
    const tutorials = <?php echo json_encode($tutorials); ?>;

    // Handle video thumbnail clicks
    const videoThumbnails = document.querySelectorAll('.video-thumbnail[data-tutorial-id]');
    videoThumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const tutorialId = this.getAttribute('data-tutorial-id');
            openVideoModal(tutorialId);
        });
    });

    // Handle watch button clicks
    const watchButtons = document.querySelectorAll('.btn-watch[data-tutorial-id]');
    watchButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tutorialId = this.getAttribute('data-tutorial-id');
            openVideoModal(tutorialId);
        });
    });

    // Function to open video modal
    function openVideoModal(tutorialId) {
        const tutorial = tutorials.find(t => t.tutorial_id == tutorialId);
        if (!tutorial) return;

        // Update modal title
        document.getElementById('videoModalLabel').textContent = tutorial.title;

        // Update video container based on video type
        const videoContainer = document.getElementById('videoContainer');

        if (tutorial.video_type === 'youtube' && tutorial.video_url) {
            // Extract YouTube video ID
            const youtubeId = extractYouTubeId(tutorial.video_url);
            if (youtubeId) {
                videoContainer.innerHTML = `
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/${youtubeId}"
                                title="${tutorial.title}"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>
                `;
            } else {
                showVideoError('Invalid YouTube URL');
            }
        } else if (tutorial.video_type === 'vimeo' && tutorial.video_url) {
            // Extract Vimeo video ID
            const vimeoId = extractVimeoId(tutorial.video_url);
            if (vimeoId) {
                videoContainer.innerHTML = `
                    <div class="ratio ratio-16x9">
                        <iframe src="https://player.vimeo.com/video/${vimeoId}"
                                title="${tutorial.title}"
                                frameborder="0"
                                allow="autoplay; fullscreen; picture-in-picture"
                                allowfullscreen>
                        </iframe>
                    </div>
                `;
            } else {
                showVideoError('Invalid Vimeo URL');
            }
        } else if (tutorial.video_type === 'upload' && tutorial.video_file) {
            videoContainer.innerHTML = `
                <video controls class="w-100" style="max-height: 400px;">
                    <source src="../../uploads/videos/${tutorial.video_file}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            `;
        } else if (tutorial.video_type === 'external' && tutorial.video_url) {
            videoContainer.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-external-link-alt fa-3x mb-3"></i>
                    <h5>External Video</h5>
                    <p class="mb-3">${tutorial.description}</p>
                    <a href="${tutorial.video_url}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Open Video
                    </a>
                </div>
            `;
        } else {
            showVideoError('Video not available');
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        modal.show();

        // Update view count
        updateViewCount(tutorialId);
    }

    function showVideoError(message) {
        const videoContainer = document.getElementById('videoContainer');
        videoContainer.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5>Video Unavailable</h5>
                <p class="text-muted">${message}</p>
            </div>
        `;
    }

    // Helper function to extract YouTube video ID
    function extractYouTubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    // Helper function to extract Vimeo video ID
    function extractVimeoId(url) {
        const regExp = /(?:vimeo)\.com.*(?:videos|video|channels|)\/([\d]+)/i;
        const match = url.match(regExp);
        return match ? match[1] : null;
    }

    // Function to update view count
    function updateViewCount(tutorialId) {
        fetch('update_tutorial_views.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ tutorial_id: tutorialId })
        }).catch(error => {
            console.log('Error updating view count:', error);
        });
    }

    // Handle hash navigation for direct links
    if (window.location.hash) {
        const targetElement = document.querySelector(window.location.hash);
        if (targetElement) {
            setTimeout(() => {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }, 500);
        }
    }

    // Add smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
