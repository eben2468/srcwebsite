<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Set page title
$pageTitle = "Terms of Service - " . $siteName;
$bodyClass = "page-terms-of-service";

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Terms of Service Header -->
<div class="terms-of-service-header animate__animated animate__fadeInDown">
    <div class="terms-of-service-header-content">
        <div class="terms-of-service-header-main">
            <h1 class="terms-of-service-title">
                <i class="fas fa-file-contract me-3"></i>
                Terms of Service
            </h1>
            <p class="terms-of-service-description">Important terms and conditions for using our system</p>
        </div>
        <div class="terms-of-service-header-actions">
            <a href="privacy-policy.php" class="btn btn-header-action">
                <i class="fas fa-shield-alt me-2"></i>Privacy Policy
            </a>
            <a href="dashboard.php" class="btn btn-header-action">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>
</div>

<style>
.terms-of-service-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.terms-of-service-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.terms-of-service-header-main {
    flex: 1;
    text-align: center;
}

.terms-of-service-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.terms-of-service-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.terms-of-service-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.terms-of-service-header-actions {
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
    .terms-of-service-header {
        padding: 2rem 1.5rem;
    }

    .terms-of-service-header-content {
        flex-direction: column;
        align-items: center;
    }

    .terms-of-service-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .terms-of-service-title i {
        font-size: 1.8rem;
    }

    .terms-of-service-description {
        font-size: 1.1rem;
    }

    .terms-of-service-header-actions {
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

/* Mobile Full-Width Optimization for Terms of Service Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"], [class*="col-xl-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .terms-header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="terms-content">
                        <div class="mb-4">
                            <p class="lead">
                                Welcome to the <?php echo $siteName; ?>. By accessing and using this system, you agree to comply with and be bound by the following terms and conditions.
                            </p>
                            <div class="alert alert-warning">
                                <strong>Important:</strong> Please read these terms carefully before using the system. Your use of the system constitutes acceptance of these terms.
                            </div>
                        </div>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-user-check text-primary me-2"></i>Acceptance of Terms</h3>
                            <p>
                                By creating an account and using the SRC Management System, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service and our Privacy Policy. If you do not agree to these terms, you must not use the system.
                            </p>
                            <ul>
                                <li>You must be a currently enrolled student at the university</li>
                                <li>You must provide accurate and complete information</li>
                                <li>You are responsible for maintaining the confidentiality of your account</li>
                                <li>You must comply with all university policies and regulations</li>
                            </ul>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-tasks text-success me-2"></i>Permitted Uses</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Authorized Activities</h5>
                                    <ul>
                                        <li>Accessing SRC documents and information</li>
                                        <li>Participating in SRC events and activities</li>
                                        <li>Viewing financial reports and budgets</li>
                                        <li>Communicating with SRC members</li>
                                        <li>Uploading approved content to galleries</li>
                                        <li>Submitting feedback and suggestions</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Administrative Functions (Authorized Users)</h5>
                                    <ul>
                                        <li>Managing user accounts and permissions</li>
                                        <li>Creating and editing SRC content</li>
                                        <li>Processing financial transactions</li>
                                        <li>Generating reports and analytics</li>
                                        <li>Managing events and announcements</li>
                                        <li>System configuration and maintenance</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-ban text-danger me-2"></i>Prohibited Activities</h3>
                            <div class="alert alert-danger">
                                <strong>Violation of these terms may result in immediate account suspension or termination.</strong>
                            </div>
                            <ul>
                                <li><strong>Unauthorized Access:</strong> Attempting to access areas or functions you're not authorized to use</li>
                                <li><strong>Data Misuse:</strong> Downloading, copying, or sharing confidential information without permission</li>
                                <li><strong>System Abuse:</strong> Attempting to hack, disrupt, or damage the system</li>
                                <li><strong>False Information:</strong> Providing false or misleading information</li>
                                <li><strong>Harassment:</strong> Using the system to harass, threaten, or intimidate other users</li>
                                <li><strong>Spam:</strong> Sending unsolicited messages or content</li>
                                <li><strong>Copyright Violation:</strong> Uploading copyrighted material without permission</li>
                                <li><strong>Commercial Use:</strong> Using the system for commercial purposes without authorization</li>
                            </ul>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-shield-alt text-info me-2"></i>User Responsibilities</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Account Security</h5>
                                    <ul>
                                        <li>Use strong, unique passwords</li>
                                        <li>Log out when using shared computers</li>
                                        <li>Report suspicious activity immediately</li>
                                        <li>Keep contact information up to date</li>
                                        <li>Do not share account credentials</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Content Guidelines</h5>
                                    <ul>
                                        <li>Ensure all uploaded content is appropriate</li>
                                        <li>Respect intellectual property rights</li>
                                        <li>Use professional language in communications</li>
                                        <li>Verify accuracy of information before sharing</li>
                                        <li>Follow university code of conduct</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>System Availability and Limitations</h3>
                            <p>While we strive to maintain high system availability, we cannot guarantee uninterrupted service.</p>
                            <ul>
                                <li><strong>Maintenance:</strong> Scheduled maintenance may temporarily limit access</li>
                                <li><strong>Technical Issues:</strong> Unexpected technical problems may occur</li>
                                <li><strong>Updates:</strong> System updates may introduce temporary limitations</li>
                                <li><strong>Force Majeure:</strong> Events beyond our control may affect service</li>
                            </ul>
                            <div class="alert alert-info">
                                <strong>Service Level:</strong> We aim for 99% uptime during academic periods, with maintenance windows scheduled during low-usage periods.
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-balance-scale text-secondary me-2"></i>Intellectual Property</h3>
                            <p>The SRC Management System and its content are protected by intellectual property laws.</p>
                            <ul>
                                <li><strong>System Code:</strong> Proprietary software owned by the university</li>
                                <li><strong>User Content:</strong> You retain rights to content you create, but grant us license to use it within the system</li>
                                <li><strong>SRC Content:</strong> Official SRC documents and materials are owned by the Student Representative Council</li>
                                <li><strong>Third-Party Content:</strong> Respect all third-party intellectual property rights</li>
                            </ul>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-gavel text-primary me-2"></i>Enforcement and Violations</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Violation Consequences</h5>
                                    <ul>
                                        <li><strong>Warning:</strong> First-time minor violations</li>
                                        <li><strong>Temporary Suspension:</strong> Repeated or moderate violations</li>
                                        <li><strong>Account Termination:</strong> Serious or repeated violations</li>
                                        <li><strong>Legal Action:</strong> Criminal activities or severe policy violations</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Appeal Process</h5>
                                    <ul>
                                        <li>Submit appeal within 7 days of action</li>
                                        <li>Provide detailed explanation and evidence</li>
                                        <li>Appeals reviewed by SRC Executive Committee</li>
                                        <li>Decision communicated within 14 days</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-edit text-success me-2"></i>Changes to Terms</h3>
                            <p>
                                We reserve the right to modify these Terms of Service at any time. Changes will be effective immediately upon posting to the system. Continued use of the system after changes constitutes acceptance of the new terms.
                            </p>
                            <div class="alert alert-info">
                                <strong>Notification:</strong> Significant changes will be announced through the system notification system and email.
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-phone text-info me-2"></i>Contact Information</h3>
                            <p>For questions about these Terms of Service or to report violations:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <h6>Technical Support</h6>
                                    <p>
                                        <i class="fas fa-envelope me-2"></i>support@src.university.edu<br>
                                        <i class="fas fa-phone me-2"></i>+233 (0) 123 456 7890
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6>SRC Executive</h6>
                                    <p>
                                        <i class="fas fa-envelope me-2"></i>executive@src.university.edu<br>
                                        <i class="fas fa-map-marker-alt me-2"></i>Student Union Building
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <h6>Legal/Compliance</h6>
                                    <p>
                                        <i class="fas fa-envelope me-2"></i>legal@university.edu<br>
                                        <i class="fas fa-clock me-2"></i>Mon-Fri, 9AM-5PM
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="text-center">
                            <div class="alert alert-success">
                                <strong>Effective Date:</strong> <?php echo date('F j, Y'); ?> | <strong>Version:</strong> 2.1.0
                            </div>
                            <div class="mt-4">
                                <a href="privacy-policy.php" class="btn btn-outline-primary me-3">
                                    <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                                </a>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.terms-content h3 {
    color: var(--primary-color);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 10px;
}

.terms-content section {
    padding: 20px 0;
}

.terms-content ul {
    padding-left: 20px;
}

.terms-content li {
    margin-bottom: 8px;
}

.terms-content .alert {
    border-left: 4px solid;
    border-radius: 0;
}

.terms-content .alert-danger {
    border-left-color: #dc3545;
}

.terms-content .alert-warning {
    border-left-color: #ffc107;
}

.terms-content .alert-info {
    border-left-color: #17a2b8;
}

.terms-content .alert-success {
    border-left-color: #28a745;
}
</style>

<?php require_once 'includes/footer.php'; ?>
