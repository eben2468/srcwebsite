<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';

// Include auth_functions.php for canManageElections function
require_once __DIR__ . '/../includes/auth_functions.php';

// Check if user is logged in and can manage elections (super admin only)
if (!isLoggedIn() || !canManageElections()) {
    $_SESSION['error'] = "You must be a super administrator to access this page.";
    header("Location: dashboard.php");
    exit();
}

// Initialize variables
$diagnosticResults = [];
$fixResults = [];
$electionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Function to add diagnostic result
function addDiagnostic($type, $description, $status, $fixAction = '') {
    global $diagnosticResults;
    $diagnosticResults[] = [
        'type' => $type,
        'description' => $description,
        'status' => $status,
        'fix_action' => $fixAction
    ];
}

// Function to add fix result
function addFixResult($type, $description, $success) {
    global $fixResults;
    $fixResults[] = [
        'type' => $type,
        'description' => $description,
        'success' => $success
    ];
}

// Get election if ID is provided
$election = null;
if ($electionId > 0) {
    $sql = "SELECT * FROM elections WHERE election_id = ?";
    $election = fetchOne($sql, [$electionId]);
}

// Run fixes if requested
if (isset($_POST['fix']) && $election) {
    // Fix missing results_published column
    if (isset($_POST['fix_results_published'])) {
        try {
            // Check if column exists first
            $columnExists = false;
            $columnsQuery = "SHOW COLUMNS FROM elections LIKE 'results_published'";
            $columns = fetchAll($columnsQuery);
            $columnExists = !empty($columns);
            
            if (!$columnExists) {
                $sql = "ALTER TABLE elections ADD COLUMN results_published TINYINT(1) NOT NULL DEFAULT 0";
                $result = $conn->query($sql);
                
                if ($result) {
                    addFixResult('database', 'Added missing results_published column to elections table', true);
                } else {
                    addFixResult('database', 'Failed to add results_published column: ' . $conn->error, false);
                }
            } else {
                addFixResult('database', 'results_published column already exists', true);
            }
        } catch (Exception $e) {
            addFixResult('database', 'Error fixing results_published column: ' . $e->getMessage(), false);
        }
    }
    
    // Fix election status if needed
    if (isset($_POST['fix_status_enum']) && isset($_POST['status_fix_type'])) {
        $fixType = $_POST['status_fix_type'];
        
        try {
            switch ($fixType) {
                case 'nomination':
                    $sql = "UPDATE elections SET status = 'nomination' WHERE election_id = ?";
                    $result = update($sql, [$electionId]);
                    
                    if ($result !== false) {
                        addFixResult('election', 'Updated election status to "nomination"', true);
                    } else {
                        addFixResult('election', 'Failed to update election status', false);
                    }
                    break;
                    
                case 'pending':
                    $sql = "UPDATE elections SET status = 'pending' WHERE election_id = ?";
                    $result = update($sql, [$electionId]);
                    
                    if ($result !== false) {
                        addFixResult('election', 'Updated election status to "pending"', true);
                    } else {
                        addFixResult('election', 'Failed to update election status', false);
                    }
                    break;
                    
                case 'active':
                    $sql = "UPDATE elections SET status = 'active' WHERE election_id = ?";
                    $result = update($sql, [$electionId]);
                    
                    if ($result !== false) {
                        addFixResult('election', 'Updated election status to "active"', true);
                    } else {
                        addFixResult('election', 'Failed to update election status', false);
                    }
                    break;
                    
                case 'completed':
                    $sql = "UPDATE elections SET status = 'completed' WHERE election_id = ?";
                    $result = update($sql, [$electionId]);
                    
                    if ($result !== false) {
                        addFixResult('election', 'Updated election status to "completed"', true);
                    } else {
                        addFixResult('election', 'Failed to update election status', false);
                    }
                    break;
            }
        } catch (Exception $e) {
            addFixResult('election', 'Error fixing election status: ' . $e->getMessage(), false);
        }
    }
    
    // Fix missing candidates if needed
    if (isset($_POST['fix_missing_candidates']) && isset($_POST['position_id'])) {
        $positionId = intval($_POST['position_id']);
        
        try {
            // First, verify the position exists
            $sql = "SELECT * FROM election_positions WHERE position_id = ? AND election_id = ?";
            $position = fetchOne($sql, [$positionId, $electionId]);
            
            if ($position) {
                // Create a placeholder candidate for demonstration
                $currentUser = getCurrentUser();
                $userId = $currentUser['user_id'];
                
                $sql = "INSERT INTO election_candidates (position_id, election_id, user_id, manifesto, status) 
                        VALUES (?, ?, ?, ?, ?)";
                $result = insert($sql, [
                    $positionId,
                    $electionId,
                    $userId,
                    'This is a placeholder candidate created by the diagnostic tool for testing purposes.',
                    'approved'
                ]);
                
                if ($result) {
                    addFixResult('candidates', 'Added a placeholder candidate for position ID ' . $positionId, true);
                } else {
                    addFixResult('candidates', 'Failed to add placeholder candidate', false);
                }
            } else {
                addFixResult('candidates', 'Position ID ' . $positionId . ' not found for this election', false);
            }
        } catch (Exception $e) {
            addFixResult('candidates', 'Error fixing missing candidates: ' . $e->getMessage(), false);
        }
    }
    
    // After fixing, reload the election data
    if ($electionId > 0) {
        $sql = "SELECT * FROM elections WHERE election_id = ?";
        $election = fetchOne($sql, [$electionId]);
    }
}

// Run diagnostic tests if election is selected
if ($election) {
    // Check database structure
    try {
        // Check if results_published column exists
        $columnsQuery = "SHOW COLUMNS FROM elections LIKE 'results_published'";
        $columns = fetchAll($columnsQuery);
        
        if (empty($columns)) {
            addDiagnostic(
                'database', 
                'Missing results_published column in elections table', 
                'error',
                'fix_results_published'
            );
        } else {
            addDiagnostic('database', 'results_published column exists', 'success');
        }
        
        // Check if status ENUM includes all required values
        $enumQuery = "SHOW COLUMNS FROM elections WHERE Field = 'status'";
        $statusColumn = fetchOne($enumQuery);
        
        if ($statusColumn) {
            $enumValues = $statusColumn['Type'];
            // Extract enum values
            preg_match("/^enum\(\'(.*)\'\)$/", $enumValues, $matches);
            $values = explode("','", $matches[1]);
            
            $requiredValues = ['upcoming', 'nomination', 'pending', 'active', 'completed', 'cancelled'];
            $missingValues = array_diff($requiredValues, $values);
            
            if (!empty($missingValues)) {
                addDiagnostic(
                    'database', 
                    'Missing status values in elections table: ' . implode(', ', $missingValues), 
                    'warning',
                    'fix_status_enum'
                );
            } else {
                addDiagnostic('database', 'All required status values exist', 'success');
            }
        }
    } catch (Exception $e) {
        addDiagnostic('database', 'Error checking database structure: ' . $e->getMessage(), 'error');
    }
    
    // Check election status
    $status = $election['status'];
    if (!in_array($status, ['upcoming', 'nomination', 'pending', 'active', 'completed', 'cancelled'])) {
        addDiagnostic(
            'election', 
            'Invalid election status: ' . $status, 
            'error',
            'fix_status'
        );
    } else {
        addDiagnostic('election', 'Election status is valid: ' . $status, 'success');
    }
    
    // Check for positions
    $sql = "SELECT * FROM election_positions WHERE election_id = ?";
    $positions = fetchAll($sql, [$electionId]);
    
    if (empty($positions)) {
        addDiagnostic('positions', 'No positions found for this election', 'error');
    } else {
        addDiagnostic('positions', count($positions) . ' position(s) found', 'success');
        
        // Check for candidates
        foreach ($positions as $position) {
            $sql = "SELECT COUNT(*) as count FROM election_candidates WHERE position_id = ? AND status = 'approved'";
            $candidateCount = fetchOne($sql, [$position['position_id']])['count'];
            
            if ($candidateCount == 0) {
                addDiagnostic(
                    'candidates', 
                    'No approved candidates for position: ' . $position['title'], 
                    'warning',
                    'fix_missing_candidates|' . $position['position_id']
                );
            } else {
                addDiagnostic('candidates', $candidateCount . ' approved candidate(s) for position: ' . $position['title'], 'success');
            }
        }
    }
    
    // Check for votes if election is active or completed
    if (in_array($status, ['active', 'completed'])) {
        $sql = "SELECT COUNT(*) as count FROM votes WHERE election_id = ?";
        $voteCount = fetchOne($sql, [$electionId])['count'];
        
        if ($voteCount == 0) {
            addDiagnostic('votes', 'No votes recorded for this election', 'warning');
        } else {
            addDiagnostic('votes', $voteCount . ' vote(s) recorded', 'success');
        }
    }
}

// Get all elections for selection dropdown
$sql = "SELECT * FROM elections ORDER BY election_id DESC";
$allElections = fetchAll($sql);

// Set page title
$pageTitle = "Election Diagnostics - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Election Diagnostic Header -->
<div class="election-diagnostic-header animate__animated animate__fadeInDown">
    <div class="election-diagnostic-header-content">
        <div class="election-diagnostic-header-main">
            <h1 class="election-diagnostic-title">
                <i class="fas fa-chart-line me-3"></i>
                Election Diagnostics
            </h1>
            <p class="election-diagnostic-description">Monitor and analyze election system performance</p>
        </div>
        <div class="election-diagnostic-header-actions">
            <a href="elections.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Elections
            </a>
        </div>
    </div>
</div>

<style>
.election-diagnostic-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.election-diagnostic-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.election-diagnostic-header-main {
    flex: 1;
    text-align: center;
}

.election-diagnostic-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.election-diagnostic-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.election-diagnostic-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.election-diagnostic-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-header-action {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

@media (max-width: 768px) {
    .election-diagnostic-header {
        padding: 2rem 1.5rem;
    }

    .election-diagnostic-header-content {
        flex-direction: column;
        align-items: center;
    }

    .election-diagnostic-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .election-diagnostic-title i {
        font-size: 1.8rem;
    }

    .election-diagnostic-description {
        font-size: 1.1rem;
    }

    .election-diagnostic-header-actions {
        width: 100%;
        justify-content: center;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
}

/* Animation classes */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -100%, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate__animated {
    animation-duration: 0.6s;
    animation-fill-mode: both;
}

.animate__fadeInDown {
    animation-name: fadeInDown;
}
</style>

<!-- Page Content -->
<div class="container-fluid" style="margin-top: 1.5rem;">
    
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
    
    <div class="row">
        <!-- Election Selection -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Select Election</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3 align-items-end">
                        <div class="col-md-8">
                            <label for="election-select" class="form-label">Election</label>
                            <select class="form-select" id="election-select" name="id">
                                <option value="">-- Select an election --</option>
                                <?php foreach ($allElections as $e): ?>
                                    <option value="<?php echo $e['election_id']; ?>" <?php echo $electionId == $e['election_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($e['title']); ?> 
                                        (Status: <?php echo ucfirst($e['status']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i> Run Diagnostics
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Fix Results (if any) -->
        <?php if (!empty($fixResults)): ?>
        <div class="col-md-12 mb-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Fix Results</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fixResults as $result): ?>
                                    <tr>
                                        <td><span class="badge bg-info"><?php echo ucfirst($result['type']); ?></span></td>
                                        <td><?php echo $result['description']; ?></td>
                                        <td>
                                            <?php if ($result['success']): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Diagnostic Results -->
        <?php if ($election): ?>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Diagnostics for: <?php echo htmlspecialchars($election['title']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($diagnosticResults)): ?>
                        <div class="alert alert-info">No diagnostic results available.</div>
                    <?php else: ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $electionId); ?>" id="fix-form">
                            <input type="hidden" name="fix" value="1">
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($diagnosticResults as $result): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?php echo ucfirst($result['type']); ?></span></td>
                                                <td><?php echo $result['description']; ?></td>
                                                <td>
                                                    <?php if ($result['status'] === 'success'): ?>
                                                        <span class="badge bg-success">Success</span>
                                                    <?php elseif ($result['status'] === 'warning'): ?>
                                                        <span class="badge bg-warning text-dark">Warning</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Error</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($result['fix_action'])): ?>
                                                        <?php 
                                                        // Parse fix action
                                                        $actionParts = explode('|', $result['fix_action']);
                                                        $action = $actionParts[0];
                                                        $param = isset($actionParts[1]) ? $actionParts[1] : '';
                                                        
                                                        if ($action === 'fix_status_enum'): 
                                                        ?>
                                                            <div class="input-group">
                                                                <select class="form-select form-select-sm" name="status_fix_type">
                                                                    <option value="nomination">Set to 'nomination'</option>
                                                                    <option value="pending">Set to 'pending'</option>
                                                                    <option value="active">Set to 'active'</option>
                                                                    <option value="completed">Set to 'completed'</option>
                                                                </select>
                                                                <button type="submit" name="<?php echo $action; ?>" class="btn btn-sm btn-primary">Fix</button>
                                                            </div>
                                                        <?php elseif ($action === 'fix_missing_candidates'): ?>
                                                            <input type="hidden" name="position_id" value="<?php echo $param; ?>">
                                                            <button type="submit" name="<?php echo $action; ?>" class="btn btn-sm btn-primary">Add Test Candidate</button>
                                                        <?php else: ?>
                                                            <button type="submit" name="<?php echo $action; ?>" class="btn btn-sm btn-primary">Fix Issue</button>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No action needed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <a href="election_detail.php?id=<?php echo $electionId; ?>" class="btn btn-primary">
                                <i class="fas fa-eye me-2"></i> View Election Details
                            </a>
                            <a href="election_edit.php?id=<?php echo $electionId; ?>" class="btn btn-secondary ms-2">
                                <i class="fas fa-edit me-2"></i> Edit Election
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 
