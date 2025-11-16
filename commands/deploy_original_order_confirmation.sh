#!/bin/bash

echo "🔄 Restoring Original Order Confirmation with Layout.php"
echo "======================================================="

# Copy the fixed order confirmation file
cp /home/devyanjethwaa/IAP2.2-1/order_confirmation.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php

echo "✅ Original order confirmation restored with fixes!"
echo ""
echo "🔧 What was restored:"
echo "   ✅ Original Layout.php integration"
echo "   ✅ Original custom CSS styling"
echo "   ✅ Original breadcrumb navigation"
echo "   ✅ Original timeline and status displays"
echo "   ✅ Safe database queries with fallbacks"
echo ""
echo "🧪 Test the restored order confirmation:"
echo "   http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 This version maintains:"
echo "   • Your original design and layout"
echo "   • Layout.php class integration"
echo "   • All original features and styling"
echo "   • Database compatibility fixes"
echo ""
echo "🎯 Same look and feel, but now works with your database!"