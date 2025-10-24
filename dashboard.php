<?php
// Start session and include necessary files
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Check if user is logged in and 2FA is verified
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $conf['site_url'] . '/Signin.php');
    exit();
}

// Check if 2FA is verified
if (!isset($_SESSION['2fa_verified']) || $_SESSION['2fa_verified'] !== true) {
    header('Location: ' . $conf['site_url'] . '/2fa_verify.php');
    exit();
}

// Get user data
try {
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $conn = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: ' . $conf['site_url'] . '/Signin.php?error=user_not_found');
        exit();
    }

    // Get user's orders count
    $orderStmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE customer_id = ?");
    $orderStmt->execute([$_SESSION['user_id']]);
    $orderCount = $orderStmt->fetch()['order_count'] ?? 0;

    // Get featured products
    $productStmt = $conn->prepare("SELECT * FROM products WHERE featured = 1 AND status = 'active' ORDER BY created_at DESC LIMIT 6");
    $productStmt->execute();
    $featuredProducts = $productStmt->fetchAll();

    // Get user's recent orders
    $recentOrdersStmt = $conn->prepare("
        SELECT o.*, p.name as product_name, p.image_url 
        FROM orders o 
        LEFT JOIN products p ON o.product_id = p.id 
        WHERE o.customer_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recentOrdersStmt->execute([$_SESSION['user_id']]);
    $recentOrders = $recentOrdersStmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Set default values if DB fails
    $user = ['username' => 'User', 'email' => 'user@example.com'];
    $orderCount = 0;
    $featuredProducts = [];
    $recentOrders = [];
}

// Use the username directly from the database
$userName = $user['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($conf['site_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }
        .welcome-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(10px);
        }
        .product-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        .price-tag {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }
        .order-card {
            border-left: 4px solid #667eea;
            transition: all 0.2s ease;
        }
        .order-card:hover {
            border-left-color: #764ba2;
            transform: translateX(5px);
        }
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 2rem;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        .stats-mini {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
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
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i><?php echo htmlspecialchars($conf['site_name']); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">
                            <i class="fas fa-shopping-cart me-1"></i>Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#orders">
                            <i class="fas fa-list me-1"></i>My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#wishlist">
                            <i class="fas fa-heart me-1"></i>Wishlist
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#customers"><i class="fas fa-users me-1"></i>Customers</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#" title="Shopping Cart">
                            <i class="fas fa-shopping-cart position-relative">
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    2<span class="visually-hidden">items in cart</span>
                                </span>
                            </i>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($userName); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#profile"><i class="fas fa-user me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="#orders"><i class="fas fa-list me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="#wishlist"><i class="fas fa-heart me-2"></i>My Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#settings"><i class="fas fa-cog me-2"></i>Account Settings</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="welcome-card">
                        <h1 class="display-5 fw-bold mb-3">
                            <i class="fas fa-wave-square me-2"></i>
                            Hello, <?php echo htmlspecialchars($userName); ?>!
                        </h1>
                        <p class="lead mb-4">
                            Welcome to your personal shopping dashboard. Discover amazing products, track your orders, and enjoy a seamless shopping experience.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="#products" class="btn btn-light btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                            <a href="#orders" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-list me-2"></i>View Orders (<?php echo $orderCount; ?>)
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="stats-mini mb-3">
                        <h3 class="text-primary mb-1"><?php echo $orderCount; ?></h3>
                        <p class="mb-0 text-muted">Total Orders</p>
                    </div>
                    <div class="stats-mini">
                        <h3 class="text-success mb-1">
                            <i class="fas fa-shield-check"></i>
                        </h3>
                        <p class="mb-0 text-muted">Account Secured</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <!-- Featured Products Section -->
        <section id="products" class="mb-5">
            <h2 class="section-title text-center mb-4">
                <i class="fas fa-star me-2"></i>Featured Products
            </h2>
            <p class="text-center text-muted mb-5">Discover our most popular items handpicked just for you</p>
            
            <div class="row">
                <?php if (empty($featuredProducts)): ?>
                    <!-- Sample Products for Demo -->
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="https://via.placeholder.com/300x200/667eea/ffffff?text=Safaricom+Phone" class="card-img-top product-image" alt="Safaricom Smartphone">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Safaricom Neon Storm</h5>
                                <p class="card-text text-muted flex-grow-1">Latest 4G smartphone with M-Pesa integration and premium features</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh 45,000</span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="https://via.placeholder.com/300x200/764ba2/ffffff?text=Tecno+Laptop" class="card-img-top product-image" alt="Tecno Laptop">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Tecno MegaBook T1</h5>
                                <p class="card-text text-muted flex-grow-1">Perfect for students and professionals with powerful performance</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh 85,000</span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="https://via.placeholder.com/300x200/28a745/ffffff?text=Oraimo+Earbuds" class="card-img-top product-image" alt="Oraimo Earbuds">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Oraimo FreePods 4</h5>
                                <p class="card-text text-muted flex-grow-1">Wireless earbuds with crystal clear sound and long battery life</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh 8,500</span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="https://via.placeholder.com/300x200/ffc107/333333?text=Samsung+TV" class="card-img-top product-image" alt="Samsung Smart TV">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Samsung 55" Smart TV</h5>
                                <p class="card-text text-muted flex-grow-1">4K UHD Smart TV with Netflix, YouTube and more streaming apps</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh 125,000</span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="https://via.placeholder.com/300x200/dc3545/ffffff?text=Infinix+Note" class="card-img-top product-image" alt="Infinix Note">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Infinix Note 30 Pro</h5>
                                <p class="card-text text-muted flex-grow-1">Gaming smartphone with fast charging and amazing camera</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh 32,000</span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="https://via.placeholder.com/300x200/17a2b8/ffffff?text=HP+Printer" class="card-img-top product-image" alt="HP Printer">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">HP DeskJet 2720e</h5>
                                <p class="card-text text-muted flex-grow-1">All-in-one wireless printer perfect for home and office use</p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh 15,500</span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x200'); ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="price-tag">KSh <?php echo number_format($product['price']); ?></span>
                                    <button class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="#all-products" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-th me-2"></i>View All Products
                </a>
            </div>
        </section>

        <!-- My Orders Section -->
        <section id="orders" class="mb-5">
            <h2 class="section-title text-center mb-4">
                <i class="fas fa-shopping-bag me-2"></i>My Recent Orders
            </h2>
            <p class="text-center text-muted mb-5">Track your recent purchases and order history</p>
            
            <?php if (empty($recentOrders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-bag text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3 text-muted">No orders yet</h4>
                <p class="text-muted">Start shopping to see your orders here</p>
                <a href="#products" class="btn btn-primary">Start Shopping</a>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($recentOrders as $order): ?>
                <div class="col-md-6 mb-3">
                    <div class="card order-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">Order #<?php echo htmlspecialchars($order['id']); ?></h6>
                                    <p class="card-text"><?php echo htmlspecialchars($order['product_name']); ?></p>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php 
                                        echo ($order['status'] == 'completed') ? 'success' : 
                                             (($order['status'] == 'processing') ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <div class="mt-2">
                                        <strong>KSh <?php echo number_format($order['total_amount']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="#all-orders" class="btn btn-outline-secondary">
                    <i class="fas fa-list me-2"></i>View All Orders
                </a>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo htmlspecialchars($conf['site_name']); ?></h5>
                    <p class="text-muted">Your trusted online shopping destination in Kenya</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">&copy; 2025 <?php echo htmlspecialchars($conf['site_name']); ?>. All rights reserved.</p>
                    <div class="mt-2">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        <small class="text-muted">Secured with 2FA Authentication</small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
