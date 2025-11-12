<?php
/**
 * Centralized Layout Management Class
 * Provides consistent header, navigation, footer, and content structure
 * All pages should use this for uniform design and easy maintenance
 */
class Layout {
    
    /**
     * Generate the HTML head section with consistent meta tags and styles
     * @param string $pageTitle Optional page-specific title
     * @param string $customCSS Optional additional CSS styles
     */
    public function header($pageTitle = '', $customCSS = '') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        global $conf;
        $title = !empty($pageTitle) ? $pageTitle . ' - ' . $conf['site_name'] : $conf['site_name'] . ' - Smart Shopping Experience';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo htmlspecialchars($conf['site_name']); ?> - Your premier online shopping destination">
    <meta name="keywords" content="ecommerce, online shopping, products, Kenya">
    <meta name="author" content="<?php echo htmlspecialchars($conf['site_name']); ?>">
    
    <title><?php echo htmlspecialchars($title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Global Styles -->
    <style>
        :root {
            /* Orange Theme Colors */
            --primary-color: #ff6b35;
            --primary-dark: #e55a2e;
            --primary-light: #ff8a5c;
            --secondary-color: #f97316;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            
            /* Bootstrap Color Overrides */
            --bs-primary: #ff6b35;
            --bs-primary-rgb: 255, 107, 53;
            --bs-secondary: #f97316;
            --bs-secondary-rgb: 249, 115, 22;
            
            /* Design System */
            --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --card-shadow-hover: 0 12px 40px rgba(0,0,0,0.15);
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --border-radius-lg: 24px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --backdrop-blur: blur(20px);
        }
        
        * {
            box-sizing: border-box;
        }
        
        body { 
            background: #ff6b35;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            position: relative;
        }
        
        /* Orange background overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 107, 53, 0.05);
            pointer-events: none;
            z-index: -1;
        }
        
        /* Navigation Styles */
        .navbar {
            backdrop-filter: var(--backdrop-blur);
            -webkit-backdrop-filter: var(--backdrop-blur);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 0;
            z-index: 1040;
            position: relative;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.75rem;
            color: #fff;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
            transition: var(--transition);
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
            filter: brightness(1.2);
        }
        
        .navbar-brand i {
            color: #fff;
            margin-right: 0.5rem;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.3));
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
            margin: 0 4px;
            padding: 12px 20px !important;
            position: relative;
            overflow: hidden;
        }
        
        .navbar-nav .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.3), transparent);
            transition: var(--transition);
        }
        
        .navbar-nav .nav-link:hover::before {
            left: 100%;
        }
        
        .navbar-nav .nav-link:hover {
            background: rgba(255,255,255,0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .navbar-nav .nav-link.active {
            background: rgba(255,255,255,0.25);
            font-weight: 600;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            transition: var(--transition);
        }
        
        .navbar-nav .nav-link:hover i {
            transform: scale(1.2);
        }
        
        /* Cart badge animation */
        .navbar .badge {
            animation: bounce 0.6s ease-in-out;
            transform-origin: center;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        
        /* Dropdown menu styling */
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow-hover);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            z-index: 1050 !important;
            position: absolute !important;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: var(--transition);
            border-radius: 0;
            margin: 0 0.5rem;
        }
        
        .dropdown-item:hover {
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius-sm);
            transform: translateX(5px);
        }
        
        /* Ensure dropdown is above other content */
        .nav-item.dropdown {
            position: relative;
        }
        
        .dropdown-toggle::after {
            transition: var(--transition);
        }
        
        .dropdown-toggle[aria-expanded="true"]::after {
            transform: rotate(180deg);
        }
        
        /* Mobile menu improvements */
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }
        
        .navbar-toggler:hover {
            background: rgba(255,255,255,0.1);
            transform: scale(1.1);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
        }
        
        /* Card Styles */
        .card-custom { 
            border: none; 
            border-radius: var(--border-radius); 
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: var(--backdrop-blur);
            position: relative;
        }
        
        .card-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: var(--transition);
        }
        
        .card-custom:hover::before {
            transform: scaleX(1);
        }
        
        .card-custom:hover {
            box-shadow: var(--card-shadow-hover);
            transform: translateY(-8px) scale(1.02);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .card-img-top {
            transition: var(--transition);
        }
        
        .card-custom:hover .card-img-top {
            transform: scale(1.1);
        }
        
        .card-body {
            position: relative;
            z-index: 1;
        }
        
        /* Enhanced card variants */
        .card-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: var(--backdrop-blur);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-gradient {
            background: var(--primary-color);
            color: white;
        }
        
        .card-gradient .card-title,
        .card-gradient .card-text {
            color: white;
        }
        
        /* Hero Section */
        .hero-section { 
            background: var(--primary-color);
            color: white; 
            padding: 6rem 2rem; 
            border-radius: var(--border-radius-lg);
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 107, 53, 0.1);
            pointer-events: none;
        }
        
        .hero-section > * {
            position: relative;
            z-index: 1;
        }
        
        .hero-section h1 { 
            font-weight: 800;
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease-out;
        }
        
        .hero-section .lead {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            font-weight: 400;
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-section .btn {
            animation: fadeInUp 1s ease-out 0.4s both;
            transform: scale(1);
            transition: var(--transition);
        }
        
        .hero-section .btn:hover {
            transform: scale(1.1) translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        
        /* Button Styles */
        .btn {
            border-radius: var(--border-radius-lg);
            padding: 12px 32px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            border: none;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 107, 53, 0.4), transparent);
            transition: var(--transition);
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.25);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        /* Bootstrap Primary Color Overrides - Orange Theme */
        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            box-shadow: 0 12px 35px rgba(255, 107, 53, 0.6);
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .border-primary {
            border-color: var(--primary-color) !important;
        }
        
        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .card-header.bg-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        /* Additional Bootstrap Component Overrides */
        .alert-primary {
            background-color: rgba(255, 107, 53, 0.1) !important;
            border-color: rgba(255, 107, 53, 0.2) !important;
            color: var(--primary-dark) !important;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }
        
        .page-link {
            color: var(--primary-color) !important;
        }
        
        .page-link:hover {
            color: var(--primary-dark) !important;
            background-color: rgba(255, 107, 53, 0.1) !important;
        }
        
        .progress-bar.bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25) !important;
        }
        
        .btn-success {
            background: var(--success-color);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }
        
        .btn-success:hover {
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.6);
        }
        
        .btn-warning {
            background: var(--secondary-color);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }
        
        .btn-danger {
            background: var(--danger-gradient);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid transparent;
            background: white;
            border: 2px solid var(--primary-color);
            color: #6366f1;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Button sizes */
        .btn-lg {
            padding: 16px 40px;
            font-size: 1.1rem;
            border-radius: var(--border-radius-lg);
        }
        
        .btn-sm {
            padding: 8px 20px;
            font-size: 0.85rem;
            border-radius: var(--border-radius);
        }
        
        /* Footer Styles */
        footer { 
            background: var(--dark-gradient);
            color: #cbd5e1; 
            padding: 4rem 0 2rem; 
            margin-top: 6rem;
            position: relative;
            overflow: hidden;
        }
        
        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--primary-color);
        }
        
        footer::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 107, 53, 0.05);
            pointer-events: none;
        }
        
        footer > * {
            position: relative;
            z-index: 1;
        }
        
        footer h5 {
            color: #f8fafc;
            margin-bottom: 1.5rem;
            font-weight: 700;
            font-size: 1.2rem;
            background: var(--primary-color);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        footer .list-unstyled {
            margin-bottom: 0;
        }
        
        footer .list-unstyled li {
            margin-bottom: 0.75rem;
        }
        
        footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0;
        }
        
        footer a:hover {
            color: #f8fafc;
            transform: translateX(5px);
            padding-left: 0.5rem;
        }
        
        footer a i {
            margin-right: 0.5rem;
            transition: var(--transition);
        }
        
        footer a:hover i {
            color: #6366f1;
            transform: scale(1.2);
        }
        
        /* Social links styling */
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            margin-right: 1rem;
            transition: var(--transition);
        }
        
        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        }
        
        /* Payment methods styling */
        .payment-methods {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-methods i {
            font-size: 1.5rem;
            color: #64748b;
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            background: rgba(255, 255, 255, 0.05);
        }
        
        .payment-methods i:hover {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.2);
        }
        
        /* Footer bottom section */
        footer hr {
            border-color: rgba(148, 163, 184, 0.2);
            margin: 2rem 0 1.5rem;
        }
        
        footer .row:last-child {
            padding-top: 1rem;
        }
        
        /* Content Container */
        .main-content {
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }
        
        /* Page transitions */
        .page-enter {
            animation: pageEnter 0.6s ease-out;
        }
        
        @keyframes pageEnter {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Utility Classes */
        .text-gradient {
            background: var(--primary-color);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .text-gradient-secondary {
            background: var(--secondary-color);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .shadow-custom {
            box-shadow: var(--card-shadow);
        }
        
        .shadow-custom-hover {
            transition: var(--transition);
        }
        
        .shadow-custom-hover:hover {
            box-shadow: var(--card-shadow-hover);
        }
        
        /* Loading spinner */
        .spinner-custom {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(99, 102, 241, 0.1);
            border-left: 4px solid #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Search form enhancements */
        .search-form .input-group {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .search-form .form-control {
            border: none;
            padding: 0.75rem 1.25rem;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .search-form .form-control:focus {
            box-shadow: none;
            background: rgba(255, 255, 255, 1);
        }
        
        .search-form .btn {
            border-radius: 0;
            border: none;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
        }
        
        /* Breadcrumb styling */
        .breadcrumb {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: var(--backdrop-blur);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "→";
            color: #6366f1;
            font-weight: bold;
        }
        
        .breadcrumb-item a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .breadcrumb-item a:hover {
            color: #4f46e5;
            transform: translateX(2px);
        }
        
        .breadcrumb-item.active {
            color: #64748b;
            font-weight: 600;
        }
        
        /* Form enhancements */
        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            background: rgba(255, 255, 255, 1);
        }
        
        /* Badge enhancements */
        .badge {
            border-radius: var(--border-radius);
            font-weight: 600;
            padding: 0.5em 0.75em;
        }
        
        /* Alert enhancements */
        .alert {
            border-radius: var(--border-radius);
            border: none;
            backdrop-filter: var(--backdrop-blur);
            box-shadow: var(--card-shadow);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.1));
            color: #1e3a8a;
            border-left: 4px solid #3b82f6;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
            color: #78350f;
            border-left: 4px solid #f59e0b;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section .lead {
                font-size: 1.2rem;
            }
            
            .hero-section {
                padding: 3rem 1rem;
            }
            
            .navbar-brand {
                font-size: 1.4rem;
            }
            
            .btn {
                padding: 10px 24px;
                font-size: 0.9rem;
            }
            
            .btn-lg {
                padding: 12px 32px;
                font-size: 1rem;
            }
            
            .payment-methods {
                justify-content: center;
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section {
                padding: 2rem 1rem;
            }
            
            .card-custom:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            .social-links {
                text-align: center;
                margin-top: 1rem;
            }
        }
        
        /* Custom page styles */
        <?php echo $customCSS; ?>
    </style>
</head>
<body>
<?php
    }

    /**
     * Generate consistent navigation bar
     * @param string $activePage Current page identifier for highlighting
     */
    public function navbar($activePage = '') {
        global $conf;
?>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-color);">
        <div class="container">
            <a class="navbar-brand" href="Index.php">
                <i class="fas fa-shopping-bag me-2"></i><?php echo htmlspecialchars($conf['site_name']); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activePage === 'products' ? 'active' : ''; ?>" href="products.php">
                            <i class="fas fa-th-grid me-1"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activePage === 'cart' ? 'active' : ''; ?>" href="cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>Cart
                            <?php 
                            // Show cart count if user is logged in
                            if (isset($_SESSION['user_id'])) {
                                $cartCount = $this->getCartCount();
                                if ($cartCount > 0): ?>
                                <span class="badge bg-light text-dark ms-1"><?php echo $cartCount; ?></span>
                            <?php endif; 
                            } ?>
                        </a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['2fa_verified'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activePage === 'orders' ? 'active' : ''; ?>" href="orders.php">
                            <i class="fas fa-list me-1"></i>My Orders
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Search Form (for product pages) -->
                <?php if ($activePage === 'products' || $activePage === 'home'): ?>
                <form class="d-flex me-3 search-form" method="GET" action="products.php">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search products, brands, categories..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               autocomplete="off">
                        <button class="btn btn-primary" type="submit" title="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- User Account Menu -->
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['2fa_verified'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Account'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="addresses.php"><i class="fas fa-map-marker-alt me-2"></i>My Addresses</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-list me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])): ?>
                            <li><a class="dropdown-item" href="admin/orders.php"><i class="fas fa-shopping-cart me-2"></i>Order Management</a></li>
                            <li><a class="dropdown-item" href="admin/users.php"><i class="fas fa-users-cog me-2"></i>User Management</a></li>
                            <li><a class="dropdown-item" href="admin/mpesa_simple.php"><i class="fas fa-mobile-alt me-2" style="color: #00D4AA;"></i>M-Pesa Transactions</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activePage === 'signin' ? 'active' : ''; ?>" href="Signin.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Sign In
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activePage === 'signup' ? 'active' : ''; ?>" href="Signup.php">
                            <i class="fas fa-user-plus me-1"></i>Sign Up
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main class="main-content">
        <div class="container">
<?php
    }

    /**
     * Generate hero/banner section
     * @param array $conf Site configuration
     * @param string $title Custom title
     * @param string $subtitle Custom subtitle
     * @param string $ctaText Call-to-action button text
     * @param string $ctaLink Call-to-action button link
     * @param array $features Optional array of features to highlight
     */
    public function banner($conf, $title = '', $subtitle = '', $ctaText = 'Start Shopping', $ctaLink = 'products.php', $features = []) {
        $defaultTitle = 'Welcome to ' . $conf['site_name'];
        $defaultSubtitle = 'Your smart shopping destination for quality products at unbeatable prices. Fast delivery across Kenya.';
        
        if (empty($features)) {
            $features = [
                ['icon' => 'fas fa-shipping-fast', 'text' => 'Free Delivery'],
                ['icon' => 'fas fa-shield-alt', 'text' => 'Secure Payment'], 
                ['icon' => 'fas fa-undo', 'text' => 'Easy Returns'],
                ['icon' => 'fas fa-headset', 'text' => '24/7 Support']
            ];
        }
?>
            <section class="hero-section">
                <div class="hero-content">
                    <h1><?php echo htmlspecialchars($title ?: $defaultTitle); ?></h1>
                    <p class="lead"><?php echo htmlspecialchars($subtitle ?: $defaultSubtitle); ?></p>
                    
                    <!-- Feature highlights -->
                    <div class="hero-features d-flex flex-wrap justify-content-center gap-4 mb-4">
                        <?php foreach ($features as $feature): ?>
                        <div class="hero-feature d-flex align-items-center">
                            <i class="<?php echo htmlspecialchars($feature['icon']); ?> me-2 fs-5"></i>
                            <span><?php echo htmlspecialchars($feature['text']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="hero-actions">
                        <a href="<?php echo htmlspecialchars($ctaLink); ?>" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-shopping-bag me-2"></i><?php echo htmlspecialchars($ctaText); ?>
                        </a>
                        <a href="products.php?featured=1" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-star me-2"></i>Featured Products
                        </a>
                    </div>
                </div>
                
                <!-- Floating elements for visual appeal -->
                <div class="hero-floating-elements">
                    <div class="floating-icon floating-icon-1">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="floating-icon floating-icon-2">
                        <i class="fas fa-lightning-bolt"></i>
                    </div>
                    <div class="floating-icon floating-icon-3">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
            </section>
            
            <style>
                .hero-features {
                    margin: 2rem 0;
                }
                
                .hero-feature {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 0.75rem 1.5rem;
                    border-radius: 25px;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                    font-weight: 500;
                    transition: var(--transition);
                }
                
                .hero-feature:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: translateY(-2px);
                }
                
                .hero-actions {
                    margin-top: 2rem;
                }
                
                .hero-floating-elements {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    pointer-events: none;
                    overflow: hidden;
                }
                
                .floating-icon {
                    position: absolute;
                    font-size: 2rem;
                    color: rgba(255, 255, 255, 0.1);
                    animation: float 6s ease-in-out infinite;
                }
                
                .floating-icon-1 {
                    top: 20%;
                    left: 10%;
                    animation-delay: 0s;
                }
                
                .floating-icon-2 {
                    top: 60%;
                    right: 15%;
                    animation-delay: 2s;
                }
                
                .floating-icon-3 {
                    bottom: 30%;
                    left: 20%;
                    animation-delay: 4s;
                }
                
                @keyframes float {
                    0%, 100% {
                        transform: translateY(0px) rotate(0deg);
                    }
                    33% {
                        transform: translateY(-20px) rotate(120deg);
                    }
                    66% {
                        transform: translateY(20px) rotate(240deg);
                    }
                }
                
                @media (max-width: 768px) {
                    .hero-features {
                        gap: 0.5rem !important;
                    }
                    
                    .hero-feature {
                        padding: 0.5rem 1rem;
                        font-size: 0.9rem;
                    }
                    
                    .hero-actions .btn {
                        display: block;
                        width: 100%;
                        margin: 0 0 1rem 0 !important;
                    }
                    
                    .floating-icon {
                        display: none;
                    }
                }
            </style>
<?php
    }

    /**
     * Generate breadcrumb navigation
     * @param array $breadcrumbs Array of ['title' => 'Title', 'url' => 'url'] or just 'Title' for active
     */
    public function breadcrumb($breadcrumbs = []) {
        if (empty($breadcrumbs)) return;
?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="Index.php">Home</a>
                    </li>
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <?php if (is_array($crumb) && isset($crumb['url'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo htmlspecialchars($crumb['url']); ?>">
                                    <?php echo htmlspecialchars($crumb['title']); ?>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars(is_array($crumb) ? $crumb['title'] : $crumb); ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>
<?php
    }

    /**
     * Start content wrapper
     */
    public function contentStart() {
?>
            <div class="content-wrapper">
<?php
    }

    /**
     * End content wrapper
     */
    public function contentEnd() {
?>
            </div>
<?php
    }

    /**
     * Generate consistent footer
     */
    public function footer() {
        global $conf;
?>
        </div> <!-- Close container -->
    </main> <!-- Close main content -->
    
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-shopping-bag me-2"></i><?php echo htmlspecialchars($conf['site_name']); ?></h5>
                    <p class="mb-3">Your premier online shopping destination in Kenya. Quality products, secure checkout, and fast delivery across the country.</p>
                    <div class="social-links">
                        <a href="#" title="Follow us on Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Follow us on Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="Follow us on Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Connect on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" title="Subscribe to our YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="mt-3">
                        <p class="mb-1"><i class="fas fa-phone me-2"></i>+254 795 550 352</p>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i>support@<?php echo strtolower($conf['site_name']); ?>.co.ke</p>
                        <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Nairobi, Kenya</p>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5><i class="fas fa-store me-2"></i>Shop</h5>
                    <ul class="list-unstyled">
                        <li><a href="products.php"><i class="fas fa-th-grid me-2"></i>All Products</a></li>
                        <li><a href="products.php?category=1"><i class="fas fa-laptop me-2"></i>Electronics</a></li>
                        <li><a href="products.php?category=2"><i class="fas fa-tshirt me-2"></i>Fashion</a></li>
                        <li><a href="products.php?category=3"><i class="fas fa-home me-2"></i>Home & Living</a></li>
                        <li><a href="products.php?category=4"><i class="fas fa-dumbbell me-2"></i>Sports</a></li>
                        <li><a href="products.php?featured=1"><i class="fas fa-star me-2"></i>Featured</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5><i class="fas fa-user-circle me-2"></i>Account</h5>
                    <ul class="list-unstyled">
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</a></li>
                        <li><a href="profile.php"><i class="fas fa-user-edit me-2"></i>My Profile</a></li>
                        <li><a href="addresses.php"><i class="fas fa-map-marker-alt me-2"></i>My Addresses</a></li>
                        <li><a href="orders.php"><i class="fas fa-list me-2"></i>My Orders</a></li>
                        <li><a href="cart.php"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        <?php else: ?>
                        <li><a href="Signin.php"><i class="fas fa-sign-in-alt me-2"></i>Sign In</a></li>
                        <li><a href="Signup.php"><i class="fas fa-user-plus me-2"></i>Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5><i class="fas fa-headset me-2"></i>Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#contact"><i class="fas fa-envelope me-2"></i>Contact Us</a></li>
                        <li><a href="#faq"><i class="fas fa-question-circle me-2"></i>FAQ</a></li>
                        <li><a href="#shipping"><i class="fas fa-shipping-fast me-2"></i>Shipping Info</a></li>
                        <li><a href="#returns"><i class="fas fa-undo me-2"></i>Returns</a></li>
                        <li><a href="#warranty"><i class="fas fa-shield-alt me-2"></i>Warranty</a></li>
                        <li><a href="#track"><i class="fas fa-search-location me-2"></i>Track Order</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5><i class="fas fa-building me-2"></i>Company</h5>
                    <ul class="list-unstyled">
                        <li><a href="#about"><i class="fas fa-info-circle me-2"></i>About Us</a></li>
                        <li><a href="#careers"><i class="fas fa-briefcase me-2"></i>Careers</a></li>
                        <li><a href="#news"><i class="fas fa-newspaper me-2"></i>News</a></li>
                        <li><a href="#investors"><i class="fas fa-chart-line me-2"></i>Investors</a></li>
                        <li><a href="#privacy"><i class="fas fa-user-shield me-2"></i>Privacy Policy</a></li>
                        <li><a href="#terms"><i class="fas fa-file-contract me-2"></i>Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Newsletter Signup -->
            <div class="row mt-4">
                <div class="col-lg-6">
                    <h5><i class="fas fa-envelope-open me-2"></i>Stay Updated</h5>
                    <p class="mb-3">Subscribe to get special offers, free giveaways, and updates on new products.</p>
                    <form class="d-flex" action="#newsletter" method="POST">
                        <input type="email" class="form-control me-2" placeholder="Enter your email" required>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane me-1"></i>Subscribe
                        </button>
                    </form>
                </div>
                <div class="col-lg-6">
                    <h5><i class="fas fa-download me-2"></i>Download Our App</h5>
                    <p class="mb-3">Shop on the go with our mobile app. Available on all platforms.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-light btn-sm">
                            <i class="fab fa-apple me-1"></i>App Store
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm">
                            <i class="fab fa-google-play me-1"></i>Play Store
                        </a>
                    </div>
                </div>
            </div>
            
            <hr class="border-secondary mt-4">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-2 mb-md-0">
                        &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($conf['site_name']); ?>. All rights reserved.
                        <br class="d-md-none">
                        <small class="text-muted">Made with <i class="fas fa-heart text-danger"></i> in Kenya</small>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-2"><small class="text-muted">We Accept:</small></p>
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa" title="Visa" data-bs-toggle="tooltip"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard" data-bs-toggle="tooltip"></i>
                        <i class="fas fa-mobile-alt text-success" title="M-Pesa" data-bs-toggle="tooltip"></i>
                        <i class="fas fa-money-bill-wave text-warning" title="Cash on Delivery" data-bs-toggle="tooltip"></i>
                        <i class="fab fa-paypal" title="PayPal" data-bs-toggle="tooltip"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Global JavaScript Functions -->
    <script>
        // Enhanced notification system
        function showNotification(message, type = 'info', duration = 5000) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notif => notif.remove());
            
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed custom-notification`;
            notification.style.cssText = `
                top: 20px; 
                right: 20px; 
                z-index: 9999; 
                min-width: 320px; 
                max-width: 400px;
                border-radius: 16px;
                backdrop-filter: blur(20px);
                border: none;
                box-shadow: 0 12px 40px rgba(0,0,0,0.15);
                animation: slideInRight 0.4s ease-out;
            `;
            
            const icons = {
                success: 'fas fa-check-circle',
                danger: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="${icons[type] || icons.info} me-2 fs-5"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-dismiss
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOutRight 0.4s ease-in forwards';
                    setTimeout(() => notification.remove(), 400);
                }
            }, duration);
        }
        
        // Enhanced cart count update with animation
        function updateCartCount() {
            fetch('cart_ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_totals'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartBadge = document.querySelector('.navbar .badge');
                    if (cartBadge) {
                        const newCount = data.cart_count || 0;
                        if (newCount !== parseInt(cartBadge.textContent)) {
                            cartBadge.style.animation = 'bounce 0.6s ease-in-out';
                            setTimeout(() => cartBadge.style.animation = '', 600);
                        }
                        cartBadge.textContent = newCount;
                        cartBadge.style.display = newCount > 0 ? 'inline' : 'none';
                    }
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Smooth scroll for anchor links
        function initSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
        
        // Loading state for buttons
        function setButtonLoading(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            } else {
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
            }
        }
        
        // Form validation enhancement
        function enhanceFormValidation() {
            const forms = document.querySelectorAll('form[data-validate="true"]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        if (!submitBtn.getAttribute('data-original-text')) {
                            submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
                        }
                        setButtonLoading(submitBtn, true);
                    }
                });
            });
        }
        
        // Search functionality enhancement
        function enhanceSearch() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    if (query.length >= 2) {
                        searchTimeout = setTimeout(() => {
                            // Add search suggestions here if needed
                            console.log('Searching for:', query);
                        }, 300);
                    }
                });
            }
        }
        
        // Lazy loading for images
        function initLazyLoading() {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        }
        
        // Back to top button
        function initBackToTop() {
            const backToTopBtn = document.createElement('button');
            backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            backToTopBtn.className = 'btn btn-primary position-fixed';
            backToTopBtn.id = 'backToTop';
            backToTopBtn.style.cssText = `
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: none;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            `;
            
            document.body.appendChild(backToTopBtn);
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopBtn.style.display = 'block';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            });
            
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
        
        // Initialize all features when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize all enhancements
            initSmoothScroll();
            enhanceFormValidation();
            enhanceSearch();
            initLazyLoading();
            initBackToTop();
            
            // Add page transition class
            document.body.classList.add('page-enter');
            
            // Update cart count on page load
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
        });
        
        // Add custom CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .lazy {
                filter: blur(5px);
                transition: filter 0.3s;
            }
            
            .lazy.loaded {
                filter: blur(0);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php
    }

    /**
     * Helper method to get base URL
     */
    private function getBaseUrl() {
        $baseUrl = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) ? '/' : '';
        return $baseUrl;
    }

    /**
     * Helper method to get cart count
     */
    private function getCartCount() {
        if (!isset($_SESSION['user_id'])) return 0;
        
        try {
            global $conf;
            $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            
            $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch();
            
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Legacy compatibility methods for existing forms
     */
    public function form_content($conf, $ObjForm) {
        echo '<div class="row g-4 mt-4">';
        echo '<div class="col-md-6">';
        echo '<div class="card card-custom p-4">';
        
        if (basename($_SERVER['PHP_SELF']) == 'Signup.php') {
            $ObjForm->signup($conf);
        } else {
            $ObjForm->signin($conf);
        }
        
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-6">';
        echo '<div class="card card-custom p-4 text-center">';
        echo '<h2 class="fw-bold">Why Shop With Us?</h2>';
        echo '<p>Enjoy exclusive discounts, secure checkout, and fast delivery right to your door.</p>';
        echo '<a href="products.php" class="btn btn-success">Shop Now</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Legacy content method
     */
    public function content($conf) {
        echo '<div class="row g-4 mt-4">';
        echo '<div class="col-md-4">';
        echo '<div class="card card-custom p-4 text-center h-100">';
        echo '<h2 class="fw-bold">Electronics</h2>';
        echo '<p>Shop the latest gadgets, smartphones, and accessories at unbeatable prices.</p>';
        echo '<a href="products.php?category=1" class="btn btn-primary">Shop Now</a>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-4">';
        echo '<div class="card card-custom p-4 text-center h-100">';
        echo '<h2 class="fw-bold">Fashion</h2>';
        echo '<p>Upgrade your wardrobe with trendy clothing, shoes, and accessories.</p>';
        echo '<a href="products.php?category=2" class="btn btn-outline-primary">Explore</a>';
        echo '</div>';
        echo '</div>';
        echo '<div class="col-md-4">';
        echo '<div class="card card-custom p-4 text-center h-100">';
        echo '<h2 class="fw-bold">Home & Living</h2>';
        echo '<p>Find essentials and decor to make your home stylish and comfortable.</p>';
        echo '<a href="products.php?category=3" class="btn btn-success">Browse</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}
