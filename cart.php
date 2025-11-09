<?php
/**
 * Shopping Cart Page
 * Displays cart items, allows quantity updates, and provides checkout option
 * Features: Item management, quantity updates, totals calculation, proceed to checkout
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Initialize layout
$layout = new Layout();

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

// Initialize cart manager
$cartManager = new CartManager($pdo);

// Get user ID if logged in
$userId = $_SESSION['user_id'] ?? null;

// Get cart items and totals
$cartItems = $cartManager->getCartItems($userId);
$cartTotals = $cartManager->getCartTotals($userId);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update cart quantities
        foreach ($_POST['quantities'] as $productId => $quantity) {
            $cartManager->updateCartItem((int)$productId, (int)$quantity, $userId);
        }
        
        // Refresh cart data
        header('Location: cart.php');
        exit;
    }
}

// Custom CSS for cart page
$customCSS = '
    .cart-item {
        transition: all 0.3s ease;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .cart-item:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .product-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
    }
    .quantity-input {
        width: 80px;
        text-align: center;
    }
    .cart-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        position: sticky;
        top: 20px;
    }
    .price-highlight {
        font-size: 1.2rem;
        font-weight: bold;
    }
    .empty-cart {
        text-align: center;
        padding: 4rem 0;
    }
    .empty-cart i {
        font-size: 5rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
    .btn-quantity {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .free-shipping-progress {
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }
';

$layout->header('Shopping Cart', $customCSS);
$layout->navbar('cart');
$layout->breadcrumb([
    ['title' => 'Home', 'url' => 'dashboard.php'],
    ['title' => 'Shopping Cart', 'url' => '', 'active' => true]
]);
$layout->contentStart();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
        <?php if ($cartTotals['total_items'] > 0): ?>
        <span class="text-muted">(<?php echo $cartTotals['total_items']; ?> items)</span>
        <?php endif; ?>
    </h2>
    <a href="products.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
    </a>
</div>

        <?php if (empty($cartItems)): ?>
        <!-- Empty Cart -->
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3 class="text-muted">Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
            <a href="products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
            </a>
        </div>
        <?php else: ?>
        
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <!-- Free Shipping Progress (if applicable) -->
                <?php if ($cartTotals['subtotal'] < 5000): ?>
                <?php 
                $freeShippingThreshold = 5000;
                $remaining = $freeShippingThreshold - $cartTotals['subtotal'];
                $progress = ($cartTotals['subtotal'] / $freeShippingThreshold) * 100;
                ?>
                <div class="alert alert-info">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><i class="fas fa-truck me-2"></i>Free shipping on orders over KSh 5,000</span>
                        <strong>KSh <?php echo number_format($remaining); ?> to go</strong>
                    </div>
                    <div class="progress free-shipping-progress">
                        <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Congratulations! You qualify for free shipping.
                </div>
                <?php endif; ?>

                <form method="POST" id="cartForm">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                        <div class="row align-items-center">
                            <!-- Product Image -->
                            <div class="col-md-2">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/100'); ?>" 
                                     class="product-image" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>
                            
                            <!-- Product Details -->
                            <div class="col-md-4">
                                <h6 class="mb-1">
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </a>
                                </h6>
                                <?php if ($item['brand_name']): ?>
                                <small class="text-muted">Brand: <?php echo htmlspecialchars($item['brand_name']); ?></small><br>
                                <?php endif; ?>
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>In Stock (<?php echo $item['stock_quantity']; ?> available)
                                </small>
                            </div>
                            
                            <!-- Quantity Controls -->
                            <div class="col-md-3">
                                <div class="d-flex align-items-center justify-content-center">
                                    <button type="button" class="btn btn-outline-secondary btn-quantity me-2" 
                                            onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" 
                                           name="quantities[<?php echo $item['product_id']; ?>]"
                                           class="form-control quantity-input" 
                                           value="<?php echo $item['quantity']; ?>"
                                           min="1" 
                                           max="<?php echo $item['stock_quantity']; ?>"
                                           onchange="updateQuantityFromInput(<?php echo $item['product_id']; ?>, this.value)">
                                    <button type="button" class="btn btn-outline-secondary btn-quantity ms-2" 
                                            onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Price and Actions -->
                            <div class="col-md-3 text-end">
                                <div class="price-highlight text-primary mb-2">
                                    KSh <?php echo number_format($item['price'] * $item['quantity']); ?>
                                </div>
                                <div class="text-muted small mb-2">
                                    KSh <?php echo number_format($item['price']); ?> each
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Cart Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Clear Cart
                        </button>
                        <button type="submit" name="update_cart" class="btn btn-primary">
                            <i class="fas fa-sync me-2"></i>Update Cart
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4 class="mb-4">
                        <i class="fas fa-receipt me-2"></i>Order Summary
                    </h4>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal (<?php echo $cartTotals['total_items']; ?> items):</span>
                        <span>KSh <?php echo number_format($cartTotals['subtotal']); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax (16% VAT):</span>
                        <span>KSh <?php echo number_format($cartTotals['tax_amount']); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Shipping:</span>
                        <span>
                            <?php if ($cartTotals['shipping_cost'] > 0): ?>
                                KSh <?php echo number_format($cartTotals['shipping_cost']); ?>
                            <?php else: ?>
                                <span class="text-success">FREE</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <hr class="border-light">
                    
                    <div class="d-flex justify-content-between mb-4">
                        <h5>Total:</h5>
                        <h5>KSh <?php echo number_format($cartTotals['total']); ?></h5>
                    </div>
                    
                    <!-- Checkout Button -->
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['2fa_verified'])): ?>
                    <a href="checkout.php" class="btn btn-light btn-lg w-100 mb-3">
                        <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                    </a>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <small>Please <a href="Signin.php?redirect=cart.php" class="text-decoration-none">login and verify 2FA</a> to proceed with checkout.</small>
                    </div>
                    <a href="Signin.php?redirect=cart.php" class="btn btn-light btn-lg w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Checkout
                    </a>
                    <?php endif; ?>
                    
                    <!-- Security Badge -->
                    <div class="text-center">
                        <small class="text-light">
                            <i class="fas fa-lock me-1"></i>Secure Checkout
                        </small>
                    </div>
                </div>
                
                <!-- Recommended Products (placeholder) -->
                <div class="mt-4">
                    <h6>You might also like:</h6>
                    <div class="alert alert-info">
                        <small>Recommended products coming soon!</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

<script>
    // Update quantity function
    function updateQuantity(productId, change) {
        const input = document.querySelector(`input[name="quantities[${productId}]"]`);
        const newQuantity = parseInt(input.value) + change;
        
        if (newQuantity >= 1 && newQuantity <= parseInt(input.max)) {
            input.value = newQuantity;
            updateQuantityFromInput(productId, newQuantity);
        }
    }

    // Update quantity from input change
    function updateQuantityFromInput(productId, quantity) {
        quantity = parseInt(quantity);
        
        if (quantity < 1) {
            quantity = 1;
        }
        
        // Update via AJAX
        fetch('cart_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update&product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page to show updated totals
                location.reload();
            } else {
                showNotification(data.message || 'Failed to update cart', 'danger');
            }
        })
        .catch(error => {
            showNotification('An error occurred. Please try again.', 'danger');
        });
    }

    // Remove item from cart
    function removeFromCart(productId) {
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            fetch('cart_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the item element
                    const itemElement = document.querySelector(`[data-product-id="${productId}"]`);
                    if (itemElement) {
                        itemElement.remove();
                    }
                    
                    // Reload page to update totals
                    location.reload();
                } else {
                    showNotification(data.message || 'Failed to remove item', 'danger');
                }
            })
            .catch(error => {
                showNotification('An error occurred. Please try again.', 'danger');
            });
        }
    }

    // Clear entire cart
    function clearCart() {
        if (confirm('Are you sure you want to clear your entire cart?')) {
            fetch('cart_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Failed to clear cart', 'danger');
                }
            })
            .catch(error => {
                showNotification('An error occurred. Please try again.', 'danger');
            });
        }
    }
</script>

<?php
$layout->contentEnd();
$layout->footer();
?>
