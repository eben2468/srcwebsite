<?php
/**
 * Feedback Assignment Fix
 * This file contains the improved assignment logic for manage-feedback.php
 */

// Include required files
require_once 'includes/simple_auth.php';
require_once 'includes/auth_functions.php';
require_once 'includes/db_config.php';
require_once 'includes/db_functions.php';
require_once 'includes/settings_functions.php';
require_once 'includes/feedback_notifications.php';

// Require login for this page
requireLogin();

// Get current user info
$currentUser = getCurrentUser();
$canManageFeedback = shouldUseAdminInterface() || isMember();

if (!$canManageFeedback) {
    header('Location: pages_php/dashboard.php?error=access_denied');
    exit();
}

/**
 * Improved feedback assignment function
 */
function assignFeedbackImproved($feedbackId, $assignedToName, $newStatus, $currentUser) {
    try {
        // Validate inputs
        if ($feedbackId <= 0) {
            return ['success' => false, 'message' => 'Invalid feedback ID.'];
        }
        
        if (empty($assignedToName)) {
            return ['success' => false, 'message' => 'Please select a member to assign the feedback to.'];
        }
        
        // Check if feedback exists
        $feedbackCheckSql = "SELECT feedback_id, subject, assigned_to FROM feedback WHERE feedback_id = ?";
        $existingFeedback = fetchOne($feedbackCheckSql, [$feedbackId]);
        
        if (!$existingFeedback) {
            return ['success' => false, 'message' => 'Feedback not found.'];
        }
        
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
            // Find user by name
            $userSql = "SELECT user_id, first_name, last_name, role FROM users WHERE CONCAT(first_name, ' ', last_name) = ? AND role IN ('admin', 'member') LIMIT 1";
            $userResult = fetchOne($userSql, [$assignedToName]);
            
            if (!$userResult) {
                // Log available users for debugging
                $debugSql = "SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
                $availableUsers = fetchAll($debugSql);
                
                error_log("Assignment failed - User '$assignedToName' not found. Available users: " . json_encode($availableUsers));
                
                return ['success' => false, 'message' => "Selected user '$assignedToName' not found. Please refresh the page and try again."];
            }
            
            $assignedToUserId = $userResult['user_id'];
        }
        
        // Update feedback assignment
        $updateSql = "UPDATE feedback SET assigned_to = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE feedback_id = ?";
        $updateResult = update($updateSql, [$assignedToUserId, $newStatus, $feedbackId]);
        
        if (!$updateResult) {
            error_log("Assignment update failed - SQL: $updateSql, Params: " . json_encode([$assignedToUserId, $newStatus, $feedbackId]));
            return ['success' => false, 'message' => 'Failed to update assignment in database.'];
        }
        
        $message = 'Feedback assigned successfully!';
        
        // Send notification to newly assigned user
        if (!empty($assignedToUserId)) {
            $notificationResult = notifyFeedbackAssignment($feedbackId, $assignedToUserId, $currentUser['user_id']);
            
            if ($notificationResult && $notificationResult['notification_sent']) {
                $message .= ' Notification sent to assigned user.';
                
                if ($notificationResult['email_sent']) {
                    $message .= ' Email notification sent.';
                }
                
                error_log("Feedback assignment notification sent to user ID: $assignedToUserId for feedback ID: $feedbackId");
            } else {
                error_log("Failed to send notification to user ID: $assignedToUserId for feedback ID: $feedbackId");
                $message .= ' (Note: Notification sending failed)';
            }
        }
        
        return ['success' => true, 'message' => $message];
        
    } catch (Exception $e) {
        error_log("Exception in assignFeedbackImproved: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during assignment. Please try again.'];
    }
}

// Process assignment if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_feedback'])) {
    $feedbackId = intval($_POST['feedback_id'] ?? 0);
    $assignedToName = trim($_POST['assigned_to'] ?? '');
    $newStatus = $_POST['new_status'] ?? 'in_progress';
    
    $result = assignFeedbackImproved($feedbackId, $assignedToName, $newStatus, $currentUser);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    // Redirect to prevent form resubmission
    header('Location: pages_php/manage-feedback.php');
    exit();
}

// If this file is accessed directly, show test interface
if (basename($_SERVER['PHP_SELF']) === 'feedback_assignment_fix.php') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Feedback Assignment Fix Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; background: #d4edda; padding: 10px; border-left: 4px solid #28a745; }
            .error { color: red; background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; }
            .info { color: blue; background: #d1ecf1; padding: 10px; border-left: 4px solid #17a2b8; }
            table { border-collapse: collapse; width: 100%; margin: 10px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .btn { padding: 10px 15px; margin: 5px; text-decoration: none; border-radius: 5px; }
            .btn-primary { background: #007bff; color: white; }
            .btn-success { background: #28a745; color: white; }
        </style>
    </head>
    <body>
        <h1>Feedback Assignment Fix Test</h1>
        
        <?php
        // Test the assignment functionality
        echo "<div class='info'>";
        echo "<h3>Testing Assignment Functionality</h3>";
        
        // Get available members
        $membersSql = "SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('admin', 'member') ORDER BY first_name, last_name";
        $members = fetchAll($membersSql);
        
        echo "<p><strong>Available Members:</strong> " . count($members) . "</p>";
        
        if (!empty($members)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Role</th></tr>";
            foreach ($members as $member) {
                echo "<tr>";
                echo "<td>" . $member['user_id'] . "</td>";
                echo "<td>" . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($member['role']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>No admin/member users found! This is why assignment is failing.</div>";
        }
        
        // Get sample feedback
        $feedbackSql = "SELECT feedback_id, subject, status, assigned_to FROM feedback ORDER BY created_at DESC LIMIT 5";
        $feedbacks = fetchAll($feedbackSql);
        
        echo "<p><strong>Recent Feedback:</strong> " . count($feedbacks) . "</p>";
        
        if (!empty($feedbacks)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Subject</th><th>Status</th><th>Assigned To</th></tr>";
            foreach ($feedbacks as $feedback) {
                echo "<tr>";
                echo "<td>" . $feedback['feedback_id'] . "</td>";
                echo "<td>" . htmlspecialchars(substr($feedback['subject'], 0, 50)) . "</td>";
                echo "<td>" . htmlspecialchars($feedback['status']) . "</td>";
                echo "<td>" . ($feedback['assigned_to'] ?? 'Unassigned') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "</div>";
        
        // Test assignment if both members and feedback exist
        if (!empty($members) && !empty($feedbacks)) {
            $testFeedback = $feedbacks[0];
            $testMember = $members[0];
            $testMemberName = $testMember['first_name'] . ' ' . $testMember['last_name'];
            
            echo "<div class='info'>";
            echo "<h3>Test Assignment</h3>";
            echo "<p>Testing assignment of Feedback #{$testFeedback['feedback_id']} to {$testMemberName}</p>";
            
            if (isset($_GET['test_assign'])) {
                $result = assignFeedbackImproved($testFeedback['feedback_id'], $testMemberName, 'in_progress', $currentUser);
                
                if ($result['success']) {
                    echo "<div class='success'>" . $result['message'] . "</div>";
                } else {
                    echo "<div class='error'>" . $result['message'] . "</div>";
                }
            } else {
                echo "<a href='?test_assign=1' class='btn btn-primary'>Test Assignment</a>";
            }
            echo "</div>";
        }
        ?>
        
        <div class="info">
            <h3>Next Steps</h3>
            <ol>
                <li>Ensure you have admin/member users in your database</li>
                <li>Test the assignment functionality above</li>
                <li>If successful, the fix can be integrated into manage-feedback.php</li>
                <li>Check notifications table for assignment notifications</li>
            </ol>
        </div>
        
        <p>
            <a href="pages_php/manage-feedback.php" class="btn btn-success">Go to Manage Feedback</a>
            <a href="fix_feedback_assignment.php" class="btn btn-primary">Run Full Diagnostic</a>
        </p>
    </body>
    </html>
    <?php
}
?>