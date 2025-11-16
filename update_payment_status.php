<?php
/**
 * M-Pesa Payment Status Updater - SMARTDUKA
 * Updates order payment status when M-Pesa payment is completed
 */

session_start();
require_once 'config.php';

echo "<h1>💳 M-Pesa Payment Status Updater</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.form-group { margin: 15px 0; }
.btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.btn:hover { background: #218838; }
</style>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'] ?? null;
    $mpesaReceipt = $_POST['mpesa_receipt'] ?? 'MP' . time() . rand(1000, 9999);
    
    if ($orderId) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update order payment status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid', 
                    payment_method = 'mpesa',
                    order_status = 'processing',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $result1 = $stmt->execute([$orderId]);
            
            // Insert or update M-Pesa transaction record
            $stmt = $pdo->prepare("
                INSERT INTO mpesa_transactions 
                (order_id, checkout_request_id, phone_number, amount, status, transaction_id, created_at)
                VALUES (?, ?, '254708374149', 512, 'completed', ?, NOW())
                ON DUPLICATE KEY UPDATE
                status = 'completed',
                transaction_id = VALUES(transaction_id),
                updated_at = NOW()
            ");
            $result2 = $stmt->execute([$orderId, 'ws_CO_' . time(), $mpesaReceipt]);
            
            // Commit transaction
            $pdo->commit();
            
            if ($result1 && $result2) {
                echo "<div class='success'>";
                echo "<h3>✅ Payment Status Updated!</h3>";
                echo "<p><strong>Order ID:</strong> $orderId</p>";
                echo "<p><strong>M-Pesa Receipt:</strong> $mpesaReceipt</p>";
                echo "<p><strong>Payment Status:</strong> PAID</p>";
                echo "<p><strong>Order Status:</strong> Processing</p>";
                echo "</div>";
                
                // Get order number for link
                $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $orderNumber = $stmt->fetchColumn();
                
                if ($orderNumber) {
                    echo "<div class='info'>";
                    echo "<h4>🔗 View Updated Order:</h4>";
                    echo "<a href='order_confirmation.php?order=$orderNumber' target='_blank'>order_confirmation.php?order=$orderNumber</a>";
                    echo "</div>";
                }
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Show form
echo "<div class='info'>";
echo "<h3>🔧 Update Order Payment Status</h3>";
echo "<p>Use this tool to mark an order as paid with M-Pesa (for testing purposes)</p>";
echo "</div>";

echo "<form method='post'>";
echo "<div class='form-group'>";
echo "<label for='order_id'>Order ID:</label><br>";
echo "<input type='number' name='order_id' id='order_id' required placeholder='Enter order ID (e.g., 16)' style='width: 300px; padding: 8px;'>";
echo "</div>";

echo "<div class='form-group'>";
echo "<label for='mpesa_receipt'>M-Pesa Receipt Number (optional):</label><br>";
echo "<input type='text' name='mpesa_receipt' id='mpesa_receipt' placeholder='Auto-generated if empty' style='width: 300px; padding: 8px;'>";
echo "</div>";

echo "<button type='submit' class='btn'>Mark Order as PAID</button>";
echo "</form>";

// Show current orders
echo "<div class='info'>";
echo "<h3>📋 Current Orders</h3>";

try {
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.total_amount, o.payment_status, o.order_status, o.created_at,
               mt.transaction_id as mpesa_receipt
        FROM orders o
        LEFT JOIN mpesa_transactions mt ON o.id = mt.order_id AND mt.status = 'completed'
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $orders = $stmt->fetchAll();
    
    if ($orders) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Order Number</th>";
        echo "<th style='padding: 8px;'>Amount</th>";
        echo "<th style='padding: 8px;'>Payment Status</th>";
        echo "<th style='padding: 8px;'>M-Pesa Receipt</th>";
        echo "<th style='padding: 8px;'>Date</th>";
        echo "</tr>";
        
        foreach ($orders as $order) {
            $statusColor = $order['payment_status'] === 'paid' ? 'background: #d4edda;' : 'background: #fff3cd;';
            echo "<tr style='$statusColor'>";
            echo "<td style='padding: 8px;'>{$order['id']}</td>";
            echo "<td style='padding: 8px;'>{$order['order_number']}</td>";
            echo "<td style='padding: 8px;'>KSh " . number_format($order['total_amount'], 2) . "</td>";
            echo "<td style='padding: 8px;'>" . ucfirst($order['payment_status']) . "</td>";
            echo "<td style='padding: 8px;'>" . ($order['mpesa_receipt'] ?: 'N/A') . "</td>";
            echo "<td style='padding: 8px;'>" . date('M j, Y g:i A', strtotime($order['created_at'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No orders found.</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>Error loading orders: " . $e->getMessage() . "</div>";
}

echo "</div>";
?>