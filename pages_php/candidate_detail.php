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

// Check if candidate ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Candidate ID is required.";
    header("Location: elections.php");
    exit();
}

$candidateId = intval($_GET['id']);

// Get candidate details with related information
$sql = "SELECT c.*, p.title as position_title, p.description as position_description, p.seats,
        e.title as election_title, e.status as election_status, e.election_id, e.start_date, e.end_date,
        u.first_name, u.last_name, u.email, u.user_id
        FROM election_candidates c
        JOIN election_positions p ON c.position_id = p.position_id
        JOIN elections e ON c.election_id = e.election_id
        JOIN users u ON c.user_id = u.user_id
        WHERE c.candidate_id = ?";
$candidate = fetchOne($sql, [$candidateId]);

if (!$candidate) {
    $_SESSION['error'] = "Candidate not found.";
    header("Location: elections.php");
    exit();
}

// Get current user info
$user = getCurrentUser();
$userId = $user['user_id'];
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$isOwner = ($userId == $candidate['user_id']);

// Format dates for display
$startDate = date('F j, Y', strtotime($candidate['start_date']));
$endDate = date('F j, Y', strtotime($candidate['end_date']));

// Set page title
$pageTitle = "Candidate Details - " . $candidate['first_name'] . " " . $candidate['last_name'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = $candidate['first_name'] . " " . $candidate['last_name'];
    $pageIcon = "fa-user-tie";
    $pageDescription = "Candidate for " . $candidate['position_title'] . " - " . $candidate['election_title'];
    $actions = [];

    // Back button
    $actions[] = [
        'url' => 'election_detail.php?id=' . $candidate['election_id'],
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Election',
        'class' => 'btn-outline-light'
    ];

    // Owner actions
    if ($isOwner && $candidate['status'] !== 'withdrawn') {
        $actions[] = [
            'url' => 'candidate_handler.php?action=withdraw&id=' . $candidateId,
            'icon' => 'fa-times-circle',
            'text' => 'Withdraw Candidacy',
            'class' => 'btn-outline-light',
            'onclick' => "return confirm('Are you sure you want to withdraw your candidacy?')"
        ];
    }

    // Admin and Super Admin actions
    if ($isSuperAdmin || $isAdmin) {
        if ($candidate['status'] === 'pending') {
            $actions[] = [
                'url' => 'candidate_handler.php?action=approve&id=' . $candidateId,
                'icon' => 'fa-check',
                'text' => 'Approve Candidate',
                'class' => 'btn-outline-light'
            ];
            $actions[] = [
                'url' => 'candidate_handler.php?action=reject&id=' . $candidateId,
                'icon' => 'fa-times',
                'text' => 'Reject Candidate',
                'class' => 'btn-outline-light',
                'onclick' => "return confirm('Are you sure you want to reject this candidate?')"
            ];
        }
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
        </div>
    </div>

    <!-- Breadcrumb navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
            <li class="breadcrumb-item"><a href="election_detail.php?id=<?php echo $candidate['election_id']; ?>"><?php echo htmlspecialchars($candidate['election_title']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Candidate Details</li>
        </ol>
    </nav>

    <!-- Candidate Details -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Candidate Information</h5>
                </div>
                <div class="card-body text-center">
                    <div class="candidate-photo mb-3">
                        <?php if (!empty($candidate['candidate_photo'])): ?>
                            <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['candidate_photo']); ?>" alt="<?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?>" class="img-fluid rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="profile-placeholder">
                                <i class="fas fa-user-circle" style="font-size: 150px; color: #ccc;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-0"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . $candidate['last_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($candidate['email']); ?></p>

                    <div class="mt-3">
                        <span class="badge bg-<?php
                            echo $candidate['status'] === 'approved' ? 'success' :
                                ($candidate['status'] === 'pending' ? 'warning' :
                                    ($candidate['status'] === 'withdrawn' ? 'secondary' : 'danger'));
                        ?> rounded-pill px-3 py-2">
                            <?php echo ucfirst(htmlspecialchars($candidate['status'])); ?>
                        </span>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Position:</strong>
                        <span><?php echo htmlspecialchars($candidate['position_title']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Election:</strong>
                        <span><?php echo htmlspecialchars($candidate['election_title']); ?></span>
                    </li>
                    <?php if ($isAdmin || $candidate['election_status'] === 'completed'): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Votes:</strong>
                        <span class="badge bg-primary rounded-pill"><?php echo $candidate['votes']; ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Seats Available:</strong>
                        <span><?php echo $candidate['seats']; ?></span>
                    </li>
                </ul>
                <?php if (($isSuperAdmin || $isAdmin) && $candidate['status'] === 'pending'): ?>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <a href="candidate_handler.php?action=approve&id=<?php echo $candidateId; ?>" class="btn btn-success">
                                <i class="fas fa-check me-2"></i> Approve Candidate
                            </a>
                            <a href="candidate_handler.php?action=reject&id=<?php echo $candidateId; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this candidate?')">
                                <i class="fas fa-times me-2"></i> Reject Candidate
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Manifesto / Campaign Statement</h5>
                    <?php if (!empty($candidate['manifesto_file'])): ?>
                        <a href="manifesto_download.php?candidate_id=<?php echo $candidateId; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i>Download Manifesto
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($candidate['manifesto_file'])): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-file-alt me-2"></i>
                            <strong>Manifesto Document:</strong> <?php echo htmlspecialchars($candidate['manifesto_file']); ?>
                            <br><small class="text-muted">Click the download button above to view the full manifesto document.</small>
                        </div>
                        <?php if (!empty($candidate['manifesto']) && !str_contains($candidate['manifesto'], '[Manifesto uploaded as file:')): ?>
                            <div class="manifesto-content mt-3">
                                <h6>Additional Statement:</h6>
                                <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif (!empty($candidate['manifesto'])): ?>
                        <div class="manifesto-content">
                            <?php echo nl2br(htmlspecialchars($candidate['manifesto'])); ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No manifesto has been provided by this candidate.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($candidate['election_status'] === 'active' && $candidate['status'] === 'approved'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Vote for this Candidate</h5>
                    </div>
                    <div class="card-body text-center">
                        <p>Election is currently active. You can vote for this candidate for the position of <?php echo htmlspecialchars($candidate['position_title']); ?>.</p>
                        <a href="vote.php?election_id=<?php echo $candidate['election_id']; ?>&position_id=<?php echo $candidate['position_id']; ?>&candidate_id=<?php echo $candidateId; ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-vote-yea me-2"></i> Cast Your Vote
                        </a>
                        <p class="text-muted mt-2"><small>Note: You can only vote once per position. Your vote cannot be changed once submitted.</small></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.manifesto-content {
    white-space: pre-line;
    line-height: 1.6;
    font-size: 1.05rem;
}

.candidate-photo {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>
