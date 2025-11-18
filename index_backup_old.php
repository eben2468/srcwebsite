<?php
// Include simple authentication
require_once __DIR__ . '/includes/simple_auth.php';
// Check if user is logged in
$isLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Valley View University - Students' Representative Council</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Swiper CSS for carousel -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

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

        /* Preloader */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--white);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease;
        }

        .preloader-content {
            text-align: center;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid var(--light-bg);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Top Bar */
        .top-bar {
            background: var(--dark-blue);
            color: var(--white);
            padding: 10px 0;
            font-size: 0.9rem;
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

        /* Navigation */
        .navbar {
            background: var(--white);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
            transition: all 0.3s;
        }

        .navbar.scrolled {
            padding: 10px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
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
            margin: 0 15px;
            transition: color 0.3s;
            position: relative;
        }

        .nav-link:hover {
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

        .nav-link:hover::after {
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

        /* Hero Slider */
        .hero-slider {
            position: relative;
            height: 650px;
            overflow: hidden;
        }

        .swiper {
            width: 100%;
            height: 100%;
        }

        .swiper-slide {
            position: relative;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .swiper-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(26, 84, 144, 0.85), rgba(13, 59, 102, 0.75));
        }

        .slide-content {
            position: relative;
            z-index: 10;
            color: var(--white);
            text-align: center;
            max-width: 900px;
            padding: 20px;
        }

        .slide-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .slide-content p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .slide-content .btn {
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            margin: 0 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary-custom {
            background: var(--secondary-color);
            border: 2px solid var(--secondary-color);
            color: var(--white);
        }

        .btn-primary-custom:hover {
            background: transparent;
            border-color: var(--white);
            color: var(--white);
        }

        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
        }

        .btn-outline-custom:hover {
            background: var(--white);
            color: var(--primary-color);
        }

        /* Quick Links */
        .quick-links {
            background: var(--white);
            margin-top: -50px;
            position: relative;
            z-index: 100;
        }

        .quick-link-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.3s;
            text-align: center;
            border-bottom: 4px solid transparent;
        }

        .quick-link-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }

        .quick-link-card.card-1:hover { border-bottom-color: var(--primary-color); }
        .quick-link-card.card-2:hover { border-bottom-color: var(--secondary-color); }
        .quick-link-card.card-3:hover { border-bottom-color: var(--accent-color); }
        .quick-link-card.card-4:hover { border-bottom-color: var(--gold); }

        .quick-link-card i {
            font-size: 3rem;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }

        .quick-link-card:hover i {
            transform: scale(1.1);
        }

        .quick-link-card.card-1 i { color: var(--primary-color); }
        .quick-link-card.card-2 i { color: var(--secondary-color); }
        .quick-link-card.card-3 i { color: var(--accent-color); }
        .quick-link-card.card-4 i { color: var(--gold); }

        .quick-link-card h4 {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .quick-link-card p {
            color: var(--text-light);
            margin: 0;
            font-size: 0.95rem;
        }

        /* Statistics Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
            color: var(--white);
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .stats-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 350px;
            height: 350px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .stat-item {
            text-align: center;
            position: relative;
            z-index: 10;
        }

        .stat-item i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* About Section */
        .about-section {
            padding: 100px 0;
            background: var(--light-bg);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 15px;
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

        .section-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 40px;
        }

        .about-content {
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--text-dark);
        }

        .about-image {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }

        .about-image img {
            width: 100%;
            height: auto;
            transition: transform 0.5s;
        }

        .about-image:hover img {
            transform: scale(1.05);
        }

        /* News Section */
        .news-section {
            padding: 100px 0;
            background: var(--white);
        }

        .news-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }

        .news-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .news-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .news-card:hover .news-image img {
            transform: scale(1.1);
        }

        .news-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--secondary-color);
            color: var(--white);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .news-content {
            padding: 25px;
        }

        .news-meta {
            display: flex;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .news-meta i {
            margin-right: 5px;
        }

        .news-meta span {
            margin-right: 15px;
        }

        .news-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
            line-height: 1.4;
        }

        .news-excerpt {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .read-more {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }

        .read-more:hover {
            color: var(--secondary-color);
        }

        .read-more i {
            margin-left: 5px;
            transition: transform 0.3s;
        }

        .read-more:hover i {
            transform: translateX(5px);
        }

        /* Events Section */
        .events-section {
            padding: 100px 0;
            background: var(--light-bg);
        }

        .event-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            display: flex;
            margin-bottom: 30px;
        }

        .event-card:hover {
            transform: translateX(10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .event-date {
            background: var(--primary-color);
            color: var(--white);
            padding: 30px;
            text-align: center;
            min-width: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .event-day {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .event-month {
            font-size: 1.2rem;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .event-details {
            padding: 30px;
            flex: 1;
        }

        .event-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .event-info {
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .event-info i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--secondary-color), var(--gold));
            padding: 80px 0;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .cta-content {
            position: relative;
            z-index: 10;
        }

        .cta-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
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

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: var(--secondary-color);
            transform: translateY(-5px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .slide-content h1 {
                font-size: 2rem;
            }

            .slide-content p {
                font-size: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .event-card {
                flex-direction: column;
            }

            .event-date {
                min-width: 100%;
                padding: 20px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .cta-title {
                font-size: 2rem;
            }

            .hero-slider {
                height: 500px;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="preloader-content">
            <div class="spinner"></div>
            <p style="color: var(--primary-color); font-weight: 600;">Loading...</p>
        </div>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <span><i class="fas fa-phone"></i> +233 123 456 789</span>
                        <span><i class="fas fa-envelope"></i> src@vvu.edu.gh</span>
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
                <i class="fas fa-university"></i>
                <span>VVU SRC</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#news">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="#events">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
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

    <!-- Hero Slider -->
    <section class="hero-slider" id="home">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                <!-- Slide 1 -->
                <div class="swiper-slide" style="background-image: linear-gradient(rgba(26, 84, 144, 0.7), rgba(13, 59, 102, 0.8)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1920');">
                    <div class="slide-content" data-aos="fade-up">
                        <h1>Valley View University</h1>
                        <p>Students' Representative Council</p>
                        <div class="mt-4">
                            <?php if ($isLoggedIn): ?>
                                <a href="pages_php/dashboard.php" class="btn btn-primary-custom">
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                </a>
                            <?php else: ?>
                                <a href="pages_php/login.php" class="btn btn-primary-custom">
                                    <i class="fas fa-sign-in-alt me-2"></i>Student Login
                                </a>
                            <?php endif; ?>
                            <a href="#about" class="btn btn-outline-custom">
                                <i class="fas fa-info-circle me-2"></i>Learn More
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="swiper-slide" style="background-image: linear-gradient(rgba(26, 84, 144, 0.7), rgba(13, 59, 102, 0.8)), url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1920');">
                    <div class="slide-content" data-aos="fade-up">
                        <h1>Your Voice Matters</h1>
                        <p>Empowering Students Through Representation</p>
                        <div class="mt-4">
                            <a href="#news" class="btn btn-primary-custom">
                                <i class="fas fa-newspaper me-2"></i>Latest News
                            </a>
                            <a href="#events" class="btn btn-outline-custom">
                                <i class="fas fa-calendar me-2"></i>Upcoming Events
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="swiper-slide" style="background-image: linear-gradient(rgba(26, 84, 144, 0.7), rgba(13, 59, 102, 0.8)), url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920');">
                    <div class="slide-content" data-aos="fade-up">
                        <h1>Excellence in Leadership</h1>
                        <p>Building Tomorrow's Leaders Today</p>
                        <div class="mt-4">
                            <a href="pages_php/login.php" class="btn btn-primary-custom">
                                <i class="fas fa-users me-2"></i>Join Us
                            </a>
                            <a href="#contact" class="btn btn-outline-custom">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </section>

    <!-- Quick Links -->
    <section class="quick-links">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="100">
                    <a href="pages_php/login.php" style="text-decoration: none;">
                        <div class="quick-link-card card-1">
                            <i class="fas fa-graduation-cap"></i>
                            <h4>Student Portal</h4>
                            <p>Access your account</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="200">
                    <a href="#events" style="text-decoration: none;">
                        <div class="quick-link-card card-2">
                            <i class="fas fa-calendar-check"></i>
                            <h4>Events</h4>
                            <p>View upcoming events</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="300">
                    <a href="#news" style="text-decoration: none;">
                        <div class="
