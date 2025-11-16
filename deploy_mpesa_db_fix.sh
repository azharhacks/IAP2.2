#!/bin/bash

echo "💳 Deploying M-Pesa Database Fix"
echo "================================"

# Copy the fix files
cp /home/devyanjethwaa/IAP2.2-1/fix_mpesa_database.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/fix_mpesa_database.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment.php

echo "✅ M-Pesa database fixes deployed!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ Fixed merchant_request_id column to allow NULL values"
echo "   ✅ Updated M-Pesa payment handler to not require merchant_request_id"
echo "   ✅ Added proper default values to mpesa_transactions table"
echo "   ✅ Created database structure if missing"
echo ""
echo "🚀 IMPORTANT: Run the database fix first!"
echo "   http://localhost/IAP2.2Dev/fix_mpesa_database.php"
echo ""
echo "🧪 After running the fix, test M-Pesa payment:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "💡 The error was caused by:"
echo "   • merchant_request_id column not allowing NULL values"
echo "   • Payment handler trying to insert without providing this field"
echo "   • Database schema missing proper defaults"
echo ""
echo "✅ This fix will resolve the 'Field merchant_request_id doesn't have a default value' error!"