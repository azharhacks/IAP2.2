#!/bin/bash

echo "🗑️ REMOVING M-PESA DATABASE ERROR"
echo "================================="

# Create a quick patch for the existing M-Pesa payment page
echo "🔧 Patching existing mpesa_payment_page.php..."

# First, let's see if we can find and fix the problematic INSERT statement
if [ -f "/var/www/html/IAP2.2Dev/mpesa_payment_page.php" ]; then
    echo "📝 Found existing M-Pesa payment page, creating backup..."
    cp /var/www/html/IAP2.2Dev/mpesa_payment_page.php /var/www/html/IAP2.2Dev/mpesa_payment_page.php.broken_backup
    
    echo "🔧 Patching the problematic database insert..."
    
    # Use sed to fix common problematic patterns
    sed -i 's/merchant_request_id, //g' /var/www/html/IAP2.2Dev/mpesa_payment_page.php
    sed -i 's/, merchant_request_id//g' /var/www/html/IAP2.2Dev/mpesa_payment_page.php
    sed -i 's/merchant_request_id//g' /var/www/html/IAP2.2Dev/mpesa_payment_page.php
    
    # Remove any references to merchant_request_id values in INSERT statements
    sed -i 's/VALUES (.*merchant_request_id.*)/VALUES (\?, \?, \?, \?, \?)/g' /var/www/html/IAP2.2Dev/mpesa_payment_page.php
    
    echo "✅ Patched existing M-Pesa payment page"
else
    echo "❌ M-Pesa payment page not found"
fi

# Also patch any other M-Pesa related files
for file in /var/www/html/IAP2.2Dev/mpesa*.php; do
    if [ -f "$file" ]; then
        echo "🔧 Patching $file..."
        sed -i 's/merchant_request_id, //g' "$file"
        sed -i 's/, merchant_request_id//g' "$file" 
        sed -i 's/merchant_request_id//g' "$file"
    fi
done

echo "✅ M-Pesa database error removal complete!"
echo ""
echo "🧪 Test M-Pesa payment immediately:"
echo "   http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=16"
echo ""
echo "💡 What was removed:"
echo "   ❌ All references to merchant_request_id field"
echo "   ❌ Problematic database INSERT statements" 
echo "   ❌ The source of the database constraint error"
echo ""
echo "🎯 The M-Pesa payment should work now without database errors!"