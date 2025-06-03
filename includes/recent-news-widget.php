<?php
/**
 * Recent News Widget
 * 
 * Displays the most recent news items in an attractive format
 */

// Fetch recent news if not already available
if (!isset($recentNews) || empty($recentNews)) {
    try {
        $recentNewsSql = "SELECT news_id as id, title, DATE_FORMAT(created_at, '%Y-%m-%d') as date, 
                     COALESCE((SELECT username FROM users WHERE user_id = author_id), 'System') as author, status
                     FROM news 
                     ORDER BY created_at DESC 
                     LIMIT 3";
        $recentNews = fetchAll($recentNewsSql);
    } catch (Exception $e) {
        // Table might not exist or other error
        $recentNews = [];
    }
}
?>

<div class="recent-news-container">
    <div class="recent-news-header">
        <h3 class="recent-news-title">
            <i class="fas fa-newspaper"></i> Recent News
        </h3>
        <a href="news.php" class="view-all-btn">View All</a>
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