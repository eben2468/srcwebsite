# Slider Font Size Customization - Feature Guide

## Overview
This feature allows administrators to customize the font sizes for the title and subtitle of each slider image independently. This provides greater flexibility in design, allowing for emphasis on specific slides or adjustments for longer text.

## Database Update Required

### Step 1: Run the SQL Migration
Execute the following SQL script to add the font size columns to your database:

```sql
-- Location: /migrations/add_slider_font_sizes.sql

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
```

**How to run:**
1. Open phpMyAdmin
2. Select your database
3. Navigate to SQL tab
4. Copy and paste the above SQL
5. Click "Execute"

## How to Use

### Adding a New Slider
1. Go to **Settings** → **Slider Images** tab
2. Under "Add New Slider Image", you will see two new fields:
   - **Title Font Size (rem):** Default is 4. Increase for larger text, decrease for smaller.
   - **Subtitle Font Size (rem):** Default is 1.3.
3. Fill in other details and click "Add Slider Image".

### Updating Existing Sliders
1. Go to **Settings** → **Slider Images** tab
2. Scroll to "Existing Slider Images"
3. For the slider you want to update, adjust the values in:
   - **Title Font Size (rem)**
   - **Subtitle Font Size (rem)**
4. Click "Update"

## Technical Details

### Responsive Behavior
The system automatically scales down the font sizes on mobile devices (screens narrower than 768px) to ensure readability and prevent layout breakage.
- **Mobile Title Size:** 60% of the set desktop size.
- **Mobile Subtitle Size:** 80% of the set desktop size.

### CSS Variables
The implementation uses CSS variables (`--title-size`, `--subtitle-size`) applied inline to the slider container, which are then used by the CSS rules. This allows for efficient responsive scaling using `calc()`.

## Troubleshooting

### Font sizes not changing?
1. **Check Database:** Ensure the migration script was run successfully and columns exist.
2. **Clear Cache:** Clear your browser cache to ensure new CSS is loaded.
3. **Check Values:** Ensure you entered valid numbers (e.g., 4.5, 3, 2.1).

---
**Version:** 1.0  
**Last Updated:** 2025-11-27  
**Created by:** Ebenezer Owusu
