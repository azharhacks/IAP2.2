#!/bin/bash

echo "📄 Deploying PDF Download Fix"
echo "=============================="

# Check syntax first
echo "🔍 Checking PHP syntax..."
php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php
if [ $? -eq 0 ]; then
    echo "✅ Order confirmation syntax is valid"
else
    echo "❌ Syntax error in order confirmation"
    exit 1
fi

php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_pdf.php
if [ $? -eq 0 ]; then
    echo "✅ PDF generator syntax is valid"
else
    echo "❌ Syntax error in PDF generator"
    exit 1
fi

# Deploy files
echo ""
echo "📁 Deploying files..."
sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php /var/www/html/IAP2.2Dev/order_confirmation.php
sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_pdf.php /var/www/html/IAP2.2Dev/

# Set permissions
sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation_pdf.php
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation.php 2>/dev/null || true
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation_pdf.php 2>/dev/null || true

echo "✅ Files deployed successfully!"
echo ""
echo "🔧 What was fixed:"
echo "   ✅ PDF download link now passes correct order_number parameter"
echo "   ✅ Created order_confirmation_pdf.php generator"
echo "   ✅ PDF shows all order details and customer information"
echo "   ✅ Auto-triggers print dialog for easy PDF saving"
echo ""
echo "🧪 Test URLs:"
echo "   Order Page: http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo "   PDF Direct: http://localhost/IAP2.2Dev/order_confirmation_pdf.php?order=ORD-20251116165528-3525"
echo ""
echo "💡 PDF Features:"
echo "   • Professional order confirmation layout"
echo "   • Company branding (SMARTDUKA)"
echo "   • Complete order details and items"
echo "   • Shipping address information"
echo "   • Payment status and method"
echo "   • Auto-print dialog for PDF generation"
echo ""
echo "🎯 PDF download should now work correctly!"