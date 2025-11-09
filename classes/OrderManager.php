<?php
/**
 * Order Management Class
 * Handles all order-related operations
 * 
 * Features:
 * - Order creation and processing
 * - Order stat        $stmt = $this->conn->prepare("
            SELECT o.*, 
                   COUNT(oi.id) as item_count,
                   GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ");
        $stmt->execute([$userId]);t
 * - Order tracking and history
 * - Payment processing integration
 * - Shipping and delivery management
 */

class OrderManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new order from cart
     * @param int $userId User ID
     * @param array $shippingAddress Shipping address
     * @param string $paymentMethod Payment method
     * @param array $orderData Additional order data
     * @return array Order result with order ID or error
     */
    public function createOrder($userId, $shippingAddress, $paymentMethod, $orderData = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Get cart items - check session cart first, then database cart
            $cartManager = new CartManager($this->pdo);
            $sessionCartTotals = $cartManager->getCartTotals();
            
            // If session cart is empty, try database cart
            if (empty($sessionCartTotals['items'])) {
                $cartManager = new CartManager($this->pdo, $userId);
                $cartTotals = $cartManager->getCartTotals();
            } else {
                $cartTotals = $sessionCartTotals;
            }
            
            if (empty($cartTotals['items'])) {
                throw new Exception('Cart is empty');
            }
            
            // Generate order number
            $orderNumber = $this->generateOrderNumber();
            
            // Prepare shipping address string
            $shippingAddressStr = $this->formatShippingAddress($shippingAddress);
            
            // Create order
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    order_number, user_id, subtotal, tax_amount, shipping_cost, 
                    total_amount, status, payment_status, payment_method, 
                    shipping_address, shipping_method, notes
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, ?)
            ");
            
            $shippingMethod = $orderData['shipping_method'] ?? 'Standard Delivery';
            $notes = $orderData['notes'] ?? '';
            
            $stmt->execute([
                $orderNumber,
                $userId,
                $cartTotals['subtotal'],
                $cartTotals['tax_amount'],
                $cartTotals['shipping_cost'],
                $cartTotals['total'],
                $paymentMethod,
                $shippingAddressStr,
                $shippingMethod,
                $notes
            ]);
            
            $orderId = $this->pdo->lastInsertId();
            
            // Add order items
            foreach ($cartTotals['items'] as $item) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, product_name, product_sku,
                        quantity, unit_price, total_price
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    '', // SKU will be fetched from product
                    $item['quantity'],
                    $item['price'],
                    $item['item_total']
                ]);
                
                // Update product stock
                $stmt = $this->pdo->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Add initial status history
            $this->addStatusHistory($orderId, 'pending', 'Order placed successfully');
            
            // Clear cart (both session and database)
            $sessionCartManager = new CartManager($this->pdo);
            $sessionCartManager->clearCart();
            $dbCartManager = new CartManager($this->pdo, $userId);
            $dbCartManager->clearCart();
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $cartTotals['total']
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user orders with pagination
     * @param int $userId User ID
     * @param int $page Current page
     * @param int $perPage Orders per page
     * @return array Orders and pagination info
     */
    public function getUserOrders($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM orders WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $totalOrders = $stmt->fetch()['total'];
        
        // Get orders
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
                   COUNT(oi.id) as item_count,
                   GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ");
        $stmt->execute([$userId]);
        
        return [
            'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $totalOrders,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalOrders / $perPage)
        ];
    }
    
    /**
     * Get order details by ID
     * @param int $orderId Order ID
     * @param int $userId User ID (for security)
     * @return array|null Order details
     */
    public function getOrderDetails($orderId, $userId = null) {
        $whereClause = $userId ? "WHERE o.id = ? AND o.user_id = ?" : "WHERE o.id = ?";
        $params = $userId ? [$orderId, $userId] : [$orderId];
        
        // Get order info
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.username, u.email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            $whereClause
        ");
        $stmt->execute($params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) return null;
        
        // Get order items
        $stmt = $this->pdo->prepare("
            SELECT oi.*, p.sku, pi.image_url
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
            WHERE oi.order_id = ?
            ORDER BY oi.id
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get status history
        $stmt = $this->pdo->prepare("
            SELECT * FROM order_status_history 
            WHERE order_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$orderId]);
        $order['status_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
    }
    
    /**
     * Update order status
     * @param int $orderId Order ID
     * @param string $status New status
     * @param string $comment Status comment
     * @param array $additionalData Additional data to update
     * @return bool Success status
     */
    public function updateOrderStatus($orderId, $status, $comment = '', $additionalData = []) {
        try {
            $this->pdo->beginTransaction();
            
            // Prepare update data
            $updateFields = ['status = ?', 'updated_at = NOW()'];
            $params = [$status];
            
            // Add timestamp fields based on status
            switch ($status) {
                case 'shipped':
                    $updateFields[] = 'shipped_at = NOW()';
                    if (isset($additionalData['tracking_number'])) {
                        $updateFields[] = 'tracking_number = ?';
                        $params[] = $additionalData['tracking_number'];
                    }
                    if (isset($additionalData['estimated_delivery'])) {
                        $updateFields[] = 'estimated_delivery = ?';
                        $params[] = $additionalData['estimated_delivery'];
                    }
                    break;
                case 'delivered':
                    $updateFields[] = 'delivered_at = NOW()';
                    break;
            }
            
            $params[] = $orderId;
            
            // Update order
            $stmt = $this->pdo->prepare("
                UPDATE orders SET " . implode(', ', $updateFields) . " 
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Add status history
            $this->addStatusHistory($orderId, $status, $comment);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    /**
     * Get order statistics for dashboard
     * @param int $userId User ID (optional, for user stats)
     * @return array Order statistics
     */
    public function getOrderStats($userId = null) {
        $whereClause = $userId ? "WHERE user_id = ?" : "";
        $params = $userId ? [$userId] : [];
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_orders,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value
            FROM orders 
            $whereClause
        ");
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search orders
     * @param string $query Search query
     * @param array $filters Additional filters
     * @return array Search results
     */
    public function searchOrders($query, $filters = []) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($query)) {
            $whereConditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $searchTerm = '%' . $query . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['status'])) {
            $whereConditions[] = "o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.username, u.email,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            $whereClause
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Private helper methods
    
    private function generateOrderNumber() {
        $prefix = 'ORD';
        $timestamp = date('YmdHis');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . '-' . $timestamp . '-' . $random;
    }
    
    private function formatShippingAddress($address) {
        return implode(', ', array_filter([
            $address['address_line_1'] ?? '',
            $address['address_line_2'] ?? '',
            $address['city'] ?? '',
            $address['county'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? 'Kenya'
        ]));
    }
    
    private function addStatusHistory($orderId, $status, $comment) {
        $stmt = $this->pdo->prepare("
            INSERT INTO order_status_history (order_id, status, comment, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        return $stmt->execute([$orderId, $status, $comment]);
    }
}
?>
