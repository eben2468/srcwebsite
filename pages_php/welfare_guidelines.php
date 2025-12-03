<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Require login for this page
requireLogin();

// Check if welfare feature is enabled
if (!hasFeaturePermission('enable_welfare')) {
    header('Location: ../dashboard.php?error=feature_disabled');
    exit();
}

// Check user roles - use unified admin interface check for super admin
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$isStudent = !$isAdmin && !$isMember;

// Set page title
$pageTitle = "Welfare Guidelines - SRC Management System";
$bodyClass = "page-welfare-guidelines";

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<style>
.welfare-guidelines-container {
    background: #f8f9fa;
    min-height: calc(100vh - 200px);
}

.guidelines-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    margin-top: 60px;
    margin-bottom: 2rem;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.guidelines-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.guidelines-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.section-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.guideline-item {
    background: #f8f9fa;
    border-left: 4px solid #667eea;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    border-radius: 0 8px 8px 0;
}

.process-step {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    position: relative;
    transition: all 0.3s ease;
}

.process-step:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
}

.step-number {
    position: absolute;
    top: -15px;
    left: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.contact-info {
    background: transparent;
    color: #333;
    border: none;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
}

.contact-info h4,
.contact-info h6 {
    color: #333;
}

.contact-info p {
    color: #555;
}

.contact-info i {
    color: #667eea;
}

.contact-info .btn-light {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.contact-info .btn-light:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: white;
}

/* Desktop spacing - only on larger screens */
@media (min-width: 992px) {
    .container-fluid {
        padding-left: 2rem;
        padding-right: 2rem;
    }
}

.back-button {
    position: absolute;
    top: 20px;
    right: 20px;
}


/* Mobile Full-Width Optimization */
@media (max-width: 991px) {
    /* Remove all column padding on mobile */
    [class*="col-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove row margin */
    .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    /* Adjust cards for full width on mobile */
    .guidelines-card {
        border-radius: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    /* Add border radius to header on mobile */
    .guidelines-header {
        border-radius: 12px !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    
    /* Contact info card full width */
    .contact-info {
        border-radius: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
}

@media (max-width: 768px) {
    .guidelines-header {
        padding: 2rem 1rem;
        margin-top: 80px;
    }
    
    .guidelines-card {
        padding: 1.5rem;
    }
    
    .back-button {
        position: relative;
        top: auto;
        right: auto;
        margin-bottom: 1rem;
    }
}
</style>

<div class="welfare-guidelines-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="guidelines-header position-relative">
            <!-- Back Button -->
            <div class="back-button">
                <a href="welfare.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Welfare
                </a>
            </div>
            
            <div class="text-center">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-book-open me-3"></i>Welfare Guidelines
                </h1>
                <p class="lead">Comprehensive guide to student welfare services and procedures</p>
            </div>
        </div>

        <div class="row">
            <!-- Overview Section -->
            <div class="col-lg-6 mb-4">
                <div class="guidelines-card">
                    <div class="section-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="mb-3">What is Student Welfare?</h3>
                    <p class="text-muted mb-3">
                        Student welfare encompasses all services and support systems designed to ensure the well-being, 
                        safety, and academic success of students within the university community.
                    </p>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-heart text-danger me-2"></i>Health & Medical Support</h6>
                        <p class="mb-0 small">Access to medical care, mental health services, and emergency assistance.</p>
                    </div>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-graduation-cap text-primary me-2"></i>Academic Support</h6>
                        <p class="mb-0 small">Tutoring, study groups, and academic counseling services.</p>
                    </div>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-dollar-sign text-success me-2"></i>Financial Assistance</h6>
                        <p class="mb-0 small">Emergency funds, scholarships, and financial counseling.</p>
                    </div>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-home text-warning me-2"></i>Accommodation Support</h6>
                        <p class="mb-0 small">Housing assistance and dormitory-related services.</p>
                    </div>
                </div>
            </div>

            <!-- Eligibility Section -->
            <div class="col-lg-6 mb-4">
                <div class="guidelines-card">
                    <div class="section-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 class="mb-3">Eligibility Criteria</h3>
                    <p class="text-muted mb-3">
                        To access welfare services, students must meet the following requirements:
                    </p>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-id-card text-info me-2"></i>Valid Student Status</h6>
                        <p class="mb-0 small">Must be a currently enrolled student with valid student ID.</p>
                    </div>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-file-alt text-secondary me-2"></i>Complete Documentation</h6>
                        <p class="mb-0 small">All required forms and supporting documents must be submitted.</p>
                    </div>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-clock text-warning me-2"></i>Timely Application</h6>
                        <p class="mb-0 small">Requests must be submitted within specified deadlines.</p>
                    </div>
                    
                    <div class="guideline-item">
                        <h6><i class="fas fa-check-circle text-success me-2"></i>Good Standing</h6>
                        <p class="mb-0 small">Student must be in good academic and disciplinary standing.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Process -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="guidelines-card">
                    <div class="section-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="mb-4">Application Process</h3>
                    <p class="text-muted mb-4">
                        Follow these steps to submit a welfare request:
                    </p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="process-step">
                                <div class="step-number">1</div>
                                <h6 class="mt-2">Submit Request</h6>
                                <p class="mb-0 small text-muted">
                                    Complete the welfare request form with accurate information and supporting documents.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="process-step">
                                <div class="step-number">2</div>
                                <h6 class="mt-2">Initial Review</h6>
                                <p class="mb-0 small text-muted">
                                    Welfare committee reviews your application for completeness and eligibility.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="process-step">
                                <div class="step-number">3</div>
                                <h6 class="mt-2">Assessment</h6>
                                <p class="mb-0 small text-muted">
                                    Detailed assessment of your needs and available resources.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="process-step">
                                <div class="step-number">4</div>
                                <h6 class="mt-2">Decision & Support</h6>
                                <p class="mb-0 small text-muted">
                                    Final decision communicated and support services provided if approved.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Types of Support -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="guidelines-card">
                    <div class="section-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3 class="mb-4">Types of Support Available</h3>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="guideline-item">
                                <h6><i class="fas fa-ambulance text-danger me-2"></i>Emergency Assistance</h6>
                                <ul class="small mb-0">
                                    <li>Medical emergencies</li>
                                    <li>Financial crisis support</li>
                                    <li>Emergency accommodation</li>
                                    <li>Crisis counseling</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="guideline-item">
                                <h6><i class="fas fa-utensils text-warning me-2"></i>Food & Nutrition</h6>
                                <ul class="small mb-0">
                                    <li>Meal vouchers</li>
                                    <li>Food bank access</li>
                                    <li>Nutritional counseling</li>
                                    <li>Special dietary support</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="guideline-item">
                                <h6><i class="fas fa-brain text-info me-2"></i>Mental Health</h6>
                                <ul class="small mb-0">
                                    <li>Counseling services</li>
                                    <li>Stress management</li>
                                    <li>Peer support groups</li>
                                    <li>Mental health workshops</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="guideline-item">
                                <h6><i class="fas fa-laptop text-primary me-2"></i>Technology Support</h6>
                                <ul class="small mb-0">
                                    <li>Device lending program</li>
                                    <li>Internet connectivity</li>
                                    <li>Software access</li>
                                    <li>Technical training</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Information -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="guidelines-card">
                    <div class="section-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="mb-3">Important Information</h3>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-clock me-2"></i>Processing Times</h6>
                        <p class="mb-0">Emergency requests: 24-48 hours | Regular requests: 5-7 business days</p>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-shield-alt me-2"></i>Confidentiality</h6>
                        <p class="mb-0">All welfare requests are treated with strict confidentiality and handled by authorized personnel only.</p>
                    </div>

                    <div class="alert alert-success">
                        <h6><i class="fas fa-redo me-2"></i>Appeals Process</h6>
                        <p class="mb-0">If your request is denied, you have the right to appeal within 14 days of the decision.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="contact-info">
                    <h4 class="mb-3">
                        <i class="fas fa-phone me-2"></i>Contact Information
                    </h4>

                    <div class="mb-3">
                        <h6>Welfare Office</h6>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i>welfare@vvu.edu.gh</p>
                        <p class="mb-1"><i class="fas fa-phone me-2"></i>+233 XX XXX XXXX</p>
                        <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Student Affairs Building</p>
                    </div>

                    <div class="mb-3">
                        <h6>Office Hours</h6>
                        <p class="mb-1">Monday - Friday: 8:00 AM - 5:00 PM</p>
                        <p class="mb-0">Emergency: 24/7 Hotline</p>
                    </div>

                    <a href="welfare.php" class="btn btn-light btn-sm">
                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
