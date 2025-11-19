<?php
// Include simple authentication
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Check if user is logged in (optional for public page)
$isLoggedIn = isLoggedIn();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 9;
$offset = ($page - 1) * $perPage;

// Fetch news from database
$newsItems = [];
$totalNews = 0;

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM news WHERE status = 'published'";
$countResult = mysqli_query($conn, $countQuery);
if ($countResult) {
    $totalNews = mysqli_fetch_assoc($countResult)['total'];
}

// Fetch news with pagination
$newsQuery = "SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$newsResult = mysqli_query($conn, $newsQuery);
if ($newsResult) {
    while ($row = mysqli_fetch_assoc($newsResult)) {
        $newsItems[] = $row;
    }
}

$totalPages = ceil($totalNews / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Updates - Valley View University SRC</title>

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

        .news-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
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
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .news-meta {
            display: flex;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 15px;
            flex-wrap: wrap;
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
            flex: 1;
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

        /* Pagination */
        .pagination {
            margin-top: 50px;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            margin: 0 5px;
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .pagination .page-link:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* No News Message */
        .no-news {
            text-align: center;
            padding: 80px 20px;
        }

        .no-news i {
            font-size: 5rem;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .no-news h3 {
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .no-news p {
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

            .news-image {
                height: 200px;
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
                        <span><i class="fas fa-phone"></i> +233 54 881 1774</span> &nbsp &nbsp
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
                    <li class="nav-item"><a class="nav-link active" href="news.php">News</a></li>
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
            <h1 data-aos="fade-up">News & Updates</h1>
            <p data-aos="fade-up" data-aos-delay="100">Stay informed with the latest happenings</p>
            <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="200">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">News</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- News Section -->
    <section class="content-section">
        <div class="container">
            <?php if (!empty($newsItems)): ?>
                <div class="row">
                    <?php foreach ($newsItems as $index => $news): ?>
                        <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="<?php echo ($index + 1) * 100; ?>">
                            <div class="news-card">
                                <div class="news-image">
                                    <span class="news-badge"><?php echo htmlspecialchars($news['category'] ?? 'News'); ?></span>
                                    <?php if (!empty($news['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($news['image_path']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>">
                                    <?php else: ?>
                                        <img src="https://images.unsplash.com/photo-1517048676732-d65bc937f952?w=600" alt="<?php echo htmlspecialchars($news['title']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="news-content">
                                    <div class="news-meta">
                                        <span><i class="far fa-calendar"></i> <?php echo date('M d, Y', strtotime($news['created_at'])); ?></span>
                                        <?php if (!empty($news['author'])): ?>
                                            <span><i class="far fa-user"></i> <?php echo htmlspecialchars($news['author']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                                    <p class="news-excerpt">
                                        <?php
                                            $excerpt = substr(strip_tags($news['content']), 0, 150);
                                            echo htmlspecialchars($excerpt);
                                            if (strlen(strip_tags($news['content'])) > 150) {
                                                echo '...';
                                            }
                                        ?>
                                    </p>
                                    <a href="pages_php/news.php<?php echo isset($news['id']) ? '?id=' . $news['id'] : ''; ?>" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="News pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-news" data-aos="fade-up">
                    <i class="far fa-newspaper"></i>
                    <h3>No News Available</h3>
                    <p>Check back soon for the latest updates and announcements from VVU SRC.</p>
                </div>
            <?php endif; ?>
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
                <p>&copy; 2024 Valley View University SRC. All Rights Reserved. | Designed by Ebenezer Owusu, SRC Editor for 2025/26 SRC Administration <i class="fas fa-heart" style="color: var(--secondary-color);"></i></p>
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
