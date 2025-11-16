<?php
/**
 * M-Pesa Payment Handler - SMARTDUKA
 * Handles M-Pesa payment initiation and status checking
 */

session_start();
require_once 'config.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    // Check database connection
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection not available');
    }
    
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'initiate_payment':
            echo json_encode(initiatePayment($pdo, $data, $_SESSION['user_id']));
            break;
            
        case 'check_status':
            echo json_encode(checkPaymentStatus($pdo, $data));
            break;
            
        case 'cancel_payment':
            echo json_encode(cancelPayment($pdo, $data));
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log('M-Pesa payment error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Initiate M-Pesa payment
 */
function initiatePayment($pdo, $data, $user_id) {
    $order_id = $data['order_id'] ?? null;
    $phone_number = $data['phone_number'] ?? null;
    
    if (!$order_id || !$phone_number) {
        throw new Exception('Order ID and phone number required');
    }
    
    // Verify order exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ? AND payment_status != 'paid'
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found or already paid');
    }
    
    // Generate checkout request ID (simulate M-Pesa)
    $checkout_request_id = 'ws_CO_' . date('dmY') . '_' . time() . '_' . rand(100000, 999999);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert M-Pesa transaction record
        $stmt = $pdo->prepare("
            INSERT INTO mpesa_transactions 
            (order_id, checkout_request_id, phone_number, amount, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$order_id, $checkout_request_id, $phone_number, $order['total_amount']]);
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'pending', payment_method = 'mpesa' 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        
        $pdo->commit();
        
        // Simulate M-Pesa API call success
        return [
            'success' => true,
            'message' => 'Payment initiated successfully',
            'checkout_request_id' => $checkout_request_id,
            'order_id' => $order_id,
            'amount' => $order['total_amount'],
            'phone_number' => $phone_number
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to initiate payment: ' . $e->getMessage());
    }
}

/**
 * Check M-Pesa payment status
 */
function checkPaymentStatus($pdo, $data) {
    $checkout_request_id = $data['checkout_request_id'] ?? null;
    
    if (!$checkout_request_id) {
        throw new Exception('Checkout request ID required');
    }
    
    // Get transaction details
    $stmt = $pdo->prepare("
        SELECT mt.*, o.order_number, o.user_id 
        FROM mpesa_transactions mt
        JOIN orders o ON mt.order_id = o.id
        WHERE mt.checkout_request_id = ?
    ");
    $stmt->execute([$checkout_request_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    // Check if user owns this transaction
    if ($transaction['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Access denied');
    }
    
    // Simulate status checking logic
    // In a real implementation, you would call M-Pesa API to check status
    
    $current_status = $transaction['status'];
    $transaction_age = time() - strtotime($transaction['created_at']);
    
    // Simulate payment completion after 30 seconds for demo
    if ($current_status === 'pending' && $transaction_age > 30) {
        // Simulate random success/failure for demo (80% success rate)
        $success = rand(1, 100) <= 80;
        
        if ($success) {
            // Mark as completed
            $new_status = 'completed';
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            $result_desc = 'Payment completed successfully';
            
            try {
                $pdo->beginTransaction();
                
                // Update transaction
                $stmt = $pdo->prepare("
                    UPDATE mpesa_transactions 
                    SET status = ?, transaction_id = ?, result_desc = ?, updated_at = NOW()
                    WHERE checkout_request_id = ?
                ");
                $stmt->execute([$new_status, $transaction_id, $result_desc, $checkout_request_id]);
                
                // Update order
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid', order_status = 'processing', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['order_id']]);
                
                $pdo->commit();
                
                return [
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully',
                    'transaction_id' => $transaction_id,
                    'order_number' => $transaction['order_number']
                ];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception('Failed to update payment status: ' . $e->getMessage());
            }
            
        } else {
            // Mark as failed
            $new_status = 'failed';
            $result_desc = 'Payment failed or cancelled by user';
            
            try {
                $pdo->beginTransaction();
                
                // Update transaction
                $stmt = $pdo->prepare("
                    UPDATE mpesa_transactions 
                    SET status = ?, result_desc = ?, updated_at = NOW()
                    WHERE checkout_request_id = ?
                ");
                $stmt->execute([$new_status, $result_desc, $checkout_request_id]);
                
                // Update order
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'failed', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['order_id']]);
                
                $pdo->commit();
                
                return [
                    'success' => true,
                    'status' => 'failed',
                    'message' => 'Payment failed or was cancelled',
                    'order_number' => $transaction['order_number']
                ];
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception('Failed to update payment status: ' . $e->getMessage());
            }
        }
    }
    
    // Return current status if still pending
    return [
        'success' => true,
        'status' => $current_status,
        'message' => 'Payment is still being processed',
        'transaction_age' => $transaction_age,
        'estimated_completion' => max(0, 30 - $transaction_age) . ' seconds remaining'
    ];
}

/**
 * Cancel M-Pesa payment
 */
function cancelPayment($pdo, $data) {
    $checkout_request_id = $data['checkout_request_id'] ?? null;
    
    if (!$checkout_request_id) {
        throw new Exception('Checkout request ID required');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update transaction to cancelled
        $stmt = $pdo->prepare("
            UPDATE mpesa_transactions 
            SET status = 'cancelled', result_desc = 'Cancelled by user', updated_at = NOW()
            WHERE checkout_request_id = ? AND status = 'pending'
        ");
        $stmt->execute([$checkout_request_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Transaction not found or already processed');
        }
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders o
            JOIN mpesa_transactions mt ON o.id = mt.order_id
            SET o.payment_status = 'cancelled', o.updated_at = NOW()
            WHERE mt.checkout_request_id = ?
        ");
        $stmt->execute([$checkout_request_id]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Payment cancelled successfully'
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to cancel payment: ' . $e->getMessage());
    }
}
?>
