# Tutor Booking System - Setup Guide

## Project Overview

This is a PHP-based tutoring platform that allows students to search for tutors, book sessions, and manage their schedules. Tutors can accept or decline booking requests, view their schedule, and mark sessions as completed.

## Prerequisites

- [XAMPP](https://www.apachefriends.org/download.html) (Apache, MySQL, PHP) - Version 8.0.0 or higher recommended
- Web browser (Chrome, Firefox, etc.)
- MySQL Database (exported SQL file provided in the project)
- PHP 8.0 or higher with PDO and MySQLi extensions enabled

## Installation Steps

### 1. Install XAMPP

- Download and install XAMPP from [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
- During installation, make sure to select Apache, MySQL, and PHP components

### 2. Clone/Extract Project Files

- Extract or copy all project files to the `htdocs` folder in your XAMPP installation
  - Default location: `C:\xampp\htdocs\project\`
- Ensure the folder structure matches exactly as provided

### 3. Start XAMPP Services

- Open XAMPP Control Panel
- Start Apache and MySQL services
- Make sure both services show a green status

### 4. Import Database

- Open your web browser and navigate to `http://localhost/phpmyadmin/`
- Create a new database named `tutor_db`
- Select the newly created `tutor_db` database
- Click on the "Import" tab
- Choose your exported SQL file of the database
- Click "Go" to import the database
- Verify that the following tables have been created:
  - `bookings` - Stores all session booking information
  - `rates` - Contains tutor rate information
  - `sessions` - Information about tutoring sessions
  - `students` - Student account information
  - `tutor` - Contains tutor details
  - `tutors` - Main tutor account information

### 5. Configure Database Connection

- Check the `db.php` file to ensure database connection parameters match your configuration
- Default configuration:
  ```php
  $host = 'localhost';
  $dbname = 'tutor_db';
  $username = 'root';
  $password = '';
  ```
- If your MySQL configuration uses a different username, password, or hostname, update the file accordingly
- If you see database connection errors, check that your XAMPP MySQL port is set to the default (3306)

### 6. Set Up File Permissions

- Ensure the `uploads` directory and its subdirectories (`profile_images` and `proofs`) have write permissions
- For Windows:
  - Right-click on the `uploads` folder
  - Select Properties → Security tab
  - Click the "Edit" button
  - In the "Permissions for uploads" dialog that appears, click "Add" button
  - For XAMPP, make sure the following users have permissions:
    - "Authenticated Users" - Check boxes for "Modify" and "Write"
    - "SYSTEM" - Should already have full control
  - Click the "Advanced" button at the bottom
  - In the Advanced Security Settings dialog, check "Replace all child object permission entries with inheritable permission entries from this object"
  - Click "Apply" and then "OK" on all open dialogs
- For Linux/Mac:
  - Open terminal and navigate to the project directory
  - Run: `chmod -R 755 uploads`
  - For write permissions: `chmod -R 777 uploads/profile_images uploads/proofs`

### 7. PHP Configuration

- Open XAMPP Control Panel
- Click on "Config" next to Apache
- Select "PHP (php.ini)"
- Ensure these settings are correctly configured:
  - `file_uploads = On`
  - `upload_max_filesize = 8M` (or higher)
  - `post_max_size = 8M` (or higher)
  - Make sure both `extension=mysqli` and `extension=pdo_mysql` are uncommented (no semicolon at the start)
- Save the file and restart Apache

### 7. Access the Application

- Open your web browser
- Navigate to `http://localhost/project/`
- The application login page should now be accessible
- If you see a blank page or PHP errors, check your Apache error logs in `C:\xampp\apache\logs\error.log`

## File Structure Overview

```
project/
├── db.php                 # Database connection settings
├── index.php              # Main entry point
├── login.php              # Login functionality
├── signup.php             # User registration
├── student-dashboard.php  # Student main interface
├── tutor-dashboard.php    # Tutor main interface
├── book-session.php       # Session booking interface
├── tutor-schedule.php     # Tutor's session calendar
├── my-sessions.php        # Student's session view
├── update-booking-status.php # Handles status changes
└── uploads/               # Directory for uploaded files
    ├── profile_images/    # User profile pictures
    └── proofs/            # Payment proof uploads
```

## Database Structure

The application uses a MySQL database with the following tables:

### Main Tables

- **bookings**: Stores all tutoring session bookings
  - Contains session dates, times, status, and relations to students and tutors
- **students**: Student user accounts and profiles
  - Contains student personal info, login credentials, and preferences
- **tutors**: Tutor user accounts and main profile information
  - Contains tutor credentials, contact information, and account settings
- **tutor**: Extended tutor information
  - Contains additional details like bio, experience, and specializations
- **sessions**: Details about individual tutoring sessions
  - Contains session metadata, materials, and outcomes
- **rates**: Tutor pricing and rate information
  - Contains hourly rates, discounts, and payment policies

## User Accounts

The system has two types of user accounts:

- **Student**: Can search for tutors, book sessions, manage bookings
- **Tutor**: Can accept/decline booking requests, manage schedule

## Database Export/Import Guide

### Exporting the Database

If you need to export your database to move it to another system:

1. Open your web browser and navigate to `http://localhost/phpmyadmin/`
2. Select the `tutor_db` database from the left panel
3. Click on the "Export" tab at the top
4. Choose "Quick" export method (default)
5. Select SQL format
6. Click "Go" to download the SQL file

### Importing an Existing Database

1. Place your exported SQL file in an accessible location
2. Follow the instructions in step 4 of the Installation Steps section

## Troubleshooting

### Database Connection Issues

- Verify MySQL is running in XAMPP Control Panel
- Check db.php configuration matches your database settings
- Ensure the database 'tutor_db' exists and has been properly imported
- Check that all required tables are present in the database
- Try connecting with the MySQL console to verify credentials work:
  - In XAMPP Control Panel, click "Shell"
  - Type: `mysql -u root -p` (enter password if you have one set)
  - Type: `SHOW DATABASES;` to confirm the database exists
- If using a non-default MySQL port, update the connection string in db.php

### File Upload Problems

- Check folder permissions on the uploads directory and its subdirectories
- Verify PHP settings in php.ini allow for file uploads
- Check upload_max_filesize and post_max_size values in php.ini
- Temporary fix: You can manually create the missing directories if uploads fail
- Check Apache error logs for specific permission errors

### Page Not Found Errors

- Ensure Apache is running
- Verify that you're accessing the correct URL (http://localhost/project/)
- Check that all files are in the correct location under htdocs/project/
- Check for .htaccess files that might be redirecting traffic
- Verify URL case sensitivity (some servers are case-sensitive)

### Blank Pages or PHP Errors

- Check Apache error logs at `C:\xampp\apache\logs\error.log`
- Enable PHP error display for debugging:
  - Open php.ini
  - Set `display_errors = On`
  - Set `error_reporting = E_ALL`
  - Restart Apache
- Check for syntax errors in PHP files
- Verify PHP version compatibility (this project works best with PHP 7.4+)

### Error with Booking Features

- Verify session functionality is working (PHP sessions must be enabled)
- Check browser console for JavaScript errors
- Ensure database tables have the correct structure
- Verify all form POST/GET variables match what the PHP scripts expect

## Transferring to Another PC

If you need to move the project to another computer, follow these steps:

### 1. Export Your Database

- Open phpMyAdmin (http://localhost/phpmyadmin/)
- Select the `tutor_db` database
- Click the "Export" tab
- Choose "Custom" export method for more options
- Select SQL format
- Enable "Add DROP TABLE" option
- Click "Go" to download the SQL file

### 2. Copy Project Files

- Create a ZIP archive of the entire `project` folder from `C:\xampp\htdocs\project`
- Make sure to include all files and subdirectories, especially the `uploads` folder

### 3. Install XAMPP on the New PC

- Download and install the same version of XAMPP on the new PC
- Start Apache and MySQL services

### 4. Set Up Project on the New PC

- Extract the project ZIP archive to `C:\xampp\htdocs\project` on the new PC
- Follow steps 4-7 from the Installation Steps section above
- Ensure the databases and file permissions are properly set up

### 5. Verify File Paths

- If the new PC has a different directory structure, you might need to update any hardcoded paths
- Check any includes or requires in PHP files that might use absolute paths

### 6. Test All Features

- Test login functionality
- Test file uploads
- Test session booking and management
- Verify that all dynamic content loads correctly

## Security Notes

- This is a development setup. For production, additional security measures should be implemented
- Change default database credentials
- Implement HTTPS
- Review and enhance form validation
- Implement proper input validation and sanitization
- Consider using prepared statements for all database queries
- Add CSRF protection for forms

## Backup Recommendations

- Regularly backup both databases
- Backup the entire project directory, especially user uploads
- Consider automating backups with a script

## Contact

If you encounter any issues or have questions about the setup process, please contact [Your Contact Information].

---

Last Updated: May 10, 2025
