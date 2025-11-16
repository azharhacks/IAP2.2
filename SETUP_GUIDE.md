# 🚀 Setup Guide - SMARTDUKA E-Commerce Platform

This comprehensive guide will walk you through setting up the SMARTDUKA e-commerce platform from scratch.

## 📋 Prerequisites

### System Requirements
- **Operating System**: Linux (Ubuntu/CentOS/Debian) or Windows with XAMPP
- **PHP**: Version 8.0 or higher
- **MySQL/MariaDB**: Version 5.7 or higher
- **Apache**: Version 2.4 or higher
- **Memory**: Minimum 2GB RAM
- **Storage**: At least 1GB free space

### Required Software
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install apache2 mysql-server php8.1 php8.1-mysql php8.1-curl php8.1-json

# CentOS/RHEL
sudo yum install httpd mysql-server php php-mysql php-curl php-json
```

---

## 🛠️ Installation Steps

### Step 1: Download and Extract Files
```bash
# Navigate to your web root
cd /var/www/html/

# Create project directory
sudo mkdir IAP2.2Dev
cd IAP2.2Dev

# Copy project files (adjust path as needed)
sudo cp -r /home/devyanjethwaa/IAP2.2-1/* .
```

### Step 2: Set Proper Permissions
```bash
# Set ownership to web server user
sudo chown -R www-data:www-data /var/www/html/IAP2.2Dev/

# Set proper file permissions
sudo chmod 644 *.php
sudo chmod 755 /var/www/html/IAP2.2Dev/
```

### Step 3: Database Setup

#### Create Database
```sql
-- Connect to MySQL as root
mysql -u root -p

-- Create database
CREATE DATABASE smartduka_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'password' with strong password)
CREATE USER 'smartduka_user'@'localhost' IDENTIFIED BY 'your_strong_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON smartduka_db.* TO 'smartduka_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Import Database Schema
```sql
-- Use the database
USE smartduka_db;

-- Create essential tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    sku VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'mpesa',
    payment_status VARCHAR(20) DEFAULT 'pending',
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_state VARCHAR(100),
    shipping_postal_code VARCHAR(20),
    shipping_country VARCHAR(100) DEFAULT 'Kenya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE mpesa_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    phone_number VARCHAR(20),
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Insert sample data
INSERT INTO products (name, description, price, sku) VALUES
('Sample Product 1', 'Description for product 1', 299.99, 'SKU001'),
('Sample Product 2', 'Description for product 2', 199.99, 'SKU002'),
('Sample Product 3', 'Description for product 3', 399.99, 'SKU003');

-- Create test user (password: 'password123')
INSERT INTO users (username, email, password_hash) VALUES
('testuser', 'test@smartduka.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create sample order
INSERT INTO orders (user_id, order_number, total_amount, subtotal, status, payment_status) VALUES
(1, 'ORD-20251116165528-3525', 511.60, 511.60, 'confirmed', 'paid');

INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price) VALUES
(1, 1, 'Sample Product 1', 1, 299.99),
(1, 2, 'Sample Product 2', 1, 211.61);
```

### Step 4: Configure Database Connection

Edit `config.php`:
```php
<?php
// Database configuration
$host = 'localhost';
$dbname = 'smartduka_db';
$username = 'smartduka_user';
$password = 'your_strong_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Site configuration
define('SITE_URL', 'http://localhost/IAP2.2Dev/');
define('SITE_NAME', 'SMARTDUKA');
?>
```

---

## 🔧 Apache Configuration

### Enable Required Modules
```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### Create Virtual Host (Optional)
```apache
# Create /etc/apache2/sites-available/smartduka.conf
<VirtualHost *:80>
    ServerName smartduka.local
    DocumentRoot /var/www/html/IAP2.2Dev
    
    <Directory /var/www/html/IAP2.2Dev>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/smartduka_error.log
    CustomLog ${APACHE_LOG_DIR}/smartduka_access.log combined
</VirtualHost>
```

```bash
# Enable site
sudo a2ensite smartduka.conf
sudo systemctl reload apache2

# Add to /etc/hosts (optional)
echo "127.0.0.1 smartduka.local" | sudo tee -a /etc/hosts
```

---

## 🧪 Testing the Installation

### Step 1: Test Basic Functionality
```bash
# Test Apache is running
curl http://localhost/IAP2.2Dev/

# Test PHP syntax
php -l /var/www/html/IAP2.2Dev/config.php
```

### Step 2: Test Database Connection
Create a test file `db_test.php`:
```php
<?php
require_once 'config.php';
echo "Database connection successful!";
?>
```

Access: `http://localhost/IAP2.2Dev/db_test.php`

### Step 3: Test Order Confirmation
Visit the order confirmation page:
```
http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525
```

---

## 🚀 Deployment Scripts

### Use Automated Deployment
```bash
# Make scripts executable
chmod +x deploy_fixed_order_confirmation.sh
chmod +x deploy_pdf_fix.sh

# Run deployment
./deploy_fixed_order_confirmation.sh
./deploy_pdf_fix.sh
```

---

## 🔒 Security Configuration

### Step 1: Secure Database
```sql
-- Remove test databases
DROP DATABASE IF EXISTS test;

-- Secure root account
ALTER USER 'root'@'localhost' IDENTIFIED BY 'strong_root_password';

-- Remove anonymous users
DELETE FROM mysql.user WHERE User='';

-- Flush privileges
FLUSH PRIVILEGES;
```

### Step 2: Configure PHP Security
Edit `/etc/php/8.1/apache2/php.ini`:
```ini
; Hide PHP version
expose_php = Off

; Limit file uploads
file_uploads = On
max_file_size = 2M
max_execution_time = 30

; Session security
session.cookie_httponly = 1
session.use_strict_mode = 1
session.cookie_secure = 1  ; Enable for HTTPS
```

### Step 3: Apache Security Headers
Create `.htaccess` in web root:
```apache
# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Hide server signature
ServerTokens Prod
ServerSignature Off

# Disable directory browsing
Options -Indexes
```

---

## 📧 Email Configuration (Optional)

For order confirmations and 2FA:

### Step 1: Install Mail Server
```bash
sudo apt install postfix mailutils
```

### Step 2: Configure SMTP (if using external provider)
```php
// Add to config.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
```

---

## 💳 M-Pesa Configuration

### Step 1: Get M-Pesa Credentials
1. Register with Safaricom M-Pesa
2. Get Consumer Key and Consumer Secret
3. Get shortcode and passkey

### Step 2: Configure M-Pesa Settings
```php
// Add to config.php
define('MPESA_CONSUMER_KEY', 'your_consumer_key');
define('MPESA_CONSUMER_SECRET', 'your_consumer_secret');
define('MPESA_SHORTCODE', 'your_shortcode');
define('MPESA_PASSKEY', 'your_passkey');
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/mpesa_callback.php');
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. HTTP 500 Error
```bash
# Check PHP syntax
php -l problematic_file.php

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check PHP error logs
sudo tail -f /var/log/php*.log
```

#### 2. Database Connection Issues
```bash
# Test MySQL connection
mysql -u smartduka_user -p smartduka_db

# Check MySQL status
sudo systemctl status mysql

# Restart MySQL
sudo systemctl restart mysql
```

#### 3. Permission Issues
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/html/IAP2.2Dev/
sudo chmod -R 644 /var/www/html/IAP2.2Dev/*.php
sudo chmod 755 /var/www/html/IAP2.2Dev/
```

#### 4. PDF Generation Issues
```bash
# Test PDF page directly
curl http://localhost/IAP2.2Dev/order_confirmation_pdf.php?order=ORD-20251116165528-3525

# Check browser console for JavaScript errors
# Ensure popups are allowed for PDF generation
```

### Performance Optimization

#### Enable PHP OPcache
```ini
; Add to php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

#### Database Optimization
```sql
-- Add indexes for better performance
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_order_number ON orders(order_number);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_mpesa_order_id ON mpesa_transactions(order_id);
```

---

## 🔄 Backup and Maintenance

### Database Backup
```bash
# Create backup
mysqldump -u smartduka_user -p smartduka_db > backup_$(date +%Y%m%d).sql

# Restore backup
mysql -u smartduka_user -p smartduka_db < backup_20231116.sql
```

### File Backup
```bash
# Create file backup
tar -czf smartduka_files_$(date +%Y%m%d).tar.gz /var/www/html/IAP2.2Dev/
```

### Regular Maintenance
```bash
# Update system packages
sudo apt update && sudo apt upgrade

# Clear old log files
sudo logrotate /etc/logrotate.conf

# Check disk space
df -h
```

---

## 📞 Getting Help

### Log Files to Check
- Apache: `/var/log/apache2/error.log`
- PHP: `/var/log/php_errors.log`
- MySQL: `/var/log/mysql/error.log`

### Useful Commands
```bash
# Check service status
sudo systemctl status apache2
sudo systemctl status mysql

# Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql

# Check PHP configuration
php -m  # List loaded modules
php --ini  # Show configuration files
```

### Support Resources
- **Documentation**: `FILES_DOCUMENTATION.md`
- **Project Overview**: `README.md`
- **GitHub Issues**: Create issues for bugs
- **Email Support**: support@smartduka.ke

---

**🎉 Congratulations! Your SMARTDUKA e-commerce platform should now be fully operational.**

Test all functionality including user registration, product browsing, order placement, and PDF generation to ensure everything works correctly.