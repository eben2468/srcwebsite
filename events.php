<?php
// Include simple authentication
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Check if user is logged in (optional for public page)
$isLoggedIn = isLoggedIn();

// Detect event table column names
$eventsColumnsQuery = "SHOW COLUMNS FROM events";
$eventsColumnsResult = @mysqli_query($conn, $eventsColumnsQuery);
$eventHasEventDate = false;
$eventHasDate = false;
$eventHasEventTime = false;
$eventHasTime = false;
$eventHasEventName = false;
$eventHasName = false;

if ($eventsColumnsResult) {
    while ($col = mysqli_fetch_assoc($eventsColumnsResult)) {
        if ($col['Field'] === 'event_date') $eventHasEventDate = true;
        if ($col['Field'] === 'date') $eventHasDate = true;
        if ($col['Field'] === 'event_time') $eventHasEventTime = true;
        if ($col['Field'] === 'time') $eventHasTime = true;
        if ($col['Field'] === 'event_name') $eventHasEventName = true;
        if ($col['Field'] === 'name') $eventHasName = true;
    }
}

// Determine correct column names
$dateColumn = $eventHasEventDate ? 'event_date' : ($eventHasDate ? 'date' : 'created_at');
$timeColumn = $eventHasEventTime ? 'event_time' : ($eventHasTime ? 'time' : null);
$nameColumn = $eventHasEventName ? 'event_name' : ($eventHasName ? 'name' : 'title');

// Fetch upcoming events
$upcomingEvents = [];
$upcomingQuery = "SELECT * FROM events ORDER BY $dateColumn ASC";
$upcomingResult = @mysqli_query($conn, $upcomingQuery);
if ($upcomingResult) {
    while ($row = mysqli_fetch_assoc($upcomingResult)) {
        $eventDateValue = $row[$dateColumn] ?? date('Y-m-d');
        if (strtotime($eventDateValue) >= strtotime('today')) {
            $upcomingEvents[] = $row;
        }
    }
}

// Fetch past events
$pastEvents = [];
$pastQuery = "SELECT * FROM events ORDER BY $dateColumn DESC LIMIT 6";
$pastResult = @mysqli_query($conn, $pastQuery);
if ($pastResult) {
    while ($row = mysqli_fetch_assoc($pastResult)) {
        $eventDateValue = $row[$dateColumn] ?? date('Y-m-d');
        if (strtotime($eventDateValue) < strtotime('today')) {
            $pastEvents[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Valley View University SRC</title>

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

        .event-date.past {
            background: var(--text-light);
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
            display: flex;
            align-items: center;
        }

        .event-info i {
            margin-right: 10px;
            color: var(--primary-color);
            width: 20px;
        }

        .event-description {
            margin-top: 15px;
            color: var(--text-dark);
            line-height: 1.6;
        }

        /* No Events Message */
        .no-events {
            text-align: center;
            padding: 80px 20px;
        }

        .no-events i {
            font-size: 5rem;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .no-events h3 {
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .no-events p {
            color: var(--text-light);
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

            .event-card {
                flex-direction: column;
            }

            .event-date {
                min-width: 100%;
                padding: 20px;
            }

            .section-title {
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
                    <li class="nav-item"><a class="nav-link active" href="events.php">Events</a></li>
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
            <h1 data-aos="fade-up">Upcoming Events</h1>
            <p data-aos="fade-up" data-aos-delay="100">Don't miss out on these exciting events</p>
            <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="200">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Events</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Upcoming Events Section -->
    <section class="content-section">
        <div class="container">
            <div class="mb-5">
                <h2 class="section-title" data-aos="fade-up">Upcoming Events</h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="100">Mark your calendar for these events</p>
            </div>

            <?php if (!empty($upcomingEvents)): ?>
                <div class="row">
                    <?php foreach ($upcomingEvents as $index => $event):
                        $eventDateValue = $event[$dateColumn] ?? date('Y-m-d');
                        $eventTimeValue = $event[$timeColumn] ?? '00:00:00';
                        $eventNameValue = $event[$nameColumn] ?? $event['event_name'] ?? $event['name'] ?? 'Event';

                        $eventDate = new DateTime($eventDateValue);
                        $eventDay = $eventDate->format('d');
                        $eventMonth = strtoupper($eventDate->format('M'));
                        $eventTime = $timeColumn ? date('g:i A', strtotime($eventTimeValue)) : '';
                        $eventEndTime = !empty($event['event_end_time']) ? date('g:i A', strtotime($event['event_end_time'])) : '';
                    ?>
                    <div class="col-lg-12" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                        <div class="event-card">
                            <div class="event-date">
                                <span class="event-day"><?php echo $eventDay; ?></span>
                                <span class="event-month"><?php echo $eventMonth; ?></span>
                            </div>
                            <div class="event-details">
                                <h4 class="event-title"><?php echo htmlspecialchars($eventNameValue); ?></h4>
                                <?php if ($eventTime): ?>
                                    <p class="event-info">
                                        <i class="fas fa-clock"></i>
                                        <?php echo $eventTime; ?><?php echo $eventEndTime ? ' - ' . $eventEndTime : ''; ?>
                                    </p>
                                <?php endif; ?>
                                <p class="event-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?>
                                </p>
                                <?php if (!empty($event['description'])): ?>
                                    <p class="event-info">
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($event['description']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($event['details'])): ?>
                                    <div class="event-description">
                                        <?php echo nl2br(htmlspecialchars($event['details'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-events" data-aos="fade-up">
                    <i class="far fa-calendar-alt"></i>
                    <h3>No Upcoming Events</h3>
                    <p>Check back soon for exciting events from VVU SRC.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Past Events Section -->
    <?php if (!empty($pastEvents)): ?>
    <section class="content-section bg-light">
        <div class="container">
            <div class="mb-5">
                <h2 class="section-title" data-aos="fade-up">Past Events</h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="100">Recent events we've hosted</p>
            </div>

            <div class="row">
                <?php foreach ($pastEvents as $index => $event):
                    $eventDateValue = $event[$dateColumn] ?? date('Y-m-d');
                    $eventTimeValue = $event[$timeColumn] ?? '00:00:00';
                    $eventNameValue = $event[$nameColumn] ?? $event['event_name'] ?? $event['name'] ?? 'Event';

                    $eventDate = new DateTime($eventDateValue);
                    $eventDay = $eventDate->format('d');
                    $eventMonth = strtoupper($eventDate->format('M'));
                    $eventTime = $timeColumn ? date('g:i A', strtotime($eventTimeValue)) : '';
                ?>
                <div class="col-lg-12" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                    <div class="event-card">
                        <div class="event-date past">
                            <span class="event-day"><?php echo $eventDay; ?></span>
                            <span class="event-month"><?php echo $eventMonth; ?></span>
                        </div>
                        <div class="event-details">
                            <h4 class="event-title"><?php echo htmlspecialchars($eventNameValue); ?></h4>
                            <?php if ($eventTime): ?>
                                <p class="event-info">
                                    <i class="fas fa-clock"></i>
                                    <?php echo $eventTime; ?>
                                </p>
                            <?php endif; ?>
                            <p class="event-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
                <p>&copy; 2024 Valley View University SRC. All Rights Reserved. | Designed with <i class="fas fa-heart" style="color: var(--secondary-color);"></i> for Students</p>
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
<?php mysqli_close($conn); ?>
