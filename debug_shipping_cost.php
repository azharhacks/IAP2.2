<?php
/**
 * Shipping Cost Debug & Fix - SMARTDUKA
 * Find and fix shipping cost calculations
 */

session_start();
require_once 'config.php';

echo "<h1>🚚 Shipping Cost Debug & Fix</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
.code { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; margin: 10px 0; border-radius: 5px; font-family: monospace; }
</style>";

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check current shipping costs in recent orders
    echo "<div class='info'><h3>Current Shipping Costs Analysis</h3>";
    
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.total_amount, o.shipping_cost, o.created_at,
               SUM(oi.quantity * oi.price) as items_total
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($orders) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Order</th><th>Items Total</th><th>Shipping Cost</th><th>Total Amount</th><th>Date</th>";
        echo "</tr>";
        
        $total_shipping = 0;
        $order_count = 0;
        
        foreach ($orders as $order) {
            $items_total = $order['items_total'] ?? 0;
            $shipping_cost = $order['shipping_cost'] ?? ($order['total_amount'] - $items_total);
            $total_shipping += $shipping_cost;
            $order_count++;
            
            $shipping_color = $shipping_cost > 100 ? '#dc3545' : '#28a745';
            
            echo "<tr>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>KSh " . number_format($items_total, 2) . "</td>";
            echo "<td style='color: $shipping_color; font-weight: bold;'>KSh " . number_format($shipping_cost, 2) . "</td>";
            echo "<td>KSh " . number_format($order['total_amount'], 2) . "</td>";
            echo "<td>" . date('M j, Y', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $avg_shipping = $order_count > 0 ? $total_shipping / $order_count : 0;
        echo "<p><strong>Average Shipping Cost:</strong> KSh " . number_format($avg_shipping, 2) . "</p>";
        
        if ($avg_shipping > 100) {
            echo "<div class='warning'>⚠️ High shipping costs detected! Average: KSh " . number_format($avg_shipping, 2) . "</div>";
        }
    } else {
        echo "<div class='warning'>No orders found to analyze</div>";
    }
    echo "</div>";
    
    // Check for shipping-related files
    echo "<div class='info'><h3>Shipping Cost Configuration Files</h3>";
    
    $shipping_files = [
        'checkout.php',
        'cart.php', 
        'place_order.php',
        'process_order.php',
        'shipping.php',
        'config.php'
    ];
    
    $found_files = [];
    foreach ($shipping_files as $file) {
        if (file_exists($file)) {
            $found_files[] = $file;
            echo "✅ $file exists<br>";
        } else {
            echo "❌ $file not found<br>";
        }
    }
    echo "</div>";
    
    // Check database schema for shipping columns
    echo "<div class='info'><h3>Database Shipping Columns</h3>";
    
    // Check orders table
    try {
        $stmt = $pdo->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $shipping_columns = [];
        foreach ($columns as $col) {
            if (stripos($col['Field'], 'shipping') !== false) {
                $shipping_columns[] = $col['Field'];
            }
        }
        
        if ($shipping_columns) {
            echo "<strong>Orders table shipping columns:</strong><br>";
            foreach ($shipping_columns as $col) {
                echo "• $col<br>";
            }
        } else {
            echo "<div class='warning'>⚠️ No shipping-related columns found in orders table</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Could not check orders table: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Search for shipping cost in files
    echo "<div class='info'><h3>Shipping Cost Code Search</h3>";
    
    foreach ($found_files as $file) {
        $content = file_get_contents($file);
        
        // Search for shipping-related patterns
        $patterns = [
            '/shipping[_\s]*cost/i',
            '/shipping[_\s]*fee/i', 
            '/500/i',
            '/\$shipping/i',
            '/delivery[_\s]*cost/i'
        ];
        
        $found_patterns = [];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $found_patterns[] = $pattern;
            }
        }
        
        if (!empty($found_patterns)) {
            echo "<strong>$file:</strong> Found shipping-related code<br>";
            foreach ($found_patterns as $pattern) {
                echo "  • Pattern: $pattern<br>";
            }
        }
    }
    echo "</div>";
    
    // Shipping cost fixes
    echo "<div class='info'><h3>Shipping Cost Fixes</h3>";
    
    echo "<button onclick='fixShippingCost()' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Set Free Shipping</button>";
    echo "<button onclick='setCustomShipping()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Set Custom Rate</button>";
    echo "<button onclick='addShippingColumn()' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px;'>Add Shipping Column</button>";
    
    echo "<div id='fixResults'></div>";
    echo "</div>";
    
    // Configuration suggestions
    echo "<div class='info'><h3>Shipping Configuration Suggestions</h3>";
    echo "<div class='code'>";
    echo "// Add to config.php or create shipping_config.php<br>";
    echo "define('FREE_SHIPPING_THRESHOLD', 1000); // Free shipping over KSh 1,000<br>";
    echo "define('DEFAULT_SHIPPING_COST', 0);      // Default shipping cost<br>";
    echo "define('EXPRESS_SHIPPING_COST', 200);   // Express shipping option<br>";
    echo "<br>";
    echo "// Shipping calculation function<br>";
    echo "function calculateShipping(\$total, \$location = 'nairobi') {<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;if (\$total >= FREE_SHIPPING_THRESHOLD) {<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return 0; // Free shipping<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;}<br>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;return DEFAULT_SHIPPING_COST;<br>";
    echo "}";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<script>
async function fixShippingCost() {
    const resultsDiv = document.getElementById('fixResults');
    resultsDiv.innerHTML = '<div class="info">🔄 Setting up free shipping...</div>';
    
    try {
        const response = await fetch('apply_shipping_fix.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'free_shipping'
            })
        });
        
        const data = await response.json();
        
        const resultClass = data.success ? 'success' : 'error';
        resultsDiv.innerHTML = 
            '<div class="' + resultClass + '">' +
            '<strong>Free Shipping Setup:</strong><br>' +
            data.message +
            '</div>';
            
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">' +
            '<strong>Error:</strong> ' + error.message +
            '</div>';
    }
}

async function setCustomShipping() {
    const cost = prompt('Enter custom shipping cost (in KSh):');
    if (cost === null) return;
    
    const threshold = prompt('Enter free shipping threshold (in KSh, 0 for always charge):');
    if (threshold === null) return;
    
    const resultsDiv = document.getElementById('fixResults');
    resultsDiv.innerHTML = '<div class="info">🔄 Setting custom shipping rates...</div>';
    
    try {
        const response = await fetch('apply_shipping_fix.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'custom_shipping',
                shipping_cost: parseFloat(cost),
                free_threshold: parseFloat(threshold)
            })
        });
        
        const data = await response.json();
        
        const resultClass = data.success ? 'success' : 'error';
        resultsDiv.innerHTML = 
            '<div class="' + resultClass + '">' +
            '<strong>Custom Shipping Setup:</strong><br>' +
            data.message +
            '</div>';
            
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">' +
            '<strong>Error:</strong> ' + error.message +
            '</div>';
    }
}

async function addShippingColumn() {
    const resultsDiv = document.getElementById('fixResults');
    resultsDiv.innerHTML = '<div class="info">🔄 Adding shipping_cost column to orders table...</div>';
    
    try {
        const response = await fetch('apply_shipping_fix.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_shipping_column'
            })
        });
        
        const data = await response.json();
        
        const resultClass = data.success ? 'success' : 'error';
        resultsDiv.innerHTML = 
            '<div class="' + resultClass + '">' +
            '<strong>Database Update:</strong><br>' +
            data.message +
            '</div>';
            
    } catch (error) {
        resultsDiv.innerHTML = 
            '<div class="error">' +
            '<strong>Error:</strong> ' + error.message +
            '</div>';
    }
}
</script>

<hr>
<h3>Quick Actions</h3>
<ul>
    <li><a href="orders.php">View Orders</a></li>
    <li><a href="checkout.php">Test Checkout</a></li>
    <li><a href="cart.php">View Cart</a></li>
</ul>