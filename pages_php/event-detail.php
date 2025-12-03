<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember;

// Set page title
$pageTitle = "Event Details - SRC Management System";

// Get event ID from URL
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Fetch event details
$event = fetchOne("SELECT * FROM events WHERE event_id = ?", [$eventId]);

// Check if event exists
if (!$event) {
    $_SESSION['error'] = "Event not found.";
    header("Location: events.php");
    exit();
}

// Extract portfolio from description if exists
$eventDescription = $event['description'];
$portfolio = '';
if (preg_match('/\n\nPortfolio: ([^\n]+)/', $eventDescription, $matches)) {
    $portfolio = trim($matches[1]);
    $eventDescription = preg_replace('/\n\nPortfolio: [^\n]+/', '', $eventDescription);
}

// Get organizer information
$organizer = fetchOne("SELECT * FROM users WHERE user_id = ?", [$event['organizer_id']]);
$organizerName = $organizer ? ($organizer['first_name'] . ' ' . $organizer['last_name']) : 'Unknown';

// Get event attendees count
$attendeesResult = fetchOne("SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?", [$eventId]);
$attendeesCount = $attendeesResult ? $attendeesResult['count'] : 0;

// Check if current user is registered
$isRegistered = false;
$registrationResult = fetchOne("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?", [$eventId, $currentUser['user_id']]);
if ($registrationResult) {
    $isRegistered = true;
}

// Handle event registration/unregistration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'register' && !$isRegistered) {
        // Check capacity
        if ($event['capacity'] > 0 && $attendeesCount >= $event['capacity']) {
            $errorMessage = "Event is at full capacity. Registration closed.";
        } else {
            $sql = "INSERT INTO event_registrations (event_id, user_id, registration_date) VALUES (?, ?, NOW())";
            $result = insert($sql, [$eventId, $currentUser['user_id']]);
            
            if ($result) {
                $successMessage = "Successfully registered for the event!";
                $isRegistered = true;
                $attendeesCount++;
            } else {
                $errorMessage = "Error registering for event. Please try again.";
            }
        }
    } elseif ($_POST['action'] === 'unregister' && $isRegistered) {
        $sql = "DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?";
        $result = delete($sql, [$eventId, $currentUser['user_id']]);
        
        if ($result) {
            $successMessage = "Successfully unregistered from the event.";
            $isRegistered = false;
            $attendeesCount--;
        } else {
            $errorMessage = "Error unregistering from event. Please try again.";
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Event Detail Header -->
<div class="event-detail-header animate__animated animate__fadeInDown">
    <div class="event-detail-header-content">
        <div class="event-detail-header-main">
            <h1 class="event-detail-title">
                <i class="fas fa-calendar-check me-3"></i>
                Event Details
            </h1>
            <p class="event-detail-description">View comprehensive event information</p>
        </div>
        <div class="event-detail-header-actions">
            <a href="events.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Events
            </a>
            <?php if ($canManageEvents && ($isAdmin || $event['organizer_id'] == $currentUser['user_id'])): ?>
            <a href="event-edit.php?id=<?php echo $eventId; ?>" class="btn btn-header-action">
                <i class="fas fa-edit me-2"></i>Edit Event
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.event-detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.event-detail-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.event-detail-header-main {
    flex: 1;
    text-align: center;
}

.event-detail-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.event-detail-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.event-detail-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.event-detail-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
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
    .event-detail-header {
        padding: 2rem 1.5rem;
    }

    .event-detail-header-content {
        flex-direction: column;
        align-items: center;
    }

    .event-detail-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .event-detail-title i {
        font-size: 1.8rem;
    }

    .event-detail-description {
        font-size: 1.1rem;
    }

    .event-detail-header-actions {
        width: 100%;
        justify-content: center;
    }

    .btn-header-action {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
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

/* Mobile Full-Width Optimization for Event Detail Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid, .container {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure event detail header has border-radius on mobile */
    .header, .event-detail-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card, .content-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

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

<!-- Event Details Content -->
<div class="row">
    <div class="col-lg-8">
        <!-- Event Main Information Card -->
        <div class="card event-info-card mb-4">
            <!-- Event Image -->
            <?php if (!empty($event['image_path'])): ?>
            <div class="event-image-container">
                <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                     class="card-img-top event-image" 
                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                     data-bs-toggle="modal" 
                     data-bs-target="#imageViewModal"
                     style="cursor: pointer;"
                     title="Click to view full size">
                <div class="event-status-overlay">
                    <span class="badge bg-<?php 
                        echo $event['status'] === 'upcoming' ? 'success' : 
                            ($event['status'] === 'planning' ? 'warning' : 
                            ($event['status'] === 'ongoing' ? 'primary' :
                            ($event['status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                    ?>">
                        <?php echo strtoupper(htmlspecialchars($event['status'])); ?>
                    </span>
                </div>
                <div class="image-hover-overlay">
                    <i class="fas fa-search-plus"></i>
                    <span>Click to view full size</span>
                </div>
                <div class="image-actions-overlay">
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#imageViewModal" title="View Full Size">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                    <a href="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                       download="<?php echo htmlspecialchars($event['title']); ?>-image.jpg" 
                       class="btn btn-sm btn-light" 
                       title="Download Image">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card-body">
                <h2 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                
                <!-- Event Meta Information -->
                <div class="event-meta">
                    <div class="event-meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>
                            <?php 
                            echo date('l, F j, Y', strtotime($event['date']));
                            if (!empty($event['end_date']) && $event['end_date'] !== $event['date']) {
                                echo ' - ' . date('F j, Y', strtotime($event['end_date']));
                            }
                            ?>
                        </span>
                    </div>
                    <div class="event-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                    </div>
                    <?php if (!empty($portfolio)): ?>
                    <div class="event-meta-item">
                        <i class="fas fa-briefcase"></i>
                        <span><?php echo htmlspecialchars($portfolio); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="event-meta-item">
                        <i class="fas fa-user"></i>
                        <span>Organized by: <?php echo htmlspecialchars($organizerName); ?></span>
                    </div>
                    <?php if ($event['capacity'] > 0): ?>
                    <div class="event-meta-item">
                        <i class="fas fa-users"></i>
                        <span>
                            Capacity: <?php echo $attendeesCount; ?>/<?php echo $event['capacity']; ?>
                            <?php if ($attendeesCount >= $event['capacity']): ?>
                                <span class="badge bg-danger ms-2">FULL</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="event-meta-item">
                        <i class="fas fa-users"></i>
                        <span>Attendees: <?php echo $attendeesCount; ?> (Unlimited capacity)</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <hr class="my-4">
                
                <!-- Event Description -->
                <div class="event-description">
                    <h4 class="mb-3"><i class="fas fa-info-circle me-2"></i>Event Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($eventDescription)); ?></p>
                </div>
                
                <!-- Event Document -->
                <?php if (!empty($event['document_path'])): ?>
                <div class="event-document mt-4">
                    <h5><i class="fas fa-file-alt me-2"></i>Event Document</h5>
                    <a href="../<?php echo htmlspecialchars($event['document_path']); ?>" 
                       target="_blank" 
                       class="btn btn-outline-primary mt-2">
                        <i class="fas fa-download me-2"></i>Download Document
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Registration Card -->
        <?php if ($event['status'] === 'upcoming' || $event['status'] === 'planning'): ?>
        <div class="card registration-card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Event Registration</h5>
            </div>
            <div class="card-body">
                <?php if ($isRegistered): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>You are registered for this event!
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="unregister">
                        <button type="submit" class="btn btn-danger btn-block w-100">
                            <i class="fas fa-times-circle me-2"></i>Unregister
                        </button>
                    </form>
                <?php else: ?>
                    <?php if ($event['capacity'] > 0 && $attendeesCount >= $event['capacity']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This event is at full capacity.
                        </div>
                    <?php else: ?>
                        <p>Register to attend this event</p>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="register">
                            <button type="submit" class="btn btn-primary btn-block w-100">
                                <i class="fas fa-check-circle me-2"></i>Register Now
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Event Statistics Card -->
        <div class="card stats-card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Event Statistics</h5>
            </div>
            <div class="card-body">
                <div class="stat-item">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Total Registrations</h6>
                        <h3><?php echo $attendeesCount; ?></h3>
                    </div>
                </div>
                
                <hr>
                
                <div class="stat-item">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Created On</h6>
                        <p><?php echo date('M j, Y', strtotime($event['created_at'])); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="stat-item">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h6>Last Updated</h6>
                        <p><?php echo date('M j, Y', strtotime($event['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <?php if ($canManageEvents && ($isAdmin || $event['organizer_id'] == $currentUser['user_id'])): ?>
        <div class="card quick-actions-card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="event-edit.php?id=<?php echo $eventId; ?>" class="btn btn-primary btn-block w-100 mb-2">
                    <i class="fas fa-edit me-2"></i>Edit Event
                </a>
                <a href="event_attendees.php?event_id=<?php echo $eventId; ?>" class="btn btn-info btn-block w-100 mb-2">
                    <i class="fas fa-users me-2"></i>View Attendees
                </a>
                <a href="events.php?action=delete&id=<?php echo $eventId; ?>" 
                   class="btn btn-danger btn-block w-100"
                   onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.');">
                    <i class="fas fa-trash me-2"></i>Delete Event
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Image View Modal -->
<?php if (!empty($event['image_path'])): ?>
<div class="modal fade" id="imageViewModal" tabindex="-1" aria-labelledby="imageViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="imageViewModalLabel">
                    <i class="fas fa-image me-2"></i><?php echo htmlspecialchars($event['title']); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                     class="img-fluid" 
                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                     style="max-height: 80vh; width: auto;">
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <a href="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                   download="<?php echo htmlspecialchars($event['title']); ?>-image.jpg" 
                   class="btn btn-light">
                    <i class="fas fa-download me-2"></i>Download Image
                </a>
                <a href="../<?php echo htmlspecialchars($event['image_path']); ?>" 
                   target="_blank" 
                   class="btn btn-light">
                    <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Event Info Card Styles */
.event-info-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.event-image-container {
    position: relative;
    width: 100%;
    height: 400px;
    overflow: hidden;
}

.event-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-status-overlay {
    position: absolute;
    top: 20px;
    right: 20px;
}

.event-status-overlay .badge {
    font-size: 1rem;
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Image Hover Overlay */
.image-hover-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.event-image-container:hover .image-hover-overlay {
    opacity: 1;
}

.image-hover-overlay i {
    font-size: 3rem;
    color: white;
    margin-bottom: 1rem;
}

.image-hover-overlay span {
    color: white;
    font-size: 1.1rem;
    font-weight: 500;
}

/* Image Action Buttons */
.image-actions-overlay {
    position: absolute;
    bottom: 20px;
    left: 20px;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.event-image-container:hover .image-actions-overlay {
    opacity: 1;
}

.image-actions-overlay .btn {
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.image-actions-overlay .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.event-image:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.event-title {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1.5rem;
}

.event-meta {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.event-meta-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1rem;
    color: #555;
}

.event-meta-item i {
    color: #667eea;
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.event-description {
    font-size: 1rem;
    line-height: 1.8;
    color: #444;
}

.event-description h4 {
    font-weight: 600;
    color: #333;
}

/* Registration Card Styles */
.registration-card,
.stats-card,
.quick-actions-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.registration-card .card-header,
.stats-card .card-header,
.quick-actions-card .card-header {
    border-bottom: none;
    padding: 1rem 1.25rem;
}

/* Stats Card Styles */
.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-details h6 {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.stat-details h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.stat-details p {
    margin: 0;
    color: #444;
}

/* Responsive Design */
@media (max-width: 992px) {
    .event-image-container {
        height: 300px;
    }
    
    .event-title {
        font-size: 1.75rem;
    }
}

@media (max-width: 768px) {
    .event-image-container {
        height: 250px;
    }
    
    .event-title {
        font-size: 1.5rem;
    }
    
    .event-meta-item {
        font-size: 0.9rem;
    }
    
    .event-status-overlay {
        top: 10px;
        right: 10px;
    }
    
    .event-status-overlay .badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .stat-details h3 {
        font-size: 1.5rem;
    }
    
    /* Mobile adjustments for image overlays */
    .image-hover-overlay i {
        font-size: 2rem;
    }
    
    .image-hover-overlay span {
        font-size: 0.9rem;
    }
    
    .image-actions-overlay {
        bottom: 10px;
        left: 10px;
    }
    
    .image-actions-overlay .btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
    }
}

@media (max-width: 576px) {
    .event-image-container {
        height: 200px;
    }
    
    .event-title {
        font-size: 1.25rem;
    }
    
    .event-meta-item {
        font-size: 0.85rem;
        gap: 0.75rem;
    }
    
    .event-meta-item i {
        font-size: 1rem;
    }
}

/* Touch Device Support - Always show action buttons on mobile */
@media (max-width: 992px) and (hover: none) {
    .image-actions-overlay {
        opacity: 1;
    }
    
    .image-hover-overlay {
        display: none;
    }
}

/* Print Styles */
@media print {
    .event-detail-header,
    .event-detail-header-actions,
    .registration-card,
    .quick-actions-card,
    .btn,
    .alert {
        display: none !important;
    }
    
    .event-info-card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
