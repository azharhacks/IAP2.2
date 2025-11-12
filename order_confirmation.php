<?php
/**
 * Order Confirmation Page
 * Shows order confirmation details after successful checkout
 * Features: Order summary, payment details, shipping information, next steps
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

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
    SELECT o.*, u.username, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$orderNumber, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name as product_name, p.sku, pi.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$orderItems = $stmt->fetchAll();

// Get order status history
$stmt = $pdo->prepare("
    SELECT * FROM order_status_history 
    WHERE order_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$order['id']]);
$statusHistory = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo htmlspecialchars($conf['site_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .confirmation-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .order-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .order-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
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
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-processing {
            background: #e2e3e5;
            color: #383d41;
        }
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
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
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            background: #007bff;
            border-radius: 50%;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 4px;
            top: 1rem;
            width: 2px;
            height: calc(100% + 1rem);
            background: #e9ecef;
        }
        .timeline-item:last-child::after {
            display: none;
        }
        .summary-row {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i><?php echo htmlspecialchars($conf['site_name']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-list me-1"></i>My Orders
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Confirmation Header -->
        <div class="confirmation-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
                </div>
                <div class="col-md-8">
                    <h1 class="mb-3">Order Confirmed!</h1>
                    <p class="lead mb-0">
                        Thank you for your order. We've received your order and will begin processing it soon.
                    </p>
                </div>
                <div class="col-md-2 text-center">
                    <h4>Order #<?php echo htmlspecialchars($order['order_number']); ?></h4>
                    <small>Placed on <?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                </div>
            </div>
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
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/60'); ?>" 
                                         class="product-image" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                    <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></small>
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

                <!-- Shipping Information -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-shipping-fast me-2"></i>Shipping Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Shipping Address</h6>
                                <div class="text-muted">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Delivery Method</h6>
                                <p class="text-muted">
                                    <?php echo htmlspecialchars($order['shipping_method'] ?? 'Standard Delivery'); ?>
                                </p>
                                
                                <h6>Estimated Delivery</h6>
                                <p class="text-muted">
                                    <?php 
                                    $estimatedDate = date('M j, Y', strtotime($order['created_at'] . ' + 3 days'));
                                    echo $estimatedDate;
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Status Timeline -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-route me-2"></i>Order Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Current Status</h6>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <h6>Payment Status</h6>
                                <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($statusHistory)): ?>
                        <hr>
                        <h6>Status History</h6>
                        <div class="timeline">
                            <?php foreach ($statusHistory as $status): ?>
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo ucfirst($status['status']); ?></strong>
                                        <?php if (!empty($status['comment'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($status['comment']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i A', strtotime($status['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="summary-row d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span>KSh <?php echo number_format($order['subtotal']); ?></span>
                        </div>
                        <div class="summary-row d-flex justify-content-between">
                            <span>Tax (16% VAT):</span>
                            <span>KSh <?php echo number_format($order['tax_amount']); ?></span>
                        </div>
                        <div class="summary-row d-flex justify-content-between">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                    KSh <?php echo number_format($order['shipping_cost']); ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="summary-row d-flex justify-content-between text-primary">
                            <span>Total:</span>
                            <span>KSh <?php echo number_format($order['total_amount']); ?></span>
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
                            <span class="text-capitalize"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Status:</span>
                            <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($order['payment_method'] === 'mpesa'): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>M-Pesa payment instructions will be sent to your phone shortly.</small>
                        </div>
                        <?php elseif ($order['payment_method'] === 'cod'): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            <small>Please have exact change ready when your order arrives.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="order-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>What's Next?
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                Order confirmation email sent
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-clock text-muted me-2"></i>
                                Order will be processed within 24 hours
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-shipping-fast text-muted me-2"></i>
                                Shipping notification will be sent
                            </li>
                            <li class="mb-0">
                                <i class="fas fa-home text-muted me-2"></i>
                                Estimated delivery: <?php echo $estimatedDate; ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <a href="orders.php" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>View All Orders
                    </a>
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Support Information -->
        <div class="order-card mt-4">
            <div class="card-body text-center">
                <h6>Need Help?</h6>
                <p class="text-muted mb-3">
                    If you have any questions about your order, please don't hesitate to contact our customer support team.
                </p>
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-phone text-primary"></i>
                        <div class="mt-2">
                            <strong>Call Us</strong>
                            <br><small class="text-muted">+254 795 550 352</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-envelope text-primary"></i>
                        <div class="mt-2">
                            <strong>Email Us</strong>
                            <br><small class="text-muted">support@<?php echo $_SERVER['HTTP_HOST']; ?></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-comments text-primary"></i>
                        <div class="mt-2">
                            <strong>Live Chat</strong>
                            <br><small class="text-muted">Available 24/7</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
