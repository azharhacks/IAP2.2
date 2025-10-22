<?php
// Start session and include necessary files
session_start();
require_once 'config.php';
require_once 'ClassAutoload.php';

// Check if user is logged in and 2FA is verified
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $conf['site_url'] . '/Signin.php');
    exit();
}

// Check if 2FA is verified
if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: ' . $conf['site_url'] . '/verify-2fa.php');
    exit();
}

// Get user data
// Connect to database - Linux/Fedora doesn't use XAMPP socket
$dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
$pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, destroy session and redirect
if (!$user) {
    session_destroy();
    header('Location: ' . $conf['site_url'] . '/Signin.php?error=user_not_found');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $conf['site_name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .icon-primary { background: linear-gradient(45deg, #007bff, #0056b3); }
        .icon-success { background: linear-gradient(45deg, #28a745, #20c997); }
        .icon-warning { background: linear-gradient(45deg, #ffc107, #fd7e14); }
        .icon-info { background: linear-gradient(45deg, #17a2b8, #138496); }
        .product-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-store me-2"></i><?php echo $conf['site_name']; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products"><i class="fas fa-box me-1"></i>Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#orders"><i class="fas fa-shopping-cart me-1"></i>Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#customers"><i class="fas fa-users me-1"></i>Customers</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 mb-0">Welcome back!</h1>
                    <p class="lead mb-0">Here's what's happening with your store today</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="alert alert-success mb-0" style="background: rgba(255,255,255,0.2); border: none; color: white;">
                        <i class="fas fa-shield-alt me-2"></i>Secured with 2FA
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stats-icon icon-primary mx-auto mb-3">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="fw-bold">247</h3>
                        <p class="text-muted mb-0">Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stats-icon icon-success mx-auto mb-3">
                            <i class="fas fa-coins"></i>
                        </div>
                        <h3 class="fw-bold">KSh 1,847,000</h3>
                        <p class="text-muted mb-0">Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stats-icon icon-warning mx-auto mb-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <h3 class="fw-bold">89</h3>
                        <p class="text-muted mb-0">Products</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card stats-card h-100">
                    <div class="card-body text-center">
                        <div class="stats-icon icon-info mx-auto mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="fw-bold">156</h3>
                        <p class="text-muted mb-0">Customers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 id="products"><i class="fas fa-box me-2"></i>Featured Products</h2>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </button>
                </div>
                
                <div class="row">
                    <!-- Product 1 -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/400x200/667eea/ffffff?text=Safaricom+Phone" class="card-img-top" alt="Safaricom Phone">
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">In Stock</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Safaricom Smartphone</h5>
                                <p class="card-text text-muted">Latest 4G smartphone with M-Pesa integration</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0">KSh 45,000</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product 2 -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/400x200/764ba2/ffffff?text=Tecno+Laptop" class="card-img-top" alt="Tecno Laptop">
                                <span class="badge bg-warning position-absolute top-0 end-0 m-2">Low Stock</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Tecno Laptop Pro</h5>
                                <p class="card-text text-muted">Affordable laptop perfect for students and professionals</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0">KSh 85,000</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product 3 -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/400x200/28a745/ffffff?text=Oraimo+Earbuds" class="card-img-top" alt="Oraimo Earbuds">
                                <span class="badge bg-success position-absolute top-0 end-0 m-2">In Stock</span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Oraimo FreePods</h5>
                                <p class="card-text text-muted">Wireless earbuds with excellent sound quality</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0">KSh 8,500</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 id="orders"><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h2>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Product</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#ORD-001</td>
                                        <td>James Mwangi</td>
                                        <td>Safaricom Smartphone</td>
                                        <td>KSh 45,000</td>
                                        <td><span class="badge bg-success">Shipped</span></td>
                                        <td>Oct 15, 2025</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-002</td>
                                        <td>Grace Njeri</td>
                                        <td>Tecno Laptop Pro</td>
                                        <td>KSh 85,000</td>
                                        <td><span class="badge bg-warning">Processing</span></td>
                                        <td>Oct 14, 2025</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#ORD-003</td>
                                        <td>Peter Kimani</td>
                                        <td>Oraimo FreePods</td>
                                        <td>KSh 8,500</td>
                                        <td><span class="badge bg-info">Pending</span></td>
                                        <td>Oct 13, 2025</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
