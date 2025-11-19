<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();

// Check if user is logged in and has permission
requirePermission('create', 'budget');

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Initialize variables
$successMessage = '';
$errorMessage = '';
$formData = [
    'category_id' => '',
    'amount' => '',
    'description' => '',
    'transaction_date' => date('Y-m-d'),
    'transaction_type' => 'expense'
];

// Fetch all active budget categories
$categoriesSql = "SELECT c.* FROM budget_categories c 
                 JOIN budget b ON c.budget_id = b.budget_id 
                 WHERE b.status != 'closed' 
                 ORDER BY c.name";
$categories = fetchAll($categoriesSql);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        // Insert transaction
        $insertSql = "INSERT INTO budget_transactions 
                     (category_id, amount, description, transaction_date, transaction_type, created_by, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $insertParams = [
            $formData['category_id'],
            $formData['amount'],
            $formData['description'],
            $formData['transaction_date'],
            $formData['transaction_type'],
            $userId
        ];
        
        $insertTypes = 'idsssi';
        
        $transactionId = insert($insertSql, $insertParams, $insertTypes);
        
        if (!$transactionId) {
            throw new Exception('Failed to record transaction');
        }
        
        // Update category spent amount if it's an expense
        if ($formData['transaction_type'] === 'expense') {
            $updateCategorySql = "UPDATE budget_categories 
                                 SET spent_amount = spent_amount + ? 
                                 WHERE category_id = ?";
            
            $updateCategoryParams = [$formData['amount'], $formData['category_id']];
            $updateCategoryTypes = 'di';
            
            $updateResult = update($updateCategorySql, $updateCategoryParams, $updateCategoryTypes);
            
            if ($updateResult === false) {
                throw new Exception('Failed to update category spent amount');
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);

        // Set success message
        $successMessage = 'Transaction recorded successfully';

        // Send notification to members and admins about new financial record
        autoNotifyFinanceCreated($formData['description'], $formData['amount'], $userId, $transactionId);
        
        // Reset form data
        $formData = [
            'category_id' => '',
            'amount' => '',
            'description' => '',
            'transaction_date' => date('Y-m-d'),
            'transaction_type' => 'expense'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        // Set error message
        $errorMessage = $e->getMessage();
    }
}

// Set page title
$pageTitle = "Add Financial Record";
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
                <!-- Custom Finance Add Record Header -->
                <div class="finance-add-record-header animate__animated animate__fadeInDown">
                    <div class="finance-add-record-header-content">
                        <div class="finance-add-record-header-main">
                            <h1 class="finance-add-record-title">
                                <i class="fas fa-plus-circle me-3"></i>
                                Add Financial Record
                            </h1>
                            <p class="finance-add-record-description">Create new financial transactions and records</p>
                        </div>
                        <div class="finance-add-record-header-actions">
                            <a href="finance.php" class="btn btn-header-action">
                                <i class="fas fa-arrow-left me-2"></i>Back to Finance
                            </a>
                        </div>
                    </div>
                </div>

                <style>
                .finance-add-record-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 2.5rem 2rem;
                    border-radius: 12px;
                    margin-top: 60px;
                    margin-bottom: 2rem;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                }

                .finance-add-record-header-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 1.5rem;
                }

                .finance-add-record-header-main {
                    flex: 1;
                    text-align: center;
                }

                .finance-add-record-title {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin: 0 0 1rem 0;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.8rem;
                }

                .finance-add-record-title i {
                    font-size: 2.2rem;
                    opacity: 0.9;
                }

                .finance-add-record-description {
                    margin: 0;
                    opacity: 0.95;
                    font-size: 1.2rem;
                    font-weight: 400;
                    line-height: 1.4;
                }

                .finance-add-record-header-actions {
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

                /* Mobile responsiveness for tablets (768px) */
                @media (max-width: 768px) {
                    .finance-add-record-header {
                        padding: 1.5rem;
                    }

                    .finance-add-record-header-content {
                        flex-direction: column;
                        align-items: center;
                    }

                    .finance-add-record-title {
                        font-size: 1.75rem;
                        gap: 0.5rem;
                    }

                    .finance-add-record-title i {
                        font-size: 1.5rem;
                    }

                    .finance-add-record-description {
                        font-size: 1rem;
                    }

                    .finance-add-record-header-actions {
                        width: 100%;
                        justify-content: center;
                    }

                    .btn-header-action {
                        font-size: 0.85rem;
                        padding: 0.45rem 0.8rem;
                    }
                }

                /* Mobile responsiveness for small screens (576px and below) */
                @media (max-width: 576px) {
                    .finance-add-record-header {
                        margin-left: -1rem;
                        margin-right: -1rem;
                        border-radius: 0;
                        padding: 1rem !important;
                    }

                    .finance-add-record-header-content {
                        flex-direction: column;
                        align-items: center;
                        gap: 0.75rem;
                    }

                    .finance-add-record-header-main {
                        width: 100%;
                    }

                    .finance-add-record-title {
                        font-size: 1.4rem;
                        gap: 0.4rem;
                        justify-content: center;
                        font-weight: 700;
                    }

                    .finance-add-record-title i {
                        font-size: 1.2rem;
                    }

                    .finance-add-record-description {
                        font-size: 0.9rem;
                        font-weight: 400;
                    }

                    .finance-add-record-header-actions {
                        width: 100%;
                        justify-content: center;
                        gap: 0.5rem;
                    }

                    .btn-header-action {
                        font-size: 0.85rem;
                        padding: 0.5rem 0.8rem;
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
                    .budget-form {
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

                /* Extra small devices (320px - 375px) */
                @media (max-width: 375px) {
                    .finance-add-record-header {
                        padding: 0.75rem !important;
                    }

                    .finance-add-record-title {
                        font-size: 1.2rem;
                        gap: 0.3rem;
                    }

                    .finance-add-record-title i {
                        font-size: 1.1rem;
                    }

                    .finance-add-record-description {
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

                    .budget-form {
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

                /* Mobile Full-Width Optimization for Finance Add Record Page */
                @media (max-width: 991px) {
                    [class*="col-md-"], [class*="col-lg-"] {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }
                    .container-fluid {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }
                    .header, .page-hero, .modern-page-header {
                        border-radius: 12px !important;
                    }
                    .card, .form-card, .budget-form {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        border-radius: 0 !important;
                    }
                }
                </style>

                <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-top: 1.5rem;">
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

                <div class="row">
                    <div class="col-lg-8 col-md-12">
                        <div class="budget-form">
                            <form method="post" action="">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="transaction_type">Transaction Type</label>
                                        <div class="d-flex">
                                            <div class="form-check me-4">
                                                <input class="form-check-input" type="radio" name="transaction_type" id="type_expense" value="expense" <?php echo $formData['transaction_type'] === 'expense' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="type_expense">
                                                    <i class="fas fa-arrow-circle-up text-danger me-1"></i> Expense
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="transaction_type" id="type_income" value="income" <?php echo $formData['transaction_type'] === 'income' ? 'checked' : ''; ?>>
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
                                        <div class="input-group">
                                            <select class="form-control" id="category_id" name="category_id" required>
                                                <option value="">Select a category</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['category_id']; ?>" <?php echo $formData['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="input-group-append">
                                                <a href="finance-categories.php" class="btn btn-outline-secondary" title="Manage Categories">
                                                    <i class="fas fa-cog"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">
                                            <a href="finance-categories.php" class="text-decoration-none">
                                                <i class="fas fa-plus me-1"></i>Add new category
                                            </a>
                                        </small>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="amount">Amount ($)</label>
                                        <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?php echo $formData['amount']; ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="transaction_date">Transaction Date</label>
                                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo $formData['transaction_date']; ?>" required>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $formData['description']; ?></textarea>
                                    </div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Record Transaction
                                    </button>
                                    <a href="finance.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-12">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Transaction Guidelines</h5>
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li class="mb-2">All transactions must be assigned to a budget category.</li>
                                    <li class="mb-2">Provide a clear, detailed description for audit purposes.</li>
                                    <li class="mb-2">Expense transactions will reduce the available budget in the selected category.</li>
                                    <li class="mb-2">Income transactions are recorded for reporting purposes.</li>
                                    <li class="mb-2">All transactions are logged with your user information for accountability.</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i> Required Documentation</h5>
                            </div>
                            <div class="card-body">
                                <p>For proper financial management, ensure you have the following:</p>
                                <ul class="mb-0">
                                    <li class="mb-2">Original receipts for all expenses</li>
                                    <li class="mb-2">Approval forms for expenses over ₵500</li>
                                    <li class="mb-2">Supporting documentation for all income sources</li>
                                </ul>
                                <hr>
                                <p class="mb-0"><strong>Note:</strong> Keep all physical documents for at least 3 years for audit purposes.</p>
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

<?php include 'includes/footer.php'; ?>