<?php
/**
 * Manage Feedback Assignment Patch
 * This file contains the improved assignment logic to replace the existing one in manage-feedback.php
 */

// This is the improved assignment processing code that should replace the existing assignment logic in manage-feedback.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log all POST data
    error_log("POST request received: " . json_encode($_POST));

    if (isset($_POST['assign_feedback'])) {
        $feedbackId = intval($_POST['feedback_id'] ?? 0);
        $assignedToName = trim($_POST['assigned_to'] ?? '');
        $newStatus = $_POST['new_status'] ?? 'in_progress';

        // Validate required fields
        if ($feedbackId <= 0) {
            $errorMessage = "Invalid feedback ID.";
        } elseif (empty($assignedToName)) {
            $errorMessage = "Please select a member to assign the feedback to.";
        } else {
            // Check if feedback exists
            $feedbackCheckSql = "SELECT feedback_id, subject, assigned_to FROM feedback WHERE feedback_id = ?";
            $existingFeedback = fetchOne($feedbackCheckSql, [$feedbackId]);
            
            if (!$existingFeedback) {
                $errorMessage = "Feedback not found. Please refresh the page and try again.";
            } else {
                $assignedToUserId = null;
                $previousAssigneeId = $existingFeedback['assigned_to'];
                
                // Handle unassignment
                if ($assignedToName === 'unassigned') {
                    $newStatus = 'pending';
                    
                    // Notify previous assignee if exists
                    if (!empty($previousAssigneeId)) {
                        notifyFeedbackUnassignment($feedbackId, $previousAssigneeId, $currentUser['user_id']);
                    }
                } else {
                    // Find user by name - improved query with better error handling
                    $userSql = "SELECT user_id, first_name, last_name, role FROM users WHERE CONCAT(first_name, ' ', last_name) = ? AND role IN ('admin', 'member') LIMIT 1";
                    error_log("User lookup - Searching for: '$assignedToName'");

                    $userResult = fetchOne($userSql, [$assignedToName]);
                    if ($userResult) {
                        $assignedToUserId = $userResult['user_id'];
                        error_log("User found - ID: $assignedToUserId, Name: {$userResult['first_name']} {$userResult['last_name']}, Role: {$userResult['role']}");
                    } else {
                        error_log("User not found - Name: '$assignedToName'");

                        // Get available users for debugging
                        $debugSql = "SELECT user_id, first_name, last_name, role, CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
                        $debugUsers = fetchAll($debugSql);
                        error_log("Available users for assignment: " . json_encode($debugUsers));

                        // Check if there are any admin/member users at all
                        if (empty($debugUsers)) {
                            $errorMessage = "No admin or member users found in the system. Please ensure users have proper roles assigned.";
                        } else {
                            $errorMessage = "Selected user '$assignedToName' not found. Available users: " . implode(', ', array_column($debugUsers, 'full_name'));
                        }
                    }
                }

                // Only proceed if we have a valid user ID or it's being unassigned
                if (!isset($errorMessage)) {
                    // Update feedback assignment
                    $assignedToValue = ($assignedToName === 'unassigned') ? null : $assignedToUserId;
                    $updateSql = "UPDATE feedback SET assigned_to = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE feedback_id = ?";

                    // Debug logging
                    error_log("Assignment Debug - Feedback ID: $feedbackId, Assigned To: " . ($assignedToValue ?? 'NULL') . ", Status: $newStatus");

                    $updateResult = update($updateSql, [$assignedToValue, $newStatus, $feedbackId]);

                    if ($updateResult) {
                        $successMessage = "Feedback assigned successfully!";
                        error_log("Assignment Success - Feedback ID: $feedbackId assigned to user ID: " . ($assignedToUserId ?? 'NULL'));

                        // Send notification to assigned user
                        if (!empty($assignedToUserId)) {
                            try {
                                $notificationResult = notifyFeedbackAssignment($feedbackId, $assignedToUserId, $currentUser['user_id']);

                                if ($notificationResult && $notificationResult['notification_sent']) {
                                    $successMessage .= " Notification sent to assigned user.";

                                    if ($notificationResult['email_sent']) {
                                        $successMessage .= " Email notification sent.";
                                    }

                                    error_log("Feedback assignment notification sent to user ID: $assignedToUserId for feedback ID: $feedbackId");
                                } else {
                                    error_log("Failed to send notification to user ID: $assignedToUserId for feedback ID: $feedbackId");
                                    $successMessage .= " (Note: Notification sending failed)";
                                }
                            } catch (Exception $e) {
                                error_log("Exception sending notification: " . $e->getMessage());
                                $successMessage .= " (Note: Notification error - " . $e->getMessage() . ")";
                            }
                        }
                    } else {
                        $errorMessage = "Failed to assign feedback. Please try again.";
                        error_log("Assignment Failed - SQL: $updateSql, Params: " . json_encode([$assignedToValue, $newStatus, $feedbackId]));

                        // Additional debugging - check if feedback still exists
                        $recheckSql = "SELECT feedback_id, status, assigned_to FROM feedback WHERE feedback_id = ?";
                        $recheckResult = fetchOne($recheckSql, [$feedbackId]);
                        if ($recheckResult) {
                            error_log("Feedback exists after failed update - Current status: " . $recheckResult['status'] . ", Current assigned_to: " . ($recheckResult['assigned_to'] ?? 'NULL'));
                        } else {
                            error_log("Feedback with ID $feedbackId no longer exists");
                            $errorMessage = "Feedback not found. Please refresh the page and try again.";
                        }
                    }
                }
            }
        }
    }
    
    // Handle feedback response (existing code remains the same)
    if (isset($_POST['respond_feedback'])) {
        $feedbackId = $_POST['feedback_id'] ?? 0;
        $response = $_POST['response'] ?? '';
        $newStatus = $_POST['new_status'] ?? 'resolved';
        $sendNotification = isset($_POST['send_notification']);

        // Get feedback details before updating
        $feedbackSql = "SELECT user_id, subject FROM feedback WHERE feedback_id = ?";
        $feedbackResult = fetchAll($feedbackSql, [$feedbackId]);

        $sql = "UPDATE feedback SET resolution = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE feedback_id = ?";
        $result = update($sql, [$response, $newStatus, $feedbackId]);

        if ($result) {
            $successMessage = "Response submitted successfully!";

            // Always send notification to feedback submitter if they are a registered user
            if (!empty($feedbackResult)) {
                $feedback = $feedbackResult[0];
                $responderName = $currentUser['first_name'] . ' ' . $currentUser['last_name'];

                // Check if user wants notification (default to true for registered users)
                if ($sendNotification || !isset($_POST['send_notification'])) {
                    notifyFeedbackResponse($feedbackId, $feedback['subject'], $response, $responderName);
                }
            }
        } else {
            $errorMessage = "Failed to submit response.";
        }
    }
}

// Improved member loading with better error handling
$membersSql = "SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
$members = fetchAll($membersSql);

// Debug logging for members
error_log("Members loaded for assignment: " . count($members) . " members found");
if (empty($members)) {
    error_log("No members found - SQL: $membersSql");
    
    // Check if there are any users at all
    $allUsersSql = "SELECT user_id, first_name, last_name, role FROM users ORDER BY role, first_name, last_name";
    $allUsers = fetchAll($allUsersSql);
    error_log("All users in system: " . count($allUsers) . " users found");
    
    if (!empty($allUsers)) {
        error_log("User roles in system: " . json_encode(array_count_values(array_column($allUsers, 'role'))));
    }
} else {
    error_log("Sample members: " . json_encode(array_slice($members, 0, 3)));
}

// Improved assignment modal HTML (this should replace the existing modal in the HTML section)
?>

<!-- Improved Assignment Modal -->
<div class="modal fade" id="assignModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" onsubmit="return validateAssignment(this);">
                <div class="modal-body">
                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">

                    <div class="mb-3">
                        <label for="assigned_to<?php echo $feedback['feedback_id']; ?>" class="form-label">Assign to:</label>
                        <select class="form-select" id="assigned_to<?php echo $feedback['feedback_id']; ?>" name="assigned_to" required>
                            <option value="">Select a member...</option>
                            <option value="unassigned">Unassign</option>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $member): ?>
                                <option value="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>" 
                                        data-user-id="<?php echo $member['user_id']; ?>"
                                        data-role="<?php echo $member['role']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> (<?php echo ucfirst($member['role']); ?>)
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No members available</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">
                            Available members: <?php echo count($members); ?>
                            <?php if (empty($members)): ?>
                                <br><span class="text-warning">⚠️ No admin/member users found. Please check user roles in the database.</span>
                                <br><span class="text-info">Users need to have 'admin' or 'member' role to receive assignments.</span>
                            <?php endif; ?>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="new_status<?php echo $feedback['feedback_id']; ?>" class="form-label">Status:</label>
                        <select class="form-select" id="new_status<?php echo $feedback['feedback_id']; ?>" name="new_status" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress" selected>In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <?php if (empty($members)): ?>
                    <div class="alert alert-warning">
                        <strong>No Assignment Options Available</strong><br>
                        No users with 'admin' or 'member' roles were found. Please:
                        <ul class="mb-0 mt-2">
                            <li>Check that users have proper roles assigned in the database</li>
                            <li>Ensure at least one user has 'admin' or 'member' role</li>
                            <li>Contact system administrator if the issue persists</li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="assign_feedback" class="btn btn-primary" <?php echo empty($members) ? 'disabled' : ''; ?>>
                        <?php echo empty($members) ? 'No Members Available' : 'Assign'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Improved assignment validation
function validateAssignment(form) {
    const assignedTo = form.assigned_to.value;
    const newStatus = form.new_status.value;
    
    if (!assignedTo) {
        alert('Please select a member to assign the feedback to.');
        return false;
    }
    
    if (!newStatus) {
        alert('Please select a status.');
        return false;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Assigning...';
    submitBtn.disabled = true;
    
    // Re-enable button after 10 seconds to prevent permanent disable
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 10000);
    
    return true;
}

// Show better error/success messages
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($successMessage)): ?>
    showNotification('<?php echo addslashes($successMessage); ?>', 'success');
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
    showNotification('<?php echo addslashes($errorMessage); ?>', 'error');
    <?php endif; ?>
});

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.assignment-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show assignment-notification`;
    notification.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto-remove after 8 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 8000);
}
</script>

<?php
// This is the end of the patch file
// The above code should replace the corresponding sections in manage-feedback.php
?>