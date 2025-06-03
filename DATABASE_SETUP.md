# SRC Management System - Database Setup

This document contains instructions for setting up the database for the SRC Management System.

## Prerequisites

- XAMPP installed on your system
- MySQL server running via XAMPP
- PHP enabled in XAMPP

## Setup Instructions

### Method 1: Using the Initialization Script

1. Ensure your XAMPP server is running (Apache and MySQL services)
2. Open a web browser and navigate to `http://localhost/srcwebsite/initialize_database.php`
3. The script will automatically:
   - Create the database
   - Create all required tables
   - Insert initial data (default admin user and settings)
4. If successful, you will see a success message

### Method 2: Manual Setup Using phpMyAdmin

1. Start XAMPP and ensure MySQL service is running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `src_management_system`
4. Select the newly created database
5. Click on the "Import" tab
6. Click "Browse" and select the `src_database.sql` file
7. Click "Go" to import the SQL file
8. The database should now be set up with all tables and initial data

### Method 3: Manual SQL Execution

1. Start XAMPP and ensure MySQL service is running
2. Open a terminal/command prompt
3. Navigate to the MySQL bin directory (e.g., `C:\xampp\mysql\bin`)
4. Run the following command to import the SQL file:
   ```
   mysql -u root -p < path\to\srcwebsite\src_database.sql
   ```
5. If prompted for a password, press Enter (default XAMPP MySQL has no password)

## Database Structure

The database includes the following tables:

1. **users** - Stores user account information
2. **departments** - Stores SRC departments
3. **portfolios** - Stores portfolio information
4. **events** - Stores event information
5. **event_registrations** - Tracks event registrations
6. **news** - Stores news/announcements
7. **documents** - Stores uploaded documents
8. **minutes** - Stores meeting minutes
9. **elections** - Stores election information
10. **election_positions** - Stores positions for each election
11. **election_candidates** - Stores candidates for each position
12. **votes** - Tracks votes cast in elections
13. **budget** - Stores budget information
14. **budget_categories** - Stores budget categories
15. **budget_transactions** - Tracks budget transactions
16. **reports** - Stores reports
17. **feedback** - Stores user feedback
18. **settings** - Stores system settings

## Default Admin User

After setup, you can log in with the following credentials:

- Username: `admin`
- Password: `admin123`

**Important**: It is strongly recommended to change the default admin password after the first login.

## Connection in PHP Files

To connect to the database in PHP files, include the `db_config.php` file:

```php
<?php
require_once 'path/to/db_config.php';

// Now you can use the connection and helper functions
$users = fetchAll("SELECT * FROM users WHERE status = ?", ['active']);
?>
```

## Helper Functions

The `db_config.php` file provides several helper functions:

- `executeQuery($sql, $params, $types)` - Execute a SQL query with parameters
- `fetchAll($sql, $params, $types)` - Fetch all rows from a query
- `fetchOne($sql, $params, $types)` - Fetch a single row from a query
- `insert($sql, $params, $types)` - Insert data and return the last inserted ID
- `update($sql, $params, $types)` - Update data and return affected rows
- `delete($sql, $params, $types)` - Delete data and return affected rows

## Troubleshooting

If you encounter issues with database setup:

1. Ensure MySQL service is running in XAMPP
2. Check that the database credentials in `db_config.php` match your XAMPP setup
3. Verify that the SQL file is in the correct location
4. Check for any error messages during initialization
5. Make sure you have sufficient permissions to create databases in MySQL 