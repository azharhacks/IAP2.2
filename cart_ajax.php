<?php
/**
 * Cart AJAX Handler
 * Handles AJAX requests for cart operations (add, update, remove items)
 * Returns JSON responses for frontend JavaScript
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize database connection
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Initialize cart manager
$cartManager = new CartManager($pdo);

// Get the action from POST data
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            /**
             * Add item to cart
             * Required: product_id, quantity
             */
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            
            if (!$productId || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
                exit;
            }
            
            // Check if product exists and is active
            $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            // Check stock availability
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Only ' . $product['stock_quantity'] . ' items available in stock'
                ]);
                exit;
            }
            
            // Add to cart
            $userId = $_SESSION['user_id'] ?? null;
            $result = $cartManager->addToCart($productId, $quantity, $userId);
            
            if ($result) {
                // Get updated cart totals
                $totals = $cartManager->getCartTotals($userId);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Product added to cart successfully',
                    'cart_count' => $totals['total_items'],
                    'cart_total' => number_format($totals['total'], 2)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
            }
            break;
            
        case 'update':
            /**
             * Update item quantity in cart
             * Required: product_id, quantity
             */
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            
            if ($quantity <= 0) {
                // Remove item if quantity is 0 or negative
                $result = $cartManager->removeFromCart($productId, $userId);
                $message = 'Item removed from cart';
            } else {
                // Check stock availability
                $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND is_active = TRUE");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    echo json_encode(['success' => false, 'message' => 'Product not found']);
                    exit;
                }
                
                if ($product['stock_quantity'] < $quantity) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Only ' . $product['stock_quantity'] . ' items available in stock'
                    ]);
                    exit;
                }
                
                // Update quantity
                $result = $cartManager->updateCartItem($productId, $quantity, $userId);
                $message = 'Cart updated successfully';
            }
            
            if ($result) {
                // Get updated cart totals
                $totals = $cartManager->getCartTotals($userId);
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'cart_count' => $totals['total_items'],
                    'cart_total' => number_format($totals['total'], 2),
                    'subtotal' => number_format($totals['subtotal'], 2),
                    'tax' => number_format($totals['tax'], 2),
                    'shipping' => number_format($totals['shipping'], 2)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
            }
            break;
            
        case 'remove':
            /**
             * Remove item from cart
             * Required: product_id
             */
            $productId = (int)($_POST['product_id'] ?? 0);
            
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                exit;
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            $result = $cartManager->removeFromCart($productId, $userId);
            
            if ($result) {
                // Get updated cart totals
                $totals = $cartManager->getCartTotals($userId);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Item removed from cart',
                    'cart_count' => $totals['total_items'],
                    'cart_total' => number_format($totals['total'], 2),
                    'subtotal' => number_format($totals['subtotal'], 2),
                    'tax' => number_format($totals['tax'], 2),
                    'shipping' => number_format($totals['shipping'], 2)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart']);
            }
            break;
            
        case 'clear':
            /**
             * Clear all items from cart
             */
            $userId = $_SESSION['user_id'] ?? null;
            $result = $cartManager->clearCart($userId);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cart cleared successfully',
                    'cart_count' => 0,
                    'cart_total' => '0.00'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
            break;
            
        case 'get_totals':
            /**
             * Get current cart totals
             */
            $userId = $_SESSION['user_id'] ?? null;
            $totals = $cartManager->getCartTotals($userId);
            
            echo json_encode([
                'success' => true,
                'cart_count' => $totals['total_items'],
                'cart_total' => number_format($totals['total'], 2),
                'subtotal' => number_format($totals['subtotal'], 2),
                'tax' => number_format($totals['tax'], 2),
                'shipping' => number_format($totals['shipping'], 2),
                'free_shipping_eligible' => $totals['subtotal'] >= 5000
            ]);
            break;
            
        case 'get_items':
            /**
             * Get all cart items
             */
            $userId = $_SESSION['user_id'] ?? null;
            $items = $cartManager->getCartItems($userId);
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    // Log error (in production, use proper logging)
    error_log("Cart AJAX Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
