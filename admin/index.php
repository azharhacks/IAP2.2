<?php
/**
 * Admin Dashboard
 * Central hub for all administrative functions
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../ClassAutoload.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: ../Signin.php');
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
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

// Get quick stats
$stats = [];

// Orders stats
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
        SUM(total_amount) as total_revenue
    FROM orders
");
$stats['orders'] = $stmt->fetch();

// Users stats
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
        SUM(CASE WHEN email_verified = 1 THEN 1 ELSE 0 END) as verified_users
    FROM users
");
$stats['users'] = $stmt->fetch();

// M-Pesa stats
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transactions,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
    FROM mpesa_transactions
");
$stats['mpesa'] = $stmt->fetch();

// Initialize layout
$layout = new Layout();

$customCSS = '
    .admin-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        background: white;
        overflow: hidden;
    }
    .admin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }
    .admin-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 1.5rem;
    }
    .stat-card {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(99, 102, 241, 0.3);
    }
    .stat-card.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    .stat-card.success:hover {
        box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
    }
    .stat-card.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    }
    .stat-card.warning:hover {
        box-shadow: 0 8px 30px rgba(245, 158, 11, 0.3);
    }
    .stat-card.mpesa {
        background: linear-gradient(135deg, #00D4AA 0%, #00A693 100%);
    }
    .stat-card.mpesa:hover {
        box-shadow: 0 8px 30px rgba(0, 212, 170, 0.3);
    }
    .quick-action {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s ease;
        text-decoration: none;
        color: #374151;
        display: block;
    }
    .quick-action:hover {
        border-color: #6366f1;
        color: #6366f1;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
        text-decoration: none;
    }
    .quick-action i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
    }
';

$layout->header('Admin Dashboard', $customCSS);
$layout->navbar('admin');
?>

<div class="container my-5">
    <!-- Welcome Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="text-center">
                <h1 class="display-4 fw-bold text-primary mb-3">
                    <i class="fas fa-tachometer-alt me-3"></i>Admin Dashboard
                </h1>
                <p class="lead text-muted">
                    Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>! 
                    Manage your e-commerce platform from here.
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="row mb-5">
        <div class="col-md-3 mb-4">
            <div class="stat-card">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <h3><?php echo number_format($stats['orders']['total_orders']); ?></h3>
                <p class="mb-1">Total Orders</p>
                <small><?php echo $stats['orders']['pending_orders']; ?> pending</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card success">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h3><?php echo number_format($stats['users']['total_users']); ?></h3>
                <p class="mb-1">Total Users</p>
                <small><?php echo $stats['users']['verified_users']; ?> verified</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card mpesa">
                <i class="fas fa-mobile-alt fa-3x mb-3"></i>
                <h3><?php echo number_format($stats['mpesa']['total_transactions']); ?></h3>
                <p class="mb-1">M-Pesa Transactions</p>
                <small><?php echo $stats['mpesa']['pending_transactions']; ?> pending</small>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="stat-card warning">
                <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                <h3>KSh <?php echo number_format($stats['mpesa']['completed_amount']); ?></h3>
                <p class="mb-1">M-Pesa Revenue</p>
                <small><?php echo $stats['mpesa']['completed_transactions']; ?> completed</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="text-center mb-4">Quick Actions</h3>
        </div>
        
        <div class="col-md-4 mb-4">
            <a href="orders.php" class="quick-action">
                <i class="fas fa-shopping-cart text-primary"></i>
                <h5>Manage Orders</h5>
                <p class="text-muted mb-0">View and update customer orders</p>
            </a>
        </div>
        
        <div class="col-md-4 mb-4">
            <a href="mpesa_transactions.php" class="quick-action">
                <i class="fas fa-mobile-alt" style="color: #00D4AA;"></i>
                <h5>M-Pesa Transactions</h5>
                <p class="text-muted mb-0">Monitor payment transactions</p>
            </a>
        </div>
        
        <div class="col-md-4 mb-4">
            <a href="users.php" class="quick-action">
                <i class="fas fa-users text-success"></i>
                <h5>User Management</h5>
                <p class="text-muted mb-0">Manage customer accounts</p>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Recent Orders
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT o.order_number, o.total_amount, o.status, o.created_at, u.username
                        FROM orders o
                        LEFT JOIN users u ON o.user_id = u.id
                        ORDER BY o.created_at DESC
                        LIMIT 5
                    ");
                    $recentOrders = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($recentOrders)): ?>
                    <p class="text-muted text-center py-3">No recent orders</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentOrders as $order): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($order['username'] ?? 'Unknown'); ?> • 
                                        <?php echo date('M j, g:i A', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <strong>KSh <?php echo number_format($order['total_amount']); ?></strong>
                                    <br>
                                    <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="admin-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-mobile-alt me-2" style="color: #00D4AA;"></i>Recent M-Pesa Transactions
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("
                        SELECT mt.amount, mt.status, mt.phone_number, mt.created_at, o.order_number
                        FROM mpesa_transactions mt
                        LEFT JOIN orders o ON mt.order_id = o.id
                        ORDER BY mt.created_at DESC
                        LIMIT 5
                    ");
                    $recentTransactions = $stmt->fetchAll();
                    ?>
                    
                    <?php if (empty($recentTransactions)): ?>
                    <p class="text-muted text-center py-3">No recent transactions</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentTransactions as $transaction): ?>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>KSh <?php echo number_format($transaction['amount']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($transaction['phone_number']); ?> • 
                                        <?php echo date('M j, g:i A', strtotime($transaction['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php 
                                        echo $transaction['status'] === 'completed' ? 'success' : 
                                            ($transaction['status'] === 'failed' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($transaction['order_number'] ?? 'N/A'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $layout->footer(); ?>
