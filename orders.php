<?php
/**
 * Orders Page
 * Shows user order history and tracking information
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Check if user is logged in and 2FA verified
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: Signin.php?redirect=orders.php');
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

// Initialize order manager
$orderManager = new OrderManager($pdo);

// Get user orders
$orderResult = $orderManager->getUserOrders($userId);
$orders = $orderResult['orders'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - <?php echo htmlspecialchars($conf['site_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-3px);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-processing { background: #e2e3e5; color: #383d41; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .empty-orders {
            text-align: center;
            padding: 4rem 0;
        }
        .empty-orders i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1rem;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="fas fa-th-grid me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">
                            <i class="fas fa-list me-1"></i>My Orders
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>Account
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">My Orders</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-list me-2"></i>My Orders
            </h2>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
            </a>
        </div>

        <?php if (empty($orders)): ?>
        <!-- Empty Orders -->
        <div class="empty-orders">
            <i class="fas fa-receipt"></i>
            <h3 class="text-muted">No orders yet</h3>
            <p class="text-muted mb-4">Looks like you haven't placed any orders yet.</p>
            <a href="products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
            </a>
        </div>
        <?php else: ?>
        
        <!-- Orders List -->
        <div class="row">
            <?php foreach ($orders as $order): ?>
            <div class="col-12">
                <div class="order-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <!-- Order Info -->
                            <div class="col-md-3">
                                <h6 class="mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h6>
                                <small class="text-muted">
                                    Placed on <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                </small>
                            </div>
                            
                            <!-- Status -->
                            <div class="col-md-2 text-center">
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            
                            <!-- Items Count -->
                            <div class="col-md-2 text-center">
                                <div class="text-muted small">Items</div>
                                <strong><?php echo $order['item_count'] ?? 'N/A'; ?></strong>
                            </div>
                            
                            <!-- Total -->
                            <div class="col-md-2 text-center">
                                <div class="text-muted small">Total</div>
                                <strong class="text-primary">KSh <?php echo number_format($order['total_amount']); ?></strong>
                            </div>
                            
                            <!-- Actions -->
                            <div class="col-md-3 text-end">
                                <a href="order_confirmation.php?order=<?php echo $order['order_number']; ?>" 
                                   class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="cancelOrder('<?php echo $order['order_number']; ?>')">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'delivered'): ?>
                                <button class="btn btn-outline-success btn-sm" disabled>
                                    <i class="fas fa-check"></i> Delivered
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Order Progress Bar -->
                        <?php if ($order['status'] !== 'pending'): ?>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <?php
                                $progress = 0;
                                switch ($order['status']) {
                                    case 'confirmed': $progress = 25; break;
                                    case 'processing': $progress = 50; break;
                                    case 'shipped': $progress = 75; break;
                                    case 'delivered': $progress = 100; break;
                                }
                                ?>
                                <div class="progress-bar bg-primary" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">Confirmed</small>
                                <small class="text-muted">Processing</small>
                                <small class="text-muted">Shipped</small>
                                <small class="text-muted">Delivered</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelOrder(orderNumber) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // Here you would make an AJAX call to cancel the order
                alert('Order cancellation functionality would be implemented here.');
            }
        }
    </script>
</body>
</html>
