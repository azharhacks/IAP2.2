<?php
/**
 * Database Setup Test Script
 * Tests database connection and creates M-Pesa transactions table
 */

require_once __DIR__ . '/config.php';

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Database connection successful!\n";
    
    // Show existing tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📋 Existing tables: " . implode(', ', $tables) . "\n";
    
    // Create M-Pesa transactions table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS mpesa_transactions (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        checkout_request_id VARCHAR(100) UNIQUE NOT NULL,
        merchant_request_id VARCHAR(100) NOT NULL,
        phone_number VARCHAR(15) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        mpesa_receipt_number VARCHAR(50) NULL,
        transaction_date TIMESTAMP NULL,
        result_code INT NULL,
        result_desc TEXT NULL,
        callback_metadata JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_order_id (order_id),
        INDEX idx_checkout_request (checkout_request_id),
        INDEX idx_phone (phone_number),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    )";
    
    $pdo->exec($createTableSQL);
    echo "✅ M-Pesa transactions table created successfully!\n";
    
    // Verify table was created
    $stmt = $pdo->query("DESCRIBE mpesa_transactions");
    $columns = $stmt->fetchAll();
    echo "📊 M-Pesa transactions table structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']}\n";
    }
    
    // Test M-Pesa configuration
    echo "\n🔧 M-Pesa Configuration:\n";
    echo "  - Consumer Key: " . substr($conf['mpesa']['consumer_key'], 0, 10) . "...\n";
    echo "  - Environment: " . $conf['mpesa']['environment'] . "\n";
    echo "  - Short Code: " . $conf['mpesa']['short_code'] . "\n";
    echo "  - Callback URL: " . $conf['mpesa']['callback_url'] . "\n";
    
    echo "\n🎉 M-Pesa integration setup completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
