<?php
// Include authentication file
require_once '../auth_functions.php';

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

// Get election positions
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM election_candidates c WHERE c.position_id = p.position_id) AS candidate_count
        FROM election_positions p 
        WHERE p.election_id = ? 
        ORDER BY p.title";
$positions = fetchAll($sql, [$electionId]);

// Format dates for display
$startDate = date('F j, Y', strtotime($election['start_date']));
$endDate = date('F j, Y', strtotime($election['end_date']));

// Map status from database to display format
$statusMap = [
    'upcoming' => 'Upcoming',
    'active' => 'Active',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

$displayStatus = $statusMap[$election['status']] ?? ucfirst($election['status']);

// Set page title
$pageTitle = "Election Details - " . $election['title'] . " - SRC Management System";

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
            <h1 class="h3 mb-0"><?php echo htmlspecialchars($election['title']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Election Details</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if (isAdmin()): ?>
                <a href="election_edit.php?id=<?php echo $electionId; ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i> Edit Election
                </a>
            <?php endif; ?>
            <?php if ($election['status'] === 'active' || $election['status'] === 'completed'): ?>
                <a href="election_results.php?id=<?php echo $electionId; ?>" class="btn btn-info">
                    <i class="fas fa-chart-bar me-2"></i> View Results
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Election Details -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Election Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Status:</strong>
                            <span class="badge bg-<?php 
                                echo $displayStatus === 'Upcoming' ? 'success' : 
                                    ($displayStatus === 'Active' ? 'primary' : 
                                        ($displayStatus === 'Planning' ? 'warning' : 'secondary')); 
                            ?>">
                                <?php echo htmlspecialchars($displayStatus); ?>
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
                    </ul>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Description</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($election['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($election['description'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No description provided.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Election Positions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($positions)): ?>
                        <p class="text-center text-muted">No positions defined for this election.</p>
                    <?php else: ?>
                        <div class="accordion" id="positionAccordion">
                            <?php foreach ($positions as $index => $position): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $position['position_id']; ?>">
                                        <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" 
                                                type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?php echo $position['position_id']; ?>" 
                                                aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                                aria-controls="collapse<?php echo $position['position_id']; ?>">
                                            <?php echo htmlspecialchars($position['title']); ?>
                                            <span class="badge bg-info ms-2"><?php echo $position['seats']; ?> seat(s)</span>
                                            <span class="badge bg-secondary ms-2"><?php echo $position['candidate_count']; ?> candidate(s)</span>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $position['position_id']; ?>" 
                                         class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                         aria-labelledby="heading<?php echo $position['position_id']; ?>" 
                                         data-bs-parent="#positionAccordion">
                                        <div class="accordion-body">
                                            <?php
                                            // Get candidates for this position
                                            $sql = "SELECT c.*, u.first_name, u.last_name, u.email 
                                                    FROM election_candidates c 
                                                    JOIN users u ON c.user_id = u.user_id 
                                                    WHERE c.position_id = ? 
                                                    ORDER BY c.votes DESC, u.first_name, u.last_name";
                                            $candidates = fetchAll($sql, [$position['position_id']]);
                                            ?>

                                            <!-- Registration option for upcoming or active elections -->
                                            <?php if ($election['status'] === 'upcoming' || $election['status'] === 'active'): ?>
                                                <!-- Check if user has already registered for this position -->
                                                <?php
                                                $userId = getCurrentUser()['user_id'];
                                                $sql = "SELECT * FROM election_candidates WHERE position_id = ? AND user_id = ?";
                                                $existingCandidate = fetchOne($sql, [$position['position_id'], $userId]);
                                                
                                                if (!$existingCandidate): ?>
                                                    <div class="mb-3 text-center">
                                                        <a href="candidate_registration.php?position_id=<?php echo $position['position_id']; ?>&election_id=<?php echo $electionId; ?>" class="btn btn-primary">
                                                            <i class="fas fa-user-plus me-2"></i> Register as Candidate
                                                        </a>
                                                    </div>
                                                <?php elseif ($existingCandidate['status'] === 'pending'): ?>
                                                    <div class="alert alert-warning mb-3">
                                                        <i class="fas fa-exclamation-circle me-2"></i> Your candidacy application for this position is pending approval.
                                                    </div>
                                                <?php elseif ($existingCandidate['status'] === 'approved'): ?>
                                                    <div class="alert alert-success mb-3">
                                                        <i class="fas fa-check-circle me-2"></i> You are an approved candidate for this position.
                                                    </div>
                                                <?php elseif ($existingCandidate['status'] === 'rejected'): ?>
                                                    <div class="alert alert-danger mb-3">
                                                        <i class="fas fa-times-circle me-2"></i> Your candidacy application for this position was not approved.
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <!-- Voting option for active elections -->
                                            <?php if ($election['status'] === 'active'): ?>
                                                <!-- Check if user has already voted for this position -->
                                                <?php
                                                $userId = getCurrentUser()['user_id'];
                                                $sql = "SELECT * FROM votes WHERE position_id = ? AND voter_id = ?";
                                                $existingVote = fetchOne($sql, [$position['position_id'], $userId]);
                                                
                                                if (!$existingVote && !empty($candidates)): ?>
                                                    <div class="mb-3 text-center">
                                                        <a href="vote.php?position_id=<?php echo $position['position_id']; ?>&election_id=<?php echo $electionId; ?>" class="btn btn-success">
                                                            <i class="fas fa-vote-yea me-2"></i> Cast Your Vote
                                                        </a>
                                                    </div>
                                                <?php elseif ($existingVote): ?>
                                                    <div class="alert alert-info mb-3">
                                                        <i class="fas fa-info-circle me-2"></i> You have already voted for this position.
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if (empty($candidates)): ?>
                                                <p class="text-muted">No candidates registered for this position yet.</p>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Candidate</th>
                                                                <th>Status</th>
                                                                <th>Votes</th>
                                                                <?php if (isAdmin()): ?>
                                                                    <th>Actions</th>
                                                                <?php endif; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($candidates as $candidate): ?>
                                                                <tr>
                                                                    <td>
                                                                        <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-<?php 
                                                                            echo $candidate['status'] === 'approved' ? 'success' : 
                                                                                ($candidate['status'] === 'pending' ? 'warning' : 
                                                                                    ($candidate['status'] === 'withdrawn' ? 'secondary' : 'danger')); 
                                                                        ?>">
                                                                            <?php echo ucfirst(htmlspecialchars($candidate['status'])); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo $candidate['votes']; ?></td>
                                                                    <?php if (isAdmin()): ?>
                                                                        <td>
                                                                            <div class="btn-group btn-group-sm">
                                                                                <a href="candidate_detail.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-primary">
                                                                                    <i class="fas fa-eye"></i>
                                                                                </a>
                                                                                <?php if ($candidate['status'] === 'pending'): ?>
                                                                                    <a href="candidate_handler.php?action=approve&id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-success">
                                                                                        <i class="fas fa-check"></i>
                                                                                    </a>
                                                                                    <a href="candidate_handler.php?action=reject&id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-danger">
                                                                                        <i class="fas fa-times"></i>
                                                                                    </a>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </td>
                                                                    <?php endif; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 