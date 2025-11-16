<?php
/**
 * M-Pesa Payment Page Debug - Check what's wrong
 */

session_start();
require_once 'config.php';

echo "<h1>M-Pesa Payment Page Debug</h1>";
echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px;'>";

// Check session
echo "<h3>1. Session Check</h3>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Verified: " . ($_SESSION['verified'] ?? 'Not set') . "<br>";
echo "Session data: " . print_r($_SESSION, true) . "<br>";

// Check order ID
echo "<h3>2. Order ID Check</h3>";
$order_id = $_GET['order'] ?? null;
echo "Order ID from URL: " . ($order_id ?? 'Not provided') . "<br>";

// Check database connection
echo "<h3>3. Database Check</h3>";
if (isset($pdo) && $pdo) {
    echo "Database connection: Available<br>";
    
    try {
        // Check if orders table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
        if ($stmt->rowCount() > 0) {
            echo "Orders table: EXISTS<br>";
            
            // Check order count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
            $count = $stmt->fetch()['count'];
            echo "Total orders in database: $count<br>";
            
            if ($order_id) {
                // Try to find the specific order
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$order_id]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($order) {
                    echo "<h4>Order Found:</h4>";
                    echo "<pre>" . print_r($order, true) . "</pre>";
                } else {
                    echo "Order ID $order_id: NOT FOUND<br>";
                    
                    // Show available orders for this user
                    if (isset($_SESSION['user_id'])) {
                        $stmt = $pdo->prepare("SELECT id, order_number, total_amount, payment_status FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute([$_SESSION['user_id']]);
                        $userOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo "<h4>Your Recent Orders:</h4>";
                        if ($userOrders) {
                            echo "<ul>";
                            foreach ($userOrders as $ord) {
                                echo "<li>ID: {$ord['id']}, Number: {$ord['order_number']}, Amount: KSh {$ord['total_amount']}, Status: {$ord['payment_status']}</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "No orders found for your account.<br>";
                        }
                    }
                }
            }
            
        } else {
            echo "Orders table: DOES NOT EXIST<br>";
        }
        
        // Check mpesa_transactions table
        $stmt = $pdo->query("SHOW TABLES LIKE 'mpesa_transactions'");
        if ($stmt->rowCount() > 0) {
            echo "M-Pesa transactions table: EXISTS<br>";
        } else {
            echo "M-Pesa transactions table: DOES NOT EXIST<br>";
        }
        
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Database connection: NOT AVAILABLE<br>";
}

// Check files
echo "<h3>4. File Check</h3>";
echo "Config file: " . (file_exists('config.php') ? 'EXISTS' : 'MISSING') . "<br>";
echo "Layout file: " . (file_exists('Abstract/Layout.php') ? 'EXISTS' : 'MISSING') . "<br>";

// Check Layout class
echo "<h3>5. Layout Class Check</h3>";
try {
    require_once 'Abstract/Layout.php';
    $layout = new Layout($conf);
    echo "Layout class: LOADED SUCCESSFULLY<br>";
} catch (Exception $e) {
    echo "Layout class error: " . $e->getMessage() . "<br>";
}

echo "</div>";

echo "<h3>Quick Links</h3>";
echo "<ul>";
echo "<li><a href='orders.php'>View Orders</a></li>";
echo "<li><a href='dashboard.php'>Dashboard</a></li>";
if ($order_id) {
    echo "<li><a href='mpesa_payment_page.php?order=$order_id'>Try Payment Page Again</a></li>";
}
echo "</ul>";
?>