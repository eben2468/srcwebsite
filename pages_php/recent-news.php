<?php
// Include simple authentication and required files
require_once __DIR__ . '/../includes/simple_auth.php';
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Require login for this page
requireLogin();
require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/settings_functions.php';

// Set page title
$pageTitle = "Recent News - SRC Management System";

// Include header
require_once 'includes/header.php';

// Fetch recent news
try {
    $recentNewsSql = "SELECT news_id as id, title, DATE_FORMAT(created_at, '%Y-%m-%d') as date, 
                 COALESCE((SELECT username FROM users WHERE user_id = author_id), 'System') as author, status
                 FROM news 
                 ORDER BY created_at DESC 
                 LIMIT 10";
    $recentNews = fetchAll($recentNewsSql);
} catch (Exception $e) {
    // Table might not exist or other error
    $recentNews = [];
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="recent-news-container">
                <div class="recent-news-header">
                    <h3 class="recent-news-title">
                        <i class="fas fa-newspaper"></i> Recent News & Announcements
                    </h3>
                </div>
                
                <?php if (empty($recentNews)): ?>
                <div class="news-empty-state">
                    <i class="fas fa-info-circle"></i>
                    <p>No recent news available</p>
                </div>
                <?php else: ?>
                <ul class="recent-news-list">
                    <?php foreach ($recentNews as $news): ?>
                    <li class="news-item">
                        <h4 class="news-title">
                            <a href="news-detail.php?id=<?php echo $news['id']; ?>">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 
