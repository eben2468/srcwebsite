-- Add font size columns to slider_images table
-- Run this SQL script to add the font size customization feature

ALTER TABLE slider_images 
ADD COLUMN IF NOT EXISTS title_font_size DECIMAL(4,1) DEFAULT 4.0 AFTER text_alignment,
ADD COLUMN IF NOT EXISTS subtitle_font_size DECIMAL(4,1) DEFAULT 1.3 AFTER title_font_size;

-- Update existing records to have default font sizes if NULL
UPDATE slider_images 
SET title_font_size = 4.0 
WHERE title_font_size IS NULL;

UPDATE slider_images 
SET subtitle_font_size = 1.3 
WHERE subtitle_font_size IS NULL;
