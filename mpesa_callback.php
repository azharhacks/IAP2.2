<?php
/**
 * M-Pesa Callback Handler - SMARTDUKA
 * Handles M-Pesa payment callbacks from Safaricom
 */

require_once 'config.php';

// Log all incoming requests for debugging
$logFile = 'mpesa_callbacks.log';
$requestBody = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');

// Log the callback
file_put_contents($logFile, "[$timestamp] Callback received: $requestBody\n", FILE_APPEND);

try {
    // Decode the callback data
    $callbackData = json_decode($requestBody, true);
    
    if (!$callbackData) {
        throw new Exception('Invalid callback data');
    }
    
    // Extract callback information
    $stkCallback = $callbackData['Body']['stkCallback'] ?? null;
    
    if (!$stkCallback) {
        throw new Exception('No STK callback data found');
    }
    
    $merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;
    $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
    $resultCode = $stkCallback['ResultCode'] ?? null;
    $resultDesc = $stkCallback['ResultDesc'] ?? null;
    
    file_put_contents($logFile, "[$timestamp] Processing: CheckoutRequestID=$checkoutRequestId, ResultCode=$resultCode\n", FILE_APPEND);
    
    if (!$checkoutRequestId) {
        throw new Exception('Missing CheckoutRequestID');
    }
    
    // Find the transaction in database
    $stmt = $pdo->prepare("SELECT * FROM mpesa_transactions WHERE checkout_request_id = ?");
    $stmt->execute([$checkoutRequestId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        file_put_contents($logFile, "[$timestamp] Transaction not found for CheckoutRequestID: $checkoutRequestId\n", FILE_APPEND);
        // Still respond with success to Safaricom
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
        exit();
    }
    
    $orderId = $transaction['order_id'];
    
    // Start database transaction
    $pdo->beginTransaction();
    
    if ($resultCode == 0) {
        // Payment successful
        $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
        
        $mpesaReceiptNumber = null;
        $transactionDate = null;
        $phoneNumber = null;
        
        // Extract metadata
        foreach ($callbackMetadata as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber':
                    $mpesaReceiptNumber = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $phoneNumber = $item['Value'];
                    break;
            }
        }
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE mpesa_transactions 
            SET status = 'completed', 
                transaction_id = ?, 
                result_desc = ?, 
                updated_at = NOW(),
                transaction_date = ?
            WHERE checkout_request_id = ?
        ");
        $stmt->execute([$mpesaReceiptNumber, $resultDesc, $transactionDate, $checkoutRequestId]);
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'paid', 
                order_status = 'processing',
                payment_method = 'mpesa',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        file_put_contents($logFile, "[$timestamp] Payment successful: Order $orderId, Receipt $mpesaReceiptNumber\n", FILE_APPEND);
        
    } else {
        // Payment failed or cancelled
        $stmt = $pdo->prepare("
            UPDATE mpesa_transactions 
            SET status = 'failed', 
                result_desc = ?, 
                updated_at = NOW()
            WHERE checkout_request_id = ?
        ");
        $stmt->execute([$resultDesc, $checkoutRequestId]);
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'failed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        file_put_contents($logFile, "[$timestamp] Payment failed: Order $orderId, Reason: $resultDesc\n", FILE_APPEND);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Send success response to Safaricom
    $response = [
        'ResultCode' => 0,
        'ResultDesc' => 'Success'
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    file_put_contents($logFile, "[$timestamp] Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Still send success response to prevent retries
    header('Content-Type: application/json');
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Success'
    ]);
}
?>
