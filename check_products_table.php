<?php
/**
 * Check Products Table Structure - SMARTDUKA Database
 */

require_once 'config.php';

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    echo "🔍 SMARTDUKA Products Table Structure\n";
    echo "====================================\n\n";
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Available Columns:\n";
    foreach ($columns as $column) {
        $nullable = $column['Null'] === 'YES' ? '(nullable)' : '(required)';
        $default = $column['Default'] ? "default: {$column['Default']}" : '';
        echo "   • {$column['Field']} - {$column['Type']} $nullable $default\n";
    }
    
    echo "\n📊 Current Products Count:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch()['count'];
    echo "   Total products: $count\n";
    
    if ($count > 0) {
        echo "\n🛒 Sample Products:\n";
        $stmt = $pdo->query("SELECT id, name, price, stock_quantity FROM products LIMIT 5");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            echo "   • ID {$product['id']}: {$product['name']} - KSh " . number_format($product['price'], 2) . " (Stock: {$product['stock_quantity']})\n";
        }
    }
    
    echo "\n✅ Table structure check complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>