<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php'; // Include the functions file directly

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
$isAdmin = shouldUseAdminInterface(); // Use unified admin interface check for super admin users
$isMember = isMember();
$canManageEvents = $isAdmin || $isMember; // Allow both admins and members to manage events

// Check if user has permission to edit events
if (!$canManageEvents) {
    header("Location: events.php?error=unauthorized");
    exit();
}

// Set page title
$pageTitle = "Edit Event - SRC Management System";

// Get event ID from URL
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Fetch event details
$event = fetchOne("SELECT * FROM events WHERE event_id = ?", [$eventId]);

// Check if event exists
if (!$event) {
    header("Location: events.php?error=not_found");
    exit();
}

// Check if user has permission to edit this specific event
// Admins can edit any event, members can only edit events they organized
if (!$isAdmin && $isMember && $event['organizer_id'] != $currentUser['user_id']) {
    header("Location: events.php?error=unauthorized");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_event'])) {
    // Get form data
    $eventName = trim($_POST['event_name']);
    $eventDate = $_POST['event_date'];
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
    $imagePath = $event['image_path']; // Default to existing image path
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['event_image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid image format. Allowed formats: JPG, PNG, GIF, WEBP";
        } else {
            $uploadDir = '../uploads/events/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'event_' . time() . '_' . basename($_FILES['event_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $targetFile)) {
                // If we're replacing an existing image, delete the old one
                if (!empty($event['image_path']) && file_exists('../' . $event['image_path'])) {
                    unlink('../' . $event['image_path']);
                }
                
                $imagePath = 'uploads/events/' . $fileName;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // Handle document upload if provided
    $documentPath = $event['document_path']; // Default to existing document path
    if (isset($_FILES['event_document']) && $_FILES['event_document']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $fileType = $_FILES['event_document']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid document format. Allowed formats: PDF, DOC, DOCX";
        } else {
            $uploadDir = '../uploads/events/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'doc_' . time() . '_' . basename($_FILES['event_document']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['event_document']['tmp_name'], $targetFile)) {
                // If we're replacing an existing document, delete the old one
                if (!empty($event['document_path']) && file_exists('../' . $event['document_path'])) {
                    unlink('../' . $event['document_path']);
                }
                
                $documentPath = 'uploads/events/' . $fileName;
            } else {
                $errors[] = "Failed to upload document. Please try again.";
            }
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        // Ensure organizer_id is a valid user ID that exists in the users table
        $organizerId = is_numeric($organizer) && intval($organizer) > 0 ? intval($organizer) : $currentUser['user_id'];
        
        // Add portfolio info to description if provided
        if (!empty($portfolio)) {
            // Remove existing portfolio info if present
            $cleanDescription = preg_replace('/\n\nPortfolio: [^\n]+/', '', $description);
            $updatedDescription = $cleanDescription . "\n\nPortfolio: " . $portfolio;
        } else {
            $updatedDescription = $description;
        }
        
        $sql = "UPDATE events SET 
                title = ?, 
                date = ?, 
                location = ?, 
                status = ?, 
                description = ?, 
                organizer_id = ?, 
                capacity = ?,
                image_path = ?,
                document_path = ?
                WHERE event_id = ?";
        
        $result = update($sql, [
            $eventName, 
            $eventDate, 
            $location, 
            $status, 
            $updatedDescription, 
            $organizerId,
            $capacity,
            $imagePath,
            $documentPath,
            $eventId
        ]);
        
        if ($result) {
            $successMessage = "Event updated successfully!";
            // Refresh event data
            $event = fetchOne("SELECT * FROM events WHERE event_id = ?", [$eventId]);
        } else {
            $errorMessage = "Error updating event. Please try again.";
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}

// Include header
require_once 'includes/header.php';
?>


<!-- Custom Event Edit Header -->
<div class="event-edit-header animate__animated animate__fadeInDown">
    <div class="event-edit-header-content">
        <div class="event-edit-header-main">
            <h1 class="event-edit-title">
                <i class="fas fa-edit me-3"></i>
                Edit Event
            </h1>
            <p class="event-edit-description">Modify event details and information</p>
        </div>
        <div class="event-edit-header-actions">
            <a href="event-detail.php?id=<?php echo $eventId; ?>" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Event
            </a>
            <a href="events.php" class="btn btn-header-action">
                <i class="fas fa-calendar-alt me-2"></i>All Events
            </a>
        </div>
    </div>
</div>

<style>
.event-edit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.event-edit-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.event-edit-header-main {
    flex: 1;
    text-align: center;
}

.event-edit-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.event-edit-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.event-edit-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.event-edit-header-actions {
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
    .event-edit-header {
        padding: 2rem 1.5rem;
    }

    .event-edit-header-content {
        flex-direction: column;
        align-items: center;
    }

    .event-edit-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .event-edit-title i {
        font-size: 1.8rem;
    }

    .event-edit-description {
        font-size: 1.1rem;
    }

    .event-edit-header-actions {
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

<!-- Edit Event Form -->
<div class="content-card">
    <div class="content-card-header">
        <h3 class="content-card-title">Edit Event: <?php echo htmlspecialchars($event['title']); ?></h3>
    </div>
    <div class="content-card-body">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $eventId; ?>" enctype="multipart/form-data" style="padding: 0 15px;">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="event_name" class="form-label">Event Name</label>
                        <input type="text" class="form-control" id="event_name" name="event_name" required
                               value="<?php echo htmlspecialchars($event['title']); ?>"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="event_status" class="form-label">Status</label>
                        <select class="form-select" id="event_status" name="event_status" required
                                style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                            <option value="planning" <?php echo $event['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                            <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date</label>
                        <input type="date" class="form-control" id="event_date" name="event_date" required
                               value="<?php echo $event['date']; ?>"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="event_location" name="event_location" required
                               value="<?php echo htmlspecialchars($event['location']); ?>"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_organizer" class="form-label">Organizer (User ID)</label>
                        <input type="number" class="form-control" id="event_organizer" name="event_organizer" min="1"
                               value="<?php echo htmlspecialchars($event['organizer_id']); ?>"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                        <small class="form-text text-muted">Enter a valid User ID (e.g., 1 for admin)</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_capacity" class="form-label">Capacity</label>
                        <input type="number" class="form-control" id="event_capacity" name="event_capacity" min="0"
                               value="<?php echo $event['capacity']; ?>"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                        <small class="form-text text-muted">Maximum number of attendees (0 for unlimited)</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="mb-3">
                        <label for="event_portfolio" class="form-label">Portfolio</label>
                        <select class="form-select" id="event_portfolio" name="event_portfolio"
                                style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                            <option value="">Select Portfolio</option>
                            <?php 
                            $portfolioList = getAllPortfolios();
                            
                            // Extract current portfolio from description if it exists
                            $currentPortfolio = '';
                            if (preg_match('/Portfolio: ([^\n]+)/', $event['description'], $matches)) {
                                $currentPortfolio = trim($matches[1]);
                            }
                            
                            foreach ($portfolioList as $portfolio): 
                            ?>
                            <option value="<?php echo htmlspecialchars($portfolio); ?>" <?php echo ($portfolio === $currentPortfolio) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($portfolio); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select the SRC portfolio responsible for this event</small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="event_description" class="form-label">Description</label>
                <textarea class="form-control" id="event_description" name="event_description" rows="4"
                          style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;"><?php 
                    // Remove portfolio info from displayed description
                    echo htmlspecialchars(preg_replace('/\n\nPortfolio: [^\n]+/', '', $event['description'])); 
                ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_image" class="form-label">Event Image</label>
                        <input type="file" class="form-control" id="event_image" name="event_image" accept="image/jpeg,image/png,image/gif,image/webp"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                        <small class="form-text text-muted">Upload a new image (JPG, PNG, GIF, WEBP)</small>
                        
                        <?php if (!empty($event['image_path'])): ?>
                        <div class="mt-2">
                            <p class="mb-1">Current image:</p>
                            <img src="<?php echo '../' . htmlspecialchars($event['image_path']); ?>" alt="Current Event Image" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="event_document" class="form-label">Event Document</label>
                        <input type="file" class="form-control" id="event_document" name="event_document" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               style="padding: 8px 12px; margin-bottom: 10px; width: 100%; box-sizing: border-box;">
                        <small class="form-text text-muted">Upload a new document (PDF, DOC, DOCX)</small>
                        
                        <?php if (!empty($event['document_path'])): ?>
                        <div class="mt-2">
                            <p class="mb-1">Current document:</p>
                            <a href="<?php echo '../' . htmlspecialchars($event['document_path']); ?>" target="_blank" class="btn btn-sm btn-info">
                                <i class="fas fa-file me-1"></i> <?php echo basename($event['document_path']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted">Event Statistics</label>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Registrations</h6>
                                <p class="card-text fs-4">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Created On</h6>
                                <p class="card-text"><?php echo date('M j, Y', strtotime($event['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Last Updated</h6>
                                <p class="card-text"><?php echo date('M j, Y', strtotime($event['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="event-detail.php?id=<?php echo $eventId; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-2"></i> View Event
                </a>
                <div>
                    <a href="events.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" name="update_event" class="btn btn-primary">Update Event</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 
