<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Set page title
$pageTitle = "Privacy Policy - " . $siteName;
$bodyClass = "page-privacy-policy";

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Privacy Policy Header -->
<div class="privacy-policy-header animate__animated animate__fadeInDown">
    <div class="privacy-policy-header-content">
        <div class="privacy-policy-header-main">
            <h1 class="privacy-policy-title">
                <i class="fas fa-shield-alt me-3"></i>
                Privacy Policy
            </h1>
            <p class="privacy-policy-description">Learn how we protect and handle your personal information</p>
        </div>
        <div class="privacy-policy-header-actions">
            <a href="terms-of-service.php" class="btn btn-header-action">
                <i class="fas fa-file-contract me-2"></i>Terms of Service
            </a>
            <a href="dashboard.php" class="btn btn-header-action">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
        </div>
    </div>
</div>

<style>
.privacy-policy-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.privacy-policy-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.privacy-policy-header-main {
    flex: 1;
    text-align: center;
}

.privacy-policy-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.privacy-policy-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.privacy-policy-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.privacy-policy-header-actions {
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
    .privacy-policy-header {
        padding: 2rem 1.5rem;
    }

    .privacy-policy-header-content {
        flex-direction: column;
        align-items: center;
    }

    .privacy-policy-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .privacy-policy-title i {
        font-size: 1.8rem;
    }

    .privacy-policy-description {
        font-size: 1.1rem;
    }

    .privacy-policy-header-actions {
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

/* Mobile Full-Width Optimization for Privacy Policy Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"], [class*="col-xl-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .privacy-policy-header, .page-hero, .modern-page-header {
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
                    <div class="privacy-policy-content">
                        <div class="mb-4">
                            <p class="lead">
                                This Privacy Policy describes how the <?php echo $siteName; ?> ("we," "our," or "us") collects, uses, and protects your personal information when you use our Student Representative Council management system.
                            </p>
                        </div>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-info-circle text-primary me-2"></i>Information We Collect</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Personal Information</h5>
                                    <ul>
                                        <li>Full name and student ID</li>
                                        <li>Email address and phone number</li>
                                        <li>Academic program and year of study</li>
                                        <li>Profile picture (optional)</li>
                                        <li>SRC position and role information</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>System Usage Data</h5>
                                    <ul>
                                        <li>Login and activity logs</li>
                                        <li>Document access and downloads</li>
                                        <li>Event participation records</li>
                                        <li>Financial transaction records</li>
                                        <li>System preferences and settings</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-shield-alt text-success me-2"></i>How We Use Your Information</h3>
                            <div class="alert alert-info">
                                <strong>Primary Purpose:</strong> To facilitate effective Student Representative Council operations and governance.
                            </div>
                            <ul>
                                <li><strong>Account Management:</strong> Creating and maintaining your user account</li>
                                <li><strong>Communication:</strong> Sending important SRC updates and notifications</li>
                                <li><strong>Event Management:</strong> Managing event registrations and attendance</li>
                                <li><strong>Financial Transparency:</strong> Tracking and reporting SRC financial activities</li>
                                <li><strong>Document Management:</strong> Providing access to relevant SRC documents</li>
                                <li><strong>System Security:</strong> Monitoring for unauthorized access and maintaining system integrity</li>
                            </ul>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-lock text-warning me-2"></i>Data Protection and Security</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Security Measures</h5>
                                    <ul>
                                        <li>Encrypted data transmission (HTTPS)</li>
                                        <li>Secure password requirements</li>
                                        <li>Regular security audits</li>
                                        <li>Access control and user permissions</li>
                                        <li>Automated backup systems</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Data Retention</h5>
                                    <ul>
                                        <li>Active user data: Duration of enrollment</li>
                                        <li>Financial records: 7 years (legal requirement)</li>
                                        <li>Event records: 3 years</li>
                                        <li>System logs: 1 year</li>
                                        <li>Inactive accounts: 2 years after graduation</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-share-alt text-info me-2"></i>Information Sharing</h3>
                            <div class="alert alert-warning">
                                <strong>Important:</strong> We do not sell, trade, or rent your personal information to third parties.
                            </div>
                            <p>We may share your information only in the following circumstances:</p>
                            <ul>
                                <li><strong>University Administration:</strong> As required for official SRC reporting</li>
                                <li><strong>Legal Compliance:</strong> When required by law or university policy</li>
                                <li><strong>Emergency Situations:</strong> To protect the safety of students or university property</li>
                                <li><strong>Service Providers:</strong> With trusted third-party services that help us operate the system (under strict confidentiality agreements)</li>
                            </ul>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-user-check text-primary me-2"></i>Your Rights</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Access and Control</h5>
                                    <ul>
                                        <li>View and update your personal information</li>
                                        <li>Download your data (data portability)</li>
                                        <li>Request account deactivation</li>
                                        <li>Opt-out of non-essential communications</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Contact Us</h5>
                                    <p>To exercise your rights or ask questions about this policy:</p>
                                    <ul>
                                        <li><strong>Email:</strong> privacy@src.university.edu</li>
                                        <li><strong>Phone:</strong> +233 (0) 123 456 7890</li>
                                        <li><strong>Office:</strong> Student Union Building, Room 201</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-cookie-bite text-secondary me-2"></i>Cookies and Tracking</h3>
                            <p>We use cookies and similar technologies to:</p>
                            <ul>
                                <li>Keep you logged in during your session</li>
                                <li>Remember your preferences and settings</li>
                                <li>Analyze system usage to improve performance</li>
                                <li>Ensure system security and prevent fraud</li>
                            </ul>
                            <p>You can control cookie settings through your browser, but some features may not work properly if cookies are disabled.</p>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-edit text-success me-2"></i>Policy Updates</h3>
                            <p>
                                We may update this Privacy Policy from time to time to reflect changes in our practices or legal requirements. 
                                We will notify users of significant changes through the system notification system and email.
                            </p>
                            <div class="alert alert-info">
                                <strong>Current Version:</strong> 2.1.0 | <strong>Effective Date:</strong> <?php echo date('F j, Y'); ?>
                            </div>
                        </section>

                        <section class="text-center">
                            <h3 class="h4 mb-3">Questions or Concerns?</h3>
                            <p>If you have any questions about this Privacy Policy or our data practices, please don't hesitate to contact us.</p>
                            <div class="mt-4">
                                <a href="contact-support.php" class="btn btn-primary me-3">
                                    <i class="fas fa-envelope me-2"></i>Contact Support
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
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
.privacy-policy-content h3 {
    color: var(--primary-color);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 10px;
}

.privacy-policy-content section {
    padding: 20px 0;
}

.privacy-policy-content ul {
    padding-left: 20px;
}

.privacy-policy-content li {
    margin-bottom: 8px;
}

.privacy-policy-content .alert {
    border-left: 4px solid;
    border-radius: 0;
}

.privacy-policy-content .alert-info {
    border-left-color: #17a2b8;
}

.privacy-policy-content .alert-warning {
    border-left-color: #ffc107;
}
</style>

<?php require_once 'includes/footer.php'; ?>
