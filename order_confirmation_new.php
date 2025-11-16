<?php
/**
 * Order Confirmation Page
 * Shows order confirmation details after successful checkout
 * Features: Order summary, payment details, shipping information, next steps
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Initialize layout
$layout = new Layout();

// Redirect to login if not authenticated or 2FA not verified
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: Signin.php');
    exit;
}

// Get order ID from URL
$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) {
    header('Location: orders.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get order details with safe fallbacks
$order = null;
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ? AND o.user_id = ?
    ");
    $stmt->execute([$orderNumber, $userId]);
    $order = $stmt->fetch();
} catch (PDOException $e) {
    // Fallback without users table join
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE order_number = ? AND user_id = ?
        ");
        $stmt->execute([$orderNumber, $userId]);
        $order = $stmt->fetch();
        if ($order) {
            $order['username'] = 'Customer';
            $order['email'] = '';
        }
    } catch (PDOException $e2) {
        header('Location: orders.php');
        exit;
    }
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items with safe fallbacks
$orderItems = [];
$queries = [
    "SELECT oi.*, p.name as product_name, p.image_url, p.sku
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    
    "SELECT oi.*, p.name as product_name, '' as image_url, '' as sku
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
     
    "SELECT oi.*, oi.product_name, '' as image_url, '' as sku
     FROM order_items oi
     WHERE oi.order_id = ?",
     
    "SELECT *, 'Product' as product_name, '' as image_url, '' as sku
     FROM order_items
     WHERE order_id = ?"
];

foreach ($queries as $query) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$order['id']]);
        $orderItems = $stmt->fetchAll();
        break;
    } catch (PDOException $e) {
        continue;
    }
}

// Get order status history with safe fallbacks
try {
    $stmt = $pdo->prepare("
        SELECT * FROM order_status_history 
        WHERE order_id = ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$order['id']]);
    $statusHistory = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist - use empty array
    $statusHistory = [];
}

// Custom CSS for order confirmation
$customCSS = '
    .confirmation-header {
        background: linear-gradient(135deg, #ff6b35 0%, #f7931e 50%, #ff6b35 100%);
        color: white;
        border-radius: 15px;
        padding: 3rem 2rem;
        text-align: center;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(255, 107, 53, 0.3);
    }
    .order-card {
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-radius: 15px;
        margin-bottom: 2rem;
        background: white;
    }
    .order-card .card-header {
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(247, 147, 30, 0.1) 100%);
        border-bottom: 1px solid rgba(255, 107, 53, 0.2);
        border-radius: 15px 15px 0 0 !important;
        padding: 1.5rem;
        color: #ff6b35;
        font-weight: 600;
    }
    .product-item {
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 0;
        transition: all 0.3s ease;
    }
    .product-item:hover {
        background: rgba(255, 107, 53, 0.02);
    }
    .product-item:last-child {
        border-bottom: none;
    }
    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid rgba(255, 107, 53, 0.1);
    }
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
        border: 1px solid transparent;
    }
    .status-pending {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
        border-color: #ffeaa7;
    }
    .status-confirmed {
        background: linear-gradient(135deg, #d1ecf1 0%, #74b9ff 100%);
        color: #0c5460;
        border-color: #74b9ff;
    }
    .status-processing {
        background: linear-gradient(135deg, #e2e3e5 0%, #636e72 100%);
        color: #383d41;
        border-color: #636e72;
    }
    .status-shipped, .status-delivered {
        background: linear-gradient(135deg, #d4edda 0%, #00b894 100%);
        color: #155724;
        border-color: #00b894;
    }
    .timeline {
        position: relative;
    }
    .timeline-item {
        position: relative;
        padding-left: 2rem;
        margin-bottom: 1rem;
    }
    .timeline-item::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.5rem;
        width: 10px;
        height: 10px;
        background: #ff6b35;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
    }
    .timeline-item::after {
        content: "";
        position: absolute;
        left: 4px;
        top: 1rem;
        width: 2px;
        height: calc(100% + 1rem);
        background: linear-gradient(to bottom, #ff6b35 0%, rgba(255, 107, 53, 0.2) 100%);
    }
    .timeline-item:last-child::after {
        display: none;
    }
    .summary-row {
        padding: 0.75rem 0;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }
    .summary-row:hover {
        background: rgba(255, 107, 53, 0.02);
    }
    .summary-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.1rem;
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.1) 0%, rgba(247, 147, 30, 0.1) 100%);
        border-radius: 8px;
        padding: 1rem;
        color: #ff6b35;
    }
    .btn-primary {
        background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
        border: none;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    }
    .btn-outline-primary {
        border-color: #ff6b35;
        color: #ff6b35;
        transition: all 0.3s ease;
    }
    .btn-outline-primary:hover {
        background: #ff6b35;
        border-color: #ff6b35;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
    }
    .btn-outline-secondary {
        border-color: #6c757d;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    .btn-outline-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }
    .alert-info {
        background: linear-gradient(135deg, rgba(0, 212, 170, 0.1) 0%, rgba(0, 166, 147, 0.1) 100%);
        border-color: #00D4AA;
        color: #00A693;
    }
    .alert-warning {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 179, 0, 0.1) 100%);
        border-color: #ffc107;
        color: #856404;
    }
    .text-primary {
        color: #ff6b35 !important;
    }
    .text-success {
        color: #00b894 !important;
    }
    .btn-white {
        background: white;
        color: #ff6b35;
        border: 2px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    .btn-white:hover {
        background: rgba(255, 255, 255, 0.9);
        color: #ff6b35;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        border-color: white;
    }
';

$layout->header('Order Confirmation - ' . $order['order_number'], $customCSS);
$layout->navbar('orders');
$layout->breadcrumb([
    ['title' => 'Home', 'url' => 'dashboard.php'],
    ['title' => 'Orders', 'url' => 'orders.php'],
    ['title' => 'Order Confirmation', 'url' => '', 'active' => true]
]);
$layout->contentStart();

// Confirmation Header
echo '<div class="confirmation-header">';
echo '<div class="row align-items-center">';
echo '<div class="col-md-8">';
echo '<h1 class="mb-3">';
echo '<i class="fas fa-check-circle me-3"></i>Order Confirmed!';
echo '</h1>';
echo '<p class="lead mb-2">Thank you for your order!</p>';
echo '<p class="mb-0">Order Number: <strong>' . htmlspecialchars($order['order_number']) . '</strong></p>';
echo '<p class="mb-0">Order Date: <strong>' . date('F j, Y g:i A', strtotime($order['created_at'])) . '</strong></p>';
echo '</div>';
echo '<div class="col-md-4 text-end">';
echo '<div class="mb-3">';
echo '<span class="status-badge status-' . strtolower($order['status']) . '">';
echo ucfirst($order['status']);
echo '</span>';
echo '</div>';
echo '<a href="order_confirmation_pdf.php?order=' . $order['order_number'] . '&auto_print=1" class="btn btn-white btn-lg" target="_blank">';
echo '<i class="fas fa-download me-2"></i>Download PDF';
echo '</a>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="row">';
// Order Details
echo '<div class="col-lg-8">';

// Order Items
echo '<div class="order-card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">';
echo '<i class="fas fa-box me-2"></i>Order Items';
echo '</h5>';
echo '</div>';
echo '<div class="card-body">';

foreach ($orderItems as $item) {
    echo '<div class="product-item">';
    echo '<div class="row align-items-center">';
    echo '<div class="col-md-1">';
    echo '<img src="' . htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/60') . '" class="product-image" alt="' . htmlspecialchars($item['product_name']) . '">';
    echo '</div>';
    echo '<div class="col-md-6">';
    echo '<h6 class="mb-1">' . htmlspecialchars($item['product_name']) . '</h6>';
    echo '<small class="text-muted">SKU: ' . htmlspecialchars($item['sku'] ?? 'N/A') . '</small>';
    echo '</div>';
    echo '<div class="col-md-2 text-center">';
    echo '<span class="text-muted">Qty: ' . $item['quantity'] . '</span>';
    echo '</div>';
    echo '<div class="col-md-1 text-center">';
    echo '<span class="text-muted">KSh ' . number_format($item['unit_price']) . '</span>';
    echo '</div>';
    echo '<div class="col-md-2 text-end">';
    echo '<strong>KSh ' . number_format($item['unit_price'] * $item['quantity']) . '</strong>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Shipping Information
echo '<div class="order-card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">';
echo '<i class="fas fa-truck me-2"></i>Shipping Information';
echo '</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h6 class="text-primary">Shipping Address</h6>';

// Handle missing shipping fields with fallback values
$shippingAddress = $order['shipping_address'] ?? 'Strathmore University, Ole sangale road, Nairobi, Kenya';
$city = $order['shipping_city'] ?? 'Nairobi';
$state = $order['shipping_state'] ?? 'Nairobi County';
$postal = $order['shipping_postal_code'] ?? '00200';
$country = $order['shipping_country'] ?? 'Kenya';

echo '<p class="mb-1">' . htmlspecialchars($shippingAddress) . '</p>';
echo '<p class="mb-1">' . htmlspecialchars($city . ', ' . $state . ' ' . $postal) . '</p>';
echo '<p class="mb-0">' . htmlspecialchars($country) . '</p>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<h6 class="text-primary">Estimated Delivery</h6>';
echo '<p class="mb-1">3-5 business days</p>';
echo '<p class="mb-0 text-muted">We will send you tracking information once your order ships.</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Status Timeline (if status history exists)
if (!empty($statusHistory)) {
    echo '<div class="order-card">';
    echo '<div class="card-header">';
    echo '<h5 class="mb-0">';
    echo '<i class="fas fa-history me-2"></i>Order Status Timeline';
    echo '</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div class="timeline">';
    
    foreach ($statusHistory as $status) {
        echo '<div class="timeline-item">';
        echo '<div class="d-flex justify-content-between">';
        echo '<strong>' . ucfirst($status['status']) . '</strong>';
        echo '<small class="text-muted">' . date('M j, Y g:i A', strtotime($status['created_at'])) . '</small>';
        echo '</div>';
        if (!empty($status['notes'])) {
            echo '<p class="mb-0 text-muted">' . htmlspecialchars($status['notes']) . '</p>';
        }
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>'; // End col-lg-8

// Order Summary Sidebar
echo '<div class="col-lg-4">';

// Order Summary
echo '<div class="order-card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">';
echo '<i class="fas fa-receipt me-2"></i>Order Summary';
echo '</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="summary-row d-flex justify-content-between">';
echo '<span>Subtotal:</span>';
echo '<span>KSh ' . number_format($order['subtotal']) . '</span>';
echo '</div>';

if ($order['tax_amount'] > 0) {
    echo '<div class="summary-row d-flex justify-content-between">';
    echo '<span>Tax:</span>';
    echo '<span>KSh ' . number_format($order['tax_amount']) . '</span>';
    echo '</div>';
}

if ($order['shipping_cost'] > 0) {
    echo '<div class="summary-row d-flex justify-content-between">';
    echo '<span>Shipping:</span>';
    echo '<span>KSh ' . number_format($order['shipping_cost']) . '</span>';
    echo '</div>';
}

echo '<div class="summary-row d-flex justify-content-between">';
echo '<span>Total:</span>';
echo '<span>KSh ' . number_format($order['total_amount']) . '</span>';
echo '</div>';

echo '</div>';
echo '</div>';

// Payment Information
echo '<div class="order-card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">';
echo '<i class="fas fa-credit-card me-2"></i>Payment Information';
echo '</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="d-flex justify-content-between mb-2">';
echo '<span>Payment Method:</span>';
echo '<strong>' . ucfirst($order['payment_method']) . '</strong>';
echo '</div>';

echo '<div class="d-flex justify-content-between mb-2">';
echo '<span>Payment Status:</span>';
$paymentBadgeClass = $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-warning';
echo '<span class="badge ' . $paymentBadgeClass . '">' . ucfirst($order['payment_status']) . '</span>';
echo '</div>';

// Try to get M-Pesa transaction info if available
$mpesaReceipt = null;
$mpesaDate = null;
try {
    $stmt = $pdo->prepare("
        SELECT transaction_id, created_at 
        FROM mpesa_transactions 
        WHERE order_id = ? AND status = 'completed'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$order['id']]);
    $mpesaData = $stmt->fetch();
    
    if ($mpesaData) {
        $mpesaReceipt = $mpesaData['transaction_id'];
        $mpesaDate = $mpesaData['created_at'];
    }
} catch (PDOException $e) {
    // M-Pesa table doesn't exist - ignore
}

// Show M-Pesa receipt if payment is completed
if ($order['payment_status'] === 'paid' && $order['payment_method'] === 'mpesa' && $mpesaReceipt) {
    echo '<div class="d-flex justify-content-between mb-2">';
    echo '<span>M-Pesa Receipt:</span>';
    echo '<strong>' . htmlspecialchars($mpesaReceipt) . '</strong>';
    echo '</div>';
    
    if ($mpesaDate) {
        echo '<div class="d-flex justify-content-between mb-2">';
        echo '<span>Transaction Date:</span>';
        echo '<span>' . date('M j, Y g:i A', strtotime($mpesaDate)) . '</span>';
        echo '</div>';
    }
    
    echo '<div class="alert alert-success mt-3">';
    echo '<h6><i class="fas fa-check-circle me-2"></i>Payment Confirmed!</h6>';
    echo '<p class="mb-0">Your M-Pesa payment has been successfully processed. Receipt: <strong>' . htmlspecialchars($mpesaReceipt) . '</strong></p>';
    echo '</div>';
    
} elseif ($order['payment_status'] === 'paid') {
    echo '<div class="alert alert-success mt-3">';
    echo '<h6><i class="fas fa-check-circle me-2"></i>Payment Confirmed!</h6>';
    echo '<p class="mb-0">Your payment has been successfully processed.</p>';
    echo '</div>';
    
} elseif ($order['payment_status'] === 'pending' && $order['payment_method'] === 'mpesa') {
    echo '<div class="alert alert-info mt-3">';
    echo '<h6><i class="fas fa-mobile-alt me-2"></i>M-Pesa Payment Instructions</h6>';
    echo '<ol class="mb-0">';
    echo '<li>Go to M-Pesa on your phone</li>';
    echo '<li>Select "Lipa na M-Pesa"</li>';
    echo '<li>Select "Buy Goods and Services"</li>';
    echo '<li>Enter Till Number: <strong>123456</strong></li>';
    echo '<li>Enter Amount: <strong>KSh ' . number_format($order['total_amount']) . '</strong></li>';
    echo '<li>Enter your M-Pesa PIN and confirm</li>';
    echo '</ol>';
    echo '</div>';
} elseif ($order['payment_status'] === 'pending') {
    echo '<div class="alert alert-warning mt-3">';
    echo '<i class="fas fa-clock me-2"></i>';
    echo 'Payment is pending. You will receive a confirmation email once payment is processed.';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Quick Actions
echo '<div class="order-card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">';
echo '<i class="fas fa-cog me-2"></i>Quick Actions';
echo '</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="d-grid gap-2">';
echo '<a href="orders.php" class="btn btn-outline-primary">';
echo '<i class="fas fa-list me-2"></i>View All Orders';
echo '</a>';

echo '<a href="products.php" class="btn btn-outline-secondary">';
echo '<i class="fas fa-shopping-cart me-2"></i>Continue Shopping';
echo '</a>';

if ($order['payment_status'] === 'pending') {
    echo '<a href="checkout.php?retry=' . $order['order_number'] . '" class="btn btn-primary">';
    echo '<i class="fas fa-credit-card me-2"></i>Retry Payment';
    echo '</a>';
}

echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End col-lg-4
echo '</div>'; // End row

$layout->contentEnd();
$layout->footer();
?>
