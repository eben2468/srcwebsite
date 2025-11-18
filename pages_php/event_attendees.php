<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/settings_functions.php';

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
    $_SESSION['error'] = "You don't have permission to manage event attendees.";
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
    $_SESSION['error'] = "You don't have permission to manage attendees for this event.";
    header("Location: event-detail.php?id=" . $eventId);
    exit();
}

// Initialize messages
$successMessage = '';
$errorMessage = '';

// Create event_attendees table if it doesn't exist
try {
    $createTableSql = "CREATE TABLE IF NOT EXISTS event_attendees (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        event_id INT(11) NOT NULL,
        user_id INT(11) DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('registered', 'attended', 'cancelled', 'no_show') DEFAULT 'registered',
        notes TEXT,
        FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($createTableSql);
} catch (Exception $e) {
    $errorMessage = "Error setting up attendee tracking: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new attendee
    if (isset($_POST['add_attendee'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone'] ?? '');
        $userId = !empty($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $status = $_POST['status'] ?? 'registered';
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate required fields
        if (empty($name) || empty($email)) {
            $errorMessage = "Name and email are required.";
        } else {
            // Check if email already registered for this event
            $existingAttendee = fetchOne(
                "SELECT * FROM event_attendees WHERE event_id = ? AND email = ?", 
                [$eventId, $email]
            );
            
            if ($existingAttendee) {
                $_SESSION['error'] = "This email is already registered for this event.";
                header("Location: event_attendees.php?event_id=" . $eventId);
                exit();
            } else {
                // Insert new attendee
                try {
                    $insertSql = "INSERT INTO event_attendees (event_id, user_id, name, email, phone, status, notes)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $result = insert($insertSql, [$eventId, $userId, $name, $email, $phone, $status, $notes]);

                    if ($result !== false && $result > 0) {
                        $_SESSION['success'] = "Attendee added successfully.";
                    } else {
                        $_SESSION['error'] = "Error adding attendee. Please try again.";
                    }
                } catch (Exception $e) {
                    $_SESSION['error'] = "Database error: " . $e->getMessage();
                }

                // Redirect to prevent form resubmission
                header("Location: event_attendees.php?event_id=" . $eventId);
                exit();
            }
        }
    }
    
    // Update attendee status
    if (isset($_POST['update_status'])) {
        $attendeeId = intval($_POST['attendee_id']);
        $newStatus = $_POST['new_status'];

        // Validate status
        $validStatuses = ['registered', 'attended', 'cancelled', 'no_show'];
        if (!in_array($newStatus, $validStatuses)) {
            $_SESSION['error'] = "Invalid status provided.";
            header("Location: event_attendees.php?event_id=" . $eventId);
            exit();
        }

        try {
            $updateSql = "UPDATE event_attendees SET status = ? WHERE id = ? AND event_id = ?";
            $result = update($updateSql, [$newStatus, $attendeeId, $eventId]);

            if ($result !== false && $result >= 0) {
                $_SESSION['success'] = "Attendee status updated successfully to " . ucfirst($newStatus) . ".";
            } else {
                $_SESSION['error'] = "Error updating attendee status. Please try again.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        // Redirect to prevent form resubmission
        header("Location: event_attendees.php?event_id=" . $eventId);
        exit();
    }
    
    // Delete attendee
    if (isset($_POST['delete_attendee'])) {
        $attendeeId = intval($_POST['attendee_id']);

        try {
            $deleteSql = "DELETE FROM event_attendees WHERE id = ? AND event_id = ?";
            $result = delete($deleteSql, [$attendeeId, $eventId]);

            if ($result !== false && $result > 0) {
                $_SESSION['success'] = "Attendee removed successfully.";
            } else {
                $_SESSION['error'] = "Error removing attendee. Please try again.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }

        // Redirect to prevent form resubmission
        header("Location: event_attendees.php?event_id=" . $eventId);
        exit();
    }
}

// Fetch all attendees for this event
$attendees = fetchAll("SELECT * FROM event_attendees WHERE event_id = ? ORDER BY registration_date DESC", [$eventId]);

// Count attendees by status
$attendeeCounts = [
    'total' => count($attendees),
    'registered' => 0,
    'attended' => 0,
    'cancelled' => 0,
    'no_show' => 0
];

foreach ($attendees as $attendee) {
    $attendeeCounts[$attendee['status']]++;
}

// Set page title
$pageTitle = "Manage Attendees - " . htmlspecialchars($event['title']);

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid" style="margin-top: 60px;">
    <!-- Modern Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-4 text-white rounded shadow-sm" style="min-height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="text-center flex-grow-1">
            <h1 class="mb-1">
                <i class="fas fa-users me-2"></i>
                Event Attendees
            </h1>
            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($event['title']); ?></p>
        </div>

        <!-- Action Buttons - Vertical Layout -->
        <div class="d-flex flex-column gap-2">
            <a href="event-detail.php?id=<?php echo $eventId; ?>" class="btn btn-light btn-sm d-flex align-items-center justify-content-center" style="min-width: 140px;">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Event
            </a>
            <a href="export_attendees.php?event_id=<?php echo $eventId; ?>" class="btn btn-light btn-sm d-flex align-items-center justify-content-center" style="min-width: 140px;">
                <i class="fas fa-file-export me-2"></i>
                Export List
            </a>
        </div>
    </div>

    <!-- Notification area -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i> Attendees for: <?php echo htmlspecialchars($event['title']); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attendees)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No attendees registered for this event yet.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendees as $attendee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attendee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($attendee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($attendee['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($attendee['registration_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $attendee['status'] === 'registered' ? 'primary' : 
                                                ($attendee['status'] === 'attended' ? 'success' : 
                                                ($attendee['status'] === 'cancelled' ? 'warning' : 'danger')); 
                                        ?>">
                                            <?php echo ucfirst($attendee['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="attendee_id" value="<?php echo $attendee['id']; ?>">
                                                        <input type="hidden" name="new_status" value="registered">
                                                        <button type="submit" name="update_status" class="dropdown-item">Registered</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="attendee_id" value="<?php echo $attendee['id']; ?>">
                                                        <input type="hidden" name="new_status" value="attended">
                                                        <button type="submit" name="update_status" class="dropdown-item">Attended</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="attendee_id" value="<?php echo $attendee['id']; ?>">
                                                        <input type="hidden" name="new_status" value="cancelled">
                                                        <button type="submit" name="update_status" class="dropdown-item">Cancelled</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="attendee_id" value="<?php echo $attendee['id']; ?>">
                                                        <input type="hidden" name="new_status" value="no_show">
                                                        <button type="submit" name="update_status" class="dropdown-item">No Show</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAttendeeModal<?php echo $attendee['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        
                                        <!-- Delete Attendee Modal -->
                                        <div class="modal fade" id="deleteAttendeeModal<?php echo $attendee['id']; ?>" tabindex="-1" aria-labelledby="deleteAttendeeModalLabel<?php echo $attendee['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteAttendeeModalLabel<?php echo $attendee['id']; ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to remove <strong><?php echo htmlspecialchars($attendee['name']); ?></strong> from the attendee list?</p>
                                                        <p class="text-danger">This action cannot be undone.</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="post" action="">
                                                            <input type="hidden" name="attendee_id" value="<?php echo $attendee['id']; ?>">
                                                            <button type="submit" name="delete_attendee" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Attendee Stats -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i> Attendance Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Total Registrations: <?php echo $attendeeCounts['total']; ?></h6>
                        <?php if ($event['capacity'] > 0): ?>
                        <div class="progress mb-2">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, ($attendeeCounts['total'] / $event['capacity']) * 100); ?>%">
                                <?php echo $attendeeCounts['total']; ?>/<?php echo $event['capacity']; ?>
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $attendeeCounts['total']; ?> out of <?php echo $event['capacity']; ?> spots filled
                            (<?php echo $event['capacity'] - $attendeeCounts['total']; ?> spots remaining)
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Registered
                            <span class="badge bg-primary rounded-pill"><?php echo $attendeeCounts['registered']; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Attended
                            <span class="badge bg-success rounded-pill"><?php echo $attendeeCounts['attended']; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Cancelled
                            <span class="badge bg-warning rounded-pill"><?php echo $attendeeCounts['cancelled']; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            No Show
                            <span class="badge bg-danger rounded-pill"><?php echo $attendeeCounts['no_show']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add New Attendee -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-plus me-2"></i> Add New Attendee
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="registered">Registered</option>
                                <option value="attended">Attended</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="no_show">No Show</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        <button type="submit" name="add_attendee" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i> Add Attendee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 
