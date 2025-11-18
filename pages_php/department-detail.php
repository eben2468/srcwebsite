<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();

// Get department code from URL
$departmentCode = isset($_GET['code']) ? strtoupper($_GET['code']) : '';

// Check for admin status - use unified admin interface check for super admin users
// Using shouldUseAdminInterface() directly throughout the file

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
    if (!shouldUseAdminInterface()) return $url;
    
    return strpos($url, '?') !== false ? $url . '&admin=1' : $url . '?admin=1';
}

// Page title
$pageTitle = $department['name'] . " (" . $department['code'] . ")";

// Include standard header instead of department_header
require_once 'includes/header.php';
?>

<!-- Modern Department Header -->
<div class="finance-header animate__animated animate__fadeInDown">
    <div class="finance-header-content">
        <div class="finance-header-main">
            <h1 class="finance-title">
                <i class="fas fa-building me-3"></i>
                <?php echo htmlspecialchars($department['name']); ?>
            </h1>
            <p class="finance-description">Department Code: <?php echo htmlspecialchars($department['code']); ?> | <?php echo htmlspecialchars($department['description'] ?? 'No description available'); ?></p>
        </div>
        <div class="finance-header-actions">
            <?php if (shouldUseAdminInterface()): ?>
        <button class="btn btn-header-action" onclick="toggleEditForm()">
            <i class="fas fa-edit me-2"></i>Edit Department
        </button>
        <?php endif; ?>
            <a href="<?php echo addAdminParam('departments.php'); ?>" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Departments
            </a>
        </div>
    </div>
</div>

<style>
/* Modern Color Variables */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    --card-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.15);
    --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    --border-radius: 16px;
}

.finance-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: var(--border-radius);
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.finance-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.1), transparent);
    pointer-events: none;
}

.finance-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.finance-header-main {
    flex: 1;
    text-align: center;
}

.finance-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    text-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.finance-title i {
    font-size: 2.2rem;
}

.finance-description {
    margin: 1rem 0 0 0;
    opacity: 0.95;
    font-size: 1.2rem;
    line-height: 1.6;
    font-weight: 300;
}

.finance-header-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.8rem;
    flex-wrap: wrap;
}

.btn-header-action {
    background: rgba(255, 255, 255, 0.15);
    border: 1.5px solid rgba(255, 255, 255, 0.3);
    color: white;
    backdrop-filter: blur(12px);
    transition: var(--transition-smooth);
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
    text-decoration: none;
}

@media (max-width: 768px) {
    .finance-header {
        padding: 2rem 1.5rem;
    }

    .finance-header-content {
        flex-direction: column;
        align-items: center;
    }

    .finance-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .finance-title i {
        font-size: 1.8rem;
    }

    .finance-description {
        font-size: 1.1rem;
    }

    .finance-header-actions {
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

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translate3d(0, 30px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translate3d(50px, 0, 0);
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

.animate__fadeInUp {
    animation-name: fadeInUp;
}

.animate__slideInRight {
    animation-name: slideInRight;
}

/* Gallery Image Styles */
.gallery-image-container {
    position: relative;
    height: 280px;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.gallery-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.gallery-image-container:hover .gallery-overlay {
    opacity: 1;
}

.gallery-image-container:hover .gallery-image {
    transform: scale(1.1);
}

.view-image-btn {
    border-radius: 50px;
    padding: 10px 20px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: var(--transition-smooth);
}

.view-image-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* Image View Modal */
.image-view-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    animation: fadeIn 0.3s ease;
}

.image-view-content {
    position: relative;
    margin: auto;
    padding: 20px;
    width: 90%;
    max-width: 1200px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.image-view-img {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
}

.image-view-caption {
    color: white;
    text-align: center;
    margin-top: 20px;
    font-size: 1.2rem;
    font-weight: 500;
}

.image-view-close {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s ease;
}

.image-view-close:hover {
    color: #ccc;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Modern Tab Styles */
.modern-tabs .nav-link {
    border: none;
    border-radius: 10px;
    padding: 0.8rem 1.5rem;
    margin: 0 0.5rem;
    color: #4a5568;
    font-weight: 500;
    transition: var(--transition-smooth);
    background: transparent;
}

.modern-tabs .nav-link:hover {
    background: rgba(102, 126, 234, 0.08);
    color: #667eea;
    transform: translateY(-2px);
}

.modern-tabs .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* Enhanced Card Styles */
.card {
    transition: var(--transition-smooth);
}

.card:hover {
    transform: translateY(-5px);
}

/* Program List Enhancement */
.list-group-item {
    border: none;
    border-radius: 10px;
    padding: 1rem 1.5rem;
    margin-bottom: 0.5rem;
    background: #f7fafc;
    transition: var(--transition-smooth);
}

.list-group-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
    transform: translateX(8px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Contact Card Enhancement */
.contact-card {
    border-radius: 12px;
    border: none;
    transition: var(--transition-smooth);
    box-shadow: var(--card-shadow);
    overflow: hidden;
}

.contact-card:hover {
    box-shadow: var(--card-shadow-hover);
    transform: translateY(-8px);
}

.contact-card .card-body {
    padding: 1.5rem;
}

.contact-card .card-title {
    font-weight: 700;
    font-size: 1.2rem;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.contact-card .card-subtitle {
    color: #667eea;
    font-weight: 500;
    margin-bottom: 1rem;
}

/* Event Item Enhancement */
.event-item {
    border: none;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    transition: var(--transition-smooth);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.event-item:hover {
    box-shadow: var(--card-shadow);
    transform: translateX(8px);
}

.event-item h5 {
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

/* Document Table Enhancement */
.table {
    border-collapse: separate;
    border-spacing: 0 0.5rem;
}

.table thead th {
    border: none;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    color: #2d3748;
    font-weight: 700;
    padding: 1rem 1.5rem;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.table tbody tr {
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: var(--transition-smooth);
}

.table tbody tr:hover {
    box-shadow: var(--card-shadow);
    transform: translateY(-2px);
}

.table tbody td {
    border: none;
    padding: 1rem 1.5rem;
    vertical-align: middle;
}

/* Button Enhancements */
.btn {
    border-radius: 10px;
    font-weight: 500;
    padding: 0.6rem 1.2rem;
    transition: var(--transition-smooth);
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
}

.btn-success:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
}

.btn-outline-primary {
    border: 2px solid #667eea;
    color: #667eea;
    background: transparent;
}

.btn-outline-primary:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
    transform: translateY(-2px);
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
}

/* Alert Enhancement */
.alert {
    border: none;
    border-radius: 12px;
    padding: 1.2rem 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.alert-info {
    background: linear-gradient(135deg, #bee3f8 0%, #90cdf4 100%);
    color: #2c5282;
}

.alert-success {
    background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
    color: #22543d;
}

.alert-danger {
    background: linear-gradient(135deg, #fed7d7 0%, #fc8181 100%);
    color: #742a2a;
}

/* Modal Enhancement */
.modal-content {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    border-bottom: 2px solid #edf2f7;
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.modal-title {
    font-weight: 700;
    color: #2d3748;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 2px solid #edf2f7;
    padding: 1.5rem 2rem;
    background: #f7fafc;
}

/* Form Enhancement */
.form-label {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: var(--transition-smooth);
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

/* Responsive Enhancements */
@media (max-width: 991px) {
    .contact-card {
        margin-bottom: 1rem;
    }
    
    .gallery-image-container {
        height: 240px;
    }
    
    h3 {
        font-size: 1.4rem !important;
    }
}

@media (max-width: 767px) {
    .card-body {
        padding: 1.5rem !important;
    }
    
    .modern-tabs .nav-link {
        padding: 0.6rem 1rem;
        margin: 0.2rem;
        font-size: 0.9rem;
    }
    
    .gallery-image-container {
        height: 220px;
    }
    
    .event-item h5 {
        font-size: 1.1rem;
    }
}

@media (max-width: 575px) {
    .finance-header {
        padding: 1.5rem 1rem;
    }
    
    .finance-title {
        font-size: 1.8rem !important;
    }
    
    .finance-description {
        font-size: 1rem !important;
    }
    
    .modern-tabs {
        flex-direction: column;
    }
    
    .modern-tabs .nav-link {
        width: 100%;
        margin: 0.25rem 0;
    }
    
    .btn {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
}

/* Smooth Scroll */
html {
    scroll-behavior: smooth;
}

/* Loading Animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.loading {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Badge Enhancement */
.badge {
    transition: var(--transition-smooth);
}

.badge:hover {
    transform: scale(1.05);
}

/* Table Responsive Enhancement */
.table-responsive {
    border-radius: 12px;
    box-shadow: var(--card-shadow);
}

/* Button Group Enhancement */
.btn-group {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    border-radius: 10px;
    overflow: hidden;
}

.btn-group .btn {
    border-radius: 0;
}

.btn-group .btn:first-child {
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
}

.btn-group .btn:last-child {
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
}
</style>

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

    <?php if (shouldUseAdminInterface()): ?>
    <!-- Edit Department Form (Above All Content) -->
    <div class="card mb-4 shadow-lg border-0 animate__animated animate__fadeInUp" id="editDepartmentSection" style="display: none; border-radius: var(--border-radius); overflow: hidden;">
        <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem;">
            <h5 class="mb-0" style="font-weight: 700;"><i class="fas fa-edit me-2"></i>Edit Department Information</h5>
        </div>
        <div class="card-body" style="padding: 2rem;">
            <form id="editDepartmentForm" action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_department">
                <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">

                <div class="row">
                    <div class="col-md-6">
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
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="departmentDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="departmentDescription" name="description" rows="4" required><?php echo htmlspecialchars($department['description']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="departmentPrograms" class="form-label">Programs Offered</label>
                            <textarea class="form-control" id="departmentPrograms" name="programs" rows="4" placeholder="Enter one program per line"><?php echo htmlspecialchars(implode("\n", $department['programs'])); ?></textarea>
                            <div class="form-text">Enter one program per line (e.g., Bachelor of Education)</div>
                        </div>

                        <div class="mb-3">
                            <label for="departmentImage" class="form-label">Department Image</label>
                            <input type="file" class="form-control" id="departmentImage" name="department_image" accept="image/jpeg,image/png,image/jpg">
                            <div class="form-text">Recommended size: 800x400 pixels. Only JPG, JPEG, PNG formats. Leave empty to keep current image.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="toggleEditForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Department Banner -->
    <div class="card mb-4 shadow-lg border-0 animate__animated animate__fadeInUp" style="animation-delay: 0.1s; border-radius: var(--border-radius); overflow: hidden;">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="<?php echo getDepartmentImagePath($department['code']); ?>" 
                         alt="<?php echo htmlspecialchars($department['name']); ?>" 
                         class="img-fluid" style="max-height: 350px; width: 100%; object-fit: cover; transition: transform 0.6s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                </div>
                <div class="col-md-8">
                    <div class="card-body h-100 d-flex flex-column justify-content-center p-5">
                        <h2 class="card-title mb-3" style="font-weight: 700; font-size: 2rem; color: #2d3748; letter-spacing: -0.01em;"><?php echo htmlspecialchars($department['name']); ?></h2>
                        <p class="card-text mb-4" style="font-size: 1.1rem; line-height: 1.8; color: #4a5568;"><?php echo htmlspecialchars($department['description']); ?></p>
                        <div class="mt-auto">
                            <span class="badge bg-primary me-2" style="padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 600; border-radius: 8px;"><?php echo htmlspecialchars($department['code']); ?></span>
                            <span class="text-muted" style="font-size: 1rem;"><i class="fas fa-user-tie me-2"></i>Head: <strong><?php echo htmlspecialchars($department['head']); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Tabs -->
    <div class="card mb-4 shadow-lg border-0 animate__animated animate__fadeInUp" style="animation-delay: 0.2s; border-radius: var(--border-radius); overflow: hidden;">
        <div class="card-header" style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border: none; padding: 1.5rem;">
            <ul class="nav nav-tabs card-header-tabs modern-tabs" id="departmentTabs" role="tablist" style="border: none;">
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
        <div class="card-body" style="padding: 2rem;">
            <div class="tab-content" id="departmentTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                    <h3 style="font-weight: 700; color: #2d3748; margin-bottom: 1.5rem; font-size: 1.5rem;"><i class="fas fa-graduation-cap me-2" style="color: #667eea;"></i>Programs Offered</h3>
                    <ul class="list-group mb-5">
                        <?php foreach ($department['programs'] as $program): ?>
                        <li class="list-group-item">
                            <i class="fas fa-graduation-cap me-2 text-primary"></i>
                            <?php echo htmlspecialchars($program); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h3 style="font-weight: 700; color: #2d3748; margin-bottom: 1.5rem; margin-top: 2rem; font-size: 1.5rem;"><i class="fas fa-phone-alt me-2" style="color: #667eea;"></i>Contact Information</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 p-4" style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                                <label class="form-label fw-bold" style="color: #2d3748; font-size: 1.1rem;"><i class="fas fa-envelope me-2" style="color: #667eea;"></i>Email:</label>
                                <div><a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($department['email']); ?>" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 500;"><?php echo htmlspecialchars($department['email']); ?></a></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 p-4" style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);">
                                <label class="form-label fw-bold" style="color: #2d3748; font-size: 1.1rem;"><i class="fas fa-phone me-2" style="color: #667eea;"></i>Phone:</label>
                                <div><a href="tel:<?php echo htmlspecialchars($department['phone']); ?>" style="color: #667eea; text-decoration: none; font-weight: 500;"><?php echo htmlspecialchars($department['phone']); ?></a></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Events Tab -->
                <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
                    <?php if (shouldUseAdminInterface()): ?>
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
                        <?php foreach ($department['events'] as $event): ?>
                        <div class="list-group-item event-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <?php if (shouldUseAdminInterface()): ?>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $event['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEventModal<?php echo $event['id']; ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <p class="mb-2" style="color: #4a5568; line-height: 1.6;"><?php echo htmlspecialchars($event['description']); ?></p>
                            <small class="text-muted" style="font-weight: 500;">
                                <i class="fas fa-calendar me-1" style="color: #667eea;"></i> <?php echo date('F j, Y', strtotime($event['date'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Contacts Tab -->
                <div class="tab-pane fade" id="contacts" role="tabpanel" aria-labelledby="contacts-tab">
                    <?php if (shouldUseAdminInterface()): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContactModal">
                            <i class="fas fa-plus me-2"></i> Add Contact
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php foreach ($department['contacts'] as $contact): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 contact-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title"><?php echo htmlspecialchars($contact['name']); ?></h5>
                                        <?php if (shouldUseAdminInterface()): ?>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editContactModal<?php echo $contact['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteContactModal<?php echo $contact['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($contact['position']); ?></h6>
                                    <p class="card-text" style="margin-bottom: 0.5rem;">
                                        <i class="fas fa-envelope me-2" style="color: #667eea;"></i>
                                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo htmlspecialchars($contact['email']); ?>" target="_blank" style="color: #4a5568; text-decoration: none;"><?php echo htmlspecialchars($contact['email']); ?></a>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-phone me-2" style="color: #667eea;"></i>
                                        <a href="tel:<?php echo htmlspecialchars($contact['phone']); ?>" style="color: #4a5568; text-decoration: none;"><?php echo htmlspecialchars($contact['phone']); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    <?php if (shouldUseAdminInterface()): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                            <i class="fas fa-plus me-2"></i> Add Document
                        </button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkUploadDocumentModal">
                            <i class="fas fa-upload me-2"></i> Bulk Upload
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
                                            <?php if (shouldUseAdminInterface()): ?>
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
                    <?php if (shouldUseAdminInterface()): ?>
                    <div class="mb-3 text-end">
                        <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addGalleryImageModal">
                            <i class="fas fa-plus me-2"></i> Add Image
                        </button>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkUploadGalleryModal">
                            <i class="fas fa-upload me-2"></i> Bulk Upload
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
                                <div class="card h-100 border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; transition: var(--transition-smooth);" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 50px rgba(0, 0, 0, 0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(0, 0, 0, 0.08)'">
                                    <div class="gallery-image-container">
                                        <img src="../<?php echo htmlspecialchars($image['path']); ?>" class="gallery-image" alt="Gallery Image">
                                        <div class="gallery-overlay">
                                            <button class="btn btn-light btn-sm view-image-btn" data-image="../<?php echo htmlspecialchars($image['path']); ?>" data-caption="<?php echo htmlspecialchars($image['caption'] ?? 'Department Gallery Image'); ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border: none; padding: 1rem;">
                                        <?php if (!empty($image['caption'])): ?>
                                        <small class="text-muted" style="font-weight: 500;"><?php echo htmlspecialchars($image['caption']); ?></small>
                                        <?php else: ?>
                                        <small class="text-muted" style="font-weight: 500;">Department Gallery Image</small>
                                        <?php endif; ?>

                                        <div>
                                            <a href="../<?php echo htmlspecialchars($image['path']); ?>" class="btn btn-sm btn-outline-primary" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php if (shouldUseAdminInterface()): ?>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteGalleryImageModal<?php echo $image['id']; ?>">
                                                <i class="fas fa-trash"></i>
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
                                    <div class="card h-100 border-0 shadow-lg" style="border-radius: 12px; overflow: hidden; transition: var(--transition-smooth);" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 20px 50px rgba(0, 0, 0, 0.2)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 30px rgba(0, 0, 0, 0.08)'">
                                        <div class="gallery-image-container">
                                            <img src="<?php echo $imagePath; ?>" class="gallery-image" alt="Gallery Image">
                                            <div class="gallery-overlay">
                                                <button class="btn btn-light btn-sm view-image-btn" data-image="<?php echo $imagePath; ?>" data-caption="Department Gallery Image">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-footer d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%); border: none; padding: 1rem;">
                                            <small class="text-muted" style="font-weight: 500;">Department Gallery Image</small>
                                            <div>
                                                <a href="<?php echo $imagePath; ?>" class="btn btn-sm btn-outline-primary" download>
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php if (shouldUseAdminInterface()): ?>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteGalleryImageModal<?php echo $index; ?>">
                                                    <i class="fas fa-trash"></i>
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



<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEventModalLabel">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
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
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
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

<!-- Edit Event Modals -->
<?php if (!empty($department['events'])): ?>
    <?php foreach ($department['events'] as $event): ?>
    <div class="modal fade" id="editEventModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="editEventModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEventModalLabel<?php echo $event['id']; ?>">Edit Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="edit_event">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="eventTitle<?php echo $event['id']; ?>" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="eventTitle<?php echo $event['id']; ?>" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="eventDate<?php echo $event['id']; ?>" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="eventDate<?php echo $event['id']; ?>" name="date" value="<?php echo htmlspecialchars($event['date']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="eventDescription<?php echo $event['id']; ?>" class="form-label">Event Description</label>
                            <textarea class="form-control" id="eventDescription<?php echo $event['id']; ?>" name="description" rows="3" required><?php echo htmlspecialchars($event['description']); ?></textarea>
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
    <?php endforeach; ?>

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
<?php endif; ?>

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
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
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

<!-- Edit Contact Modals -->
<?php if (!empty($department['contacts'])): ?>
    <?php foreach ($department['contacts'] as $contact): ?>
    <div class="modal fade" id="editContactModal<?php echo $contact['id']; ?>" tabindex="-1" aria-labelledby="editContactModalLabel<?php echo $contact['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editContactModalLabel<?php echo $contact['id']; ?>">Edit Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
                        <input type="hidden" name="action" value="edit_contact">
                        <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">
                        <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="contactName<?php echo $contact['id']; ?>" class="form-label">Name</label>
                            <input type="text" class="form-control" id="contactName<?php echo $contact['id']; ?>" name="name" value="<?php echo htmlspecialchars($contact['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contactPosition<?php echo $contact['id']; ?>" class="form-label">Position</label>
                            <input type="text" class="form-control" id="contactPosition<?php echo $contact['id']; ?>" name="position" value="<?php echo htmlspecialchars($contact['position']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contactEmail<?php echo $contact['id']; ?>" class="form-label">Email</label>
                            <input type="email" class="form-control" id="contactEmail<?php echo $contact['id']; ?>" name="email" value="<?php echo htmlspecialchars($contact['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="contactPhone<?php echo $contact['id']; ?>" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="contactPhone<?php echo $contact['id']; ?>" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>" required>
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
    <?php endforeach; ?>

<!-- Add Document Modal -->
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDocumentModalLabel">Add New Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
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
<?php endif; ?>

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
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
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
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
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
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
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
                    <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST">
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

<script>
// Toggle edit form visibility
function toggleEditForm() {
    const editSection = document.getElementById('editDepartmentSection');
    const isVisible = editSection.style.display !== 'none';

    if (isVisible) {
        editSection.style.display = 'none';
    } else {
        editSection.style.display = 'block';
        // Scroll to the edit form
        editSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Clean up any existing modal backdrops on page load
document.addEventListener('DOMContentLoaded', function() {
    // Remove any existing modal backdrops
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(backdrop => backdrop.remove());

    // Ensure body doesn't have modal-open class
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});
</script>

        </div>
    </div>
</div>

<!-- Bulk Upload Gallery Modal -->
<div class="modal fade" id="bulkUploadGalleryModal" tabindex="-1" aria-labelledby="bulkUploadGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadGalleryModalLabel">Bulk Upload Gallery Images</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="bulk_upload_gallery">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">

                    <div class="mb-3">
                        <label for="bulkGalleryImages" class="form-label">Select Multiple Images</label>
                        <input type="file" class="form-control" id="bulkGalleryImages" name="gallery_images[]" multiple required accept="image/jpeg,image/png,image/jpg">
                        <div class="form-text">Accepted formats: JPG, JPEG, PNG. Max file size per image: 5MB. You can select multiple images at once.</div>
                    </div>

                    <div class="mb-3">
                        <label for="bulkImageCaption" class="form-label">Default Caption (optional)</label>
                        <input type="text" class="form-control" id="bulkImageCaption" name="default_caption" placeholder="This caption will be applied to all uploaded images">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Upload Images</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Upload Document Modal -->
<div class="modal fade" id="bulkUploadDocumentModal" tabindex="-1" aria-labelledby="bulkUploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkUploadDocumentModalLabel">Bulk Upload Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="department_handler.php<?php echo shouldUseAdminInterface() ? '?admin=1' : ''; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="bulk_upload_documents">
                    <input type="hidden" name="department_code" value="<?php echo htmlspecialchars($departmentCode); ?>">

                    <div class="mb-3">
                        <label for="bulkDocuments" class="form-label">Select Multiple Documents</label>
                        <input type="file" class="form-control" id="bulkDocuments" name="documents[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT. Max file size per document: 10MB. You can select multiple documents at once.</div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Document titles will be automatically generated from the file names. You can edit individual document titles after upload.
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Upload Documents</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Image View Modal -->
<div id="imageViewModal" class="image-view-modal">
    <div class="image-view-content">
        <span class="image-view-close">&times;</span>
        <img id="viewModalImage" class="image-view-img" src="" alt="Gallery Image">
        <div id="viewModalCaption" class="image-view-caption"></div>
    </div>
</div>

<script>
// Image view functionality
document.addEventListener('DOMContentLoaded', function() {
    const imageViewModal = document.getElementById('imageViewModal');
    const viewModalImage = document.getElementById('viewModalImage');
    const viewModalCaption = document.getElementById('viewModalCaption');
    const closeBtn = document.querySelector('.image-view-close');

    // View image buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-image-btn')) {
            const btn = e.target.closest('.view-image-btn');
            const imageSrc = btn.getAttribute('data-image');
            const caption = btn.getAttribute('data-caption');

            viewModalImage.src = imageSrc;
            viewModalCaption.textContent = caption;
            imageViewModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });

    // Close modal
    function closeImageModal() {
        imageViewModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeImageModal);

    imageViewModal.addEventListener('click', function(e) {
        if (e.target === imageViewModal) {
            closeImageModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && imageViewModal.style.display === 'block') {
            closeImageModal();
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
