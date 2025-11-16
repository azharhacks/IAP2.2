<?php
/**
 * M-Pesa Transactions CSV Export
 */

session_start();
require_once '../config.php';

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
        LIMIT 10000
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    $filename = 'SMARTDUKA_MPesa_Export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Headers
    fputcsv($output, [
        'Transaction ID',
        'Receipt Number',
        'Phone Number',
        'Amount (KSh)',
        'Status',
        'Order Number',
        'Order Total (KSh)',
        'Customer Name',
        'Customer Email',
        'Transaction Date',
        'Created At',
        'Updated At'
    ]);
    
    // Add transaction data
    foreach ($transactions as $transaction) {
        $phone = $transaction['phone_number'] ? '+254' . substr($transaction['phone_number'], -9) : 'N/A';
        
        fputcsv($output, [
            $transaction['id'],
            $transaction['mpesa_receipt_number'] ?? 'N/A',
            $phone,
            number_format($transaction['amount'], 2),
            ucfirst($transaction['status']),
            $transaction['order_number'] ?? 'N/A',
            $transaction['order_total'] ? number_format($transaction['order_total'], 2) : 'N/A',
            $transaction['username'] ?? 'Unknown',
            $transaction['email'] ?? 'N/A',
            $transaction['transaction_date'] ? date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])) : 'N/A',
            date('Y-m-d H:i:s', strtotime($transaction['created_at'])),
            date('Y-m-d H:i:s', strtotime($transaction['updated_at']))
        ]);
    }
    
    // Add summary statistics at the end
    fputcsv($output, []); // Empty row
    fputcsv($output, ['SUMMARY STATISTICS']);
    
    // Get stats
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as total_amount,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN amount END), 0) as avg_amount
        FROM mpesa_transactions");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    fputcsv($output, ['Total Transactions', $stats['total_transactions']]);
    fputcsv($output, ['Completed Transactions', $stats['completed_transactions']]);
    fputcsv($output, ['Pending Transactions', $stats['pending_transactions']]);
    fputcsv($output, ['Failed Transactions', $stats['failed_transactions']]);
    fputcsv($output, ['Total Revenue (KSh)', number_format($stats['total_amount'], 2)]);
    fputcsv($output, ['Average Transaction (KSh)', number_format($stats['avg_amount'], 2)]);
    fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("M-Pesa CSV Export Error: " . $e->getMessage());
    http_response_code(500);
    echo 'Error generating CSV export: ' . $e->getMessage();
}
?>