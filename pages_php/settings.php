<?php
// Include authentication file
require_once '../auth_functions.php';
require_once '../settings_functions.php';
require_once '../db_config.php';

// Try to load icon functions
$iconFunctionsAvailable = false;
try {
    if (file_exists('../icon_functions.php')) {
        require_once '../icon_functions.php';
        $iconFunctionsAvailable = true;
    }
} catch (Exception $e) {
    // If there's an error loading the file, just continue without icons
}

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit();
}

// Get site name from settings
$siteName = getSetting('site_name', 'SRC Management System');

// Set page title
$pageTitle = "Settings - " . $siteName;

// Get current user ID for tracking who updated settings
$currentUser = getCurrentUser();
$userId = $currentUser['user_id'] ?? null;

// Get settings from database
$settings = getAllSettings();

// If settings are not found, use defaults
if (empty($settings)) {
    $settings = [
        'general' => [
            'site_name' => 'SRC Management System',
            'welcome_message' => 'Welcome to the SRC Management System',
            'contact_email' => 'src@example.com',
            'support_phone' => '+1234567890',
            'timezone' => 'UTC'
        ],
        'features' => [
            'enable_elections' => true,
            'enable_documents' => true,
            'enable_news' => true,
            'enable_budget' => true
        ],
        'appearance' => [
            'primary_color' => '#0d6efd',
            'logo_url' => '../images/logo.png',
            'system_icon' => 'church',
            'footer_text' => '© 2023 SRC Management System. All rights reserved.',
            'theme_mode' => 'system',
            'logo_type' => 'icon'
        ],
        'security' => [
            'password_expiry_days' => 90,
            'max_login_attempts' => 5,
            'session_timeout_minutes' => 30,
            'require_2fa' => false
        ]
    ];
}

// Get available icons
$availableIcons = [];
$currentIconValue = $settings['appearance']['system_icon'] ?? 'church';
$currentIconInfo = null;

if ($iconFunctionsAvailable) {
    $availableIcons = getAvailableIcons();
    $currentIconInfo = getIconInfo($currentIconValue);
}

// Check if form was submitted to update settings
$formSubmitted = false;
$formSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['settings_section'])) {
    $formSubmitted = true;
    $section = $_POST['settings_section'];
    
    // Process and save settings based on section
    switch ($section) {
        case 'general':
            $generalSettings = [
                'site_name' => $_POST['site_name'] ?? '',
                'welcome_message' => $_POST['welcome_message'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'support_phone' => $_POST['support_phone'] ?? '',
                'timezone' => $_POST['timezone'] ?? 'UTC'
            ];
            $formSuccess = updateMultipleSettings($generalSettings, 'general', $userId);
            
            if ($formSuccess) {
                $settings['general'] = $generalSettings;
                
                // Apply settings immediately
                if (isset($generalSettings['site_name'])) {
                    $_SESSION['site_name'] = $generalSettings['site_name'];
                    $siteName = $generalSettings['site_name'];
                }
                
                // Apply timezone immediately
                if (isset($generalSettings['timezone'])) {
                    date_default_timezone_set($generalSettings['timezone']);
                }
            }
            break;
            
        case 'features':
            $featureSettings = [
                'enable_elections' => isset($_POST['enable_elections']),
                'enable_documents' => isset($_POST['enable_documents']),
                'enable_news' => isset($_POST['enable_news']),
                'enable_budget' => isset($_POST['enable_budget'])
            ];
            $formSuccess = updateMultipleSettings($featureSettings, 'features', $userId);
            
            if ($formSuccess) {
                $settings['features'] = $featureSettings;
                
                // Store feature settings in session for immediate effect
                $_SESSION['features'] = $featureSettings;
            }
            break;
            
        case 'appearance':
            $appearanceSettings = [
                'primary_color' => $_POST['primary_color'] ?? '#0d6efd',
                'footer_text' => $_POST['footer_text'] ?? '',
                'theme_mode' => $_POST['theme_mode'] ?? 'system',
                'logo_type' => $_POST['logo_type'] ?? 'icon',
                'system_icon' => $_POST['system_icon'] ?? 'university'
            ];
            
            // Handle logo upload if a file was provided
            if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../images/';
                $fileName = 'logo_' . time() . '.' . pathinfo($_FILES['logo_upload']['name'], PATHINFO_EXTENSION);
                $targetFile = $uploadDir . $fileName;
                $tempFile = $uploadDir . 'temp_' . $fileName;
                
                // First move the uploaded file to a temporary location
                if (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $tempFile)) {
                    // Check if GD is available for resizing
                    $gdAvailable = function_exists('imagecreatetruecolor');
                    
                    if ($gdAvailable) {
                        // Try to resize the image
                        if (resizeImage($tempFile, $targetFile, 48, 48)) {
                            $appearanceSettings['logo_url'] = $targetFile;
                            // Remove the temporary file
                            @unlink($tempFile);
                        } else {
                            // If resize fails, just use the original
                            rename($tempFile, $targetFile);
                            $appearanceSettings['logo_url'] = $targetFile;
                        }
                    } else {
                        // GD not available, just use the original file
                        rename($tempFile, $targetFile);
                        $appearanceSettings['logo_url'] = $targetFile;
                    }
                }
            }
            
            // Apply system icon change immediately
            applySystemIconChange();
            
            // Debug settings before update
            // echo "<!-- Before update: " . print_r($appearanceSettings, true) . " -->";
            
            // Ensure system_icon is included
            if (!isset($_POST['system_icon']) && isset($currentIconValue)) {
                $appearanceSettings['system_icon'] = $currentIconValue;
            }
            
            $formSuccess = updateMultipleSettings($appearanceSettings, 'appearance', $userId);
            
            // Debug settings after update
            // echo "<!-- After update: " . print_r($appearanceSettings, true) . " -->";
            
            if ($formSuccess) {
                $settings['appearance'] = array_merge($settings['appearance'] ?? [], $appearanceSettings);
                
                // Apply theme mode and primary color immediately
                if (isset($appearanceSettings['theme_mode'])) {
                    $_SESSION['theme_mode'] = $appearanceSettings['theme_mode'];
                }
                
                if (isset($appearanceSettings['primary_color'])) {
                    $_SESSION['primary_color'] = $appearanceSettings['primary_color'];
                }
                
                // Apply logo type and system icon immediately
                if (isset($appearanceSettings['logo_type'])) {
                    $_SESSION['logo_type'] = $appearanceSettings['logo_type'];
                }
                
                if (isset($appearanceSettings['system_icon'])) {
                    $_SESSION['system_icon'] = $appearanceSettings['system_icon'];
                    $currentIconValue = $appearanceSettings['system_icon'];
                    $currentIconInfo = getIconInfo($currentIconValue);
                }
            }
            break;
            
        case 'security':
            $securitySettings = [
                'password_expiry_days' => intval($_POST['password_expiry'] ?? 90),
                'max_login_attempts' => intval($_POST['max_login_attempts'] ?? 5),
                'session_timeout_minutes' => intval($_POST['session_timeout'] ?? 30),
                'require_2fa' => isset($_POST['require_2fa'])
            ];
            $formSuccess = updateMultipleSettings($securitySettings, 'security', $userId);
            
            if ($formSuccess) {
                $settings['security'] = $securitySettings;
            }
            break;
    }
}

// Include header
require_once 'includes/header.php';

/**
 * Resize an image and save it
 * 
 * @param string $sourcePath Source image path
 * @param string $destPath Destination path for resized image
 * @param int $newWidth New width
 * @param int $newHeight New height
 * @return bool True if successful, false otherwise
 */
function resizeImage($sourcePath, $destPath, $newWidth = 48, $newHeight = 48) {
    // Check if GD library is available
    if (!function_exists('imagecreatetruecolor')) {
        // GD library not available, just copy the file
        return copy($sourcePath, $destPath);
    }
    
    try {
        // Get image information
        list($width, $height, $type) = getimagesize($sourcePath);
        
        // Create a new image resource based on file type
        switch ($type) {
            case IMAGETYPE_JPEG:
                if (!function_exists('imagecreatefromjpeg')) {
                    return copy($sourcePath, $destPath);
                }
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                if (!function_exists('imagecreatefrompng')) {
                    return copy($sourcePath, $destPath);
                }
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                if (!function_exists('imagecreatefromgif')) {
                    return copy($sourcePath, $destPath);
                }
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return copy($sourcePath, $destPath);
        }
        
        // Create a new image with the desired size
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // For PNG images, preserve transparency
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize the image
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        $success = false;
        
        // Save the resized image based on file type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($destination, $destPath, 90);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($destination, $destPath, 9);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($destination, $destPath);
                break;
        }
        
        // Free up memory
        imagedestroy($source);
        imagedestroy($destination);
        
        return $success;
    } catch (Exception $e) {
        // If any error occurs, just copy the file
        return copy($sourcePath, $destPath);
    }
}

/**
 * Apply system icon change immediately
 *
 * This function updates the system icon setting in the database and session
 */
function applySystemIconChange() {
    global $conn, $userId;
    
    if (isset($_POST['system_icon'])) {
        $iconValue = $_POST['system_icon'];
        
        // Update in the session immediately
        $_SESSION['system_icon'] = $iconValue;
        
        // Direct database update to ensure it takes effect
        $sql = "UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = 'system_icon'";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $iconValue, $userId);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Always set logo_type to 'icon' to ensure system icon is used
        $logoType = 'icon';
        $updateLogoType = "UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = 'logo_type'";
        $stmtLogoType = mysqli_prepare($conn, $updateLogoType);
        mysqli_stmt_bind_param($stmtLogoType, 'si', $logoType, $userId);
        mysqli_stmt_execute($stmtLogoType);
        mysqli_stmt_close($stmtLogoType);
        
        // Set in session
        $_SESSION['logo_type'] = 'icon';
        
        // Force a timestamp update on the icon file to break browser cache
        $iconPath = '../images/icons/' . $iconValue . '.svg';
        if (file_exists($iconPath)) {
            touch($iconPath);
        }
        
        return $success;
    }
    
    return false;
}
?>

<!-- Page Content -->
<?php if ($formSubmitted): ?>
<div class="alert alert-<?php echo $formSuccess ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
    <?php echo $formSuccess ? 'Settings updated successfully!' : 'Failed to update settings. Please try again.'; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="header">
    <h1 class="page-title">System Settings</h1>
    <div class="header-actions">
        <button class="btn btn-warning" id="backupSettingsBtn" style="background-color: #ff9800; border-color: #ff9800; color: #fff; font-weight: 500;">
            <i class="fas fa-download me-2"></i> Backup Settings
        </button>
    </div>
</div>

<!-- Settings Tabs -->
<div class="content-card">
    <div class="content-card-body">
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                    <i class="fas fa-sliders-h me-2"></i> General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">
                    <i class="fas fa-puzzle-piece me-2"></i> Features
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">
                    <i class="fas fa-paint-brush me-2"></i> Appearance
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                    <i class="fas fa-shield-alt me-2"></i> Security
                </button>
            </li>
        </ul>
        <div class="tab-content p-3" id="settingsTabsContent">
            <!-- General Settings -->
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="settings_section" value="general">
                    <div class="mb-3">
                        <label for="site-name" class="form-label">Site Name</label>
                        <input type="text" class="form-control" id="site-name" name="site_name" value="<?php echo htmlspecialchars($settings['general']['site_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="welcome-message" class="form-label">Welcome Message</label>
                        <textarea class="form-control" id="welcome-message" name="welcome_message" rows="2"><?php echo htmlspecialchars($settings['general']['welcome_message'] ?? ''); ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contact-email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="contact-email" name="contact_email" value="<?php echo htmlspecialchars($settings['general']['contact_email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="support-phone" class="form-label">Support Phone</label>
                            <input type="text" class="form-control" id="support-phone" name="support_phone" value="<?php echo htmlspecialchars($settings['general']['support_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select class="form-select" id="timezone" name="timezone">
                            <option value="UTC" <?php echo ($settings['general']['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            <option value="Africa/Accra" <?php echo ($settings['general']['timezone'] ?? '') === 'Africa/Accra' ? 'selected' : ''; ?>>Ghana (GMT)</option>
                            <option value="Africa/Lagos" <?php echo ($settings['general']['timezone'] ?? '') === 'Africa/Lagos' ? 'selected' : ''; ?>>West Africa Time</option>
                            <option value="Africa/Cairo" <?php echo ($settings['general']['timezone'] ?? '') === 'Africa/Cairo' ? 'selected' : ''; ?>>East Africa Time</option>
                            <option value="Africa/Johannesburg" <?php echo ($settings['general']['timezone'] ?? '') === 'Africa/Johannesburg' ? 'selected' : ''; ?>>South Africa Time</option>
                            <option value="America/New_York" <?php echo ($settings['general']['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time (ET)</option>
                            <option value="America/Chicago" <?php echo ($settings['general']['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time (CT)</option>
                            <option value="America/Denver" <?php echo ($settings['general']['timezone'] ?? '') === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time (MT)</option>
                            <option value="America/Los_Angeles" <?php echo ($settings['general']['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time (PT)</option>
                            <option value="Europe/London" <?php echo ($settings['general']['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London (GMT)</option>
                            <option value="Europe/Paris" <?php echo ($settings['general']['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : ''; ?>>Central European Time</option>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                    </div>
                </form>
            </div>
            
            <!-- Features Settings -->
            <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="settings_section" value="features">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable-elections" name="enable_elections" <?php echo ($settings['features']['enable_elections'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable-elections">Enable Elections</label>
                        </div>
                        <div class="form-text">Allow users to create and manage elections</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable-documents" name="enable_documents" <?php echo ($settings['features']['enable_documents'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable-documents">Enable Document Repository</label>
                        </div>
                        <div class="form-text">Allow users to upload and manage documents</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable-news" name="enable_news" <?php echo ($settings['features']['enable_news'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable-news">Enable News & Announcements</label>
                        </div>
                        <div class="form-text">Allow users to post and manage news items</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable-budget" name="enable_budget" <?php echo ($settings['features']['enable_budget'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable-budget">Enable Budget Management</label>
                        </div>
                        <div class="form-text">Allow admins to manage budget items</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Feature Settings</button>
                    </div>
                </form>
            </div>
            
            <!-- Appearance Settings -->
            <div class="tab-pane fade" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="settings_section" value="appearance">
                    <div class="mb-3">
                        <label for="primary-color" class="form-label">Primary Color</label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-2" id="primary-color" name="primary_color" value="<?php echo htmlspecialchars($settings['appearance']['primary_color'] ?? '#0d6efd'); ?>">
                            <span class="color-preview" style="width: 100px; height: 30px; background-color: <?php echo htmlspecialchars($settings['appearance']['primary_color'] ?? '#0d6efd'); ?>; border-radius: 4px;"></span>
                        </div>
                        <div class="form-text">This color will be used for buttons, links, and other UI elements</div>
                    </div>
                    
                    <!-- Logo & System Icon Settings -->
                    <div class="mb-3">
                        <label class="form-label">System Logo Options</label>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="logo_type" id="use-icon" value="icon" checked>
                                <label class="form-check-label" for="use-icon">
                                    <i class="fas fa-icons me-2"></i> Use System Icon
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="logo_type" id="use-custom" value="custom" <?php echo ($settings['appearance']['logo_type'] ?? 'icon') === 'custom' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="use-custom">
                                    <i class="fas fa-image me-2"></i> Use Custom Logo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Icon Selector -->
                    <div id="icon-selector-container" class="mb-3" <?php echo ($settings['appearance']['logo_type'] ?? 'icon') === 'custom' ? 'style="display:none;"' : ''; ?>>
                        <label class="form-label">System Icon</label>
                        <input type="hidden" name="system_icon" id="system-icon" value="<?php echo htmlspecialchars($currentIconValue); ?>">
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <img src="<?php echo htmlspecialchars($currentIconInfo['path'] ?? '../images/logo.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($currentIconInfo['name'] ?? 'Icon'); ?>" 
                                     id="selected-icon-display" 
                                     class="border p-1" 
                                     style="height: 48px; width: 48px; object-fit: contain;">
                            </div>
                            <div>
                                <span class="d-block mb-1">Selected: <strong id="selected-icon-name"><?php echo htmlspecialchars($currentIconInfo['name'] ?? 'None'); ?></strong></span>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="change-icon-btn" data-bs-toggle="collapse" data-bs-target="#icon-grid">
                                    <i class="fas fa-exchange-alt me-1"></i> Change Icon
                                </button>
                                <div class="mt-2">
                                    <a href="../add_icon.php" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="fas fa-plus me-1"></i> Add Custom Icon
                                    </a>
                                    <a href="../create_fa_icon.php" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="fab fa-font-awesome me-1"></i> Create From Font Awesome
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="collapse mb-3" id="icon-grid">
                            <div class="card card-body">
                                <div class="row g-2">
                                    <?php foreach ($availableIcons as $icon): ?>
                                    <div class="col-4 col-sm-3 col-md-2">
                                        <div class="icon-option-card <?php echo $icon['value'] === $currentIconValue ? 'active' : ''; ?>" 
                                             data-value="<?php echo htmlspecialchars($icon['value']); ?>"
                                             data-path="<?php echo htmlspecialchars($icon['path']); ?>"
                                             data-name="<?php echo htmlspecialchars($icon['name']); ?>">
                                            <img src="<?php echo htmlspecialchars($icon['path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($icon['name']); ?>" 
                                                 class="icon-option-img">
                                            <div class="icon-option-name"><?php echo htmlspecialchars($icon['name']); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-text">Select the icon that will be used throughout the system</div>
                    </div>
                    
                    <!-- Custom Logo Upload -->
                    <div id="logo-upload-container" class="mb-3" <?php echo ($settings['appearance']['logo_type'] ?? 'icon') !== 'custom' ? 'style="display:none;"' : ''; ?>>
                        <label for="logo-upload" class="form-label">Custom Logo</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="<?php echo htmlspecialchars($settings['appearance']['logo_url'] ?? '../images/logo.png'); ?>" alt="Logo" class="me-3" style="height: 48px; object-fit: contain;">
                            <div class="form-text">Current custom logo</div>
                        </div>
                        <input type="file" class="form-control" id="logo-upload" name="logo_upload" accept="image/*">
                        <div class="form-text">Recommended size: 48x48 pixels. Logo will be resized to match system icons.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="footer-text" class="form-label">Footer Text</label>
                        <input type="text" class="form-control" id="footer-text" name="footer_text" value="<?php echo htmlspecialchars($settings['appearance']['footer_text'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Theme Mode</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme_mode" id="theme-light" value="light" <?php echo ($settings['appearance']['theme_mode'] ?? '') === 'light' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="theme-light">
                                    <i class="fas fa-sun me-2"></i> Light Mode
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme_mode" id="theme-dark" value="dark" <?php echo ($settings['appearance']['theme_mode'] ?? '') === 'dark' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="theme-dark">
                                    <i class="fas fa-moon me-2"></i> Dark Mode
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="theme_mode" id="theme-system" value="system" <?php echo ($settings['appearance']['theme_mode'] ?? 'system') === 'system' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="theme-system">
                                    <i class="fas fa-desktop me-2"></i> System Default
                                </label>
                            </div>
                        </div>
                        <div class="form-text">Choose how the system appearance should be displayed</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Appearance Settings</button>
                    </div>
                </form>
            </div>
            
            <!-- Security Settings -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="settings_section" value="security">
                    <div class="mb-3">
                        <label for="password-expiry" class="form-label">Password Expiry (days)</label>
                        <input type="number" class="form-control" id="password-expiry" name="password_expiry" value="<?php echo $settings['security']['password_expiry_days'] ?? 90; ?>" min="0" max="365">
                        <div class="form-text">Set to 0 for no expiration</div>
                    </div>
                    <div class="mb-3">
                        <label for="max-login-attempts" class="form-label">Max Login Attempts</label>
                        <input type="number" class="form-control" id="max-login-attempts" name="max_login_attempts" value="<?php echo $settings['security']['max_login_attempts'] ?? 5; ?>" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label for="session-timeout" class="form-label">Session Timeout (minutes)</label>
                        <input type="number" class="form-control" id="session-timeout" name="session_timeout" value="<?php echo $settings['security']['session_timeout_minutes'] ?? 30; ?>" min="5" max="240">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="require-2fa" name="require_2fa" <?php echo ($settings['security']['require_2fa'] ?? false) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="require-2fa">Require Two-Factor Authentication</label>
                        </div>
                        <div class="form-text">Require all users to set up 2FA</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Security Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($iconFunctionsAvailable && !empty($availableIcons)): ?>
<script src="../js/icon-selector.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle backup settings button
    const backupBtn = document.getElementById('backupSettingsBtn');
    if (backupBtn) {
        backupBtn.addEventListener('click', function() {
            fetch('settings_backup.php')
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = 'settings_backup_' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => {
                    console.error('Error downloading settings backup:', error);
                    alert('Failed to download settings backup. Please try again.');
                });
        });
    }
    
    // Activate tab based on URL hash
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`a[href="${hash}"]`);
        if (tab) {
            const tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }
    }
    
    // Preview theme changes
    const themeRadios = document.querySelectorAll('input[name="theme_mode"]');
    themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const theme = this.value;
            document.documentElement.setAttribute('data-bs-theme', theme === 'system' ? 
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : 
                theme);
        });
    });
    
    // Preview primary color changes
    const primaryColorInput = document.getElementById('primary-color');
    const colorPreview = document.querySelector('.color-preview');
    if (primaryColorInput && colorPreview) {
        primaryColorInput.addEventListener('input', function() {
            colorPreview.style.backgroundColor = this.value;
            
            // Apply color to UI elements for preview
            document.querySelectorAll('.btn-primary').forEach(btn => {
                btn.style.backgroundColor = this.value;
                btn.style.borderColor = this.value;
            });
            
            document.querySelectorAll('.nav-link.active').forEach(nav => {
                nav.style.backgroundColor = this.value;
                nav.style.borderColor = this.value;
            });
        });
    }

    // Logo type toggle
    const logoTypeRadios = document.querySelectorAll('input[name="logo_type"]');
    const iconSelectorContainer = document.getElementById('icon-selector-container');
    const logoUploadContainer = document.getElementById('logo-upload-container');
    
    logoTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'icon') {
                iconSelectorContainer.style.display = 'block';
                logoUploadContainer.style.display = 'none';
            } else if (this.value === 'custom') {
                iconSelectorContainer.style.display = 'none';
                logoUploadContainer.style.display = 'block';
            }
        });
    });

    // Icon selector functionality
    const iconCards = document.querySelectorAll('.icon-option-card');
    const selectedIconDisplay = document.getElementById('selected-icon-display');
    const selectedIconName = document.getElementById('selected-icon-name');
    const iconInput = document.getElementById('system-icon');
    
    iconCards.forEach(card => {
        card.addEventListener('click', function() {
            const iconValue = this.getAttribute('data-value');
            const iconPath = this.getAttribute('data-path');
            const iconName = this.getAttribute('data-name');
            
            // Update hidden input
            if (iconInput) iconInput.value = iconValue;
            
            // Update display
            if (selectedIconDisplay) selectedIconDisplay.src = iconPath;
            if (selectedIconName) selectedIconName.textContent = iconName;
            
            // Update active class
            document.querySelectorAll('.icon-option-card.active').forEach(activeCard => {
                activeCard.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // Force reload after settings form submission
    const appearanceForm = document.querySelector('#appearance form');
    if (appearanceForm) {
        appearanceForm.addEventListener('submit', function() {
            // Store a flag in sessionStorage
            sessionStorage.setItem('appearance_updated', 'true');
        });
    }
    
    // Check if we should reload the page
    if (sessionStorage.getItem('appearance_updated') === 'true') {
        // Clear the flag
        sessionStorage.removeItem('appearance_updated');
        
        // Show a notification
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <strong>Settings updated!</strong> Refreshing page to apply changes...
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.querySelector('.content-card').prepend(alertDiv);
        
        // Reload the page after a short delay
        setTimeout(function() {
            window.location.reload(true); // Force reload from server
        }, 1500);
    }
});
</script>

<style>
/* Icon option card styles */
.icon-option-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s ease;
    height: 100%;
}

.icon-option-card:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.icon-option-card.active {
    border-color: #0d6efd;
    background-color: #e7f1ff;
    box-shadow: 0 0 5px rgba(13, 110, 253, 0.3);
}

.icon-option-img {
    height: 32px;
    width: 32px;
    object-fit: contain;
    margin-bottom: 5px;
}

.icon-option-name {
    font-size: 12px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
</style>

<?php require_once 'includes/footer.php'; ?> 