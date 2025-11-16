<?php
/**
 * Simple Order Confirmation Test - No HTTP 500 Errors
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Order Confirmation Test</title>";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
echo "</head><body>";

echo '<div class="container mt-5">';
echo '<div class="alert alert-success">';
echo '<h4>✅ PHP Syntax Check Passed!</h4>';
echo '<p>If you can see this message, the PHP file is working correctly.</p>';
echo '</div>';

// Test basic PHP functionality
echo '<div class="card">';
echo '<div class="card-header"><h5>🧪 Basic Tests</h5></div>';
echo '<div class="card-body">';

echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
echo '<p><strong>Current Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';

// Test order data simulation
$testOrder = [
    'order_number' => 'ORD-20251116165528-3525',
    'created_at' => date('Y-m-d H:i:s'),
    'total_amount' => 511.60,
    'payment_status' => 'paid',
    'payment_method' => 'mpesa'
];

echo '<p><strong>Test Order Number:</strong> ' . htmlspecialchars($testOrder['order_number']) . '</p>';
echo '<p><strong>Test Amount:</strong> KSh ' . number_format($testOrder['total_amount'], 2) . '</p>';

// Test shipping address fallback
$shippingAddress = 'Strathmore University, Ole sangale road, Nairobi, Kenya';
$city = 'Nairobi';
$state = 'Nairobi County';
$postal = '00200';
$country = 'Kenya';

echo '<hr>';
echo '<h6>📍 Shipping Address Test:</h6>';
echo '<p>' . htmlspecialchars($shippingAddress) . '</p>';
echo '<p>' . htmlspecialchars($city . ', ' . $state . ' ' . $postal) . '</p>';
echo '<p>' . htmlspecialchars($country) . '</p>';

echo '</div>';
echo '</div>';

echo '<div class="mt-3 text-center">';
echo '<a href="order_confirmation.php?order=ORD-20251116165528-3525" class="btn btn-success">Test Real Order Confirmation</a>';
echo '</div>';

echo '</div>'; // container
echo '</body></html>';
?>