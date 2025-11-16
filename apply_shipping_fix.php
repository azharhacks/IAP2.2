<?php
/**
 * Apply Shipping Fixes - SMARTDUKA
 * Handler for shipping cost configuration changes
 */

session_start();
require_once 'config.php';

// Set JSON response headers
header('Content-Type: application/json');

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'free_shipping':
            echo json_encode(setFreeShipping($pdo));
            break;
            
        case 'custom_shipping':
            $cost = $data['shipping_cost'] ?? 0;
            $threshold = $data['free_threshold'] ?? 0;
            echo json_encode(setCustomShipping($pdo, $cost, $threshold));
            break;
            
        case 'add_shipping_column':
            echo json_encode(addShippingColumn($pdo));
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log('Shipping fix error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Set free shipping for all orders
 */
function setFreeShipping($pdo) {
    try {
        $pdo->beginTransaction();
        
        // Add shipping_cost column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00");
        } catch (Exception $e) {
            // Column might already exist, that's okay
        }
        
        // Update all existing orders to have 0 shipping cost
        $stmt = $pdo->exec("UPDATE orders SET shipping_cost = 0.00");
        
        // Create or update shipping configuration
        createShippingConfig(0, 0);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => "Free shipping enabled! Updated $stmt existing orders and created configuration."
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to set free shipping: ' . $e->getMessage());
    }
}

/**
 * Set custom shipping rates
 */
function setCustomShipping($pdo, $cost, $threshold) {
    try {
        $pdo->beginTransaction();
        
        // Add shipping_cost column if it doesn't exist
        try {
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT $cost");
        } catch (Exception $e) {
            // Column might already exist, that's okay
        }
        
        // Update existing orders based on threshold
        if ($threshold > 0) {
            // Apply shipping cost only to orders under threshold
            $stmt1 = $pdo->prepare("
                UPDATE orders 
                SET shipping_cost = ? 
                WHERE (total_amount - COALESCE(shipping_cost, 0)) < ?
            ");
            $stmt1->execute([$cost, $threshold]);
            
            $stmt2 = $pdo->prepare("
                UPDATE orders 
                SET shipping_cost = 0.00 
                WHERE (total_amount - COALESCE(shipping_cost, 0)) >= ?
            ");
            $stmt2->execute([$threshold]);
            
            $updated = $stmt1->rowCount() + $stmt2->rowCount();
        } else {
            // Apply shipping cost to all orders
            $stmt = $pdo->prepare("UPDATE orders SET shipping_cost = ?");
            $stmt->execute([$cost]);
            $updated = $stmt->rowCount();
        }
        
        // Create shipping configuration
        createShippingConfig($cost, $threshold);
        
        $pdo->commit();
        
        $message = "Custom shipping rates applied!\n";
        $message .= "• Shipping cost: KSh " . number_format($cost, 2) . "\n";
        $message .= "• Free shipping threshold: KSh " . number_format($threshold, 2) . "\n";
        $message .= "• Updated $updated existing orders";
        
        return [
            'success' => true,
            'message' => $message
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to set custom shipping: ' . $e->getMessage());
    }
}

/**
 * Add shipping_cost column to orders table
 */
function addShippingColumn($pdo) {
    try {
        // Check if column already exists
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_cost'");
        if ($stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'shipping_cost column already exists in orders table'
            ];
        }
        
        // Add the column
        $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount");
        
        // Update existing orders to extract shipping from total
        $stmt = $pdo->query("
            UPDATE orders o
            SET shipping_cost = CASE 
                WHEN total_amount > (
                    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) 
                    FROM order_items oi 
                    WHERE oi.order_id = o.id
                ) THEN total_amount - (
                    SELECT COALESCE(SUM(oi.quantity * oi.price), 0) 
                    FROM order_items oi 
                    WHERE oi.order_id = o.id
                )
                ELSE 0.00
            END
        ");
        
        $updated = $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => "Successfully added shipping_cost column and updated $updated existing orders"
        ];
        
    } catch (Exception $e) {
        throw new Exception('Failed to add shipping column: ' . $e->getMessage());
    }
}

/**
 * Create shipping configuration file
 */
function createShippingConfig($cost, $threshold) {
    $config = "<?php\n";
    $config .= "/**\n";
    $config .= " * Shipping Configuration - SMARTDUKA\n";
    $config .= " * Auto-generated shipping settings\n";
    $config .= " */\n\n";
    $config .= "// Shipping costs\n";
    $config .= "define('DEFAULT_SHIPPING_COST', $cost);\n";
    $config .= "define('FREE_SHIPPING_THRESHOLD', $threshold);\n";
    $config .= "define('EXPRESS_SHIPPING_COST', " . ($cost * 2) . ");\n\n";
    $config .= "/**\n";
    $config .= " * Calculate shipping cost based on order total\n";
    $config .= " */\n";
    $config .= "function calculateShipping(\$orderTotal, \$isExpress = false) {\n";
    $config .= "    if (\$orderTotal >= FREE_SHIPPING_THRESHOLD && FREE_SHIPPING_THRESHOLD > 0) {\n";
    $config .= "        return 0; // Free shipping\n";
    $config .= "    }\n";
    $config .= "    \n";
    $config .= "    return \$isExpress ? EXPRESS_SHIPPING_COST : DEFAULT_SHIPPING_COST;\n";
    $config .= "}\n\n";
    $config .= "/**\n";
    $config .= " * Get shipping message for display\n";
    $config .= " */\n";
    $config .= "function getShippingMessage(\$orderTotal) {\n";
    $config .= "    \$shipping = calculateShipping(\$orderTotal);\n";
    $config .= "    \n";
    $config .= "    if (\$shipping == 0) {\n";
    $config .= "        return 'Free Shipping!';\n";
    $config .= "    }\n";
    $config .= "    \n";
    $config .= "    if (FREE_SHIPPING_THRESHOLD > 0 && \$orderTotal < FREE_SHIPPING_THRESHOLD) {\n";
    $config .= "        \$needed = FREE_SHIPPING_THRESHOLD - \$orderTotal;\n";
    $config .= "        return 'Add KSh ' . number_format(\$needed, 2) . ' for free shipping';\n";
    $config .= "    }\n";
    $config .= "    \n";
    $config .= "    return 'Shipping: KSh ' . number_format(\$shipping, 2);\n";
    $config .= "}\n";
    $config .= "?>";
    
    file_put_contents('shipping_config.php', $config);
}
?>