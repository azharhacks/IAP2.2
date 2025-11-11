<?php
/**
 * Admin M-Pesa Transactions Page
 * View and manage M-Pesa transactions
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../ClassAutoload.php';

// Initialize layout
$layout = new Layout();

// Check if user is admin (you may need to adjust this based on your admin system)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: ../Signin.php');
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

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query
$where = [];
$params = [];

if (!empty($status)) {
    $where[] = "mt.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(mt.phone_number LIKE ? OR mt.mpesa_receipt_number LIKE ? OR o.order_number LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get transactions
$stmt = $pdo->prepare("
    SELECT mt.*, o.order_number, o.total_amount as order_total,
           CONCAT(u.username) as customer_name
    FROM mpesa_transactions mt
    LEFT JOIN orders o ON mt.order_id = o.id
    LEFT JOIN users u ON o.user_id = u.id
    {$whereClause}
    ORDER BY mt.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get total count
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM mpesa_transactions mt
    LEFT JOIN orders o ON mt.order_id = o.id
    {$whereClause}
");
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $limit);

// Get statistics
$stmt = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count,
        SUM(amount) as total_amount,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mpesa_transactions)), 2) as percentage
    FROM mpesa_transactions 
    GROUP BY status
");
$stats = $stmt->fetchAll();

$customCSS = '
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    .transaction-card {
        border-radius: 10px;
        margin-bottom: 1rem;
        border-left: 4px solid #007bff;
    }
    .transaction-card.completed {
        border-left-color: #28a745;
    }
    .transaction-card.failed {
        border-left-color: #dc3545;
    }
    .transaction-card.cancelled {
        border-left-color: #6c757d;
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    .mpesa-icon {
        color: #00D4AA;
    }
';

$layout->header('M-Pesa Transactions', $customCSS);
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-mobile-alt mpesa-icon me-2"></i>
            M-Pesa Transactions
        </h2>
        <a href="orders.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Orders
        </a>
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <?php foreach ($stats as $stat): ?>
        <div class="col-md-3">
            <div class="stats-card text-center">
                <h3 class="mb-1"><?php echo $stat['count']; ?></h3>
                <p class="mb-1"><?php echo ucfirst($stat['status']); ?></p>
                <small>KSh <?php echo number_format($stat['total_amount'] ?? 0); ?></small>
                <div class="mt-2">
                    <small><?php echo $stat['percentage']; ?>% of total</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Phone number, receipt number, or order number..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Filter
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Transactions (<?php echo number_format($totalCount); ?> total)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($transactions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No transactions found</h5>
                <p class="text-muted">Try adjusting your filters or search criteria.</p>
            </div>
            <?php else: ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Receipt</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td>
                                <small>
                                    <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?php echo $transaction['order_id']; ?>">
                                    <?php echo htmlspecialchars($transaction['order_number'] ?? 'N/A'); ?>
                                </a>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($transaction['customer_name'] ?? 'Unknown'); ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($transaction['phone_number']); ?></code>
                            </td>
                            <td>
                                <strong>KSh <?php echo number_format($transaction['amount']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'cancelled' => 'secondary'
                                ][$transaction['status']] ?? 'primary';
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($transaction['mpesa_receipt_number']): ?>
                                <code class="text-success">
                                    <?php echo htmlspecialchars($transaction['mpesa_receipt_number']); ?>
                                </code>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="viewTransaction('<?php echo $transaction['id']; ?>')" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#transactionModal">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            Previous
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function viewTransaction(transactionId) {
    const detailsDiv = document.getElementById('transactionDetails');
    
    // Show loading
    detailsDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch('../mpesa_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_transaction_details',
                transaction_id: transactionId
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.transaction) {
            const t = result.transaction;
            detailsDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Transaction Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID:</strong></td><td>${t.id}</td></tr>
                            <tr><td><strong>Status:</strong></td><td>
                                <span class="badge bg-${getStatusClass(t.status)}">${t.status.toUpperCase()}</span>
                            </td></tr>
                            <tr><td><strong>Amount:</strong></td><td>KSh ${parseFloat(t.amount).toLocaleString()}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>${t.phone_number}</td></tr>
                            <tr><td><strong>Created:</strong></td><td>${new Date(t.created_at).toLocaleString()}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>M-Pesa Details</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Checkout ID:</strong></td><td><code>${t.checkout_request_id}</code></td></tr>
                            <tr><td><strong>Merchant ID:</strong></td><td><code>${t.merchant_request_id}</code></td></tr>
                            <tr><td><strong>Receipt:</strong></td><td>${t.mpesa_receipt_number || 'N/A'}</td></tr>
                            <tr><td><strong>Transaction Date:</strong></td><td>${t.transaction_date ? new Date(t.transaction_date).toLocaleString() : 'N/A'}</td></tr>
                            <tr><td><strong>Result:</strong></td><td>${t.result_desc || 'Pending'}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Order Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Order Number:</strong></td><td>${t.order_number}</td></tr>
                            <tr><td><strong>Order Total:</strong></td><td>KSh ${parseFloat(t.order_total).toLocaleString()}</td></tr>
                        </table>
                    </div>
                </div>
            `;
        } else {
            detailsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load transaction details: ${result.message}
                </div>
            `;
        }
        
    } catch (error) {
        detailsDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading transaction details: ${error.message}
            </div>
        `;
    }
}

function getStatusClass(status) {
    const classes = {
        'pending': 'warning',
        'completed': 'success',
        'failed': 'danger',
        'cancelled': 'secondary'
    };
    return classes[status] || 'primary';
}
</script>

<?php
$layout->footer();
?>
