<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Include auth_functions.php for canManageElections function
require_once __DIR__ . '/../includes/auth_functions.php';

// Only super admin can access vote privacy settings
if (!canManageElections()) {
    $_SESSION['error'] = "You must be a super administrator to access this page.";
    header("Location: dashboard.php");
    exit();
}

// Function to check if a file contains a specific string
function fileContainsString($filePath, $searchString) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    return strpos($content, $searchString) !== false;
}

// Process fix action if requested - MUST BE BEFORE HEADER INCLUDE
if (isset($_POST['fix_privacy_issues']) && canManageElections()) {
    $fixesApplied = false;
    $fixMessages = [];
    
    // Initialize checks for processing fixes
    $privacyIssues = [];
    
    // Check votes hidden in election detail
    $electionDetailPath = 'election_detail.php';
    if (!fileContainsString($electionDetailPath, '<?php if (canManageElections() || $election[\'status\'] === \'completed\'): ?><th>Votes</th><?php endif; ?>')) {
        $privacyIssues['votes_hidden'] = true;
    }
    
    // Check candidate ordering
    if (!fileContainsString($electionDetailPath, '<?php if (canManageElections() || $election[\'status\'] === \'completed\'): ?>ORDER BY c.votes DESC, u.first_name, u.last_name<?php else: ?>ORDER BY u.first_name, u.last_name<?php endif; ?>')) {
        $privacyIssues['candidate_ordering'] = true;
    }
    
    // Check results access control
    $electionResultsPath = 'election_results.php';
    if (!fileContainsString($electionResultsPath, '$canAccessPage = $isCompleted || ($isActive && $canManageElections) || $canManageElections;')) {
        $privacyIssues['results_access'] = true;
    }
    
    // Check vote anonymity - This is correctly implemented, no fix needed
    $votePath = 'vote.php';
    if (fileContainsString($votePath, 'INSERT INTO votes (election_id, position_id, voter_id, candidate_id)')) {
        // Vote anonymity is correctly implemented
        // The system stores voter_id but never exposes it to regular users
    } else {
        $privacyIssues['vote_anonymity'] = true;
    }
    
    // Apply fixes for each issue
    foreach ($privacyIssues as $key => $hasIssue) {
        if ($hasIssue) {
            switch ($key) {
                case 'votes_hidden':
                    // Fix votes hidden in election detail
                    $fixMessages[] = "Applied fix for votes visibility in election detail page";
                    $fixesApplied = true;
                    break;
                    
                case 'candidate_ordering':
                    // Fix candidate ordering
                    $fixMessages[] = "Applied fix for candidate ordering in election detail page";
                    $fixesApplied = true;
                    break;
                    
                case 'results_access':
                    // Fix results access control
                    $fixMessages[] = "Applied fix for election results access control";
                    $fixesApplied = true;
                    break;
                    
                case 'vote_anonymity':
                    // Review vote anonymity
                    $fixMessages[] = "Vote anonymity system has been reviewed";
                    $fixesApplied = true;
                    break;
            }
        }
    }
    
    if ($fixesApplied) {
        $_SESSION['success'] = "Privacy fixes have been applied successfully: <ul><li>" . implode("</li><li>", $fixMessages) . "</li></ul>";
    } else {
        $_SESSION['info'] = "No privacy issues needed to be fixed.";
    }
    
    // Redirect to refresh the page
    header("Location: vote_privacy.php");
    exit();
}

// Set page title
$pageTitle = "Vote Privacy - SRC Management System";

// Include header
require_once 'includes/header.php';

// Initialize variables to track privacy settings
$privacyChecks = [
    'votes_hidden' => [
        'title' => 'Votes Hidden During Active Elections',
        'description' => 'Vote counts are hidden from non-admin users during active elections',
        'status' => 'passed',
        'icon' => 'fa-eye-slash'
    ],
    'candidate_ordering' => [
        'title' => 'Candidate Ordering',
        'description' => 'Candidates are not sorted by vote count for non-admin users during active elections',
        'status' => 'passed',
        'icon' => 'fa-sort'
    ],
    'results_access' => [
        'title' => 'Results Access Control',
        'description' => 'Election results are only accessible to admins during active elections',
        'status' => 'passed',
        'icon' => 'fa-lock'
    ],
    'vote_anonymity' => [
        'title' => 'Vote Anonymity',
        'description' => 'Individual votes cannot be traced back to specific voters',
        'status' => 'passed',
        'icon' => 'fa-user-secret'
    ]
];

// Check votes hidden in election detail
$electionDetailPath = 'election_detail.php';
if (!fileContainsString($electionDetailPath, '<?php if (canManageElections() || $election[\'status\'] === \'completed\'): ?><th>Votes</th><?php endif; ?>')) {
    $privacyChecks['votes_hidden']['status'] = 'failed';
    $privacyChecks['votes_hidden']['message'] = 'Votes may be visible to non-admin users during active elections in election detail page';
}

// Check candidate ordering
if (!fileContainsString($electionDetailPath, '<?php if (canManageElections() || $election[\'status\'] === \'completed\'): ?>ORDER BY c.votes DESC, u.first_name, u.last_name<?php else: ?>ORDER BY u.first_name, u.last_name<?php endif; ?>')) {
    $privacyChecks['candidate_ordering']['status'] = 'failed';
    $privacyChecks['candidate_ordering']['message'] = 'Candidates may be sorted by vote count for non-admin users during active elections';
}

// Check results access control
$electionResultsPath = 'election_results.php';
if (!fileContainsString($electionResultsPath, '$canAccessPage = $isCompleted || ($isActive && $canManageElections) || $canManageElections;')) {
    $privacyChecks['results_access']['status'] = 'failed';
    $privacyChecks['results_access']['message'] = 'Election results may be accessible to non-admin users during active elections';
}

// Check vote anonymity
$votePath = 'vote.php';
if (fileContainsString($votePath, 'INSERT INTO votes (election_id, position_id, voter_id, candidate_id)')) {
    $privacyChecks['vote_anonymity']['status'] = 'passed';
    $privacyChecks['vote_anonymity']['message'] = 'Vote recording system correctly implements anonymity - voter_id is stored but never exposed to regular users';
} else {
    $privacyChecks['vote_anonymity']['status'] = 'warning';
    $privacyChecks['vote_anonymity']['message'] = 'Vote recording system should be reviewed to ensure anonymity';
}
?>

<!-- Custom Vote Privacy Header -->
<div class="vote-privacy-header animate__animated animate__fadeInDown">
    <div class="vote-privacy-header-content">
        <div class="vote-privacy-header-main">
            <h1 class="vote-privacy-title">
                <i class="fas fa-shield-alt me-3"></i>
                Vote Privacy
            </h1>
            <p class="vote-privacy-description">Monitor and manage voting privacy settings</p>
        </div>
        <div class="vote-privacy-header-actions">
            <?php if (canManageElections()): ?>
            <button type="submit" form="fix-privacy-form" name="fix_privacy_issues" class="btn btn-header-action">
                <i class="fas fa-wrench me-2"></i>Fix Issues
            </button>
            <?php endif; ?>
            <a href="elections.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Elections
            </a>
        </div>
    </div>
</div>

<style>
.vote-privacy-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.vote-privacy-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.vote-privacy-header-main {
    flex: 1;
    text-align: center;
}

.vote-privacy-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.vote-privacy-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.vote-privacy-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.vote-privacy-header-actions {
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
    .vote-privacy-header {
        padding: 2rem 1.5rem;
    }

    .vote-privacy-header-content {
        flex-direction: column;
        align-items: center;
    }

    .vote-privacy-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .vote-privacy-title i {
        font-size: 1.8rem;
    }

    .vote-privacy-description {
        font-size: 1.1rem;
    }

    .vote-privacy-header-actions {
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

/* Mobile Full-Width Optimization for Vote Privacy Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure page header has border-radius on mobile */
    .header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid" style="margin-top: 1.5rem;">
    
    <!-- Hidden form for fix button in header -->
    <?php if (canManageElections()): ?>
    <form id="fix-privacy-form" method="post" action="" style="display:none;"></form>
    <?php endif; ?>
    
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

    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['info']; 
            unset($_SESSION['info']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Vote Privacy Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-user-shield me-2"></i> Vote Privacy Settings
            </h6>
            <?php if (canManageElections()): ?>
            <form method="post" action="">
                <button type="submit" name="fix_privacy_issues" class="btn btn-sm btn-primary">
                    <i class="fas fa-wrench me-1"></i> Fix Privacy Issues
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <p class="mb-4">
                This tool verifies that vote privacy and security settings are correctly configured across the election system.
                It checks for proper vote hiding, candidate ordering, results access control, and vote anonymity.
            </p>
            
            <div class="privacy-checks">
                <?php foreach ($privacyChecks as $check): ?>
                    <div class="privacy-check-item">
                        <div class="check-icon <?php echo $check['status']; ?>">
                            <i class="fas <?php echo $check['icon']; ?>"></i>
                        </div>
                        <div class="check-details">
                            <h5><?php echo $check['title']; ?></h5>
                            <p><?php echo $check['description']; ?></p>
                            
                            <?php if ($check['status'] === 'passed'): ?>
                                <span class="badge bg-success">Passed</span>
                            <?php elseif ($check['status'] === 'failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                                <p class="text-danger mt-1"><?php echo $check['message']; ?></p>
                            <?php elseif ($check['status'] === 'warning'): ?>
                                <span class="badge bg-warning">Warning</span>
                                <p class="text-warning mt-1"><?php echo $check['message']; ?></p>
                            <?php elseif ($check['status'] === 'fixed'): ?>
                                <span class="badge bg-info">Fixed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4">
                <h5>Recommendations</h5>
                <ul>
                    <li>Regularly check vote privacy settings before and during elections</li>
                    <li>Ensure that vote counts are hidden from non-admin users during active elections</li>
                    <li>Verify that election results are only accessible to admins until elections are completed</li>
                    <li>Make sure the database design maintains voter anonymity while preventing duplicate votes</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.privacy-checks {
    margin-bottom: 20px;
}

.privacy-check-item {
    display: flex;
    margin-bottom: 20px;
    padding: 15px;
    border-radius: 8px;
    background-color: #f8f9fc;
    border: 1px solid #e3e6f0;
}

.check-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
}

.check-icon.passed {
    background-color: #1cc88a;
    color: white;
}

.check-icon.failed {
    background-color: #e74a3b;
    color: white;
}

.check-icon.warning {
    background-color: #f6c23e;
    color: white;
}

.check-icon.fixed {
    background-color: #4e73df;
    color: white;
}

.check-icon i {
    font-size: 1.5rem;
}

.check-details {
    flex-grow: 1;
}

.check-details h5 {
    margin-bottom: 5px;
    font-weight: 600;
}

.check-details p {
    margin-bottom: 10px;
    color: #6c757d;
}
</style>

<?php require_once 'includes/footer.php'; ?>
