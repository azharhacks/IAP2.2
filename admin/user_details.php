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

$userId = (int)($_GET['id'] ?? 0);
if (!$userId) {
    http_response_code(400);
    exit('Invalid user ID');
}

// Get user details with profile information
$stmt = $pdo->prepare("
    SELECT u.*, up.first_name, up.last_name, up.phone, up.date_of_birth, up.gender
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id 
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    exit('User not found');
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user orders
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        COUNT(DISTINCT a.id) as address_count,
        COUNT(DISTINCT c.id) as cart_items
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN addresses a ON u.id = a.user_id AND a.is_active = 1
    LEFT JOIN cart c ON u.id = c.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$userId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="mb-3">Account Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>User ID:</strong></td>
                <td><?= htmlspecialchars($user['id']) ?></td>
            </tr>
            <tr>
                <td><strong>Username:</strong></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
            </tr>
            <tr>
                <td><strong>Full Name:</strong></td>
                <td>
                    <?php if ($user['first_name'] || $user['last_name']): ?>
                        <?= htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) ?>
                    <?php else: ?>
                        <em class="text-muted">Not provided</em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Phone:</strong></td>
                <td>
                    <?= $user['phone'] ? htmlspecialchars($user['phone']) : '<em class="text-muted">Not provided</em>' ?>
                </td>
            </tr>
            <tr>
                <td><strong>Role:</strong></td>
                <td>
                    <?php
                    $roleColors = [
                        'user' => 'secondary',
                        'admin' => 'danger',
                        'super_admin' => 'dark'
                    ];
                    $roleColor = $roleColors[$user['role']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $roleColor ?>">
                        <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>Registration:</strong></td>
                <td><?= date('M j, Y g:i A', strtotime($user['created_at'])) ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="mb-3">Account Status</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Email Verified:</strong></td>
                <td>
                    <?php if ($user['email_verified']): ?>
                        <span class="badge bg-success">Verified</span>
                    <?php else: ?>
                        <span class="badge bg-warning">Pending</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>2FA Status:</strong></td>
                <td>
                    <?php if ($user['totp_secret']): ?>
                        <span class="badge bg-info">Enabled</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Disabled</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Date of Birth:</strong></td>
                <td>
                    <?= $user['date_of_birth'] ? date('M j, Y', strtotime($user['date_of_birth'])) : '<em class="text-muted">Not provided</em>' ?>
                </td>
            </tr>
            <tr>
                <td><strong>Gender:</strong></td>
                <td>
                    <?= $user['gender'] ? ucfirst($user['gender']) : '<em class="text-muted">Not provided</em>' ?>
                </td>
            </tr>
        </table>
        
        <h6 class="mb-3 mt-4">Statistics</h6>
        <div class="row text-center">
            <div class="col-6">
                <div class="card bg-light">
                    <div class="card-body p-2">
                        <h5 class="mb-0"><?= number_format($stats['total_orders']) ?></h5>
                        <small>Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card bg-light">
                    <div class="card-body p-2">
                        <h5 class="mb-0">KSh <?= number_format($stats['total_spent'], 2) ?></h5>
                        <small>Total Spent</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($addresses)): ?>
<hr>
<h6 class="mb-3">Addresses (<?= count($addresses) ?>)</h6>
<div class="row">
    <?php foreach ($addresses as $address): ?>
    <div class="col-md-6 mb-3">
        <div class="card <?= $address['is_default'] ? 'border-primary' : '' ?>">
            <div class="card-body p-3">
                <?php if ($address['is_default']): ?>
                    <span class="badge bg-primary mb-2">Default</span>
                <?php endif; ?>
                <p class="mb-1">
                    <strong><?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?></strong>
                </p>
                <p class="mb-1"><?= htmlspecialchars($address['address_line_1']) ?></p>
                <?php if ($address['address_line_2']): ?>
                    <p class="mb-1"><?= htmlspecialchars($address['address_line_2']) ?></p>
                <?php endif; ?>
                <p class="mb-1">
                    <?= htmlspecialchars($address['city']) ?>, 
                    <?= htmlspecialchars($address['county']) ?> 
                    <?= htmlspecialchars($address['postal_code']) ?>
                </p>
                <p class="mb-1"><?= htmlspecialchars($address['country']) ?></p>
                <p class="mb-0">
                    <small class="text-muted">
                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($address['phone']) ?>
                    </small>
                </p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($orders)): ?>
<hr>
<h6 class="mb-3">Recent Orders (<?= count($orders) ?>)</h6>
<div class="table-responsive">
    <table class="table table-sm">
        <thead class="table-light">
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= htmlspecialchars($order['order_number']) ?></td>
                <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                <td><?= $order['item_count'] ?> items</td>
                <td>KSh <?= number_format($order['total_amount'], 2) ?></td>
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
                    <span class="badge bg-<?= $statusColor ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
