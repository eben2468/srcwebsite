<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if committees feature is enabled
if (!hasFeaturePermission('enable_committees')) {
    $_SESSION['error'] = "Committees feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Check for admin status - use unified admin interface check for super admin users
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();

// Get unique committees from minutes table
try {
    $committees = [];
    $committeeSql = "SELECT DISTINCT committee FROM minutes ORDER BY committee";
    $committeesResult = fetchAll($committeeSql);
    $committees = array_column($committeesResult, 'committee');
} catch (Exception $e) {
    // Silent error handling
}

/**
 * Function to output committee content - either from database or static HTML
 * 
 * @param string $committeeName The name of the committee
 * @param array $updatedCommittees Array of committees from database
 * @param string $purpose Default purpose text if not in database
 * @param string $composition Default composition HTML if not in database
 * @param string $responsibilities Default responsibilities HTML if not in database
 * @return void
 */
function renderCommitteeContent($committeeName, $updatedCommittees, $purpose, $composition, $responsibilities) {
    if (isset($updatedCommittees[$committeeName])) {
        $committee = $updatedCommittees[$committeeName];
        // Debug comment to indicate data source
        echo "<!-- Using database content for $committeeName -->";
        ?>
        <p class="mb-3"><strong>Purpose:</strong> <?php echo htmlspecialchars($committee['purpose']); ?></p>
        
        <?php if (!empty($committee['composition'])): ?>
        <h6><i class="fas fa-users me-2"></i>Composition:</h6>
        <?php echo $committee['composition']; ?>
        <?php endif; ?>
        
        <?php if (!empty($committee['responsibilities'])): ?>
        <h6><i class="fas fa-tasks me-2"></i>Key Responsibilities:</h6>
        <?php echo $committee['responsibilities']; ?>
        <?php endif; ?>
        
        <?php if (!empty($committee['description'])): ?>
        <h6><i class="fas fa-info-circle me-2"></i>Additional Information:</h6>
        <p><?php echo htmlspecialchars($committee['description']); ?></p>
        <?php endif;
    } else {
        // Debug comment to indicate data source
        echo "<!-- Using default static content for $committeeName -->";
        ?>
        <p class="mb-3"><strong>Purpose:</strong> <?php echo $purpose; ?></p>
        
        <h6><i class="fas fa-users me-2"></i>Composition:</h6>
        <?php echo $composition; ?>
        
        <h6><i class="fas fa-tasks me-2"></i>Key Responsibilities:</h6>
        <?php echo $responsibilities; ?>
        <?php
    }
}

// Page title
$pageTitle = "Committees - SRC Management System";

// Include header
require_once 'includes/header.php';

// Add cache-busting meta tags
echo '<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />';
echo '<meta http-equiv="Pragma" content="no-cache" />';
echo '<meta http-equiv="Expires" content="0" />';

// Check for updated static committees in the database
$updatedStaticCommittees = [];
try {
    $staticCommitteeNames = [
        "Finance Committee", 
        "Welfare Committee", 
        "President\'s Committee", 
        "Public Relations Committee", 
        "Organizing Committee", 
        "Judicial Committee", 
        "Audit Board", 
        "Editorial Committee",
        "Electoral Commission",
        "Vetting Committee",
        "Constitutional Amendment Committee"
    ];
    
    // Convert array to comma-separated quoted strings for SQL
    $committeeNamesList = implode("','", array_map(function($name) {
        return str_replace("'", "''", $name);
    }, $staticCommitteeNames));
    
    // Add a debug log
    error_log("Fetching updated committee data for: " . implode(", ", $staticCommitteeNames));
    
    // Use a cache-busting query parameter to ensure fresh data
    $cacheParam = isset($_GET['updated']) ? intval($_GET['updated']) : time();
    
    $updatedCommitteesSql = "SELECT * FROM committees WHERE name IN ('$committeeNamesList') ORDER BY updated_at DESC";
    $updatedCommitteesResult = fetchAll($updatedCommitteesSql);
    
    // Create associative array with committee name as key for easy lookup
    if (!empty($updatedCommitteesResult)) {
        foreach ($updatedCommitteesResult as $committee) {
            $updatedStaticCommittees[$committee['name']] = $committee;
            // Add debug log for each committee found
            error_log("Found committee in DB: " . $committee['name'] . ", Last updated: " . $committee['updated_at']);
        }
    }
} catch (Exception $e) {
    // Log error for debugging
    error_log("Error fetching committees: " . $e->getMessage());
}

?>

<!-- Modern CSS for Committee Cards -->
<style>
    /* Modern Page Layout */
    .modern-container {
        min-height: 100vh;
        padding: 2rem 0;
    }

    .page-hero {
        background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.9) 100%);
        border-radius: 20px;
        padding: 3rem 2rem;
        margin-top: 60px; /* 60px margin between page title header and system's main header */
        margin-bottom: 2rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
    }

    .page-hero h1 {
        font-size: 3rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 1rem;
    }

    .page-hero p {
        font-size: 1.2rem;
        color: #6c757d;
        margin-bottom: 0;
    }

    /* Modern Committee Card Styling */
    .committee-card {
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
        border: none;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        position: relative;
    }

    .committee-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 20px;
        pointer-events: none; /* Ensure overlay doesn't block clicks */
    }

    .committee-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 25px 50px rgba(0,0,0,0.15);
    }

    .committee-card:hover::before {
        opacity: 1;
    }

    .committee-card .card-header {
        padding: 1.5rem;
        border-bottom: none;
        border-radius: 20px 20px 0 0;
        position: relative;
        overflow: hidden;
        background: none !important;
    }

    /* Modern gradient backgrounds for headers */
    .committee-card .card-header.finance-gradient {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    }

    .committee-card .card-header.welfare-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .committee-card .card-header.president-gradient {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    }

    .committee-card .card-header.pr-gradient {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    }

    .committee-card .card-header.organizing-gradient {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    }

    .committee-card .card-header.judicial-gradient {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }

    .committee-card .card-header.audit-gradient {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%) !important;
    }

    .committee-card .card-header.editorial-gradient {
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
    }

    .committee-card .card-header.electoral-gradient {
        background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%) !important;
    }

    .committee-card .card-header.vetting-gradient {
        background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%) !important;
    }

    .committee-card .card-header.constitutional-gradient {
        background: linear-gradient(135deg, #fad0c4 0%, #ffd1ff 100%) !important;
    }

    .committee-card .card-header.adhoc-gradient {
        background: linear-gradient(135deg, #ff9a56 0%, #ffad56 100%) !important;
    }

    /* Modern header content styling */
    .committee-card .card-header .d-flex {
        position: relative;
        z-index: 3;
        padding: 1rem;
        border-radius: 15px;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        width: 100%;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .committee-card .card-header h5 {
        font-weight: 700;
        position: relative;
        z-index: 1;
        color: #2d3748;
        font-size: 1.3rem;
        letter-spacing: 0.5px;
        margin: 0;
    }

    /* Modern icon styling */
    .committee-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 2px solid rgba(255,255,255,0.8);
    }

    .committee-card:hover .committee-icon {
        transform: scale(1.15) rotate(5deg);
        box-shadow: 0 12px 30px rgba(0,0,0,0.2);
    }

    .committee-icon i {
        font-size: 1.4rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Modern content card styling */
    .content-card {
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        margin-bottom: 2rem;
    }

    .content-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .content-card-header {
        border-radius: 20px 20px 0 0;
        padding: 2rem 2rem 1.5rem;
        background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,249,250,0.95) 100%) !important;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .content-card-title {
        font-size: 2.2rem !important;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin: 0;
    }

    .content-card-body {
        padding: 2rem;
    }

    /* Modern committee content styling */
    .committee-content {
        max-height: 350px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(102,126,234,0.3) transparent;
        padding-right: 10px;
    }

    .committee-content::-webkit-scrollbar {
        width: 8px;
    }

    .committee-content::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 10px;
    }

    .committee-content::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        border: 2px solid transparent;
        background-clip: content-box;
    }

    .committee-content::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        background-clip: content-box;
    }

    /* Modern committee example styling */
    .committee-example {
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
        border-left: 4px solid;
        border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
        padding: 1rem;
        margin-bottom: 0.75rem;
    }

    .committee-example:hover {
        transform: translateX(8px) scale(1.02);
        box-shadow: 0 8px 25px rgba(102,126,234,0.2);
        background: linear-gradient(135deg, rgba(102,126,234,0.15) 0%, rgba(118,75,162,0.15) 100%);
    }

    /* Modern active period styling */
    .active-period {
        background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%);
        padding: 1.5rem;
        border-radius: 15px;
        border: 2px solid rgba(102,126,234,0.2);
        backdrop-filter: blur(5px);
        position: relative;
        z-index: 5;
    }

    /* Modern button styling */
    .committee-actions .btn {
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border-radius: 25px;
        padding: 0.5rem 1.2rem;
        font-weight: 600;
        border: 2px solid transparent;
        position: relative;
        z-index: 10; /* Ensure buttons are above any overlays */
    }

    .committee-actions .btn-outline-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }

    .committee-actions .btn-outline-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    }

    .committee-actions .btn-outline-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        border: none;
    }

    .committee-actions .btn-outline-secondary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(108,117,125,0.3);
    }

    /* Modern admin actions panel */
    .admin-actions-panel {
        background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(248,249,250,0.95) 100%);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        margin-bottom: 2rem;
        position: relative;
        z-index: 5;
    }

    .admin-actions-panel .btn {
        border-radius: 25px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: none;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .admin-actions-panel .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    /* Responsive design improvements */
    @media (max-width: 768px) {
        .page-hero {
            padding: 2rem 1rem;
            margin-bottom: 1rem;
        }

        .page-hero h1 {
            font-size: 2.2rem;
        }

        .committee-icon {
            width: 40px;
            height: 40px;
        }

        .committee-icon i {
            font-size: 1.2rem;
        }

        .committee-card .card-header .d-flex {
            padding: 0.75rem;
        }

        .committee-card .card-header h5 {
            font-size: 1.1rem;
        }

        .content-card-header {
            padding: 1.5rem 1rem 1rem;
        }

        .content-card-title {
            font-size: 1.8rem !important;
        }

        .content-card-body {
            padding: 1.5rem 1rem;
        }
    }

    @media (max-width: 576px) {
        .modern-container {
            padding: 1rem 0;
        }

        .page-hero h1 {
            font-size: 1.8rem;
        }

        .page-hero p {
            font-size: 1rem;
        }

        .admin-actions-panel {
            padding: 1rem;
        }

        .admin-actions-panel .btn {
            width: 100%;
            margin-right: 0;
        }
    }
    
    /* Mobile Column Padding Override for Full-Width Cards */
    @media (max-width: 991px) {
        [class*="col-md-"] {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        
        /* Remove container padding on mobile for full width */
        .modern-container {
            padding: 0 !important;
        }
        
        .container-fluid {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        
        /* Ensure page hero extends full width */
        .page-hero {
            margin-left: 0 !important;
            margin-right: 0 !important;
            border-radius: 12px !important;
        }
        
        /* Ensure content cards extend full width */
        .content-card,
        .committee-card {
            margin-left: 0 !important;
            margin-right: 0 !important;
            border-radius: 0 !important;
        }
    }
</style>

<div class="modern-container">
    <div class="container-fluid px-4">
        <script>
            document.body.classList.add('committees-page');
        </script>

        <?php
        // Set up modern page header variables
        $pageTitle = "SRC Committees";
        $pageIcon = "fa-users";
        $pageDescription = "Discover the organizational structure and committees that drive student governance";
        $actions = [];

        if (shouldUseAdminInterface()) {
            $actions[] = [
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#addCommitteeModal',
                'icon' => 'fa-plus',
                'text' => 'Add Committee',
                'class' => 'btn-primary',
                'id' => 'addCommitteeBtn'
            ];
            $actions[] = [
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#manageCommitteesModal',
                'icon' => 'fa-list',
                'text' => 'Manage',
                'class' => 'btn-primary',
                'id' => 'manageCommitteesBtn'
            ];
        }

        // Include the modern page header
        include 'includes/modern_page_header.php';
        ?>

        <!-- Debug Script for Buttons -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Debugging committees buttons...');
            
            // Check if buttons exist
            const addBtn = document.getElementById('addCommitteeBtn');
            const manageBtn = document.getElementById('manageCommitteesBtn');
            
            console.log('Add Committee Button:', addBtn ? '✅ Found' : '❌ Not found');
            console.log('Manage Button:', manageBtn ? '✅ Found' : '❌ Not found');
            
            if (addBtn) {
                console.log('Add Button attributes:', {
                    'data-bs-toggle': addBtn.getAttribute('data-bs-toggle'),
                    'data-bs-target': addBtn.getAttribute('data-bs-target'),
                    'id': addBtn.id,
                    'class': addBtn.className
                });
                
                // Add click event listener
                addBtn.addEventListener('click', function(e) {
                    console.log('🖱️ Add Committee button clicked!');
                    console.log('Event:', e);
                    
                    // Check if modal exists
                    const modal = document.getElementById('addCommitteeModal');
                    console.log('Add Committee Modal:', modal ? '✅ Found' : '❌ Not found');
                    
                    if (modal && typeof bootstrap !== 'undefined') {
                        console.log('🚀 Attempting to show modal manually...');
                        const modalInstance = new bootstrap.Modal(modal);
                        modalInstance.show();
                    }
                });
            }
            
            if (manageBtn) {
                console.log('Manage Button attributes:', {
                    'data-bs-toggle': manageBtn.getAttribute('data-bs-toggle'),
                    'data-bs-target': manageBtn.getAttribute('data-bs-target'),
                    'id': manageBtn.id,
                    'class': manageBtn.className
                });
                
                // Add click event listener
                manageBtn.addEventListener('click', function(e) {
                    console.log('🖱️ Manage button clicked!');
                    console.log('Event:', e);
                    
                    // Check if modal exists
                    const modal = document.getElementById('manageCommitteesModal');
                    console.log('Manage Committees Modal:', modal ? '✅ Found' : '❌ Not found');
                    
                    if (modal && typeof bootstrap !== 'undefined') {
                        console.log('🚀 Attempting to show modal manually...');
                        const modalInstance = new bootstrap.Modal(modal);
                        modalInstance.show();
                    }
                });
            }
            
            // Check Bootstrap availability
            console.log('Bootstrap available:', typeof bootstrap !== 'undefined' ? '✅ Yes' : '❌ No');
            
            // Check if modals exist
            const addModal = document.getElementById('addCommitteeModal');
            const manageModal = document.getElementById('manageCommitteesModal');
            console.log('Add Committee Modal exists:', addModal ? '✅ Yes' : '❌ No');
            console.log('Manage Committees Modal exists:', manageModal ? '✅ Yes' : '❌ No');
        });
        </script>

        <!-- Modal Backdrop Cleanup Script -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧹 Setting up modal backdrop cleanup...');
            
            // Function to remove all modal backdrops
            function removeAllBackdrops() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    console.log('🗑️ Removing backdrop:', backdrop);
                    backdrop.remove();
                });
                
                // Reset body styles
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                console.log('✅ Body styles reset');
            }
            
            // Function to setup modal cleanup for a specific modal
            function setupModalCleanup(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;
                
                console.log('🔧 Setting up cleanup for modal:', modalId);
                
                // Listen for modal hide events
                modal.addEventListener('hide.bs.modal', function() {
                    console.log('🚪 Modal hiding:', modalId);
                    setTimeout(removeAllBackdrops, 100);
                });
                
                modal.addEventListener('hidden.bs.modal', function() {
                    console.log('🚪 Modal hidden:', modalId);
                    setTimeout(removeAllBackdrops, 100);
                });
                
                // Setup close button cleanup
                const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        console.log('❌ Close button clicked for:', modalId);
                        setTimeout(removeAllBackdrops, 200);
                    });
                });
            }
            
            // Setup cleanup for all modals
            setupModalCleanup('addCommitteeModal');
            setupModalCleanup('manageCommitteesModal');
            setupModalCleanup('addCommitteeMemberModal');
            setupModalCleanup('deleteCommitteeModal');
            setupModalCleanup('deleteCommitteeMemberModal');
            setupModalCleanup('deleteCommitteeMeetingModal');
            
            // Global escape key handler
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    console.log('⌨️ Escape key pressed - cleaning up modals');
                    setTimeout(removeAllBackdrops, 100);
                }
            });
            
            // Global click outside modal handler
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    console.log('🖱️ Clicked outside modal - cleaning up');
                    setTimeout(removeAllBackdrops, 100);
                }
            });
            
            // Periodic cleanup (safety net)
            setInterval(function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    console.log('🔄 Periodic cleanup - found', backdrops.length, 'backdrops');
                    removeAllBackdrops();
                }
            }, 1000);
            
            // Initial cleanup on page load
            setTimeout(removeAllBackdrops, 500);
        });
        </script>

        <style>
        /* Modal backdrop fix */
        .modal-backdrop {
            display: none !important;
        }
        
        .modal {
            background: rgba(0, 0, 0, 0.5) !important;
        }
        
        .modal.show {
            background: rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Ensure body doesn't get stuck with modal-open class */
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }
        
        /* Ensure modals are properly positioned */
        .modal-dialog {
            position: relative;
            z-index: 1055;
        }
        
        .committees-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 12px;
            margin-top: 60px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .committees-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .committees-header-main {
            flex: 1;
            text-align: center;
        }

        .committees-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 1rem 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .committees-title i {
            font-size: 2.2rem;
            opacity: 0.9;
        }

        .committees-description {
            margin: 0;
            opacity: 0.95;
            font-size: 1.2rem;
            font-weight: 400;
            line-height: 1.4;
        }

        .committees-header-actions {
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
            cursor: pointer;
            z-index: 1000;
            position: relative;
            pointer-events: auto;
        }

        .btn-header-action:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .committees-header {
                padding: 2rem 1.5rem;
            }

            .committees-header-content {
                flex-direction: column;
                align-items: center;
            }

            .committees-title {
                font-size: 2rem;
                gap: 0.6rem;
            }

            .committees-title i {
                font-size: 1.8rem;
            }

            .committees-description {
                font-size: 1.1rem;
            }

            .committees-header-actions {
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
        </style>



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

    <!-- Committees Overview -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h4 class="content-card-title">
                <i class="fas fa-sitemap me-2"></i>Committee Structure
            </h4>
        </div>
        <div class="content-card-body">
            <p class="lead">According to Article XII of the VVUSRC Constitution, the SRC operates through a system of committees that focus on specific areas of student governance and welfare. These committees allow for specialized attention to various aspects of student life and efficient management of SRC operations.</p>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card committee-card mb-4 h-100">
                        <div class="card-header finance-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-landmark"></i>
                                </div>
                                <h5 class="card-title mb-0">Standing Committees</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>The Constitution establishes eight standing committees to handle ongoing SRC functions:</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">1</span>
                                            Finance Committee
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">2</span>
                                            Welfare Committee
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">3</span>
                                            President's Committee
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">4</span>
                                            Public Relations Committee
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">5</span>
                                            Organizing Committee
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">6</span>
                                            Judicial Committee
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">7</span>
                                            Audit Board
                                        </li>
                                        <li class="list-group-item d-flex align-items-center">
                                            <span class="badge rounded-pill bg-primary me-2">8</span>
                                            Editorial Committee
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card committee-card mb-4 h-100">
                        <div class="card-header president-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <h5 class="card-title mb-0">Ad Hoc Committees</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>According to Article XII Section 9 of the VVUSRC Constitution, the Executive has the power to form adhoc Committees when necessary and dissolve them when the work is completed.</p>
                            <p>Notable examples include:</p>
                            <div class="d-flex flex-column gap-2">
                                <div class="committee-example p-2 rounded" style="background-color: rgba(131,77,155,0.1); border-left: 3px solid #834d9b;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-vote-yea text-purple me-2" style="color: #834d9b;"></i>
                                        <strong>Electoral Commission</strong>
                                    </div>
                                </div>
                                <div class="committee-example p-2 rounded" style="background-color: rgba(131,77,155,0.1); border-left: 3px solid #834d9b;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clipboard-check text-purple me-2" style="color: #834d9b;"></i>
                                        <strong>Vetting Committee</strong>
                                    </div>
                                </div>
                                <div class="committee-example p-2 rounded" style="background-color: rgba(131,77,155,0.1); border-left: 3px solid #834d9b;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-balance-scale text-purple me-2" style="color: #834d9b;"></i>
                                        <strong>Constitutional Amendment Committee</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-primary mt-3 d-flex">
                <div class="me-3 fs-4">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <strong>Constitutional Basis:</strong> The committee structure is defined in Article XII of the VVUSRC Constitution, which outlines the composition, duties, and responsibilities of each committee.
                </div>
            </div>
        </div>
    </div>

    <!-- Standing Committees -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h4 class="content-card-title">
                <i class="fas fa-landmark me-2"></i>Standing Committees
            </h4>
        </div>
        <div class="content-card-body">
            <div class="row">
                <!-- Finance Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header finance-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <h5 class="card-title mb-0">Finance Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Finance Committee', $updatedStaticCommittees, 'Manages SRC finances, budgeting, and financial planning.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer, main campus (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer, Techiman campus</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer, Kumasi campus</li><li><i class="fas fa-check-circle me-2 text-success"></i>President from the School of Business Student\'s Association</li><li><i class="fas fa-check-circle me-2 text-success"></i>Senate President</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (Financial Administration)</li></ul>', '<ul><li>Prepare and present a budget to the Administrative Committee</li><li>Coordinate with Finance Officers of each Campus</li><li>Raise funds for the Council</li><li>Draw policies regarding financial discipline</li><li>Review existing financial policies</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Finance Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="financeCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="financeCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=finance">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Finance Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Welfare Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header welfare-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-hands-helping"></i>
                                </div>
                                <h5 class="card-title mb-0">Welfare Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Welfare Committee', $updatedStaticCommittees, 'Focuses on student wellbeing, health, accommodation, and general welfare issues.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Vice-President (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Welfare Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Representatives from various halls on campus</li><li><i class="fas fa-check-circle me-2 text-success"></i>Two off-campus representatives</li><li><i class="fas fa-check-circle me-2 text-success"></i>Representatives from distance, summer, sandwich and evening modes</li></ul>', '<ul><li>Address all facets of students\' wellbeing</li><li>Handle matters related to sickness and health</li><li>Support during bereavement and marriage</li><li>Ensure students\' security both in and outside campus</li><li>Develop policies for welfare donations</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Welfare Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="welfareCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welfareCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=welfare">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Welfare Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- President's Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header president-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h5 class="card-title mb-0">President's Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('President\'s Committee', $updatedStaticCommittees, 'Brings together leadership from all campus organizations to develop plans for collective interest.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>SRC President (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>SRC Vice-President</li><li><i class="fas fa-check-circle me-2 text-success"></i>SRC Secretary</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Presidents of other modes</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Presidents of Clubs and Associations</li></ul>', '<ul><li>Develop insightful plans from departmental perspectives</li><li>Work for the collective interest of the Council</li><li>Meet at least twice per semester</li><li>Coordinate activities across all campus organizations</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="President\'s Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="presidentCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="presidentCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=presidents">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="President\'s Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Public Relations Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header pr-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h5 class="card-title mb-0">Public Relations Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Public Relations Committee', $updatedStaticCommittees, 'Handles publicity and promotes the image of the Council.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Public Relations Officer of SRC (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Organizing Secretary</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Public Relations Officers of recognized Clubs, Associations and modes</li></ul>', '<ul><li>Publicize activities of the Council</li><li>Project the image of the Council in and outside campus</li><li>Manage Council\'s public communications</li><li>Coordinate with media outlets</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Public Relations Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="prCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="prCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=publicity">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Public Relations Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Organizing Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header organizing-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h5 class="card-title mb-0">Organizing Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Organizing Committee', $updatedStaticCommittees, 'Implements and manages all programs and social activities of the Council.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>SRC Organizing Secretary (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Sports Commissioner</li><li><i class="fas fa-check-circle me-2 text-success"></i>All Organizing Secretaries of Clubs, Associations and modes</li><li><i class="fas fa-check-circle me-2 text-success"></i>Executive Secretary (Secretary)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Public Relations Officer</li></ul>', '<ul><li>Implement all programs of the Council</li><li>Manage social activities organized by the Council</li><li>Coordinate event logistics</li><li>Ensure successful execution of Council initiatives</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Organizing Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="organizingCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="organizingCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=organizing">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Organizing Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Judicial Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header judicial-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-gavel"></i>
                                </div>
                                <h5 class="card-title mb-0">Judicial Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Judicial Committee', $updatedStaticCommittees, 'Enforces and interprets the constitution and handles disputes.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (General Administration) (Chair)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisor (Financial Administration)</li><li><i class="fas fa-check-circle me-2 text-success"></i>One Senior Hall Assistant from each Hall of Residence</li><li><i class="fas fa-check-circle me-2 text-success"></i>The University Chaplain</li></ul>', '<ul><li>Enforce and interpret provisions of the constitution</li><li>Address cases of power abuse</li><li>Issue sanctions for constitutional violations</li><li>Resolve disputes within the Council</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Judicial Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="judicialCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="judicialCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=judicial">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Judicial Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Audit Board -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header audit-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <h5 class="card-title mb-0">Audit Board</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Audit Board', $updatedStaticCommittees, 'Audits the financial records and operations of the Council.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Qualified Auditors (2-4 members based on student population)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Members with minimum B+ in Intermediate Accounting</li></ul>', '<ul><li>Access and review Council\'s financial books</li><li>Present audit reports to Senate and General Assembly</li><li>Audit the incumbent Administration after elections</li><li>Ensure financial transparency and accountability</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Audit Board" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="auditBoardActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="auditBoardActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=audit">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Audit Board">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Editorial Committee -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header editorial-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                                <h5 class="card-title mb-0">Editorial Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Editorial Committee', $updatedStaticCommittees, 'Responsible for the Council\'s publications and serves as a medium of information and education.', '<ul class="list-unstyled mb-3"><li><i class="fas fa-check-circle me-2 text-success"></i>Editor (Chairman)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Faculty Advisors</li><li><i class="fas fa-check-circle me-2 text-success"></i>Finance Officer</li><li><i class="fas fa-check-circle me-2 text-success"></i>Executive Secretary (Secretary)</li><li><i class="fas fa-check-circle me-2 text-success"></i>SRC President (ex-officio)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Public Relations Officer (ex-officio)</li><li><i class="fas fa-check-circle me-2 text-success"></i>Three students nominated by the Executive</li></ul>', '<ul><li>Compile and edit materials for publication</li><li>Forward materials to Senate for approval</li><li>Publish and circulate approved materials</li><li>Serve as a medium of information and education</li><li>Raise funds for the Council through publications</li></ul>'); ?>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Editorial Committee" data-type="Standing">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="editorialCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="editorialCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=editorial">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Editorial Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ad Hoc Committees -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h4 class="content-card-title">
                <i class="fas fa-project-diagram me-2"></i>Ad Hoc Committees
            </h4>
        </div>
        <div class="content-card-body">
            <p class="lead">According to Article XII Section 9 of the VVUSRC Constitution, the Executive has the power to form adhoc Committees when necessary and dissolve them when the work is completed. These committees have a defined purpose and timeline.</p>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> Committee members are not entitled to allowances, but shall be awarded certificates of merit as stated in the Constitution.
            </div>
            
            <div class="row">
                <!-- Electoral Commission -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header electoral-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                                <h5 class="card-title mb-0">Electoral Commission</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Electoral Commission', $updatedStaticCommittees, 'Organizes and oversees SRC elections to ensure fairness and transparency.', '<ul class="mb-3"><li>Chairman/Chairperson (Electoral Commissioner)</li><li>Two Deputy Commissioners</li><li>Four level representatives (Level 100, 200, 300, and 400)</li></ul>', '<ul><li>Conduct and supervise General Elections</li><li>Open nominations and receive applications</li><li>Pass applications to the Vetting Committee</li><li>Conduct Senate elections</li><li>Supervise all other Clubs and Associations elections</li></ul>'); ?>
                            </div>
                            
                            <div class="active-period mt-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="badge bg-primary rounded-pill p-2 me-2">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Active Period</h6>
                                        <small class="text-muted">Formed during the first semester of the academic year</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="elections.php" class="btn btn-sm btn-outline-primary rounded-pill me-2">
                                            <i class="fas fa-vote-yea me-1"></i> Elections
                                        </a>
                                        <a href="#" class="btn btn-sm btn-info rounded-pill view-committee-details text-white" data-committee="Electoral Commission" data-type="Ad Hoc">
                                            <i class="fas fa-eye me-1"></i> Details
                                        </a>
                                    </div>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="electoralCommissionActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="electoralCommissionActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=electoral">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Electoral Commission">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vetting Committee -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header vetting-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h5 class="card-title mb-0">Vetting Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Vetting Committee', $updatedStaticCommittees, 'Ensures that candidates for elections satisfy the requirements provided in the Constitution.', '<ul class="mb-3"><li>Chairman of the Electoral Commission (Chairman)</li><li>Two deputies of the Electoral Commission</li><li>Dean of Students\' Life and Services</li><li>University Chaplain</li><li>Dean from each hall of residence</li><li>Incumbent officer whose position aspirants are being vetted</li><li>President of the Council</li><li>Senate President</li><li>Faculty member elected to serve on electoral commission</li><li>Four members of Electoral Commission (one as Secretary)</li><li>Council\'s Advisors for the academic year</li></ul>', '<ul><li>Vet candidates for elections</li><li>Ensure candidates meet constitutional requirements</li><li>Disqualify candidates who don\'t satisfy requirements</li><li>Evaluate candidates\' performance during vetting</li></ul>'); ?>
                            </div>
                            
                            <div class="active-period mt-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="badge bg-primary rounded-pill p-2 me-2">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Active Period</h6>
                                        <small class="text-muted">Formed during election periods to vet candidates</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="#" class="btn btn-sm btn-primary rounded-pill view-committee-details text-white" data-committee="Vetting Committee" data-type="Ad Hoc">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="vettingCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="vettingCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=vetting">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Vetting Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Constitutional Amendment Committee -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header constitutional-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <h5 class="card-title mb-0">Constitutional Amendment Committee</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="committee-content">
                                <?php renderCommitteeContent('Constitutional Amendment Committee', $updatedStaticCommittees, 'Reviews and proposes amendments to the SRC Constitution.', '<ul class="mb-3"><li>Faculty Advisor (General Administration) as Chairman</li><li>An Editor from the Executive Officers</li><li>An English Student (Level 300+ with minimum B+ in specific courses)</li><li>A Senate member</li><li>Two Executive members from Departmental Associations</li><li>Representatives from each mode in the University</li><li>A representative from each campus</li></ul>', '<ul><li>Review proposed amendments</li><li>Publish provisions intended to be amended</li><li>Present draft proposals to the General Assembly</li><li>Present final work to the Senate for ratification</li><li>Ensure other Associations are aware of amendments</li></ul>'); ?>
                            </div>
                            
                            <div class="active-period mt-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="badge bg-primary rounded-pill p-2 me-2">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Active Period</h6>
                                        <small class="text-muted">Formed as needed when constitutional amendments are proposed</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="committee-actions mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="documents.php" class="btn btn-sm btn-outline-warning rounded-pill me-2">
                                            <i class="fas fa-file-alt me-1"></i> Documents
                                        </a>
                                        <a href="#" class="btn btn-sm btn-info rounded-pill view-committee-details text-white" data-committee="Constitutional Amendment Committee" data-type="Ad Hoc">
                                            <i class="fas fa-eye me-1"></i> Details
                                        </a>
                                    </div>
                                    <?php if (shouldUseAdminInterface()): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" type="button" id="constitutionalCommitteeActions" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="constitutionalCommitteeActions">
                                            <li>
                                                <a class="dropdown-item" href="static_committee_edit.php?id=constitutional">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-static-committee" data-committee="Constitutional Amendment Committee">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Other Ad Hoc Committees -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 committee-card">
                        <div class="card-header adhoc-gradient">
                            <div class="d-flex align-items-center">
                                <div class="committee-icon me-3">
                                    <i class="fas fa-puzzle-piece"></i>
                                </div>
                                <h5 class="card-title mb-0">Other Ad Hoc Committees</h5>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>The Executive may form other ad hoc committees as needed to address specific issues, projects, or initiatives. These committees are dissolved once their objectives are met.</p>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="committee-example p-2 mb-2 rounded" style="background-color: rgba(255,95,109,0.1); border-left: 3px solid #FF5F6D;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-users-cog text-danger me-2"></i>
                                            <strong>Orientation Committee</strong>
                                        </div>
                                        <small class="text-muted">For welcoming new students</small>
                                    </div>
                                    <div class="committee-example p-2 mb-2 rounded" style="background-color: rgba(255,95,109,0.1); border-left: 3px solid #FF5F6D;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tasks text-danger me-2"></i>
                                            <strong>Project Implementation Committee</strong>
                                        </div>
                                        <small class="text-muted">For specific SRC projects</small>
                                    </div>
                                    <div class="committee-example p-2 mb-2 rounded" style="background-color: rgba(255,95,109,0.1); border-left: 3px solid #FF5F6D;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar-day text-danger me-2"></i>
                                            <strong>Special Events Committee</strong>
                                        </div>
                                        <small class="text-muted">For organizing major events</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="committee-example p-2 mb-2 rounded" style="background-color: rgba(255,95,109,0.1); border-left: 3px solid #FF5F6D;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                            <strong>Crisis Management Committee</strong>
                                        </div>
                                        <small class="text-muted">For addressing emergencies</small>
                                    </div>
                                    <div class="committee-example p-2 mb-2 rounded" style="background-color: rgba(255,95,109,0.1); border-left: 3px solid #FF5F6D;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-search text-danger me-2"></i>
                                            <strong>Research Committee</strong>
                                        </div>
                                        <small class="text-muted">For investigating specific issues</small>
                                    </div>
                                    <div class="committee-example p-2 mb-2 rounded" style="background-color: rgba(255,95,109,0.1); border-left: 3px solid #FF5F6D;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-bullhorn text-danger me-2"></i>
                                            <strong>Advocacy Committee</strong>
                                        </div>
                                        <small class="text-muted">For specific student concerns</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3 d-flex align-items-center">
                                <i class="fas fa-lightbulb me-3 fs-4"></i>
                                <div>
                                    <strong>Have an idea?</strong> If you wish to propose the formation of an ad hoc committee for a specific purpose, please contact the SRC Executive with your proposal.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Committees from Database -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h4 class="content-card-title">
                <i class="fas fa-sitemap me-2"></i>Additional Committees
            </h4>
        </div>
        <div class="content-card-body">
            <p class="lead">The following committees have been created to address specific needs:</p>
            
            <div class="row">
                <?php
                // Fetch committees from database
                try {
                    $dynamicCommitteesSql = "SELECT * FROM committees ORDER BY type, name";
                    $dynamicCommittees = fetchAll($dynamicCommitteesSql);
                    
                    if (!empty($dynamicCommittees)) {
                        foreach ($dynamicCommittees as $committee) {
                            $cardColorClass = $committee['type'] == 'Standing' ? 'border-primary' : 'border-success';
                            $headerColorClass = $committee['type'] == 'Standing' ? 'bg-primary' : 'bg-success';
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 <?php echo $cardColorClass; ?> committee-card">
                                    <div class="card-header <?php echo $headerColorClass; ?> text-white">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($committee['name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-3"><strong>Purpose:</strong> <?php echo htmlspecialchars($committee['purpose']); ?></p>
                                        
                                        <h6><i class="fas fa-info-circle me-2"></i>Type:</h6>
                                        <p class="mb-3"><?php echo htmlspecialchars($committee['type']); ?> Committee</p>
                                        
                                        <?php if (!empty($committee['description'])): ?>
                                        <h6><i class="fas fa-align-left me-2"></i>Description:</h6>
                                        <p class="mb-3"><?php echo htmlspecialchars($committee['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php
                                        // Get committee members
                                        try {
                                            $membersSql = "SELECT * FROM committee_members WHERE committee_id = ? ORDER BY position";
                                            $members = fetchAll($membersSql, [$committee['committee_id']]);
                                            
                                            if (!empty($members)): ?>
                                                <h6><i class="fas fa-users me-2"></i>Members:</h6>
                                                <ul class="mb-3">
                                                    <?php foreach ($members as $member): ?>
                                                        <li>
                                                            <strong><?php echo htmlspecialchars($member['position']); ?>:</strong> 
                                                            <?php echo htmlspecialchars($member['name']); ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif;
                                        } catch (Exception $e) {
                                            // Silent error handling
                                        }
                                        ?>
                                        
                                        <div class="text-center mt-3">
                                            <?php if (shouldUseAdminInterface()): ?>
                                            <div class="btn-group">
                                                <a href="committees_edit.php?id=<?php echo $committee['committee_id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-outline-danger delete-dynamic-committee" data-id="<?php echo $committee['committee_id']; ?>" data-name="<?php echo htmlspecialchars($committee['name']); ?>">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </button>
                                            </div>
                                            <?php else: ?>
                                            <a href="minutes.php?committee=<?php echo urlencode($committee['name']); ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-file-alt me-1"></i> View Minutes
                                            </a>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <a href="#" class="btn btn-sm btn-info view-dynamic-committee-details text-white" data-id="<?php echo $committee['committee_id']; ?>">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="col-12"><div class="alert alert-info">No additional committees have been created yet.</div></div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="col-12"><div class="alert alert-danger">Error loading committees: ' . $e->getMessage() . '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Committee Formation Guide -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <h4 class="content-card-title">
                <i class="fas fa-info-circle me-2"></i>Committee Formation Guide
            </h4>
        </div>
        <div class="content-card-body">
            <div class="row">
                <div class="col-lg-6">
                    <h5 class="section-subheader">How to Form a Committee</h5>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Identify Need</div>
                                Determine the specific purpose and objectives for the committee
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Submit Proposal</div>
                                Present a formal proposal to the Senate or Executive Committee
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Approval</div>
                                Obtain approval from the Senate by majority vote
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Member Selection</div>
                                Appoint or elect committee members based on expertise and interest
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">Establish Guidelines</div>
                                Create committee charter, meeting schedule, and reporting procedures
                            </div>
                        </li>
                    </ol>
                </div>
                <div class="col-lg-6">
                    <h5 class="section-subheader">Committee Best Practices</h5>
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Hold regular meetings with clear agendas
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Keep detailed minutes of all proceedings
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Set specific, measurable goals and timelines
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Regularly report progress to the Senate
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Involve diverse student perspectives
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Maintain transparency in all activities
                                </li>
                                <li class="list-group-item bg-transparent">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Evaluate effectiveness and adjust as needed
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div> <!-- End container-fluid -->
</div> <!-- End modern-container -->

<?php
// Include footer
require_once 'includes/footer.php';
?>

<style>
/* Card styling */
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 0;
    background: linear-gradient(to bottom, rgba(255,255,255,0.1), transparent);
    transition: height 0.3s ease;
    z-index: 1;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.card:hover::before {
    height: 100%;
}

.card-header {
    font-weight: 600;
    padding: 1rem 1.25rem;
    position: relative;
    z-index: 2;
}

.card-body {
    padding: 1.25rem;
    position: relative;
    z-index: 2;
}

/* Enhanced badge styling */
.badge {
    font-weight: 500;
    letter-spacing: 0.5px;
    padding: 0.35em 0.65em;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.badge.bg-primary {
    background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
}

.badge.bg-success {
    background: linear-gradient(45deg, #198754, #157347) !important;
}

.badge.bg-info {
    background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
}

.badge.bg-warning {
    background: linear-gradient(45deg, #ffc107, #d39e00) !important;
}

.badge.bg-danger {
    background: linear-gradient(45deg, #dc3545, #b02a37) !important;
}

/* Enhanced button styling */
.btn {
    border-radius: 0.25rem;
    font-weight: 500;
    padding: 0.375rem 0.75rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background-color: rgba(255,255,255,0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.5s, height 0.5s;
}

.btn:active::after {
    width: 300px;
    height: 300px;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Enhanced list group styling */
.list-group-item {
    transition: all 0.2s ease;
    border-left: none;
    border-right: none;
    position: relative;
    overflow: hidden;
}

.list-group-item::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0;
    height: 2px;
    background-color: #0d6efd;
    transition: width 0.3s ease;
}

.list-group-item:hover::after {
    width: 100%;
}

/* Enhanced alert styling */
.alert {
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.alert::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background-color: rgba(0,0,0,0.1);
}

/* Committee cards enhanced styling */
.committee-card {
    overflow: hidden;
    position: relative;
}

.committee-card::after {
    content: '';
    position: absolute;
    top: -100%;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
    transform: rotate(45deg);
    transition: all 0.7s ease;
}

.committee-card:hover::after {
    top: 100%;
    left: 100%;
}

/* Enhanced animation for border refresh */
@keyframes border-pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
    50% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0.4); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}

.border-refresh {
    animation: border-pulse 2s ease-out infinite;
}
</style>

<?php if (shouldUseAdminInterface()): ?>
<!-- Add Committee Modal -->
<div class="modal fade" id="addCommitteeModal" tabindex="-1" aria-labelledby="addCommitteeModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addCommitteeModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Committee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="committees_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_committee">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="committee_name" class="form-label">Committee Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="committee_name" name="committee_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="committee_type" class="form-label">Committee Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="committee_type" name="committee_type" required>
                                <option value="">Select Type</option>
                                <option value="Standing">Standing Committee</option>
                                <option value="Ad Hoc">Ad Hoc Committee</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="committee_purpose" class="form-label">Purpose <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="committee_purpose" name="committee_purpose" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="committee_composition" class="form-label">Composition</label>
                        <textarea class="form-control" id="committee_composition" name="committee_composition" rows="4"></textarea>
                        <small class="form-text text-muted">Enter each member/position on a separate line. HTML formatting will be added automatically.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="committee_responsibilities" class="form-label">Key Responsibilities</label>
                        <textarea class="form-control" id="committee_responsibilities" name="committee_responsibilities" rows="4"></textarea>
                        <small class="form-text text-muted">Enter each responsibility on a separate line. HTML formatting will be added automatically.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="committee_description" class="form-label">Additional Information (Optional)</label>
                        <textarea class="form-control" id="committee_description" name="committee_description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle me-1"></i> Add Committee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Committee Member Modal -->
<div class="modal fade" id="addCommitteeMemberModal" tabindex="-1" aria-labelledby="addCommitteeMemberModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addCommitteeMemberModalLabel"><i class="fas fa-user-plus me-2"></i>Add Committee Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="committees_actions.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_member">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="committee_id" class="form-label">Committee <span class="text-danger">*</span></label>
                            <select class="form-select" id="committee_id" name="committee_id" required>
                                <option value="">Select Committee</option>
                                <?php
                                try {
                                    $committeeSql = "SELECT committee_id, name FROM committees ORDER BY name";
                                    $committees = fetchAll($committeeSql);

                                    if (!empty($committees)) {
                                        foreach ($committees as $committee) {
                                            echo '<option value="' . $committee['committee_id'] . '">' . htmlspecialchars($committee['name']) . '</option>';
                                        }
                                    }
                                } catch (Exception $e) {
                                    // Silent error handling
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="member_position" class="form-label">Position <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="member_position" name="member_position" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="member_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="member_name" name="member_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="member_department" class="form-label">Department/Faculty</label>
                            <input type="text" class="form-control" id="member_department" name="member_department">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="member_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="member_email" name="member_email">
                        </div>
                        <div class="col-md-6">
                            <label for="member_phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="member_phone" name="member_phone">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="member_photo" class="form-label">Profile Photo</label>
                        <input type="file" class="form-control" id="member_photo" name="member_photo" accept="image/*">
                        <small class="form-text text-muted">Upload a professional photo (Max size: 2MB, Formats: JPG, PNG)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i> Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Committees Modal -->
<div class="modal fade" id="manageCommitteesModal" tabindex="-1" aria-labelledby="manageCommitteesModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="manageCommitteesModalLabel"><i class="fas fa-list me-2"></i>Manage Committees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Members</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch committees from database (fallback to static list if table doesn't exist)
                            try {
                                // Check if committees table exists
                                $checkTableSql = "SHOW TABLES LIKE 'committees'";
                                $tableExists = count(fetchAll($checkTableSql)) > 0;

                                if ($tableExists) {
                                    $committeesSql = "SELECT c.*, COUNT(cm.id) as member_count
                                                    FROM committees c
                                                    LEFT JOIN committee_members cm ON c.committee_id = cm.committee_id
                                                    GROUP BY c.committee_id
                                                    ORDER BY c.type, c.name";
                                    $committees = fetchAll($committeesSql);
                                } else {
                                    // Use static committee list from minutes table
                                    $committees = [];
                                }
                                
                                if (!empty($committees)) {
                                    foreach ($committees as $committee) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($committee['name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($committee['type']) . '</td>';
                                        echo '<td>' . $committee['member_count'] . '</td>';
                                        echo '<td>';
                                        echo '<button type="button" class="btn btn-sm btn-primary edit-committee-btn me-1" data-id="' . $committee['committee_id'] . '"><i class="fas fa-edit"></i></button>';
                                        echo '<button type="button" class="btn btn-sm btn-info view-members-btn me-1" data-id="' . $committee['committee_id'] . '" data-name="' . htmlspecialchars($committee['name']) . '"><i class="fas fa-users"></i></button>';
                                        echo '<button type="button" class="btn btn-sm btn-danger delete-committee-btn" data-id="' . $committee['committee_id'] . '" data-name="' . htmlspecialchars($committee['name']) . '"><i class="fas fa-trash"></i></button>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">No committees found</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="4" class="text-center">Error loading committees</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCommitteeModal">
                    <i class="fas fa-plus me-1"></i> Add New Committee
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Committee Confirmation Modal -->
<div class="modal fade" id="deleteCommitteeModal" tabindex="-1" aria-labelledby="deleteCommitteeModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCommitteeModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="committeeNameToDelete">this committee</span>?</p>
                <p class="text-danger"><strong>This action cannot be undone and will delete all associated members and data.</strong></p>
            </div>
            <div class="modal-footer">
                <form action="committees_actions.php" method="post">
                    <input type="hidden" name="action" value="delete_committee">
                    <input type="hidden" name="committee_id" id="committeeIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Committee Content Modal -->
<div class="modal fade" id="deleteCommitteeContentModal" tabindex="-1" aria-labelledby="deleteCommitteeContentModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCommitteeContentModalLabel"><i class="fas fa-trash me-2"></i>Delete Committee Content</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Please select the content you wish to delete. This action cannot be undone.
                </div>
                
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteCommitteeModal">
                        <div>
                            <i class="fas fa-users me-2"></i> Delete Committee
                        </div>
                        <span class="badge bg-danger rounded-pill"><i class="fas fa-arrow-right"></i></span>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteCommitteeMemberModal">
                        <div>
                            <i class="fas fa-user me-2"></i> Delete Committee Member
                        </div>
                        <span class="badge bg-danger rounded-pill"><i class="fas fa-arrow-right"></i></span>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteCommitteeMeetingModal">
                        <div>
                            <i class="fas fa-calendar-alt me-2"></i> Delete Committee Meeting
                        </div>
                        <span class="badge bg-danger rounded-pill"><i class="fas fa-arrow-right"></i></span>
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Committee Meetings Modal -->
<div class="modal fade" id="manageCommitteeMeetingsModal" tabindex="-1" aria-labelledby="manageCommitteeMeetingsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="manageCommitteeMeetingsModalLabel"><i class="fas fa-calendar-alt me-2"></i>Manage Committee Meetings</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Meeting Form -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Meeting</h6>
                    </div>
                    <div class="card-body">
                        <form action="committees_actions.php" method="post">
                            <input type="hidden" name="action" value="add_meeting">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="meeting_committee_id" class="form-label">Committee <span class="text-danger">*</span></label>
                                    <select class="form-select" id="meeting_committee_id" name="committee_id" required>
                                        <option value="">Select Committee</option>
                                        <?php
                                        try {
                                            $committeesQuery = "SELECT committee_id, name FROM committees ORDER BY name";
                                            $committeesResult = fetchAll($committeesQuery);
                                            foreach ($committeesResult as $committee) {
                                                echo "<option value='" . $committee['committee_id'] . "'>" . htmlspecialchars($committee['name']) . "</option>";
                                            }
                                        } catch (Exception $e) {
                                            echo "<option value=''>No committees available</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="meeting_date" class="form-label">Meeting Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="meeting_date" name="meeting_date" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="meeting_venue" class="form-label">Venue</label>
                                    <input type="text" class="form-control" id="meeting_venue" name="meeting_venue" placeholder="Meeting venue">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="meeting_status" class="form-label">Status</label>
                                    <select class="form-select" id="meeting_status" name="meeting_status">
                                        <option value="Scheduled">Scheduled</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="meeting_agenda" class="form-label">Agenda <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="meeting_agenda" name="meeting_agenda" rows="4" required placeholder="Meeting agenda..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Meeting</button>
                        </form>
                    </div>
                </div>

                <!-- Existing Meetings List -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Existing Meetings</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Committee</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Status</th>
                                        <th>Agenda</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // Check if committee_meetings table exists
                                        $checkTableSql = "SHOW TABLES LIKE 'committee_meetings'";
                                        $tableExists = count(fetchAll($checkTableSql)) > 0;

                                        if ($tableExists) {
                                            $meetingsQuery = "SELECT cm.*, c.name as committee_name
                                                            FROM committee_meetings cm
                                                            LEFT JOIN committees c ON cm.committee_id = c.committee_id
                                                            ORDER BY cm.meeting_date DESC";
                                            $meetings = fetchAll($meetingsQuery);
                                        } else {
                                            $meetings = [];
                                        }

                                        if (empty($meetings)) {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No meetings found</td></tr>";
                                        } else {
                                            foreach ($meetings as $meeting) {
                                                $statusClass = '';
                                                switch ($meeting['status']) {
                                                    case 'Scheduled': $statusClass = 'bg-info'; break;
                                                    case 'In Progress': $statusClass = 'bg-warning'; break;
                                                    case 'Completed': $statusClass = 'bg-success'; break;
                                                    case 'Cancelled': $statusClass = 'bg-danger'; break;
                                                }

                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($meeting['committee_name'] ?? 'Unknown') . "</td>";
                                                echo "<td>" . date('M d, Y', strtotime($meeting['meeting_date'])) . "</td>";
                                                echo "<td>" . htmlspecialchars($meeting['venue'] ?? 'TBD') . "</td>";
                                                echo "<td><span class='badge $statusClass'>" . htmlspecialchars($meeting['status']) . "</span></td>";
                                                echo "<td>" . htmlspecialchars(substr($meeting['agenda'], 0, 50)) . "...</td>";
                                                echo "<td>";
                                                echo "<button class='btn btn-sm btn-outline-primary me-1' title='Edit'><i class='fas fa-edit'></i></button>";
                                                echo "<button class='btn btn-sm btn-outline-danger' title='Delete'><i class='fas fa-trash'></i></button>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='6' class='text-center text-danger'>Error loading meetings</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Committee Reports Modal -->
<div class="modal fade" id="manageCommitteeReportsModal" tabindex="-1" aria-labelledby="manageCommitteeReportsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="manageCommitteeReportsModalLabel"><i class="fas fa-file-alt me-2"></i>Manage Committee Reports</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Report Form -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Report</h6>
                    </div>
                    <div class="card-body">
                        <form action="committees_actions.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_report">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="report_committee_id" class="form-label">Committee <span class="text-danger">*</span></label>
                                    <select class="form-select" id="report_committee_id" name="committee_id" required>
                                        <option value="">Select Committee</option>
                                        <?php
                                        try {
                                            $committeesQuery = "SELECT committee_id, name FROM committees ORDER BY name";
                                            $committeesResult = fetchAll($committeesQuery);
                                            foreach ($committeesResult as $committee) {
                                                echo "<option value='" . $committee['committee_id'] . "'>" . htmlspecialchars($committee['name']) . "</option>";
                                            }
                                        } catch (Exception $e) {
                                            echo "<option value=''>No committees available</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="report_date" class="form-label">Report Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="report_date" name="report_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="report_title" class="form-label">Report Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="report_title" name="report_title" required placeholder="Report title">
                            </div>
                            <div class="mb-3">
                                <label for="report_content" class="form-label">Report Content</label>
                                <textarea class="form-control" id="report_content" name="report_content" rows="6" placeholder="Report content..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="report_file" class="form-label">Upload Report File (Optional)</label>
                                <input type="file" class="form-control" id="report_file" name="report_file" accept=".pdf,.doc,.docx">
                                <small class="form-text text-muted">Accepted formats: PDF, DOC, DOCX. Max size: 10MB</small>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus me-1"></i> Add Report</button>
                        </form>
                    </div>
                </div>

                <!-- Existing Reports List -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Existing Reports</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Committee</th>
                                        <th>Title</th>
                                        <th>Date</th>
                                        <th>File</th>
                                        <th>Content Preview</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // Check if committee_reports table exists
                                        $checkTableSql = "SHOW TABLES LIKE 'committee_reports'";
                                        $tableExists = count(fetchAll($checkTableSql)) > 0;

                                        if ($tableExists) {
                                            $reportsQuery = "SELECT cr.*, c.name as committee_name
                                                           FROM committee_reports cr
                                                           LEFT JOIN committees c ON cr.committee_id = c.committee_id
                                                           ORDER BY cr.report_date DESC";
                                            $reports = fetchAll($reportsQuery);
                                        } else {
                                            $reports = [];
                                        }

                                        if (empty($reports)) {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No reports found</td></tr>";
                                        } else {
                                            foreach ($reports as $report) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($report['committee_name'] ?? 'Unknown') . "</td>";
                                                echo "<td>" . htmlspecialchars($report['title']) . "</td>";
                                                echo "<td>" . date('M d, Y', strtotime($report['report_date'])) . "</td>";
                                                echo "<td>";
                                                if (!empty($report['file_name'])) {
                                                    echo "<a href='../uploads/reports/" . htmlspecialchars($report['file_name']) . "' target='_blank' class='btn btn-sm btn-outline-primary'>";
                                                    echo "<i class='fas fa-download me-1'></i>Download";
                                                    echo "</a>";
                                                } else {
                                                    echo "<span class='text-muted'>No file</span>";
                                                }
                                                echo "</td>";
                                                echo "<td>" . htmlspecialchars(substr($report['content'] ?? '', 0, 50)) . "...</td>";
                                                echo "<td>";
                                                echo "<button class='btn btn-sm btn-outline-primary me-1' title='Edit'><i class='fas fa-edit'></i></button>";
                                                echo "<button class='btn btn-sm btn-outline-danger' title='Delete'><i class='fas fa-trash'></i></button>";
                                                echo "</td>";
                                                echo "</tr>";
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo "<tr><td colspan='6' class='text-center text-danger'>Error loading reports</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript for Committee page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Debug logging to check if buttons are found
    console.log('Total view-committee-details buttons:', document.querySelectorAll('.view-committee-details').length);
    console.log('Total view-dynamic-committee-details buttons:', document.querySelectorAll('.view-dynamic-committee-details').length);
    
    // Function to decode HTML entities and extract meaningful content from HTML tags
    function extractContentFromHtml(html) {
        if (!html) return '';
        
        // Create a temporary element
        const element = document.createElement('div');
        
        // Set the HTML content
        element.innerHTML = html;
        
        // Process list items specially
        const listItems = element.querySelectorAll('li');
        if (listItems.length > 0) {
            // Extract content from list items, each on its own line
            return Array.from(listItems)
                .map(li => li.textContent.trim())
                .join('\n');
        }
        
        // If no list items, return all text content
        return element.textContent || element.innerText || '';
    }
    
    // Delete committee confirmation
    const deleteCommitteeBtns = document.querySelectorAll('.delete-committee-btn');
    if (deleteCommitteeBtns.length > 0) {
        deleteCommitteeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeId = this.getAttribute('data-id');
                const committeeName = this.getAttribute('data-name');
                
                document.getElementById('committeeIdToDelete').value = committeeId;
                document.getElementById('committeeNameToDelete').textContent = committeeName;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteCommitteeModal'));
                deleteModal.show();
            });
        });
    }
    
    // Edit committee functionality
    const editCommitteeBtns = document.querySelectorAll('.edit-committee-btn');
    if (editCommitteeBtns.length > 0) {
        editCommitteeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeId = this.getAttribute('data-id');
                // Redirect to the edit page
                window.location.href = 'committees_edit.php?id=' + committeeId;
            });
        });
    }

    // View committee members functionality
    const viewMembersBtns = document.querySelectorAll('.view-members-btn');
    if (viewMembersBtns.length > 0) {
        viewMembersBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeId = this.getAttribute('data-id');
                const committeeName = this.getAttribute('data-name');

                // Fetch committee members via AJAX
                const url = 'committees_actions.php?action=get_committee_members&id=' + committeeId;

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(members => {
                    // Create a modal to display committee members
                    const modalId = 'viewCommitteeMembersModal';
                    let modal = document.getElementById(modalId);

                    if (!modal) {
                        // Create modal if it doesn't exist
                        const modalHTML = `
                            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true" data-bs-backdrop="false">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title" id="${modalId}Label"><i class="fas fa-users me-2"></i>Committee Members</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body" id="${modalId}Body">
                                            <!-- Content will be inserted here -->
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        document.body.insertAdjacentHTML('beforeend', modalHTML);
                        modal = document.getElementById(modalId);
                    }

                    const modalBody = document.getElementById(`${modalId}Body`);

                    // Create content for the modal
                    let modalContent = `<h4>${committeeName} Members</h4>`;

                    if (members && members.length > 0) {
                        modalContent += `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Contact</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        members.forEach(member => {
                            modalContent += `
                                <tr>
                                    <td><strong>${member.position}</strong></td>
                                    <td>${member.name}</td>
                                    <td>${member.department || 'N/A'}</td>
                                    <td>
                                        ${member.email ? `<a href="mailto:${member.email}"><i class="fas fa-envelope me-1"></i> Email</a>` : ''}
                                        ${member.email && member.phone ? ' | ' : ''}
                                        ${member.phone ? `<a href="tel:${member.phone}"><i class="fas fa-phone me-1"></i> Call</a>` : ''}
                                        ${!member.email && !member.phone ? 'N/A' : ''}
                                    </td>
                                </tr>
                            `;
                        });

                        modalContent += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        modalContent += `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No members have been added to this committee yet.
                            </div>
                        `;
                    }

                    modalBody.innerHTML = modalContent;

                    // Show the modal
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                })
                .catch(error => {
                    console.error('Error fetching committee members:', error);
                    alert('Error loading committee members. Please try again.');
                });
            });
        });
    }
    
    // Dynamic committees edit functionality
    const editDynamicCommitteeBtns = document.querySelectorAll('.edit-dynamic-committee');
    if (editDynamicCommitteeBtns.length > 0) {
        editDynamicCommitteeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeId = this.getAttribute('data-id');
                window.location.href = 'committees_edit.php?id=' + committeeId;
            });
        });
    }
    
    // Dynamic committees delete functionality
    const deleteDynamicCommitteeBtns = document.querySelectorAll('.delete-dynamic-committee');
    if (deleteDynamicCommitteeBtns.length > 0) {
        deleteDynamicCommitteeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeId = this.getAttribute('data-id');
                const committeeName = this.getAttribute('data-name');
                
                // Use the existing delete modal
                document.getElementById('committeeIdToDelete').value = committeeId;
                document.getElementById('committeeNameToDelete').textContent = committeeName;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteCommitteeModal'));
                deleteModal.show();
            });
        });
    }
    
    // Static committees edit functionality
    const editStaticCommitteeBtns = document.querySelectorAll('.edit-static-committee');
    if (editStaticCommitteeBtns.length > 0) {
        editStaticCommitteeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeName = this.getAttribute('data-committee');
                const committeeType = this.getAttribute('data-type');
                
                // Find the committee card to extract detailed purpose, composition and responsibilities
                const committeeCard = this.closest('.committee-card');
                let purpose = '';
                let composition = '';
                let responsibilities = '';
                
                if (committeeCard) {
                    // Extract purpose from the card - it's in the first paragraph with class mb-3
                    const purposeText = committeeCard.querySelector('p.mb-3');
                    if (purposeText) {
                        purpose = purposeText.textContent.replace('Purpose:', '').trim();
                    }
                    
                    // Find composition section
                    const h6Elements = committeeCard.querySelectorAll('h6');
                    h6Elements.forEach(h6 => {
                        if (h6.textContent.includes('Composition')) {
                            // Get the ul element after the h6
                            const compositionList = h6.nextElementSibling;
                            if (compositionList) {
                                composition = compositionList.outerHTML;
                            }
                        } else if (h6.textContent.includes('Responsibilities') || h6.textContent.includes('Key Responsibilities')) {
                            // Get the ul element after the h6
                            const respList = h6.nextElementSibling;
                            if (respList) {
                                responsibilities = respList.outerHTML;
                            }
                        }
                    });
                }
                
                // Show the add committee modal but repurpose it for editing
                const modal = new bootstrap.Modal(document.getElementById('addCommitteeModal'));
                
                // Update modal title and button text
                document.getElementById('addCommitteeModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Committee';
                document.querySelector('#addCommitteeModal .modal-footer button[type="submit"]').innerHTML = '<i class="fas fa-save me-1"></i> Save Changes';
                
                // Update form action
                const form = document.querySelector('#addCommitteeModal form');
                form.querySelector('input[name="action"]').value = 'edit_static_committee';
                
                // Add committee name field
                if (!form.querySelector('input[name="original_committee_name"]')) {
                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'original_committee_name';
                    nameInput.value = committeeName;
                    form.appendChild(nameInput);
                } else {
                    form.querySelector('input[name="original_committee_name"]').value = committeeName;
                }
                
                // Add hidden fields for composition and responsibilities
                if (!form.querySelector('input[name="committee_composition_html"]')) {
                    const compInput = document.createElement('input');
                    compInput.type = 'hidden';
                    compInput.name = 'committee_composition_html';
                    compInput.value = composition;
                    form.appendChild(compInput);
                } else {
                    form.querySelector('input[name="committee_composition_html"]').value = composition;
                }
                
                if (!form.querySelector('input[name="committee_responsibilities_html"]')) {
                    const respInput = document.createElement('input');
                    respInput.type = 'hidden';
                    respInput.name = 'committee_responsibilities_html';
                    respInput.value = responsibilities;
                    form.appendChild(respInput);
                } else {
                    form.querySelector('input[name="committee_responsibilities_html"]').value = responsibilities;
                }
                
                // Populate form fields
                document.getElementById('committee_name').value = committeeName;
                document.getElementById('committee_type').value = committeeType;
                document.getElementById('committee_purpose').value = purpose;
                document.getElementById('committee_composition').value = extractContentFromHtml(composition);
                document.getElementById('committee_responsibilities').value = extractContentFromHtml(responsibilities);
                
                // Change the description label to "Additional Information (Optional)" - this happens by default now
                document.getElementById('committee_description').value = '';
                
                modal.show();
            });
        });
    }
    
    // Static committees delete functionality
    const deleteStaticCommitteeBtns = document.querySelectorAll('.delete-static-committee');
    if (deleteStaticCommitteeBtns.length > 0) {
        deleteStaticCommitteeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const committeeName = this.getAttribute('data-committee');
                
                // Use the existing delete modal
                document.getElementById('committeeIdToDelete').value = '';
                document.getElementById('committeeNameToDelete').textContent = committeeName;
                
                // Add a hidden field for the committee name
                const form = document.querySelector('#deleteCommitteeModal form');
                if (!form.querySelector('input[name="committee_name"]')) {
                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'committee_name';
                    nameInput.value = committeeName;
                    form.appendChild(nameInput);
                } else {
                    form.querySelector('input[name="committee_name"]').value = committeeName;
                }
                
                // Update form action
                form.querySelector('input[name="action"]').value = 'delete_static_committee';
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteCommitteeModal'));
                deleteModal.show();
            });
        });
    }
    
    // NOTE: View committee details functionality moved outside admin section to be available for all users
    
    // Fix for jQuery-like :contains selector - replace with standard DOM API
    const findElementsWithText = function(selector, text) {
        const elements = document.querySelectorAll(selector);
        return Array.from(elements).filter(element => element.textContent.includes(text));
    };
    
    // Add event listener for save changes in modal
    document.querySelectorAll('.modal .modal-footer button[type="submit"]').forEach(button => {
        button.addEventListener('click', function(e) {
            // For committee forms, we want to just submit the form normally to avoid AJAX issues
            const form = this.closest('form');
            if (!form) return;
            
            // Check if this is a committee edit or add form
            const action = form.querySelector('input[name="action"]').value || '';
            
            if (action.includes('committee')) {
                // Let the form submit normally - it will refresh the page
                return true;
            }
            
            // For other forms, prevent default and handle via AJAX
            e.preventDefault();
            
            // Use FormData to get all form fields including files
            const formData = new FormData(form);
            
            // Submit via AJAX
            fetch('committees_actions.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(result => {
                // Debug log
                console.log('Server response:', result);
                // Check if result is JSON
                try {
                    const jsonResult = JSON.parse(result);
                    if (jsonResult.success) {
                        // Show success message
                        const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> ${jsonResult.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                        document.querySelector('.container-fluid').insertAdjacentHTML('afterbegin', alertHtml);
                        
                        // Hide the modal
                        const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                        modal.hide();
                        
                        // Force reload the page to reflect changes
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        // Show error message
                        alert(jsonResult.message || 'An error occurred while processing your request.');
                    }
                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                    console.log('Raw response:', result);
                    // If result is not JSON, simply reload the page
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
    
    // Add event listener for cancel button to reset forms
    document.querySelectorAll('.modal .modal-footer button[data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.modal').querySelector('form');
            if (form) {
                setTimeout(() => {
                    form.reset();
                }, 500); // Small delay to ensure modal is fully hidden
            }
        });
    });
});
</script>
<?php endif; ?>

<!-- Script to handle committee card refresh -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug logging to check if buttons are found
    console.log('Total view-committee-details buttons:', document.querySelectorAll('.view-committee-details').length);
    console.log('Total view-dynamic-committee-details buttons:', document.querySelectorAll('.view-dynamic-committee-details').length);
    
    // View committee details functionality for static committees
    const viewStaticCommitteeDetailsBtns = document.querySelectorAll('.view-committee-details');
    if (viewStaticCommitteeDetailsBtns.length > 0) {
        viewStaticCommitteeDetailsBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const committeeName = this.getAttribute('data-committee');
                const committeeType = this.getAttribute('data-type');
                
                // Create a modal to display committee details
                const modalId = 'viewCommitteeDetailsModal';
                let modal = document.getElementById(modalId);
                
                if (!modal) {
                    // Create modal if it doesn't exist
                    const modalHTML = `
                        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true" data-bs-backdrop="false">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title" id="${modalId}Label"><i class="fas fa-info-circle me-2"></i>Committee Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="${modalId}Body">
                                        <!-- Content will be inserted here -->
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    modal = document.getElementById(modalId);
                }
                
                // Find the committee card to extract details
                const committeeCard = this.closest('.committee-card');
                const modalBody = document.getElementById(`${modalId}Body`);
                
                if (committeeCard) {
                    // Extract details from the card
                    const purpose = committeeCard.querySelector('p.mb-3')?.textContent || '';
                    
                    // Find composition and responsibilities using standard selectors
                    let composition = '';
                    let responsibilities = '';
                    
                    // Find all h6 elements and check their text content
                    const h6Elements = committeeCard.querySelectorAll('h6');
                    h6Elements.forEach(h6 => {
                        if (h6.textContent.includes('Composition')) {
                            composition = h6.nextElementSibling?.outerHTML || '';
                        } else if (h6.textContent.includes('Responsibilities')) {
                            responsibilities = h6.nextElementSibling?.outerHTML || '';
                        }
                    });
                    
                    // Create content for the modal
                    modalBody.innerHTML = `
                        <h4>${committeeName}</h4>
                        <div class="badge bg-${committeeType === 'Standing' ? 'primary' : 'success'} mb-3">${committeeType} Committee</div>
                        <h5>Purpose</h5>
                        <p>${purpose}</p>
                        ${composition ? `<h5>Composition</h5>${composition}` : ''}
                        ${responsibilities ? `<h5>Key Responsibilities</h5>${responsibilities}` : ''}
                    `;
                } else {
                    modalBody.innerHTML = `<div class="alert alert-warning">Details for ${committeeName} could not be found.</div>`;
                }
                
                // Show the modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            });
        });
    }
    
    // View dynamic committee details functionality
    const viewDynamicCommitteeDetailsBtns = document.querySelectorAll('.view-dynamic-committee-details');
    if (viewDynamicCommitteeDetailsBtns.length > 0) {
        viewDynamicCommitteeDetailsBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const committeeId = this.getAttribute('data-id');
                
                // Add debug logging
                console.log('View committee details clicked. Committee ID:', committeeId);
                
                // Fetch committee data via AJAX
                const url = 'committees_actions.php?action=get_committee&id=' + committeeId;
                console.log('Fetching committee data from:', url);
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Committee data received:', data);
                        // Create a modal to display committee details
                        const modalId = 'viewCommitteeDetailsModal';
                        let modal = document.getElementById(modalId);
                        
                        if (!modal) {
                            // Create modal if it doesn't exist
                            const modalHTML = `
                                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true" data-bs-backdrop="false">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title" id="${modalId}Label"><i class="fas fa-info-circle me-2"></i>Committee Details</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body" id="${modalId}Body">
                                                <!-- Content will be inserted here -->
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            document.body.insertAdjacentHTML('beforeend', modalHTML);
                            modal = document.getElementById(modalId);
                        }
                        
                        const modalBody = document.getElementById(`${modalId}Body`);
                        
                        // Create content for the modal
                        let modalContent = `
                            <h4>${data.name}</h4>
                            <div class="badge bg-${data.type === 'Standing' ? 'primary' : 'success'} mb-3">${data.type} Committee</div>
                            <h5>Purpose</h5>
                            <p>${data.purpose || 'No purpose specified'}</p>
                        `;
                        
                        // Add composition if available
                        if (data.composition) {
                            modalContent += `<h5>Composition</h5>${data.composition}`;
                        }
                        
                        // Add responsibilities if available
                        if (data.responsibilities) {
                            modalContent += `<h5>Key Responsibilities</h5>${data.responsibilities}`;
                        } else if (data.description) {
                            modalContent += `<h5>Description</h5><p>${data.description}</p>`;
                        }
                        
                        modalBody.innerHTML = modalContent;
                        
                        // Fetch committee members
                        const membersUrl = 'committees_actions.php?action=get_committee_members&id=' + committeeId;
                        console.log('Fetching committee members from:', membersUrl);
                        
                        fetch(membersUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        })
                            .then(response => {
                                console.log('Members response status:', response.status);
                                if (!response.ok) {
                                    throw new Error('Network response was not ok: ' + response.status);
                                }
                                return response.json();
                            })
                            .then(members => {
                                console.log('Committee members received:', members);
                                if (members && members.length > 0) {
                                    let membersHTML = '<h5>Members</h5><ul class="list-group mb-3">';
                                    members.forEach(member => {
                                        membersHTML += `
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold">${member.position}</div>
                                                    ${member.name}
                                                </div>
                                            </li>
                                        `;
                                    });
                                    membersHTML += '</ul>';
                                    modalBody.innerHTML += membersHTML;
                                }
                                
                                // Show the modal
                                const bsModal = new bootstrap.Modal(modal);
                                bsModal.show();
                            })
                            .catch(error => {
                                console.error('Error fetching committee members:', error);
                                
                                // Show the modal even if members couldn't be fetched
                                const bsModal = new bootstrap.Modal(modal);
                                bsModal.show();
                            });
                    })
                    .catch(error => {
                        console.error('Error fetching committee data:', error);
                        alert('Error loading committee details. Please try again.');
                    });
            });
        });
    }

    // Check if we're loading the page after an update
    const urlParams = new URLSearchParams(window.location.search);
    const updated = urlParams.get('updated');
    
    if (updated) {
        console.log('Page loaded after committee update. Timestamp:', updated);
        
        // Force browser to not use cached content for committee cards
        document.querySelectorAll('.committee-card').forEach(card => {
            // Add a small visual indication that the card has been updated
            card.classList.add('border-refresh');
            setTimeout(() => {
                card.classList.remove('border-refresh');
            }, 2000);
        });
        
        // Clear the updated parameter from URL without reloading
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
});
</script>

<style>
/* Add a subtle highlight effect for refreshed cards */
@keyframes border-pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}

.border-refresh {
    animation: border-pulse 2s ease-out;
}
</style>

<style>
/* Texture pattern for card headers */
.card-header.bg-gradient {
    position: relative;
    background-size: cover !important;
    background-position: center !important;
    background-blend-mode: overlay;
}

.card-header.bg-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Add styling for h5 headers within content cards */
.content-card-body h5 {
    font-size: 1.4rem; /* Larger font size for sub-headers */
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    color: #343a40;
}

/* Enhanced styling for section subheaders */
.section-subheader {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #2c3e50 !important;
    margin-bottom: 1.25rem !important;
    padding-bottom: 0.5rem !important;
    border-bottom: 2px solid #e9ecef !important;
    position: relative;
}

.section-subheader::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 60px;
    height: 2px;
    background-color: #4b6cb7;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
