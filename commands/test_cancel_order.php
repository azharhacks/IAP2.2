<?php
/**
 * Test Order Cancellation - SMARTDUKA
 * Verify the cancel order functionality works properly
 */

session_start();
require_once 'config.php';

echo "<h1>🚫 Order Cancellation Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='error'>❌ Please login first to test order cancellation</div>";
        echo "<a href='Signin.php'>Login</a>";
        exit();
    }
    
    echo "<div class='success'>✅ User authenticated (ID: {$_SESSION['user_id']})</div>";
    
    // Check database
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    echo "<div class='success'>✅ Database connected</div>";
    
    // Get user's orders
    $stmt = $pdo->prepare("
        SELECT id, order_number, order_status, payment_status, total_amount, created_at
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>📊 Found " . count($orders) . " orders for your account</div>";
    
    if (empty($orders)) {
        echo "<div class='warning'>⚠️ No orders found. Please create an order first to test cancellation.</div>";
        echo "<a href='products.php'>Shop Products</a>";
        exit();
    }
    
    // Display orders with cancellation status
    echo "<h2>Your Orders</h2>";
    echo "<table border='1' cellpadding='10' cellspacing='0' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th>Order ID</th><th>Order Number</th><th>Status</th><th>Payment</th><th>Amount</th><th>Can Cancel?</th><th>Action</th>";
    echo "</tr>";
    
    foreach ($orders as $order) {
        $canCancel = (
            $order['order_status'] === 'pending' || 
            ($order['order_status'] === 'processing' && $order['payment_status'] !== 'paid')
        ) && $order['order_status'] !== 'cancelled';
        
        $statusColor = match($order['order_status']) {
            'pending' => '#ffc107',
            'processing' => '#17a2b8', 
            'completed' => '#28a745',
            'cancelled' => '#dc3545',
            default => '#6c757d'
        };
        
        echo "<tr>";
        echo "<td>{$order['id']}</td>";
        echo "<td>{$order['order_number']}</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>" . ucfirst($order['order_status']) . "</td>";
        echo "<td>" . ucfirst($order['payment_status']) . "</td>";
        echo "<td>KSh " . number_format($order['total_amount'], 2) . "</td>";
        echo "<td>" . ($canCancel ? "✅ Yes" : "❌ No") . "</td>";
        
        if ($canCancel) {
            echo "<td>";
            echo "<button onclick='testCancelOrder({$order['id']})' style='background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;'>";
            echo "Cancel Order";
            echo "</button>";
            echo "</td>";
        } else {
            $reason = match(true) {
                $order['order_status'] === 'cancelled' => 'Already cancelled',
                $order['payment_status'] === 'paid' => 'Payment completed',
                in_array($order['order_status'], ['shipped', 'completed']) => 'Order shipped/completed',
                default => 'Cannot cancel'
            };
            echo "<td style='color: #6c757d;'>$reason</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if cancel_order.php exists
    echo "<h2>🔧 System Check</h2>";
    if (file_exists('cancel_order.php')) {
        echo "<div class='success'>✅ cancel_order.php endpoint exists</div>";
    } else {
        echo "<div class='error'>❌ cancel_order.php endpoint missing</div>";
    }
    
    // Test AJAX functionality
    echo "<h2>🧪 Test Cancellation</h2>";
    echo "<div id='testResults'></div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

?>

<script>
async function testCancelOrder(orderId) {
    const resultsDiv = document.getElementById('testResults');
    
    if (!confirm('Test cancel order ' + orderId + '?\n\nThis will actually cancel the order!')) {
        return;
    }
    
    resultsDiv.innerHTML = '<div class="info">🔄 Testing order cancellation for Order ID: ' + orderId + '</div>';
    
    try {
        const response = await fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                order_id: orderId,
                timestamp: new Date().getTime()
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultsDiv.innerHTML = 
                '<div class="success">✅ <strong>Cancellation Successful!</strong><br>' +
                'Order ID: ' + data.order_id + '<br>' +
                'Order Number: ' + (data.order_number || 'N/A') + '<br>' +
                'Message: ' + data.message + '</div>';
                
            // Reload page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            resultsDiv.innerHTML = 
                '<div class="error">❌ <strong>Cancellation Failed</strong><br>' +
                'Error: ' + data.message + '</div>';
        }
        
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">❌ <strong>Network Error</strong><br>' +
            'Error: ' + error.message + '</div>';
    }
}
</script>

<hr>
<h3>Navigation</h3>
<ul>
    <li><a href="orders.php">View Orders (Main Page)</a></li>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="products.php">Shop Products</a></li>
</ul>