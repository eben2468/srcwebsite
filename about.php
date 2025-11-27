<?php
// Include simple authentication
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Check if user is logged in (optional for public page)
$isLoggedIn = isLoggedIn();

// Fetch executive portfolios from database
$executivePortfolios = [];
$departmentPortfolios = [];

try {
    // Fetch all portfolios for executive team
    $portfolioQuery = "SELECT * FROM portfolios ORDER BY FIELD(title, 'Senate President', 'Finance Officer', 'Executive Secretary', 'Vice President', 'President') DESC, title ASC";
    $executiveResult = mysqli_query($conn, $portfolioQuery);
    if ($executiveResult) {
        while ($row = mysqli_fetch_assoc($executiveResult)) {
            $executivePortfolios[] = $row;
        }
    }
} catch (Exception $e) {
    // Silent error handling
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Valley View University SRC</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #1a5490;
            --secondary-color: #e67e22;
            --accent-color: #27ae60;
            --dark-blue: #0d3b66;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --gold: #f39c12;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Top Bar */
        .top-bar {
            background: var(--dark-blue);
            color: var(--white);
            padding: 10px 0;
            font-size: 1.1rem;
        }

        .top-bar a {
            color: var(--white);
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.3s;
        }

        .top-bar a:hover {
            color: var(--secondary-color);
        }

        .top-bar i {
            margin-right: 5px;
        }

        /* Container adjustments for less side spacing */
        .container {
            max-width: 1400px;
            padding-left: 20px;
            padding-right: 20px;
        }

        @media (min-width: 1400px) {
            .container {
                max-width: 95%;
            }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            .top-bar {
                font-size: 0.85rem;
                padding: 8px 0;
            }

            .top-bar .d-flex {
                flex-wrap: wrap;
            }

            .top-bar span {
                font-size: 0.85rem;
            }

            .navbar-brand {
                font-size: 1.1rem;
            }

            .navbar-brand img {
                height: 35px;
            }

            .nav-link {
                font-size: 1.1rem;
                margin: 5px 0;
            }
        }

        /* Navigation */
        .navbar {
            background: var(--white);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            height: 50px;
            margin-right: 15px;
        }

        .nav-link {
            color: var(--text-dark) !important;
            font-weight: 500;
            font-size: 1.3rem;
            margin: 0 15px;
            transition: color 0.3s;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s;
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }

        .btn-login {
            background: var(--primary-color);
            color: var(--white);
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid var(--primary-color);
        }

        .btn-login:hover {
            background: transparent;
            color: var(--primary-color);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
            color: var(--white);
            padding: 100px 0 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            position: relative;
            z-index: 10;
        }

        .page-header p {
            font-size: 1.3rem;
            opacity: 0.9;
            position: relative;
            z-index: 10;
        }

        .breadcrumb {
            background: transparent;
            margin-bottom: 0;
            justify-content: center;
        }

        .breadcrumb-item a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--white);
        }

        /* Content Section */
        .content-section {
            padding: 80px 0;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--secondary-color);
        }

        .mission-vision-card {
            background: var(--white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            height: 100%;
            transition: all 0.3s;
        }

        .mission-vision-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .mission-vision-card .icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .mission-vision-card h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .mission-vision-card ul {
            list-style: none;
            padding: 0;
        }

        .mission-vision-card ul li {
            padding: 10px 0;
            border-bottom: 1px solid var(--light-bg);
            display: flex;
            align-items: flex-start;
        }

        .mission-vision-card ul li:last-child {
            border-bottom: none;
        }

        .mission-vision-card ul li i {
            color: var(--accent-color);
            margin-right: 15px;
            margin-top: 5px;
        }

        /* Portfolio Section */
        .portfolio-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            margin-bottom: 30px;
        }

        .portfolio-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .portfolio-header {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
            color: var(--white);
            padding: 30px;
            text-align: center;
        }

        .portfolio-header .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .portfolio-header h4 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .portfolio-body {
            padding: 30px;
        }

        .portfolio-member {
            margin-bottom: 20px;
        }

        .portfolio-member h5 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .portfolio-member p {
            color: var(--text-light);
            margin: 0;
        }

        .portfolio-member .contact-info {
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .portfolio-member .contact-info i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
            color: var(--white);
            padding: 80px 0;
        }

        .stat-box {
            text-align: center;
            padding: 30px;
        }

        .stat-box i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .stat-box h3 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-box p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: var(--dark-blue);
            color: var(--white);
            padding: 60px 0 30px;
        }

        .footer h5 {
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 1.3rem;
        }

        .footer ul {
            list-style: none;
            padding: 0;
        }

        .footer ul li {
            margin-bottom: 12px;
        }

        .footer ul li a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }

        .footer ul li a:hover {
            color: var(--secondary-color);
            padding-left: 5px;
        }

        .footer ul li a i {
            margin-right: 8px;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: var(--white);
            margin-right: 10px;
            transition: all 0.3s;
            font-size: 1.2rem;
        }

        .social-links a:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 40px;
            padding-top: 30px;
            text-align: center;
        }

        .footer-bottom p {
            margin: 0;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .stat-box h3 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span><i class="fas fa-phone"></i> +233 54 881 1774</span>&nbsp &nbsp
                        <span><i class="fas fa-envelope"></i> officialsrcvvu@gmail.com</span>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <?php if ($isLoggedIn): ?>
                        <a href="pages_php/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <a href="pages_php/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php else: ?>
                        <a href="pages_php/login.php"><i class="fas fa-sign-in-alt"></i> Student Portal</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="IMG_0375[1].jpg" alt="VVU Logo">
                <span>VALLEY VIEW UNIVERSITY SRC</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="events.php">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (!$isLoggedIn): ?>
                        <li class="nav-item ms-3">
                            <a href="pages_php/login.php" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1 data-aos="fade-up">About VVU SRC</h1>
            <p data-aos="fade-up" data-aos-delay="100">Learn about our mission, vision, and organizational structure</p>
            <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="200">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">About</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="content-section bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="mission-vision-card">
                        <div class="icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Our Mission</h3>
                        <p class="mb-4">The Student Representative Council (SRC) serves as the official voice of the student body, advocating for student interests and working collaboratively with university administration to enhance the academic and social experience for all students.</p>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> <span>Represent and protect the rights and interests of all students</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Provide essential services and support to enhance student welfare</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Organize social, cultural, and educational events that enrich campus life</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Foster a diverse and inclusive community where all students feel valued</span></li>
                            <li><i class="fas fa-check-circle"></i> <span>Facilitate effective communication between students and university administration</span></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="mission-vision-card">
                        <div class="icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Our Vision</h3>
                        <p class="mb-4">We envision a campus community where students are empowered to actively participate in decision-making processes and where diversity is celebrated.</p>
                        <ul>
                            <li><i class="fas fa-star"></i> <span>Students are empowered to actively participate in decision-making processes</span></li>
                            <li><i class="fas fa-star"></i> <span>Diversity is celebrated and all voices are heard and respected</span></li>
                            <li><i class="fas fa-star"></i> <span>Innovation and creativity are encouraged in all student initiatives</span></li>
                            <li><i class="fas fa-star"></i> <span>Strong partnerships exist between students, faculty, and administration</span></li>
                            <li><i class="fas fa-star"></i> <span>Students graduate as well-rounded individuals ready to make positive impacts</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-box">
                        <i class="fas fa-users"></i>
                        <h3>1500+</h3>
                        <p>Active Students</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-box">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>20+</h3>
                        <p>Events Annually</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-box">
                        <i class="fas fa-award"></i>
                        <h3>10+</h3>
                        <p>SRC Portfolios</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-box">
                        <i class="fas fa-hands-helping"></i>
                        <h3>50+</h3>
                        <p>Projects Completed</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Executive Portfolios Section -->
    <section class="content-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title" data-aos="fade-up">Executive Team</h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="100">Meet the leaders of Valley View University SRC</p>
            </div>
            <div class="row">
                <?php if (!empty($executivePortfolios)): ?>
                    <?php foreach ($executivePortfolios as $index => $portfolio): ?>
                        <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                            <div class="portfolio-card">
                                <div class="portfolio-header">
                                    <div class="icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <h4><?php echo htmlspecialchars($portfolio['title']); ?></h4>
                                </div>
                                <div class="portfolio-body">
                                    <div class="portfolio-member">
                                        <h5><?php echo htmlspecialchars($portfolio['name'] ?? 'TBA'); ?></h5>
                                        <?php if (!empty($portfolio['email']) || !empty($portfolio['phone'])): ?>
                                            <div class="contact-info">
                                                <?php if (!empty($portfolio['email'])): ?>
                                                    <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($portfolio['email']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($portfolio['phone'])): ?>
                                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($portfolio['phone']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>Executive portfolio information will be available soon.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <h5><i class="fas fa-university me-2"></i>VVU SRC</h5>
                    <p style="color: rgba(255,255,255,0.8); line-height: 1.8;">
                        The official Students' Representative Council of Valley View University, dedicated to serving the student body with excellence and integrity.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="news.php"><i class="fas fa-chevron-right"></i> News</a></li>
                        <li><a href="events.php"><i class="fas fa-chevron-right"></i> Events</a></li>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Portal</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5>Services</h5>
                    <ul>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Student Welfare</a></li>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Events Management</a></li>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Scholarships</a></li>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Feedback System</a></li>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5>Contact Info</h5>
                    <ul>
                        <li style="display: flex; align-items: start;">
                            <i class="fas fa-map-marker-alt" style="margin-top: 5px;"></i>
                            <span>Valley View University<br>Oyibi, Accra, Ghana</span>
                        </li>
                        <li><i class="fas fa-phone"></i> +233 123 456 789</li>
                        <li><i class="fas fa-envelope"></i> officialsrcvvu@gmail.com</li>
                        <li><i class="fas fa-clock"></i> Mon - Thur: 9AM - 5PM <br> &nbsp &nbsp Fri: 9:00 AM - 1:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025/2026 Valley View University SRC. All Rights Reserved. | Designed by Ebenezer Owusu, SRC Editor for 2025/26 SRC Administration <i class="fas fa-heart" style="color: var(--secondary-color);"></i></p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>
