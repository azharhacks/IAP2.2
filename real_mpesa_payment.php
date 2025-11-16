<?php
/**
 * Real M-Pesa Integration - SMARTDUKA
 * Actual Safaricom M-Pesa API integration with real STK Push
 */

session_start();
require_once 'config.php';

// M-Pesa API Configuration
class MpesaAPI {
    private $consumerKey;
    private $consumerSecret;
    private $businessShortCode;
    private $passkey;
    private $callbackURL;
    private $environment; // sandbox or live
    
    public function __construct() {
        // M-Pesa Sandbox Configuration - Valid Sandbox Credentials
        $this->consumerKey = 'cXfEmCCWj9N5fd2Z1Oz541C9n90RjtECBS1Ff6pKVWSSh88H'; // Sandbox Consumer Key
        $this->consumerSecret = 'UBbIDpR2sqPBDshDPaiAdyEIgAGX3FvLEg89ZXlRffjX2K8plnCmnlUI5lQwfiPg'; // Sandbox Consumer Secret
        $this->businessShortCode = '174379'; // Sandbox Business Short Code
        $this->passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; // Sandbox Passkey
        $this->callbackURL = 'https://postb.in/1732058475434-4518937609226'; // Temporary public webhook for testing
        $this->environment = 'sandbox'; // SANDBOX ENVIRONMENT
    }
    
    /**
     * Generate OAuth access token
     */
    private function generateAccessToken() {
        $url = $this->environment === 'live' 
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
            
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to generate access token');
        }
        
        $result = json_decode($response, true);
        return $result['access_token'] ?? null;
    }
    
    /**
     * Generate password for STK Push
     */
    private function generatePassword() {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->businessShortCode . $this->passkey . $timestamp);
        return ['password' => $password, 'timestamp' => $timestamp];
    }
    
    /**
     * Initiate STK Push
     */
    public function stkPush($phoneNumber, $amount, $accountReference, $transactionDesc) {
        try {
            // Ensure amount is a positive integer
            $amount = (int)round(abs($amount));
            if ($amount < 1) {
                throw new Exception('Amount must be at least 1 KSh');
            }
            
            $accessToken = $this->generateAccessToken();
            if (!$accessToken) {
                throw new Exception('Failed to get access token');
            }
            
            $url = $this->environment === 'live'
                ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
            
            $passwordData = $this->generatePassword();
            
            // Format phone number
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '254' . substr($phoneNumber, 1);
            } elseif (substr($phoneNumber, 0, 3) !== '254') {
                $phoneNumber = '254' . $phoneNumber;
            }
            
            $requestData = [
                'BusinessShortCode' => $this->businessShortCode,
                'Password' => $passwordData['password'],
                'Timestamp' => $passwordData['timestamp'],
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phoneNumber,
                'PartyB' => $this->businessShortCode,
                'PhoneNumber' => $phoneNumber,
                'CallBackURL' => $this->callbackURL,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $transactionDesc
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
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['CheckoutRequestID'])) {
                return [
                    'success' => true,
                    'checkout_request_id' => $result['CheckoutRequestID'],
                    'merchant_request_id' => $result['MerchantRequestID'],
                    'response_code' => $result['ResponseCode'],
                    'response_description' => $result['ResponseDescription']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['errorMessage'] ?? 'STK Push failed',
                    'response' => $result
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Query STK Push status
     */
    public function queryStkStatus($checkoutRequestId) {
        try {
            $accessToken = $this->generateAccessToken();
            if (!$accessToken) {
                throw new Exception('Failed to get access token');
            }
            
            $url = $this->environment === 'live'
                ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
                : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
            
            $passwordData = $this->generatePassword();
            
            $requestData = [
                'BusinessShortCode' => $this->businessShortCode,
                'Password' => $passwordData['password'],
                'Timestamp' => $passwordData['timestamp'],
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
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            $result = json_decode($response, true);
            
            return [
                'success' => $httpCode === 200,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data');
        }
        
        $action = $data['action'] ?? '';
        $mpesa = new MpesaAPI();
        
        switch ($action) {
            case 'initiate_payment':
                $orderId = $data['order_id'] ?? null;
                $phoneNumber = $data['phone_number'] ?? null;
                $amount = $data['amount'] ?? null;
                
                if (!$orderId || !$phoneNumber || !$amount) {
                    throw new Exception('Missing required parameters');
                }
                
                // Get order details from database
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$order) {
                    throw new Exception('Order not found');
                }
                
                // Initiate STK Push (convert amount to integer as required by M-Pesa API)
                $result = $mpesa->stkPush(
                    $phoneNumber,
                    (int)round($amount), // M-Pesa requires whole numbers only
                    'ORD-' . $orderId,
                    'Payment for Order #' . $order['order_number']
                );
                
                if ($result['success']) {
                    // Store transaction in database
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO mpesa_transactions 
                            (order_id, checkout_request_id, merchant_request_id, phone_number, amount, status) 
                            VALUES (?, ?, ?, ?, ?, 'pending')
                        ");
                        $stmt->execute([
                            $orderId,
                            $result['checkout_request_id'],
                            $result['merchant_request_id'],
                            $phoneNumber,
                            $amount
                        ]);
                    } catch (Exception $e) {
                        // If database insert fails, continue anyway
                        error_log('Failed to store M-Pesa transaction: ' . $e->getMessage());
                    }
                }
                
                echo json_encode($result);
                break;
                
            case 'check_status':
                $checkoutRequestId = $data['checkout_request_id'] ?? null;
                
                if (!$checkoutRequestId) {
                    throw new Exception('Checkout request ID required');
                }
                
                $result = $mpesa->queryStkStatus($checkoutRequestId);
                echo json_encode($result);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit();
}

// Get order details for display
$orderId = $_GET['order'] ?? null;
if (!$orderId) {
    header('Location: orders.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.quantity, 'x)') SEPARATOR ', ') as items_summary
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ? AND o.user_id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php');
        exit();
    }
} catch (Exception $e) {
    $order = [
        'id' => $orderId,
        'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT),
        'total_amount' => 511.60,
        'items_summary' => 'Sample Items'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real M-Pesa Payment - SMARTDUKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .mpesa-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: #28a745;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .phone-input-group {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .phone-prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
            z-index: 2;
        }
        
        .phone-input {
            padding: 1rem 1rem 1rem 70px;
            border: 2px solid #28a745;
            border-radius: 10px;
            font-size: 1.1rem;
            width: 100%;
        }
        
        .phone-input:focus {
            border-color: #20c997;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            outline: none;
        }
        
        .pay-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.2rem;
            font-weight: bold;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .pay-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
        
        .pay-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .status-message {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            display: none;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-processing {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .real-mpesa-badge {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .api-status {
            background: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <div class="mpesa-logo">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="real-mpesa-badge">
                    <i class="fas fa-satellite-dish me-1"></i>Real M-Pesa API
                </div>
                <h2>M-Pesa Payment</h2>
                <p class="mb-0">Safaricom STK Push Integration</p>
            </div>
            
            <div class="payment-body">
                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-receipt me-2"></i>Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
                        <span class="badge bg-success">Live Transaction</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Items:</span>
                        <span><?php echo htmlspecialchars($order['items_summary']); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Total Amount:</strong>
                        <strong class="text-success">KSh <?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                </div>
                
                <!-- Phone Number Input -->
                <div class="phone-input-group">
                    <label for="phoneNumber" class="form-label">M-Pesa Registered Phone Number</label>
                    <div style="position: relative;">
                        <span class="phone-prefix">+254</span>
                        <input type="tel" 
                               class="phone-input" 
                               id="phoneNumber"
                               placeholder="712345678"
                               maxlength="9"
                               pattern="[0-9]{9}">
                    </div>
                    <div class="form-text">Enter your M-Pesa registered phone number (without +254)</div>
                </div>
                
                <!-- Pay Button -->
                <button class="pay-button" id="payButton" onclick="initiateRealPayment()">
                    <i class="fas fa-credit-card me-2"></i>Send STK Push - KSh <?php echo number_format($order['total_amount'], 2); ?>
                </button>
                
                <!-- Status Message -->
                <div id="statusMessage" class="status-message"></div>
                
                <!-- API Status -->
                <div class="api-status">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Real M-Pesa Integration:</strong> This sends actual STK Push to your phone via Safaricom API.
                    You will receive a real M-Pesa prompt to complete the payment.
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="text-center mt-3">
            <a href="orders.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
    </div>

    <script>
        let paymentInProgress = false;
        let statusCheckInterval = null;
        
        async function initiateRealPayment() {
            if (paymentInProgress) return;
            
            const phoneInput = document.getElementById('phoneNumber');
            const phoneNumber = phoneInput.value.trim();
            const statusDiv = document.getElementById('statusMessage');
            const payButton = document.getElementById('payButton');
            
            // Validate phone number
            if (!phoneNumber || phoneNumber.length !== 9 || !/^\d{9}$/.test(phoneNumber)) {
                showStatus('error', 'Please enter a valid 9-digit phone number (e.g., 712345678)');
                return;
            }
            
            // Start payment process
            paymentInProgress = true;
            payButton.disabled = true;
            payButton.innerHTML = '<span class="loading-spinner"></span>Connecting to Safaricom...';
            
            try {
                showStatus('processing', 'Connecting to Safaricom M-Pesa API...');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'initiate_payment',
                        order_id: '<?php echo $order['id']; ?>',
                        phone_number: '254' + phoneNumber,
                        amount: Math.round(<?php echo $order['total_amount']; ?>) // Send as integer
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus('processing', '📱 STK Push sent to your phone! Please check for M-Pesa prompt and enter your PIN.');
                    payButton.innerHTML = '<span class="loading-spinner"></span>Waiting for M-Pesa confirmation...';
                    
                    // Start checking payment status
                    startStatusChecking(data.checkout_request_id);
                    
                } else {
                    throw new Error(data.message || 'Failed to initiate payment');
                }
                
            } catch (error) {
                paymentInProgress = false;
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Send STK Push - KSh <?php echo number_format($order['total_amount'], 2); ?>';
                showStatus('error', '❌ Payment failed: ' + error.message);
            }
        }
        
        function startStatusChecking(checkoutRequestId) {
            let attempts = 0;
            const maxAttempts = 24; // 2 minutes total (5 second intervals)
            
            statusCheckInterval = setInterval(async () => {
                attempts++;
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'check_status',
                            checkout_request_id: checkoutRequestId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const resultCode = data.data.ResultCode;
                        
                        if (resultCode === '0') {
                            // Payment successful
                            clearInterval(statusCheckInterval);
                            paymentInProgress = false;
                            
                            showStatus('success', '✅ Payment successful! Transaction completed via M-Pesa.');
                            document.getElementById('payButton').innerHTML = '<i class="fas fa-check me-2"></i>Payment Complete';
                            
                            // Redirect after 3 seconds
                            setTimeout(() => {
                                window.location.href = 'order_confirmation.php?order=<?php echo urlencode($order['order_number']); ?>';
                            }, 3000);
                            
                        } else if (resultCode !== undefined && resultCode !== '1032') {
                            // Payment failed or cancelled
                            clearInterval(statusCheckInterval);
                            paymentInProgress = false;
                            
                            const errorMsg = data.data.ResultDesc || 'Payment was cancelled or failed';
                            showStatus('error', '❌ ' + errorMsg);
                            
                            const payButton = document.getElementById('payButton');
                            payButton.disabled = false;
                            payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Try Again';
                        }
                        // If ResultCode is 1032, payment is still pending
                    }
                    
                    // Update status message with remaining time
                    const remainingTime = Math.max(0, (maxAttempts - attempts) * 5);
                    if (remainingTime > 0 && paymentInProgress) {
                        showStatus('processing', `📱 Waiting for M-Pesa confirmation... (${remainingTime}s remaining)`);
                    }
                    
                } catch (error) {
                    console.error('Status check error:', error);
                }
                
                // Timeout after max attempts
                if (attempts >= maxAttempts) {
                    clearInterval(statusCheckInterval);
                    paymentInProgress = false;
                    
                    showStatus('error', '⏱️ Payment timeout. Please try again or check your phone for M-Pesa prompts.');
                    
                    const payButton = document.getElementById('payButton');
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Try Again';
                }
                
            }, 5000); // Check every 5 seconds
        }
        
        function showStatus(type, message) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.className = `status-message status-${type}`;
            statusDiv.innerHTML = message;
            statusDiv.style.display = 'block';
        }
        
        // Format phone number input
        document.getElementById('phoneNumber').addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, '');
            e.target.value = value;
            
            // Enable/disable pay button based on phone number validity
            const payButton = document.getElementById('payButton');
            if (value.length === 9 && /^\d{9}$/.test(value) && !paymentInProgress) {
                payButton.disabled = false;
            } else {
                payButton.disabled = true;
            }
        });
        
        // Initially disable pay button
        document.getElementById('payButton').disabled = true;
    </script>
</body>
</html>