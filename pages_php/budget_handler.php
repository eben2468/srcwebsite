<?php
// Include authentication file and database config
header('Content-Type: application/json; charset=utf-8');
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../auth_bridge.php'; // Add bridge for admin status consistency
require_once '../activity_functions.php'; // Include activity functions

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'] ?? 0;
$isAdmin = isAdmin() || getBridgedAdminStatus(); // Check both auth system and bridge
$isMember = isMember(); // Add member check
$canManageContent = $isAdmin || $isMember; // Allow both admins and members to manage content

// Get user profile data including full name
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

// Check if action is provided
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit();
}

$action = $_POST['action'];

// Handle different actions
try {
    switch ($action) {
        case 'create':
            // Check if user has permission to create budgets
            if (!$canManageContent) {
                throw new Exception('You do not have permission to create budgets');
            }
            
            // Get form data
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $category = $_POST['category'] ?? '';
            $amount = (float) ($_POST['amount'] ?? 0);
            $departmentId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
            
            // Validate required fields
            if (empty($title)) {
                throw new Exception('Budget title is required');
            }
            
            if ($amount <= 0) {
                throw new Exception('Budget amount must be greater than zero');
            }
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            // Insert budget
            $insertBudgetSQL = "INSERT INTO budgets (title, description, amount, status, category, department_id, created_by, created_at, updated_at) 
                              VALUES (?, ?, ?, 'pending', ?, ?, ?, NOW(), NOW())";
            $insertBudgetParams = [$title, $description, $amount, $category, $departmentId, $userId];
            $insertBudgetTypes = 'ssdsis';
            
            $budgetId = insert($insertBudgetSQL, $insertBudgetParams, $insertBudgetTypes);
            
            if (!$budgetId) {
                throw new Exception('Failed to create budget');
            }
            
            // Handle budget items
            if (isset($_POST['items']) && is_array($_POST['items'])) {
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
            
            // Record history
            $historySQL = "INSERT INTO budget_history (budget_id, user_id, action, created_at) 
                          VALUES (?, ?, 'created', NOW())";
            $historyParams = [$budgetId, $userId];
            $historyTypes = 'ii';
            
            insert($historySQL, $historyParams, $historyTypes);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Record user activity
            try {
                if (function_exists('recordUserActivity')) {
                    recordUserActivity($userId, 'create', 'budget', $budgetId, 'Created new budget: ' . $title);
                }
            } catch (Exception $e) {
                // Silently handle any errors with activity recording
                error_log("Error recording user activity: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Budget created successfully', 
                'budget_id' => $budgetId,
                'redirect' => 'budget-detail.php?id=' . $budgetId
            ]);
            break;
            
        case 'update_status':
            // Check if user has admin permission to update status
            if (!$isAdmin) {
                throw new Exception('You do not have permission to update budget status');
            }
            
            // Get form data
            $budgetId = (int) ($_POST['budget_id'] ?? 0);
            $newStatus = $_POST['status'] ?? '';
            
            // Validate required fields
            if ($budgetId <= 0) {
                throw new Exception('Invalid budget ID');
            }
            
            if (!in_array($newStatus, ['pending', 'approved', 'declined'])) {
                throw new Exception('Invalid status');
            }
            
            // Get current budget status
            $budgetSQL = "SELECT status FROM budgets WHERE budget_id = ?";
            $budget = fetchOne($budgetSQL, [$budgetId]);
            
            if (!$budget) {
                throw new Exception('Budget not found');
            }
            
            $oldStatus = $budget['status'];
            
            // Skip if status hasn't changed
            if ($oldStatus === $newStatus) {
                echo json_encode(['success' => true, 'message' => 'Status unchanged']);
                exit();
            }
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            // Update budget status
            $updateStatusSQL = "UPDATE budgets SET status = ?, updated_at = NOW() WHERE budget_id = ?";
            $updateStatusParams = [$newStatus, $budgetId];
            $updateStatusTypes = 'si';
            
            $result = update($updateStatusSQL, $updateStatusParams, $updateStatusTypes);
            
            if ($result === false) {
                throw new Exception('Failed to update budget status');
            }
            
            // Record history
            $historySQL = "INSERT INTO budget_history (budget_id, user_id, action, old_status, new_status, created_at) 
                          VALUES (?, ?, 'status_change', ?, ?, NOW())";
            $historyParams = [$budgetId, $userId, $oldStatus, $newStatus];
            $historyTypes = 'iiss';
            
            insert($historySQL, $historyParams, $historyTypes);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Record user activity
            try {
                if (function_exists('recordUserActivity')) {
                    recordUserActivity($userId, 'update', 'budget', $budgetId, 'Updated budget status from ' . $oldStatus . ' to ' . $newStatus);
                }
            } catch (Exception $e) {
                // Silently handle any errors with activity recording
                error_log("Error recording user activity: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Budget status updated successfully']);
            break;
            
        case 'delete':
            // Check if user has admin permission to delete
            if (!$isAdmin) {
                throw new Exception('You do not have permission to delete budgets');
            }
            
            // Get form data
            $budgetId = (int) ($_POST['budget_id'] ?? 0);
            
            // Validate required fields
            if ($budgetId <= 0) {
                throw new Exception('Invalid budget ID');
            }
            
            // Get budget details for activity log
            $budgetSQL = "SELECT title FROM budgets WHERE budget_id = ?";
            $budget = fetchOne($budgetSQL, [$budgetId]);
            
            if (!$budget) {
                throw new Exception('Budget not found');
            }
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            // Delete budget items
            $deleteItemsSQL = "DELETE FROM budget_items WHERE budget_id = ?";
            delete($deleteItemsSQL, [$budgetId], 'i');
            
            // Delete budget comments
            $deleteCommentsSQL = "DELETE FROM budget_comments WHERE budget_id = ?";
            delete($deleteCommentsSQL, [$budgetId], 'i');
            
            // Delete budget history
            $deleteHistorySQL = "DELETE FROM budget_history WHERE budget_id = ?";
            delete($deleteHistorySQL, [$budgetId], 'i');
            
            // Delete budget
            $deleteBudgetSQL = "DELETE FROM budgets WHERE budget_id = ?";
            $result = delete($deleteBudgetSQL, [$budgetId], 'i');
            
            if ($result === false) {
                throw new Exception('Failed to delete budget');
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Record user activity
            try {
                if (function_exists('recordUserActivity')) {
                    recordUserActivity($userId, 'delete', 'budget', $budgetId, 'Deleted budget: ' . $budget['title']);
                }
            } catch (Exception $e) {
                // Silently handle any errors with activity recording
                error_log("Error recording user activity: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true, 'message' => 'Budget deleted successfully']);
            break;
            
        case 'add_comment':
            // Get form data
            $budgetId = (int) ($_POST['budget_id'] ?? 0);
            $comment = $_POST['comment'] ?? '';
            
            // Validate required fields
            if ($budgetId <= 0) {
                throw new Exception('Invalid budget ID');
            }
            
            if (empty($comment)) {
                throw new Exception('Comment cannot be empty');
            }
            
            // Check if budget exists
            $budgetSQL = "SELECT budget_id FROM budgets WHERE budget_id = ?";
            $budget = fetchOne($budgetSQL, [$budgetId]);
            
            if (!$budget) {
                throw new Exception('Budget not found');
            }
            
            // Insert comment
            $insertCommentSQL = "INSERT INTO budget_comments (budget_id, user_id, comment, created_at) 
                               VALUES (?, ?, ?, NOW())";
            $insertCommentParams = [$budgetId, $userId, $comment];
            $insertCommentTypes = 'iis';
            
            $commentId = insert($insertCommentSQL, $insertCommentParams, $insertCommentTypes);
            
            if (!$commentId) {
                throw new Exception('Failed to add comment');
            }
            
            // Record user activity
            try {
                if (function_exists('recordUserActivity')) {
                    recordUserActivity($userId, 'comment', 'budget', $budgetId, 'Added comment to budget');
                }
            } catch (Exception $e) {
                // Silently handle any errors with activity recording
                error_log("Error recording user activity: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Comment added successfully',
                'comment_id' => $commentId,
                'user_name' => $userName,
                'user_initial' => $userInitial,
                'comment' => $comment,
                'date' => date('M d, Y H:i')
            ]);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (Exception $e) {
    // Rollback transaction if active
    if (mysqli_get_connection_stats($conn)['in_transaction']) {
        mysqli_rollback($conn);
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 