<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in and has permission
requirePermission('update', 'budget');

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Initialize variables
$successMessage = '';
$errorMessage = '';
$formData = [];

// Check if transaction ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: finance-approvals.php");
    exit();
}

$transactionId = (int)$_GET['id'];

// Fetch transaction data
$transactionSql = "SELECT t.*, c.name as category_name 
                  FROM budget_transactions t 
                  JOIN budget_categories c ON t.category_id = c.category_id 
                  WHERE t.transaction_id = ?";
$transaction = fetchOne($transactionSql, [$transactionId]);

if (!$transaction) {
    header("Location: finance-approvals.php");
    exit();
}

// Check if transaction is editable (only pending transactions can be edited)
$isEditable = $transaction['status'] === 'pending';

// Set form data from transaction
$formData = [
    'category_id' => $transaction['category_id'],
    'amount' => $transaction['amount'],
    'description' => $transaction['description'],
    'transaction_date' => $transaction['transaction_date'],
    'transaction_type' => $transaction['transaction_type']
];

// Fetch all active budget categories
$categoriesSql = "SELECT c.* FROM budget_categories c 
                 JOIN budget b ON c.budget_id = b.budget_id 
                 WHERE b.status != 'closed' 
                 ORDER BY c.name";
$categories = fetchAll($categoriesSql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isEditable) {
    try {
        // Get form data
        $formData = [
            'category_id' => isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0,
            'amount' => isset($_POST['amount']) ? (float)$_POST['amount'] : 0,
            'description' => $_POST['description'] ?? '',
            'transaction_date' => $_POST['transaction_date'] ?? date('Y-m-d'),
            'transaction_type' => $_POST['transaction_type'] ?? 'expense'
        ];
        
        // Validate required fields
        if ($formData['category_id'] <= 0) {
            throw new Exception('Please select a category');
        }
        
        if ($formData['amount'] <= 0) {
            throw new Exception('Amount must be greater than zero');
        }
        
        if (empty($formData['description'])) {
            throw new Exception('Description is required');
        }
        
        // Check if category exists
        $categorySql = "SELECT * FROM budget_categories WHERE category_id = ?";
        $category = fetchOne($categorySql, [$formData['category_id']]);
        
        if (!$category) {
            throw new Exception('Selected category does not exist');
        }
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Update transaction
        $updateSql = "UPDATE budget_transactions 
                     SET category_id = ?, amount = ?, description = ?, transaction_date = ?, transaction_type = ? 
                     WHERE transaction_id = ?";
        
        $updateParams = [
            $formData['category_id'],
            $formData['amount'],
            $formData['description'],
            $formData['transaction_date'],
            $formData['transaction_type'],
            $transactionId
        ];
        
        $updateTypes = 'idsssi';
        
        $updateResult = update($updateSql, $updateParams, $updateTypes);
        
        if ($updateResult === false) {
            throw new Exception('Failed to update transaction');
        }
        
        // Add transaction history
        $historySql = "INSERT INTO budget_history 
                      (transaction_id, user_id, action, notes, created_at) 
                      VALUES (?, ?, 'edit', 'Transaction updated', NOW())";
        
        $historyParams = [$transactionId, $userId];
        $historyTypes = 'ii';
        
        $historyResult = insert($historySql, $historyParams, $historyTypes);
        
        if (!$historyResult) {
            throw new Exception('Failed to record transaction history');
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Set success message
        $successMessage = 'Transaction updated successfully';
        
        // Refresh transaction data
        $transaction = fetchOne($transactionSql, [$transactionId]);
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        // Set error message
        $errorMessage = $e->getMessage();
    }
}

// Set page title
$pageTitle = "Edit Financial Record";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SRC Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/budget.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Modern Finance Header -->
                <div class="finance-header animate__animated animate__fadeInDown">
                    <div class="finance-header-content">
                        <div class="finance-header-main">
                            <h1 class="finance-title">
                                <i class="fas fa-edit me-3"></i>
                                Edit Financial Record
                            </h1>
                            <p class="finance-description">Modify transaction details and update financial records</p>
                        </div>
                        <div class="finance-header-actions">
                            <a href="finance-approvals.php" class="btn btn-header-action">
                                <i class="fas fa-arrow-left me-2"></i>Back to Approvals
                            </a>
                            <a href="finance.php" class="btn btn-header-action">
                                <i class="fas fa-chart-line me-2"></i>Finance Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <style>
                .finance-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 2.5rem 2rem;
                    border-radius: 12px;
                    margin-top: 60px;
                    margin-bottom: 1.5rem;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                }

                .finance-header-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 1.5rem;
                }

                .finance-header-main {
                    flex: 1;
                    text-align: center;
                }

                .finance-title {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 1rem;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .finance-title i {
                    font-size: 2.2rem;
                }

                .finance-description {
                    margin: 1rem 0 0 0;
                    opacity: 0.9;
                    font-size: 1.2rem;
                    line-height: 1.4;
                }

                .finance-header-actions {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-end;
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

                /* Mobile responsiveness for tablets (768px) */
                @media (max-width: 768px) {
                    .finance-header {
                        padding: 1.5rem;
                    }

                    .finance-header-content {
                        flex-direction: column;
                        align-items: center;
                    }

                    .finance-title {
                        font-size: 1.75rem;
                        gap: 0.5rem;
                    }

                    .finance-title i {
                        font-size: 1.5rem;
                    }

                    .finance-description {
                        font-size: 1rem;
                        margin-top: 0.5rem;
                    }

                    .finance-header-actions {
                        width: 100%;
                        justify-content: center;
                        flex-direction: row;
                    }

                    .btn-header-action {
                        font-size: 0.85rem;
                        padding: 0.45rem 0.8rem;
                    }
                }

                /* Mobile responsiveness for small screens (576px and below) */
                @media (max-width: 576px) {
                    .finance-header {
                        margin-left: -1rem;
                        margin-right: -1rem;
                        border-radius: 0;
                        padding: 1rem !important;
                    }

                    .finance-header-content {
                        flex-direction: column;
                        align-items: center;
                        gap: 0.75rem;
                    }

                    .finance-header-main {
                        width: 100%;
                    }

                    .finance-title {
                        font-size: 1.4rem;
                        gap: 0.4rem;
                        justify-content: center;
                        font-weight: 700;
                    }

                    .finance-title i {
                        font-size: 1.2rem;
                    }

                    .finance-description {
                        font-size: 0.9rem;
                        font-weight: 400;
                        margin: 0.5rem 0 0 0;
                    }

                    .finance-header-actions {
                        width: 100%;
                        justify-content: center;
                        gap: 0.5rem;
                        flex-direction: column;
                    }

                    .btn-header-action {
                        font-size: 0.85rem;
                        padding: 0.5rem 0.8rem;
                        width: 100%;
                    }

                    main {
                        padding: 0 !important;
                        margin: 0 !important;
                    }

                    .row {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                    }

                    .row > * {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }

                    .col-lg-8,
                    .col-md-12 {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }

                    /* Form improvements */
                    .budget-form,
                    .form-wrapper {
                        padding: 1rem !important;
                        margin-bottom: 0;
                    }

                    .form-row {
                        margin: 0;
                    }

                    .form-group {
                        margin-bottom: 1.25rem;
                    }

                    .form-group label {
                        font-size: 0.95rem;
                        font-weight: 600;
                        margin-bottom: 0.5rem;
                        display: block;
                    }

                    .form-control,
                    .form-select {
                        font-size: 1rem;
                        padding: 0.75rem;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                        width: 100%;
                    }

                    .form-check-label {
                        font-size: 0.95rem;
                        margin-top: 0.25rem;
                    }

                    /* Alert improvements */
                    .alert {
                        padding: 1rem !important;
                        font-size: 0.95rem;
                        margin-bottom: 1rem !important;
                        margin-left: 0;
                        margin-right: 0;
                        border-radius: 12px;
                    }

                    .alert i {
                        font-size: 1rem;
                        margin-right: 0.75rem;
                    }

                    /* Button improvements */
                    .btn {
                        font-size: 0.95rem;
                        padding: 0.6rem 1rem;
                    }

                    .btn-sm {
                        font-size: 0.85rem;
                        padding: 0.45rem 0.75rem;
                    }

                    /* Card styles */
                    .card {
                        border: none;
                        border-radius: 12px;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                        margin-bottom: 1rem !important;
                    }

                    .card-header {
                        padding: 1rem !important;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border: none;
                        border-radius: 12px 12px 0 0;
                        font-size: 1.1rem;
                        font-weight: 600;
                    }

                    .card-body {
                        padding: 1rem !important;
                        border-radius: 0 0 12px 12px;
                    }
                    }
                }

                /* Extra small devices (320px - 375px) */
                @media (max-width: 375px) {
                    .finance-header {
                        padding: 0.75rem !important;
                    }

                    .finance-title {
                        font-size: 1.2rem;
                        gap: 0.3rem;
                    }

                    .finance-title i {
                        font-size: 1.1rem;
                    }

                    .finance-description {
                        font-size: 0.85rem;
                    }

                    .btn-header-action {
                        font-size: 0.8rem;
                        padding: 0.4rem 0.7rem;
                        width: 100%;
                    }

                    main {
                        padding: 0 !important;
                    }

                    .budget-form,
                    .form-wrapper {
                        padding: 0.9rem !important;
                    }

                    .form-group {
                        margin-bottom: 1rem;
                    }

                    .form-group label {
                        font-size: 0.9rem;
                    }

                    .form-control,
                    .form-select {
                        font-size: 0.95rem;
                        padding: 0.65rem;
                    }

                    .btn {
                        font-size: 0.9rem;
                        padding: 0.55rem 0.9rem;
                    }

                    .card-header {
                        padding: 0.85rem !important;
                        font-size: 1rem;
                    }

                    .card-body {
                        padding: 0.85rem !important;
                    }
                }                    .finance-header-actions {
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

                <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (!$isEditable): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> This transaction cannot be edited because its status is <strong><?php echo ucfirst($transaction['status']); ?></strong>.
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8 col-md-12">
                        <div class="budget-form">
                            <form method="post" action="">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="transaction_type">Transaction Type</label>
                                        <div class="d-flex">
                                            <div class="form-check me-4">
                                                <input class="form-check-input" type="radio" name="transaction_type" id="type_expense" value="expense" <?php echo $formData['transaction_type'] === 'expense' ? 'checked' : ''; ?> <?php echo !$isEditable ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="type_expense">
                                                    <i class="fas fa-arrow-circle-up text-danger me-1"></i> Expense
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="transaction_type" id="type_income" value="income" <?php echo $formData['transaction_type'] === 'income' ? 'checked' : ''; ?> <?php echo !$isEditable ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="type_income">
                                                    <i class="fas fa-arrow-circle-down text-success me-1"></i> Income
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="category_id">Budget Category</label>
                                        <select class="form-control" id="category_id" name="category_id" required <?php echo !$isEditable ? 'disabled' : ''; ?>>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>" <?php echo $formData['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="amount">Amount ($)</label>
                                        <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?php echo $formData['amount']; ?>" required <?php echo !$isEditable ? 'disabled' : ''; ?>>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="transaction_date">Transaction Date</label>
                                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo $formData['transaction_date']; ?>" required <?php echo !$isEditable ? 'disabled' : ''; ?>>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required <?php echo !$isEditable ? 'disabled' : ''; ?>><?php echo $formData['description']; ?></textarea>
                                    </div>
                                </div>

                                <?php if ($isEditable): ?>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Update Transaction
                                    </button>
                                    <a href="finance-transactions.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                                </div>
                                <?php else: ?>
                                <div class="form-group mt-4">
                                    <a href="finance-transactions.php" class="btn btn-primary">
                                        <i class="fas fa-arrow-left me-2"></i> Back to Transactions
                                    </a>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-12">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Transaction Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-muted">Transaction ID</h6>
                                    <p><?php echo $transaction['transaction_id']; ?></p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="text-muted">Status</h6>
                                    <p>
                                        <span class="badge <?php echo $transaction['status'] === 'approved' ? 'bg-success' : ($transaction['status'] === 'rejected' ? 'bg-danger' : 'bg-warning'); ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="text-muted">Created At</h6>
                                    <p><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="text-muted">Created By</h6>
                                    <p>
                                        <?php 
                                        $createdBySql = "SELECT username FROM users WHERE user_id = ?";
                                        $createdBy = fetchOne($createdBySql, [$transaction['created_by']]);
                                        echo $createdBy ? htmlspecialchars($createdBy['username']) : 'Unknown';
                                        ?>
                                    </p>
                                </div>
                                <?php if ($transaction['approved_by']): ?>
                                <div class="mb-3">
                                    <h6 class="text-muted">Approved/Rejected By</h6>
                                    <p>
                                        <?php 
                                        $approvedBySql = "SELECT username FROM users WHERE user_id = ?";
                                        $approvedBy = fetchOne($approvedBySql, [$transaction['approved_by']]);
                                        echo $approvedBy ? htmlspecialchars($approvedBy['username']) : 'Unknown';
                                        ?>
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <h6 class="text-muted">Approved/Rejected At</h6>
                                    <p><?php echo date('M d, Y H:i', strtotime($transaction['approved_at'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-history me-2"></i> Transaction History</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $historySql = "SELECT h.*, u.username 
                                              FROM budget_history h 
                                              JOIN users u ON h.user_id = u.user_id 
                                              WHERE h.transaction_id = ? 
                                              ORDER BY h.created_at DESC";
                                $history = fetchAll($historySql, [$transactionId]);
                                
                                if (empty($history)):
                                ?>
                                <p class="text-muted">No history available for this transaction.</p>
                                <?php else: ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($history as $entry): ?>
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between">
                                            <span>
                                                <strong><?php echo ucfirst($entry['action']); ?></strong> by <?php echo htmlspecialchars($entry['username']); ?>
                                            </span>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($entry['created_at'])); ?></small>
                                        </div>
                                        <?php if (!empty($entry['notes'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($entry['notes']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script src="../js/budget.js"></script>
    <script>
        // Initialize form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Check category
                const category = document.getElementById('category_id');
                if (!category.value) {
                    category.classList.add('is-invalid');
                    isValid = false;
                } else {
                    category.classList.remove('is-invalid');
                }
                
                // Check amount
                const amount = document.getElementById('amount');
                if (!amount.value || parseFloat(amount.value) <= 0) {
                    amount.classList.add('is-invalid');
                    isValid = false;
                } else {
                    amount.classList.remove('is-invalid');
                }
                
                // Check description
                const description = document.getElementById('description');
                if (!description.value.trim()) {
                    description.classList.add('is-invalid');
                    isValid = false;
                } else {
                    description.classList.remove('is-invalid');
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 