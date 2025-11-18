<?php
/**
 * Sequential Voting Interface
 * Modern step-by-step voting experience for elections
 */

// Include necessary files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    die("Error: Authentication functions not available. Please check your system configuration.");
}

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to vote.";
    header("Location: login.php");
    exit();
}

// Get current user info
if (!function_exists('getCurrentUser')) {
    die("Error: getCurrentUser function not available. Please check your system configuration.");
}

$user = getCurrentUser();
if (!$user || !isset($user['user_id'])) {
    $_SESSION['error'] = "Unable to get user information. Please log in again.";
    header("Location: login.php");
    exit();
}

$userId = $user['user_id'];

// Check if election ID is provided
if (!isset($_GET['election_id']) || empty($_GET['election_id'])) {
    $_SESSION['error'] = "Election ID is required to access the voting interface.";
    header("Location: elections.php");
    exit();
}

// Validate election ID is numeric
if (!is_numeric($_GET['election_id'])) {
    $_SESSION['error'] = "Invalid election ID provided.";
    header("Location: elections.php");
    exit();
}

$electionId = intval($_GET['election_id']);

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

// Get all positions for this election (ordered by title)
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM election_candidates c WHERE c.position_id = p.position_id AND c.status = 'approved') AS candidate_count
        FROM election_positions p 
        WHERE p.election_id = ? 
        ORDER BY p.title";
$positions = fetchAll($sql, [$electionId]);

if (empty($positions)) {
    $_SESSION['error'] = "No positions found for this election.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Check which positions the user has already voted for
$votedPositions = [];
$sql = "SELECT position_id FROM votes WHERE election_id = ? AND voter_id = ?";
$userVotes = fetchAll($sql, [$electionId, $userId]);
foreach ($userVotes as $vote) {
    $votedPositions[] = $vote['position_id'];
}

// Filter out positions with no candidates or already voted positions
$availablePositions = [];
foreach ($positions as $position) {
    if ($position['candidate_count'] > 0 && !in_array($position['position_id'], $votedPositions)) {
        $availablePositions[] = $position;
    }
}

// If no positions available to vote for, redirect back
if (empty($availablePositions)) {
    $_SESSION['info'] = "You have already voted for all available positions in this election.";
    header("Location: election_detail.php?id=" . $electionId);
    exit();
}

// Handle AJAX requests for getting position data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_position') {
    header('Content-Type: application/json');
    
    $positionId = intval($_GET['position_id']);
    
    // Get position details
    $sql = "SELECT * FROM election_positions WHERE position_id = ? AND election_id = ?";
    $position = fetchOne($sql, [$positionId, $electionId]);
    
    if (!$position) {
        echo json_encode(['error' => 'Position not found']);
        exit();
    }
    
    // Get candidates for this position
    $sql = "SELECT c.*, u.first_name, u.last_name, u.email
            FROM election_candidates c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.position_id = ? AND c.status = 'approved'
            ORDER BY u.first_name, u.last_name";
    $candidates = fetchAll($sql, [$positionId]);
    
    echo json_encode([
        'position' => $position,
        'candidates' => $candidates
    ]);
    exit();
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_votes'])) {
    header('Content-Type: application/json');
    
    $votes = json_decode($_POST['votes'], true);
    
    if (empty($votes)) {
        echo json_encode(['error' => 'No votes to submit']);
        exit();
    }
    
    $conn->begin_transaction();
    
    try {
        foreach ($votes as $vote) {
            $positionId = intval($vote['position_id']);
            $candidateId = intval($vote['candidate_id']);
            
            // Verify this is a valid position and candidate
            $sql = "SELECT c.* FROM election_candidates c 
                    JOIN election_positions p ON c.position_id = p.position_id 
                    WHERE c.candidate_id = ? AND p.position_id = ? AND p.election_id = ? AND c.status = 'approved'";
            $validCandidate = fetchOne($sql, [$candidateId, $positionId, $electionId]);
            
            if (!$validCandidate) {
                throw new Exception("Invalid candidate selection for position ID: $positionId");
            }
            
            // Check if user already voted for this position
            $sql = "SELECT * FROM votes WHERE election_id = ? AND position_id = ? AND voter_id = ?";
            $existingVote = fetchOne($sql, [$electionId, $positionId, $userId]);
            
            if ($existingVote) {
                throw new Exception("You have already voted for this position");
            }
            
            // Insert vote
            $sql = "INSERT INTO votes (election_id, position_id, voter_id, candidate_id) VALUES (?, ?, ?, ?)";
            $voteId = insert($sql, [$electionId, $positionId, $userId, $candidateId]);
            
            if (!$voteId) {
                throw new Exception("Failed to record vote for position ID: $positionId");
            }
            
            // Update candidate votes count
            $sql = "UPDATE election_candidates SET votes = votes + 1 WHERE candidate_id = ?";
            $result = update($sql, [$candidateId]);
            
            if ($result === false) {
                throw new Exception("Failed to update candidate votes for candidate ID: $candidateId");
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'All votes submitted successfully']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => $e->getMessage()]);
    }
    
    exit();
}

// Set page title
$pageTitle = "Vote - " . $election['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add external CSS for sequential voting
echo '<link rel="stylesheet" href="../css/sequential-voting.css">';
?>

<style>
/* Additional inline styles for specific customizations */
.progress-bar-container {
    margin-top: 1rem;
}

.progress-bar-container .progress {
    height: 8px;
    border-radius: 4px;
    background-color: #e9ecef;
}

.progress-bar-container .progress-bar {
    background: linear-gradient(90deg, #007bff, #28a745);
    border-radius: 4px;
    transition: width 0.5s ease;
}

/* Full width layout adjustments */
.voting-container {
    max-width: none !important;
    margin: 0 !important;
}

/* Voting specific styles */
.voting-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

@media (max-width: 768px) {
    .voting-cards-grid {
        padding: 0 15px;
    }
}

/* Loading spinner for AJAX requests */
.loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success animation */
.success-checkmark {
    animation: checkmark 0.6s ease-in-out;
}

@keyframes checkmark {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}
</style>

<!-- Page Content -->
<div class="container-fluid px-0" style="margin-top: 60px;">
    <div class="voting-container" style="max-width: none; margin: 0; padding: 0 30px;">
        <?php
        // Set up modern page header variables
        $pageTitle = $election['title'];
        $pageIcon = "fa-vote-yea";
        $pageDescription = "Election Details - " . ucfirst($election['status']);
        $actions = [];

        // Back button
        $actions[] = [
            'url' => 'election_detail.php?id=' . $electionId,
            'icon' => 'fa-arrow-left',
            'text' => 'Back to Election',
            'class' => 'btn-outline-light'
        ];

        // Include the modern page header
        include_once 'includes/modern_page_header.php';
        ?>

        <!-- Progress Indicator -->
        <div class="voting-progress">
            <div class="progress-steps" id="progressSteps">
                <!-- Progress steps will be generated by JavaScript -->
            </div>
            <div class="progress-bar-container">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar"></div>
                </div>
            </div>
        </div>

        <!-- Voting Cards Container -->
        <div id="votingCards">
            <!-- Voting cards will be generated by JavaScript -->
        </div>

        <!-- Review Section -->
        <div class="review-section" id="reviewSection">
            <div class="text-center mb-4">
                <h2><i class="fas fa-clipboard-check me-3"></i>Review Your Votes</h2>
                <p class="text-muted">Please review your selections before submitting your final votes</p>
            </div>
            
            <div id="voteSummary">
                <!-- Vote summary will be generated by JavaScript -->
            </div>
            
            <div class="text-center mt-4">
                <button type="button" class="btn btn-outline-secondary btn-modern me-3" onclick="goBackToVoting()">
                    <i class="fas fa-arrow-left me-2"></i>Back to Voting
                </button>
                <button type="button" class="btn btn-success-modern" onclick="submitAllVotes()">
                    <i class="fas fa-check me-2"></i>Submit All Votes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
let positions = <?php echo json_encode($availablePositions); ?>;
let currentPositionIndex = 0;
let userVotes = {};
let positionData = {};

// Initialize the voting interface
document.addEventListener('DOMContentLoaded', function() {
    initializeVoting();
    setupKeyboardNavigation();
    setupAccessibility();
});

// Keyboard navigation support
function setupKeyboardNavigation() {
    document.addEventListener('keydown', function(e) {
        // Arrow keys for navigation
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            nextPosition();
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            previousPosition();
        } else if (e.key >= '1' && e.key <= '9') {
            // Number keys for candidate selection
            e.preventDefault();
            const candidateIndex = parseInt(e.key) - 1;
            selectCandidateByIndex(candidateIndex);
        } else if (e.key === 'Enter' || e.key === ' ') {
            // Enter or Space to proceed
            const activeElement = document.activeElement;
            if (activeElement && activeElement.classList.contains('candidate-card')) {
                e.preventDefault();
                activeElement.click();
            }
        }
    });
}

// Accessibility improvements
function setupAccessibility() {
    // Add ARIA labels and roles
    const progressSteps = document.getElementById('progressSteps');
    if (progressSteps) {
        progressSteps.setAttribute('role', 'progressbar');
        progressSteps.setAttribute('aria-label', 'Voting progress');
    }

    // Make candidate cards focusable
    document.addEventListener('click', function(e) {
        if (e.target.closest('.candidate-card')) {
            const candidateCards = document.querySelectorAll('.candidate-card');
            candidateCards.forEach(card => {
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
                card.setAttribute('aria-label', `Select candidate ${card.querySelector('.candidate-name').textContent}`);
            });
        }
    });
}

// Select candidate by index (for keyboard navigation)
function selectCandidateByIndex(index) {
    const currentCard = document.querySelector('.voting-card.active');
    if (currentCard) {
        const candidateCards = currentCard.querySelectorAll('.candidate-card');
        if (candidateCards[index]) {
            candidateCards[index].click();
        }
    }
}

function initializeVoting() {
    generateProgressSteps();
    loadPositionData(0);
}

function generateProgressSteps() {
    const progressSteps = document.getElementById('progressSteps');
    progressSteps.innerHTML = '';
    
    positions.forEach((position, index) => {
        const stepDiv = document.createElement('div');
        stepDiv.className = 'progress-step';
        stepDiv.innerHTML = `
            <div class="step-circle ${index === 0 ? 'active' : ''}" id="step-${index}">
                ${index + 1}
            </div>
            <div class="step-label ${index === 0 ? 'active' : ''}" id="label-${index}">
                ${position.title}
            </div>
            ${index < positions.length - 1 ? '<div class="progress-line" id="line-' + index + '"></div>' : ''}
        `;
        progressSteps.appendChild(stepDiv);
    });
    
    // Add review step
    const reviewStep = document.createElement('div');
    reviewStep.className = 'progress-step';
    reviewStep.innerHTML = `
        <div class="step-circle" id="step-review">
            <i class="fas fa-check"></i>
        </div>
        <div class="step-label" id="label-review">
            Review
        </div>
    `;
    progressSteps.appendChild(reviewStep);
    
    updateProgressBar();
}

async function loadPositionData(index) {
    if (index >= positions.length) {
        showReviewSection();
        return;
    }

    const position = positions[index];

    // Show loading state
    showLoadingState();

    try {
        const response = await fetch(`sequential_vote.php?ajax=get_position&position_id=${position.position_id}&election_id=<?php echo $electionId; ?>`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            showErrorMessage('Error loading position data: ' + data.error);
            return;
        }

        positionData[position.position_id] = data;
        generateVotingCard(data, index);

    } catch (error) {
        console.error('Error loading position data:', error);
        showErrorMessage('Error loading position data. Please check your connection and try again.');
    } finally {
        hideLoadingState();
    }
}

// Show loading state
function showLoadingState() {
    const votingCards = document.getElementById('votingCards');
    votingCards.innerHTML = `
        <div class="voting-card active">
            <div class="text-center py-5">
                <div class="loading-spinner mb-3"></div>
                <p class="text-muted">Loading position data...</p>
            </div>
        </div>
    `;
}

// Hide loading state
function hideLoadingState() {
    // Loading state will be replaced by the actual voting card
}

// Show error message
function showErrorMessage(message) {
    const votingCards = document.getElementById('votingCards');
    votingCards.innerHTML = `
        <div class="voting-card active">
            <div class="text-center py-5">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn btn-primary" onclick="retryLoadPosition()">
                    <i class="fas fa-redo me-2"></i>Try Again
                </button>
            </div>
        </div>
    `;
}

// Retry loading current position
function retryLoadPosition() {
    loadPositionData(currentPositionIndex);
}

function generateVotingCard(data, index) {
    const votingCards = document.getElementById('votingCards');
    
    // Hide all existing cards
    const existingCards = votingCards.querySelectorAll('.voting-card');
    existingCards.forEach(card => card.classList.remove('active'));
    
    const cardDiv = document.createElement('div');
    cardDiv.className = 'voting-card active';
    cardDiv.id = `voting-card-${index}`;
    
    let candidatesHtml = '';
    data.candidates.forEach(candidate => {
        const photoSrc = candidate.candidate_photo ?
            `../uploads/candidates/${candidate.candidate_photo}` : '';

        candidatesHtml += `
            <div class="candidate-card" onclick="selectCandidate(${candidate.candidate_id}, ${data.position.position_id})">
                <div class="selection-indicator">
                    <i class="fas fa-check"></i>
                </div>
                <div class="candidate-photo">
                    ${photoSrc ?
                        `<img src="${photoSrc}" alt="${candidate.first_name} ${candidate.last_name}" onerror="this.parentElement.innerHTML='<div class=\\'no-photo\\'><i class=\\'fas fa-user\\'></i></div>'">` :
                        '<div class="no-photo"><i class="fas fa-user"></i></div>'
                    }
                </div>
                <div class="candidate-name">
                    ${candidate.first_name} ${candidate.last_name}
                </div>
            </div>
        `;
    });
    
    cardDiv.innerHTML = `
        <div class="position-header">
            <h2 class="position-title">${data.position.title}</h2>
            <p class="position-description">${data.position.description || 'Select your preferred candidate for this position'}</p>
        </div>
        
        <div class="candidates-grid">
            ${candidatesHtml}
        </div>
        
        <div class="voting-actions">
            <button type="button" class="btn btn-outline-secondary btn-modern" onclick="previousPosition()" ${index === 0 ? 'style="visibility: hidden;"' : ''}>
                <i class="fas fa-arrow-left me-2"></i>Previous
            </button>
            <div class="text-center">
                <span class="text-muted">Position ${index + 1} of ${positions.length}</span>
            </div>
            <button type="button" class="btn btn-primary-modern" onclick="nextPosition()" id="nextBtn-${index}">
                ${index === positions.length - 1 ? '<i class="fas fa-eye me-2"></i>Review Votes' : '<i class="fas fa-arrow-right me-2"></i>Next'}
            </button>
        </div>
    `;
    
    votingCards.appendChild(cardDiv);
    
    // Restore previous selection if exists
    if (userVotes[data.position.position_id]) {
        const selectedCard = cardDiv.querySelector(`[onclick*="${userVotes[data.position.position_id]}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
    }
}

function selectCandidate(candidateId, positionId) {
    // Remove selection from all candidates in current position
    const currentCard = document.querySelector('.voting-card.active');
    const candidateCards = currentCard.querySelectorAll('.candidate-card');
    candidateCards.forEach(card => card.classList.remove('selected'));

    // Add selection to clicked candidate with animation
    const selectedCard = event.currentTarget;
    selectedCard.classList.add('selected');

    // Add success animation to selection indicator
    const indicator = selectedCard.querySelector('.selection-indicator');
    if (indicator) {
        indicator.classList.add('success-checkmark');
        setTimeout(() => {
            indicator.classList.remove('success-checkmark');
        }, 600);
    }

    // Store the vote
    userVotes[positionId] = candidateId;

    // Update progress
    updateStepStatus(currentPositionIndex, 'completed');
    updateProgressBar();

    // Enable next button if it was disabled
    const nextBtn = document.getElementById(`nextBtn-${currentPositionIndex}`);
    if (nextBtn) {
        nextBtn.disabled = false;
        nextBtn.classList.remove('btn-outline-secondary');
        nextBtn.classList.add('btn-primary-modern');
    }

    // Provide haptic feedback on mobile devices
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

function nextPosition() {
    const currentPositionId = positions[currentPositionIndex].position_id;

    if (!userVotes[currentPositionId]) {
        showNotification('Please select a candidate before proceeding to the next position.', 'warning');
        return;
    }

    currentPositionIndex++;

    if (currentPositionIndex >= positions.length) {
        showReviewSection();
    } else {
        updateStepStatus(currentPositionIndex, 'active');
        updateProgressBar();
        loadPositionData(currentPositionIndex);
    }
}

// Show notification instead of alert
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.voting-notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className = `alert alert-${type} voting-notification`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
        animation: slideInRight 0.3s ease-in-out;
    `;
    notification.innerHTML = `
        <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease-in-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function previousPosition() {
    if (currentPositionIndex > 0) {
        currentPositionIndex--;
        updateStepStatus(currentPositionIndex, 'active');
        updateStepStatus(currentPositionIndex + 1, '');
        updateProgressBar();
        
        // Show existing card
        const votingCards = document.getElementById('votingCards');
        const existingCards = votingCards.querySelectorAll('.voting-card');
        existingCards.forEach(card => card.classList.remove('active'));
        
        const targetCard = document.getElementById(`voting-card-${currentPositionIndex}`);
        if (targetCard) {
            targetCard.classList.add('active');
        }
    }
}

function updateStepStatus(index, status) {
    const stepCircle = document.getElementById(`step-${index}`);
    const stepLabel = document.getElementById(`label-${index}`);
    
    if (stepCircle && stepLabel) {
        stepCircle.className = `step-circle ${status}`;
        stepLabel.className = `step-label ${status}`;
    }
    
    // Update progress line
    if (index > 0) {
        const progressLine = document.getElementById(`line-${index - 1}`);
        if (progressLine && status === 'completed') {
            progressLine.classList.add('completed');
        }
    }
}

function updateProgressBar() {
    const progressBar = document.getElementById('progressBar');
    const completedVotes = Object.keys(userVotes).length;
    const totalPositions = positions.length;
    const percentage = (completedVotes / totalPositions) * 100;
    
    progressBar.style.width = percentage + '%';
}

function showReviewSection() {
    // Hide voting cards
    const votingCards = document.getElementById('votingCards');
    votingCards.style.display = 'none';
    
    // Show review section
    const reviewSection = document.getElementById('reviewSection');
    reviewSection.classList.add('active');
    
    // Update review step
    updateStepStatus('review', 'active');
    
    // Generate vote summary
    generateVoteSummary();
}

function generateVoteSummary() {
    const voteSummary = document.getElementById('voteSummary');
    let summaryHtml = '<div class="vote-summary">';
    
    positions.forEach(position => {
        const candidateId = userVotes[position.position_id];
        if (candidateId && positionData[position.position_id]) {
            const candidate = positionData[position.position_id].candidates.find(c => c.candidate_id == candidateId);
            if (candidate) {
                summaryHtml += `
                    <div class="vote-item">
                        <div class="position-name">${position.title}</div>
                        <div class="candidate-selected">${candidate.first_name} ${candidate.last_name}</div>
                    </div>
                `;
            }
        }
    });
    
    summaryHtml += '</div>';
    voteSummary.innerHTML = summaryHtml;
}

function goBackToVoting() {
    // Show voting cards
    const votingCards = document.getElementById('votingCards');
    votingCards.style.display = 'block';
    
    // Hide review section
    const reviewSection = document.getElementById('reviewSection');
    reviewSection.classList.remove('active');
    
    // Go back to last position
    currentPositionIndex = positions.length - 1;
    updateStepStatus('review', '');
    updateStepStatus(currentPositionIndex, 'active');
    
    // Show last voting card
    const existingCards = votingCards.querySelectorAll('.voting-card');
    existingCards.forEach(card => card.classList.remove('active'));
    
    const targetCard = document.getElementById(`voting-card-${currentPositionIndex}`);
    if (targetCard) {
        targetCard.classList.add('active');
    }
}

async function submitAllVotes() {
    if (Object.keys(userVotes).length !== positions.length) {
        showErrorMessage('Please complete voting for all positions before submitting.');
        return;
    }

    // Show confirmation dialog
    const confirmed = await showConfirmationDialog();
    if (!confirmed) {
        return;
    }

    // Prepare votes data
    const votesData = [];
    for (const [positionId, candidateId] of Object.entries(userVotes)) {
        votesData.push({
            position_id: parseInt(positionId),
            candidate_id: parseInt(candidateId)
        });
    }

    // Show loading state
    const submitBtn = document.querySelector('.btn-success-modern');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Submitting Votes...';

    try {
        const formData = new FormData();
        formData.append('submit_votes', '1');
        formData.append('votes', JSON.stringify(votesData));

        const response = await fetch('sequential_vote.php?election_id=<?php echo $electionId; ?>', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // Show success message
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Votes Submitted!';
            submitBtn.classList.remove('btn-success-modern');
            submitBtn.classList.add('btn-success');

            // Redirect after a short delay
            setTimeout(() => {
                window.location.href = 'election_detail.php?id=<?php echo $electionId; ?>';
            }, 2000);
        } else {
            throw new Error(result.error || 'Unknown error occurred');
        }

    } catch (error) {
        console.error('Error submitting votes:', error);
        showErrorMessage('Error submitting votes: ' + error.message + '. Please try again.');

        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Show confirmation dialog
function showConfirmationDialog() {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Confirm Vote Submission
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p><strong>Are you sure you want to submit your votes?</strong></p>
                        <p class="text-muted">Once submitted, your votes cannot be changed. Please review your selections one more time.</p>
                        <div class="alert alert-info">
                            <small><i class="fas fa-info-circle me-1"></i>You are submitting votes for ${Object.keys(userVotes).length} position(s).</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmSubmit">Yes, Submit Votes</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();

        modal.querySelector('#confirmSubmit').addEventListener('click', () => {
            bootstrapModal.hide();
            resolve(true);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
            resolve(false);
        });
    });
}
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
