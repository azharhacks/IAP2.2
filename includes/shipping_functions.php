<?php
/**
 * SMARTDUKA Shipping Helper Functions
 * Reasonable shipping calculations for e-commerce
 */

/**
 * Calculate shipping cost based on order subtotal
 * @param float $subtotal Order subtotal amount
 * @return float Shipping cost
 */
function calculateShippingCost($subtotal) {
    // Free shipping for orders over KSh 1000
    if ($subtotal >= 1000) {
        return 0;
    }
    
    // Tiered shipping rates
    if ($subtotal >= 500) {
        return 50; // KSh 50 for orders KSh 500-999
    } elseif ($subtotal >= 100) {
        return 30; // KSh 30 for orders KSh 100-499
    } else {
        return 20; // KSh 20 for orders under KSh 100
    }
}

/**
 * Get shipping information with details
 * @param float $subtotal Order subtotal amount
 * @return array Shipping details
 */
function getShippingInfo($subtotal) {
    $cost = calculateShippingCost($subtotal);
    $isFree = ($cost == 0);
    
    $info = [
        'cost' => $cost,
        'is_free' => $isFree,
        'formatted_cost' => 'KSh ' . number_format($cost, 0)
    ];
    
    if ($isFree) {
        $info['message'] = 'Free shipping!';
        $info['reason'] = 'Orders over KSh 1,000 qualify for free shipping';
    } elseif ($subtotal >= 500) {
        $info['message'] = 'Standard shipping';
        $info['reason'] = 'Orders KSh 500-999';
        $info['free_threshold'] = 1000 - $subtotal;
        $info['free_message'] = 'Add KSh ' . number_format(1000 - $subtotal, 0) . ' more for free shipping!';
    } else {
        $info['message'] = 'Economy shipping';
        $info['reason'] = 'Small order shipping';
        $info['free_threshold'] = 1000 - $subtotal;
        $info['free_message'] = 'Add KSh ' . number_format(1000 - $subtotal, 0) . ' more for free shipping!';
    }
    
    return $info;
}

/**
 * Calculate total cart value including shipping and tax
 * @param array $cart Shopping cart items
 * @param float $taxRate Tax rate (default 16% VAT)
 * @return array Complete order calculation
 */
function calculateOrderTotal($cart, $taxRate = 0.16) {
    $subtotal = 0;
    
    foreach ($cart as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
    }
    
    $shipping = calculateShippingCost($subtotal);
    $tax = $subtotal * $taxRate;
    $total = $subtotal + $shipping + $tax;
    
    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'tax' => $tax,
        'total' => $total,
        'shipping_info' => getShippingInfo($subtotal),
        'formatted' => [
            'subtotal' => 'KSh ' . number_format($subtotal, 2),
            'shipping' => 'KSh ' . number_format($shipping, 2),
            'tax' => 'KSh ' . number_format($tax, 2),
            'total' => 'KSh ' . number_format($total, 2)
        ]
    ];
}

// Example usage:
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $orderCalculation = calculateOrderTotal($_SESSION['cart']);
    // Use $orderCalculation['total'] for order total
    // Use $orderCalculation['shipping_info']['free_message'] for shipping promotions
}
?>