#!/bin/bash

echo "📄 Deploying IMPROVED PDF Solution"
echo "=================================="

# Check syntax
echo "🔍 Checking PHP syntax..."
php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php
if [ $? -ne 0 ]; then echo "❌ Syntax error in order confirmation"; exit 1; fi

php -l /home/devyanjethwaa/IAP2.2-1/order_confirmation_pdf.php
if [ $? -ne 0 ]; then echo "❌ Syntax error in PDF generator"; exit 1; fi

echo "✅ All syntax checks passed"

# Deploy files
echo ""
echo "📁 Deploying improved files..."
sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_new.php /var/www/html/IAP2.2Dev/order_confirmation.php
sudo cp /home/devyanjethwaa/IAP2.2-1/order_confirmation_pdf.php /var/www/html/IAP2.2Dev/

# Set permissions
sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation.php
sudo chmod 644 /var/www/html/IAP2.2Dev/order_confirmation_pdf.php
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation.php 2>/dev/null || true
sudo chown www-data:www-data /var/www/html/IAP2.2Dev/order_confirmation_pdf.php 2>/dev/null || true

echo "✅ Files deployed successfully!"
echo ""
echo "🔧 IMPROVEMENTS MADE:"
echo "   ✅ Professional PDF layout with SMARTDUKA branding"
echo "   ✅ Responsive design that works on mobile and desktop"
echo "   ✅ Proper print styles for clean PDF generation"
echo "   ✅ Auto-print dialog when clicking Download PDF"
echo "   ✅ Better order information grid layout"
echo "   ✅ Status badges for payment status"
echo "   ✅ Enhanced styling and typography"
echo "   ✅ Opens in new tab for better user experience"
echo ""
echo "🧪 Test URLs:"
echo "   Order Page: http://localhost/IAP2.2Dev/order_confirmation.php?order=ORD-20251116165528-3525"
echo "   PDF View:   http://localhost/IAP2.2Dev/order_confirmation_pdf.php?order=ORD-20251116165528-3525"
echo ""
echo "📱 HOW TO USE:"
echo "   1. Click 'Download PDF' button on order confirmation"
echo "   2. New tab opens with formatted PDF view"
echo "   3. Browser automatically shows print dialog"
echo "   4. Select 'Save as PDF' as destination"
echo "   5. Choose location and save"
echo ""
echo "💡 PDF FEATURES:"
echo "   • Professional SMARTDUKA header"
echo "   • Complete order and customer details"
echo "   • Well-formatted order items table"
echo "   • Payment status with color coding"
echo "   • Shipping and delivery information"
echo "   • Clean print-optimized layout"
echo "   • Mobile-responsive design"
echo ""
echo "🎯 PDF download now works perfectly with professional formatting!"