<?php
session_start();
require_once '../config.php';
require_once '../ClassAutoload.php';

// Admin check - require login and admin role
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Signin.php?redirect=admin/orders.php');
    exit();
}

// Check if user has admin or super_admin role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    // Redirect non-admin users with error message
    header('Location: ../dashboard.php?error=access_denied');
    exit();
}

// Using global $pdo from config.php

$orderManager = new OrderManager($pdo);

// Handle status updates
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    $comment = $_POST['comment'] ?? '';
    $trackingNumber = $_POST['tracking_number'] ?? '';
    
    $additionalData = [];
    if (!empty($trackingNumber)) {
        $additionalData['tracking_number'] = $trackingNumber;
    }
    
    if ($orderManager->updateOrderStatus($orderId, $newStatus, $comment, $additionalData)) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status.";
    }
}

// Get search/filter parameters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query based on filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($statusFilter)) {
    $whereConditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get orders with pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $whereClause
");
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];

// Get orders
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email,
           COUNT(oi.id) as item_count,
           SUM(oi.total_price) as items_total
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $whereClause
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats = $orderManager->getOrderStats();

$layout = new Layout();
$layout->header('Admin - Order Management');
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Admin Panel</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="orders.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a href="mpesa_simple.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-mobile-alt me-2" style="color: #00D4AA;"></i>M-Pesa Transactions
                    </a>
                    <a href="../dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Header and Stats -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-shopping-cart me-2"></i>Order Management</h2>
                <div>
                    <a href="mpesa_transactions.php" class="btn btn-outline-success me-2">
                        <i class="fas fa-mobile-alt me-2" style="color: #00D4AA;"></i>M-Pesa Transactions
                    </a>
                    <a href="users.php" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>User Management
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Orders</h6>
                                    <h3><?= number_format($stats['total_orders']) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Pending Orders</h6>
                                    <h3><?= number_format($stats['pending_orders']) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Processing</h6>
                                    <h3><?= number_format($stats['processing_orders']) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-gear fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Delivered</h6>
                                    <h3><?= number_format($stats['delivered_orders']) ?></h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Order number, customer...">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-2"></i>Orders 
                        <span class="badge bg-secondary"><?= number_format($totalOrders) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No orders found</h5>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($order['username']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?= date('M j, Y', strtotime($order['created_at'])) ?><br>
                                                    <small class="text-muted"><?= date('g:i A', strtotime($order['created_at'])) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= $order['item_count'] ?> items</span>
                                            </td>
                                            <td>
                                                <strong>KSh <?= number_format($order['total_amount'], 2) ?></strong>
                                            </td>
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
                                                <span class="badge bg-<?= $paymentColor ?>">
                                                    <?= ucfirst($order['payment_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#orderModal"
                                                            onclick="loadOrderDetails(<?= $order['id'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#statusModal"
                                                            onclick="openStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalOrders > $perPage): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $totalPages = ceil($totalOrders / $perPage);
                        $queryParams = http_build_query(array_filter($_GET, fn($key) => $key !== 'page', ARRAY_FILTER_USE_KEY));
                        $queryString = $queryParams ? '&' . $queryParams : '';
                        
                        // Previous page
                        if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $queryString ?>">Previous</a>
                            </li>
                        <?php endif;
                        
                        // Page numbers
                        for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;
                        
                        // Next page
                        if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $queryString ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="text-center py-3">
                    <div class="spinner-border" role="status"></div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label">New Status</label>
                        <select class="form-select" name="status" id="statusSelect" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3" id="trackingNumberField" style="display: none;">
                        <label for="trackingNumber" class="form-label">Tracking Number</label>
                        <input type="text" class="form-control" name="tracking_number" id="trackingNumber"
                               placeholder="Enter tracking number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="statusComment" class="form-label">Comment (Optional)</label>
                        <textarea class="form-control" name="comment" id="statusComment" rows="3"
                                  placeholder="Add a note about this status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function loadOrderDetails(orderId) {
    const content = document.getElementById('orderDetailsContent');
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';
    
    fetch(`order_details.php?id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text();
        })
        .then(html => {
            if (html.includes('File not found') || html.includes('404 Not Found')) {
                throw new Error('Order details page not found');
            }
            content.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <h5>Unable to load order details</h5>
                    <p>Error: ${error.message}</p>
                    <small>Check browser console for more details.</small>
                </div>
            `;
        });
}

function openStatusModal(orderId, currentStatus) {
    document.getElementById('statusOrderId').value = orderId;
    document.getElementById('statusSelect').value = currentStatus;
    
    // Show/hide tracking number field based on status
    const statusSelect = document.getElementById('statusSelect');
    const trackingField = document.getElementById('trackingNumberField');
    
    function toggleTrackingField() {
        if (statusSelect.value === 'shipped') {
            trackingField.style.display = 'block';
        } else {
            trackingField.style.display = 'none';
        }
    }
    
    toggleTrackingField();
    statusSelect.addEventListener('change', toggleTrackingField);
}
</script>

<?php $layout->footer(); ?>
