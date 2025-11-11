<?php
/**
 * M-Pesa Timeout Handler
 * Handles payment timeout notifications from Safaricom
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Set JSON response headers
header('Content-Type: application/json');

// Log timeout data for debugging
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'raw_input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'get_data' => $_GET
];

error_log('M-Pesa Timeout Data: ' . json_encode($logData));

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Get timeout data
    $timeoutData = json_decode(file_get_contents('php://input'), true);
    
    if (!$timeoutData) {
        throw new Exception('Invalid timeout data received');
    }
    
    // Log the structured timeout data
    error_log('M-Pesa Structured Timeout: ' . json_encode($timeoutData));
    
    // Extract checkout request ID if available
    $checkoutRequestId = null;
    if (isset($timeoutData['Body']['stkCallback']['CheckoutRequestID'])) {
        $checkoutRequestId = $timeoutData['Body']['stkCallback']['CheckoutRequestID'];
    }
    
    if ($checkoutRequestId) {
        // Update transaction status to failed due to timeout
        $stmt = $pdo->prepare("
            UPDATE mpesa_transactions 
            SET status = 'failed', 
                result_code = 1, 
                result_desc = 'Payment timeout - Customer did not respond in time',
                updated_at = NOW()
            WHERE checkout_request_id = ? AND status = 'pending'
        ");
        $stmt->execute([$checkoutRequestId]);
        
        error_log("M-Pesa Transaction timeout processed for: {$checkoutRequestId}");
    }
    
    // Return success response to Safaricom
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Timeout processed successfully'
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('M-Pesa Timeout Error: ' . $e->getMessage());
    
    // Return error response to Safaricom
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Internal server error: ' . $e->getMessage()
    ]);
}

// Always return HTTP 200 to prevent Safaricom from retrying
http_response_code(200);
