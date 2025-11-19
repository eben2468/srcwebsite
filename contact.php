<?php
// Include simple authentication
require_once __DIR__ . '/includes/simple_auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Check if user is logged in (optional for public page)
$isLoggedIn = isLoggedIn();

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long.";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $name)) {
        $errors[] = "Name can only contain letters, spaces, hyphens and apostrophes.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address format.";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required.";
    } elseif (strlen($subject) < 3) {
        $errors[] = "Subject must be at least 3 characters long.";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    } elseif (strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters long.";
    }
    
    if (!empty($errors)) {
        $errorMessage = implode('<br>', $errors);
    } else {
        // Sanitize inputs
        $name_sanitized = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email_sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        $subject_sanitized = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $message_sanitized = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
        // Save to database for record keeping
        $db_saved = false;
        $insert_sql = "INSERT INTO contact_messages (name, email, subject, message, ip_address, submitted_at) 
                      VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = @mysqli_prepare($conn, $insert_sql);
        if ($stmt) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($stmt, "sssss", $name_sanitized, $email_sanitized, $subject_sanitized, $message_sanitized, $ip_address);
            if (@mysqli_stmt_execute($stmt)) {
                $db_saved = true;
            }
            mysqli_stmt_close($stmt);
        }
        
        // Recipient email
        $to = "officialsrcvvu@gmail.com";
        
        // Email subject
        $email_subject = "[VVU SRC Contact Form] " . $subject_sanitized;
        
        // Email headers - simplified for better compatibility
        $headers = "From: noreply@vvusrc.local" . "\r\n";
        $headers .= "Reply-To: " . $email_sanitized . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Email body (HTML format)
        $email_body = "<!DOCTYPE html>";
        $email_body .= "<html><head><style>";
        $email_body .= "body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }";
        $email_body .= ".container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }";
        $email_body .= ".header { background: #1a5490; color: white; padding: 20px; text-align: center; }";
        $email_body .= ".content { background: white; padding: 30px; margin: 20px 0; border-radius: 5px; }";
        $email_body .= ".field { margin-bottom: 15px; }";
        $email_body .= ".label { font-weight: bold; color: #1a5490; }";
        $email_body .= ".footer { text-align: center; padding: 20px; color: #777; font-size: 12px; }";
        $email_body .= "</style></head><body>";
        $email_body .= "<div class='container'>";
        $email_body .= "<div class='header'><h2>New Contact Form Submission</h2></div>";
        $email_body .= "<div class='content'>";
        $email_body .= "<div class='field'><span class='label'>Name:</span> " . $name_sanitized . "</div>";
        $email_body .= "<div class='field'><span class='label'>Email:</span> " . $email_sanitized . "</div>";
        $email_body .= "<div class='field'><span class='label'>Subject:</span> " . $subject_sanitized . "</div>";
        $email_body .= "<div class='field'><span class='label'>Message:</span><br>" . nl2br($message_sanitized) . "</div>";
        $email_body .= "<hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>";
        $email_body .= "<div class='field'><span class='label'>Submitted:</span> " . date('F j, Y g:i A') . "</div>";
        $email_body .= "<div class='field'><span class='label'>IP Address:</span> " . $_SERVER['REMOTE_ADDR'] . "</div>";
        $email_body .= "</div>";
        $email_body .= "<div class='footer'>This email was sent from the VVU SRC Contact Form<br>Valley View University SRC</div>";
        $email_body .= "</div></body></html>";
        
        // Try to send email
        $mail_sent = @mail($to, $email_subject, $email_body, $headers);
        
        // If database saved successfully, show success even if email fails
        if ($db_saved) {
            $successMessage = "Thank you for contacting us, " . $name_sanitized . "! Your message has been received and saved. Our team will review it and get back to you as soon as possible.";
            if (!$mail_sent) {
                $successMessage .= " <small class='text-muted'>(Note: Email notification may be delayed due to server configuration, but your message has been recorded.)</small>";
            }
            
            // Clear form
            $name = $email = $subject = $message = '';
        } else {
            $errorMessage = "Sorry, there was a problem processing your message. Please try again later or contact us directly at officialsrcvvu@gmail.com or call +233 123 456 789.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Valley View University SRC</title>

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

        /* Contact Cards */
        .contact-card {
            background: var(--white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s;
            height: 100%;
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .contact-card .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--dark-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--white);
        }

        .contact-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .contact-card p {
            color: var(--text-light);
            margin: 0;
        }

        .contact-card a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .contact-card a:hover {
            color: var(--secondary-color);
        }

        /* Contact Form */
        .contact-form {
            background: var(--white);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .form-control, .form-select {
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 84, 144, 0.25);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: var(--primary-color);
            color: var(--white);
            padding: 15px 50px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: var(--dark-blue);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        /* Map Section */
        .map-container {
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* Office Hours */
        .office-hours {
            background: var(--light-bg);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .office-hours h5 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .office-hours ul {
            list-style: none;
            padding: 0;
        }

        .office-hours ul li {
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
        }

        .office-hours ul li:last-child {
            border-bottom: none;
        }

        .office-hours .day {
            font-weight: 600;
            color: var(--text-dark);
        }

        .office-hours .time {
            color: var(--text-light);
        }

        /* Alert Messages */
        .alert {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
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
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
            color: var(--orange);
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

            .contact-form {
                padding: 30px 20px;
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
                    <li class="nav-item"><a class="nav-link" href="events.php">Events</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
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
            <h1 data-aos="fade-up">Contact Us</h1>
            <p data-aos="fade-up" data-aos-delay="100">Get in touch with VVU SRC</p>
            <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="200">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Contact Cards Section -->
    <section class="content-section">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="contact-card">
                        <div class="icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Our Location</h4>
                        <p>Valley View University<br>Oyibi, Accra<br>Ghana</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="contact-card">
                        <div class="icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4>Phone Number</h4>
                        <p><a href="tel:+233548811774">+233 54 881 1774</a></p>
                        <p><a href="tel:+233245849246">+233 24 584 9246</a></p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="contact-card">
                        <div class="icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Email Address</h4>
                        <p><a href="mailto:officialsrcvvu@gmail.com">officialsrcvvu@gmail.com</a></p>
                        <p><a href="mailto:vvusrc1@gmail.com">vvusrc1@gmail.com</a></p>
                    </div>
                </div>
            </div>

            <!-- Contact Form and Map -->
            <div class="row">
                <div class="col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="contact-form">
                        <h2 class="section-title mb-4">Send us a Message</h2>
                        <p class="text-muted mb-4">Fill out the form below and we'll get back to you as soon as possible.</p>

                        <?php if ($successMessage): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($errorMessage): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <input type="text" class="form-control" name="name" placeholder="Your Name *" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Your Email *" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="subject" placeholder="Subject *" required value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" name="message" placeholder="Your Message *" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="contact_submit" class="btn btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="500">
                    <h2 class="section-title mb-4">Find Us Here</h2>
                    <div class="map-container">
                        <!-- Google Maps Embed - Replace with actual coordinates -->
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d9807.672676648304!2d-0.12025663881670771!3d5.8029920883633475!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfdf7991589a6c09%3A0xfb9d9343410f73c4!2sValley%20View%20University!5e0!3m2!1sen!2sgh!4v1763516113321!5m2!1sen!2sgh" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <!-- Office Hours -->
                    <div class="office-hours">
                        <h5><i class="far fa-clock me-2"></i>Office Hours</h5>
                        <ul>
                            <li>
                                <span class="day">Monday - Thursday</span>
                                <span class="time">9:00 AM - 5:00 PM</span>
                            </li>
                            <li>
                                <span class="day">Friday</span>
                                <span class="time">9:00 AM - 1:00 PM</span>
                            </li>
                            <li>
                                <span class="day">Weekend</span>
                                <span class="time">Closed</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Media Section -->
    <section class="content-section bg-light">
        <div class="container text-center">
            <h2 class="section-title mx-auto mb-4" data-aos="fade-up">Follow Us on Social Media</h2>
            <p class="text-muted mb-5" data-aos="fade-up" data-aos-delay="100">Stay connected with us on social media for the latest updates</p>
            <div class="social-links" data-aos="fade-up" data-aos-delay="200" style="font-size: 2rem;">
                <a href="#" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="#" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fab fa-youtube"></i>
                </a>
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
