<?php
// Include required files
if (file_exists('../settings_functions.php')) {
    require_once '../settings_functions.php';
} elseif (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
}

// Define the path prefix for links if not already set
$path_prefix = isset($GLOBALS['path_prefix']) ? $GLOBALS['path_prefix'] : '';

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();

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

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Get theme mode from settings or session
$themeMode = $_SESSION['theme_mode'] ?? 'system';
if ($themeMode === 'system') {
    // If system theme is selected, no specific data-bs-theme will be set
    // Bootstrap will use the browser/OS preference
    $themeAttribute = '';
} else {
    // Otherwise use the specific theme (light or dark)
    $themeAttribute = ' data-bs-theme="' . htmlspecialchars($themeMode) . '"';
}

// Get primary color from settings or session
$primaryColor = $_SESSION['primary_color'] ?? '#0d6efd';

// Get site name from session or settings
$siteName = $_SESSION['site_name'] ?? null;
if (!$siteName) {
    try {
        if (function_exists('getSetting')) {
            $siteName = getSetting('site_name', 'SRC Management System');
        } else {
            $siteName = 'SRC Management System'; // Default fallback
        }
    } catch (Exception $e) {
        $siteName = 'SRC Management System'; // Default fallback if error occurs
    }
}

// Get logo settings from session first, then from database
$logoType = $_SESSION['logo_type'] ?? getSetting('logo_type', 'icon');
$logoUrl = $_SESSION['logo_url'] ?? getSetting('logo_url', '../images/logo.png');

// Add cache-busting parameter to prevent browser caching
$cacheBuster = '?v=' . time();

// Get system icon
try {
    if (file_exists('../icon_functions.php')) {
        require_once '../icon_functions.php';
        
        // Force direct database retrieval instead of using session
        $systemIconValue = getSetting('system_icon', 'book');
        // Only fallback to session if database retrieval fails
        if (empty($systemIconValue)) {
            $systemIconValue = $_SESSION['system_icon'] ?? 'book';
        }
        $iconInfo = getIconInfo($systemIconValue);
        $systemIconPath = $iconInfo ? $iconInfo['path'] : '../images/icons/book.svg';
    } else {
        $systemIconPath = '../images/icons/book.svg';
        $systemIconValue = 'book';
    }
} catch (Exception $e) {
    // If any error occurs with icon functions, use default
    $systemIconPath = '../images/icons/book.svg';
    $systemIconValue = 'book';
}

// Always refresh cached session values for system_icon and logo_type
$_SESSION['system_icon'] = $systemIconValue;
$_SESSION['logo_type'] = getSetting('logo_type', 'icon');

// Calculate hover and active colors (slightly darker versions of primary color)
function adjustBrightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Convert hex to rgb
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }

    // Convert hex to rgb
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Adjust
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    // Convert back to hex
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
           str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
           str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

$primaryColorHover = adjustBrightness($primaryColor, -20);
$primaryColorActive = adjustBrightness($primaryColor, -30);

// Convert hex to RGB for CSS variables
$hex = ltrim($primaryColor, '#');
$r = hexdec(substr($hex, 0, 2));
$g = hexdec(substr($hex, 2, 2));
$b = hexdec(substr($hex, 4, 2));
$primaryColorRGB = "$r, $g, $b";
?>
<!DOCTYPE html>
<html lang="en"<?php echo $themeAttribute; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : $siteName; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/custom-theme.css">
    <link rel="stylesheet" href="../css/enhanced-dashboard.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/budget-items.css">
    <link rel="stylesheet" href="../css/recent-news.css">
    <link rel="stylesheet" href="../css/report-cards.css">
    <link rel="stylesheet" href="../assets/css/image-viewer.css">
    <link rel="stylesheet" href="../css/fix-spacing.css">
    <link rel="stylesheet" href="../css/fix-card-size.css">
    <link rel="stylesheet" href="../css/report-fix.css">
    <link rel="stylesheet" href="../css/minutes.css">
    <link rel="stylesheet" href="../css/sidebar-fix.css">
    
    <!-- Sidebar fix script -->
    <script src="../js/sidebar-fix.js" defer></script>
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($primaryColor); ?>;
            --primary-color-rgb: <?php echo $primaryColorRGB; ?>;
            --primary-color-hover: <?php echo htmlspecialchars($primaryColorHover); ?>;
            --primary-color-active: <?php echo htmlspecialchars($primaryColorActive); ?>;
            --primary-color-light: rgba(<?php echo $primaryColorRGB; ?>, 0.1);
        }
        
        /* Primary color overrides */
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .btn-outline-primary {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .nav-pills .nav-link.active, 
        .nav-tabs .nav-link.active {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .text-primary {
            color: var(--bs-primary) !important;
        }
        
        .sidebar-link.active {
            background-color: var(--bs-primary);
        }
        
        a {
            color: var(--bs-primary);
        }
        
        .form-check-input:checked {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        /* Profile picture styles */
        .profile-picture {
            border: 3px solid #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .profile-picture:hover {
            transform: scale(1.05);
        }
        
        .dropdown .profile-picture {
            border-width: 2px;
        }
        
        /* System icon styles */
        .system-icon {
            height: 32px;
            width: auto;
            max-width: 48px;
            object-fit: contain;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        /* Theme mode toggle in header */
        .theme-toggle {
            margin-left: 10px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .theme-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        /* Settings button styles */
        .settings-btn {
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .settings-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: rotate(15deg);
        }
        
        /* Dark mode specific styles */
        [data-bs-theme="dark"] .navbar,
        [data-bs-theme="dark"] .sidebar {
            background-color: #212529;
        }
        
        [data-bs-theme="dark"] .content-card {
            background-color: #2c3034;
            border-color: #373b3e;
        }
        
        [data-bs-theme="dark"] .avatar-circle {
            background-color: #495057;
        }
    </style>
    <script>
        // Function to detect system dark mode preference
        function detectColorScheme() {
            const theme = '<?php echo $themeMode; ?>';
            if (theme === 'system') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                }
                
                // Listen for changes in system theme
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                    document.documentElement.setAttribute('data-bs-theme', e.matches ? 'dark' : 'light');
                });
            }
        }
        
        // Run on page load
        document.addEventListener('DOMContentLoaded', detectColorScheme);
    </script>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $isAdminDir ? '../pages_php/dashboard.php' : 'dashboard.php'; ?>">
                <?php 
                // Debug information
                $debugInfo = [
                    'logoType' => $logoType,
                    'logoUrl' => $logoUrl,
                    'systemIconPath' => $systemIconPath,
                    'systemIconValue' => $systemIconValue
                ];
                
                // Create a timestamp-based cache buster
                $cacheBuster = '?v=' . time();
                
                // Always use system icon (as requested)
                $freshSystemIcon = getSetting('system_icon', 'book');
                
                // Get the icon path directly
                $freshIconPath = '../images/icons/' . $freshSystemIcon . '.svg';
                
                // Check if the direct icon path exists
                if (file_exists(str_replace('../', '', $freshIconPath))) {
                    echo '<img src="' . htmlspecialchars($freshIconPath . $cacheBuster) . '" alt="System Icon" class="system-icon">';
                } else {
                    // Try the relative path from includes directory
                    $relIconPath = '../../images/icons/' . $freshSystemIcon . '.svg';
                    if (file_exists(str_replace('../', '', $relIconPath))) {
                        echo '<img src="' . htmlspecialchars($relIconPath . $cacheBuster) . '" alt="System Icon" class="system-icon">';
                    } else {
                        // Fallback to FontAwesome icon
                        echo '<i class="fas fa-' . htmlspecialchars($freshSystemIcon) . ' me-2"></i>';
                    }
                }
                ?>
                <?php echo $siteName; ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="far fa-calendar-alt me-2"></i><?php echo date('l, F j, Y'); ?> <span class="ms-2 me-1">|</span> <i class="far fa-clock me-1"></i><span id="current-time"><?php echo date('h:i:s A'); ?></span>
                </span>
                <div class="theme-toggle" id="themeToggle" title="Toggle theme">
                    <i class="fas fa-<?php echo $themeMode === 'dark' ? 'sun' : 'moon'; ?>"></i>
                </div>
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($currentUser['profile_picture']) && file_exists('../images/profiles/' . $currentUser['profile_picture'])): ?>
                            <img src="../images/profiles/<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                                 class="rounded-circle profile-picture me-2" 
                                 style="width: 30px; height: 30px; object-fit: cover;" 
                                 alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user-circle me-2"></i>
                        <?php endif; ?>
                        <?php echo $userName; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php 
                        // Determine if we're in admin directory
                        $isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
                        $dashboardPath = $isAdminDir ? '../pages_php/dashboard.php' : 'dashboard.php';
                        $profilePath = $isAdminDir ? '../pages_php/profile.php' : 'profile.php';
                        $logoutPath = $isAdminDir ? '../pages_php/logout.php' : 'logout.php';
                        ?>
                        <li><a class="dropdown-item" href="<?php echo $dashboardPath; ?>"><i class="fas fa-home me-2"></i>Home</a></li>
                        <li><a class="dropdown-item" href="<?php echo $profilePath; ?>"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $logoutPath; ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
                <?php if ($isAdmin): ?>
                <?php $settingsPath = $isAdminDir ? '../pages_php/settings.php' : 'settings.php'; ?>
                <a href="<?php echo $settingsPath; ?>" class="btn ms-2 text-white settings-btn" title="Settings">
                    <i class="fas fa-cog"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile text-center py-4">
            <?php if (!empty($currentUser['profile_picture']) && file_exists('../images/profiles/' . $currentUser['profile_picture'])): ?>
                <img src="../images/profiles/<?php echo htmlspecialchars($currentUser['profile_picture']); ?>" 
                     class="rounded-circle profile-picture" 
                     style="width: 80px; height: 80px; object-fit: cover;" 
                     alt="Profile Picture">
            <?php else: ?>
                <div class="avatar-circle">
                    <span class="avatar-text"><?php echo $userInitial; ?></span>
                </div>
            <?php endif; ?>
            <div class="mt-2">
                <h6 class="mb-0"><?php echo $userName; ?></h6>
                <small class="text-muted"><?php echo $userRole; ?></small>
            </div>
        </div>
        <hr class="mx-3">
        <?php 
        // Determine base path for links based on whether we're in admin or pages_php directory
        $basePath = $isAdminDir ? '../pages_php/' : '';
        ?>
        <a href="<?php echo $basePath; ?>dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="<?php echo $basePath; ?>about.php" class="sidebar-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">
            <i class="fas fa-info-circle me-2"></i> About SRC
        </a>
        <a href="<?php echo $basePath; ?>events.php" class="sidebar-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt me-2"></i> Events
        </a>
        <a href="<?php echo $basePath; ?>news.php" class="sidebar-link <?php echo $currentPage === 'news.php' ? 'active' : ''; ?>">
            <i class="fas fa-newspaper me-2"></i> News
        </a>
        <a href="<?php echo $basePath; ?>documents.php" class="sidebar-link <?php echo $currentPage === 'documents.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt me-2"></i> Documents
        </a>
        <a href="<?php echo $basePath; ?>gallery.php" class="sidebar-link <?php echo $currentPage === 'gallery.php' ? 'active' : ''; ?>">
            <i class="fas fa-images me-2"></i> Gallery
        </a>
        <a href="<?php echo $basePath; ?>elections.php" class="sidebar-link <?php echo $currentPage === 'elections.php' ? 'active' : ''; ?>">
            <i class="fas fa-vote-yea me-2"></i> Elections
        </a>
        <?php if ($isAdmin || $isMember): ?>
        <a href="<?php echo $basePath; ?>minutes.php" class="sidebar-link <?php echo $currentPage === 'minutes.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard me-2"></i> Minutes
        </a>
        <?php endif; ?>
        <?php if (hasPermission('read', 'reports')): ?>
        <a href="<?php echo $basePath; ?>reports.php" class="sidebar-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar me-2"></i> Reports
        </a>
        <?php endif; ?>
        <a href="<?php echo $basePath; ?>portfolio.php" class="sidebar-link <?php echo $currentPage === 'portfolio.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie me-2"></i> Portfolios
        </a>
        <a href="<?php echo $basePath; ?>departments.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" class="sidebar-link <?php echo $currentPage === 'departments.php' ? 'active' : ''; ?>">
            <i class="fas fa-building me-2"></i> Departments
        </a>
        <a href="<?php echo $basePath; ?>feedback.php" class="sidebar-link <?php echo $currentPage === 'feedback.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-alt me-2"></i> Feedback
        </a>
        <?php if ($isAdmin || $isMember): ?>
        <hr class="mx-3">
        <div class="sidebar-heading ms-3">MANAGEMENT</div>
        
        <?php if ($isAdmin): ?>
        <a href="<?php echo $basePath; ?>users.php" class="sidebar-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users me-2"></i> Users
        </a>
        <a href="<?php echo $basePath; ?>user-activities.php" class="sidebar-link <?php echo $currentPage === 'user-activities.php' ? 'active' : ''; ?>">
            <i class="fas fa-history me-2"></i> User Activities
        </a>
        <a href="<?php echo $isAdminDir ? 'feedback_dashboard.php' : '../admin/feedback_dashboard.php'; ?>" class="sidebar-link <?php echo $currentPage === 'feedback_dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-dots me-2"></i> Feedback Dashboard
        </a>
        <?php endif; ?>
        
        <a href="<?php echo $basePath; ?>budget.php" class="sidebar-link <?php echo $currentPage === 'budget.php' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill-wave me-2"></i> Budget
        </a>
        <?php endif; ?>
        
        <?php if ($isAdmin): ?>
        <a href="<?php echo $basePath; ?>settings.php" class="sidebar-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog me-2"></i> Settings
        </a>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle functionality
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            themeToggle.querySelector('i').className = 'fas fa-' + (newTheme === 'dark' ? 'sun' : 'moon');
            
            // Save preference in session via AJAX
            fetch('theme_toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'theme=' + newTheme
            });
        });
    }
    
    // Update time every second
    function updateTime() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // Convert hour 0 to 12
        const formattedHours = hours.toString().padStart(2, '0');
        
        document.getElementById('current-time').textContent = `${formattedHours}:${minutes}:${seconds} ${ampm}`;
        setTimeout(updateTime, 1000);
    }
    
    updateTime();
});
</script>
</body>
</html>