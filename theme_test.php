<?php
require_once 'Abstract/Layout.php';

$layout = new Layout();
$layout->header('Orange Theme Test');
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 mb-3">🍊 Orange Theme Applied</h1>
                <p class="lead text-muted">Testing all orange-themed components</p>
            </div>

            <!-- Buttons Test -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Button Tests</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button class="btn btn-primary">Primary Button</button>
                        <button class="btn btn-outline-primary">Outline Primary</button>
                        <button class="btn btn-secondary">Secondary</button>
                        <button class="btn btn-success">Success</button>
                        <button class="btn btn-danger">Danger</button>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-lg">Large Primary</button>
                        <button class="btn btn-primary btn-sm">Small Primary</button>
                    </div>
                </div>
            </div>

            <!-- Cards and Badges Test -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Orange Header</h6>
                        </div>
                        <div class="card-body">
                            <h6 class="text-primary">Orange Text</h6>
                            <p>This card has orange accents.</p>
                            <span class="badge bg-primary">Primary Badge</span>
                            <span class="badge bg-secondary ms-2">Secondary Badge</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-primary">Another Orange Title</h6>
                            <p class="card-text">Testing text with orange accents.</p>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-primary" style="width: 75%"></div>
                            </div>
                            <a href="#" class="btn btn-primary">Orange Button</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Test -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Form Elements</h5>
                </div>
                <div class="card-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label text-primary">Email</label>
                            <input type="email" class="form-control border-primary">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-primary">Select Option</label>
                            <select class="form-select border-primary">
                                <option>Choose...</option>
                                <option>Option 1</option>
                                <option>Option 2</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="check1">
                                <label class="form-check-label" for="check1">
                                    <span class="text-primary">I agree to the terms</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Form</button>
                    </form>
                </div>
            </div>

            <!-- Alert Test -->
            <div class="alert alert-primary" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Orange Alert!</strong> This is a primary alert with orange styling.
            </div>

            <!-- Navigation Test -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item"><a class="page-link text-primary" href="#">Previous</a></li>
                    <li class="page-item active"><a class="page-link bg-primary border-primary" href="#">1</a></li>
                    <li class="page-item"><a class="page-link text-primary" href="#">2</a></li>
                    <li class="page-item"><a class="page-link text-primary" href="#">3</a></li>
                    <li class="page-item"><a class="page-link text-primary" href="#">Next</a></li>
                </ul>
            </nav>

            <!-- Back Button -->
            <div class="text-center mt-5">
                <a href="dashboard.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</div>

<?php $layout->footer(); ?>
