<!DOCTYPE html>
<html>
<head>
    <title>Admin Navigation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Admin Navigation Test</h2>
        <p>This is how your admin sidebar should look:</p>
        
        <div class="row">
            <div class="col-md-3">
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
                        <a href="mpesa_transactions.php" class="list-group-item list-group-item-action" style="background-color: #f0f8ff;">
                            <i class="fas fa-mobile-alt me-2" style="color: #00D4AA;"></i>M-Pesa Transactions
                        </a>
                        <a href="../dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="alert alert-info">
                    <h5>Try these steps:</h5>
                    <ol>
                        <li><strong>Hard Refresh:</strong> Press Ctrl+F5 (or Cmd+Shift+R on Mac)</li>
                        <li><strong>Clear Browser Cache:</strong> Press F12 → Right-click refresh button → "Empty Cache and Hard Reload"</li>
                        <li><strong>Direct Link:</strong> <a href="mpesa_transactions.php" class="btn btn-success btn-sm">Go to M-Pesa Transactions</a></li>
                    </ol>
                </div>
                
                <div class="alert alert-warning">
                    <strong>Current Session Check:</strong>
                    <?php
                    session_start();
                    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
                        echo '<span class="text-success">✅ Admin role detected - Links should be visible</span>';
                    } else {
                        echo '<span class="text-danger">❌ Admin role not detected - You may need to log out and log back in</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
