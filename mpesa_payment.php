<?php
/**
 * M-Pesa Payment Processing Endpoint
 * Handles STK Push initiation and status checking
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Ensure user is authenticated and 2FA verified
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Initialize M-Pesa payment class
    $mpesa = new MpesaPayment($pdo, $conf['mpesa']);
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $action = $input['action'] ?? '';
    $userId = $_SESSION['user_id'];
    
    switch ($action) {
        case 'initiate_payment':
            // Validate required fields
            $orderId = (int)($input['order_id'] ?? 0);
            $phoneNumber = trim($input['phone_number'] ?? '');
            
            if (!$orderId || !$phoneNumber) {
                throw new Exception('Order ID and phone number are required');
            }
            
            // Verify order belongs to user and get details
            $stmt = $pdo->prepare("
                SELECT id, total_amount, payment_status, status
                FROM orders 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found or access denied');
            }
            
            if ($order['payment_status'] === 'paid') {
                throw new Exception('Order is already paid');
            }
            
            // Check if there's already a pending M-Pesa transaction for this order
            $stmt = $pdo->prepare("
                SELECT checkout_request_id 
                FROM mpesa_transactions 
                WHERE order_id = ? AND status = 'pending'
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$orderId]);
            $existingTransaction = $stmt->fetch();
            
            if ($existingTransaction) {
                echo json_encode([
                    'success' => false,
                    'message' => 'A payment request is already pending for this order',
                    'checkout_request_id' => $existingTransaction['checkout_request_id']
                ]);
                exit;
            }
            
            // Initiate STK Push
            $result = $mpesa->initiateSTKPush(
                $orderId,
                $phoneNumber,
                $order['total_amount'],
                "Order-{$orderId}",
                "Payment for Order #{$orderId}"
            );
            
            echo json_encode($result);
            break;
            
        case 'check_status':
            $checkoutRequestId = trim($input['checkout_request_id'] ?? '');
            
            if (!$checkoutRequestId) {
                throw new Exception('Checkout request ID is required');
            }
            
            // Verify transaction belongs to user
            $stmt = $pdo->prepare("
                SELECT mt.*, o.user_id 
                FROM mpesa_transactions mt
                JOIN orders o ON mt.order_id = o.id
                WHERE mt.checkout_request_id = ?
            ");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction || $transaction['user_id'] != $userId) {
                throw new Exception('Transaction not found or access denied');
            }
            
            $result = $mpesa->checkPaymentStatus($checkoutRequestId);
            echo json_encode($result);
            break;
            
        case 'cancel_payment':
            $checkoutRequestId = trim($input['checkout_request_id'] ?? '');
            
            if (!$checkoutRequestId) {
                throw new Exception('Checkout request ID is required');
            }
            
            // Verify transaction belongs to user
            $stmt = $pdo->prepare("
                SELECT mt.*, o.user_id 
                FROM mpesa_transactions mt
                JOIN orders o ON mt.order_id = o.id
                WHERE mt.checkout_request_id = ? AND mt.status = 'pending'
            ");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction || $transaction['user_id'] != $userId) {
                throw new Exception('Transaction not found or access denied');
            }
            
            $result = $mpesa->cancelTransaction($checkoutRequestId);
            echo json_encode($result);
            break;
            
        case 'get_transaction_details':
            $checkoutRequestId = trim($input['checkout_request_id'] ?? '');
            
            if (!$checkoutRequestId) {
                throw new Exception('Checkout request ID is required');
            }
            
            // Verify transaction belongs to user
            $stmt = $pdo->prepare("
                SELECT mt.*, o.user_id, o.order_number, o.total_amount as order_total
                FROM mpesa_transactions mt
                JOIN orders o ON mt.order_id = o.id
                WHERE mt.checkout_request_id = ?
            ");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction || $transaction['user_id'] != $userId) {
                throw new Exception('Transaction not found or access denied');
            }
            
            // Remove sensitive data
            unset($transaction['user_id']);
            
            echo json_encode([
                'success' => true,
                'transaction' => $transaction
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log('M-Pesa Payment API Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
