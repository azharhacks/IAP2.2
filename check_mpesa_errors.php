<?php
/**
 * M-Pesa Error Checker - Identify what's wrong
 */

echo "<h1>SMARTDUKA M-Pesa Error Checker</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.error { background: #ffe6e6; border: 1px solid #ff0000; padding: 10px; margin: 10px 0; }
.success { background: #e6ffe6; border: 1px solid #00ff00; padding: 10px; margin: 10px 0; }
.warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; }
.info { background: #e6f3ff; border: 1px solid #007bff; padding: 10px; margin: 10px 0; }
</style>";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div class='info'><h3>1. PHP Error Settings</h3>";
echo "Error reporting: " . (error_reporting() ? "ON" : "OFF") . "<br>";
echo "Display errors: " . (ini_get('display_errors') ? "ON" : "OFF") . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "</div>";

// Check if config file exists and loads
echo "<div class='info'><h3>2. Config File Check</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    try {
        require_once 'config.php';
        echo "✅ config.php loaded successfully<br>";
        
        // Check database connection
        if (isset($pdo) && $pdo) {
            echo "✅ Database connection available<br>";
            
            // Test database query
            try {
                $stmt = $pdo->query("SELECT 1");
                echo "✅ Database query test successful<br>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database query failed: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Database connection not available</div>";
        }
        
        // Check config variables
        if (isset($conf)) {
            echo "✅ \$conf variable available<br>";
        } else {
            echo "<div class='error'>❌ \$conf variable not set</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error loading config.php: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ config.php not found</div>";
}
echo "</div>";

// Check Layout class
echo "<div class='info'><h3>3. Layout Class Check</h3>";
if (file_exists('Abstract/Layout.php')) {
    echo "✅ Abstract/Layout.php exists<br>";
    try {
        require_once 'Abstract/Layout.php';
        echo "✅ Layout.php loaded successfully<br>";
        
        if (class_exists('Layout')) {
            echo "✅ Layout class available<br>";
            
            // Try to create Layout instance
            try {
                $layout = new Layout($conf ?? []);
                echo "✅ Layout instance created successfully<br>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Layout instantiation failed: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Layout class not found</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error loading Layout.php: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ Abstract/Layout.php not found</div>";
}
echo "</div>";

// Check session
session_start();
echo "<div class='info'><h3>4. Session Check</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session active<br>";
    echo "Session ID: " . session_id() . "<br>";
    
    if (isset($_SESSION['user_id'])) {
        echo "✅ User logged in (ID: " . $_SESSION['user_id'] . ")<br>";
        
        if (isset($_SESSION['verified']) && $_SESSION['verified'] === true) {
            echo "✅ User verified<br>";
        } else {
            echo "<div class='warning'>⚠️ User not verified</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ User not logged in</div>";
    }
} else {
    echo "<div class='error'>❌ Session not active</div>";
}
echo "</div>";

// Check order parameter
echo "<div class='info'><h3>5. Order Parameter Check</h3>";
$order_id = $_GET['order'] ?? null;
if ($order_id) {
    echo "✅ Order ID provided: $order_id<br>";
    
    // Try to fetch order if we have database connection
    if (isset($pdo) && $pdo && isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                echo "✅ Order found<br>";
                echo "Order number: " . $order['order_number'] . "<br>";
                echo "Total amount: KSh " . number_format($order['total_amount'], 2) . "<br>";
                echo "Payment status: " . $order['payment_status'] . "<br>";
            } else {
                echo "<div class='error'>❌ Order not found or access denied</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error fetching order: " . $e->getMessage() . "</div>";
        }
    }
} else {
    echo "<div class='warning'>⚠️ No order ID provided in URL</div>";
}
echo "</div>";

// Check required tables
echo "<div class='info'><h3>6. Database Tables Check</h3>";
if (isset($pdo) && $pdo) {
    $required_tables = ['orders', 'order_items', 'users', 'mpesa_transactions'];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists<br>";
                
                // Check record count
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "&nbsp;&nbsp;&nbsp;Records: $count<br>";
            } else {
                echo "<div class='error'>❌ Table '$table' missing</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</div>";
        }
    }
}
echo "</div>";

// Check PHP extensions
echo "<div class='info'><h3>7. PHP Extensions Check</h3>";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "<div class='error'>❌ $ext extension missing</div>";
    }
}
echo "</div>";

// Try to simulate the M-Pesa page loading
echo "<div class='info'><h3>8. M-Pesa Page Simulation</h3>";
try {
    // Simulate the exact conditions of mpesa_payment_page.php
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
        echo "<div class='warning'>⚠️ Would redirect to login (user not authenticated)</div>";
    } else {
        echo "✅ Authentication check passed<br>";
        
        if (!$order_id) {
            echo "<div class='warning'>⚠️ Would redirect to orders (no order ID)</div>";
        } else {
            echo "✅ Order ID validation passed<br>";
            
            if (isset($pdo) && $pdo) {
                echo "✅ Database connection check passed<br>";
                echo "✅ All checks passed - M-Pesa page should load normally<br>";
            } else {
                echo "<div class='error'>❌ Database connection failed</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Simulation error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Show error log if accessible
echo "<div class='info'><h3>9. Recent PHP Error Log</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Error log location: $error_log<br>";
    $recent_errors = tail($error_log, 10);
    if ($recent_errors) {
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo htmlspecialchars(implode('', $recent_errors));
        echo "</pre>";
    }
} else {
    echo "Error log not found or not accessible<br>";
}
echo "</div>";

// Helper function to read last lines of a file
function tail($filename, $lines = 10) {
    if (!file_exists($filename)) return false;
    
    $handle = fopen($filename, 'r');
    if (!$handle) return false;
    
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines-$linecounter-1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    return array_reverse($text);
}

echo "<hr>";
echo "<h3>Quick Actions</h3>";
echo "<ul>";
echo "<li><a href='mpesa_payment_page.php" . ($order_id ? "?order=$order_id" : "") . "'>Try M-Pesa Payment Page</a></li>";
echo "<li><a href='orders.php'>View Orders</a></li>";
echo "<li><a href='dashboard.php'>Dashboard</a></li>";
echo "<li><a href='config.php' target='_blank'>View Config (if accessible)</a></li>";
echo "</ul>";
?>