<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';
// Require login for this page
requireLogin();
// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if senate feature is enabled
if (!hasFeaturePermission('enable_senate')) {
    $_SESSION['error'] = "Senate feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Check for admin status
$isAdmin = shouldUseAdminInterface();
$isMember = isMember();
$shouldUseAdminInterface = shouldUseAdminInterface();

// Get Senate President info from portfolios
try {
    $senatePresident = null;
    // First check if the portfolios table exists
    $checkTableSql = "SHOW TABLES LIKE 'portfolios'";
    $tableExists = fetchOne($checkTableSql);
    
    if ($tableExists) {
    $senateSql = "SELECT * FROM portfolios WHERE title = 'Senate President' LIMIT 1";
    $senateResult = fetchOne($senateSql);
    if ($senateResult) {
        $senatePresident = $senateResult;
        // Parse JSON data
        $senatePresident['responsibilities'] = json_decode($senatePresident['responsibilities'] ?? '[]', true);
        $senatePresident['qualifications'] = json_decode($senatePresident['qualifications'] ?? '[]', true);
        // Map portfolio_id to id for consistency
        $senatePresident['id'] = $senatePresident['portfolio_id'];
        }
    }
} catch (Exception $e) {
    // Silent error handling
    error_log("Error loading Senate President: " . $e->getMessage());
}

// Page title
$pageTitle = "Senate - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<!-- Custom Senate Header -->
<div class="senate-header animate__animated animate__fadeInDown">
    <div class="senate-header-content">
        <div class="senate-header-main">
            <h1 class="senate-title">
                <i class="fas fa-university me-3"></i>
                Senate
            </h1>
            <p class="senate-description">Student Senate - The legislative authority of the VVUSRC</p>
        </div>
        <?php if ($shouldUseAdminInterface): ?>
        <div class="senate-header-actions">
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#manageSenateSessionsModal">
                <i class="fas fa-calendar-alt me-2"></i>Manage Sessions
            </button>
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#manageSenateDocumentsModal">
                <i class="fas fa-file-alt me-2"></i>Manage Documents
            </button>
            <button type="button" class="btn btn-header-action" data-bs-toggle="modal" data-bs-target="#manageSenateMembersModal">
                <i class="fas fa-user-edit me-2"></i>Manage Members
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.senate-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 12px;
    margin-top: 60px;
    margin-bottom: 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.senate-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.senate-header-main {
    flex: 1;
    text-align: center;
}

.senate-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
}

.senate-title i {
    font-size: 2.2rem;
    opacity: 0.9;
}

.senate-description {
    margin: 0;
    opacity: 0.95;
    font-size: 1.2rem;
    font-weight: 400;
    line-height: 1.4;
}

.senate-header-actions {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    flex-wrap: wrap;
    justify-content: center;
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
}

.btn-header-action:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .senate-header {
        padding: 2rem 1.5rem;
    }

    .senate-header-content {
        flex-direction: column;
        align-items: center;
    }

    .senate-title {
        font-size: 2rem;
        gap: 0.6rem;
    }

    .senate-title i {
        font-size: 1.8rem;
    }

    .senate-description {
        font-size: 1.1rem;
    }

    .senate-header-actions {
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
<?php if (isset($_SESSION['notification'])): ?>
    <div class="alert alert-<?= $_SESSION['notification_type'] ?> alert-dismissible fade show" role="alert" style="margin-top: 1rem;">
        <?= $_SESSION['notification'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
    // Clear the notification after displaying
    unset($_SESSION['notification']);
    unset($_SESSION['notification_type']);
    ?>
<?php endif; ?>

<!-- Main Content -->
<div class="row" style="margin-top: 1.5rem;">
        <!-- Senate Overview -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-university me-2"></i>Senate Overview</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="mb-3">About the Senate</h4>
                            <p class="lead">The Student Senate is the chief legislative authority of the VVUSRC, empowered to enact laws within Valley View University regulations that serve the best interest of the Council and the Institution.</p>
                            
                            <div class="mt-4">
                                <h5><i class="fas fa-gavel me-2"></i>Authority and Powers</h5>
                                <ul class="list-group list-group-flush mb-4">
                                    <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>Serves as the representative body of the Students' Representative Council</li>
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Enacts laws within the framework of Valley View University regulations</li>
                                    <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>Power to subpoena Executive members to answer questions or present reports</li>
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>Holds oversight responsibility for the Executive Committee</li>
                                    <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>Reviews and approves SRC budget and financial operations</li>
                </ul>
            </div>
        </div>
                        <div class="col-md-4">
                            <div class="card bg-light mb-4">
                                <div class="card-body text-center">
                                    <h5 class="card-title mb-3"><i class="fas fa-calendar-alt me-2"></i>Senate Meetings</h5>
                                    <p class="card-text">Regular Senate meetings are held on the third week of every month as stipulated in the VVUSRC Constitution.</p>
                                    <div class="d-grid gap-2">
                                        <a href="#upcoming-sessions" class="btn btn-outline-primary"><i class="fas fa-arrow-down me-1"></i> View Schedule</a>
    </div>
    </div>
                            </div>
                            
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5 class="card-title mb-3"><i class="fas fa-file-alt me-2"></i>Senate Documents</h5>
                                    <p class="card-text">Access important Senate documents including Constitution, minutes, and legislation.</p>
                                    <div class="d-grid gap-2">
                                        <a href="#senate-resources" class="btn btn-outline-primary"><i class="fas fa-arrow-down me-1"></i> View Documents</a>
    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Senate Structure -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-sitemap me-2"></i>Senate Structure</h3>
            </div>
                <div class="card-body p-4">
                <div class="row">
                        <div class="col-lg-6 mb-4">
                            <h4>Senate Leadership</h4>
                            <p>The Senate is led by the Senate President who is elected during the General Election. At the first sitting, the Senate elects the following officers:</p>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-user-tie text-primary me-2"></i>Senate President</h5>
                                            <p class="card-text">Presides over all Senate meetings and represents the Senate in official capacities.</p>
                            </div>
                    </div>
                            </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-user-tie text-primary me-2"></i>Vice President</h5>
                                            <p class="card-text">Assists the Senate President and presides over meetings in the President's absence.</p>
                    </div>
                            </div>
                    </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-user-edit text-primary me-2"></i>Clerk</h5>
                                            <p class="card-text">Records minutes and maintains official Senate documents.</p>
                            </div>
                    </div>
                </div>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><i class="fas fa-user-shield text-primary me-2"></i>Sergeant-at-Arms</h5>
                                            <p class="card-text">Maintains order during Senate proceedings.</p>
            </div>
        </div>
    </div>
                </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <h4>Senatorial Constituencies</h4>
                            <p>As per Article V, Section 2 of the VVUSRC Constitution, the Senate includes representatives from the following constituencies:</p>
                            
                            <div class="accordion" id="constituencyAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="classesHeading">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#classesCollapse" aria-expanded="true" aria-controls="classesCollapse">
                                            <i class="fas fa-users me-2"></i> Class Representatives
                                        </button>
                                    </h2>
                                    <div id="classesCollapse" class="accordion-collapse collapse show" aria-labelledby="classesHeading">
                                        <div class="accordion-body">
                                <ul class="list-group list-group-flush">
                                                <li class="list-group-item">Level 100 (Freshmen) - One Representative</li>
                                                <li class="list-group-item">Level 200 (Sophomore) - One Representative</li>
                                                <li class="list-group-item">Level 300 (Junior) - One Representative</li>
                                                <li class="list-group-item">Level 400 (Senior) - One Representative</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="residentialHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#residentialCollapse" aria-expanded="false" aria-controls="residentialCollapse">
                                            <i class="fas fa-home me-2"></i> Residential Representatives
                                        </button>
                                    </h2>
                                    <div id="residentialCollapse" class="accordion-collapse collapse" aria-labelledby="residentialHeading">
                                        <div class="accordion-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">Male Hall of Residence - One Representative for each hall</li>
                                                <li class="list-group-item">Female Hall of Residence - One Representative for each hall</li>
                                                <li class="list-group-item">Non-Residential Constituency - Five Representatives</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="otherHeading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#otherCollapse" aria-expanded="false" aria-controls="otherCollapse">
                                            <i class="fas fa-university me-2"></i> Other Constituencies
                                        </button>
                                    </h2>
                                    <div id="otherCollapse" class="accordion-collapse collapse" aria-labelledby="otherHeading">
                                        <div class="accordion-body">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item">Adult and Distance Education - Two Representatives</li>
                                                <li class="list-group-item">Clubs/Associations - One Representative from each recognized club</li>
                                                <li class="list-group-item">Sandwich - Two Representatives</li>
                                                <li class="list-group-item">Summer School - One Representative</li>
                                                <li class="list-group-item">Evening School - One Representative</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                            </div>
                        </div>
                    </div>

        <!-- Legislative Process -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-landmark me-2"></i>The Legislative Process</h3>
                </div>
                <div class="card-body p-4">
                    <div class="timeline-container">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="mb-2">1. Bill Introduction</h4>
                                    <p>Any Senator can introduce a bill or resolution for consideration. Bills must be submitted in writing to the Senate President at least 48 hours before a scheduled Senate meeting.</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="mb-2">2. Committee Review</h4>
                                    <p>Upon introduction, bills are assigned to the appropriate committee for review. Committees hold hearings, conduct research, and make recommendations to the full Senate.</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="mb-2">3. Senate Debate</h4>
                                    <p>The full Senate debates the bill, considers amendments, and discusses its merits. All Senators have the opportunity to voice their opinions and concerns.</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="mb-2">4. Voting</h4>
                                    <p>After debate, the Senate votes on the bill. A simple majority is required for passage, except for constitutional amendments which require a two-thirds majority.</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="mb-2">5. Executive Approval</h4>
                                    <p>Passed bills are sent to the SRC President for approval. The President may sign the bill into law, veto it, or allow it to become law without signature after 7 days.</p>
                                </div>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h4 class="mb-2">6. Implementation</h4>
                                    <p>Once a bill becomes law, it is implemented by the Executive Committee and relevant committees or departments. The Senate provides oversight to ensure proper implementation.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Senate President Profile -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-user-tie me-2"></i>Senate President</h3>
                </div>
                <div class="card-body p-0">
                    <div class="senate-president-profile text-center">
                        <?php if (!empty($senatePresident)): ?>
                            <!-- Use the same image handling approach as portfolio.php -->
                            <img src="../images/avatars/<?php echo htmlspecialchars($senatePresident['photo_url'] ?? $senatePresident['photo'] ?? ''); ?>" 
                                 alt="Senate President" 
                                 class="rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 5px solid #fff; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);"
                                 onerror="this.src='../images/avatars/default.jpg'">
                            <h4><?= htmlspecialchars($senatePresident['name']) ?></h4>
                            <?php if (!empty($senatePresident['email'])): ?>
                                <p class="text-muted mb-2"><?= htmlspecialchars($senatePresident['email']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($senatePresident['phone'])): ?>
                                <p class="text-muted mb-2"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($senatePresident['phone']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($senatePresident['description'])): ?>
                                <p class="text-muted mb-3 small"><?= htmlspecialchars($senatePresident['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <h5 class="text-center">Responsibilities</h5>
                                <ul class="list-unstyled text-start" style="padding-left: 30px;">
                                    <?php if (!empty($senatePresident['responsibilities']) && is_array($senatePresident['responsibilities'])): ?>
                                        <?php foreach ($senatePresident['responsibilities'] as $responsibility): ?>
                                            <li><i class="fas fa-check-circle text-success me-2"></i><?= htmlspecialchars($responsibility) ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Fallback to default responsibilities if none are set -->
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Presides over all Senate meetings</li>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Represents the Senate at official functions</li>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Appoints committee members</li>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Sets the agenda for Senate meetings</li>
                                        <li><i class="fas fa-check-circle text-success me-2"></i>Serves as a liaison between the Senate and Executive</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <?php if (!empty($senatePresident['qualifications']) && is_array($senatePresident['qualifications'])): ?>
                            <div class="mt-3">
                                <h5 class="text-center">Qualifications</h5>
                                <ul class="list-unstyled text-start" style="padding-left: 30px;">
                                    <?php foreach ($senatePresident['qualifications'] as $qualification): ?>
                                        <li><i class="fas fa-graduation-cap text-primary me-2"></i><?= htmlspecialchars($qualification) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                    <?php else: ?>
                            <div class="alert alert-info m-3">
                                <i class="fas fa-info-circle me-2"></i> Senate President information not available.
                                <?php if ($shouldUseAdminInterface): ?>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSenateMemberModal">
                                        <i class="fas fa-plus-circle me-1"></i> Add Senate President
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

        <!-- Upcoming Senate Sessions -->
        <div class="col-md-8 mb-4" id="upcoming-sessions">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-info text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Senate Sessions</h3>
                </div>
                <div class="card-body p-4">
                    <?php
                    // Fetch actual senate sessions from database instead of using sample data
                    try {
                        // Check if senate_sessions table exists
                        $checkTableSql = "SHOW TABLES LIKE 'senate_sessions'";
                        $tableExists = count(fetchAll($checkTableSql)) > 0;
                        
                        if ($tableExists) {
                            // Fetch upcoming sessions ordered by date
                            $sessionsSql = "SELECT * FROM senate_sessions ORDER BY session_date ASC";
                            $upcomingSessions = fetchAll($sessionsSql);
                        } else {
                            $upcomingSessions = [];
                        }
                        
                        if (!empty($upcomingSessions)) {
                            ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Agenda</th>
                                            <th>Status</th>
                                            <?php if ($shouldUseAdminInterface): ?>
                                            <th>Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingSessions as $session): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($session['session_date'])) ?></td>
                                                <td><?= htmlspecialchars($session['session_type']) ?></td>
                                                <td><?= htmlspecialchars($session['agenda']) ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch($session['status']) {
                                                        case 'Scheduled':
                                                            $statusClass = 'bg-secondary';
                                                            break;
                                                        case 'Pending':
                                                            $statusClass = 'bg-warning text-dark';
                                                            break;
                                                        case 'In Progress':
                                                            $statusClass = 'bg-info';
                                                            break;
                                                        case 'Completed':
                                                            $statusClass = 'bg-success';
                                                            break;
                                                        case 'Cancelled':
                                                            $statusClass = 'bg-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($session['status']) ?></span>
                                                </td>
                                                <?php if ($shouldUseAdminInterface): ?>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary edit-session-btn"
                                                            data-id="<?= $session['id'] ?>"
                                                            data-date="<?= $session['session_date'] ?>"
                                                            data-type="<?= htmlspecialchars($session['session_type']) ?>"
                                                            data-agenda="<?= htmlspecialchars($session['agenda']) ?>"
                                                            data-status="<?= htmlspecialchars($session['status']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-session-btn"
                                                            data-id="<?= $session['id'] ?>"
                                                            data-date="<?= date('M d, Y', strtotime($session['session_date'])) ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> No upcoming Senate sessions are scheduled at this time.
                            </div>
                        <?php } ?>
                        
                        <?php if ($shouldUseAdminInterface): ?>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                                    <i class="fas fa-plus-circle me-1"></i> Add New Session
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php
                    } catch (Exception $e) { ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> Error loading Senate sessions: <?= $e->getMessage() ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Senate Resources -->
        <div class="col-12 mb-4" id="senate-resources">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-book me-2"></i>Senate Resources</h3>
                                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <a href="src_constitution.php" class="resource-card text-decoration-none">
                                <div class="p-4 text-center rounded h-100">
                                    <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                    <h5>VVUSRC Constitution</h5>
                                    <p class="text-muted">The official governing document of the Student Representative Council</p>
                            </div>
                            </a>
                        </div>
                        
                        <div class="col-md-4">
                            <a href="documents.php?category=bylaws" class="resource-card text-decoration-none">
                                <div class="p-4 text-center rounded h-100">
                                    <i class="fas fa-file-alt fa-3x text-info mb-3"></i>
                                    <h5>Senate Bylaws</h5>
                                    <p class="text-muted">Rules and procedures governing Senate operations</p>
                    </div>
                            </a>
                </div>
                        
                        <div class="col-md-4">
                            <?php if ($shouldUseAdminInterface): ?>
                            <a href="minutes.php?committee=Senate" class="resource-card text-decoration-none">
                            <?php else: ?>
                            <div class="resource-card text-decoration-none">
                            <?php endif; ?>
                                <div class="p-4 text-center rounded h-100">
                                    <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                                    <h5>Meeting Minutes</h5>
                                    <p class="text-muted">Records of previous Senate meetings and discussions</p>
                                    <?php if (!$shouldUseAdminInterface): ?>
                                    <button class="btn btn-secondary" disabled>Restricted Access</button>
                                    <p class="small text-muted mt-2">Only SRC members can access minutes</p>
                                    <?php endif; ?>
                                </div>
                            <?php if ($shouldUseAdminInterface): ?>
                            </a>
                            <?php else: ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <a href="documents.php?category=legislation" class="resource-card text-decoration-none">
                                <div class="p-4 text-center rounded h-100">
                                    <i class="fas fa-gavel fa-3x text-warning mb-3"></i>
                                    <h5>Legislation Archive</h5>
                                    <p class="text-muted">Browse past bills, resolutions, and acts passed by the Senate</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-4">
                            <a href="committees.php" class="resource-card text-decoration-none">
                                <div class="p-4 text-center rounded h-100">
                                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                    <h5>Senate Committees</h5>
                                    <p class="text-muted">Information about standing and ad hoc committees</p>
                                </div>
                            </a>
                        </div>
                        
                        <div class="col-md-4">
                            <a href="#faqs" class="resource-card text-decoration-none">
                                <div class="p-4 text-center rounded h-100">
                                    <i class="fas fa-question-circle fa-3x text-secondary mb-3"></i>
                                    <h5>FAQs</h5>
                                    <p class="text-muted">Frequently asked questions about the Senate</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How to Get Involved -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-3">
                    <h3 class="mb-0"><i class="fas fa-hands-helping me-2"></i>How to Get Involved</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-7">
                            <h4 class="mb-3">Student Participation</h4>
                            <p>The Senate welcomes student involvement and input. Here are ways you can participate:</p>
                            
                            <ul class="list-group list-group-flush mb-4">
                                <li class="list-group-item bg-light"><i class="fas fa-vote-yea text-primary me-2"></i><strong>Run for Senate:</strong> Consider running as a representative from your constituency</li>
                                <li class="list-group-item"><i class="fas fa-comment text-primary me-2"></i><strong>Attend Meetings:</strong> Senate meetings are open to all students as observers</li>
                                <li class="list-group-item bg-light"><i class="fas fa-lightbulb text-primary me-2"></i><strong>Propose Ideas:</strong> Submit policy proposals through your Senator</li>
                                <li class="list-group-item"><i class="fas fa-clipboard-list text-primary me-2"></i><strong>Join Committees:</strong> Volunteer to serve on Senate committees</li>
                                <li class="list-group-item bg-light"><i class="fas fa-bullhorn text-primary me-2"></i><strong>Voice Concerns:</strong> Share your concerns and feedback with your representatives</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-5">
                            <div class="card bg-light h-100">
                                <div class="card-body p-4">
                                    <h4 class="card-title mb-3"><i class="fas fa-calendar-plus text-success me-2"></i>Upcoming Elections</h4>
                                    <p>Interested in becoming a Senator? Elections for the next academic year will be held during the General Election period.</p>
                                    
                                    <div class="alert alert-info mb-3">
                                        <i class="fas fa-info-circle me-2"></i> Nomination forms will be available from the Electoral Commission during the election period.
                                    </div>
                                    
                                    <p class="mb-0">For more information about the election process and requirements, contact:</p>
                                    <ul class="list-unstyled mt-2">
                                        <li><i class="fas fa-envelope me-2"></i> electoral.commission@vvu.edu.gh</li>
                                        <li><i class="fas fa-phone me-2"></i> +233 XX XXX XXXX</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FAQs Section -->
<div class="col-12 mb-4" id="faqs">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-secondary text-white py-3">
            <h3 class="mb-0"><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h3>
        </div>
        <div class="card-body p-4">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            What is the role of the Senate in the SRC?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            The Senate is the chief legislative authority of the VVUSRC. It is empowered to enact laws within Valley View University regulations that serve the best interest of the Council and the Institution. The Senate also provides oversight for the Executive Committee and approves the SRC budget.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            How often does the Senate meet?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            According to the VVUSRC Constitution, regular Senate meetings are held on the third week of every month during the academic year. Special sessions may be called by the Senate President or upon request by a majority of Senators.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            How are Senate members elected?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            The Senate President is elected during the General Election. Other Senators are elected by their respective constituencies (class levels, residential halls, departments, etc.) at the beginning of each academic year. The Senate leadership positions (Vice President, Clerk, Sergeant-at-Arms) are elected by the Senate members during their first sitting.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            How can I attend Senate meetings?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Senate meetings are generally open to all students. The schedule is posted on notice boards and announced through official SRC communication channels. If you wish to address the Senate, you must submit a request to the Senate Clerk at least 48 hours before the scheduled meeting.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            How can I propose legislation to the Senate?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="faqFive" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Any student can propose legislation by working with their Senator. Draft your proposal and submit it to your constituency's Senator, who will then introduce it during a Senate session. You can also attend Senate meetings to advocate for your proposal during the public comment period.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqSix">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                            What is the relationship between the Senate and the Executive Committee?
                        </button>
                    </h2>
                    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="faqSix" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            The Senate provides oversight for the Executive Committee. The Executive implements policies and decisions approved by the Senate. The Senate has the power to summon Executive members to answer questions or present reports. The Senate also approves the SRC budget proposed by the Executive Committee.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced Styling for Senate Page */
/* Timeline styling */
.timeline-container {
    padding: 20px 0;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: -1px;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -36px;
    top: 6px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #0d6efd;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: transform 0.3s ease;
}

.timeline-item:hover .timeline-content {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Card styling */
.card {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    font-weight: 600;
}

/* Mobile Full-Width Optimization for Senate Page */
@media (max-width: 991px) {
    [class*="col-md-"], [class*="col-lg-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure senate header has border-radius on mobile */
    .header, .senate-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards extend full width */
    .card, .senate-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}

/* ... existing code ... */
</style>

<?php if ($shouldUseAdminInterface): ?>
<!-- Add Senate Member Modal -->

<div class="modal fade" id="addSenateMemberModal" tabindex="-1" aria-labelledby="addSenateMemberModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addSenateMemberModalLabel"><i class="fas fa-plus-circle me-2"></i>Add Senate Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="senate_actions.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_member">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                            <select class="form-select" id="position" name="position" required>
                                <option value="">Select Position</option>
                                <option value="Senate President">Senate President</option>
                                <option value="Deputy Senate President">Deputy Senate President</option>
                                <option value="Senate Secretary">Senate Secretary</option>
                                <option value="Faculty Representative">Faculty Representative</option>
                                <option value="Class Representative">Class Representative</option>
                                <option value="Residence Hall Representative">Residence Hall Representative</option>
                                <option value="International Student Representative">International Student Representative</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="faculty" class="form-label">Faculty/Department</label>
                            <input type="text" class="form-control" id="faculty" name="faculty">
                        </div>
                        <div class="col-md-6">
                            <label for="term" class="form-label">Term of Office</label>
                            <input type="text" class="form-control" id="term" name="term" placeholder="e.g., 2023-2024">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="photo" class="form-label">Profile Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <small class="form-text text-muted">Upload a professional photo (Max size: 2MB, Formats: JPG, PNG)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="responsibilities" class="form-label">Responsibilities</label>
                        <textarea class="form-control" id="responsibilities" name="responsibilities" rows="3" placeholder="Enter responsibilities, one per line"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Brief Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle me-1"></i> Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Senate Info Modal -->
<div class="modal fade" id="editSenateInfoModal" tabindex="-1" aria-labelledby="editSenateInfoModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editSenateInfoModalLabel"><i class="fas fa-edit me-2"></i>Edit Senate Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="senate_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div class="mb-3">
                        <label for="senate_description" class="form-label">Senate Description</label>
                        <textarea class="form-control" id="senate_description" name="senate_description" rows="4">The Student Senate is the chief legislative authority of the SRC, empowered to enact laws within Valley View University regulations that serve the best interest of the Council and the Institution.</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Functions and Powers</label>
                        <div id="functions_container">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="functions[]" value="Enact laws and policies that govern student affairs">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="functions[]" value="Review and approve the SRC budget">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="functions[]" value="Oversight of Executive Committee activities">
                                <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_function"><i class="fas fa-plus me-1"></i> Add Function</button>
                    </div>
                    
                    <div class="mb-3">
                        <label for="senate_structure" class="form-label">Senate Structure</label>
                        <textarea class="form-control" id="senate_structure" name="senate_structure" rows="4">The Senate consists of elected representatives from various constituencies within the university.</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="meeting_schedule" class="form-label">Meeting Schedule</label>
                        <textarea class="form-control" id="meeting_schedule" name="meeting_schedule" rows="3">Regular Sessions: Bi-weekly meetings during the academic term
Special Sessions: Called by the Senate President or upon request of one-third of Senate members
Emergency Sessions: Called to address urgent matters requiring immediate attention</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Senate Sessions Modal -->
<div class="modal fade" id="manageSenateSessionsModal" tabindex="-1" aria-labelledby="manageSenateSessionsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manageSenateSessionsModalLabel"><i class="fas fa-calendar-alt me-2"></i>Manage Senate Sessions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSessionModal">
                        <i class="fas fa-plus me-1"></i> Add New Session
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Agenda</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch senate sessions from database
                            try {
                                // Check if senate_sessions table exists
                                $checkTableSql = "SHOW TABLES LIKE 'senate_sessions'";
                                $tableExists = count(fetchAll($checkTableSql)) > 0;
                                
                                if ($tableExists) {
                                    // Fetch all sessions ordered by date
                                    $sessionsSql = "SELECT * FROM senate_sessions ORDER BY session_date DESC";
                                    $sessions = fetchAll($sessionsSql);
                                    
                                    if (!empty($sessions)) {
                                        foreach ($sessions as $session) {
                                            // Determine status class
                                            $statusClass = '';
                                            switch($session['status']) {
                                                case 'Scheduled':
                                                    $statusClass = 'bg-secondary';
                                                    break;
                                                case 'Pending':
                                                    $statusClass = 'bg-warning text-dark';
                                                    break;
                                                case 'In Progress':
                                                    $statusClass = 'bg-info';
                                                    break;
                                                case 'Completed':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'Cancelled':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            
                                            echo '<tr>';
                                            echo '<td>' . date('Y-m-d', strtotime($session['session_date'])) . '</td>';
                                            echo '<td>' . htmlspecialchars($session['session_type']) . '</td>';
                                            echo '<td>' . htmlspecialchars($session['agenda']) . '</td>';
                                            echo '<td><span class="badge ' . $statusClass . '">' . htmlspecialchars($session['status']) . '</span></td>';
                                            echo '<td>';
                                            echo '<button type="button" class="btn btn-sm btn-primary edit-session-btn me-1" 
                                                    data-id="' . $session['id'] . '"
                                                    data-date="' . $session['session_date'] . '"
                                                    data-type="' . htmlspecialchars($session['session_type']) . '"
                                                    data-agenda="' . htmlspecialchars($session['agenda']) . '"
                                                    data-status="' . htmlspecialchars($session['status']) . '">
                                                    <i class="fas fa-edit"></i>
                                                </button>';
                                            echo '<button type="button" class="btn btn-sm btn-danger delete-session-btn"
                                                    data-id="' . $session['id'] . '"
                                                    data-date="' . date('M d, Y', strtotime($session['session_date'])) . '">
                                                    <i class="fas fa-trash"></i>
                                                </button>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No senate sessions found</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">Senate sessions table not found</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-center">Error loading senate sessions: ' . $e->getMessage() . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Senate Documents Modal -->
<div class="modal fade" id="manageSenateDocumentsModal" tabindex="-1" aria-labelledby="manageSenateDocumentsModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="manageSenateDocumentsModalLabel"><i class="fas fa-file-alt me-2"></i>Manage Senate Documents</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                        <i class="fas fa-plus me-1"></i> Add New Document
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Date Added</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch senate documents from database
                            try {
                                // Check if senate_documents table exists
                                $checkTableSql = "SHOW TABLES LIKE 'senate_documents'";
                                $tableExists = count(fetchAll($checkTableSql)) > 0;
                                
                                if ($tableExists) {
                                    // Fetch all documents ordered by date
                                    $documentsSql = "SELECT * FROM senate_documents ORDER BY created_at DESC";
                                    $documents = fetchAll($documentsSql);
                                    
                                    if (!empty($documents)) {
                                        foreach ($documents as $document) {
                                            // Determine status class
                                            $statusClass = '';
                                            switch($document['status']) {
                                                case 'Active':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'Draft':
                                                    $statusClass = 'bg-warning text-dark';
                                                    break;
                                                case 'Archived':
                                                    $statusClass = 'bg-secondary';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-info';
                                            }
                                            
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($document['title']) . '</td>';
                                            echo '<td>' . htmlspecialchars($document['type']) . '</td>';
                                            echo '<td>' . date('Y-m-d', strtotime($document['created_at'])) . '</td>';
                                            echo '<td><span class="badge ' . $statusClass . '">' . htmlspecialchars($document['status']) . '</span></td>';
                                            echo '<td>';
                                            echo '<button type="button" class="btn btn-sm btn-primary edit-document-btn me-1" 
                                                    data-id="' . $document['id'] . '">
                                                    <i class="fas fa-edit"></i>
                                                </button>';
                                            echo '<button type="button" class="btn btn-sm btn-danger delete-document-btn me-1"
                                                    data-id="' . $document['id'] . '"
                                                    data-title="' . htmlspecialchars($document['title']) . '">
                                                    <i class="fas fa-trash"></i>
                                                </button>';
                                            echo '<a href="../uploads/documents/' . htmlspecialchars($document['file_name']) . '" class="btn btn-sm btn-info" download>
                                                    <i class="fas fa-download"></i>
                                                </a>';
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="5" class="text-center">No senate documents found</td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center">Senate documents table not found</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="5" class="text-center">Error loading senate documents: ' . $e->getMessage() . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="addDocumentForm" class="card mt-3 d-none">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Add New Document</h5>
                    </div>
                    <div class="card-body">
                        <form action="senate_actions.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_document">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="document_title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="document_title" name="document_title" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="document_type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="document_type" name="document_type" required>
                                        <option value="Constitution">Constitution</option>
                                        <option value="Procedure">Procedure</option>
                                        <option value="Guidelines">Guidelines</option>
                                        <option value="Minutes">Minutes</option>
                                        <option value="Report">Report</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="document_file" class="form-label">Document File <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="document_file" name="document_file" required accept=".pdf,.doc,.docx">
                                <small class="form-text text-muted">Accepted formats: PDF, DOC, DOCX (Max size: 10MB)</small>
                            </div>
                            <div class="mb-3">
                                <label for="document_description" class="form-label">Description</label>
                                <textarea class="form-control" id="document_description" name="document_description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="document_status" class="form-label">Status</label>
                                <select class="form-select" id="document_status" name="document_status">
                                    <option value="Active">Active</option>
                                    <option value="Draft">Draft</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" id="cancelAddDocument">Cancel</button>
                                <button type="submit" class="btn btn-success">Upload Document</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Senate Members Modal -->
<div class="modal fade" id="manageSenateMembersModal" tabindex="-1" aria-labelledby="manageSenateMembersModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="manageSenateMembersModalLabel"><i class="fas fa-user-edit me-2"></i>Manage Senate Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Faculty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch senate members from database
                            try {
                                $membersSql = "SELECT * FROM senate_members ORDER BY position, name";
                                $members = fetchAll($membersSql);
                                
                                if (!empty($members)) {
                                    foreach ($members as $member) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($member['name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($member['position']) . '</td>';
                                        echo '<td>' . htmlspecialchars($member['faculty']) . '</td>';
                                        echo '<td>';
                                        echo '<button type="button" class="btn btn-sm btn-primary edit-member-btn me-1" data-id="' . $member['id'] . '"><i class="fas fa-edit"></i></button>';
                                        echo '<button type="button" class="btn btn-sm btn-danger delete-member-btn" data-id="' . $member['id'] . '" data-name="' . htmlspecialchars($member['name']) . '"><i class="fas fa-trash"></i></button>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">No senate members found</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="4" class="text-center">Error loading senate members</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSenateMemberModal">
                    <i class="fas fa-plus me-1"></i> Add New Member
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Member Confirmation Modal -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-labelledby="deleteMemberModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMemberModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="memberNameToDelete">this member</span>?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <form action="senate_actions.php" method="post">
                    <input type="hidden" name="action" value="delete_member">
                    <input type="hidden" name="member_id" id="memberIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Content Modal -->
<div class="modal fade" id="deleteContentModal" tabindex="-1" aria-labelledby="deleteContentModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteContentModalLabel"><i class="fas fa-trash me-2"></i>Delete Senate Content</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Please select the content you wish to delete. This action cannot be undone.
                </div>
                
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteMemberModal">
                        <div>
                            <i class="fas fa-users me-2"></i> Delete Senate Member
                        </div>
                        <span class="badge bg-danger rounded-pill"><i class="fas fa-arrow-right"></i></span>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteSessionModal">
                        <div>
                            <i class="fas fa-calendar-alt me-2"></i> Delete Senate Session
                        </div>
                        <span class="badge bg-danger rounded-pill"><i class="fas fa-arrow-right"></i></span>
                    </button>
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-bs-toggle="modal" data-bs-target="#deleteDocumentModal">
                        <div>
                            <i class="fas fa-file-alt me-2"></i> Delete Senate Document
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

<!-- Add Session Modal -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-labelledby="addSessionModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addSessionModalLabel"><i class="fas fa-plus-circle me-2"></i>Add Senate Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="senate_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_session">
                    
                    <div class="mb-3">
                        <label for="session_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="session_date" name="session_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="session_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="session_type" name="session_type" required>
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="session_agenda" class="form-label">Agenda <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="session_agenda" name="session_agenda" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="session_status" class="form-label">Status</label>
                        <select class="form-select" id="session_status" name="session_status">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Session Modal -->
<div class="modal fade" id="editSessionModal" tabindex="-1" aria-labelledby="editSessionModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editSessionModalLabel"><i class="fas fa-edit me-2"></i>Edit Senate Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="senate_actions.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_session">
                    <input type="hidden" name="session_id" id="edit_session_id">
                    
                    <div class="mb-3">
                        <label for="edit_session_date" class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="edit_session_date" name="session_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_session_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_session_type" name="session_type" required>
                            <option value="Regular">Regular</option>
                            <option value="Special">Special</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_session_agenda" class="form-label">Agenda <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_session_agenda" name="session_agenda" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_session_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_session_status" name="session_status">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Session Modal -->
<div class="modal fade" id="deleteSessionModal" tabindex="-1" aria-labelledby="deleteSessionModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteSessionModalLabel"><i class="fas fa-trash me-2"></i>Delete Senate Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the Senate session scheduled for <span id="sessionDateToDelete"></span>?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <form action="senate_actions.php" method="post">
                    <input type="hidden" name="action" value="delete_session">
                    <input type="hidden" name="session_id" id="sessionIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Document Modal -->
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addDocumentModalLabel"><i class="fas fa-plus-circle me-2"></i>Add Senate Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="senate_actions.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_document">
                    
                    <div class="mb-3">
                        <label for="document_title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="document_title" name="document_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="">Select Type</option>
                            <option value="Constitution">Constitution</option>
                            <option value="Procedure">Procedure</option>
                            <option value="Guidelines">Guidelines</option>
                            <option value="Minutes">Minutes</option>
                            <option value="Report">Report</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_file" class="form-label">Document File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="document_file" name="document_file" required accept=".pdf,.doc,.docx">
                        <small class="form-text text-muted">Accepted formats: PDF, DOC, DOCX (Max size: 10MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_description" class="form-label">Description</label>
                        <textarea class="form-control" id="document_description" name="document_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="document_status" class="form-label">Status</label>
                        <select class="form-select" id="document_status" name="document_status">
                            <option value="Active">Active</option>
                            <option value="Draft">Draft</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Document Modal -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true" data-bs-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteDocumentModalLabel"><i class="fas fa-trash me-2"></i>Delete Senate Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the document "<span id="documentTitleToDelete"></span>"?</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <form action="senate_actions.php" method="post">
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="document_id" id="documentIdToDelete">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// JavaScript for Senate page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add function button
    const addFunctionBtn = document.getElementById('add_function');
    if (addFunctionBtn) {
        addFunctionBtn.addEventListener('click', function() {
            const container = document.getElementById('functions_container');
            const newItem = document.createElement('div');
            newItem.className = 'input-group mb-2';
            newItem.innerHTML = `
                <input type="text" class="form-control" name="functions[]" value="">
                <button type="button" class="btn btn-outline-danger remove-item"><i class="fas fa-times"></i></button>
            `;
            container.appendChild(newItem);
            
            // Add event listener to the new remove button
            newItem.querySelector('.remove-item').addEventListener('click', function() {
                container.removeChild(newItem);
            });
        });
    }
    
    // Remove function buttons
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.input-group').remove();
        });
    });
    
    // Edit session functionality
    const editSessionBtns = document.querySelectorAll('.edit-session-btn');
    if (editSessionBtns.length > 0) {
        editSessionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = this.getAttribute('data-id');
                const sessionDate = this.getAttribute('data-date');
                const sessionType = this.getAttribute('data-type');
                const sessionAgenda = this.getAttribute('data-agenda');
                const sessionStatus = this.getAttribute('data-status');
                
                document.getElementById('edit_session_id').value = sessionId;
                document.getElementById('edit_session_date').value = sessionDate;
                document.getElementById('edit_session_type').value = sessionType;
                document.getElementById('edit_session_agenda').value = sessionAgenda;
                document.getElementById('edit_session_status').value = sessionStatus;
                
                const editModal = new bootstrap.Modal(document.getElementById('editSessionModal'));
                editModal.show();
            });
        });
    }
    
    // Delete session functionality
    const deleteSessionBtns = document.querySelectorAll('.delete-session-btn');
    if (deleteSessionBtns.length > 0) {
        deleteSessionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const sessionId = this.getAttribute('data-id');
                const sessionDate = this.getAttribute('data-date');
                
                document.getElementById('sessionIdToDelete').value = sessionId;
                document.getElementById('sessionDateToDelete').textContent = sessionDate;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteSessionModal'));
                deleteModal.show();
            });
        });
    }
    
    // Delete document functionality
    const deleteDocumentBtns = document.querySelectorAll('.delete-document-btn');
    if (deleteDocumentBtns.length > 0) {
        deleteDocumentBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const documentId = this.getAttribute('data-id');
                const documentTitle = this.getAttribute('data-title');
                
                document.getElementById('documentIdToDelete').value = documentId;
                document.getElementById('documentTitleToDelete').textContent = documentTitle;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteDocumentModal'));
                deleteModal.show();
            });
        });
    }
    
    // Edit member functionality
    const editMemberBtns = document.querySelectorAll('.edit-member-btn');
    if (editMemberBtns.length > 0) {
        editMemberBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const memberId = this.getAttribute('data-id');
                // Implement edit functionality - could load member data via AJAX
                // and populate a form, or redirect to an edit page
                alert('Edit member ID: ' + memberId + ' - Implement this functionality');
            });
        });
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
