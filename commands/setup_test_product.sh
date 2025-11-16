#!/bin/bash

echo "🍭 Adding 10 KSh Sample Product to SMARTDUKA"
echo "==========================================="

# Run the PHP script to add the product
cd /home/devyanjethwaa/IAP2.2-1/
php add_sample_product.php

echo ""
echo "📋 Copying files to web directory..."

# Copy the product addition script to web directory for easy access
cp /home/devyanjethwaa/IAP2.2-1/add_sample_product.php /var/www/html/IAP2.2Dev/

echo "✅ Files copied successfully!"
echo ""
echo "🎯 Next Steps:"
echo "=============="
echo "1. Visit: http://localhost/IAP2.2Dev/products.php"
echo "2. Find the 'Sample Candy Bar' (KSh 10.00)"
echo "3. Add it to cart"
echo "4. Proceed to checkout"
echo "5. Test M-Pesa payment with this affordable item!"
echo ""
echo "💡 This 10 KSh product is perfect for testing the M-Pesa payment system!"