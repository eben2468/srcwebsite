<?php
/**
 * Version Configuration File
 * This file contains version information for the VVUSRC Management System
 */

// System version information
define('SYSTEM_VERSION', '2.1.0');
define('SYSTEM_BUILD', '20250106');
define('SYSTEM_CODENAME', 'Phoenix');
define('SYSTEM_RELEASE_DATE', '2025-01-06');

// Version history for tracking
$version_history = [
    '2.1.0' => [
        'release_date' => '2025-01-06',
        'codename' => 'Phoenix',
        'features' => [
            'Bulk image upload in gallery',
            'Ghana Cedis currency support',
            'Dynamic version tracking',
            'Enhanced footer functionality',
            'Complete finance management system'
        ],
        'fixes' => [
            'Database structure improvements',
            'UI/UX enhancements',
            'Performance optimizations'
        ]
    ],
    '2.0.0' => [
        'release_date' => '2024-12-15',
        'codename' => 'Renaissance',
        'features' => [
            'Complete system redesign',
            'Modern dashboard interface',
            'Enhanced user management',
            'Financial management system',
            'Document management',
            'Event management',
            'Gallery system'
        ]
    ],
    '1.0.0' => [
        'release_date' => '2024-01-01',
        'codename' => 'Genesis',
        'features' => [
            'Initial system release',
            'Basic user authentication',
            'Simple dashboard',
            'Basic CRUD operations'
        ]
    ]
];

/**
 * Get current system version
 * @return string
 */
function getSystemVersion() {
    return SYSTEM_VERSION;
}

/**
 * Get system build number
 * @return string
 */
function getSystemBuild() {
    return SYSTEM_BUILD;
}

/**
 * Get system codename
 * @return string
 */
function getSystemCodename() {
    return SYSTEM_CODENAME;
}

/**
 * Get system release date
 * @return string
 */
function getSystemReleaseDate() {
    return SYSTEM_RELEASE_DATE;
}

/**
 * Get formatted version string
 * @param bool $includeCodename
 * @param bool $includeBuild
 * @return string
 */
function getFormattedVersion($includeCodename = false, $includeBuild = false) {
    $version = 'v' . SYSTEM_VERSION;
    
    if ($includeCodename) {
        $version .= ' "' . SYSTEM_CODENAME . '"';
    }
    
    if ($includeBuild) {
        $version .= ' (Build ' . SYSTEM_BUILD . ')';
    }
    
    return $version;
}

/**
 * Get version history
 * @return array
 */
function getVersionHistory() {
    global $version_history;
    return $version_history;
}

/**
 * Get latest version info
 * @return array
 */
function getLatestVersionInfo() {
    global $version_history;
    return $version_history[SYSTEM_VERSION] ?? [];
}

/**
 * Check if system is up to date (placeholder for future update checking)
 * @return bool
 */
function isSystemUpToDate() {
    // This could be enhanced to check against a remote server
    return true;
}

/**
 * Get system status
 * @return array
 */
function getSystemStatus() {
    return [
        'version' => SYSTEM_VERSION,
        'build' => SYSTEM_BUILD,
        'codename' => SYSTEM_CODENAME,
        'release_date' => SYSTEM_RELEASE_DATE,
        'status' => 'online',
        'uptime' => getSystemUptime(),
        'last_update' => SYSTEM_RELEASE_DATE
    ];
}

/**
 * Get system uptime (simplified calculation)
 * @return string
 */
function getSystemUptime() {
    $releaseTimestamp = strtotime(SYSTEM_RELEASE_DATE);
    $currentTimestamp = time();
    $uptimeSeconds = $currentTimestamp - $releaseTimestamp;
    
    $days = floor($uptimeSeconds / 86400);
    $hours = floor(($uptimeSeconds % 86400) / 3600);
    
    if ($days > 0) {
        return $days . ' days, ' . $hours . ' hours';
    } else {
        return $hours . ' hours';
    }
}
?>
