<?php
/**
 * M-Pesa Database Quick Fix - SMARTDUKA
 * Fix the merchant_request_id column issue
 */

session_start();
require_once 'config.php';

echo "<h1>💳 M-Pesa Database Quick Fix</h1>";
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
    
    // Check if mpesa_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'mpesa_transactions'");
    if ($stmt->rowCount() === 0) {
        echo "<div class='info'>Creating mpesa_transactions table...</div>";
        
        // Create the table with proper defaults
        $createSQL = "
        CREATE TABLE mpesa_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            checkout_request_id VARCHAR(255) NOT NULL,
            merchant_request_id VARCHAR(255) NULL DEFAULT NULL,
            phone_number VARCHAR(15) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            transaction_id VARCHAR(255) NULL DEFAULT NULL,
            result_desc TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_checkout_request (checkout_request_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($createSQL);
        echo "<div class='success'>✅ mpesa_transactions table created successfully</div>";
        
    } else {
        echo "<div class='info'>mpesa_transactions table exists, checking columns...</div>";
        
        // Check and fix the merchant_request_id column
        $stmt = $pdo->query("DESCRIBE mpesa_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $merchantColumnExists = false;
        $needsDefaultFix = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'merchant_request_id') {
                $merchantColumnExists = true;
                if ($column['Null'] === 'NO' && $column['Default'] === null) {
                    $needsDefaultFix = true;
                }
            }
        }
        
        if (!$merchantColumnExists) {
            echo "<div class='info'>Adding merchant_request_id column...</div>";
            $pdo->exec("ALTER TABLE mpesa_transactions ADD COLUMN merchant_request_id VARCHAR(255) NULL DEFAULT NULL AFTER checkout_request_id");
            echo "<div class='success'>✅ merchant_request_id column added</div>";
            
        } elseif ($needsDefaultFix) {
            echo "<div class='info'>Fixing merchant_request_id column to allow NULL...</div>";
            $pdo->exec("ALTER TABLE mpesa_transactions MODIFY COLUMN merchant_request_id VARCHAR(255) NULL DEFAULT NULL");
            echo "<div class='success'>✅ merchant_request_id column fixed to allow NULL</div>";
            
        } else {
            echo "<div class='success'>✅ merchant_request_id column is properly configured</div>";
        }
    }
    
    // Test the fixed table
    echo "<div class='info'>Testing M-Pesa transaction insert...</div>";
    
    try {
        $testStmt = $pdo->prepare("
            INSERT INTO mpesa_transactions 
            (order_id, checkout_request_id, phone_number, amount, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $testOrderId = 999;
        $testCheckoutId = 'TEST_' . time();
        $testPhone = '254712345678';
        $testAmount = 100.00;
        $testStatus = 'pending';
        
        $testStmt->execute([$testOrderId, $testCheckoutId, $testPhone, $testAmount, $testStatus]);
        
        // Clean up test record
        $pdo->exec("DELETE FROM mpesa_transactions WHERE checkout_request_id = '$testCheckoutId'");
        
        echo "<div class='success'>✅ M-Pesa transaction insert test successful!</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Insert test failed: " . $e->getMessage() . "</div>";
    }
    
    // Show current table structure
    echo "<div class='info'><h3>Current mpesa_transactions Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE mpesa_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>✅ M-Pesa Database Fix Complete!</h3>";
    echo "<p>The database schema has been fixed. You should now be able to initiate M-Pesa payments without errors.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>

<div class="info">
    <h3>Next Steps</h3>
    <ul>
        <li><a href="mpesa_payment_page.php?order=16">Test M-Pesa Payment Again</a></li>
        <li><a href="debug_mpesa_status.php">Debug M-Pesa Status</a></li>
        <li><a href="orders.php">View Orders</a></li>
    </ul>
</div>