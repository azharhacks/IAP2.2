#!/bin/bash

echo "🚚 Deploying Shipping Cost Fixes"
echo "================================"

# Copy shipping fix files
cp /home/devyanjethwaa/IAP2.2-1/debug_shipping_cost.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/apply_shipping_fix.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/debug_shipping_cost.php
chmod 644 /var/www/html/IAP2.2Dev/apply_shipping_fix.php

echo "✅ Shipping cost fix tools deployed!"
echo ""
echo "🔧 Tools Available:"
echo "   ✅ Shipping cost debugger and analyzer"
echo "   ✅ Automated shipping configuration fixer"
echo "   ✅ Database schema updater for shipping"
echo ""
echo "🔍 Debug shipping costs:"
echo "   http://localhost/IAP2.2Dev/debug_shipping_cost.php"
echo ""
echo "💡 Quick Fixes Available:"
echo "   • Set Free Shipping (0 KSh for all orders)"
echo "   • Set Custom Shipping Rate with threshold"
echo "   • Add shipping_cost column to database"
echo "   • Analyze current shipping costs"
echo ""
echo "🎯 Expected Results:"
echo "   • View current shipping costs in all orders"
echo "   • Identify where 500 KSh shipping is coming from"
echo "   • Apply instant fixes with one click"
echo "   • Generate shipping configuration file"
echo ""
echo "🚀 To fix shipping costs:"
echo "   1. Visit the debug page above"
echo "   2. Click 'Set Free Shipping' for 0 cost"
echo "   3. Or set custom rates with thresholds"
echo "   4. Database will be updated automatically"