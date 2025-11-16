<?php
/**
 * Database Schema Fix - Add Missing Order Status Columns
 */

require_once 'config.php';

echo "<h1>🔧 SMARTDUKA Database Schema Fix</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check current orders table structure
    echo "<div class='info'><h3>Current Orders Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasOrderStatus = false;
    $hasPaymentStatus = false;
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'order_status') $hasOrderStatus = true;
        if ($column['Field'] === 'payment_status') $hasPaymentStatus = true;
        
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Check what needs to be added
    $needsUpdates = [];
    if (!$hasOrderStatus) {
        $needsUpdates[] = 'order_status';
    }
    if (!$hasPaymentStatus) {
        $needsUpdates[] = 'payment_status';
    }
    
    if (empty($needsUpdates)) {
        echo "<div class='success'>✅ All required columns exist!</div>";
    } else {
        echo "<div class='warning'>⚠️ Missing columns: " . implode(', ', $needsUpdates) . "</div>";
        
        // Add missing columns
        foreach ($needsUpdates as $column) {
            echo "<div class='info'>Adding column: $column</div>";
            
            if ($column === 'order_status') {
                $sql = "ALTER TABLE orders ADD COLUMN order_status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending'";
            } elseif ($column === 'payment_status') {
                $sql = "ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending'";
            }
            
            try {
                $pdo->exec($sql);
                echo "<div class='success'>✅ Added column: $column</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Failed to add $column: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Update existing orders with default values
    echo "<div class='info'><h3>Updating Existing Orders</h3>";
    
    try {
        $stmt = $pdo->exec("UPDATE orders SET order_status = 'pending' WHERE order_status IS NULL OR order_status = ''");
        echo "<div class='success'>✅ Updated $stmt orders with default order_status</div>";
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Could not update order_status: " . $e->getMessage() . "</div>";
    }
    
    try {
        $stmt = $pdo->exec("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
        echo "<div class='success'>✅ Updated $stmt orders with default payment_status</div>";
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Could not update payment_status: " . $e->getMessage() . "</div>";
    }
    
    // Test the fixed query
    echo "<div class='info'><h3>Testing Fixed Query</h3>";
    
    $testStmt = $pdo->prepare("
        SELECT o.id,
               o.order_number,
               COALESCE(o.order_status, 'pending') as order_status,
               COALESCE(o.payment_status, 'pending') as payment_status,
               o.total_amount,
               o.created_at
        FROM orders o
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $testStmt->execute();
    $testOrders = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($testOrders) {
        echo "<div class='success'>✅ Query test successful! Found " . count($testOrders) . " orders</div>";
        
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Order Number</th><th>Order Status</th><th>Payment Status</th><th>Amount</th></tr>";
        
        foreach ($testOrders as $order) {
            echo "<tr>";
            echo "<td>{$order['order_number']}</td>";
            echo "<td>{$order['order_status']}</td>";
            echo "<td>{$order['payment_status']}</td>";
            echo "<td>KSh " . number_format($order['total_amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ No orders found or query failed</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Schema Fix Complete!</h3>";
    echo "<p>The orders.php page should now work without warnings.</p>";
    echo "<a href='orders.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Orders Page</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>