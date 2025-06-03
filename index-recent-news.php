<?php
// Include database config
require_once 'db_config.php';

// Fetch recent news data
$recentNews = [
    [
        'id' => 1,
        'title' => 'Important Announcement: June 1, 2025',
        'date' => '2025-06-01',
        'author' => 'System'
    ],
    [
        'id' => 2,
        'title' => 'Examination update',
        'date' => '2025-05-31',
        'author' => 'eben24680'
    ],
    [
        'id' => 3,
        'title' => 'Academic Calendar Update',
        'date' => '2025-05-31',
        'author' => 'admin'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent News</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Recent News CSS -->
    <link rel="stylesheet" href="css/recent-news.css">
    
    <style>
        body {
            background-color: #f5f5f5;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="recent-news-container">
            <div class="recent-news-header">
                <h3 class="recent-news-title">
                    <i class="fas fa-newspaper"></i> Recent News
                </h3>
                <a href="#" class="view-all-btn">View All</a>
            </div>
            
            <ul class="recent-news-list">
                <?php foreach ($recentNews as $news): ?>
                <li class="news-item">
                    <h4 class="news-title">
                        <a href="#">
                            <?php echo htmlspecialchars($news['title']); ?>
                        </a>
                    </h4>
                    <div class="news-meta">
                        <div class="news-date">
                            <i class="fas fa-calendar-day"></i>
                            <span><?php echo htmlspecialchars($news['date']); ?></span>
                        </div>
                        <div class="news-author">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($news['author']); ?></span>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 