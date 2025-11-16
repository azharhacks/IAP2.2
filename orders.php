<?php
/**
 * My Orders - SMARTDUKA E-commerce Platform
 * User orders management with Layout class integration
 */

session_start();
require_once 'config.php';
require_once 'Abstract/Layout.php';

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

// Get user orders with proper error handling
try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    $stmt = $pdo->prepare("
        SELECT o.id,
               o.order_number,
               o.user_id,
               o.total_amount,
               COALESCE(o.order_status, 'pending') as order_status,
               COALESCE(o.payment_status, 'pending') as payment_status,
               o.payment_method,
               o.shipping_address,
               o.created_at,
               o.updated_at,
               GROUP_CONCAT(
                   CONCAT(oi.product_name, ' (', oi.quantity, 'x)')
                   SEPARATOR ', '
               ) as items_summary,
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id, o.order_number, o.user_id, o.total_amount, o.order_status, o.payment_status, 
                 o.payment_method, o.shipping_address, o.created_at, o.updated_at
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Orders fetch error: " . $e->getMessage());
    $orders = [];
    $error_message = "Unable to load orders at this time. Please try again later.";
}

$layout = new Layout($conf);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $layout->header('My Orders - SMARTDUKA'); ?>
    <style>
        /* SMARTDUKA Orders Page Styling */
        .orders-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .page-header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            text-align: center;
            border-radius: 0 0 20px 20px;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }
        
        .order-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 107, 53, 0.1);
            border-radius: 16px;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.08),
                0 2px 8px rgba(255, 107, 53, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 12px 40px rgba(0, 0, 0, 0.12),
                0 4px 16px rgba(255, 107, 53, 0.2);
            border-color: rgba(255, 107, 53, 0.3);
        }
        
        .order-header {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-number {
            font-size: 1.1rem;
            font-weight: 700;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .order-date {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .order-body {
            padding: 1.5rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
            color: white;
        }
        
        .status-processing {
            background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
            color: white;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-cancelled {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .progress-container {
            margin: 1.5rem 0;
        }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(90deg, #e9ecef 0%, #f8f9fa 100%);
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b35 0%, #f7931e 100%);
            border-radius: 4px;
            transition: width 0.6s ease;
            position: relative;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .order-items {
            background: rgba(248, 249, 250, 0.5);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid rgba(255, 107, 53, 0.1);
        }
        
        .order-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: #ff6b35;
            text-align: right;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-order-action {
            border-radius: 25px;
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .empty-orders-icon {
            font-size: 4rem;
            color: #ff6b35;
            margin-bottom: 1rem;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .order-card {
                margin-bottom: 1rem;
            }
            
            .order-header {
                padding: 1rem;
            }
            
            .order-body {
                padding: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-order-action {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php $layout->navbar('orders'); ?>
    
    <div class="orders-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <h1 class="page-title">
                    <i class="fas fa-shopping-bag me-3"></i>My Orders
                </h1>
                <p class="page-subtitle">Track and manage your SMARTDUKA purchases</p>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <?php 
        try {
            $layout->breadcrumb([
                ['title' => 'Dashboard', 'url' => 'dashboard.php'],
                ['title' => 'My Orders', 'url' => '']
            ]); 
        } catch (Exception $e) {
            // Breadcrumb failed, show simple navigation
            echo '<nav aria-label="breadcrumb" class="container mt-3">';
            echo '<ol class="breadcrumb">';
            echo '<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>';
            echo '<li class="breadcrumb-item active" aria-current="page">My Orders</li>';
            echo '</ol>';
            echo '</nav>';
        }
        ?>
        
        <!-- Main Content -->
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <!-- Empty State -->
                <div class="empty-orders">
                    <div class="empty-orders-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>No Orders Yet</h3>
                    <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <div class="row">
                    <?php foreach ($orders as $order): 
                        // Calculate progress percentage
                        $progress = match($order['order_status']) {
                            'pending' => 25,
                            'processing' => 50,
                            'shipped' => 75,
                            'completed' => 100,
                            'cancelled' => 0,
                            default => 25
                        };
                    ?>
                        <div class="col-12">
                            <div class="order-card">
                                <!-- Order Header -->
                                <div class="order-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="order-number">
                                                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                                            </div>
                                            <div class="order-date">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php 
                                            $statusIcons = [
                                                'pending' => 'clock',
                                                'processing' => 'cog',
                                                'shipped' => 'truck',
                                                'completed' => 'check-circle',
                                                'cancelled' => 'times-circle'
                                            ];
                                            $icon = $statusIcons[$order['order_status']] ?? 'info-circle';
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Order Body -->
                                <div class="order-body">
                                    <!-- Progress Bar -->
                                    <?php if ($order['order_status'] !== 'cancelled'): ?>
                                        <div class="progress-container">
                                            <div class="d-flex justify-content-between mb-2">
                                                <small class="text-muted">Order Progress</small>
                                                <small class="text-muted"><?php echo $progress; ?>%</small>
                                            </div>
                                            <div class="progress-bar-custom">
                                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Order Details -->
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="order-items">
                                                <h6 class="mb-2">
                                                    <i class="fas fa-box me-2"></i>
                                                    Items (<?php echo $order['total_items'] ?? $order['item_count']; ?>)
                                                </h6>
                                                <p class="mb-0 text-muted">
                                                    <?php echo htmlspecialchars($order['items_summary'] ?? 'Order items'); ?>
                                                </p>
                                            </div>
                                            
                                            <?php if (!empty($order['shipping_address'])): ?>
                                                <div class="mt-3">
                                                    <h6>
                                                        <i class="fas fa-map-marker-alt me-2"></i>
                                                        Shipping Address
                                                    </h6>
                                                    <p class="text-muted mb-0">
                                                        <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="text-end">
                                                <div class="order-total">
                                                    KSh <?php echo number_format($order['total_amount'], 2); ?>
                                                </div>
                                                
                                                <?php if (!empty($order['payment_method'])): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-credit-card me-1"></i>
                                                            <?php echo ucfirst($order['payment_method']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Action Buttons -->
                                                <div class="action-buttons mt-3">
                                                    <a href="order_confirmation.php?order=<?php echo urlencode($order['order_number']); ?>" 
                                                       class="btn-order-action btn-view">
                                                        <i class="fas fa-eye"></i>
                                                        View Details
                                                    </a>
                                                    
                                                    <?php 
                                                    // Show cancel button for orders that can be cancelled
                                                    $canCancel = (
                                                        $order['order_status'] === 'pending' || 
                                                        ($order['order_status'] === 'processing' && $order['payment_status'] !== 'paid')
                                                    ) && $order['order_status'] !== 'cancelled';
                                                    
                                                    if ($canCancel): 
                                                    ?>
                                                        <button class="btn-order-action btn-cancel" 
                                                                onclick="cancelOrder('<?php echo $order['id']; ?>')"
                                                                title="Cancel this order">
                                                            <i class="fas fa-times"></i>
                                                            Cancel Order
                                                        </button>
                                                    <?php elseif ($order['order_status'] === 'cancelled'): ?>
                                                        <span class="btn-order-action" style="background: #6c757d; cursor: not-allowed;">
                                                            <i class="fas fa-ban"></i>
                                                            Cancelled
                                                        </span>
                                                    <?php elseif ($order['payment_status'] === 'paid'): ?>
                                                        <span class="btn-order-action" style="background: #28a745; cursor: not-allowed;">
                                                            <i class="fas fa-lock"></i>
                                                            Paid
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Continue Shopping -->
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Continue Shopping
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php $layout->contentEnd(); ?>
    <?php $layout->footer(); ?>
    
    <script>
        // Cancel order functionality
        function cancelOrder(orderId) {
            // Show detailed confirmation
            const confirmMessage = 
                'Are you sure you want to cancel this order?\n\n' +
                '• This action cannot be undone\n' +
                '• Product stock will be restored\n' +
                '• Any pending payments will be cancelled\n\n' +
                'Click OK to proceed with cancellation.';
                
            if (confirm(confirmMessage)) {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="loading-spinner"></span> Cancelling...';
                btn.disabled = true;
                
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ 
                        order_id: orderId,
                        timestamp: new Date().getTime()
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert('✅ Order cancelled successfully!\n\nOrder #' + (data.order_number || orderId) + ' has been cancelled.');
                        
                        // Reload the page to show updated status
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                })
                .catch(error => {
                    console.error('Cancel order error:', error);
                    alert('❌ Failed to cancel order: ' + error.message + '\n\nPlease try again or contact support.');
                    
                    // Reset button
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        }
        
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
        
        // Add tooltips to status badges
        const statusBadges = document.querySelectorAll('.status-badge');
        statusBadges.forEach(badge => {
            const status = badge.textContent.trim().toLowerCase();
            let tooltip = '';
            
            switch(status) {
                case 'pending':
                    tooltip = 'Your order has been received and is being processed';
                    break;
                case 'processing':
                    tooltip = 'Your order is being prepared for shipment';
                    break;
                case 'shipped':
                    tooltip = 'Your order is on its way to you';
                    break;
                case 'completed':
                    tooltip = 'Your order has been successfully delivered';
                    break;
                case 'cancelled':
                    tooltip = 'This order has been cancelled';
                    break;
            }
            
            if (tooltip) {
                badge.setAttribute('title', tooltip);
                badge.setAttribute('data-bs-toggle', 'tooltip');
            }
        });
        
        // Initialize Bootstrap tooltips
        if (typeof bootstrap !== 'undefined') {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    </script>
</body>
</html>