<?php
/**
 * M-Pesa Timeout Handler - SMARTDUKA E-commerce Platform
 * Handles M-Pesa payment timeouts
 */

require_once 'config.php';

// Log timeout request
error_log('M-Pesa Timeout received: ' . file_get_contents('php://input'));

// Set response headers
header('Content-Type: application/json');

try {
    // Check database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Get timeout data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data) {
        $checkoutRequestId = $data['CheckoutRequestID'] ?? '';
        
        if ($checkoutRequestId) {
            // Update transaction status to failed due to timeout
            $stmt = $pdo->prepare("
                UPDATE mpesa_transactions 
                SET status = 'failed',
                    result_desc = 'Payment request timed out',
                    updated_at = NOW()
                WHERE checkout_request_id = ? AND status = 'pending'
            ");
            $stmt->execute([$checkoutRequestId]);
            
            error_log("M-Pesa payment timed out - CheckoutRequestID: $checkoutRequestId");
        }
    }
    
    // Send success response
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Timeout processed successfully'
    ]);
    
} catch (Exception $e) {
    error_log('M-Pesa Timeout Error: ' . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Timeout processing failed'
    ]);
}
?>
