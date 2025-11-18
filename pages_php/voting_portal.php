<?php
/**
 * Voting Portal
 * A simple portal to access sequential voting for active elections
 */

// Include necessary files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to access the voting portal.";
    header("Location: login.php");
    exit();
}

// Get current user info
$user = getCurrentUser();
$userId = $user['user_id'];

// Get active elections with positions and candidates
$sql = "SELECT e.election_id, e.title, e.description, e.start_date, e.end_date,
        COUNT(DISTINCT p.position_id) as position_count,
        COUNT(DISTINCT c.candidate_id) as candidate_count,
        COUNT(DISTINCT v.vote_id) as user_votes
        FROM elections e
        LEFT JOIN election_positions p ON e.election_id = p.election_id
        LEFT JOIN election_candidates c ON p.position_id = c.position_id AND c.status = 'approved'
        LEFT JOIN votes v ON e.election_id = v.election_id AND v.voter_id = ?
        WHERE e.status = 'active'
        GROUP BY e.election_id, e.title, e.description, e.start_date, e.end_date
        ORDER BY e.start_date DESC";

$activeElections = fetchAll($sql, [$userId]);

// Set page title
$pageTitle = "Voting Portal - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<style>
.voting-portal {
    max-width: none;
    margin: 0;
    padding: 0 30px;
}

.elections-list {
    margin-top: 1rem;
}

.no-elections {
    text-align: center;
    padding: 3rem 2rem;
    margin-top: 2rem;
}

/* Voting portal specific styles */

.election-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.election-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.election-title {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.election-meta {
    display: flex;
    gap: 1rem;
    margin: 1rem 0;
    flex-wrap: wrap;
}

.meta-item {
    background: #f8f9fa;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    color: #6c757d;
}

.voting-status {
    margin: 1rem 0;
}

.status-complete {
    color: #28a745;
    font-weight: bold;
}

.status-partial {
    color: #ffc107;
    font-weight: bold;
}

.status-none {
    color: #6c757d;
}

.voting-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.btn-vote {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-vote:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102,126,234,0.4);
    color: white;
    text-decoration: none;
}

.btn-details {
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #dee2e6;
    padding: 12px 24px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-details:hover {
    background: #e9ecef;
    color: #495057;
    text-decoration: none;
}

.no-elections {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .voting-portal {
        padding: 10px;
    }
    
    .election-meta {
        flex-direction: column;
        gap: 0.5rem;
    }

    .header-text h1 {
        font-size: 1.5rem;
    }

    .voting-actions {
        flex-direction: column;
    }

    .btn-vote, .btn-details {
        text-align: center;
    }

    .voting-portal {
        padding: 0 15px;
    }
}
</style>

<!-- Page Content -->
<div class="container-fluid px-0" style="margin-top: 60px;">
    <div class="voting-portal">
        <?php
        // Set up modern page header variables
        $pageTitle = "Voting Portal";
        $pageIcon = "fa-vote-yea";
        $pageDescription = "Welcome, " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "! Cast your votes in active elections.";
        $actions = [];

        // Back button
        $actions[] = [
            'url' => 'elections.php',
            'icon' => 'fa-arrow-left',
            'text' => 'Back to Elections',
            'class' => 'btn-outline-light'
        ];

        // All Elections button
        $actions[] = [
            'url' => 'elections.php',
            'icon' => 'fa-list',
            'text' => 'All Elections',
            'class' => 'btn-outline-light'
        ];

        // Include the modern page header
        include_once 'includes/modern_page_header.php';
        ?>

        <?php if (empty($activeElections)): ?>
            <div class="no-elections">
                <i class="fas fa-info-circle fa-3x mb-3 text-muted"></i>
                <h3>No Active Elections</h3>
                <p>There are currently no active elections available for voting.</p>
                <p>Check back later or contact your SRC administrators for more information.</p>
                <a href="elections.php" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>View All Elections
                </a>
            </div>
        <?php else: ?>
            <div class="elections-list">
                <?php foreach ($activeElections as $election): ?>
                    <?php
                    // Calculate voting progress
                    $totalPositions = $election['position_count'];
                    $userVotes = $election['user_votes'];
                    $votingComplete = $userVotes >= $totalPositions && $totalPositions > 0;
                    $votingPartial = $userVotes > 0 && $userVotes < $totalPositions;
                    ?>
                    
                    <div class="election-card">
                        <h3 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h3>
                        
                        <?php if (!empty($election['description'])): ?>
                            <p class="text-muted"><?php echo htmlspecialchars($election['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="election-meta">
                            <div class="meta-item">
                                <i class="fas fa-users me-1"></i>
                                <?php echo $election['position_count']; ?> Position(s)
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-user-tie me-1"></i>
                                <?php echo $election['candidate_count']; ?> Candidate(s)
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('M j, Y', strtotime($election['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="voting-status">
                            <?php if ($votingComplete): ?>
                                <div class="status-complete">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Voting Complete (<?php echo $userVotes; ?>/<?php echo $totalPositions; ?> positions)
                                </div>
                            <?php elseif ($votingPartial): ?>
                                <div class="status-partial">
                                    <i class="fas fa-clock me-2"></i>
                                    Voting In Progress (<?php echo $userVotes; ?>/<?php echo $totalPositions; ?> positions)
                                </div>
                            <?php else: ?>
                                <div class="status-none">
                                    <i class="fas fa-ballot me-2"></i>
                                    Ready to Vote (0/<?php echo $totalPositions; ?> positions)
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="voting-actions">
                            <?php if (!$votingComplete && $election['candidate_count'] > 0): ?>
                                <a href="sequential_vote.php?election_id=<?php echo $election['election_id']; ?>" class="btn-vote">
                                    <i class="fas fa-play me-2"></i>
                                    <?php echo $votingPartial ? 'Continue Voting' : 'Start Voting'; ?>
                                </a>
                            <?php endif; ?>
                            
                            <a href="election_detail.php?id=<?php echo $election['election_id']; ?>" class="btn-details">
                                <i class="fas fa-info-circle me-2"></i>View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
