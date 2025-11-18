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
requireLogin();

// Check if user has finance access (super admin or finance role)
if (!isSuperAdmin() && !isFinance()) {
    $_SESSION['error'] = 'You do not have permission to access this page.';
    header("Location: ../index.php");
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'];

// Initialize variables
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';

// Clear the messages from session
unset($_SESSION['success']);
unset($_SESSION['error']);

// Set default timezone
date_default_timezone_set('UTC');

// Initialize filter variables
$status = $_GET['status'] ?? 'pending';
$category_id = $_GET['category_id'] ?? '';
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Process approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        $action = $_POST['action'] ?? '';
        
        if ($action === 'bulk_approve') {
            // Handle bulk approval
            $transaction_ids = $_POST['transaction_ids'] ?? [];
            
            if (empty($transaction_ids)) {
                throw new Exception('No transactions selected for approval');
            }
            
            // Start transaction
            $pdo = getDbConnection();
            $pdo->beginTransaction();
            
            $approved_count = 0;
            foreach ($transaction_ids as $transaction_id) {
                $transaction_id = (int)$transaction_id;
                
                // Update transaction status
                $sql = "UPDATE budget_transactions SET 
                        status = 'approved', 
                        approved_by = ?, 
                        approved_at = NOW() 
                        WHERE transaction_id = ? AND status = 'pending'";
                
                if (execute($sql, [$userId, $transaction_id])) {
                    $approved_count++;
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Successfully approved {$approved_count} transaction(s).";
            
        } elseif ($action === 'approve' || $action === 'reject') {
            // Handle single approval/rejection
            $transaction_id = (int)($_POST['transaction_id'] ?? 0);
            $admin_notes = $_POST['admin_notes'] ?? '';
            
            if ($transaction_id <= 0) {
                throw new Exception('Invalid transaction ID');
            }
            
            $new_status = ($action === 'approve') ? 'approved' : 'rejected';
            
            $sql = "UPDATE budget_transactions SET 
                    status = ?, 
                    approved_by = ?, 
                    approved_at = NOW(), 
                    admin_notes = ? 
                    WHERE transaction_id = ?";
            
            if (execute($sql, [$new_status, $userId, $admin_notes, $transaction_id])) {
                $_SESSION['success'] = "Transaction {$action}d successfully.";
            } else {
                throw new Exception("Failed to {$action} transaction");
            }
        }
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header("Location: finance-approvals.php");
    exit();
}

// Build query for transactions
$whereConditions = [];
$params = [];

if ($status && $status !== 'all') {
    $whereConditions[] = "bt.status = ?";
    $params[] = $status;
}

if ($category_id) {
    $whereConditions[] = "bt.category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $whereConditions[] = "(bt.description LIKE ? OR bt.reference_number LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($start_date) {
    $whereConditions[] = "DATE(bt.transaction_date) >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $whereConditions[] = "DATE(bt.transaction_date) <= ?";
    $params[] = $end_date;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM budget_transactions bt {$whereClause}";
$totalResult = fetchOne($countSql, $params);
$totalRecords = $totalResult['total'] ?? 0;
$totalPages = ceil($totalRecords / $per_page);

// Get transactions
$sql = "SELECT bt.*, bc.name as category_name,
               CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
               CONCAT(ua.first_name, ' ', ua.last_name) as approved_by_name
        FROM budget_transactions bt
        LEFT JOIN budget_categories bc ON bt.category_id = bc.category_id
        LEFT JOIN users u ON bt.created_by = u.user_id
        LEFT JOIN users ua ON bt.approved_by = ua.user_id
        {$whereClause}
        ORDER BY 
            CASE WHEN bt.status = 'pending' THEN 0 ELSE 1 END,
            bt.transaction_date DESC, bt.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}";

$transactions = fetchAll($sql, $params);

// Get categories for filter
$categories = fetchAll("SELECT * FROM budget_categories ORDER BY name");

// Get pending transactions count for priority display
$pendingCount = fetchOne("SELECT COUNT(*) as count FROM budget_transactions WHERE status = 'pending'")['count'] ?? 0;

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set page title
$pageTitle = "Finance Approvals - SRC Management System";

// Include header
require_once 'includes/header.php';

// Define page title, icon, and actions for the modern header
$pageTitle = "Finance Approvals";
$pageIcon = "fa-check-circle";
$pageDescription = "Review and approve financial transactions";
$actions = [
    [
        'url' => 'finance.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Finance',
        'class' => 'btn-outline-light'
    ]
];

// Include the modern page header
include_once 'includes/modern_page_header.php';
?>

<div class="container-fluid px-4">
    <!-- Notification area -->
    <?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Priority Pending Transactions -->
    <?php if ($pendingCount > 0 && $status === 'pending'): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Priority: Pending Transactions Requiring Approval (<?php echo $pendingCount; ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong><?php echo $pendingCount; ?></strong> transaction(s) are waiting for your approval.
                        Please review and approve/reject them as soon as possible.
                    </p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkApproveModal" onclick="updateSelectedTransactions()">
                            <i class="fas fa-check-double me-2"></i>Bulk Approve
                        </button>
                        <a href="#transactions-table" class="btn btn-primary">
                            <i class="fas fa-eye me-2"></i>Review Transactions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Transactions</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" 
                                <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Description or reference..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card" id="transactions-table">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Transactions</h5>
            <div class="d-flex gap-2">
                <?php if ($status === 'pending' && !empty($transactions)): ?>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkApproveModal" onclick="updateSelectedTransactions()">
                    <i class="fas fa-check-double me-1"></i>Bulk Approve
                </button>
                <?php endif; ?>
                <span class="badge bg-primary"><?php echo $totalRecords; ?> total</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No transactions found</h5>
                <p class="text-muted">Try adjusting your filters or check back later.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <?php if ($status === 'pending'): ?>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <?php endif; ?>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount (₵)</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <?php if ($status === 'pending'): ?>
                            <td>
                                <input type="checkbox" name="transaction_ids[]"
                                       value="<?php echo $transaction['transaction_id']; ?>"
                                       class="form-check-input transaction-checkbox">
                            </td>
                            <?php endif; ?>
                            <td>#<?php echo $transaction['transaction_id']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $transaction['transaction_type'] === 'income' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                    <?php echo htmlspecialchars($transaction['description']); ?>
                                </div>
                            </td>
                            <td class="fw-bold text-<?php echo $transaction['transaction_type'] === 'income' ? 'success' : 'danger'; ?>">
                                <?php echo $transaction['transaction_type'] === 'income' ? '+' : '-'; ?>₵<?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger'
                                ];
                                $statusColor = $statusColors[$transaction['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $statusColor; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($transaction['created_by_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="viewTransaction(<?php echo $transaction['transaction_id']; ?>)"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($transaction['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                            onclick="approveTransaction(<?php echo $transaction['transaction_id']; ?>)"
                                            title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="rejectTransaction(<?php echo $transaction['transaction_id']; ?>)"
                                            title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Transactions pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Transaction Modal -->
<div class="modal fade" id="viewTransactionModal" tabindex="-1" aria-labelledby="viewTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTransactionModalLabel">
                    <i class="fas fa-eye me-2"></i>Transaction Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Transaction Modal -->
<div class="modal fade" id="approveTransactionModal" tabindex="-1" aria-labelledby="approveTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveTransactionModalLabel">
                    <i class="fas fa-check me-2"></i>Approve Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="approveForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="transaction_id" id="approveTransactionId">

                    <div class="alert alert-success">
                        <i class="fas fa-info-circle me-2"></i>
                        Are you sure you want to approve this transaction?
                    </div>

                    <div class="mb-3">
                        <label for="approveAdminNotes" class="form-label">Admin Notes (Optional)</label>
                        <textarea name="admin_notes" id="approveAdminNotes" class="form-control" rows="3"
                                  placeholder="Add any notes about this approval..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Transaction Modal -->
<div class="modal fade" id="rejectTransactionModal" tabindex="-1" aria-labelledby="rejectTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectTransactionModalLabel">
                    <i class="fas fa-times me-2"></i>Reject Transaction
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="transaction_id" id="rejectTransactionId">

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Are you sure you want to reject this transaction?
                    </div>

                    <div class="mb-3">
                        <label for="rejectAdminNotes" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="admin_notes" id="rejectAdminNotes" class="form-control" rows="3"
                                  placeholder="Please provide a reason for rejecting this transaction..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Approve Modal -->
<div class="modal fade" id="bulkApproveModal" tabindex="-1" aria-labelledby="bulkApproveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkApproveModalLabel">
                    <i class="fas fa-check-double me-2"></i>Bulk Approve Transactions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="bulkApproveForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="bulk_approve">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="bulkApproveCount">0</span> transaction(s) selected for approval.
                    </div>

                    <p>Are you sure you want to approve all selected transactions?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="bulkApproveBtn" disabled>
                        <i class="fas fa-check-double me-2"></i>Approve Selected
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Global variables
let selectedTransactions = [];

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    const transactionCheckboxes = document.querySelectorAll('.transaction-checkbox');

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            transactionCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedTransactions();
        });
    }

    // Initialize individual checkboxes
    transactionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedTransactions();

            // Update select all checkbox
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.transaction-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === transactionCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < transactionCheckboxes.length;
            }
        });
    });

    // Initialize bulk approve form
    const bulkApproveForm = document.getElementById('bulkApproveForm');
    if (bulkApproveForm) {
        bulkApproveForm.addEventListener('submit', function(e) {
            // Clear any existing hidden inputs
            const existingInputs = this.querySelectorAll('input[name="transaction_ids[]"]');
            existingInputs.forEach(input => input.remove());

            // Add selected transaction IDs to form
            selectedTransactions.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'transaction_ids[]';
                input.value = id;
                this.appendChild(input);
            });
        });
    }

    // Auto-hide success alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
            setTimeout(() => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            }, 5000);
        }
    });

    // Initialize modal events
    const bulkApproveModal = document.getElementById('bulkApproveModal');
    if (bulkApproveModal) {
        bulkApproveModal.addEventListener('show.bs.modal', function() {
            updateSelectedTransactions();
        });
    }

    // Initial update
    updateSelectedTransactions();
});

// Update selected transactions array
function updateSelectedTransactions() {
    selectedTransactions = [];
    document.querySelectorAll('.transaction-checkbox:checked').forEach(checkbox => {
        selectedTransactions.push(checkbox.value);
    });

    // Update bulk approve button and count
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkApproveCount = document.getElementById('bulkApproveCount');

    if (bulkApproveBtn && bulkApproveCount) {
        bulkApproveBtn.disabled = selectedTransactions.length === 0;
        bulkApproveCount.textContent = selectedTransactions.length;
    }
}

// View transaction details
function viewTransaction(transactionId) {
    const modal = new bootstrap.Modal(document.getElementById('viewTransactionModal'));
    const detailsContainer = document.getElementById('transactionDetails');

    // Show loading
    detailsContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    modal.show();

    // Fetch transaction details
    fetch('finance-transactions.php?action=get_transaction&id=' + transactionId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const transaction = data.transaction;
                detailsContainer.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr><td><strong>ID:</strong></td><td>#${transaction.transaction_id}</td></tr>
                                <tr><td><strong>Date:</strong></td><td>${new Date(transaction.transaction_date).toLocaleDateString()}</td></tr>
                                <tr><td><strong>Type:</strong></td><td><span class="badge bg-${transaction.type === 'income' ? 'success' : 'danger'}">${transaction.type}</span></td></tr>
                                <tr><td><strong>Amount:</strong></td><td class="fw-bold text-${transaction.type === 'income' ? 'success' : 'danger'}">${transaction.type === 'income' ? '+' : '-'}₵${parseFloat(transaction.amount).toLocaleString()}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-${transaction.status === 'approved' ? 'success' : transaction.status === 'rejected' ? 'danger' : 'warning'}">${transaction.status}</span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Additional Details</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Category:</strong></td><td>${transaction.category_name || 'N/A'}</td></tr>
                                <tr><td><strong>Reference:</strong></td><td>${transaction.reference_number || 'N/A'}</td></tr>
                                <tr><td><strong>Created By:</strong></td><td>${transaction.created_by_name || 'N/A'}</td></tr>
                                <tr><td><strong>Created At:</strong></td><td>${new Date(transaction.created_at).toLocaleString()}</td></tr>
                                ${transaction.approved_by_name ? `<tr><td><strong>Approved By:</strong></td><td>${transaction.approved_by_name}</td></tr>` : ''}
                                ${transaction.approved_at ? `<tr><td><strong>Approved At:</strong></td><td>${new Date(transaction.approved_at).toLocaleString()}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Description</h6>
                            <p class="border p-3 rounded bg-light">${transaction.description || 'No description provided'}</p>
                        </div>
                    </div>
                    ${transaction.admin_notes ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Admin Notes</h6>
                            <p class="border p-3 rounded bg-warning bg-opacity-10">${transaction.admin_notes}</p>
                        </div>
                    </div>
                    ` : ''}
                `;
            } else {
                detailsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading transaction details: ${data.message || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading transaction details. Please try again.
                </div>
            `;
        });
}

// Approve transaction
function approveTransaction(transactionId) {
    document.getElementById('approveTransactionId').value = transactionId;
    const modal = new bootstrap.Modal(document.getElementById('approveTransactionModal'));
    modal.show();
}

// Reject transaction
function rejectTransaction(transactionId) {
    document.getElementById('rejectTransactionId').value = transactionId;
    const modal = new bootstrap.Modal(document.getElementById('rejectTransactionModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>
