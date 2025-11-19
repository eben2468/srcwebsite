<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if finance feature is enabled
if (!hasFeaturePermission('enable_finance')) {
    header('Location: ../dashboard.php?error=feature_disabled');
    exit();
}

// Check if user has finance access (super admin or finance role)
if (!isSuperAdmin() && !isFinance()) {
    header("Location: dashboard.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];
$userRole = $currentUser['role'];

// Check if user has permission to manage budget
$canManageBudget = hasPermission('create', 'finance');
$canApproveBudget = isSuperAdmin() || isFinance(); // Super admin and finance can approve

// Get fiscal year for filtering (default to current year)
$currentYear = date('Y');
$fiscalYear = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : $currentYear . '/' . ($currentYear + 1);

// Fetch budget summary
$budgetSummary = [];
$budgetSql = "SELECT * FROM budget WHERE fiscal_year = ? LIMIT 1";
$budgetData = fetchOne($budgetSql, [$fiscalYear]);

if (!$budgetData) {
    // No budget found for this fiscal year, create default structure
    $budgetData = [
        'budget_id' => 0,
        'fiscal_year' => $fiscalYear,
        'total_amount' => 0,
        'allocated_amount' => 0,
        'remaining_amount' => 0,
        'status' => 'draft'
    ];
}

// Fetch budget categories
$categoriesSql = "SELECT * FROM budget_categories WHERE budget_id = ?";
$categories = fetchAll($categoriesSql, [$budgetData['budget_id']]);

// Fetch recent transactions (last 10)
$transactionsSql = "SELECT t.*, c.name as category_name, u.username as created_by_name
                   FROM budget_transactions t
                   LEFT JOIN budget_categories c ON t.category_id = c.category_id
                   LEFT JOIN users u ON t.created_by = u.user_id
                   ORDER BY t.transaction_date DESC, t.created_at DESC
                   LIMIT 10";
$recentTransactions = fetchAll($transactionsSql);

// Calculate total income and expenses
$incomeSql = "SELECT SUM(amount) as total FROM budget_transactions WHERE transaction_type = 'income'";
$expenseSql = "SELECT SUM(amount) as total FROM budget_transactions WHERE transaction_type = 'expense'";

$totalIncome = fetchOne($incomeSql)['total'] ?? 0;
$totalExpenses = fetchOne($expenseSql)['total'] ?? 0;

// Set page title
$pageTitle = "Financial Management";
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
    <?php
    // Set page variables
    $pageTitle = "Financial Management";
    $bodyClass = 'dashboard-page';

    // Include header first
    include 'includes/header.php';
    ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <script>
                    document.body.classList.add('finance-page');
                </script>

                <!-- Custom Finance Header -->
                <div class="finance-header animate__animated animate__fadeInDown">
                    <div class="finance-header-content">
                        <div class="finance-header-main">
                            <h1 class="finance-title">
                                <i class="fas fa-coins me-3"></i>
                                Financial Management
                            </h1>
                            <p class="finance-description">Manage SRC finances, budgets, and transactions</p>
                        </div>
                        <div class="finance-header-actions">
                            <?php if ($canManageBudget): ?>
                            <a href="finance-add-record.php" class="btn btn-header-action">
                                <i class="fas fa-plus me-2"></i>Add Transaction
                            </a>
                            <a href="finance-categories.php" class="btn btn-header-action">
                                <i class="fas fa-tags me-2"></i>Add Category
                            </a>
                            <a href="finance-reports.php" class="btn btn-header-action">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                            <?php endif; ?>
                            <?php if ($canApproveBudget): ?>
                            <a href="finance-approvals.php" class="btn btn-header-action">
                                <i class="fas fa-check-circle me-2"></i>Approvals
                            </a>
                            <?php endif; ?>
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
                    align-items: flex-start;
                    flex-wrap: wrap;
                    gap: 1.5rem;
                }

                .finance-header-main {
                    flex: 1;
                    text-align: left;
                    min-width: 0;
                }

                .finance-title {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin: 0 0 0.5rem 0;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    display: flex;
                    align-items: center;
                    justify-content: flex-start;
                    gap: 0.8rem;
                    line-height: 1.2;
                }

                .finance-title i {
                    font-size: 2.2rem;
                    opacity: 0.9;
                    flex-shrink: 0;
                }

                .finance-description {
                    margin: 0;
                    opacity: 0.95;
                    font-size: 1.1rem;
                    font-weight: 400;
                    line-height: 1.4;
                }

                .finance-header-actions {
                    display: flex;
                    flex-direction: column;
                    align-items: flex-end;
                    gap: 0.8rem;
                    flex-wrap: wrap;
                    flex-shrink: 0;
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
                    .finance-header {
                        padding: 2rem 1.5rem;
                    }

                    .finance-header-content {
                        flex-direction: column;
                        align-items: center;
                        text-align: center;
                        gap: 1rem;
                    }

                    .finance-header-main {
                        text-align: center;
                        width: 100%;
                    }

                    .finance-title {
                        font-size: 2rem;
                        gap: 0.6rem;
                        justify-content: center;
                        flex-wrap: wrap;
                    }

                    .finance-title i {
                        font-size: 1.8rem;
                    }

                    .finance-description {
                        font-size: 1rem;
                        text-align: center;
                    }

                    .finance-header-actions {
                        width: 100%;
                        justify-content: center;
                        align-items: center;
                        flex-direction: row;
                        flex-wrap: wrap;
                        gap: 0.5rem;
                    }

                    .btn-header-action {
                        font-size: 0.85rem;
                        padding: 0.5rem 0.75rem;
                        flex: 1;
                        min-width: 120px;
                        max-width: 150px;
                        text-align: center;
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
                        text-align: center;
                        gap: 0.75rem;
                        align-items: center;
                    }

                    .finance-header-main {
                        text-align: center;
                        width: 100%;
                    }

                    .finance-title {
                        font-size: 1.5rem;
                        justify-content: center;
                        text-align: center;
                        gap: 0.5rem;
                        font-weight: 700;
                    }

                    .finance-title i {
                        font-size: 1.3rem;
                    }

                    .finance-description {
                        font-size: 0.95rem;
                        text-align: center;
                        font-weight: 400;
                    }

                    .finance-header-actions {
                        flex-direction: column;
                        gap: 0.5rem;
                        width: 100%;
                        align-items: center;
                    }

                    .finance-header-actions .btn {
                        width: 100%;
                        font-size: 0.9rem;
                        padding: 0.6rem 1rem;
                        text-align: center;
                    }

                    .main-content {
                        padding: 0 !important;
                        margin: 0 !important;
                    }

                    /* Budget summary cards - single column on mobile */
                    .budget-summary {
                        grid-template-columns: 1fr;
                        gap: 1rem;
                        margin-bottom: 1.5rem;
                        padding: 0 !important;
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                    }

                    .budget-card {
                        padding: 1.25rem;
                        text-align: center;
                        border-radius: 0;
                        margin: 0 !important;
                    }

                    .budget-card-icon {
                        width: 55px;
                        height: 55px;
                        font-size: 1.75rem;
                        margin: 0 auto 0.75rem;
                    }

                    .budget-card-title {
                        font-size: 0.95rem;
                        margin-bottom: 0.5rem;
                        font-weight: 600;
                    }

                    .budget-card-amount {
                        font-size: 1.6rem;
                        margin-bottom: 0.5rem;
                        font-weight: 700;
                    }

                    .budget-card-count {
                        font-size: 0.85rem;
                        color: #6c757d;
                    }

                    /* Table responsiveness */
                    .budget-table-container {
                        margin: 0;
                        overflow-x: auto;
                        -webkit-overflow-scrolling: touch;
                        padding: 0;
                    }

                    .budget-table {
                        font-size: 0.85rem;
                        min-width: 100%;
                        border-collapse: collapse;
                        margin: 0;
                    }

                    .budget-table th {
                        padding: 0.75rem !important;
                        font-size: 0.8rem !important;
                        font-weight: 600;
                        background-color: #f8f9fa;
                        border-bottom: 2px solid #dee2e6;
                        text-align: center !important;
                    }

                    .budget-table td {
                        padding: 0.75rem !important;
                        font-size: 0.85rem !important;
                        border-bottom: 1px solid #f1f3f4;
                        text-align: center !important;
                    }

                    .budget-table td:first-child {
                        text-align: left !important;
                        font-weight: 600;
                        font-size: 0.9rem !important;
                    }

                    /* Hide less important columns on mobile */
                    .budget-table th:nth-child(4),
                    .budget-table td:nth-child(4) {
                        display: none; /* Hide progress column */
                    }

                    /* Row spacing - remove excessive padding */
                    .row {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                    }

                    .row > * {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }

                    .col-md-3,
                    .col-md-4,
                    .col-md-6,
                    .col-md-12,
                    .col-lg-10 {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }

                    /* Card styles on mobile */
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

                    /* Budget summary cards */
                    .budget-card {
                        border-radius: 12px;
                        overflow: hidden;
                    }

                    /* Alert improvements */
                    .alert {
                        padding: 1rem !important;
                        font-size: 0.95rem;
                        margin-bottom: 1rem !important;
                        margin-left: 0;
                        margin-right: 0;
                        border-radius: 0;
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

                    /* Form improvements */
                    .form-label {
                        font-size: 0.95rem;
                        font-weight: 500;
                        margin-bottom: 0.5rem;
                    }

                    .form-control {
                        font-size: 1rem;
                        padding: 0.75rem;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    }

                    /* Mobile card layout styles */
                    .mobile-budget-cards {
                        display: block;
                        padding: 0;
                        margin: 0;
                    }

                    .mobile-budget-card {
                        background: white;
                        border: 1px solid #e9ecef;
                        border-radius: 8px;
                        padding: 1rem;
                        margin-bottom: 1rem;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .mobile-budget-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 0.75rem;
                        padding-bottom: 0.75rem;
                        border-bottom: 1px solid #f8f9fa;
                    }

                    .mobile-budget-name {
                        font-weight: 600;
                        color: #495057;
                        font-size: 1rem;
                    }

                    .mobile-budget-actions {
                        display: flex;
                        gap: 0.5rem;
                    }

                    .mobile-budget-actions .btn {
                        padding: 0.4rem 0.6rem;
                        font-size: 0.8rem;
                    }

                    .mobile-budget-details {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 0.75rem;
                        font-size: 0.9rem;
                    }

                    .mobile-detail-item {
                        display: flex;
                        flex-direction: column;
                    }

                    .mobile-detail-label {
                        font-weight: 600;
                        color: #6c757d;
                        font-size: 0.8rem;
                        text-transform: uppercase;
                        margin-bottom: 0.25rem;
                    }

                    .mobile-detail-value {
                        color: #495057;
                        font-size: 0.95rem;
                        font-weight: 500;
                    }

                    .mobile-detail-value.allocated {
                        color: #007bff;
                        font-weight: 700;
                    }

                    .mobile-detail-value.spent {
                        color: #dc3545;
                        font-weight: 700;
                    }

                    .mobile-detail-value.remaining {
                        color: #28a745;
                        font-weight: 700;
                    }
                }

                /* Extra small devices (320px - 375px) */
                @media (max-width: 375px) {
                    .finance-header {
                        padding: 0.75rem !important;
                    }

                    .finance-header-content {
                        gap: 0.75rem;
                    }

                    .finance-title {
                        font-size: 1.3rem;
                        gap: 0.4rem;
                        justify-content: center;
                        text-align: center;
                        font-weight: 700;
                    }

                    .finance-title i {
                        font-size: 1.2rem;
                    }

                    .finance-description {
                        font-size: 0.9rem;
                        text-align: center;
                    }

                    .finance-header-actions .btn {
                        font-size: 0.9rem;
                        padding: 0.55rem 0.9rem;
                        width: 100%;
                    }

                    .main-content {
                        padding: 0 !important;
                    }

                    .budget-summary {
                        gap: 0.75rem;
                    }

                    .budget-card {
                        padding: 1rem;
                    }

                    .budget-card-icon {
                        width: 50px;
                        height: 50px;
                        font-size: 1.5rem;
                        margin-bottom: 0.5rem;
                    }

                    .budget-card-title {
                        font-size: 0.9rem;
                        font-weight: 600;
                    }

                    .budget-card-amount {
                        font-size: 1.4rem;
                        font-weight: 700;
                    }

                    .budget-card-count {
                        font-size: 0.8rem;
                    }

                    /* Compact table */
                    .budget-table {
                        font-size: 0.8rem;
                        min-width: 100%;
                    }

                    .budget-table th,
                    .budget-table td {
                        padding: 0.6rem !important;
                    }

                    /* Hide even more columns on very small screens */
                    .budget-table th:nth-child(3),
                    .budget-table td:nth-child(3) {
                        display: none; /* Hide spent column */
                    }

                    /* Mobile card layout styles */
                    .mobile-budget-cards {
                        display: block;
                    }

                    .mobile-budget-card {
                        background: white;
                        border: 1px solid #e9ecef;
                        border-radius: 8px;
                        padding: 0.9rem;
                        margin-bottom: 0.85rem;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .mobile-budget-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 0.65rem;
                        padding-bottom: 0.65rem;
                        border-bottom: 1px solid #f8f9fa;
                    }

                    .mobile-budget-name {
                        font-weight: 600;
                        color: #495057;
                        font-size: 0.95rem;
                    }

                    .mobile-budget-actions {
                        display: flex;
                        gap: 0.4rem;
                    }

                    .mobile-budget-actions .btn {
                        padding: 0.35rem 0.55rem;
                        font-size: 0.75rem;
                    }

                    .mobile-budget-details {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 0.6rem;
                        font-size: 0.85rem;
                    }

                    .mobile-detail-item {
                        display: flex;
                        flex-direction: column;
                    }

                    .mobile-detail-label {
                        font-weight: 600;
                        color: #6c757d;
                        font-size: 0.75rem;
                        text-transform: uppercase;
                        margin-bottom: 0.2rem;
                    }

                    .mobile-detail-value {
                        color: #495057;
                        font-size: 0.9rem;
                        font-weight: 500;
                    }

                    .mobile-detail-value.allocated {
                        color: #007bff;
                        font-weight: 700;
                    }

                    .mobile-detail-value.spent {
                        color: #dc3545;
                        font-weight: 700;
                    }

                    .mobile-detail-value.remaining {
                        color: #28a745;
                        font-weight: 700;
                    }

                    /* Card and form improvements */
                    .card {
                        border-radius: 0;
                        margin-bottom: 0.75rem !important;
                    }

                    .card-header {
                        padding: 0.9rem !important;
                        font-size: 1rem;
                    }

                    .card-body {
                        padding: 0.9rem !important;
                    }

                    .form-label {
                        font-size: 0.9rem;
                    }

                    .form-control {
                        font-size: 0.9rem;
                        padding: 0.6rem 0.75rem;
                    }

                    .btn {
                        font-size: 0.9rem;
                        padding: 0.5rem 0.9rem;
                    }
                }                        border-bottom: 1px solid #f8f9fa;
                    }

                    .mobile-budget-name {
                        font-weight: 600;
                        color: #495057;
                        font-size: 0.9rem;
                    }

                    .mobile-budget-details {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 0.5rem;
                        font-size: 0.8rem;
                    }

                    .mobile-budget-item {
                        display: flex;
                        flex-direction: column;
                    }

                    .mobile-budget-label {
                        font-weight: 600;
                        color: #6c757d;
                        font-size: 0.7rem;
                        text-transform: uppercase;
                        margin-bottom: 0.125rem;
                    }

                    .mobile-budget-value {
                        color: #495057;
                        font-weight: 600;
                    }

                    .mobile-budget-value.allocated {
                        color: #007bff;
                    }

                    .mobile-budget-value.spent {
                        color: #dc3545;
                    }

                    .mobile-budget-value.remaining {
                        color: #28a745;
                    }

                    .mobile-budget-value.remaining.negative {
                        color: #dc3545;
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
                
                /* Mobile Full-Width Optimization for Finance Page */
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
                    .card, .budget-card, .budget-table-container {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        border-radius: 0 !important;
                    }
                }
                </style>

                <!-- Budget Summary -->
                <div class="budget-summary">
                    <div class="budget-card total">
                        <div class="budget-card-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="budget-card-title">Total Budget</div>
                        <div class="budget-card-amount">₵<?php echo number_format($budgetData['total_amount'], 2); ?></div>
                        <div class="budget-card-count"><?php echo $budgetData['fiscal_year']; ?></div>
                    </div>

                    <div class="budget-card approved">
                        <div class="budget-card-icon">
                            <i class="fas fa-arrow-circle-down"></i>
                        </div>
                        <div class="budget-card-title">Total Income</div>
                        <div class="budget-card-amount">₵<?php echo number_format($totalIncome, 2); ?></div>
                        <div class="budget-card-count">All time</div>
                    </div>

                    <div class="budget-card declined">
                        <div class="budget-card-icon">
                            <i class="fas fa-arrow-circle-up"></i>
                        </div>
                        <div class="budget-card-title">Total Expenses</div>
                        <div class="budget-card-amount">₵<?php echo number_format($totalExpenses, 2); ?></div>
                        <div class="budget-card-count">All time</div>
                    </div>

                    <div class="budget-card pending">
                        <div class="budget-card-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <div class="budget-card-title">Balance</div>
                        <div class="budget-card-amount">₵<?php echo number_format($totalIncome - $totalExpenses, 2); ?></div>
                        <div class="budget-card-count">Available funds</div>
                    </div>
                </div>

                <!-- Budget Categories -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="budget-table-container">
                            <?php ?>
<div class="table-responsive">
                                <table class="budget-table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Allocated</th>
                                            <th>Spent</th>
                                            <th>Remaining</th>
                                            <th>Usage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">No budget categories found. <?php if ($canManageBudget): ?><a href="finance-categories.php">Add categories</a><?php endif; ?></td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $category):
                                                $usagePercentage = $category['allocated_amount'] > 0 ?
                                                    round(($category['spent_amount'] / $category['allocated_amount']) * 100) : 0;
                                                $variant = 'success';
                                                if ($usagePercentage > 90) $variant = 'danger';
                                                else if ($usagePercentage > 70) $variant = 'warning';
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td>₵<?php echo number_format($category['allocated_amount'], 2); ?></td>
                                                <td>₵<?php echo number_format($category['spent_amount'], 2); ?></td>
                                                <td>₵<?php echo number_format($category['allocated_amount'] - $category['spent_amount'], 2); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress-container">
                                                            <div class="progress-bar <?php echo $variant; ?>" style="width: <?php echo $usagePercentage; ?>%"></div>
                                                        </div>
                                                        <span class="ms-2"><?php echo $usagePercentage; ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="budget-table-container">
                            <?php ?>
<div class="table-responsive">
                                <table class="budget-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th>Type</th>
                                            <?php if ($canManageBudget): ?>
                                            <th>
<?php endif; ?>Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="<?php echo $canManageBudget ? '6' : '5'; ?>" class="text-center py-3">No transactions found.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                                <td>₵<?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $transaction['transaction_type']; ?>">
                                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <?php if ($canManageBudget): ?>
                                                <td>
                                                    <a href="finance-edit-record.php?id=<?php echo $transaction['transaction_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>





    <script>
    // Mobile layout functions for finance page
    function checkMobileLayout() {
        const isMobile = window.innerWidth <= 375;
        const budgetTableContainer = document.querySelector('.budget-table-container');

        if (isMobile && budgetTableContainer) {
            convertTablesToMobileCards();
        } else {
            restoreTableLayout();
        }
    }

    function convertTablesToMobileCards() {
        const tables = document.querySelectorAll('.budget-table');

        tables.forEach((table, index) => {
            if (window.innerWidth <= 375) {
                table.style.display = 'none';

                // Create mobile cards if they don't exist
                const containerId = `mobile-cards-container-${index}`;
                if (!document.getElementById(containerId)) {
                    createMobileCardsFromTable(table, index);
                }
            }
        });
    }

    function restoreTableLayout() {
        const tables = document.querySelectorAll('.budget-table');
        const mobileContainers = document.querySelectorAll('[id^="mobile-cards-container-"]');

        tables.forEach(table => {
            table.style.display = 'table';
        });

        mobileContainers.forEach(container => {
            container.remove();
        });
    }

    function createMobileCardsFromTable(table, tableIndex) {
        const rows = table.querySelectorAll('tbody tr');
        const container = table.closest('.budget-table-container');

        if (!container || rows.length === 0) return;

        const mobileContainer = document.createElement('div');
        mobileContainer.id = `mobile-cards-container-${tableIndex}`;
        mobileContainer.className = 'mobile-budget-cards';

        // Determine table type based on headers
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const isCategoryTable = headers.includes('Category') && headers.includes('Allocated');
        const isTransactionTable = headers.includes('Date') && headers.includes('Description');

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 0) return;

            const card = document.createElement('div');
            card.className = 'mobile-budget-card';

            if (isCategoryTable) {
                createCategoryCard(card, cells);
            } else if (isTransactionTable) {
                createTransactionCard(card, cells);
            }

            mobileContainer.appendChild(card);
        });

        container.appendChild(mobileContainer);
    }

    function createCategoryCard(card, cells) {
        const category = cells[0]?.textContent.trim() || 'Unknown';
        const allocated = cells[1]?.textContent.trim() || '₵0.00';
        const spent = cells[2]?.textContent.trim() || '₵0.00';
        const remaining = cells[3]?.textContent.trim() || '₵0.00';

        card.innerHTML = `
            <div class="mobile-budget-header">
                <div class="mobile-budget-name">${category}</div>
            </div>
            <div class="mobile-budget-details">
                <div class="mobile-budget-item">
                    <div class="mobile-budget-label">Allocated</div>
                    <div class="mobile-budget-value allocated">${allocated}</div>
                </div>
                <div class="mobile-budget-item">
                    <div class="mobile-budget-label">Spent</div>
                    <div class="mobile-budget-value spent">${spent}</div>
                </div>
                <div class="mobile-budget-item">
                    <div class="mobile-budget-label">Remaining</div>
                    <div class="mobile-budget-value remaining ${remaining.includes('-') ? 'negative' : ''}">${remaining}</div>
                </div>
            </div>
        `;
    }

    function createTransactionCard(card, cells) {
        const date = cells[0]?.textContent.trim() || 'Unknown';
        const description = cells[1]?.textContent.trim() || 'No description';
        const type = cells[2]?.textContent.trim() || 'Unknown';
        const amount = cells[3]?.textContent.trim() || '₵0.00';
        const category = cells[4]?.textContent.trim() || 'Uncategorized';

        card.innerHTML = `
            <div class="mobile-budget-header">
                <div class="mobile-budget-name">${description}</div>
                <div class="mobile-budget-value ${type.toLowerCase() === 'income' ? 'allocated' : 'spent'}">${amount}</div>
            </div>
            <div class="mobile-budget-details">
                <div class="mobile-budget-item">
                    <div class="mobile-budget-label">Date</div>
                    <div class="mobile-budget-value">${date}</div>
                </div>
                <div class="mobile-budget-item">
                    <div class="mobile-budget-label">Type</div>
                    <div class="mobile-budget-value">${type}</div>
                </div>
                <div class="mobile-budget-item">
                    <div class="mobile-budget-label">Category</div>
                    <div class="mobile-budget-value">${category}</div>
                </div>
            </div>
        `;
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Finance page loaded - checking mobile layout');
        checkMobileLayout();

        // Handle window resize
        window.addEventListener('resize', function() {
            checkMobileLayout();
        });

        // Initialize tooltips
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });
    </script>
<?php include 'includes/footer.php'; ?>

