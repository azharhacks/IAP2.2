#!/bin/bash

echo "🚫 Fixing Cancel Order Error"
echo "============================"

# Copy debug and simple cancel files
cp /home/devyanjethwaa/IAP2.2-1/debug_cancel_order.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/cancel_order_simple.php /var/www/html/IAP2.2Dev/

# Copy the original cancel_order.php if it exists, otherwise use the simple version
if [ -f "/home/devyanjethwaa/IAP2.2-1/cancel_order.php" ]; then
    cp /home/devyanjethwaa/IAP2.2-1/cancel_order.php /var/www/html/IAP2.2Dev/
    echo "✅ Copied original cancel_order.php"
else
    cp /home/devyanjethwaa/IAP2.2-1/cancel_order_simple.php /var/www/html/IAP2.2Dev/cancel_order.php
    echo "✅ Created cancel_order.php from simple version"
fi

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/debug_cancel_order.php
chmod 644 /var/www/html/IAP2.2Dev/cancel_order_simple.php
chmod 644 /var/www/html/IAP2.2Dev/cancel_order.php

echo "✅ Cancel order fix deployed!"
echo ""
echo "🔍 Debug the issue:"
echo "   http://localhost/IAP2.2Dev/debug_cancel_order.php"
echo ""
echo "🧪 Test endpoints:"
echo "   Simple version: http://localhost/IAP2.2Dev/cancel_order_simple.php"
echo "   Main version:   http://localhost/IAP2.2Dev/cancel_order.php"
echo ""
echo "🎯 Try canceling an order again at:"
echo "   http://localhost/IAP2.2Dev/orders.php"
echo ""
echo "💡 The debug page will show exactly what's wrong with the cancel system."