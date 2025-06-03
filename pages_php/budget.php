<?php
// Include authentication file and database config
header('Content-Type: text/html; charset=utf-8');
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../auth_bridge.php'; // Add bridge for admin status consistency
require_once '../activity_functions.php'; // Include activity functions
require_once '../settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin() || getBridgedAdminStatus(); // Check both auth system and bridge
$isMember = isMember(); // Add member check
$canManageContent = $isAdmin || $isMember; // Allow both admins and members to manage content

// Get user profile data including full name
$userId = $currentUser['user_id'] ?? 0;
$userProfile = null;
if ($userId > 0) {
    try {
        $userProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Silently handle any database errors
    }
}

// Get user's name and first initial for avatar
$fullName = $userProfile['full_name'] ?? $currentUser['username'] ?? 'User';
$userInitial = strtoupper(substr($fullName, 0, 1));
$userName = $fullName;
$userRole = ucfirst($currentUser['role'] ?? 'User');

// Check if budget tables exist, if not create them
$checkTableSQL = "SHOW TABLES LIKE 'budgets'";
$result = mysqli_query($conn, $checkTableSQL);
if ($result && mysqli_num_rows($result) == 0) {
    // Table doesn't exist, include the creation script
    include_once '../create_budget_tables.php';
}

// Get budget statistics
$totalBudget = 0;
$approvedBudget = 0;
$pendingBudget = 0;
$declinedBudget = 0;
$totalItems = 0;
$approvedItems = 0;
$pendingItems = 0;
$declinedItems = 0;

try {
    // Get total budget amount
    $totalBudgetSQL = "SELECT SUM(amount) as total FROM budgets";
    $totalBudgetResult = fetchOne($totalBudgetSQL);
    $totalBudget = $totalBudgetResult ? $totalBudgetResult['total'] : 0;
    
    // Get approved budget amount
    $approvedBudgetSQL = "SELECT SUM(amount) as total, COUNT(*) as count FROM budgets WHERE status = 'approved'";
    $approvedBudgetResult = fetchOne($approvedBudgetSQL);
    $approvedBudget = $approvedBudgetResult ? $approvedBudgetResult['total'] : 0;
    $approvedItems = $approvedBudgetResult ? $approvedBudgetResult['count'] : 0;
    
    // Get pending budget amount
    $pendingBudgetSQL = "SELECT SUM(amount) as total, COUNT(*) as count FROM budgets WHERE status = 'pending'";
    $pendingBudgetResult = fetchOne($pendingBudgetSQL);
    $pendingBudget = $pendingBudgetResult ? $pendingBudgetResult['total'] : 0;
    $pendingItems = $pendingBudgetResult ? $pendingBudgetResult['count'] : 0;
    
    // Get declined budget amount
    $declinedBudgetSQL = "SELECT SUM(amount) as total, COUNT(*) as count FROM budgets WHERE status = 'declined'";
    $declinedBudgetResult = fetchOne($declinedBudgetSQL);
    $declinedBudget = $declinedBudgetResult ? $declinedBudgetResult['total'] : 0;
    $declinedItems = $declinedBudgetResult ? $declinedBudgetResult['count'] : 0;
    
    // Get total items count
    $totalItemsSQL = "SELECT COUNT(*) as count FROM budgets";
    $totalItemsResult = fetchOne($totalItemsSQL);
    $totalItems = $totalItemsResult ? $totalItemsResult['count'] : 0;
} catch (Exception $e) {
    // Handle database errors
    $error = "Error fetching budget statistics: " . $e->getMessage();
}

// Get all budget categories for filter
$categories = [];
try {
    $categoriesSQL = "SELECT DISTINCT category FROM budgets WHERE category IS NOT NULL AND category != '' ORDER BY category";
    $categoriesResult = fetchAll($categoriesSQL);
    foreach ($categoriesResult as $row) {
        $categories[] = $row['category'];
    }
} catch (Exception $e) {
    // Handle database errors
}

// Get all budgets for the table
$budgets = [];
try {
    $budgetsSQL = "SELECT b.*, u.username as created_by_name, 
                  (SELECT COUNT(*) FROM budget_items WHERE budget_id = b.budget_id) as item_count
                  FROM budgets b
                  LEFT JOIN users u ON b.created_by = u.user_id
                  ORDER BY b.created_at DESC";
    $budgets = fetchAll($budgetsSQL);
} catch (Exception $e) {
    // Handle database errors
    $error = "Error fetching budgets: " . $e->getMessage();
}

// Get monthly budget data for chart
$monthlyData = [];
try {
    $monthlySQL = "SELECT 
                  DATE_FORMAT(created_at, '%Y-%m') as month,
                  SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
                  SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                  FROM budgets
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month";
    $monthlyResult = fetchAll($monthlySQL);
    
    $months = [];
    $approvedData = [];
    $pendingData = [];
    
    foreach ($monthlyResult as $row) {
        $monthDate = new DateTime($row['month'] . '-01');
        $months[] = $monthDate->format('M Y');
        $approvedData[] = $row['approved_amount'];
        $pendingData[] = $row['pending_amount'];
    }
    
    $monthlyData = [
        'months' => $months,
        'approved' => $approvedData,
        'pending' => $pendingData
    ];
} catch (Exception $e) {
    // Handle database errors
}

// Get category distribution data for chart
$categoryData = [];
try {
    $categorySQL = "SELECT 
                  COALESCE(category, 'Uncategorized') as category,
                  SUM(amount) as total_amount
                  FROM budgets
                  GROUP BY COALESCE(category, 'Uncategorized')
                  ORDER BY total_amount DESC
                  LIMIT 5";
    $categoryResult = fetchAll($categorySQL);
    
    $categoryLabels = [];
    $categoryValues = [];
    
    foreach ($categoryResult as $row) {
        $categoryLabels[] = $row['category'];
        $categoryValues[] = $row['total_amount'];
    }
    
    $categoryData = [
        'categories' => $categoryLabels,
        'values' => $categoryValues
    ];
} catch (Exception $e) {
    // Handle database errors
}

// Record user activity
try {
    if (function_exists('recordUserActivity')) {
        recordUserActivity($userId, 'view', 'budget', 0, 'Viewed budget management page');
    }
} catch (Exception $e) {
    // Silently handle any errors with activity recording
    error_log("Error recording user activity: " . $e->getMessage());
}

// Set page title
$pageTitle = "Budget Management - " . $siteName;

// Include header
require_once 'includes/header.php';

// Add CSS for budget page
?>
<link rel="stylesheet" href="../css/budget.css">
<!-- Inline CSS fix for modal -->
<style>
    #createBudgetModalOverlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background-color: rgba(0, 0, 0, 0.5) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 9999 !important;
    }
    
    #createBudgetModal {
        background-color: white !important;
        border-radius: 12px !important;
        width: 90% !important;
        max-width: 600px !important;
        max-height: 90vh !important;
        overflow-y: auto !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        z-index: 10000 !important;
        position: relative !important;
    }
    
    .modal-header, .modal-body, .modal-footer {
        padding: 1rem !important;
    }
    
    .modal-header {
        border-bottom: 1px solid #e2e8f0 !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }
    
    .modal-footer {
        border-top: 1px solid #e2e8f0 !important;
        display: flex !important;
        justify-content: flex-end !important;
        gap: 0.5rem !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="header">
        <h1 class="page-title">Budget Management</h1>
        
        <div class="header-actions">
            <?php if ($canManageContent): ?>
            <button type="button" class="btn btn-primary" id="createBudgetBtn" onclick="openCreateBudgetModal()">
                <i class="fas fa-plus me-1"></i> Create New Budget
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Budget Summary Cards -->
    <div class="budget-summary">
        <div class="budget-card total animate-fade-in">
            <div class="budget-card-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="budget-card-title">TOTAL BUDGET</div>
            <div class="budget-card-amount">₵<?php echo number_format($totalBudget, 2); ?></div>
            <div class="budget-card-count"><?php echo $totalItems; ?> budget items</div>
        </div>
        
        <div class="budget-card approved animate-fade-in">
            <div class="budget-card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="budget-card-title">APPROVED</div>
            <div class="budget-card-amount">₵<?php echo number_format($approvedBudget, 2); ?></div>
            <div class="budget-card-count"><?php echo $approvedItems; ?> items</div>
        </div>
        
        <div class="budget-card pending animate-fade-in">
            <div class="budget-card-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="budget-card-title">PENDING</div>
            <div class="budget-card-amount">₵<?php echo number_format($pendingBudget, 2); ?></div>
            <div class="budget-card-count"><?php echo $pendingItems; ?> items</div>
        </div>
        
        <div class="budget-card declined animate-fade-in">
            <div class="budget-card-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="budget-card-title">DECLINED</div>
            <div class="budget-card-amount">₵<?php echo number_format($declinedBudget, 2); ?></div>
            <div class="budget-card-count"><?php echo $declinedItems; ?> items</div>
        </div>
    </div>
</div>

<!-- Budget Charts -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h3 class="chart-title">Budget Distribution</h3>
                <div style="height: 300px;">
                    <canvas id="budgetDistributionChart" 
                            data-approved="<?php echo $approvedBudget; ?>" 
                            data-pending="<?php echo $pendingBudget; ?>" 
                            data-declined="<?php echo $declinedBudget; ?>"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h3 class="chart-title">Monthly Budget Trends</h3>
                <div style="height: 300px;">
                    <canvas id="budgetTrendsChart" 
                            data-months='<?php echo json_encode($monthlyData['months'] ?? []); ?>' 
                            data-approved='<?php echo json_encode($monthlyData['approved'] ?? []); ?>' 
                            data-pending='<?php echo json_encode($monthlyData['pending'] ?? []); ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="chart-container">
                <h3 class="chart-title">Budget by Category</h3>
                <div style="height: 300px;">
                    <canvas id="categoryDistributionChart" 
                            data-categories='<?php echo json_encode($categoryData['categories'] ?? []); ?>' 
                            data-values='<?php echo json_encode($categoryData['values'] ?? []); ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Budget Filters -->
    <div class="filters">
        <div class="filter-item">
            <span class="filter-label">Status:</span>
            <select id="statusFilter" class="filter-select">
                <option value="">All</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending</option>
                <option value="declined">Declined</option>
            </select>
        </div>
        
        <div class="filter-item">
            <span class="filter-label">Category:</span>
            <select id="categoryFilter" class="filter-select">
                <option value="">All</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="search-input" placeholder="Search budgets...">
    </div>

    <!-- Budget Table -->
    <div class="budget-table-container">
        <table class="budget-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($budgets)): ?>
                <tr>
                    <td colspan="7" class="text-center">No budgets found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($budgets as $budget): ?>
                <tr data-budget-id="<?php echo $budget['budget_id']; ?>" 
                    data-status="<?php echo $budget['status']; ?>" 
                    data-category="<?php echo htmlspecialchars($budget['category'] ?? ''); ?>"
                    data-title="<?php echo htmlspecialchars($budget['title']); ?>"
                    data-description="<?php echo htmlspecialchars($budget['description'] ?? ''); ?>">
                    <td>
                        <a href="budget-detail.php?id=<?php echo $budget['budget_id']; ?>" class="fw-bold text-decoration-none">
                            <?php echo htmlspecialchars($budget['title']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($budget['category'] ?? 'Uncategorized'); ?></td>
                    <td>₵<?php echo number_format($budget['amount'], 2); ?></td>
                    <td>
                        <span class="status-badge <?php echo $budget['status']; ?>">
                            <?php echo ucfirst($budget['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($budget['created_by_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($budget['created_at'])); ?></td>
                    <td>
                        <a href="budget-detail.php?id=<?php echo $budget['budget_id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <?php if ($canManageContent): ?>
                        <a href="budget-edit.php?id=<?php echo $budget['budget_id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <?php if ($isAdmin): ?>
                        <?php if ($budget['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-sm btn-outline-success status-change-btn" 
                                data-budget-id="<?php echo $budget['budget_id']; ?>" 
                                data-status="approved">
                            <i class="fas fa-check"></i>
                        </button>
                        
                        <button type="button" class="btn btn-sm btn-outline-danger status-change-btn" 
                                data-budget-id="<?php echo $budget['budget_id']; ?>" 
                                data-status="declined">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-sm btn-outline-danger delete-budget-btn" 
                                data-budget-id="<?php echo $budget['budget_id']; ?>" 
                                data-title="<?php echo htmlspecialchars($budget['title']); ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <!-- Pagination will be dynamically generated by JavaScript -->
    </div>
</div>

<?php if ($canManageContent): ?>
<!-- Create Budget Modal -->
<div class="modal-overlay" id="createBudgetModalOverlay">
    <div class="modal" id="createBudgetModal">
        <div class="modal-header">
            <h3 class="modal-title">Create New Budget</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form action="budget_handler.php" method="post" id="createBudgetForm">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="title">Budget Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" class="form-control" id="category" name="category" list="categoryList">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_amount">Total Amount</label>
                        <input type="number" step="0.01" class="form-control" id="total_amount" name="amount" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Budget Items</label>
                    <div id="budgetItemsContainer" class="budget-items-form">
                        <div class="budget-item-form">
                            <button type="button" class="remove-item">&times;</button>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="item_name_1">Item Name</label>
                                    <input type="text" class="form-control" id="item_name_1" name="items[1][name]" required>
                                </div>
                                <div class="form-group">
                                    <label for="item_amount_1">Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="item_amount_1" name="items[1][amount]" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="item_description_1">Description</label>
                                <textarea class="form-control" id="item_description_1" name="items[1][description]" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="item_quantity_1">Quantity</label>
                                <input type="number" class="form-control" id="item_quantity_1" name="items[1][quantity]" value="1" min="1">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" id="addBudgetItem" class="add-item-btn">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
            <button type="submit" form="createBudgetForm" class="btn btn-primary">Create Budget</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Include footer -->
<?php include_once 'includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JS -->
<script src="../js/budget.js"></script>

<!-- Direct fix for Create Budget modal -->
<script>
    // Function to open the create budget modal
    function openCreateBudgetModal() {
        const createBudgetModal = document.getElementById('createBudgetModal');
        const createBudgetModalOverlay = document.getElementById('createBudgetModalOverlay');
        
        if (createBudgetModal && createBudgetModalOverlay) {
            // Ensure the overlay is visible with proper styling
            createBudgetModalOverlay.style.display = 'flex';
            createBudgetModalOverlay.style.opacity = '1';
            createBudgetModalOverlay.style.visibility = 'visible';
            
            // Make the modal visible
            createBudgetModal.style.opacity = '1';
            createBudgetModal.style.transform = 'scale(1)';
            
            // Focus on the first input
            setTimeout(() => {
                const firstInput = createBudgetModal.querySelector('input[type="text"]');
                if (firstInput) firstInput.focus();
            }, 100);
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Direct fix for the Create Budget button
        const createBudgetBtn = document.getElementById('createBudgetBtn');
        const createBudgetModal = document.getElementById('createBudgetModal');
        const createBudgetModalOverlay = document.getElementById('createBudgetModalOverlay');
        
        if (createBudgetBtn && createBudgetModal && createBudgetModalOverlay) {
            // Close modal when clicking on the close button
            const closeButtons = createBudgetModal.querySelectorAll('.modal-close, [data-modal-close]');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    createBudgetModalOverlay.style.opacity = '0';
                    createBudgetModalOverlay.style.visibility = 'hidden';
                    setTimeout(() => {
                        createBudgetModalOverlay.style.display = 'none';
                    }, 300);
                });
            });
            
            // Close modal when clicking on the overlay
            createBudgetModalOverlay.addEventListener('click', function(e) {
                if (e.target === createBudgetModalOverlay) {
                    createBudgetModalOverlay.style.opacity = '0';
                    createBudgetModalOverlay.style.visibility = 'hidden';
                    setTimeout(() => {
                        createBudgetModalOverlay.style.display = 'none';
                    }, 300);
                }
            });
        }
    });
</script> 