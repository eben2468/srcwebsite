<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();

// Check for admin status - use unified admin interface check for super admin users
// Using shouldUseAdminInterface() directly throughout the file

// Check if departments feature is enabled
if (!hasFeaturePermission('enable_departments')) {
    $_SESSION['error'] = "Departments feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Load departments data from JSON file
$departmentsFile = '../data/departments.json';
$departments = [];

if (file_exists($departmentsFile)) {
    $departmentsData = file_get_contents($departmentsFile);
    $departmentsArray = json_decode($departmentsData, true) ?: [];
    
    // Convert associative array to numeric array for display
    foreach ($departmentsArray as $dept) {
        $departments[] = $dept;
    }
} else {
    // Create empty departments file if it doesn't exist
    if (!file_exists('../data')) {
        mkdir('../data', 0777, true);
    }
    file_put_contents($departmentsFile, json_encode([], JSON_PRETTY_PRINT));
    
    // Default departments as fallback
    $departments = [
        [
            'id' => 1,
            'code' => 'NURSA',
            'name' => 'School of Nursing and Midwifery',
            'head' => 'Prof. Sarah Johnson',
            'description' => 'The School of Nursing and Midwifery is dedicated to developing competent, compassionate nursing professionals through innovative education, research, and clinical practice.',
            'programs' => ['Bachelor of Nursing', 'Diploma in Midwifery', 'Master of Nursing Science', 'PhD in Nursing']
        ],
        [
            'id' => 2,
            'code' => 'THEMSA',
            'name' => 'School of Theology and Mission',
            'head' => 'Dr. James Matthews',
            'description' => 'The School of Theology and Mission prepares students for ministries in various contexts through theological education, spiritual formation, and practical training.',
            'programs' => ['Bachelor of Theology', 'Master of Divinity', 'Master of Arts in Biblical Studies', 'Diploma in Mission Studies']
        ]
    ];
}

// Function to get department image path
function getDepartmentImagePath($departmentCode) {
    $code = strtolower($departmentCode);
    $mainPath = "../images/departments/{$code}.jpg";
    $pngPath = "../images/departments/{$code}.png";
    $jpegPath = "../images/departments/{$code}.jpeg";
    $defaultPath = "../images/departments/default.jpg";
    
    // Debug information
    $debug = [];
    $debug[] = "Checking paths for department code: {$code}";
    $debug[] = "JPG path: {$mainPath} - Exists: " . (file_exists($mainPath) ? 'Yes' : 'No');
    $debug[] = "PNG path: {$pngPath} - Exists: " . (file_exists($pngPath) ? 'Yes' : 'No');
    $debug[] = "JPEG path: {$jpegPath} - Exists: " . (file_exists($jpegPath) ? 'Yes' : 'No');
    
    // Log debug info to a file for troubleshooting
    file_put_contents('../debug_images.log', implode("\n", $debug) . "\n\n", FILE_APPEND);
    
    // Check if any of the image files exist
    if (file_exists($mainPath)) {
        return $mainPath;
    } elseif (file_exists($pngPath)) {
        return $pngPath;
    } elseif (file_exists($jpegPath)) {
        return $jpegPath;
    } else {
        // If default image doesn't exist, create a placeholder URL with the department code
        if (!file_exists($defaultPath)) {
            return "https://via.placeholder.com/800x400/0066cc/ffffff?text=" . urlencode($departmentCode);
        }
        return $defaultPath;
    }
}

// Helper function to add admin parameter to URLs - uses unified admin interface check
function addAdminParam($url) {
    if (!shouldUseAdminInterface()) return $url;
    
    return strpos($url, '?') !== false ? $url . '&admin=1' : $url . '?admin=1';
}

// Page title
$pageTitle = "School Departments";

// Include standard header instead of department_header
require_once 'includes/header.php';
?>



<!-- Page Content -->
<div class="container-fluid" style="margin-top: 60px;">
    <?php
    // Set up modern page header variables
    $pageTitle = "School Departments";
    $pageIcon = "fa-building";
    $pageDescription = "Explore the various academic departments and their programs";
    $actions = [];

    if (shouldUseAdminInterface()) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Add Department',
            'class' => 'btn-outline-light',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#addDepartmentModal'
        ];
    }

    // Include modern page header
    include 'includes/modern_page_header.php';
    ?>

<script>
    document.body.classList.add('departments-page');
</script>

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

    <?php if (empty($departments)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> No departments found. Add a department to get started.
    </div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($departments as $department): ?>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="modern-department-card h-100">
                <!-- Card Header with Image and Code Badge -->
                <div class="department-image-container">
                    <?php
                    $imagePath = getDepartmentImagePath($department['code']);
                    $departmentName = htmlspecialchars($department['name']);
                    ?>
                    <img src="<?php echo $imagePath; ?>"
                         class="department-image"
                         alt="<?php echo $departmentName; ?>"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/800x400/667eea/ffffff?text=<?php echo urlencode($department['code']); ?>';">

                    <!-- Department Code Badge -->
                    <div class="department-code-badge">
                        <?php echo htmlspecialchars($department['code']); ?>
                    </div>

                    <!-- Gradient Overlay -->
                    <div class="image-overlay"></div>
                </div>

                <!-- Card Body -->
                <div class="card-body-modern">
                    <!-- Department Title -->
                    <h3 class="department-title">
                        <?php echo $departmentName; ?>
                    </h3>

                    <!-- Department Description -->
                    <p class="department-description">
                        <?php echo substr(htmlspecialchars($department['description']), 0, 140); ?>
                        <?php echo strlen($department['description']) > 140 ? '...' : ''; ?>
                    </p>

                    <!-- Department Head Info -->
                    <div class="department-head-info">
                        <div class="head-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="head-details">
                            <span class="head-label">Department Head</span>
                            <span class="head-name"><?php echo htmlspecialchars($department['head']); ?></span>
                        </div>
                    </div>

                    <!-- Department Stats (if available) -->
                    <div class="department-stats">
                        <div class="stat-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Academic</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span>Active</span>
                        </div>
                    </div>
                </div>

                <!-- Card Footer with Actions -->
                <div class="card-footer-modern">
                    <!-- Primary Action -->
                    <div class="primary-actions">
                        <a href="<?php echo addAdminParam('department-detail.php?code=' . urlencode($department['code'])); ?>" class="btn-primary-modern">
                            <i class="fas fa-eye"></i>
                            <span>View Details</span>
                        </a>
                    </div>

                    <!-- Secondary Actions -->
                    <?php if (shouldUseAdminInterface()): ?>
                    <div class="secondary-actions">
                        <a href="<?php echo addAdminParam('department-detail.php?code=' . urlencode($department['code'])); ?>" class="action-btn" title="Manage Department">
                            <i class="fas fa-cog"></i>
                        </a>

                        <button class="action-btn delete-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteDepartmentModal<?php echo $department['id']; ?>"
                                title="Delete Department">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (shouldUseAdminInterface()): ?>
<!-- Admin Modals -->

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDepartmentModalLabel">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_department">
                    
                    <div class="mb-3">
                        <label for="departmentCode" class="form-label">Department Code</label>
                        <input type="text" class="form-control" id="departmentCode" name="code" maxlength="10" required>
                        <div class="form-text">Short code for the department (e.g., NURSA, EDSA)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentName" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="departmentName" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentHead" class="form-label">Department Head</label>
                        <input type="text" class="form-control" id="departmentHead" name="head" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentEmail" class="form-label">Department Email</label>
                        <input type="email" class="form-control" id="departmentEmail" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentPhone" class="form-label">Department Phone</label>
                        <input type="text" class="form-control" id="departmentPhone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="departmentDescription" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentPrograms" class="form-label">Programs Offered</label>
                        <textarea class="form-control" id="departmentPrograms" name="programs" rows="3" placeholder="Enter one program per line"></textarea>
                        <div class="form-text">Enter one program per line (e.g., Bachelor of Education)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="departmentImage" class="form-label">Department Image</label>
                        <input type="file" class="form-control" id="departmentImage" name="department_image" accept="image/jpeg,image/png,image/jpg">
                        <div class="form-text">Recommended size: 800x400 pixels. Only JPG, JPEG, PNG formats.</div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Department</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Department Modals -->
<?php foreach ($departments as $department): ?>
<div class="modal fade" id="deleteDepartmentModal<?php echo $department['id']; ?>" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel<?php echo $department['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDepartmentModalLabel<?php echo $department['id']; ?>">Delete Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the department "<?php echo htmlspecialchars($department['name']); ?>" (<?php echo htmlspecialchars($department['code']); ?>)?
                <p class="text-danger mt-3"><strong>Warning:</strong> This action cannot be undone and will delete all associated events, contacts, documents, and gallery images.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
                    <input type="hidden" name="action" value="delete_department">
                    <input type="hidden" name="department_id" value="<?php echo $department['id']; ?>">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($department['code']); ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<style>
/* Modern Department Card Styles */
.modern-department-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.05);
    position: relative;
}

.modern-department-card:hover {
    transform: translateY(-12px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
}

/* Department Image Container */
.department-image-container {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.department-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.modern-department-card:hover .department-image {
    transform: scale(1.08);
}

.department-code-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 700;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    backdrop-filter: blur(10px);
}

.image-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 50%;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
    pointer-events: none;
}

/* Card Body */
.card-body-modern {
    padding: 2rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.department-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1a202c;
    margin-bottom: 1rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.department-description {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    flex-grow: 1;
    font-size: 0.95rem;
}

.department-head-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    padding: 1rem;
    border-radius: 16px;
    margin-bottom: 1.5rem;
    border: 1px solid #e2e8f0;
}

.head-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.head-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.head-label {
    font-size: 0.75rem;
    color: #718096;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.head-name {
    font-size: 1rem;
    color: #2d3748;
    font-weight: 600;
}

.department-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    font-size: 0.875rem;
    color: #4a5568;
    font-weight: 500;
}

.stat-item i {
    color: #667eea;
}

/* Card Footer */
.card-footer-modern {
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.primary-actions {
    flex: 1;
}

.btn-primary-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    border: none;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.btn-primary-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.secondary-actions {
    display: flex;
    gap: 0.75rem;
    margin-left: 1.5rem;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: white;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
    font-size: 1rem;
}

.action-btn:hover {
    background: #f1f5f9;
    color: #475569;
    transform: translateY(-2px);
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-btn.delete-btn:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body-modern {
        padding: 1.5rem;
    }

    .card-footer-modern {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
        padding: 1.5rem;
    }

    .secondary-actions {
        margin-left: 0;
        justify-content: center;
    }

    .btn-primary-modern {
        justify-content: center;
    }

    .department-head-info {
        padding: 0.75rem;
    }

    .head-avatar {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .department-image-container {
        height: 180px;
    }

    .department-title {
        font-size: 1.25rem;
    }

    .department-stats {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
