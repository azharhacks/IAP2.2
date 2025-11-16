<?php
/**
 * Order Confirmation PDF Generator - SMARTDUKA
 * Generates PDF version of order confirmation
 */

session_start();
require_once __DIR__ . '/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    die('Access denied');
}

// Get order number from URL
$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) {
    die('No order number provided');
}

$userId = $_SESSION['user_id'];

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.username, u.email 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$orderNumber, $userId]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

// Get order items
$orderItems = [];
try {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $orderItems = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback
    $orderItems = [
        ['product_name' => 'Order Item', 'quantity' => 1, 'unit_price' => $order['total_amount']]
    ];
}

// Set headers for HTML display (browser will handle PDF conversion)
header('Content-Type: text/html; charset=UTF-8');

// Add query parameter to check if this is for PDF generation
$isPdfMode = isset($_GET['pdf']) && $_GET['pdf'] === '1';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation - <?php echo htmlspecialchars($orderNumber); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            margin: 0;
            padding: 20px;
            color: #333;
            background: white;
            line-height: 1.4;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            text-align: center;
            border-bottom: 3px solid #ff6b35;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #ff6b35;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .order-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        .order-info h2 {
            color: #ff6b35;
            margin: 0 0 15px 0;
            font-size: 18px;
            border-bottom: 1px solid #ff6b35;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .order-items th {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
        }
        .order-items td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .order-items tr:hover {
            background: rgba(255, 107, 53, 0.02);
        }
        .total-row {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            font-weight: bold;
            border-top: 2px solid #ff6b35;
        }
        .total-row td {
            padding: 15px 12px;
            font-size: 16px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #e9ecef;
            padding-top: 20px;
        }
        .status-paid {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background: #ffc107;
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Print Styles */
        @media print {
            body { 
                margin: 0;
                padding: 15px;
                font-size: 12px;
            }
            .no-print { display: none !important; }
            .order-items th {
                background: #ff6b35 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .page-break {
                page-break-before: always;
            }
        }
        
        /* Mobile Responsive */
        @media screen and (max-width: 600px) {
            .container { padding: 10px; }
            .info-grid { grid-template-columns: 1fr; }
            .order-items { font-size: 14px; }
            .order-items th, .order-items td { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">SMARTDUKA</div>
            <div class="subtitle">Order Confirmation & Receipt</div>
            <div style="color: #666; font-size: 14px; margin-top: 10px;">
                Strathmore University - Ole Sangale Road, Nairobi
            </div>
        </div>

        <!-- Order Information -->
        <div class="order-info">
            <h2>Order Details</h2>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <span class="info-label">Order Number:</span><br>
                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Date:</span><br>
                        <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Customer:</span><br>
                        <?php echo htmlspecialchars($order['username'] ?? 'Customer'); ?>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <span class="info-label">Payment Method:</span><br>
                        <?php echo ucfirst($order['payment_method']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Status:</span><br>
                        <span class="status-<?php echo $order['payment_status']; ?>">
                            <?php echo strtoupper($order['payment_status']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Amount:</span><br>
                        <strong style="font-size: 18px; color: #ff6b35;">KSh <?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Information -->
        <div class="order-info">
            <h2>Shipping & Delivery Information</h2>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <span class="info-label">Delivery Address:</span><br>
                        <?php
                        $shippingAddress = $order['shipping_address'] ?? 'Strathmore University, Ole sangale road, Nairobi, Kenya';
                        $city = $order['shipping_city'] ?? 'Nairobi';
                        $state = $order['shipping_state'] ?? 'Nairobi County';
                        $postal = $order['shipping_postal_code'] ?? '00200';
                        $country = $order['shipping_country'] ?? 'Kenya';
                        ?>
                        <?php echo htmlspecialchars($shippingAddress); ?><br>
                        <?php echo htmlspecialchars($city . ', ' . $state . ' ' . $postal); ?><br>
                        <?php echo htmlspecialchars($country); ?>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <span class="info-label">Estimated Delivery:</span><br>
                        3-5 business days
                    </div>
                    <div class="info-item">
                        <span class="info-label">Delivery Method:</span><br>
                        Standard Delivery
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div style="margin-bottom: 20px;">
            <h2 style="color: #ff6b35; margin-bottom: 15px;">Order Items</h2>
            <table class="order-items">
                <thead>
                    <tr>
                        <th style="width: 50%;">Product Description</th>
                        <th style="width: 15%; text-align: center;">Qty</th>
                        <th style="width: 17%; text-align: right;">Unit Price</th>
                        <th style="width: 18%; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">KSh <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right;"><strong>KSh <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <!-- Order Total -->
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTAL AMOUNT:</strong></td>
                        <td style="text-align: right;"><strong>KSh <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

    <!-- Payment Information -->
    <?php if ($order['payment_status'] === 'paid' && $order['payment_method'] === 'mpesa'): ?>
    <div class="order-info">
        <h2>Payment Information</h2>
        <p><strong>Payment Method:</strong> M-Pesa</p>
        <p><strong>Status:</strong> PAID</p>
        <p><strong>Amount:</strong> KSh <?php echo number_format($order['total_amount'], 2); ?></p>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for your order!</p>
        <p>SMARTDUKA - Strathmore University</p>
        <p>Generated on <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <!-- Print Instructions -->
    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <p><strong>To save as PDF:</strong> Press Ctrl+P and select "Save as PDF" as the destination.</p>
        <button onclick="window.print()" style="background: #ff6b35; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
            Print / Save as PDF
        </button>
    </div>

    <script>
        window.onload = function() {
            // Check if this is opened directly or via PDF request
            const urlParams = new URLSearchParams(window.location.search);
            const autoPrint = urlParams.get('auto_print');
            
            if (autoPrint === '1') {
                // Auto-trigger print dialog after a short delay
                setTimeout(function() {
                    window.print();
                }, 800);
            }
        };
        
        function printDocument() {
            window.print();
        }
        
        function downloadPDF() {
            // Add auto_print parameter and reload to trigger print
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('auto_print', '1');
            window.location.href = currentUrl.toString();
        }
    </script>
    </div> <!-- Close container -->
</body>
</html>
