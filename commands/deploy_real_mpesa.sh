#!/bin/bash

echo "📱 Deploying Real M-Pesa Integration"
echo "==================================="

# Copy the real M-Pesa files
cp /home/devyanjethwaa/IAP2.2-1/real_mpesa_payment.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_callback.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/verify_mpesa_sandbox.php /var/www/html/IAP2.2Dev/

# Replace the existing M-Pesa payment page with the real one
cp /var/www/html/IAP2.2Dev/mpesa_payment_page.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php.old_backup 2>/dev/null
cp /home/devyanjethwaa/IAP2.2-1/real_mpesa_payment.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/real_mpesa_payment.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_callback.php
chmod 644 /var/www/html/IAP2.2Dev/verify_mpesa_sandbox.php

echo "✅ Real M-Pesa integration deployed!"
echo ""
echo "📱 REAL M-PESA INTEGRATION FEATURES:"
echo "   ✅ Actual Safaricom STK Push API calls"
echo "   ✅ Real-time phone prompts"
echo "   ✅ Live transaction status checking"
echo "   ✅ Proper M-Pesa callback handling"
echo "   ✅ Real M-Pesa receipt numbers"
echo ""
echo "🔍 VERIFY Sandbox Setup First:"
echo "   http://localhost/IAP2.2Dev/verify_mpesa_sandbox.php"
echo ""
echo "🧪 Test the REAL M-Pesa integration:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "🏖️  SANDBOX CONFIGURATION:"
echo "   ✅ Already configured with VALID Safaricom sandbox credentials"
echo "   ✅ Environment: SANDBOX (safe for testing)"
echo "   ✅ No additional setup required for testing"
echo ""
echo "📱 SANDBOX TEST PHONE NUMBERS:"
echo "   ✅ 254708374149 (Always succeeds)"
echo "   ✅ 254712345678 (Always succeeds)"
echo "   ✅ 254799999999 (Always fails - for testing failures)"
echo ""
echo "⚙️  FOR PRODUCTION ONLY (later):"
echo "   1. Get M-Pesa API credentials from Safaricom Developer Portal"
echo "   2. Change environment to 'live'"
echo "   3. Replace credentials with production keys"
echo ""
echo "🔧 For LIVE/PRODUCTION:"
echo "   1. Change \$this->environment = 'live' in MpesaAPI class"
echo "   2. Use your production credentials"
echo "   3. Update callback URL to your domain"
echo ""
echo "📋 How it works:"
echo "   1. User enters phone number"
echo "   2. Sends REAL STK Push via Safaricom API"
echo "   3. User receives ACTUAL M-Pesa prompt on phone"
echo "   4. User enters M-Pesa PIN"
echo "   5. Real-time status checking"
echo "   6. Automatic order update on payment"
echo ""
echo "📞 The user will receive an ACTUAL M-Pesa prompt on their phone!"
echo "💳 This is REAL M-Pesa integration, not simulation!"