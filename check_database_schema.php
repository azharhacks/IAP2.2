<?php
/**
 * Database Schema Checker - SMARTDUKA
 * Check for missing columns and table structure issues
 */

require_once 'config.php';

echo "<h1>🔍 Database Schema Checker</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 10px 0; border-radius: 5px; }
.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f8f9fa; }
</style>";

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    echo "<div class='success'>✅ Database connected</div>";
    
    // Check all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'><h3>Database Tables Found</h3>";
    echo "Found " . count($tables) . " tables: " . implode(', ', $tables);
    echo "</div>";
    
    // Check specific tables and their columns
    $requiredTables = [
        'users' => ['id', 'username', 'email', 'password', 'created_at'],
        'orders' => ['id', 'order_number', 'user_id', 'total_amount', 'order_status', 'payment_status', 'created_at'],
        'order_items' => ['id', 'order_id', 'product_id', 'product_name', 'quantity', 'price'],
        'products' => ['id', 'name', 'price', 'stock_quantity'],
        'mpesa_transactions' => ['id', 'order_id', 'checkout_request_id', 'status']
    ];
    
    foreach ($requiredTables as $tableName => $requiredColumns) {
        echo "<div class='info'><h3>Table: $tableName</h3>";
        
        if (in_array($tableName, $tables)) {
            echo "✅ Table exists<br>";
            
            // Get actual columns
            $stmt = $pdo->query("DESCRIBE $tableName");
            $actualColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $actualColumnNames = array_column($actualColumns, 'Field');
            
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";
            
            foreach ($actualColumns as $column) {
                $isRequired = in_array($column['Field'], $requiredColumns);
                $status = $isRequired ? '✅ Required' : '📋 Optional';
                
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check for missing required columns
            $missingColumns = array_diff($requiredColumns, $actualColumnNames);
            if (!empty($missingColumns)) {
                echo "<div class='warning'>⚠️ Missing required columns: " . implode(', ', $missingColumns) . "</div>";
            } else {
                echo "<div class='success'>✅ All required columns present</div>";
            }
            
        } else {
            echo "<div class='error'>❌ Table missing</div>";
        }
        echo "</div>";
    }
    
    // Check for common issues
    echo "<div class='info'><h3>Common Schema Issues</h3>";
    
    // Check users table for phone column
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("DESCRIBE users");
        $userColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        if (in_array('phone', $userColumns)) {
            echo "✅ users.phone column exists<br>";
        } else {
            echo "<div class='warning'>⚠️ users.phone column missing - M-Pesa payment page will need phone input from user</div>";
        }
    }
    
    // Check orders table for status columns
    if (in_array('orders', $tables)) {
        $stmt = $pdo->query("DESCRIBE orders");
        $orderColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        $statusColumns = ['order_status', 'payment_status', 'payment_method', 'shipping_address'];
        foreach ($statusColumns as $col) {
            if (in_array($col, $orderColumns)) {
                echo "✅ orders.$col column exists<br>";
            } else {
                echo "<div class='warning'>⚠️ orders.$col column missing</div>";
            }
        }
    }
    
    echo "</div>";
    
    // SQL suggestions for missing columns
    echo "<div class='info'><h3>SQL Fixes for Missing Columns</h3>";
    echo "<pre>";
    echo "-- Add phone column to users table\n";
    echo "ALTER TABLE users ADD COLUMN phone VARCHAR(15) NULL;\n\n";
    
    echo "-- Add status columns to orders table\n";
    echo "ALTER TABLE orders ADD COLUMN order_status ENUM('pending', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending';\n";
    echo "ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending';\n";
    echo "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) NULL;\n";
    echo "ALTER TABLE orders ADD COLUMN shipping_address TEXT NULL;\n\n";
    
    echo "-- Create mpesa_transactions table if missing\n";
    echo "CREATE TABLE IF NOT EXISTS mpesa_transactions (\n";
    echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    order_id INT NOT NULL,\n";
    echo "    checkout_request_id VARCHAR(255) NOT NULL,\n";
    echo "    merchant_request_id VARCHAR(255) NULL,\n";
    echo "    phone_number VARCHAR(15) NOT NULL,\n";
    echo "    amount DECIMAL(10,2) NOT NULL,\n";
    echo "    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',\n";
    echo "    transaction_id VARCHAR(255) NULL,\n";
    echo "    result_desc TEXT NULL,\n";
    echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
    echo "    FOREIGN KEY (order_id) REFERENCES orders(id)\n";
    echo ");\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>✅ Schema Check Complete</h3>";
    echo "<p>Review the issues above and run the suggested SQL fixes if needed.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}
?>