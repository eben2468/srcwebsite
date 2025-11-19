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
require_once __DIR__ . '/../includes/settings_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check if about page feature is enabled
if (!hasFeaturePermission('enable_about')) {
    $_SESSION['error'] = "The about page feature is currently disabled.";
    header("Location: dashboard.php");
    exit();
}

// Fetch executive portfolios from database
try {
    $executivePortfolios = [];
    $portfolioQuery = "SELECT * FROM portfolios WHERE title IN ('President', 'Vice President', 'Executive Secretary', 'Finance Officer', 'Senate President') ORDER BY FIELD(title, 'President', 'Vice President', 'Executive Secretary', 'Finance Officer', 'Senate President')";
    $executivePortfolios = fetchAll($portfolioQuery);

    // Fetch department portfolios
    $departmentQuery = "SELECT * FROM portfolios WHERE title NOT IN ('President', 'Vice President', 'Executive Secretary', 'Finance Officer', 'Senate President') ORDER BY title";
    $departmentPortfolios = fetchAll($departmentQuery);
} catch (Exception $e) {
    // Silent error handling - will just show static content if database fetch fails
    $executivePortfolios = [];
    $departmentPortfolios = [];
}

// Set page title
$pageTitle = "About SRC - SRC Management System";

// Include header
require_once 'includes/header.php';
?>

<script>
    document.body.classList.add('about-page');
</script>

<?php
// Set up modern page header variables
$pageTitle = "About VVU SRC";
$pageIcon = "fa-info-circle";
$pageDescription = "Learn about our mission, vision, and organizational structure";
$actions = [
    ['url' => '#mission', 'icon' => 'fa-bullseye', 'text' => 'Our Mission', 'class' => 'btn-primary'],
    ['url' => '#vision', 'icon' => 'fa-eye', 'text' => 'Our Vision', 'class' => 'btn-primary'],
    ['url' => '#structure', 'icon' => 'fa-sitemap', 'text' => 'Structure', 'class' => 'btn-primary']
];

// Include the modern page header
include 'includes/modern_page_header.php';
?>

<div class="dashboard-section animate-fadeIn" id="mission">
    <div class="content-card">
        <div class="content-card-header">
            <h3 class="content-card-title"><i class="fas fa-bullseye me-2"></i>Our Mission</h3>
                    </div>
        <div class="content-card-body">
            <p class="lead">The Student Representative Council (SRC) serves as the official voice of the student body, advocating for student interests and working collaboratively with university administration to enhance the academic and social experience for all students.</p>
            
            <p>Our mission is to:</p>
            <ul class="list-group list-group-flush mission-list">
                <li class="list-group-item d-flex align-items-center"><i class="fas fa-check-circle text-success me-3"></i> Represent and protect the rights and interests of all students</li>
                <li class="list-group-item d-flex align-items-center"><i class="fas fa-check-circle text-success me-3"></i> Provide essential services and support to enhance student welfare</li>
                <li class="list-group-item d-flex align-items-center"><i class="fas fa-check-circle text-success me-3"></i> Organize social, cultural, and educational events that enrich campus life</li>
                <li class="list-group-item d-flex align-items-center"><i class="fas fa-check-circle text-success me-3"></i> Foster a diverse and inclusive community where all students feel valued</li>
                <li class="list-group-item d-flex align-items-center"><i class="fas fa-check-circle text-success me-3"></i> Facilitate effective communication between students and university administration</li>
            </ul>
                    </div>
                </div>
            </div>
            
<div class="dashboard-section animate-fadeIn" style="animation-delay: 0.1s;" id="vision">
    <div class="row" style="display: flex; flex-wrap: wrap; margin: 0 -0.75rem;">
        <div class="col-lg-6" style="padding: 0 0.75rem; box-sizing: border-box; margin-bottom: 1.5rem;">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-eye me-2"></i>Our Vision</h3>
                </div>
                <div class="content-card-body">
                    <p>We envision a campus community where:</p>
                    <ul class="vision-list">
                        <li class="animate__animated animate__fadeInLeft animate__delay-1s"><span class="vision-icon"><i class="fas fa-users"></i></span> Students are empowered to actively participate in decision-making processes</li>
                        <li class="animate__animated animate__fadeInLeft animate__delay-1s"><span class="vision-icon"><i class="fas fa-globe-americas"></i></span> Diversity is celebrated and all voices are heard and respected</li>
                        <li class="animate__animated animate__fadeInLeft animate__delay-1s"><span class="vision-icon"><i class="fas fa-graduation-cap"></i></span> Academic excellence is supported through student-centered policies</li>
                        <li class="animate__animated animate__fadeInLeft animate__delay-1s"><span class="vision-icon"><i class="fas fa-handshake"></i></span> Collaboration between students, faculty, and administration creates positive change</li>
                        <li class="animate__animated animate__fadeInLeft animate__delay-1s"><span class="vision-icon"><i class="fas fa-heart"></i></span> Student wellbeing is prioritized in all university operations</li>
                    </ul>
                </div>
                </div>
            </div>
            
        <div class="col-lg-6" style="padding: 0 0.75rem; box-sizing: border-box; margin-bottom: 1.5rem;">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <h3 class="content-card-title"><i class="fas fa-compass me-2"></i>Core Values</h3>
                                </div>
                <div class="content-card-body">
                    <div class="core-values">
                        <div class="core-value-item animate__animated animate__fadeInRight animate__delay-1s">
                            <div class="core-value-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="core-value-content">
                                <h4>Integrity</h4>
                                <p>We act with honesty, transparency, and accountability in all our operations.</p>
                            </div>
                        </div>
                        <div class="core-value-item animate__animated animate__fadeInRight animate__delay-2s">
                            <div class="core-value-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <div class="core-value-content">
                                <h4>Inclusivity</h4>
                                <p>We embrace diversity and ensure all students feel welcome and represented.</p>
                            </div>
                        </div>
                        <div class="core-value-item animate__animated animate__fadeInRight animate__delay-3s">
                            <div class="core-value-icon">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <div class="core-value-content">
                                <h4>Service</h4>
                                <p>We are committed to serving the needs of the entire student body.</p>
                            </div>
                        </div>
                        <div class="core-value-item animate__animated animate__fadeInRight animate__delay-4s">
                            <div class="core-value-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <div class="core-value-content">
                                <h4>Excellence</h4>
                                <p>We strive for the highest standards in our advocacy and service delivery.</p>
                            </div>
                        </div>
                        <div class="core-value-item animate__animated animate__fadeInRight animate__delay-5s">
                            <div class="core-value-icon">
                                <i class="fas fa-people-carry"></i>
                            </div>
                            <div class="core-value-content">
                                <h4>Collaboration</h4>
                                <p>We work together with all stakeholders to achieve common goals.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                                        </div>
                                    </div>
                                </div>
                                
<div class="dashboard-section animate-fadeIn" style="animation-delay: 0.2s;" id="structure">
    <div class="content-card">
        <div class="content-card-header">
            <h3 class="content-card-title"><i class="fas fa-sitemap me-2"></i>SRC Structure</h3>
                                        </div>
        <div class="content-card-body">
            <p class="lead mb-4">The SRC consists of elected student representatives who serve in various roles and departments:</p>
            
            <div class="org-chart-container">
                <div class="org-chart">
                    <div class="org-chart-top">
                        <div class="executive org-box">
                            <h4>Executive Portfolios</h4>
                        </div>
                    </div>
                    <div class="org-chart-main-connector">
                        <div class="main-vertical-line"></div>
                        <div class="horizontal-line"></div>
                    </div>
                    <div class="org-chart-all-portfolios">
                        <!-- Executive Portfolios -->
                        <?php if (!empty($executivePortfolios)) { ?>
                            <?php foreach ($executivePortfolios as $portfolio) { ?>
                            <div class="executive-position org-box">
                                <i class="<?php 
                                    switch($portfolio['title']) {
                                        case 'President':
                                            echo 'fas fa-user-tie';
                                            break;
                                        case 'Vice President':
                                            echo 'fas fa-user-shield';
                                            break;
                                        case 'Executive Secretary':
                                            echo 'fas fa-user-edit';
                                            break;
                                        case 'Finance Officer':
                                            echo 'fas fa-dollar-sign';
                                            break;
                                        case 'Senate President':
                                            echo 'fas fa-user-graduate';
                                            break;
                                        default:
                                            echo 'fas fa-user-circle';
                                    }
                                ?>"></i>
                                <h5><?php echo htmlspecialchars($portfolio['title']); ?></h5>
                                <p class="executive-name"><?php echo htmlspecialchars($portfolio['name']); ?></p>
                            </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="executive-position org-box">
                                <i class="fas fa-user-tie"></i>
                                <h5>President</h5>
                                <p class="executive-name">Courage Amedzorneku</p>
                            </div>
                            <div class="executive-position org-box">
                                <i class="fas fa-user-shield"></i>
                                <h5>Vice President</h5>
                                <p class="executive-name">Mariam Adams</p>
                            </div>
                            <div class="executive-position org-box">
                                <i class="fas fa-user-graduate"></i>
                                <h5>Senate President</h5>
                                <p class="executive-name">Bright Kweku Nimo</p>
                            </div>
                            <div class="executive-position org-box">
                                <i class="fas fa-user-edit"></i>
                                <h5>Executive Secretary</h5>
                                <p class="executive-name">Nimafo Olivia</p>
                            </div>
                            <div class="executive-position org-box">
                                <i class="fas fa-dollar-sign"></i>
                                <h5>Finance Officer</h5>
                                <p class="executive-name">Yeboah Bright Peprah</p>
                            </div>
                        <?php } ?>
                        
                        <!-- Departmental Portfolios -->
                        <?php 
                        // Define default departments with their icons and names
                        $defaultDepartments = [
                            'Editor' => ['icon' => 'fas fa-edit', 'name' => 'Owusu Ebenezer'],
                            'Organizing Secretary' => ['icon' => 'fas fa-calendar-alt', 'name' => 'Doku Richard Addo'],
                            'Welfare Officer' => ['icon' => 'fas fa-heart', 'name' => 'Asenso Boamah Mary'],
                            'Women\'s Commissioner' => ['icon' => 'fas fa-female', 'name' => 'Angela Korkoi Sampa'],
                            'Sports Commissioner' => ['icon' => 'fas fa-futbol', 'name' => 'Oppong Elisha'],
                            'Chaplain' => ['icon' => 'fas fa-pray', 'name' => 'Solomon Kofi Boakye'],
                            'Public Relations Officer' => ['icon' => 'fas fa-bullhorn', 'name' => 'Aguda Espoir Ahwiefa Abla']
                        ];
                        
                        // Use actual departments if available, otherwise use defaults
                        if (!empty($departmentPortfolios)) { 
                            $displayedDepartments = [];
                            foreach ($departmentPortfolios as $dept) { 
                                // Skip if we've already displayed this department title
                                if (in_array($dept['title'], $displayedDepartments)) continue;
                                
                                $displayedDepartments[] = $dept['title'];
                                // Find an appropriate icon for this department
                                $icon = 'fas fa-users'; // Default icon
                                foreach ($defaultDepartments as $deptName => $deptInfo) {
                                    if (stripos($dept['title'], str_replace(['&', 'and'], '', $deptName)) !== false) {
                                        $icon = $deptInfo['icon'];
                                        break;
                                    }
                                }
                        ?>
                            <div class="department org-box">
                                <i class="<?php echo $icon; ?>"></i>
                                <h5><?php echo htmlspecialchars($dept['title']); ?></h5>
                                <p class="executive-name"><?php echo htmlspecialchars($dept['name']); ?></p>
                            </div>
                        <?php 
                            } 
                            
                            // If we have fewer than expected departments, fill with default ones
                            if (count($displayedDepartments) < 7) {
                                foreach ($defaultDepartments as $deptName => $deptInfo) {
                                    if (count($displayedDepartments) >= 7) break;
                                    if (!in_array($deptName, $displayedDepartments)) {
                                        $displayedDepartments[] = $deptName;
                        ?>
                            <div class="department org-box">
                                <i class="<?php echo $deptInfo['icon']; ?>"></i>
                                <h5><?php echo $deptName; ?></h5>
                                <p class="executive-name <?php echo (stripos($deptName, 'P.R.O') !== false || stripos($deptName, 'Public Relations') !== false) ? 'pro-name-adjust' : ''; ?>"><?php echo $deptInfo['name']; ?></p>
                            </div>
                        <?php
                                    }
                                }
                            }
                        } else { 
                            // Display default departments if no actual ones are available
                            foreach ($defaultDepartments as $deptName => $deptInfo) {
                        ?>
                            <div class="department org-box">
                                <i class="<?php echo $deptInfo['icon']; ?>"></i>
                                <h5><?php echo $deptName; ?></h5>
                                <p class="executive-name <?php echo (stripos($deptName, 'P.R.O') !== false || stripos($deptName, 'Public Relations') !== false) ? 'pro-name-adjust' : ''; ?>"><?php echo $deptInfo['name']; ?></p>
                            </div>
                        <?php 
                            }
                        } 
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-5">
                <div class="department-details">
                    <h4 class="mb-4">Portfolio Responsibilities</h4>
                    
                    <!-- Executive Portfolios -->
                    <div class="row mb-5">
                        <?php
                        // Define executive descriptions
                        $executiveDescriptions = [
                            'President' => 'The President serves as the head of the SRC, providing leadership, representing students to administration, and overseeing all SRC operations and initiatives.',
                            'Vice President' => 'The Vice President supports the President in leadership duties, coordinates internal operations, and ensures effective implementation of SRC policies and programs.',
                            'Executive Secretary' => 'The Executive Secretary manages all administrative matters, maintains records and documentation, and facilitates communication within the SRC structure.',
                            'Finance Officer' => 'The Finance Officer supervises the collection and disbursement of funds, maintains all financial accounts, and ensures proper financial accountability of the SRC.',
                            'Senate President' => 'The Senate President leads the chief legislative authority of the SRC, empowered to enact laws within Valley View University regulations that serve the best interest of the Council and the Institution.'
                        ];
                        
                        if (!empty($executivePortfolios)) {
                            foreach ($executivePortfolios as $portfolio) {
                                // Get appropriate description
                                $description = $executiveDescriptions[$portfolio['title']] ?? 'Executive committee member responsible for leadership and management duties within the SRC.';
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="department-card animate__animated animate__zoomIn">
                                <div class="department-icon">
                                    <i class="<?php 
                                    switch($portfolio['title']) {
                                        case 'President':
                                            echo 'fas fa-user-tie';
                                            break;
                                        case 'Vice President':
                                            echo 'fas fa-user-shield';
                                            break;
                                        case 'Executive Secretary':
                                            echo 'fas fa-user-edit';
                                            break;
                                        case 'Finance Officer':
                                            echo 'fas fa-dollar-sign';
                                            break;
                                        case 'Senate President':
                                            echo 'fas fa-user-graduate';
                                            break;
                                        default:
                                            echo 'fas fa-user-circle';
                                    }
                                    ?>"></i>
                                </div>
                                <div class="department-content">
                                    <h5><?php echo htmlspecialchars($portfolio['title']); ?></h5>
                                    <p class="executive-name <?php echo (stripos($portfolio['title'], 'P.R.O') !== false || stripos($portfolio['title'], 'Public Relations') !== false) ? 'pro-name-adjust' : ''; ?>"><?php echo htmlspecialchars($portfolio['name']); ?></p>
                                    <p><?php echo htmlspecialchars($description); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                            }
                        }
                        ?>
                    </div>
                    
                    <!-- Departmental Portfolios -->
                    <div class="row">
                        <?php 
                        // Define department descriptions
                        $departmentDescriptions = [
                            'Editor' => 'The Editor manages all SRC media and publications, oversees content creation and distribution, and ensures effective communication with the student body through newsletters, social media, and digital platforms.',
                            'Organizing Secretary' => 'The Organizing Secretary coordinates and manages all SRC events, activities, and programs to ensure successful implementation and student engagement, handling logistics and resource allocation.',
                            'Welfare Officer' => 'The Welfare Officer focuses on student well-being, health, and safety, addressing accommodation issues, mental health support, and general welfare concerns to ensure a positive student experience.',
                            'Women\'s Commissioner' => 'The Women\'s Commissioner advocates for gender equality and women\'s rights on campus, addresses issues affecting female students and creates awareness programs to promote gender equality and women\'s empowerment.',
                            'Sports Commissioner' => 'The Sports Commissioner promotes sports and recreational activities on campus, coordinates sporting events and tournaments, and represents the interests of student athletes while advocating for improved sports facilities.',
                            'Chaplain' => 'The Chaplain provides spiritual guidance and pastoral care to students, organizes religious activities and interfaith dialogues, and offers counseling services to support the spiritual wellbeing of the student community.',
                            'Public Relations Officer' => 'The Public Relations Officer manages the public image of the SRC, handles external communications and media relations, and promotes SRC activities to the broader community while building stakeholder relationships.'
                        ];
                        
                        // Animation delay counter
                        $animationDelay = 1;
                        
                        // Display actual departments if available
                        if (!empty($departmentPortfolios)) {
                            $displayedDeptCards = [];
                            foreach ($departmentPortfolios as $dept) {
                                // Skip if we've already displayed this department title
                                if (in_array($dept['title'], $displayedDeptCards)) continue;
                                
                                $displayedDeptCards[] = $dept['title'];
                                
                                // Find an appropriate icon and description for this department
                                $icon = 'fas fa-users'; // Default icon
                                $description = '';
                                
                                // Try to match with our predefined departments
                                foreach ($departmentDescriptions as $deptName => $deptDesc) {
                                    if (stripos($dept['title'], str_replace(['&', 'and', '\'s', '(', ')'], '', $deptName)) !== false) {
                                        $description = $deptDesc;
                                        break;
                                    }
                                }
                                
                                // Find icon
                                foreach ($defaultDepartments as $deptName => $deptInfo) {
                                    if (stripos($dept['title'], str_replace(['&', 'and', '\'s', '(', ')'], '', $deptName)) !== false) {
                                        $icon = $deptInfo['icon'];
                                        break;
                                    }
                                }
                                
                                // If no matching description found, use a generic one or get from portfolio description
                                if (empty($description) && !empty($dept['description'])) {
                                    $description = $dept['description'];
                                } elseif (empty($description)) {
                                    $description = 'Responsible for managing and coordinating activities related to ' . $dept['title'] . '.';
                                }
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="department-card animate__animated animate__zoomIn animate__delay-<?php echo $animationDelay; ?>s">
                                <div class="department-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="department-content">
                                    <h5><?php echo htmlspecialchars($dept['title']); ?></h5>
                                    <p class="executive-name <?php echo (stripos($dept['title'], 'P.R.O') !== false || stripos($dept['title'], 'Public Relations') !== false) ? 'pro-name-adjust' : ''; ?>"><?php echo htmlspecialchars($dept['name']); ?></p>
                                    <p><?php echo htmlspecialchars($description); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php 
                                $animationDelay++; 
                            }
                            
                            // If we have fewer than 7 departments, fill with default ones
                            if (count($displayedDeptCards) < 7) {
                                foreach ($departmentDescriptions as $deptName => $deptDesc) {
                                    if (count($displayedDeptCards) >= 7) break;
                                    if (!in_array($deptName, $displayedDeptCards)) {
                                        $displayedDeptCards[] = $deptName;
                                        $icon = isset($defaultDepartments[$deptName]['icon']) ? $defaultDepartments[$deptName]['icon'] : 'fas fa-users';
                                        $name = isset($defaultDepartments[$deptName]['name']) ? $defaultDepartments[$deptName]['name'] : '';
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="department-card animate__animated animate__zoomIn animate__delay-<?php echo $animationDelay; ?>s">
                                <div class="department-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="department-content">
                                    <h5><?php echo $deptName; ?></h5>
                                    <p class="executive-name <?php echo (stripos($deptName, 'P.R.O') !== false || stripos($deptName, 'Public Relations') !== false) ? 'pro-name-adjust' : ''; ?>"><?php echo $name; ?></p>
                                    <p><?php echo $deptDesc; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                                        $animationDelay++;
                                    }
                                }
                            }
                        } else {
                            // Display default departments if no actual ones are available
                            $animationDelay = 1;
                            foreach ($departmentDescriptions as $deptName => $deptDesc) {
                                $icon = isset($defaultDepartments[$deptName]['icon']) ? $defaultDepartments[$deptName]['icon'] : 'fas fa-users';
                                $name = isset($defaultDepartments[$deptName]['name']) ? $defaultDepartments[$deptName]['name'] : '';
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="department-card animate__animated animate__zoomIn animate__delay-<?php echo $animationDelay; ?>s">
                                <div class="department-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="department-content">
                                    <h5><?php echo $deptName; ?></h5>
                                    <p class="executive-name <?php echo (stripos($deptName, 'P.R.O') !== false || stripos($deptName, 'Public Relations') !== false) ? 'pro-name-adjust' : ''; ?>"><?php echo $name; ?></p>
                                    <p><?php echo $deptDesc; ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                                $animationDelay++;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="portfolio.php" class="btn btn-primary btn-lg">View All Portfolios</a>
        </div>
        </div>
    </div>
</div>

<!-- Add custom CSS for this page -->
<style>
/* Vision List Styling */
.vision-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.vision-list li {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s;
    background-color: rgba(var(--primary-color-rgb), 0.05);
}

.vision-list li:hover {
    transform: translateX(5px);
    background-color: rgba(var(--primary-color-rgb), 0.1);
}

.vision-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Core Values Styling */
.core-values {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.core-value-item {
    display: flex;
    align-items: center;
    background-color: rgba(var(--primary-color-rgb), 0.05);
    border-radius: 10px;
    padding: 15px;
    transition: all 0.3s;
}

.core-value-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
    background-color: rgba(var(--primary-color-rgb), 0.1);
}

.core-value-icon {
    width: 50px;
    height: 50px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 15px;
    flex-shrink: 0;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.core-value-content h4 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.core-value-content p {
    margin: 0;
    color: #666;
}

/* Mission List Styling */
.mission-list .list-group-item {
    border: none;
    padding: 12px 0;
    background-color: transparent;
    transition: all 0.3s;
}

.mission-list .list-group-item:hover {
    background-color: rgba(var(--primary-color-rgb), 0.05);
    padding-left: 10px;
    border-radius: 8px;
}

/* Organization Chart Styling */
.org-chart-container {
    margin: 40px 0;
    overflow-x: auto;
    overflow-y: visible;
    min-height: 500px;
}

.org-chart {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
    position: relative;
    padding: 20px 0;
    min-height: 450px;
    overflow: visible;
}

.org-box {
    padding: 15px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin: 10px;
    transition: all 0.3s;
    border: 2px solid rgba(var(--primary-color-rgb), 0.2);
    position: relative;
    z-index: 10;
    overflow: visible;
}

.org-box:hover {
    transform: translateY(-5px) scale(1.03);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: var(--primary-color);
}

.org-box h4 {
    margin: 0;
    color: var(--primary-color);
    font-weight: 600;
}

.org-box h5 {
    margin: 5px 0;
    font-weight: 600;
}

.org-box .executive-name {
    font-weight: 600;
    color: var(--primary-color);
    margin: 3px 0;
    font-size: 0.9rem;
}

/* Adjust P.R.O name position */
.org-box:has(i.fa-bullhorn) .executive-name {
    position: relative;
    top: -3px;
}

.department-content:has(+ .department-icon i.fa-bullhorn) .executive-name,
.department-card:has(.department-icon i.fa-bullhorn) .executive-name {
    position: relative;
    top: -3px;
}

/* Alternative selectors for broader browser compatibility */
.pro-name-adjust {
    position: relative;
    top: -3px;
}

.org-box p {
    margin: 0;
    font-size: 0.85rem;
    color: #666;
}

.org-box i {
    font-size: 1.5rem;
    color: var(--primary-color);
    margin-bottom: 8px;
    background-color: rgba(var(--primary-color-rgb), 0.1);
    width: 50px;
    height: 50px;
    line-height: 50px;
    border-radius: 50%;
    display: inline-block;
    transition: all 0.3s;
}

/* Specific styling for bullhorn icon */
.org-box i.fa-bullhorn {
    line-height: 50px;
    text-align: center;
    vertical-align: middle;
    padding: 0;
    position: relative;
    top: 5px;
}

/* Additional styling for bullhorn in department icons */
.department-icon i.fa-bullhorn {
    position: relative;
    top: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}

.org-box:hover i {
    transform: rotate(360deg);
    background-color: var(--primary-color);
    color: white;
}

.org-chart-top {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;
}

.org-chart-all-portfolios {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
    gap: 20px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px 40px 20px;
    min-height: 500px;
}

.executive-position, .department {
    width: 200px;
    height: 180px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 10;
    margin: 20px 15px;
    flex-shrink: 0;
}

.org-chart-main-connector {
    position: relative;
    height: 70px;
    margin-bottom: 10px;
}

.main-vertical-line {
    width: 2px;
    height: 30px;
    background-color: var(--primary-color);
    margin: 0 auto;
}

.horizontal-line {
    width: 90%;
    height: 2px;
    background-color: var(--primary-color);
    margin: 0 auto;
    position: absolute;
    bottom: 0;
    left: 5%;
}

.org-chart::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle, rgba(var(--primary-color-rgb), 0.05) 0%, rgba(var(--primary-color-rgb), 0) 70%);
    z-index: 0;
    pointer-events: none;
}

/* Add individual connectors from the horizontal line to each portfolio box */
.org-chart-all-portfolios .executive-position::before,
.org-chart-all-portfolios .department::before {
    content: '';
    position: absolute;
    width: 2px;
    height: 30px;
    background-color: var(--primary-color);
    top: -40px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1;
}

/* Department Cards Styling */
.department-card {
    display: flex;
    align-items: center;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    padding: 20px;
    height: 100%;
    transition: all 0.3s;
}

.department-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.department-icon {
    width: 60px;
    height: 60px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 20px;
    flex-shrink: 0;
}

/* Fix for bullhorn icon alignment */
.fas.fa-bullhorn {
    position: relative;
    display: inline-block;
    line-height: normal;
}

.department-content h5 {
    margin: 0 0 10px 0;
    font-weight: 600;
}

.department-content p {
    margin: 0;
    color: #666;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInRight {
    from { transform: translateX(50px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.dashboard-section {
    opacity: 1;
}

.core-value-item, .vision-list li, .department-card {
    opacity: 1;
}

.core-value-item:nth-child(1) { animation-delay: 0.1s; }
.core-value-item:nth-child(2) { animation-delay: 0.2s; }
.core-value-item:nth-child(3) { animation-delay: 0.3s; }
.core-value-item:nth-child(4) { animation-delay: 0.4s; }
.core-value-item:nth-child(5) { animation-delay: 0.5s; }

.vision-list li:nth-child(1) { animation-delay: 0.1s; }
.vision-list li:nth-child(2) { animation-delay: 0.2s; }
.vision-list li:nth-child(3) { animation-delay: 0.3s; }
.vision-list li:nth-child(4) { animation-delay: 0.4s; }
.vision-list li:nth-child(5) { animation-delay: 0.5s; }

.department-card:nth-child(1) { animation-delay: 0.1s; }
.department-card:nth-child(2) { animation-delay: 0.2s; }
.department-card:nth-child(3) { animation-delay: 0.3s; }
.department-card:nth-child(4) { animation-delay: 0.4s; }
.department-card:nth-child(5) { animation-delay: 0.5s; }
.department-card:nth-child(6) { animation-delay: 0.6s; }

/* Media Queries for Responsiveness */
@media (max-width: 992px) {
    .org-chart-all-portfolios {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .horizontal-line {
        width: 100%;
        left: 0;
    }
    
    .core-value-item, .department-card {
        flex-direction: column;
        text-align: center;
    }
    
    .core-value-icon, .department-icon {
        margin: 0 0 15px 0;
    }
    
    .executive-position, .department {
        width: 45%;
        margin: 10px auto;
    }
}

@media (max-width: 768px) {
    .executive-position, .department {
        width: 90%;
    }
    
    .vision-list li {
        flex-direction: column;
        text-align: center;
    }
    
    .vision-icon {
        margin: 0 0 10px 0;
    }
    
    .header-actions {
        display: none;
    }
    
    .core-value-item, .department-card {
        padding: 15px 10px;
    }
    
    .org-chart-container {
        overflow-x: hidden;
    }
    
    .org-box {
        width: 90%;
        margin: 5px auto;
    }
}

/* Dark Theme Support */
[data-bs-theme="dark"] .org-box {
    background-color: #2b2b2b;
}

[data-bs-theme="dark"] .department-card {
    background-color: #2b2b2b;
}

[data-bs-theme="dark"] .core-value-content p, 
[data-bs-theme="dark"] .department-content p {
    color: #aaa;
}

/* Team Member Styling */
.team-member {
    text-align: center;
    transition: all 0.3s;
    padding: 20px;
    border-radius: 10px;
    background-color: rgba(var(--primary-color-rgb), 0.02);
}

.team-member:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    background-color: rgba(var(--primary-color-rgb), 0.05);
}

.team-member-photo {
    width: 120px;
    height: 120px;
    margin: 0 auto 15px;
    position: relative;
    overflow: hidden;
    border-radius: 50%;
    border: 3px solid var(--primary-color);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.team-member-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.5s;
}

.team-member:hover .team-member-photo img {
    transform: scale(1.1);
}

.team-member-info h5 {
    margin-bottom: 5px;
    font-weight: 600;
}

.team-member-info .position {
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 10px;
}

/* Mobile navbar size increase and spacing adjustments for about page */
@media (max-width: 768px) {
    .about-page .navbar {
        height: 70px !important;
        padding: 0.75rem 1rem !important;
    }
    
    .about-page .navbar .navbar-brand {
        font-size: 1.3rem !important;
    }
    
    .about-page .navbar .system-icon {
        width: 35px !important;
        height: 35px !important;
    }
    
    .about-page .navbar .btn {
        font-size: 1.1rem !important;
        padding: 0.5rem 0.75rem !important;
    }
    
    .about-page .navbar .site-name {
        font-size: 1.1rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .about-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .about-page .header {
        margin-top: 10px !important; /* 70px navbar + 30px spacing */
    }
}

@media (max-width: 480px) {
    .about-page .navbar {
        height: 65px !important;
        padding: 0.6rem 0.8rem !important;
    }
    
    .about-page .navbar .navbar-brand {
        font-size: 1.2rem !important;
    }
    
    .about-page .navbar .system-icon {
        width: 32px !important;
        height: 32px !important;
    }
    
    .about-page .navbar .btn {
        font-size: 1rem !important;
        padding: 0.4rem 0.6rem !important;
    }
    
    .about-page .navbar .site-name {
        font-size: 1rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .about-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .about-page .header {
        margin-top: 15px !important; /* 65px navbar + 30px spacing */
    }
}

@media (max-width: 375px) {
    .about-page .navbar {
        height: 60px !important;
        padding: 0.5rem 0.7rem !important;
    }
    
    .about-page .navbar .navbar-brand {
        font-size: 1.1rem !important;
    }
    
    .about-page .navbar .system-icon {
        width: 30px !important;
        height: 30px !important;
    }
    
    .about-page .navbar .btn {
        font-size: 0.95rem !important;
        padding: 0.35rem 0.5rem !important;
    }
    
    .about-page .navbar .site-name {
        font-size: 0.95rem !important;
    }
    
    /* Remove main-content padding-top to prevent double spacing */
    .about-page .main-content {
        padding-top: 0 !important;
    }
    
    /* Adjust margin between navbar and page header to 30px */
    .about-page .header {
        margin-top: 90px !important; /* 60px navbar + 30px spacing */
    }
}

/* Mobile Full-Width Optimization for About Page */
@media (max-width: 991px) {
    [class*="col-md-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Remove container padding on mobile for full width */
    .container-fluid, .container {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    
    /* Ensure about header has border-radius on mobile */
    .header, .about-header {
        border-radius: 12px !important;
    }
    
    /* Ensure content cards and sections extend full width */
    .card, .section, .department-card {
        margin-left: 0 !important;
        margin-right: 0 !important;
        border-radius: 0 !important;
    }
}
</style>

<!-- Add custom JS for smooth scrolling and animations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Animation for elements when they come into view
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.core-value-item, .vision-list li, .department-card, .org-box');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 50) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Run on load
    animateOnScroll();
    
    // Run on scroll
    window.addEventListener('scroll', animateOnScroll);
});
</script>

<?php require_once 'includes/footer.php'; ?> 
