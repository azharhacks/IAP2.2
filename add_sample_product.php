<?php
/**
 * Add Sample Product - 10 KSh Product for Testing
 */

require_once 'config.php';

try {
    // Check database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Sample product data
    $productData = [
        'name' => 'Sample Candy Bar',
        'description' => 'Delicious chocolate candy bar - perfect for testing payments. Sweet treat with premium chocolate coating and crispy center.',
        'price' => 10.00,
        'stock_quantity' => 100,
        'category_id' => 1, // Assuming category 1 exists, or we'll create it
        'image_url' => 'https://via.placeholder.com/300x300/ff6b35/ffffff?text=Candy+Bar',
        'status' => 'active',
        'weight' => 50, // 50 grams
        'dimensions' => '10x5x2 cm',
        'sku' => 'CANDY-001-' . date('Ymd')
    ];
    
    echo "🍭 Adding Sample Product to SMARTDUKA Database\n";
    echo "============================================\n\n";
    
    // First, ensure we have at least one category
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $categoryCount = $stmt->fetch()['count'];
    
    if ($categoryCount == 0) {
        echo "📂 Creating default category...\n";
        $stmt = $pdo->prepare("
            INSERT INTO categories (name, description, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute(['Snacks & Treats', 'Delicious snacks and sweet treats']);
        $categoryId = $pdo->lastInsertId();
        $productData['category_id'] = $categoryId;
        echo "   ✅ Category created with ID: $categoryId\n";
    } else {
        // Get the first available category
        $stmt = $pdo->query("SELECT id FROM categories LIMIT 1");
        $category = $stmt->fetch();
        $productData['category_id'] = $category['id'];
        echo "📂 Using existing category ID: " . $category['id'] . "\n";
    }
    
    // Check if product already exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? OR sku = ?");
    $stmt->execute([$productData['name'], $productData['sku']]);
    $existingProduct = $stmt->fetch();
    
    if ($existingProduct) {
        echo "⚠️  Product already exists with ID: " . $existingProduct['id'] . "\n";
        echo "   Updating existing product...\n";
        
        // Build update query based on available columns
        $updateParts = ['description = ?', 'price = ?', 'stock_quantity = ?'];
        $updateParams = [
            $productData['description'],
            $productData['price'],
            $productData['stock_quantity']
        ];
        
        if (in_array('category_id', $columns)) {
            $updateParts[] = 'category_id = ?';
            $updateParams[] = $productData['category_id'];
        }
        
        if (in_array('updated_at', $columns)) {
            $updateParts[] = 'updated_at = NOW()';
        }
        
        $updateParams[] = $existingProduct['id']; // WHERE condition
        
        $sql = "UPDATE products SET " . implode(', ', $updateParts) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateParams);
        
        $productId = $existingProduct['id'];
        echo "   ✅ Product updated successfully!\n";
        
    } else {
        echo "➕ Adding new product...\n";
        
        // Check what columns exist in the products table
        $stmt = $pdo->query("DESCRIBE products");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Build query based on available columns
        $insertColumns = ['name', 'description', 'price', 'stock_quantity'];
        $insertValues = ['?', '?', '?', '?'];
        $executeParams = [
            $productData['name'],
            $productData['description'],
            $productData['price'],
            $productData['stock_quantity']
        ];
        
        // Add optional columns if they exist
        if (in_array('category_id', $columns)) {
            $insertColumns[] = 'category_id';
            $insertValues[] = '?';
            $executeParams[] = $productData['category_id'];
        }
        
        if (in_array('sku', $columns)) {
            $insertColumns[] = 'sku';
            $insertValues[] = '?';
            $executeParams[] = $productData['sku'];
        }
        
        if (in_array('status', $columns)) {
            $insertColumns[] = 'status';
            $insertValues[] = '?';
            $executeParams[] = $productData['status'];
        }
        
        if (in_array('created_at', $columns)) {
            $insertColumns[] = 'created_at';
            $insertValues[] = 'NOW()';
        }
        
        if (in_array('updated_at', $columns)) {
            $insertColumns[] = 'updated_at';
            $insertValues[] = 'NOW()';
        }
        
        $sql = "INSERT INTO products (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($executeParams);
        
        $productId = $pdo->lastInsertId();
        echo "   ✅ Product added successfully!\n";
    }
    
    echo "\n📋 Product Details:\n";
    echo "   ID: $productId\n";
    echo "   Name: " . $productData['name'] . "\n";
    echo "   Price: KSh " . number_format($productData['price'], 2) . "\n";
    echo "   Stock: " . $productData['stock_quantity'] . " units\n";
    echo "   SKU: " . $productData['sku'] . "\n";
    echo "   Category ID: " . $productData['category_id'] . "\n";
    
    // Verify the product was added
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo "\n✅ Product Successfully Added to Database!\n";
        echo "=========================================\n";
        echo "🌐 You can now:\n";
        echo "   • View it at: http://localhost/IAP2.2Dev/products.php\n";
        echo "   • Add to cart and test M-Pesa payment\n";
        echo "   • Use for testing 10 KSh payments\n";
        echo "\n🛒 Perfect for testing the M-Pesa payment system!\n";
    } else {
        throw new Exception("Failed to verify product creation");
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>