<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if election ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
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

// Check if election is completed (only completed elections should show full results)
$showResults = ($election['status'] === 'completed');
// Allow admins to see results of active elections too
$isAdmin = isAdmin();
$canViewResults = $showResults || $isAdmin;

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

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
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

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Election Results</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
                    <li class="breadcrumb-item"><a href="election_detail.php?id=<?php echo $electionId; ?>"><?php echo htmlspecialchars($election['title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Results</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Election
            </a>
            <?php if ($isAdmin): ?>
                <a href="#" class="btn btn-outline-secondary" id="print-results">
                    <i class="fas fa-print me-2"></i> Print Results
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Results Overview Card -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Results Overview: <?php echo htmlspecialchars($election['title']); ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (!$canViewResults): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Full results will be available once the election is completed. Current status: <strong><?php echo ucfirst($election['status']); ?></strong>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Election Information</h5>
                        <ul class="list-group list-group-flush mb-4">
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
                    <div class="col-md-6">
                        <h5>Voter Turnout</h5>
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
                        <div class="turnout-chart-container text-center mb-3">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $turnoutPercentage; ?>%;" 
                                     aria-valuenow="<?php echo $turnoutPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo round($turnoutPercentage, 1); ?>%
                                </div>
                            </div>
                            <p class="mt-2"><?php echo $uniqueVoters; ?> out of <?php echo $totalUsers; ?> eligible voters participated</p>
                        </div>
                        
                        <div class="position-participation">
                            <h6>Participation by Position</h6>
                            <div class="position-chart-container">
                                <?php foreach ($positions as $position): ?>
                                    <div class="position-bar mb-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small><?php echo htmlspecialchars($position['title']); ?></small>
                                            <small><?php echo $position['vote_count']; ?> votes</small>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $totalUsers > 0 ? ($position['vote_count'] / $totalUsers) * 100 : 0; ?>%;" 
                                                 aria-valuenow="<?php echo $totalUsers > 0 ? ($position['vote_count'] / $totalUsers) * 100 : 0; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Position Results -->
    <?php foreach ($positions as $position): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <?php echo htmlspecialchars($position['title']); ?>
                    <span class="badge bg-info ms-2"><?php echo $position['seats']; ?> seat(s)</span>
                    <span class="badge bg-secondary ms-2"><?php echo $position['vote_count']; ?> vote(s)</span>
                </h5>
            </div>
            <div class="card-body">
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
                    <?php if (!$canViewResults): ?>
                        <p class="text-center">Results will be available when the election is completed.</p>
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

<style>
.candidate-result-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s;
    position: relative;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.candidate-result-card.winner {
    border-color: var(--success);
    background-color: rgba(var(--success-rgb), 0.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.candidate-result-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.candidate-photo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
    flex-shrink: 0;
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.candidate-photo i {
    font-size: 40px;
    color: #ccc;
}

.winner-badge {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background-color: var(--success);
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.candidate-info h5 {
    margin-bottom: 5px;
    font-weight: 600;
}

.candidate-result-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.votes-count, .votes-percentage {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.votes-count strong, .votes-percentage strong {
    font-size: 1.2rem;
}

.votes-count span {
    font-size: 0.8rem;
    color: #6c757d;
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

@media print {
    .breadcrumb, .btn, .navbar, .sidebar, footer {
        display: none !important;
    }
    
    .container-fluid {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    .position-results .col-md-6 {
        width: 50% !important;
        float: left !important;
    }
    
    .winner-badge {
        border: 1px solid #28a745 !important;
    }
    
    @page {
        margin: 1cm;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print functionality
    const printButton = document.getElementById('print-results');
    if (printButton) {
        printButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 