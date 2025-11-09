<?php
/**
 * Product Details Page
 * Shows detailed information about a single product
 * Features: Product images, specifications, reviews, add to cart, related products
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    header('Location: products.php');
    exit();
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

// Initialize managers
$productManager = new ProductManager($pdo);
$cartManager = new CartManager($pdo, $_SESSION['user_id'] ?? null);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (!isset($_SESSION['user_id'])) {
        // For guest users, we'll redirect to login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: Signin.php?message=Please login to add items to cart');
        exit();
    }
    
    $result = $cartManager->addToCart($productId, $quantity);
    
    if ($result) {
        $_SESSION['success_message'] = 'Product added to cart successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to add product to cart. Please check stock availability.';
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Get product details
$product = $productManager->getProductDetails($productId);

if (!$product) {
    header('Location: products.php?error=Product not found');
    exit();
}

// Get related products
$relatedProducts = $productManager->getRelatedProducts($productId, 4);

// Calculate discount percentage
$discountPercentage = 0;
if ($product['compare_price'] && $product['compare_price'] > $product['price']) {
    $discountPercentage = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($conf['site_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-image-main {
            max-height: 500px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product-thumbnail {
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .product-thumbnail:hover,
        .product-thumbnail.active {
            border: 2px solid #007bff;
            transform: scale(1.05);
        }
        .price-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 1.5rem;
        }
        .rating-stars {
            color: #ffc107;
        }
        .stock-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        .low-stock {
            background: #fff3cd;
            color: #856404;
        }
        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        .attribute-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
        }
        .review-card {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation (simplified) -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shopping-bag me-2"></i><?php echo htmlspecialchars($conf['site_name']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a class="nav-link" href="products.php">
                    <i class="fas fa-th-grid me-1"></i>Products
                </a>
                <a class="nav-link" href="cart.php">
                    <i class="fas fa-shopping-cart me-1"></i>Cart
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item"><?php echo htmlspecialchars($product['category_name']); ?></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="mb-3">
                    <?php 
                    $mainImage = !empty($product['images']) ? $product['images'][0] : null;
                    $imageUrl = $mainImage['image_url'] ?? 'https://via.placeholder.com/500x500/f8f9fa/6c757d?text=No+Image';
                    ?>
                    <img id="main-product-image" 
                         src="<?php echo htmlspecialchars($imageUrl); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="img-fluid product-image-main w-100">
                </div>
                
                <!-- Thumbnail Images -->
                <?php if (count($product['images']) > 1): ?>
                <div class="row g-2">
                    <?php foreach ($product['images'] as $index => $image): ?>
                    <div class="col-3">
                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($image['alt_text'] ?? $product['name']); ?>" 
                             class="img-fluid product-thumbnail w-100 <?php echo $index === 0 ? 'active' : ''; ?>"
                             onclick="changeMainImage('<?php echo htmlspecialchars($image['image_url']); ?>')">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div class="col-lg-6">
                <div class="ps-lg-4">
                    <!-- Product Title and Brand -->
                    <h1 class="h2 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <?php if ($product['brand_name']): ?>
                    <p class="text-muted mb-3">
                        <strong>Brand:</strong> <?php echo htmlspecialchars($product['brand_name']); ?>
                    </p>
                    <?php endif; ?>

                    <!-- Rating and Reviews -->
                    <?php if ($product['review_count'] > 0): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="rating-stars me-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= round($product['avg_rating']) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="me-2"><?php echo number_format($product['avg_rating'], 1); ?></span>
                        <small class="text-muted">(<?php echo $product['review_count']; ?> reviews)</small>
                    </div>
                    <?php endif; ?>

                    <!-- Price Section -->
                    <div class="price-section mb-4">
                        <div class="d-flex align-items-center">
                            <span class="h3 text-primary mb-0">KSh <?php echo number_format($product['price']); ?></span>
                            
                            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                            <span class="text-muted text-decoration-line-through ms-3">
                                KSh <?php echo number_format($product['compare_price']); ?>
                            </span>
                            <span class="badge bg-danger ms-2"><?php echo $discountPercentage; ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Stock Status -->
                        <div class="mt-3">
                            <?php if ($product['stock_quantity'] > 10): ?>
                                <span class="stock-badge in-stock">
                                    <i class="fas fa-check-circle me-1"></i>In Stock
                                </span>
                            <?php elseif ($product['stock_quantity'] > 0): ?>
                                <span class="stock-badge low-stock">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Only <?php echo $product['stock_quantity']; ?> left!
                                </span>
                            <?php else: ?>
                                <span class="stock-badge out-of-stock">
                                    <i class="fas fa-times-circle me-1"></i>Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add to Cart Form -->
                    <?php if ($product['stock_quantity'] > 0): ?>
                    <form method="POST" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Quantity</label>
                                <select name="quantity" id="quantity" class="form-select">
                                    <?php for ($i = 1; $i <= min($product['stock_quantity'], 10); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="d-flex gap-2 mb-4">
                        <button class="btn btn-outline-secondary" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                            <i class="fas fa-heart me-1"></i>Add to Wishlist
                        </button>
                        <button class="btn btn-outline-info" onclick="shareProduct()">
                            <i class="fas fa-share me-1"></i>Share
                        </button>
                    </div>

                    <!-- Short Description -->
                    <?php if ($product['short_description']): ?>
                    <div class="mb-4">
                        <h5>Overview</h5>
                        <p class="text-muted"><?php echo htmlspecialchars($product['short_description']); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Product Attributes -->
                    <?php if (!empty($product['attributes'])): ?>
                    <div class="attribute-list">
                        <h6 class="mb-3">Specifications</h6>
                        <div class="row">
                            <?php foreach ($product['attributes'] as $attr): ?>
                            <div class="col-md-6 mb-2">
                                <strong><?php echo htmlspecialchars($attr['attribute_name']); ?>:</strong>
                                <span class="text-muted"><?php echo htmlspecialchars($attr['attribute_value']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Description -->
        <?php if ($product['description']): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Product Description</h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reviews Section -->
        <?php if (!empty($product['reviews'])): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4>Customer Reviews</h4>
                <div class="row">
                    <?php foreach (array_slice($product['reviews'], 0, 4) as $review): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card review-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($review['username']); ?></h6>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php if ($review['title']): ?>
                                <h6 class="text-primary"><?php echo htmlspecialchars($review['title']); ?></h6>
                                <?php endif; ?>
                                <p class="mb-1"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                    <?php if ($review['is_verified_purchase']): ?>
                                        <span class="badge bg-success ms-1">Verified Purchase</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="mb-4">Related Products</h4>
                <div class="row">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($relatedProduct['image_url'] ?? 'https://via.placeholder.com/300x200'); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h6>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h6 text-primary mb-0">KSh <?php echo number_format($relatedProduct['price']); ?></span>
                                        <a href="product.php?id=<?php echo $relatedProduct['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Change main product image
        function changeMainImage(imageUrl) {
            document.getElementById('main-product-image').src = imageUrl;
            
            // Update thumbnail active state
            document.querySelectorAll('.product-thumbnail').forEach(img => {
                img.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Add to wishlist function (placeholder)
        function addToWishlist(productId) {
            // This would make an AJAX call to add the product to wishlist
            alert('Wishlist functionality coming soon!');
        }

        // Share product function
        function shareProduct() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($product['name']); ?>',
                    text: '<?php echo htmlspecialchars($product['short_description']); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy URL to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Product URL copied to clipboard!');
            }
        }
    </script>
</body>
</html>
