<?php
/**
 * M-Pesa Payment Integration Class
 * Handles M-Pesa STK Push payments for the e-commerce platform
 * Features: STK Push initiation, status checking, callback processing
 */

class MpesaPayment
{
    private $pdo;
    private $config;
    private $accessToken;
    
    public function __construct($pdo, $mpesaConfig)
    {
        $this->pdo = $pdo;
        $this->config = $mpesaConfig;
    }
    
    /**
     * Get M-Pesa access token from Safaricom API
     */
    private function getAccessToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        $url = $this->config['environment'] === 'production' 
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        
        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get M-Pesa access token. HTTP Code: ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            throw new Exception('Invalid access token response from M-Pesa API');
        }
        
        $this->accessToken = $result['access_token'];
        return $this->accessToken;
    }
    
    /**
     * Format phone number to M-Pesa format (254XXXXXXXXX)
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle different formats
        if (substr($phone, 0, 3) === '254') {
            return $phone; // Already in correct format
        } elseif (substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1); // Remove leading 0 and add 254
        } elseif (strlen($phone) === 9) {
            return '254' . $phone; // Add 254 prefix
        }
        
        // Validate final format
        if (strlen($phone) !== 12 || substr($phone, 0, 3) !== '254') {
            throw new Exception('Invalid phone number format. Use format: 254712345678');
        }
        
        return $phone;
    }
    
    /**
     * Initiate M-Pesa STK Push payment
     */
    public function initiateSTKPush($orderId, $phoneNumber, $amount, $accountReference = null, $transactionDesc = null)
    {
        try {
            $accessToken = $this->getAccessToken();
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Generate timestamp and password
            $timestamp = date('YmdHis');
            $password = base64_encode($this->config['short_code'] . $this->config['passkey'] . $timestamp);
            
            $url = $this->config['environment'] === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            
            $postData = [
                'BusinessShortCode' => $this->config['short_code'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)$amount,
                'PartyA' => $formattedPhone,
                'PartyB' => $this->config['short_code'],
                'PhoneNumber' => $formattedPhone,
                'CallBackURL' => $this->config['callback_url'],
                'AccountReference' => $accountReference ?: $this->config['account_reference'],
                'TransactionDesc' => $transactionDesc ?: $this->config['transaction_desc']
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($httpCode !== 200) {
                throw new Exception('STK Push request failed. HTTP Code: ' . $httpCode);
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['CheckoutRequestID'])) {
                $errorMsg = $result['errorMessage'] ?? 'Unknown error occurred';
                throw new Exception('STK Push failed: ' . $errorMsg);
            }
            
            // Save transaction to database
            $stmt = $this->pdo->prepare("
                INSERT INTO mpesa_transactions (
                    order_id, checkout_request_id, merchant_request_id, 
                    phone_number, amount, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $orderId,
                $result['CheckoutRequestID'],
                $result['MerchantRequestID'],
                $formattedPhone,
                $amount
            ]);
            
            return [
                'success' => true,
                'message' => 'STK Push initiated successfully',
                'checkout_request_id' => $result['CheckoutRequestID'],
                'merchant_request_id' => $result['MerchantRequestID'],
                'customer_message' => $result['CustomerMessage'] ?? 'Payment request sent to your phone'
            ];
            
        } catch (Exception $e) {
            error_log('M-Pesa STK Push Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check STK Push payment status
     */
    public function checkPaymentStatus($checkoutRequestId)
    {
        try {
            $accessToken = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = base64_encode($this->config['short_code'] . $this->config['passkey'] . $timestamp);
            
            $url = $this->config['environment'] === 'production'
                ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
            
            $postData = [
                'BusinessShortCode' => $this->config['short_code'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId
            ];
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($httpCode !== 200) {
                throw new Exception('Status check request failed. HTTP Code: ' . $httpCode);
            }
            
            $result = json_decode($response, true);
            
            // Get database transaction for order info
            $stmt = $this->pdo->prepare("
                SELECT * FROM mpesa_transactions 
                WHERE checkout_request_id = ?
            ");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return [
                    'success' => false,
                    'message' => 'Transaction not found'
                ];
            }
            
            // Process the real M-Pesa API response
            $apiResultCode = $result['ResultCode'] ?? null;
            $apiResultDesc = $result['ResultDesc'] ?? 'Unknown status';
            
            // Determine status from M-Pesa API response
            $mpesaStatus = 'pending'; // Default
            $mpesaReceiptNumber = null;
            $transactionDate = null;
            
            if ($apiResultCode !== null) {
                if ($apiResultCode == 0) {
                    // Success - Payment completed
                    $mpesaStatus = 'completed';
                    
                    // Update database with completed status if not already done
                    if ($transaction['status'] !== 'completed') {
                        $stmt = $this->pdo->prepare("
                            UPDATE mpesa_transactions 
                            SET status = 'completed', 
                                result_code = ?, 
                                result_desc = ?,
                                updated_at = NOW()
                            WHERE checkout_request_id = ?
                        ");
                        $stmt->execute([$apiResultCode, $apiResultDesc, $checkoutRequestId]);
                        
                        // Update order payment status
                        $stmt = $this->pdo->prepare("
                            UPDATE orders SET 
                                payment_status = 'paid',
                                status = 'confirmed'
                            WHERE id = ?
                        ");
                        $stmt->execute([$transaction['order_id']]);
                    }
                } elseif ($apiResultCode == 1032) {
                    // User cancelled
                    $mpesaStatus = 'cancelled';
                } elseif ($apiResultCode == 1037) {
                    // Timeout - user didn't enter PIN
                    $mpesaStatus = 'timeout';
                } elseif ($apiResultCode > 0) {
                    // Other error codes indicate failure
                    $mpesaStatus = 'failed';
                    
                    // Update database with failed status
                    if ($transaction['status'] === 'pending') {
                        $stmt = $this->pdo->prepare("
                            UPDATE mpesa_transactions 
                            SET status = ?, 
                                result_code = ?, 
                                result_desc = ?,
                                updated_at = NOW()
                            WHERE checkout_request_id = ?
                        ");
                        $stmt->execute([$mpesaStatus, $apiResultCode, $apiResultDesc, $checkoutRequestId]);
                    }
                }
            }
            
            return [
                'success' => true,
                'status' => $mpesaStatus,
                'mpesa_receipt_number' => $transaction['mpesa_receipt_number'] ?? $mpesaReceiptNumber,
                'transaction_date' => $transaction['transaction_date'] ?? $transactionDate,
                'result_desc' => $apiResultDesc,
                'result_code' => $apiResultCode,
                'api_response' => $result
            ];
            
        } catch (Exception $e) {
            error_log('M-Pesa Status Check Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process M-Pesa callback
     */
    public function processCallback($callbackData)
    {
        try {
            if (!isset($callbackData['Body']['stkCallback'])) {
                throw new Exception('Invalid callback data structure');
            }
            
            $stkCallback = $callbackData['Body']['stkCallback'];
            $checkoutRequestId = $stkCallback['CheckoutRequestID'];
            $resultCode = $stkCallback['ResultCode'];
            $resultDesc = $stkCallback['ResultDesc'];
            
            // Determine status based on result code
            $status = ($resultCode == 0) ? 'completed' : 'failed';
            
            // Extract callback metadata
            $mpesaReceiptNumber = null;
            $transactionDate = null;
            $metadata = null;
            
            if (isset($stkCallback['CallbackMetadata']['Item'])) {
                $metadata = json_encode($stkCallback['CallbackMetadata']['Item']);
                
                foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $mpesaReceiptNumber = $item['Value'];
                    } elseif ($item['Name'] === 'TransactionDate') {
                        // Convert M-Pesa date format to MySQL datetime
                        $transactionDate = date('Y-m-d H:i:s', strtotime($item['Value']));
                    }
                }
            }
            
            // Update transaction in database
            $stmt = $this->pdo->prepare("
                UPDATE mpesa_transactions SET 
                    status = ?, 
                    mpesa_receipt_number = ?, 
                    transaction_date = ?, 
                    result_code = ?, 
                    result_desc = ?, 
                    callback_metadata = ?,
                    updated_at = NOW()
                WHERE checkout_request_id = ?
            ");
            
            $stmt->execute([
                $status,
                $mpesaReceiptNumber,
                $transactionDate,
                $resultCode,
                $resultDesc,
                $metadata,
                $checkoutRequestId
            ]);
            
            // Get the updated transaction with order info
            $stmt = $this->pdo->prepare("
                SELECT mt.*, o.id as order_id 
                FROM mpesa_transactions mt
                JOIN orders o ON mt.order_id = o.id
                WHERE mt.checkout_request_id = ?
            ");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction && $status === 'completed') {
                // Update order payment status
                $stmt = $this->pdo->prepare("
                    UPDATE orders SET 
                        payment_status = 'paid',
                        status = 'confirmed',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['order_id']]);
                
                // Add order status history
                $stmt = $this->pdo->prepare("
                    INSERT INTO order_status_history (order_id, status, comment, created_at)
                    VALUES (?, 'confirmed', 'Payment confirmed via M-Pesa', NOW())
                ");
                $stmt->execute([$transaction['order_id']]);
            }
            
            return [
                'success' => true,
                'message' => 'Callback processed successfully',
                'status' => $status,
                'mpesa_receipt_number' => $mpesaReceiptNumber
            ];
            
        } catch (Exception $e) {
            error_log('M-Pesa Callback Processing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get transaction by checkout request ID
     */
    public function getTransaction($checkoutRequestId)
    {
        $stmt = $this->pdo->prepare("
            SELECT mt.*, o.order_number, o.total_amount as order_total
            FROM mpesa_transactions mt
            LEFT JOIN orders o ON mt.order_id = o.id
            WHERE mt.checkout_request_id = ?
        ");
        $stmt->execute([$checkoutRequestId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get transactions by order ID
     */
    public function getTransactionsByOrderId($orderId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM mpesa_transactions 
            WHERE order_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cancel pending transaction
     */
    public function cancelTransaction($checkoutRequestId)
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE mpesa_transactions 
                SET status = 'cancelled', updated_at = NOW()
                WHERE checkout_request_id = ? AND status = 'pending'
            ");
            $stmt->execute([$checkoutRequestId]);
            
            return [
                'success' => true,
                'message' => 'Transaction cancelled successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to cancel transaction: ' . $e->getMessage()
            ];
        }
    }
}
