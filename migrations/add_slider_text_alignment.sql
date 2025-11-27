-- Add text_alignment column to slider_images table
-- Run this SQL script to add the text alignment feature

ALTER TABLE slider_images 
ADD COLUMN IF NOT EXISTS text_alignment VARCHAR(20) DEFAULT 'center' AFTER button2_link;

-- Update existing records to have center alignment if NULL
UPDATE slider_images 
SET text_alignment = 'center' 
WHERE text_alignment IS NULL OR text_alignment = '';
