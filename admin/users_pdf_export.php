<?php
/**
 * Users PDF Export
 * Generates PDF export for user management
 */

session_start();
require_once '../config.php';
require_once '../vendor/autoload.php';

// Basic auth check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Access denied - Admin privileges required');
}

try {
    $pdo = new PDO("mysql:host={$conf['db_host']};dbname={$conf['db_name']};charset=utf8", 
                   $conf['db_user'], $conf['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all users with statistics
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT o.id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_spent,
               MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as verified_users FROM users WHERE email_verified = 1");
    $verifiedUsers = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as admin_users FROM users WHERE role IN ('admin', 'super_admin')");
    $adminUsers = $stmt->fetchColumn();
    
} catch (Exception $e) {
    exit('Database error: ' . $e->getMessage());
}

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($conf['site_name']);
$pdf->SetTitle('Users Export - ' . date('Y-m-d'));
$pdf->SetSubject('Users Export');

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
$pdf->Cell(0, 5, 'Users Management Report', 0, 1, 'C');
$pdf->Ln(10);

// Summary Statistics Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(255, 107, 53); // Orange background
$pdf->SetTextColor(255, 255, 255); // White text
$pdf->Cell(0, 8, 'SUMMARY STATISTICS', 0, 1, 'L', 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Statistics in columns
$pdf->Cell(60, 6, 'Total Users:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, number_format($totalUsers), 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'Verified Users:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, number_format($verifiedUsers), 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'Admin Users:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, number_format($adminUsers), 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'Report Date:', 0, 0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 6, date('M j, Y g:i A'), 0, 1);

$pdf->Ln(10);

// Users List Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(255, 107, 53);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, 'USERS LIST', 0, 1, 'L', 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

if (!empty($users)) {
    // Table headers
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(20, 8, 'ID', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Username', 1, 0, 'L', 1);
    $pdf->Cell(50, 8, 'Email', 1, 0, 'L', 1);
    $pdf->Cell(20, 8, 'Role', 1, 0, 'C', 1);
    $pdf->Cell(15, 8, 'Verified', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'Orders', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Total Spent', 1, 1, 'R', 1);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($users as $user) {
        // Check if we need a new page
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            // Repeat headers
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(20, 8, 'ID', 1, 0, 'C', 1);
            $pdf->Cell(40, 8, 'Username', 1, 0, 'L', 1);
            $pdf->Cell(50, 8, 'Email', 1, 0, 'L', 1);
            $pdf->Cell(20, 8, 'Role', 1, 0, 'C', 1);
            $pdf->Cell(15, 8, 'Verified', 1, 0, 'C', 1);
            $pdf->Cell(20, 8, 'Orders', 1, 0, 'C', 1);
            $pdf->Cell(25, 8, 'Total Spent', 1, 1, 'R', 1);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $pdf->Cell(20, 6, $user['id'], 1, 0, 'C');
        $pdf->Cell(40, 6, substr($user['username'], 0, 18), 1, 0, 'L');
        $pdf->Cell(50, 6, substr($user['email'], 0, 25), 1, 0, 'L');
        $pdf->Cell(20, 6, ucfirst($user['role']), 1, 0, 'C');
        $pdf->Cell(15, 6, $user['email_verified'] ? 'Yes' : 'No', 1, 0, 'C');
        $pdf->Cell(20, 6, $user['total_orders'], 1, 0, 'C');
        $pdf->Cell(25, 6, 'KSh ' . number_format($user['total_spent']), 1, 1, 'R');
    }
    
} else {
    $pdf->Cell(0, 6, 'No users found', 0, 1, 'C');
}

$pdf->Ln(10);

// Role Distribution
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC");
$roleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($roleStats)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(255, 107, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'ROLE DISTRIBUTION', 0, 1, 'L', 1);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    foreach ($roleStats as $role) {
        $pdf->Cell(60, 6, ucfirst($role['role']) . ':', 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 6, number_format($role['count']) . ' users', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
    }
}

$pdf->Ln(10);

// Recent Registrations
$stmt = $pdo->query("
    SELECT username, email, role, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($recentUsers)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(255, 107, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'RECENT REGISTRATIONS (Last 10)', 0, 1, 'L', 1);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(40, 8, 'Username', 1, 0, 'L', 1);
    $pdf->Cell(60, 8, 'Email', 1, 0, 'L', 1);
    $pdf->Cell(25, 8, 'Role', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'Registration Date', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($recentUsers as $user) {
        $pdf->Cell(40, 6, substr($user['username'], 0, 18), 1, 0, 'L');
        $pdf->Cell(60, 6, substr($user['email'], 0, 30), 1, 0, 'L');
        $pdf->Cell(25, 6, ucfirst($user['role']), 1, 0, 'C');
        $pdf->Cell(35, 6, date('M j, Y', strtotime($user['created_at'])), 1, 1, 'C');
    }
}

// Footer
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 5, 'Generated on ' . date('M j, Y g:i A') . ' by ' . $conf['site_name'] . ' Admin Panel', 0, 1, 'C');
$pdf->Cell(0, 5, 'This is a confidential document containing user information.', 0, 1, 'C');

// Output PDF
$filename = 'Users_Export_' . date('Y-m-d_H-i-s') . '.pdf';
$pdf->Output($filename, 'D'); // 'D' = download
?>
