#!/bin/bash

echo "🗄️ Deploying Complete Database Schema Fixes"
echo "============================================"

# Copy all updated files
cp /home/devyanjethwaa/IAP2.2-1/mpesa_payment_page.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/fix_database_schema.php /var/www/html/IAP2.2Dev/
cp /home/devyanjethwaa/IAP2.2-1/check_database_schema.php /var/www/html/IAP2.2Dev/

# Set permissions
chmod 644 /var/www/html/IAP2.2Dev/mpesa_payment_page.php
chmod 644 /var/www/html/IAP2.2Dev/fix_database_schema.php
chmod 644 /var/www/html/IAP2.2Dev/check_database_schema.php

echo "✅ All database schema fixes deployed!"
echo ""
echo "🔧 Changes Made:"
echo "   ✅ Removed oi.price reference from M-Pesa payment query"
echo "   ✅ Created comprehensive database schema fixer"
echo "   ✅ Added schema checker for diagnostics"
echo ""
echo "🚀 IMPORTANT: Run the schema fixer first!"
echo "   http://localhost/IAP2.2Dev/fix_database_schema.php"
echo ""
echo "This will automatically:"
echo "   • Add missing phone column to users table"
echo "   • Add order_status and payment_status to orders table"
echo "   • Add price column to order_items table"
echo "   • Create mpesa_transactions table"
echo "   • Update existing data with defaults"
echo ""
echo "🧪 After running the fixer, test these:"
echo "   Orders Page: http://localhost/IAP2.2Dev/orders.php"
echo "   M-Pesa Page: http://localhost/IAP2.2Dev/mpesa_payment_page.php?order=14"
echo ""
echo "💡 The schema fixer will show you exactly what was fixed!"