<?php
// Include authentication file and database connection
require_once '../auth_functions.php';
require_once '../db_config.php';
require_once '../functions.php'; // Include the functions file directly

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

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember; // Allow both admins and members to manage events

// Check if user is trying to access new event form and is not authorized
if (isset($_GET['action']) && $_GET['action'] === 'new' && !$canManageEvents) {
    // Redirect non-privileged users back to the events page
    header("Location: events.php");
    exit();
}

// Set page title
$pageTitle = "Events - SRC Management System";

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
?>

<script>
    document.body.classList.add('events-page');
</script>

<div class="header">
    <h1 class="page-title">Events</h1>
    
    <div class="header-actions">
        <?php if ($canManageEvents): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
            <i class="fas fa-plus me-2"></i> Create Event
        </button>
        <?php endif; ?>
    </div>
</div>

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
<div class="content-card">
    <div class="content-card-body">
        <?php if (empty($events)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> No events found.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>EVENT NAME</th>
                        <th>DATE</th>
                        <th>LOCATION</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td>
                            <?php echo date('M j, Y', strtotime($event['date'])); ?>
                        </td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $event['status'] === 'upcoming' ? 'success' : 
                                    ($event['status'] === 'planning' ? 'warning' : 
                                    ($event['status'] === 'ongoing' ? 'primary' :
                                    ($event['status'] === 'cancelled' ? 'danger' : 'secondary'))); 
                            ?>">
                                <?php echo strtoupper(htmlspecialchars($event['status'])); ?>
                            </span>
                        </td>
                        <td>
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
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

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

<?php require_once 'includes/footer.php'; ?> 