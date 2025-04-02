# Habit Tracker - Installation Guide

This guide will help you set up and run the Habit Tracker application on your server or local development environment.

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO PHP Extension
- JSON PHP Extension
- mod_rewrite enabled (for Apache)

## Installation Steps

### 1. Database Setup

1. Create a new MySQL database for the application:
   ```sql
   CREATE DATABASE habit_tracker;
   ```

2. Create a database user and grant privileges:
   ```sql
   CREATE USER 'habit_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON habit_tracker.* TO 'habit_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. Import the database schema:
   ```bash
   mysql -u habit_user -p habit_tracker < database_schema.sql
   ```

### 2. Application Setup

1. Download or clone the application files to your server's web directory:
   ```bash
   git clone https://github.com/your-username/habit-tracker.git
   ```
   
   OR
   
   Upload the application files to your web server using FTP or other file transfer methods.

2. Configure database connection:
   - Open the `config/database.php` file.
   - Update the database connection details:
     ```php
     $host = 'localhost';
     $db_name = 'habit_tracker';
     $username = 'habit_user';
     $password = 'your_password';
     ```

3. Set proper permissions:
   ```bash
   chmod 755 -R /path/to/habit-tracker
   chmod 777 -R /path/to/habit-tracker/logs
   ```

4. Create a logs directory if it doesn't exist:
   ```bash
   mkdir -p /path/to/habit-tracker/logs
   ```

### 3. Web Server Configuration

#### For Apache

1. Make sure mod_rewrite is enabled:
   ```bash
   a2enmod rewrite
   service apache2 restart
   ```

2. The application includes an `.htaccess` file in the root directory. Ensure your Apache configuration allows .htaccess files by setting `AllowOverride All`.

   Example configuration in `/etc/apache2/sites-available/000-default.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot /var/www/habit-tracker

       <Directory /var/www/habit-tracker>
           Options Indexes FollowSymLinks MultiViews
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. Restart Apache:
   ```bash
   service apache2 restart
   ```

#### For Nginx

1. Create a new Nginx server block in `/etc/nginx/sites-available/habit-tracker`:
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;
       root /var/www/habit-tracker;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
       }

       location ~ /\.ht {
           deny all;
       }

       location ~ /(config|controllers|models|utils) {
           deny all;
       }
   }
   ```

2. Enable the site and restart Nginx:
   ```bash
   ln -s /etc/nginx/sites-available/habit-tracker /etc/nginx/sites-enabled/
   service nginx restart
   ```

### 4. Application Assets

1. Download Bootstrap and jQuery:
   - Download Bootstrap 5 from https://getbootstrap.com/
   - Download jQuery from https://jquery.com/download/
   - Extract them to the `assets/css/` and `assets/js/` directories.

2. Create the badges and other image assets:
   ```bash
   mkdir -p assets/images/badges
   ```
   
3. Add placeholder badge images for levels 1-5:
   - level-1.png
   - level-2.png
   - level-3.png
   - level-4.png
   - level-5.png

### 5. First Run

1. Visit your application URL in a web browser (e.g., http://yourdomain.com or http://localhost/habit-tracker).

2. You should be redirected to the login page. Click on "Register" to create your first account.

3. Log in with your new account to access the dashboard.

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Verify database credentials in `config/database.php`.
   - Ensure the MySQL server is running.
   - Check if the database and user exist with correct privileges.

2. **Page Not Found (404) Errors**:
   - Ensure mod_rewrite is enabled (Apache).
   - Check .htaccess file is present and readable.
   - Verify the web server configuration.

3. **Permission Issues**:
   - Make sure the web server user has read/write permissions to the application directory.
   - Ensure the logs directory is writable.

4. **Blank Page or PHP Errors**:
   - Check the PHP error logs.
   - Enable error reporting in development by adding the following to the top of index.php:
     ```php
     ini_set('display_errors', 1);
     ini_set('display_startup_errors', 1);
     error_reporting(E_ALL);
     ```

### Getting Help

If you encounter issues not covered in this guide, please:

1. Check the error logs in the `logs` directory.
2. Review common PHP and MySQL troubleshooting steps.
3. Reach out to the project maintainers through GitHub issues or contact channels provided in the project documentation.

## Security Considerations

- Always keep your PHP, MySQL, and web server software up to date.
- Change default database credentials to strong, unique passwords.
- Limit server access to trusted IPs where possible.
- Consider implementing HTTPS for secure data transmission.
- Regularly back up your database.

## Updates and Maintenance

1. To update the application:
   ```bash
   git pull origin main
   ```
   
2. After updating, check for any database schema changes and apply them if necessary.

3. Clear cache files if any exist:
   ```bash
   rm -rf /path/to/habit-tracker/cache/*
   ```

## License

This Habit Tracker application is released under the [MIT License](LICENSE). See the LICENSE file for more details.
