<?php
/**
 * Checkout Page
 * Handles the checkout process including address selection, payment, and order creation
 * Features: Address management, order summary, payment processing, order confirmation
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ClassAutoload.php';

// Initialize layout
$layout = new Layout();

// Redirect to login if not authenticated or 2FA not verified
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    header('Location: Signin.php?redirect=checkout.php');
    exit;
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
$cartManager = new CartManager($pdo);
$orderManager = new OrderManager($pdo);

$userId = $_SESSION['user_id'];

// Get cart items and totals
$cartItems = $cartManager->getCartItems($userId);
$cartTotals = $cartManager->getCartTotals($userId);

// Redirect to cart if empty
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Get user addresses
$stmt = $pdo->prepare("
    SELECT * FROM addresses 
    WHERE user_id = ? AND is_active = TRUE 
    ORDER BY is_default DESC, created_at DESC
");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();

// Handle form submissions
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        // Add new address
        $addressData = [
            'user_id' => $userId,
            'type' => $_POST['address_type'] ?? 'shipping',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'address_line_1' => trim($_POST['address_line_1'] ?? ''),
            'address_line_2' => trim($_POST['address_line_2'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'country' => trim($_POST['country'] ?? 'Kenya'),
            'phone' => trim($_POST['phone'] ?? ''),
            'is_default' => (!empty($_POST['is_default']) && $_POST['is_default'] !== '') ? 1 : 0,
            'is_active' => 1
        ];
        
        // Validate required fields
        if (empty($addressData['first_name']) || empty($addressData['last_name']) || 
            empty($addressData['address_line_1']) || empty($addressData['city']) || 
            empty($addressData['phone'])) {
            $errors[] = 'Please fill in all required address fields.';
        } else {
            try {
                // If this is set as default, remove default from other addresses
                if ($addressData['is_default']) {
                    $stmt = $pdo->prepare("UPDATE addresses SET is_default = FALSE WHERE user_id = ?");
                    $stmt->execute([$userId]);
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO addresses (user_id, type, first_name, last_name, company, 
                                         address_line_1, address_line_2, city, state, postal_code, 
                                         country, phone, is_default, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $addressData['user_id'], $addressData['type'], $addressData['first_name'],
                    $addressData['last_name'], $addressData['company'], $addressData['address_line_1'],
                    $addressData['address_line_2'], $addressData['city'], $addressData['state'],
                    $addressData['postal_code'], $addressData['country'], $addressData['phone'],
                    $addressData['is_default'], $addressData['is_active']
                ]);
                
                $success = 'Address added successfully!';
                
                // Refresh addresses
                $stmt = $pdo->prepare("
                    SELECT * FROM addresses 
                    WHERE user_id = ? AND is_active = TRUE 
                    ORDER BY is_default DESC, created_at DESC
                ");
                $stmt->execute([$userId]);
                $addresses = $stmt->fetchAll();
                
            } catch (Exception $e) {
                $errors[] = 'Failed to add address. Please try again.';
            }
        }
    }
    
    if (isset($_POST['place_order'])) {
        // Process order
        $addressId = (int)($_POST['shipping_address'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? '';
        $notes = trim($_POST['order_notes'] ?? '');
        
        // Validate
        if (!$addressId) {
            $errors[] = 'Please select a shipping address.';
        }
        if (empty($paymentMethod)) {
            $errors[] = 'Please select a payment method.';
        }
        
        if (empty($errors)) {
            try {
                // Get shipping address details
                $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$addressId, $userId]);
                $shippingAddress = $stmt->fetch();
                
                if (!$shippingAddress) {
                    $errors[] = 'Invalid shipping address selected.';
                } else {
                    // Create order with additional data
                    $additionalOrderData = [
                        'shipping_method' => 'Standard Delivery',
                        'notes' => $notes
                    ];
                    
                    $result = $orderManager->createOrder($userId, $shippingAddress, $paymentMethod, $additionalOrderData);
                    $orderId = $result['order_id'] ?? null;
                
                    if ($orderId) {
                        // Clear cart
                        $cartManager->clearCart($userId);
                        
                        // Redirect based on payment method
                        if ($paymentMethod === 'mpesa') {
                            header('Location: mpesa_payment_page.php?order=' . $orderId);
                        } else {
                            header('Location: order_confirmation.php?order=' . $orderId);
                        }
                        exit;
                    } else {
                        $errors[] = 'Failed to create order. Please try again.';
                    }
                }
                
            } catch (Exception $e) {
                $errors[] = 'An error occurred while processing your order. Please try again.';
            }
        }
    }
}

// Custom CSS for checkout page
$customCSS = '
    .checkout-step {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .address-card {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .address-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 15px rgba(0,123,255,0.2);
    }
    .address-card.selected {
        border-color: #007bff;
        background: #f8f9fa;
    }
    .order-summary {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        position: sticky;
        top: 20px;
    }
    .payment-method {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .payment-method:hover {
        border-color: #007bff;
    }
    .payment-method.selected {
        border-color: #007bff;
        background: #f8f9fa;
    }
    .payment-method.mpesa {
        border-color: #00D4AA;
    }
    .payment-method.mpesa:hover {
        border-color: #00A693;
        box-shadow: 0 4px 15px rgba(0, 212, 170, 0.2);
    }
    .payment-method.mpesa.selected {
        border-color: #00A693;
        background: linear-gradient(135deg, rgba(0, 212, 170, 0.1) 0%, rgba(0, 166, 147, 0.1) 100%);
    }
    .product-item {
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 0;
    }
    .product-item:last-child {
        border-bottom: none;
    }
    .product-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
    }
    .mpesa-icon {
        color: #00D4AA;
        font-size: 1.2rem;
    }
';

$layout->header('Checkout', $customCSS);
$layout->navbar('checkout');
$layout->breadcrumb([
    ['title' => 'Home', 'url' => 'dashboard.php'],
    ['title' => 'Cart', 'url' => 'cart.php'],
    ['title' => 'Checkout', 'url' => '', 'active' => true]
]);
$layout->contentStart();
?>

<!-- Error and Success Messages -->
<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="checkout-step">
    <h2 class="mb-0">
        <i class="fas fa-credit-card me-2"></i>Secure Checkout
    </h2>
    <p class="mb-0 mt-2">Complete your order securely with our encrypted checkout process</p>
</div>

<div class="row">
    <!-- Main Checkout Form -->
    <div class="col-lg-8">
        <form method="POST" id="checkoutForm">
            <!-- Shipping Address Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Shipping Address
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($addresses)): ?>
                    <p class="text-muted">No addresses found. Please add a shipping address.</p>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($addresses as $address): ?>
                        <div class="col-md-6">
                            <div class="address-card" onclick="selectAddress(<?php echo $address['id']; ?>)">
                                <input type="radio" name="shipping_address" value="<?php echo $address['id']; ?>" 
                                       id="addr_<?php echo $address['id']; ?>" 
                                       <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                <label for="addr_<?php echo $address['id']; ?>" class="form-label w-100">
                                    <strong><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></strong>
                                    <?php if ($address['is_default']): ?>
                                    <span class="badge bg-primary ms-2">Default</span>
                                    <?php endif; ?>
                                    <br>
                                    <?php if ($address['company']): ?>
                                    <?php echo htmlspecialchars($address['company']); ?><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                    <?php if ($address['address_line_2']): ?>
                                    <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($address['city'] . ', ' . $address['state']); ?><br>
                                    <?php echo htmlspecialchars($address['country'] . ' ' . $address['postal_code']); ?><br>
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($address['phone']); ?>
                                    </small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Add New Address Button -->
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-2"></i>Add New Address
                    </button>
                </div>
            </div>

            <!-- Payment Method Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>Payment Method
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="payment-method mpesa" onclick="selectPayment('mpesa')">
                                <input type="radio" name="payment_method" value="mpesa" id="payment_mpesa">
                                <label for="payment_mpesa" class="form-label w-100">
                                    <i class="fas fa-mobile-alt mpesa-icon me-2"></i>
                                    <strong>M-Pesa</strong>
                                    <small class="d-block text-muted mt-1">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Secure mobile money payment
                                    </small>
                                    <small class="d-block text-success mt-1">
                                        <i class="fas fa-check me-1"></i>
                                        Instant payment confirmation
                                    </small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payment-method" onclick="selectPayment('cod')">
                                <input type="radio" name="payment_method" value="cod" id="payment_cod">
                                <label for="payment_cod" class="form-label w-100">
                                    <i class="fas fa-money-bill text-warning me-2"></i>
                                    <strong>Cash on Delivery</strong>
                                    <small class="d-block text-muted mt-1">
                                        <i class="fas fa-truck me-1"></i>
                                        Pay when your order arrives
                                    </small>
                                    <small class="d-block text-info mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Additional KSh 200 COD fee applies
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Notes Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note me-2"></i>Order Notes (Optional)
                    </h5>
                </div>
                <div class="card-body">
                    <textarea name="order_notes" class="form-control" rows="3" 
                              placeholder="Special instructions for your order..."></textarea>
                </div>
            </div>

            <!-- Place Order Button -->
            <div class="d-grid">
                <button type="submit" name="place_order" class="btn btn-primary btn-lg">
                    <i class="fas fa-lock me-2"></i>Place Order - KSh <?php echo number_format($cartTotals['total']); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Order Summary Sidebar -->
    <div class="col-lg-4">
        <div class="order-summary">
            <h5 class="mb-3">
                <i class="fas fa-receipt me-2"></i>Order Summary
            </h5>

            <!-- Order Items -->
            <div class="mb-3">
                <?php foreach ($cartItems as $item): ?>
                <div class="product-item">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/60'); ?>" 
                             class="product-image me-3" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                            <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                        </div>
                        <div class="text-end">
                            <strong>KSh <?php echo number_format($item['price'] * $item['quantity']); ?></strong>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Totals -->
            <div class="border-top pt-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>KSh <?php echo number_format($cartTotals['subtotal']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
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
                <div class="d-flex justify-content-between">
                    <h5>Total:</h5>
                    <h5 class="text-primary">KSh <?php echo number_format($cartTotals['total']); ?></h5>
                </div>
            </div>

            <!-- Payment Method Info -->
            <div class="text-center mt-4 pt-3 border-top">
                <div id="mpesa-info" class="d-none">
                    <div class="alert alert-info py-2 px-3 mb-3">
                        <small>
                            <i class="fas fa-mobile-alt me-1"></i>
                            You'll be redirected to M-Pesa payment after placing your order
                        </small>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Your payment information is secure and encrypted
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="company" class="form-label">Company (Optional)</label>
                        <input type="text" class="form-control" name="company">
                    </div>
                    <div class="mb-3">
                        <label for="address_line_1" class="form-label">Address Line 1 *</label>
                        <input type="text" class="form-control" name="address_line_1" required>
                    </div>
                    <div class="mb-3">
                        <label for="address_line_2" class="form-label">Address Line 2 (Optional)</label>
                        <input type="text" class="form-control" name="address_line_2">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" class="form-control" name="city" required>
                        </div>
                        <div class="col-md-6">
                            <label for="state" class="form-label">State/County</label>
                            <input type="text" class="form-control" name="state">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" name="postal_code">
                        </div>
                        <div class="col-md-6">
                            <label for="country" class="form-label">Country</label>
                            <select class="form-control" name="country">
                                <option value="Kenya" selected>Kenya</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_default" id="is_default">
                        <label class="form-check-label" for="is_default">
                            Set as default address
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_address" class="btn btn-primary">Add Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectAddress(addressId) {
    // Remove selected class from all address cards
    document.querySelectorAll('.address-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selected class to clicked card
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById('addr_' + addressId).checked = true;
}

function selectPayment(method) {
    // Remove selected class from all payment methods
    document.querySelectorAll('.payment-method').forEach(method => {
        method.classList.remove('selected');
    });
    
    // Add selected class to clicked method
    event.currentTarget.classList.add('selected');
    
    // Check the radio button
    document.getElementById('payment_' + method).checked = true;
    
    // Show/hide M-Pesa info
    const mpesaInfo = document.getElementById('mpesa-info');
    if (method === 'mpesa') {
        mpesaInfo.classList.remove('d-none');
    } else {
        mpesaInfo.classList.add('d-none');
    }
}

// Initialize selected address and payment method
document.addEventListener('DOMContentLoaded', function() {
    const checkedAddress = document.querySelector('input[name="shipping_address"]:checked');
    if (checkedAddress) {
        checkedAddress.closest('.address-card').classList.add('selected');
    }
    
    const checkedPayment = document.querySelector('input[name="payment_method"]:checked');
    if (checkedPayment) {
        checkedPayment.closest('.payment-method').classList.add('selected');
        if (checkedPayment.value === 'mpesa') {
            document.getElementById('mpesa-info').classList.remove('d-none');
        }
    }
});
</script>

<?php
$layout->contentEnd();
$layout->footer();
?>
