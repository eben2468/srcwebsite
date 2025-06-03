<?php
/**
 * Create Font Awesome Icon
 * This script allows administrators to add Font Awesome icons to the system
 */

// Start session
session_start();

// Include required files
if (file_exists('db_config.php')) {
    require_once 'db_config.php';
} else {
    die('Database configuration file not found.');
}

if (file_exists('auth_functions.php')) {
    require_once 'auth_functions.php';
} else {
    die('Auth functions file not found.');
}

if (file_exists('icon_functions.php')) {
    require_once 'icon_functions.php';
} else {
    die('Icon functions file not found.');
}

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = '';
$iconName = '';
$iconValue = '';
$faIconName = '';

// Font Awesome icon map - you can add more as needed
$faIcons = [
    'academics' => ['name' => 'Academics', 'fa' => 'fa-graduation-cap', 'color' => '#3498db'],
    'award' => ['name' => 'Award', 'fa' => 'fa-award', 'color' => '#f39c12'],
    'bell' => ['name' => 'Bell', 'fa' => 'fa-bell', 'color' => '#e74c3c'],
    'bible' => ['name' => 'Bible', 'fa' => 'fa-book-bible', 'color' => '#2c3e50'],
    'book' => ['name' => 'Book', 'fa' => 'fa-book', 'color' => '#27ae60'],
    'books' => ['name' => 'Books', 'fa' => 'fa-books', 'color' => '#8e44ad'],
    'calendar' => ['name' => 'Calendar', 'fa' => 'fa-calendar-alt', 'color' => '#3498db'],
    'certificate' => ['name' => 'Certificate', 'fa' => 'fa-certificate', 'color' => '#f1c40f'],
    'church' => ['name' => 'Church', 'fa' => 'fa-church', 'color' => '#34495e'],
    'cross' => ['name' => 'Cross', 'fa' => 'fa-cross', 'color' => '#7f8c8d'],
    'dove' => ['name' => 'Dove', 'fa' => 'fa-dove', 'color' => '#bdc3c7'],
    'globe' => ['name' => 'Globe', 'fa' => 'fa-globe', 'color' => '#2980b9'],
    'graduation-cap' => ['name' => 'Graduation Cap', 'fa' => 'fa-graduation-cap', 'color' => '#16a085'],
    'hands-praying' => ['name' => 'Praying Hands', 'fa' => 'fa-hands-praying', 'color' => '#95a5a6'],
    'heart' => ['name' => 'Heart', 'fa' => 'fa-heart', 'color' => '#e74c3c'],
    'landmark' => ['name' => 'Landmark', 'fa' => 'fa-landmark', 'color' => '#d35400'],
    'lightbulb' => ['name' => 'Lightbulb', 'fa' => 'fa-lightbulb', 'color' => '#f1c40f'],
    'medal' => ['name' => 'Medal', 'fa' => 'fa-medal', 'color' => '#f39c12'],
    'people-group' => ['name' => 'People Group', 'fa' => 'fa-people-group', 'color' => '#2ecc71'],
    'school' => ['name' => 'School', 'fa' => 'fa-school', 'color' => '#e67e22'],
    'star' => ['name' => 'Star', 'fa' => 'fa-star', 'color' => '#f1c40f'],
    'trophy' => ['name' => 'Trophy', 'fa' => 'fa-trophy', 'color' => '#f39c12'],
    'university' => ['name' => 'University', 'fa' => 'fa-university', 'color' => '#34495e'],
    'users' => ['name' => 'Users', 'fa' => 'fa-users', 'color' => '#3498db']
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get icon name, value and FA icon
    $iconName = trim($_POST['icon_name'] ?? '');
    $iconValue = trim($_POST['icon_value'] ?? '');
    $faIconName = trim($_POST['fa_icon'] ?? '');
    $iconColor = trim($_POST['icon_color'] ?? '#000000');
    
    // Validate inputs
    if (empty($iconName) || empty($iconValue) || empty($faIconName)) {
        $message = 'All fields are required.';
        $messageType = 'danger';
    } else {
        // Validate icon value (alphanumeric and underscores only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $iconValue)) {
            $message = 'Icon value can only contain letters, numbers, and underscores.';
            $messageType = 'danger';
        } else {
            // Create icons directory if it doesn't exist
            $iconDir = 'images/icons/';
            if (!is_dir($iconDir)) {
                mkdir($iconDir, 0755, true);
            }
            
            // Set destination path
            $svgFileName = $iconValue . '.svg';
            $svgFilePath = $iconDir . $svgFileName;
            
            // Check if file already exists
            if (file_exists($svgFilePath)) {
                $message = 'An icon with this value already exists. Please choose a different value.';
                $messageType = 'danger';
            } else {
                // Get Font Awesome classes (strip "fa-" prefix if it exists)
                $faClass = str_starts_with($faIconName, 'fa-') ? $faIconName : 'fa-' . $faIconName;
                
                // Create simple SVG wrapper for Font Awesome icon
                $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
    <!-- Font Awesome Icon: $faClass -->
    <path fill="$iconColor" d="M500.3 227.7c-20.8-38.5-51.1-70-89.5-91.3C388.8 123 352.6 112 313.4 112c-15.9 0-31.9 2.3-47.5 6.7-14.8 4.2-28.9 10.2-42.1 17.9C203.2 103.4 165.4 80 123.8 80c-10.1 0-20.3 1.2-30.2 3.5C63.1 91.7 35.3 112.2 17.3 139.4 6.1 158.6 0 180.2 0 202.6c0 48.2 18.7 91.4 53.4 124 34.7 32.6 80.9 53.4 132.6 59.7l9.1 1.1v104.8c0 11 8.9 19.9 20 19.9 11 0 20-8.9 20-19.9V386.9c38.8-2.4 75.4-13.5 107.9-32.6 34.6-20.5 62.8-49.6 81.7-84.2 18.9-34.7 28.5-74 28.5-113.7 0-9.9-.8-19.8-2.5-29.5zM272 236c0 24.3-11.5 46.8-32 63.4-21.9 17.8-52.4 29.1-85.8 31.7l-9.1.7v-92.3c0-11-9-20-20-20s-20 9-20 20V335l-2.2-.4c-42.3-7.6-82.5-28.5-110-54.6-25.5-24.1-39.6-56.9-39.6-92.5 0-15.2 4.1-30 11.4-42.5 12.5-21.6 34.5-38.3 58.6-44.4 7.9-2 16.1-3 24.1-3 30.5 0 58.3 16.9 73 44.1l1.5 2.8 2.7-1.5c13.3-7.5 27.5-13.1 42.3-16.7 12.2-3.5 24.9-5.3 37.6-5.3 31 0 60.1 9 84.6 25.8 30.9 21.1 54.2 52.2 67.8 90.2 1.3 3.6 2.3 7.2 3.1 10.9H345.1c-40.4 0-73.1-32.7-73.1-73.1 0-11-9-20-20-20s-20 9-20 20V236z" class="$faClass"/>
</svg>
SVG;
                
                // Save SVG file
                if (file_put_contents($svgFilePath, $svgContent)) {
                    // Create HTML preview file
                    $htmlFilePath = $iconDir . $iconValue . '.html';
                    $htmlContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>$iconName Icon</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .icon {
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>
    <img src="$svgFileName" class="icon" alt="$iconName Icon">
</body>
</html>
HTML;
                    file_put_contents($htmlFilePath, $htmlContent);
                    
                    // Create success message
                    $message = "Font Awesome icon \"$iconName\" added successfully!";
                    $messageType = 'success';
                    
                    // Clear form values
                    $iconName = '';
                    $iconValue = '';
                    $faIconName = '';
                } else {
                    $message = 'Failed to create SVG file.';
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Get all existing icons
$existingIcons = getAvailableIcons();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Font Awesome Icon - SRC Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .icon-preview {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
        }
        .icon-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .icon-card:hover {
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .icon-name {
            margin-top: 5px;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .icon-value {
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
        .fa-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Create Font Awesome Icon</h1>
        
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="pages_php/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="pages_php/settings.php">Settings</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Font Awesome Icon</li>
            </ol>
        </nav>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Create Icon from Font Awesome</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="icon_name" class="form-label">Icon Name</label>
                        <input type="text" class="form-control" id="icon_name" name="icon_name" value="<?php echo htmlspecialchars($iconName); ?>" required>
                        <div class="form-text">Display name for the icon (e.g., "Cross", "Bible")</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon_value" class="form-label">Icon Value</label>
                        <input type="text" class="form-control" id="icon_value" name="icon_value" value="<?php echo htmlspecialchars($iconValue); ?>" required>
                        <div class="form-text">Technical name used in code (e.g., "cross", "bible"). Use only letters, numbers, and underscores.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fa_icon" class="form-label">Font Awesome Icon</label>
                        <input type="text" class="form-control" id="fa_icon" name="fa_icon" value="<?php echo htmlspecialchars($faIconName); ?>" required>
                        <div class="form-text">Font Awesome icon name (e.g., "church", "bible", "cross"). You can add "fa-" prefix or omit it.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon_color" class="form-label">Icon Color</label>
                        <input type="color" class="form-control form-control-color" id="icon_color" name="icon_color" value="#000000">
                        <div class="form-text">Choose a color for the icon.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Create Icon</button>
                        <a href="pages_php/settings.php" class="btn btn-outline-secondary">Back to Settings</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Font Awesome Icon Examples</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Click on an icon to use it:</p>
                <div class="icon-grid">
                    <?php foreach ($faIcons as $value => $icon): ?>
                    <div class="icon-card fa-icon-card" data-value="<?php echo $value; ?>" data-name="<?php echo $icon['name']; ?>" data-fa="<?php echo $icon['fa']; ?>" data-color="<?php echo $icon['color']; ?>">
                        <i class="fa <?php echo $icon['fa']; ?>" style="color: <?php echo $icon['color']; ?>"></i>
                        <div class="icon-name"><?php echo $icon['name']; ?></div>
                        <div class="icon-value"><?php echo $value; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Existing Icons</h5>
            </div>
            <div class="card-body">
                <div class="icon-grid">
                    <?php foreach ($existingIcons as $icon): ?>
                    <div class="icon-card">
                        <img src="<?php echo str_replace('../', '', $icon['path']); ?>" alt="<?php echo htmlspecialchars($icon['name']); ?>" class="icon-preview">
                        <div class="icon-name"><?php echo htmlspecialchars($icon['name']); ?></div>
                        <div class="icon-value"><?php echo htmlspecialchars($icon['value']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-generate icon value from name
        document.getElementById('icon_name').addEventListener('input', function() {
            const iconName = this.value.trim();
            const iconValue = document.getElementById('icon_value');
            
            // Only auto-generate if the user hasn't manually entered a value
            if (iconValue.value === '' || iconValue.value === iconValue.defaultValue) {
                iconValue.value = iconName
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '')  // Remove special characters
                    .replace(/\s+/g, '_');        // Replace spaces with underscores
            }
        });
        
        // Handle icon selection
        document.querySelectorAll('.fa-icon-card').forEach(card => {
            card.addEventListener('click', function() {
                const iconName = this.getAttribute('data-name');
                const iconValue = this.getAttribute('data-value');
                const faIcon = this.getAttribute('data-fa');
                const iconColor = this.getAttribute('data-color');
                
                document.getElementById('icon_name').value = iconName;
                document.getElementById('icon_value').value = iconValue;
                document.getElementById('fa_icon').value = faIcon;
                document.getElementById('icon_color').value = iconColor;
                
                // Scroll back to form
                document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html> 