<?php
/**
 * M-Pesa Transactions Management - WORKING VERSION
 */

session_start();
require_once '../config.php';
require_once '../Abstract/Layout.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: ../Signin.php');
    exit();
}

// Initialize variables
$transactions = [];
$stats = ['total_transactions' => 0, 'completed_transactions' => 0, 'pending_transactions' => 0, 'failed_transactions' => 0, 'total_amount' => 0, 'avg_amount' => 0];
$error_message = '';

// Get M-Pesa transactions with proper error handling
try {
    // Use existing PDO connection from config
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Get transactions with order and user info
    $stmt = $pdo->prepare("
        SELECT mt.*, 
               o.order_number, o.total_amount as order_total,
               u.username, u.email
        FROM mpesa_transactions mt
        LEFT JOIN orders o ON mt.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY mt.created_at DESC
        LIMIT 100
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as total_amount,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN amount END), 0) as avg_amount
        FROM mpesa_transactions");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("M-Pesa Simple Error: " . $e->getMessage());
    $error_message = "Unable to load M-Pesa transactions: " . $e->getMessage();
}

$layout = new Layout($conf);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php $layout->header('M-Pesa Transactions - SMARTDUKA Admin'); ?>
    <style>
        .stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid rgba(0, 212, 170, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stats-icon.mpesa-green {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
        }
        
        .stats-icon.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .stats-icon.warning {
            background: linear-gradient(135deg, #ffc107 0%, #ff8f00 100%);
        }
        
        .stats-icon.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .transaction-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .table-header {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
            color: white;
            padding: 1rem;
        }
        
        .export-section {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .btn-export {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-export:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <?php $layout->navbar('admin'); ?>
    
    <div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3"><i class="fas fa-mobile-alt me-2 text-success"></i>M-Pesa Transactions</h1>
            <div class="d-flex gap-2">
                <a href="../admin" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Admin
                </a>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Export Section -->
        <div class="export-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4><i class="fas fa-download me-2"></i>Export M-Pesa Data</h4>
                    <p class="mb-0">Download comprehensive M-Pesa transaction reports for analysis</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="mpesa_pdf_export.php" class="btn-export" target="_blank">
                        <i class="fas fa-file-pdf"></i>Export PDF Report
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon mpesa-green">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4 class="mb-1"><?php echo number_format($stats['total_transactions']); ?></h4>
                    <p class="text-muted mb-0">Total Transactions</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4 class="mb-1"><?php echo number_format($stats['completed_transactions']); ?></h4>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4 class="mb-1"><?php echo number_format($stats['pending_transactions']); ?></h4>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h4 class="mb-1"><?php echo number_format($stats['failed_transactions']); ?></h4>
                    <p class="text-muted mb-0">Failed</p>
                </div>
            </div>
        </div>
        
        <!-- Revenue Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <h5><i class="fas fa-coins me-2 text-success"></i>Total Revenue</h5>
                    <h2 class="text-success">KSh <?php echo number_format($stats['total_amount'], 2); ?></h2>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <h5><i class="fas fa-chart-line me-2 text-info"></i>Average Transaction</h5>
                    <h2 class="text-info">KSh <?php echo number_format($stats['avg_amount'], 2); ?></h2>
                </div>
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="transaction-table">
            <div class="table-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Transactions</h5>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt Number</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No M-Pesa transactions found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($transaction['mpesa_receipt_number'] ?? 'N/A'); ?></code>
                                    </td>
                                    <td>
                                        <?php 
                                        $phone = $transaction['phone_number'] ?? '';
                                        echo $phone ? '+254' . substr($phone, -9) : 'N/A';
                                        ?>
                                    </td>
                                    <td class="fw-bold">
                                        KSh <?php echo number_format($transaction['amount'], 2); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($transaction['status']) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($transaction['order_number']): ?>
                                            <a href="order_details.php?id=<?php echo $transaction['order_id']; ?>" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars($transaction['order_number']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['username'] ?? 'Unknown'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php $layout->footer(); ?>
</body>
</html>
