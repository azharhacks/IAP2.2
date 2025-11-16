# 🚀 Deployment & Maintenance Guide - SMARTDUKA

This guide covers deployment procedures, maintenance tasks, and troubleshooting for the SMARTDUKA e-commerce platform.

## ✅ Pre-Deployment Checklist

### 🔍 Code Quality Checks
- [ ] **PHP Syntax Validation**
  ```bash
  find . -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
  ```

- [ ] **Database Connection Test**
  ```bash
  php -r "require 'config.php'; echo 'Database connection: OK\n';"
  ```

- [ ] **File Permissions Check**
  ```bash
  ls -la *.php  # Should show 644 permissions
  ```

- [ ] **Security Headers Verification**
  ```bash
  curl -I http://localhost/IAP2.2Dev/ | grep -E "(X-Content-Type|X-Frame|X-XSS)"
  ```

### 🔒 Security Checklist
- [ ] **Strong Database Passwords** - Minimum 12 characters, mixed case, numbers, symbols
- [ ] **SSL Certificate** - HTTPS enabled for production
- [ ] **Database User Privileges** - Limited to necessary permissions only
- [ ] **File Upload Restrictions** - Proper file type validation
- [ ] **Session Security** - Secure session configuration
- [ ] **Input Sanitization** - XSS and SQL injection prevention
- [ ] **Error Handling** - No sensitive information in error messages

### 🛠️ Configuration Checklist
- [ ] **Environment Variables** - Production vs development settings
- [ ] **M-Pesa Credentials** - Valid API keys and endpoints
- [ ] **Email Configuration** - SMTP settings for notifications
- [ ] **Backup Systems** - Automated backup procedures
- [ ] **Monitoring Setup** - Log monitoring and alerts
- [ ] **Performance Optimization** - Caching and database optimization

---

## 🔄 Deployment Procedures

### 📁 File Deployment

#### Method 1: Manual Deployment
```bash
# 1. Create deployment directory
sudo mkdir -p /var/www/html/smartduka-production

# 2. Copy files (excluding development files)
sudo rsync -av --exclude='.git' --exclude='*.log' \
    /home/devyanjethwaa/IAP2.2-1/ \
    /var/www/html/smartduka-production/

# 3. Set ownership and permissions
sudo chown -R www-data:www-data /var/www/html/smartduka-production/
sudo find /var/www/html/smartduka-production/ -type f -name "*.php" -exec chmod 644 {} \;
sudo find /var/www/html/smartduka-production/ -type d -exec chmod 755 {} \;
```

#### Method 2: Automated Deployment Script
```bash
#!/bin/bash
# deploy_production.sh

set -e  # Exit on any error

DEPLOY_DIR="/var/www/html/smartduka-production"
SOURCE_DIR="/home/devyanjethwaa/IAP2.2-1"
BACKUP_DIR="/backups/smartduka-$(date +%Y%m%d-%H%M%S)"

echo "🚀 Starting SMARTDUKA Production Deployment"
echo "=========================================="

# 1. Create backup of current production
if [ -d "$DEPLOY_DIR" ]; then
    echo "📦 Creating backup..."
    sudo mkdir -p /backups
    sudo cp -r "$DEPLOY_DIR" "$BACKUP_DIR"
    echo "✅ Backup created: $BACKUP_DIR"
fi

# 2. Run pre-deployment tests
echo "🧪 Running pre-deployment tests..."
cd "$SOURCE_DIR"

# Check PHP syntax
find . -name "*.php" -exec php -l {} \; > /tmp/syntax_check.log 2>&1
if grep -q "Parse error" /tmp/syntax_check.log; then
    echo "❌ PHP syntax errors found!"
    cat /tmp/syntax_check.log
    exit 1
fi
echo "✅ PHP syntax check passed"

# Test database connection
php -r "require 'config.php';" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "✅ Database connection test passed"
else
    echo "❌ Database connection failed!"
    exit 1
fi

# 3. Deploy files
echo "📁 Deploying files..."
sudo mkdir -p "$DEPLOY_DIR"
sudo rsync -av \
    --exclude='.git' \
    --exclude='*.log' \
    --exclude='tmp/' \
    --exclude='tests/' \
    "$SOURCE_DIR/" "$DEPLOY_DIR/"

# 4. Set permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data "$DEPLOY_DIR"
sudo find "$DEPLOY_DIR" -type f -name "*.php" -exec chmod 644 {} \;
sudo find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;

# 5. Restart services
echo "♻️ Restarting services..."
sudo systemctl reload apache2
sudo systemctl status apache2 --no-pager -l

# 6. Run post-deployment tests
echo "🧪 Running post-deployment tests..."
curl -s -o /dev/null -w "%{http_code}" http://localhost/smartduka-production/ | grep -q "200"
if [ $? -eq 0 ]; then
    echo "✅ Website is responding"
else
    echo "❌ Website not responding!"
    exit 1
fi

echo "🎉 Deployment completed successfully!"
echo "📊 Deployment summary:"
echo "   Source: $SOURCE_DIR"
echo "   Target: $DEPLOY_DIR"
echo "   Backup: $BACKUP_DIR"
echo "   Time: $(date)"
```

### 🗄️ Database Migration

#### Production Database Setup
```sql
-- Create production database
CREATE DATABASE smartduka_production 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create production user
CREATE USER 'smartduka_prod'@'localhost' 
IDENTIFIED BY 'secure_production_password_here';

-- Grant privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON smartduka_production.* 
TO 'smartduka_prod'@'localhost';
FLUSH PRIVILEGES;
```

#### Database Migration Script
```bash
#!/bin/bash
# migrate_database.sh

SOURCE_DB="smartduka_dev"
TARGET_DB="smartduka_production"
BACKUP_FILE="/backups/db_backup_$(date +%Y%m%d_%H%M%S).sql"

echo "🗄️ Starting Database Migration"
echo "=============================="

# 1. Backup current production database
echo "📦 Backing up production database..."
mysqldump -u root -p "$TARGET_DB" > "$BACKUP_FILE" 2>/dev/null || echo "No existing production DB"
echo "✅ Backup saved: $BACKUP_FILE"

# 2. Export development database
echo "📤 Exporting development database..."
mysqldump -u root -p --single-transaction --routines --triggers "$SOURCE_DB" > /tmp/dev_export.sql

# 3. Import to production
echo "📥 Importing to production database..."
mysql -u root -p "$TARGET_DB" < /tmp/dev_export.sql

# 4. Clean up sensitive development data
echo "🧹 Cleaning up development data..."
mysql -u root -p "$TARGET_DB" << EOF
-- Remove test users (keep admin)
DELETE FROM users WHERE username LIKE 'test%';

-- Reset order numbers for production
UPDATE orders SET order_number = CONCAT('PROD-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '-', LPAD(id, 4, '0'));

-- Clear development logs
TRUNCATE TABLE system_logs;

-- Update configuration for production
UPDATE settings SET value = 'production' WHERE key = 'environment';
EOF

echo "✅ Database migration completed!"
```

---

## 🔧 Maintenance Procedures

### 📅 Daily Maintenance Tasks

#### Automated Daily Script
```bash
#!/bin/bash
# daily_maintenance.sh

LOG_FILE="/var/log/smartduka_maintenance.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$DATE] Starting daily maintenance..." >> "$LOG_FILE"

# 1. Database backup
echo "📦 Creating database backup..."
mysqldump -u root -p smartduka_production > "/backups/daily_backup_$(date +%Y%m%d).sql"
if [ $? -eq 0 ]; then
    echo "[$DATE] Database backup: SUCCESS" >> "$LOG_FILE"
else
    echo "[$DATE] Database backup: FAILED" >> "$LOG_FILE"
fi

# 2. Clean old log files
echo "🧹 Cleaning old logs..."
find /var/log -name "*smartduka*" -mtime +30 -delete
find /backups -name "daily_backup_*.sql" -mtime +7 -delete

# 3. Check disk space
echo "💾 Checking disk space..."
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "[$DATE] WARNING: Disk usage at ${DISK_USAGE}%" >> "$LOG_FILE"
    # Send alert email
    echo "Disk usage critical: ${DISK_USAGE}%" | mail -s "SMARTDUKA Disk Alert" admin@smartduka.ke
fi

# 4. Check service status
echo "🔍 Checking services..."
if ! systemctl is-active --quiet apache2; then
    echo "[$DATE] ERROR: Apache is down!" >> "$LOG_FILE"
    systemctl restart apache2
fi

if ! systemctl is-active --quiet mysql; then
    echo "[$DATE] ERROR: MySQL is down!" >> "$LOG_FILE"
    systemctl restart mysql
fi

# 5. Update system packages (security only)
echo "🔄 Updating security packages..."
apt list --upgradable | grep -i security | wc -l > /tmp/security_updates
SECURITY_COUNT=$(cat /tmp/security_updates)
if [ "$SECURITY_COUNT" -gt 0 ]; then
    echo "[$DATE] Installing $SECURITY_COUNT security updates..." >> "$LOG_FILE"
    DEBIAN_FRONTEND=noninteractive apt-get -y upgrade
fi

echo "[$DATE] Daily maintenance completed." >> "$LOG_FILE"
```

#### Setup Cron Job
```bash
# Add to crontab (crontab -e)
0 2 * * * /usr/local/bin/daily_maintenance.sh
0 6 * * 0 /usr/local/bin/weekly_maintenance.sh
0 4 1 * * /usr/local/bin/monthly_maintenance.sh
```

### 📊 Weekly Maintenance Tasks

```bash
#!/bin/bash
# weekly_maintenance.sh

echo "📊 Starting weekly maintenance..."

# 1. Database optimization
echo "⚡ Optimizing database..."
mysql -u root -p smartduka_production << EOF
ANALYZE TABLE users, products, orders, order_items, mpesa_transactions;
OPTIMIZE TABLE users, products, orders, order_items, mpesa_transactions;
EOF

# 2. Generate performance report
echo "📈 Generating performance report..."
mysql -u root -p smartduka_production << EOF > /tmp/weekly_report.txt
SELECT 
    'Orders This Week' as metric,
    COUNT(*) as value
FROM orders 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);

SELECT 
    'Revenue This Week' as metric,
    CONCAT('KSh ', FORMAT(SUM(total_amount), 2)) as value
FROM orders 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
AND payment_status = 'paid';

SELECT 
    'Top Product This Week' as metric,
    p.name as value
FROM order_items oi
JOIN products p ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY p.id
ORDER BY SUM(oi.quantity) DESC
LIMIT 1;
EOF

# Email report to admin
mail -s "SMARTDUKA Weekly Report" admin@smartduka.ke < /tmp/weekly_report.txt

# 3. Clear temporary files
echo "🧹 Clearing temporary files..."
find /tmp -name "*smartduka*" -mtime +1 -delete
find /var/tmp -name "*smartduka*" -mtime +1 -delete

# 4. Check SSL certificate expiry
echo "🔒 Checking SSL certificate..."
CERT_EXPIRY=$(openssl x509 -in /etc/ssl/certs/smartduka.crt -noout -dates | grep notAfter | cut -d= -f2)
EXPIRY_TIMESTAMP=$(date -d "$CERT_EXPIRY" +%s)
CURRENT_TIMESTAMP=$(date +%s)
DAYS_UNTIL_EXPIRY=$(( ($EXPIRY_TIMESTAMP - $CURRENT_TIMESTAMP) / 86400 ))

if [ "$DAYS_UNTIL_EXPIRY" -lt 30 ]; then
    echo "SSL certificate expires in $DAYS_UNTIL_EXPIRY days!" | mail -s "SSL Certificate Alert" admin@smartduka.ke
fi

echo "✅ Weekly maintenance completed."
```

### 🗓️ Monthly Maintenance Tasks

```bash
#!/bin/bash
# monthly_maintenance.sh

echo "🗓️ Starting monthly maintenance..."

# 1. Full system backup
echo "💾 Creating full system backup..."
tar -czf "/backups/full_backup_$(date +%Y%m).tar.gz" \
    /var/www/html/smartduka-production \
    /etc/apache2/sites-available/smartduka.conf \
    /etc/ssl/certs/smartduka.*

# 2. Database performance analysis
echo "📊 Analyzing database performance..."
mysql -u root -p smartduka_production << EOF > /tmp/db_analysis.txt
SELECT 
    TABLE_NAME as 'Table',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Size (MB)',
    TABLE_ROWS as 'Rows'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'smartduka_production'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

SHOW PROCESSLIST;

SELECT * FROM INFORMATION_SCHEMA.INNODB_TRX;
EOF

# 3. Security audit
echo "🔒 Running security audit..."
# Check for suspicious files
find /var/www/html/smartduka-production -name "*.php" -exec grep -l "eval\|base64_decode\|shell_exec" {} \; > /tmp/suspicious_files.txt

# Check file permissions
find /var/www/html/smartduka-production -type f -perm /o+w > /tmp/world_writable.txt

# 4. Performance optimization
echo "⚡ Optimizing performance..."
# Clear PHP OPcache
service apache2 reload

# Rebuild database indexes
mysql -u root -p smartduka_production << EOF
ALTER TABLE orders ENGINE=InnoDB;
ALTER TABLE order_items ENGINE=InnoDB;
ALTER TABLE products ENGINE=InnoDB;
EOF

# 5. Generate monthly report
echo "📈 Generating monthly report..."
php /var/www/html/smartduka-production/generate_monthly_report.php

echo "✅ Monthly maintenance completed."
```

---

## 🚨 Monitoring & Alerts

### 📊 System Monitoring Script

```bash
#!/bin/bash
# monitor_system.sh

ALERT_EMAIL="admin@smartduka.ke"
LOG_FILE="/var/log/smartduka_monitoring.log"

# Function to send alert
send_alert() {
    local subject="$1"
    local message="$2"
    echo "[$(date)] ALERT: $subject" >> "$LOG_FILE"
    echo "$message" | mail -s "SMARTDUKA ALERT: $subject" "$ALERT_EMAIL"
}

# Check website availability
check_website() {
    local response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/smartduka-production/)
    if [ "$response" != "200" ]; then
        send_alert "Website Down" "Website returned HTTP $response"
    fi
}

# Check database connectivity
check_database() {
    mysql -u smartduka_prod -p smartduka_production -e "SELECT 1" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        send_alert "Database Connection Failed" "Cannot connect to production database"
    fi
}

# Check disk space
check_disk_space() {
    local usage=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')
    if [ "$usage" -gt 85 ]; then
        send_alert "Disk Space Critical" "Disk usage: ${usage}%"
    fi
}

# Check memory usage
check_memory() {
    local mem_usage=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    if [ "$mem_usage" -gt 90 ]; then
        send_alert "Memory Usage High" "Memory usage: ${mem_usage}%"
    fi
}

# Check Apache processes
check_apache() {
    local apache_procs=$(pgrep apache2 | wc -l)
    if [ "$apache_procs" -lt 2 ]; then
        send_alert "Apache Issues" "Only $apache_procs Apache processes running"
    fi
}

# Check for failed login attempts
check_security() {
    local failed_logins=$(grep "authentication failure" /var/log/auth.log | grep "$(date '+%b %d')" | wc -l)
    if [ "$failed_logins" -gt 20 ]; then
        send_alert "Security Alert" "$failed_logins failed login attempts today"
    fi
}

# Run all checks
echo "[$(date)] Running system monitoring checks..." >> "$LOG_FILE"
check_website
check_database
check_disk_space
check_memory
check_apache
check_security
```

### 📧 Log Analysis and Alerting

```bash
#!/bin/bash
# analyze_logs.sh

# Check Apache error logs for critical issues
check_apache_errors() {
    local error_count=$(grep "$(date '+%Y-%m-%d')" /var/log/apache2/error.log | wc -l)
    local critical_errors=$(grep -E "segfault|fatal|critical" /var/log/apache2/error.log | grep "$(date '+%Y-%m-%d')" | wc -l)
    
    if [ "$critical_errors" -gt 0 ]; then
        echo "Critical Apache errors found: $critical_errors" | mail -s "Apache Critical Errors" admin@smartduka.ke
    fi
}

# Check PHP error logs
check_php_errors() {
    local php_errors=$(grep "$(date '+Y-m-d')" /var/log/php_errors.log 2>/dev/null | wc -l)
    
    if [ "$php_errors" -gt 10 ]; then
        tail -20 /var/log/php_errors.log | mail -s "High PHP Error Rate" admin@smartduka.ke
    fi
}

# Check MySQL slow query log
check_mysql_performance() {
    if [ -f /var/log/mysql/mysql-slow.log ]; then
        local slow_queries=$(grep "$(date '+%y%m%d')" /var/log/mysql/mysql-slow.log | wc -l)
        
        if [ "$slow_queries" -gt 5 ]; then
            echo "Slow queries detected: $slow_queries" | mail -s "MySQL Performance Alert" admin@smartduka.ke
        fi
    fi
}

# Run log analysis
check_apache_errors
check_php_errors
check_mysql_performance
```

---

## 🔧 Troubleshooting Guide

### 🚨 Common Issues and Solutions

#### Website Not Loading (HTTP 500)
```bash
# 1. Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# 2. Check PHP syntax
find /var/www/html/smartduka-production -name "*.php" -exec php -l {} \;

# 3. Check file permissions
sudo chown -R www-data:www-data /var/www/html/smartduka-production/
sudo chmod -R 644 /var/www/html/smartduka-production/*.php

# 4. Restart Apache
sudo systemctl restart apache2
```

#### Database Connection Issues
```bash
# 1. Check MySQL status
sudo systemctl status mysql

# 2. Test connection
mysql -u smartduka_prod -p smartduka_production -e "SELECT 1"

# 3. Check user privileges
mysql -u root -p -e "SHOW GRANTS FOR 'smartduka_prod'@'localhost'"

# 4. Reset password if needed
mysql -u root -p -e "ALTER USER 'smartduka_prod'@'localhost' IDENTIFIED BY 'new_password'"
```

#### M-Pesa Payment Failures
```bash
# 1. Check M-Pesa callback logs
tail -f /var/log/mpesa_callbacks.log

# 2. Verify M-Pesa credentials
curl -X POST "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials" \
     -H "Authorization: Basic $(echo -n 'consumer_key:consumer_secret' | base64)"

# 3. Check callback URL accessibility
curl -X POST "https://yourdomain.com/mpesa_callback.php" \
     -H "Content-Type: application/json" \
     -d '{"test": "callback"}'
```

#### High Server Load
```bash
# 1. Check running processes
top -c

# 2. Check Apache connections
ss -tuln | grep :80

# 3. Check database connections
mysql -u root -p -e "SHOW PROCESSLIST"

# 4. Optimize database if needed
mysql -u root -p smartduka_production -e "OPTIMIZE TABLE orders, products, order_items"
```

### 🔄 Recovery Procedures

#### Database Recovery
```bash
# 1. Stop applications
sudo systemctl stop apache2

# 2. Restore from backup
mysql -u root -p smartduka_production < /backups/latest_backup.sql

# 3. Restart services
sudo systemctl start apache2
sudo systemctl start mysql
```

#### File System Recovery
```bash
# 1. Restore files from backup
sudo rm -rf /var/www/html/smartduka-production/*
sudo tar -xzf /backups/full_backup_latest.tar.gz -C /

# 2. Set permissions
sudo chown -R www-data:www-data /var/www/html/smartduka-production/
sudo chmod -R 644 /var/www/html/smartduka-production/*.php

# 3. Restart services
sudo systemctl restart apache2
```

---

## 📈 Performance Optimization

### 🚀 Apache Optimization

```apache
# /etc/apache2/conf-available/smartduka-performance.conf

# Enable compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</Location>

# Enable caching
LoadModule expires_module modules/mod_expires.so
ExpiresActive On
ExpiresByType text/css "access plus 1 month"
ExpiresByType application/javascript "access plus 1 month"
ExpiresByType image/png "access plus 1 month"
ExpiresByType image/jpg "access plus 1 month"
ExpiresByType image/jpeg "access plus 1 month"
ExpiresByType image/gif "access plus 1 month"

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
```

### 🗄️ MySQL Optimization

```sql
-- my.cnf optimization
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 64M
max_connections = 100
thread_cache_size = 16
```

### 🐘 PHP Optimization

```ini
; php.ini optimization
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1

memory_limit=256M
max_execution_time=60
max_input_vars=3000
post_max_size=32M
upload_max_filesize=32M
```

---

This comprehensive maintenance guide ensures your SMARTDUKA platform runs smoothly and securely in production. Regular execution of these procedures will maintain optimal performance and prevent issues before they occur.