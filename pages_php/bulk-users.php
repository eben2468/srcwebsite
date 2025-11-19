<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();

// Check if user has admin privileges (super admin or admin can access bulk user management)
$hasAdminPrivileges = hasAdminPrivileges();

if (!$hasAdminPrivileges) {
    $_SESSION['error'] = "Access denied. Only administrators and super administrators can perform bulk operations.";
    header("Location: users.php");
    exit();
}

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Get current user info
$currentUser = getCurrentUser();

// Get user profile data including full name
$userId = $currentUser['user_id'] ?? 0;
$userProfile = null;
if ($userId > 0) {
    try {
        $userProfile = fetchOne("SELECT * FROM user_profiles WHERE user_id = ?", [$userId]);
    } catch (Exception $e) {
        // Silently handle any database errors
    }
}

// Get user's name and first initial for avatar
$fullName = $userProfile['full_name'] ?? $currentUser['username'] ?? 'User';
$userInitial = strtoupper(substr($fullName, 0, 1));
$userName = $fullName;
$userRole = ucfirst($currentUser['role'] ?? 'User');

// Set page title
$pageTitle = "Bulk User Management - " . $siteName;

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Bulk Users Header -->
<div class="bulk-users-header animate__animated animate__fadeInDown">
    <div class="bulk-users-header-content">
        <div class="bulk-users-header-main">
            <h1 class="bulk-users-title">
                <i class="fas fa-users-cog me-3"></i>
                Bulk User Management
            </h1>
            <p class="bulk-users-description">Import and export users in bulk, manage user data efficiently</p>
        </div>
        <div class="bulk-users-header-actions">
            <a href="users.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Users
            </a>
        </div>
    </div>
</div>

<style>
.bulk-users-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.bulk-users-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.bulk-users-header-main {
    flex: 1;
    text-align: center;
}

.bulk-users-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.bulk-users-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.bulk-users-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.bulk-users-header-actions {
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
    .bulk-users-header {
        padding: 2rem 1.5rem;
    }

    .bulk-users-header-content {
        flex-direction: column;
        align-items: center;
    }

    .bulk-users-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .bulk-users-title i {
        font-size: 1.8rem;
    }

    .bulk-users-description {
        font-size: 1.1rem;
    }

    .bulk-users-header-actions {
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

/* Mobile Full-Width Optimization for Bulk Users Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure page header extends full width */
    .bulk-users-header {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid py-4">
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

    <!-- Bulk Actions Cards -->
    <div class="row">
        <!-- Import Users Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-upload me-2"></i>Import Users
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Upload a CSV file to import multiple users at once. Download the template below to ensure proper formatting.</p>
                    
                    <div class="mb-3">
                        <a href="download-template.php" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Download CSV Template
                        </a>
                    </div>
                    
                    <form action="bulk-user-handler.php" method="post" enctype="multipart/form-data" id="importForm">
                        <input type="hidden" name="action" value="import">
                        
                        <div class="mb-3">
                            <label for="csvFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csvFile" name="csv_file" accept=".csv" required>
                            <div class="form-text">Only CSV files are accepted. Maximum file size: 5MB.</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Import Users
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Export Users Card -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-download me-2"></i>Export Users
                    </h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Export users to a CSV file for backup or external use. You can select which roles to include.</p>

                    <form action="bulk-user-handler.php" method="post" id="exportForm">
                        <input type="hidden" name="action" value="export">

                        <div class="mb-3">
                            <label class="form-label">Export Options</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeAdmins" name="include_admins" value="1">
                                <label class="form-check-label" for="includeAdmins">
                                    Include Admins
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeMembers" name="include_members" value="1" checked>
                                <label class="form-check-label" for="includeMembers">
                                    Include Members
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeFinance" name="include_finance" value="1" checked>
                                <label class="form-check-label" for="includeFinance">
                                    Include Finance
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeStudents" name="include_students" value="1" checked>
                                <label class="form-check-label" for="includeStudents">
                                    Include Students
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-download me-2"></i>Export Users
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>Import Instructions
            </h5>
        </div>
        <div class="card-body">
            <h6>CSV Template Format:</h6>
            <ul>
                <li><strong>Fullname:</strong> Complete name of the user</li>
                <li><strong>Username:</strong> Will be automatically generated from the first name if left empty</li>
                <li><strong>Role:</strong> One of: "admin", "member", "finance", or "student" (super_admin cannot be imported for security)</li>
                <li><strong>Email:</strong> Valid email address (must be unique)</li>
                <li><strong>Phone:</strong> Contact phone number</li>
                <li><strong>Password:</strong> Will be automatically set based on role:
                    <ul>
                        <li>Admins: "admin123"</li>
                        <li>Members: "member123"</li>
                        <li>Finance: "finance123"</li>
                        <li>Students: "student123"</li>
                    </ul>
                </li>
            </ul>
            
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Important:</strong> Users will be required to change their password on first login for security purposes.
            </div>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to forms
    const importForm = document.getElementById('importForm');
    const exportForm = document.getElementById('exportForm');
    
    if (importForm) {
        importForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';
            submitBtn.disabled = true;
        });
    }
    
    if (exportForm) {
        exportForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Exporting...';
            submitBtn.disabled = true;
        });
    }
});
</script>
