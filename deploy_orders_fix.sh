#!/bin/bash

echo "🔧 Deploying Orders Page Fixes"
echo "=============================="

# Copy updated files
cp /home/devyanjethwaa/IAP2.2-1/orders.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/fix_orders_schema.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/orders.php
chmod 644 /var/www/html/IAP2.2Dev/fix_orders_schema.php

echo "✅ Files deployed successfully!"
echo ""
echo "🔧 Fixes Applied:"
echo "   ✅ Fixed SQL query to properly select order_status and payment_status"
echo "   ✅ Added COALESCE to handle NULL values with default 'pending'"
echo "   ✅ Fixed GROUP BY clause to include all selected columns"
echo "   ✅ Added error handling for breadcrumb function"
echo ""
echo "🔍 Next Steps:"
echo "   1. Run schema fix: http://localhost/IAP2.2Dev/fix_orders_schema.php"
echo "   2. Test orders page: http://localhost/IAP2.2Dev/orders.php"
echo ""
echo "💡 The schema fix will:"
echo "   • Add missing order_status column if needed"
echo "   • Add missing payment_status column if needed" 
echo "   • Set default values for existing orders"
echo "   • Test the updated query"
echo ""
echo "🎯 This should eliminate all the 'Undefined array key' warnings!"