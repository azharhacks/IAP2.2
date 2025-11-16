<?php
/**
 * M-Pesa Payment Page - Safe Version with Error Handling
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>M-Pesa Payment - SMARTDUKA</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .error{background:#ffe6e6;border:1px solid #ff0000;padding:10px;margin:10px 0;} .success{background:#e6ffe6;border:1px solid #00ff00;padding:10px;margin:10px 0;}</style>";
echo "</head><body>";

try {
    echo "<h1>M-Pesa Payment - Debug Mode</h1>";
    
    // Step 1: Start session
    session_start();
    echo "<div class='success'>✅ Session started</div>";
    
    // Step 2: Load config
    if (!file_exists('config.php')) {
        throw new Exception("config.php file not found");
    }
    require_once 'config.php';
    echo "<div class='success'>✅ Config loaded</div>";
    
    // Step 3: Check database
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    echo "<div class='success'>✅ Database connected</div>";
    
    // Step 4: Check authentication
    if (!isset($_SESSION['user_id'])) {
        echo "<div class='error'>❌ User not logged in. <a href='Signin.php'>Please login</a></div>";
        exit();
    }
    echo "<div class='success'>✅ User authenticated (ID: " . $_SESSION['user_id'] . ")</div>";
    
    // Step 5: Get order ID
    $order_id = $_GET['order'] ?? null;
    if (!$order_id) {
        echo "<div class='error'>❌ No order ID provided. <a href='orders.php'>Go to orders</a></div>";
        exit();
    }
    echo "<div class='success'>✅ Order ID: $order_id</div>";
    
    // Step 6: Fetch order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "<div class='error'>❌ Order not found or access denied</div>";
        exit();
    }
    echo "<div class='success'>✅ Order found: #{$order['order_number']} - KSh " . number_format($order['total_amount'], 2) . "</div>";
    
    // Step 7: Load Layout (optional)
    $layout_available = false;
    try {
        if (file_exists('Abstract/Layout.php')) {
            require_once 'Abstract/Layout.php';
            $layout = new Layout($conf);
            $layout_available = true;
            echo "<div class='success'>✅ Layout class loaded</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>⚠️ Layout not available: " . $e->getMessage() . "</div>";
    }
    
    // Step 8: Display payment form
    echo "<hr><h2>Payment Form</h2>";
    
    if ($order['payment_status'] === 'paid') {
        echo "<div class='success'>This order is already paid!</div>";
        echo "<a href='order_confirmation.php?order=" . urlencode($order['order_number']) . "'>View confirmation</a>";
    } else {
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>Order Summary</h3>";
        echo "<p><strong>Order:</strong> #{$order['order_number']}</p>";
        echo "<p><strong>Amount:</strong> KSh " . number_format($order['total_amount'], 2) . "</p>";
        echo "<p><strong>Status:</strong> " . ucfirst($order['payment_status']) . "</p>";
        echo "<p><strong>Created:</strong> " . date('M j, Y g:i A', strtotime($order['created_at'])) . "</p>";
        echo "</div>";
        
        echo "<div style='background: #e6f3ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>M-Pesa Payment</h3>";
        echo "<form style='margin: 20px 0;'>";
        echo "<label>Phone Number (+254):</label><br>";
        echo "<input type='tel' placeholder='712345678' maxlength='9' style='padding: 10px; width: 200px; margin: 10px 0;'><br>";
        echo "<button type='button' onclick='alert(\"Payment simulation - this would initiate M-Pesa payment\")' style='background: #00d4aa; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer;'>Pay KSh " . number_format($order['total_amount'], 2) . "</button>";
        echo "</form>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3>Debug Information</h3>";
    echo "<ul>";
    echo "<li>Session ID: " . session_id() . "</li>";
    echo "<li>User ID: " . $_SESSION['user_id'] . "</li>";
    echo "<li>Order ID: $order_id</li>";
    echo "<li>Database: Connected</li>";
    echo "<li>Layout: " . ($layout_available ? "Available" : "Not available") . "</li>";
    echo "</ul>";
    
    echo "<h3>Quick Links</h3>";
    echo "<ul>";
    echo "<li><a href='orders.php'>My Orders</a></li>";
    echo "<li><a href='dashboard.php'>Dashboard</a></li>";
    echo "<li><a href='check_mpesa_errors.php?order=$order_id'>Full Error Check</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error Details:</strong><br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
    
    echo "<h3>Quick Actions</h3>";
    echo "<ul>";
    echo "<li><a href='check_mpesa_errors.php'>Run Error Checker</a></li>";
    echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
    echo "</ul>";
}

echo "</body></html>";
?>