<?php
/**
 * Manifesto Download Handler
 * Handles downloading of candidate manifesto files
 */

// Include necessary files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to download manifestos.";
    header("Location: login.php");
    exit();
}

// Check if candidate ID is provided
if (!isset($_GET['candidate_id']) || empty($_GET['candidate_id'])) {
    $_SESSION['error'] = "Candidate ID is required.";
    header("Location: elections.php");
    exit();
}

$candidateId = intval($_GET['candidate_id']);

// Get candidate details and manifesto file
$sql = "SELECT c.manifesto_file, c.candidate_id, u.first_name, u.last_name, p.title as position_title, e.title as election_title
        FROM election_candidates c
        JOIN users u ON c.user_id = u.user_id
        JOIN election_positions p ON c.position_id = p.position_id
        JOIN elections e ON c.election_id = e.election_id
        WHERE c.candidate_id = ?";

$candidate = fetchOne($sql, [$candidateId]);

if (!$candidate) {
    $_SESSION['error'] = "Candidate not found.";
    header("Location: elections.php");
    exit();
}

// Check if manifesto file exists
if (empty($candidate['manifesto_file'])) {
    $_SESSION['error'] = "No manifesto file available for this candidate.";
    header("Location: candidate_detail.php?id=" . $candidateId);
    exit();
}

// Define the uploads directory
$uploadsDir = '../uploads/manifestos';
$filePath = $uploadsDir . '/' . $candidate['manifesto_file'];

// Check if file exists on server
if (!file_exists($filePath)) {
    $_SESSION['error'] = "Manifesto file not found on server.";
    header("Location: candidate_detail.php?id=" . $candidateId);
    exit();
}

// Get file information
$fileInfo = pathinfo($filePath);
$fileName = $candidate['first_name'] . '_' . $candidate['last_name'] . '_' . $candidate['position_title'] . '_Manifesto.' . $fileInfo['extension'];

// Clean filename for download
$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffer to prevent corruption
if (ob_get_level()) {
    ob_end_clean();
}

// Output file
readfile($filePath);
exit();
?>
