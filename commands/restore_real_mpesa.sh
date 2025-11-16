#!/bin/bash

echo "🔧 Restoring Real M-Pesa Integration"
echo "===================================="

# Copy the database fix script
cp /home/devyanjethwaa/IAP2.2-1/fix_real_mpesa_db.php /var/www/html/IAP2.2Dev/

# Restore original M-Pesa payment page if backup exists
if [ -f "/var/www/html/IAP2.2Dev/mpesa_payment_page.php.backup" ]; then
    echo "📦 Restoring original M-Pesa payment page..."
    cp /var/www/html/IAP2.2Dev/mpesa_payment_page.php.backup /var/www/html/IAP2.2Dev/mpesa_payment_page.php
    echo "✅ Original M-Pesa payment page restored"
else
    echo "⚠️ No backup found, keeping current version"
fi

# Restore original order confirmation if backup exists
if [ -f "/var/www/html/IAP2.2Dev/order_confirmation.php.backup" ]; then
    echo "📦 Restoring original order confirmation page..."
    cp /var/www/html/IAP2.2Dev/order_confirmation.php.backup /var/www/html/IAP2.2Dev/order_confirmation.php
    echo "✅ Original order confirmation page restored"
else
    echo "⚠️ No backup found, keeping current version"
fi

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/fix_real_mpesa_db.php

echo "✅ Real M-Pesa integration restoration complete!"
echo ""
echo "🚀 IMPORTANT: Run the database fix first!"
echo "   http://localhost/IAP2.2Dev/fix_real_mpesa_db.php"
echo ""
echo "🔧 This will:"
echo "   ✅ Fix the mpesa_transactions table structure"
echo "   ✅ Ensure all columns have proper defaults"
echo "   ✅ Test M-Pesa transaction creation"
echo "   ✅ Preserve your real M-Pesa integration"
echo ""
echo "🧪 After running the fix, test:"
echo "   Original M-Pesa: http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "💡 This preserves your real M-Pesa API integration while fixing the database issues!"
echo ""
echo "📋 What was restored:"
if [ -f "/var/www/html/IAP2.2Dev/mpesa_payment_page.php.backup" ]; then
    echo "   ✅ Original mpesa_payment_page.php (real integration)"
else
    echo "   ⚠️ No original M-Pesa page backup found"
fi

if [ -f "/var/www/html/IAP2.2Dev/order_confirmation.php.backup" ]; then
    echo "   ✅ Original order_confirmation.php (real integration)"
else
    echo "   ⚠️ No original order confirmation backup found"
fi