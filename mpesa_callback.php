<?php
/**
 * M-Pesa Callback Handler
 * Processes payment confirmations from Safaricom
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Set JSON response headers
header('Content-Type: application/json');

// Log all incoming data for debugging
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'raw_input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'get_data' => $_GET
];

error_log('M-Pesa Callback Data: ' . json_encode($logData));

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Get callback data
    $callbackData = json_decode(file_get_contents('php://input'), true);
    
    if (!$callbackData) {
        throw new Exception('Invalid callback data received');
    }
    
    // Log the structured callback data
    error_log('M-Pesa Structured Callback: ' . json_encode($callbackData));
    
    // Initialize M-Pesa payment class
    $mpesa = new MpesaPayment($pdo, $conf['mpesa']);
    
    // Process the callback
    $result = $mpesa->processCallback($callbackData);
    
    if ($result['success']) {
        // Log successful processing
        error_log('M-Pesa Callback Processed Successfully: ' . json_encode($result));
        
        // Return success response to Safaricom
        echo json_encode([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback processed successfully'
        ]);
    } else {
        // Log processing error
        error_log('M-Pesa Callback Processing Failed: ' . $result['message']);
        
        // Return error response to Safaricom
        echo json_encode([
            'ResultCode' => 1,
            'ResultDesc' => 'Callback processing failed: ' . $result['message']
        ]);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log('M-Pesa Callback Error: ' . $e->getMessage());
    
    // Return error response to Safaricom
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Internal server error: ' . $e->getMessage()
    ]);
}

// Always return HTTP 200 to prevent Safaricom from retrying
http_response_code(200);
