<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user has permission to edit elections (super admin only)
if (!canManageElections()) {
    $_SESSION['error'] = "You don't have permission to edit elections.";
    header("Location: elections.php");
    exit();
}

// Check if election ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Election ID is required.";
    header("Location: elections.php");
    exit();
}

$electionId = intval($_GET['id']);

// Get election details
$sql = "SELECT * FROM elections WHERE election_id = ?";
$election = fetchOne($sql, [$electionId]);

if (!$election) {
    $_SESSION['error'] = "Election not found.";
    header("Location: elections.php");
    exit();
}

// Get election positions
$sql = "SELECT * FROM election_positions WHERE election_id = ? ORDER BY title";
$positions = fetchAll($sql, [$electionId]);

// Format dates for form
$startDate = date('Y-m-d', strtotime($election['start_date']));
$endDate = date('Y-m-d', strtotime($election['end_date']));

// Map status from database to display format
$statusMap = [
    'nomination' => 'Nominations Open',
    'pending' => 'Pending Voting',
    'upcoming' => 'Upcoming',
    'active' => 'Active',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

$displayStatus = $statusMap[$election['status']] ?? ucfirst($election['status']);

// Set page title
$pageTitle = "Edit Election - " . $election['title'] . " - SRC Management System";

// Include header
require_once 'includes/header.php';
?>



<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = "Edit Election";
    $pageIcon = "fa-edit";
    $pageDescription = "Modify election details and settings";
    $actions = [
        [
            'url' => 'election_detail.php?id=' . $electionId,
            'icon' => 'fa-eye',
            'text' => 'View Election',
            'class' => 'btn-outline-light'
        ],
        [
            'url' => 'elections.php',
            'icon' => 'fa-arrow-left',
            'text' => 'Back to Elections',
            'class' => 'btn-outline-light'
        ]
    ];

    // Include modern page header
    include 'includes/modern_page_header.php';
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

        <div class="header-actions">
            <div class="header-date">
                <i class="fas fa-calendar"></i> <?php echo $startDate; ?> - <?php echo $endDate; ?>
            </div>
            <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-icon">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>
    </div>
    
    <!-- Breadcrumb navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="elections.php">Elections</a></li>
            <li class="breadcrumb-item"><a href="election_detail.php?id=<?php echo $electionId; ?>">Election Details</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Election</li>
        </ol>
    </nav>

    <!-- Edit Election Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Edit Election</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="election_handler.php" id="editElectionForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="election_id" value="<?php echo $electionId; ?>">
                <input type="hidden" name="deleted_position_ids" id="deleted_position_ids" value="">
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="election-title" class="form-label">Election Title</label>
                        <input type="text" class="form-control" id="election-title" name="election_title" 
                               value="<?php echo htmlspecialchars($election['title']); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="election-start-date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="election-start-date" name="election_start_date" 
                               value="<?php echo $startDate; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="election-end-date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="election-end-date" name="election_end_date" 
                               value="<?php echo $endDate; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="election-status" class="form-label">Status</label>
                        <select class="form-select" id="election-status" name="election_status" required>
                            <option value="Planning" <?php echo $displayStatus === 'Upcoming' && strtotime($election['start_date']) > time() ? 'selected' : ''; ?>>Planning</option>
                            <option value="Nominations Open" <?php echo $displayStatus === 'Nominations Open' ? 'selected' : ''; ?>>Nominations Open</option>
                            <option value="Pending Voting" <?php echo $displayStatus === 'Pending Voting' ? 'selected' : ''; ?>>Pending Voting</option>
                            <option value="Upcoming" <?php echo $displayStatus === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="Active" <?php echo $displayStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Completed" <?php echo $displayStatus === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $displayStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="election-description" class="form-label">Description</label>
                    <textarea class="form-control" id="election-description" name="election_description" rows="4"><?php echo htmlspecialchars($election['description']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Existing Positions</label>
                    <div id="existing-positions">
                        <?php if (empty($positions)): ?>
                            <p class="text-muted">No positions defined for this election.</p>
                        <?php else: ?>
                            <?php foreach ($positions as $position): ?>
                                <div class="row mb-2 existing-position-input" data-position-id="<?php echo $position['position_id']; ?>">
                                    <div class="col-md-6">
                                        <input type="hidden" name="existing_position_ids[]" value="<?php echo $position['position_id']; ?>">
                                        <input type="text" class="form-control" name="existing_positions[]" 
                                               placeholder="Position Title" value="<?php echo htmlspecialchars($position['title']); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" class="form-control" name="existing_seats[]" 
                                               placeholder="Number of Seats" min="1" value="<?php echo $position['seats']; ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-existing-position">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Add New Positions</label>
                    <div id="new-positions">
                        <div class="row mb-2 new-position-input">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="new_positions[]" placeholder="Position Title">
                            </div>
                            <div class="col-md-4">
                                <input type="number" class="form-control" name="new_seats[]" placeholder="Number of Seats" min="1" value="1">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-secondary w-100 add-new-position">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Update Election</button>
                        <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle adding new positions
        const newPositionsContainer = document.getElementById('new-positions');
        const addNewPositionButtons = document.querySelectorAll('.add-new-position');
        
        addNewPositionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const newPositionRow = document.createElement('div');
                newPositionRow.className = 'row mb-2 new-position-input';
                newPositionRow.innerHTML = `
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="new_positions[]" placeholder="Position Title" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" class="form-control" name="new_seats[]" placeholder="Number of Seats" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger w-100 remove-new-position">
                            <i class="fas fa-minus"></i> Remove
                        </button>
                    </div>
                `;
                newPositionsContainer.appendChild(newPositionRow);
                
                // Add event listener to the new remove button
                const removeBtn = newPositionRow.querySelector('.remove-new-position');
                removeBtn.addEventListener('click', function() {
                    newPositionRow.remove();
                });
            });
        });
        
        // Handle deleting existing positions
        const existingPositionsContainer = document.getElementById('existing-positions');
        const removeExistingPositionButtons = document.querySelectorAll('.remove-existing-position');
        const deletedPositionIdsInput = document.getElementById('deleted_position_ids');
        const deletedPositionIds = [];
        
        removeExistingPositionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const positionRow = this.closest('.existing-position-input');
                const positionId = positionRow.dataset.positionId;
                
                if (positionId) {
                    deletedPositionIds.push(positionId);
                    deletedPositionIdsInput.value = deletedPositionIds.join(',');
                }
                
                positionRow.remove();
            });
        });
        
        // Form validation
        const form = document.getElementById('editElectionForm');
        form.addEventListener('submit', function(event) {
            const startDate = new Date(document.getElementById('election-start-date').value);
            const endDate = new Date(document.getElementById('election-end-date').value);
            
            if (endDate <= startDate) {
                event.preventDefault();
                alert('End date must be after start date.');
                return false;
            }
            
            return true;
        });
    });
</script>

<style>
/* Mobile Full-Width Optimization for Election Edit Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure page header has border-radius on mobile */
    .header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 
