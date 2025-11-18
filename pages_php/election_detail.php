<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user info
$user = getCurrentUser();

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
    'nomination' => 'Nominations Open',
    'pending' => 'Pending Voting',
    'active' => 'Active',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

$displayStatus = $statusMap[$election['status']] ?? ucfirst($election['status']);

// Set page title
$pageTitle = "Election Details - " . $election['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add mobile fix CSS for candidate cards
echo '<link rel="stylesheet" href="../css/candidate-card-mobile-fix.css">';
echo '<link rel="stylesheet" href="../css/election-mobile-fix.css">';
?>

<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = $election['title'];
    $pageIcon = "fa-vote-yea";
    $pageDescription = "Election Details - " . $displayStatus;
    $actions = [];

    // Back button
    $actions[] = [
        'url' => 'elections.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Elections',
        'class' => 'btn-outline-light'
    ];

    // Voting Portal button for active elections (available to all users)
    if ($election['status'] === 'active') {
        $actions[] = [
            'url' => 'voting_portal.php',
            'icon' => 'fa-vote-yea',
            'text' => 'Voting Portal',
            'class' => 'btn-outline-light'
        ];
    }

    // Super admin actions
    if (canManageElections()) {
        $actions[] = [
            'url' => 'election_edit.php?id=' . $electionId,
            'icon' => 'fa-edit',
            'text' => 'Edit Election',
            'class' => 'btn-outline-light'
        ];

        // Status-specific actions
        if ($election['status'] === 'upcoming' || $election['status'] === 'nomination') {
            if ($election['status'] !== 'nomination') {
                $actions[] = [
                    'url' => 'election_handler.php?action=open_nominations&id=' . $electionId,
                    'icon' => 'fa-unlock',
                    'text' => 'Open Nominations',
                    'class' => 'btn-outline-light'
                ];
            } else {
                $actions[] = [
                    'url' => 'election_handler.php?action=close_nominations&id=' . $electionId,
                    'icon' => 'fa-lock',
                    'text' => 'Close Nominations',
                    'class' => 'btn-outline-light'
                ];
            }
        }

        if ($election['status'] === 'pending') {
            $actions[] = [
                'url' => 'election_handler.php?action=open_voting&id=' . $electionId,
                'icon' => 'fa-vote-yea',
                'text' => 'Open Voting',
                'class' => 'btn-outline-light'
            ];
        }

        if ($election['status'] === 'active') {
            $actions[] = [
                'url' => 'election_handler.php?action=close_voting&id=' . $electionId,
                'icon' => 'fa-lock',
                'text' => 'Close Voting',
                'class' => 'btn-outline-light'
            ];
        }

        if ($election['status'] === 'completed' && (!isset($election['results_published']) || $election['results_published'] != 1)) {
            $actions[] = [
                'url' => 'election_handler.php?action=publish_results&id=' . $electionId,
                'icon' => 'fa-share-alt',
                'text' => 'Publish Results',
                'class' => 'btn-outline-light'
            ];
        }

        $actions[] = [
            'url' => 'election_results.php?id=' . $electionId,
            'icon' => 'fa-chart-bar',
            'text' => 'View Results',
            'class' => 'btn-outline-light'
        ];
    } elseif ($election['status'] === 'completed') {
        $actions[] = [
            'url' => 'election_results.php?id=' . $electionId,
            'icon' => 'fa-chart-bar',
            'text' => 'View Results',
            'class' => 'btn-outline-light'
        ];
    }

    // Include the modern page header
    include_once 'includes/modern_page_header.php';
    ?>
    
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

    <!-- Election Details -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Election Process</h5>
                </div>
                <div class="card-body">
                    <div class="election-progress">
                        <div class="progress-container">
                            <div class="progress-step <?php echo in_array($election['status'], ['upcoming', 'nomination', 'pending', 'active', 'completed']) ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-flag"></i></div>
                                <div class="step-label">Planning</div>
                            </div>
                            <div class="progress-connector"></div>
                            <div class="progress-step <?php echo in_array($election['status'], ['nomination', 'pending', 'active', 'completed']) ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                                <div class="step-label">Nominations</div>
                            </div>
                            <div class="progress-connector"></div>
                            <div class="progress-step <?php echo in_array($election['status'], ['pending', 'active', 'completed']) ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-clipboard-check"></i></div>
                                <div class="step-label">Candidates Review</div>
                            </div>
                            <div class="progress-connector"></div>
                            <div class="progress-step <?php echo in_array($election['status'], ['active', 'completed']) ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-vote-yea"></i></div>
                                <div class="step-label">Voting</div>
                            </div>
                            <div class="progress-connector"></div>
                            <div class="progress-step <?php echo in_array($election['status'], ['completed']) ? 'active' : ''; ?> <?php echo (isset($election['results_published']) && $election['results_published'] == 1) ? 'completed' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-chart-bar"></i></div>
                                <div class="step-label">Results</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                                        ($displayStatus === 'Planning' ? 'warning' : 
                                            ($displayStatus === 'Nominations Open' ? 'info' :
                                                ($displayStatus === 'Pending Voting' ? 'secondary' :
                                                    ($displayStatus === 'Completed' ? 'success' : 'secondary')))));
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
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Current Status
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Define messages for each status
                    $statusMessages = [
                        'upcoming' => 'This election is in the planning stage. When ready, the administrator will open nominations.',
                        'nomination' => 'Nominations are currently open. Eligible members can register as candidates for available positions until nominations are closed.',
                        'pending' => 'Nominations are now closed. The election administrator is reviewing candidates before opening voting.',
                        'active' => 'Voting is currently open. Cast your vote for your preferred candidates before the election ends. You can view current voting statistics, but individual results will be hidden until the election is completed.',
                        'completed' => isset($election['results_published']) && $election['results_published'] == 1 
                            ? 'This election has concluded and the results have been published.' 
                            : 'This election has concluded. Results will be published soon by the administrator.',
                        'cancelled' => 'This election has been cancelled.'
                    ];
                    
                    $currentStatusMessage = $statusMessages[$election['status']] ?? 'Status information not available.';
                    $isStatusError = !isset($statusMessages[$election['status']]);
                    ?>
                    
                    <div class="alert alert-<?php 
                        echo $isStatusError ? 'danger' : 
                            ($election['status'] === 'upcoming' ? 'warning' : 
                                ($election['status'] === 'nomination' ? 'info' : 
                                    ($election['status'] === 'pending' ? 'secondary' : 
                                        ($election['status'] === 'active' ? 'primary' : 
                                            ($election['status'] === 'completed' ? 'success' : 'danger')))));
                    ?>">
                        <?php if ($isStatusError && canManageElections()): ?>
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $currentStatusMessage; ?>
                            <div class="mt-2">
                                <a href="../fix_status.php" class="btn btn-sm btn-danger">
                                    <i class="fas fa-tools me-1"></i> Fix Database Status
                                </a>
                            </div>
                        <?php else: ?>
                            <i class="fas fa-<?php 
                                echo $election['status'] === 'upcoming' ? 'exclamation-triangle' : 
                                    ($election['status'] === 'nomination' ? 'user-plus' : 
                                        ($election['status'] === 'pending' ? 'clock' : 
                                            ($election['status'] === 'active' ? 'vote-yea' : 
                                                ($election['status'] === 'completed' ? 'check-circle' : 'times-circle')))); 
                            ?> me-2"></i>
                            <?php echo $currentStatusMessage; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($election['status'] === 'nomination'): ?>
                        <div class="mt-3">
                            <h6>How to register as a candidate:</h6>
                            <ol>
                                <li>Go to the position you want to run for in the "Election Positions" section</li>
                                <li>Click on "Register as Candidate" button</li>
                                <li>Fill out the candidate registration form</li>
                                <li>Submit your application</li>
                                <li>Wait for approval from an administrator</li>
                            </ol>
                        </div>
                    <?php elseif ($election['status'] === 'active'): ?>
                        <div class="mt-3">
                            <h6>How to vote:</h6>
                            <ol>
                                <li>Use the "Start Sequential Voting" button below for a guided voting experience</li>
                                <li>Or go to individual positions in the "Election Positions" section</li>
                                <li>Select your preferred candidates</li>
                                <li>Submit your votes</li>
                            </ol>
                            <p class="text-danger mt-2"><strong>Note:</strong> You can only vote once per position!</p>

                            <?php
                            // Check if user has any positions available to vote for
                            $sql = "SELECT p.position_id
                                    FROM election_positions p
                                    WHERE p.election_id = ?
                                    AND (SELECT COUNT(*) FROM election_candidates c WHERE c.position_id = p.position_id AND c.status = 'approved') > 0
                                    AND p.position_id NOT IN (SELECT v.position_id FROM votes v WHERE v.election_id = ? AND v.voter_id = ?)";
                            $availablePositions = fetchAll($sql, [$electionId, $electionId, $user['user_id']]);

                            if (!empty($availablePositions)): ?>
                                <div class="text-center mt-4">
                                    <a href="sequential_vote.php?election_id=<?php echo $electionId; ?>" class="btn btn-primary btn-lg">
                                        <i class="fas fa-play me-2"></i>Start Sequential Voting
                                    </a>
                                    <p class="text-muted mt-2 small">Vote for all positions in a guided, step-by-step process</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You have completed voting for all available positions in this election.
                                </div>
                            <?php endif; ?>
                        </div>
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
                                            if (canManageElections() || $election['status'] === 'completed') {
                                                $sql = "SELECT c.*, u.first_name, u.last_name, u.email 
                                                        FROM election_candidates c 
                                                        JOIN users u ON c.user_id = u.user_id 
                                                        WHERE c.position_id = ? 
                                                        ORDER BY c.votes DESC, u.first_name, u.last_name";
                                            } else {
                                                $sql = "SELECT c.*, u.first_name, u.last_name, u.email 
                                                        FROM election_candidates c 
                                                        JOIN users u ON c.user_id = u.user_id 
                                                        WHERE c.position_id = ? 
                                                        ORDER BY u.first_name, u.last_name";
                                            }
                                            $candidates = fetchAll($sql, [$position['position_id']]);
                                            ?>

                                            <!-- Registration option for when nominations are open -->
                                            <?php if ($election['status'] === 'nomination'): ?>
                                                <!-- Check if user has already registered for this position -->
                                                <?php
                                                $userId = $user['user_id'];
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
                                                $userId = $user['user_id'];
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
                                                                <?php if (canManageElections() || $election['status'] === 'completed'): ?>
                                                                <th>Votes</th>
                                                                <?php endif; ?>
                                                                <?php if (canManageElections()): ?>
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
                                                                    <?php if (canManageElections() || $election['status'] === 'completed'): ?>
                                                                    <td><?php echo $candidate['votes']; ?></td>
                                                                    <?php endif; ?>
                                                                    <?php if (canManageElections() || ($candidate['user_id'] == $user['user_id'] && $election['status'] === 'nomination')): ?>
                                                                    <td>
                                                                        <div class="btn-group btn-group-sm">
                                                                            <a href="candidate_detail.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-primary">
                                                                                <i class="fas fa-eye"></i>
                                                                            </a>
                                                                            <?php if (canManageElections()): ?>
                                                                                <?php if ($candidate['status'] === 'pending'): ?>
                                                                                    <a href="candidate_handler.php?action=approve&id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-success" title="Approve Candidate">
                                                                                        <i class="fas fa-check"></i>
                                                                                    </a>
                                                                                    <a href="candidate_handler.php?action=reject&id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-danger" title="Reject Candidate" onclick="return confirm('Are you sure you want to reject this candidate?')">
                                                                                        <i class="fas fa-times"></i>
                                                                                    </a>
                                                                                <?php endif; ?>
                                                                            <?php elseif ($candidate['user_id'] == $user['user_id'] && $election['status'] === 'nomination'): ?>
                                                                                <?php if ($candidate['status'] !== 'withdrawn'): ?>
                                                                                    <a href="candidate_handler.php?action=withdraw&id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-warning" title="Withdraw Candidacy" onclick="return confirm('Are you sure you want to withdraw your candidacy?')">
                                                                                        <i class="fas fa-sign-out-alt"></i>
                                                                                    </a>
                                                                                <?php endif; ?>
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
