#!/bin/bash

echo "🗄️ Deploying Database Column Fixes"
echo "=================================="

# Copy updated files
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/check_database_schema.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php
chmod 644 /var/www/html/IAP2.2Dev/check_database_schema.php

echo "✅ Database column fixes deployed!"
echo ""
echo "🔧 Changes Made:"
echo "   ✅ Removed u.phone from M-Pesa payment query"
echo "   ✅ Fixed GROUP BY clause to include all selected columns"
echo "   ✅ Removed phone field dependency from payment form"
echo "   ✅ Added comprehensive database schema checker"
echo ""
echo "🔍 Check your database schema:"
echo "   http://localhost/IAP2.2Dev/check_database_schema.php"
echo ""
echo "🎯 Test the M-Pesa payment page:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=14"
echo ""
echo "💡 The schema checker will show you exactly what columns are missing"
echo "    and provide SQL commands to fix them."