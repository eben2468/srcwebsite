<?php
// Include authentication file
require_once '../auth_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageMinutes = $isAdmin || $isMember; // Allow both admins and members to manage minutes

// Set page title
$pageTitle = "Meeting Minutes";

// Build query for minutes
$sql = "SELECT m.*, u.first_name, u.last_name 
        FROM minutes m 
        LEFT JOIN users u ON m.created_by = u.user_id";
$params = [];
$whereAdded = false;

// Apply committee filter if provided
if (isset($_GET['committee']) && !empty($_GET['committee'])) {
    $sql .= " WHERE m.committee = ?";
    $params[] = $_GET['committee'];
    $whereAdded = true;
}

// Apply search filter if provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    if ($whereAdded) {
        $sql .= " AND (m.title LIKE ? OR m.summary LIKE ?)";
    } else {
        $sql .= " WHERE (m.title LIKE ? OR m.summary LIKE ?)";
        $whereAdded = true;
    }
    $params[] = $search;
    $params[] = $search;
}

// Order by meeting date (most recent first)
$sql .= " ORDER BY m.meeting_date DESC";

// Fetch minutes
$minutes = fetchAll($sql, $params);

// Get unique committees for dropdown
$committeeSql = "SELECT DISTINCT committee FROM minutes ORDER BY committee";
$committeesResult = fetchAll($committeeSql);
$committees = array_column($committeesResult, 'committee');

// Include header
require_once 'includes/header.php';

// Custom styles for this page
?>
<style>
    .minutes-card {
        transition: transform 0.3s;
    }
    .minutes-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .status-badge-approved {
        background-color: #28a745;
    }
    .status-badge-draft {
        background-color: #ffc107;
    }
    pre {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        white-space: pre-wrap;
        font-family: inherit;
    }
</style>

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

    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageIcon = "fa-clipboard";
    $actions = [];
    
    if ($canManageMinutes) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'New Minutes',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle="modal" data-bs-target="#addMinutesModal"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>
                
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="content-card animate-fadeIn">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-info-circle me-2"></i> SRC Meeting Minutes Repository</h3>
                </div>
                <div class="content-card-body">
                    <p>Access and review all SRC meeting minutes. Minutes are available once they have been approved.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="content-card animate-fadeIn">
        <div class="content-card-header">
            <h3 class="content-card-title"><i class="fas fa-filter me-2"></i> Filter Minutes</h3>
        </div>
        <div class="content-card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" placeholder="Search minutes..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-5">
                    <select class="form-select" id="committee" name="committee">
                        <option value="">All Committees</option>
                        <?php foreach ($committees as $committee): ?>
                        <option value="<?php echo htmlspecialchars($committee); ?>" <?php echo isset($_GET['committee']) && $_GET['committee'] === $committee ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($committee); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
                <?php if (isset($_GET['committee']) || isset($_GET['search'])): ?>
                <div class="col-12 mt-2">
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Minutes Cards -->
    <div class="row mt-4">
        <?php if (empty($minutes)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No meeting minutes found. 
                <?php if (isset($_GET['committee']) || isset($_GET['search'])): ?>
                Try changing your search criteria.
                <?php elseif (isAdmin()): ?>
                Click the "New Minutes" button to add meeting minutes.
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <?php 
            $cardIndex = 0;
            foreach ($minutes as $minute): 
                $cardIndex++;
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card minutes-card animate-fadeIn" style="--card-index: <?php echo $cardIndex; ?>">
                    <?php if ($minute['status'] === 'Approved'): ?>
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i> Approved
                    </div>
                    <?php else: ?>
                    <div class="card-header bg-warning">
                        <i class="fas fa-clock me-2"></i> Draft
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($minute['title']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('M d, Y', strtotime($minute['meeting_date'])); ?></span>
                        </h6>
                        <div class="badge-container">
                            <span class="badge bg-info text-white">
                                <?php echo htmlspecialchars($minute['committee']); ?>
                            </span>
                            <?php if (!empty($minute['file_path'])): ?>
                            <span class="badge bg-secondary">
                                <i class="fas fa-file-alt me-1"></i> Attachment
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars(substr($minute['summary'], 0, 100)) . (strlen($minute['summary']) > 100 ? '...' : ''); ?></p>
                        <div class="d-grid">
                            <a href="minutes_detail.php?id=<?php echo $minute['minutes_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-file-alt"></i> View Minutes
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted d-flex justify-content-between align-items-center">
                        <small><i class="fas fa-calendar-day me-1"></i> Next Meeting: <?php echo date('M d, Y', strtotime($minute['next_meeting_date'])); ?></small>
                        <?php if ($canManageMinutes): ?>
                        <div>
                            <a href="minutes_edit.php?id=<?php echo $minute['minutes_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="minutes_handler.php?action=delete&id=<?php echo $minute['minutes_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete these minutes?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Minutes Modal -->
    <?php if ($canManageMinutes): ?>
    <div class="modal fade" id="addMinutesModal" tabindex="-1" aria-labelledby="addMinutesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMinutesModalLabel">Add New Meeting Minutes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="minutes_handler.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Meeting Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="meeting_date" class="form-label">Meeting Date</label>
                                <input type="date" class="form-control" id="meeting_date" name="meeting_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="committee" class="form-label">Committee</label>
                                <input type="text" class="form-control" id="committee" name="committee" list="committee-list" required>
                                <datalist id="committee-list">
                                    <?php foreach ($committees as $committee): ?>
                                    <option value="<?php echo htmlspecialchars($committee); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="summary" class="form-label">Summary</label>
                            <textarea class="form-control" id="summary" name="summary" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="attendees" class="form-label">Attendees</label>
                            <textarea class="form-control" id="attendees" name="attendees" rows="2" placeholder="Enter names separated by commas"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="minutes_content" class="form-label">Minutes Content</label>
                            <textarea class="form-control" id="minutes_content" name="minutes_content" rows="6" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="next_meeting_date" class="form-label">Next Meeting Date</label>
                                <input type="date" class="form-control" id="next_meeting_date" name="next_meeting_date">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Draft">Draft</option>
                                    <option value="Approved">Approved</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="minutes_file" class="form-label">Attach File (Optional)</label>
                            <input type="file" class="form-control" id="minutes_file" name="minutes_file">
                            <div class="form-text">Attach a PDF or document file if available.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_minutes">Save Minutes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 