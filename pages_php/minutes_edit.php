<?php
// Include authentication file
require_once '../auth_functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = "You don't have permission to edit minutes.";
    header("Location: minutes.php");
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No minutes ID provided.";
    header("Location: minutes.php");
    exit();
}

$minutesId = intval($_GET['id']);

// Get minutes data
$sql = "SELECT * FROM minutes WHERE minutes_id = ?";
$minutes = fetchOne($sql, [$minutesId]);

// Check if minutes exists
if (!$minutes) {
    $_SESSION['error'] = "Minutes not found.";
    header("Location: minutes.php");
    exit();
}

// Get unique committees for dropdown
$committeeSql = "SELECT DISTINCT committee FROM minutes ORDER BY committee";
$committeesResult = fetchAll($committeeSql);
$committees = array_column($committeesResult, 'committee');

// Set page title
$pageTitle = "Edit Minutes: " . $minutes['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="minutes_detail.php?id=<?php echo $minutes['minutes_id']; ?>" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Minutes Detail
            </a>
            <h1 class="h3 mb-0">Edit Meeting Minutes</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="minutes_handler.php" enctype="multipart/form-data">
                <input type="hidden" name="edit_minutes" value="1">
                <input type="hidden" name="minutes_id" value="<?php echo $minutes['minutes_id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label">Meeting Title</label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($minutes['title']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="meeting_date" class="form-label">Meeting Date</label>
                        <input type="date" class="form-control" id="meeting_date" name="meeting_date" required value="<?php echo $minutes['meeting_date']; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" required value="<?php echo htmlspecialchars($minutes['location']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="committee" class="form-label">Committee</label>
                        <select class="form-select" id="committee" name="committee" required>
                            <option value="">Select Committee</option>
                            <?php foreach ($committees as $committee): ?>
                                <option value="<?php echo htmlspecialchars($committee); ?>" <?php echo ($minutes['committee'] === $committee) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($committee); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">Add New Committee</option>
                        </select>
                        <div id="newCommitteeField" class="mt-2" style="display: none;">
                            <input type="text" class="form-control" id="new_committee" name="new_committee" placeholder="Enter new committee name">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="attendees" class="form-label">Attendees</label>
                    <textarea class="form-control" id="attendees" name="attendees" rows="2" required><?php echo htmlspecialchars($minutes['attendees']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="apologies" class="form-label">Apologies</label>
                    <textarea class="form-control" id="apologies" name="apologies" rows="1"><?php echo htmlspecialchars($minutes['apologies']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="agenda" class="form-label">Agenda</label>
                    <textarea class="form-control" id="agenda" name="agenda" rows="3" required><?php echo htmlspecialchars($minutes['agenda']); ?></textarea>
                    <small class="form-text">Enter each agenda item on a new line.</small>
                </div>
                
                <div class="mb-3">
                    <label for="summary" class="form-label">Summary</label>
                    <textarea class="form-control" id="summary" name="summary" rows="2" required><?php echo htmlspecialchars($minutes['summary']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="decisions" class="form-label">Decisions</label>
                    <textarea class="form-control" id="decisions" name="decisions" rows="3" required><?php echo htmlspecialchars($minutes['decisions']); ?></textarea>
                    <small class="form-text">Enter each decision on a new line.</small>
                </div>
                
                <div class="mb-3">
                    <label for="actions" class="form-label">Action Items</label>
                    <textarea class="form-control" id="actions" name="actions" rows="3" required><?php echo htmlspecialchars($minutes['actions']); ?></textarea>
                    <small class="form-text">Enter each action item on a new line.</small>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="next_meeting_date" class="form-label">Next Meeting Date</label>
                        <input type="date" class="form-control" id="next_meeting_date" name="next_meeting_date" required value="<?php echo $minutes['next_meeting_date']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Draft" <?php echo ($minutes['status'] === 'Draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="Approved" <?php echo ($minutes['status'] === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="minutes_file" class="form-label">Attach Minutes File (Optional)</label>
                    <input type="file" class="form-control" id="minutes_file" name="minutes_file">
                    <small class="form-text">
                        <?php if (!empty($minutes['file_path'])): ?>
                            Current file: <?php echo strtoupper($minutes['file_type']); ?> (<?php echo formatFileSize($minutes['file_size']); ?>)
                            <a href="minutes_handler.php?action=download&id=<?php echo $minutes['minutes_id']; ?>" class="ms-2">
                                <i class="fas fa-download"></i> Download
                            </a>
                        <?php else: ?>
                            No file currently attached. Upload one if needed.
                        <?php endif; ?>
                    </small>
                    <div class="form-text">Accepted file types: PDF, DOC, DOCX, TXT (Max size: 5MB)</div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="minutes_detail.php?id=<?php echo $minutes['minutes_id']; ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle new committee selection
    const committeeSelect = document.getElementById('committee');
    const newCommitteeField = document.getElementById('newCommitteeField');
    
    committeeSelect.addEventListener('change', function() {
        if (this.value === 'new') {
            newCommitteeField.style.display = 'block';
            document.getElementById('new_committee').setAttribute('required', 'required');
        } else {
            newCommitteeField.style.display = 'none';
            document.getElementById('new_committee').removeAttribute('required');
        }
    });
});
</script>

<?php
/**
 * Format file size for display
 *
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if (empty($bytes)) return 'N/A';
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

require_once 'includes/footer.php';
?> 