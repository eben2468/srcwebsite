# SRC Management System - XAMPP Setup Instructions

This document provides detailed instructions on how to set up and run the SRC Management System using XAMPP.

## Prerequisites

Before you can run the application, you need to install the following software:

1. **XAMPP**: A free and open-source cross-platform web server solution stack package.
   - Download and install XAMPP from [apachefriends.org](https://www.apachefriends.org/)
   - This package includes Apache, MySQL, PHP, and phpMyAdmin

## Installation Steps

### 1. Install XAMPP

Follow the installation instructions for your operating system from the XAMPP website.

### 2. Start Apache Server

1. Open the XAMPP Control Panel
2. Click the "Start" button next to Apache to start the web server
3. (Optional) Click the "Start" button next to MySQL if you plan to use a database in the future

### 3. Deploy the SRC Management System

1. Navigate to the XAMPP installation directory
   - Windows: `C:\xampp\htdocs\`
   - macOS: `/Applications/XAMPP/htdocs/`
   - Linux: `/opt/lampp/htdocs/`

2. Copy the entire SRC Management System folder to the htdocs directory
   - You can also clone the repository directly into the htdocs directory if you have Git installed

3. If you're copying files manually, make sure you include all of these folders and files:
   - `css/` - Contains stylesheets
   - `js/` - Contains JavaScript files
   - `pages_php/` - Contains PHP pages
   - `images/` - Contains images
   - `index.php` - Main entry page

## Accessing the System

1. Open your web browser
2. Enter one of the following URLs:
   - `http://localhost/srcwebsite/` (if you placed the files in a folder named 'srcwebsite')
   - `http://127.0.0.1/srcwebsite/`

This will open the index.php file, which serves as the entry point to the application.

## Project Structure

The project follows a standard PHP web application structure:

```
srcwebsite/
  ├── css/          # CSS stylesheets
  │   └── style.css # Main stylesheet
  ├── js/           # JavaScript files
  │   └── main.js   # JavaScript utilities
  ├── pages_php/    # PHP application pages
  │   ├── auth.php  # Authentication helper
  │   ├── login.php
  │   ├── dashboard.php
  │   ├── events.php
  │   └── ...
  ├── images/       # Image assets
  └── index.php     # Main entry page
```

## Login Credentials

For testing purposes, you can use the following credentials:

- **Admin User**:
  - Email: `admin@example.com`
  - Password: `password`

- **Regular User**:
  - Email: `user@example.com`
  - Password: `password`

## PHP Features

The application uses PHP for server-side functionality:

1. **Session Management**: Secure login/logout functionality using PHP sessions
2. **Role-based Access Control**: Different access levels for admins and regular users
3. **Form Processing**: Server-side form validation and processing
4. **Security Features**: Protection against common web vulnerabilities
5. **Templating**: PHP is used for generating dynamic HTML content

## Browser Compatibility

The system has been tested and works with the following browsers:
- Google Chrome
- Mozilla Firefox
- Microsoft Edge
- Safari

## Troubleshooting

1. **Apache won't start**:
   - Check if another program is using port 80 (like Skype or IIS)
   - Try changing the Apache port in the XAMPP control panel (Config -> Apache)

2. **Access forbidden error**:
   - Check file permissions on the website folder
   - Make sure the Apache user has read access to all files

3. **PHP errors**:
   - Check the Apache error log in the XAMPP control panel
   - Make sure PHP is correctly configured in XAMPP

4. **Login not working**:
   - Make sure PHP sessions are enabled
   - Clear browser cache and cookies
   - Verify PHP version (requires PHP 7.4+)

5. **White screen or 500 error**:
   - Enable error display in php.ini for debugging
   - Check Apache error logs for detailed error messages

## Database Integration

To integrate with a MySQL database:

1. Start MySQL from the XAMPP Control Panel
2. Access phpMyAdmin at `http://localhost/phpmyadmin`
3. Create a new database for the SRC Management System
4. Create the necessary tables (users, events, news, etc.)
5. Update the PHP files to connect to the database using:

```php
$conn = new mysqli("localhost", "username", "password", "srcdb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

## Support

If you encounter any issues or have questions about the SRC Management System, please contact the development team. 