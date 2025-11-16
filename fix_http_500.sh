#!/bin/bash

echo "🔧 Fixing HTTP 500 Error - Order Confirmation"
echo "============================================="

# Check PHP syntax first
echo "🔍 Checking PHP syntax..."
php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php

if [ $? -eq 0 ]; then
    echo "✅ PHP syntax is valid"
    
    # Deploy the fixed file
    echo "📁 Deploying fixed order confirmation..."
    sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php /var/www/html/IAP2.2Dev/order_confirmation.php
    
    # Set permissions
    sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
    sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation.php 2>/dev/null || true
    
    echo "✅ File deployed successfully!"
    
else
    echo "❌ PHP syntax error found. Please check the file."
    exit 1
fi

# Check Apache error log
echo ""
echo "🔍 Checking recent Apache errors..."
if [ -f /var/log/apache2/error.log ]; then
    echo "Last 5 Apache errors:"
    sudo tail -5 /var/log/apache2/error.log
else
    echo "No Apache error log found"
fi

# Test URLs
echo ""
echo "🧪 Test URLs after fix:"
echo "========================"
echo "Order Confirmation: http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo "Alternative:        http://127.0.0.1/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 If still getting HTTP 500:"
echo "1. Check Apache error logs: sudo tail -f /var/log/apache2/error.log"
echo "2. Restart Apache: sudo systemctl restart apache2"
echo "3. Check PHP error logs: sudo tail -f /var/log/php*.log"
echo ""
echo "🎯 HTTP 500 error should now be fixed!"