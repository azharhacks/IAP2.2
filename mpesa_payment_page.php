<?php
/**
 * M-Pesa Payment Page
 * Dedicated page for M-Pesa STK Push payments
 * Features: Phone number input, payment initiation, real-time status updates
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Initialize layout
$layout = new Layout();

// Redirect to login if not authenticated or 2FA not verified
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: Signin.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Get order ID from URL
$orderId = (int)($_GET['order'] ?? 0);

if (!$orderId) {
    header('Location: dashboard.php');
    exit;
}

// Initialize database connection
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$userId = $_SESSION['user_id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(oi.product_name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.id = ? AND o.user_id = ?
    GROUP BY o.id
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

// Check if order is already paid
if ($order['payment_status'] === 'paid') {
    header('Location: order_confirmation.php?order=' . $orderId);
    exit;
}

// Check for existing pending M-Pesa transaction
$stmt = $pdo->prepare("
    SELECT checkout_request_id, created_at 
    FROM mpesa_transactions 
    WHERE order_id = ? AND status = 'pending'
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$orderId]);
$existingTransaction = $stmt->fetch();

// Custom CSS for M-Pesa payment page
$customCSS = '
    .mpesa-container {
        max-width: 600px;
        margin: 0 auto;
    }
    .mpesa-header {
        background: linear-gradient(135deg, #00D4AA 0%, #00A693 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    .mpesa-logo {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
    }
    .step {
        display: flex;
        align-items: center;
        margin: 0 1rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        background: #f8f9fa;
        color: #6c757d;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    .step.active {
        background: #00D4AA;
        color: white;
    }
    .step.completed {
        background: #28a745;
        color: white;
    }
    .step-number {
        background: rgba(255,255,255,0.3);
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.5rem;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .payment-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .payment-form {
        padding: 2rem;
    }
    .phone-input-group {
        position: relative;
        margin-bottom: 1.5rem;
    }
    .phone-prefix {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-weight: 500;
        z-index: 3;
    }
    .phone-input {
        padding-left: 70px;
        height: 50px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }
    .phone-input:focus {
        border-color: #00D4AA;
        box-shadow: 0 0 0 0.2rem rgba(0, 212, 170, 0.25);
    }
    .pay-button {
        background: linear-gradient(135deg, #00D4AA 0%, #00A693 100%);
        border: none;
        padding: 1rem 2rem;
        border-radius: 10px;
        color: white;
        font-size: 1.1rem;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }
    .pay-button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 212, 170, 0.4);
    }
    .pay-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    .status-card {
        padding: 1.5rem;
        text-align: center;
        display: none;
    }
    .status-card.show {
        display: block;
    }
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #00D4AA;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .order-summary {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    .instructions {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .success-icon {
        color: #28a745;
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .error-icon {
        color: #dc3545;
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .progress-bar-container {
        background: #e9ecef;
        border-radius: 10px;
        height: 6px;
        margin: 1rem 0;
        overflow: hidden;
    }
    .progress-bar {
        background: linear-gradient(90deg, #00D4AA, #00A693);
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
    }
';

$customJS = '
    let currentStep = 1;
    let checkoutRequestId = null;
    let statusCheckInterval = null;
    let timeoutTimer = null;
    
    const steps = {
        1: "Enter Phone Number",
        2: "Confirm Payment",
        3: "Payment Complete"
    };
    
    function updateStepIndicator(step) {
        currentStep = step;
        const stepElements = document.querySelectorAll(".step");
        
        stepElements.forEach((element, index) => {
            const stepNum = index + 1;
            element.classList.remove("active", "completed");
            
            if (stepNum < step) {
                element.classList.add("completed");
            } else if (stepNum === step) {
                element.classList.add("active");
            }
        });
        
        // Update progress bar
        const progress = ((step - 1) / 2) * 100;
        document.querySelector(".progress-bar").style.width = progress + "%";
    }
    
    function showStatus(type, message, showSpinner = false) {
        const statusCard = document.querySelector(".status-card");
        const statusIcon = statusCard.querySelector(".status-icon");
        const statusMessage = statusCard.querySelector(".status-message");
        const loadingSpinner = statusCard.querySelector(".loading-spinner");
        
        statusCard.classList.add("show");
        statusCard.className = "status-card show " + type;
        
        if (showSpinner) {
            loadingSpinner.style.display = "block";
            statusIcon.style.display = "none";
        } else {
            loadingSpinner.style.display = "none";
            statusIcon.style.display = "block";
            
            if (type === "success") {
                statusIcon.innerHTML = \'<i class="fas fa-check-circle success-icon"></i>\';
            } else if (type === "error") {
                statusIcon.innerHTML = \'<i class="fas fa-times-circle error-icon"></i>\';
            } else {
                statusIcon.innerHTML = \'<i class="fas fa-info-circle text-info" style="font-size: 3rem;"></i>\';
            }
        }
        
        statusMessage.textContent = message;
    }
    
    function hideStatus() {
        document.querySelector(".status-card").classList.remove("show");
    }
    
    function validatePhoneNumber(phone) {
        // Remove any non-digit characters
        const cleaned = phone.replace(/\D/g, "");
        
        // Check various valid formats
        if (cleaned.match(/^254[17]\d{8}$/)) return true; // 254712345678
        if (cleaned.match(/^0[17]\d{8}$/)) return true;   // 0712345678
        if (cleaned.match(/^[17]\d{8}$/)) return true;     // 712345678
        
        return false;
    }
    
    function formatPhoneNumber(phone) {
        const cleaned = phone.replace(/\D/g, "");
        
        if (cleaned.startsWith("254")) {
            return cleaned;
        } else if (cleaned.startsWith("0")) {
            return "254" + cleaned.substring(1);
        } else if (cleaned.length === 9) {
            return "254" + cleaned;
        }
        
        return cleaned;
    }
    
    async function initiatePayment() {
        const phoneInput = document.getElementById("phoneNumber");
        const phoneNumber = phoneInput.value.trim();
        
        if (!phoneNumber) {
            alert("Please enter your phone number");
            phoneInput.focus();
            return;
        }
        
        if (!validatePhoneNumber(phoneNumber)) {
            alert("Please enter a valid Kenyan phone number (e.g., 0712345678)");
            phoneInput.focus();
            return;
        }
        
        const payButton = document.getElementById("payButton");
        payButton.disabled = true;
        payButton.innerHTML = \'<i class="fas fa-spinner fa-spin me-2"></i>Initiating Payment...\';
        
        showStatus("info", "Sending payment request to your phone...", true);
        updateStepIndicator(2);
        
        try {
            const response = await fetch("mpesa_payment.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    action: "initiate_payment",
                    order_id: ' . $orderId . ',
                    phone_number: phoneNumber
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                checkoutRequestId = result.checkout_request_id;
                showStatus("info", "Payment request sent! Please check your phone and enter your M-Pesa PIN.", true);
                
                // Start checking payment status
                startStatusCheck();
                
                // Set timeout for payment (5 minutes)
                timeoutTimer = setTimeout(() => {
                    showStatus("error", "Payment timeout. Please try again.");
                    resetPaymentForm();
                }, 300000); // 5 minutes
                
            } else {
                showStatus("error", result.message || "Failed to initiate payment");
                resetPaymentForm();
            }
            
        } catch (error) {
            console.error("Payment initiation error:", error);
            showStatus("error", "Network error. Please check your connection and try again.");
            resetPaymentForm();
        }
    }
    
    function startStatusCheck() {
        if (!checkoutRequestId) return;
        
        statusCheckInterval = setInterval(async () => {
            try {
                const response = await fetch("mpesa_payment.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        action: "check_status",
                        checkout_request_id: checkoutRequestId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.status === "completed") {
                        clearInterval(statusCheckInterval);
                        clearTimeout(timeoutTimer);
                        
                        showStatus("success", "Payment successful! Redirecting to order confirmation...");
                        updateStepIndicator(3);
                        
                        setTimeout(() => {
                            window.location.href = "order_confirmation.php?order=' . $orderId . '";
                        }, 3000);
                        
                    } else if (result.status === "failed") {
                        clearInterval(statusCheckInterval);
                        clearTimeout(timeoutTimer);
                        
                        showStatus("error", result.result_desc || "Payment failed. Please try again.");
                        resetPaymentForm();
                    }
                    // If pending, continue checking
                }
                
            } catch (error) {
                console.error("Status check error:", error);
            }
        }, 3000); // Check every 3 seconds
    }
    
    function resetPaymentForm() {
        const payButton = document.getElementById("payButton");
        payButton.disabled = false;
        payButton.innerHTML = \'<i class="fas fa-mobile-alt me-2"></i>Pay with M-Pesa\';
        
        updateStepIndicator(1);
        checkoutRequestId = null;
        
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
            statusCheckInterval = null;
        }
        
        if (timeoutTimer) {
            clearTimeout(timeoutTimer);
            timeoutTimer = null;
        }
        
        setTimeout(() => {
            hideStatus();
        }, 5000);
    }
    
    async function cancelPayment() {
        if (!checkoutRequestId) return;
        
        try {
            const response = await fetch("mpesa_payment.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    action: "cancel_payment",
                    checkout_request_id: checkoutRequestId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showStatus("info", "Payment cancelled");
            }
            
        } catch (error) {
            console.error("Cancel payment error:", error);
        }
        
        resetPaymentForm();
    }
    
    // Initialize page
    document.addEventListener("DOMContentLoaded", function() {
        updateStepIndicator(1);
        
        // Format phone number as user types
        const phoneInput = document.getElementById("phoneNumber");
        phoneInput.addEventListener("input", function() {
            let value = this.value.replace(/\D/g, "");
            
            // Format display
            if (value.startsWith("254")) {
                value = value.substring(3);
            } else if (value.startsWith("0")) {
                value = value.substring(1);
            }
            
            // Add formatting
            if (value.length >= 3) {
                value = value.substring(0, 3) + " " + value.substring(3);
            }
            if (value.length >= 7) {
                value = value.substring(0, 7) + " " + value.substring(7);
            }
            
            this.value = value;
        });
        
        // Enter key support
        phoneInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                initiatePayment();
            }
        });
    });
';

$layout->header('M-Pesa Payment', $customCSS);
?>

<div class="container my-4">
    <div class="mpesa-container">
        <!-- M-Pesa Header -->
        <div class="mpesa-header">
            <div class="mpesa-logo">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h2 class="mb-2">M-Pesa Payment</h2>
            <p class="mb-0">Secure mobile money payment</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step">
                <div class="step-number">1</div>
                <span>Enter Phone</span>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <span>Confirm</span>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <span>Complete</span>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar-container">
            <div class="progress-bar"></div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <h5 class="mb-3">
                <i class="fas fa-receipt me-2"></i>Order Summary
            </h5>
            <div class="d-flex justify-content-between mb-2">
                <span>Order Number:</span>
                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>Items:</span>
                <span><?php echo $order['item_count']; ?> item(s)</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span>Products:</span>
                <span class="text-muted small"><?php echo htmlspecialchars(substr($order['product_names'], 0, 50)) . (strlen($order['product_names']) > 50 ? '...' : ''); ?></span>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <h6>Total Amount:</h6>
                <h6 class="text-success">KSh <?php echo number_format($order['total_amount'], 2); ?></h6>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h6 class="text-primary mb-3">
                <i class="fas fa-info-circle me-2"></i>How to pay with M-Pesa
            </h6>
            <ol class="mb-0">
                <li>Enter your M-Pesa registered phone number below</li>
                <li>Click "Pay with M-Pesa" button</li>
                <li>You'll receive an STK push notification on your phone</li>
                <li>Enter your M-Pesa PIN to complete the payment</li>
                <li>You'll be redirected to order confirmation upon success</li>
            </ol>
        </div>

        <!-- Payment Form -->
        <div class="payment-card">
            <div class="payment-form">
                <h5 class="mb-4">
                    <i class="fas fa-phone me-2"></i>Enter Your Phone Number
                </h5>
                
                <div class="phone-input-group">
                    <span class="phone-prefix">+254</span>
                    <input type="tel" 
                           class="form-control phone-input" 
                           id="phoneNumber" 
                           placeholder="712 345 678"
                           maxlength="11"
                           <?php if ($existingTransaction): ?>
                           value="<?php echo substr($existingTransaction['phone_number'] ?? '', 3); ?>"
                           <?php endif; ?>>
                </div>
                
                <button type="button" 
                        class="pay-button" 
                        id="payButton"
                        onclick="initiatePayment()">
                    <i class="fas fa-mobile-alt me-2"></i>Pay KSh <?php echo number_format($order['total_amount'], 2); ?>
                </button>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Powered by Safaricom M-Pesa
                    </small>
                </div>
            </div>
            
            <!-- Status Card -->
            <div class="status-card">
                <div class="loading-spinner"></div>
                <div class="status-icon"></div>
                <div class="status-message"></div>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="cancelPayment()">
                        <i class="fas fa-times me-1"></i>Cancel Payment
                    </button>
                </div>
            </div>
        </div>

        <!-- Back to Checkout -->
        <div class="text-center mt-4">
            <a href="checkout.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Checkout
            </a>
        </div>
    </div>
</div>

<script>
<?php echo $customJS; ?>
</script>

<?php
$layout->footer();
?>
