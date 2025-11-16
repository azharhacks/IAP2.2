#!/bin/bash

echo "🔧 Deploying Order Confirmation Fixes"
echo "====================================="

# Copy the fixed order confirmation file
cp /home/devyanjethwaa/IAP2.2-1/order_confirmation.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php

echo "✅ Order confirmation fixes deployed!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ Removed 'Undefined array key' warnings for shipping fields"
echo "   ✅ Added fallback values for missing shipping data"
echo "   ✅ Changed PDF button to white styling"
echo "   ✅ Proper handling of missing database columns"
echo ""
echo "🏢 Default shipping address:"
echo "   Strathmore University, Ole sangale road, Nairobi, Kenya"
echo "   Nairobi, Nairobi County 00200"
echo "   Kenya"
echo ""
echo "🧪 Test the fixed order confirmation:"
echo "   http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 Now the page will:"
echo "   • Show shipping address without PHP warnings"
echo "   • Use default Strathmore address if no shipping data"
echo "   • Display white PDF download button"
echo "   • Work with any database structure"
echo ""
echo "🎯 No more PHP warnings and clean white PDF button!"