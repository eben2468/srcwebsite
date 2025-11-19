<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';

// Require login and super admin privileges ONLY
requireLogin();
$currentUser = getCurrentUser();
if (!$currentUser || !isSuperAdmin()) {
    $_SESSION['error'] = "Access denied. This feature is restricted to super administrators only.";
    header("Location: dashboard.php");
    exit;
}

$success_message = '';
$error_message = '';

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reset_password'])) {
        $userId = intval($_POST['user_id']);
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($newPassword)) {
            $error_message = "Please enter a new password.";
        } elseif (strlen($newPassword) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update user's password
            $updateResult = update("UPDATE users SET password = ? WHERE user_id = ?", [$hashedPassword, $userId]);
            
            if ($updateResult !== false) {
                // Get user info for logging
                $user = fetchOne("SELECT username, email FROM users WHERE user_id = ?", [$userId]);
                $success_message = "Password reset successfully for user: " . $user['username'];
                
                // Log the action
                error_log("Admin password reset: {$currentUser['username']} reset password for {$user['username']} ({$user['email']})");
            } else {
                $error_message = "Error updating password. Please try again.";
            }
        }
    } elseif (isset($_POST['generate_temp_password'])) {
        $userId = intval($_POST['user_id']);
        
        // Generate a temporary password
        $tempPassword = 'temp' . rand(1000, 9999);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Update user's password
        $updateResult = update("UPDATE users SET password = ? WHERE user_id = ?", [$hashedPassword, $userId]);
        
        if ($updateResult !== false) {
            $user = fetchOne("SELECT username, email FROM users WHERE user_id = ?", [$userId]);
            $success_message = "Temporary password generated for {$user['username']}: <strong>$tempPassword</strong><br>";
            $success_message .= "Please share this with the user and ask them to change it after login.";
            
            // Log the action
            error_log("Admin temp password: {$currentUser['username']} generated temp password for {$user['username']} ({$user['email']})");
        } else {
            $error_message = "Error generating temporary password. Please try again.";
        }
    } elseif (isset($_POST['process_admin_request'])) {
        $requestId = intval($_POST['request_id']);
        $action = $_POST['action'] ?? '';
        $adminNotes = trim($_POST['admin_notes'] ?? '');



        // Validate that an action was selected
        if (empty($action)) {
            $error_message = "Please select an action (Approve or Reject) before processing the request.";
        } elseif ($action === 'approve') {
            // Approve and reset password
            $newPassword = trim($_POST['new_password'] ?? '');
            if (empty($newPassword)) {
                $error_message = "Password field is empty. Please ensure you select 'Approve' first, then enter a password in the field that appears, and try again.";
            } elseif (strlen($newPassword) < 6) {
                $error_message = "Password must be at least 6 characters long.";
            } else {
                // Get request details
                $request = fetchOne("SELECT * FROM admin_reset_requests WHERE id = ?", [$requestId]);
                if ($request) {
                    // Find user and update password
                    $user = fetchOne("SELECT user_id, username FROM users WHERE email = ?", [$request['email']]);
                    if ($user) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateResult = update("UPDATE users SET password = ? WHERE user_id = ?", [$hashedPassword, $user['user_id']]);

                        if ($updateResult !== false) {
                            // Update request status
                            update("UPDATE admin_reset_requests SET status = 'completed', admin_notes = ?, processed_by = ?, updated_at = NOW() WHERE id = ?",
                                   [$adminNotes, $currentUser['user_id'], $requestId]);

                            $success_message = "Password reset completed for {$user['username']}. New password: <strong>$newPassword</strong>";
                            error_log("Admin completed reset request #$requestId for {$user['username']}");
                        } else {
                            $error_message = "Error updating password.";
                        }
                    } else {
                        $error_message = "User not found.";
                    }
                } else {
                    $error_message = "Request not found.";
                }
            }
        } elseif ($action === 'reject') {
            // Reject request
            $updateResult = update("UPDATE admin_reset_requests SET status = 'rejected', admin_notes = ?, processed_by = ?, updated_at = NOW() WHERE id = ?",
                                   [$adminNotes, $currentUser['user_id'], $requestId]);

            if ($updateResult !== false) {
                $success_message = "Request #$requestId has been rejected.";
                error_log("Admin rejected reset request #$requestId");
            } else {
                $error_message = "Error updating request status.";
            }
        }
    }
}

// Get all users
$users = fetchAll("SELECT user_id, username, email, role, phone FROM users ORDER BY username");

// Get recent password reset requests
$resetRequests = fetchAll("
    SELECT prt.*, u.username, u.email
    FROM password_reset_tokens prt
    JOIN users u ON prt.user_id = u.user_id
    WHERE prt.expires_at > NOW()
    ORDER BY prt.created_at DESC
    LIMIT 10
");

// Get admin reset requests
$adminRequests = fetchAll("
    SELECT arr.*, p.username as processed_by_username
    FROM admin_reset_requests arr
    LEFT JOIN users p ON arr.processed_by = p.user_id
    ORDER BY arr.created_at DESC
    LIMIT 20
");
?>
<?php
// Include the main system header which handles all the HTML structure
include 'includes/header.php';
?>
    
            <?php
            // Define page title, icon, and actions for the modern header
            $pageTitle = "Admin Password Reset";
            $pageIcon = "fa-key";
            $pageDescription = "Manage user password resets and generate temporary passwords";
            $actions = [];

            // Add back button
            $backButton = [
                'href' => 'dashboard.php',
                'icon' => 'fa-arrow-left',
                'text' => 'Back to Dashboard'
            ];

            // Include the modern page header
            include 'includes/modern_page_header.php';
            ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success persistent-alert" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger persistent-alert" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Admin Reset Requests Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-shield me-2"></i>Admin Reset Requests
                                <?php
                                $pendingCount = count(array_filter($adminRequests, function($req) { return $req['status'] === 'pending'; }));
                                if ($pendingCount > 0):
                                ?>
                                    <span class="badge bg-warning ms-2"><?php echo $pendingCount; ?> Pending</span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($adminRequests)): ?>
                                <p class="text-muted">No admin reset requests found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request ID</th>
                                                <th>User Details</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($adminRequests as $request): ?>
                                                <tr class="<?php echo $request['status'] === 'pending' ? 'table-warning' : ''; ?>">
                                                    <td>
                                                        <strong>#<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($request['username']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                        <?php if ($request['phone']): ?>
                                                            <br><small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['phone']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars(substr($request['reason'], 0, 100)) . (strlen($request['reason']) > 100 ? '...' : ''); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusColors = [
                                                            'pending' => 'warning',
                                                            'approved' => 'info',
                                                            'completed' => 'success',
                                                            'rejected' => 'danger'
                                                        ];
                                                        $color = $statusColors[$request['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $color; ?>">
                                                            <?php echo ucfirst($request['status']); ?>
                                                        </span>
                                                        <?php if ($request['processed_by_username']): ?>
                                                            <br><small class="text-muted">by <?php echo htmlspecialchars($request['processed_by_username']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-sm btn-success me-1"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#processModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-check"></i> Process
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- Process Request Modal -->
                                                <div class="modal fade" id="processModal<?php echo $request['id']; ?>" tabindex="-1" data-bs-backdrop="false">
                                                    <div class="modal-dialog modal-xl">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Process Reset Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <strong>User:</strong> <?php echo htmlspecialchars($request['username']); ?><br>
                                                                            <strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?><br>
                                                                            <?php if ($request['phone']): ?>
                                                                                <strong>Phone:</strong> <?php echo htmlspecialchars($request['phone']); ?><br>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>Requested:</strong> <?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?>
                                                                        </div>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <strong>Reason:</strong><br>
                                                                        <div class="bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></div>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label fw-bold text-primary">Action Required: <span class="text-danger">*</span></label>
                                                                        <div class="border rounded p-3 bg-light">
                                                                            <div class="form-check mb-2">
                                                                                <input class="form-check-input" type="radio" name="action" value="approve" id="approve<?php echo $request['id']; ?>" required>
                                                                                <label class="form-check-label fw-semibold" for="approve<?php echo $request['id']; ?>">
                                                                                    <i class="fas fa-check text-success"></i> Approve and Reset Password
                                                                                </label>
                                                                            </div>
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="radio" name="action" value="reject" id="reject<?php echo $request['id']; ?>" required>
                                                                                <label class="form-check-label fw-semibold" for="reject<?php echo $request['id']; ?>">
                                                                                    <i class="fas fa-times text-danger"></i> Reject Request
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <small class="text-muted">Please select one of the above actions to proceed</small>
                                                                    </div>

                                                                    <!-- Password field - shown by default, hidden when reject is selected -->
                                                                    <div class="mb-3 border rounded p-3 bg-light" id="passwordField<?php echo $request['id']; ?>" style="display: block;">
                                                                        <label for="new_password<?php echo $request['id']; ?>" class="form-label fw-bold text-danger">
                                                                            <i class="fas fa-key"></i> New Password <span class="text-danger">*</span>
                                                                        </label>
                                                                        <input type="password" class="form-control border-danger"
                                                                               id="new_password<?php echo $request['id']; ?>"
                                                                               name="new_password"
                                                                               minlength="6"
                                                                               placeholder="Enter new password for user (required for approval)"
                                                                               autocomplete="new-password">
                                                                        <div class="form-text text-danger fw-semibold">
                                                                            <i class="fas fa-exclamation-triangle"></i> Password is required when approving requests (minimum 6 characters)
                                                                        </div>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label for="admin_notes<?php echo $request['id']; ?>" class="form-label">Admin Notes</label>
                                                                        <textarea class="form-control"
                                                                                  id="admin_notes<?php echo $request['id']; ?>"
                                                                                  name="admin_notes"
                                                                                  rows="3"
                                                                                  placeholder="Optional notes for the user or other admins"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="process_admin_request" class="btn btn-primary">Process Request</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- View Request Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1" data-bs-backdrop="false">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Request #<?php echo str_pad($request['id'], 6, '0', STR_PAD_LEFT); ?> Details</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row mb-3">
                                                                    <div class="col-sm-4"><strong>User:</strong></div>
                                                                    <div class="col-sm-8"><?php echo htmlspecialchars($request['username']); ?></div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-sm-4"><strong>Email:</strong></div>
                                                                    <div class="col-sm-8"><?php echo htmlspecialchars($request['email']); ?></div>
                                                                </div>
                                                                <?php if ($request['phone']): ?>
                                                                <div class="row mb-3">
                                                                    <div class="col-sm-4"><strong>Phone:</strong></div>
                                                                    <div class="col-sm-8"><?php echo htmlspecialchars($request['phone']); ?></div>
                                                                </div>
                                                                <?php endif; ?>
                                                                <div class="row mb-3">
                                                                    <div class="col-sm-4"><strong>Status:</strong></div>
                                                                    <div class="col-sm-8">
                                                                        <span class="badge bg-<?php echo $statusColors[$request['status']] ?? 'secondary'; ?>">
                                                                            <?php echo ucfirst($request['status']); ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-sm-4"><strong>Requested:</strong></div>
                                                                    <div class="col-sm-8"><?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?></div>
                                                                </div>
                                                                <?php if ($request['processed_by_username']): ?>
                                                                <div class="row mb-3">
                                                                    <div class="col-sm-4"><strong>Processed by:</strong></div>
                                                                    <div class="col-sm-8"><?php echo htmlspecialchars($request['processed_by_username']); ?></div>
                                                                </div>
                                                                <?php endif; ?>
                                                                <div class="mb-3">
                                                                    <strong>Reason:</strong><br>
                                                                    <div class="bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></div>
                                                                </div>
                                                                <?php if ($request['admin_notes']): ?>
                                                                <div class="mb-3">
                                                                    <strong>Admin Notes:</strong><br>
                                                                    <div class="bg-light p-2 rounded"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Sections Row -->
            <div class="row">
                <!-- Recent Reset Requests -->
                <div class="col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Token Requests
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($resetRequests)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clock fa-2x mb-3"></i>
                                    <p>No recent token-based reset requests.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Requested</th>
                                                <th>Code</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resetRequests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($request['username']); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <small><?php echo date('M j, H:i', strtotime($request['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <code class="small"><?php echo substr($request['token'], 0, 8); ?></code>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- User Password Management -->
                <div class="col-lg-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users me-2"></i>User Password Management
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User Details</th>
                                            <th>Role</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-initials me-3" style="width: 40px; height: 40px; background-color: #<?php echo substr(md5($user['username']), 0, 6); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                            <?php if ($user['phone']): ?>
                                                                <br><small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'member' ? 'warning' : 'secondary'); ?> fs-6">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#resetModal<?php echo $user['user_id']; ?>"
                                                                title="Reset Password">
                                                            <i class="fas fa-key"></i> Reset
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-sm"
                                                                onclick="generateTempPassword(<?php echo $user['user_id']; ?>)"
                                                                title="Generate Temporary Password">
                                                            <i class="fas fa-random"></i> Temp
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <!-- Reset Password Modal -->
                                            <div class="modal fade" id="resetModal<?php echo $user['user_id']; ?>" tabindex="-1" data-bs-backdrop="false">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reset Password for <?php echo htmlspecialchars($user['username']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="new_password<?php echo $user['user_id']; ?>" class="form-label">New Password</label>
                                                                    <input type="password" class="form-control" 
                                                                           id="new_password<?php echo $user['user_id']; ?>" 
                                                                           name="new_password" 
                                                                           required minlength="6">
                                                                    <div class="form-text">Minimum 6 characters</div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Hidden form for temp password generation -->
    <form id="tempPasswordForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="tempUserId">
        <input type="hidden" name="generate_temp_password" value="1">
    </form>

    <!-- Disable sidebar toggle conflicts on this page -->
    <script>
        window.DISABLE_FOOTER_SIDEBAR_TOGGLE = true;
        window.DISABLE_MAIN_SIDEBAR_TOGGLE = false; // Keep main toggle enabled
    </script>

    <script>
    // Minimal persistent alert protection - NO modal interference
    document.addEventListener('DOMContentLoaded', function() {
        // Only protect persistent alerts from global dismissal - no other interference
        const originalDismissAllAlerts = window.dismissAllAlerts;
        if (originalDismissAllAlerts) {
            window.dismissAllAlerts = function() {
                // Only dismiss non-persistent alerts
                const alerts = document.querySelectorAll('.alert:not(.persistent-alert)');
                alerts.forEach(alert => {
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                            const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
                            bsAlert.close();
                        } else {
                            alert.style.display = 'none';
                        }
                    } catch (e) {
                        console.log('Error dismissing alert:', e);
                    }
                });
            };
        }

        // Manual close for persistent alerts only
        document.querySelectorAll('.persistent-alert .btn-close').forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                if (alert) {
                    alert.style.display = 'none';
                }
            });
        });
    });

    // Function to generate temporary password
    function generateTempPassword(userId) {
        document.getElementById('tempUserId').value = userId;
        document.getElementById('tempPasswordForm').submit();
    }
    </script>

    <?php include 'includes/footer.php'; ?>

    <style>
        /* Remove any conflicting layout styles - let the system handle layout */

        /* Persistent alert styling */
        .persistent-alert {
            position: relative;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
            border: 1px solid transparent;
        }

        .persistent-alert.alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }

        .persistent-alert.alert-danger {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }

        .persistent-alert .btn-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 2;
            padding: 0.375rem;
        }

        /* Ensure persistent alerts are visible but don't interfere with modals */
        .persistent-alert {
            display: block;
            opacity: 1;
            visibility: visible;
        }

        /* Ensure modals work properly - no interference */
        .modal, .modal *, .modal-backdrop {
            /* Let Bootstrap handle all modal styling and behavior */
        }

        /* Card styling improvements */
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.5rem;
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 0.5rem 0.5rem 0 0 !important;
            padding: 1rem 1.25rem;
        }

        .table-responsive {
            border-radius: 0.375rem;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            font-size: 0.875rem;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .btn-group .btn {
            margin: 0 2px;
        }

        .avatar-initials {
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
        }

        .text-center.text-muted {
            padding: 2rem 1rem;
        }

        .text-center.text-muted i {
            opacity: 0.5;
        }

        /* Ensure cards fill available height */
        .h-100 {
            height: 100% !important;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .btn-group {
                flex-direction: column;
            }

            .btn-group .btn {
                margin: 2px 0;
                border-radius: 0.375rem !important;
            }
        }

        /* Status badges styling */
        .badge.bg-danger { background-color: #dc3545 !important; }
        .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
        .badge.bg-secondary { background-color: #6c757d !important; }
        .badge.bg-success { background-color: #198754 !important; }
        .badge.bg-info { background-color: #0dcaf0 !important; color: #000 !important; }

        /* Card header colors */
        .card-header.bg-info { background-color: #0dcaf0 !important; }
        .card-header.bg-success { background-color: #198754 !important; }
        .card-header.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
        
        /* Prevent blinking and animations on admin password reset page */
        /*.header, .navbar, .sidebar {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }
        
        /* Disable hover effects that might cause blinking */
        /*.modal:hover, .card:hover {
            transform: none !important;
        }
        
        /* Ensure password fields work properly */
        [id^="passwordField"] {
            transition: none !important;
            animation: none !important;
        }
        
        /* Prevent any parallax or mouse-based effects */
        /*body, html {
            overflow-x: hidden;
        }
        
        /* Disable any global animations */
        /*.animate-fadeIn, .animate-float, .animate-pulse {
            animation: none !important;
        }
        
        /* Ensure form controls work normally */
        /*.btn, .form-control, .modal {
            transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out !important;
        }*/

        /* Mobile Full-Width Optimization for Admin Password Reset Page */
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
            .card, .table-container {
                margin-left: 0 !important;
                margin-right: 0 !important;
                border-radius: 0 !important;
            }
        }

    </style>

    <script>
        function generateTempPassword(userId) {
            if (confirm('Generate a temporary password for this user? The current password will be replaced.')) {
                document.getElementById('tempUserId').value = userId;
                document.getElementById('tempPasswordForm').submit();
            }
        }

        // Simple modal interaction handling - no conflicts
        /*document.addEventListener('DOMContentLoaded', function() {
            // Disable any global animations or effects that might cause blinking
            document.body.style.setProperty('--disable-animations', 'true');
            
            // Disable dashboard animations specifically
            if (window.initializeParallaxHeader) {
                window.initializeParallaxHeader = function() {}; // Override to do nothing
            }
            
            // Clear any existing transforms on header elements
            const headers = document.querySelectorAll('.header, .navbar');
            headers.forEach(header => {
                header.style.transform = 'none';
                header.style.transition = 'none';
            });*/
            
            // Function to toggle password field visibility
            function togglePasswordField(requestId, show) {
                const passwordField = document.getElementById('passwordField' + requestId);
                const passwordInput = document.getElementById('new_password' + requestId);
                
                if (passwordField && passwordInput) {
                    if (show) {
                        passwordField.style.display = 'block';
                        passwordField.style.visibility = 'visible';
                        passwordInput.required = true;
                        passwordInput.disabled = false;
                        passwordField.classList.add('border-danger');
                        console.log('Password field shown for request', requestId);
                    } else {
                        passwordField.style.display = 'none';
                        passwordField.style.visibility = 'hidden';
                        passwordInput.required = false;
                        passwordInput.disabled = true;
                        passwordInput.value = '';
                        passwordField.classList.remove('border-danger');
                        console.log('Password field hidden for request', requestId);
                    }
                }
            }
            
            // Initialize all password fields as visible by default
            function initializePasswordFields() {
                const allPasswordFields = document.querySelectorAll('[id^="passwordField"]');
                allPasswordFields.forEach(field => {
                    const requestId = field.id.replace('passwordField', '');
                    const passwordInput = document.getElementById('new_password' + requestId);
                    
                    if (passwordInput) {
                        field.style.display = 'block';
                        field.style.visibility = 'visible';
                        passwordInput.required = true;
                        passwordInput.disabled = false;
                        field.classList.add('border-danger');
                    }
                });
            }
            
            // Initialize password fields on page load
            setTimeout(() => {
                initializePasswordFields();
                
                // Check for any reject radio buttons that are selected and hide their password fields
                const rejectRadios = document.querySelectorAll('input[name="action"][value="reject"]:checked');
                rejectRadios.forEach(radio => {
                    const form = radio.closest('form');
                    if (form) {
                        const requestIdInput = form.querySelector('input[name="request_id"]');
                        if (requestIdInput) {
                            togglePasswordField(requestIdInput.value, false);
                        }
                    }
                });
            }, 100);
            
            // Handle modal show events to ensure password fields are visible
            document.addEventListener('shown.bs.modal', function(event) {
                const modal = event.target;
                const form = modal.querySelector('form');
                if (form) {
                    const requestIdInput = form.querySelector('input[name="request_id"]');
                    const rejectRadio = form.querySelector('input[name="action"][value="reject"]');
                    
                    if (requestIdInput) {
                        const requestId = requestIdInput.value;
                        // Show password field by default, hide only if reject is selected
                        if (rejectRadio && rejectRadio.checked) {
                            togglePasswordField(requestId, false);
                        } else {
                            togglePasswordField(requestId, true);
                        }
                    }
                }
            });

            // Handle action radio button changes and clicks
            document.addEventListener('change', function(event) {
                if (event.target && event.target.name === 'action') {
                    const form = event.target.closest('form');
                    if (form) {
                        const requestIdInput = form.querySelector('input[name="request_id"]');
                        if (requestIdInput) {
                            const requestId = requestIdInput.value;
                            const isApprove = event.target.value === 'approve';
                            togglePasswordField(requestId, isApprove);
                        }
                    }
                }
            });
            
            // Also handle click events for immediate response
            document.addEventListener('click', function(event) {
                if (event.target && event.target.name === 'action') {
                    const form = event.target.closest('form');
                    if (form) {
                        const requestIdInput = form.querySelector('input[name="request_id"]');
                        if (requestIdInput) {
                            const requestId = requestIdInput.value;
                            const isApprove = event.target.value === 'approve';
                            // Small delay to ensure radio button state is updated
                            setTimeout(() => {
                                togglePasswordField(requestId, isApprove);
                            }, 10);
                        }
                    }
                }
            });

            // Simple form validation
            document.addEventListener('submit', function(event) {
                const form = event.target;
                const processButton = form.querySelector('button[name="process_admin_request"]');
                
                if (processButton) {
                    const actionRadio = form.querySelector('input[name="action"]:checked');
                    
                    if (!actionRadio) {
                        event.preventDefault();
                        alert('Please select an action (Approve or Reject) before processing the request.');
                        return;
                    }

                    if (actionRadio.value === 'approve') {
                        const requestIdInput = form.querySelector('input[name="request_id"]');
                        const requestId = requestIdInput ? requestIdInput.value : null;
                        const passwordInput = requestId ? document.getElementById('new_password' + requestId) : null;

                        if (!passwordInput || !passwordInput.value.trim()) {
                            event.preventDefault();
                            alert('Please provide a new password when approving a request.');
                            if (passwordInput) passwordInput.focus();
                            return;
                        }
                        
                        if (passwordInput.value.trim().length < 6) {
                            event.preventDefault();
                            alert('Password must be at least 6 characters long.');
                            passwordInput.focus();
                            return;
                        }
                    }
                }
            });
        
    </script>
</body>
</html>
