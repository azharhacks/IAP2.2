<?php
/**
 * Order PDF Export
 * Generates PDF for order details
 */

session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';

// Basic auth check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}

$orderId = (int)($_GET['id'] ?? 0);

if (!$orderId) {
    exit('No order ID provided');
}

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        exit('Order not found');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.description 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    exit('Database error: ' . $e->getMessage());
}

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($conf['site_name']);
$pdf->SetTitle('Order Details - ' . $order['order_number']);
$pdf->SetSubject('Order Details');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Company Header
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(255, 107, 53); // Orange color
$pdf->Cell(0, 10, strtoupper($conf['site_name']), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0); // Black color
$pdf->Cell(0, 5, 'Order Details Report', 0, 1, 'C');
$pdf->Ln(10);

// Order Information Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(255, 107, 53); // Orange background
$pdf->SetTextColor(255, 255, 255); // White text
$pdf->Cell(0, 8, 'ORDER INFORMATION', 0, 1, 'L', 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Order details in two columns
$leftColumnX = 15;
$rightColumnX = 105;
$currentY = $pdf->GetY();

// Left column
$pdf->SetXY($leftColumnX, $currentY);
$pdf->Cell(40, 6, 'Order Number:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, $order['order_number'] ?? 'N/A', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->SetX($leftColumnX);
$pdf->Cell(40, 6, 'Order Date:', 0, 0);
$pdf->Cell(50, 6, date('M j, Y g:i A', strtotime($order['created_at'])), 0, 1);

$pdf->SetX($leftColumnX);
$pdf->Cell(40, 6, 'Order Status:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, ucfirst($order['status']), 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->SetX($leftColumnX);
$pdf->Cell(40, 6, 'Payment Status:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, ucfirst($order['payment_status']), 0, 1);

// Right column
$pdf->SetXY($rightColumnX, $currentY);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Customer:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, $order['username'] ?? 'Unknown', 0, 1);

$pdf->SetX($rightColumnX);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Email:', 0, 0);
$pdf->Cell(50, 6, $order['email'] ?? 'N/A', 0, 1);

$pdf->SetX($rightColumnX);
$pdf->Cell(40, 6, 'Total Amount:', 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(50, 6, 'KSh ' . number_format($order['total_amount'], 2), 0, 1);

$pdf->Ln(10);

// Order Items Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(255, 107, 53);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'ORDER ITEMS', 0, 1, 'L', 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

if (!empty($orderItems)) {
    // Table headers
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(80, 8, 'Product', 1, 0, 'L', 1);
    $pdf->Cell(25, 8, 'Quantity', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', 1);
    $pdf->Cell(40, 8, 'Total', 1, 1, 'R', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(255, 255, 255);
    
    $subtotal = 0;
    foreach ($orderItems as $item) {
        $itemTotal = $item['unit_price'] * $item['quantity'];
        $subtotal += $itemTotal;
        
        $pdf->Cell(80, 6, substr($item['product_name'] ?? 'Unknown Product', 0, 35), 1, 0, 'L');
        $pdf->Cell(25, 6, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(35, 6, 'KSh ' . number_format($item['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(40, 6, 'KSh ' . number_format($itemTotal, 2), 1, 1, 'R');
    }
    
    // Subtotal
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 8, 'Subtotal:', 1, 0, 'R');
    $pdf->Cell(40, 8, 'KSh ' . number_format($subtotal, 2), 1, 1, 'R');
    
    // Total
    $pdf->SetFillColor(255, 107, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(140, 8, 'TOTAL AMOUNT:', 1, 0, 'R', 1);
    $pdf->Cell(40, 8, 'KSh ' . number_format($order['total_amount'], 2), 1, 1, 'R', 1);
    
} else {
    $pdf->Cell(0, 6, 'No items found in this order', 0, 1, 'C');
}

$pdf->Ln(10);

// Shipping Address Section
if (!empty($order['shipping_address'])) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(255, 107, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'SHIPPING ADDRESS', 0, 1, 'L', 1);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    // Create a border around the address
    $pdf->SetFillColor(245, 245, 245);
    $addressLines = explode("\n", $order['shipping_address']);
    $lineHeight = 6;
    $addressHeight = count($addressLines) * $lineHeight + 4;
    
    $pdf->Rect($pdf->GetX(), $pdf->GetY(), 180, $addressHeight, 'F');
    $pdf->SetXY($pdf->GetX() + 5, $pdf->GetY() + 2);
    
    foreach ($addressLines as $line) {
        $pdf->Cell(0, $lineHeight, trim($line), 0, 1, 'L');
        $pdf->SetX($pdf->GetX() + 5);
    }
    
    $pdf->Ln(5);
}

// Footer
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 5, 'Generated on ' . date('M j, Y g:i A') . ' by ' . $conf['site_name'], 0, 1, 'C');
$pdf->Cell(0, 5, 'This is a computer-generated document and does not require a signature.', 0, 1, 'C');

// Output PDF
$filename = 'Order_' . $order['order_number'] . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D'); // 'D' = download
?>
