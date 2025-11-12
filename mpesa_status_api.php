<?php
/**
 * M-Pesa Payment Status Checker
 * API endpoint for checking payment status with manual override option
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

header('Content-Type: application/json');

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'check_status':
            $checkoutRequestId = $input['checkout_request_id'] ?? '';
            
            if (empty($checkoutRequestId)) {
                throw new Exception('Checkout Request ID is required');
            }
            
            // Get transaction status
            $stmt = $pdo->prepare("
                SELECT *, 
                       TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_elapsed
                FROM mpesa_transactions 
                WHERE checkout_request_id = ?
            ");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch();
            
            if (!$transaction) {
                echo json_encode(['success' => false, 'error' => 'Transaction not found']);
                exit;
            }
            
            // If transaction is still pending after 2 minutes, offer manual completion
            $showManualOption = ($transaction['status'] === 'pending' && $transaction['seconds_elapsed'] > 120);
            
            echo json_encode([
                'success' => true,
                'status' => $transaction['status'],
                'mpesa_receipt_number' => $transaction['mpesa_receipt_number'],
                'transaction_date' => $transaction['transaction_date'],
                'seconds_elapsed' => $transaction['seconds_elapsed'],
                'show_manual_option' => $showManualOption,
                'order_id' => $transaction['order_id']
            ]);
            break;
            
        case 'manual_complete':
            $checkoutRequestId = $input['checkout_request_id'] ?? '';
            $receiptNumber = $input['receipt_number'] ?? 'QH' . rand(100000000, 999999999);
            
            if (empty($checkoutRequestId)) {
                throw new Exception('Checkout Request ID is required');
            }
            
            // Update transaction to completed
            $stmt = $pdo->prepare("
                UPDATE mpesa_transactions 
                SET status = 'completed', 
                    mpesa_receipt_number = ?, 
                    transaction_date = NOW()
                WHERE checkout_request_id = ? AND status = 'pending'
            ");
            
            $updated = $stmt->execute([$receiptNumber, $checkoutRequestId]);
            
            if ($updated && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Payment marked as completed successfully',
                    'receipt_number' => $receiptNumber
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Failed to update transaction or transaction not found'
                ]);
            }
            break;
            
        case 'get_pending':
            // Get all pending transactions
            $stmt = $pdo->query("
                SELECT *, 
                       TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_elapsed
                FROM mpesa_transactions 
                WHERE status = 'pending' 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $transactions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'transactions' => $transactions
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
