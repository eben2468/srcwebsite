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

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if election ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Check if this is a visibility check request
    if (isset($_GET['action']) && $_GET['action'] === 'check_visibility') {
        // Include auth_functions.php for canManageElections function
        require_once __DIR__ . '/../includes/auth_functions.php';
        
        // Only super admin can run visibility checks
        if (!canManageElections()) {
            $_SESSION['error'] = "You don't have permission to perform this action.";
            header("Location: elections.php");
            exit();
        }
        
        $pageTitle = "Vote Privacy Check - SRC Management System";
        require_once 'includes/header.php';
        ?>
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Vote Privacy Check</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Vote Privacy Check</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="elections.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Elections
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vote Privacy Verification</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> <strong>Privacy Protections Active:</strong> Vote counts are properly hidden from non-admin users during active elections.
                    </div>
                    
                    <p>The following privacy protections are currently in place:</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Protection</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Election Detail Page</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>Vote counts are hidden in the election detail page for non-admin users</td>
                                </tr>
                                <tr>
                                    <td>Candidate Detail Page</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>Vote counts are hidden in candidate profiles for non-admin users</td>
                                </tr>
                                <tr>
                                    <td>Results Page Access</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>Election results page is restricted to admins during active elections</td>
                                </tr>
                                <tr>
                                    <td>Candidate Ordering</td>
                                    <td><span class="badge bg-success">Active</span></td>
                                    <td>Candidates are shown in alphabetical order to non-admin users</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i> <strong>Administrator Access:</strong> As an administrator, you can still view vote counts and results for all elections, including active ones.
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="elections.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i> Return to Elections
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php 
        require_once 'includes/footer.php';
        exit();
    }
    
    $_SESSION['error'] = "Election ID is required.";
    header("Location: elections.php");
    exit();
}

$electionId = intval($_GET['id']);

// Get election details
$sql = "SELECT * FROM elections WHERE election_id = ?";
$election = fetchOne($sql, [$electionId]);

if (!$election) {
    $_SESSION['error'] = "Election not found.";
    header("Location: elections.php");
    exit();
}

// Check if election is completed and results are published
$resultsPublished = isset($election['results_published']) && $election['results_published'] == 1;
$isCompleted = $election['status'] === 'completed';
$isActive = $election['status'] === 'active';
$showDetailedResults = ($isCompleted && $resultsPublished);

// Allow super admin to see detailed results of any election
$canManageElections = canManageElections();
$canViewDetailedResults = $showDetailedResults || $canManageElections;

// Only super admin can view results during active elections, completed elections are visible to all
$canAccessPage = $isCompleted || ($isActive && $canManageElections) || $canManageElections;

// If not authorized to view the page at all, redirect
if (!$canAccessPage) {
    $_SESSION['error'] = "Results will be available when the election is completed.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Get position details with candidates
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM election_candidates c WHERE c.position_id = p.position_id) AS candidate_count,
        (SELECT COUNT(*) FROM votes v WHERE v.position_id = p.position_id) AS vote_count
        FROM election_positions p
        WHERE p.election_id = ?
        ORDER BY p.title";
$positions = fetchAll($sql, [$electionId]);

// Format dates for display
$startDate = date('F j, Y', strtotime($election['start_date']));
$endDate = date('F j, Y', strtotime($election['end_date']));

// Set page title
$pageTitle = "Election Results - " . $election['title'] . " - SRC Management System";

// Force browser to not use cached versions - MUST be before any output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include header
require_once 'includes/header.php';

// Add mobile fix CSS for candidate cards
echo '<link rel="stylesheet" href="../css/candidate-card-mobile-fix.css">';
echo '<link rel="stylesheet" href="../css/election-mobile-fix.css">';

// Add custom CSS and JS for printing with cache-busting version parameter
$version = time(); // Use current timestamp to prevent caching

// Add force reload script first to ensure it loads before other resources
echo '<script src="../js/election-results-force-reload.js?v=' . $version . '"></script>';

// Add CSS and JS files with cache-busting parameters
echo '<link rel="stylesheet" href="../css/election-results-print.css?v=' . $version . '">';
echo '<link rel="stylesheet" href="../css/election-results-print-override.css?v=' . $version . '">';
echo '<script src="../js/election-results-print.js?v=' . $version . '"></script>';
?>

<!-- Enhanced Print Header (hidden by default, shown only when printing) -->
<div class="print-header">
    <div class="university-logo">
        <i class="fas fa-university"></i>
    </div>
    <h1>Valley View University</h1>
    <h2>Students' Representative Council</h2>
    <h3><?php echo htmlspecialchars($election['title']); ?></h3>
    <div class="election-date">
        <?php echo $startDate; ?> - <?php echo $endDate; ?>
    </div>
</div>

<!-- Hidden message for debugging print/PDF issues -->
<div class="print-debug" style="display: none; visibility: hidden;">
    <p>If you can see this message in print or PDF, the print styles are working correctly.</p>
</div>

<!-- Modern Page Styling -->
<style>
    /* Remove any empty spaces and improve layout */
    body {
        background-color: #f8f9fc;
    }

    /* Ensure proper spacing from header */
    main.container-fluid {
        margin-top: 1rem;
        margin-bottom: 2rem;
    }

    /* Ensure content doesn't get hidden under fixed headers */
    .main-content {
        padding-top: 0;
    }

    /* Voter statistics styling */
    .voter-stats .stat-item {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .voter-stats .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    /* Add subtle pulse animation for numbers */
    @keyframes pulse-number {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .voter-stats .stat-item > div:first-child {
        animation: pulse-number 2s ease-in-out infinite;
    }

    /* Ensure voter stats cards are always independent and side by side */
    .voter-stats .row {
        display: flex !important;
        flex-wrap: nowrap !important;
        margin: 0 -0.5rem;
    }

    .voter-stats .col-6 {
        flex: 1 1 50% !important;
        max-width: 50% !important;
        padding: 0 0.5rem;
        display: flex;
        flex-direction: column;
    }

    .voter-stats .stat-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 120px;
        position: relative;
        isolation: isolate;
    }

    /* Specific styling for each card to ensure independence */
    .voted-card {
        margin-right: 0.5rem;
    }

    .eligible-card {
        margin-left: 0.5rem;
    }

    /* Ensure cards don't overlap */
    .voter-stats .col-6:first-child {
        padding-right: 0.25rem;
    }

    .voter-stats .col-6:last-child {
        padding-left: 0.25rem;
    }

    .card {
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    /* Remove any unwanted margins and padding */
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    /* Ensure no empty spaces */
    .row {
        margin-left: 0;
        margin-right: 0;
    }

    .col-12, .col-md-6 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    /* Proper spacing between voter turnout and position participation */
    .turnout-section, .position-participation-section {
        margin-bottom: 1rem;
    }

    /* Side by side layout with proper spacing */
    @media (min-width: 768px) {
        .turnout-section {
            padding-right: 1rem;
        }
        .position-participation-section {
            padding-left: 1rem;
        }
    }

    /* Ensure equal heights for both sections */
    .turnout-chart-container, .position-participation {
        min-height: 350px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        /* Reduce minimum heights on mobile */
        .turnout-chart-container, .position-participation {
            min-height: auto;
        }

        /* Stack sections vertically on mobile */
        .turnout-section, .position-participation-section {
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-bottom: 1.5rem;
        }

        /* Adjust turnout display for mobile */
        .turnout-percentage {
            font-size: 2.5rem !important;
        }

        .turnout-stats {
            flex-direction: column;
            gap: 1rem;
        }

        .turnout-stat {
            text-align: center;
        }

        /* Improve voter statistics visibility on mobile */
        .voter-stats .stat-item {
            padding: 1.25rem !important;
            margin-bottom: 0.75rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .voter-stats .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
        }

        .voter-stats .stat-item > div:first-child {
            font-size: 2.2rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.5rem;
        }

        .voter-stats .stat-item > div:last-child {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Ensure good spacing between voter stats - maintain side by side on mobile */
        .voter-stats .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            margin: 0 -0.25rem;
        }

        .voter-stats .col-6 {
            flex: 1 1 50% !important;
            max-width: 50% !important;
            padding: 0 0.25rem;
        }

        .voter-stats .stat-item {
            min-height: 100px;
        }

        /* Improve candidate cards on mobile */
        .candidate-result-card {
            margin-bottom: 1rem;
            padding: 15px;
        }

        .candidate-photo {
            width: 60px;
            height: 60px;
            margin-right: 10px;
        }

        .candidate-name {
            font-size: 1rem !important;
        }

        .candidate-votes {
            font-size: 1.5rem !important;
        }

        /* Adjust position bars for mobile */
        .position-bar {
            padding: 0.75rem !important;
        }

        .position-bar .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 0.5rem;
        }

        /* Make progress bars more visible on mobile */
        .progress {
            height: 8px !important;
        }
    }

    @media (max-width: 576px) {
        /* Extra small screens */
        .container-fluid {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .turnout-chart-container, .position-participation {
            padding: 1rem !important;
        }

        .turnout-percentage {
            font-size: 2rem !important;
        }

        /* Enhanced voter statistics for extra small screens */
        .voter-stats .row {
            margin: 0 -0.125rem !important;
        }

        .voter-stats .col-6 {
            padding: 0 0.125rem !important;
        }

        .voter-stats .stat-item {
            padding: 0.75rem !important;
            min-height: 90px;
        }

        .voter-stats .stat-item > div:first-child {
            font-size: 1.6rem !important;
        }

        .voter-stats .stat-item > div:last-child {
            font-size: 0.9rem !important;
        }

        .candidate-result-card {
            padding: 10px;
        }

        .candidate-photo {
            width: 50px;
            height: 50px;
        }

        .candidate-name {
            font-size: 0.9rem !important;
        }

        .candidate-votes {
            font-size: 1.3rem !important;
        }

        /* Improve card headers on mobile */
        .card-header {
            padding: 1rem !important;
        }

        .card-header h5 {
            font-size: 1.1rem !important;
        }

        .card-body {
            padding: 1rem !important;
        }

        /* Make list items more mobile-friendly */
        .list-group-item {
            padding: 0.75rem !important;
            flex-direction: column;
            align-items: flex-start !important;
            gap: 0.25rem;
        }

        .list-group-item .badge {
            align-self: flex-end;
        }

        /* Improve alert spacing on mobile */
        .alert {
            margin-bottom: 1rem;
            padding: 0.75rem;
        }
    }

    /* Landscape mobile phones */
    @media (max-width: 768px) and (orientation: landscape) {
        .turnout-chart-container, .position-participation {
            min-height: 250px;
        }

        .turnout-percentage {
            font-size: 2.2rem !important;
        }
    }
    
    /* Candidate result card styling */
    .candidate-result-card {
        position: relative;
        border-radius: 15px;
        padding: 20px;
        background-color: #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
        overflow: hidden;
    }
    
    .candidate-result-card.winner {
        border: 2px solid var(--success);
        background-color: rgba(40, 167, 69, 0.05);
    }
    
    .candidate-result-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .candidate-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 15px;
        background-color: #f8f9fa;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #e9ecef;
    }
    
    .candidate-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .candidate-photo i {
        font-size: 40px;
        color: #adb5bd;
    }
    
    .winner-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 30px;
        height: 30px;
        background-color: var(--success);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    
    .candidate-info {
        flex: 1;
    }
    
    .candidate-info h5 {
        margin-bottom: 5px;
        font-weight: 600;
        color: #212529;
    }
    
    .candidate-info p {
        margin-bottom: 0;
        font-size: 0.9rem;
    }
    
    .candidate-result-stats {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .votes-count {
        display: flex;
        flex-direction: column;
    }
    
    .votes-count strong {
        font-size: 1.5rem;
        color: #495057;
        line-height: 1;
    }
    
    .votes-count span {
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .votes-percentage strong {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0d6efd;
    }
    
    .candidate-result-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
    }
    
    .winner-label {
        color: var(--success);
        font-weight: 600;
    }

    /* Print-specific styles */
    @media print {
        /* Hide non-printable elements */
        .breadcrumb, .btn, .navbar, .sidebar, footer, .header-actions, 
        #adminActionsDropdown, .dropdown-menu, .no-print {
            display: none !important;
        }
        
        /* Reset page margins and padding */
        body {
            padding: 0 !important;
            margin: 0 !important;
            background-color: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        
        /* Create a custom print header */
        body::before {
            content: '';
            display: block;
            height: 120px;
            background: linear-gradient(135deg, #4e54c8, #8f94fb);
            margin-bottom: 20px;
            position: relative;
        }
        
        /* University and SRC headers */
        body::after {
            content: "Valley View University";
            display: block;
            position: absolute;
            top: 30px;
            left: 0;
            width: 100%;
            text-align: center;
            color: white;
            font-size: 28px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .container-fluid::before {
            content: "Students' Representative Council";
            display: block;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            text-align: center;
            color: white;
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        /* Election title */
        .container-fluid::after {
            content: "<?php echo htmlspecialchars($election['title']); ?>";
            display: block;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin: 30px 0 20px;
            padding-top: 40px;
        }
        
        /* Card styling for print */
        .card {
            break-inside: avoid;
            page-break-inside: avoid;
            border: none !important;
            box-shadow: none !important;
            margin-bottom: 30px !important;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4e73df, #224abe) !important;
            color: white !important;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px !important;
        }
        
        .card-body {
            border: 1px solid #e3e6f0 !important;
            border-top: none !important;
            border-radius: 0 0 10px 10px !important;
            padding: 20px !important;
        }
        
        /* Ensure columns display properly */
        .position-results .row {
            display: flex;
            flex-wrap: wrap;
        }
        
        .position-results .col-md-6 {
            width: 50% !important;
            float: left !important;
            padding: 10px !important;
        }
        
        /* Candidate cards */
        .candidate-result-card {
            border: 1px solid #e3e6f0 !important;
            box-shadow: none !important;
            page-break-inside: avoid !important;
        }
        
        .candidate-result-card.winner {
            border: 2px solid #28a745 !important;
            background-color: rgba(40, 167, 69, 0.05) !important;
        }
        
        .winner-badge {
            background-color: #28a745 !important;
            color: white !important;
            border: 2px solid white !important;
        }
        
        /* Progress bars */
        .progress {
            background-color: #e9ecef !important;
            print-color-adjust: exact !important;
        }
        
        .progress-bar {
            print-color-adjust: exact !important;
        }
        
        .progress-bar.bg-success {
            background-color: #28a745 !important;
        }
        
        .progress-bar.bg-primary {
            background-color: #4e73df !important;
        }
        
        /* Page settings */
        @page {
            size: A4;
            margin: 0.5cm;
        }
        
        /* Add a decorative element at the bottom of each page */
        .container-fluid {
            position: relative;
        }
        
        .container-fluid::after {
            content: "";
            display: block;
            height: 10px;
            background: linear-gradient(90deg, #4e54c8, #8f94fb);
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
        }
        
        /* Summary box styling */
        .list-group-item {
            border-color: #e3e6f0 !important;
        }
        
        .badge {
            print-color-adjust: exact !important;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
            color: white !important;
        }
        
        .badge.bg-primary {
            background-color: #4e73df !important;
            color: white !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
            color: white !important;
        }
    }

    .progress {
        border-radius: 100px;
        overflow: hidden;
    }

    /* Added for the simple candidate cards during active voting */
    .candidate-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        height: 100%;
        transition: all 0.3s;
        position: relative;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        background-color: #fff;
    }

    .candidate-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .candidate-card .candidate-header {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .candidate-card .candidate-photo {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 15px;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .candidate-card .candidate-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .candidate-card .no-photo {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #e9ecef;
        color: #6c757d;
        font-size: 24px;
    }

    .candidate-card .candidate-info h5 {
        margin-bottom: 5px;
        font-weight: 600;
    }

    .candidate-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Total votes info box during active voting */
    .total-votes-info {
        background-color: #f8f9fc;
        border-left: 4px solid #4e73df;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .total-votes-info .count {
        font-size: 24px;
        font-weight: 700;
        color: #4e73df;
        margin-right: 10px;
    }

    .total-votes-info .label {
        font-size: 16px;
        color: #5a5c69;
    }
</style>

<?php
// Set variables for the modern page header
$pageTitle = 'Election Results';
$pageIcon = 'fa-chart-bar';
$pageDescription = htmlspecialchars($election['title']) . ' • ' . $startDate . ' - ' . $endDate;

// Prepare actions for the modern page header
$actions = [];

// Back button
$actions[] = [
    'url' => 'election_detail.php?id=' . $electionId,
    'icon' => 'fa-arrow-left',
    'text' => 'Back',
    'class' => 'btn-outline-light'
];

// Super admin actions
if ($canManageElections) {
    $actions[] = [
        'url' => '#',
        'icon' => 'fa-print',
        'text' => 'Print Results',
        'class' => 'btn-outline-light',
        'id' => 'print-results'
    ];

    $actions[] = [
        'url' => '#',
        'icon' => 'fa-file-pdf',
        'text' => 'Export PDF',
        'class' => 'btn-outline-light',
        'id' => 'export-pdf'
    ];

    $actions[] = [
        'url' => '#',
        'icon' => 'fa-file-csv',
        'text' => 'Export CSV',
        'class' => 'btn-outline-light',
        'id' => 'export-csv'
    ];
}

// Include the modern page header
include 'includes/modern_page_header.php';
?>

<!-- Main Content -->
<main class="container-fluid px-4" style="margin-top: 1rem; margin-bottom: 2rem;">
    <div class="row justify-content-center">
        <div class="col-12">
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

            <!-- Results Overview Card -->
            <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem;">
                    <h5 class="card-title mb-0 text-center" style="font-size: 1.3rem; font-weight: 600;">
                        <i class="fas fa-chart-bar me-2"></i>
                        Results Overview
                    </h5>
                </div>
                <div class="card-body" style="padding: 2rem;">
                <?php if (!$canViewDetailedResults): ?>
                    <div class="alert alert-info text-center" style="border-radius: 10px; border: none; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">
                        <i class="fas fa-info-circle me-2"></i>
                        Full results will be available once the election is completed. Current status: <strong><?php echo ucfirst($election['status']); ?></strong>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-section">
                                <h5 class="text-center mb-4" style="color: #667eea; font-weight: 600;">
                                    <i class="fas fa-info-circle me-2"></i>Election Information
                                </h5>
                                <ul class="list-group list-group-flush" style="border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08);"
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Status:</strong>
                                <span class="badge bg-<?php 
                                    echo $election['status'] === 'completed' ? 'success' : 
                                        ($election['status'] === 'active' ? 'primary' : 
                                            ($election['status'] === 'upcoming' ? 'warning' : 'secondary')); 
                                ?>">
                                    <?php echo ucfirst($election['status']); ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Start Date:</strong>
                                <span><?php echo $startDate; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>End Date:</strong>
                                <span><?php echo $endDate; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Total Positions:</strong>
                                <span><?php echo count($positions); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Total Candidates:</strong>
                                <span>
                                    <?php 
                                    $totalCandidates = 0;
                                    foreach ($positions as $position) {
                                        $totalCandidates += $position['candidate_count'];
                                    }
                                    echo $totalCandidates;
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <strong>Total Votes Cast:</strong>
                                <span>
                                    <?php 
                                    $totalVotes = 0;
                                    foreach ($positions as $position) {
                                        $totalVotes += $position['vote_count'];
                                    }
                                    echo $totalVotes;
                                    ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Voter Turnout and Position Participation Side by Side -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="turnout-section">
                            <h5 class="text-center mb-4" style="color: #667eea; font-weight: 600;">
                                <i class="fas fa-chart-pie me-2"></i>Voter Turnout
                            </h5>
                                <?php
                                // Get total eligible voters (all users)
                                $sql = "SELECT COUNT(*) as total_users FROM users";
                                $result = fetchOne($sql);
                                $totalUsers = $result ? $result['total_users'] : 0;

                                // Calculate unique voters
                                $sql = "SELECT COUNT(DISTINCT voter_id) as unique_voters FROM votes WHERE election_id = ?";
                                $result = fetchOne($sql, [$electionId]);
                                $uniqueVoters = $result ? $result['unique_voters'] : 0;

                                // Calculate voter turnout percentage
                                $turnoutPercentage = $totalUsers > 0 ? ($uniqueVoters / $totalUsers) * 100 : 0;
                                ?>
                                <div class="turnout-chart-container text-center mb-4" style="background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%); padding: 2rem; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: 1px solid #e8f5e8;">
                                    <!-- Main Turnout Display -->
                                    <div class="turnout-main mb-4">
                                        <div class="turnout-percentage" style="font-size: 3rem; font-weight: 700; color: #28a745; margin-bottom: 0.5rem;">
                                            <?php echo round($turnoutPercentage, 1); ?>%
                                        </div>
                                        <div class="turnout-label" style="font-size: 1.1rem; color: #6c757d; margin-bottom: 1rem;">
                                            Voter Turnout
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="progress mb-3" style="height: 20px; border-radius: 10px; background-color: #e9ecef;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: <?php echo $turnoutPercentage; ?>%; background: linear-gradient(90deg, #28a745 0%, #20c997 100%); border-radius: 10px;"
                                             aria-valuenow="<?php echo $turnoutPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>

                                    <!-- Voter Statistics -->
                                    <div class="voter-stats mt-4">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="stat-item voted-card" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(40, 167, 69, 0.05) 100%); padding: 1.5rem; border-radius: 12px; border: 2px solid rgba(40, 167, 69, 0.2); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1); width: 100%; box-sizing: border-box;">
                                                    <div style="font-size: 2rem; font-weight: 700; color: #28a745; margin-bottom: 0.5rem;">
                                                        <?php echo $uniqueVoters; ?>
                                                    </div>
                                                    <div style="font-size: 1rem; color: #28a745; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Voted
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item eligible-card" style="background: linear-gradient(135deg, rgba(108, 117, 125, 0.15) 0%, rgba(108, 117, 125, 0.05) 100%); padding: 1.5rem; border-radius: 12px; border: 2px solid rgba(108, 117, 125, 0.2); box-shadow: 0 4px 15px rgba(108, 117, 125, 0.1); width: 100%; box-sizing: border-box;">
                                                    <div style="font-size: 2rem; font-weight: 700; color: #495057; margin-bottom: 0.5rem;">
                                                        <?php echo $totalUsers; ?>
                                                    </div>
                                                    <div style="font-size: 1rem; color: #495057; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                                        Eligible
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Position Participation - Now in separate column -->
                        <div class="col-md-6">
                            <div class="position-participation-section">
                                <h5 class="text-center mb-4" style="color: #667eea; font-weight: 600;">
                                    <i class="fas fa-chart-line me-2"></i>Participation by Position
                                </h5>
                                <div class="position-participation" style="background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%); padding: 1.5rem; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); border: 1px solid #e9ecef; min-height: 400px;">
                                    <div class="position-chart-container">
                                        <?php foreach ($positions as $position): ?>
                                            <?php $positionPercentage = $totalUsers > 0 ? ($position['vote_count'] / $totalUsers) * 100 : 0; ?>
                                            <div class="position-bar mb-3" style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span style="font-weight: 600; color: #495057; font-size: 0.95rem;"><?php echo htmlspecialchars($position['title']); ?></span>
                                                    <div>
                                                        <span class="badge bg-primary" style="margin-right: 5px; font-size: 0.8rem;"><?php echo $position['vote_count']; ?> votes</span>
                                                        <span class="badge bg-secondary" style="font-size: 0.8rem;"><?php echo round($positionPercentage, 1); ?>%</span>
                                                    </div>
                                                </div>
                                                <div class="progress" style="height: 12px; border-radius: 6px; background-color: #e9ecef;">
                                                    <div class="progress-bar" role="progressbar"
                                                         style="width: <?php echo $positionPercentage; ?>%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); border-radius: 6px;"
                                                         aria-valuenow="<?php echo $positionPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
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
            <?php endif; ?>
        </div>
    </div>

            <!-- Position Results -->
            <?php foreach ($positions as $position): ?>
                <div class="card shadow-lg border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-grow-1">
                                <h5 class="card-title mb-2" style="font-size: 1.4rem; font-weight: 600;">
                                    <i class="fas fa-users-cog me-2"></i>
                                    <?php echo htmlspecialchars($position['title']); ?>
                                </h5>
                                <div>
                                    <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.9rem; padding: 8px 15px; margin-right: 10px;">
                                        <i class="fas fa-chair me-1"></i><?php echo $position['seats']; ?> seat(s)
                                    </span>
                                    <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 0.9rem; padding: 8px 15px;">
                                        <i class="fas fa-vote-yea me-1"></i><?php echo $position['vote_count']; ?> vote(s)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 2rem;">
                <?php
                // Get candidates for this position
                $sql = "SELECT c.*, u.first_name, u.last_name, u.email
                        FROM election_candidates c
                        JOIN users u ON c.user_id = u.user_id
                        WHERE c.position_id = ? AND c.status = 'approved'
                        ORDER BY c.votes DESC, u.first_name, u.last_name";
                $candidates = fetchAll($sql, [$position['position_id']]);
                ?>

                <?php if (empty($candidates)): ?>
                    <p class="text-muted text-center">No approved candidates for this position.</p>
                <?php else: ?>
                    <?php if (!$canViewDetailedResults): ?>
                        <?php if ($isActive): ?>
                            <!-- During active elections, show candidates without vote counts -->
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Voting is in progress.</strong> Individual vote counts will be available when the election is completed.
                            </div>
                            
                            <!-- Total votes cast for this position during active voting -->
                            <div class="total-votes-info mb-4">
                                <div class="d-flex align-items-center">
                                    <span class="count"><?php echo $position['vote_count']; ?></span>
                                    <span class="label">total votes cast for this position</span>
                                </div>
                                <div class="progress mt-2" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: 100%;" 
                                         aria-valuenow="100" 
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <div class="position-results">
                                <div class="row">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="candidate-card">
                                                <div class="candidate-header">
                                                    <div class="candidate-photo">
                                                        <?php if (!empty($candidate['candidate_photo'])): ?>
                                                            <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>">
                                                        <?php else: ?>
                                                            <div class="no-photo">
                                                                <i class="fas fa-user"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="candidate-info">
                                                        <h5><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h5>
                                                    </div>
                                                </div>
                                                <div class="candidate-footer mt-3">
                                                    <a href="candidate_detail.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        View Profile
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                        <p class="text-center">Results will be available when the election is completed.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php
                        // Get total votes for this position to calculate percentages
                        $totalPositionVotes = $position['vote_count'];
                        
                        // Determine winners based on number of seats
                        $seats = $position['seats'];
                        $winners = array_slice($candidates, 0, $seats);
                        ?>
                        
                        <div class="position-results">
                            <div class="row">
                                <?php foreach ($candidates as $index => $candidate): ?>
                                    <?php 
                                    $isWinner = $index < $seats;
                                    $votePercentage = $totalPositionVotes > 0 ? ($candidate['votes'] / $totalPositionVotes) * 100 : 0;
                                    ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="candidate-result-card <?php echo $isWinner ? 'winner' : ''; ?>">
                                            <div class="candidate-result-header">
                                                <div class="candidate-photo">
                                                    <?php if (!empty($candidate['candidate_photo'])): ?>
                                                        <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle"></i>
                                                    <?php endif; ?>
                                                    <?php if ($isWinner): ?>
                                                        <div class="winner-badge">
                                                            <i class="fas fa-trophy"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="candidate-info">
                                                    <h5><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h5>
                                                    <p class="text-muted"><?php echo htmlspecialchars($candidate['email']); ?></p>
                                                </div>
                                            </div>
                                            <div class="candidate-result-stats">
                                                <div class="votes-count">
                                                    <strong><?php echo $candidate['votes']; ?></strong>
                                                    <span>votes</span>
                                                </div>
                                                <div class="votes-percentage">
                                                    <strong><?php echo round($votePercentage, 1); ?>%</strong>
                                                </div>
                                            </div>
                                            <div class="progress mb-3" style="height: 10px;">
                                                <div class="progress-bar <?php echo $isWinner ? 'bg-success' : 'bg-primary'; ?>" role="progressbar" 
                                                     style="width: <?php echo $votePercentage; ?>%;" 
                                                     aria-valuenow="<?php echo $votePercentage; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="candidate-result-footer">
                                                <a href="candidate_detail.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View Profile
                                                </a>
                                                <?php if ($isWinner): ?>
                                                    <span class="winner-label">
                                                        <i class="fas fa-check-circle me-1"></i> Elected
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// All functionality moved to external JS file (election-results-print.js)
// This comment is kept to maintain the script tag for any other inline scripts that might be needed
            
// All functionality moved to external JS file (election-results-print.js)
</script>

<script>
// Copy link functionality - keeping this separate from the main JS file
document.addEventListener('DOMContentLoaded', function() {
    const copyLinkButton = document.getElementById('copy-link');
    if (copyLinkButton) {
        copyLinkButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const currentUrl = window.location.href;
            
            // Create temporary input element to copy URL
            const tempInput = document.createElement('input');
            tempInput.value = currentUrl;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Show feedback
            alert('Link copied to clipboard!');
        });
    }
});
</script>

        </div>
    </div>
</main>

<?php
// Include footer - will be hidden in print view
require_once 'includes/footer.php';
?>
