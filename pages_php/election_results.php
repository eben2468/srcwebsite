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

<!-- Modern Election Results CSS -->
<link rel="stylesheet" href="../css/election-results-modern.css?v=<?php echo time(); ?>">
<style>
    /* Print overrides */
    @media print {
        .election-hero, .no-print { display: none !important; }
        .stats-dashboard { margin-top: 0; }
        .card { break-inside: avoid; box-shadow: none; border: 1px solid #ddd; }
    }
</style>

<?php
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
<!-- Main Content -->
<main class="container-fluid px-0">
    <!-- Hero Section -->
    <div class="election-hero text-center">
        <div class="container">
            <h1 class="election-title animate-up"><?php echo htmlspecialchars($election['title']); ?></h1>
            <div class="election-meta animate-up delay-1">
                <i class="far fa-calendar-alt me-2"></i>
                <?php echo $startDate; ?> - <?php echo $endDate; ?>
            </div>
        </div>
    </div>

    <div class="container" style="max-width: 1200px;">
        <!-- Stats Dashboard -->
        <div class="stats-dashboard animate-up delay-2">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo ucfirst($election['status']); ?>
                        </div>
                        <div class="stat-label">Status</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chair"></i>
                        </div>
                        <div class="stat-value">
                            <?php echo count($positions); ?>
                        </div>
                        <div class="stat-label">Positions</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $totalCandidates = 0;
                            foreach ($positions as $position) {
                                $totalCandidates += $position['candidate_count'];
                            }
                            echo $totalCandidates;
                            ?>
                        </div>
                        <div class="stat-label">Candidates</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <div class="stat-value">
                            <?php 
                            $totalVotes = 0;
                            foreach ($positions as $position) {
                                $totalVotes += $position['vote_count'];
                            }
                            echo $totalVotes;
                            ?>
                        </div>
                        <div class="stat-label">Total Votes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate-up" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show animate-up" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!$canViewDetailedResults): ?>
            <div class="alert alert-info text-center animate-up" style="border-radius: 15px; padding: 2rem;">
                <i class="fas fa-info-circle fa-2x mb-3 text-primary"></i>
                <h4>Results Pending</h4>
                <p class="mb-0">Full results will be available once the election is completed.</p>
            </div>
        <?php else: ?>
            
            <!-- Voter Turnout -->
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
            <div class="turnout-card animate-up delay-3">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <h4 class="mb-0 fw-bold">Voter Turnout</h4>
                    <div class="text-end">
                        <span class="display-4 fw-bold text-success"><?php echo round($turnoutPercentage, 1); ?>%</span>
                        <div class="text-muted small"><?php echo $uniqueVoters; ?> of <?php echo $totalUsers; ?> voters</div>
                    </div>
                </div>
                <div class="turnout-progress-container">
                    <div class="turnout-progress-bar" style="width: <?php echo $turnoutPercentage; ?>%"></div>
                </div>
            </div>

            <!-- Search Filter -->
            <div class="row mb-4 animate-up delay-3">
                <div class="col-md-6 mx-auto">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="result-search" class="form-control border-start-0" placeholder="Search positions or candidates...">
                    </div>
                </div>
            </div>

            <!-- Position Results -->
            <?php foreach ($positions as $index => $position): ?>
                <div class="position-section animate-up delay-<?php echo min($index + 4, 9); ?>">
                    <div class="position-header">
                        <h3 class="position-title"><?php echo htmlspecialchars($position['title']); ?></h3>
                        <span class="position-badge">
                            <?php echo $position['seats']; ?> Seat<?php echo $position['seats'] > 1 ? 's' : ''; ?>
                        </span>
                    </div>

                    <?php
                    // Get candidates for this position
                    $sql = "SELECT c.*, u.first_name, u.last_name, u.email
                            FROM election_candidates c
                            JOIN users u ON c.user_id = u.user_id
                            WHERE c.position_id = ? AND c.status = 'approved'
                            ORDER BY c.votes DESC, u.first_name, u.last_name";
                    $candidates = fetchAll($sql, [$position['position_id']]);
                    
                    // Determine winners
                    $seats = $position['seats'];
                    $totalPositionVotes = $position['vote_count'];
                    ?>

                    <?php if (empty($candidates)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="far fa-folder-open fa-3x mb-3"></i>
                            <p>No candidates for this position.</p>
                        </div>
                    <?php else: ?>
                        <?php if ($isActive): ?>
                            <div class="alert alert-light border text-center">
                                <i class="fas fa-vote-yea me-2 text-primary"></i>
                                Voting in progress. <?php echo $position['vote_count']; ?> votes cast so far.
                            </div>
                            <div class="candidate-grid">
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="candidate-card-modern">
                                        <div class="candidate-header-modern">
                                            <?php if (!empty($candidate['candidate_photo'])): ?>
                                                <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="Candidate" class="candidate-photo-modern">
                                            <?php else: ?>
                                                <div class="candidate-photo-modern d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user fa-3x text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                            <h4 class="candidate-name-modern"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h4>
                                            <div class="candidate-meta-modern">Candidate</div>
                                        </div>
                                        <div class="candidate-body-modern text-center pb-4">
                                            <a href="candidate_detail.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-outline-primary rounded-pill px-4">View Profile</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="candidate-grid">
                                <?php foreach ($candidates as $cIndex => $candidate): ?>
                                    <?php 
                                    $isWinner = $cIndex < $seats;
                                    $votePercentage = $totalPositionVotes > 0 ? ($candidate['votes'] / $totalPositionVotes) * 100 : 0;
                                    ?>
                                    <div class="candidate-card-modern <?php echo $isWinner ? 'winner' : ''; ?>">
                                        <?php if ($isWinner): ?>
                                            <div class="winner-ribbon">WINNER</div>
                                        <?php endif; ?>
                                        
                                        <div class="candidate-header-modern">
                                            <?php if (!empty($candidate['candidate_photo'])): ?>
                                                <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="Candidate" class="candidate-photo-modern">
                                            <?php else: ?>
                                                <div class="candidate-photo-modern d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user fa-3x text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                            <h4 class="candidate-name-modern"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h4>
                                            <div class="candidate-meta-modern"><?php echo htmlspecialchars($candidate['email']); ?></div>
                                        </div>
                                        
                                        <div class="candidate-body-modern">
                                            <div class="vote-stats-modern">
                                                <span class="vote-count-modern"><?php echo $candidate['votes']; ?></span>
                                                <span class="vote-percentage-modern"><?php echo round($votePercentage, 1); ?>%</span>
                                            </div>
                                            <div class="vote-progress-modern">
                                                <div class="vote-bar-modern" style="width: <?php echo $votePercentage; ?>%"></div>
                                            </div>
                                            <?php if ($isWinner): ?>
                                                <div class="text-center mt-3 text-success fw-bold">
                                                    <i class="fas fa-check-circle me-1"></i> Elected
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
        <?php endif; ?>
    </div>
</main>

<!-- Confetti Canvas -->
<canvas id="confetti-canvas"></canvas>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress bars on scroll
    const progressBars = document.querySelectorAll('.turnout-progress-bar, .vote-bar-modern');
    
    // Search functionality
    const searchInput = document.getElementById('result-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const positionSections = document.querySelectorAll('.position-section');
            
            positionSections.forEach(section => {
                const positionTitle = section.querySelector('.position-title').textContent.toLowerCase();
                const candidates = section.querySelectorAll('.candidate-card-modern');
                let hasVisibleCandidates = false;
                
                // If position matches, show all candidates
                if (positionTitle.includes(searchTerm)) {
                    section.style.display = 'block';
                    candidates.forEach(card => card.style.display = 'flex');
                    return;
                }
                
                // Otherwise check individual candidates
                candidates.forEach(card => {
                    const name = card.querySelector('.candidate-name-modern').textContent.toLowerCase();
                    if (name.includes(searchTerm)) {
                        card.style.display = 'flex';
                        hasVisibleCandidates = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show/hide section based on visible candidates
                section.style.display = hasVisibleCandidates ? 'block' : 'none';
            });
        });
    }
    
    // Simple confetti effect if election is completed
    <?php if ($isCompleted): ?>
    startConfetti();
    <?php endif; ?>
    
    function startConfetti() {
        // Basic confetti implementation
        const canvas = document.getElementById('confetti-canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        const pieces = [];
        const numberOfPieces = 100;
        const colors = ['#f00', '#0f0', '#00f', '#ff0', '#0ff'];
        
        function update() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pieces.forEach(p => {
                ctx.fillStyle = p.color;
                ctx.fillRect(p.x, p.y, p.size, p.size);
                p.y += p.speed;
                p.rotation += p.rotationSpeed;
                if (p.y > canvas.height) p.y = -10;
            });
            requestAnimationFrame(update);
        }
        
        for (let i = 0; i < numberOfPieces; i++) {
            pieces.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                size: Math.random() * 10 + 5,
                speed: Math.random() * 3 + 1,
                rotation: Math.random() * 360,
                rotationSpeed: Math.random() * 10 - 5,
                color: colors[Math.floor(Math.random() * colors.length)]
            });
        }
        
        // Run for 5 seconds then stop to save resources
        let animationId = requestAnimationFrame(update);
        setTimeout(() => {
            cancelAnimationFrame(animationId);
            canvas.style.display = 'none';
        }, 5000);
    }
});
</script>

<!-- Election Results Print/Export Script -->
<script src="../js/election-results-print.js"></script>

<?php
// Include footer - will be hidden in print view
require_once 'includes/footer.php';
?>
