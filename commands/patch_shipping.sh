#!/bin/bash

echo "📦 Patching SMARTDUKA Shipping Costs"
echo "==================================="

cd /home/devyanjethwaa/IAP2.2-1/

# Run the shipping fix analysis
echo "🔍 Analyzing current shipping setup..."
php fix_shipping.php

echo ""
echo "📝 Quick fixes to apply:"
echo "========================"

# Create a simple shipping override for immediate testing
cat > temp_shipping_fix.php << 'EOF'
<?php
// Quick shipping cost override - include this in checkout files
function getReasonableShipping($subtotal) {
    if ($subtotal >= 1000) return 0;      // Free shipping
    if ($subtotal >= 500) return 50;      // KSh 50
    if ($subtotal >= 100) return 30;      // KSh 30
    return 20;                            // KSh 20 minimum
}

// Override any existing shipping calculation
if (isset($shipping_cost)) {
    $cart_total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cart_total += $item['price'] * $item['quantity'];
        }
    }
    $shipping_cost = getReasonableShipping($cart_total);
}
?>
EOF

echo "✅ Created temp_shipping_fix.php"
echo ""
echo "🎯 For your KSh 10 candy bar order:"
echo "   Subtotal: KSh 10.00"
echo "   Shipping: KSh 20.00 (instead of KSh 500!)"
echo "   Tax (16%): KSh 1.60"
echo "   Total: KSh 31.60"
echo ""
echo "💡 Much more reasonable than KSh 512!"
echo ""
echo "🔧 To apply the fix:"
echo "   1. Find where shipping is calculated in your checkout"
echo "   2. Replace the KSh 500 fixed cost with tiered pricing"
echo "   3. Or include the shipping functions file"

# Copy files to web directory
cp includes/shipping_functions.php /var/www/html/IAP2.2Dev/includes/ 2>/dev/null || mkdir -p /var/www/html/IAP2.2Dev/includes/ && cp includes/shipping_functions.php /var/www/html/IAP2.2Dev/includes/
cp temp_shipping_fix.php /var/www/html/IAP2.2Dev/

echo "📁 Files copied to web directory for immediate use"