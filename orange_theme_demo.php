<?php
require_once 'Abstract/Layout.php';

$layout = new Layout();
$layout->header('Orange Theme Demo');
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-palette me-2"></i>New Orange Theme Applied!</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Theme Update Complete!</strong> Your layout has been changed from gradients to a solid orange color scheme.
                    </div>
                    
                    <h5 class="mb-3">Theme Changes Made:</h5>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">
                            <i class="fas fa-paint-brush text-primary me-2"></i>
                            <strong>Background:</strong> Changed from gradient to solid orange (#ff6b35)
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-palette text-primary me-2"></i>
                            <strong>Color Variables:</strong> Updated CSS variables to use solid orange colors
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-magic text-primary me-2"></i>
                            <strong>Effects:</strong> Replaced gradients with orange-themed solid colors
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-text-height text-primary me-2"></i>
                            <strong>Navigation:</strong> Updated navbar brand styling to match orange theme
                        </li>
                    </ul>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-home fa-3x text-primary mb-3"></i>
                                    <h6>Primary Color</h6>
                                    <code>#ff6b35</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-secondary">
                                <div class="card-body text-center">
                                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                                    <h6>Secondary Color</h6>
                                    <code>#f97316</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="dashboard.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                        </a>
                        <a href="products.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-shopping-bag me-2"></i>Browse Products
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sample buttons to show the new orange theme -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="mb-0">🎯 Button Examples with New Theme</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary">Primary Button</button>
                        <button class="btn btn-secondary">Secondary Button</button>
                        <button class="btn btn-success">Success Button</button>
                        <button class="btn btn-outline-primary">Outlined Primary</button>
                        <button class="btn btn-outline-secondary">Outlined Secondary</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $layout->footer(); ?>
