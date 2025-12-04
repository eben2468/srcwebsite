# Database Migrations

This directory contains database migration scripts for the VVU SRC Student Portal.

## Available Migrations

### 1. Add Student Fields to Profiles (2025-12-03)
**File:** `run_migration.php`

Adds three new fields to the `user_profiles` table:
- `student_id` - VARCHAR(50) - Student identification number
- `level` - VARCHAR(20) - Student level (Level 100, 200, 300, or 400)
- `department` - VARCHAR(255) - Student's department/school

#### How to Run:

**Option 1: Run via Browser (Recommended)**
1. Make sure XAMPP MySQL and Apache are running
2. Open your browser and navigate to:
   ```
   http://localhost/vvusrc/database/migrations/run_migration.php
   ```
3. The script will show you the progress and results of the migration
4. Once complete, click "Go to Profile Page" to test the new fields

**Option 2: Run SQL Script Directly**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database
3. Go to the "SQL" tab
4. Copy and paste the contents of `add_student_fields_to_profiles.sql`
5. Click "Go" to execute

## Notes

- The migration scripts are safe to run multiple times - they check if columns already exist before adding them
- Always backup your database before running migrations
- The profile.php page will automatically use these new fields once they are added to the database

## Testing

After running the migration:
1. Navigate to the profile page: `http://localhost/vvusrc/pages_php/profile.php`
2. You should see three new fields:
   - Student ID (text input)
   - Level (dropdown)
   - Department (dropdown)
3. Fill in the fields and save to verify the migration worked correctly
