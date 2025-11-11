<?php
/**
 * Dashboard Page
 * Main landing page after successful login
 * Shows user profile, recent orders, and featured products
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Check if user is logged in and 2FA is verified
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $conf['site_url'] . '/Signin.php');
    exit();
}

// Check if 2FA is verified
if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: ' . $conf['site_url'] . '/2fa_verify.php');
    exit();
}

// Get user data
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $conn = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: logout.php');
        exit();
    }

    // Initialize managers for dashboard data
    $productManager = new ProductManager($conn);
    $orderManager = new OrderManager($conn);
    $cartManager = new CartManager($conn);

    // Get dashboard statistics
    $featuredProducts = $productManager->getFeaturedProducts(6);
    $ordersData = $orderManager->getUserOrders($_SESSION['user_id'], 1, 5); // Last 5 orders
    $recentOrders = $ordersData['orders'] ?? []; // Extract the orders array
    $orderStats = $orderManager->getOrderStats($_SESSION['user_id']);
    $cartTotals = $cartManager->getCartTotals($_SESSION['user_id']);
    
    // Set dashboard variables for template use
    $userName = $user['username'];
    $orderCount = count($recentOrders);

} catch (PDOException $e) {
    error_log("Database error in dashboard: " . $e->getMessage());
    $error_message = "Unable to load dashboard data. Please try again later.";
    // Set default values on error
    $userName = 'User';
    $orderCount = 0;
    $featuredProducts = [];
    $recentOrders = [];
}

// Create layout instance
$layout = new Layout();

// Start the page
$layout->header('My Dashboard');
$layout->navbar('dashboard');

// Custom banner for dashboard
$layout->banner($conf, 
    'Welcome back, ' . htmlspecialchars($user['username']) . '!', 
    'Manage your account, track orders, and discover new products.',
    'Continue Shopping',
    'products.php'
);

$layout->breadcrumb([
    'Dashboard'
]);

$layout->contentStart();

// Check for access denied error
$access_error = '';
if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
    $access_error = 'Access denied. You need administrator privileges to access that page.';
}
?>

<!-- Dashboard Content -->
<div class="container-fluid py-4">
    
    <?php if ($access_error): ?>
    <!-- Access Denied Alert -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Access Denied:</strong> <?php echo htmlspecialchars($access_error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-3">
                                <i class="fas fa-home text-primary me-2"></i>
                                Welcome back, <?php echo htmlspecialchars($userName); ?>!
                            </h2>
                            <p class="text-muted mb-3">
                                Manage your orders, browse products, and track your shopping activity from your personalized dashboard.
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-1"></i>Browse Products
                                </a>
                                <a href="orders.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-1"></i>View Orders
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="stats-card">
                                <h3 class="text-primary"><?php echo $orderCount; ?></h3>
                                <p class="text-muted mb-0">Total Orders</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Quick Actions</h4>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon bg-primary-light mb-3">
                        <i class="fas fa-shopping-cart text-primary"></i>
                    </div>
                    <h5>Browse Products</h5>
                    <p class="text-muted small">Discover our latest collection</p>
                    <a href="products.php" class="btn btn-outline-primary btn-sm">Shop Now</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon bg-success-light mb-3">
                        <i class="fas fa-list text-success"></i>
                    </div>
                    <h5>My Orders</h5>
                    <p class="text-muted small">Track your order history</p>
                    <a href="orders.php" class="btn btn-outline-success btn-sm">View Orders</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon bg-warning-light mb-3">
                        <i class="fas fa-shopping-basket text-warning"></i>
                    </div>
                    <h5>Shopping Cart</h5>
                    <p class="text-muted small">Review your cart items</p>
                    <a href="cart.php" class="btn btn-outline-warning btn-sm">View Cart</a>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="feature-icon bg-info-light mb-3">
                        <i class="fas fa-user-circle text-info"></i>
                    </div>
                    <h5>Account Settings</h5>
                    <p class="text-muted small">Manage your profile</p>
                    <a href="#" class="btn btn-outline-info btn-sm">Settings</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <?php if (!empty($recentOrders)): ?>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-primary me-2"></i>Recent Orders
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_slice($recentOrders, 0, 3) as $order): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title">Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></h6>
                                    <p class="text-muted small mb-2">
                                        <?php echo date('M j, Y', strtotime($order['created_at'] ?? 'now')); ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>KSh <?php echo number_format($order['total_amount'] ?? 0, 2); ?></strong>
                                    </p>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] === 'delivered' ? 'success' : 
                                            ($order['status'] === 'pending' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="orders.php" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i>View All Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.stats-card {
    background: rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 1.5rem;
}

.bg-primary-light {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

.bg-success-light {
    background-color: rgba(var(--bs-success-rgb), 0.1);
}

.bg-warning-light {
    background-color: rgba(var(--bs-warning-rgb), 0.1);
}

.bg-info-light {
    background-color: rgba(var(--bs-info-rgb), 0.1);
}
</style>

<?php
$layout->contentEnd();
$layout->footer();
?>
