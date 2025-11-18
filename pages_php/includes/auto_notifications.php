<?php
/**
 * Auto Notifications System
 * This file provides easy-to-use functions for automatically creating notifications
 * when content is created, updated, or published across different pages
 */

// Include notification helper if not already included
if (!function_exists('createSystemNotification')) {
    require_once __DIR__ . '/../support/notification_helper.php';
}

/**
 * Auto-notify when news is created
 */
function autoNotifyNewsCreated($news_title, $news_content, $created_by_id, $news_id = null) {
    $description = substr(strip_tags($news_content), 0, 100) . '...';
    return notifyContentCreated('news', $news_title, $description, $created_by_id, $news_id);
}

/**
 * Auto-notify when event is created
 */
function autoNotifyEventCreated($event_title, $event_description, $created_by_id, $event_id = null) {
    $description = substr(strip_tags($event_description), 0, 100) . '...';
    return notifyContentCreated('events', $event_title, $description, $created_by_id, $event_id);
}

/**
 * Auto-notify when election is created
 */
function autoNotifyElectionCreated($election_title, $election_description, $created_by_id, $election_id = null) {
    $description = substr(strip_tags($election_description), 0, 100) . '...';
    return notifyContentCreated('elections', $election_title, $description, $created_by_id, $election_id);
}

/**
 * Auto-notify when gallery images are uploaded
 */
function autoNotifyGalleryUploaded($album_title, $image_count, $created_by_id, $album_id = null) {
    $description = "New images uploaded to gallery album: {$album_title} ({$image_count} images)";
    return notifyContentCreated('gallery', "New Gallery Images", $description, $created_by_id, $album_id);
}

/**
 * Auto-notify when minutes are created
 */
function autoNotifyMinutesCreated($meeting_title, $meeting_date, $created_by_id, $minutes_id = null) {
    $description = "Meeting minutes for {$meeting_title} on {$meeting_date}";
    return notifyContentCreated('minutes', "New Meeting Minutes", $description, $created_by_id, $minutes_id);
}

/**
 * Auto-notify when reports are created
 */
function autoNotifyReportCreated($report_title, $report_type, $created_by_id, $report_id = null) {
    $description = "New {$report_type} report: {$report_title}";
    return notifyContentCreated('reports', "New Report Available", $description, $created_by_id, $report_id);
}

/**
 * Auto-notify when feedback is submitted
 */
function autoNotifyFeedbackSubmitted($feedback_subject, $feedback_type, $created_by_id, $feedback_id = null) {
    // Create more descriptive notification based on portfolio/category
    $portfolio_names = [
        'president' => 'President',
        'vice_president' => 'Vice President',
        'senate_president' => 'Senate President',
        'finance' => 'Finance Officer',
        'editor' => 'Editor',
        'secretary' => 'General Secretary',
        'sports' => 'Sports Commissioner',
        'welfare' => 'Welfare Commissioner',
        'women' => 'Women\'s Commissioner',
        'pro' => 'Public Relations Officer',
        'chaplain' => 'Chaplain',
        'general' => 'General Feedback',
        'website' => 'Website Feedback',
        'other' => 'Other'
    ];
    
    $portfolio_display = $portfolio_names[$feedback_subject] ?? $feedback_subject;
    $title = "New Feedback: " . $portfolio_display;
    $description = "New {$feedback_type} feedback submitted for {$portfolio_display}. Please review and respond as needed.";
    
    return notifyContentCreated('feedback', $title, $description, $created_by_id, $feedback_id);
}

/**
 * Auto-notify when documents are uploaded
 */
function autoNotifyDocumentUploaded($document_title, $document_type, $created_by_id, $document_id = null) {
    $description = "New {$document_type} document: {$document_title}";
    return notifyContentCreated('documents', "New Document Available", $description, $created_by_id, $document_id);
}

/**
 * Auto-notify when welfare requests are created
 */
function autoNotifyWelfareRequest($request_title, $request_type, $created_by_id, $request_id = null) {
    $description = "New {$request_type} welfare request: {$request_title}";
    return notifyContentCreated('welfare', "New Welfare Request", $description, $created_by_id, $request_id);
}

/**
 * Auto-notify when department information is updated
 */
function autoNotifyDepartmentUpdated($department_name, $update_type, $updated_by_id, $department_id = null) {
    $description = "Department {$department_name} has been {$update_type}";
    return notifyContentUpdated('departments', "Department Update", $description, $updated_by_id, $department_id);
}

/**
 * Auto-notify when senate information is updated
 */
function autoNotifySenateUpdated($senate_title, $update_type, $updated_by_id, $senate_id = null) {
    $description = "Senate information updated: {$senate_title}";
    return notifyContentUpdated('senate', "Senate Update", $description, $updated_by_id, $senate_id);
}

/**
 * Auto-notify when finance records are created
 */
function autoNotifyFinanceCreated($transaction_title, $amount, $created_by_id, $transaction_id = null) {
    $description = "New financial transaction: {$transaction_title} - ₵{$amount}";
    return notifyContentCreated('finance', "New Financial Record", $description, $created_by_id, $transaction_id);
}

/**
 * Auto-notify when messages are sent
 */
function autoNotifyMessageSent($message_subject, $recipient_count, $sent_by_id, $message_id = null) {
    $description = "New message sent to {$recipient_count} recipients: {$message_subject}";
    return notifyContentCreated('messaging', "New Message", $description, $sent_by_id, $message_id);
}

/**
 * Generic function to create custom notifications
 */
function autoNotifyCustom($content_type, $title, $description, $created_by_id, $item_id = null, $action = 'created') {
    return createSystemNotification($content_type, $action, $title, $description, $created_by_id, $item_id);
}

/**
 * Batch notification for multiple content items
 */
function autoNotifyBatch($notifications_data) {
    $success_count = 0;
    
    foreach ($notifications_data as $notification) {
        if (isset($notification['type'], $notification['title'], $notification['description'], $notification['created_by_id'])) {
            $result = autoNotifyCustom(
                $notification['type'],
                $notification['title'],
                $notification['description'],
                $notification['created_by_id'],
                $notification['item_id'] ?? null,
                $notification['action'] ?? 'created'
            );
            
            if ($result) {
                $success_count++;
            }
        }
    }
    
    return $success_count;
}

/**
 * Helper function to log notification creation for debugging
 */
function logNotificationCreation($content_type, $title, $user_count, $success = true) {
    $log_message = sprintf(
        "[%s] %s notification '%s' - %s to %d users",
        date('Y-m-d H:i:s'),
        ucfirst($content_type),
        $title,
        $success ? 'sent successfully' : 'failed to send',
        $user_count
    );
    
    // You can uncomment this to enable logging to a file
    // error_log($log_message, 3, __DIR__ . '/../../logs/notifications.log');
}

/**
 * Check if notifications are enabled for a specific content type
 */
function areNotificationsEnabled($content_type) {
    // Check if the feature is enabled in settings
    if (function_exists('hasFeaturePermission')) {
        switch ($content_type) {
            case 'welfare':
                return hasFeaturePermission('enable_welfare');
            case 'finance':
                return hasFeaturePermission('enable_finance');
            case 'support':
                return hasFeaturePermission('enable_support');
            default:
                return true; // Default to enabled for other content types
        }
    }
    
    return true; // Default to enabled if feature checking is not available
}

/**
 * Smart notification wrapper that checks permissions and settings
 */
function smartNotify($content_type, $action, $title, $description, $created_by_id, $item_id = null) {
    // Check if notifications are enabled for this content type
    if (!areNotificationsEnabled($content_type)) {
        return false;
    }
    
    // Create the notification
    $result = createSystemNotification($content_type, $action, $title, $description, $created_by_id, $item_id);
    
    // Log the result for debugging
    logNotificationCreation($content_type, $title, $result ?: 0, $result > 0);
    
    return $result;
}
?>
