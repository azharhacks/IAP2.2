<?php
/**
 * Products Listing Page
 * Shows all products with search, filtering, and pagination
 * Features: Category filtering, brand filtering, price range, search, sorting
 */

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

// Initialize managers
$productManager = new ProductManager($pdo);

// Get filter parameters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'category_id' => (int)($_GET['category'] ?? 0),
    'brand_id' => (int)($_GET['brand'] ?? 0),
    'min_price' => (float)($_GET['min_price'] ?? 0),
    'max_price' => (float)($_GET['max_price'] ?? 0),
    'sort' => $_GET['sort'] ?? 'p.created_at DESC'
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '' && $value !== 0;
});

// Get current page
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// Get products
$productsData = $productManager->getProducts($filters, $currentPage, $perPage);
$products = $productsData['products'];
$totalPages = $productsData['total_pages'];

// Get categories and brands for filters
$categories = $productManager->getCategories();
$brands = $productManager->getBrands();

// Get price range for slider
$stmt = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = TRUE");
$priceRange = $stmt->fetch();

// Helper function to build filter URLs
function buildFilterUrl($newParams = []) {
    $params = array_merge($_GET, $newParams);
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    return 'products.php?' . http_build_query($params);
}

// Create layout instance
$layout = new Layout();

// Define custom CSS for products page
$customCSS = '';

// Start the page
$layout->header('Products - Shop Now', $customCSS);
$layout->navbar('products'); // This will show search bar

// Build breadcrumb
$breadcrumbs = ['Shop'];
if (!empty($filters['search'])) {
    $breadcrumbs[] = 'Search: "' . htmlspecialchars($filters['search']) . '"';
} else if (!empty($filters['category_id'])) {
    foreach($categories as $category) {
        if ($category['id'] == $filters['category_id']) {
            $breadcrumbs[] = $category['name'];
            break;
        }
    }
}

$layout->breadcrumb($breadcrumbs);

// Don't show banner on products page to save space, go straight to content
$layout->contentStart();
?>

<!-- Custom CSS for Products Page -->
<style>
.product-card {
    transition: all 0.3s ease;
    height: 100%;
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
.product-image {
    height: 250px;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.product-card:hover .product-image {
    transform: scale(1.05);
}
.price-old {
    text-decoration: line-through;
    color: #6c757d;
}
.filter-sidebar {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
}
.filter-section {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}
.filter-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.brand-checkbox:checked + label {
    background: #e3f2fd;
    border-color: #2196f3;
}
.sort-dropdown {
    min-width: 200px;
}
.results-info {
    color: #6c757d;
    font-size: 0.9rem;
}
</style>

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <h5 class="mb-3">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h5>

                    <form method="GET" id="filterForm">
                        <!-- Preserve search query -->
                        <?php if (!empty($_GET['search'])): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                        <?php endif; ?>

                        <!-- Categories Filter -->
                        <div class="filter-section">
                            <h6>Categories</h6>
                            <?php foreach ($categories as $category): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" 
                                       value="<?php echo $category['id']; ?>" 
                                       id="cat<?php echo $category['id']; ?>"
                                       <?php echo ($_GET['category'] ?? '') == $category['id'] ? 'checked' : ''; ?>
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="cat<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?> 
                                    <small class="text-muted">(<?php echo $category['product_count']; ?>)</small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!empty($_GET['category'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" value="" 
                                       id="catAll" onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="catAll">
                                    <strong>All Categories</strong>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Brands Filter -->
                        <div class="filter-section">
                            <h6>Brands</h6>
                            <?php foreach ($brands as $brand): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="brand" 
                                       value="<?php echo $brand['id']; ?>" 
                                       id="brand<?php echo $brand['id']; ?>"
                                       <?php echo ($_GET['brand'] ?? '') == $brand['id'] ? 'checked' : ''; ?>
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="brand<?php echo $brand['id']; ?>">
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                    <small class="text-muted">(<?php echo $brand['product_count']; ?>)</small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!empty($_GET['brand'])): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="brand" value="" 
                                       id="brandAll" onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="brandAll">
                                    <strong>All Brands</strong>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="filter-section">
                            <h6>Price Range</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="min_price" class="form-label">Min</label>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="min_price" id="min_price" 
                                           placeholder="0" 
                                           min="<?php echo $priceRange['min_price']; ?>"
                                           max="<?php echo $priceRange['max_price']; ?>"
                                           value="<?php echo $_GET['min_price'] ?? ''; ?>">
                                </div>
                                <div class="col-6">
                                    <label for="max_price" class="form-label">Max</label>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="max_price" id="max_price" 
                                           placeholder="<?php echo number_format($priceRange['max_price']); ?>"
                                           min="<?php echo $priceRange['min_price']; ?>"
                                           max="<?php echo $priceRange['max_price']; ?>"
                                           value="<?php echo $_GET['max_price'] ?? ''; ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 mt-2">
                                Apply Price Filter
                            </button>
                        </div>

                        <!-- Clear Filters -->
                        <?php if (!empty(array_filter($_GET))): ?>
                        <div class="text-center">
                            <a href="products.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear All Filters
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4>Products</h4>
                        <p class="results-info mb-0">
                            Showing <?php echo count($products); ?> of <?php echo $productsData['total']; ?> products
                            <?php if (!empty($filters['search'])): ?>
                                for "<?php echo htmlspecialchars($filters['search']); ?>"
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <!-- Sort Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle sort-dropdown" 
                                type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-sort me-2"></i>Sort By
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo buildFilterUrl(['sort' => 'p.created_at DESC']); ?>">Newest First</a></li>
                            <li><a class="dropdown-item" href="<?php echo buildFilterUrl(['sort' => 'p.price ASC']); ?>">Price: Low to High</a></li>
                            <li><a class="dropdown-item" href="<?php echo buildFilterUrl(['sort' => 'p.price DESC']); ?>">Price: High to Low</a></li>
                            <li><a class="dropdown-item" href="<?php echo buildFilterUrl(['sort' => 'p.name ASC']); ?>">Name: A to Z</a></li>
                            <li><a class="dropdown-item" href="<?php echo buildFilterUrl(['sort' => 'avg_rating DESC']); ?>">Highest Rated</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-muted">No products found</h4>
                    <p class="text-muted">Try adjusting your search criteria or browse our categories.</p>
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card">
                            <div class="position-relative overflow-hidden">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300x250'); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                
                                <!-- Discount Badge -->
                                <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                <?php $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>
                                <span class="position-absolute top-0 start-0 badge bg-danger m-2">
                                    <?php echo $discount; ?>% OFF
                                </span>
                                <?php endif; ?>

                                <!-- Quick View Button -->
                                <div class="position-absolute top-0 end-0 m-2">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-light btn-sm rounded-circle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <!-- Brand -->
                                <?php if ($product['brand_name']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></small>
                                <?php endif; ?>
                                
                                <!-- Product Name -->
                                <h6 class="card-title">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h6>

                                <!-- Rating -->
                                <?php if ($product['review_count'] > 0): ?>
                                <div class="mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="text-warning me-1">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= round($product['avg_rating']) ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">
                                            (<?php echo $product['review_count']; ?>)
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Price -->
                                <div class="mt-auto">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="h6 text-primary mb-0">
                                                KSh <?php echo number_format($product['price']); ?>
                                            </span>
                                            <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                            <br>
                                            <small class="price-old">
                                                KSh <?php echo number_format($product['compare_price']); ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Add to Cart Button -->
                                        <div>
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                            <button class="btn btn-primary btn-sm" 
                                                    onclick="addToCart(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-cart-plus"></i>
                                            </button>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Out of Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Products pagination">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page -->
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildFilterUrl(['page' => $currentPage - 1]); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo buildFilterUrl(['page' => $i]); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildFilterUrl(['page' => $currentPage + 1]); ?>">
                                <i class="fas fa-chevron-right"></i>
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

<script>
    // Add to cart function
    function addToCart(productId) {
        // Check if user is logged in and 2FA verified
        <?php if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])): ?>
        showNotification('Please login and verify 2FA to add items to cart', 'warning');
        window.location.href = 'Signin.php?redirect=' + encodeURIComponent(window.location.href);
        return;
        <?php endif; ?>

        // Make AJAX request to add to cart
        fetch('cart_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=add&product_id=' + productId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product added to cart!', 'success');
                updateCartCount();
            } else {
                showNotification(data.message || 'Failed to add product to cart', 'danger');
            }
        })
        .catch(error => {
            showNotification('An error occurred. Please try again.', 'danger');
        });
    }
</script>

<?php
$layout->contentEnd();
$layout->footer();
?>