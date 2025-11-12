<?php
/**
 * Simple M-Pesa Transactions Page - Minimal Version
 */

session_start();
require_once __DIR__ . '/../config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: ../Signin.php');
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get basic transaction data
$stmt = $pdo->query("SELECT COUNT(*) as total FROM mpesa_transactions");
$totalTransactions = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Transactions - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
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
                        <a href="mpesa_simple.php" class="list-group-item list-group-item-action active">
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
                        <i class="fas fa-mobile-alt me-2" style="color: #00D4AA;"></i>
                        M-Pesa Transactions
                    </h2>
                </div>

                <div class="alert alert-success">
                    <h4>✅ M-Pesa Admin Panel Working!</h4>
                    <p>Total M-Pesa Transactions: <strong><?php echo $totalTransactions; ?></strong></p>
                    <hr>
                    <p>This is a simplified version of the M-Pesa admin panel.</p>
                    <a href="mpesa_transactions.php" class="btn btn-primary">Try Full Version</a>
                    <a href="users.php" class="btn btn-outline-secondary">Back to Users</a>
                </div>

                <?php
                // Get recent transactions
                $stmt = $pdo->query("
                    SELECT mt.*, o.order_number 
                    FROM mpesa_transactions mt 
                    LEFT JOIN orders o ON mt.order_id = o.id 
                    ORDER BY mt.created_at DESC 
                    LIMIT 10
                ");
                $transactions = $stmt->fetchAll();
                ?>

                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list me-2"></i>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($transactions)): ?>
                        <p class="text-muted">No transactions found.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Order</th>
                                        <th>Phone</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $transaction['id']; ?></td>
                                        <td><?php echo htmlspecialchars($transaction['order_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['phone_number']); ?></td>
                                        <td>KSh <?php echo number_format($transaction['amount']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
