<?php
// Simple index file to redirect after department system deletion
// Check if user is logged in
require_once 'auth_functions.php';
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SRC Management System</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/index-styles.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0061f2;
            --secondary-color: #6900c7;
            --accent-color: #00cfd5;
            --text-color: #1f2d3d;
            --light-color: #f8f9fa;
            --dark-color: #212832;
        }
        
        /* Absolute positioned button */
        .absolute-button {
            position: absolute;
            bottom: 50px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            padding: 15px 40px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            background: white;
            color: var(--primary-color);
            border: none;
            font-weight: 600;
            border-radius: 40px;
        }
        
        .absolute-button:hover {
            transform: translateX(-50%) translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            background: white;
            color: var(--secondary-color);
        }
        
        /* Preloader styles */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #ffffff;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .loader {
            position: relative;
            text-align: center;
        }
        
        .circular {
            animation: rotate 2s linear infinite;
            height: 50px;
            width: 50px;
            position: relative;
        }
        
        .path {
            stroke: var(--primary-color);
            stroke-dasharray: 90, 150;
            stroke-dashoffset: 0;
            stroke-linecap: round;
            animation: dash 1.5s ease-in-out infinite;
        }
        
        .loading-text {
            margin-top: 15px;
            font-weight: 500;
            color: var(--primary-color);
            letter-spacing: 1px;
        }
        
        @keyframes rotate {
            100% {
                transform: rotate(360deg);
            }
        }
        
        @keyframes dash {
            0% {
                stroke-dasharray: 1, 150;
                stroke-dashoffset: 0;
            }
            50% {
                stroke-dasharray: 90, 150;
                stroke-dashoffset: -35;
            }
            100% {
                stroke-dasharray: 90, 150;
                stroke-dashoffset: -124;
            }
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-color);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 120px 0 100px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB2aWV3Qm94PSIwIDAgMTI4MCAxNDAiIHByZXNlcnZlQXNwZWN0UmF0aW89Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGcgZmlsbD0iI2ZmZmZmZiI+PHBhdGggZD0iTTAgNTEuNzZjMzYuMjEtMi4yNSA3Ny41Ny0zLjU4IDEyNi40Mi0zLjU4IDMyMCAwIDMyMCA1NyA2NDAgNTcgMjcxLjE1IDAgMzEyLjU4LTQwLjkxIDUxMy41OC01Ny40OFYxNDBIMFY1MS43NnoiIGZpbGwtb3BhY2l0eT0iLjMiLz48cGF0aCBkPSJNMCA5MC43MmMxNzEtMTcuNTUgMzQwLjkxLTIxLjQ2IDUwMC4wMy0yMS40NiAyNzguNzQgMCAzOTcuODcgMTQuMzMgNzc5Ljk3IDE0LjMzIDI0Ljc1IDAgNDguMzgtLjk0IDAtMS43Mi0zNS4zLTIuMjctNzAuNTktMy41LTEwNS45OC0zLjVDNjM5LjU1IDc4LjM3IDUxMi44MSA1NiAzMjQgNTZjLTEwMS4xNSAwLTE5Mi43NiA4LjU3LTMyNCAxNi43MnY3NHoiIGZpbGwtb3BhY2l0eT0iLjUiLz48cGF0aCBkPSJNMCAxNDBWOTkuNzdjMTMzLjIxIDEuNDggMjQ0LjQ2IDE1LjY3IDM1MC45MSAzMS4xNkM2MTQuNTUgMTQwIDc2NC45MSAxNDAgMTI4MCAxNDB6Ii8+PC9nPjwvc3ZnPg==') center bottom/100% 100px no-repeat;
            opacity: 0.1;
        }
        
        .hero-title, .university-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            display: block;
            width: 100%;
            text-align: center;
            overflow: visible;
            line-height: 1.2;
            color: white;
            letter-spacing: normal;
            word-spacing: 0.2em;
        }
        
        .university-title {
            margin-bottom: 0.5rem;
        }
        
        .university-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: rgba(255, 255, 255, 0.7);
            margin: 5px auto 10px;
            border-radius: 2px;
        }
        
        /* Cursor animation for typing effect */
        .university-title.typing-active::before,
        .hero-title.typing-active::after {
            content: '|';
            display: inline-block;
            margin-left: 2px;
            animation: blink-cursor 0.7s step-end infinite;
            vertical-align: text-bottom;
        }
        
        @keyframes blink-cursor {
            from, to { opacity: 0; }
            50% { opacity: 1; }
        }
        
        .hero-subtitle {
            font-size: 1.6rem;
            font-weight: 300;
            margin-bottom: 2.5rem;
            opacity: 0.9;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .title-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            overflow: visible;
        }
        
        .features-section {
            padding: 80px 0;
            background-color: white;
            position: relative;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            margin: 15px auto 0;
            border-radius: 2px;
        }
        
        .feature-card {
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            height: 100%;
            transition: all 0.4s ease;
            background-color: white;
            border-bottom: 4px solid transparent;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 0;
            background: linear-gradient(135deg, rgba(0,97,242,0.03), rgba(105,0,199,0.03));
            z-index: -1;
            transition: height 0.4s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            border-bottom: 4px solid var(--primary-color);
        }
        
        .feature-card:hover::before {
            height: 100%;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 25px;
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: transform 0.4s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .feature-card p {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, rgba(0,97,242,0.05), rgba(105,0,199,0.05));
            text-align: center;
            position: relative;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .btn-primary {
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 5px 15px rgba(0,97,242,0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,97,242,0.5);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        .btn-light {
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            background: white;
            color: var(--primary-color);
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-light:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: white;
            color: var(--secondary-color);
        }
        
        .btn-outline-light {
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 50px 0 30px;
            margin-top: auto;
        }
        
        .footer p {
            opacity: 0.8;
        }
        
        .nav-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 100;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            opacity: 0.2;
            border-radius: 50%;
        }
        
        .shape-1 {
            width: 150px;
            height: 150px;
            background: var(--accent-color);
            top: 20%;
            left: 10%;
            animation: float 8s ease-in-out infinite;
        }
        
        .shape-2 {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            top: 60%;
            left: 20%;
            animation: float 9s ease-in-out infinite 1s;
        }
        
        .shape-3 {
            width: 120px;
            height: 120px;
            background: var(--secondary-color);
            top: 30%;
            right: 15%;
            animation: float 7s ease-in-out infinite 2s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }
        
        @media (max-width: 991px) {
            .university-title, .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.4rem;
            }
            
            .section-title {
                margin-bottom: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 100px 0 80px;
            }
            
            .university-title, .hero-title {
                font-size: 2.3rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .nav-buttons {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 20px;
                display: flex;
                justify-content: center;
            }
            
            .features-section,
            .cta-section {
                padding: 60px 0;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }
        }
        
        /* Button enhancements */
        .btn-light, .btn-primary {
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-light {
            background: white;
            color: var(--primary-color);
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-light:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: white;
            color: var(--secondary-color);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: 0 5px 15px rgba(0,97,242,0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,97,242,0.5);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        .btn-light:active, .btn-primary:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="loader">
            <svg class="circular" viewBox="25 25 50 50">
                <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="3" stroke-miterlimit="10" />
            </svg>
            <p class="loading-text">Loading...</p>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="nav-buttons">
        <?php if ($isLoggedIn): ?>
            <a href="pages_php/dashboard.php" class="btn btn-light me-2">
                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
            </a>
            <a href="pages_php/logout.php" class="btn btn-outline-light">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        <?php else: ?>
            <a href="pages_php/register.php" class="btn btn-light me-2">
                <i class="fas fa-user-plus me-1"></i> Sign Up / Login
            </a>
        <?php endif; ?>
    </div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container" data-aos="fade-up" data-aos-duration="1000">
            <div class="title-container">
                <h1 class="university-title">Valley View University</h1>
                <h1 class="hero-title">Students' Representative Council</h1>
            </div>
            <p class="hero-subtitle">Management System</p>
        </div>
        
        <!-- Main Get Started button (absolute positioned) -->
        <?php if (!$isLoggedIn): ?>
            <a href="pages_php/register.php" class="btn absolute-button">
                <i class="fas fa-user-plus me-1"></i> Get Started
            </a>
        <?php else: ?>
            <a href="pages_php/dashboard.php" class="btn absolute-button">
                <i class="fas fa-tachometer-alt me-1"></i> Go to Dashboard
            </a>
        <?php endif; ?>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title gradient-text" data-aos="fade-up">Our Features</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="hover-overlay"></div>
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Portfolio Management</h3>
                        <p>Manage SRC portfolios and officers with detailed profiles and responsibilities.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="hover-overlay"></div>
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Event Planning</h3>
                        <p>Organize and track SRC events, meetings, and activities with our comprehensive calendar system.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="hover-overlay"></div>
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3>Document Management</h3>
                        <p>Store and access important documents, minutes, and reports in a secure central location.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="mb-4 gradient-text" data-aos="fade-up">Let's Get Started</h2>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> SRC Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <!-- Custom animations -->
    <script src="js/index-animations.js"></script>
    <script>
        // Initialize AOS animation
        document.addEventListener('DOMContentLoaded', function() {
            // Hide preloader once page is loaded
            const preloader = document.getElementById('preloader');
            if (preloader) {
                setTimeout(() => {
                    preloader.style.opacity = '0';
                    preloader.style.visibility = 'hidden';
                }, 800);
            }
            
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
    </script>
</body>
</html> 