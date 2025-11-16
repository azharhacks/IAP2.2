<?php
/**
 * M-Pesa Transactions PDF Export - WORKING VERSION
 */

session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';

// Admin authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}

try {
    // Use existing PDO connection from config
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not available");
    }
    
    // Get M-Pesa transactions with order and user info
    $stmt = $pdo->prepare("
        SELECT mt.*, 
               o.order_number, o.total_amount as order_total,
               u.username, u.email
        FROM mpesa_transactions mt
        LEFT JOIN orders o ON mt.order_id = o.id
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY mt.created_at DESC
        LIMIT 1000
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as total_amount,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN amount END), 0) as avg_amount
        FROM mpesa_transactions");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$stats) {
        $stats = ['total_transactions' => 0, 'completed_transactions' => 0, 'pending_transactions' => 0, 'failed_transactions' => 0, 'total_amount' => 0, 'avg_amount' => 0];
    }
    
} catch (Exception $e) {
    error_log("M-Pesa PDF Export Error: " . $e->getMessage());
    exit('Database error: Unable to generate report. Please try again.');
}

// Create PDF
try {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SMARTDUKA Admin');
    $pdf->SetAuthor($conf['site_name']);
    $pdf->SetTitle('M-Pesa Transactions Export - ' . date('Y-m-d'));
    $pdf->SetSubject('M-Pesa Transactions Export');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();
    
    // Company Header
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(255, 107, 53); // SMARTDUKA Orange
    $pdf->Cell(0, 12, strtoupper($conf['site_name']), 0, 1, 'C');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(0, 212, 170); // M-Pesa Green
    $pdf->Cell(0, 8, 'M-Pesa Transactions Report', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // M-Pesa Header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(0, 212, 170);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'M-PESA PAYMENT TRANSACTIONS', 0, 1, 'C', 1);
    $pdf->Ln(8);
    
    // Summary Statistics
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(51, 51, 51);
    $pdf->Cell(0, 10, 'SUMMARY STATISTICS', 0, 1, 'L', 1);
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    // Statistics layout in two columns
    $leftX = 15;
    $rightX = 105;
    $y = $pdf->GetY();
    
    // Left column
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, 8, 'Total Transactions:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(255, 107, 53);
    $pdf->Cell(40, 8, number_format($stats['total_transactions']), 0, 1, 'R');
    
    $pdf->SetX($leftX);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 8, 'Completed:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(40, 167, 69);
    $pdf->Cell(40, 8, number_format($stats['completed_transactions']), 0, 1, 'R');
    
    $pdf->SetX($leftX);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 8, 'Pending:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(255, 193, 7);
    $pdf->Cell(40, 8, number_format($stats['pending_transactions']), 0, 1, 'R');
    
    // Right column
    $pdf->SetXY($rightX, $y);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 8, 'Total Revenue:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(40, 167, 69);
    $pdf->Cell(40, 8, 'KSh ' . number_format($stats['total_amount'], 2), 0, 1, 'R');
    
    $pdf->SetX($rightX);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 8, 'Average Transaction:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->Cell(40, 8, 'KSh ' . number_format($stats['avg_amount'], 2), 0, 1, 'R');
    
    $pdf->SetX($rightX);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 8, 'Report Generated:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(255, 107, 53);
    $pdf->Cell(40, 8, date('M j, Y H:i'), 0, 1, 'R');
    
    $pdf->Ln(10);
    
    // Transactions Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(0, 212, 170);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'DETAILED TRANSACTIONS', 0, 1, 'L', 1);
    $pdf->Ln(3);
    
    if (!empty($transactions)) {
        // Table headers
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetTextColor(73, 80, 87);
        
        $pdf->Cell(28, 8, 'Receipt Number', 1, 0, 'C', 1);
        $pdf->Cell(22, 8, 'Phone', 1, 0, 'C', 1);
        $pdf->Cell(22, 8, 'Amount (KSh)', 1, 0, 'C', 1);
        $pdf->Cell(18, 8, 'Status', 1, 0, 'C', 1);
        $pdf->Cell(30, 8, 'Customer', 1, 0, 'C', 1);
        $pdf->Cell(28, 8, 'Order Number', 1, 0, 'C', 1);
        $pdf->Cell(32, 8, 'Date & Time', 1, 1, 'C', 1);
        
        $pdf->SetFont('helvetica', '', 7);
        $rowCount = 0;
        
        foreach ($transactions as $transaction) {
            // Alternate row colors
            $pdf->SetFillColor($rowCount % 2 === 0 ? 255 : 248, $rowCount % 2 === 0 ? 255 : 249, $rowCount % 2 === 0 ? 255 : 250);
            
            // Check for new page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repeat headers
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(248, 249, 250);
                $pdf->SetTextColor(73, 80, 87);
                $pdf->Cell(28, 8, 'Receipt Number', 1, 0, 'C', 1);
                $pdf->Cell(22, 8, 'Phone', 1, 0, 'C', 1);
                $pdf->Cell(22, 8, 'Amount (KSh)', 1, 0, 'C', 1);
                $pdf->Cell(18, 8, 'Status', 1, 0, 'C', 1);
                $pdf->Cell(30, 8, 'Customer', 1, 0, 'C', 1);
                $pdf->Cell(28, 8, 'Order Number', 1, 0, 'C', 1);
                $pdf->Cell(32, 8, 'Date & Time', 1, 1, 'C', 1);
                $pdf->SetFont('helvetica', '', 7);
            }
            
            // Status colors
            switch ($transaction['status']) {
                case 'completed':
                    $pdf->SetTextColor(40, 167, 69);
                    break;
                case 'pending':
                    $pdf->SetTextColor(255, 193, 7);
                    break;
                case 'failed':
                    $pdf->SetTextColor(220, 53, 69);
                    break;
                default:
                    $pdf->SetTextColor(108, 117, 125);
            }
            
            // Data cells
            $receiptNo = $transaction['mpesa_receipt_number'] ?? 'N/A';
            $pdf->Cell(28, 6, substr($receiptNo, 0, 13), 1, 0, 'L', 1);
            
            $phone = $transaction['phone_number'] ?? '';
            $formattedPhone = $phone ? '+254' . substr($phone, -9) : 'N/A';
            $pdf->Cell(22, 6, $formattedPhone, 1, 0, 'C', 1);
            
            $pdf->Cell(22, 6, number_format($transaction['amount'], 2), 1, 0, 'R', 1);
            $pdf->Cell(18, 6, ucfirst($transaction['status']), 1, 0, 'C', 1);
            
            $pdf->SetTextColor(0, 0, 0);
            $customer = $transaction['username'] ?? 'Unknown';
            $pdf->Cell(30, 6, substr($customer, 0, 18), 1, 0, 'L', 1);
            
            $orderNo = $transaction['order_number'] ?? 'N/A';
            $pdf->Cell(28, 6, substr($orderNo, 0, 15), 1, 0, 'L', 1);
            
            $pdf->SetFont('helvetica', '', 6);
            $dateTime = date('M j, Y H:i', strtotime($transaction['created_at']));
            $pdf->Cell(32, 6, $dateTime, 1, 1, 'C', 1);
            $pdf->SetFont('helvetica', '', 7);
            
            $rowCount++;
        }
    } else {
        $pdf->SetFillColor(253, 236, 234);
        $pdf->SetTextColor(114, 28, 36);
        $pdf->Cell(0, 10, 'No M-Pesa transactions found in the system.', 1, 1, 'C', 1);
    }
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(8);
    
    // Footer
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Cell(0, 8, 'Generated on ' . date('F j, Y \a\t g:i A T') . ' by ' . $conf['site_name'] . ' Admin System', 0, 1, 'C', 1);
    $pdf->Cell(0, 6, 'This report contains confidential financial transaction data - Handle with care', 0, 1, 'C');
    
    // Output PDF
    $filename = 'SMARTDUKA_MPesa_Export_' . date('Y-m-d_H-i-s') . '.pdf';
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
    http_response_code(500);
    echo "Error generating PDF report: " . $e->getMessage();
}
?>
