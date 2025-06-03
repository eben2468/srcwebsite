<?php
// Department detail page
require_once '../db_config.php';
require_once '../functions.php';
require_once '../auth_functions.php'; // Add auth_functions for standard auth
require_once '../auth_bridge.php'; // Add auth bridge for user authentication

// Get department code from URL
$departmentCode = isset($_GET['code']) ? strtoupper($_GET['code']) : '';

// Check for admin status using both systems
$isAdmin = isAdmin() || getBridgedAdminStatus();

// Load departments data from JSON file
$departmentsFile = '../data/departments.json';
$departments = [];

if (file_exists($departmentsFile)) {
    $departmentsData = file_get_contents($departmentsFile);
    $departments = json_decode($departmentsData, true) ?: [];
} else {
    // Redirect to departments page if data file doesn't exist
    header("Location: departments.php");
    exit();
}

// Check if department exists
if (!isset($departments[$departmentCode])) {
    header("Location: departments.php");
    exit();
}

$department = $departments[$departmentCode];

// Function to get department image path
function getDepartmentImagePath($departmentCode) {
    $code = strtolower($departmentCode);
    $mainPath = "../images/departments/{$code}.jpg";
    $pngPath = "../images/departments/{$code}.png";
    $jpegPath = "../images/departments/{$code}.jpeg";
    $defaultPath = "../images/departments/default.jpg";
    
    if (file_exists($mainPath)) {
        return $mainPath;
    } elseif (file_exists($pngPath)) {
        return $pngPath;
    } elseif (file_exists($jpegPath)) {
        return $jpegPath;
    } else {
        // If default image doesn't exist, return a placeholder URL
        if (!file_exists($defaultPath)) {
            return "https://via.placeholder.com/800x400/0066cc/ffffff?text=" . urlencode($departmentCode);
        }
        return $defaultPath;
    }
}

// Helper function to add admin parameter to URLs - copied from the department_header.php
function addAdminParam($url) {
    global $isAdmin;
    if (!$isAdmin) return $url;
    
    return strpos($url, '?') !== false ? $url . '&admin=1' : $url . '?admin=1';
}

// Page title
$pageTitle = $department['name'] . " (" . $department['code'] . ")";

// Include standard header instead of department_header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
        
        <div>
            <?php if ($isAdmin): ?>
            <a href="#" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#editDepartmentModal">
                <i class="fas fa-edit me-2"></i> Edit Department
            </a>
            <?php endif; ?>
            <a href="<?php echo addAdminParam('departments.php'); ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Departments
            </a>
        </div>
    </div>
    
    <!-- Notification area -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Department Banner -->
    <div class="card mb-4">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="<?php echo getDepartmentImagePath($department['code']); ?>" 
                         alt="<?php echo htmlspecialchars($department['name']); ?>" 
                         class="img-fluid rounded-start" style="max-height: 300px; width: 100%; object-fit: cover;">
                </div>
                <div class="col-md-8">
                    <div class="card-body h-100 d-flex flex-column justify-content-center">
                        <h2 class="card-title"><?php echo htmlspecialchars($department['name']); ?></h2>
                        <p class="card-text"><?php echo htmlspecialchars($department['description']); ?></p>
                        <div class="mt-3">
                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($department['code']); ?></span>
                            <span class="text-muted">Head: <?php echo htmlspecialchars($department['head']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Tabs -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="departmentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" href="#overview" role="tab" aria-controls="overview" aria-selected="true">
                        <i class="fas fa-info-circle me-2"></i> Overview
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" href="#events" role="tab" aria-controls="events" aria-selected="false">
                        <i class="fas fa-calendar-alt me-2"></i> Events
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="contacts-tab" data-bs-toggle="tab" data-bs-target="#contacts" href="#contacts" role="tab" aria-controls="contacts" aria-selected="false">
                        <i class="fas fa-address-book me-2"></i> Contacts
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" href="#documents" role="tab" aria-controls="documents" aria-selected="false">
                        <i class="fas fa-file-alt me-2"></i> Documents
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#gallery" href="#gallery" role="tab" aria-controls="gallery" aria-selected="false">
                        <i class="fas fa-images me-2"></i> Gallery
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="departmentTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <h3>Programs Offered</h3>
                    <ul class="list-group mb-4">
                        <?php foreach ($department['programs'] as $program): ?>
                        <li class="list-group-item">
                            <i class="fas fa-graduation-cap me-2 text-primary"></i>
                            <?php echo htmlspecialchars($program); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h3>Contact Information</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email:</label>
                                <div><a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($department['email']); ?>" target="_blank"><?php echo htmlspecialchars($department['email']); ?></a></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone:</label>
                                <div><a href="tel:<?php echo htmlspecialchars($department['phone']); ?>"><?php echo htmlspecialchars($department['phone']); ?></a></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Events Tab -->
                <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
                    <?php if ($isAdmin): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                            <i class="fas fa-plus me-2"></i> Add Event
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($department['events'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No upcoming events for this department.
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($department['events'] as $index => $event): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <?php if ($isAdmin): ?>
                                <div>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEventModal<?php echo $index; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($event['description']); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i> <?php echo date('F j, Y', strtotime($event['date'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Contacts Tab -->
                <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
                    <?php if ($isAdmin): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                            <i class="fas fa-plus me-2"></i> Add Contact
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php foreach ($department['contacts'] as $index => $contact): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title"><?php echo htmlspecialchars($contact['name']); ?></h5>
                                        <?php if ($isAdmin): ?>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteContactModal<?php echo $index; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($contact['position']); ?></h6>
                                    <p class="card-text">
                                        <i class="fas fa-envelope me-2"></i>
                                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($contact['email']); ?>" target="_blank"><?php echo htmlspecialchars($contact['email']); ?></a>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-phone me-2"></i>
                                        <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>"><?php echo htmlspecialchars($contact['phone']); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    <?php if ($isAdmin): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                            <i class="fas fa-plus me-2"></i> Add Document
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (empty($department['documents'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> No documents available for this department.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Document Title</th>
                                    <th>Filename</th>
                                    <th>Upload Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($department['documents'] as $document): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($document['title']); ?></td>
                                    <td><?php echo htmlspecialchars($document['original_filename'] ?? $document['filename']); ?></td>
                                    <td><?php echo isset($document['upload_date']) ? date('M d, Y', strtotime($document['upload_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../documents/departments/<?php echo htmlspecialchars($document['filename']); ?>" class="btn btn-sm btn-primary" download>
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                            <?php if ($isAdmin): ?>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteDocumentModal<?php echo $document['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Gallery Tab -->
                <div class="tab-pane fade" id="gallery" role="tabpanel" aria-labelledby="gallery-tab">
                    <?php if ($isAdmin): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGalleryImageModal">
                            <i class="fas fa-plus me-2"></i> Add Image
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php
                        // Check for gallery data in the department JSON first
                        if (!empty($department['gallery'])):
                        ?>
                            <?php foreach ($department['gallery'] as $image): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <img src="../<?php echo htmlspecialchars($image['path']); ?>" class="card-img-top" alt="Gallery Image">
                                    <div class="card-footer d-flex justify-content-between align-items-center">
                                        <?php if (!empty($image['caption'])): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($image['caption']); ?></small>
                                        <?php else: ?>
                                        <small class="text-muted">Department Gallery Image</small>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <a href="../<?php echo htmlspecialchars($image['path']); ?>" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <?php if ($isAdmin): ?>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteGalleryImageModal<?php echo $image['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php 
                        else:
                            // Fallback to directory scanning if no JSON data
                            $galleryDir = "../images/departments/gallery/" . strtolower($department['code']) . "/";
                            $galleryImages = [];
                            
                            if (file_exists($galleryDir) && is_dir($galleryDir)) {
                                $files = scandir($galleryDir);
                                foreach ($files as $file) {
                                    if ($file != "." && $file != ".." && (pathinfo($file, PATHINFO_EXTENSION) == "jpg" || 
                                        pathinfo($file, PATHINFO_EXTENSION) == "jpeg" || 
                                        pathinfo($file, PATHINFO_EXTENSION) == "png")) {
                                        $galleryImages[] = $galleryDir . $file;
                                    }
                                }
                            }
                            
                            if (empty($galleryImages)):
                            ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> No gallery images available for this department.
                                </div>
                            </div>
                            <?php else: ?>
                                <?php foreach ($galleryImages as $index => $imagePath): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card">
                                        <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="Gallery Image">
                                        <div class="card-footer d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Department Gallery Image</small>
                                            <div>
                                                <a href="<?php echo $imagePath; ?>" class="btn btn-sm btn-outline-primary" download>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <?php if ($isAdmin): ?>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteGalleryImageModal<?php echo $index; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Admin Modals -->
<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-labelledby="editDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDepartmentModalLabel">Edit Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_department">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                    
                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="departmentName" name="name" value="<?php echo htmlspecialchars($department['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentHead" class="form-label">Department Head</label>
                        <input type="text" class="form-control" id="departmentHead" name="head" value="<?php echo htmlspecialchars($department['head']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentEmail" class="form-label">Department Email</label>
                        <input type="email" class="form-control" id="departmentEmail" name="email" value="<?php echo htmlspecialchars($department['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentPhone" class="form-label">Department Phone</label>
                        <input type="text" class="form-control" id="departmentPhone" name="phone" value="<?php echo htmlspecialchars($department['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="departmentDescription" name="description" rows="3" required><?php echo htmlspecialchars($department['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentPrograms" class="form-label">Programs Offered</label>
                        <textarea class="form-control" id="departmentPrograms" name="programs" rows="3" placeholder="Enter one program per line"><?php echo htmlspecialchars(implode("\n", $department['programs'])); ?></textarea>
                        <div class="form-text">Enter one program per line (e.g., Bachelor of Education)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentImage" class="form-label">Department Image</label>
                        <input type="file" class="form-control" id="departmentImage" name="department_image" accept="image/jpeg,image/png,image/jpg">
                        <div class="form-text">Recommended size: 800x400 pixels. Only JPG, JPEG, PNG formats. Leave empty to keep current image.</div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                    
                    <div class="mb-3">
                        <label for="eventTitle" class="form-label">Event Title</label>
                        <input type="text" class="form-control" id="eventTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventDate" class="form-label">Event Date</label>
                        <input type="date" class="form-control" id="eventDate" name="date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">Event Description</label>
                        <textarea class="form-control" id="eventDescription" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Event Modals -->
<?php if (!empty($department['events'])): ?>
    <?php foreach ($department['events'] as $event): ?>
    <div class="modal fade" id="deleteEventModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="deleteEventModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEventModalLabel<?php echo $event['id']; ?>">Delete Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the event "<?php echo htmlspecialchars($event['title']); ?>"?
                    <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="delete_event">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Add Contact Modal -->
<div class="modal fade" id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addContactModalLabel">Add New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                    <input type="hidden" name="action" value="add_contact">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                    
                    <div class="mb-3">
                        <label for="contactName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="contactName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contactPosition" class="form-label">Position</label>
                        <input type="text" class="form-control" id="contactPosition" name="position" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contactEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="contactEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contactPhone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="contactPhone" name="phone" required>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Contact</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Contact Modals -->
<?php if (!empty($department['contacts'])): ?>
    <?php foreach ($department['contacts'] as $contact): ?>
    <div class="modal fade" id="deleteContactModal<?php echo $contact['id']; ?>" tabindex="-1" aria-labelledby="deleteContactModalLabel<?php echo $contact['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteContactModalLabel<?php echo $contact['id']; ?>">Delete Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the contact "<?php echo htmlspecialchars($contact['name']); ?>"?
                    <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="delete_contact">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Add Document Modal -->
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDocumentModalLabel">Add New Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_document">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                    
                    <div class="mb-3">
                        <label for="documentTitle" class="form-label">Document Title</label>
                        <input type="text" class="form-control" id="documentTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="documentFile" class="form-label">Document File</label>
                        <input type="file" class="form-control" id="documentFile" name="document_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT</div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Document</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Document Modals -->
<?php if (!empty($department['documents'])): ?>
    <?php foreach ($department['documents'] as $document): ?>
    <div class="modal fade" id="deleteDocumentModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="deleteDocumentModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteDocumentModalLabel<?php echo $document['id']; ?>">Delete Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the document "<?php echo htmlspecialchars($document['title']); ?>"?
                    <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="delete_document">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Add Gallery Image Modal -->
<div class="modal fade" id="addGalleryImageModal" tabindex="-1" aria-labelledby="addGalleryImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGalleryImageModalLabel">Add Gallery Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_gallery_image">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                    
                    <div class="mb-3">
                        <label for="galleryImage" class="form-label">Image File</label>
                        <input type="file" class="form-control" id="galleryImage" name="gallery_image" required accept="image/jpeg,image/png,image/jpg">
                        <div class="form-text">Accepted formats: JPG, JPEG, PNG. Max file size: 5MB.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="imageCaption" class="form-label">Image Caption (optional)</label>
                        <input type="text" class="form-control" id="imageCaption" name="caption">
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Image</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Gallery Image Modals -->
<?php if (!empty($department['gallery'])): ?>
    <?php foreach ($department['gallery'] as $image): ?>
    <div class="modal fade" id="deleteGalleryImageModal<?php echo $image['id']; ?>" tabindex="-1" aria-labelledby="deleteGalleryImageModalLabel<?php echo $image['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteGalleryImageModalLabel<?php echo $image['id']; ?>">Delete Gallery Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this gallery image?
                    <?php if (!empty($image['caption'])): ?>
                    <p><strong>Caption:</strong> "<?php echo htmlspecialchars($image['caption']); ?>"</p>
                    <?php endif; ?>
                    <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="delete_gallery_image">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Delete Gallery Image Modals for directory images -->
<?php if (empty($department['gallery']) && !empty($galleryImages)): ?>
    <?php foreach ($galleryImages as $index => $imagePath): ?>
    <div class="modal fade" id="deleteGalleryImageModal<?php echo $index; ?>" tabindex="-1" aria-labelledby="deleteGalleryImageModalLabel<?php echo $index; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteGalleryImageModalLabel<?php echo $index; ?>">Delete Gallery Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this gallery image?</p>
                    <div class="text-center my-3">
                        <img src="<?php echo $imagePath; ?>" class="img-fluid" style="max-height: 200px;" alt="Image preview">
                    </div>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="delete_gallery_image_file">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($imagePath); ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php
// Include standard footer instead of department_footer
require_once 'includes/footer.php';
?> 