#!/bin/bash

echo "🔍 Deploying M-Pesa Debug Files"
echo "==============================="

# Copy debug files
cp /home/devyanjethwaa/IAP2.2-1/mpesa_debug.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_debug.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php

echo "✅ Files deployed successfully!"
echo ""
echo "🔍 Debug URLs:"
echo "   Debug page: http://localhost/IAP2.2Dev/mpesa_debug.php?order=14"
echo "   Payment page: http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=14"
echo ""
echo "💡 The debug page will show exactly what's wrong with the payment page."