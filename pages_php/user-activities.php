<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
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

// Check if user has admin privileges (super admin or admin)
if (!hasAdminPrivileges()) {
    header("Location: dashboard.php");
    exit();
}

// Set page title
$pageTitle = "User Activities - SRC Management System";

// Process activity creation if requested
$createMessage = '';
$createMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_activity'])) {
    // Get data from the form
    $userId = isset($_POST['create_user_id']) ? (int)$_POST['create_user_id'] : 0;
    $activityType = isset($_POST['create_activity_type']) ? $_POST['create_activity_type'] : '';
    $description = isset($_POST['create_activity_description']) ? $_POST['create_activity_description'] : '';
    $pageUrl = isset($_POST['create_page_url']) ? $_POST['create_page_url'] : '';
    $ipAddress = isset($_POST['create_ip_address']) ? $_POST['create_ip_address'] : $_SERVER['REMOTE_ADDR'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($userId)) {
        $errors[] = "User is required";
    }
    
    if (empty($activityType)) {
        $errors[] = "Activity type is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    // If no errors, create the activity
    if (empty($errors)) {
        // Get the user details
        $user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$userId]);
        
        if ($user) {
            // Insert into user_activities table
            $sql = "INSERT INTO user_activities (user_id, activity_type, activity_description, page_url, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $result = insert($sql, [$userId, $activityType, $description, $pageUrl, $ipAddress]);
            
            if ($result) {
                $createMessage = "Activity created successfully!";
                $createMessageType = "success";
            } else {
                $createMessage = "Failed to create activity. Please try again.";
                $createMessageType = "danger";
            }
        } else {
            $createMessage = "Invalid user selected.";
            $createMessageType = "danger";
        }
    } else {
        $createMessage = "Please fix the following errors: " . implode(", ", $errors);
        $createMessageType = "danger";
    }
}

// Process activity clearing if requested
$clearMessage = '';
$clearMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_activities'])) {
    // Get filters from the form
    $clearFilters = [];
    
    if (!empty($_POST['clear_user_id'])) {
        $clearFilters['user_id'] = (int)$_POST['clear_user_id'];
    }
    
    if (!empty($_POST['clear_activity_type'])) {
        $clearFilters['activity_type'] = $_POST['clear_activity_type'];
    }
    
    if (!empty($_POST['clear_start_date'])) {
        $clearFilters['start_date'] = $_POST['clear_start_date'];
    }
    
    if (!empty($_POST['clear_end_date'])) {
        $clearFilters['end_date'] = $_POST['clear_end_date'];
    }
    
    // Attempt to clear activities
    $clearResult = clearUserActivities($clearFilters);
    
    if ($clearResult) {
        $clearMessage = 'Activities cleared successfully!';
        $clearMessageType = 'success';
    } else {
        $clearMessage = 'Failed to clear activities. Please try again.';
        $clearMessageType = 'danger';
    }
}

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}

// Initialize filters
$filters = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Process filter form
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['filter'])) {
    if (!empty($_GET['email'])) {
        $filters['user_email'] = $_GET['email'];
    }
    
    if (!empty($_GET['activity_type'])) {
        $filters['activity_type'] = $_GET['activity_type'];
    }
    
    if (!empty($_GET['start_date'])) {
        $filters['start_date'] = $_GET['start_date'];
    }
    
    if (!empty($_GET['end_date'])) {
        $filters['end_date'] = $_GET['end_date'];
    }
    
    if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
        $filters['user_id'] = (int)$_GET['user_id'];
    }
}

// Get activity data
$activities = getUserActivities($filters, $perPage, $offset);
$totalActivities = countUserActivities($filters);
$totalPages = ceil($totalActivities / $perPage);

// Get summary data for charts
$activityTypeSummary = getActivityTypeSummary($filters);

// Get the list of users for filter dropdown
$users = fetchAll("SELECT user_id, email, CONCAT(first_name, ' ', last_name) as full_name FROM users ORDER BY email");

// Get activity types for filter dropdown
$activityTypes = [
    'login' => 'Login',
    'logout' => 'Logout',
    'create' => 'Create',
    'update' => 'Update',
    'delete' => 'Delete',
    'view' => 'View'
];

// Activity description templates for enhanced detail
$activityDescriptionTemplates = [
    'login' => [
        'title' => 'User Login',
        'icon' => 'fa-sign-in-alt',
        'color' => '#4caf50',
        'details' => 'User successfully authenticated and logged into the system'
    ],
    'logout' => [
        'title' => 'User Logout',
        'icon' => 'fa-sign-out-alt',
        'color' => '#f44336',
        'details' => 'User session ended and logged out of the system'
    ],
    'create' => [
        'title' => 'Content Created',
        'icon' => 'fa-plus-circle',
        'color' => '#2196f3',
        'details' => 'New content, record, or entity was created in the system'
    ],
    'update' => [
        'title' => 'Content Updated',
        'icon' => 'fa-edit',
        'color' => '#ff9800',
        'details' => 'Existing content or record was modified or updated'
    ],
    'delete' => [
        'title' => 'Content Deleted',
        'icon' => 'fa-trash-alt',
        'color' => '#e91e63',
        'details' => 'Content or record was permanently removed from the system'
    ],
    'view' => [
        'title' => 'Content Viewed',
        'icon' => 'fa-eye',
        'color' => '#9c27b0',
        'details' => 'User accessed and viewed specific content or page'
    ],
    'download' => [
        'title' => 'File Downloaded',
        'icon' => 'fa-download',
        'color' => '#00bcd4',
        'details' => 'User downloaded a file or resource from the system'
    ],
    'upload' => [
        'title' => 'File Uploaded',
        'icon' => 'fa-upload',
        'color' => '#3f51b5',
        'details' => 'User uploaded a file or resource to the system'
    ],
    'register' => [
        'title' => 'User Registered',
        'icon' => 'fa-user-plus',
        'color' => '#009688',
        'details' => 'New user account was created in the system'
    ],
    'vote' => [
        'title' => 'Vote Recorded',
        'icon' => 'fa-check-square',
        'color' => '#673ab7',
        'details' => 'User cast a vote or made a selection in a poll or ballot'
    ],
    'email' => [
        'title' => 'Email Action',
        'icon' => 'fa-envelope',
        'color' => '#795548',
        'details' => 'Email was sent to or received from user'
    ],
    'sms' => [
        'title' => 'SMS Action',
        'icon' => 'fa-comment',
        'color' => '#607d8b',
        'details' => 'SMS message was sent to or received from user'
    ]
];

// Function to get activity description context
function getActivityDescription($activityType, $description) {
    global $activityDescriptionTemplates;
    
    if (isset($activityDescriptionTemplates[$activityType])) {
        return $activityDescriptionTemplates[$activityType];
    }
    
    // Default template for unknown types
    return [
        'title' => ucfirst($activityType),
        'icon' => 'fa-dot-circle',
        'color' => '#757575',
        'details' => $description ?: 'System activity recorded'
    ];
}
?>

<style>
    .activity-badge {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 15px;
    }
    .activity-badge.login { background-color: #4caf50; }
    .activity-badge.logout { background-color: #f44336; }
    .activity-badge.create { background-color: #2196f3; }
    .activity-badge.update { background-color: #ff9800; }
    .activity-badge.delete { background-color: #e91e63; }
    .activity-badge.view { background-color: #9c27b0; }
    .activity-badge.download { background-color: #00bcd4; }
    .activity-badge.upload { background-color: #3f51b5; }
    .activity-badge.register { background-color: #009688; }
    .activity-badge.vote { background-color: #673ab7; }
    .activity-badge.email { background-color: #795548; }
    .activity-badge.sms { background-color: #607d8b; }
    .activity-badge.other { background-color: #757575; }
    
    .activity-item {
        display: flex;
        align-items: flex-start;
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }
    .activity-item:hover {
        background-color: #f9f9f9;
    }
    .activity-content {
        flex: 1;
    }
    .activity-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    .activity-user {
        font-weight: 600;
        color: #333;
    }
    .activity-time {
        color: #777;
        font-size: 0.85rem;
    }
    .activity-description {
        margin-bottom: 5px;
    }
    .activity-meta {
        font-size: 0.85rem;
        color: #666;
    }
    
    /* Enhanced activity detail styles */
    .activity-row-expandable {
        cursor: pointer;
        user-select: none;
    }
    
    .activity-row-expandable:hover {
        background-color: #f5f5f5 !important;
    }
    
    .activity-expand-btn {
        transition: transform 0.3s ease;
        color: #667eea;
    }
    
    .activity-expand-btn.expanded {
        transform: rotate(180deg);
    }
    
    .activity-detail-row {
        display: none;
        background-color: #f9f9f9;
    }
    
    .activity-detail-row.show {
        display: table-row;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .activity-detail-content {
        padding: 20px;
        background: white;
        border-top: 2px solid #e0e0e0;
    }
    
    .detail-section {
        margin-bottom: 15px;
    }
    
    .detail-section:last-child {
        margin-bottom: 0;
    }
    
    .detail-label {
        font-weight: 600;
        color: #667eea;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: block;
    }
    
    .detail-value {
        margin-top: 5px;
        color: #333;
        padding-left: 10px;
        border-left: 3px solid #667eea;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        line-height: 1.6;
        display: block;
        text-decoration: none;
    }
    
    .detail-value * {
        text-decoration: none;
    }
    
    .detail-value strong {
        display: block;
        margin-top: 8px;
        margin-bottom: 4px;
        font-weight: 600;
    }
    
    .detail-value em {
        display: block;
        margin-top: 4px;
        font-style: italic;
    }
    
    .detail-value br {
        display: block;
        content: '';
        margin: 6px 0;
    }
    
    .detail-value code {
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        word-break: break-all;
        display: inline-block;
        margin: 4px 0;
    }
    

    .activity-info-badge {
        display: inline-block;
        background-color: #f0f0f0;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-right: 8px;
        margin-bottom: 8px;
        border-left: 3px solid #667eea;
    }
    
    .filter-card {
        margin-bottom: 20px;
    }
    .filter-toggle {
        cursor: pointer;
    }
    
    /* Mobile responsive table styles */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .table thead {
            display: none;
        }
        
        .table tbody {
            display: block;
            width: 100%;
        }
        
        .table tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: visible;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            page-break-inside: avoid;
            break-inside: avoid;
            background: white;
        }
        
        .table td {
            display: grid;
            grid-template-columns: 110px 1fr;
            grid-gap: 10px;
            text-align: left;
            padding: 12px 15px;
            border: none;
            border-bottom: 1px solid #eee;
            position: relative;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
            line-height: 1.5;
            align-items: start;
        }
        
        .table td:last-child {
            border-bottom: none;
        }
        
        .table td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #667eea;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            word-wrap: break-word;
            flex-shrink: 0;
        }
        
        /* First column (expand button) - no label */
        .table td:first-child {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            grid-template-columns: none;
        }
        
        .table td:first-child:before {
            display: none;
        }
        
        /* Ensure text content is properly displayed */
        .table td > span,
        .table td > small {
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        
        .activity-expand-btn {
            display: inline-block !important;
            float: none !important;
            margin: 0 !important;
        }
        
        .pagination {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .pagination .page-link {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 576px) {
        .filter-card .row {
            flex-direction: column;
        }
        
        .filter-card .col-md-6,
        .filter-card .col-md-12 {
            width: 100%;
        }
        
        .user-activities-header {
            padding: 1.5rem 1rem;
        }

        .user-activities-header-content {
            flex-direction: column;
            gap: 1rem;
        }

        .user-activities-title {
            font-size: 1.5rem;
            gap: 0.5rem;
        }

        .user-activities-title i {
            font-size: 1.3rem;
        }

        .user-activities-description {
            font-size: 1rem;
        }

        .user-activities-header-actions {
            width: 100%;
        }

        .btn-header-action {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            flex: 1;
            min-width: 120px;
        }
        
        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .content-card {
            margin-bottom: 15px;
        }
        
        .content-card-body {
            padding: 12px;
        }
        
        .table td {
            padding: 8px 10px;
            line-height: 1.5;
            white-space: normal;
        }
        
        .table td::before {
            font-size: 0.75rem;
            min-width: 90px;
        }
        
        .detail-value {
            font-size: 0.9rem;
            line-height: 1.6;
            white-space: normal;
            word-wrap: break-word;
            overflow-wrap: break-word;
            padding-left: 8px;
        }
        
        .detail-value strong,
        .detail-value em {
            display: block;
            margin-top: 6px;
            margin-bottom: 2px;
        }
        
        .detail-value br {
            display: block;
            margin: 4px 0;
        }
        
        .detail-label {
            margin-bottom: 8px;
            display: block;
            clear: both;
            font-size: 0.85rem;
        }
        
        .detail-section {
            margin-bottom: 15px;
            clear: both;
            page-break-inside: avoid;
        }
        
        .activity-info-badge {
            display: block;
            margin-bottom: 8px;
            width: 100%;
            box-sizing: border-box;
            clear: both;
        }
        
        .activity-detail-content .row {
            margin-left: -5px;
            margin-right: -5px;
        }
        
        .activity-detail-content .col-md-6,
        .activity-detail-content .col-12 {
            padding-left: 5px;
            padding-right: 5px;
        }
        
        .detail-value br {
            display: block;
            content: '';
            margin: 2px 0;
        }
        
        .detail-value strong,
        .detail-value em {
            display: block;
            margin-top: 4px;
        }
    }
    
    /* Alignment and spacing improvements */
    .content-card {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    
    .content-card-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 2px solid #e9ecef;
        border-radius: 8px 8px 0 0;
    }
    
    .content-card-title {
        margin: 0;
        color: #333;
        font-weight: 600;
        font-size: 1.3rem;
    }
    
    .content-card-body {
        padding: 20px;
    }
    
    /* Alignment for activity summary table */
    .content-card .table {
        margin-bottom: 0;
    }
    
    .content-card .table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 12px 15px;
        text-align: left;
    }
    
    .content-card .table td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    /* Better alignment for badges and content */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0.4rem 0.8rem;
    }
    
    .progress {
        background-color: #e9ecef;
    }
    
    .progress-bar {
        background-color: #667eea;
    }
    
    /* Responsive form styles */
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    
    .form-control,
    .form-select {
        border-color: #ddd;
        border-radius: 6px;
        padding: 0.6rem 0.75rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn {
        border-radius: 6px;
        padding: 0.6rem 1rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Footer Styles */
    body {
        margin: 0 !important;
        padding: 0 !important;
        min-height: 100vh !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .main-content {
        flex: 1 !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    .src-footer {
        margin-top: auto !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .src-footer .container-fluid,
    .footer-container {
        padding-left: 300px !important;
        padding-right: 50px !important;
        margin: 0 !important;
        max-width: none !important;
        width: 100% !important;
    }
    
    .footer-bottom {
        background: transparent !important;
    }
    
    .copyright-and-links {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 25px !important;
    }
    
    @media (max-width: 768px) {
        .src-footer .container-fluid,
        .footer-container {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
        
        .copyright-and-links {
            flex-direction: column;
            gap: 15px;
        }
    }

    /* Mobile Full-Width Optimization for User Activities Page */
    @media (max-width: 991px) {
        [class*="col-md-"], [class*="col-lg-"], [class*="col-xl-"] {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .container-fluid {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .user-activities-header, .page-hero, .modern-page-header {
            border-radius: 12px !important;
        }
        .card, .activity-card, .filter-card, .chart-card {
            margin-left: 0 !important;
            margin-right: 0 !important;
            border-radius: 0 !important;
        }
    }
</style>

<!-- Custom User Activities Header -->
<div class="user-activities-header animate__animated animate__fadeInDown">
    <div class="user-activities-header-content">
        <div class="user-activities-header-main">
            <h1 class="user-activities-title">
                <i class="fas fa-chart-line me-3"></i>
                User Activities
            </h1>
            <p class="user-activities-description">Monitor and manage user activity logs and system interactions</p>
        </div>
        <div class="user-activities-header-actions">
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-2"></i>Create Activity
            </button>
            <button type="button" class="btn btn-header-action btn-header-danger" data-bs-toggle="modal" data-bs-target="#clearActivitiesModal">
                <i class="fas fa-trash-alt me-2"></i>Clear Activities
            </button>
        </div>
    </div>
</div>

<style>
.user-activities-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: visible;
}

.user-activities-header-content {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-wrap: wrap;
    gap: 2rem;
    width: 100%;
}

.user-activities-header-main {
    flex: 0 1 auto;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.user-activities-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 0.8rem;
    white-space: nowrap;
}

.user-activities-title i {
    font-size: 2.2rem;
    opacity: 0.9;
    flex-shrink: 0;
}

.user-activities-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
    white-space: nowrap;
}

.user-activities-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
    justify-content: flex-start;
    flex-shrink: 0;
    margin-left: auto;
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
    white-space: nowrap;
    flex-shrink: 0;
    z-index: 10;
    position: relative;
    min-width: fit-content;
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-header-danger {
    background: rgba(220, 53, 69, 0.3);
    border-color: rgba(220, 53, 69, 0.5);
}

.btn-header-danger:hover {
    background: rgba(220, 53, 69, 0.5);
    border-color: rgba(220, 53, 69, 0.7);
}

@media (max-width: 1024px) {
    .user-activities-header {
        padding: 2rem 1.5rem;
    }

    .user-activities-header-content {
        gap: 1.5rem;
    }

    .user-activities-title {
        font-size: 1.8rem;
    }

    .user-activities-description {
        font-size: 1rem;
    }

    .btn-header-action {
        font-size: 0.85rem;
        padding: 0.5rem 0.9rem;
    }
}

@media (max-width: 768px) {
    .user-activities-header {
        padding: 2rem 1.5rem;
    }

    .user-activities-header-content {
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
    }

    .user-activities-header-main {
        flex: 1;
        text-align: center;
        align-items: center;
        width: 100%;
    }

    .user-activities-title {
        font-size: 1.5rem;
        gap: 0.6rem;
        justify-content: center;
        white-space: normal;
    }

    .user-activities-title i {
        font-size: 1.4rem;
    }

    .user-activities-description {
        font-size: 0.95rem;
        white-space: normal;
    }

    .user-activities-header-actions {
        width: 100%;
        justify-content: center;
        margin-left: 0;
    }

    .btn-header-action {
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
        flex: 1;
        min-width: 120px;
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

<!-- Page Content -->
<div class="container-fluid px-4">
    
    <!-- Notification area -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

<!-- Activity Summary -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">Activity Summary</h3>
            </div>
            <div class="content-card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Activity Type</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalCount = 0;
                            foreach ($activityTypeSummary as $summary) {
                                $totalCount += $summary['count'];
                            }
                            
                            foreach ($activityTypeSummary as $summary): 
                                $percentage = $totalCount > 0 ? round(($summary['count'] / $totalCount) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                        $icon = '';
                                        switch ($summary['activity_type']) {
                                            case 'login':
                                                $icon = '<i class="fas fa-sign-in-alt text-success me-2"></i>';
                                                break;
                                            case 'logout':
                                                $icon = '<i class="fas fa-sign-out-alt text-danger me-2"></i>';
                                                break;
                                            case 'create':
                                                $icon = '<i class="fas fa-plus text-primary me-2"></i>';
                                                break;
                                            case 'update':
                                                $icon = '<i class="fas fa-edit text-info me-2"></i>';
                                                break;
                                            case 'delete':
                                                $icon = '<i class="fas fa-trash text-danger me-2"></i>';
                                                break;
                                            case 'view':
                                                $icon = '<i class="fas fa-eye text-secondary me-2"></i>';
                                                break;
                                            default:
                                                $icon = '<i class="fas fa-dot-circle me-2"></i>';
                                        }
                                        echo $icon . ucfirst($summary['activity_type']);
                                    ?>
                                </td>
                                <td class="text-center"><?php echo $summary['count']; ?></td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="progress me-2" style="width: 100px; height: 10px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                 aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span><?php echo $percentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($activityTypeSummary)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No activity data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="content-card">
            <div class="content-card-header">
                <h3 class="content-card-title">Filter Activities</h3>
            </div>
            <div class="content-card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" 
                               value="<?php echo isset($filters['email']) ? htmlspecialchars($filters['email']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>"
                                <?php echo (isset($filters['user_id']) && $filters['user_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['email'] . ' (' . $user['full_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="activity_type" name="activity_type">
                            <option value="">All Activities</option>
                            <?php foreach ($activityTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"
                                <?php echo (isset($filters['activity_type']) && $filters['activity_type'] == $type) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo isset($filters['start_date']) ? htmlspecialchars($filters['start_date']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo isset($filters['end_date']) ? htmlspecialchars($filters['end_date']) : ''; ?>">
                    </div>
                    
                    <div class="col-md-12 text-end">
                        <button type="submit" name="filter" value="1" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i> Apply Filters
                        </button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-sync-alt me-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activities List -->
<div class="content-card mb-4">
    <div class="content-card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="content-card-title">User Activities</h3>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2"><?php echo number_format($totalActivities); ?> activities found</span>
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearActivitiesModal">
                    <i class="fas fa-trash-alt me-1"></i> Clear Activities
                </button>
            </div>
        </div>
    </div>
    <div class="content-card-body">
        <?php if (!empty($createMessage)): ?>
        <div class="alert alert-<?php echo $createMessageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $createMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($clearMessage)): ?>
        <div class="alert alert-<?php echo $clearMessageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $clearMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th style="width: 30px;"></th>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Activity Type</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $index => $activity): 
                        $activityDesc = getActivityDescription($activity['activity_type'], $activity['activity_description']);
                        $rowId = 'activity-' . $index;
                    ?>
                    <tr class="activity-row-expandable" data-activity-id="<?php echo $rowId; ?>" role="button" tabindex="0">
                        <td style="text-align: center;">
                            <i class="fas fa-chevron-down activity-expand-btn" data-toggle="collapse" data-target="#<?php echo $rowId; ?>"></i>
                        </td>
                        <td data-label="Date & Time"><?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?></td>
                        <td data-label="User">
                            <?php 
                                $fullName = trim($activity['first_name'] . ' ' . $activity['last_name']);
                                echo !empty($fullName) ? htmlspecialchars($fullName) : htmlspecialchars($activity['email']); 
                            ?>
                        </td>
                        <td data-label="Role"><span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucfirst($activity['role'] ?? 'Unknown')); ?></span></td>
                        <td data-label="Activity Type">
                            <?php 
                                $badgeClass = 'bg-secondary';
                                
                                switch ($activity['activity_type']) {
                                    case 'login':
                                        $badgeClass = 'bg-success';
                                        break;
                                    case 'logout':
                                        $badgeClass = 'bg-danger';
                                        break;
                                    case 'create':
                                        $badgeClass = 'bg-primary';
                                        break;
                                    case 'update':
                                        $badgeClass = 'bg-info';
                                        break;
                                    case 'delete':
                                        $badgeClass = 'bg-danger';
                                        break;
                                    case 'view':
                                        $badgeClass = 'bg-secondary';
                                        break;
                                }
                                
                                echo '<span class="badge ' . $badgeClass . '"><i class="fas ' . $activityDesc['icon'] . ' me-1"></i> ' . $activityDesc['title'] . '</span>';
                            ?>
                        </td>
                        <td data-label="Description">
                            <small class="text-muted"><?php echo htmlspecialchars(substr($activity['activity_description'], 0, 50)); ?><?php echo strlen($activity['activity_description']) > 50 ? '...' : ''; ?></small>
                        </td>
                        <td data-label="IP Address"><?php echo htmlspecialchars($activity['ip_address'] ?? 'Unknown'); ?></td>
                    </tr>
                    <tr class="activity-detail-row" id="<?php echo $rowId; ?>">
                        <td colspan="7">
                            <div class="activity-detail-content">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="detail-section">
                                            <div class="detail-label"><i class="fas fa-user me-2"></i>User Information</div>
                                            <div class="detail-value">
                                                <strong><?php 
                                                    $fullName = trim($activity['first_name'] . ' ' . $activity['last_name']);
                                                    echo !empty($fullName) ? htmlspecialchars($fullName) : htmlspecialchars($activity['email']); 
                                                ?></strong><br>
                                                Email: <?php echo htmlspecialchars($activity['email']); ?><br>
                                                Role: <span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucfirst($activity['role'] ?? 'Unknown')); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="detail-section">
                                            <div class="detail-label"><i class="fas fa-clock me-2"></i>Timestamp</div>
                                            <div class="detail-value">
                                                <?php echo date('l, F j, Y \a\t g:i A', strtotime($activity['created_at'])); ?><br>
                                                <small class="text-muted"><?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="detail-section">
                                            <div class="detail-label"><i class="fas <?php echo $activityDesc['icon']; ?> me-2"></i>Activity Details</div>
                                            <div class="detail-value">
                                                <strong><?php echo $activityDesc['title']; ?></strong><br>
                                                <em><?php echo $activityDesc['details']; ?></em>
                                            </div>
                                        </div>
                                        
                                        <div class="detail-section">
                                            <div class="detail-label"><i class="fas fa-network-wired me-2"></i>Network Information</div>
                                            <div class="detail-value">
                                                IP Address: <?php echo htmlspecialchars($activity['ip_address'] ?? 'Unknown'); ?><br>
                                                <small class="text-muted">Connection from this IP address</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="detail-section">
                                            <div class="detail-label"><i class="fas fa-file-alt me-2"></i>Full Description</div>
                                            <div class="detail-value">
                                                <?php echo htmlspecialchars($activity['activity_description']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($activity['page_url'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="detail-section">
                                            <div class="detail-label"><i class="fas fa-link me-2"></i>Page Information</div>
                                            <div class="detail-value">
                                                Page: <code><?php echo htmlspecialchars($activity['page_url']); ?></code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            No activities found
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Activity pagination">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate range of pages to show
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : '') . '">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Show page links
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="?page=' . $i . (!empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : '') . '">' . $i . '</a></li>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : '') . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($filters) ? '&' . http_build_query(array_merge($filters, ['filter' => 1])) : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Clear Activities Modal -->
<div class="modal fade" id="clearActivitiesModal" tabindex="-1" aria-labelledby="clearActivitiesModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearActivitiesModalLabel">Clear User Activities</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Activities will be permanently deleted.
                    </div>
                    
                    <p>You can optionally filter which activities to clear:</p>
                    
                    <div class="mb-3">
                        <label for="clear_user_id" class="form-label">User</label>
                        <select class="form-select" id="clear_user_id" name="clear_user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['email'] . ' (' . $user['full_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clear_activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="clear_activity_type" name="clear_activity_type">
                            <option value="">All Activities</option>
                            <?php foreach ($activityTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="clear_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="clear_start_date" name="clear_start_date">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="clear_end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="clear_end_date" name="clear_end_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="clear_activities" value="1" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Clear Activities
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Activity Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Create New Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_user_id" class="form-label">User</label>
                        <select class="form-select" id="create_user_id" name="create_user_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo htmlspecialchars($user['email'] . ' (' . $user['full_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_activity_type" class="form-label">Activity Type</label>
                        <select class="form-select" id="create_activity_type" name="create_activity_type" required>
                            <option value="">Select Activity Type</option>
                            <?php foreach ($activityTypes as $type => $label): ?>
                            <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_activity_description" class="form-label">Description</label>
                        <textarea class="form-control" id="create_activity_description" name="create_activity_description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_page_url" class="form-label">Page URL</label>
                        <input type="text" class="form-control" id="create_page_url" name="create_page_url" placeholder="e.g., dashboard.php">
                    </div>
                    
                    <div class="mb-3">
                        <label for="create_ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="create_ip_address" name="create_ip_address" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_activity" value="1" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
// Expandable activity rows functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle expandable rows
    const expandableBtns = document.querySelectorAll('.activity-expand-btn');
    expandableBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const row = this.closest('tr');
            const activityId = row.dataset.activityId;
            const detailRow = document.getElementById(activityId);
            
            if (detailRow) {
                detailRow.classList.toggle('show');
                btn.classList.toggle('expanded');
            }
        });
    });
    
    // Handle row click to expand
    const expandableRows = document.querySelectorAll('.activity-row-expandable');
    expandableRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on the button itself
            if (!e.target.closest('.activity-expand-btn')) {
                const activityId = this.dataset.activityId;
                const detailRow = document.getElementById(activityId);
                const btn = this.querySelector('.activity-expand-btn');
                
                if (detailRow) {
                    detailRow.classList.toggle('show');
                    btn.classList.toggle('expanded');
                }
            }
        });
        
        // Handle keyboard navigation
        row.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const activityId = this.dataset.activityId;
                const detailRow = document.getElementById(activityId);
                const btn = this.querySelector('.activity-expand-btn');
                
                if (detailRow) {
                    detailRow.classList.toggle('show');
                    btn.classList.toggle('expanded');
                }
            }
        });
    });
    
    // Add confirmation for clearing activities
    const clearActivitiesForm = document.querySelector('#clearActivitiesModal form');
    if (clearActivitiesForm) {
        clearActivitiesForm.addEventListener('submit', function(event) {
            // Get selected filters
            const userId = document.getElementById('clear_user_id').value;
            const activityType = document.getElementById('clear_activity_type').value;
            const startDate = document.getElementById('clear_start_date').value;
            const endDate = document.getElementById('clear_end_date').value;
            
            // Build confirmation message
            let confirmMsg = 'Are you sure you want to clear';
            
            if (userId) {
                const userOption = document.querySelector(`#clear_user_id option[value="${userId}"]`);
                confirmMsg += ' activities for ' + userOption.textContent;
            } else {
                confirmMsg += ' ALL user activities';
            }
            
            if (activityType) {
                const typeOption = document.querySelector(`#clear_activity_type option[value="${activityType}"]`);
                confirmMsg += ' of type "' + typeOption.textContent + '"';
            }
            
            if (startDate || endDate) {
                confirmMsg += ' from';
                if (startDate) confirmMsg += ' ' + startDate;
                if (startDate && endDate) confirmMsg += ' to';
                if (endDate) confirmMsg += ' ' + endDate;
            }
            
            confirmMsg += '? This action cannot be undone.';
            
            // Show confirmation dialog
            if (!confirm(confirmMsg)) {
                event.preventDefault();
            }
        });
    }
    
    // Responsive behavior for tables
    function adjustTableForMobile() {
        const tables = document.querySelectorAll('.table');
        const isMobile = window.innerWidth <= 768;
        
        tables.forEach(table => {
            if (isMobile) {
                table.classList.add('mobile-responsive');
            } else {
                table.classList.remove('mobile-responsive');
            }
        });
    }
    
    // Call on load and window resize
    adjustTableForMobile();
    window.addEventListener('resize', adjustTableForMobile);
    
    // Smooth scrolling for pagination
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add smooth scroll animation
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    });
});
</script>
 
</div> <!-- Close container-fluid -->
</div> <!-- Close main-content -->

<?php require_once 'includes/footer.php'; ?>
