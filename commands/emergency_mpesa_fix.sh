#!/bin/bash

echo "🚨 Emergency M-Pesa Payment Fix"
echo "==============================="

# First, backup the existing broken file
if [ -f "/var/www/html/IAP2.2Dev/mpesa_payment_page.php" ]; then
    cp /var/www/html/IAP2.2Dev/mpesa_payment_page.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php.backup
    echo "✅ Backed up existing mpesa_payment_page.php"
fi

# Copy our simple working version over the broken one
cp /home/devyanjethwaa/IAP2.2-1/simple_mpesa_payment.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php

echo "✅ Emergency fix applied!"
echo ""
echo "🔧 What was done:"
echo "   ✅ Backed up broken mpesa_payment_page.php"
echo "   ✅ Replaced it with working simple version"
echo "   ✅ Set proper permissions"
echo ""
echo "🧪 Test the fixed M-Pesa payment:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "💡 The original broken file is saved as:"
echo "   mpesa_payment_page.php.backup"
echo ""
echo "🎯 This should completely eliminate the database error!"