#!/bin/bash

echo "🔧 Deploying M-Pesa Debug Tools"
echo "==============================="

# Copy all M-Pesa files including debug tools
cp /home/devyanjethwaa/IAP2.2-1/real_mpesa_payment.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php
cp /home/devyanjethwaa/IAP2.2-1/verify_mpesa_sandbox.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/debug_mpesa_amount.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_callback.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php
chmod 644 /var/www/html/IAP2.2Dev/verify_mpesa_sandbox.php
chmod 644 /var/www/html/IAP2.2Dev/debug_mpesa_amount.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_callback.php

echo "✅ M-Pesa debug tools deployed!"
echo ""
echo "🔧 DEBUGGING THE AMOUNT ISSUE:"
echo ""
echo "🧪 Step 1 - Run Amount Debug Test:"
echo "   http://localhost/IAP2.2Dev/debug_mpesa_amount.php"
echo ""
echo "🔍 Step 2 - Verify Sandbox Setup:"
echo "   http://localhost/IAP2.2Dev/verify_mpesa_sandbox.php"
echo ""
echo "🧪 Step 3 - Test Fixed Payment:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "💡 The debug test will show exactly what's being sent to M-Pesa API"
echo "   and help identify why the amount is invalid."
echo ""
echo "🔧 Common M-Pesa amount issues:"
echo "   ❌ Decimal amounts (511.60) - M-Pesa needs integers"
echo "   ❌ Negative amounts"
echo "   ❌ Zero amounts"
echo "   ❌ Very large amounts (over 999,999)"
echo "   ✅ Positive integers (512, 100, 1)"
echo ""
echo "🎯 The fixed code now:"
echo "   • Converts 511.60 → 512 (rounded integer)"
echo "   • Validates amount > 0"
echo "   • Sends proper integer to M-Pesa API"