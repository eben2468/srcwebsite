<?php
/**
 * Profile Picture Helper Functions
 * Unified functions for handling profile picture display across the application
 */

/**
 * Get the correct profile picture path for display
 * @param string $filename The profile picture filename from database
 * @param string $context The context from which this is called ('root', 'pages_php', 'admin')
 * @return string The correct path to the profile picture or empty string if not found
 */
function getProfilePicturePath($filename, $context = 'pages_php') {
    if (empty($filename) || $filename === 'default.jpg') {
        return '';
    }
    
    // Define possible paths based on context
    $possiblePaths = [];
    
    switch ($context) {
        case 'root':
            $possiblePaths = [
                'images/profiles/' . $filename,
                'uploads/profile_pictures/' . $filename
            ];
            break;
        case 'support':
            $possiblePaths = [
                '../../images/profiles/' . $filename,
                '../../../uploads/profile_pictures/' . $filename,
                '../../uploads/profile_pictures/' . $filename
            ];
            break;
        case 'pages_php':
        case 'admin':
        default:
            $possiblePaths = [
                '../images/profiles/' . $filename,
                '../../uploads/profile_pictures/' . $filename,
                '../uploads/profile_pictures/' . $filename
            ];
            break;
    }
    
    // Check each possible path
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return '';
}

/**
 * Display profile picture with fallback to old avatar-circle design
 * @param array $user User data array containing profile_picture, first_name, last_name, username
 * @param string $context Context for path resolution
 * @param array $options Display options (width, height, class, style)
 * @return string HTML for profile picture or avatar-circle
 */
function displayProfilePicture($user, $context = 'pages_php', $options = []) {
    // Default options
    $defaults = [
        'width' => 40,
        'height' => 40,
        'class' => 'rounded-circle profile-picture',
        'style' => 'object-fit: cover;',
        'show_initials' => true
    ];
    $options = array_merge($defaults, $options);

    $profilePicturePath = getProfilePicturePath($user['profile_picture'] ?? '', $context);

    if (!empty($profilePicturePath)) {
        return sprintf(
            '<img src="%s" alt="Profile Picture" class="%s" style="width: %dpx; height: %dpx; %s">',
            htmlspecialchars($profilePicturePath),
            htmlspecialchars($options['class']),
            $options['width'],
            $options['height'],
            htmlspecialchars($options['style'])
        );
    } elseif ($options['show_initials']) {
        // Generate initials
        $firstName = $user['first_name'] ?? '';
        $lastName = $user['last_name'] ?? '';
        $username = $user['username'] ?? '';

        if (!empty($firstName) && !empty($lastName)) {
            $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
        } elseif (!empty($firstName)) {
            $initials = strtoupper(substr($firstName, 0, 1));
        } elseif (!empty($username)) {
            $initials = strtoupper(substr($username, 0, 1));
        } else {
            $initials = '?';
        }

        // Create a modern avatar with better alignment and visibility
        $circleStyle = sprintf(
            'width: %dpx; height: %dpx; background: linear-gradient(135deg, #667eea 0%%, #764ba2 100%%); border-radius: 50%%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: %dpx; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);',
            $options['width'],
            $options['height'],
            max(12, $options['width'] * 0.4)
        );

        return sprintf(
            '<div class="avatar-initials" style="%s">%s</div>',
            $circleStyle,
            $initials
        );
    }

    return '';
}

/**
 * Get user initials for avatar display
 * @param array $user User data array
 * @return string User initials (1-2 characters)
 */
function getUserInitials($user) {
    $firstName = $user['first_name'] ?? '';
    $lastName = $user['last_name'] ?? '';
    $username = $user['username'] ?? '';
    
    if (!empty($firstName) && !empty($lastName)) {
        return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    } elseif (!empty($firstName)) {
        return strtoupper(substr($firstName, 0, 1));
    } elseif (!empty($username)) {
        return strtoupper(substr($username, 0, 1));
    }
    
    return '?';
}
?>