<?php
/**
 * Announcement and Event Cards Component
 * This file displays the latest announcements and events in a card format
 */

// Add cache-busting parameter to ensure fresh data
$cacheBuster = time();

// Get latest news/announcements (limit to 3) - check for image column first
$latestNews = [];
try {
    // First try with image_path column
    $latestNews = fetchAll("SELECT n.news_id, n.title, n.content, n.image_path, n.created_at,
                            COALESCE(u.username, 'System') as author_name
                            FROM news n
                            LEFT JOIN users u ON n.author_id = u.user_id
                            ORDER BY n.created_at DESC
                            LIMIT 3");
} catch (Exception $e) {
    // If image_path doesn't exist, try without it
    try {
        $latestNews = fetchAll("SELECT n.news_id, n.title, n.content, n.created_at,
                                COALESCE(u.username, 'System') as author_name
                                FROM news n
                                LEFT JOIN users u ON n.author_id = u.user_id
                                ORDER BY n.created_at DESC
                                LIMIT 3");
    } catch (Exception $e2) {
        // If that fails too, use empty array
        $latestNews = [];
    }
}

// Get upcoming events (limit to 3)
// First check if end_date column exists
$checkColumnSQL = "SHOW COLUMNS FROM events LIKE 'end_date'";
$result = mysqli_query($conn, $checkColumnSQL);
$endDateExists = $result && mysqli_num_rows($result) > 0;

if ($endDateExists) {
    // Use query with end_date if it exists
    $upcomingEvents = fetchAll("SELECT * FROM events 
                               WHERE date >= CURDATE() OR
                                     (end_date IS NOT NULL AND end_date >= CURDATE())
                               ORDER BY date ASC 
                               LIMIT 3");
} else {
    // Use simpler query without end_date
    $upcomingEvents = fetchAll("SELECT * FROM events 
                               WHERE date >= CURDATE()
                               ORDER BY date ASC 
                               LIMIT 3");
}
?>

<!-- Announcements & Events Section -->
<div class="row">
    <!-- Latest Announcements -->
    <div class="col-md-6 mb-4">
        <div class="content-card h-100 animate-fadeIn">
            <div class="content-card-header">
                <h3 class="content-card-title">
                    <i class="fas fa-bullhorn me-2"></i> Latest Announcements
                </h3>
                <a href="news.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="content-card-body">
                <?php if (empty($latestNews)): ?>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i> No announcements available.
                </div>
                <?php else: ?>
                <div class="announcement-cards">
                    <?php 
                    $cardIndex = 0;
                    foreach ($latestNews as $news): 
                        $cardIndex++;
                    ?>
                    <div class="announcement-card mb-3" style="--card-index: <?php echo $cardIndex; ?>">
                        <div class="row g-0">
                            <?php if (isset($news['image_path']) && !empty($news['image_path'])): ?>
                            <div class="col-md-4">
                                <img src="<?php echo '../' . htmlspecialchars($news['image_path']); ?>" class="img-fluid rounded" alt="Announcement Image" style="height: 100%; object-fit: cover;">
                            </div>
                            <div class="col-md-8">
                            <?php else: ?>
                            <div class="col-md-12">
                            <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
                                    <p class="card-text text-truncate"><?php echo htmlspecialchars(substr($news['content'], 0, 100)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M j, Y', strtotime($news['created_at'])); ?>
                                        </small>
                                    </div>
                                    <a href="news-detail.php?id=<?php echo $news['news_id']; ?>" class="btn btn-sm btn-primary mt-2">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Important Notices with Upcoming Events -->
    <div class="col-md-6 mb-4">
        <div class="content-card h-100 animate-fadeIn">
            <div class="content-card-header">
                <h3 class="content-card-title">
                    <i class="fas fa-calendar-alt me-2"></i> Important Notices
                </h3>
                <a href="events.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="content-card-body">
                <?php if (empty($upcomingEvents)): ?>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i> No upcoming events available.
                </div>
                <?php else: ?>
                <div class="announcement-cards">
                    <?php 
                    $eventCardIndex = 0;
                    foreach ($upcomingEvents as $event): 
                        $eventCardIndex++;
                        // Default image if none is set
                        $eventImage = (isset($event['image_path']) && !empty($event['image_path'])) ? '../' . $event['image_path'] : '../assets/images/event-default.png';
                    ?>
                    <div class="announcement-card mb-3" style="--card-index: <?php echo $eventCardIndex; ?>">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <img src="<?php echo htmlspecialchars($eventImage); ?>" class="img-fluid rounded" alt="Event Image" style="height: 100%; object-fit: cover;">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                    <p class="card-text text-truncate"><?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 100)) . '...'; ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M j, Y', strtotime($event['date'])); ?>
                                        </small>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location'] ?? 'TBA'); ?>
                                        </span>
                                    </div>
                                    <a href="event-detail.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary mt-2">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
