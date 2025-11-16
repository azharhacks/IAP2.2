<?php
/**
 * M-Pesa Payment Page - SMARTDUKA E-commerce Platform
 * User-facing M-Pesa payment interface with Layout integration
 */

session_start();
require_once 'config.php';

// Try to include Layout class with error handling
try {
    require_once 'Abstract/Layout.php';
} catch (Exception $e) {
    die("Layout class error: " . $e->getMessage());
}

// Check if user is logged in (be flexible about verification status)
if (!isset($_SESSION['user_id'])) {
    header('Location: Signin.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Optional verification check - some systems may not use this
if (isset($_SESSION['verified']) && $_SESSION['verified'] === false) {
    // Only redirect if explicitly set to false
    header('Location: Signin.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get order ID from URL
$order_id = $_GET['order'] ?? null;
if (!$order_id) {
    header('Location: orders.php');
    exit();
}

// Get order details with proper error handling
try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Verify order belongs to user and get details
    $stmt = $pdo->prepare("
        SELECT o.*, 
               u.username, u.email,
               GROUP_CONCAT(
                   CONCAT(oi.product_name, ' (', oi.quantity, 'x)')
                   SEPARATOR '<br>'
               ) as items_list,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_items
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ? AND o.user_id = ?
        GROUP BY o.id, o.order_number, o.user_id, o.total_amount, o.created_at, u.username, u.email
    ");
    
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception("Order not found or access denied");
    }
    
    // Check if order is already paid
    if ($order['payment_status'] === 'paid') {
        header('Location: order_confirmation.php?order=' . urlencode($order['order_number']));
        exit();
    }
    
    // Check for existing pending M-Pesa transaction
    $stmt = $pdo->prepare("
        SELECT * FROM mpesa_transactions 
        WHERE order_id = ? AND status = 'pending'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    $existing_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("M-Pesa payment page error: " . $e->getMessage());
    $error_message = "Database Error: " . $e->getMessage();
    $order = null;
}

// Debug output for development
if (!$order && isset($error_message)) {
    echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px; border-left: 4px solid #dc3545;'>";
    echo "<h3>Debug Information</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error_message) . "</p>";
    echo "<p><strong>Order ID:</strong> " . htmlspecialchars($order_id ?? 'Not provided') . "</p>";
    echo "<p><strong>User ID:</strong> " . htmlspecialchars($_SESSION['user_id'] ?? 'Not logged in') . "</p>";
    echo "<p><strong>Database Connection:</strong> " . (isset($pdo) && $pdo ? 'Available' : 'Not available') . "</p>";
    echo "</div>";
}

$layout = new Layout($conf);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $layout->header('M-Pesa Payment - SMARTDUKA'); ?>
    <style>
        /* SMARTDUKA M-Pesa Payment Page Styling */
        .payment-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            text-align: center;
            border-radius: 0 0 20px 20px;
        }
        
        .mpesa-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            color: #00d4aa;
        }
        
        .payment-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(0, 212, 170, 0.1);
        }
        
        .payment-header {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .order-summary {
            background: rgba(248, 249, 250, 0.8);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 212, 170, 0.1);
        }
        
        .phone-input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .phone-prefix {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: #00d4aa;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .phone-input {
            padding-left: 5rem !important;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            height: 50px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .phone-input:focus {
            border-color: #00d4aa;
            box-shadow: 0 0 0 0.2rem rgba(0, 212, 170, 0.25);
        }
        
        .btn-mpesa {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .btn-mpesa:hover {
            background: linear-gradient(135deg, #00b894 0%, #009f7f 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 212, 170, 0.3);
            color: white;
        }
        
        .btn-mpesa:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }
        
        .btn-mpesa .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .payment-status {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .status-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
            color: white;
        }
        
        .status-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-failed {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .instructions {
            background: rgba(0, 212, 170, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid #00d4aa;
        }
        
        .security-note {
            background: rgba(255, 107, 53, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 4px solid #ff6b35;
        }
        
        @media (max-width: 768px) {
            .payment-body {
                padding: 1rem;
            }
            
            .phone-prefix {
                left: 0.75rem;
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
            
            .phone-input {
                padding-left: 4.5rem !important;
            }
        }
    </style>
</head>
<body>
    <?php $layout->navbar(''); ?>
    
    <div class="payment-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <div class="mpesa-logo">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h1 class="mb-2">M-Pesa Payment</h1>
                <p class="mb-0 opacity-90">Pay securely using M-Pesa</p>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <?php 
        try {
            $layout->breadcrumb([
                ['title' => 'Dashboard', 'url' => 'dashboard.php'],
                ['title' => 'Orders', 'url' => 'orders.php'],
                ['title' => 'M-Pesa Payment', 'url' => '']
            ]); 
        } catch (Exception $e) {
            // Breadcrumb failed, show simple navigation
            echo '<nav aria-label="breadcrumb" class="container mt-3">';
            echo '<ol class="breadcrumb">';
            echo '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>';
            echo '<li class="breadcrumb-item"><a href="orders.php">Orders</a></li>';
            echo '<li class="breadcrumb-item active" aria-current="page">M-Pesa Payment</li>';
            echo '</ol>';
            echo '</nav>';
        }
        ?>
        
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <!-- Payment Form -->
                        <div class="payment-card" id="paymentForm">
                            <div class="payment-header">
                                <h4 class="mb-0">Complete Your Payment</h4>
                            </div>
                            
                            <div class="payment-body">
                                <!-- Order Summary -->
                                <div class="order-summary">
                                    <h5 class="mb-3">
                                        <i class="fas fa-shopping-bag me-2"></i>
                                        Order Summary
                                    </h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <div class="h4 text-success mb-0">
                                                KSh <?php echo number_format($order['total_amount'], 2); ?>
                                            </div>
                                            <small class="text-muted"><?php echo $order['total_items']; ?> item(s)</small>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="order-items">
                                        <h6>Items:</h6>
                                        <div class="small text-muted">
                                            <?php echo $order['items_list']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Phone Number Input -->
                                <div class="phone-input-group">
                                    <label class="form-label">M-Pesa Phone Number</label>
                                    <div class="position-relative">
                                        <span class="phone-prefix">+254</span>
                                        <input type="tel" 
                                               class="form-control phone-input" 
                                               id="phoneNumber"
                                               placeholder="712345678"
                                               value=""
                                               maxlength="9"
                                               pattern="[0-9]{9}">
                                    </div>
                                    <small class="form-text text-muted">
                                        Enter your M-Pesa registered phone number (without +254)
                                    </small>
                                </div>
                                
                                <!-- Payment Button -->
                                <button type="button" class="btn btn-mpesa" id="payButton" onclick="initiatePayment()">
                                    <span class="btn-text">
                                        <i class="fas fa-mobile-alt me-2"></i>
                                        Pay KSh <?php echo number_format($order['total_amount'], 2); ?>
                                    </span>
                                    <span class="spinner"></span>
                                </button>
                                
                                <!-- Instructions -->
                                <div class="instructions">
                                    <h6><i class="fas fa-info-circle me-2"></i>Payment Instructions</h6>
                                    <ol class="mb-0 small">
                                        <li>Enter your M-Pesa registered phone number</li>
                                        <li>Click "Pay" to initiate the payment</li>
                                        <li>Check your phone for M-Pesa prompt</li>
                                        <li>Enter your M-Pesa PIN to complete payment</li>
                                    </ol>
                                </div>
                                
                                <!-- Security Note -->
                                <div class="security-note">
                                    <h6><i class="fas fa-shield-alt me-2"></i>Security Note</h6>
                                    <p class="mb-0 small">
                                        Your payment is processed securely through Safaricom M-Pesa. 
                                        Never share your M-Pesa PIN with anyone.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Status -->
                        <div class="payment-card payment-status" id="paymentStatus">
                            <div class="payment-body">
                                <div class="status-icon" id="statusIcon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h4 id="statusTitle">Processing Payment...</h4>
                                <p id="statusMessage" class="text-muted">
                                    Please check your phone for the M-Pesa prompt and enter your PIN.
                                </p>
                                <div class="mt-3">
                                    <button class="btn btn-outline-secondary" onclick="checkPaymentStatus()">
                                        <i class="fas fa-sync-alt me-2"></i>Check Status
                                    </button>
                                    <button class="btn btn-outline-danger ms-2" onclick="cancelPayment()">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php $layout->contentEnd(); ?>
    <?php $layout->footer(); ?>
    
    <script>
        let currentCheckoutRequestId = null;
        let statusCheckInterval = null;
        
        // Phone number formatting
        document.getElementById('phoneNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            e.target.value = value;
        });
        
        // Initiate M-Pesa payment
        async function initiatePayment() {
            const phoneNumber = document.getElementById('phoneNumber').value.trim();
            
            if (!phoneNumber || phoneNumber.length !== 9) {
                alert('Please enter a valid 9-digit phone number');
                return;
            }
            
            const payButton = document.getElementById('payButton');
            const btnText = payButton.querySelector('.btn-text');
            const spinner = payButton.querySelector('.spinner');
            
            // Show loading state
            payButton.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'inline-block';
            
            try {
                const response = await fetch('mpesa_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'initiate_payment',
                        order_id: <?php echo $order_id; ?>,
                        phone_number: '254' + phoneNumber
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentCheckoutRequestId = data.checkout_request_id;
                    showPaymentStatus('pending');
                    
                    // Start checking payment status
                    statusCheckInterval = setInterval(checkPaymentStatus, 5000);
                    
                    // Auto-check after 30 seconds
                    setTimeout(checkPaymentStatus, 30000);
                } else {
                    throw new Error(data.message || 'Failed to initiate payment');
                }
                
            } catch (error) {
                alert('Payment initiation failed: ' + error.message);
                
                // Reset button
                payButton.disabled = false;
                btnText.style.display = 'inline';
                spinner.style.display = 'none';
            }
        }
        
        // Check payment status
        async function checkPaymentStatus() {
            if (!currentCheckoutRequestId) return;
            
            try {
                const response = await fetch('mpesa_payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'check_status',
                        checkout_request_id: currentCheckoutRequestId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const status = data.status || data.transaction?.status;
                    
                    if (status === 'completed') {
                        clearInterval(statusCheckInterval);
                        showPaymentStatus('success');
                        
                        // Redirect to confirmation page after 3 seconds
                        setTimeout(() => {
                            window.location.href = 'order_confirmation.php?order=<?php echo urlencode($order['order_number']); ?>';
                        }, 3000);
                        
                    } else if (status === 'failed' || status === 'cancelled') {
                        clearInterval(statusCheckInterval);
                        showPaymentStatus('failed');
                    }
                    // Keep checking if still pending
                }
                
            } catch (error) {
                console.error('Status check failed:', error);
            }
        }
        
        // Show payment status
        function showPaymentStatus(status) {
            const form = document.getElementById('paymentForm');
            const statusDiv = document.getElementById('paymentStatus');
            const icon = document.getElementById('statusIcon');
            const title = document.getElementById('statusTitle');
            const message = document.getElementById('statusMessage');
            
            form.style.display = 'none';
            statusDiv.style.display = 'block';
            
            switch (status) {
                case 'pending':
                    icon.className = 'status-icon status-pending';
                    icon.innerHTML = '<i class="fas fa-clock"></i>';
                    title.textContent = 'Payment Pending';
                    message.textContent = 'Please check your phone for the M-Pesa prompt and enter your PIN to complete the payment.';
                    break;
                    
                case 'success':
                    icon.className = 'status-icon status-success';
                    icon.innerHTML = '<i class="fas fa-check"></i>';
                    title.textContent = 'Payment Successful!';
                    message.textContent = 'Your payment has been processed successfully. Redirecting to order confirmation...';
                    break;
                    
                case 'failed':
                    icon.className = 'status-icon status-failed';
                    icon.innerHTML = '<i class="fas fa-times"></i>';
                    title.textContent = 'Payment Failed';
                    message.textContent = 'Your payment could not be processed. Please try again or contact support.';
                    break;
            }
        }
        
        // Cancel payment
        function cancelPayment() {
            if (confirm('Are you sure you want to cancel this payment?')) {
                clearInterval(statusCheckInterval);
                
                if (currentCheckoutRequestId) {
                    fetch('mpesa_payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'cancel_payment',
                            checkout_request_id: currentCheckoutRequestId
                        })
                    });
                }
                
                window.location.href = 'orders.php';
            }
        }
        
        // Check for existing pending transaction
        <?php if ($existing_transaction): ?>
            currentCheckoutRequestId = '<?php echo $existing_transaction['checkout_request_id']; ?>';
            showPaymentStatus('pending');
            statusCheckInterval = setInterval(checkPaymentStatus, 5000);
        <?php endif; ?>
    </script>
</body>
</html>