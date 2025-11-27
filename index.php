<?php
// Include simple authentication
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();

// Fetch slider images from database
$sliderImages = [];
$sliderQuery = "SELECT * FROM slider_images WHERE is_active = 1 ORDER BY slide_order ASC";
$sliderResult = mysqli_query($conn, $sliderQuery);
if ($sliderResult) {
    while ($row = mysqli_fetch_assoc($sliderResult)) {
        $sliderImages[] = $row;
    }
}

// If no slider images in database, use defaults
if (empty($sliderImages)) {
    $sliderImages = [
        [
            'image_path' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1920',
            'title' => 'Valley View University',
            'subtitle' => 'Students\' Representative Council',
            'button1_text' => 'Student Login',
            'button1_link' => 'pages_php/login.php',
            'button2_text' => 'Learn More',
            'button2_link' => '#about'
        ],
        [
            'image_path' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1920',
            'title' => 'Your Voice Matters',
            'subtitle' => 'Empowering Students Through Representation',
            'button1_text' => 'Latest News',
            'button1_link' => '#news',
            'button2_text' => 'Upcoming Events',
            'button2_link' => '#events'
        ],
        [
            'image_path' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920',
            'title' => 'Excellence in Leadership',
            'subtitle' => 'Building Tomorrow\'s Leaders Today',
            'button1_text' => 'Join Us',
            'button1_link' => 'pages_php/login.php',
            'button2_text' => 'Contact Us',
            'button2_link' => '#contact'
        ]
    ];
}

// Fetch news from database
$newsItems = [];
$newsQuery = "SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 3";
$newsResult = mysqli_query($conn, $newsQuery);
if ($newsResult) {
    while ($row = mysqli_fetch_assoc($newsResult)) {
        $newsItems[] = $row;
    }
}

// If no news in database, use defaults
if (empty($newsItems)) {
    $newsItems = [
        [
            'title' => 'SRC Election 2024 Schedule Announced',
            'content' => 'The electoral commission has released the official schedule for the upcoming SRC elections. Nominations open next week.',
            'image_path' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=600',
            'category' => 'Announcement',
            'created_at' => date('Y-m-d'),
            'author' => 'SRC President'
        ],
        [
            'title' => 'Freshers\' Orientation Week Success',
            'content' => 'Over 1,000 new students participated in this year\'s orientation week activities, making it the most successful ever.',
            'image_path' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600',
            'category' => 'Event',
            'created_at' => date('Y-m-d', strtotime('-5 days')),
            'author' => 'Events Team'
        ],
        [
            'title' => 'Scholarship Fund Reaches GHS 50,000',
            'content' => 'The SRC scholarship initiative has successfully raised over GHS 50,000 to support underprivileged students.',
            'image_path' => 'https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?w=600',
            'category' => 'Achievement',
            'created_at' => date('Y-m-d', strtotime('-10 days')),
            'author' => 'SRC Team'
        ]
    ];
}

// Fetch events from database
$eventsItems = [];
// First check what columns exist in events table
$eventsColumnsQuery = "SHOW COLUMNS FROM events";
$eventsColumnsResult = @mysqli_query($conn, $eventsColumnsQuery);
$eventHasEventDate = false;
$eventHasDate = false;
$eventHasEventTime = false;
$eventHasTime = false;

if ($eventsColumnsResult) {
    while ($col = mysqli_fetch_assoc($eventsColumnsResult)) {
        if ($col['Field'] === 'event_date') $eventHasEventDate = true;
        if ($col['Field'] === 'date') $eventHasDate = true;
        if ($col['Field'] === 'event_time') $eventHasEventTime = true;
        if ($col['Field'] === 'time') $eventHasTime = true;
    }
}

// Build query based on actual column names
$dateColumn = $eventHasEventDate ? 'event_date' : ($eventHasDate ? 'date' : 'created_at');
$timeColumn = $eventHasEventTime ? 'event_time' : ($eventHasTime ? 'time' : 'created_at');

$eventsQuery = "SELECT * FROM events ORDER BY $dateColumn ASC LIMIT 10";
$eventsResult = @mysqli_query($conn, $eventsQuery);
if ($eventsResult) {
    while ($row = mysqli_fetch_assoc($eventsResult)) {
        // Filter for upcoming events
        $eventDateValue = $row[$dateColumn] ?? $row['created_at'] ?? date('Y-m-d');
        if (strtotime($eventDateValue) >= strtotime('today')) {
            $eventsItems[] = $row;
            if (count($eventsItems) >= 4) break;
        }
    }
}

// If no events in database, use defaults
if (empty($eventsItems)) {
    $dateCol = $eventHasEventDate ? 'event_date' : 'date';
    $timeCol = $eventHasEventTime ? 'event_time' : 'time';
    $eventsItems = [
        [
            'event_name' => 'Christmas Carol Night',
            $dateCol => '2024-12-25',
            $timeCol => '18:00:00',
            'event_end_time' => '21:00:00',
            'location' => 'University Auditorium',
            'description' => 'Open to all students'
        ],
        [
            'event_name' => 'SRC Leadership Summit 2025',
            $dateCol => '2025-01-05',
            $timeCol => '09:00:00',
            'event_end_time' => '17:00:00',
            'location' => 'Conference Hall',
            'description' => 'Student Leaders'
        ],
        [
            'event_name' => 'Career Fair 2025',
            $dateCol => '2025-01-15',
            $timeCol => '10:00:00',
            'event_end_time' => '16:00:00',
            'location' => 'Sports Complex',
            'description' => 'All students welcome'
        ],
        [
            'event_name' => 'Inter-Hall Sports Competition',
            $dateCol => '2025-01-20',
            $timeCol => '08:00:00',
            'event_end_time' => '18:00:00',
            'location' => 'University Stadium',
            'description' => 'All halls participate'
        ]
    ];
}
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

        html {
            overflow-x: hidden;
            width: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-dark);
            overflow-x: hidden;
            width: 100%;
            position: relative;
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
            font-size: 1.3rem;
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
            height: 850px;
            overflow: hidden;
            width: 100%;
        }

        .swiper {
            width: 100%;
            height: 100%;
        }

        .swiper-wrapper {
            width: 100%;
        }

        .swiper-slide {
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            width: 100%;
        }

        .swiper-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Overlay removed - image now fully visible */
        }

        .slide-content {
            position: absolute;
            z-index: 10;
            color: var(--white);
            max-width: 900px;
            padding: 40px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
        }

        /* Text Alignment Positions */
        .slide-content.align-top-left {
            top: 80px !important;
            left: 60px !important;
            right: auto !important;
            bottom: auto !important;
            transform: none !important;
            text-align: left !important;
        }

        .slide-content.align-top-center {
            top: 80px !important;
            left: 50% !important;
            right: auto !important;
            bottom: auto !important;
            transform: translateX(-50%) !important;
            text-align: center !important;
        }

        .slide-content.align-top-right {
            top: 80px !important;
            left: auto !important;
            right: 60px !important;
            bottom: auto !important;
            transform: none !important;
            text-align: right !important;
        }

        .slide-content.align-center-left {
            top: 50% !important;
            left: 60px !important;
            right: auto !important;
            bottom: auto !important;
            transform: translateY(-50%) !important;
            text-align: left !important;
        }

        .slide-content.align-center {
            top: 50% !important;
            left: 50% !important;
            right: auto !important;
            bottom: auto !important;
            transform: translate(-50%, -50%) !important;
            text-align: center !important;
        }

        .slide-content.align-center-right {
            top: 50% !important;
            left: auto !important;
            right: 60px !important;
            bottom: auto !important;
            transform: translateY(-50%) !important;
            text-align: right !important;
        }

        .slide-content.align-bottom-left {
            top: auto !important;
            left: 60px !important;
            right: auto !important;
            bottom: 80px !important;
            transform: none !important;
            text-align: left !important;
        }

        .slide-content.align-bottom-center {
            top: auto !important;
            left: 50% !important;
            right: auto !important;
            bottom: 80px !important;
            transform: translateX(-50%) !important;
            text-align: center !important;
        }

        .slide-content.align-bottom-right {
            top: auto !important;
            left: auto !important;
            right: 60px !important;
            bottom: 80px !important;
            transform: none !important;
            text-align: right !important;
        }

        /* Responsive adjustments for alignment */
        @media (max-width: 768px) {
            .slide-content {
                padding: 20px;
                max-width: 90%;
            }

            .slide-content.align-top-left,
            .slide-content.align-center-left,
            .slide-content.align-bottom-left {
                left: 20px;
            }

            .slide-content.align-top-right,
            .slide-content.align-center-right,
            .slide-content.align-bottom-right {
                right: 20px;
            }

            .slide-content.align-top-left,
            .slide-content.align-top-center,
            .slide-content.align-top-right {
                top: 40px;
            }

            .slide-content.align-bottom-left,
            .slide-content.align-bottom-center,
            .slide-content.align-bottom-right {
                bottom: 40px;
            }
        }



        .slide-content h1 {
            font-family: 'Argentum Sans', serif;
            font-size: var(--title-size, 4rem);
            font-weight: 900;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 3px 3px 10px rgba(0,0,0,0.5), 0 0 20px rgba(0,0,0,0.7);
        }

        .slide-content p {
            font-size: var(--subtitle-size, 1.3rem);
            margin-bottom: 30px;
            opacity: 0.95;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.9), 0 0 15px rgba(0,0,0,0.7);
        }

        /* Responsive font sizes */
        @media (max-width: 768px) {
            .slide-content h1 {
                font-size: calc(var(--title-size, 4rem) * 0.6);
            }
            .slide-content p {
                font-size: calc(var(--subtitle-size, 1.3rem) * 0.8);
            }
        }

        .slide-content .btn {
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 10px;
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
            html, body {
                overflow-x: hidden;
                width: 100%;
                max-width: 100%;
            }

            .container, .container-fluid {
                overflow-x: hidden;
                max-width: 100%;
            }

            .row {
                margin-left: 0;
                margin-right: 0;
            }

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
                width: 100%;
            }

            /* Hide swiper navigation buttons on mobile */
            .swiper-button-next,
            .swiper-button-prev {
                display: none !important;
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
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
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

    <!-- Hero Slider -->
    <section class="hero-slider" id="home">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                <?php foreach ($sliderImages as $index => $slide): ?>
                <!-- Slide <?php echo $index + 1; ?> -->
                <div class="swiper-slide" style="background-image: url('<?php echo htmlspecialchars($slide['image_path']); ?>');">
                    <?php 
                    // Get text alignment, default to 'center' if not set
                    $textAlignment = $slide['text_alignment'] ?? 'center';
                    $alignmentClass = 'align-' . $textAlignment;
                    
                    // Get font sizes, default to standard values if not set
                    $titleFontSize = $slide['title_font_size'] ?? '4';
                    $subtitleFontSize = $slide['subtitle_font_size'] ?? '1.3';
                    ?>
                    <div class="slide-content <?php echo $alignmentClass; ?>" data-aos="fade-up" style="--title-size: <?php echo htmlspecialchars($titleFontSize); ?>rem; --subtitle-size: <?php echo htmlspecialchars($subtitleFontSize); ?>rem;">
                        <?php if (!empty($slide['title'])): ?>
                        <h1><?php echo htmlspecialchars($slide['title']); ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($slide['subtitle'])): ?>
                        <p><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                        <?php endif; ?>
                        <div class="mt-4">
                            <?php if (!empty($slide['button1_text']) && !empty($slide['button1_link'])): ?>
                                <?php
                                $btn1Link = $slide['button1_link'];
                                // If logged in and button1 is login link, change to dashboard
                                if ($isLoggedIn && strpos($btn1Link, 'login.php') !== false) {
                                    $btn1Link = 'pages_php/dashboard.php';
                                    $btn1Text = 'Go to Dashboard';
                                    $btn1Icon = 'tachometer-alt';
                                } else {
                                    $btn1Text = $slide['button1_text'];
                                    $btn1Icon = strpos($btn1Link, 'login') !== false ? 'sign-in-alt' : 'newspaper';
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($btn1Link); ?>" class="btn btn-primary-custom">
                                    <i class="fas fa-<?php echo $btn1Icon; ?> me-2"></i><?php echo htmlspecialchars($btn1Text); ?>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($slide['button2_text']) && !empty($slide['button2_link'])): ?>
                                <a href="<?php echo htmlspecialchars($slide['button2_link']); ?>" class="btn btn-outline-custom">
                                    <i class="fas fa-<?php echo strpos($slide['button2_link'], 'contact') !== false ? 'envelope' : 'info-circle'; ?> me-2"></i><?php echo htmlspecialchars($slide['button2_text']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
                        <div class="quick-link-card card-3">
                            <i class="fas fa-newspaper"></i>
                            <h4>News & Updates</h4>
                            <p>Latest announcements</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="400">
                    <a href="#contact" style="text-decoration: none;">
                        <div class="quick-link-card card-4">
                            <i class="fas fa-envelope"></i>
                            <h4>Contact Us</h4>
                            <p>Get in touch with SRC</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span class="stat-number">1500+</span>
                        <span class="stat-label">Active Students</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="stat-number">30+</span>
                        <span class="stat-label">Events Annually</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-item">
                        <i class="fas fa-award"></i>
                        <span class="stat-number">10+</span>
                        <span class="stat-label">SRC Portfolios</span>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-item">
                        <i class="fas fa-hands-helping"></i>
                        <span class="stat-number">20+</span>
                        <span class="stat-label">Projects Completed</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
                    <h2 class="section-title">About VVU SRC</h2>
                    <p class="section-subtitle">Serving students with excellence and integrity</p>
                    <div class="about-content">
                        <p>The Students' Representative Council (SRC) of Valley View University is the official student government body representing the interests and welfare of all students. We are committed to fostering a vibrant campus life, advocating for student rights, and creating meaningful opportunities for personal and academic growth.</p>
                        <p>Our mission is to bridge the gap between students and university administration, organize impactful events, manage student welfare initiatives, and ensure that every student's voice is heard and valued.</p>
                        <p>Through transparent leadership, innovative programs, and dedicated service, we strive to make VVU a better place for all students.</p>
                        <div class="mt-4">
                            <a href="pages_php/login.php" class="btn btn-primary-custom me-2">
                                <i class="fas fa-user-circle me-2"></i>Get Involved
                            </a>
                            <a href="#contact" class="btn btn-outline-custom">
                                <i class="fas fa-phone me-2"></i>Contact Us
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-image">
                        <img src="https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=800" alt="Students" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section class="news-section" id="news">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Latest News & Updates</h2>
                <p class="section-subtitle">Stay informed with the latest happenings</p>
            </div>
            <div class="row">
                <?php foreach ($newsItems as $index => $news): ?>
                <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                    <div class="news-card">
                        <div class="news-image">
                            <span class="news-badge"><?php echo htmlspecialchars($news['category'] ?? 'News'); ?></span>
                            <img src="<?php echo htmlspecialchars($news['image_path'] ?? 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=600'); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($news['created_at'])); ?></span>
                                <span><i class="far fa-user"></i> <?php
                                    if (isset($news['first_name']) && isset($news['last_name'])) {
                                        echo htmlspecialchars($news['first_name'] . ' ' . $news['last_name']);
                                    } elseif (isset($news['author'])) {
                                        echo htmlspecialchars($news['author']);
                                    } else {
                                        echo 'SRC Admin';
                                    }
                                ?></span>
                            </div>
                            <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                            <p class="news-excerpt"><?php echo htmlspecialchars(substr($news['content'], 0, 150)); ?><?php echo strlen($news['content']) > 150 ? '...' : ''; ?></p>
                            <a href="pages_php/news.php<?php echo isset($news['id']) ? '?id=' . $news['id'] : ''; ?>" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="events-section" id="events">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Upcoming Events</h2>
                <p class="section-subtitle">Don't miss out on these exciting events</p>
            </div>
            <div class="row">
                <?php foreach ($eventsItems as $index => $event):
                    // Handle different column names
                    $eventDateValue = $event['event_date'] ?? $event['date'] ?? date('Y-m-d');
                    $eventTimeValue = $event['event_time'] ?? $event['time'] ?? '00:00:00';

                    $eventDate = new DateTime($eventDateValue);
                    $eventDay = $eventDate->format('d');
                    $eventMonth = strtoupper($eventDate->format('M'));
                    $eventTime = date('g:i A', strtotime($eventTimeValue));
                    $eventEndTime = !empty($event['event_end_time']) ? date('g:i A', strtotime($event['event_end_time'])) : '';
                ?>
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                    <div class="event-card">
                        <div class="event-date">
                            <span class="event-day"><?php echo $eventDay; ?></span>
                            <span class="event-month"><?php echo $eventMonth; ?></span>
                        </div>
                        <div class="event-details">
                            <h4 class="event-title"><?php echo htmlspecialchars($event['event_name'] ?? $event['name'] ?? 'Event'); ?></h4>
                            <p class="event-info"><i class="fas fa-clock"></i> <?php echo $eventTime; ?><?php echo $eventEndTime ? ' - ' . $eventEndTime : ''; ?></p>
                            <p class="event-info"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?></p>
                            <p class="event-info"><i class="fas fa-users"></i> <?php echo htmlspecialchars($event['description'] ?? 'All students'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="contact">
        <div class="cta-content">
            <div class="container">
                <h2 class="cta-title" data-aos="fade-up">Get Involved with SRC</h2>
                <p class="cta-text" data-aos="fade-up" data-aos-delay="100">
                    Join us in making a difference. Your voice matters, and we want to hear from you!
                </p>
                <div data-aos="fade-up" data-aos-delay="200">
                    <?php if ($isLoggedIn): ?>
                        <a href="pages_php/dashboard.php" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="pages_php/login.php" class="btn btn-primary-custom btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join Us Today
                        </a>
                    <?php endif; ?>
                    <a href="#contact" class="btn btn-outline-custom btn-lg ms-2">
                        <i class="fas fa-envelope me-2"></i>Contact SRC
                    </a>
                </div>
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
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="#news"><i class="fas fa-chevron-right"></i> News</a></li>
                        <li><a href="#events"><i class="fas fa-chevron-right"></i> Events</a></li>
                        <li><a href="pages_php/login.php"><i class="fas fa-chevron-right"></i> Portal</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h5>Services</h5>
                    <ul>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Student Welfare</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Events Management</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Scholarships</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Feedback System</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Support</a></li>
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
                        <li><i class="fas fa-envelope"></i> src@vvu.edu.gh</li>
                        <li><i class="fas fa-clock"></i> Mon - Fri: 8AM - 5PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025/2026 Valley View University SRC. All Rights Reserved. | Designed by Ebenezer Owusu, SRC Editor for 2025/26 SRC Administration <i class="fas fa-heart" style="color: var(--secondary-color);"></i></p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- AOS JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Initialize Swiper
        const swiper = new Swiper('.heroSwiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            }
        });

        // Preloader
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            setTimeout(() => {
                preloader.style.opacity = '0';
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 500);
            }, 800);
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Back to top button
        const backToTopBtn = document.getElementById('backToTop');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href !== '#contact') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        const offsetTop = target.offsetTop - 80;
                        window.scrollTo({
                            top: offsetTop,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });

        // Counter animation
        function animateCounter(element, start, end, duration) {
            let current = start;
            const increment = (end - start) / (duration / 16);
            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    element.textContent = end + '+';
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current) + '+';
                }
            }, 16);
        }

        // Trigger counter animation when stats section is visible
        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counters = document.querySelectorAll('.stat-number');
                        counters.forEach(counter => {
                            const target = parseInt(counter.textContent.replace('+', ''));
                            animateCounter(counter, 0, target, 2000);
                        });
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            observer.observe(statsSection);
        }
    </script>
</body>
</html>
