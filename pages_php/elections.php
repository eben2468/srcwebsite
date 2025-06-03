<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if elections feature is enabled
if (!isFeatureEnabled('enable_elections', true)) {
    $_SESSION['error'] = "The elections feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Set page title
$pageTitle = "Elections - SRC Management System";

// Include header
require_once 'includes/header.php';

// Get current user info
$user = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageElections = $isAdmin || $isMember; // Allow both admins and members to manage elections

// Add elections-page class to body
echo '<script>document.body.classList.add("elections-page");</script>';

// Check if user is trying to create new election without permission
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$canManageElections) {
    // Redirect non-privileged users back to the elections page
    header("Location: elections.php");
    exit();
}

// Build query for elections
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM election_positions WHERE election_id = e.election_id) AS position_count,
        (SELECT COUNT(*) FROM election_candidates c JOIN election_positions p ON c.position_id = p.position_id WHERE p.election_id = e.election_id) AS candidate_count
        FROM elections e";
$params = [];

// Apply search filter if provided
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " WHERE (e.title LIKE ? OR e.description LIKE ?)";
    $search = '%' . $_GET['search'] . '%';
    $params[] = $search;
    $params[] = $search;
    $whereAdded = true;
} else {
    $whereAdded = false;
}

// Apply status filter if provided
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $statusMap = [
        'Upcoming' => 'upcoming',
        'Active' => 'active',
        'Planning' => 'upcoming', // Treat Planning as Upcoming in the database
        'Completed' => 'completed',
        'Cancelled' => 'cancelled'
    ];
    
    $dbStatus = $statusMap[$_GET['status']] ?? strtolower($_GET['status']);
    
    if ($whereAdded) {
        $sql .= " AND e.status = ?";
    } else {
        $sql .= " WHERE e.status = ?";
        $whereAdded = true;
    }
    
    $params[] = $dbStatus;
}

// Apply month filter if provided
if (isset($_GET['month']) && !empty($_GET['month'])) {
    $monthParts = explode('-', $_GET['month']);
    if (count($monthParts) === 2) {
        $year = $monthParts[0];
        $month = $monthParts[1];
        
        if ($whereAdded) {
            $sql .= " AND (MONTH(e.start_date) = ? OR MONTH(e.end_date) = ?) AND (YEAR(e.start_date) = ? OR YEAR(e.end_date) = ?)";
        } else {
            $sql .= " WHERE (MONTH(e.start_date) = ? OR MONTH(e.end_date) = ?) AND (YEAR(e.start_date) = ? OR YEAR(e.end_date) = ?)";
            $whereAdded = true;
        }
        
        $params[] = $month;
        $params[] = $month;
        $params[] = $year;
        $params[] = $year;
    }
}

// Order by start date (most recent first)
$sql .= " ORDER BY e.start_date DESC";

// Fetch elections
$elections = fetchAll($sql, $params);

// Map database status to display format
$statusDisplayMap = [
    'upcoming' => 'Upcoming',
    'active' => 'Active',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

// Handle deletion if requested
if (isset($_GET['delete']) && $canManageElections) {
    $electionId = intval($_GET['delete']);
    header("Location: election_handler.php?action=delete&id=" . $electionId);
    exit();
}
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
            
    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageTitle = "Elections";
    $pageIcon = "fa-vote-yea";
    $actions = [];
    
    if ($canManageElections) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Create Election',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle="modal" data-bs-target="#createElectionModal"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?>

    <!-- Filters and Search -->
    <div class="content-card animate-fadeIn">
        <div class="content-card-header">
            <h3 class="content-card-title"><i class="fas fa-filter"></i> Filter Elections</h3>
        </div>
        <div class="content-card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Search elections..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="Upcoming" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="Active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Planning" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Planning') ? 'selected' : ''; ?>>Planning</option>
                            <option value="Completed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="month" class="form-control" name="month" placeholder="Filter by month" 
                               value="<?php echo isset($_GET['month']) ? htmlspecialchars($_GET['month']) : ''; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Elections Table -->
    <div class="content-card animate-fadeIn">
        <div class="content-card-header">
            <h3 class="content-card-title"><i class="fas fa-list"></i> All Elections</h3>
        </div>
        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Positions</th>
                            <th>Candidates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($elections)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No elections found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($elections as $election): ?>
                                <?php 
                                // Determine if it's in Planning status (upcoming but future start date)
                                $displayStatus = $statusDisplayMap[$election['status']] ?? ucfirst($election['status']);
                                if ($displayStatus === 'Upcoming' && strtotime($election['start_date']) > time()) {
                                    $displayStatus = 'Planning';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($election['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($election['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($election['end_date'])); ?></td>
                                    <td><?php echo $election['position_count']; ?></td>
                                    <td><?php echo $election['candidate_count']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $displayStatus === 'Upcoming' ? 'success' : 
                                                ($displayStatus === 'Active' ? 'primary' : 
                                                    ($displayStatus === 'Planning' ? 'warning' : 'secondary')); 
                                        ?>">
                                            <?php echo htmlspecialchars($displayStatus); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="election_detail.php?id=<?php echo $election['election_id']; ?>" class="btn btn-sm btn-primary btn-action">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($canManageElections): ?>
                                            <a href="election_edit.php?id=<?php echo $election['election_id']; ?>" class="btn btn-sm btn-secondary btn-action">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="elections.php?delete=<?php echo $election['election_id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Are you sure you want to delete this election?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Election Modal -->
<?php if (isAdmin()): ?>
<div class="modal fade" id="createElectionModal" tabindex="-1" aria-labelledby="createElectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createElectionModalLabel">Create New Election</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="election_handler.php" id="createElectionForm">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="election-title" class="form-label">Election Title</label>
                            <input type="text" class="form-control" id="election-title" name="election_title" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="election-start-date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="election-start-date" name="election_start_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="election-end-date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="election-end-date" name="election_end_date" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="election-status" class="form-label">Status</label>
                            <select class="form-select" id="election-status" name="election_status" required>
                                <option value="Planning">Planning</option>
                                <option value="Upcoming">Upcoming</option>
                                <option value="Active">Active</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="election-description" class="form-label">Description</label>
                        <textarea class="form-control" id="election-description" name="election_description" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Positions</label>
                        <div class="position-inputs">
                            <div class="row mb-2 position-input">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="positions[]" placeholder="Position Title" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="seats[]" placeholder="Number of Seats" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-secondary w-100 add-position">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Election</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Check for "new" action in URL to show create election modal
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
    const createElectionModal = new bootstrap.Modal(document.getElementById('createElectionModal'));
    createElectionModal.show();
    <?php endif; ?>
    
    // Add position functionality
    const addPositionBtn = document.querySelector('.add-position');
    const positionInputs = document.querySelector('.position-inputs');
    
    if(addPositionBtn) {
        addPositionBtn.addEventListener('click', function() {
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2 position-input';
            newRow.innerHTML = `
                <div class="col-md-6">
                    <input type="text" class="form-control" name="positions[]" placeholder="Position Title" required>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control" name="seats[]" placeholder="Number of Seats" min="1" value="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 remove-position">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            `;
            positionInputs.appendChild(newRow);
            
            // Add event listener to the new remove button
            const removeBtn = newRow.querySelector('.remove-position');
            removeBtn.addEventListener('click', function() {
                newRow.remove();
            });
        });
    }
    
    // Event delegation for remove position buttons
    if(positionInputs) {
        positionInputs.addEventListener('click', function(e) {
            if(e.target.closest('.remove-position')) {
                e.target.closest('.position-input').remove();
            }
        });
    }
    
    // Form validation
    const form = document.getElementById('createElectionForm');
    if(form) {
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
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>