<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine directory location early for path resolution
$isAdminDir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$isSupportDir = strpos($_SERVER['PHP_SELF'], '/support/') !== false;

// Define CSS, JS, and assets paths based on directory location
$cssPath = $isSupportDir ? '../../css/' : '../css/';
$jsPath = $isSupportDir ? '../../js/' : '../js/';
$assetsPath = $isSupportDir ? '../../assets/' : '../assets/';

// Define base path
$basePath = dirname(dirname($_SERVER['PHP_SELF']));
$basePath = $basePath === '/' ? '' : $basePath . '/';

// Include required files in the correct order
// First include the database configuration
require_once dirname(__DIR__, 2) . '/includes/db_config.php';

// Then include database functions
require_once dirname(__DIR__, 2) . '/includes/db_functions.php';

// Include simple authentication
require_once dirname(__DIR__, 2) . '/includes/simple_auth.php';

// Include profile picture helpers
require_once dirname(__DIR__, 2) . '/includes/profile_picture_helpers.php';

// Then include settings_functions.php which contains getSetting()
require_once dirname(__DIR__, 2) . '/includes/settings_functions.php';

// Then include other required files
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Set default site name if not already set
if (!isset($siteName)) {
    $siteName = 'SRC Management System';
}

// Define the path prefix for links if not already set
$path_prefix = isset($GLOBALS['path_prefix']) ? $GLOBALS['path_prefix'] : '';

// Get current user info using simple auth functions
$currentUser = getCurrentUser();
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$isMember = isMember();
$isStudent = isStudent();
$isFinance = isFinance();
$isElectoralCommission = isElectoralCommission();
$hasAdminPrivileges = hasAdminPrivileges();
$hasMemberPrivileges = hasMemberPrivileges();

// Get user's name and first initial for avatar
$fullName = 'User';
if ($currentUser && isset($currentUser['first_name']) && isset($currentUser['last_name'])) {
    $fullName = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
}
$userInitial = strtoupper(substr($fullName, 0, 1));
$userName = $fullName;
// Format role name for display
$roleDisplay = $currentUser['role'] ?? 'Guest';
if ($roleDisplay === 'electoral_commission') {
    $userRole = 'Electoral Commission';
} else {
    $userRole = ucfirst(str_replace('_', ' ', $roleDisplay));
}

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;">
    <title><?php echo isset($pageTitle) ? $pageTitle : $siteName; ?></title>
    
    <!-- CRITICAL: Apply theme immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('vvusrc_theme_preference') || 'light';
            console.log('[THEME INIT] Applying saved theme:', savedTheme);
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-mode');
                document.documentElement.classList.remove('light-mode');
            } else {
                document.documentElement.classList.add('light-mode');
                document.documentElement.classList.remove('dark-mode');
            }
        })();
    </script>
    


    <!-- Modal Position Fix - removed non-existent file -->
    
    <!-- Header inline scripts moved to external file to fix CSP violation -->
    <script src="<?php echo $jsPath; ?>header-inline-scripts.js"></script>
    
    <!-- Disable conflicting header scripts globally -->
    <script src="<?php echo $jsPath; ?>disable-conflicting-header-scripts.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>dashboard.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>style.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>custom-theme.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>font-size-increase.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>enhanced-dashboard.css">

    <link rel="stylesheet" href="<?php echo $cssPath; ?>budget-items.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>recent-news.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>report-cards.css">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>departments.css">
    <!-- Removed non-existent CSS files -->

    <!-- Mobile Responsive CSS Framework -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>mobile-responsive.css">
    <!-- Layout Standardization CSS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>layout-standardization.css?v=<?php echo time(); ?>">
    <!-- Essential mobile fixes -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>footer.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $cssPath; ?>page-header.css?v=<?php echo time(); ?>">
    <!-- Enhanced Dark Mode CSS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>dark-mode-enhancement.css?v=<?php echo time(); ?>">
    <!-- Persistent Theme Toggle CSS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>theme-toggle-persistent.css?v=<?php echo time(); ?>">
    
    <!-- OPTIMIZED MOBILE SOLUTION - SINGLE CONSOLIDATED FIX -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>mobile-optimized.css?v=<?php echo time(); ?>">
    <script src="<?php echo $jsPath; ?>mobile-optimized.js?v=<?php echo time(); ?>"></script>
    
    <!-- NAVBAR DROPDOWN FIX - ESSENTIAL FOR DROPDOWNS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>navbar-dropdown-fix.css?v=<?php echo time(); ?>">
    <script src="<?php echo $jsPath; ?>navbar-dropdown-fix.js?v=<?php echo time(); ?>"></script>
    
    <!-- DESKTOP SIDEBAR POSITIONING FIX - ENSURES PROPER SIDEBAR DISPLAY ON DESKTOP -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>desktop-sidebar-fix.css?v=<?php echo time(); ?>">
    
    <!-- SIDEBAR PROFILE CENTERED LAYOUT - CENTERS PROFILE ICON AND STACKS TEXT BELOW -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>sidebar-profile-centered.css?v=<?php echo time(); ?>">
    
    <!-- REMOVE REDUNDANT SCROLL BUTTONS - HIDES UNNECESSARY SCROLL UI ELEMENTS -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>remove-scroll-buttons.css?v=<?php echo time(); ?>">
    <script src="<?php echo $jsPath; ?>remove-scroll-buttons.js?v=<?php echo time(); ?>"></script>

    <!-- UNIVERSAL SCROLL BUTTON REMOVAL - COMPREHENSIVE SOLUTION -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>universal-scroll-button-removal.css?v=<?php echo time(); ?>">
    <script src="<?php echo $jsPath; ?>universal-scroll-button-removal.js?v=<?php echo time(); ?>"></script>

    <!-- UNIVERSAL MODAL FIX - PROPER MODAL DISPLAY AND CENTERING -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>universal-modal-fix.css?v=<?php echo time(); ?>">
    <script src="<?php echo $jsPath; ?>universal-modal-fix.js?v=<?php echo time(); ?>"></script>

    <!-- MOBILE NAVBAR BUTTONS OPTIMAL LAYOUT - CLEAN BUTTON ARRANGEMENT AND SPACING -->
    <link rel="stylesheet" href="<?php echo $cssPath; ?>mobile-navbar-buttons-optimal.css?v=<?php echo time(); ?>">
    <script src="<?php echo $jsPath; ?>mobile-navbar-buttons-optimal.js?v=<?php echo time(); ?>"></script>
    
    <!-- TEMPORARY: Navbar Click Diagnostic Script -->
    <script src="<?php echo $jsPath; ?>navbar-click-diagnostic.js?v=<?php echo time(); ?>"></script>

    <!-- DEBUG: Sidebar and Theme Toggle Diagnostic -->
    <script src="<?php echo $jsPath; ?>debug-sidebar-theme.js?v=<?php echo time(); ?>"></script>


    <style>
        /* Ensure sidebar has proper z-index */
        .sidebar {
            z-index: 1000 !important;
            margin-top: 0rem !important;
        }

        /* Ensure navbar has higher z-index than sidebar */
        .navbar {
            z-index: 1001 !important;
        }
        
        /* Critical Senate and Committees link fixes */
        .sidebar a.sidebar-link[href="senate.php"],
        .sidebar a.sidebar-link[href="committees.php"] {
            background-color: transparent !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            padding: 0.75rem 1.25rem !important;
            text-decoration: none !important;
            transition: background-color 0.2s ease !important;
            border: none !important;
            margin: 0 !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
        }
        
        /* Ensure all sidebar links have consistent styling */
        .sidebar a.sidebar-link {
            background-color: transparent !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            padding: 0.75rem 1.25rem !important;
            text-decoration: none !important;
            transition: background-color 0.2s ease !important;
            border: none !important;
            margin: 0 !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
        }
        
        /* Hover and active states for all sidebar links */
        .sidebar a.sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }
        
        .sidebar a.sidebar-link.active {
            background-color: var(--primary-color) !important;
            color: white !important;
        }
        
        /* Ensure icons are consistent */
        .sidebar a.sidebar-link i {
            margin-right: 0.5rem !important;
            width: 1.25rem !important;
            text-align: center !important;
            font-size: 1rem !important;
        }

        /* Sidebar dropdown styles */
        .sidebar-dropdown {
            position: relative;
        }

        .sidebar-dropdown .sidebar-link {
            cursor: pointer;
        }

        .sidebar-submenu {
            display: none;
            background-color: rgba(255, 255, 255, 0.1);
            margin-left: 1rem;
            border-left: 2px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-dropdown.active .sidebar-submenu {
            display: block;
        }

        .sidebar-sublink {
            display: flex !important;
            align-items: center !important;
            padding: 0.5rem 1rem !important;
            color: rgba(255, 255, 255, 0.8) !important;
            text-decoration: none !important;
            font-size: 0.9rem !important;
            transition: all 0.2s ease !important;
        }

        .sidebar-sublink:hover,
        .sidebar-sublink.active {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }

        .sidebar-sublink i {
            margin-right: 0.5rem !important;
            width: 1rem !important;
            text-align: center !important;
            font-size: 0.8rem !important;
        }

        .sidebar-dropdown .fa-chevron-down {
            transition: transform 0.2s ease;
        }

        .sidebar-dropdown.active .fa-chevron-down {
            transform: rotate(180deg);
        }
        .sidebar .mt-2 .text-muted {
            color: gold !important;
        }

        @media (max-width: 991px) {
            .sidebar {
                margin-top: 3rem !important;
            }
        }
    </style>
    
    <!-- Keep only existing sidebar toggle script -->
    <script src="<?php echo $jsPath; ?>sidebar-toggle.js" defer></script>
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
        
        /* Navbar brand container to prevent overflow */
        .navbar-brand {
            display: flex !important;
            align-items: center !important;
            max-height: 55px !important;
            overflow: hidden !important;
            padding: 5px 0 !important;
        }
        
        /* System icon and navbar logo styles */
        .system-icon,
        .navbar-logo {
            height: 45px;
            width: auto;
            max-width: 50px;
            max-height: 45px;
            object-fit: contain;
            margin-right: 10px;
            vertical-align: middle;
            display: inline-block;
            flex-shrink: 0;
        }
        
        /* Mobile responsiveness for logo */
        @media (max-width: 768px) {
            .navbar-brand {
                max-height: 50px !important;
                padding: 3px 0 !important;
            }
            
            .system-icon,
            .navbar-logo {
                height: 38px;
                max-width: 42px;
                max-height: 38px;
                margin-right: 8px;
            }
            
            .site-name {
                font-size: 1rem !important;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-brand {
                max-height: 45px !important;
                padding: 2px 0 !important;
            }
            
            .system-icon,
            .navbar-logo {
                height: 32px;
                max-width: 35px;
                max-height: 32px;
                margin-right: 6px;
            }
            
            .site-name {
                font-size: 0.9rem !important;
                max-width: 120px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
        
        /* Theme mode toggle in header */
        .theme-toggle {
            color: white !important;
            margin-left: 10px !important;
            margin-right: 10px !important;
            cursor: pointer !important;
            padding: 5px 10px !important;
            border-radius: 4px !important;
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 40px !important;
            height: 40px !important;
            transition: all 0.2s ease !important;
            pointer-events: auto !important;
            position: relative !important;
            z-index: 1050 !important;
            min-width: 40px !important;
            flex-shrink: 0 !important;
        }
        
        .theme-toggle:hover {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.2) !important;
        }
        
        .theme-toggle:focus {
            outline: 2px solid rgba(255, 255, 255, 0.5) !important;
            outline-offset: 2px !important;
        }
        
        .theme-toggle:active {
            transform: scale(0.95);
        }

        /* Notification dropdown styles */
        .notification-dropdown {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-radius: 15px;
        }

        .notification-dropdown .dropdown-header {
            background: #fff;
            color: #333;
            border-radius: 15px 15px 0 0;
            padding: 16px;
            margin: 0;
            border: none;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-dropdown .dropdown-divider {
            margin: 0;
        }

        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background-color: #fff;
            position: relative;
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: #2196f3;
        }

        .notification-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-content {
            min-width: 0;
        }

        .notification-message {
            font-size: 14px;
            line-height: 1.4;
            color: #333;
            margin-bottom: 4px;
        }

        .notification-time {
            font-size: 12px;
            color: #666;
        }

        .notification-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #9e9e9e;
        }

        .notification-badge {
            font-size: 0.7rem;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
        }

        .notification-content {
            width: 100%;
        }

        .notification-link {
            margin-top: 0.5rem;
        }

        .notification-link .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
        }

        .notification-link .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

        /* Modern avatar initials styling */
        .avatar-initials {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 50% !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            font-weight: bold !important;
            border: 2px solid #fff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            vertical-align: middle !important;
            flex-shrink: 0 !important;
        }

        /* Dark mode avatar initials */
        [data-bs-theme="dark"] .avatar-initials {
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%) !important;
            border-color: #4a5568 !important;
        }

        /* Ensure consistent alignment with profile pictures */
        .profile-picture, .avatar-initials {
            vertical-align: middle !important;
        }
        
        /* Navbar sidebar toggle button - moved to dashboard.css */

        /* Center date/time in navbar */
        .navbar-datetime {
            font-size: 1rem;
            font-weight: 500;
            white-space: nowrap;
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
        }

        /* Responsive adjustments for date/time */
        @media (max-width: 1018px) {
            .navbar-datetime {
                font-size: 1rem;
                display: none; /* Hide on mobile as requested */
            }

            .navbar-datetime .far {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .navbar-datetime {
                font-size: 0.9rem;
                display: none; /* Hide on mobile as requested */
            }
        }
    </style>
    <!-- Theme detection and navigation functions moved to external file -->
    <!-- Sidebar fix moved to external file -->
    <!-- Auto-dismiss script moved to external file -->
    
    <!-- Layout standardization script -->
    <!-- Removed force-layout-standardization.js as it interferes with header positioning -->
</head>
<body class="<?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : ''; ?>">
    <script>
        // Apply theme class to body immediately
        (function() {
            const savedTheme = localStorage.getItem('vvusrc_theme_preference') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                document.body.classList.remove('light-mode');
            } else {
                document.body.classList.add('light-mode');
                document.body.classList.remove('dark-mode');
            }
        })();
    </script>
    <div id="main-wrapper">
        <div class="content-wrapper">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <!-- Left side - Logo and Brand -->
            <div class="d-flex align-items-center">
                <button id="sidebar-toggle-navbar" class="btn text-white me-2" title="Toggle Sidebar" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <?php
                // Define dashboard links for each possible location
                $adminDashboardLink = '../pages_php/dashboard.php';
                $pagesDashboardLink = 'dashboard.php';
                $rootDashboardLink = 'pages_php/dashboard.php';

                // Determine which link to use based on current path
                $currentPath = $_SERVER['PHP_SELF'];
                $dashboardLink = $rootDashboardLink; // Default

                if (strpos($currentPath, '/admin/') !== false) {
                    $dashboardLink = $adminDashboardLink;
                } elseif (strpos($currentPath, '/pages_php/') !== false) {
                    $dashboardLink = $pagesDashboardLink;
                }
                ?>
                <a class="navbar-brand" href="<?php echo $dashboardLink; ?>">
                    <?php
                    // Use logo based on settings
                    $cacheBuster = '?v=' . time();
                    
                    // Adjust logo path based on directory location
                    $logoUrlAdjusted = $logoUrl;
                    if ($isSupportDir && !empty($logoUrl)) {
                        // If in support dir, adjust the path (change ../ to ../../)
                        $logoUrlAdjusted = str_replace('../', '../../', $logoUrl);
                    }
                    
                    // For file_exists checks, convert relative path to absolute
                    $logoFileCheckPath = dirname(__DIR__, 2) . '/' . str_replace('../', '', $logoUrl);
                    
                    // Check logo type from settings
                    if ($logoType === 'custom' && !empty($logoUrl)) {
                        // Use custom logo from settings
                        if (file_exists($logoFileCheckPath)) {
                            echo '<img src="' . htmlspecialchars($logoUrlAdjusted . $cacheBuster) . '" alt="Custom Logo" class="navbar-logo" style="height: 45px; max-height: 45px; width: auto; max-width: 50px; object-fit: contain; margin-right: 10px; display: inline-block;">';
                        } else {
                            // Fallback to system icon if custom logo file not found
                            $freshSystemIcon = getSetting('system_icon', 'university');
                            $freshIconPath = $isSupportDir ? '../../images/icons/' . $freshSystemIcon . '.svg' : '../images/icons/' . $freshSystemIcon . '.svg';
                            $iconFileCheckPath = dirname(__DIR__, 2) . '/images/icons/' . $freshSystemIcon . '.svg';
                            
                            if (file_exists($iconFileCheckPath)) {
                                echo '<img src="' . htmlspecialchars($freshIconPath . $cacheBuster) . '" alt="System Icon" class="system-icon">';
                            } else {
                                echo '<i class="fas fa-university me-2"></i>';
                            }
                        }
                    } else {
                        // Use system icon
                        $freshSystemIcon = getSetting('system_icon', 'university');
                        $freshIconPath = $isSupportDir ? '../../images/icons/' . $freshSystemIcon . '.svg' : '../images/icons/' . $freshSystemIcon . '.svg';
                        $iconFileCheckPath = dirname(__DIR__, 2) . '/images/icons/' . $freshSystemIcon . '.svg';
                        
                        if (file_exists($iconFileCheckPath)) {
                            echo '<img src="' . htmlspecialchars($freshIconPath . $cacheBuster) . '" alt="System Icon" class="system-icon">';
                        } else {
                            echo '<i class="fas fa-' . htmlspecialchars($freshSystemIcon) . ' me-2"></i>';
                        }
                    }
                    ?>
                    <span class="site-name"><?php echo $siteName; ?></span>
                </a>
            </div>

            <!-- Middle - Date/Time (Centered and hidden on mobile) -->
            <div class="position-relative flex-grow-1 d-none d-md-block">
                <span class="text-white navbar-datetime">
                    <i class="far fa-calendar-alt me-2"></i><?php echo date('l, F j, Y'); ?> <span class="ms-2 me-1">|</span> <i class="far fa-clock me-1"></i><span id="current-time"><?php echo date('h:i:s A'); ?></span>
                </span>
            </div>

            <!-- Right side - Theme Toggle, User Menu -->
            <div class="d-flex align-items-center ms-auto">
                <button class="theme-toggle" id="themeToggle" title="Toggle dark/light theme" type="button" aria-label="Toggle theme" aria-pressed="false">
                    <i class="fas fa-<?php echo $themeMode === 'dark' ? 'sun' : 'moon'; ?>" aria-hidden="true"></i>
                </button>

                <!-- Notifications Dropdown -->
                <div class="dropdown me-2">
                    <button class="btn btn-outline-light position-relative" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                            0
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown" style="width: 300px !important; max-width: calc(100vw - 30px) !important; min-width: 280px !important; max-height: 400px !important; overflow-y: auto !important; padding: 0 !important; margin: 0 !important; box-sizing: border-box !important; border-radius: 8px !important;">
                        <li class="dropdown-header" style="display: flex !important; justify-content: space-between !important; align-items: center !important; width: 100% !important; max-width: 100% !important; padding: 8px 10px !important; margin: 0 !important; box-sizing: border-box !important; background: #fff !important; border-bottom: 1px solid #f1f3f4 !important; overflow: hidden !important;">
                            <span style="font-size: 13px !important; font-weight: 600 !important; color: #333 !important; margin: 0 !important; padding: 0 !important; flex-shrink: 0 !important; white-space: nowrap !important; max-width: 60% !important;">Notifications</span>
                            <a href="<?php echo $isSupportDir ? 'notifications.php' : 'support/notifications.php'; ?>" style="font-size: 11px !important; color: #2196f3 !important; text-decoration: none !important; font-weight: 500 !important; margin: 0 !important; padding: 2px 4px !important; flex-shrink: 0 !important; white-space: nowrap !important; max-width: 35% !important; border-radius: 4px !important;">View All</a>
                        </li>
                        <div id="notificationsList" style="max-height: 320px !important; overflow-y: auto !important; overflow-x: hidden !important; padding: 0 !important; margin: 0 !important;">
                            <li class="text-center p-3 text-muted">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                <p class="mb-0 small">Showing recent notifications</p>
                            </li>
                        </div>
                    </ul>
                </div>

                <div class="dropdown me-2">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php
                        $context = $isSupportDir ? 'support' : 'pages_php';
                        $profilePic = displayProfilePicture($currentUser, $context, ['width' => 30, 'height' => 30, 'class' => 'rounded-circle profile-picture me-2', 'style' => 'object-fit: cover;', 'show_initials' => true]);
                        echo $profilePic;
                        ?>
                        <span class="user-name-text"><?php echo $userName; ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php 
                        // Determine correct paths for dropdown links based on directory location
                        if ($isAdminDir) {
                            $dashboardPath = '../pages_php/dashboard.php';
                            $profilePath = '../pages_php/profile.php';
                            $logoutPath = '../pages_php/logout.php';
                        } elseif ($isSupportDir) {
                            $dashboardPath = '../dashboard.php';
                            $profilePath = '../profile.php';
                            $logoutPath = '../logout.php';
                        } else {
                            $dashboardPath = 'dashboard.php';
                            $profilePath = 'profile.php';
                            $logoutPath = 'logout.php';
                        }
                        ?>
                        <li><a class="dropdown-item" href="<?php echo $dashboardPath; ?>"><i class="fas fa-home me-2"></i>Home</a></li>
                        <li><a class="dropdown-item" href="<?php echo $profilePath; ?>"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $logoutPath; ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
                <!-- Settings - Only for super admin -->
                <?php if ($isSuperAdmin): ?>
                <?php
                if ($isAdminDir) {
                    $settingsPath = '../pages_php/settings.php';
                } elseif ($isSupportDir) {
                    $settingsPath = '../settings.php';
                } else {
                    $settingsPath = 'settings.php';
                }
                ?>
                <a href="<?php echo $settingsPath; ?>" class="btn ms-2 text-white settings-btn" title="Settings">
                    <i class="fas fa-cog"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <?php
    // Only include sidebar if it hasn't been included yet and hideSidebar is not true
    if ((!isset($GLOBALS['sidebar_included']) || !$GLOBALS['sidebar_included']) && (!isset($hideSidebar) || $hideSidebar !== true)):
        $GLOBALS['sidebar_included'] = true;
    ?>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="user-profile text-center py-4">
            <?php
            // Display profile picture using helper function
            $context = $isSupportDir ? 'support' : 'pages_php';
            echo displayProfilePicture($currentUser, $context, [
                'width' => 80,
                'height' => 80,
                'class' => 'rounded-circle profile-picture',
                'style' => 'object-fit: cover;'
            ]);
            ?>
            <div class="mt-2">
                <h6 class="mb-0"><?php echo $userName; ?></h6>
                <small class="text-muted"><?php echo $userRole; ?></small>
            </div>
        </div>
        <hr class="mx-3">
        <?php
        // Determine base path for links based on directory location
        if ($isAdminDir) {
            $basePath = '../pages_php/';
        } elseif ($isSupportDir) {
            $basePath = '../';
        } else {
            $basePath = '';
        }
        
        // Special sidebar for Electoral Commission - only show 4 specific pages
        if ($isElectoralCommission):
        ?>
        <!-- Dashboard - Available to all users -->
        <a href="<?php echo $basePath; ?>dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>

        <!-- Elections - Available to electoral commission -->
        <a href="<?php echo $basePath; ?>elections.php" class="sidebar-link <?php echo $currentPage === 'elections.php' ? 'active' : ''; ?>">
            <i class="fas fa-vote-yea me-2"></i> Elections
        </a>

        <!-- Live Election Monitor - Available to electoral commission -->
        <a href="<?php echo $basePath; ?>live_election_monitor.php" class="sidebar-link <?php echo $currentPage === 'live_election_monitor.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line me-2"></i> Live Election Monitor
        </a>

        <!-- Public Chat - Available to electoral commission -->
        <?php if (hasFeaturePermission('enable_public_chat')): ?>
        <a href="<?php echo $basePath; ?>public_chat.php" class="sidebar-link <?php echo $currentPage === 'public_chat.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments me-2"></i> Public Chat
        </a>
        <?php endif; ?>
        
        <?php else: // Normal sidebar for other users ?>
        <!-- Dashboard - Available to all users -->
        <a href="<?php echo $basePath; ?>dashboard.php" class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>

        <!-- About SRC - Available to all users -->
        <a href="<?php echo $basePath; ?>about.php" class="sidebar-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">
            <i class="fas fa-info-circle me-2"></i> About SRC
        </a>

        <!-- Events - Available to all users -->
        <a href="<?php echo $basePath; ?>events.php" class="sidebar-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt me-2"></i> Events
        </a>

        <!-- News - Available to all users -->
        <a href="<?php echo $basePath; ?>news.php" class="sidebar-link <?php echo $currentPage === 'news.php' ? 'active' : ''; ?>">
            <i class="fas fa-newspaper me-2"></i> News
        </a>

        <!-- Documents - Available to all users -->
        <a href="<?php echo $basePath; ?>documents.php" class="sidebar-link <?php echo $currentPage === 'documents.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt me-2"></i> Documents
        </a>

        <!-- Gallery - Available to all users -->
        <a href="<?php echo $basePath; ?>gallery.php" class="sidebar-link <?php echo $currentPage === 'gallery.php' ? 'active' : ''; ?>">
            <i class="fas fa-images me-2"></i> Gallery
        </a>

        <!-- Elections - Available to all users (read-only for non-super-admins) -->
        <a href="<?php echo $basePath; ?>elections.php" class="sidebar-link <?php echo $currentPage === 'elections.php' ? 'active' : ''; ?>">
            <i class="fas fa-vote-yea me-2"></i> Elections
        </a>
        <!-- Minutes - Available to super admin, admin, member, and finance -->
        <?php if ($hasMemberPrivileges): ?>
        <a href="<?php echo $basePath; ?>minutes.php" class="sidebar-link <?php echo $currentPage === 'minutes.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard me-2"></i> Minutes
        </a>
        <?php endif; ?>

        <!-- Reports - Available to super admin, admin, member, and finance -->
        <?php if ($hasMemberPrivileges): ?>
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
        
        <!-- Senate link - explicitly added -->
        <a href="<?php echo $basePath; ?>senate.php" class="sidebar-link <?php echo $currentPage === 'senate.php' ? 'active' : ''; ?>" style="padding: 0.75rem 1.25rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-gavel me-2" style="margin-right: 0.5rem !important; width: 1.25rem !important; text-align: center !important;"></i> Senate
        </a>
        
        <!-- Committees link - explicitly added -->
        <a href="<?php echo $basePath; ?>committees.php" class="sidebar-link <?php echo $currentPage === 'committees.php' ? 'active' : ''; ?>" style="padding: 0.75rem 1.25rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-users me-2" style="margin-right: 0.5rem !important; width: 1.25rem !important; text-align: center !important;"></i> Committees
        </a>
        
        <a href="<?php echo $basePath; ?>feedback.php" class="sidebar-link <?php echo $currentPage === 'feedback.php' ? 'active' : ''; ?>">
            <i class="fas fa-comment-alt me-2"></i> Feedback
        </a>

        <!-- Student Welfare link -->
        <?php if (hasFeaturePermission('enable_welfare')): ?>
        <a href="<?php echo $basePath; ?>welfare.php" class="sidebar-link <?php echo $currentPage === 'welfare.php' ? 'active' : ''; ?>">
            <i class="fas fa-heart me-2"></i> Student Welfare
        </a>
        <?php endif; ?>

        <!-- Support link -->
        <?php if (hasFeaturePermission('enable_support')): ?>
        <a href="<?php echo $basePath; ?>support/" class="sidebar-link <?php echo ($isSupportDir && basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-life-ring me-2"></i> Support
        </a>
        <?php endif; ?>

        <!-- Notifications link -->
        <?php if (hasFeaturePermission('enable_notifications')): ?>
        <a href="<?php echo $basePath; ?>support/notifications.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell me-2"></i> Notifications
        </a>
        <?php endif; ?>

        <!-- Public Chat link -->
        <?php if (hasFeaturePermission('enable_public_chat')): ?>
        <a href="<?php echo $basePath; ?>public_chat.php" class="sidebar-link <?php echo $currentPage === 'public_chat.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments me-2"></i> Public Chat
        </a>
        <?php endif; ?>

        <!-- Finance link - Only for super admin and finance roles -->
        <?php if (($isSuperAdmin || $isFinance) && hasFeaturePermission('enable_finance')): ?>
        <a href="<?php echo $basePath; ?>finance.php" class="sidebar-link <?php echo $currentPage === 'finance.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line me-2"></i> Finance
        </a>
        <?php endif; ?>

        <!-- Management Section - Available to super admin, admin, member, and finance -->
        <?php if ($hasMemberPrivileges): ?>
        <hr class="mx-3">
        <div class="sidebar-heading ms-3 text-uppercase" style="padding: 0.75rem 1.25rem 0.5rem !important; margin-top: 0.5rem !important; margin-bottom: 0.25rem !important; font-size: 0.85rem !important; letter-spacing: 0.5px !important; font-weight: 600 !important; color: rgba(255, 255, 255, 0.6) !important;">MANAGEMENT</div>

        <!-- Users - Only for super admin -->
        <?php if ($isSuperAdmin): ?>
        <a href="<?php echo $basePath; ?>users.php" class="sidebar-link management-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-users me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> Users
        </a>

        <?php endif; ?>

        <!-- User Activities - For super admin and admin -->
        <?php if ($hasAdminPrivileges): ?>
        <a href="<?php echo $basePath; ?>user-activities.php" class="sidebar-link management-link <?php echo $currentPage === 'user-activities.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-history me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> User Activities
        </a>

        <!-- Password Reset - For super admin ONLY -->
        <?php if (isSuperAdmin()): ?>
        <a href="<?php echo $basePath; ?>admin_password_reset.php" class="sidebar-link management-link <?php echo $currentPage === 'admin_password_reset.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-key me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> Password Reset
            <?php
            // Show notification badge for pending admin reset requests
            try {
                $pendingRequests = fetchOne("SELECT COUNT(*) as count FROM admin_reset_requests WHERE status = 'pending'");
                if ($pendingRequests && $pendingRequests['count'] > 0) {
                    echo '<span class="badge bg-warning ms-2" style="font-size: 0.7rem;">' . $pendingRequests['count'] . '</span>';
                }
            } catch (Exception $e) {
                // Silently handle error if table doesn't exist yet
            }
            ?>
        </a>
        <?php endif; ?>

        <!-- Messaging - For super admin and admin -->
        <a href="<?php echo $basePath; ?>messaging.php" class="sidebar-link management-link <?php echo $currentPage === 'messaging.php' || $currentPage === 'whatsapp_messaging.php' || $currentPage === 'sms_messaging.php' || $currentPage === 'in_app_messaging.php' || $currentPage === 'email_messaging.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-comment-dots me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> Messaging
        </a>
        <?php endif; ?>

       

        <!-- Live Election Monitor - Only for super admin (electoral commission sees it in main menu) -->
        <?php if ($isSuperAdmin): ?>
        <a href="<?php echo $basePath; ?>live_election_monitor.php" class="sidebar-link management-link <?php echo $currentPage === 'live_election_monitor.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-chart-line me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> Live Election Monitor
        </a>
        <?php endif; ?>

        <!-- Budget - Available to super admin, admin, member, and finance -->
        <?php if ($hasMemberPrivileges): ?>
        <a href="<?php echo $basePath; ?>budget.php" class="sidebar-link management-link <?php echo $currentPage === 'budget.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-money-bill-wave me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> Budget
        </a>
        <?php endif; ?>

        <!-- Settings - Only for super admin -->
        <?php if ($isSuperAdmin): ?>
        <a href="<?php echo $basePath; ?>settings.php" class="sidebar-link management-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>" style="padding: 0.85rem 1.5rem !important; display: flex !important; align-items: center !important;">
            <i class="fas fa-cog me-2" style="margin-right: 12px !important; width: 24px !important; text-align: center !important; font-size: 1.1rem !important;"></i> Settings
        </a>
        <?php endif; ?>
        <?php endif; // End $hasMemberPrivileges ?>
        <?php endif; // End $isElectoralCommission check - close normal sidebar ?>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-2">

<script>
/**
 * Persistent Theme Toggle System
 * Uses localStorage to maintain user's theme preference across all pages
 * Syncs with Bootstrap's data-bs-theme attribute and CSS dark mode classes
 */
(function() {
    'use strict';

    // Theme storage key
    const THEME_STORAGE_KEY = 'vvusrc_theme_preference';
    const THEME_TRANSITION_KEY = 'vvusrc_theme_transition';
    
    /**
     * Get the saved theme from localStorage
     * @returns {string} - 'dark' or 'light'
     */
    function getSavedTheme() {
        return localStorage.getItem(THEME_STORAGE_KEY) || 'light';
    }
    
    /**
     * Save theme preference to localStorage
     * @param {string} theme - 'dark' or 'light'
     */
    function saveTheme(theme) {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    }
    
    /**
     * Apply theme to DOM and CSS
     * @param {string} theme - 'dark' or 'light'
     */
    function applyTheme(theme) {
        const html = document.documentElement;
        
        // Set Bootstrap data attribute
        html.setAttribute('data-bs-theme', theme);
        
        // Update CSS classes for dark mode
        if (theme === 'dark') {
            document.body.classList.add('dark-mode');
            document.body.classList.remove('light-mode');
        } else {
            document.body.classList.add('light-mode');
            document.body.classList.remove('dark-mode');
        }
        
        // Dispatch custom event for other components to listen
        const event = new CustomEvent('themeChanged', {
            detail: { theme: theme }
        });
        window.dispatchEvent(event);
    }
    
    /**
     * Update the theme toggle button icon and state
     * @param {string} theme - 'dark' or 'light'
     */
    function updateToggleButton(theme) {
        const themeToggle = document.getElementById('themeToggle');
        
        if (!themeToggle) {
            return;
        }
        
        const icon = themeToggle.querySelector('i');
        if (icon) {
            // Show sun icon in dark mode, moon icon in light mode
            icon.className = 'fas fa-' + (theme === 'dark' ? 'sun' : 'moon');
        }
        
        // Update aria-label and title
        const modeText = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
        themeToggle.setAttribute('aria-label', 'Switch to ' + modeText);
        themeToggle.setAttribute('title', 'Switch to ' + modeText);
        
        // Update aria-pressed state
        themeToggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }
    
    /**
     * Initialize theme on page load
     * This runs immediately to prevent flash of wrong theme
     */
    function initializeThemeOnLoad() {
        const savedTheme = getSavedTheme();
        applyTheme(savedTheme);
        updateToggleButton(savedTheme);
    }
    
    /**
     * Initialize theme toggle button click handler
     */
    function initThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        
        if (!themeToggle) {
            return;
        }
        
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get current theme
            const currentTheme = localStorage.getItem(THEME_STORAGE_KEY) || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            console.log('[THEME TOGGLE] Switching from', currentTheme, 'to', newTheme);
            
            // Save to localStorage
            saveTheme(newTheme);
            
            // Apply theme immediately for better UX
            applyTheme(newTheme);
            updateToggleButton(newTheme);
            
            // Save to server in background (optional)
            saveThemeToServer(newTheme);
        });
    }
    
    /**
     * Save theme preference to server for persistence across sessions
     * @param {string} theme - 'dark' or 'light'
     */
    function saveThemeToServer(theme) {
        // Determine correct path to theme_toggle.php
        let themePath = 'theme_toggle.php';
        const pathname = window.location.pathname;
        
        // If we're in a subdirectory, adjust the path
        if (pathname.includes('/admin/')) {
            themePath = '../pages_php/theme_toggle.php';
        } else if (pathname.includes('/support/')) {
            themePath = '../theme_toggle.php';
        } else if (pathname.includes('/pages_php/')) {
            themePath = 'theme_toggle.php';
        } else {
            // Root level, assume pages_php directory
            themePath = 'pages_php/theme_toggle.php';
        }
        
        // Save to server
        fetch(themePath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'theme=' + encodeURIComponent(theme)
        }).catch(err => {
            console.warn('Could not save theme to server:', err);
        });
    }
    
    /**
     * Synchronize theme across browser tabs/windows
     */
    function initStorageSync() {
        window.addEventListener('storage', function(e) {
            if (e.key === THEME_STORAGE_KEY && e.newValue) {
                applyTheme(e.newValue);
                updateToggleButton(e.newValue);
            }
        });
    }
    
    /**
     * Initialize all theme functionality
     */
    function initializeTheme() {
        // Apply theme immediately (before DOMContentLoaded)
        initializeThemeOnLoad();
        
        // Wait for DOM to be ready for click handlers
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initThemeToggle();
                initStorageSync();
            });
        } else {
            initThemeToggle();
            initStorageSync();
        }
    }
    
    // Start initialization
    initializeTheme();
    
    // Expose API for other scripts
    window.themeManager = {
        setTheme: function(theme) {
            saveTheme(theme);
            applyTheme(theme);
            updateToggleButton(theme);
            saveThemeToServer(theme);
        },
        getTheme: function() {
            return getSavedTheme();
        },
        toggleTheme: function() {
            const current = getSavedTheme();
            const next = current === 'dark' ? 'light' : 'dark';
            this.setTheme(next);
        }
    };
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {

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

    // Load notifications
    loadNotifications();

    // Refresh notifications every 30 seconds - TEMPORARILY DISABLED FOR MODAL FIX
    // setInterval(loadNotifications, 30000);

    // AGGRESSIVE: Add event listener for notification dropdown show
    const notificationDropdown = document.getElementById('notificationsDropdown');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function() {
            setTimeout(() => {
                forceNotificationMobileLayout();
            }, 150);
        });

        // Also apply on dropdown shown event
        notificationDropdown.addEventListener('shown.bs.dropdown', function() {
            forceNotificationMobileLayout();
        });
    }

    // Apply layout fix on window resize
    window.addEventListener('resize', function() {
        setTimeout(() => {
            forceNotificationMobileLayout();
        }, 100);
    });
});

// Function to load notifications
function loadNotifications() {
    fetch('<?php echo $isSupportDir ? "get_notifications.php" : "support/get_notifications.php"; ?>')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid JSON response from server');
            }
        })
        .then(data => {
            const notificationsList = document.getElementById('notificationsList');
            const notificationBadge = document.getElementById('notificationBadge');

            if (data.success && data.notifications && data.notifications.length > 0) {
                // Update badge
                const unreadCount = data.unread_count || 0;
                if (unreadCount > 0) {
                    notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    notificationBadge.style.display = 'block';
                } else {
                    notificationBadge.style.display = 'none';
                }

                // Update notifications list
                let notificationsHtml = '';
                data.notifications.slice(0, 5).forEach(notification => {
                    const isUnread = notification.is_read == 0;
                    const timeAgo = formatTimeAgo(notification.created_at);
                    const hasActionUrl = notification.action_url && notification.action_url.trim() !== '';

                    // Use the action URL as provided by the notification system
                    // Apply path corrections for any legacy incorrect paths
                    let actionUrl = notification.action_url;
                    
                    // Fix common path issues
                    if (actionUrl) {
                        // Get current page path to determine context
                        const currentPath = window.location.pathname;
                        const isInSupportDir = currentPath.includes('/support/');
                        
                        console.log('=== PATH CORRECTION DEBUG ===');
                        console.log('Current path:', currentPath);
                        console.log('Is in support dir:', isInSupportDir);
                        console.log('Original action URL:', notification.action_url);
                        
                        // AGGRESSIVE path cleaning - handle all duplicate patterns
                        // Remove any duplicate pages_php/ segments
                        actionUrl = actionUrl.replace(/pages_php\/pages_php\//g, '');
                        
                        // Remove any duplicate support/ segments (most aggressive)
                        actionUrl = actionUrl.replace(/support\/support\//g, 'support/');
                        actionUrl = actionUrl.replace(/\/support\/support\//g, '/support/');
                        actionUrl = actionUrl.replace(/pages_php\/support\/support\//g, 'support/');
                        
                        // Remove absolute path prefixes like /vvusrc/pages_php/
                        actionUrl = actionUrl.replace(/^\/vvusrc\/pages_php\//, '');
                        
                        // Convert pages_php/support/ to support/ (since we're already in pages_php directory)
                        actionUrl = actionUrl.replace(/^pages_php\/support\//, 'support/');
                        
                        // Remove pages_php/ prefix for other files (since we're already in pages_php directory)
                        actionUrl = actionUrl.replace(/^pages_php\//, '');
                        
                        // Special handling when in support directory
                        if (isInSupportDir) {
                            // If the URL starts with support/, we need to navigate up and then down
                            if (actionUrl.startsWith('support/')) {
                                actionUrl = '../' + actionUrl;
                            }
                            // If it doesn't start with support/ or ../, we need to go up one level
                            else if (!actionUrl.startsWith('../') && !actionUrl.startsWith('http') && !actionUrl.startsWith('/')) {
                                actionUrl = '../' + actionUrl;
                            }
                        }
                        
                        // Final cleanup - remove any remaining duplicate segments
                        actionUrl = actionUrl.replace(/\/\/+/g, '/'); // Remove multiple slashes
                        actionUrl = actionUrl.replace(/support\/support\//g, 'support/'); // Final duplicate support cleanup
                        
                        console.log('Final corrected URL:', actionUrl);
                        console.log('=== END PATH CORRECTION ===');
                    }

                    // Function to get notification icon based on type
                    function getNotificationIcon(type) {
                        switch(type) {
                            case 'event': return 'calendar-alt';
                            case 'document': return 'file-alt';
                            case 'member': return 'user-plus';
                            case 'election': return 'vote-yea';
                            case 'finance': return 'money-bill-wave';
                            case 'welfare': return 'heart';
                            case 'feedback': return 'comments';
                            case 'minutes': return 'clipboard-list';
                            case 'report': return 'chart-bar';
                            default: return 'bell';
                        }
                    }

                    notificationsHtml += `
                        <li class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}" ${hasActionUrl ? `onclick="window.location.href='${actionUrl}'"` : ''} style="cursor: ${hasActionUrl ? 'pointer' : 'default'}; padding: 8px 10px !important; margin: 0 !important; width: 100% !important; box-sizing: border-box !important; border-bottom: 1px solid #f1f3f4 !important;">
                            <div class="d-flex align-items-start" style="display: flex !important; align-items: flex-start !important; width: 100% !important; margin: 0 !important; padding: 0 !important; gap: 4px !important; box-sizing: border-box !important;">
                                <div class="notification-icon" style="flex-shrink: 0 !important; width: 14px !important; height: 14px !important; display: flex !important; align-items: center !important; justify-content: center !important; margin: 0 !important; padding: 0 !important;">
                                    <i class="fas fa-${getNotificationIcon(notification.type)}" style="font-size: 10px !important; color: #2196f3 !important; margin: 0 !important; padding: 0 !important;"></i>
                                </div>
                                <div class="notification-content" style="flex: 1 !important; min-width: 0 !important; width: calc(100% - 18px) !important; margin: 0 !important; padding: 0 !important; overflow: visible !important; box-sizing: border-box !important;">
                                    <div class="notification-message" style="font-size: 11px !important; line-height: 1.2 !important; color: #333 !important; margin: 0 0 2px 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; word-wrap: break-word !important; overflow-wrap: break-word !important; white-space: normal !important; text-align: left !important; display: block !important; overflow: visible !important; box-sizing: border-box !important;">${notification.message}</div>
                                    <div class="notification-time" style="font-size: 9px !important; color: #666 !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; text-align: left !important; display: block !important; line-height: 1.1 !important; box-sizing: border-box !important;">${timeAgo}</div>
                                </div>
                            </div>
                        </li>
                    `;
                });

                notificationsList.innerHTML = notificationsHtml;

                // Ensure scrollable container is maintained
                notificationsList.style.cssText = `
                    max-height: 320px !important;
                    overflow-y: auto !important;
                    overflow-x: hidden !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    box-sizing: border-box !important;
                    scrollbar-width: thin !important;
                    scrollbar-color: #ccc #f8f9fa !important;
                `;

                // AGGRESSIVE MOBILE NOTIFICATION LAYOUT FIX
                setTimeout(() => {
                    forceNotificationMobileLayout();
                }, 100);
            } else {
                notificationBadge.style.display = 'none';
                notificationsList.innerHTML = `
                    <div class="text-center p-3 text-muted">
                        <i class="fas fa-bell-slash fa-2x mb-2"></i>
                        <p class="mb-0">No new notifications</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            // Hide notification badge and show fallback message
            const notificationBadge = document.getElementById('notificationBadge');
            const notificationsList = document.getElementById('notificationsList');

            if (notificationBadge) {
                notificationBadge.style.display = 'none';
            }

            if (notificationsList) {
                notificationsList.innerHTML = `
                    <div class="text-center p-3 text-muted">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0">Unable to load notifications</p>
                        <small>Please check your connection</small>
                    </div>
                `;
            }
        });
}

// AGGRESSIVE Mobile Notification Layout Fix Function
function forceNotificationMobileLayout() {
    // Only apply on mobile devices
    if (window.innerWidth <= 991) {
        const dropdown = document.querySelector('.dropdown-menu.notification-dropdown');
        const notificationItems = document.querySelectorAll('.notification-item');

        if (dropdown) {
            // Force dropdown container styles
            dropdown.style.cssText = `
                width: 300px !important;
                max-width: calc(100vw - 30px) !important;
                min-width: 280px !important;
                max-height: 400px !important;
                padding: 0 !important;
                margin: 0 !important;
                box-sizing: border-box !important;
                border-radius: 8px !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                position: fixed !important;
                top: 60px !important;
                left: 50% !important;
                right: auto !important;
                transform: translateX(-50%) !important;
                z-index: 1055 !important;
            `;
        }

        // Force notifications list scrollable container
        const notificationsList = document.getElementById('notificationsList');
        if (notificationsList) {
            notificationsList.style.cssText = `
                max-height: 320px !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                padding: 0 !important;
                margin: 0 !important;
                box-sizing: border-box !important;
                scrollbar-width: thin !important;
                scrollbar-color: #ccc #f8f9fa !important;
            `;
        }

        // Force dropdown header styles
        const dropdownHeader = document.querySelector('.dropdown-menu.notification-dropdown .dropdown-header');
        if (dropdownHeader) {
            dropdownHeader.style.cssText = `
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 8px 10px !important;
                margin: 0 !important;
                box-sizing: border-box !important;
                background: #fff !important;
                border-bottom: 1px solid #f1f3f4 !important;
                overflow: hidden !important;
            `;

            const headerTitle = dropdownHeader.querySelector('span');
            if (headerTitle) {
                headerTitle.style.cssText = `
                    font-size: 13px !important;
                    font-weight: 600 !important;
                    color: #333 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    flex-shrink: 0 !important;
                    white-space: nowrap !important;
                    max-width: 60% !important;
                `;
            }

            const headerLink = dropdownHeader.querySelector('a');
            if (headerLink) {
                headerLink.style.cssText = `
                    font-size: 11px !important;
                    color: #2196f3 !important;
                    text-decoration: none !important;
                    font-weight: 500 !important;
                    margin: 0 !important;
                    padding: 2px 4px !important;
                    flex-shrink: 0 !important;
                    white-space: nowrap !important;
                    max-width: 35% !important;
                    border-radius: 4px !important;
                `;
            }
        }

        notificationItems.forEach(item => {
            // Force notification item styles
            item.style.cssText = `
                padding: 8px 10px !important;
                margin: 0 !important;
                width: 100% !important;
                box-sizing: border-box !important;
                display: block !important;
                border-bottom: 1px solid #f1f3f4 !important;
            `;

            const flexContainer = item.querySelector('.d-flex');
            if (flexContainer) {
                flexContainer.style.cssText = `
                    display: flex !important;
                    align-items: flex-start !important;
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    gap: 4px !important;
                    box-sizing: border-box !important;
                `;
            }

            const icon = item.querySelector('.notification-icon');
            if (icon) {
                icon.style.cssText = `
                    flex-shrink: 0 !important;
                    width: 14px !important;
                    height: 14px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    margin: 0 !important;
                    padding: 0 !important;
                `;

                const iconElement = icon.querySelector('i');
                if (iconElement) {
                    iconElement.style.cssText = `
                        font-size: 10px !important;
                        color: #2196f3 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    `;
                }
            }

            const content = item.querySelector('.notification-content');
            if (content) {
                content.style.cssText = `
                    flex: 1 !important;
                    min-width: 0 !important;
                    width: calc(100% - 18px) !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                `;
            }

            const message = item.querySelector('.notification-message');
            if (message) {
                message.style.cssText = `
                    font-size: 11px !important;
                    line-height: 1.2 !important;
                    color: #333 !important;
                    margin: 0 0 2px 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    word-wrap: break-word !important;
                    overflow-wrap: break-word !important;
                    white-space: normal !important;
                    text-align: left !important;
                    display: block !important;
                    overflow: visible !important;
                    box-sizing: border-box !important;
                `;
            }

            const time = item.querySelector('.notification-time');
            if (time) {
                time.style.cssText = `
                    font-size: 9px !important;
                    color: #666 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    text-align: left !important;
                    display: block !important;
                    line-height: 1.1 !important;
                    box-sizing: border-box !important;
                `;
            }
        });
    }
}

// Function to format time ago
function formatTimeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
    if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
    if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + 'd ago';
    return date.toLocaleDateString();
}

// Sidebar toggle functionality is now handled in sidebar-toggle.js
</script>

<!-- Mobile Responsive JavaScript -->
<!-- Removed mobile-responsive-tables.js as it may interfere with header positioning -->

<!-- Page content starts here -->