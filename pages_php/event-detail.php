<?php
// Include authentication file and database connection
require_once '../auth_functions.php';
require_once '../db_config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Set page title
$pageTitle = "Event Details - SRC Management System";

// Get current user info
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember; // Allow both admins and members to manage events

// Get event ID from URL
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Fetch event from database
$event = fetchOne("SELECT * FROM events WHERE event_id = ?", [$eventId]);

// Check if user can edit this specific event (admin or event organizer)
$canEditEvent = $isAdmin || ($isMember && $event && $event['organizer_id'] == $currentUser['user_id']);

// Calculate registration percentage if event exists
$registrationPercentage = 0;
$registrations = 0; // Default value since there's no registrations column

if ($event) {
    if ($event['capacity'] > 0) {
        $registrationPercentage = round(($registrations / $event['capacity']) * 100);
    } else {
        // If capacity is 0 (unlimited), set percentage to 0
        $registrationPercentage = 0;
    }
    
    // Determine progress bar color based on percentage
    if ($registrationPercentage > 90) {
        $progressBarClass = 'bg-danger';
    } elseif ($registrationPercentage > 75) {
        $progressBarClass = 'bg-warning';
    } else {
        $progressBarClass = 'bg-success';
    }
}

// Handle delete action
if (isset($_GET['delete']) && $_GET['delete'] == 'confirm') {
    if ($event) {
        // Check if user is admin or the event organizer
        if ($isAdmin || ($isMember && $event['organizer_id'] == $currentUser['user_id'])) {
            $result = delete("DELETE FROM events WHERE event_id = ?", [$eventId]);
            
            if ($result) {
                header("Location: events.php?deleted=true");
                exit;
            } else {
                $errorMessage = "Error deleting event. Please try again.";
            }
        } else {
            $errorMessage = "You don't have permission to delete this event.";
        }
    }
}

// Handle event registration (if implemented)
if (isset($_POST['register']) && $event) {
    // In a real application, this would add a registration to the database
    // For now, just update the registrations count
    if ($event['capacity'] == 0 || $registrations < $event['capacity']) {
        $successMessage = "You have successfully registered for this event!";
        $registrations++; // Increment local count for display
    } else {
        $errorMessage = "This event has reached its capacity. Registration is closed.";
    }
}

// Handle image and document deletion
if ($canEditEvent) {
    // Handle image deletion
    if (isset($_GET['delete_image']) && $_GET['delete_image'] == 1 && !empty($event['image_path'])) {
        $imagePath = '../' . $event['image_path'];
        
        // Check if file exists before attempting to delete
        if (file_exists($imagePath)) {
            // Try to delete the file
            if (unlink($imagePath)) {
                // Update the database to remove the reference
                $updateSql = "UPDATE events SET image_path = NULL WHERE event_id = ?";
                $result = update($updateSql, [$eventId]);
                
                if ($result) {
                    // Redirect to remove the GET parameter
                    header("Location: event-detail.php?id={$eventId}&success=image_deleted");
                    exit;
                } else {
                    $errorMessage = "Error updating database after deleting image.";
                }
            } else {
                $errorMessage = "Error deleting image file. Check file permissions.";
            }
        } else {
            // File doesn't exist, just update the database
            $updateSql = "UPDATE events SET image_path = NULL WHERE event_id = ?";
            $result = update($updateSql, [$eventId]);
            
            if ($result) {
                header("Location: event-detail.php?id={$eventId}&success=image_deleted");
                exit;
            } else {
                $errorMessage = "Error updating database.";
            }
        }
    }
    
    // Handle document deletion
    if (isset($_GET['delete_document']) && $_GET['delete_document'] == 1 && !empty($event['document_path'])) {
        $documentPath = '../' . $event['document_path'];
        
        // Check if file exists before attempting to delete
        if (file_exists($documentPath)) {
            // Try to delete the file
            if (unlink($documentPath)) {
                // Update the database to remove the reference
                $updateSql = "UPDATE events SET document_path = NULL WHERE event_id = ?";
                $result = update($updateSql, [$eventId]);
                
                if ($result) {
                    // Redirect to remove the GET parameter
                    header("Location: event-detail.php?id={$eventId}&success=document_deleted");
                    exit;
                } else {
                    $errorMessage = "Error updating database after deleting document.";
                }
            } else {
                $errorMessage = "Error deleting document file. Check file permissions.";
            }
        } else {
            // File doesn't exist, just update the database
            $updateSql = "UPDATE events SET document_path = NULL WHERE event_id = ?";
            $result = update($updateSql, [$eventId]);
            
            if ($result) {
                header("Location: event-detail.php?id={$eventId}&success=document_deleted");
                exit;
            } else {
                $errorMessage = "Error updating database.";
            }
        }
    }
}

// Set success message if action was successful
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'image_deleted') {
        $successMessage = "Event image was successfully deleted.";
    } elseif ($_GET['success'] == 'document_deleted') {
        $successMessage = "Event document was successfully deleted.";
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="container-fluid">
    <?php if (!$event): ?>
    <div class="alert alert-danger">
        <h4 class="alert-heading">Event Not Found</h4>
        <p>The event you are looking for does not exist or has been removed.</p>
        <a href="events.php" class="btn btn-outline-danger">
            <i class="fas fa-arrow-left me-2"></i> Back to Events
        </a>
    </div>
    <?php else: ?>
    
    <!-- Notification area -->
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $successMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $errorMessage; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="events.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> Back to Events
        </a>
        <?php if ($canManageEvents): ?>
        <div>
            <?php if ($canEditEvent): ?>
            <a href="event-edit.php?id=<?php echo $event['event_id']; ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-edit me-2"></i> Edit Event
            </a>
            <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                <i class="fas fa-trash me-2"></i> Delete Event
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Event Details -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-calendar-alt me-2"></i> 
                            <?php echo date('M j, Y', strtotime($event['date'])); ?>
                        </div>
                        <div class="d-flex align-items-center text-muted">
                            <i class="fas fa-map-marker-alt me-2"></i> <?php echo htmlspecialchars($event['location']); ?>
                        </div>
                        <span class="badge bg-<?php 
                            echo $event['status'] === 'upcoming' ? 'success' : 
                                ($event['status'] === 'planning' ? 'warning' : 
                                ($event['status'] === 'ongoing' ? 'primary' :
                                ($event['status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                        ?>">
                            <?php echo htmlspecialchars($event['status']); ?>
                        </span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    
                    <?php if (!empty($event['image_path'])): ?>
                    <div class="event-image mb-4">
                        <h5>Event Image</h5>
                        <img src="<?php echo '../' . htmlspecialchars($event['image_path']); ?>" alt="Event Image" class="img-fluid rounded" style="max-height: 450px; width: auto;">
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#imageModal">
                                <i class="fas fa-eye me-2"></i> View
                            </button>
                            <a href="<?php echo '../' . htmlspecialchars($event['image_path']); ?>" class="btn btn-success" download>
                                <i class="fas fa-download me-2"></i> Download
                            </a>
                            <?php if ($canEditEvent): ?>
                            <a href="event-detail.php?id=<?php echo $event['event_id']; ?>&delete_image=1" 
                               class="btn btn-danger"
                               onclick="return confirm('Are you sure you want to delete this image?');">
                                <i class="fas fa-trash me-2"></i> Delete
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h5 class="mb-3">Event Details</h5>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Organizer:</td>
                                        <td><?php echo htmlspecialchars($event['organizer_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Capacity:</td>
                                        <td><?php echo $event['capacity'] > 0 ? $event['capacity'] . ' attendees' : 'Unlimited'; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Registrations:</td>
                                        <td><?php echo $registrations; ?> attendees</td>
                                    </tr>
                                    <?php 
                                    // Extract and display portfolio if it exists
                                    if (preg_match('/Portfolio: ([^\n]+)/', $event['description'], $matches)) {
                                        $portfolioInfo = trim($matches[1]);
                                        echo "<tr>
                                            <td class=\"text-muted\">Portfolio:</td>
                                            <td>" . htmlspecialchars($portfolioInfo) . "</td>
                                        </tr>";
                                    }
                                    ?>
                                    <?php if (!empty($event['document_path'])): ?>
                                    <tr>
                                        <td class="text-muted">Document:</td>
                                        <td>
                                            <div>
                                                <a href="<?php echo '../' . htmlspecialchars($event['document_path']); ?>" class="btn btn-primary" target="_blank">
                                                    <i class="fas fa-eye me-2"></i> View
                                                </a>
                                                <a href="<?php echo '../' . htmlspecialchars($event['document_path']); ?>" class="btn btn-success" download>
                                                    <i class="fas fa-download me-2"></i> Download
                                                </a>
                                                <?php if ($canEditEvent): ?>
                                                <a href="event-detail.php?id=<?php echo $event['event_id']; ?>&delete_document=1" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this document?');">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($isAdmin): ?>
                                    <tr>
                                        <td class="text-muted">Created:</td>
                                        <td><?php echo date('M j, Y', strtotime($event['created_at'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            
                            <?php if ($event['status'] === 'upcoming' || $event['status'] === 'ongoing'): ?>
                            <div class="mt-3">
                                <?php if ($event['capacity'] == 0 || $registrations < $event['capacity']): ?>
                                <form method="post" action="">
                                    <button type="submit" name="register" class="btn btn-primary w-100">
                                        <i class="fas fa-user-plus me-2"></i> Register for Event
                                    </button>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-ban me-2"></i> Registration Full
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Status -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Registration Status</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Registration Progress</span>
                    <span><?php echo $event['capacity'] > 0 ? $registrationPercentage . '%' : 'Unlimited'; ?></span>
                </div>
                <?php if ($event['capacity'] > 0): ?>
                <div class="progress">
                    <div class="progress-bar <?php echo $progressBarClass; ?>" role="progressbar" style="width: <?php echo $registrationPercentage; ?>%"></div>
                </div>
                <div class="text-muted small mt-1">
                    <?php echo $registrations; ?> out of <?php echo $event['capacity']; ?> spots filled
                </div>
                <?php else: ?>
                <div class="progress">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 100%">Unlimited Capacity</div>
                </div>
                <div class="text-muted small mt-1">
                    <?php echo $registrations; ?> registrations so far
                </div>
                <?php endif; ?>
            </div>

            <?php if ($isAdmin): ?>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">
                    <i class="fas fa-users me-2"></i> Manage Attendees
                </button>
                <button class="btn btn-outline-secondary">
                    <i class="fas fa-file-export me-2"></i> Export Attendee List
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($event && $canEditEvent): ?>
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the event "<strong><?php echo htmlspecialchars($event['title']); ?></strong>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="event-detail.php?id=<?php echo $event['event_id']; ?>&delete=confirm" class="btn btn-danger">Delete Event</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Image Modal -->
<?php if ($event && !empty($event['image_path'])): ?>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel"><?php echo htmlspecialchars($event['title']); ?> - Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?php echo '../' . htmlspecialchars($event['image_path']); ?>" alt="Event Image" class="img-fluid" style="max-height: 80vh; max-width: 100%;">
            </div>
            <div class="modal-footer">
                <a href="<?php echo '../' . htmlspecialchars($event['image_path']); ?>" class="btn btn-success" download>
                    <i class="fas fa-download me-2"></i> Download
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>