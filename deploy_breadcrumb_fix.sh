#!/bin/bash

echo "🍞 Deploying Breadcrumb Fixes"
echo "============================"

# Copy updated files
cp /home/devyanjethwaa/IAP2.2-1/orders.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/orders.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php

echo "✅ Breadcrumb fixes deployed!"
echo ""
echo "🔧 Changes Made:"
echo "   ✅ Fixed orders.php breadcrumb to use 'title' key"
echo "   ✅ Fixed mpesa_payment_page.php breadcrumb to use 'title' key"
echo "   ✅ Added error handling for breadcrumb failures"
echo "   ✅ Fallback to simple HTML breadcrumb if Layout fails"
echo ""
echo "🎯 This should eliminate the 'Undefined array key title' warnings!"
echo ""
echo "Test URLs:"
echo "   Orders Page: http://localhost/IAP2.2Dev/orders.php"
echo "   M-Pesa Page: http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=14"