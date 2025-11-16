<?php
/**
 * Payment Receipt PDF Generator
 * Generates PDF receipts for completed M-Pesa payments
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['2fa_verified'])) {
    http_response_code(403);
    exit('Access denied - Authentication required');
}

$transactionId = $_GET['transaction'] ?? '';
$userId = $_SESSION['user_id'];

if (!$transactionId) {
    exit('No transaction ID provided');
}

try {
    // Initialize database connection
    $dsn = "mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $conf['db_user'], $conf['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Get transaction details with order and user info
    $stmt = $pdo->prepare("
        SELECT mt.*, o.*, u.username, u.email
        FROM mpesa_transactions mt
        JOIN orders o ON mt.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE mt.checkout_request_id = ? AND o.user_id = ? AND mt.status = 'completed'
    ");
    $stmt->execute([$transactionId, $userId]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        exit('Transaction not found or access denied');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.description
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$transaction['order_id']]);
    $orderItems = $stmt->fetchAll();
    
} catch (Exception $e) {
    exit('Database error: ' . $e->getMessage());
}

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($conf['site_name']);
$pdf->SetTitle('Payment Receipt - ' . $transaction['mpesa_receipt_number']);
$pdf->SetSubject('Payment Receipt');

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
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(0, 212, 170); // M-Pesa green color
$pdf->Cell(0, 12, strtoupper($conf['site_name']), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'PAYMENT RECEIPT', 0, 1, 'C');
$pdf->Ln(8);

// Receipt Header with M-Pesa branding
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetFillColor(0, 212, 170);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'M-PESA PAYMENT CONFIRMATION', 0, 1, 'C', 1);
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Transaction Information Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 8, 'TRANSACTION DETAILS', 0, 1, 'L', 1);
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 10);

// Transaction details in two columns
$leftColumnX = 15;
$rightColumnX = 105;
$currentY = $pdf->GetY();

// Left column
$pdf->SetXY($leftColumnX, $currentY);
$pdf->Cell(45, 6, 'Transaction ID:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, $transaction['mpesa_receipt_number'], 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->SetX($leftColumnX);
$pdf->Cell(45, 6, 'Payment Method:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, 'M-Pesa', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$pdf->SetX($leftColumnX);
$pdf->Cell(45, 6, 'Phone Number:', 0, 0);
$pdf->Cell(50, 6, '+254' . $transaction['phone_number'], 0, 1);

$pdf->SetX($leftColumnX);
$pdf->Cell(45, 6, 'Payment Date:', 0, 0);
$pdf->Cell(50, 6, date('M j, Y g:i A', strtotime($transaction['created_at'])), 0, 1);

// Right column
$pdf->SetXY($rightColumnX, $currentY);
$pdf->Cell(40, 6, 'Order Number:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, $transaction['order_number'], 0, 1);

$pdf->SetX($rightColumnX);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Customer:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 6, $transaction['username'], 0, 1);

$pdf->SetX($rightColumnX);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(40, 6, 'Email:', 0, 0);
$pdf->Cell(50, 6, $transaction['email'], 0, 1);

$pdf->SetX($rightColumnX);
$pdf->Cell(40, 6, 'Amount Paid:', 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 150, 0);
$pdf->Cell(50, 6, 'KSh ' . number_format($transaction['amount'], 2), 0, 1);

$pdf->Ln(10);

// Order Items Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'ORDER ITEMS', 0, 1, 'L', 1);
$pdf->Ln(3);

if (!empty($orderItems)) {
    // Table headers
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(0, 212, 170);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(80, 8, 'Product', 1, 0, 'L', 1);
    $pdf->Cell(25, 8, 'Quantity', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', 1);
    $pdf->Cell(40, 8, 'Total', 1, 1, 'R', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    
    $subtotal = 0;
    foreach ($orderItems as $item) {
        $itemTotal = $item['unit_price'] * $item['quantity'];
        $subtotal += $itemTotal;
        
        $pdf->Cell(80, 6, substr($item['product_name'], 0, 35), 1, 0, 'L');
        $pdf->Cell(25, 6, $item['quantity'], 1, 0, 'C');
        $pdf->Cell(35, 6, 'KSh ' . number_format($item['unit_price'], 2), 1, 0, 'R');
        $pdf->Cell(40, 6, 'KSh ' . number_format($itemTotal, 2), 1, 1, 'R');
    }
    
    // Totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 8, 'Subtotal:', 1, 0, 'R');
    $pdf->Cell(40, 8, 'KSh ' . number_format($subtotal, 2), 1, 1, 'R');
    
    // Calculate tax (assuming 16% VAT)
    $taxAmount = $subtotal * 0.16;
    $pdf->Cell(140, 8, 'Tax (16% VAT):', 1, 0, 'R');
    $pdf->Cell(40, 8, 'KSh ' . number_format($taxAmount, 2), 1, 1, 'R');
    
    // Total paid
    $pdf->SetFillColor(0, 212, 170);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(140, 8, 'TOTAL PAID:', 1, 0, 'R', 1);
    $pdf->Cell(40, 8, 'KSh ' . number_format($transaction['amount'], 2), 1, 1, 'R', 1);
    
} else {
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 6, 'No items found in this order', 0, 1, 'C');
}

$pdf->Ln(10);

// Payment Status
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(40, 167, 69);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 10, 'PAYMENT STATUS: COMPLETED', 0, 1, 'C', 1);
$pdf->Ln(5);

// Shipping Address Section
if (!empty($transaction['shipping_address'])) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, 'SHIPPING ADDRESS', 0, 1, 'L', 1);
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 10);
    
    // Create a border around the address
    $pdf->SetFillColor(250, 250, 250);
    $addressLines = explode("\n", $transaction['shipping_address']);
    $lineHeight = 6;
    $addressHeight = count($addressLines) * $lineHeight + 4;
    
    $pdf->Rect($pdf->GetX(), $pdf->GetY(), 180, $addressHeight, 'F');
    $pdf->SetXY($pdf->GetX() + 5, $pdf->GetY() + 2);
    
    foreach ($addressLines as $line) {
        $pdf->Cell(0, $lineHeight, trim($line), 0, 1, 'L');
        $pdf->SetX($pdf->GetX() + 5);
    }
    
    $pdf->Ln(8);
}

// Important Notes
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(220, 53, 69);
$pdf->Cell(0, 8, 'IMPORTANT NOTES:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, '• This is an official payment receipt from ' . $conf['site_name'], 0, 1, 'L');
$pdf->Cell(0, 5, '• Keep this receipt for your records and warranty purposes', 0, 1, 'L');
$pdf->Cell(0, 5, '• Your order will be processed and shipped to the provided address', 0, 1, 'L');
$pdf->Cell(0, 5, '• For any queries, contact us at ' . $conf['admin_email'], 0, 1, 'L');

$pdf->Ln(8);

// Footer
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 5, 'Generated on ' . date('M j, Y g:i A') . ' | ' . $conf['site_name'] . ' - Secure M-Pesa Payment System', 0, 1, 'C');
$pdf->Cell(0, 5, 'This is a computer-generated receipt and does not require a signature.', 0, 1, 'C');

// QR Code for verification (optional)
$pdf->Ln(5);
$qrText = $conf['site_url'] . '/verify_receipt.php?tx=' . $transaction['mpesa_receipt_number'];
$pdf->SetFont('helvetica', '', 7);
$pdf->Cell(0, 4, 'Scan QR code to verify this receipt online:', 0, 1, 'C');

// Output PDF
$filename = 'Receipt_' . $transaction['mpesa_receipt_number'] . '_' . date('Y-m-d') . '.pdf';
$pdf->Output($filename, 'D'); // 'D' = download
?>
