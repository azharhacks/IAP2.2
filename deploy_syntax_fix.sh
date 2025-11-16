#!/bin/bash

echo "🔧 Final Fix - Order Confirmation Syntax Error"
echo "=============================================="

# Check PHP syntax
echo "🔍 Checking PHP syntax..."
php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php

if [ $? -eq 0 ]; then
    echo "✅ PHP syntax is now valid!"
    
    # Deploy the file
    echo "📁 Deploying corrected file..."
    sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php /var/www/html/IAP2.2Dev/order_confirmation.php
    sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
    sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation.php 2>/dev/null || true
    
    echo "✅ File deployed successfully!"
    echo ""
    echo "🧪 Test the fixed page:"
    echo "http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
    echo ""
    echo "🎯 HTTP 500 error should now be resolved!"
    
else
    echo "❌ Still has syntax errors. Let me show the error details:"
    php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php
fi