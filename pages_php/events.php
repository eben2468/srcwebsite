<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Include auto notifications system
require_once __DIR__ . '/includes/auto_notifications.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php'; // Include the functions file directly
require_once __DIR__ . '/../includes/settings_functions.php';

// Define the function locally in case it's not found from the include
if (!function_exists('getAllPortfolios')) {
    function getAllPortfolios() {
        global $conn;
        
        $portfolios = [];
        
        // Try to get portfolios from the database
        try {
            $portfoliosSql = "SELECT DISTINCT portfolio FROM reports ORDER BY portfolio";
            $result = mysqli_query($conn, $portfoliosSql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $portfolios[] = $row['portfolio'];
                }
            }
        } catch (Exception $e) {
            // Just handle silently and use default list
        }
        
        // If no portfolios found in database, use a standard list
        if (empty($portfolios)) {
            $portfolios = [
                'President',
                'Secretary General',
                'Treasurer',
                'Academic Affairs',
                'Sports & Culture',
                'Student Welfare',
                'International Students',
                'General'
            ];
        }
        
        // Sort the portfolios
        sort($portfolios);
        
        return $portfolios;
    }
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if events feature is enabled
if (!hasFeaturePermission('enable_events')) {
    $_SESSION['error'] = "The events feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = shouldUseAdminInterface(); // Use unified admin interface check for super admin users
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember; // Allow both admins and members to manage events

// Check if user is trying to access new event form and is not authorized
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$canManageEvents) {
    // Redirect non-privileged users back to the events page
    header("Location: events.php");
    exit();
}

// Set page title and body class
$pageTitle = "Events - SRC Management System";
$bodyClass = "page-events"; // Add body class for CSS targeting

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Handle Create Event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['event_name'])) {
    // Check if user has permission to create events
    if ($canManageEvents) {
        // Get form data
        $eventName = trim($_POST['event_name']);
        $eventDate = $_POST['event_date'];
        $endDate = $_POST['end_date'];
        $location = trim($_POST['event_location']);
        $status = $_POST['event_status'];
        $description = trim($_POST['event_description']);
        $organizer = trim($_POST['event_organizer']);
        $capacity = intval($_POST['event_capacity']);
        $portfolio = isset($_POST['event_portfolio']) ? trim($_POST['event_portfolio']) : '';
        
        // Validate required fields
        $errors = [];
        
        if (empty($eventName)) {
            $errors[] = "Event name is required";
        }
        
        if (empty($eventDate)) {
            $errors[] = "Event date is required";
        }
        
        if (empty($location)) {
            $errors[] = "Location is required";
        }
        
        // Handle image upload if provided
        $imagePath = null;
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = $_FILES['event_image']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP";
            } else {
                $uploadDir = '../uploads/events/';
                $fileName = 'event_' . time() . '_' . basename($_FILES['event_image']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetFile)) {
                    $imagePath = 'uploads/events/' . $fileName;
                } else {
                    $errors[] = "Failed to upload image. Please try again.";
                }
            }
        }
        
        // Handle document upload if provided
        $documentPath = null;
        if (isset($_FILES['event_document']) && $_FILES['event_document']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $fileType = $_FILES['event_document']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid document format. Allowed formats: PDF, DOC, DOCX";
            } else {
                $uploadDir = '../uploads/events/';
                $fileName = 'doc_' . time() . '_' . basename($_FILES['event_document']['name']);
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['event_document']['tmp_name'], $targetFile)) {
                    $documentPath = 'uploads/events/' . $fileName;
                } else {
                    $errors[] = "Failed to upload document. Please try again.";
                }
            }
        }
        
        // If no errors, insert into database
        if (empty($errors)) {
            // Use current user's ID as organizer_id
            $organizerId = isset($currentUser['user_id']) ? $currentUser['user_id'] : 1;
            
            // Prepare SQL query to include portfolio info
            if (!empty($portfolio)) {
                // Look up the department ID by name (if using a departments table)
                // For now, we'll store the portfolio name in a variable
                $portfolioInfo = $portfolio;
                
                // You might want to store this as metadata or in another related table
                // For now, we'll add it to the query
                $sql = "INSERT INTO events (title, date, end_date, location, status, description, image_path, document_path, organizer_id, capacity, department_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = insert($sql, [
                    $eventName, 
                    $eventDate, 
                    $endDate, 
                    $location, 
                    $status, 
                    $description,
                    $imagePath,
                    $documentPath,
                    $organizerId,
                    $capacity,
                    NULL  // We'll use NULL for department_id for now, you can replace this with a real ID if needed
                ]);
                
                if ($result) {
                    // Get the inserted event ID
                    $eventId = mysqli_insert_id($conn);

                    // Store portfolio info in event metadata table if you have one
                    // For now, we'll just update the existing record with the portfolio info in the description
                    $updatedDescription = $description . "\n\nPortfolio: " . $portfolioInfo;
                    $updateSql = "UPDATE events SET description = ? WHERE event_id = ?";
                    update($updateSql, [$updatedDescription, $eventId]);

                    $successMessage = "Event created successfully!";

                    // Send notification to all users about new event
                    autoNotifyEventCreated($eventName, $description, $currentUser['user_id'], $eventId);
                } else {
                    $errorMessage = "Error creating event. Please try again.";
                }
            } else {
                // If no portfolio is selected, proceed with the original query
                $sql = "INSERT INTO events (title, date, end_date, location, status, description, image_path, document_path, organizer_id, capacity, department_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";
                
                $result = insert($sql, [
                    $eventName, 
                    $eventDate, 
                    $endDate, 
                    $location, 
                    $status, 
                    $description,
                    $imagePath,
                    $documentPath,
                    $organizerId,
                    $capacity
                ]);
                
                if ($result) {
                    $successMessage = "Event created successfully!";

                    // Send notification to all users about new event
                    $eventId = mysqli_insert_id($conn);
                    autoNotifyEventCreated($eventName, $description, $currentUser['user_id'], $eventId);
                } else {
                    $errorMessage = "Error creating event. Please try again.";
                }
            }
        } else {
            $errorMessage = implode("<br>", $errors);
        }
    } else {
        $errorMessage = "You don't have permission to create events.";
    }
}

// Handle Delete Event
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $eventId = intval($_GET['id']);
    
    if ($canManageEvents) {
        // Check if event exists
        $checkEvent = fetchOne("SELECT event_id, organizer_id FROM events WHERE event_id = ?", [$eventId]);
        
        if ($checkEvent) {
            // If user is admin or the event organizer, allow deletion
            if ($isAdmin || $checkEvent['organizer_id'] == $currentUser['user_id']) {
                $result = delete("DELETE FROM events WHERE event_id = ?", [$eventId]);
                
                if ($result) {
                    $successMessage = "Event deleted successfully!";
                } else {
                    $errorMessage = "Error deleting event. Please try again.";
                }
            } else {
                $errorMessage = "You can only delete events that you organized.";
            }
        } else {
            $errorMessage = "Event not found.";
        }
    } else {
        $errorMessage = "You don't have permission to delete events.";
    }
}

// Build query with filters
$whereConditions = [];
$params = [];
$types = '';

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $whereConditions[] = "(title LIKE ? OR location LIKE ? OR description LIKE ? OR organizer_id LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
    $types .= 'ssss';
}

// Status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $whereConditions[] = "status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Month filter
if (isset($_GET['month']) && !empty($_GET['month'])) {
    // Format: YYYY-MM
    $month = $_GET['month'];
    $whereConditions[] = "(DATE_FORMAT(date, '%Y-%m') = ?)";
    $params = array_merge($params, [$month]);
    $types .= 's';
}

// Build the complete query
$sql = "SELECT * FROM events";

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " ORDER BY date DESC";

// Fetch events from database
$events = fetchAll($sql, $params, $types);

// Include header
require_once 'includes/header.php';

// Add direct fixing script immediately after include header
if (isset($createEventModal) && $createEventModal === true) {
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Fix event modal positioning
        var eventModal = document.getElementById("createEventModal");
        if (eventModal) {
            eventModal.setAttribute("data-bs-backdrop", "false");
            
            // Adjust the modal position below the header
            var header = document.querySelector(".navbar");
            var headerHeight = header ? header.offsetHeight : 60;
            var modalDialog = eventModal.querySelector(".modal-dialog");
            if (modalDialog) {
                modalDialog.style.marginTop = (headerHeight + 10) + "px";
            }
        }
    });
    </script>';
}
?>

<script>
    document.body.classList.add('events-page');
</script>

<!-- Custom Events Header -->
<div class="events-header animate__animated animate__fadeInDown">
    <div class="events-header-content">
        <div class="events-header-main">
            <h1 class="events-title">
                <i class="fas fa-calendar-alt me-3"></i>
                Events
            </h1>
            <p class="events-description">Manage and organize university events and activities</p>
        </div>
        <?php if ($canManageEvents): ?>
        <div class="events-header-actions">
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#createEventModal">
                <i class="fas fa-plus me-2"></i>Create Event
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.events-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.events-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.events-header-main {
    flex: 1;
    text-align: center;
}

.events-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.events-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.events-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.events-header-actions {
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
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .events-header {
        padding: 2rem 1.5rem;
    }

    .events-header-content {
        flex-direction: column;
        align-items: center;
    }

    .events-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .events-title i {
        font-size: 1.8rem;
    }

    .events-description {
        font-size: 1.1rem;
    }

    .events-header-actions {
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

<h3 class="mb-3">All Events</h3>

<!-- All Events Table -->
<div class="event-grid">
    <?php if (empty($events)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No events found.
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="card event-card">
                <?php if (!empty($event['image_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($event['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>">
                <?php else: ?>
                    <img src="../assets/images/default_event.jpg" class="card-img-top" alt="Default Event Image">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                    <p class="card-text">
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M j, Y', strtotime($event['date'])); ?>
                        </small>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($event['location']); ?>
                        </small>
                    </p>
                    <span class="badge bg-<?php 
                        echo $event['status'] === 'upcoming' ? 'success' : 
                            ($event['status'] === 'planning' ? 'warning' : 
                            ($event['status'] === 'ongoing' ? 'primary' :
                            ($event['status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                    ?>">
                        <?php echo strtoupper(htmlspecialchars($event['status'])); ?>
                    </span>
                </div>
                <div class="card-footer">
                    <a href="event-detail.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <?php if ($canManageEvents): ?>
                    <a href="event-edit.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="events.php?action=delete&id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this event?');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.event-card {
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.event-card .card-img-top {
    height: 180px;
    object-fit: cover;
}

.event-card .card-body {
    padding: 1.25rem;
}

.event-card .card-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.event-card .card-text {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.event-card .card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    padding: 0.75rem 1.25rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Mobile navbar size increase and spacing adjustments for events page */
@media (max-width: 768px) {
    .events-page .navbar {
        height: 70px !important;
        padding: 0.75rem 1rem !important;
    }
    
    .events-page .navbar .navbar-brand {
        font-size: 1.3rem !important;
    }
    
    .events-page .navbar .system-icon {
        width: 35px !important;
        height: 35px !important;
    }
    
    .events-page .navbar .btn {
        font-size: 1.1rem !important;
        padding: 0.5rem 0.75rem !important;
    }
    
    .events-page .navbar .site-name {
        font-size: 1.1rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .events-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .events-page .header {
        margin-top: 100px !important; /* 70px navbar + 30px spacing */
    }
}

@media (max-width: 480px) {
    .events-page .navbar {
        height: 65px !important;
        padding: 0.6rem 0.8rem !important;
    }
    
    .events-page .navbar .navbar-brand {
        font-size: 1.2rem !important;
    }
    
    .events-page .navbar .system-icon {
        width: 32px !important;
        height: 32px !important;
    }
    
    .events-page .navbar .btn {
        font-size: 1rem !important;
        padding: 0.4rem 0.6rem !important;
    }
    
    .events-page .navbar .site-name {
        font-size: 1rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .events-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .events-page .header {
        margin-top: 25px !important; /* 65px navbar + 30px spacing */
    }
}

@media (max-width: 375px) {
    .events-page .navbar {
        height: 60px !important;
        padding: 0.5rem 0.7rem !important;
    }
    
    .events-page .navbar .navbar-brand {
        font-size: 1.1rem !important;
    }
    
    .events-page .navbar .system-icon {
        width: 30px !important;
        height: 30px !important;
    }
    
    .events-page .navbar .btn {
        font-size: 0.95rem !important;
        padding: 0.35rem 0.5rem !important;
    }
    
    .events-page .navbar .site-name {
        font-size: 0.95rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .events-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .events-page .header {
        margin-top: 30px !important; /* 60px navbar + 30px spacing */
    }
}
</style>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="event_name" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_status" class="form-label">Status</label>
                                <select class="form-select" id="event_status" name="event_status" required>
                                    <option value="planning">Planning</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="event_date" name="event_date" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                                <small class="form-text text-muted">For multi-day events</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="event_location" name="event_location" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_organizer" class="form-label">Organizer (User ID)</label>
                                <input type="number" class="form-control" id="event_organizer" name="event_organizer" min="1" value="1">
                                <small class="form-text text-muted">Enter a valid User ID (e.g., 1 for admin)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="event_capacity" name="event_capacity" min="0" value="0">
                                <small class="form-text text-muted">Maximum number of attendees (0 for unlimited)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="event_portfolio" class="form-label">Portfolio</label>
                                <select class="form-select" id="event_portfolio" name="event_portfolio">
                                    <option value="">Select Portfolio</option>
                                    <?php 
                                    $portfolioList = getAllPortfolios();
                                    foreach ($portfolioList as $portfolio): 
                                    ?>
                                    <option value="<?php echo htmlspecialchars($portfolio); ?>">
                                        <?php echo htmlspecialchars($portfolio); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="event_description" class="form-label">Description</label>
                        <textarea class="form-control" id="event_description" name="event_description" rows="4"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_image" class="form-label">Event Image</label>
                                <input type="file" class="form-control" id="event_image" name="event_image">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_document" class="form-label">Event Document</label>
                                <input type="file" class="form-control" id="event_document" name="event_document">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadDocumentModalLabel">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="document_handler.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="mb-3">
                        <label for="document_title" class="form-label">Document Title</label>
                        <input type="text" class="form-control" id="document_title" name="document_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="document_category" class="form-label">Category</label>
                        <select class="form-select" id="document_category" name="document_category" required>
                            <option value="">Select Category</option>
                            <option value="legal">Legal</option>
                            <option value="general">General</option>
                            <option value="financial">Financial</option>
                            <option value="elections">Elections</option>
                            <option value="events">Events</option>
                            <option value="reports">Reports</option>
                            <option value="minutes">Minutes</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document_file" class="form-label">File</label>
                        <input type="file" class="form-control" id="document_file" name="document_file" required>
                        <div class="form-text">Accepted file types: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT (Max size: 5MB)</div>
                    </div>
                    <div class="mb-3">
                        <label for="document_description" class="form-label">Description</label>
                        <textarea class="form-control" id="document_description" name="document_description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

</div> <!-- Close main content container -->

<?php require_once 'includes/footer.php'; ?> 
