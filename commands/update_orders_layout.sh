#!/bin/bash

echo "📋 Updating orders.php with Layout integration..."

# Copy updated orders.php to web directory
cp /home/devyanjethwaa/IAP2.2-1/orders.php /var/www/html/IAP2.2Dev/

# Check if copy was successful
if [ -f "/var/www/html/IAP2.2Dev/orders.php" ]; then
    echo "✅ orders.php successfully updated in web directory"
else
    echo "❌ Failed to copy orders.php to web directory"
    exit 1
fi

# Set proper permissions
chmod 644 /var/www/html/IAP2.2Dev/orders.php

echo "🎨 Layout Features Added:"
echo "   ✅ Layout class integration"
echo "   ✅ SMARTDUKA orange theme"
echo "   ✅ Professional order cards"
echo "   ✅ Progress bars for order status"
echo "   ✅ Hover effects and animations"
echo "   ✅ Responsive mobile design"
echo "   ✅ Empty state handling"
echo "   ✅ Error handling with alerts"
echo ""
echo "🚀 orders.php now matches Layout.php styling!"