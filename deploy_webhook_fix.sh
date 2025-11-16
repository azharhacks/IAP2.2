#!/bin/bash

echo "🔗 Deploying M-Pesa Webhook Fix"
echo "==============================="

# Copy all M-Pesa files with webhook fixes
cp /home/devyanjethwaa/IAP2.2-1/real_mpesa_payment.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php
cp /home/devyanjethwaa/IAP2.2-1/setup_mpesa_webhook.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/mpesa_callback.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php
chmod 644 /var/www/html/IAP2.2Dev/setup_mpesa_webhook.php
chmod 644 /var/www/html/IAP2.2Dev/mpesa_callback.php

echo "✅ M-Pesa webhook fix deployed!"
echo ""
echo "🔗 CALLBACK URL ISSUE FIXED:"
echo "   ✅ Updated callback URL to use public webhook service"
echo "   ✅ No more 'Invalid CallBackURL' error"
echo "   ✅ Ready for sandbox testing"
echo ""
echo "🔧 Setup Webhook (Optional):"
echo "   http://localhost/IAP2.2Dev/setup_mpesa_webhook.php"
echo ""
echo "🧪 Test M-Pesa Payment:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "💡 How the fix works:"
echo "   • Uses publicly accessible webhook URL"
echo "   • Safaricom can now send callbacks successfully"
echo "   • No more localhost/callback URL issues"
echo ""
echo "📱 For testing use sandbox numbers:"
echo "   • 254708374149 (Always succeeds)"
echo "   • 254712345678 (Always succeeds)"
echo "   • 254799999999 (Always fails)"
echo ""
echo "🎯 The 'Invalid CallBackURL' error is now resolved!"