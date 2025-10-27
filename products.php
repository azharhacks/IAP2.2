<?php
/**
 * Products Page
 * Displays all active products with their details and allows users to add items to cart
 */

// Start session to maintain user authentication state
session_start();
require_once 'config.php';
require_once 'ClassAutoload.php';

// Get database connection instance using singleton pattern
$db = Database::getInstance()->getConnection();

// Query to fetch all active products with their category names
// LEFT JOIN ensures products without categories are still displayed
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
// Fetch all products as associative array for easy access
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Our Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Available Products</h2>
        <!-- Product grid layout using Bootstrap responsive grid -->
        <div class="row">
            <?php foreach ($products as $product): ?>
                <!-- Product card for each item in the database -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <!-- Display product image if available -->
                        <?php if ($product['image_url']): ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                            <!-- Format price with proper currency formatting (Kenyan Shillings) -->
                            <p><strong>Price: KSh <?= number_format($product['price'], 2) ?></strong></p>
                            <!-- Display category name for better product organization -->
                            <p>Category: <?= htmlspecialchars($product['category_name']) ?></p>
                            <!-- Add to cart button with AJAX functionality -->
                            <button class="btn btn-primary add-to-cart" data-product-id="<?= $product['id'] ?>">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- jQuery library for AJAX functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Handle add to cart button click event
        // This uses AJAX to add products to cart without page refresh
        $('.add-to-cart').click(function() {
            // Get product ID from data attribute
            const productId = $(this).data('product-id');
            
            // Send POST request to add_to_cart.php endpoint
            $.post('add_to_cart.php', {
                product_id: productId
            }).done(function(response) {
                // Success callback - notify user of successful addition
                alert('Product added to cart!');
            }).fail(function() {
                // Error callback - notify user of failure
                alert('Error adding product to cart');
            });
        });
    </script>
</body>
</html>