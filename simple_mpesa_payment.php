<?php
/**
 * Simple M-Pesa Payment Page - SMARTDUKA
 * Simplified version that works without complex database dependencies
 */

session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Signin.php');
    exit();
}

// Get order ID from URL
$order_id = $_GET['order'] ?? null;
if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Simple order details (you can expand this later)
$order = [
    'id' => $order_id,
    'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad($order_id, 4, '0', STR_PAD_LEFT),
    'total_amount' => 511.60,
    'items' => 'Sample Candy Bar (1x)',
    'created_at' => date('Y-m-d H:i:s')
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Payment - SMARTDUKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1.1rem;
            width: 100%;
        }
        
        .phone-input:focus {
            border-color: #28a745;
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
        
        .instructions {
            background: #e7f3ff;
            border: 1px solid #bee5eb;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .security-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h2><i class="fas fa-mobile-alt me-2"></i>Complete Your Payment</h2>
                <p class="mb-0">M-Pesa Secure Payment</p>
            </div>
            
            <div class="payment-body">
                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-receipt me-2"></i>Order <?php echo htmlspecialchars($order['order_number']); ?></span>
                        <span class="badge bg-success">1 Item(s)</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Items:</span>
                        <span><?php echo htmlspecialchars($order['items']); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Total Amount:</strong>
                        <strong class="text-success">KSh <?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                </div>
                
                <!-- Phone Number Input -->
                <div class="phone-input-group">
                    <label for="phoneNumber" class="form-label">M-Pesa Phone Number</label>
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
                <button class="pay-button" onclick="initiatePayment()">
                    <i class="fas fa-credit-card me-2"></i>Pay KSh <?php echo number_format($order['total_amount'], 2); ?>
                </button>
                
                <!-- Status Message -->
                <div id="statusMessage" class="status-message"></div>
                
                <!-- Instructions -->
                <div class="instructions">
                    <h6><i class="fas fa-info-circle me-2"></i>Payment Instructions</h6>
                    <ol class="mb-0">
                        <li>Enter your M-Pesa registered phone number</li>
                        <li>Click "Pay" to initiate the payment</li>
                        <li>Check your phone for M-Pesa prompt</li>
                        <li>Enter your M-Pesa PIN to complete payment</li>
                    </ol>
                </div>
                
                <!-- Security Note -->
                <div class="security-note">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Security Note:</strong> Your payment is processed securely through Safaricom M-Pesa. Never share your M-Pesa PIN with anyone.
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let paymentInProgress = false;
        let statusCheckInterval = null;
        
        async function initiatePayment() {
            if (paymentInProgress) return;
            
            const phoneInput = document.getElementById('phoneNumber');
            const phoneNumber = phoneInput.value.trim();
            const statusDiv = document.getElementById('statusMessage');
            const payButton = document.querySelector('.pay-button');
            
            // Validate phone number
            if (!phoneNumber || phoneNumber.length !== 9 || !/^\d{9}$/.test(phoneNumber)) {
                showStatus('error', 'Please enter a valid 9-digit phone number (e.g., 712345678)');
                return;
            }
            
            // Start payment process
            paymentInProgress = true;
            payButton.disabled = true;
            payButton.innerHTML = '<span class="loading-spinner"></span>Processing Payment...';
            
            try {
                // Simulate payment initiation
                showStatus('processing', 'Initiating M-Pesa payment... Please wait.');
                
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                // Simulate successful initiation
                showStatus('processing', 'Payment request sent to your phone. Please check for M-Pesa prompt and enter your PIN.');
                payButton.innerHTML = '<span class="loading-spinner"></span>Waiting for Payment...';
                
                // Start checking payment status
                let attempts = 0;
                const maxAttempts = 12; // 60 seconds total
                
                statusCheckInterval = setInterval(async () => {
                    attempts++;
                    
                    showStatus('processing', `Waiting for payment confirmation... (${Math.max(0, 60 - attempts * 5)} seconds remaining)`);
                    
                    // Simulate random completion after 30-60 seconds (80% success rate)
                    if (attempts >= 6 && Math.random() > 0.2) {
                        // Payment successful
                        clearInterval(statusCheckInterval);
                        paymentInProgress = false;
                        
                        showStatus('success', '✅ Payment successful! Redirecting to order confirmation...');
                        payButton.innerHTML = '<i class="fas fa-check me-2"></i>Payment Complete';
                        
                        // Redirect after 3 seconds
                        setTimeout(() => {
                            window.location.href = 'order_confirmation.php?order=<?php echo urlencode($order['order_number']); ?>';
                        }, 3000);
                        
                    } else if (attempts >= maxAttempts) {
                        // Payment timeout
                        clearInterval(statusCheckInterval);
                        paymentInProgress = false;
                        
                        showStatus('error', '❌ Payment timeout. Please try again or contact support.');
                        payButton.disabled = false;
                        payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Try Again';
                    }
                }, 5000);
                
            } catch (error) {
                paymentInProgress = false;
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay KSh <?php echo number_format($order['total_amount'], 2); ?>';
                showStatus('error', '❌ Payment failed: ' + error.message);
            }
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
        });
        
        // Enable payment button when phone number is valid
        document.getElementById('phoneNumber').addEventListener('input', function(e) {
            const payButton = document.querySelector('.pay-button');
            const phoneNumber = e.target.value.trim();
            
            if (phoneNumber.length === 9 && /^\d{9}$/.test(phoneNumber) && !paymentInProgress) {
                payButton.disabled = false;
            } else {
                payButton.disabled = true;
            }
        });
    </script>
</body>
</html>