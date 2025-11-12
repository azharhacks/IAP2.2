<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

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

// Initialize product manager for featured products
$productManager = new ProductManager($pdo);
$featuredProducts = $productManager->getFeaturedProducts(6);

// Create Layout instance
$ObjLayout = new Layout();

$ObjLayout->header('Home', $conf);
$ObjLayout->navbar('');
$ObjLayout->banner($conf, 
    'Welcome to ' . $conf['site_name'], 
    'Discover amazing products at unbeatable prices. Quality guaranteed, fast delivery across Kenya.',
    'Shop Now',
    'products.php'
);

// Add featured products section
?>
<div class="row g-4 mt-5">
    <div class="col-12 text-center mb-4">
        <h2 class="text-gradient mb-3">Featured Products</h2>
        <p class="lead text-muted">Handpicked items just for you</p>
    </div>
    <?php foreach ($featuredProducts as $product): ?>
    <div class="col-md-4">
        <div class="card card-custom h-100">
            <div class="position-relative">
                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x200'); ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="height: 200px; object-fit: cover;">
                <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                <?php $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>
                <span class="position-absolute top-0 start-0 badge bg-danger m-2">
                    <?php echo $discount; ?>% OFF
                </span>
                <?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                <p class="card-text text-muted flex-grow-1">
                    <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="h5 text-primary">KSh <?php echo number_format($product['price']); ?></span>
                        <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                        <br><small class="text-muted text-decoration-line-through">
                            KSh <?php echo number_format($product['compare_price']); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                        View Details
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

    <div class="col-12 text-center mt-5">
        <a href="products.php" class="btn btn-primary btn-lg me-3">
            <i class="fas fa-th-grid me-2"></i>View All Products
        </a>
        <a href="products.php?featured=1" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-star me-2"></i>More Featured
        </a>
    </div>
</div>

<!-- Additional sections for better homepage -->
<div class="row g-4 mt-5">
    <div class="col-12 text-center mb-4">
        <h2 class="text-gradient mb-3">Why Choose <?php echo htmlspecialchars($conf['site_name']); ?>?</h2>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-custom h-100 text-center p-4">
            <div class="mb-3">
                <i class="fas fa-shipping-fast text-primary fs-1"></i>
            </div>
            <h5 class="card-title">Fast Delivery</h5>
            <p class="card-text text-muted">Free delivery within Nairobi and affordable rates countrywide.</p>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-custom h-100 text-center p-4">
            <div class="mb-3">
                <i class="fas fa-shield-alt text-primary fs-1"></i>
            </div>
            <h5 class="card-title">Secure Shopping</h5>
            <p class="card-text text-muted">Your data is protected with 2FA and encrypted transactions.</p>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-custom h-100 text-center p-4">
            <div class="mb-3">
                <i class="fas fa-award text-primary fs-1"></i>
            </div>
            <h5 class="card-title">Quality Products</h5>
            <p class="card-text text-muted">Carefully curated products from trusted brands and suppliers.</p>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card card-custom h-100 text-center p-4">
            <div class="mb-3">
                <i class="fas fa-headset text-primary fs-1"></i>
            </div>
            <h5 class="card-title">24/7 Support</h5>
            <p class="card-text text-muted">Our customer service team is always ready to help you.</p>
        </div>
    </div>
</div>

<?php
$ObjLayout->footer();
?>
