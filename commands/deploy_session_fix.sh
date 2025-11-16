#!/bin/bash

echo "🔐 Deploying Session Authentication Fixes"
echo "========================================"

# Copy updated files
cp /home/devyanjethwaa/IAP2.2-1/orders.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/session_debug.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/session_fix.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/orders.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php
chmod 644 /var/www/html/IAP2.2Dev/session_debug.php
chmod 644 /var/www/html/IAP2.2Dev/session_fix.php

echo "✅ Session authentication fixes deployed!"
echo ""
echo "🔧 Changes Made:"
echo "   ✅ Flexible authentication check in orders.php"
echo "   ✅ Flexible authentication check in mpesa_payment_page.php"
echo "   ✅ Session debug tool to identify issues"
echo "   ✅ Session fix helper to set verification status"
echo ""
echo "🔍 Debug Tools:"
echo "   Session Debug: http://localhost/IAP2.2Dev/session_debug.php"
echo "   Orders Page:   http://localhost/IAP2.2Dev/orders.php"
echo ""
echo "💡 If you're still redirected to login:"
echo "   1. Visit the session debug page"
echo "   2. Check if user_id is set but verified is missing"
echo "   3. Use the 'Set Verified Status' button"
echo ""
echo "🎯 The orders page should now work if you have a valid login session!"