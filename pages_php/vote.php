<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
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

// Get current user info
$user = getCurrentUser();
$userId = $user['user_id'];

// Check if election ID and position ID are provided
if (!isset($_GET['election_id']) || empty($_GET['election_id']) || !isset($_GET['position_id']) || empty($_GET['position_id'])) {
    $_SESSION['error'] = "Election ID and Position ID are required.";
    header("Location: elections.php");
    exit();
}

$electionId = intval($_GET['election_id']);
$positionId = intval($_GET['position_id']);

// Check if a specific candidate is pre-selected
$candidateId = isset($_GET['candidate_id']) ? intval($_GET['candidate_id']) : 0;

// Get election details
$sql = "SELECT * FROM elections WHERE election_id = ?";
$election = fetchOne($sql, [$electionId]);

if (!$election) {
    $_SESSION['error'] = "Election not found.";
    header("Location: elections.php");
    exit();
}

// Check if election is active
if ($election['status'] !== 'active') {
    $_SESSION['error'] = "Voting is only allowed for active elections.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Get position details
$sql = "SELECT * FROM election_positions WHERE position_id = ? AND election_id = ?";
$position = fetchOne($sql, [$positionId, $electionId]);

if (!$position) {
    $_SESSION['error'] = "Position not found in this election.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Check if user has already voted for this position
$sql = "SELECT * FROM votes WHERE election_id = ? AND position_id = ? AND voter_id = ?";
$existingVote = fetchOne($sql, [$electionId, $positionId, $userId]);

if ($existingVote) {
    $_SESSION['error'] = "You have already voted for this position.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Get approved candidates for this position
$sql = "SELECT c.*, u.first_name, u.last_name, u.email
        FROM election_candidates c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.position_id = ? AND c.election_id = ? AND c.status = 'approved'
        ORDER BY u.first_name, u.last_name";
$candidates = fetchAll($sql, [$positionId, $electionId]);

if (empty($candidates)) {
    $_SESSION['error'] = "No approved candidates found for this position.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Process vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['candidate_id']) || empty($_POST['candidate_id'])) {
        $_SESSION['error'] = "Please select a candidate to vote for.";
    } else {
        $voteCandidateId = intval($_POST['candidate_id']);
        
        // Verify candidate is valid
        $validCandidate = false;
        foreach ($candidates as $candidate) {
            if ($candidate['candidate_id'] == $voteCandidateId) {
                $validCandidate = true;
                break;
            }
        }
        
        if (!$validCandidate) {
            $_SESSION['error'] = "Invalid candidate selection.";
        } else {
            // Insert vote into database and update candidate votes count
            $conn->begin_transaction();
            
            try {
                // Insert into votes table
                $sql = "INSERT INTO votes (election_id, position_id, voter_id, candidate_id) 
                        VALUES (?, ?, ?, ?)";
                $params = [$electionId, $positionId, $userId, $voteCandidateId];
                $voteId = insert($sql, $params);
                
                if (!$voteId) {
                    throw new Exception("Failed to record vote.");
                }
                
                // Update candidate votes count
                $sql = "UPDATE election_candidates SET votes = votes + 1 WHERE candidate_id = ?";
                $result = update($sql, [$voteCandidateId]);
                
                if ($result === false) {
                    throw new Exception("Failed to update candidate votes.");
                }
                
                // Commit transaction
                $conn->commit();
                
                $_SESSION['success'] = "Your vote has been successfully recorded.";
                header("Location: election_detail.php?id=" . $electionId);
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $_SESSION['error'] = "Error recording vote: " . $e->getMessage();
            }
        }
    }
}

// Set page title
$pageTitle = "Vote - " . $position['title'] . " - " . $election['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add mobile fix CSS for candidate cards
echo '<link rel="stylesheet" href="../css/candidate-card-mobile-fix.css">';
echo '<link rel="stylesheet" href="../css/election-mobile-fix.css">';
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
            

</div>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
                    <li class="breadcrumb-item"><a href="election_detail.php?id=<?php echo $electionId; ?>"><?php echo htmlspecialchars($election['title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Vote for <?php echo htmlspecialchars($position['title']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Election
            </a>
        </div>
    </div>

    <!-- Voting Interface -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-vote-yea me-2"></i>
                        Vote for <?php echo htmlspecialchars($position['title']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> You can only vote once for this position. Your vote cannot be changed once submitted.
                    </div>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?election_id=" . $electionId . "&position_id=" . $positionId); ?>" id="voteForm">
                        <div class="candidate-selection">
                            <h6 class="mb-3">Select your preferred candidate for <?php echo htmlspecialchars($position['title']); ?>:</h6>
                            
                            <div class="candidates-grid row">
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="candidate-card">
                                            <input type="radio" class="candidate-radio" name="candidate_id" id="candidate-<?php echo $candidate['candidate_id']; ?>" value="<?php echo $candidate['candidate_id']; ?>" <?php echo $candidateId == $candidate['candidate_id'] ? 'checked' : ''; ?>>
                                            <label for="candidate-<?php echo $candidate['candidate_id']; ?>" class="candidate-label">
                                                <span class="selection-indicator"><i class="fas fa-check-circle"></i> Selected</span>
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
                                                <div class="candidate-manifesto-preview">
                                                    <?php 
                                                        // Show a preview of the manifesto
                                                        $manifestoPreview = strlen($candidate['manifesto']) > 150 ? 
                                                            substr($candidate['manifesto'], 0, 150) . '...' : 
                                                            $candidate['manifesto'];
                                                        echo nl2br(htmlspecialchars($manifestoPreview)); 
                                                    ?>
                                                    <?php if (strlen($candidate['manifesto']) > 150): ?>
                                                        <a href="#" class="read-more" data-bs-toggle="modal" data-bs-target="#candidateModal-<?php echo $candidate['candidate_id']; ?>">Read more</a>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <!-- Candidate Modal -->
                                        <div class="modal fade" id="candidateModal-<?php echo $candidate['candidate_id']; ?>" tabindex="-1" aria-labelledby="candidateModalLabel-<?php echo $candidate['candidate_id']; ?>" aria-hidden="true" data-bs-backdrop="false">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="candidateModalLabel-<?php echo $candidate['candidate_id']; ?>">
                                                            <?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-4 text-center">
                                                                <?php if (!empty($candidate['candidate_photo'])): ?>
                                                                    <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>" class="img-fluid rounded candidate-modal-photo">
                                                                <?php else: ?>
                                                                    <div class="no-photo large">
                                                                        <i class="fas fa-user"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-md-8">
                                                                <h5>Manifesto</h5>
                                                                <div class="manifesto-full">
                                                                    <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="button" class="btn btn-primary select-candidate" data-candidate-id="<?php echo $candidate['candidate_id']; ?>" data-bs-dismiss="modal">Select this Candidate</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Back to Election
                            </a>
                            <button type="submit" class="btn btn-success" id="submitVote">
                                <i class="fas fa-vote-yea me-2"></i> Submit Vote
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.candidate-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
}

.candidate-card:hover {
    border-color: #4e73df;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-5px);
}

.candidate-radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.candidate-label {
    padding: 15px;
    display: block;
    cursor: pointer;
    height: 100%;
    position: relative;
    padding-top: 35px; /* Make space for the selection indicator */
}

/* Selection indicator */
.selection-indicator {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background-color: #1cc88a;
    color: white;
    text-align: center;
    padding: 5px;
    font-weight: bold;
    font-size: 14px;
}

.selection-indicator i {
    margin-right: 5px;
}

.candidate-radio:checked + .candidate-label .selection-indicator {
    display: block;
}

/* Add visible radio button */
.candidate-label:before {
    content: '';
    display: block;
    width: 24px;
    height: 24px;
    border: 2px solid #4e73df;
    border-radius: 50%;
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #fff;
    transition: all 0.2s;
}

/* Radio button dot when selected */
.candidate-radio:checked + .candidate-label:after {
    content: '';
    display: block;
    width: 12px;
    height: 12px;
    background-color: #4e73df;
    border-radius: 50%;
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 1;
}

.candidate-radio:checked + .candidate-label {
    background-color: rgba(78, 115, 223, 0.1);
    border-color: #4e73df;
}

.candidate-radio:checked + .candidate-label:before {
    border-color: #4e73df;
    background-color: #fff;
}

.candidate-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding-right: 20px; /* Make space for the radio button */
}

.candidate-photo {
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

.candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-photo {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e9ecef;
    color: #6c757d;
    font-size: 24px;
}

.no-photo.large {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    font-size: 50px;
    border-radius: 50%;
}

.candidate-info h5 {
    margin-bottom: 5px;
    font-weight: 600;
}

.candidate-manifesto-preview {
    font-size: 14px;
    color: #6c757d;
    line-height: 1.5;
}

.read-more {
    display: inline-block;
    margin-top: 8px;
    color: #4e73df;
    font-weight: 600;
    }
    
.candidate-modal-photo {
    max-width: 150px;
    max-height: 150px;
        margin-bottom: 15px;
    }
    
.manifesto-full {
    white-space: pre-line;
    line-height: 1.6;
}

.candidates-grid {
    margin-top: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const voteForm = document.getElementById('voteForm');
    const submitBtn = document.getElementById('submitVote');
    
    voteForm.addEventListener('submit', function(e) {
        const selectedCandidate = document.querySelector('input[name="candidate_id"]:checked');
        
        if (!selectedCandidate) {
            e.preventDefault();
            alert('Please select a candidate before submitting your vote.');
            return false;
        } else {
            // Ask for confirmation
            if (!confirm('Are you sure you want to vote for this candidate? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
            
            // Add a loading state to the button to prevent double-clicks
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        }
    });
    
    // Modal select button functionality
    const selectButtons = document.querySelectorAll('.select-candidate');
    
    selectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const candidateId = this.getAttribute('data-candidate-id');
            const radioButton = document.getElementById('candidate-' + candidateId);
            
            if (radioButton) {
                radioButton.checked = true;
                
                // Scroll to the selected candidate
                radioButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Highlight the selection briefly
                const candidateCard = radioButton.closest('.candidate-card');
                candidateCard.style.animation = 'pulse 1s';
                setTimeout(() => {
                    candidateCard.style.animation = '';
                }, 1000);
            }
        });
    });
    
    // Add a highlight animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php require_once 'includes/footer.php'; ?> 
