<?php
/**
 * Fix Shipping Costs - SMARTDUKA E-commerce Platform
 * Update shipping calculations to reasonable rates
 */

require_once 'config.php';

echo "📦 Fixing SMARTDUKA Shipping Costs\n";
echo "=================================\n\n";

// Function to calculate reasonable shipping
function calculateShipping($subtotal) {
    if ($subtotal >= 1000) {
        return 0; // Free shipping for orders over KSh 1000
    } elseif ($subtotal >= 500) {
        return 50; // KSh 50 for orders KSh 500-999
    } elseif ($subtotal >= 100) {
        return 30; // KSh 30 for orders KSh 100-499
    } else {
        return 20; // KSh 20 for orders under KSh 100
    }
}

// Test shipping calculations
echo "💰 New Shipping Rate Structure:\n";
echo "   Orders under KSh 100:     KSh 20\n";
echo "   Orders KSh 100-499:       KSh 30\n";
echo "   Orders KSh 500-999:       KSh 50\n";
echo "   Orders KSh 1000+:         FREE\n\n";

echo "🧪 Test Cases:\n";
$testAmounts = [10, 50, 100, 250, 500, 750, 1000, 1500];

foreach ($testAmounts as $amount) {
    $shipping = calculateShipping($amount);
    $total = $amount + $shipping;
    echo sprintf("   KSh %4d + shipping KSh %2d = KSh %4d\n", $amount, $shipping, $total);
}

echo "\n📝 Files that need shipping updates:\n";
echo "   • checkout.php\n";
echo "   • cart.php (if it shows shipping preview)\n";
echo "   • order processing scripts\n\n";

echo "✅ For your KSh 10 candy bar:\n";
$candyShipping = calculateShipping(10);
$candyTotal = 10 + $candyShipping + (10 * 0.16); // Including 16% VAT
echo "   Subtotal: KSh 10\n";
echo "   Shipping: KSh $candyShipping\n";
echo "   VAT (16%): KSh " . number_format(10 * 0.16, 2) . "\n";
echo "   Total: KSh " . number_format($candyTotal, 2) . "\n\n";

echo "🎯 Much better than KSh 500 shipping!\n";

// Create shipping config for reference
$shippingConfig = [
    'free_shipping_threshold' => 1000,
    'rates' => [
        ['min' => 0, 'max' => 99.99, 'cost' => 20],
        ['min' => 100, 'max' => 499.99, 'cost' => 30],
        ['min' => 500, 'max' => 999.99, 'cost' => 50],
        ['min' => 1000, 'max' => 999999, 'cost' => 0]
    ]
];

file_put_contents('/home/devyanjethwaa/IAP2.2-1/shipping_config.json', json_encode($shippingConfig, JSON_PRETTY_PRINT));
echo "📋 Shipping configuration saved to shipping_config.json\n";
?>