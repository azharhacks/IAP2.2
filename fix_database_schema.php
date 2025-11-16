<?php
/**
 * Database Schema Fixer - SMARTDUKA
 * Automatically add missing columns to fix database errors
 */

require_once 'config.php';

echo "<h1>🔧 Database Schema Auto-Fixer</h1>";
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
    
    // Function to check if column exists
    function columnExists($pdo, $table, $column) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Function to check if table exists
    function tableExists($pdo, $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    $fixes_applied = 0;
    
    // Fix 1: Add missing columns to users table
    echo "<div class='info'><h3>Fixing Users Table</h3>";
    if (tableExists($pdo, 'users')) {
        if (!columnExists($pdo, 'users', 'phone')) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(15) NULL AFTER email");
                echo "✅ Added phone column to users table<br>";
                $fixes_applied++;
            } catch (Exception $e) {
                echo "❌ Failed to add phone column: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ phone column already exists<br>";
        }
    } else {
        echo "❌ users table doesn't exist<br>";
    }
    echo "</div>";
    
    // Fix 2: Add missing columns to orders table
    echo "<div class='info'><h3>Fixing Orders Table</h3>";
    if (tableExists($pdo, 'orders')) {
        $orderColumns = [
            'order_status' => "ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending'",
            'payment_status' => "ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending'",
            'payment_method' => "VARCHAR(50) NULL",
            'shipping_address' => "TEXT NULL"
        ];
        
        foreach ($orderColumns as $column => $definition) {
            if (!columnExists($pdo, 'orders', $column)) {
                try {
                    $pdo->exec("ALTER TABLE orders ADD COLUMN $column $definition");
                    echo "✅ Added $column column to orders table<br>";
                    $fixes_applied++;
                } catch (Exception $e) {
                    echo "❌ Failed to add $column column: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "✅ $column column already exists<br>";
            }
        }
    } else {
        echo "❌ orders table doesn't exist<br>";
    }
    echo "</div>";
    
    // Fix 3: Add missing columns to order_items table
    echo "<div class='info'><h3>Fixing Order Items Table</h3>";
    if (tableExists($pdo, 'order_items')) {
        if (!columnExists($pdo, 'order_items', 'price')) {
            try {
                $pdo->exec("ALTER TABLE order_items ADD COLUMN price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER product_name");
                echo "✅ Added price column to order_items table<br>";
                $fixes_applied++;
            } catch (Exception $e) {
                echo "❌ Failed to add price column: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ price column already exists<br>";
        }
    } else {
        echo "❌ order_items table doesn't exist<br>";
    }
    echo "</div>";
    
    // Fix 4: Create mpesa_transactions table if missing
    echo "<div class='info'><h3>Fixing M-Pesa Transactions Table</h3>";
    if (!tableExists($pdo, 'mpesa_transactions')) {
        try {
            $createTableSQL = "
            CREATE TABLE mpesa_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                checkout_request_id VARCHAR(255) NOT NULL,
                merchant_request_id VARCHAR(255) NULL,
                phone_number VARCHAR(15) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                transaction_id VARCHAR(255) NULL,
                result_desc TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_order_id (order_id),
                INDEX idx_checkout_request (checkout_request_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $pdo->exec($createTableSQL);
            echo "✅ Created mpesa_transactions table<br>";
            $fixes_applied++;
        } catch (Exception $e) {
            echo "❌ Failed to create mpesa_transactions table: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ mpesa_transactions table already exists<br>";
    }
    echo "</div>";
    
    // Fix 5: Update existing orders with default values
    echo "<div class='info'><h3>Updating Existing Data</h3>";
    if (tableExists($pdo, 'orders')) {
        try {
            $stmt = $pdo->exec("UPDATE orders SET order_status = 'pending' WHERE order_status IS NULL OR order_status = ''");
            echo "✅ Updated $stmt orders with default order_status<br>";
            
            $stmt = $pdo->exec("UPDATE orders SET payment_status = 'pending' WHERE payment_status IS NULL OR payment_status = ''");
            echo "✅ Updated $stmt orders with default payment_status<br>";
            
            $fixes_applied++;
        } catch (Exception $e) {
            echo "❌ Failed to update existing data: " . $e->getMessage() . "<br>";
        }
    }
    echo "</div>";
    
    // Test the fixes
    echo "<div class='info'><h3>Testing Fixed Queries</h3>";
    
    // Test orders query
    try {
        $testStmt = $pdo->prepare("
            SELECT o.id, o.order_number, o.order_status, o.payment_status 
            FROM orders o 
            LIMIT 1
        ");
        $testStmt->execute();
        echo "✅ Orders query test passed<br>";
    } catch (Exception $e) {
        echo "❌ Orders query test failed: " . $e->getMessage() . "<br>";
    }
    
    // Test order_items query  
    try {
        $testStmt = $pdo->prepare("
            SELECT oi.product_name, oi.quantity, oi.price 
            FROM order_items oi 
            LIMIT 1
        ");
        $testStmt->execute();
        echo "✅ Order items query test passed<br>";
    } catch (Exception $e) {
        echo "❌ Order items query test failed: " . $e->getMessage() . "<br>";
    }
    
    // Test users query
    try {
        $testStmt = $pdo->prepare("
            SELECT u.username, u.email, u.phone 
            FROM users u 
            LIMIT 1
        ");
        $testStmt->execute();
        echo "✅ Users query test passed<br>";
    } catch (Exception $e) {
        echo "❌ Users query test failed: " . $e->getMessage() . "<br>";
    }
    
    echo "</div>";
    
    // Summary
    echo "<div class='success'>";
    echo "<h3>✅ Schema Fix Complete!</h3>";
    echo "<p><strong>Fixes applied:</strong> $fixes_applied</p>";
    if ($fixes_applied > 0) {
        echo "<p>Your database schema has been updated. The SMARTDUKA platform should now work without column errors.</p>";
    } else {
        echo "<p>No fixes were needed. Your database schema is already up to date.</p>";
    }
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Next Steps</h3>";
    echo "<ul>";
    echo "<li><a href='orders.php'>Test Orders Page</a></li>";
    echo "<li><a href='mpesa_payment_page.php?order=14'>Test M-Pesa Payment</a></li>";
    echo "<li><a href='check_database_schema.php'>Run Full Schema Check</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>