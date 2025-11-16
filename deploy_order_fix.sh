#!/bin/bash

echo "🔧 Deploying Order Confirmation Database Fix"
echo "============================================="

# Copy the fixed order confirmation file
cp /home/devyanjethwaa/IAP2.2-1/order_confirmation.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php

echo "✅ Order confirmation database fix deployed!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ Added proper error handling for missing mpesa_transactions table"
echo "   ✅ Safe fallback queries that don't crash if M-Pesa table doesn't exist"
echo "   ✅ Graceful handling of missing columns"
echo "   ✅ No more 'Column not found' errors"
echo ""
echo "🧪 Test the fixed order confirmation:"
echo "   http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 The page will now work even if:"
echo "   • M-Pesa transactions table doesn't exist"
echo "   • Table has different column names"
echo "   • Database structure is incomplete"
echo ""
echo "🎯 Order confirmation should load without database errors!"