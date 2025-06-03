<?php
/**
 * View Button Component
 * This file generates a view button for events
 */

// Get the event ID from the parameter
$event_id = isset($event_id) ? (int)$event_id : 0;

// Generate the button HTML
$button_html = '<a href="event-detail.php?id=' . $event_id . '" class="btn btn-sm btn-primary">';
$button_html .= '<i class="fas fa-eye"></i> View';
$button_html .= '</a>';

// Output the button
echo $button_html;
?> 