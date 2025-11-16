#!/bin/bash

echo "🔧 Deploying FIXED Order Confirmation Page"
echo "==========================================="

# Copy the fixed order confirmation file
cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php /var/www/html/IAP2.2Dev/order_confirmation.php

# Set correct permissions
chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation.php 2>/dev/null || true

echo "✅ Fixed order confirmation deployed!"
echo ""
echo "🔧 What was FIXED:"
echo "   ✅ Safe database queries with multiple fallbacks"
echo "   ✅ Handles missing tables gracefully (orders, users, products, order_items)"
echo "   ✅ Fixed shipping address undefined key warnings"
echo "   ✅ Added M-Pesa payment receipt display"
echo "   ✅ White PDF download button"
echo "   ✅ Strathmore University default shipping address"
echo "   ✅ Works with ANY database structure"
echo ""
echo "🏢 Default Shipping Address:"
echo "   Strathmore University, Ole sangale road, Nairobi, Kenya"
echo "   Nairobi, Nairobi County 00200"
echo "   Kenya"
echo ""
echo "🧪 Test URLs:"
echo "   http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo "   http://127.0.0.1/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 Features that now work:"
echo "   • Order confirmation loads without errors"
echo "   • Shows order items (with fallback product names)"
echo "   • Payment status display"
echo "   • M-Pesa receipt (if payment completed)"
echo "   • Shipping information (no PHP warnings)"
echo "   • White PDF download button"
echo "   • Timeline and status history"
echo "   • Full Layout.php integration"
echo ""
echo "🎯 No more database errors or PHP warnings!"