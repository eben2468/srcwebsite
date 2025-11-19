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
requirePermission('read', 'budget');

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Get filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Fetch all categories for filter dropdown
$categoriesSql = "SELECT * FROM budget_categories ORDER BY name";
$categories = fetchAll($categoriesSql);

// Build the query based on filters
$transactionsSql = "SELECT t.*, c.name as category_name, u.username as created_by_name 
                   FROM budget_transactions t 
                   LEFT JOIN budget_categories c ON t.category_id = c.category_id 
                   LEFT JOIN users u ON t.created_by = u.user_id 
                   WHERE t.transaction_date BETWEEN ? AND ?";
$queryParams = [$startDate, $endDate];
$queryTypes = 'ss';

// Add category filter if specified
if ($category !== 'all') {
    $transactionsSql .= " AND t.category_id = ?";
    $queryParams[] = $category;
    $queryTypes .= 'i';
}

// Add type filter if specified
if ($type !== 'all') {
    $transactionsSql .= " AND t.transaction_type = ?";
    $queryParams[] = $type;
    $queryTypes .= 's';
}

// Add order by clause
$transactionsSql .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

// Execute the query
$transactions = fetchAll($transactionsSql, $queryParams, $queryTypes);

// Calculate totals
$totalIncome = 0;
$totalExpenses = 0;
$categoryTotals = [];

foreach ($transactions as $transaction) {
    if ($transaction['transaction_type'] === 'income') {
        $totalIncome += $transaction['amount'];
    } else {
        $totalExpenses += $transaction['amount'];
    }
    
    // Track totals by category
    $categoryName = $transaction['category_name'];
    if (!isset($categoryTotals[$categoryName])) {
        $categoryTotals[$categoryName] = [
            'income' => 0,
            'expense' => 0
        ];
    }
    
    if ($transaction['transaction_type'] === 'income') {
        $categoryTotals[$categoryName]['income'] += $transaction['amount'];
    } else {
        $categoryTotals[$categoryName]['expense'] += $transaction['amount'];
    }
}

// Calculate net balance
$netBalance = $totalIncome - $totalExpenses;

// Set page title
$pageTitle = "Financial Reports";
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
                <!-- Custom Finance Reports Header -->
                <div class="finance-reports-header animate__animated animate__fadeInDown">
                    <div class="finance-reports-header-content">
                        <div class="finance-reports-header-main">
                            <h1 class="finance-reports-title">
                                <i class="fas fa-chart-bar me-3"></i>
                                Financial Reports
                            </h1>
                            <p class="finance-reports-description">View comprehensive financial reports and analytics</p>
                        </div>
                        <div class="finance-reports-header-actions">
                            <button type="button" class="btn btn-header-action me-2" onclick="printReport()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <a href="finance.php" class="btn btn-header-action">
                                <i class="fas fa-arrow-left me-2"></i>Back to Finance
                            </a>
                        </div>
                    </div>
                </div>

                <style>
                .finance-reports-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 2.5rem 2rem;
                    border-radius: 12px;
                    margin-top: 60px;
                    margin-bottom: 0;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                }

                .finance-reports-header-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 1.5rem;
                }

                .finance-reports-header-main {
                    flex: 1;
                    text-align: center;
                }

                .finance-reports-title {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin: 0 0 1rem 0;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.8rem;
                }

                .finance-reports-title i {
                    font-size: 2.2rem;
                    opacity: 0.9;
                }

                .finance-reports-description {
                    margin: 0;
                    opacity: 0.95;
                    font-size: 1.2rem;
                    font-weight: 400;
                    line-height: 1.4;
                }

                .finance-reports-header-actions {
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
                    .finance-reports-header {
                        padding: 1.5rem;
                    }

                    .finance-reports-header-content {
                        flex-direction: column;
                        align-items: center;
                    }

                    .finance-reports-title {
                        font-size: 1.75rem;
                        gap: 0.5rem;
                    }

                    .finance-reports-title i {
                        font-size: 1.5rem;
                    }

                    .finance-reports-description {
                        font-size: 1rem;
                    }

                    .finance-reports-header-actions {
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
                    .finance-reports-header {
                        margin-left: -1rem;
                        margin-right: -1rem;
                        border-radius: 0;
                        padding: 1rem !important;
                    }

                    .finance-reports-header-content {
                        flex-direction: column;
                        align-items: center;
                        gap: 0.75rem;
                    }

                    .finance-reports-header-main {
                        width: 100%;
                    }

                    .finance-reports-title {
                        font-size: 1.4rem;
                        gap: 0.4rem;
                        justify-content: center;
                        font-weight: 700;
                    }

                    .finance-reports-title i {
                        font-size: 1.2rem;
                    }

                    .finance-reports-description {
                        font-size: 0.9rem;
                        font-weight: 400;
                    }

                    .finance-reports-header-actions {
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

                    .col-lg-12,
                    .col-md-12 {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
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

                    /* Form improvements */
                    .form-label {
                        font-size: 0.95rem;
                        font-weight: 600;
                        margin-bottom: 0.5rem;
                    }

                    .form-control,
                    .form-select {
                        font-size: 1rem;
                        padding: 0.75rem;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    }

                    /* Table responsiveness */
                    .table-responsive {
                        margin: 0;
                        padding: 0;
                        -webkit-overflow-scrolling: touch;
                    }

                    .table {
                        font-size: 0.85rem;
                        margin-bottom: 0;
                    }

                    .table th {
                        padding: 0.75rem !important;
                        font-size: 0.8rem !important;
                        font-weight: 600;
                        background-color: #f8f9fa;
                        border-bottom: 2px solid #dee2e6;
                    }

                    .table td {
                        padding: 0.75rem !important;
                        font-size: 0.85rem !important;
                        border-bottom: 1px solid #f1f3f4;
                    }
                }

                /* Extra small devices (320px - 375px) */
                @media (max-width: 375px) {
                    .finance-reports-header {
                        padding: 0.75rem !important;
                    }

                    .finance-reports-title {
                        font-size: 1.2rem;
                        gap: 0.3rem;
                    }

                    .finance-reports-title i {
                        font-size: 1.1rem;
                    }

                    .finance-reports-description {
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

                    .alert {
                        padding: 0.85rem !important;
                        font-size: 0.9rem;
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

                    .form-label {
                        font-size: 0.9rem;
                    }

                    .form-control,
                    .form-select {
                        font-size: 0.95rem;
                        padding: 0.65rem;
                    }

                    .table {
                        font-size: 0.8rem;
                    }

                    .table th,
                    .table td {
                        padding: 0.6rem !important;
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

                /* Mobile Full-Width Optimization for Finance Reports Page */
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
                    .card, .report-card, .chart-card {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        border-radius: 0 !important;
                    }
                }
                </style>

                <!-- Filters -->
                <div class="card border-0 shadow-sm mb-4" style="margin-top: 1.5rem;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Report Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <div class="input-group">
                                    <select class="form-control" id="category" name="category">
                                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="input-group-append">
                                        <a href="finance-categories.php" class="btn btn-outline-secondary" title="Manage Categories">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Transaction Type</label>
                                <select class="form-control" id="type" name="type">
                                    <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <option value="income" <?php echo $type === 'income' ? 'selected' : ''; ?>>Income</option>
                                    <option value="expense" <?php echo $type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                </button>
                                <a href="finance-reports.php" class="btn btn-outline-secondary ms-2">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Summary -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Income</h5>
                                <h2 class="text-success">₵<?php echo number_format($totalIncome, 2); ?></h2>
                                <p class="text-muted mb-0">Total income for selected period</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Expenses</h5>
                                <h2 class="text-danger">₵<?php echo number_format($totalExpenses, 2); ?></h2>
                                <p class="text-muted mb-0">Total expenses for selected period</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Net Balance</h5>
                                <h2 class="<?php echo $netBalance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    ₵<?php echo number_format(abs($netBalance), 2); ?>
                                    <?php echo $netBalance >= 0 ? 'Surplus' : 'Deficit'; ?>
                                </h2>
                                <p class="text-muted mb-0">Net balance for selected period</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Category Breakdown</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Income</th>
                                                <th>Expenses</th>
                                                <th>Net</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($categoryTotals)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No data available for the selected filters</td>
                                            </tr>
                                            <?php else: ?>
                                                <?php foreach ($categoryTotals as $catName => $totals): 
                                                    $catNet = $totals['income'] - $totals['expense'];
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($catName); ?></td>
                                                    <td class="text-success">₵<?php echo number_format($totals['income'], 2); ?></td>
                                                    <td class="text-danger">₵<?php echo number_format($totals['expense'], 2); ?></td>
                                                    <td class="<?php echo $catNet >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                        ₵<?php echo number_format(abs($catNet), 2); ?>
                                                        <?php echo $catNet >= 0 ? '' : '(Deficit)'; ?>
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
                </div>

                <!-- Transaction Details -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Transaction Details</h5>
                                <span class="badge bg-primary"><?php echo count($transactions); ?> Transactions</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($transactions)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                                    <h5>No transactions found</h5>
                                    <p class="text-muted">Try adjusting your filters to see more results</p>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="transactionsTable">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Category</th>
                                                <th>Amount</th>
                                                <th>Type</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                                <td>₵<?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $transaction['transaction_type'] === 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($transaction['created_by_name']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script src="../js/budget.js"></script>
    <script>
        // Print report function
        function printReport() {
            window.print();
        }
        
        // Initialize date range validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            endDateInput.addEventListener('change', function() {
                if (startDateInput.value && this.value && this.value < startDateInput.value) {
                    alert('End date cannot be earlier than start date');
                    this.value = startDateInput.value;
                }
            });
            
            startDateInput.addEventListener('change', function() {
                if (endDateInput.value && this.value && this.value > endDateInput.value) {
                    endDateInput.value = this.value;
                }
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>