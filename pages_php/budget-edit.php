<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
// Add bridge for admin status consistency
require_once __DIR__ . '/../includes/activity_functions.php'; // Include activity functions
require_once __DIR__ . '/../includes/settings_functions.php';

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
$isSuperAdmin = isSuperAdmin();
$isAdmin = isAdmin(); // Check both auth system and bridge
$isMember = isMember(); // Add member check
$isFinance = isFinance(); // Add finance check
$hasAdminPrivileges = hasAdminPrivileges(); // Super admin or admin
$hasMemberPrivileges = hasMemberPrivileges(); // Super admin, admin, member, or finance
$canManageContent = $hasMemberPrivileges; // Allow super admin, admin, member, and finance to manage content
$canManageBudget = $hasMemberPrivileges; // Finance users have full budget CRUD privileges

// Redirect if user doesn't have permission
if (!$canManageBudget) {
    header("Location: budget.php");
    exit();
}

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

// Check if budget ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: budget.php");
    exit();
}

$budgetId = (int) $_GET['id'];

// Determine if this is edit mode (always true for this page since ID is required)
$isEditMode = true;

// Get budget details
$budget = null;
$budgetItems = [];
$error = null;
$success = null;

try {
    // Get budget details
    $budgetSQL = "SELECT b.*, u.username as created_by_name
                 FROM budgets b
                 LEFT JOIN users u ON b.created_by = u.user_id
                 WHERE b.budget_id = ?";
    $budget = fetchOne($budgetSQL, [$budgetId]);
    
    if (!$budget) {
        header("Location: budget.php");
        exit();
    }
    
    // Check if user has permission to edit this budget
    if (!$hasAdminPrivileges && !$isFinance && $budget['created_by'] != $userId) {
        header("Location: budget-detail.php?id=" . $budgetId);
        exit();
    }
    
    // Get budget items
    $itemsSQL = "SELECT * FROM budget_items WHERE budget_id = ? ORDER BY item_id";
    $budgetItems = fetchAll($itemsSQL, [$budgetId]);
    
    // Get all departments for dropdown
    $departmentsSQL = "SELECT department_id, name FROM departments ORDER BY name";
    $departments = fetchAll($departmentsSQL);
    
    // Get all budget categories for datalist
    $categoriesSQL = "SELECT DISTINCT category FROM budgets WHERE category IS NOT NULL AND category != '' ORDER BY category";
    $categories = fetchAll($categoriesSQL);
    
} catch (Exception $e) {
    $error = "Error fetching budget details: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Get form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category = $_POST['category'] ?? '';
        $amount = (float) ($_POST['amount'] ?? 0);
        $departmentId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
        
        // Validate required fields
        if (empty($title)) {
            throw new Exception("Budget title is required.");
        }
        
        if ($amount <= 0) {
            throw new Exception("Budget amount must be greater than zero.");
        }
        
        // Check if amount changed
        $oldAmount = (float) $budget['amount'];
        $amountChanged = $amount != $oldAmount;
        
        // Update budget
        $updateBudgetSQL = "UPDATE budgets SET 
                          title = ?, 
                          description = ?, 
                          amount = ?, 
                          category = ?, 
                          department_id = ?, 
                          updated_at = NOW() 
                          WHERE budget_id = ?";
        
        $updateBudgetParams = [$title, $description, $amount, $category, $departmentId, $budgetId];
        $updateBudgetTypes = 'ssdsii';
        
        update($updateBudgetSQL, $updateBudgetParams, $updateBudgetTypes);
        
        // Handle budget items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            // Delete existing items
            $deleteItemsSQL = "DELETE FROM budget_items WHERE budget_id = ?";
            delete($deleteItemsSQL, [$budgetId], 'i');
            
            // Insert new items
            foreach ($_POST['items'] as $item) {
                if (empty($item['name']) || !isset($item['amount'])) {
                    continue; // Skip incomplete items
                }
                
                $itemName = $item['name'];
                $itemDescription = $item['description'] ?? '';
                $itemAmount = (float) ($item['amount'] ?? 0);
                $itemQuantity = (int) ($item['quantity'] ?? 1);
                
                if ($itemAmount <= 0 || $itemQuantity <= 0) {
                    continue; // Skip invalid items
                }
                
                $insertItemSQL = "INSERT INTO budget_items (budget_id, name, description, amount, quantity) 
                                VALUES (?, ?, ?, ?, ?)";
                $insertItemParams = [$budgetId, $itemName, $itemDescription, $itemAmount, $itemQuantity];
                $insertItemTypes = 'issdi';
                
                insert($insertItemSQL, $insertItemParams, $insertItemTypes);
            }
        }
        
        // Record history if amount changed
        if ($amountChanged) {
            $historySQL = "INSERT INTO budget_history (budget_id, user_id, action, old_amount, new_amount, created_at) 
                          VALUES (?, ?, 'amount_change', ?, ?, NOW())";
            $historyParams = [$budgetId, $userId, $oldAmount, $amount];
            $historyTypes = 'iidd';
            
            insert($historySQL, $historyParams, $historyTypes);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Record user activity
        try {
            if (function_exists('recordUserActivity')) {
                recordUserActivity($userId, 'edit', 'budget', $budgetId, 'Updated budget: ' . $title);
            }
        } catch (Exception $e) {
            // Silently handle any errors with activity recording
            error_log("Error recording user activity: " . $e->getMessage());
        }
        
        $success = "Budget updated successfully!";
        
        // Refresh budget data
        $budget = fetchOne($budgetSQL, [$budgetId]);
        $budgetItems = fetchAll($itemsSQL, [$budgetId]);
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        $error = "Error updating budget: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Budget - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/budget.css">
</head>
<body>
    <!-- Include header/navbar -->
    <?php include_once 'includes/header.php'; ?>

    <!-- Custom Budget Edit Header -->
    <div class="budget-edit-header animate__animated animate__fadeInDown">
        <div class="budget-edit-header-content">
            <div class="budget-edit-header-main">
                <h1 class="budget-edit-title">
                    <i class="fas fa-edit me-3"></i>
                    Edit Budget
                </h1>
                <p class="budget-edit-description">Modify budget details and financial allocations</p>
            </div>
            <div class="budget-edit-header-actions">
                <a href="budget.php" class="btn btn-header-action">
                    <i class="fas fa-arrow-left me-2"></i>Back to Budgets
                </a>
            </div>
        </div>
    </div>

    <style>
    .budget-edit-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        margin-top: 60px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .budget-edit-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .budget-edit-header-main {
        flex: 1;
        text-align: center;
    }

    .budget-edit-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
    }

    .budget-edit-title i {
        font-size: 2.2rem;
        opacity: 0.9;
    }

    .budget-edit-description {
        margin: 0;
        opacity: 0.95;
        font-size: 1.2rem;
        font-weight: 400;
        line-height: 1.4;
    }

    .budget-edit-header-actions {
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
        .budget-edit-header {
            padding: 2rem 1.5rem;
        }

        .budget-edit-header-content {
            flex-direction: column;
            align-items: center;
        }

        .budget-edit-title {
            font-size: 2rem;
            gap: 0.6rem;
        }

        .budget-edit-title i {
            font-size: 1.8rem;
        }

        .budget-edit-description {
            font-size: 1.1rem;
        }

        .budget-edit-header-actions {
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

    <div class="container-fluid px-0">
        <div class="row g-0">
            <!-- Include sidebar -->
            <?php include_once 'includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="budget.php">Budgets</a></li>
                        <li class="breadcrumb-item"><a href="budget-detail.php?id=<?php echo $budgetId; ?>">Budget Details</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Budget</li>
                    </ol>
                </nav>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Budget</h1>
                </div>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($budget): ?>
                <div class="budget-form animate-fade-in">
                    <form method="post" action="">
                        <div class="form-group mb-3">
                            <label for="title">Budget Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($budget['title']); ?>" required>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($budget['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row mb-3">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category">Category</label>
                                    <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($budget['category'] ?? ''); ?>" list="categoryList">
                                    <datalist id="categoryList">
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="total_amount">Total Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="total_amount" name="amount" value="<?php echo $budget['amount']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($isAdmin && !empty($departments)): ?>
                        <div class="form-group mb-3">
                            <label for="department_id">Department</label>
                            <select class="form-control" id="department_id" name="department_id">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>" <?php echo ($budget['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group mb-3">
                            <label>Budget Items</label>
                            <div id="budgetItemsContainer" class="budget-items-form">
                                <?php if (empty($budgetItems)): ?>
                                <div class="budget-item-form">
                                    <button type="button" class="remove-item">&times;</button>
                                    <div class="form-row">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="item_name_1">Item Name</label>
                                                <input type="text" class="form-control" id="item_name_1" name="items[1][name]" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="item_amount_1">Amount</label>
                                                <input type="number" step="0.01" class="form-control" id="item_amount_1" name="items[1][amount]" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="item_description_1">Description</label>
                                        <textarea class="form-control" id="item_description_1" name="items[1][description]" rows="2"></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="item_quantity_1">Quantity</label>
                                        <input type="number" class="form-control" id="item_quantity_1" name="items[1][quantity]" value="1" min="1">
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php foreach ($budgetItems as $index => $item): ?>
                                <div class="budget-item-form">
                                    <button type="button" class="remove-item">&times;</button>
                                    <div class="form-row">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="item_name_<?php echo $index + 1; ?>">Item Name</label>
                                                <input type="text" class="form-control" id="item_name_<?php echo $index + 1; ?>" name="items[<?php echo $index + 1; ?>][name]" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="item_amount_<?php echo $index + 1; ?>">Amount</label>
                                                <input type="number" step="0.01" class="form-control" id="item_amount_<?php echo $index + 1; ?>" name="items[<?php echo $index + 1; ?>][amount]" value="<?php echo $item['amount']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="item_description_<?php echo $index + 1; ?>">Description</label>
                                        <textarea class="form-control" id="item_description_<?php echo $index + 1; ?>" name="items[<?php echo $index + 1; ?>][description]" rows="2"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="item_quantity_<?php echo $index + 1; ?>">Quantity</label>
                                        <input type="number" class="form-control" id="item_quantity_<?php echo $index + 1; ?>" name="items[<?php echo $index + 1; ?>][quantity]" value="<?php echo $item['quantity']; ?>" min="1">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" id="addBudgetItem" class="add-item-btn mt-3">
                                <i class="fas fa-plus me-1"></i> Add Item
                            </button>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                            <a href="budget-detail.php?id=<?php echo $budgetId; ?>" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="alert alert-warning" role="alert">
                    Budget not found or has been deleted.
                </div>
                <a href="budget.php" class="btn btn-primary">Back to Budgets</a>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Include footer -->
    <?php include_once 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../js/budget.js"></script>
</body>
</html> 