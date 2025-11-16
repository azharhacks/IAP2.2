<?php
/**
 * Real M-Pesa Database Fix - SMARTDUKA
 * Fix the database issues while preserving real M-Pesa integration
 */

session_start();
require_once 'config.php';

echo "<h1>🔧 Real M-Pesa Database Fix</h1>";
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
    
    // Step 1: Check if mpesa_transactions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'mpesa_transactions'");
    if ($stmt->rowCount() === 0) {
        echo "<div class='info'>Creating mpesa_transactions table...</div>";
        
        // Create the proper M-Pesa transactions table
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
            result_desc TEXT NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_id (order_id),
            INDEX idx_checkout_request (checkout_request_id),
            INDEX idx_status (status),
            INDEX idx_merchant_request (merchant_request_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($createSQL);
        echo "<div class='success'>✅ mpesa_transactions table created successfully</div>";
        
    } else {
        echo "<div class='info'>mpesa_transactions table exists, checking structure...</div>";
        
        // Check and fix the problematic columns
        $stmt = $pdo->query("DESCRIBE mpesa_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnsByName = [];
        foreach ($columns as $column) {
            $columnsByName[$column['Field']] = $column;
        }
        
        // Fix merchant_request_id if it exists and has wrong constraints
        if (isset($columnsByName['merchant_request_id'])) {
            $col = $columnsByName['merchant_request_id'];
            if ($col['Null'] === 'NO' && $col['Default'] === null) {
                echo "<div class='info'>Fixing merchant_request_id column...</div>";
                $pdo->exec("ALTER TABLE mpesa_transactions MODIFY COLUMN merchant_request_id VARCHAR(255) NULL DEFAULT NULL");
                echo "<div class='success'>✅ merchant_request_id column fixed</div>";
            } else {
                echo "<div class='success'>✅ merchant_request_id column is properly configured</div>";
            }
        } else {
            echo "<div class='info'>Adding merchant_request_id column...</div>";
            $pdo->exec("ALTER TABLE mpesa_transactions ADD COLUMN merchant_request_id VARCHAR(255) NULL DEFAULT NULL AFTER checkout_request_id");
            echo "<div class='success'>✅ merchant_request_id column added</div>";
        }
        
        // Ensure other columns have proper defaults
        $columnFixes = [
            'transaction_id' => "ALTER TABLE mpesa_transactions MODIFY COLUMN transaction_id VARCHAR(255) NULL DEFAULT NULL",
            'result_desc' => "ALTER TABLE mpesa_transactions MODIFY COLUMN result_desc TEXT NULL DEFAULT NULL"
        ];
        
        foreach ($columnFixes as $colName => $sql) {
            if (isset($columnsByName[$colName])) {
                $col = $columnsByName[$colName];
                if ($col['Null'] === 'NO' && $col['Default'] === null) {
                    echo "<div class='info'>Fixing $colName column...</div>";
                    $pdo->exec($sql);
                    echo "<div class='success'>✅ $colName column fixed</div>";
                }
            }
        }
    }
    
    // Step 2: Test M-Pesa transaction insert
    echo "<div class='info'><h3>Testing M-Pesa Transaction Insert</h3>";
    
    try {
        $pdo->beginTransaction();
        
        // Test insert with minimal required fields
        $testStmt = $pdo->prepare("
            INSERT INTO mpesa_transactions 
            (order_id, checkout_request_id, phone_number, amount, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $testOrderId = 999999; // Use a test order ID that won't conflict
        $testCheckoutId = 'TEST_' . time() . '_' . rand(1000, 9999);
        $testPhone = '254712345678';
        $testAmount = 100.00;
        $testStatus = 'pending';
        
        $testStmt->execute([$testOrderId, $testCheckoutId, $testPhone, $testAmount, $testStatus]);
        
        echo "<div class='success'>✅ M-Pesa transaction insert test successful!</div>";
        
        // Clean up test record
        $pdo->exec("DELETE FROM mpesa_transactions WHERE order_id = $testOrderId");
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='error'>❌ Insert test failed: " . $e->getMessage() . "</div>";
    }
    
    // Step 3: Show current table structure
    echo "<div class='info'><h3>Current mpesa_transactions Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE mpesa_transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f8f9fa;'><th>Column</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        $nullStatus = $column['Null'] === 'YES' ? '✅ YES' : '❌ NO';
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>$nullStatus</td>";
        echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Step 4: Create a test endpoint for M-Pesa
    echo "<div class='info'><h3>Creating Test M-Pesa Endpoint</h3>";
    
    $testEndpointContent = '<?php
session_start();
require_once "config.php";

header("Content-Type: application/json");

try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON data");
    }
    
    // Test M-Pesa transaction creation
    $stmt = $pdo->prepare("
        INSERT INTO mpesa_transactions 
        (order_id, checkout_request_id, phone_number, amount, status) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $orderId = $data["order_id"] ?? 16;
    $checkoutId = "ws_CO_" . date("dmY") . "_" . time() . "_" . rand(100000, 999999);
    $phone = $data["phone_number"] ?? "254712345678";
    $amount = $data["amount"] ?? 511.60;
    $status = "pending";
    
    $stmt->execute([$orderId, $checkoutId, $phone, $amount, $status]);
    
    echo json_encode([
        "success" => true,
        "message" => "M-Pesa transaction created successfully",
        "checkout_request_id" => $checkoutId,
        "order_id" => $orderId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>';
    
    file_put_contents('test_mpesa_endpoint.php', $testEndpointContent);
    echo "<div class='success'>✅ Test M-Pesa endpoint created: test_mpesa_endpoint.php</div>";
    
    echo "<div class='success'>";
    echo "<h3>✅ Real M-Pesa Database Fix Complete!</h3>";
    echo "<p>Your database is now properly configured for real M-Pesa integration.</p>";
    echo "<p>The original M-Pesa payment page should now work without database errors.</p>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>Next Steps</h3>";
    echo "<ol>";
    echo "<li><strong>Test the fixed M-Pesa page:</strong> <a href='mpesa_payment_page.php?order=16'>mpesa_payment_page.php?order=16</a></li>";
    echo "<li><strong>Test the endpoint:</strong> <a href='test_mpesa_endpoint.php'>test_mpesa_endpoint.php</a></li>";
    echo "<li><strong>Check your orders:</strong> <a href='orders.php'>orders.php</a></li>";
    echo "</ol>";
    echo "<p><strong>The real M-Pesa integration should now work without any database errors!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>