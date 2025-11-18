<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user is admin or member
$isAdmin = isAdmin();
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember;

if (!$canManageEvents) {
    $_SESSION['error'] = "You don't have permission to export event attendees.";
    header("Location: dashboard.php");
    exit();
}

// Get event ID from URL
$eventId = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Fetch event from database
$event = fetchOne("SELECT * FROM events WHERE event_id = ?", [$eventId]);

// Check if event exists
if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header("Location: events.php");
    exit();
}

// Check if user can manage this specific event (admin or event organizer)
$currentUser = getCurrentUser();
$canEditEvent = $isAdmin || ($isMember && $event['organizer_id'] == $currentUser['user_id']);

if (!$canEditEvent) {
    $_SESSION['error'] = "You don't have permission to export attendees for this event.";
    header("Location: event-detail.php?id=" . $eventId);
    exit();
}

// Fetch all attendees for this event
$attendees = fetchAll("SELECT * FROM event_attendees WHERE event_id = ? ORDER BY registration_date DESC", [$eventId]);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . sanitizeFilename($event['title']) . '_attendees_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fputs($output, "\xEF\xBB\xBF");

// Set column headers
fputcsv($output, [
    'Name', 
    'Email', 
    'Phone', 
    'Status', 
    'Registration Date',
    'Notes'
]);

// Add attendee data rows
foreach ($attendees as $attendee) {
    fputcsv($output, [
        $attendee['name'],
        $attendee['email'],
        $attendee['phone'] ?? '',
        ucfirst($attendee['status']),
        date('Y-m-d H:i:s', strtotime($attendee['registration_date'])),
        $attendee['notes'] ?? ''
    ]);
}

// Close the file pointer
fclose($output);
exit;

/**
 * Helper function to sanitize filename
 * 
 * @param string $filename The filename to sanitize
 * @return string Sanitized filename
 */
function sanitizeFilename($filename) {
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove any non-alphanumeric characters except underscores and hyphens
    $filename = preg_replace('/[^A-Za-z0-9_\-]/', '', $filename);
    
    // Limit length
    $filename = substr($filename, 0, 50);
    
    return $filename;
}
?> 
