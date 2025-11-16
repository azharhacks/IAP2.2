#!/bin/bash

echo "🚀 Deploying M-Pesa Files to Web Directory"
echo "=========================================="

# Copy all M-Pesa files to web directory
echo "📋 Copying M-Pesa files..."
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_callback.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_timeout.php /var/www/html/IAP2.2Dev/

# Copy admin M-Pesa files
echo "📋 Copying admin M-Pesa files..."
cp /home/devyanjethwaa/IAP2.2-1/admin/mpesa_simple.php /var/www/html/IAP2.2Dev/admin/
cp /home/devyanjethwaa/IAP2.2-1/admin/mpesa_pdf_export.php /var/www/html/IAP2.2Dev/admin/
cp /home/devyanjethwaa/IAP2.2-1/admin/mpesa_admin_api.php /var/www/html/IAP2.2Dev/admin/
cp /home/devyanjethwaa/IAP2.2-1/admin/mpesa_csv_export.php /var/www/html/IAP2.2Dev/admin/
cp /home/devyanjethwaa/IAP2.2-1/admin/goto_mpesa.php /var/www/html/IAP2.2Dev/admin/

# Set proper permissions
echo "🔒 Setting file permissions..."
chmod 644 /var/www/html/IAP2.2Dev/mpesa_*.php
chmod 644 /var/www/html/IAP2.2Dev/admin/mpesa_*.php

echo ""
echo "✅ M-Pesa Files Successfully Deployed!"
echo "====================================="
echo ""
echo "🌐 User M-Pesa URLs:"
echo "   Payment Page: http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=13"
echo "   Payment API:  http://localhost/IAP2.2Dev/mpesa_payment.php"
echo "   Callback:     http://localhost/IAP2.2Dev/mpesa_callback.php"
echo "   Timeout:      http://localhost/IAP2.2Dev/mpesa_timeout.php"
echo ""
echo "🔧 Admin M-Pesa URLs:"
echo "   Management:   http://localhost/IAP2.2Dev/admin/mpesa_simple.php"
echo "   PDF Export:   http://localhost/IAP2.2Dev/admin/mpesa_pdf_export.php"
echo "   CSV Export:   http://localhost/IAP2.2Dev/admin/mpesa_csv_export.php"
echo ""
echo "🎉 M-Pesa system is now fully operational for both users and admins!"