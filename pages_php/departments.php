<?php
// Main departments listing page
require_once '../db_config.php';
require_once '../functions.php';
require_once '../auth_functions.php'; // Change: Added auth_functions for standard auth
require_once '../auth_bridge.php'; // Add auth bridge for user authentication

// Check for admin status
$isAdmin = isAdmin() || getBridgedAdminStatus();

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
$pageTitle = "School Departments";

// Include standard header instead of department_header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    
    <?php 
    // Define page title, icon, and actions for the enhanced header
    $pageTitle = "Departments";
    $pageIcon = "fa-building";
    $actions = [];
    
    if ($isAdmin || $isMember || isset($canManageDepartments)) {
        $actions[] = [
            'url' => '#',
            'icon' => 'fa-plus',
            'text' => 'Add New',
            'class' => 'btn-primary',
            'attributes' => 'data-bs-toggle="modal" data-bs-target="#addDepartmentModal"'
        ];
    }
    
    // Include the enhanced page header
    include_once 'includes/enhanced_page_header.php';
    ?></div>
    
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
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <img src="<?php echo getDepartmentImagePath($department['code']); ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($department['name']); ?>"
                     style="height: 200px; object-fit: cover;">
                <div class="content-card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="content-card-title"><?php echo htmlspecialchars($department['name']); ?></h5>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($department['code']); ?></span>
                    </div>
                    <p class="card-text"><?php echo substr(htmlspecialchars($department['description']), 0, 150); ?>...</p>
                </div>
                <div class="card-footer bg-transparent d-flex justify-content-between">
                    <small class="text-muted">Head: <?php echo htmlspecialchars($department['head']); ?></small>
                    <a href="<?php echo addAdminParam('department-detail.php?code=' . urlencode($department['code'])); ?>" class="btn btn-sm btn-outline-primary">
                        View Details
                    </a>
                </div>
                <?php if ($isAdmin): ?>
                <div class="card-footer bg-transparent text-end">
                    <a href="<?php echo addAdminParam('department-detail.php?code=' . urlencode($department['code'])); ?>" class="btn btn-sm btn-outline-info me-2">
                        <i class="fas fa-cog"></i> Manage
                    </a>
                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteDepartmentModal<?php echo $department['id']; ?>">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
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
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
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
                <form action="department_handler.php<?php echo $isAdmin ? '?admin=1' : ''; ?>" method="POST">
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

<?php
// Include standard footer instead of department_footer
require_once 'includes/footer.php';
?> 