<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Set page title
$pageTitle = "Cookie Policy - " . $siteName;
$bodyClass = "page-cookie-policy";

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Cookie Policy Header -->
<div class="cookie-policy-header animate__animated animate__fadeInDown">
    <div class="cookie-policy-header-content">
        <div class="cookie-policy-header-main">
            <h1 class="cookie-policy-title">
                <i class="fas fa-cookie-bite me-3"></i>
                Cookie Policy
            </h1>
            <p class="cookie-policy-description">Learn how we use cookies to enhance your experience</p>
        </div>
        <div class="cookie-policy-header-actions">
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
.cookie-policy-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.cookie-policy-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.cookie-policy-header-main {
    flex: 1;
    text-align: center;
}

.cookie-policy-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.cookie-policy-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.cookie-policy-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.cookie-policy-header-actions {
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
    .cookie-policy-header {
        padding: 2rem 1.5rem;
    }

    .cookie-policy-header-content {
        flex-direction: column;
        align-items: center;
    }

    .cookie-policy-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .cookie-policy-title i {
        font-size: 1.8rem;
    }

    .cookie-policy-description {
        font-size: 1.1rem;
    }

    .cookie-policy-header-actions {
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

/* Mobile Full-Width Optimization for Cookie Policy Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"], [class*="col-xl-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .cookie-policy-header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    .card, .cookie-category {
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
                    <div class="cookie-policy-content">
                        <div class="mb-4">
                            <p class="lead">
                                This Cookie Policy explains how the <?php echo $siteName; ?> uses cookies and similar technologies to enhance your experience and improve our services.
                            </p>
                            <div class="alert alert-info">
                                <strong>Quick Summary:</strong> We use cookies to keep you logged in, remember your preferences, and improve system performance. You can control cookie settings in your browser.
                            </div>
                        </div>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-cookie-bite text-primary me-2"></i>What Are Cookies?</h3>
                            <p>
                                Cookies are small text files that are stored on your device when you visit our website. They help us provide you with a better experience by remembering your preferences and enabling certain functionality.
                            </p>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Types of Cookies</h5>
                                    <ul>
                                        <li><strong>Session Cookies:</strong> Temporary cookies that expire when you close your browser</li>
                                        <li><strong>Persistent Cookies:</strong> Cookies that remain on your device for a set period</li>
                                        <li><strong>First-Party Cookies:</strong> Set by our website directly</li>
                                        <li><strong>Third-Party Cookies:</strong> Set by external services we use</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Cookie Categories</h5>
                                    <ul>
                                        <li><strong>Essential:</strong> Required for basic functionality</li>
                                        <li><strong>Functional:</strong> Remember your preferences</li>
                                        <li><strong>Analytics:</strong> Help us understand usage patterns</li>
                                        <li><strong>Security:</strong> Protect against fraud and abuse</li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-cogs text-success me-2"></i>How We Use Cookies</h3>
                            
                            <div class="cookie-category mb-4">
                                <h5 class="text-success"><i class="fas fa-check-circle me-2"></i>Essential Cookies (Always Active)</h5>
                                <p>These cookies are necessary for the website to function and cannot be switched off.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Cookie Name</th>
                                                <th>Purpose</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>PHPSESSID</code></td>
                                                <td>Maintains your login session</td>
                                                <td>Session</td>
                                            </tr>
                                            <tr>
                                                <td><code>csrf_token</code></td>
                                                <td>Protects against cross-site request forgery</td>
                                                <td>Session</td>
                                            </tr>
                                            <tr>
                                                <td><code>auth_remember</code></td>
                                                <td>Remembers your login (if enabled)</td>
                                                <td>30 days</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="cookie-category mb-4">
                                <h5 class="text-info"><i class="fas fa-user-cog me-2"></i>Functional Cookies</h5>
                                <p>These cookies enable enhanced functionality and personalization.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Cookie Name</th>
                                                <th>Purpose</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>user_preferences</code></td>
                                                <td>Stores your dashboard layout and theme preferences</td>
                                                <td>1 year</td>
                                            </tr>
                                            <tr>
                                                <td><code>language_setting</code></td>
                                                <td>Remembers your language preference</td>
                                                <td>1 year</td>
                                            </tr>
                                            <tr>
                                                <td><code>notification_settings</code></td>
                                                <td>Stores your notification preferences</td>
                                                <td>6 months</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="cookie-category mb-4">
                                <h5 class="text-warning"><i class="fas fa-chart-line me-2"></i>Analytics Cookies</h5>
                                <p>These cookies help us understand how you use the system to improve performance.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Cookie Name</th>
                                                <th>Purpose</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>usage_analytics</code></td>
                                                <td>Tracks page views and feature usage</td>
                                                <td>2 years</td>
                                            </tr>
                                            <tr>
                                                <td><code>performance_metrics</code></td>
                                                <td>Monitors system performance and load times</td>
                                                <td>1 year</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="cookie-category mb-4">
                                <h5 class="text-danger"><i class="fas fa-shield-alt me-2"></i>Security Cookies</h5>
                                <p>These cookies help protect the system and your account from security threats.</p>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Cookie Name</th>
                                                <th>Purpose</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>security_token</code></td>
                                                <td>Validates legitimate requests</td>
                                                <td>Session</td>
                                            </tr>
                                            <tr>
                                                <td><code>login_attempts</code></td>
                                                <td>Tracks failed login attempts for security</td>
                                                <td>1 hour</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-sliders-h text-info me-2"></i>Managing Your Cookie Preferences</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Browser Settings</h5>
                                    <p>You can control cookies through your browser settings:</p>
                                    <ul>
                                        <li><strong>Chrome:</strong> Settings > Privacy and Security > Cookies</li>
                                        <li><strong>Firefox:</strong> Options > Privacy & Security > Cookies</li>
                                        <li><strong>Safari:</strong> Preferences > Privacy > Cookies</li>
                                        <li><strong>Edge:</strong> Settings > Cookies and Site Permissions</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>System Settings</h5>
                                    <p>You can also manage some cookie preferences within the system:</p>
                                    <ul>
                                        <li>Go to your Profile Settings</li>
                                        <li>Navigate to Privacy Preferences</li>
                                        <li>Adjust cookie and tracking settings</li>
                                        <li>Save your preferences</li>
                                    </ul>
                                    <div class="mt-3">
                                        <a href="profile-settings.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-cog me-2"></i>Manage Preferences
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3">
                                <strong>Important:</strong> Disabling essential cookies may prevent the system from working properly. Some features may become unavailable.
                            </div>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-external-link-alt text-secondary me-2"></i>Third-Party Services</h3>
                            <p>We use some third-party services that may set their own cookies:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>External Services</h5>
                                    <ul>
                                        <li><strong>CDN Services:</strong> For faster content delivery</li>
                                        <li><strong>Font Services:</strong> For web fonts and icons</li>
                                        <li><strong>Security Services:</strong> For DDoS protection</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Analytics (If Enabled)</h5>
                                    <ul>
                                        <li><strong>Usage Analytics:</strong> Anonymous usage statistics</li>
                                        <li><strong>Performance Monitoring:</strong> System performance tracking</li>
                                        <li><strong>Error Tracking:</strong> Bug and error reporting</li>
                                    </ul>
                                </div>
                            </div>
                            <p>These services have their own privacy policies and cookie practices. We recommend reviewing their policies if you have concerns.</p>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-mobile-alt text-primary me-2"></i>Mobile and App Usage</h3>
                            <p>When accessing the system through mobile browsers or apps:</p>
                            <ul>
                                <li>Similar cookie principles apply</li>
                                <li>Some functionality may use local storage instead of cookies</li>
                                <li>Mobile browsers have their own cookie management settings</li>
                                <li>App versions may use different tracking technologies</li>
                            </ul>
                        </section>

                        <section class="mb-5">
                            <h3 class="h4 mb-3"><i class="fas fa-sync-alt text-success me-2"></i>Updates to This Policy</h3>
                            <p>
                                We may update this Cookie Policy from time to time to reflect changes in our practices or legal requirements. 
                                We will notify users of significant changes through the system.
                            </p>
                            <div class="alert alert-info">
                                <strong>Current Version:</strong> 2.1.0 | <strong>Last Updated:</strong> <?php echo date('F j, Y'); ?>
                            </div>
                        </section>

                        <section class="text-center">
                            <h3 class="h4 mb-3">Need Help?</h3>
                            <p>If you have questions about our use of cookies or need help managing your preferences:</p>
                            <div class="mt-4">
                                <a href="contact-support.php" class="btn btn-primary me-3">
                                    <i class="fas fa-envelope me-2"></i>Contact Support
                                </a>
                                <a href="privacy-policy.php" class="btn btn-outline-secondary me-3">
                                    <i class="fas fa-shield-alt me-2"></i>Privacy Policy
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
.cookie-policy-content h3 {
    color: var(--primary-color);
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 10px;
}

.cookie-policy-content section {
    padding: 20px 0;
}

.cookie-policy-content ul {
    padding-left: 20px;
}

.cookie-policy-content li {
    margin-bottom: 8px;
}

.cookie-category {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    background-color: #f8f9fa;
}

.cookie-category h5 {
    margin-bottom: 15px;
}

.cookie-policy-content .table {
    background-color: white;
}

.cookie-policy-content .table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}

.cookie-policy-content code {
    background-color: #e9ecef;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.9em;
}

.cookie-policy-content .alert {
    border-left: 4px solid;
    border-radius: 0;
}

.cookie-policy-content .alert-info {
    border-left-color: #17a2b8;
}

.cookie-policy-content .alert-warning {
    border-left-color: #ffc107;
}
</style>

<?php require_once 'includes/footer.php'; ?>
