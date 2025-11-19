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

// Initialize variables
$successMessage = '';
$errorMessage = '';
$formData = [
    'name' => '',
    'description' => '',
    'allocated_amount' => ''
];

// Get current active budget
$budgetSql = "SELECT * FROM budget WHERE status = 'approved' ORDER BY created_at DESC LIMIT 1";
$currentBudget = fetchOne($budgetSql);

if (!$currentBudget) {
    // No active budget found, create a default one
    $fiscalYear = date('Y') . '/' . (date('Y') + 1);

    $insertBudgetSql = "INSERT INTO budget (fiscal_year, total_amount, allocated_amount, remaining_amount, status, created_by)
                        VALUES (?, 100000.00, 0.00, 100000.00, 'approved', ?)";
    $insertBudgetParams = [$fiscalYear, $userId];
    $insertBudgetTypes = 'si';

    $budgetId = insert($insertBudgetSql, $insertBudgetParams, $insertBudgetTypes);

    if ($budgetId) {
        $currentBudget = fetchOne("SELECT * FROM budget WHERE budget_id = ?", [$budgetId]);
    } else {
        $errorMessage = "Failed to create a default budget. Please contact the administrator.";
    }
}

// Process form submission for adding/editing category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Check if user has permission to modify budget
    if (!hasPermission('update', 'budget') && !hasPermission('create', 'budget')) {
        $errorMessage = "You don't have permission to modify budget categories.";
    } else {
        try {
            $action = $_POST['action'];

            if ($action === 'add') {
            // Add new category
            $formData = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'allocated_amount' => isset($_POST['allocated_amount']) ? (float)$_POST['allocated_amount'] : 0
            ];

            // Validate required fields
            if (empty($formData['name'])) {
                throw new Exception('Category name is required');
            }

            if ($formData['allocated_amount'] < 0) {
                throw new Exception('Allocated amount cannot be negative');
            }

            // Start transaction
            mysqli_begin_transaction($conn);

            // Insert category
            $insertSql = "INSERT INTO budget_categories (budget_id, name, description, allocated_amount)
                         VALUES (?, ?, ?, ?)";

            $insertParams = [
                $currentBudget['budget_id'],
                $formData['name'],
                $formData['description'],
                $formData['allocated_amount']
            ];

            $insertTypes = 'issd';

            $categoryId = insert($insertSql, $insertParams, $insertTypes);

            if (!$categoryId) {
                throw new Exception('Failed to add category');
            }

            // Update budget allocated and remaining amounts
            $updateBudgetSql = "UPDATE budget
                               SET allocated_amount = allocated_amount + ?,
                                   remaining_amount = total_amount - (allocated_amount + ?)
                               WHERE budget_id = ?";

            $updateBudgetParams = [$formData['allocated_amount'], $formData['allocated_amount'], $currentBudget['budget_id']];
            $updateBudgetTypes = 'ddi';

            $updateResult = update($updateBudgetSql, $updateBudgetParams, $updateBudgetTypes);

            if ($updateResult === false) {
                throw new Exception('Failed to update budget allocation');
            }

            // Commit transaction
            mysqli_commit($conn);

            // Set success message
            $successMessage = 'Category added successfully';

            // Reset form data
            $formData = [
                'name' => '',
                'description' => '',
                'allocated_amount' => ''
            ];

            // Refresh current budget data
            $currentBudget = fetchOne($budgetSql);

        } elseif ($action === 'edit' && isset($_POST['category_id'])) {
            // Edit existing category
            $categoryId = (int)$_POST['category_id'];
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $allocatedAmount = isset($_POST['allocated_amount']) ? (float)$_POST['allocated_amount'] : 0;

            // Validate required fields
            if (empty($name)) {
                throw new Exception('Category name is required');
            }

            if ($allocatedAmount < 0) {
                throw new Exception('Allocated amount cannot be negative');
            }

            // Get current category data
            $categorySql = "SELECT * FROM budget_categories WHERE category_id = ?";
            $category = fetchOne($categorySql, [$categoryId]);

            if (!$category) {
                throw new Exception('Category not found');
            }

            // Calculate difference in allocation
            $allocationDifference = $allocatedAmount - $category['allocated_amount'];

            // Start transaction
            mysqli_begin_transaction($conn);

            // Update category
            $updateSql = "UPDATE budget_categories
                         SET name = ?, description = ?, allocated_amount = ?
                         WHERE category_id = ?";

            $updateParams = [$name, $description, $allocatedAmount, $categoryId];
            $updateTypes = 'ssdi';

            $updateResult = update($updateSql, $updateParams, $updateTypes);

            if ($updateResult === false) {
                throw new Exception('Failed to update category');
            }

            // Update budget allocated and remaining amounts if allocation changed
            if ($allocationDifference != 0) {
                $updateBudgetSql = "UPDATE budget
                                   SET allocated_amount = allocated_amount + ?,
                                       remaining_amount = remaining_amount - ?
                                   WHERE budget_id = ?";

                $updateBudgetParams = [$allocationDifference, $allocationDifference, $currentBudget['budget_id']];
                $updateBudgetTypes = 'ddi';

                $updateBudgetResult = update($updateBudgetSql, $updateBudgetParams, $updateBudgetTypes);

                if ($updateBudgetResult === false) {
                    throw new Exception('Failed to update budget allocation');
                }
            }

            // Commit transaction
            mysqli_commit($conn);

            // Set success message
            $successMessage = 'Category updated successfully';

            // Refresh current budget data
            $currentBudget = fetchOne($budgetSql);
        }

        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);

            // Set error message
            $errorMessage = $e->getMessage();
        }
    }
}

// Process category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['category_id'])) {
    // Check if user has permission to delete budget categories
    if (!hasPermission('delete', 'budget')) {
        $errorMessage = "You don't have permission to delete budget categories.";
    } else {
        try {
        $categoryId = (int)$_POST['category_id'];

        // Get category data
        $categorySql = "SELECT * FROM budget_categories WHERE category_id = ?";
        $category = fetchOne($categorySql, [$categoryId]);

        if (!$category) {
            throw new Exception('Category not found');
        }

        // Check if category has transactions
        $transactionsSql = "SELECT COUNT(*) as count FROM budget_transactions WHERE category_id = ?";
        $transactionsCount = fetchOne($transactionsSql, [$categoryId])['count'] ?? 0;

        if ($transactionsCount > 0) {
            throw new Exception('Cannot delete category with existing transactions');
        }

        // Start transaction
        mysqli_begin_transaction($conn);

        // Delete category
        $deleteSql = "DELETE FROM budget_categories WHERE category_id = ?";
        $deleteResult = delete($deleteSql, [$categoryId], 'i');

        if ($deleteResult === false) {
            throw new Exception('Failed to delete category');
        }

        // Update budget allocated and remaining amounts
        $updateBudgetSql = "UPDATE budget
                           SET allocated_amount = allocated_amount - ?,
                               remaining_amount = remaining_amount + ?
                           WHERE budget_id = ?";

        $updateBudgetParams = [$category['allocated_amount'], $category['allocated_amount'], $currentBudget['budget_id']];
        $updateBudgetTypes = 'ddi';

        $updateBudgetResult = update($updateBudgetSql, $updateBudgetParams, $updateBudgetTypes);

        if ($updateBudgetResult === false) {
            throw new Exception('Failed to update budget allocation');
        }

        // Commit transaction
        mysqli_commit($conn);

        // Set success message
        $successMessage = 'Category deleted successfully';

        // Refresh current budget data
        $currentBudget = fetchOne($budgetSql);

        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);

            // Set error message
            $errorMessage = $e->getMessage();
        }
    }
}

// Fetch all categories for the current budget
$categoriesSql = "SELECT c.*,
                 (SELECT COUNT(*) FROM budget_transactions WHERE category_id = c.category_id) as transaction_count
                 FROM budget_categories c
                 WHERE c.budget_id = ?
                 ORDER BY c.name";
$categories = fetchAll($categoriesSql, [$currentBudget['budget_id']]);

// Set page title
$pageTitle = "Budget Categories";
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
    <!-- Mobile Optimization CSS -->
    <link rel="stylesheet" href="../css/finance-categories-mobile-full-width.css">
    <!-- Nuclear Mobile Fix - Overrides all conflicts -->
    <link rel="stylesheet" href="../css/finance-categories-nuclear-mobile-fix.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10">
                <!-- Modern Finance Categories Header -->
                <div class="finance-header animate__animated animate__fadeInDown">
                    <div class="finance-header-content">
                        <div class="finance-header-main">
                            <h1 class="finance-title">
                                <i class="fas fa-tags me-3"></i>
                                Budget Categories
                            </h1>
                            <p class="finance-description">Manage budget categories and allocations</p>
                        </div>
                        <div class="finance-header-actions">
                            <a href="finance.php" class="btn btn-header-action">
                                <i class="fas fa-arrow-left me-2"></i>Back to Finance
                            </a>
                        </div>
                    </div>
                </div>

                <style>
                /* Finance Categories Page Styles - MOBILE FIRST APPROACH */
                
                * {
                    box-sizing: border-box;
                }
                
                /* Force main content to extend full width */
                main {
                    padding: 0 !important;
                    margin-left: 0 !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    position: relative !important;
                    overflow-x: hidden !important;
                }
                
                /* Content area with consistent padding */
                .content-area {
                    width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    overflow-x: hidden !important;
                }
                
                /* Header matches main content width exactly */
                .finance-header {
                    width: calc(100% + 2rem) !important;
                    margin: 60px -1rem 2rem -1rem !important;
                    padding: 1.5rem !important;
                    overflow-x: hidden !important;
                }
                
                /* Ensure all content elements use full width */
                .budget-overview-card,
                .add-category-card,
                .categories-list-card {
                    width: 100% !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }
                
                /* Remove Bootstrap row margins */
                .content-area .row {
                    margin: 0 !important;
                    width: 100% !important;
                }
                
                /* Force container to use full width */
                .container-fluid {
                    padding: 0 !important;
                    max-width: 100% !important;
                    overflow-x: hidden !important;
                }
                
                /* Responsive adjustments */
                @media (max-width: 991px) {
                    main {
                        width: 100% !important;
                        margin-left: 0 !important;
                    }
                    
                    .content-area {
                        padding: 0 !important;
                    }
                    
                    .finance-header {
                        margin: 60px 0 2rem 0 !important;
                        padding: 1.5rem !important;
                    }
                }
                
                @media (max-width: 768px) {
                    .content-area {
                        padding: 0 !important;
                    }
                    
                    .finance-header {
                        margin: 50px 0 1.5rem 0 !important;
                        padding: 1.25rem !important;
                    }
                }
                
                @media (max-width: 576px) {
                    .content-area {
                        padding: 0 !important;
                    }
                    
                    .finance-header {
                        margin: 50px 0 1rem 0 !important;
                        padding: 1rem !important;
                    }
                    
                    main {
                        padding: 0 !important;
                    }
                }

                .finance-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 2.5rem 2rem;
                    border-radius: 12px;
                    margin-top: 60px;
                    margin-bottom: 2rem;
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

                @media (max-width: 768px) {
                    .finance-header {
                        padding: 1.5rem;
                        margin-bottom: 1.5rem;
                    }

                    .finance-header-content {
                        flex-direction: column;
                        align-items: center;
                        gap: 1rem;
                    }

                    .finance-title {
                        font-size: 1.75rem;
                        gap: 0.5rem;
                    }

                    .finance-title i {
                        font-size: 1.5rem;
                    }

                    .finance-description {
                        font-size: 0.95rem;
                        margin: 0.5rem 0 0 0;
                    }

                    .finance-header-actions {
                        width: 100%;
                        justify-content: center;
                        flex-direction: row;
                        gap: 0.5rem;
                    }

                    .btn-header-action {
                        font-size: 0.85rem;
                        padding: 0.4rem 0.8rem;
                    }
                }
                
                @media (max-width: 576px) {
                    .finance-header {
                        padding: 1rem;
                        margin-bottom: 1rem;
                        border-radius: 8px;
                    }

                    .finance-header-content {
                        gap: 0.75rem;
                    }

                    .finance-title {
                        font-size: 1.5rem;
                        gap: 0.4rem;
                    }

                    .finance-title i {
                        font-size: 1.3rem;
                    }

                    .finance-description {
                        font-size: 0.85rem;
                        margin: 0.25rem 0 0 0;
                    }

                    .finance-header-actions {
                        gap: 0.4rem;
                    }

                    .btn-header-action {
                        font-size: 0.8rem;
                        padding: 0.35rem 0.7rem;
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

                /* Remove empty spaces and improve organization */
                .row {
                    margin-bottom: 0;
                }

                .card {
                    margin-bottom: 1.5rem;
                }

                .alert {
                    margin-bottom: 1rem;
                }

                /* Improve content visibility and spacing */
                .main-content {
                    padding: 1.5rem;
                    margin: 0;
                }

                .col-md-8, .col-md-4 {
                    padding-left: 0.75rem;
                    padding-right: 0.75rem;
                }

                .table-responsive {
                    border-radius: 8px;
                }

                .btn-sm {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
                }

                /* Modal fixes - Force visibility */
                .modal {
                    z-index: 1055 !important;
                    display: none !important;
                }

                .modal.show {
                    display: block !important;
                }

                .modal-backdrop {
                    z-index: 1050 !important;
                }

                /* Dropdown fixes */
                .dropdown-menu {
                    z-index: 1060 !important;
                }

                .dropdown-toggle::after {
                    display: inline-block !important;
                    margin-left: 0.255em !important;
                    vertical-align: 0.255em !important;
                    content: "" !important;
                    border-top: 0.3em solid !important;
                    border-right: 0.3em solid transparent !important;
                    border-bottom: 0 !important;
                    border-left: 0.3em solid transparent !important;
                }

                .modal-dialog {
                    margin: 1.75rem auto !important;
                    max-width: 500px !important;
                    position: relative !important;
                    width: auto !important;
                    pointer-events: none !important;
                }

                .modal-content {
                    background-color: #fff !important;
                    border: 1px solid rgba(0,0,0,.2) !important;
                    border-radius: 0.375rem !important;
                    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
                    pointer-events: auto !important;
                    position: relative !important;
                    display: flex !important;
                    flex-direction: column !important;
                    width: 100% !important;
                    outline: 0 !important;
                }

                .modal-header {
                    padding: 1rem 1rem !important;
                    border-bottom: 1px solid #dee2e6 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    background-color: #fff !important;
                }

                .modal-body {
                    padding: 1rem !important;
                    position: relative !important;
                    flex: 1 1 auto !important;
                    background-color: #fff !important;
                }

                .modal-footer {
                    padding: 0.75rem 1rem !important;
                    border-top: 1px solid #dee2e6 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: flex-end !important;
                    background-color: #fff !important;
                }

                .modal-title {
                    margin-bottom: 0 !important;
                    line-height: 1.5 !important;
                    color: #212529 !important;
                    font-size: 1.25rem !important;
                    font-weight: 500 !important;
                }

                /* Ensure form elements are visible */
                .form-control {
                    display: block !important;
                    width: 100% !important;
                    padding: 0.375rem 0.75rem !important;
                    font-size: 1rem !important;
                    line-height: 1.5 !important;
                    color: #212529 !important;
                    background-color: #fff !important;
                    background-image: none !important;
                    border: 1px solid #ced4da !important;
                    border-radius: 0.375rem !important;
                    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out !important;
                    appearance: none !important;
                    min-height: calc(1.5em + 0.75rem + 2px) !important;
                }

                .form-control:focus {
                    color: #212529 !important;
                    background-color: #fff !important;
                    border-color: #86b7fe !important;
                    outline: 0 !important;
                    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
                }

                .form-label {
                    margin-bottom: 0.5rem !important;
                    font-weight: 500 !important;
                    color: #212529 !important;
                    display: inline-block !important;
                }

                .form-text {
                    margin-top: 0.25rem !important;
                    font-size: 0.875em !important;
                    color: #6c757d !important;
                }

                textarea.form-control {
                    min-height: calc(1.5em + 0.75rem + 2px) !important;
                    resize: vertical !important;
                }

                .mb-3 {
                    margin-bottom: 1rem !important;
                }

                /* Button fixes */
                .btn {
                    display: inline-block !important;
                    font-weight: 400 !important;
                    line-height: 1.5 !important;
                    text-align: center !important;
                    text-decoration: none !important;
                    vertical-align: middle !important;
                    cursor: pointer !important;
                    user-select: none !important;
                    border: 1px solid transparent !important;
                    padding: 0.375rem 0.75rem !important;
                    font-size: 1rem !important;
                    border-radius: 0.375rem !important;
                    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out !important;
                }

                .btn-primary {
                    color: #fff !important;
                    background-color: #0d6efd !important;
                    border-color: #0d6efd !important;
                }

                .btn-secondary {
                    color: #fff !important;
                    background-color: #6c757d !important;
                    border-color: #6c757d !important;
                }

                .btn-close {
                    box-sizing: content-box !important;
                    width: 1em !important;
                    height: 1em !important;
                    padding: 0.25em 0.25em !important;
                    color: #000 !important;
                    background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='m.235.867 8.832 8.832-8.832 8.832a.5.5 0 0 0 .707.707L9.774 9.406l8.832 8.832a.5.5 0 0 0 .707-.707L11.481 8.699l8.832-8.832a.5.5 0 0 0-.707-.707L10.774 7.992 1.942-.84a.5.5 0 1 0-.707.707z'/%3e%3c/svg%3e") center/1em auto no-repeat !important;
                    border: 0 !important;
                    border-radius: 0.375rem !important;
                    opacity: 0.5 !important;
                }

                /* Custom Finance Categories Styles */
                .budget-overview-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border: none;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    margin-bottom: 2rem;
                }

                .budget-stat {
                    display: flex;
                    align-items: center;
                    padding: 1rem;
                    border-radius: 8px;
                    background: white;
                    margin: 0.5rem 0;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    transition: transform 0.2s ease;
                }

                .budget-stat:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .budget-stat-icon {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 1rem;
                    font-size: 1.5rem;
                    color: white;
                    flex-shrink: 0;
                }

                .budget-stat-icon.total {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                }

                .budget-stat-icon.allocated {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .budget-stat-icon.remaining {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .budget-stat-icon.categories {
                    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
                }

                .budget-stat-content h6 {
                    margin: 0;
                    font-size: 0.875rem;
                    color: #6c757d;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .budget-stat-content h4 {
                    margin: 0.25rem 0 0 0;
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #212529;
                }

                .add-category-card {
                    border: none;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    margin-bottom: 2rem;
                }

                .add-category-card .card-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 12px 12px 0 0;
                    padding: 1.25rem 1.5rem;
                }

                .categories-list-card {
                    border: none;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                }

                .categories-list-card .card-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 12px 12px 0 0;
                    padding: 1.25rem 1.5rem;
                }

                .card-actions {
                    display: flex;
                    gap: 0.5rem;
                    flex-wrap: wrap;
                }

                .categories-table {
                    margin-bottom: 0;
                }

                .categories-table th {
                    background-color: #f8f9fa;
                    border-top: none;
                    font-weight: 600;
                    color: #495057;
                    padding: 1rem 0.75rem;
                    border-bottom: 2px solid #dee2e6;
                }

                .categories-table td {
                    padding: 1rem 0.75rem;
                    vertical-align: middle;
                    border-bottom: 1px solid #f1f3f4;
                    position: relative;
                }

                .categories-table .action-buttons {
                    position: relative;
                    z-index: 10;
                }

                .categories-table .btn {
                    pointer-events: auto !important;
                    cursor: pointer !important;
                    position: relative;
                    z-index: 11;
                }

                .categories-table tbody tr:hover {
                    background-color: #f8f9fa;
                }

                .category-name strong {
                    color: #495057;
                    font-weight: 600;
                }

                .amount {
                    font-weight: 600;
                    font-family: 'Courier New', monospace;
                }

                .amount.allocated {
                    color: #0d6efd;
                }

                .amount.spent {
                    color: #dc3545;
                }

                .amount.remaining {
                    color: #198754;
                }

                .progress-container {
                    min-width: 100px;
                }

                .progress {
                    margin-bottom: 0.25rem;
                }

                .action-buttons {
                    display: flex;
                    gap: 0.25rem;
                    z-index: 1;
                    position: relative;
                }

                .action-buttons .btn {
                    pointer-events: auto;
                    cursor: pointer;
                }

                .empty-state {
                    text-align: center;
                    padding: 3rem 2rem;
                    color: #6c757d;
                }

                .empty-state-icon {
                    font-size: 4rem;
                    color: #dee2e6;
                    margin-bottom: 1rem;
                }

                .empty-state h5 {
                    color: #495057;
                    margin-bottom: 1rem;
                }

                /* Mobile responsiveness for 320px and up */
                @media (max-width: 576px) {
                    .finance-header {
                        margin-left: -0.5rem;
                        margin-right: -0.5rem;
                        border-radius: 0;
                        padding: 1rem 0.5rem !important;
                    }

                    .finance-header-content {
                        flex-direction: column;
                        text-align: center;
                        gap: 0.75rem;
                    }

                    .finance-header-actions {
                        justify-content: center;
                        width: 100%;
                    }

                    .finance-header-actions .btn {
                        font-size: 0.85rem;
                        padding: 0.4rem 0.8rem;
                    }

                    .main-content {
                        padding: 0 !important;
                        margin: 0 !important;
                    }

                    .budget-overview-card {
                        margin-bottom: 1.5rem !important;
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        padding: 1rem !important;
                        border-radius: 12px;
                    }

                    .budget-overview-card .row > div {
                        margin-bottom: 0.75rem;
                        padding: 0 !important;
                    }

                    .budget-stat {
                        flex-direction: row !important;
                        align-items: center !important;
                        padding: 1rem !important;
                        margin: 0.5rem 0 !important;
                        background: white !important;
                        border-radius: 8px;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    }

                    .budget-stat-icon {
                        width: 50px !important;
                        height: 50px !important;
                        min-width: 50px !important;
                        margin-right: 1rem !important;
                        margin-bottom: 0 !important;
                        font-size: 1.5rem !important;
                    }

                    .budget-stat-content h6 {
                        font-size: 0.9rem !important;
                        margin: 0 !important;
                        color: #6c757d;
                        text-transform: uppercase;
                    }

                    .budget-stat-content h4 {
                        font-size: 1.35rem !important;
                        margin: 0.25rem 0 0 0 !important;
                        font-weight: 700;
                    }

                    .add-category-form .row > div {
                        margin-bottom: 0.75rem;
                    }

                    /* Table responsiveness */
                    .table-responsive {
                        font-size: 0.85rem;
                        border: none;
                        -webkit-overflow-scrolling: touch;
                        margin: 0 !important;
                        padding: 0 !important;
                    }

                    .categories-table {
                        font-size: 0.85rem;
                        width: 100%;
                        border-collapse: collapse;
                    }

                    .categories-table th {
                        padding: 0.75rem !important;
                        font-size: 0.8rem !important;
                        font-weight: 600;
                        background-color: #f8f9fa;
                        border-bottom: 2px solid #dee2e6;
                        text-align: center !important;
                        word-break: break-word;
                    }

                    .categories-table td {
                        padding: 0.75rem !important;
                        font-size: 0.85rem !important;
                        border-bottom: 1px solid #f1f3f4;
                        text-align: center !important;
                        vertical-align: middle !important;
                    }

                    .categories-table td:first-child {
                        text-align: left !important;
                        font-weight: 600;
                        font-size: 0.9rem !important;
                    }

                    /* Hide less important columns on mobile */
                    .categories-table th:nth-child(2),
                    .categories-table td:nth-child(2) {
                        display: none; /* Hide description column */
                    }

                    .categories-table th:nth-child(6),
                    .categories-table td:nth-child(6) {
                        display: none; /* Hide transactions column */
                    }

                    .categories-table th:nth-child(7),
                    .categories-table td:nth-child(7) {
                        display: none; /* Hide progress column */
                    }

                    /* Make action buttons smaller and stacked */
                    .action-buttons {
                        flex-direction: column !important;
                        gap: 0.5rem !important;
                        width: 100%;
                    }

                    .action-buttons .btn {
                        padding: 0.5rem 0.75rem !important;
                        font-size: 0.8rem !important;
                        width: 100%;
                    }

                    .action-buttons .btn i {
                        font-size: 0.85rem !important;
                    }

                    /* Budget stats mobile layout */
                    .budget-stat {
                        padding: 1rem !important;
                        margin-bottom: 0.5rem !important;
                    }

                    .budget-stat-content h4 {
                        font-size: 1.35rem !important;
                    }

                    .budget-stat-content h6 {
                        font-size: 0.9rem !important;
                    }

                    /* Add category form mobile */
                    .add-category-card {
                        margin-bottom: 1rem !important;
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        border-radius: 12px;
                    }

                    .add-category-card .card-header {
                        padding: 1rem !important;
                        font-size: 1.1rem;
                        font-weight: 600;
                    }

                    .add-category-card .card-body {
                        padding: 1rem !important;
                    }

                    .add-category-form .form-control {
                        font-size: 1rem;
                        padding: 0.75rem;
                        height: auto;
                        border: 1px solid #ced4da;
                        border-radius: 4px;
                    }

                    .add-category-form .form-label {
                        font-size: 0.95rem;
                        font-weight: 500;
                        margin-bottom: 0.5rem;
                    }

                    .add-category-form .btn {
                        width: 100%;
                        margin-top: 0.75rem;
                        font-size: 1rem;
                        padding: 0.75rem;
                        font-weight: 600;
                    }

                    .categories-list-card {
                        border-radius: 12px;
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                    }

                    .categories-list-card .card-header {
                        padding: 1rem !important;
                        font-size: 1.1rem;
                        font-weight: 600;
                    }

                    .categories-list-card .card-body {
                        padding: 0 !important;
                    }

                    .categories-list-card .card-actions {
                        padding: 0.85rem !important;
                        gap: 0.5rem;
                        flex-wrap: wrap;
                    }

                    .categories-list-card .card-actions .btn {
                        font-size: 0.85rem;
                        padding: 0.5rem 0.8rem;
                        width: auto;
                        flex: 1;
                        min-width: 110px;
                    }

                    /* Remove column padding on mobile */
                    .col-md-3,
                    .col-md-4,
                    .col-md-6,
                    .col-md-12,
                    .col-lg-10 {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                    }
                }

                    .card {
                        margin-bottom: 1rem !important;
                        border: none;
                        border-radius: 0;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    }

                    .card-body {
                        padding: 1rem !important;
                    }

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

                    /* Mobile card layout for categories - Alternative view */
                    .mobile-category-card {
                        display: block;
                        background: white;
                        border: 1px solid #e9ecef;
                        border-radius: 8px;
                        padding: 1rem;
                        margin-bottom: 1rem;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }

                    .mobile-category-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 0.75rem;
                        padding-bottom: 0.75rem;
                        border-bottom: 1px solid #f8f9fa;
                    }

                    .mobile-category-name {
                        font-weight: 600;
                        color: #495057;
                        font-size: 1rem;
                    }

                    .mobile-category-actions {
                        display: flex;
                        gap: 0.5rem;
                    }

                    .mobile-category-actions .btn {
                        padding: 0.4rem 0.6rem;
                        font-size: 0.8rem;
                    }

                    .mobile-category-details {
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

                    .mobile-detail-value.remaining.negative {
                        color: #dc3545;
                    }

                    /* Empty state mobile */
                    .empty-state {
                        padding: 2rem 1rem !important;
                        text-align: center;
                        background: white;
                        margin: 0;
                    }

                    .empty-state-icon {
                        font-size: 3.5rem;
                        margin-bottom: 1rem;
                    }

                    .empty-state h5 {
                        font-size: 1.25rem;
                        font-weight: 600;
                        margin-bottom: 0.75rem;
                    }

                    .empty-state p {
                        font-size: 0.95rem;
                        margin-bottom: 1.25rem;
                        line-height: 1.5;
                    }
                }

                /* Extra small devices (320px and up) */
                @media (max-width: 375px) {
                    .finance-header {
                        padding: 0.75rem !important;
                    }

                    .finance-title {
                        font-size: 1.4rem !important;
                    }

                    .finance-description {
                        font-size: 0.9rem !important;
                    }

                    .main-content {
                        padding: 0 !important;
                    }

                    .card {
                        margin-bottom: 0.75rem !important;
                    }

                    .card-body {
                        padding: 1rem !important;
                    }

                    /* Ultra compact table for very small screens */
                    .categories-table {
                        font-size: 0.8rem;
                    }

                    .categories-table th,
                    .categories-table td {
                        padding: 0.6rem !important;
                    }

                    /* Stack budget stats vertically */
                    .budget-overview-card .row {
                        margin: 0;
                    }

                    .budget-overview-card .col-md-3 {
                        padding: 0 !important;
                    }

                    .budget-stat {
                        padding: 0.85rem !important;
                        margin-bottom: 0.5rem !important;
                    }

                    .budget-stat-icon {
                        width: 48px !important;
                        height: 48px !important;
                        margin-right: 0.85rem !important;
                        font-size: 1.3rem !important;
                    }

                    .budget-stat-content h4 {
                        font-size: 1.2rem !important;
                    }

                    .budget-stat-content h6 {
                        font-size: 0.8rem !important;
                    }

                    /* Compact form elements */
                    .form-label {
                        font-size: 0.95rem;
                        margin-bottom: 0.4rem;
                    }

                    .form-control {
                        font-size: 0.95rem;
                        padding: 0.65rem 0.75rem;
                    }

                    .btn {
                        font-size: 0.95rem;
                        padding: 0.6rem 1rem;
                    }

                    /* Add category card */
                    .add-category-card .card-header {
                        padding: 0.9rem !important;
                        font-size: 1rem;
                    }

                    .add-category-form .btn {
                        font-size: 1rem;
                        padding: 0.7rem;
                    }

                    /* Categories list card */
                    .categories-list-card .card-header {
                        padding: 0.9rem !important;
                        font-size: 1rem;
                    }

                    .categories-list-card .card-actions {
                        padding: 0.9rem !important;
                    }

                    .categories-list-card .card-actions .btn {
                        font-size: 0.8rem;
                        padding: 0.45rem 0.7rem;
                    }

                    /* Mobile card layout */
                    .mobile-category-card {
                        padding: 0.9rem;
                        margin-bottom: 0.85rem;
                    }

                    .mobile-category-name {
                        font-size: 0.95rem;
                    }

                    .mobile-category-actions .btn {
                        padding: 0.4rem 0.6rem;
                        font-size: 0.75rem;
                    }

                    .mobile-category-details {
                        gap: 0.65rem;
                        font-size: 0.85rem;
                    }

                    .mobile-detail-label {
                        font-size: 0.75rem;
                        margin-bottom: 0.2rem;
                    }

                    .mobile-detail-value {
                        font-size: 0.9rem;
                    }

                    /* Alert improvements */
                    .alert {
                        padding: 0.85rem !important;
                        font-size: 0.9rem;
                    }

                    .btn-close {
                        font-size: 1.1rem;
                    }
                }

                /* Custom Modal System */
                .custom-modal-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                }

                .custom-modal-container {
                    position: relative;
                    width: 100%;
                    max-width: 500px;
                    margin: 0 auto;
                }

                .custom-modal {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                    max-height: 90vh;
                    overflow-y: auto;
                }

                .custom-modal-content {
                    width: 100%;
                }

                .custom-modal-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 1rem 1.5rem;
                    border-radius: 12px 12px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .custom-modal-header h5 {
                    margin: 0;
                    font-size: 1.25rem;
                }

                .custom-modal-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: background-color 0.2s;
                }

                .custom-modal-close:hover {
                    background-color: rgba(255, 255, 255, 0.2);
                }

                .custom-modal-body {
                    padding: 2rem;
                }

                .custom-modal-footer {
                    padding: 1rem 2rem;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    justify-content: flex-end;
                    gap: 0.5rem;
                }

                /* Prevent body scroll when modal is open */
                body.modal-open {
                    overflow: hidden !important;
                    padding-right: 0 !important;
                }

                /* Mobile responsiveness for modal */
                @media (max-width: 576px) {
                    .custom-modal-overlay {
                        padding: 0.5rem;
                    }

                    .custom-modal-container {
                        max-width: calc(100% - 1rem);
                    }

                    .custom-modal {
                        max-height: 95vh;
                    }

                    .custom-modal-header {
                        padding: 0.75rem 1rem;
                    }

                    .custom-modal-header h5 {
                        font-size: 1.1rem;
                    }

                    .custom-modal-body {
                        padding: 1rem;
                    }

                    .custom-modal-footer {
                        padding: 0.75rem 1rem;
                        flex-direction: column;
                        gap: 0.5rem;
                    }

                    .custom-modal-footer .btn {
                        width: 100%;
                        font-size: 0.9rem;
                    }
                }

                @media (max-width: 375px) {
                    .custom-modal-overlay {
                        padding: 0.25rem;
                    }

                    .custom-modal-container {
                        max-width: 100%;
                    }

                    .custom-modal-header h5 {
                        font-size: 1rem;
                    }

                    .custom-modal-close {
                        width: 28px;
                        height: 28px;
                        font-size: 1.3rem;
                    }

                    .custom-modal-body {
                        padding: 0.75rem;
                    }

                    .custom-modal-footer {
                        padding: 0.5rem 0.75rem;
                        gap: 0.25rem;
                    }

                    .custom-modal-footer .btn {
                        font-size: 0.8rem;
                        padding: 0.4rem 0.8rem;
                    }
                }

                /* Mobile Full-Width Optimization for Finance Categories Page */
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
                    .card, .category-card, .budget-overview-card, .add-category-card, .categories-list-card {
                        margin-left: 0 !important;
                        margin-right: 0 !important;
                        border-radius: 0 !important;
                    }
                }
                </style>

                <!-- Main content area -->
                <div class="main-content">
                    <!-- Success/Error Messages -->
                    <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($successMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($errorMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Debug Information (remove in production) -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info">
                        <h6>Debug Information:</h6>
                        <p><strong>User:</strong> <?php echo htmlspecialchars($currentUser['username'] ?? 'Unknown'); ?></p>
                        <p><strong>User Role:</strong> <?php echo htmlspecialchars($currentUser['role'] ?? 'Unknown'); ?></p>
                        <p><strong>Can Create Budget:</strong> <?php echo hasPermission('create', 'budget') ? 'Yes' : 'No'; ?></p>
                        <p><strong>Can Update Budget:</strong> <?php echo hasPermission('update', 'budget') ? 'Yes' : 'No'; ?></p>
                        <p><strong>Can Delete Budget:</strong> <?php echo hasPermission('delete', 'budget') ? 'Yes' : 'No'; ?></p>
                        <p><strong>Categories Count:</strong> <?php echo count($categories); ?></p>
                        <p><strong>Current Budget ID:</strong> <?php echo $currentBudget['budget_id'] ?? 'None'; ?></p>
                    </div>
                    <?php endif; ?>

                <!-- Content Area with Proper Padding -->
                <div class="content-area">
                    <!-- Budget Overview -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card budget-overview-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="budget-stat">
                                                <div class="budget-stat-icon total">
                                                    <i class="fas fa-wallet"></i>
                                                </div>
                                                <div class="budget-stat-content">
                                                    <h6>Total Budget</h6>
                                                    <h4>₵<?php echo number_format($currentBudget['total_amount'], 2); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="budget-stat">
                                                <div class="budget-stat-icon allocated">
                                                    <i class="fas fa-chart-pie"></i>
                                                </div>
                                                <div class="budget-stat-content">
                                                    <h6>Allocated</h6>
                                                    <h4>₵<?php echo number_format($currentBudget['allocated_amount'], 2); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="budget-stat">
                                                <div class="budget-stat-icon remaining">
                                                    <i class="fas fa-piggy-bank"></i>
                                                </div>
                                                <div class="budget-stat-content">
                                                    <h6>Remaining</h6>
                                                    <h4>₵<?php echo number_format($currentBudget['remaining_amount'], 2); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="budget-stat">
                                                <div class="budget-stat-icon categories">
                                                    <i class="fas fa-tags"></i>
                                                </div>
                                                <div class="budget-stat-content">
                                                    <h6>Categories</h6>
                                                    <h4><?php echo count($categories); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Category Section -->
                    <?php if (hasPermission('create', 'budget') || hasPermission('update', 'budget')): ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card add-category-card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-plus-circle me-2"></i>Add New Category
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" class="add-category-form">
                                        <input type="hidden" name="action" value="add">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                           value="<?php echo htmlspecialchars($formData['name']); ?>"
                                                           placeholder="e.g., Events & Programs" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="allocated_amount" class="form-label">Allocated Amount (₵)</label>
                                                    <input type="number" class="form-control" id="allocated_amount" name="allocated_amount"
                                                           value="<?php echo htmlspecialchars($formData['allocated_amount']); ?>"
                                                           placeholder="0.00" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">&nbsp;</label>
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-plus me-2"></i>Add Category
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="description" class="form-label">Description</label>
                                                    <textarea class="form-control" id="description" name="description" rows="2"
                                                              placeholder="Brief description of this category..."><?php echo htmlspecialchars($formData['description']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Categories List -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card categories-list-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>Budget Categories
                                    </h5>
                                    <div class="card-actions">
                                        <a href="finance.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-arrow-left me-1"></i>Back to Finance
                                        </a>
                                        <a href="finance-add-record.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-plus me-1"></i>Add Transaction
                                        </a>
                                        <button type="button" class="btn btn-info btn-sm" onclick="testJS()" title="Test JavaScript">
                                            <i class="fas fa-code"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($categories)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-tags"></i>
                                        </div>
                                        <h5>No Categories Found</h5>
                                        <p class="text-muted">Start by adding your first budget category to organize your finances.</p>
                                        <?php if (hasPermission('create', 'budget')): ?>
                                        <button type="button" class="btn btn-primary" onclick="focusAddCategory()">
                                            <i class="fas fa-plus me-2"></i>Add First Category
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover categories-table">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Description</th>
                                                    <th>Allocated Amount</th>
                                                    <th>Spent Amount</th>
                                                    <th>Remaining</th>
                                                    <th>Transactions</th>
                                                    <th>Progress</th>
                                                    <?php if (hasPermission('update', 'budget') || hasPermission('delete', 'budget')): ?>
                                                    <th>Actions</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categories as $category):
                                                    $remaining = $category['allocated_amount'] - $category['spent_amount'];
                                                    $progressPercent = $category['allocated_amount'] > 0 ?
                                                        ($category['spent_amount'] / $category['allocated_amount']) * 100 : 0;
                                                    $progressClass = $progressPercent >= 90 ? 'bg-danger' :
                                                                   ($progressPercent >= 75 ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="category-name">
                                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">
                                                            <?php echo htmlspecialchars($category['description'] ?: 'No description'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="amount allocated">
                                                            ₵<?php echo number_format($category['allocated_amount'], 2); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="amount spent">
                                                            ₵<?php echo number_format($category['spent_amount'], 2); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="amount remaining <?php echo $remaining < 0 ? 'text-danger' : 'text-success'; ?>">
                                                            ₵<?php echo number_format($remaining, 2); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo $category['transaction_count']; ?> transactions
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="progress-container">
                                                            <div class="progress" style="height: 8px;">
                                                                <div class="progress-bar <?php echo $progressClass; ?>"
                                                                     style="width: <?php echo min($progressPercent, 100); ?>%"></div>
                                                            </div>
                                                            <small class="text-muted"><?php echo number_format($progressPercent, 1); ?>%</small>
                                                        </div>
                                                    </td>
                                                    <?php if (hasPermission('update', 'budget') || hasPermission('delete', 'budget')): ?>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <?php if (hasPermission('update', 'budget')): ?>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-primary edit-btn"
                                                                    data-category-id="<?php echo $category['category_id']; ?>"
                                                                    data-category-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                                    data-category-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                                    data-category-amount="<?php echo $category['allocated_amount']; ?>"
                                                                    title="Edit Category">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <?php if (hasPermission('delete', 'budget')): ?>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-danger delete-btn"
                                                                    data-category-id="<?php echo $category['category_id']; ?>"
                                                                    data-category-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                                    data-transaction-count="<?php echo $category['transaction_count']; ?>"
                                                                    title="Delete Category">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <?php endif; ?>
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
                </div>
                <!-- End Content Area -->
                </div>
            </main>
        </div>
    </div>





    <!-- Custom Modal Overlay -->
    <div id="customModalOverlay" class="custom-modal-overlay" style="display: none;">
        <div class="custom-modal-container">
            <!-- Edit Category Modal -->
            <div id="editCategoryModal" class="custom-modal" style="display: none;">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <h5><i class="fas fa-edit me-2"></i>Edit Category</h5>
                        <button type="button" class="custom-modal-close" onclick="closeCustomModal()">&times;</button>
                    </div>
                    <form method="POST" action="" id="editCategoryForm">
                        <div class="custom-modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="category_id" id="edit_category_id">

                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="edit_allocated_amount" class="form-label">Allocated Amount (₵)</label>
                                <input type="number" class="form-control" id="edit_allocated_amount" name="allocated_amount"
                                       step="0.01" min="0">
                            </div>

                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeCustomModal()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Category Modal -->
            <div id="deleteCategoryModal" class="custom-modal" style="display: none;">
                <div class="custom-modal-content">
                    <div class="custom-modal-header">
                        <h5><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Delete Category</h5>
                        <button type="button" class="custom-modal-close" onclick="closeCustomModal()">&times;</button>
                    </div>
                    <form method="POST" action="" id="deleteCategoryForm">
                        <div class="custom-modal-body">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="category_id" id="delete_category_id">

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This action cannot be undone.
                            </div>

                            <p>Are you sure you want to delete the category <strong id="delete_category_name"></strong>?</p>

                            <div id="delete_warning_transactions" class="alert alert-danger" style="display: none;">
                                <i class="fas fa-ban me-2"></i>
                                <strong>Cannot Delete:</strong> This category has existing transactions and cannot be deleted.
                            </div>
                        </div>
                        <div class="custom-modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeCustomModal()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                                <i class="fas fa-trash me-2"></i>Delete Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (needed for some functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script src="../js/budget.js"></script>
    <!-- Mobile Optimization JavaScript -->
    <script src="../js/finance-categories-mobile-optimization.js"></script>
    <!-- Nuclear Mobile Fix - Removes all conflicts -->
    <script src="../js/finance-categories-nuclear-mobile-fix.js"></script>

    <script>
    // Finance Categories Page JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Force content width to match header
        const main = document.querySelector('main');
        const sidebar = document.querySelector('.sidebar');
        
        if (main && sidebar) {
            const sidebarWidth = sidebar.offsetWidth || 260;
            main.style.width = `calc(100vw - ${sidebarWidth}px)`;
            main.style.maxWidth = 'none';
            main.style.padding = '0';
        }
        
        // Ensure content area uses full width
        const contentArea = document.querySelector('.content-area');
        if (contentArea) {
            contentArea.style.width = '100%';
        }
        
        // Initialize Bootstrap components
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize dropdowns explicitly
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Ensure notification and profile dropdowns work
        const notificationDropdown = document.getElementById('notificationsDropdown');
        const userDropdown = document.getElementById('userDropdown');

        if (notificationDropdown) {
            notificationDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = bootstrap.Dropdown.getOrCreateInstance(this);
                dropdown.toggle();
            });
        }

        if (userDropdown) {
            userDropdown.addEventListener('click', function(e) {
                e.preventDefault();
                const dropdown = bootstrap.Dropdown.getOrCreateInstance(this);
                dropdown.toggle();
            });
        }

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (e) {
                    // Fallback if Bootstrap Alert fails
                    alert.style.display = 'none';
                }
            }, 5000);
        });

        // Form validation for add category form
        const addCategoryForm = document.querySelector('.add-category-form');
        if (addCategoryForm) {
            addCategoryForm.addEventListener('submit', function(e) {
                const name = document.getElementById('name').value.trim();
                if (!name) {
                    e.preventDefault();
                    alert('Category name is required.');
                    document.getElementById('name').focus();
                    return false;
                }
            });
        }

        // Form validation for edit category form
        const editCategoryForm = document.getElementById('editCategoryForm');
        if (editCategoryForm) {
            editCategoryForm.addEventListener('submit', function(e) {
                const name = document.getElementById('edit_name').value.trim();
                if (!name) {
                    e.preventDefault();
                    alert('Category name is required.');
                    document.getElementById('edit_name').focus();
                    return false;
                }
            });
        }
    });

    // Custom Modal Functions
    function showCustomModal(modalId) {
        try {
            // Hide all modals first
            document.querySelectorAll('.custom-modal').forEach(modal => {
                modal.style.display = 'none';
            });

            // Show the overlay
            const overlay = document.getElementById('customModalOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
            } else {
                console.error('Modal overlay not found');
                return;
            }

            // Show the specific modal
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            } else {
                console.error('Modal not found:', modalId);
                return;
            }

            // Prevent body scroll
            document.body.classList.add('modal-open');

            console.log('Custom modal shown:', modalId);
        } catch (error) {
            console.error('Error showing modal:', error);
            alert('Error opening modal: ' + error.message);
        }
    }

    function closeCustomModal() {
        try {
            // Hide the overlay
            const overlay = document.getElementById('customModalOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }

            // Hide all modals
            document.querySelectorAll('.custom-modal').forEach(modal => {
                modal.style.display = 'none';
            });

            // Restore body scroll
            document.body.classList.remove('modal-open');

            console.log('Custom modal closed');
        } catch (error) {
            console.error('Error closing modal:', error);
        }
    }

    // Edit Category Function
    function editCategory(categoryId, name, description, allocatedAmount) {
        console.log('Edit category called with:', categoryId, name, description, allocatedAmount);

        try {
            // Set form values safely
            const categoryIdField = document.getElementById('edit_category_id');
            const nameField = document.getElementById('edit_name');
            const descriptionField = document.getElementById('edit_description');
            const amountField = document.getElementById('edit_allocated_amount');

            if (categoryIdField) categoryIdField.value = categoryId;
            if (nameField) nameField.value = name || '';
            if (descriptionField) descriptionField.value = description || '';
            if (amountField) amountField.value = allocatedAmount || 0;

            // Show custom modal
            showCustomModal('editCategoryModal');

            // Focus on name field
            setTimeout(() => {
                const nameField = document.getElementById('edit_name');
                if (nameField) nameField.focus();
            }, 100);

        } catch (error) {
            console.error('Error in editCategory function:', error);
            alert('Error opening edit form: ' + error.message);
        }
    }

    // Delete Category Function
    function deleteCategory(categoryId, name, transactionCount) {
        console.log('Delete category called with:', categoryId, name, transactionCount);

        try {
            // Set form values safely
            const categoryIdField = document.getElementById('delete_category_id');
            const categoryNameField = document.getElementById('delete_category_name');

            if (categoryIdField) categoryIdField.value = categoryId;
            if (categoryNameField) categoryNameField.textContent = name || 'Unknown Category';

            // Handle transaction count warning safely
            const warningDiv = document.getElementById('delete_warning_transactions');
            const confirmBtn = document.getElementById('confirmDeleteBtn');

            if (warningDiv && confirmBtn) {
                if (transactionCount > 0) {
                    warningDiv.style.display = 'block';
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Cannot Delete';
                    confirmBtn.classList.add('disabled');
                } else {
                    warningDiv.style.display = 'none';
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Delete Category';
                    confirmBtn.classList.remove('disabled');
                }
            } else {
                console.warn('Warning div or confirm button not found');
            }

            // Show custom modal
            showCustomModal('deleteCategoryModal');

        } catch (error) {
            console.error('Error in deleteCategory function:', error);
            alert('Error opening delete confirmation: ' + error.message);
        }
    }

    // Function to focus on add category form
    function focusAddCategory() {
        const nameField = document.getElementById('name');
        if (nameField) {
            nameField.focus();
            nameField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Debug function to test if JavaScript is working
    function testJS() {
        alert('JavaScript is working!');
        console.log('Finance Categories page JavaScript loaded successfully');
    }

    // Debug function to test button clicks
    function testButtons() {
        const editBtns = document.querySelectorAll('.edit-btn');
        const deleteBtns = document.querySelectorAll('.delete-btn');

        console.log('Found edit buttons:', editBtns.length);
        console.log('Found delete buttons:', deleteBtns.length);

        editBtns.forEach((btn, index) => {
            console.log(`Edit button ${index}:`, btn);
            console.log('  - Category ID:', btn.getAttribute('data-category-id'));
            console.log('  - Category Name:', btn.getAttribute('data-category-name'));
        });

        deleteBtns.forEach((btn, index) => {
            console.log(`Delete button ${index}:`, btn);
            console.log('  - Category ID:', btn.getAttribute('data-category-id'));
            console.log('  - Category Name:', btn.getAttribute('data-category-name'));
        });

        alert(`Found ${editBtns.length} edit buttons and ${deleteBtns.length} delete buttons`);
    }

    // Make functions globally available
    window.testJS = testJS;
    window.testButtons = testButtons;
    window.editCategory = editCategory;
    window.deleteCategory = deleteCategory;
    window.showCustomModal = showCustomModal;
    window.closeCustomModal = closeCustomModal;

    // Log when page is ready
    console.log('Finance Categories page loaded');

    // Mobile layout function
    function checkMobileLayout() {
        const isMobile = window.innerWidth <= 375;
        const tableContainer = document.querySelector('.table-responsive');

        if (isMobile && tableContainer) {
            // Convert table to mobile cards
            convertTableToMobileCards();
        }
    }

    function convertTableToMobileCards() {
        const table = document.querySelector('.categories-table');
        const tableContainer = document.querySelector('.table-responsive');

        if (!table || !tableContainer) return;

        // Hide the table on mobile
        if (window.innerWidth <= 375) {
            table.style.display = 'none';

            // Create mobile cards if they don't exist
            if (!document.querySelector('.mobile-categories-container')) {
                createMobileCards(table, tableContainer);
            }
        } else {
            table.style.display = 'table';
            const mobileContainer = document.querySelector('.mobile-categories-container');
            if (mobileContainer) {
                mobileContainer.remove();
            }
        }
    }

    function createMobileCards(table, container) {
        const rows = table.querySelectorAll('tbody tr');
        const mobileContainer = document.createElement('div');
        mobileContainer.className = 'mobile-categories-container';

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 0) return;

            const card = document.createElement('div');
            card.className = 'mobile-category-card';

            // Extract data from table cells
            const categoryName = cells[0]?.textContent.trim() || 'Unknown';
            const allocated = cells[2]?.textContent.trim() || '₵0.00';
            const spent = cells[3]?.textContent.trim() || '₵0.00';
            const remaining = cells[4]?.textContent.trim() || '₵0.00';
            const transactions = cells[5]?.textContent.trim() || '0 transactions';

            // Get action buttons
            const actionButtons = cells[cells.length - 1]?.querySelector('.action-buttons')?.innerHTML || '';

            card.innerHTML = `
                <div class="mobile-category-header">
                    <div class="mobile-category-name">${categoryName}</div>
                    <div class="mobile-category-actions">
                        ${actionButtons}
                    </div>
                </div>
                <div class="mobile-category-details">
                    <div class="mobile-detail-item">
                        <div class="mobile-detail-label">Allocated</div>
                        <div class="mobile-detail-value allocated">${allocated}</div>
                    </div>
                    <div class="mobile-detail-item">
                        <div class="mobile-detail-label">Spent</div>
                        <div class="mobile-detail-value spent">${spent}</div>
                    </div>
                    <div class="mobile-detail-item">
                        <div class="mobile-detail-label">Remaining</div>
                        <div class="mobile-detail-value remaining ${remaining.includes('-') ? 'negative' : ''}">${remaining}</div>
                    </div>
                    <div class="mobile-detail-item">
                        <div class="mobile-detail-label">Transactions</div>
                        <div class="mobile-detail-value">${transactions}</div>
                    </div>
                </div>
            `;

            mobileContainer.appendChild(card);
        });

        container.appendChild(mobileContainer);
    }

    // Document ready function
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - Finance Categories with Custom Modals');

        // Initialize tooltips if Bootstrap is available
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Check mobile layout on load
        checkMobileLayout();

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);

        // Add event listeners for edit buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-btn')) {
                e.preventDefault();
                e.stopPropagation();

                const button = e.target.closest('.edit-btn');
                const categoryId = button.getAttribute('data-category-id');
                const categoryName = button.getAttribute('data-category-name');
                const categoryDescription = button.getAttribute('data-category-description');
                const categoryAmount = button.getAttribute('data-category-amount');

                console.log('Edit button clicked:', categoryId, categoryName);
                editCategory(categoryId, categoryName, categoryDescription, categoryAmount);
            }

            if (e.target.closest('.delete-btn')) {
                e.preventDefault();
                e.stopPropagation();

                const button = e.target.closest('.delete-btn');
                const categoryId = button.getAttribute('data-category-id');
                const categoryName = button.getAttribute('data-category-name');
                const transactionCount = button.getAttribute('data-transaction-count');

                console.log('Delete button clicked:', categoryId, categoryName);
                deleteCategory(categoryId, categoryName, transactionCount);
            }
        });

        // Add escape key listener to close modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCustomModal();
            }
        });

        // Add click outside to close modal
        const modalOverlay = document.getElementById('customModalOverlay');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCustomModal();
                }
            });
        } else {
            console.warn('Modal overlay not found for click outside handler');
        }

        // Handle window resize for mobile layout
        window.addEventListener('resize', function() {
            checkMobileLayout();
        });

    });
    </script>

    <!-- Additional Bootstrap and jQuery fallbacks -->
    <script>
    // Fallback for Bootstrap if CDN fails
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap not loaded, loading fallback...');
        document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"><\/script>');
    }

    // Fallback for jQuery if CDN fails
    if (typeof jQuery === 'undefined') {
        console.warn('jQuery not loaded, loading fallback...');
        document.write('<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"><\/script>');
    }
    </script>

<?php include 'includes/footer.php'; ?>