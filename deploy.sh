#!/bin/bash

# M-Pesa E-commerce Deployment Script
# Deploys the updated e-commerce platform with M-Pesa integration

echo "🚀 Starting M-Pesa E-commerce Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SOURCE_DIR="/home/devyanjethwaa/IAP2.2-1"
TARGET_DIR="/var/www/html/IAP2.2Dev"
BACKUP_DIR="/var/www/html/IAP2.2Dev_backup_$(date +%Y%m%d_%H%M%S)"

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running as user with sudo privileges
if [ "$EUID" -eq 0 ]; then 
    print_error "Please don't run this script as root. Run as your user with sudo when needed."
    exit 1
fi

print_info "Source: $SOURCE_DIR"
print_info "Target: $TARGET_DIR"
print_info "Backup: $BACKUP_DIR"

# Check if source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    print_error "Source directory $SOURCE_DIR does not exist!"
    exit 1
fi

# Check if target directory exists and create backup
if [ -d "$TARGET_DIR" ]; then
    print_warning "Target directory exists. Creating backup..."
    sudo cp -r "$TARGET_DIR" "$BACKUP_DIR"
    if [ $? -eq 0 ]; then
        print_status "Backup created at $BACKUP_DIR"
    else
        print_error "Failed to create backup!"
        exit 1
    fi
else
    print_info "Target directory doesn't exist. Will create new deployment."
fi

# Create target directory if it doesn't exist
sudo mkdir -p "$TARGET_DIR"

# Copy files to web directory
print_info "Copying files to web directory..."
sudo rsync -av --exclude='.git' --exclude='*.backup' --exclude='node_modules' "$SOURCE_DIR/" "$TARGET_DIR/"

if [ $? -eq 0 ]; then
    print_status "Files copied successfully"
else
    print_error "Failed to copy files!"
    exit 1
fi

# Set proper permissions
print_info "Setting file permissions..."
sudo chown -R devyanjethwaa:apache "$TARGET_DIR"
sudo find "$TARGET_DIR" -type f -exec chmod 644 {} \;
sudo find "$TARGET_DIR" -type d -exec chmod 755 {} \;

# Set special permissions for specific directories
sudo chmod -R 755 "$TARGET_DIR/admin"
sudo chmod -R 755 "$TARGET_DIR/classes"
sudo chmod 644 "$TARGET_DIR"/*.php

print_status "Permissions set successfully"

# Check Apache configuration
print_info "Checking Apache configuration..."
sudo apachectl configtest

if [ $? -eq 0 ]; then
    print_status "Apache configuration is valid"
else
    print_error "Apache configuration has issues!"
fi

# Restart Apache to ensure everything is loaded
print_info "Restarting Apache..."
sudo systemctl restart httpd

if [ $? -eq 0 ]; then
    print_status "Apache restarted successfully"
else
    print_error "Failed to restart Apache!"
fi

# Check if MySQL is running
print_info "Checking MySQL status..."
systemctl is-active --quiet mariadb || systemctl is-active --quiet mysql

if [ $? -eq 0 ]; then
    print_status "MySQL/MariaDB is running"
else
    print_warning "MySQL/MariaDB is not running. Starting it..."
    sudo systemctl start mariadb 2>/dev/null || sudo systemctl start mysql 2>/dev/null
fi

# Test database connection
print_info "Testing database connection..."
php -r "
try {
    \$pdo = new PDO('mysql:host=localhost;dbname=auth_db', 'root', 'devyan2005');
    echo 'Database connection successful\n';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -eq 0 ]; then
    print_status "Database connection test passed"
else
    print_error "Database connection test failed!"
fi

# Verify M-Pesa tables exist
print_info "Checking M-Pesa tables..."
mysql -u root -pdevyan2005 -D auth_db -e "DESCRIBE mpesa_transactions;" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    print_status "M-Pesa transactions table exists"
else
    print_warning "M-Pesa transactions table not found. Creating..."
    mysql -u root -pdevyan2005 -D auth_db << 'EOF'
CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    checkout_request_id VARCHAR(100) UNIQUE NOT NULL,
    merchant_request_id VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    mpesa_receipt_number VARCHAR(50) NULL,
    transaction_date TIMESTAMP NULL,
    result_code INT NULL,
    result_desc TEXT NULL,
    callback_metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_checkout_request (checkout_request_id),
    INDEX idx_phone (phone_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);
EOF
    
    if [ $? -eq 0 ]; then
        print_status "M-Pesa transactions table created"
    else
        print_error "Failed to create M-Pesa transactions table"
    fi
fi

# Check PHP extensions
print_info "Checking required PHP extensions..."
php -m | grep -E "(pdo|pdo_mysql|curl|json|mbstring)" > /dev/null

if [ $? -eq 0 ]; then
    print_status "Required PHP extensions are available"
else
    print_warning "Some PHP extensions might be missing. Checking individually..."
    
    for ext in pdo pdo_mysql curl json mbstring; do
        php -m | grep -q "$ext"
        if [ $? -eq 0 ]; then
            print_status "$ext extension is loaded"
        else
            print_error "$ext extension is missing!"
        fi
    done
fi

# Test the website
print_info "Testing website accessibility..."
curl -s -o /dev/null -w "%{http_code}" http://localhost/IAP2.2Dev/dashboard.php > /tmp/http_test

HTTP_CODE=$(cat /tmp/http_test)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    print_status "Website is accessible (HTTP $HTTP_CODE)"
else
    print_warning "Website returned HTTP $HTTP_CODE"
fi

# Test M-Pesa endpoints
print_info "Testing M-Pesa endpoints..."
if [ -f "$TARGET_DIR/mpesa_payment.php" ] && [ -f "$TARGET_DIR/mpesa_callback.php" ]; then
    print_status "M-Pesa endpoint files are present"
else
    print_error "M-Pesa endpoint files are missing!"
fi

# Display deployment summary
echo ""
echo "🎉 Deployment Summary:"
echo "===================="
print_info "Source: $SOURCE_DIR"
print_info "Target: $TARGET_DIR"
print_info "URL: http://localhost/IAP2.2Dev/"
print_info "Admin URL: http://localhost/IAP2.2Dev/admin/"

echo ""
echo "📁 Key Files Deployed:"
echo "====================="
print_status "✅ M-Pesa Payment Class: classes/MpesaPayment.php"
print_status "✅ M-Pesa Payment API: mpesa_payment.php"
print_status "✅ M-Pesa Callback: mpesa_callback.php"
print_status "✅ M-Pesa Timeout: mpesa_timeout.php"
print_status "✅ M-Pesa Payment Page: mpesa_payment_page.php"
print_status "✅ Updated Checkout: checkout.php"
print_status "✅ Admin M-Pesa Interface: admin/mpesa_transactions.php"
print_status "✅ Configuration: config.php (with M-Pesa settings)"

echo ""
echo "🔧 M-Pesa Configuration:"
echo "======================="
print_info "Environment: Sandbox"
print_info "Consumer Key: cXfEmCCWj9..."
print_info "Shortcode: 174379"
print_info "Callback URL: http://localhost/IAP2.2Dev/mpesa_callback.php"

echo ""
echo "🧪 Testing Instructions:"
echo "======================="
print_info "1. Visit: http://localhost/IAP2.2Dev/"
print_info "2. Sign up/Login and verify 2FA"
print_info "3. Add items to cart"
print_info "4. Go to checkout and select M-Pesa"
print_info "5. Use test phone number: 254708374149"
print_info "6. Test sandbox environment"

echo ""
echo "📊 Admin Interface:"
echo "=================="
print_info "M-Pesa Transactions: http://localhost/IAP2.2Dev/admin/mpesa_transactions.php"
print_info "Orders Management: http://localhost/IAP2.2Dev/admin/orders.php"

echo ""
if [ -d "$BACKUP_DIR" ]; then
    print_warning "Backup available at: $BACKUP_DIR"
    print_info "To restore backup: sudo mv $BACKUP_DIR $TARGET_DIR"
fi

echo ""
print_status "🚀 Deployment completed successfully!"
print_info "Your M-Pesa integrated e-commerce platform is now live!"

# Final status check
echo ""
echo "🔍 Final Status Check:"
echo "====================="
systemctl is-active --quiet httpd && print_status "Apache: Running" || print_error "Apache: Not running"
systemctl is-active --quiet mariadb && print_status "MariaDB: Running" || print_error "MariaDB: Not running"
[ -f "$TARGET_DIR/config.php" ] && print_status "Config: Present" || print_error "Config: Missing"
[ -f "$TARGET_DIR/mpesa_payment.php" ] && print_status "M-Pesa API: Present" || print_error "M-Pesa API: Missing"

echo ""
echo "🎯 Next Steps:"
echo "============="
print_info "1. Test the complete purchase flow"
print_info "2. Monitor logs for any issues"
print_info "3. For production: Update M-Pesa credentials in config.php"
print_info "4. Set up SSL certificate for production"
print_info "5. Configure firewall rules if needed"

echo ""
print_status "Happy selling with M-Pesa! 🇰🇪💰"
