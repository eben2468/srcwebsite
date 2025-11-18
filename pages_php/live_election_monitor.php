<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if user is super admin (only super admins can access this page)
if (!isSuperAdmin()) {
    $_SESSION['error'] = "Access denied. Only super administrators can access this page.";
    header("Location: dashboard.php");
    exit();
}

// Get current user info
$user = getCurrentUser();
$userId = $user['user_id'];

// Set page title
$pageTitle = "Live Election Monitor - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add custom CSS for the live monitor
echo '<link rel="stylesheet" href="../css/election-mobile-fix.css">';

// Get active elections
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM election_positions WHERE election_id = e.election_id) AS position_count,
        (SELECT COUNT(*) FROM election_candidates c JOIN election_positions p ON c.position_id = p.position_id WHERE p.election_id = e.election_id) AS candidate_count
        FROM elections e
        WHERE e.status = 'active'
        ORDER BY e.start_date DESC";
$activeElections = fetchAll($sql);

// Get total eligible voters (all users)
$sql = "SELECT COUNT(*) as total_users FROM users";
$result = fetchOne($sql);
$totalUsers = $result ? $result['total_users'] : 0;
?>

<style>
.live-monitor-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.live-monitor-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.live-monitor-header-main {
    flex: 1;
    text-align: center;
}

.live-monitor-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.live-monitor-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.live-monitor-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.live-monitor-header-actions {
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

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    height: 100%;
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
}

.stats-card-title {
    text-align: center;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.stats-card-value {
    text-align: center;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stats-card-label {
    text-align: center;
    font-size: 0.9rem;
    color: #6c757d;
}

.turnout-chart-container {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border: 1px solid #e8f5e8;
    margin-bottom: 2rem;
}

.turnout-percentage {
    font-size: 3rem;
    font-weight: 700;
    color: #28a745;
    margin-bottom: 0.5rem;
    text-align: center;
}

.turnout-label {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 1rem;
    text-align: center;
}

.voter-stats {
    margin-top: 2rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.voted-card {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.15) 0%, rgba(40, 167, 69, 0.05) 100%);
    border: 2px solid rgba(40, 167, 69, 0.2);
}

.eligible-card {
    background: linear-gradient(135deg, rgba(108, 117, 125, 0.15) 0%, rgba(108, 117, 125, 0.05) 100%);
    border: 2px solid rgba(108, 117, 125, 0.2);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.position-bar {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 1rem;
}

.position-chart-container {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
}

/* Scrollbar styling */
.position-chart-container::-webkit-scrollbar {
    width: 8px;
}

.position-chart-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.position-chart-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.position-chart-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.no-elections {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

.no-elections i {
    font-size: 3rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.no-elections h3 {
    color: #495057;
    margin-bottom: 1rem;
}

.no-elections p {
    color: #6c757d;
    font-size: 1.1rem;
}

/* Responsive styles */
@media (max-width: 768px) {
    .live-monitor-header {
        padding: 2rem 1.5rem;
        margin-top: 20px;
    }

    .live-monitor-header-content {
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .live-monitor-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .live-monitor-title i {
        font-size: 1.8rem;
    }

    .live-monitor-description {
        font-size: 1.1rem;
    }

    .live-monitor-header-actions {
        width: 100%;
        justify-content: center;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }

    .stats-card-value {
        font-size: 1.5rem;
    }

    .turnout-percentage {
        font-size: 2.5rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .live-monitor-header {
        padding: 1.5rem 1rem;
    }

    .live-monitor-title {
        font-size: 1.8rem;
        gap: 0.5rem;
    }

    .live-monitor-title i {
        font-size: 1.5rem;
    }

    .live-monitor-description {
        font-size: 1rem;
    }

    .stats-card {
        padding: 1rem;
    }

    .stats-card-value {
        font-size: 1.3rem;
    }

    .turnout-percentage {
        font-size: 2rem;
    }

    .stat-value {
        font-size: 1.3rem;
    }

    .position-chart-container {
        max-height: 300px;
    }
}
</style>

<div class="container-fluid">
    <!-- Custom Live Monitor Header -->
    <div class="live-monitor-header animate__animated animate__fadeInDown">
        <div class="live-monitor-header-content">
            <div class="live-monitor-header-main">
                <h1 class="live-monitor-title">
                    <i class="fas fa-chart-line me-3"></i>
                    Live Election Monitor
                </h1>
                <p class="live-monitor-description">Real-time monitoring of ongoing elections, voter turnout, and position participation</p>
            </div>
            <div class="live-monitor-header-actions">
                <a href="elections.php" class="btn btn-header-action">
                    <i class="fas fa-arrow-left me-2"></i>Back to Elections
                </a>
                <button type="button" class="btn btn-header-action" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($activeElections)): ?>
        <div class="no-elections">
            <i class="fas fa-vote-yea"></i>
            <h3>No Active Elections</h3>
            <p>There are currently no elections in progress.</p>
            <a href="elections.php" class="btn btn-primary mt-3">
                <i class="fas fa-vote-yea me-2"></i>View All Elections
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($activeElections as $election): ?>
            <?php
            // Get election ID
            $electionId = $election['election_id'];
            
            // Calculate unique voters for this election
            $sql = "SELECT COUNT(DISTINCT voter_id) as unique_voters FROM votes WHERE election_id = ?";
            $result = fetchOne($sql, [$electionId]);
            $uniqueVoters = $result ? $result['unique_voters'] : 0;

            // Calculate voter turnout percentage
            $turnoutPercentage = $totalUsers > 0 ? ($uniqueVoters / $totalUsers) * 100 : 0;

            // Get positions for this election
            $sql = "SELECT p.*, 
                    (SELECT COUNT(*) FROM votes v WHERE v.position_id = p.position_id) AS vote_count
                    FROM election_positions p 
                    WHERE p.election_id = ?
                    ORDER BY p.title";
            $positions = fetchAll($sql, [$electionId]);
            ?>
            
            <div class="card mb-4 shadow-lg border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="card-title mb-0" style="font-weight: 600;">
                                <i class="fas fa-vote-yea me-2"></i>
                                <?php echo htmlspecialchars($election['title']); ?>
                            </h3>
                            <p class="mb-0 mt-2 opacity-75">
                                <?php echo htmlspecialchars($election['description']); ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark fs-6">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('M j, Y', strtotime($election['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($election['end_date'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Stats Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-card-icon bg-primary text-white">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stats-card-title">Total Eligible</div>
                                <div class="stats-card-value text-primary"><?php echo $totalUsers; ?></div>
                                <div class="stats-card-label">Voters</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-card-icon bg-success text-white">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                                <div class="stats-card-title">Votes Cast</div>
                                <div class="stats-card-value text-success"><?php echo $uniqueVoters; ?></div>
                                <div class="stats-card-label">Unique Voters</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-card-icon bg-info text-white">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stats-card-title">Turnout Rate</div>
                                <div class="stats-card-value text-info"><?php echo round($turnoutPercentage, 1); ?>%</div>
                                <div class="stats-card-label">Participation</div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stats-card">
                                <div class="stats-card-icon bg-warning text-white">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="stats-card-title">Positions</div>
                                <div class="stats-card-value text-warning"><?php echo $election['position_count']; ?></div>
                                <div class="stats-card-label">Available</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Voter Turnout Visualization -->
                    <div class="turnout-chart-container">
                        <h4 class="text-center mb-4" style="color: #667eea; font-weight: 600;">
                            <i class="fas fa-chart-pie me-2"></i>Voter Turnout
                        </h4>
                        
                        <div class="turnout-main mb-4">
                            <div class="turnout-percentage">
                                <?php echo round($turnoutPercentage, 1); ?>%
                            </div>
                            <div class="turnout-label">
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
                                <div class="col-md-6 mb-3">
                                    <div class="stat-item voted-card">
                                        <div class="stat-value text-success">
                                            <?php echo $uniqueVoters; ?>
                                        </div>
                                        <div class="stat-label text-success">
                                            Voted
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="stat-item eligible-card">
                                        <div class="stat-value text-secondary">
                                            <?php echo $totalUsers; ?>
                                        </div>
                                        <div class="stat-label text-secondary">
                                            Eligible
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Position Participation -->
                    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>Participation by Position
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($positions)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tasks fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No positions found for this election.</p>
                                </div>
                            <?php else: ?>
                                <div class="position-chart-container">
                                    <?php foreach ($positions as $position): ?>
                                        <?php 
                                        $positionPercentage = $totalUsers > 0 ? ($position['vote_count'] / $totalUsers) * 100 : 0;
                                        ?>
                                        <div class="position-bar">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span style="font-weight: 600; color: #495057; font-size: 0.95rem;">
                                                    <?php echo htmlspecialchars($position['title']); ?>
                                                </span>
                                                <div>
                                                    <span class="badge bg-primary" style="margin-right: 5px; font-size: 0.8rem;">
                                                        <?php echo $position['vote_count']; ?> votes
                                                    </span>
                                                    <span class="badge bg-secondary" style="font-size: 0.8rem;">
                                                        <?php echo round($positionPercentage, 1); ?>%
                                                    </span>
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
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Top Candidates for Each Position -->
                    <div class="card border-0 shadow-sm mt-4" style="border-radius: 15px; overflow: hidden;">
                        <div class="card-header bg-light">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-trophy me-2"></i>Leading Candidates
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($positions)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">No positions found for this election.</p>
                                </div>
                            <?php else: ?>
                                <div class="accordion" id="candidatesAccordion">
                                    <?php foreach ($positions as $index => $position): ?>
                                        <?php
                                        // Get top 3 candidates for this position
                                        $sql = "SELECT c.*, u.first_name, u.last_name, u.email
                                                FROM election_candidates c
                                                JOIN users u ON c.user_id = u.user_id
                                                WHERE c.position_id = ? AND c.status = 'approved'
                                                ORDER BY c.votes DESC
                                                LIMIT 3";
                                        $candidates = fetchAll($sql, [$position['position_id']]);
                                        ?>
                                        
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                    <strong><?php echo htmlspecialchars($position['title']); ?></strong>
                                                    <span class="badge bg-primary ms-2"><?php echo $position['vote_count']; ?> votes</span>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#candidatesAccordion">
                                                <div class="accordion-body">
                                                    <?php if (empty($candidates)): ?>
                                                        <div class="text-center py-3">
                                                            <p class="text-muted mb-0">No candidates found for this position.</p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="row">
                                                            <?php foreach ($candidates as $rank => $candidate): ?>
                                                                <div class="col-md-4 mb-3">
                                                                    <div class="card h-100 <?php echo $rank === 0 ? 'border-success' : ''; ?>">
                                                                        <div class="card-body text-center">
                                                                            <div class="position-relative">
                                                                                <?php if ($rank === 0): ?>
                                                                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                                                        <i class="fas fa-crown"></i>
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                                
                                                                                <div class="mb-3">
                                                                                    <?php if (!empty($candidate['candidate_photo'])): ?>
                                                                                        <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                                                                    <?php else: ?>
                                                                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                                                                            <i class="fas fa-user fa-2x text-secondary"></i>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                
                                                                                <h5 class="card-title">
                                                                                    <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                                                </h5>
                                                                                
                                                                                <div class="mt-3">
                                                                                    <span class="badge <?php echo $rank === 0 ? 'bg-success' : ($rank === 1 ? 'bg-warning' : 'bg-info'); ?> fs-6">
                                                                                        <?php echo $candidate['votes']; ?> votes
                                                                                    </span>
                                                                                </div>
                                                                                
                                                                                <?php if ($rank === 0): ?>
                                                                                    <div class="mt-2">
                                                                                        <span class="badge bg-success">
                                                                                            <i class="fas fa-award me-1"></i>Leading
                                                                                        </span>
                                                                                    </div>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
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
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Auto-refresh the page every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);

// Add animation classes
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card, .stats-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('animate__animated', 'animate__fadeInUp');
        }, 100 * index);
    });
});
</script>

<?php include 'includes/footer.php'; ?>