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
