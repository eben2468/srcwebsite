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
            <h1 class="h3 mb-0">Cast Your Vote</h1>
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
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?election_id=" . $electionId . "&position_id=" . $positionId); ?>" id="voting-form">
                        <div class="mb-4">
                            <h5>Select your candidate:</h5>
                            <div class="candidate-list">
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="candidate-option form-check">
                                        <input class="form-check-input" type="radio" name="candidate_id" 
                                               id="candidate-<?php echo $candidate['candidate_id']; ?>" 
                                               value="<?php echo $candidate['candidate_id']; ?>"
                                               <?php echo ($candidateId == $candidate['candidate_id']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label candidate-card" for="candidate-<?php echo $candidate['candidate_id']; ?>">
                                            <div class="candidate-info">
                                                <div class="candidate-photo">
                                                    <?php if (!empty($candidate['candidate_photo'])): ?>
                                                        <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>">
                                                    <?php else: ?>
                                                        <i class="fas fa-user-circle"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="candidate-details">
                                                    <h5><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h5>
                                                    <p class="text-muted"><?php echo htmlspecialchars($candidate['email']); ?></p>
                                                    <a href="candidate_detail.php?id=<?php echo $candidate['candidate_id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        View Manifesto
                                                    </a>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="confirm-vote" required>
                            <label class="form-check-label" for="confirm-vote">
                                I confirm that I am voting for my chosen candidate and understand this action cannot be undone.
                            </label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i> Submit Vote
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.candidate-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.candidate-option {
    position: relative;
}

.candidate-card {
    display: block;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
    margin: 0;
}

.form-check-input:checked + .candidate-card {
    border-color: var(--primary-color);
    background-color: rgba(var(--primary-color-rgb), 0.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.candidate-info {
    display: flex;
    align-items: center;
}

.candidate-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 20px;
    flex-shrink: 0;
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

.candidate-photo i {
    font-size: 60px;
    color: #ccc;
}

.candidate-details {
    flex-grow: 1;
}

.candidate-details h5 {
    margin-bottom: 5px;
    font-weight: 600;
}

.form-check-input {
    position: absolute;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    width: 1.5em;
    height: 1.5em;
}

@media (max-width: 768px) {
    .candidate-info {
        flex-direction: column;
        text-align: center;
    }
    
    .candidate-photo {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .form-check-input {
        top: 15px;
        right: 15px;
        transform: none;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 