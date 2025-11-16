<?php
/**
 * Debug Cancel Order - Test the cancellation system
 */

session_start();
require_once 'config.php';

echo "<h1>🔍 Cancel Order Debug</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

// Check if files exist
echo "<div class='info'><h3>File Check</h3>";
$requiredFiles = ['cancel_order.php', 'config.php'];
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}
echo "</div>";

// Check session
echo "<div class='info'><h3>Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ User not logged in<br>";
}
echo "</div>";

// Check database
echo "<div class='info'><h3>Database Check</h3>";
try {
    if (isset($pdo) && $pdo) {
        echo "✅ Database connected<br>";
        
        // Check for orders
        $stmt = $pdo->prepare("SELECT id, order_number, order_status FROM orders WHERE user_id = ? LIMIT 5");
        $stmt->execute([$_SESSION['user_id'] ?? 0]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($orders) {
            echo "✅ Found " . count($orders) . " orders<br>";
            foreach ($orders as $order) {
                echo "Order {$order['id']}: {$order['order_number']} - {$order['order_status']}<br>";
            }
        } else {
            echo "⚠️ No orders found<br>";
        }
    } else {
        echo "❌ Database not connected<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
echo "</div>";

// Test cancel_order.php if it exists
if (file_exists('cancel_order.php')) {
    echo "<div class='info'><h3>Testing cancel_order.php</h3>";
    
    // Test with a simple request
    $testData = json_encode(['order_id' => '999', 'test' => true]);
    
    echo "Test URL: " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "cancel_order.php<br>";
    echo "This should be accessible at: <a href='cancel_order.php'>cancel_order.php</a><br>";
    
    echo "<button onclick='testCancelEndpoint()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Cancel Endpoint</button>";
    echo "<div id='testResult'></div>";
}

// Show direct file content check
echo "<div class='info'><h3>Quick Actions</h3>";
echo "<ul>";
echo "<li><a href='orders.php'>Back to Orders</a></li>";
echo "<li><a href='cancel_order.php' target='_blank'>View cancel_order.php (if exists)</a></li>";
echo "<li><a href='session_debug.php'>Session Debug</a></li>";
echo "</ul>";
echo "</div>";
?>

<script>
async function testCancelEndpoint() {
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<div style="background: #fff3cd; padding: 10px; margin: 10px 0;">🔄 Testing cancel endpoint...</div>';
    
    try {
        const response = await fetch('cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                order_id: '999',
                test: true
            })
        });
        
        const responseText = await response.text();
        
        resultDiv.innerHTML = 
            '<div style="background: ' + (response.ok ? '#d4edda' : '#f8d7da') + '; padding: 10px; margin: 10px 0;">' +
            '<strong>Response Status:</strong> ' + response.status + ' ' + response.statusText + '<br>' +
            '<strong>Response:</strong> ' + responseText +
            '</div>';
            
    } catch (error) {
        resultDiv.innerHTML = 
            '<div style="background: #f8d7da; padding: 10px; margin: 10px 0;">' +
            '<strong>Error:</strong> ' + error.message +
            '</div>';
    }
}
</script>