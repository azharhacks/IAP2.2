<?php
/**
 * Simple Cancel Order - SMARTDUKA
 * Basic order cancellation without complex features
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    if (!isset($data['order_id'])) {
        throw new Exception('Order ID required');
    }
    
    $order_id = $data['order_id'];
    $user_id = $_SESSION['user_id'];
    
    // Include database connection
    require_once 'config.php';
    
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if order exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT id, order_number, order_status, payment_status 
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
        throw new Exception('Cannot cancel paid orders');
    }
    
    // Update order status to cancelled
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET order_status = 'cancelled', 
            payment_status = 'cancelled'
        WHERE id = ? AND user_id = ?
    ");
    
    $success = $stmt->execute([$order_id, $user_id]);
    
    if (!$success || $stmt->rowCount() === 0) {
        throw new Exception('Failed to cancel order');
    }
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully',
        'order_id' => $order_id,
        'order_number' => $order['order_number']
    ]);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Cancel order error: ' . $e->getMessage());
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? 'not set',
            'input' => $input ?? 'no input',
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile()
        ]
    ]);
}
?>