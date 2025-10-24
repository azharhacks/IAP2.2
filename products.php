<?php
session_start();
require_once 'config.php';
require_once 'ClassAutoload.php';

$db = Database::getInstance()->getConnection();
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
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
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <?php if ($product['image_url']): ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                            <p><strong>Price: KSh <?= number_format($product['price'], 2) ?></strong></p>
                            <p>Category: <?= htmlspecialchars($product['category_name']) ?></p>
                            <button class="btn btn-primary add-to-cart" data-product-id="<?= $product['id'] ?>">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $('.add-to-cart').click(function() {
            const productId = $(this).data('product-id');
            $.post('add_to_cart.php', {
                product_id: productId
            }).done(function(response) {
                alert('Product added to cart!');
            }).fail(function() {
                alert('Error adding product to cart');
            });
        });
    </script>
</body>
</html>