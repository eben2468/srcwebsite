<?php
/**
 * Gallery Carousel Component
 * This file displays the most recent gallery images in an animated carousel
 */

// Include required files if not already included
if (!function_exists('fetchAll')) {
    require_once 'db_config.php';
}

// Get recent gallery images (limit to 10)
try {
    $galleryImages = fetchAll("SELECT g.gallery_id, g.title, g.description, g.file_name, g.file_type, 
                             g.upload_date, COALESCE(u.username, 'System') as uploader 
                             FROM gallery g 
                             LEFT JOIN users u ON g.uploaded_by = u.user_id 
                             WHERE g.file_type = 'image' AND g.status = 'active' 
                             ORDER BY g.upload_date DESC 
                             LIMIT 10");
} catch (Exception $e) {
    // Table might not exist or other error
    $galleryImages = [];
}

// If no images found in the database, try to find images in the uploads directory
if (empty($galleryImages)) {
    $galleryDir = 'uploads/gallery/';
    if (file_exists($galleryDir) && is_dir($galleryDir)) {
        $files = scandir($galleryDir);
        foreach ($files as $file) {
            if ($file != "." && $file != ".." && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $galleryImages[] = [
                    'gallery_id' => uniqid(),
                    'title' => pathinfo($file, PATHINFO_FILENAME),
                    'description' => '',
                    'file_name' => $file,
                    'file_type' => 'image',
                    'upload_date' => date('Y-m-d H:i:s', filemtime($galleryDir . $file)),
                    'uploader' => 'System'
                ];
            }
        }
        // Sort by upload date (newest first)
        usort($galleryImages, function($a, $b) {
            return strtotime($b['upload_date']) - strtotime($a['upload_date']);
        });
        // Limit to 10
        $galleryImages = array_slice($galleryImages, 0, 10);
    }
}

// If still no images found, use placeholder images
if (empty($galleryImages)) {
    // Base64 encoded placeholder images
    $placeholderBase64 = [
        'campus' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAZABkAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t6KKK+fPpAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k=',
        'library' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAZABkAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t6KKK+fPpAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k=',
        'graduation' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAZABkAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t6KKK+fPpAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k=',
        'lecture' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAZABkAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t6KKK+fPpAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k=',
        'sports' => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2NjIpLCBxdWFsaXR5ID0gOTAK/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU/8AAEQgAZABkAwEiAAIRAQMRAf/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC//EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29/j5+v/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC//EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29/j5+v/aAAwDAQACEQMRAD8A+t6KKK+fPpAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooA/9k='
    ];
    
    $placeholders = [
        [
            'gallery_id' => 'placeholder1',
            'title' => 'Campus Life',
            'description' => 'Students enjoying activities on campus',
            'file_name' => 'placeholder1.jpg',
            'file_type' => 'image',
            'upload_date' => date('Y-m-d H:i:s'),
            'uploader' => 'System',
            'placeholder_type' => 'campus'
        ],
        [
            'gallery_id' => 'placeholder2',
            'title' => 'Library Resources',
            'description' => 'Modern library facilities for research and study',
            'file_name' => 'placeholder2.jpg',
            'file_type' => 'image',
            'upload_date' => date('Y-m-d H:i:s'),
            'uploader' => 'System',
            'placeholder_type' => 'library'
        ],
        [
            'gallery_id' => 'placeholder3',
            'title' => 'Graduation Ceremony',
            'description' => 'Celebrating academic achievements and success',
            'file_name' => 'placeholder3.jpg',
            'file_type' => 'image',
            'upload_date' => date('Y-m-d H:i:s'),
            'uploader' => 'System',
            'placeholder_type' => 'graduation'
        ],
        [
            'gallery_id' => 'placeholder4',
            'title' => 'Lecture Hall',
            'description' => 'Modern lecture facilities for interactive learning',
            'file_name' => 'placeholder4.jpg',
            'file_type' => 'image',
            'upload_date' => date('Y-m-d H:i:s'),
            'uploader' => 'System',
            'placeholder_type' => 'lecture'
        ],
        [
            'gallery_id' => 'placeholder5',
            'title' => 'Sports Activities',
            'description' => 'Promoting physical fitness and team spirit',
            'file_name' => 'placeholder5.jpg',
            'file_type' => 'image',
            'upload_date' => date('Y-m-d H:i:s'),
            'uploader' => 'System',
            'placeholder_type' => 'sports'
        ]
    ];
    $galleryImages = $placeholders;
}

// Generate a unique ID for this carousel instance
$carouselId = 'gallery-carousel-' . uniqid();
?>

<!-- Gallery Carousel Card -->
<div class="content-card animate-fadeIn">
    <div class="content-card-header">
        <h3 class="content-card-title">
            <i class="fas fa-images me-2"></i> Gallery Showcase
        </h3>
        <a href="gallery.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    <div class="content-card-body p-0">
        <?php if (empty($galleryImages)): ?>
        <div class="alert alert-info m-3">
            <i class="fas fa-info-circle me-2"></i> No gallery images available.
        </div>
        <?php else: ?>
        <div id="<?php echo $carouselId; ?>" class="gallery-carousel">
            <div class="gallery-carousel-inner">
                <?php foreach ($galleryImages as $index => $image): ?>
                <div class="gallery-carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                    <img src="<?php 
                        // Handle different path scenarios
                        $imagePath = '';
                        if (isset($image['file_name'])) {
                            // Check if it's a placeholder image
                            if (strpos($image['gallery_id'], 'placeholder') === 0) {
                                if (isset($image['placeholder_type']) && isset($placeholderBase64[$image['placeholder_type']])) {
                                    $imagePath = $placeholderBase64[$image['placeholder_type']];
                                } else {
                                    $imagePath = 'https://source.unsplash.com/random/800x600/?campus,' . str_replace(' ', ',', $image['title']);
                                }
                            }
                            // Check if it's a full path
                            else if (strpos($image['file_name'], 'uploads/') === 0) {
                                $imagePath = $image['file_name'];
                            }
                            // Check if file exists in uploads/gallery
                            else if (file_exists('uploads/gallery/' . $image['file_name'])) {
                                $imagePath = 'uploads/gallery/' . $image['file_name'];
                            }
                            // Check if file exists in ../uploads/gallery (for pages in subdirectories)
                            else if (file_exists('../uploads/gallery/' . $image['file_name'])) {
                                $imagePath = '../uploads/gallery/' . $image['file_name'];
                            }
                            // If no image found, use placeholder
                            else {
                                $imagePath = 'https://source.unsplash.com/random/800x600/?campus,' . str_replace(' ', ',', $image['title']);
                            }
                        }
                        echo $imagePath;
                    ?>" 
                         alt="<?php echo htmlspecialchars($image['title']); ?>" 
                         class="gallery-carousel-image">
                    <div class="gallery-carousel-caption">
                        <h5><?php echo htmlspecialchars($image['title']); ?></h5>
                        <?php if (!empty($image['description'])): ?>
                        <p><?php echo htmlspecialchars($image['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button class="gallery-carousel-control gallery-carousel-prev" type="button" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="gallery-carousel-control gallery-carousel-next" type="button" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="gallery-carousel-indicators">
                <?php foreach ($galleryImages as $index => $image): ?>
                <button type="button" class="<?php echo ($index === 0) ? 'active' : ''; ?>" 
                        data-index="<?php echo $index; ?>" 
                        aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.gallery-carousel {
    position: relative;
    width: 100%;
    overflow: hidden;
    height: 350px;
    background-color: #000;
    border-radius: 8px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.gallery-carousel-inner {
    position: relative;
    width: 100%;
    height: 100%;
}

.gallery-carousel-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.8s ease-in-out, transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    transform: scale(0.95) translateY(10px);
}

.gallery-carousel-item.active {
    opacity: 1;
    transform: scale(1) translateY(0);
    z-index: 1;
}

.gallery-carousel-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.9);
    transition: filter 0.5s ease;
}

.gallery-carousel:hover .gallery-carousel-image {
    filter: brightness(1);
}

.gallery-carousel-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0));
    color: white;
    padding: 30px 15px 15px;
    text-align: center;
    transform: translateY(0);
    transition: transform 0.5s ease;
}

.gallery-carousel-caption h5 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
}

.gallery-carousel-caption p {
    margin: 8px 0 0;
    font-size: 14px;
    opacity: 0.9;
    max-width: 80%;
    margin-left: auto;
    margin-right: auto;
}

.gallery-carousel-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s ease;
}

.gallery-carousel:hover .gallery-carousel-control {
    opacity: 0.8;
}

.gallery-carousel-control:hover {
    opacity: 1 !important;
    background: rgba(0, 0, 0, 0.7);
    transform: translateY(-50%) scale(1.1);
}

.gallery-carousel-prev {
    left: 15px;
}

.gallery-carousel-next {
    right: 15px;
}

.gallery-carousel-indicators {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
    display: flex;
    gap: 8px;
}

.gallery-carousel-indicators button {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    border: none;
    padding: 0;
    cursor: pointer;
    transition: all 0.3s ease;
    transform: scale(1);
}

.gallery-carousel-indicators button.active {
    background: white;
    transform: scale(1.3);
}

.gallery-carousel-indicators button:hover {
    background: white;
}

/* Animations for carousel items */
@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.92) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes fadeOutScale {
    from { opacity: 1; transform: scale(1) translateY(0); }
    to { opacity: 0; transform: scale(1.08) translateY(-10px); }
}

.gallery-carousel-item.animate-in {
    animation: fadeInScale 0.8s forwards cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.gallery-carousel-item.animate-out {
    animation: fadeOutScale 0.8s forwards cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .gallery-carousel {
        height: 280px;
    }
    
    .gallery-carousel-caption h5 {
        font-size: 16px;
    }
    
    .gallery-carousel-caption p {
        font-size: 12px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the gallery carousel
    const carouselId = '<?php echo $carouselId; ?>';
    const carousel = document.getElementById(carouselId);
    if (!carousel) return;
    
    const items = carousel.querySelectorAll('.gallery-carousel-item');
    const prevBtn = carousel.querySelector('.gallery-carousel-prev');
    const nextBtn = carousel.querySelector('.gallery-carousel-next');
    const indicators = carousel.querySelectorAll('.gallery-carousel-indicators button');
    
    let currentIndex = 0;
    let interval = null;
    const autoplayDelay = 5000; // 5 seconds
    let isAnimating = false;
    
    // Function to show a specific slide
    function showSlide(index) {
        if (isAnimating) return;
        isAnimating = true;
        
        // Get the current active item
        const currentItem = carousel.querySelector('.gallery-carousel-item.active');
        
        // Remove active class from all indicators
        indicators.forEach(indicator => {
            indicator.classList.remove('active');
        });
        
        // Update current index
        currentIndex = index;
        
        // Add active class to current indicator
        indicators[currentIndex].classList.add('active');
        
        // Animate out the current item
        if (currentItem) {
            currentItem.classList.add('animate-out');
            setTimeout(() => {
                currentItem.classList.remove('active', 'animate-out');
            }, 800);
        }
        
        // Animate in the new item
        setTimeout(() => {
            items[currentIndex].classList.add('active', 'animate-in');
            setTimeout(() => {
                items[currentIndex].classList.remove('animate-in');
                isAnimating = false;
            }, 800);
        }, currentItem ? 400 : 0);
    }
    
    // Function to show next slide
    function nextSlide() {
        let nextIndex = currentIndex + 1;
        if (nextIndex >= items.length) {
            nextIndex = 0;
        }
        showSlide(nextIndex);
    }
    
    // Function to show previous slide
    function prevSlide() {
        let prevIndex = currentIndex - 1;
        if (prevIndex < 0) {
            prevIndex = items.length - 1;
        }
        showSlide(prevIndex);
    }
    
    // Set up event listeners
    prevBtn.addEventListener('click', () => {
        prevSlide();
        resetAutoplay();
    });
    
    nextBtn.addEventListener('click', () => {
        nextSlide();
        resetAutoplay();
    });
    
    // Set up indicator clicks
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => {
            if (currentIndex !== index) {
                showSlide(index);
                resetAutoplay();
            }
        });
    });
    
    // Start autoplay
    function startAutoplay() {
        interval = setInterval(() => {
            if (!isAnimating) {
                nextSlide();
            }
        }, autoplayDelay);
    }
    
    // Reset autoplay
    function resetAutoplay() {
        clearInterval(interval);
        startAutoplay();
    }
    
    // Initialize autoplay
    startAutoplay();
    
    // Pause autoplay when hovering over carousel
    carousel.addEventListener('mouseenter', () => {
        clearInterval(interval);
    });
    
    // Resume autoplay when mouse leaves carousel
    carousel.addEventListener('mouseleave', () => {
        startAutoplay();
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            resetAutoplay();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            resetAutoplay();
        }
    });
    
    // Touch support
    let touchStartX = 0;
    let touchEndX = 0;
    
    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    carousel.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        if (touchEndX < touchStartX - 50) {
            nextSlide();
            resetAutoplay();
        } else if (touchEndX > touchStartX + 50) {
            prevSlide();
            resetAutoplay();
        }
    }
});
</script> 