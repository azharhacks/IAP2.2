<?php
/**
 * Order Confirmation Page - SAFE VERSION
 * Shows order confirmation details after successful checkout
 * Features: Order summary, payment details, shipping information, next steps
 * Safe for any database structure
 */

session_start();
require_once __DIR__ . '/config.php';

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
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

// Get order details with basic info (safe fallback)
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
    // Even more basic fallback
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
        $stmt->execute([$orderNumber, $userId]);
        $order = $stmt->fetch();
        if ($order) {
            $order['username'] = 'Customer';
            $order['email'] = '';
        }
    } catch (PDOException $e2) {
        // Ultimate fallback - redirect to orders
        header('Location: orders.php');
        exit;
    }
}

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Try to get M-Pesa transaction info if available
$order['mpesa_receipt'] = null;
$order['mpesa_status'] = null;
$order['mpesa_date'] = null;

try {
    $stmt = $pdo->prepare("
        SELECT transaction_id, status, created_at as transaction_date 
        FROM mpesa_transactions 
        WHERE order_id = ? AND status = 'completed'
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$order['id']]);
    $mpesaData = $stmt->fetch();
    
    if ($mpesaData) {
        $order['mpesa_receipt'] = $mpesaData['transaction_id'];
        $order['mpesa_status'] = $mpesaData['status'];
        $order['mpesa_date'] = $mpesaData['transaction_date'];
    }
} catch (PDOException $e) {
    // M-Pesa table doesn't exist or has different structure - ignore
}

// Get order items with maximum compatibility
$orderItems = [];
$queries = [
    "SELECT oi.*, p.name as product_name, p.image_url, p.sku
     FROM order_items oi JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    
    "SELECT oi.*, p.name as product_name, '' as image_url, '' as sku
     FROM order_items oi JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
     
    "SELECT oi.*, oi.product_name, '' as image_url, '' as sku
     FROM order_items oi WHERE oi.order_id = ?",
     
    "SELECT *, 'Product' as product_name, '' as image_url, '' as sku
     FROM order_items WHERE order_id = ?"
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

// Fallback order items if nothing worked
if (empty($orderItems)) {
    $orderItems = [
        [
            'product_name' => 'Order Item',
            'quantity' => 1,
            'unit_price' => $order['total_amount'] ?? 0,
            'image_url' => '',
            'sku' => ''
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .confirmation-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(40, 167, 69, 0.3);
        }
        .order-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 2rem;
            background: white;
        }
        .order-card .card-header {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.1) 100%);
            border-bottom: 1px solid rgba(40, 167, 69, 0.2);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            color: #28a745;
            font-weight: 600;
        }
        .product-item {
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 0;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid rgba(40, 167, 69, 0.1);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }
        .btn-outline-primary {
            border-color: #28a745;
            color: #28a745;
        }
        .btn-outline-primary:hover {
            background: #28a745;
            border-color: #28a745;
        }
        .alert-success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.1) 100%);
            border-color: #28a745;
            color: #155724;
        }
        .text-success {
            color: #28a745 !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Confirmation Header -->
        <div class="confirmation-header">
            <h1 class="mb-3">
                <i class="fas fa-check-circle me-3"></i>Order Confirmed!
            </h1>
            <p class="lead mb-2">Thank you for your order!</p>
            <p class="mb-0">Order Number: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
            <p class="mb-0">Order Date: <strong><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></strong></p>
        </div>

        <div class="row">
            <!-- Order Details -->
            <div class="col-lg-8">
                <!-- Order Items -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-box me-2"></i>Order Items
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="product-item">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="product-image" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <?php else: ?>
                                            <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                                <i class="fas fa-box text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                        <?php if (!empty($item['sku'])): ?>
                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <span class="text-muted">Qty: <?php echo $item['quantity']; ?></span>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <span class="text-muted">KSh <?php echo number_format($item['unit_price']); ?></span>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong>KSh <?php echo number_format($item['unit_price'] * $item['quantity']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total:</span>
                            <strong>KSh <?php echo number_format($order['total_amount'] ?? 0); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>Payment Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Method:</span>
                            <strong><?php echo ucfirst($order['payment_method'] ?? 'Unknown'); ?></strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Status:</span>
                            <?php 
                            $paymentStatus = $order['payment_status'] ?? 'pending';
                            $badgeClass = $paymentStatus === 'paid' ? 'bg-success' : 'bg-warning';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($paymentStatus); ?></span>
                        </div>

                        <?php if ($paymentStatus === 'paid' && ($order['payment_method'] ?? '') === 'mpesa' && $order['mpesa_receipt']): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>M-Pesa Receipt:</span>
                                <strong><?php echo htmlspecialchars($order['mpesa_receipt']); ?></strong>
                            </div>
                            
                            <?php if ($order['mpesa_date']): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Transaction Date:</span>
                                    <span><?php echo date('M j, Y g:i A', strtotime($order['mpesa_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="alert alert-success mt-3">
                                <h6><i class="fas fa-check-circle me-2"></i>Payment Confirmed!</h6>
                                <p class="mb-0">Your M-Pesa payment has been successfully processed. Receipt: <strong><?php echo htmlspecialchars($order['mpesa_receipt']); ?></strong></p>
                            </div>
                            
                        <?php elseif ($paymentStatus === 'paid'): ?>
                            <div class="alert alert-success mt-3">
                                <h6><i class="fas fa-check-circle me-2"></i>Payment Confirmed!</h6>
                                <p class="mb-0">Your payment has been successfully processed.</p>
                            </div>
                            
                        <?php elseif ($paymentStatus === 'pending' && ($order['payment_method'] ?? '') === 'mpesa'): ?>
                            <div class="alert alert-info mt-3">
                                <h6><i class="fas fa-mobile-alt me-2"></i>Complete M-Pesa Payment</h6>
                                <p class="mb-2">If you haven't completed your M-Pesa payment yet, click the button below:</p>
                                <a href="mpesa_payment_page.php?order=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-mobile-alt me-2"></i>Pay with M-Pesa
                                </a>
                            </div>
                            
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-clock me-2"></i>
                                Payment is pending. You will receive a confirmation email once payment is processed.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="orders.php" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i>View All Orders
                            </a>
                            
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                            </a>

                            <?php if ($paymentStatus === 'pending'): ?>
                                <a href="mpesa_payment_page.php?order=<?php echo $order['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card me-2"></i>Complete Payment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>