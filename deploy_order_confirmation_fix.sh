#!/bin/bash

echo "📋 Deploying Order Confirmation Fix"
echo "===================================="

# Copy updated files
cp /home/devyanjethwaa/IAP2.2-1/order_confirmation.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/update_payment_status.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
chmod 644 /var/www/html/IAP2.2Dev/update_payment_status.php

echo "✅ Order confirmation fix deployed!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ Order confirmation now shows M-Pesa payment details"
echo "   ✅ Displays M-Pesa receipt number when paid"
echo "   ✅ Shows transaction date for completed payments"
echo "   ✅ Proper payment status badges and alerts"
echo ""
echo "🧪 Test the fixed order confirmation:"
echo "   http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💳 Update payment status (for testing):"
echo "   http://localhost/IAP2.2Dev/update_payment_status.php"
echo ""
echo "💡 How to test the complete flow:"
echo "   1. Go to M-Pesa payment page and complete STK Push"
echo "   2. OR use the payment status updater tool"
echo "   3. Visit order confirmation page"
echo "   4. Should show 'Payment Confirmed!' with M-Pesa receipt"
echo ""
echo "🎯 Order confirmation now properly shows PAID status!"