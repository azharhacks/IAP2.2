<?php
/**
 * Simple Order Confirmation - SMARTDUKA
 * Database-free order confirmation page
 */

session_start();

// Get order from URL
$order_number = $_GET['order'] ?? 'ORD-' . date('Ymd') . '-0016';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - SMARTDUKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .confirmation-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .confirmation-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 3rem 2rem 2rem 2rem;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0, -30px, 0);
            }
            70% {
                transform: translate3d(0, -15px, 0);
            }
            90% {
                transform: translate3d(0, -4px, 0);
            }
        }
        
        .confirmation-body {
            padding: 2rem;
        }
        
        .order-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        
        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary-action {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-secondary-action {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            color: white;
        }
        
        .timeline {
            margin: 2rem 0;
            text-align: left;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .timeline-icon {
            background: #28a745;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .timeline-content {
            flex: 1;
        }
        
        .timeline-title {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .timeline-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Payment Successful!</h1>
                <p class="mb-0">Your order has been confirmed and is being processed</p>
            </div>
            
            <div class="confirmation-body">
                <h3>Order Confirmation</h3>
                <p class="text-muted">Thank you for your purchase from SMARTDUKA!</p>
                
                <!-- Order Details -->
                <div class="order-details">
                    <div class="detail-row">
                        <span><i class="fas fa-hashtag me-2"></i>Order Number:</span>
                        <span><?php echo htmlspecialchars($order_number); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-calendar me-2"></i>Order Date:</span>
                        <span><?php echo date('M j, Y \a\t g:i A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-box me-2"></i>Items:</span>
                        <span>Sample Candy Bar (1x)</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-credit-card me-2"></i>Payment Method:</span>
                        <span>M-Pesa</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fas fa-truck me-2"></i>Delivery:</span>
                        <span>Standard Shipping</span>
                    </div>
                    <div class="detail-row">
                        <span>Total Amount:</span>
                        <span class="text-success">KSh 511.60</span>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div class="timeline">
                    <h5>What happens next?</h5>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Order Confirmed</div>
                            <div class="timeline-desc">Your payment has been received and order is confirmed</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Processing (1-2 business days)</div>
                            <div class="timeline-desc">We're preparing your items for shipment</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Shipped (2-5 business days)</div>
                            <div class="timeline-desc">Your order is on its way to you</div>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title">Delivered</div>
                            <div class="timeline-desc">Enjoy your SMARTDUKA purchase!</div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="orders.php" class="btn-action btn-primary-action">
                        <i class="fas fa-list me-2"></i>View My Orders
                    </a>
                    <a href="products.php" class="btn-action btn-secondary-action">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
                
                <!-- Contact Info -->
                <div style="margin-top: 2rem; padding: 1rem; background: #e7f3ff; border-radius: 10px;">
                    <p class="mb-1"><strong>Need help with your order?</strong></p>
                    <p class="mb-0">
                        <i class="fas fa-envelope me-2"></i>Email: support@smartduka.com<br>
                        <i class="fas fa-phone me-2"></i>Phone: +254 700 123 456
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some celebration effect
        document.addEventListener('DOMContentLoaded', function() {
            // Simple confetti effect (optional)
            setTimeout(() => {
                console.log('🎉 Order confirmed successfully!');
            }, 1000);
        });
    </script>
</body>
</html>