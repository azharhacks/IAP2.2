
<?php
require __DIR__ . '/../Config.php';
require __DIR__ . '/../ClassAutoload.php';
class Layout {
    public function header($conf) {
?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?php echo $conf['site_name']; ?> - Online Store</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
         body { background-color: #f8f9fa; }
         .hero-section { background: linear-gradient(135deg, #0d6efd, #6610f2); color: white; padding: 4rem 2rem; border-radius: 1rem; }
         .hero-section h1 { font-weight: 700; }
         .card-custom { border: none; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
         footer { background: #212529; color: #adb5bd; padding: 1.5rem 0; text-align: center; margin-top: 3rem; }
         footer p { margin: 0; }
      </style>
   </head>
<?php
    }

    public function navbar($conf) {
?>
   <body>
      <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
         <div class="container">
            <a class="navbar-brand fw-bold" href="./"><?php echo $conf['site_name']; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
               <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
               <ul class="navbar-nav ms-auto">
                  <li class="nav-item"><a class="nav-link active" href="./">Home</a></li>
                  <li class="nav-item"><a class="nav-link" href="products.php">Shop</a></li>
                  <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                  <li class="nav-item"><a class="nav-link" href="signup.php">Sign Up</a></li>
                  <li class="nav-item"><a class="nav-link" href="signin.php">Sign In</a></li>
               </ul>
            </div>
         </div>
      </nav>
      <main class="container py-4">
<?php
    }

    public function banner($conf) {
?>
         <section class="hero-section text-center">
            <h1>Welcome to <?php echo $conf['site_name']; ?></h1>
            <p class="lead">Discover the best deals on top products, right at your fingertips.</p>
            <a href="products.php" class="btn btn-light btn-lg mt-3">Start Shopping</a>
         </section>
<?php
    }

    public function content($conf) {
?>
         <div class="row g-4 mt-4">
            <div class="col-md-4">
               <div class="card card-custom p-4 text-center h-100">
                  <h2 class="fw-bold">Electronics</h2>
                  <p>Shop the latest gadgets, smartphones, and accessories at unbeatable prices.</p>
                  <a href="products.php?cat=electronics" class="btn btn-primary">Shop Now</a>
               </div>
            </div>
            <div class="col-md-4">
               <div class="card card-custom p-4 text-center h-100">
                  <h2 class="fw-bold">Fashion</h2>
                  <p>Upgrade your wardrobe with trendy clothing, shoes, and accessories.</p>
                  <a href="products.php?cat=fashion" class="btn btn-outline-primary">Explore</a>
               </div>
            </div>
            <div class="col-md-4">
               <div class="card card-custom p-4 text-center h-100">
                  <h2 class="fw-bold">Home & Living</h2>
                  <p>Find essentials and decor to make your home stylish and comfortable.</p>
                  <a href="products.php?cat=home" class="btn btn-success">Browse</a>
               </div>
            </div>
         </div>
<?php
    }

    public function form_content($conf, $ObjForm) {
?>
         <div class="row g-4 mt-4">
            <div class="col-md-6">
               <div class="card card-custom p-4">
                  <?php 
                     if (basename($_SERVER['PHP_SELF']) == 'signup.php') {
                        $ObjForm->signup($conf);
                     } else {
                        $ObjForm->signin($conf);
                     }
                  ?>
               </div>
            </div>
            <div class="col-md-6">
               <div class="card card-custom p-4 text-center">
                  <h2 class="fw-bold">Why Shop With Us?</h2>
                  <p>Enjoy exclusive discounts, secure checkout, and fast delivery right to your door.</p>
                  <a href="products.php" class="btn btn-success">Shop Now</a>
               </div>
            </div>
         </div>
<?php
    }

    public function footer($conf) {
?>
      </main>
      <footer>
         <div class="container">
            <p>&copy; <?php echo date("Y"); ?> <?php echo $conf['site_name']; ?>. All rights reserved.</p>
         </div>
      </footer>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   </body>
</html>
<?php
    }
}
