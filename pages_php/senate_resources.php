<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
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

// Check for admin status
$isAdmin = isAdmin();
$isMember = isMember();
$isSuperAdmin = isSuperAdmin();

// Page title
$pageTitle = "Senate Resources - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Senate Resources Header -->
<div class="senate-resources-header animate__animated animate__fadeInDown">
    <div class="senate-resources-header-content">
        <div class="senate-resources-header-main">
            <h1 class="senate-resources-title">
                <i class="fas fa-book me-3"></i>
                Senate Resources
            </h1>
            <p class="senate-resources-description">Access important Senate documents, constitution, and legislative materials</p>
        </div>
        <div class="senate-resources-header-actions">
            <a href="senate.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Senate
            </a>
        </div>
    </div>
</div>

<style>
.senate-resources-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.senate-resources-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.senate-resources-header-main {
    flex: 1;
    text-align: center;
}

.senate-resources-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.senate-resources-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.senate-resources-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.senate-resources-header-actions {
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
    .senate-resources-header {
        padding: 2rem 1.5rem;
    }

    .senate-resources-header-content {
        flex-direction: column;
        align-items: center;
    }

    .senate-resources-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .senate-resources-title i {
        font-size: 1.8rem;
    }

    .senate-resources-description {
        font-size: 1.1rem;
    }

    .senate-resources-header-actions {
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

/* Mobile Full-Width Optimization for Senate Resources Page */
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
    
    /* Ensure page header has border-radius on mobile */
    .header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card, .resource-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid px-4">

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

    <!-- Back to Senate Button -->
    <div class="mb-4">
        <a href="senate.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Senate
        </a>
    </div>

    <!-- Senate Resources Overview -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h4 class="content-card-title"><i class="fas fa-book me-2"></i>Senate Resources</h4>
        </div>
        <div class="content-card-body">
            <p class="lead">Access important documents, information, and resources related to the Student Representative Council Senate.</p>
            
            <div class="row mt-4">
                <!-- VVUSRC Constitution -->
                <div class="col-md-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body text-center">
                            <div class="resource-icon mb-3">
                                <i class="fas fa-file-pdf text-danger fa-4x"></i>
                            </div>
                            <h5 class="card-title">VVUSRC Constitution</h5>
                            <p class="card-text">The official governing document of the Student Representative Council</p>
                            <a href="src_constitution.php" class="btn btn-primary">View Constitution</a>
                        </div>
                    </div>
                </div>
                
                <!-- Senate Bylaws -->
                <div class="col-md-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body text-center">
                            <div class="resource-icon mb-3">
                                <i class="fas fa-file-alt text-info fa-4x"></i>
                            </div>
                            <h5 class="card-title">Senate Bylaws</h5>
                            <p class="card-text">Rules and procedures governing Senate operations</p>
                            <a href="documents.php?category=bylaws" class="btn btn-primary">View Bylaws</a>
                        </div>
                    </div>
                </div>
                
                <!-- Meeting Minutes -->
                <div class="col-md-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body text-center">
                            <div class="resource-icon mb-3">
                                <i class="fas fa-clipboard-list text-success fa-4x"></i>
                            </div>
                            <h5 class="card-title">Meeting Minutes</h5>
                            <p class="card-text">Records of previous Senate meetings and discussions</p>
                            <?php if ($isSuperAdmin || $isAdmin || $isMember): ?>
                            <a href="minutes.php?committee=Senate" class="btn btn-primary">View Minutes</a>
                            <?php else: ?>
                            <button class="btn btn-secondary" disabled>Restricted Access</button>
                            <p class="small text-muted mt-2">Only SRC members can access minutes</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Legislation Archive -->
                <div class="col-md-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body text-center">
                            <div class="resource-icon mb-3">
                                <i class="fas fa-gavel text-warning fa-4x"></i>
                            </div>
                            <h5 class="card-title">Legislation Archive</h5>
                            <p class="card-text">Browse past bills, resolutions, and acts passed by the Senate</p>
                            <a href="documents.php?category=legislation" class="btn btn-primary">View Legislation</a>
                        </div>
                    </div>
                </div>
                
                <!-- Senate Committees -->
                <div class="col-md-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body text-center">
                            <div class="resource-icon mb-3">
                                <i class="fas fa-users text-primary fa-4x"></i>
                            </div>
                            <h5 class="card-title">Senate Committees</h5>
                            <p class="card-text">Information about standing and ad hoc committees</p>
                            <a href="committees.php" class="btn btn-primary">View Committees</a>
                        </div>
                    </div>
                </div>
                
                <!-- FAQs -->
                <div class="col-md-4 mb-4">
                    <div class="card resource-card h-100">
                        <div class="card-body text-center">
                            <div class="resource-icon mb-3">
                                <i class="fas fa-question-circle text-secondary fa-4x"></i>
                            </div>
                            <h5 class="card-title">FAQs</h5>
                            <p class="card-text">Frequently asked questions about the Senate</p>
                            <a href="senate.php#faqs" class="btn btn-primary">View FAQs</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Resource Cards Styling */
.resource-card {
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}

.resource-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

.resource-icon {
    margin: 0 auto;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.content-card {
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    background-color: #fff;
    transition: all 0.3s ease;
}

.content-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.content-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    background-color: rgba(0, 0, 0, 0.03);
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

.content-card-body {
    padding: 1.5rem;
}

.content-card-title {
    margin-bottom: 0;
    font-weight: 600;
    color: #343a40;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
