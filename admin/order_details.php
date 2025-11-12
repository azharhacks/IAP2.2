<?php
/**
 * Simple Order Details for AJAX - Minimal Working Version
 */

session_start();
require_once '../config.php';

// Basic auth check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Access denied - Admin privileges required</div>');
}

$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    exit('<div class="alert alert-warning">No order ID provided</div>');
}

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        exit('<div class="alert alert-warning">Order not found</div>');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    exit('<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>');
}
?>

<div class="row">
    <div class="col-md-6">
        <h5>Order Information</h5>
        <table class="table table-sm">
            <tr><td><strong>Order ID:</strong></td><td><?php echo $order['id']; ?></td></tr>
            <tr><td><strong>Order Number:</strong></td><td><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></td></tr>
            <tr><td><strong>Customer:</strong></td><td><?php echo htmlspecialchars($order['username'] ?? 'Unknown'); ?></td></tr>
            <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></td></tr>
            <tr><td><strong>Status:</strong></td><td>
                <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </td></tr>
            <tr><td><strong>Total:</strong></td><td><strong>KSh <?php echo number_format($order['total_amount']); ?></strong></td></tr>
            <tr><td><strong>Created:</strong></td><td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td></tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h5>Order Items</h5>
        <?php if (empty($orderItems)): ?>
        <p class="text-muted">No items found</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name'] ?? $item['product_name'] ?? 'Unknown'); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>KSh <?php echo number_format($item['unit_price']); ?></td>
                        <td>KSh <?php echo number_format($item['unit_price'] * $item['quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <h5>Shipping Address</h5>
        <div class="bg-light p-3 rounded">
            <?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? 'No address provided')); ?>
        </div>
    </div>
</div>
