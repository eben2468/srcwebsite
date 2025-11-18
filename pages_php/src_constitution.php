<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Get current user
$currentUser = getCurrentUser();
$isAdmin = isAdmin();

// Set page title
$pageTitle = "SRC Constitution";

// Include header
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <?php
    // Define page title, icon, and actions for the modern header
    $pageTitle = "SRC Constitution";
    $pageIcon = "fa-scroll";
    $pageDescription = "The foundational document that governs the Students Representative Council";
    $actions = [];

    // Add back button
    $backButton = [
        'href' => 'senate.php',
        'icon' => 'fa-arrow-left',
        'text' => 'Back to Senate'
    ];

    if ($isAdmin) {
        $actions[] = [
            'url' => '../update_all_portfolio_info.php',
            'icon' => 'fa-sync-alt',
            'text' => 'Update Portfolios',
            'class' => 'btn-outline-light',
            'onclick' => 'return confirm(\'This will update all portfolio information based on the VVUSRC Constitution. Continue?\');'
        ];
    }

    // Include the modern page header
    include_once 'includes/modern_page_header.php';
    ?>
    
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
    

    
    <!-- Hero Section -->
    <div class="constitution-hero mb-4 rounded overflow-hidden position-relative">
        <div class="overlay"></div>
        <div class="hero-content text-center text-white p-5">
            <h1 class="display-4 fw-bold mb-3">VVUSRC Constitution</h1>
            <p class="lead mb-4">The foundational document that governs the Students Representative Council of Valley View University</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="../src_constitution.pdf" class="btn btn-lg btn-primary" download>
                    <i class="fas fa-file-pdf me-2"></i> Download PDF
                </a>
                <a href="../src_constitution.docx" class="btn btn-lg btn-outline-light" download>
                    <i class="fas fa-file-word me-2"></i> Download Word
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="row g-4">
        <!-- Left Column - Constitution Overview -->
        <div class="col-lg-8">
            <!-- Preamble Section -->
            <div class="card shadow-sm border-0 mb-4 constitution-card">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="card-title mb-0"><i class="fas fa-feather-alt me-2"></i>Preamble</h3>
                </div>
                <div class="card-body p-4">
                    <div class="preamble-text p-4 bg-light rounded border-start border-5 border-primary">
                        <p class="lead fst-italic">"Having absolute belief in the Omnipotent God, the first cause; mindful of the fact that no Community exist without Laws and Leadership. WE the Students of Valley View University DETERMINED to raise to the highest level the moral, spiritual, political and intellectual standards of our society for the worthy emulation of posterity..."</p>
                    </div>
                </div>
            </div>
            
            <!-- Key Articles -->
            <div class="card shadow-sm border-0 mb-4 constitution-card">
                <div class="card-header bg-info text-white py-3">
                    <h3 class="card-title mb-0"><i class="fas fa-landmark me-2"></i>Key Articles</h3>
                </div>
                <div class="card-body p-4">
                    <div class="accordion" id="constitutionAccordion">
                        <!-- Article I -->
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <strong>Article I: Name and Supremacy of the Constitution</strong>
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#constitutionAccordion">
                                <div class="accordion-body">
                                    <p>The Association shall be known and called Valley View University Students Representative Council, herein referred to as "SRC". This Constitution shall be the supreme constitution of all Students, Clubs, Associations in the Valley View University.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Article II -->
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <strong>Article II: Aims and Objectives</strong>
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#constitutionAccordion">
                                <div class="accordion-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>To serve as the mouthpiece of the entire student body.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>To pursue the interest and welfare of members of the Council.</li>
                                        <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>To bring out the abilities of students through team work.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>To promote holistic education among members.</li>
                                        <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>To establish good relations between members of this Council, and the outside world.</li>
                                        <li class="list-group-item"><i class="fas fa-check-circle text-success me-2"></i>To create platforms to enable students contribute to the progress and development of this university.</li>
                                        <li class="list-group-item bg-light"><i class="fas fa-check-circle text-success me-2"></i>To promoting Valley View University to the outside world on the basis of her core values, excellence, integrity and service.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Article III -->
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    <strong>Article III: Membership</strong>
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#constitutionAccordion">
                                <div class="accordion-body">
                                    <p>Membership of the Council shall be open to all students of Valley View University who have duly registered for the current academic year. Every registered student automatically becomes a member of the Council.</p>
                                    
                                    <h6 class="mt-3">Rights of Members:</h6>
                                    <ul>
                                        <li>To attend all general meetings of the Council</li>
                                        <li>To vote in all elections organized by the Electoral Commission</li>
                                        <li>To contest for any position in the Council</li>
                                        <li>To enjoy all facilities and services provided by the Council</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Article IV -->
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    <strong>Article IV: Structure of Government</strong>
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#constitutionAccordion">
                                <div class="accordion-body">
                                    <p>The SRC shall consist of three branches of government:</p>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-primary">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-gavel fa-3x text-primary mb-3"></i>
                                                    <h5 class="card-title">Legislative Branch</h5>
                                                    <p class="card-text">The Senate, which makes laws and policies for the student body</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-success">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-user-tie fa-3x text-success mb-3"></i>
                                                    <h5 class="card-title">Executive Branch</h5>
                                                    <p class="card-text">The Executive Committee, which implements policies and manages day-to-day affairs</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 border-danger">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-balance-scale fa-3x text-danger mb-3"></i>
                                                    <h5 class="card-title">Judicial Branch</h5>
                                                    <p class="card-text">The Judicial Committee, which interprets the constitution and resolves disputes</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Article V -->
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    <strong>Article V: The Executive Committee</strong>
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#constitutionAccordion">
                                <div class="accordion-body">
                                    <p>The Executive Committee shall be responsible for the day-to-day administration of the Council. It shall consist of:</p>
                                    <ul>
                                        <li>President</li>
                                        <li>Vice President</li>
                                        <li>General Secretary</li>
                                        <li>Financial Secretary</li>
                                        <li>Organizing Secretary</li>
                                        <li>Editor</li>
                                        <li>Public Relations Officer</li>
                                        <li>Welfare Officer</li>   
                                        <li>Women's Commissioner</li>
                                        <li>Sports Commissioner</li>
                                        <li>Chaplain</li>
                                    </ul>
                                    
                                    <div class="text-center mt-3">
                                        <a href="portfolio.php" class="btn btn-outline-primary">
                                            <i class="fas fa-users me-2"></i> View SRC Portfolios
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Article VI -->
                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header" id="headingSix">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                    <strong>Article VI: Elections</strong>
                                </button>
                            </h2>
                            <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#constitutionAccordion">
                                <div class="accordion-body">
                                    <p>Elections shall be conducted to choose officers to run the affairs of the Council. The Electoral Commission shall be responsible for conducting all elections.</p>
                                    
                                    <h6 class="mt-3">Election Timeline:</h6>
                                    <ul>
                                        <li>Elections shall be held in the second semester of each academic year</li>
                                        <li>Elected officers shall serve for one academic year</li>
                                        <li>Handover ceremony shall take place within two weeks after the end of examinations</li>
                                    </ul>
                                    
                                    <div class="text-center mt-3">
                                        <a href="elections.php" class="btn btn-outline-primary">
                                            <i class="fas fa-vote-yea me-2"></i> View Elections
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Sidebar Content -->
        <div class="col-lg-4">
            <!-- Constitution Timeline -->
            <div class="card shadow-sm border-0 mb-4 constitution-card">
                <div class="card-header bg-success text-white py-3">
                    <h3 class="card-title mb-0"><i class="fas fa-history me-2"></i>Constitution Timeline</h3>
                </div>
                <div class="card-body p-4">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5>1979</h5>
                                <p>First SRC Constitution drafted and approved</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5>1995</h5>
                                <p>Major revision to accommodate university status</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5>2008</h5>
                                <p>Updated to include multi-campus representation</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5>2015</h5>
                                <p>Comprehensive review and modernization</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5>2022</h5>
                                <p>Latest amendments and updates</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card shadow-sm border-0 mb-4 constitution-card">
                <div class="card-header bg-warning text-dark py-3">
                    <h3 class="card-title mb-0"><i class="fas fa-link me-2"></i>Related Resources</h3>
                </div>
                <div class="card-body p-4">
                    <div class="list-group">
                        <a href="senate.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-gavel me-2 text-primary"></i> Senate
                            </div>
                            <span class="badge bg-primary rounded-pill"><i class="fas fa-arrow-right"></i></span>
                        </a>
                        <a href="committees.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-users me-2 text-success"></i> Committees
                            </div>
                            <span class="badge bg-success rounded-pill"><i class="fas fa-arrow-right"></i></span>
                        </a>
                        <a href="portfolio.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-user-tie me-2 text-info"></i> Portfolios
                            </div>
                            <span class="badge bg-info rounded-pill"><i class="fas fa-arrow-right"></i></span>
                        </a>
                        <a href="elections.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-vote-yea me-2 text-danger"></i> Elections
                            </div>
                            <span class="badge bg-danger rounded-pill"><i class="fas fa-arrow-right"></i></span>
                        </a>
                        <a href="senate_resources.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-book me-2 text-secondary"></i> Senate Resources
                            </div>
                            <span class="badge bg-secondary rounded-pill"><i class="fas fa-arrow-right"></i></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Constitutional FAQs -->
            <div class="card shadow-sm border-0 mb-4 constitution-card">
                <div class="card-header bg-secondary text-white py-3">
                    <h3 class="card-title mb-0"><i class="fas fa-question-circle me-2"></i>Constitutional FAQs</h3>
                </div>
                <div class="card-body p-4">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="faqOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="false" aria-controls="faqCollapseOne">
                                    How is the Constitution amended?
                                </button>
                            </h2>
                            <div id="faqCollapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Amendments to the Constitution require a two-thirds majority vote of the Senate and approval by the University Administration. Proposed amendments must be published for at least 14 days before voting.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="faqTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                                    Who interprets the Constitution?
                                </button>
                            </h2>
                            <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The Judicial Committee has the authority to interpret the Constitution and resolve any disputes arising from its interpretation or application.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 shadow-sm">
                            <h2 class="accordion-header" id="faqThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                                    What happens if there's a constitutional crisis?
                                </button>
                            </h2>
                            <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    In case of a constitutional crisis, the Dean of Students and the University Administration may intervene to resolve the situation in consultation with student representatives.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced styling for Constitution page */
.constitution-hero {
    background: linear-gradient(rgba(25, 55, 109, 0.85), rgba(25, 55, 109, 0.95)), url('../images/vvu-campus.jpg') center/cover no-repeat;
    min-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(25, 55, 109, 0.2);
}

.hero-content {
    position: relative;
    z-index: 2;
}

.constitution-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}

.constitution-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.preamble-text {
    position: relative;
}

.preamble-text::before {
    content: """;
    position: absolute;
    top: -20px;
    left: 10px;
    font-size: 100px;
    color: rgba(0, 123, 255, 0.1);
    font-family: Georgia, serif;
}

.preamble-text::after {
    content: """;
    position: absolute;
    bottom: -80px;
    right: 10px;
    font-size: 100px;
    color: rgba(0, 123, 255, 0.1);
    font-family: Georgia, serif;
}

/* Timeline styling */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 2px;
    background-color: #28a745;
}

.timeline-item {
    position: relative;
    margin-bottom: 25px;
}

.timeline-marker {
    position: absolute;
    left: -38px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #28a745;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #28a745;
}

.timeline-content {
    padding-left: 15px;
}

.timeline-content h5 {
    color: #28a745;
    font-weight: 600;
    margin-bottom: 5px;
}

/* Accordion styling */
.accordion-item {
    border-radius: 0.5rem;
    overflow: hidden;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(0, 123, 255, 0.1);
    color: #0d6efd;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0, 123, 255, 0.25);
}

.list-group-item {
    border-left: none;
    border-right: none;
    padding: 0.75rem 1.25rem;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
    transform: translateX(5px);
}

.list-group-item:first-child {
    border-top: none;
}

.list-group-item:last-child {
    border-bottom: none;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 
