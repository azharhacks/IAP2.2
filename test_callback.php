<?php
/**
 * M-Pesa Callback Tester
 * Simulates M-Pesa callback for testing payments
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Handle both GET (form display) and POST (callback simulation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_request_id'])) {
    
    try {
        // Initialize database connection
        $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        $checkoutRequestId = $_POST['checkout_request_id'];
        $resultCode = (int)$_POST['result_code'];
        $mpesaReceiptNumber = $_POST['mpesa_receipt_number'] ?? null;
        
        // Determine status based on result code
        $status = ($resultCode === 0) ? 'completed' : 'failed';
        $resultDesc = ($resultCode === 0) ? 'The service request is processed successfully.' : 'Payment failed or was cancelled by user.';
        
        // Update transaction status
        $stmt = $pdo->prepare("
            UPDATE mpesa_transactions 
            SET status = ?, 
                mpesa_receipt_number = ?, 
                transaction_date = NOW()
            WHERE checkout_request_id = ?
        ");
        
        $updated = $stmt->execute([$status, $mpesaReceiptNumber, $checkoutRequestId]);
        
        if ($updated && $stmt->rowCount() > 0) {
            $message = "✅ Transaction updated successfully!";
            $alertClass = "alert-success";
            
            // Get updated transaction details
            $stmt = $pdo->prepare("SELECT * FROM mpesa_transactions WHERE checkout_request_id = ?");
            $stmt->execute([$checkoutRequestId]);
            $transaction = $stmt->fetch();
            
        } else {
            $message = "❌ No transaction found with that Checkout Request ID.";
            $alertClass = "alert-danger";
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        $alertClass = "alert-danger";
    }
}

// Get pending transactions for the dropdown
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->query("SELECT * FROM mpesa_transactions WHERE status = 'pending' ORDER BY created_at DESC");
    $pendingTransactions = $stmt->fetchAll();
    
} catch (Exception $e) {
    $pendingTransactions = [];
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Callback Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-primary { background: linear-gradient(135deg, #00D4AA 0%, #00A693 100%); border: none; }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; }
        .btn-danger { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); border: none; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🧪 M-Pesa Callback Tester</h4>
                        <small>Simulate M-Pesa callbacks for testing purposes</small>
                    </div>
                    <div class="card-body">
                        
                        <?php if (isset($message)): ?>
                            <div class="alert <?= $alertClass ?> alert-dismissible fade show">
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            
                            <?php if (isset($transaction)): ?>
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <strong>Updated Transaction Details</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Order ID:</strong> <?= $transaction['order_id'] ?></p>
                                                <p><strong>Phone:</strong> <?= $transaction['phone_number'] ?></p>
                                                <p><strong>Amount:</strong> KSh <?= number_format($transaction['amount'], 2) ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Status:</strong> 
                                                    <span class="badge bg-<?= $transaction['status'] === 'completed' ? 'success' : 'danger' ?>">
                                                        <?= ucfirst($transaction['status']) ?>
                                                    </span>
                                                </p>
                                                <p><strong>Receipt:</strong> <?= $transaction['mpesa_receipt_number'] ?? 'N/A' ?></p>
                                                <p><strong>Updated:</strong> <?= date('M j, Y H:i', strtotime($transaction['updated_at'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label class="form-label">Select Pending Transaction:</label>
                                <select name="checkout_request_id" class="form-select" required>
                                    <option value="">Choose a pending transaction...</option>
                                    <?php foreach ($pendingTransactions as $txn): ?>
                                        <option value="<?= htmlspecialchars($txn['checkout_request_id']) ?>">
                                            Order #<?= $txn['order_id'] ?> - <?= $txn['phone_number'] ?> - KSh <?= number_format($txn['amount'], 2) ?>
                                            (<?= date('M j, H:i', strtotime($txn['created_at'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Result Code:</label>
                                        <select name="result_code" class="form-select" required>
                                            <option value="0">0 - Success</option>
                                            <option value="1">1 - Failed</option>
                                            <option value="1032">1032 - Canceled by user</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">M-Pesa Receipt Number:</label>
                                        <input type="text" name="mpesa_receipt_number" class="form-control" 
                                               placeholder="QH123456789" value="QH<?= rand(100000000, 999999999) ?>">
                                        <small class="text-muted">Only for successful payments</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-success me-md-2">
                                    ✅ Mark as Completed
                                </button>
                                <button type="submit" onclick="document.querySelector('[name=result_code]').value='1'" class="btn btn-danger">
                                    ❌ Mark as Failed
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>📊 Current Pending Transactions</h6>
                                <?php if (empty($pendingTransactions)): ?>
                                    <p class="text-muted">No pending transactions</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($pendingTransactions as $txn): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">Order #<?= $txn['order_id'] ?></h6>
                                                    <small><?= date('H:i', strtotime($txn['created_at'])) ?></small>
                                                </div>
                                                <p class="mb-1"><?= $txn['phone_number'] ?> - KSh <?= number_format($txn['amount'], 2) ?></p>
                                                <small class="text-muted"><?= $txn['checkout_request_id'] ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h6>ℹ️ How to Use</h6>
                                <ol>
                                    <li>Make a payment via M-Pesa STK Push</li>
                                    <li>The transaction will appear as "pending"</li>
                                    <li>Select it from the dropdown above</li>
                                    <li>Choose result code (0 = success)</li>
                                    <li>Click "Mark as Completed"</li>
                                    <li>The payment page will detect the change</li>
                                </ol>
                                
                                <div class="alert alert-info mt-3">
                                    <small>
                                        <strong>Note:</strong> This is a testing tool to simulate M-Pesa callbacks 
                                        since the actual callbacks require a public URL.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="card-footer text-center">
                        <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
                        <a href="mpesa_payment_page.php?order_id=<?= $pendingTransactions[0]['order_id'] ?? '1' ?>" class="btn btn-outline-primary">Test Payment Page</a>
                    </div>
                </</div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
