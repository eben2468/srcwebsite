<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/activity_functions.php';

// Include our messaging service
define('INCLUDED_FROM_APP', true);
require_once __DIR__ . '/../messaging_service.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user should use admin interface (super admin or admin)
if (!shouldUseAdminInterface()) {
    header("Location: dashboard.php");
    exit();
}

// Set page title
$pageTitle = "Messaging Settings";

// Include header
require_once 'includes/header.php';

// Track page view
if (function_exists('trackPageView')) {
    trackPageView($pageTitle);
}

// Log user activity
if (function_exists('logUserActivity')) {
    logUserActivity(
        $currentUser['user_id'],
        $currentUser['email'],
        'page_view',
        'Viewed Messaging Settings page',
        $_SERVER['REQUEST_URI']
    );
}

// Initialize variables
$successMessage = '';
$errorMessage = '';
$activeTab = 'email';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['email_settings'])) {
        $activeTab = 'email';
        // Process email settings
        $emailService = $_POST['email_service'] ?? 'phpmailer';
        $fromName = $_POST['from_name'] ?? '';
        $fromEmail = $_POST['from_email'] ?? '';
        $smtpHost = $_POST['smtp_host'] ?? '';
        $smtpPort = $_POST['smtp_port'] ?? '587';
        $smtpSecure = $_POST['smtp_secure'] ?? 'tls';
        $smtpAuth = isset($_POST['smtp_auth']) ? 1 : 0;
        $smtpUsername = $_POST['smtp_username'] ?? '';
        $smtpPassword = $_POST['smtp_password'] ?? '';
        $sendgridApiKey = $_POST['sendgrid_api_key'] ?? '';
        $mailchimpApiKey = $_POST['mailchimp_api_key'] ?? '';
        $infobipBaseUrl = $_POST['infobip_base_url'] ?? '';
        $infobipApiKey = $_POST['infobip_api_key'] ?? '';
        
        // Save settings to database
        $settings = [
            'email_service' => $emailService,
            'email_from_name' => $fromName,
            'email_from_email' => $fromEmail,
            'email_smtp_host' => $smtpHost,
            'email_smtp_port' => $smtpPort,
            'email_smtp_secure' => $smtpSecure,
            'email_smtp_auth' => $smtpAuth,
            'email_smtp_username' => $smtpUsername,
            'email_sendgrid_api_key' => $sendgridApiKey,
            'email_mailchimp_api_key' => $mailchimpApiKey,
            'email_infobip_base_url' => $infobipBaseUrl,
            'email_infobip_api_key' => $infobipApiKey
        ];
        
        // Only save password if not empty
        if (!empty($smtpPassword)) {
            $settings['email_smtp_password'] = $smtpPassword;
        }
        
        $success = true;
        foreach ($settings as $name => $value) {
            if (!saveSystemSetting($name, $value)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $successMessage = "Email settings saved successfully.";
        } else {
            $errorMessage = "Error saving email settings.";
        }
    } elseif (isset($_POST['sms_settings'])) {
        $activeTab = 'sms';
        // Process SMS settings
        $smsService = $_POST['sms_service'] ?? 'twilio';
        $twilioAccountSid = $_POST['twilio_account_sid'] ?? '';
        $twilioAuthToken = $_POST['twilio_auth_token'] ?? '';
        $twilioPhoneNumber = $_POST['twilio_phone_number'] ?? '';
        $nexmoApiKey = $_POST['nexmo_api_key'] ?? '';
        $nexmoApiSecret = $_POST['nexmo_api_secret'] ?? '';
        $nexmoFrom = $_POST['nexmo_from'] ?? '';
        $africastalkingUsername = $_POST['africastalking_username'] ?? '';
        $africastalkingApiKey = $_POST['africastalking_api_key'] ?? '';
        $africastalkingFrom = $_POST['africastalking_from'] ?? '';
        // Infobip settings
        $infobipBaseUrl = $_POST['infobip_base_url'] ?? '';
        $infobipApiKey = $_POST['infobip_api_key'] ?? '';
        $infobipSender = $_POST['infobip_sender'] ?? '';
        // Zenoph settings
        $zenophApiKey = $_POST['zenoph_api_key'] ?? '';
        $zenophSender = $_POST['zenoph_sender'] ?? '';
        $zenophHost = $_POST['zenoph_host'] ?? 'api.smsonlinegh.com';
        
        // Create new config structure
        $newSmsConfig = [
            'default_provider' => $smsService,
            'service' => $smsService,
            'twilio_account_sid' => $twilioAccountSid,
            'twilio_auth_token' => $twilioAuthToken,
            'twilio_phone_number' => $twilioPhoneNumber,
            'nexmo_api_key' => $nexmoApiKey,
            'nexmo_api_secret' => $nexmoApiSecret,
            'nexmo_from' => $nexmoFrom,
            'africastalking_username' => $africastalkingUsername,
            'africastalking_api_key' => $africastalkingApiKey,
            'africastalking_from' => $africastalkingFrom,
            'infobip_base_url' => $infobipBaseUrl,
            'infobip_api_key' => $infobipApiKey,
            'infobip_sender' => $infobipSender,
            'zenoph_api_key' => $zenophApiKey,
            'zenoph_sender' => $zenophSender,
            'zenoph_host' => $zenophHost
        ];
        
        // Save to new config format
        if (saveSMSConfig($newSmsConfig)) {
            $successMessage = "SMS settings saved successfully.";
        } else {
            $errorMessage = "Error saving SMS settings.";
        }
        
        // Also save to legacy system settings for backward compatibility
        $settings = [
            'sms_service' => $smsService,
            'sms_twilio_account_sid' => $twilioAccountSid,
            'sms_twilio_auth_token' => $twilioAuthToken,
            'sms_twilio_phone_number' => $twilioPhoneNumber,
            'sms_nexmo_api_key' => $nexmoApiKey,
            'sms_nexmo_api_secret' => $nexmoApiSecret,
            'sms_nexmo_from' => $nexmoFrom,
            'sms_africastalking_username' => $africastalkingUsername,
            'sms_africastalking_api_key' => $africastalkingApiKey,
            'sms_africastalking_from' => $africastalkingFrom,
            'sms_infobip_base_url' => $infobipBaseUrl,
            'sms_infobip_api_key' => $infobipApiKey,
            'sms_infobip_sender' => $infobipSender,
            'sms_zenoph_api_key' => $zenophApiKey,
            'sms_zenoph_sender' => $zenophSender,
            'sms_zenoph_host' => $zenophHost
        ];
        
        foreach ($settings as $name => $value) {
            saveSystemSetting($name, $value);
        }
    } elseif (isset($_POST['whatsapp_settings'])) {
        $activeTab = 'whatsapp';
        // Process WhatsApp settings
        $whatsappService = $_POST['whatsapp_service'] ?? 'twilio';
        $twilioAccountSid = $_POST['twilio_account_sid'] ?? '';
        $twilioAuthToken = $_POST['twilio_auth_token'] ?? '';
        $twilioWhatsappNumber = $_POST['twilio_whatsapp_number'] ?? '';
        $messagebirdApiKey = $_POST['messagebird_api_key'] ?? '';
        $messagebirdChannelId = $_POST['messagebird_channel_id'] ?? '';
        // Infobip settings
        $infobipBaseUrl = $_POST['infobip_base_url'] ?? '';
        $infobipApiKey = $_POST['infobip_api_key'] ?? '';
        $infobipSender = $_POST['infobip_sender'] ?? '';
        
        // Create new config structure
        $newWhatsappConfig = [
            'default_provider' => $whatsappService,
            'service' => $whatsappService,
            'twilio_account_sid' => $twilioAccountSid,
            'twilio_auth_token' => $twilioAuthToken,
            'twilio_phone_number' => $twilioWhatsappNumber,
            'twilio_whatsapp_number' => $twilioWhatsappNumber,
            'messagebird_api_key' => $messagebirdApiKey,
            'messagebird_channel_id' => $messagebirdChannelId,
            'infobip_base_url' => $infobipBaseUrl,
            'infobip_api_key' => $infobipApiKey,
            'infobip_sender' => $infobipSender
        ];
        
        // Save to new config format
        if (saveWhatsAppConfig($newWhatsappConfig)) {
            $successMessage = "WhatsApp settings saved successfully.";
        } else {
            $errorMessage = "Error saving WhatsApp settings.";
        }
        
        // Also save to legacy system settings for backward compatibility
        $settings = [
            'whatsapp_service' => $whatsappService,
            'whatsapp_twilio_account_sid' => $twilioAccountSid,
            'whatsapp_twilio_auth_token' => $twilioAuthToken,
            'whatsapp_twilio_whatsapp_number' => $twilioWhatsappNumber,
            'whatsapp_messagebird_api_key' => $messagebirdApiKey,
            'whatsapp_messagebird_channel_id' => $messagebirdChannelId,
            'whatsapp_infobip_base_url' => $infobipBaseUrl,
            'whatsapp_infobip_api_key' => $infobipApiKey,
            'whatsapp_infobip_sender' => $infobipSender
        ];
        
        foreach ($settings as $name => $value) {
            saveSystemSetting($name, $value);
        }
    }
}

// Load current settings
$emailSettings = getEmailConfig();

// Make sure all keys are set in $emailSettings
if (!isset($emailSettings['service'])) $emailSettings['service'] = 'phpmailer';
if (!isset($emailSettings['from_name'])) $emailSettings['from_name'] = '';
if (!isset($emailSettings['from_email'])) $emailSettings['from_email'] = '';
if (!isset($emailSettings['smtp_host'])) $emailSettings['smtp_host'] = '';
if (!isset($emailSettings['smtp_port'])) $emailSettings['smtp_port'] = '587';
if (!isset($emailSettings['smtp_secure'])) $emailSettings['smtp_secure'] = 'tls';
if (!isset($emailSettings['smtp_auth'])) $emailSettings['smtp_auth'] = 0;
if (!isset($emailSettings['smtp_username'])) $emailSettings['smtp_username'] = '';
if (!isset($emailSettings['smtp_password'])) $emailSettings['smtp_password'] = '';
if (!isset($emailSettings['sendgrid_api_key'])) $emailSettings['sendgrid_api_key'] = '';
if (!isset($emailSettings['mailchimp_api_key'])) $emailSettings['mailchimp_api_key'] = '';

// Convert old SMS settings format to new format if needed
$smsSettings = getSMSConfig();
if (!isset($smsSettings['service']) && isset($smsSettings['default_provider'])) {
    $smsSettings['service'] = $smsSettings['default_provider'];
}

// Make sure all keys are set in $smsSettings
if (!isset($smsSettings['twilio_account_sid'])) $smsSettings['twilio_account_sid'] = '';
if (!isset($smsSettings['twilio_auth_token'])) $smsSettings['twilio_auth_token'] = '';
if (!isset($smsSettings['twilio_phone_number'])) $smsSettings['twilio_phone_number'] = '';
if (!isset($smsSettings['nexmo_api_key'])) $smsSettings['nexmo_api_key'] = '';
if (!isset($smsSettings['nexmo_api_secret'])) $smsSettings['nexmo_api_secret'] = '';
if (!isset($smsSettings['nexmo_from'])) $smsSettings['nexmo_from'] = '';
if (!isset($smsSettings['africastalking_username'])) $smsSettings['africastalking_username'] = '';
if (!isset($smsSettings['africastalking_api_key'])) $smsSettings['africastalking_api_key'] = '';
if (!isset($smsSettings['africastalking_from'])) $smsSettings['africastalking_from'] = '';
if (!isset($smsSettings['infobip_base_url'])) $smsSettings['infobip_base_url'] = '';
if (!isset($smsSettings['infobip_api_key'])) $smsSettings['infobip_api_key'] = '';
if (!isset($smsSettings['infobip_sender'])) $smsSettings['infobip_sender'] = '';
if (!isset($smsSettings['zenoph_api_key'])) $smsSettings['zenoph_api_key'] = '';
if (!isset($smsSettings['zenoph_sender'])) $smsSettings['zenoph_sender'] = '';
if (!isset($smsSettings['zenoph_host'])) $smsSettings['zenoph_host'] = 'api.smsonlinegh.com';

// Convert old WhatsApp settings format to new format if needed
$whatsappSettings = getWhatsAppConfig();
if (!isset($whatsappSettings['service']) && isset($whatsappSettings['default_provider'])) {
    $whatsappSettings['service'] = $whatsappSettings['default_provider'];
}

// Make sure all keys are set in $whatsappSettings
if (!isset($whatsappSettings['twilio_account_sid'])) $whatsappSettings['twilio_account_sid'] = '';
if (!isset($whatsappSettings['twilio_auth_token'])) $whatsappSettings['twilio_auth_token'] = '';
if (!isset($whatsappSettings['twilio_whatsapp_number'])) $whatsappSettings['twilio_whatsapp_number'] = '';
if (!isset($whatsappSettings['messagebird_api_key'])) $whatsappSettings['messagebird_api_key'] = '';
if (!isset($whatsappSettings['messagebird_channel_id'])) $whatsappSettings['messagebird_channel_id'] = '';
if (!isset($whatsappSettings['infobip_base_url'])) $whatsappSettings['infobip_base_url'] = '';
if (!isset($whatsappSettings['infobip_api_key'])) $whatsappSettings['infobip_api_key'] = '';
if (!isset($whatsappSettings['infobip_sender'])) $whatsappSettings['infobip_sender'] = '';

/**
 * Helper function to save a system setting
 * 
 * @param string $name Setting name
 * @param string $value Setting value
 * @return bool Success status
 */
function saveSystemSetting($name, $value) {
    global $conn;
    
    // Check if setting exists
    $sql = "SELECT * FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing setting
            $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, 'ss', $value, $name);
                $success = mysqli_stmt_execute($updateStmt);
                mysqli_stmt_close($updateStmt);
                
                return $success;
            }
        } else {
            // Insert new setting
            $insertSql = "INSERT INTO settings (setting_key, setting_value, setting_group, description) VALUES (?, ?, 'messaging', ?)";
            $insertStmt = mysqli_prepare($conn, $insertSql);
            
            if ($insertStmt) {
                $description = "Messaging setting: " . $name;
                mysqli_stmt_bind_param($insertStmt, 'sss', $name, $value, $description);
                $success = mysqli_stmt_execute($insertStmt);
                mysqli_stmt_close($insertStmt);
                
                return $success;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return false;
}
?>

<!-- Custom Messaging Settings Header -->
<div class="messaging-settings-header animate__animated animate__fadeInDown">
    <div class="messaging-settings-header-content">
        <div class="messaging-settings-header-main">
            <h1 class="messaging-settings-title">
                <i class="fas fa-cog me-3"></i>
                Messaging Settings
            </h1>
            <p class="messaging-settings-description">Configure email, SMS, and WhatsApp messaging settings</p>
        </div>
        <div class="messaging-settings-header-actions">
            <a href="messaging.php" class="btn btn-header-action">
                <i class="fas fa-arrow-left me-2"></i>Back to Messaging
            </a>
        </div>
    </div>
</div>

<style>
.messaging-settings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.messaging-settings-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.messaging-settings-header-main {
    flex: 1;
    text-align: center;
}

.messaging-settings-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.messaging-settings-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.messaging-settings-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.messaging-settings-header-actions {
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
    .messaging-settings-header {
        padding: 2rem 1.5rem;
    }

    .messaging-settings-header-content {
        flex-direction: column;
        align-items: center;
    }

    .messaging-settings-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .messaging-settings-title i {
        font-size: 1.8rem;
    }

    .messaging-settings-description {
        font-size: 1.1rem;
    }

    .messaging-settings-header-actions {
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

/* Mobile Full-Width Optimization for Messaging Settings Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"], [class*="col-xl-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .header, .page-hero, .modern-page-header {
        border-radius: 12px !important;
    }
    .card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<div class="container-fluid px-4">
    
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'email' ? 'active' : ''; ?>" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="<?php echo $activeTab === 'email' ? 'true' : 'false'; ?>">
                                <i class="fas fa-envelope me-1"></i> Email Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'sms' ? 'active' : ''; ?>" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms" type="button" role="tab" aria-controls="sms" aria-selected="<?php echo $activeTab === 'sms' ? 'true' : 'false'; ?>">
                                <i class="fas fa-sms me-1"></i> SMS Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeTab === 'whatsapp' ? 'active' : ''; ?>" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button" role="tab" aria-controls="whatsapp" aria-selected="<?php echo $activeTab === 'whatsapp' ? 'true' : 'false'; ?>">
                                <i class="fab fa-whatsapp me-1"></i> WhatsApp Settings
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Email Settings Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'email' ? 'show active' : ''; ?>" id="email" role="tabpanel" aria-labelledby="email-tab">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="email_service" class="form-label">Email Service</label>
                                    <select class="form-select" id="email_service" name="email_service">
                                        <option value="phpmailer" <?php echo $emailSettings['service'] === 'phpmailer' ? 'selected' : ''; ?>>PHP Mailer</option>
                                        <option value="smtp" <?php echo $emailSettings['service'] === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                                        <option value="sendgrid" <?php echo $emailSettings['service'] === 'sendgrid' ? 'selected' : ''; ?>>SendGrid</option>
                                        <option value="mailchimp" <?php echo $emailSettings['service'] === 'mailchimp' ? 'selected' : ''; ?>>Mailchimp</option>
                                        <option value="infobip" <?php echo $emailSettings['service'] === 'infobip' ? 'selected' : ''; ?>>Infobip</option>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="from_name" class="form-label">From Name</label>
                                        <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo htmlspecialchars($emailSettings['from_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="from_email" class="form-label">From Email</label>
                                        <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo htmlspecialchars($emailSettings['from_email']); ?>">
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">SMTP Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="smtp_host" class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($emailSettings['smtp_host']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="smtp_port" class="form-label">SMTP Port</label>
                                                <input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($emailSettings['smtp_port']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="smtp_secure" class="form-label">SMTP Security</label>
                                                <select class="form-select" id="smtp_secure" name="smtp_secure">
                                                    <option value="tls" <?php echo $emailSettings['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                    <option value="ssl" <?php echo $emailSettings['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                    <option value="none" <?php echo $emailSettings['smtp_secure'] === 'none' ? 'selected' : ''; ?>>None</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" id="smtp_auth" name="smtp_auth" <?php echo $emailSettings['smtp_auth'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="smtp_auth">
                                                        SMTP Authentication
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($emailSettings['smtp_username']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Leave empty to keep current password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="sendgrid_api_key" class="form-label">SendGrid API Key</label>
                                        <input type="password" class="form-control" id="sendgrid_api_key" name="sendgrid_api_key" value="<?php echo htmlspecialchars($emailSettings['sendgrid_api_key']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mailchimp_api_key" class="form-label">Mailchimp API Key</label>
                                        <input type="password" class="form-control" id="mailchimp_api_key" name="mailchimp_api_key" value="<?php echo htmlspecialchars($emailSettings['mailchimp_api_key']); ?>">
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Infobip Email Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="infobip_base_url" class="form-label">Base URL</label>
                                                <input type="text" class="form-control" id="infobip_base_url" name="infobip_base_url" value="<?php echo htmlspecialchars($emailSettings['infobip_base_url'] ?? ''); ?>">
                                                <div class="form-text">E.g., https://api.infobip.com</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="infobip_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="infobip_api_key" name="infobip_api_key" value="<?php echo htmlspecialchars($emailSettings['infobip_api_key'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary" name="email_settings">Save Email Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- SMS Settings Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'sms' ? 'show active' : ''; ?>" id="sms" role="tabpanel" aria-labelledby="sms-tab">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="sms_service" class="form-label">SMS Service</label>
                                    <select class="form-select" id="sms_service" name="sms_service">
                                        <option value="twilio" <?php echo (isset($smsSettings['service']) && $smsSettings['service'] === 'twilio') ? 'selected' : ''; ?>>Twilio</option>
                                        <option value="nexmo" <?php echo (isset($smsSettings['service']) && $smsSettings['service'] === 'nexmo') ? 'selected' : ''; ?>>Nexmo (Vonage)</option>
                                        <option value="africastalking" <?php echo (isset($smsSettings['service']) && $smsSettings['service'] === 'africastalking') ? 'selected' : ''; ?>>Africa's Talking</option>
                                        <option value="infobip" <?php echo (isset($smsSettings['service']) && $smsSettings['service'] === 'infobip') ? 'selected' : ''; ?>>Infobip</option>
                                        <option value="zenoph" <?php echo (isset($smsSettings['service']) && $smsSettings['service'] === 'zenoph') ? 'selected' : ''; ?>>Zenoph (SMS Online GH)</option>
                                    </select>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Twilio Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="twilio_account_sid" class="form-label">Account SID</label>
                                                <input type="text" class="form-control" id="twilio_account_sid" name="twilio_account_sid" value="<?php echo htmlspecialchars($smsSettings['twilio_account_sid']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="twilio_auth_token" class="form-label">Auth Token</label>
                                                <input type="password" class="form-control" id="twilio_auth_token" name="twilio_auth_token" value="<?php echo htmlspecialchars($smsSettings['twilio_auth_token']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="twilio_phone_number" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="twilio_phone_number" name="twilio_phone_number" value="<?php echo htmlspecialchars($smsSettings['twilio_phone_number']); ?>">
                                            <div class="form-text">Include country code (e.g., +1234567890)</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Nexmo (Vonage) Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="nexmo_api_key" class="form-label">API Key</label>
                                                <input type="text" class="form-control" id="nexmo_api_key" name="nexmo_api_key" value="<?php echo htmlspecialchars($smsSettings['nexmo_api_key']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="nexmo_api_secret" class="form-label">API Secret</label>
                                                <input type="password" class="form-control" id="nexmo_api_secret" name="nexmo_api_secret" value="<?php echo htmlspecialchars($smsSettings['nexmo_api_secret']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="nexmo_from" class="form-label">From</label>
                                            <input type="text" class="form-control" id="nexmo_from" name="nexmo_from" value="<?php echo htmlspecialchars($smsSettings['nexmo_from']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Africa's Talking Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="africastalking_username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="africastalking_username" name="africastalking_username" value="<?php echo htmlspecialchars($smsSettings['africastalking_username']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="africastalking_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="africastalking_api_key" name="africastalking_api_key" value="<?php echo htmlspecialchars($smsSettings['africastalking_api_key']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="africastalking_from" class="form-label">From (Sender ID)</label>
                                            <input type="text" class="form-control" id="africastalking_from" name="africastalking_from" value="<?php echo htmlspecialchars($smsSettings['africastalking_from']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Infobip Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="infobip_base_url" class="form-label">Base URL</label>
                                                <input type="text" class="form-control" id="infobip_base_url" name="infobip_base_url" value="<?php echo htmlspecialchars($smsSettings['infobip_base_url']); ?>">
                                                <div class="form-text">E.g., https://api.infobip.com</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="infobip_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="infobip_api_key" name="infobip_api_key" value="<?php echo htmlspecialchars($smsSettings['infobip_api_key']); ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="infobip_sender" class="form-label">Sender ID</label>
                                            <input type="text" class="form-control" id="infobip_sender" name="infobip_sender" value="<?php echo htmlspecialchars($smsSettings['infobip_sender']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="card mb-3">
                                    <div class="card-header">Zenoph (SMS Online GH) Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="zenoph_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="zenoph_api_key" name="zenoph_api_key" value="<?php echo htmlspecialchars($smsSettings['zenoph_api_key']); ?>">
                                                <div class="form-text">Your SMS Online GH API key</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="zenoph_sender" class="form-label">Sender ID</label>
                                                <input type="text" class="form-control" id="zenoph_sender" name="zenoph_sender" value="<?php echo htmlspecialchars($smsSettings['zenoph_sender']); ?>">
                                                <div class="form-text">Your approved sender ID</div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="zenoph_host" class="form-label">Host Domain</label>
                                            <input type="text" class="form-control" id="zenoph_host" name="zenoph_host" value="<?php echo htmlspecialchars($smsSettings['zenoph_host']); ?>">
                                            <div class="form-text">Default: api.smsonlinegh.com</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary" name="sms_settings">Save SMS Settings</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- WhatsApp Settings Tab -->
                        <div class="tab-pane fade <?php echo $activeTab === 'whatsapp' ? 'show active' : ''; ?>" id="whatsapp" role="tabpanel" aria-labelledby="whatsapp-tab">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="whatsapp_service" class="form-label">WhatsApp Service</label>
                                    <select class="form-select" id="whatsapp_service" name="whatsapp_service">
                                        <option value="twilio" <?php echo (isset($whatsappSettings['service']) && $whatsappSettings['service'] === 'twilio') ? 'selected' : ''; ?>>Twilio</option>
                                        <option value="messagebird" <?php echo (isset($whatsappSettings['service']) && $whatsappSettings['service'] === 'messagebird') ? 'selected' : ''; ?>>MessageBird</option>
                                        <option value="infobip" <?php echo (isset($whatsappSettings['service']) && $whatsappSettings['service'] === 'infobip') ? 'selected' : ''; ?>>Infobip</option>
                                    </select>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Twilio WhatsApp Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="twilio_account_sid" class="form-label">Account SID</label>
                                                <input type="text" class="form-control" id="twilio_account_sid" name="twilio_account_sid" value="<?php echo htmlspecialchars($whatsappSettings['twilio_account_sid']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="twilio_auth_token" class="form-label">Auth Token</label>
                                                <input type="password" class="form-control" id="twilio_auth_token" name="twilio_auth_token" value="<?php echo htmlspecialchars($whatsappSettings['twilio_auth_token']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="twilio_whatsapp_number" class="form-label">WhatsApp Number</label>
                                            <input type="text" class="form-control" id="twilio_whatsapp_number" name="twilio_whatsapp_number" value="<?php echo htmlspecialchars($whatsappSettings['twilio_whatsapp_number']); ?>">
                                            <div class="form-text">Include country code (e.g., +1234567890)</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">MessageBird Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="messagebird_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="messagebird_api_key" name="messagebird_api_key" value="<?php echo htmlspecialchars($whatsappSettings['messagebird_api_key']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="messagebird_channel_id" class="form-label">Channel ID</label>
                                                <input type="text" class="form-control" id="messagebird_channel_id" name="messagebird_channel_id" value="<?php echo htmlspecialchars($whatsappSettings['messagebird_channel_id']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">Infobip WhatsApp Settings</div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="infobip_base_url" class="form-label">Base URL</label>
                                                <input type="text" class="form-control" id="infobip_base_url" name="infobip_base_url" value="<?php echo htmlspecialchars($whatsappSettings['infobip_base_url']); ?>">
                                                <div class="form-text">E.g., https://api.infobip.com</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="infobip_api_key" class="form-label">API Key</label>
                                                <input type="password" class="form-control" id="infobip_api_key" name="infobip_api_key" value="<?php echo htmlspecialchars($whatsappSettings['infobip_api_key']); ?>">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="infobip_sender" class="form-label">Sender ID</label>
                                            <input type="text" class="form-control" id="infobip_sender" name="infobip_sender" value="<?php echo htmlspecialchars($whatsappSettings['infobip_sender']); ?>">
                                            <div class="form-text">This is your WhatsApp Business Account Phone Number ID</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary" name="whatsapp_settings">Save WhatsApp Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show active tab based on URL hash
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`[data-bs-target="${hash}"]`);
        if (tab) {
            const tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }
    }
    
    // Toggle SMTP settings visibility based on email service selection
    const emailServiceSelect = document.getElementById('email_service');
    const toggleEmailSettings = function() {
        // Find the SMTP and Infobip card elements
        let smtpCard = null;
        let infobipCard = null;
        
        // Find cards by their headers
        document.querySelectorAll('.card-header').forEach(header => {
            if (header.textContent.includes('SMTP Settings')) {
                smtpCard = header.closest('.card');
            }
            if (header.textContent.includes('Infobip Email')) {
                infobipCard = header.closest('.card');
            }
        });
        
        // Hide all service-specific cards first
        if (smtpCard) smtpCard.style.display = 'none';
        if (infobipCard) infobipCard.style.display = 'none';
        
        // Show the relevant card based on selection
        switch(emailServiceSelect.value) {
            case 'smtp':
            case 'phpmailer':
                if (smtpCard) smtpCard.style.display = 'block';
                break;
            case 'infobip':
                if (infobipCard) infobipCard.style.display = 'block';
                break;
        }
    };
    
    if (emailServiceSelect) {
        emailServiceSelect.addEventListener('change', toggleEmailSettings);
        toggleEmailSettings(); // Initial state
    }
    
    // Toggle SMS service settings visibility
    const smsServiceSelect = document.getElementById('sms_service');
    const toggleSmsSettings = function() {
        // Find all SMS service cards
        let twilioCard = null;
        let nexmoCard = null;
        let africasTalkingCard = null;
        let infobipCard = null;
        let zenophCard = null;

        // Find cards by their headers
        document.querySelectorAll('.card-header').forEach(header => {
            if (header.textContent.includes('Twilio Settings') && !header.textContent.includes('WhatsApp')) {
                twilioCard = header.closest('.card');
            }
            if (header.textContent.includes('Nexmo')) {
                nexmoCard = header.closest('.card');
            }
            if (header.textContent.includes('Africa\'s Talking')) {
                africasTalkingCard = header.closest('.card');
            }
            if (header.textContent.includes('Infobip Settings') && !header.textContent.includes('WhatsApp') && !header.textContent.includes('Email')) {
                infobipCard = header.closest('.card');
            }
            if (header.textContent.includes('Zenoph')) {
                zenophCard = header.closest('.card');
            }
        });

        // Hide all service-specific cards first
        if (twilioCard) twilioCard.style.display = 'none';
        if (nexmoCard) nexmoCard.style.display = 'none';
        if (africasTalkingCard) africasTalkingCard.style.display = 'none';
        if (infobipCard) infobipCard.style.display = 'none';
        if (zenophCard) zenophCard.style.display = 'none';
        
        // Show the relevant card based on selection
        switch(smsServiceSelect.value) {
            case 'twilio':
                if (twilioCard) twilioCard.style.display = 'block';
                break;
            case 'nexmo':
                if (nexmoCard) nexmoCard.style.display = 'block';
                break;
            case 'africastalking':
                if (africasTalkingCard) africasTalkingCard.style.display = 'block';
                break;
            case 'infobip':
                if (infobipCard) infobipCard.style.display = 'block';
                break;
            case 'zenoph':
                if (zenophCard) zenophCard.style.display = 'block';
                break;
        }
    };
    
    if (smsServiceSelect) {
        smsServiceSelect.addEventListener('change', toggleSmsSettings);
        toggleSmsSettings(); // Initial state
    }
    
    // Toggle WhatsApp service settings visibility
    const whatsappServiceSelect = document.getElementById('whatsapp_service');
    const toggleWhatsappSettings = function() {
        // Find all WhatsApp service cards
        let twilioCard = null;
        let messagebirdCard = null;
        let infobipCard = null;
        
        // Find cards by their headers
        document.querySelectorAll('.card-header').forEach(header => {
            if (header.textContent.includes('Twilio WhatsApp')) {
                twilioCard = header.closest('.card');
            }
            if (header.textContent.includes('MessageBird')) {
                messagebirdCard = header.closest('.card');
            }
            if (header.textContent.includes('Infobip WhatsApp')) {
                infobipCard = header.closest('.card');
            }
        });
        
        // Hide all service-specific cards first
        if (twilioCard) twilioCard.style.display = 'none';
        if (messagebirdCard) messagebirdCard.style.display = 'none';
        if (infobipCard) infobipCard.style.display = 'none';
        
        // Show the relevant card based on selection
        switch(whatsappServiceSelect.value) {
            case 'twilio':
                if (twilioCard) twilioCard.style.display = 'block';
                break;
            case 'messagebird':
                if (messagebirdCard) messagebirdCard.style.display = 'block';
                break;
            case 'infobip':
                if (infobipCard) infobipCard.style.display = 'block';
                break;
        }
    };
    
    if (whatsappServiceSelect) {
        whatsappServiceSelect.addEventListener('change', toggleWhatsappSettings);
        toggleWhatsappSettings(); // Initial state
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
