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
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Admin Panel</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="orders.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-cart me-2"></i>Orders
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a href="mpesa_transactions.php" class="list-group-item list-group-item-action active">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-mobile-alt mpesa-icon me-2"></i>
                    M-Pesa Transactions
                </h2>
                <div>
                    <button class="btn btn-info me-2" onclick="bulkCheckStatus()">
                        <i class="fas fa-sync me-2"></i>Bulk Status Check
                    </button>
                    <a href="orders.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                </div>
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
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewTransaction('<?php echo $transaction['id']; ?>')" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#transactionModal"
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($transaction['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="checkRealStatus('<?php echo $transaction['id']; ?>')"
                                            title="Check Real M-Pesa Status">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="manualComplete('<?php echo $transaction['id']; ?>')"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#manualCompleteModal"
                                            title="Manual Complete">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="cancelTransaction('<?php echo $transaction['id']; ?>')"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#cancelModal"
                                            title="Cancel Transaction">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick="viewApiLogs('<?php echo $transaction['id']; ?>')"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#apiLogsModal"
                                            title="View API Logs">
                                        <i class="fas fa-code"></i>
                                    </button>
                                </div>
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

<!-- Manual Complete Modal -->
<div class="modal fade" id="manualCompleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manual Complete Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Admin Override:</strong> Use this only when you have verified the payment outside the system.
                </div>
                <form id="manualCompleteForm">
                    <input type="hidden" id="completeTransactionId">
                    <div class="mb-3">
                        <label for="receiptNumber" class="form-label">M-Pesa Receipt Number *</label>
                        <input type="text" class="form-control" id="receiptNumber" required
                               placeholder="e.g., QH123456789">
                    </div>
                    <div class="mb-3">
                        <label for="completeReason" class="form-label">Reason *</label>
                        <textarea class="form-control" id="completeReason" rows="3" required
                                  placeholder="Explain why this transaction is being manually completed..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitManualComplete()">
                    <i class="fas fa-check me-2"></i>Complete Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Transaction Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This will permanently cancel the transaction.
                </div>
                <form id="cancelForm">
                    <input type="hidden" id="cancelTransactionId">
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Reason for Cancellation *</label>
                        <textarea class="form-control" id="cancelReason" rows="3" required
                                  placeholder="Explain why this transaction is being cancelled..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="submitCancel()">
                    <i class="fas fa-times me-2"></i>Cancel Transaction
                </button>
            </div>
        </div>
    </div>
</div>

<!-- API Logs Modal -->
<div class="modal fade" id="apiLogsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">M-Pesa API Logs & Messages</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="apiLogsDetails">
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
        const response = await fetch('mpesa_admin_api.php', {
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
                        <h6>Customer Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Customer:</strong></td><td>${t.username}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${t.email}</td></tr>
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

async function checkRealStatus(transactionId) {
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    
    // Show loading
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    try {
        const response = await fetch('mpesa_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_real_status',
                transaction_id: transactionId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show result in alert
            const alertClass = result.status === 'completed' ? 'success' : 
                              result.status === 'failed' ? 'danger' : 'info';
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${alertClass} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <strong>M-Pesa API Status:</strong> ${result.status.toUpperCase()}<br>
                <small>${result.result_desc || 'No additional message'}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert alert at top of page
            document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
            
            // Refresh page if status changed
            if (result.status === 'completed' || result.status === 'failed') {
                setTimeout(() => location.reload(), 2000);
            }
            
        } else {
            alert('Error checking status: ' + result.message);
        }
        
    } catch (error) {
        alert('Error checking status: ' + error.message);
    } finally {
        button.innerHTML = originalHtml;
        button.disabled = false;
    }
}

function manualComplete(transactionId) {
    document.getElementById('completeTransactionId').value = transactionId;
    document.getElementById('receiptNumber').value = '';
    document.getElementById('completeReason').value = '';
}

async function submitManualComplete() {
    const transactionId = document.getElementById('completeTransactionId').value;
    const receiptNumber = document.getElementById('receiptNumber').value;
    const reason = document.getElementById('completeReason').value;
    
    if (!receiptNumber || !reason) {
        alert('Please fill in all required fields');
        return;
    }
    
    try {
        const response = await fetch('mpesa_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'manual_complete',
                transaction_id: transactionId,
                receipt_number: receiptNumber,
                reason: reason
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Transaction completed successfully!');
            bootstrap.Modal.getInstance(document.getElementById('manualCompleteModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function cancelTransaction(transactionId) {
    document.getElementById('cancelTransactionId').value = transactionId;
    document.getElementById('cancelReason').value = '';
}

async function submitCancel() {
    const transactionId = document.getElementById('cancelTransactionId').value;
    const reason = document.getElementById('cancelReason').value;
    
    if (!reason) {
        alert('Please provide a reason for cancellation');
        return;
    }
    
    try {
        const response = await fetch('mpesa_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'cancel_transaction',
                transaction_id: transactionId,
                reason: reason
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Transaction cancelled successfully!');
            bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

async function viewApiLogs(transactionId) {
    const logsDiv = document.getElementById('apiLogsDetails');
    
    // Show loading
    logsDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch('mpesa_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_api_logs',
                transaction_id: transactionId
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.logs) {
            const logs = result.logs;
            
            logsDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Transaction Status</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Current Status:</strong></td><td>
                                <span class="badge bg-${getStatusClass(logs.status)}">${logs.status.toUpperCase()}</span>
                            </td></tr>
                            <tr><td><strong>Result Code:</strong></td><td>${logs.result_code || 'N/A'}</td></tr>
                            <tr><td><strong>Result Description:</strong></td><td>${logs.result_desc || 'No message available'}</td></tr>
                            <tr><td><strong>Created:</strong></td><td>${new Date(logs.created_at).toLocaleString()}</td></tr>
                            <tr><td><strong>Last Updated:</strong></td><td>${logs.updated_at ? new Date(logs.updated_at).toLocaleString() : 'Never'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-code me-2"></i>M-Pesa API Response</h6>
                        ${logs.callback_metadata ? `
                            <div class="bg-light p-3 rounded">
                                <pre class="mb-0" style="font-size: 0.8rem; max-height: 300px; overflow-y: auto;">${JSON.stringify(logs.callback_metadata, null, 2)}</pre>
                            </div>
                        ` : `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No callback data received yet. This usually means:
                                <ul class="mt-2 mb-0">
                                    <li>Payment is still pending</li>
                                    <li>Customer hasn't completed payment</li>
                                    <li>Callback URL is not accessible</li>
                                </ul>
                            </div>
                        `}
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <h6><i class="fas fa-lightbulb me-2"></i>Admin Actions</h6>
                        <div class="alert alert-warning">
                            <strong>Real M-Pesa API Messages:</strong> The result description above comes directly from Safaricom's M-Pesa API. 
                            Common messages include:
                            <ul class="mt-2 mb-0">
                                <li><code>Request processed successfully</code> - Payment completed</li>
                                <li><code>The service request is processed successfully</code> - Success</li>
                                <li><code>Request cancelled by user</code> - User cancelled payment</li>
                                <li><code>The transaction was timed out</code> - User didn't enter PIN in time</li>
                                <li><code>Insufficient balance</code> - User doesn't have enough money</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        } else {
            logsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load API logs: ${result.message}
                </div>
            `;
        }
        
    } catch (error) {
        logsDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Error loading API logs: ${error.message}
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

// Bulk status check function
async function bulkCheckStatus() {
    if (!confirm('Check status for all pending transactions via M-Pesa API? This may take a moment.')) {
        return;
    }
    
    const button = event.target;
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
    button.disabled = true;
    
    try {
        const response = await fetch('mpesa_admin_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'bulk_check_status'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`Bulk status check completed! Checked ${result.checked_count} transactions.`);
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
        
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        button.innerHTML = originalHtml;
        button.disabled = false;
    }
}
</script>

        </div> <!-- End Main Content -->
    </div> <!-- End Row -->
</div> <!-- End Container -->

<?php
$layout->footer();
?>
