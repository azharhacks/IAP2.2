<?php
/**
 * Admin M-Pesa Management API
 * Allows admins to check real M-Pesa status, view API responses, and manage transactions
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../ClassAutoload.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Admin privileges required'
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
    
    switch ($action) {
        case 'get_transaction_details':
            $transactionId = (int)($input['transaction_id'] ?? 0);
            
            if (!$transactionId) {
                throw new Exception('Transaction ID is required');
            }
            
            // Get transaction details
            $stmt = $pdo->prepare("
                SELECT mt.*, o.order_number, o.total_amount as order_total,
                       u.username, u.email
                FROM mpesa_transactions mt
                LEFT JOIN orders o ON mt.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE mt.id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            echo json_encode([
                'success' => true,
                'transaction' => $transaction
            ]);
            break;
            
        case 'check_real_status':
            $transactionId = (int)($input['transaction_id'] ?? 0);
            
            if (!$transactionId) {
                throw new Exception('Transaction ID is required');
            }
            
            // Get transaction
            $stmt = $pdo->prepare("
                SELECT checkout_request_id FROM mpesa_transactions WHERE id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Check real M-Pesa API status
            $result = $mpesa->checkPaymentStatus($transaction['checkout_request_id']);
            
            // Log admin action
            error_log("Admin {$_SESSION['username']} checked M-Pesa status for transaction {$transactionId}");
            
            echo json_encode($result);
            break;
            
        case 'manual_complete':
            $transactionId = (int)($input['transaction_id'] ?? 0);
            $receiptNumber = trim($input['receipt_number'] ?? '');
            $reason = trim($input['reason'] ?? '');
            
            if (!$transactionId) {
                throw new Exception('Transaction ID is required');
            }
            
            if (!$receiptNumber) {
                throw new Exception('Receipt number is required for manual completion');
            }
            
            // Get transaction
            $stmt = $pdo->prepare("
                SELECT * FROM mpesa_transactions WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found or not pending');
            }
            
            // Manual completion with admin override
            $stmt = $pdo->prepare("
                UPDATE mpesa_transactions 
                SET status = 'completed',
                    mpesa_receipt_number = ?,
                    transaction_date = NOW(),
                    result_desc = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $adminReason = "Manually completed by admin: {$_SESSION['username']}. Reason: {$reason}";
            $stmt->execute([$receiptNumber, $adminReason, $transactionId]);
            
            // Update order status
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET payment_status = 'paid',
                    status = 'confirmed'
                WHERE id = ?
            ");
            $stmt->execute([$transaction['order_id']]);
            
            // Log admin action
            error_log("Admin {$_SESSION['username']} manually completed transaction {$transactionId} with receipt {$receiptNumber}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Transaction manually completed successfully'
            ]);
            break;
            
        case 'cancel_transaction':
            $transactionId = (int)($input['transaction_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            
            if (!$transactionId) {
                throw new Exception('Transaction ID is required');
            }
            
            // Get transaction
            $stmt = $pdo->prepare("
                SELECT * FROM mpesa_transactions WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found or not pending');
            }
            
            // Cancel transaction
            $stmt = $pdo->prepare("
                UPDATE mpesa_transactions 
                SET status = 'cancelled',
                    result_desc = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $adminReason = "Cancelled by admin: {$_SESSION['username']}. Reason: {$reason}";
            $stmt->execute([$adminReason, $transactionId]);
            
            // Log admin action
            error_log("Admin {$_SESSION['username']} cancelled transaction {$transactionId}. Reason: {$reason}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Transaction cancelled successfully'
            ]);
            break;
            
        case 'get_api_logs':
            $transactionId = (int)($input['transaction_id'] ?? 0);
            
            if (!$transactionId) {
                throw new Exception('Transaction ID is required');
            }
            
            // Get transaction with callback metadata
            $stmt = $pdo->prepare("
                SELECT callback_metadata, result_desc, result_code, 
                       created_at, updated_at, status
                FROM mpesa_transactions 
                WHERE id = ?
            ");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            $logs = [
                'status' => $transaction['status'],
                'result_code' => $transaction['result_code'],
                'result_desc' => $transaction['result_desc'],
                'created_at' => $transaction['created_at'],
                'updated_at' => $transaction['updated_at'],
                'callback_metadata' => null
            ];
            
            if ($transaction['callback_metadata']) {
                $logs['callback_metadata'] = json_decode($transaction['callback_metadata'], true);
            }
            
            echo json_encode([
                'success' => true,
                'logs' => $logs
            ]);
            break;
            
        case 'bulk_check_status':
            // Check status for all pending transactions
            $stmt = $pdo->prepare("
                SELECT id, checkout_request_id 
                FROM mpesa_transactions 
                WHERE status = 'pending' 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $pendingTransactions = $stmt->fetchAll();
            
            $results = [];
            foreach ($pendingTransactions as $transaction) {
                $status = $mpesa->checkPaymentStatus($transaction['checkout_request_id']);
                $results[] = [
                    'transaction_id' => $transaction['id'],
                    'checkout_request_id' => $transaction['checkout_request_id'],
                    'status_check' => $status
                ];
            }
            
            // Log admin action
            error_log("Admin {$_SESSION['username']} performed bulk status check on " . count($pendingTransactions) . " transactions");
            
            echo json_encode([
                'success' => true,
                'results' => $results,
                'checked_count' => count($pendingTransactions)
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log('M-Pesa Admin API Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
