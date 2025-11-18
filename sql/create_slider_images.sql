-- Create table for slider images
CREATE TABLE IF NOT EXISTS slider_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    button1_text VARCHAR(100),
    button1_link VARCHAR(255),
    button2_text VARCHAR(100),
    button2_link VARCHAR(255),
    slide_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default slider images
INSERT INTO slider_images (image_path, title, subtitle, button1_text, button1_link, button2_text, button2_link, slide_order, is_active) VALUES
('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1920', 'Valley View University', 'Students'' Representative Council', 'Student Login', 'pages_php/login.php', 'Learn More', '#about', 1, 1),
('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1920', 'Your Voice Matters', 'Empowering Students Through Representation', 'Latest News', '#news', 'Upcoming Events', '#events', 2, 1),
('https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1920', 'Excellence in Leadership', 'Building Tomorrow''s Leaders Today', 'Join Us', 'pages_php/login.php', 'Contact Us', '#contact', 3, 1);
