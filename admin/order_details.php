<?php
session_start();
require_once '../config.php';
require_once '../ClassAutoload.php';

// Admin check - require login and admin role
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

// Check if user has admin or super_admin role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed');
}

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
    http_response_code(400);
    exit('Invalid order ID');
}

$orderManager = new OrderManager($pdo);
$order = $orderManager->getOrderDetails($orderId);

if (!$order) {
    http_response_code(404);
    exit('Order not found');
}

// Calculate totals
$subtotal = 0;
foreach ($order['items'] as $item) {
    $subtotal += $item['total_price'];
}
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="mb-3">Order Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Order Number:</strong></td>
                <td><?= htmlspecialchars($order['order_number']) ?></td>
            </tr>
            <tr>
                <td><strong>Customer:</strong></td>
                <td>
                    <?= htmlspecialchars($order['username']) ?><br>
                    <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                </td>
            </tr>
            <tr>
                <td><strong>Order Date:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <?php
                    $statusColors = [
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $statusColor = $statusColors[$order['status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $statusColor ?>"><?= ucfirst($order['status']) ?></span>
                </td>
            </tr>
            <tr>
                <td><strong>Payment Status:</strong></td>
                <td>
                    <?php
                    $paymentColors = [
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info'
                    ];
                    $paymentColor = $paymentColors[$order['payment_status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $paymentColor ?>"><?= ucfirst($order['payment_status']) ?></span>
                </td>
            </tr>
            <tr>
                <td><strong>Payment Method:</strong></td>
                <td><?= htmlspecialchars($order['payment_method']) ?></td>
            </tr>
            <?php if (!empty($order['tracking_number'])): ?>
            <tr>
                <td><strong>Tracking Number:</strong></td>
                <td><?= htmlspecialchars($order['tracking_number']) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="mb-3">Shipping Information</h6>
        <div class="card bg-light">
            <div class="card-body">
                <?php if (!empty($order['shipping_address'])): ?>
                    <address class="mb-0">
                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                    </address>
                <?php else: ?>
                    <em class="text-muted">No shipping address provided</em>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($order['notes'])): ?>
        <h6 class="mb-3 mt-4">Order Notes</h6>
        <div class="card bg-light">
            <div class="card-body">
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<hr>

<h6 class="mb-3">Order Items</h6>
<div class="table-responsive">
    <table class="table table-sm">
        <thead class="table-light">
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-end">Quantity</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <?php if (!empty($item['image_url'])): ?>
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                             alt="Product Image" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                        <?php endif; ?>
                        <div>
                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                        </div>
                    </div>
                </td>
                <td><?= htmlspecialchars($item['sku'] ?? 'N/A') ?></td>
                <td class="text-end"><?= number_format($item['quantity']) ?></td>
                <td class="text-end">KSh <?= number_format($item['unit_price'], 2) ?></td>
                <td class="text-end"><strong>KSh <?= number_format($item['total_price'], 2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                <td class="text-end"><strong>KSh <?= number_format($order['subtotal'], 2) ?></strong></td>
            </tr>
            <?php if ($order['tax_amount'] > 0): ?>
            <tr>
                <td colspan="4" class="text-end"><strong>Tax:</strong></td>
                <td class="text-end"><strong>KSh <?= number_format($order['tax_amount'], 2) ?></strong></td>
            </tr>
            <?php endif; ?>
            <?php if ($order['shipping_cost'] > 0): ?>
            <tr>
                <td colspan="4" class="text-end"><strong>Shipping:</strong></td>
                <td class="text-end"><strong>KSh <?= number_format($order['shipping_cost'], 2) ?></strong></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td colspan="4" class="text-end"><strong>Total:</strong></td>
                <td class="text-end"><strong>KSh <?= number_format($order['total_amount'], 2) ?></strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php if (!empty($order['status_history'])): ?>
<hr>
<h6 class="mb-3">Status History</h6>
<div class="timeline">
    <?php foreach ($order['status_history'] as $history): ?>
    <div class="timeline-item mb-3">
        <div class="d-flex">
            <div class="flex-shrink-0">
                <?php
                $statusIcons = [
                    'pending' => 'fas fa-clock text-warning',
                    'confirmed' => 'fas fa-check text-info',
                    'processing' => 'fas fa-gear text-primary',
                    'shipped' => 'fas fa-truck text-success',
                    'delivered' => 'fas fa-check-circle text-success',
                    'cancelled' => 'fas fa-times-circle text-danger'
                ];
                $iconClass = $statusIcons[$history['status']] ?? 'fas fa-circle text-secondary';
                ?>
                <i class="<?= $iconClass ?> me-2"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong><?= ucfirst($history['status']) ?></strong>
                        <?php if (!empty($history['comment'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($history['comment']) ?></small>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">
                        <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
