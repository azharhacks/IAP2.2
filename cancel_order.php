<?php
/**
 * Cancel Order API - SMARTDUKA E-commerce Platform
 * Handles order cancellation requests
 */

session_start();
require_once 'config.php';

// Set JSON response headers
header('Content-Type: application/json');

try {
    // Check if user is logged in and verified
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['verified']) || $_SESSION['verified'] !== true) {
        throw new Exception('User not authenticated');
    }
    
    // Check database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['order_id'])) {
        throw new Exception('Invalid request data');
    }
    
    $order_id = $data['order_id'];
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Verify order exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT id, order_number, order_status, payment_status, total_amount 
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found or access denied');
    }
    
    // Check if order can be cancelled
    if ($order['order_status'] === 'cancelled') {
        throw new Exception('Order is already cancelled');
    }
    
    if ($order['payment_status'] === 'paid') {
        throw new Exception('Cannot cancel paid orders. Please contact support for refunds.');
    }
    
    if (in_array($order['order_status'], ['shipped', 'completed'])) {
        throw new Exception('Cannot cancel orders that are already shipped or completed');
    }
    
    // Update order status to cancelled
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET order_status = 'cancelled', 
            payment_status = 'cancelled',
            updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to update order status');
    }
    
    // Cancel any pending M-Pesa transactions
    $stmt = $pdo->prepare("
        UPDATE mpesa_transactions 
        SET status = 'cancelled',
            result_desc = 'Order cancelled by user',
            updated_at = NOW()
        WHERE order_id = ? AND status = 'pending'
    ");
    $stmt->execute([$order_id]);
    
    // Restore product stock (if order items table tracks stock)
    $stmt = $pdo->prepare("
        SELECT oi.product_id, oi.quantity, p.stock_quantity
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($order_items as $item) {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Log the cancellation
    $stmt = $pdo->prepare("
        INSERT INTO order_logs (order_id, user_id, action, notes, created_at)
        VALUES (?, ?, 'cancelled', 'Order cancelled by user', NOW())
    ");
    
    // Try to insert log, but don't fail if table doesn't exist
    try {
        $stmt->execute([$order_id, $user_id]);
    } catch (Exception $e) {
        // Log table doesn't exist, that's okay
        error_log("Order log insert failed (table may not exist): " . $e->getMessage());
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully',
        'order_id' => $order_id,
        'order_number' => $order['order_number'],
        'refunded_amount' => $order['payment_status'] === 'pending' ? 0 : $order['total_amount']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log('Order cancellation error: ' . $e->getMessage());
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>