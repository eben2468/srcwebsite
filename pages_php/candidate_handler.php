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

// Check if action and candidate ID are provided
if (!isset($_GET['action']) || empty($_GET['action']) || !isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Action and candidate ID are required.";
    header("Location: elections.php");
    exit();
}

$action = $_GET['action'];
$candidateId = intval($_GET['id']);

// Get candidate details
$sql = "SELECT c.*, p.title as position_title, e.title as election_title, e.election_id,
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

// Check permissions for different actions
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin();
$isOwner = ($userId == $candidate['user_id']);

switch ($action) {
    case 'approve':
        // Only super admins and admins can approve candidates
        if (!$isSuperAdmin && !$isAdmin) {
            $_SESSION['error'] = "You don't have permission to approve candidates.";
            header("Location: election_detail.php?id=" . $candidate['election_id']);
            exit();
        }

        // Update candidate status to approved
        $sql = "UPDATE election_candidates SET status = 'approved' WHERE candidate_id = ?";
        $result = update($sql, [$candidateId]);

        if ($result) {
            $_SESSION['success'] = $candidate['first_name'] . " " . $candidate['last_name'] . " has been approved as a candidate for " . $candidate['position_title'] . ".";
        } else {
            $_SESSION['error'] = "Failed to approve candidate. Please try again.";
        }

        header("Location: election_detail.php?id=" . $candidate['election_id']);
        exit();

    case 'reject':
        // Only super admins and admins can reject candidates
        if (!$isSuperAdmin && !$isAdmin) {
            $_SESSION['error'] = "You don't have permission to reject candidates.";
            header("Location: election_detail.php?id=" . $candidate['election_id']);
            exit();
        }

        // Update candidate status to rejected
        $sql = "UPDATE election_candidates SET status = 'rejected' WHERE candidate_id = ?";
        $result = update($sql, [$candidateId]);

        if ($result) {
            $_SESSION['success'] = "Candidate application has been rejected.";
        } else {
            $_SESSION['error'] = "Failed to reject candidate. Please try again.";
        }

        header("Location: election_detail.php?id=" . $candidate['election_id']);
        exit();

    case 'withdraw':
        // Only the candidate themselves can withdraw
        if (!$isOwner && !$isSuperAdmin && !$isAdmin) {
            $_SESSION['error'] = "You don't have permission to withdraw this candidacy.";
            header("Location: election_detail.php?id=" . $candidate['election_id']);
            exit();
        }

        // Update candidate status to withdrawn
        $sql = "UPDATE election_candidates SET status = 'withdrawn' WHERE candidate_id = ?";
        $result = update($sql, [$candidateId]);

        if ($result) {
            $_SESSION['success'] = "You have successfully withdrawn your candidacy for " . $candidate['position_title'] . ".";
        } else {
            $_SESSION['error'] = "Failed to withdraw candidacy. Please try again.";
        }

        header("Location: election_detail.php?id=" . $candidate['election_id']);
        exit();

    case 'view':
        // Redirect to candidate detail page
        header("Location: candidate_detail.php?id=" . $candidateId);
        exit();

    default:
        $_SESSION['error'] = "Invalid action.";
        header("Location: election_detail.php?id=" . $candidate['election_id']);
        exit();
}
?>
