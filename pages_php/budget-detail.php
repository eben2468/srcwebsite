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

// Get budget details
$budget = null;
$budgetItems = [];
$budgetComments = [];
$error = null;

try {
    // Get budget details - Use explicit column names instead of *
    $budgetSQL = "SELECT b.budget_id, b.title, b.description, b.amount, b.status, b.category, 
                 b.department_id, b.created_by, b.created_at, b.updated_at,
                 u.username as created_by_name, d.name as department_name
                 FROM budgets b
                 LEFT JOIN users u ON b.created_by = u.user_id
                 LEFT JOIN departments d ON b.department_id = d.department_id
                 WHERE b.budget_id = ?";
    
    // Debug info
    error_log("Budget SQL: " . $budgetSQL);
    error_log("Budget ID: " . $budgetId);
    
    $budget = fetchOne($budgetSQL, [$budgetId]);
    
    if (!$budget) {
        $error = "Budget not found with ID: " . $budgetId;
        error_log($error);
    } else {
        // Get budget items
        $itemsSQL = "SELECT * FROM budget_items WHERE budget_id = ? ORDER BY item_id";
        $budgetItems = fetchAll($itemsSQL, [$budgetId]);
        
        // Get budget comments
        $commentsSQL = "SELECT c.*, u.username, up.full_name
                       FROM budget_comments c
                       LEFT JOIN users u ON c.user_id = u.user_id
                       LEFT JOIN user_profiles up ON c.user_id = up.user_id
                       WHERE c.budget_id = ?
                       ORDER BY c.created_at DESC";
        $budgetComments = fetchAll($commentsSQL, [$budgetId]);
        
        // Get budget history
        $historySQL = "SELECT h.*, u.username, up.full_name
                      FROM budget_history h
                      LEFT JOIN users u ON h.user_id = u.user_id
                      LEFT JOIN user_profiles up ON h.user_id = up.user_id
                      WHERE h.budget_id = ?
                      ORDER BY h.created_at DESC";
        $budgetHistory = fetchAll($historySQL, [$budgetId]);
    }
} catch (Exception $e) {
    $error = "Error fetching budget details: " . $e->getMessage();
    error_log($error);
}

// Record user activity
try {
    if (function_exists('recordUserActivity')) {
        recordUserActivity($userId, 'view', 'budget', $budgetId, 'Viewed budget details: ' . ($budget['title'] ?? 'Unknown'));
    }
} catch (Exception $e) {
    // Silently handle any errors with activity recording
    error_log("Error recording user activity: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Details - <?php echo htmlspecialchars($siteName); ?></title>
    
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

    <!-- Custom Budget Detail Header -->
    <div class="budget-detail-header animate__animated animate__fadeInDown">
        <div class="budget-detail-header-content">
            <div class="budget-detail-header-main">
                <h1 class="budget-detail-title">
                    <i class="fas fa-chart-line me-3"></i>
                    Budget Details
                </h1>
                <p class="budget-detail-description">View detailed budget information and financial breakdown</p>
            </div>
            <div class="budget-detail-header-actions">
                <a href="budget.php" class="btn btn-header-action">
                    <i class="fas fa-arrow-left me-2"></i>Back to Budgets
                </a>
            </div>
        </div>
    </div>

    <style>
    .budget-detail-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        padding: 2.5rem 2rem;
        border-radius: 12px;
        margin-top: 60px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .budget-detail-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1.5rem;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .budget-detail-header-main {
        flex: 1;
        text-align: center;
        color: white !important;
    }

    .budget-detail-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0 0 1rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.8rem;
        color: white !important;
    }

    .budget-detail-title i {
        font-size: 2.2rem;
        opacity: 0.9;
        color: white !important;
    }

    .budget-detail-description {
        margin: 0;
        opacity: 0.95;
        font-size: 1.2rem;
        font-weight: 400;
        line-height: 1.4;
        color: white !important;
        text-align: center;
    }

    .budget-detail-header-actions {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
        flex-shrink: 0;
        margin-left: auto;
        padding-left: 2rem;
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
        .budget-detail-header {
            padding: 2rem 1.5rem;
        }

        .budget-detail-header-content {
            flex-direction: column;
            align-items: center;
        }

        .budget-detail-header-main {
            margin-bottom: 1rem;
            color: white !important;
        }

        .budget-detail-title {
            font-size: 2rem;
            gap: 0.6rem;
            color: white !important;
        }

        .budget-detail-title i {
            font-size: 1.8rem;
            color: white !important;
        }

        .budget-detail-description {
            font-size: 1.1rem;
            color: white !important;
        }

        .budget-detail-header-actions {
            width: 100%;
            justify-content: center;
            margin-left: 0;
            padding-left: 0;
        }

        .btn-header-action {
            font-size: 0.9rem;
            padding: 0.7rem 1.3rem;
            margin-right: 0;
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
                        <li class="breadcrumb-item active" aria-current="page">Budget Details</li>
                    </ol>
                </nav>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($budget): ?>
                <div class="budget-detail animate-fade-in">
                    <div class="budget-detail-main">
                        <div class="budget-detail-header">
                            <h1 class="budget-detail-title"><?php echo htmlspecialchars($budget['title']); ?></h1>
                            
                            <div class="d-flex gap-2">
                                <?php if ($canManageBudget): ?>
                                <a href="budget-edit.php?id=<?php echo $budget['budget_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <?php endif; ?>

                                <?php if (($hasAdminPrivileges || $isFinance) && $budget['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-success status-change-btn"
                                        data-budget-id="<?php echo $budget['budget_id']; ?>"
                                        data-status="approved">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>

                                <button type="button" class="btn btn-danger status-change-btn"
                                        data-budget-id="<?php echo $budget['budget_id']; ?>"
                                        data-status="declined">
                                    <i class="fas fa-times me-1"></i> Decline
                                </button>
                                <?php endif; ?>

                                <?php if ($canManageBudget): ?>
                                <button type="button" class="btn btn-outline-danger delete-budget-btn"
                                        data-budget-id="<?php echo $budget['budget_id']; ?>"
                                        data-title="<?php echo htmlspecialchars($budget['title']); ?>">
                                    <i class="fas fa-trash me-1"></i> Delete
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Status</p>
                                <p><span class="status-badge <?php echo $budget['status']; ?>"><?php echo ucfirst($budget['status']); ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Category</p>
                                <p><?php echo htmlspecialchars($budget['category'] ?? 'Uncategorized'); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Created By</p>
                                <p><?php echo htmlspecialchars($budget['created_by_name'] ?? 'Unknown'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Created On</p>
                                <p><?php echo date('F d, Y', strtotime($budget['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($budget['department_id']): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Department</p>
                                <p><?php echo htmlspecialchars($budget['department_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <p class="text-muted mb-1">Description</p>
                                <p><?php echo nl2br(htmlspecialchars($budget['description'] ?? 'No description provided.')); ?></p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h3>Budget Items</h3>
                                
                                <?php if (empty($budgetItems)): ?>
                                <p>No budget items found.</p>
                                <?php else: ?>
                                <div class="budget-items">
                                    <?php 
                                    $totalAmount = 0;
                                    foreach ($budgetItems as $item): 
                                        $itemTotal = $item['amount'] * $item['quantity'];
                                        $totalAmount += $itemTotal;
                                    ?>
                                    <div class="budget-item">
                                        <div class="budget-item-details">
                                            <div class="budget-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div class="budget-item-description"><?php echo htmlspecialchars($item['description'] ?? 'No description'); ?></div>
                                            <div class="mt-1">
                                                <small class="text-muted">
                                                    Quantity: <?php echo $item['quantity']; ?> × 
                                                    ₵<?php echo number_format($item['amount'], 2); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="budget-item-amount">
                                            ₵<?php echo number_format($itemTotal, 2); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="budget-item">
                                        <div class="budget-item-details">
                                            <div class="budget-item-name fw-bold">Total</div>
                                        </div>
                                        <div class="budget-item-amount fw-bold">
                                            ₵<?php echo number_format($totalAmount, 2); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Comments Section -->
                        <div class="comments-section">
                            <h3>Comments</h3>
                            
                            <form id="commentForm" class="mb-4">
                                <input type="hidden" name="budget_id" value="<?php echo $budget['budget_id']; ?>">
                                <div class="form-group">
                                    <textarea class="form-control" name="comment" rows="3" placeholder="Add a comment..."></textarea>
                                </div>
                                <div class="mt-2">
                                    <button type="submit" class="btn btn-primary">Post Comment</button>
                                </div>
                            </form>
                            
                            <div class="comments-list">
                                <?php if (empty($budgetComments)): ?>
                                <p>No comments yet.</p>
                                <?php else: ?>
                                <?php foreach ($budgetComments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-avatar">
                                        <?php 
                                        $commentUserName = $comment['full_name'] ?? $comment['username'] ?? 'User';
                                        echo strtoupper(substr($commentUserName, 0, 1)); 
                                        ?>
                                    </div>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <div class="comment-author"><?php echo htmlspecialchars($commentUserName); ?></div>
                                            <div class="comment-date"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></div>
                                        </div>
                                        <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="budget-detail-sidebar">
                        <h3 class="mb-3">Budget Summary</h3>
                        
                        <div class="mb-3">
                            <h4 class="h5 mb-2">Total Amount</h4>
                            <div class="h3 text-primary">₵<?php echo number_format($budget['amount'], 2); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <h4 class="h5 mb-2">Status</h4>
                            <div class="progress-container">
                                <div class="progress-bar <?php echo $budget['status']; ?>" style="width: 100%;" data-width="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="status-badge <?php echo $budget['status']; ?>"><?php echo ucfirst($budget['status']); ?></span>
                                <span class="text-muted"><?php echo date('M d, Y', strtotime($budget['updated_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4 class="h5 mb-2">Items</h4>
                            <div class="h4"><?php echo count($budgetItems); ?> items</div>
                        </div>
                        
                        <?php if (!empty($budgetHistory)): ?>
                        <div class="mt-4">
                            <h4 class="h5 mb-3">Budget History</h4>
                            <div class="budget-history">
                                <?php foreach ($budgetHistory as $history): ?>
                                <div class="history-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo ucfirst($history['action']); ?></strong>
                                            <?php if ($history['action'] === 'status_change'): ?>
                                            from <span class="status-badge <?php echo $history['old_status']; ?>"><?php echo ucfirst($history['old_status']); ?></span>
                                            to <span class="status-badge <?php echo $history['new_status']; ?>"><?php echo ucfirst($history['new_status']); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if ($history['action'] === 'amount_change'): ?>
                                            from ₵<?php echo number_format($history['old_amount'], 2); ?>
                                            to ₵<?php echo number_format($history['new_amount'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-muted small">
                                        <?php 
                                        $historyUserName = $history['full_name'] ?? $history['username'] ?? 'User';
                                        echo htmlspecialchars($historyUserName) . ' - ' . date('M d, Y H:i', strtotime($history['created_at'])); 
                                        ?>
                                    </div>
                                    <?php if (!empty($history['notes'])): ?>
                                    <div class="mt-1 small"><?php echo htmlspecialchars($history['notes']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info" role="alert">
                    Budget not found. <a href="budget.php" class="alert-link">Return to budgets list</a>.
                </div>
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