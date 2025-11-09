<?php
/**
 * Shopping Cart Management Class
 * Handles all cart-related operations
 * 
 * Features:
 * - Add/remove items from cart
 * - Update quantities
 * - Calculate totals and taxes
 * - Session-based cart for guests
 * - Database cart for logged-in users
 */

class CartManager {
    private $pdo;
    private $userId;
    
    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }
    
    /**
     * Add item to cart
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @return bool Success status
     */
    public function addToCart($productId, $quantity = 1) {
        if (!$this->userId) {
            return $this->addToSessionCart($productId, $quantity);
        }
        
        // Check if product exists and is available
        $stmt = $this->pdo->prepare("
            SELECT id, name, price, stock_quantity 
            FROM products 
            WHERE id = ? AND is_active = TRUE
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || $product['stock_quantity'] < $quantity) {
            return false;
        }
        
        // Check if item already in cart
        $stmt = $this->pdo->prepare("
            SELECT id, quantity FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$this->userId, $productId]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingItem) {
            // Update existing item
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock_quantity']) {
                $newQuantity = $product['stock_quantity'];
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE cart 
                SET quantity = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$newQuantity, $existingItem['id']]);
        } else {
            // Add new item
            $stmt = $this->pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$this->userId, $productId, $quantity, $product['price']]);
        }
    }
    
    /**
     * Update cart item quantity
     * @param int $productId Product ID
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function updateCartItem($productId, $quantity) {
        if (!$this->userId) {
            return $this->updateSessionCartItem($productId, $quantity);
        }
        
        if ($quantity <= 0) {
            return $this->removeFromCart($productId);
        }
        
        // Check stock availability
        $stmt = $this->pdo->prepare("
            SELECT stock_quantity FROM products 
            WHERE id = ? AND is_active = TRUE
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || $quantity > $product['stock_quantity']) {
            return false;
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE cart 
            SET quantity = ?, updated_at = NOW() 
            WHERE user_id = ? AND product_id = ?
        ");
        return $stmt->execute([$quantity, $this->userId, $productId]);
    }
    
    /**
     * Remove item from cart
     * @param int $productId Product ID
     * @return bool Success status
     */
    public function removeFromCart($productId) {
        if (!$this->userId) {
            return $this->removeFromSessionCart($productId);
        }
        
        $stmt = $this->pdo->prepare("
            DELETE FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        return $stmt->execute([$this->userId, $productId]);
    }
    
    /**
     * Get cart items with product details
     * @return array Cart items
     */
    public function getCartItems() {
        if (!$this->userId) {
            return $this->getSessionCartItems();
        }
        
        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   p.name as product_name, 
                   p.price as current_price, 
                   p.stock_quantity,
                   b.name as brand_name,
                   pi.image_url, 
                   pi.alt_text,
                   (c.quantity * c.price) as item_total
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
            WHERE c.user_id = ? AND p.is_active = TRUE
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get cart totals
     * @return array Cart totals
     */
    public function getCartTotals() {
        $items = $this->getCartItems();
        $subtotal = 0;
        $itemCount = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['item_total'];
            $itemCount += $item['quantity'];
        }
        
        // Calculate tax (16% VAT in Kenya)
        $taxRate = 0.16;
        $taxAmount = $subtotal * $taxRate;
        
        // Calculate shipping (free for orders over KSh 5000)
        $shippingCost = $subtotal >= 5000 ? 0 : 500;
        
        $total = $subtotal + $taxAmount + $shippingCost;
        
        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'item_count' => $itemCount,
            'total_items' => $itemCount, // Added for compatibility
            'items' => $items
        ];
    }
    
    /**
     * Clear cart
     * @return bool Success status
     */
    public function clearCart() {
        if (!$this->userId) {
            unset($_SESSION['cart']);
            return true;
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$this->userId]);
    }
    
    /**
     * Merge session cart with database cart when user logs in
     * @return bool Success status
     */
    public function mergeSessionCart() {
        if (!isset($_SESSION['cart']) || !$this->userId) {
            return false;
        }
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $this->addToCart($productId, $quantity);
        }
        
        unset($_SESSION['cart']);
        return true;
    }
    
    // Session cart methods for guest users
    private function addToSessionCart($productId, $quantity) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        return true;
    }
    
    private function updateSessionCartItem($productId, $quantity) {
        if (!isset($_SESSION['cart'])) {
            return false;
        }
        
        if ($quantity <= 0) {
            return $this->removeFromSessionCart($productId);
        }
        
        $_SESSION['cart'][$productId] = $quantity;
        return true;
    }
    
    private function removeFromSessionCart($productId) {
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            return true;
        }
        return false;
    }
    
    private function getSessionCartItems() {
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return [];
        }
        
        $productIds = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   b.name as brand_name,
                   pi.image_url, 
                   pi.alt_text
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
            WHERE p.id IN ($placeholders) AND p.is_active = TRUE
        ");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $cartItems = [];
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $cartItems[] = [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'name' => $product['name'], // Keep both for compatibility
                'price' => $product['price'],
                'current_price' => $product['price'],
                'quantity' => $quantity,
                'stock_quantity' => $product['stock_quantity'],
                'brand_name' => $product['brand_name'],
                'image_url' => $product['image_url'],
                'alt_text' => $product['alt_text'],
                'item_total' => $product['price'] * $quantity
            ];
        }
        
        return $cartItems;
    }
}
?>
