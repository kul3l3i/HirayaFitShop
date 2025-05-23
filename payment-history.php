<?php
// Start the session at the very beginning
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';


// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: sign-in.php");
    exit;
}

// Get admin information from the database
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT admin_id, username, fullname, email, profile_image, role FROM admins WHERE admin_id = ? AND is_active = TRUE");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Admin not found or not active, destroy session and redirect to login
    session_destroy();
    header("Location: sign-in.php");
    exit;
}

// Get admin details
$admin = $result->fetch_assoc();

// Set default role if not present
if (!isset($admin['role'])) {
    $admin['role'] = 'Administrator';
}

// Update last login time
$update_stmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE admin_id = ?");
$update_stmt->bind_param("i", $admin_id);
$update_stmt->execute();
$update_stmt->close();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: sign-in.php");
    exit;
}

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png"; // Default image path
    }
}

// Get profile image URL
$profileImageUrl = getProfileImageUrl($admin['profile_image']);

// Load transactions from XML file
$transactionsData = [];
if (file_exists('transaction.xml')) {
    $xml = simplexml_load_file('transaction.xml');
    
    foreach ($xml->transaction as $transaction) {
        $items = [];
        foreach ($transaction->items->item as $item) {
            $items[] = [
                'product_id' => (string)$item->product_id,
                'product_name' => (string)$item->product_name,
                'price' => (float)$item->price,
                'quantity' => (int)$item->quantity,
                'color' => (string)$item->color,
                'size' => (string)$item->size,
                'subtotal' => (float)$item->subtotal
            ];
        }
        
        $transactionsData[] = [
            'transaction_id' => (string)$transaction->transaction_id,
            'user_id' => (int)$transaction->user_id,
            'transaction_date' => (string)$transaction->transaction_date,
            'status' => (string)$transaction->status,
            'payment_method' => (string)$transaction->payment_method,
            'subtotal' => (float)$transaction->subtotal,
            'shipping_fee' => (float)$transaction->shipping_fee,
            'total_amount' => (float)$transaction->total_amount,
            'items' => $items,
            'shipping_info' => [
                'fullname' => (string)$transaction->shipping_info->fullname,
                'email' => (string)$transaction->shipping_info->email,
                'phone' => (string)$transaction->shipping_info->phone,
                'address' => (string)$transaction->shipping_info->address,
                'city' => (string)$transaction->shipping_info->city,
                'postal_code' => (string)$transaction->shipping_info->postal_code,
                'notes' => (string)$transaction->shipping_info->notes
            ]
        ];
    }
}

// Filter data based on search and filter options
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterPaymentMethod = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$filteredTransactions = $transactionsData;

// Apply filters
if (!empty($searchTerm)) {
    $filteredTransactions = array_filter($filteredTransactions, function($transaction) use ($searchTerm) {
        return (
            stripos($transaction['transaction_id'], $searchTerm) !== false ||
            stripos($transaction['shipping_info']['fullname'], $searchTerm) !== false ||
            stripos($transaction['shipping_info']['email'], $searchTerm) !== false
        );
    });
}

if (!empty($filterStatus)) {
    $filteredTransactions = array_filter($filteredTransactions, function($transaction) use ($filterStatus) {
        return $transaction['status'] === $filterStatus;
    });
}

if (!empty($filterPaymentMethod)) {
    $filteredTransactions = array_filter($filteredTransactions, function($transaction) use ($filterPaymentMethod) {
        return $transaction['payment_method'] === $filterPaymentMethod;
    });
}

if (!empty($startDate) && !empty($endDate)) {
    $startDateTime = strtotime($startDate . ' 00:00:00');
    $endDateTime = strtotime($endDate . ' 23:59:59');
    
    $filteredTransactions = array_filter($filteredTransactions, function($transaction) use ($startDateTime, $endDateTime) {
        $transactionTime = strtotime($transaction['transaction_date']);
        return ($transactionTime >= $startDateTime && $transactionTime <= $endDateTime);
    });
}

// Sort transactions by date (newest first)
usort($filteredTransactions, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Close the statement
$stmt->close();
?>
<?php
// Handle PDF export for HirayaFit Payment History
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Include TCPDF library
    require_once('TCPDF-main/TCPDF-main/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('HirayaFit System');
    $pdf->SetAuthor('HirayaFit Admin');
    $pdf->SetTitle('HirayaFit Payment History Report');
    $pdf->SetSubject('Payment History & Transaction Analytics');
    $pdf->SetKeywords('HirayaFit, Payment, Transaction, Report, E-commerce');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 20);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Custom Header with HirayaFit Branding
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->SetTextColor(41, 128, 185); // Professional blue
    $pdf->Cell(0, 15, 'HIRAYAFIT', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(108, 117, 125); // Gray
    $pdf->Cell(0, 8, 'FITNESS & ATHLETIC WEAR', 0, 1, 'C');
    
    // Add separator line
    $pdf->SetDrawColor(41, 128, 185);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, 45, 195, 45);
    
    $pdf->Ln(10);
    
    // Report Title
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(52, 58, 64); // Dark gray
    $pdf->Cell(0, 12, 'PAYMENT HISTORY REPORT', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(108, 117, 125);
    $pdf->Cell(0, 6, 'Generated on: ' . date('F j, Y \a\t g:i A'), 0, 1, 'C');
    
    $pdf->Ln(8);
    
    // Build CSS styles for HTML content
    $styles = "
    <style>
        .header-box {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .filter-section {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .filter-title {
            color: #495057;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 2px solid #2980b9;
            padding-bottom: 5px;
        }
        .filter-item {
            margin: 5px 0;
            font-size: 11px;
        }
        .summary-section {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-stats {
            display: table;
            width: 100%;
        }
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 10px;
            opacity: 0.9;
        }
        .table-header {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            color: white;
        }
        .table-header th {
            padding: 12px 8px;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .transaction-row {
            border-bottom: 1px solid #dee2e6;
        }
        .transaction-row:nth-child(even) {
            background-color: #f8f9fa;
        }
        .transaction-row td {
            padding: 10px 8px;
            font-size: 9px;
            vertical-align: middle;
        }
        .status-completed { color: #28a745; font-weight: bold; }
        .status-pending { color: #ffc107; font-weight: bold; }
        .status-cancelled { color: #dc3545; font-weight: bold; }
        .amount-cell {
            font-weight: bold;
            color: #2980b9;
            text-align: right;
        }
        .detail-section {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            margin: 15px 0;
            page-break-inside: avoid;
        }
        .detail-header {
            background: linear-gradient(135deg, #8e44ad, #9b59b6);
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            border-radius: 6px 6px 0 0;
        }
        .detail-content {
            padding: 15px;
        }
        .shipping-info {
            background-color: #f1f3f4;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .items-table th {
            background-color: #495057;
            color: white;
            padding: 8px 5px;
            font-size: 9px;
            text-align: center;
        }
        .items-table td {
            padding: 6px 5px;
            font-size: 8px;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
        }
        .total-section {
            background-color: #e8f5e8;
            border-radius: 5px;
            padding: 10px;
            margin-top: 15px;
            text-align: right;
        }
        .total-line {
            margin: 3px 0;
            font-size: 10px;
        }
        .grand-total {
            font-weight: bold;
            font-size: 12px;
            color: #2980b9;
            border-top: 2px solid #2980b9;
            padding-top: 5px;
            margin-top: 8px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>";
    
    // Start building HTML content
    $html = $styles;
    
    // Add filter information if any filters are applied
    if (!empty($searchTerm) || !empty($filterStatus) || !empty($filterPaymentMethod) || (!empty($startDate) && !empty($endDate))) {
        $html .= '<div class="filter-section">';
        $html .= '<div class="filter-title">APPLIED FILTERS</div>';
        
        if (!empty($searchTerm)) {
            $html .= '<div class="filter-item"><strong>Search Query:</strong> ' . htmlspecialchars($searchTerm) . '</div>';
        }
        if (!empty($filterStatus)) {
            $html .= '<div class="filter-item"><strong>Status Filter:</strong> ' . htmlspecialchars(ucfirst($filterStatus)) . '</div>';
        }
        if (!empty($filterPaymentMethod)) {
            $html .= '<div class="filter-item"><strong>Payment Method:</strong> ' . htmlspecialchars($filterPaymentMethod) . '</div>';
        }
        if (!empty($startDate) && !empty($endDate)) {
            $html .= '<div class="filter-item"><strong>Date Range:</strong> ' . htmlspecialchars(date('M j, Y', strtotime($startDate))) . ' to ' . htmlspecialchars(date('M j, Y', strtotime($endDate))) . '</div>';
        }
        
        $html .= '</div>';
    }
    
    // Calculate summary statistics
    $totalTransactions = count($filteredTransactions);
    $totalAmount = array_sum(array_column($filteredTransactions, 'total_amount'));
    $completedTransactions = count(array_filter($filteredTransactions, function($t) { return strtolower($t['status']) === 'completed'; }));
    $pendingTransactions = count(array_filter($filteredTransactions, function($t) { return strtolower($t['status']) === 'pending'; }));
    $averageOrderValue = $totalTransactions > 0 ? $totalAmount / $totalTransactions : 0;
    
    // Add summary section
    $html .= '<div class="summary-section">';
    $html .= '<div class="summary-title">Transaction Summary</div>';
    $html .= '<div class="summary-stats">';
    $html .= '<div class="stat-item">';
    $html .= '<span class="stat-value">' . number_format($totalTransactions) . '</span>';
    $html .= '<span class="stat-label">Total Orders</span>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<span class="stat-value">₱' . number_format($totalAmount, 2) . '</span>';
    $html .= '<span class="stat-label">Total Revenue</span>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<span class="stat-value">' . $completedTransactions . '</span>';
    $html .= '<span class="stat-label">Completed</span>';
    $html .= '</div>';
    $html .= '<div class="stat-item">';
    $html .= '<span class="stat-value">₱' . number_format($averageOrderValue, 2) . '</span>';
    $html .= '<span class="stat-label">Avg. Order Value</span>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Create transactions overview table
    if (!empty($filteredTransactions)) {
        $html .= '<table border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        $html .= '<thead class="table-header">';
        $html .= '<tr>';
        $html .= '<th style="width: 12%;">Order ID</th>';
        $html .= '<th style="width: 20%;">Customer</th>';
        $html .= '<th style="width: 15%;">Date</th>';
        $html .= '<th style="width: 10%;">Status</th>';
        $html .= '<th style="width: 15%;">Payment</th>';
        $html .= '<th style="width: 12%;">Amount</th>';
        $html .= '<th style="width: 8%;">Items</th>';
        $html .= '<th style="width: 8%;">Location</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($filteredTransactions as $transaction) {
            $formattedDate = date('M j, Y', strtotime($transaction['transaction_date']));
            $formattedTime = date('g:i A', strtotime($transaction['transaction_date']));
            
            $statusClass = 'status-' . strtolower(str_replace(' ', '-', $transaction['status']));
            
            $html .= '<tr class="transaction-row">';
            $html .= '<td style="font-weight: bold; color: #2980b9;">#' . htmlspecialchars(substr($transaction['transaction_id'], -8)) . '</td>';
            $html .= '<td>';
            $html .= '<div style="font-weight: bold; color: #495057;">' . htmlspecialchars($transaction['shipping_info']['fullname']) . '</div>';
            $html .= '<div style="font-size: 8px; color: #6c757d;">' . htmlspecialchars($transaction['shipping_info']['email']) . '</div>';
            $html .= '</td>';
            $html .= '<td>';
            $html .= '<div>' . $formattedDate . '</div>';
            $html .= '<div style="font-size: 8px; color: #6c757d;">' . $formattedTime . '</div>';
            $html .= '</td>';
            $html .= '<td><span class="' . $statusClass . '">' . htmlspecialchars(strtoupper($transaction['status'])) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($transaction['payment_method']) . '</td>';
            $html .= '<td class="amount-cell">₱' . number_format($transaction['total_amount'], 2) . '</td>';
            $html .= '<td style="text-align: center;">' . count($transaction['items']) . '</td>';
            $html .= '<td style="font-size: 8px;">' . htmlspecialchars($transaction['shipping_info']['city']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Start new page for detailed transactions
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->AddPage();
        
        // Detailed Transaction Information
        $detailHtml = $styles;
        $detailHtml .= '<div style="text-align: center; margin-bottom: 20px;">';
        $detailHtml .= '<h2 style="color: #2980b9; margin: 0;">DETAILED TRANSACTION RECORDS</h2>';
        $detailHtml .= '<p style="color: #6c757d; margin: 5px 0;">Complete order information and item details</p>';
        $detailHtml .= '</div>';
        
        foreach ($filteredTransactions as $index => $transaction) {
            $detailHtml .= '<div class="detail-section">';
            $detailHtml .= '<div class="detail-header">';
            $detailHtml .= 'ORDER #' . htmlspecialchars(substr($transaction['transaction_id'], -8)) . ' - ' . htmlspecialchars(strtoupper($transaction['status']));
            $detailHtml .= '<span style="float: right;">' . date('M j, Y g:i A', strtotime($transaction['transaction_date'])) . '</span>';
            $detailHtml .= '</div>';
            
            $detailHtml .= '<div class="detail-content">';
            
            // Customer & Shipping Information
            $detailHtml .= '<div class="shipping-info">';
            $detailHtml .= '<strong style="color: #495057; display: block; margin-bottom: 8px;">CUSTOMER & SHIPPING DETAILS</strong>';
            $detailHtml .= '<table style="width: 100%; font-size: 10px;">';
            $detailHtml .= '<tr>';
            $detailHtml .= '<td style="width: 50%; vertical-align: top;">';
            $detailHtml .= '<strong>Name:</strong> ' . htmlspecialchars($transaction['shipping_info']['fullname']) . '<br>';
            $detailHtml .= '<strong>Email:</strong> ' . htmlspecialchars($transaction['shipping_info']['email']) . '<br>';
            $detailHtml .= '<strong>Phone:</strong> ' . htmlspecialchars($transaction['shipping_info']['phone']) . '<br>';
            $detailHtml .= '<strong>Payment:</strong> ' . htmlspecialchars($transaction['payment_method']);
            $detailHtml .= '</td>';
            $detailHtml .= '<td style="width: 50%; vertical-align: top;">';
            $detailHtml .= '<strong>Address:</strong><br>';
            $detailHtml .= htmlspecialchars($transaction['shipping_info']['address']) . '<br>';
            $detailHtml .= htmlspecialchars($transaction['shipping_info']['city']) . ' ' . htmlspecialchars($transaction['shipping_info']['postal_code']);
            if (!empty($transaction['shipping_info']['notes'])) {
                $detailHtml .= '<br><strong>Notes:</strong> ' . htmlspecialchars($transaction['shipping_info']['notes']);
            }
            $detailHtml .= '</td>';
            $detailHtml .= '</tr>';
            $detailHtml .= '</table>';
            $detailHtml .= '</div>';
            
            // Items Table
            $detailHtml .= '<strong style="color: #495057; display: block; margin-bottom: 8px;">ORDERED ITEMS</strong>';
            $detailHtml .= '<table class="items-table">';
            $detailHtml .= '<thead>';
            $detailHtml .= '<tr>';
            $detailHtml .= '<th style="width: 35%;">Product Name</th>';
            $detailHtml .= '<th style="width: 12%;">Color</th>';
            $detailHtml .= '<th style="width: 10%;">Size</th>';
            $detailHtml .= '<th style="width: 15%;">Unit Price</th>';
            $detailHtml .= '<th style="width: 8%;">Qty</th>';
            $detailHtml .= '<th style="width: 20%;">Subtotal</th>';
            $detailHtml .= '</tr>';
            $detailHtml .= '</thead>';
            $detailHtml .= '<tbody>';
            
            foreach ($transaction['items'] as $item) {
                $detailHtml .= '<tr>';
                $detailHtml .= '<td style="text-align: left; font-weight: bold;">' . htmlspecialchars($item['product_name']) . '</td>';
                $detailHtml .= '<td>' . htmlspecialchars($item['color']) . '</td>';
                $detailHtml .= '<td>' . htmlspecialchars($item['size']) . '</td>';
                $detailHtml .= '<td>₱' . number_format($item['price'], 2) . '</td>';
                $detailHtml .= '<td>' . $item['quantity'] . '</td>';
                $detailHtml .= '<td style="font-weight: bold;">₱' . number_format($item['subtotal'], 2) . '</td>';
                $detailHtml .= '</tr>';
            }
            
            $detailHtml .= '</tbody>';
            $detailHtml .= '</table>';
            
            // Order Totals
            $detailHtml .= '<div class="total-section">';
            $detailHtml .= '<div class="total-line">Subtotal: <strong>₱' . number_format($transaction['subtotal'], 2) . '</strong></div>';
            $detailHtml .= '<div class="total-line">Shipping Fee: <strong>₱' . number_format($transaction['shipping_fee'], 2) . '</strong></div>';
            $detailHtml .= '<div class="total-line grand-total">TOTAL AMOUNT: ₱' . number_format($transaction['total_amount'], 2) . '</div>';
            $detailHtml .= '</div>';
            
            $detailHtml .= '</div>'; // End detail-content
            $detailHtml .= '</div>'; // End detail-section
            
            // Add page break every 2 transactions to avoid overflow
            if (($index + 1) % 2 == 0 && ($index + 1) < count($filteredTransactions)) {
                $detailHtml .= '<div style="page-break-after: always;"></div>';
            }
        }
        
        $pdf->writeHTML($detailHtml, true, false, true, false, '');
        
    } else {
        $html .= '<div class="no-data">';
        $html .= '<h3 style="color: #6c757d; margin-bottom: 10px;">No Transactions Found</h3>';
        $html .= '<p>No payment records match your current filter criteria.</p>';
        $html .= '<p>Try adjusting your search parameters or date range.</p>';
        $html .= '</div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
    }
    
    // Add footer on last page
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(108, 117, 125);
    $currentY = $pdf->GetY();
    $pdf->SetXY(15, 280);
    $pdf->Cell(0, 5, 'HirayaFit E-commerce Platform - Confidential Business Report', 0, 0, 'L');
    $pdf->Cell(0, 5, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'R');
    
    // Generate filename with timestamp
    $filename = 'HirayaFit_Payment_Report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF for download
    $pdf->Output($filename, 'D');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datepicker/1.0.10/datepicker.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #111111;
            --secondary: #0071c5;
            --accent: #e5e5e5;
            --light: #ffffff;
            --dark: #111111;
            --grey: #767676;
            --sidebar-width: 250px;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            font-size: 22px;
            font-weight: 700;
            color: var(--light);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .sidebar-logo span {
            color: var(--secondary);
        }
        
        .sidebar-close {
            color: white;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            display: none;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #adb5bd;
            padding: 10px 20px;
            margin-top: 10px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: #e9ecef;
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--secondary);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.08);
            border-left: 3px solid var(--secondary);
            color: var(--secondary);
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        /* Top Navigation */
        .top-navbar {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            color: var(--dark);
            font-size: 20px;
            cursor: pointer;
            display: none;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
        }
        
        .navbar-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 18px;
            margin-right: 20px;
        }
        
        .welcome-text {
            font-size: 14px;
            color: var(--grey);
        }
        
        .welcome-text strong {
            color: var(--dark);
            font-weight: 600;
        }
        
        .navbar-actions {
            display: flex;
            align-items: center;
        }
        
        .navbar-actions .nav-link {
            color: var(--dark);
            font-size: 18px;
            margin-right: 20px;
            position: relative;
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Admin Profile Styles */
        .admin-profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
        }

        .admin-avatar-container {
            position: relative;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            overflow: hidden;
            border: 2px solid var(--secondary);
            margin-right: 10px;
        }

        .admin-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-info {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-weight: 600;
            font-size: 14px;
            display: block;
            line-height: 1.2;
        }

        .admin-role {
            font-size: 12px;
            color: var(--secondary);
            display: block;
        }

        .admin-dropdown {
            position: relative;
        }

        .admin-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            background-color: white;
            min-width: 220px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 4px;
            overflow: hidden;
        }

        .admin-dropdown-header {
            background-color: var(--dark);
            color: white;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-dropdown-avatar-container {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            overflow: hidden;
            border: 2px solid var(--secondary);
            margin-right: 15px;
        }

        .admin-dropdown-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-dropdown-info {
            display: flex;
            flex-direction: column;
        }

        .admin-dropdown-name {
            font-weight: 600;
            font-size: 16px;
        }

        .admin-dropdown-role {
            font-size: 12px;
            color: var(--secondary);
        }

        .admin-dropdown-user {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .admin-dropdown-user-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .admin-dropdown-user-email {
            color: #6c757d;
            font-size: 14px;
        }

        .admin-dropdown-content a {
            color: var(--dark);
            padding: 12px 15px;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            border-bottom: 1px solid #f5f5f5;
        }

        .admin-dropdown-content a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .admin-dropdown-content a.logout {
            color: var(--danger);
        }

        .admin-dropdown-content a:hover {
            background-color: #f8f9fa;
        }

        .admin-dropdown.show .admin-dropdown-content {
            display: block;
        }
        
        /* Payment History Container */
        .payment-history-container {
            padding: 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: var(--grey);
            margin-bottom: 20px;
        }

        .page-actions {
            display: flex;
            gap: 10px;
        }

        .card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Filter Controls */
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--dark);
        }

        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            min-width: 200px;
        }

        .date-input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            min-width: 150px;
        }

        select.filter-input {
            height: 38px;
            background-color: white;
        }

        .filter-button {
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 9px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-button.clear {
            background-color: var(--grey);
        }

        .filter-button:hover {
            opacity: 0.9;
        }

        /* Table Styles */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 1px solid #e9ecef;
        }

        .payment-table td {
            padding: 12px 15px;
            font-size: 14px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: middle;
        }

        .payment-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-processing {
            background-color: rgba(255, 193, 7, 0.2);
            color: #d39e00;
        }

        .status-completed {
            background-color: rgba(40, 167, 69, 0.2);
            color: #1e7e34;
        }

        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.2);
            color: #bd2130;
        }

        .status-delivered {
            background-color: rgba(23, 162, 184, 0.2);
            color: #117a8b;
        }

        /* Payment method badges */
        .payment-badge {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-badge i {
            margin-right: 5px;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-view {
            background-color: var(--secondary);
            color: white;
        }

        .btn-export {
            background-color: var(--success);
            color: white;
        }

        .btn-view:hover, .btn-export:hover {
            opacity: 0.9;
        }

        .transaction-details {
            display: none;
            margin-top: 10px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 14px;
        }

        .transaction-details h4 {
            margin-bottom: 10px;
            font-size: 16px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .details-section {
            margin-bottom: 20px;
        }

        .details-item {
            margin-bottom: 8px;
            display: flex;
        }

        .details-label {
            font-weight: 600;
            width: 120px;
            color: var(--dark);
        }

        .details-value {
            color: var(--grey);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th {
            background-color: #eef0f2;
            text-align: left;
            padding: 8px 10px;
            font-size: 13px;
        }

        .items-table td {
            padding: 8px 10px;
            font-size: 13px;
            border-bottom: 1px solid #eef0f2;
        }

        .export-options {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 50px;
            color: #d1d1d1;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 14px;
            color: var(--grey);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-input, .date-input {
                min-width: unset;
            }
            
            .payment-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                width: var(--sidebar-width);
                transform: translateX(0);
            }
            
            .sidebar-close {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
            
            .navbar-title {
                display: none;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .page-actions {
                width: 100%;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .payment-history-container {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">Hiraya<span>Fit</span></a>
            <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-title">MAIN</div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="orders_admin.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="payment-history.php" class="active"><i class="fas fa-money-check-alt"></i> Payment History</a>
            
            <div class="menu-title">INVENTORY</div>
            <a href="products.php"><i class="fas fa-tshirt"></i> Products & Categories</a>
            <a href="stock.php"><i class="fas fa-box"></i> Stock Management</a>
            
            <div class="menu-title">USERS</div>
            <a href="users.php"><i class="fas fa-users"></i> User Management</a>
            
            <div class="menu-title">REPORTS & SETTINGS</div>
            <a href="reports.php"><i class="fas fa-file-pdf"></i> Reports & Analytics</a>
            <a href="settings.php"><i class="fas fa-cog"></i> System Settings</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="nav-left">
                <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <span class="navbar-title">Payment History</span>
                <div class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($admin['fullname']); ?></strong>!</div>
            </div>
            
            <div class="navbar-actions">
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-count">5</span>
                </a>
                
                <div class="admin-dropdown" id="adminDropdown">
                    <div class="admin-profile">
                        <div class="admin-avatar-container">
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Admin" class="admin-avatar">
                        </div>
                        <div class="admin-info">
                            <span class="admin-name"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                            <span class="admin-role"><?php echo htmlspecialchars($admin['role']); ?></span>
                        </div>
                    </div>
                    
                    <div class="admin-dropdown-content" id="adminDropdownContent">
                        <div class="admin-dropdown-header">
                            <div class="admin-dropdown-avatar-container">
                                <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Admin" class="admin-dropdown-avatar">
                            </div>
                            <div class="admin-dropdown-info">
                                <span class="admin-dropdown-name"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                                <span class="admin-dropdown-role"><?php echo htmlspecialchars($admin['role']); ?></span>
                            </div>
                        </div>
                        <div class="admin-dropdown-user">
                            <h4 class="admin-dropdown-user-name"><?php echo htmlspecialchars($admin['fullname']); ?></h4>
                            <p class="admin-dropdown-user-email"><?php echo htmlspecialchars($admin['email']); ?></p>
                        </div>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile Settings</a>
                        <!--<a href="change-password.php"><i class="fas fa-lock"></i> Change Password</a>-->
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Payment History Content -->
        <div class="payment-history-container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Payment History</h1>
                    <p class="page-subtitle">View and manage all payment transactions in the system</p>
                </div>
                <div class="page-actions">
                    <button type="button" class="btn btn-export" id="exportPDF">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Filter Payments
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="filter-container">
                        <div class="filter-group">
                            <label class="filter-label">Search</label>
                            <input type="text" name="search" class="filter-input" placeholder="Transaction ID or customer name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        
                        <!--<div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select name="status" class="filter-input">
                                <option value="">All Statuses</option>
                                <option value="processing" <?php echo $filterStatus === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>-->
                        
                        <div class="filter-group">
                            <label class="filter-label">Payment Method</label>
                            <select name="payment_method" class="filter-input">
                                <option value="">All Methods</option>
                                <option value="gcash" <?php echo $filterPaymentMethod === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                                <option value="cod" <?php echo $filterPaymentMethod === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                <option value="bank_transfer" <?php echo $filterPaymentMethod === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="credit_card" <?php echo $filterPaymentMethod === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Start Date</label>
                            <input type="date" name="start_date" class="date-input" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">End Date</label>
                            <input type="date" name="end_date" class="date-input" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="filter-button">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="payment-history.php" class="filter-button clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Payment Transactions
                </div>
                <div class="card-body">
                    <?php if (count($filteredTransactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="payment-table" id="payment-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredTransactions as $index => $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                                    <td>
                                        <?php 
                                            $date = new DateTime($transaction['transaction_date']);
                                            echo $date->format('M d, Y h:i A'); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['shipping_info']['fullname']); ?></td>
                                    <td>
                                        <span class="payment-badge">
                                            <?php 
                                            switch($transaction['payment_method']) {
                                                case 'gcash':
                                                    echo '<i class="fas fa-mobile-alt"></i> GCash';
                                                    break;
                                                case 'cod':
                                                    echo '<i class="fas fa-money-bill-wave"></i> COD';
                                                    break;
                                                case 'bank_transfer':
                                                    echo '<i class="fas fa-university"></i> Bank';
                                                    break;
                                                case 'credit_card':
                                                    echo '<i class="fas fa-credit-card"></i> Card';
                                                    break;
                                                default:
                                                    echo '<i class="fas fa-money-check"></i> ' . ucfirst($transaction['payment_method']);
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                            <?php echo ucfirst($transaction['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-view view-details" data-id="<?php echo $index; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="details-row" id="details-<?php echo $index; ?>" style="display: none;">
                                    <td colspan="7">
                                        <div class="transaction-details">
                                            <div class="details-grid">
                                                <div class="details-section">
                                                    <h4>Transaction Information</h4>
                                                    <div class="details-item">
                                                        <div class="details-label">Transaction ID:</div>
                                                        <div class="details-value"><?php echo htmlspecialchars($transaction['transaction_id']); ?></div>
                                                    </div>
                                                    <div class="details-item">
                                                        <div class="details-label">Date:</div>
                                                        <div class="details-value"><?php echo $date->format('F d, Y h:i A'); ?></div>
                                                    </div>
                                                    <div class="details-item">
                                                        <div class="details-label">Status:</div>
                                                        <div class="details-value"><?php echo ucfirst($transaction['status']); ?></div>
                                                    </div>
                                                    <div class="details-item">
                                                        <div class="details-label">Payment Method:</div>
                                                        <div class="details-value"><?php echo ucfirst($transaction['payment_method']); ?></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="details-section">
                                                    <h4>Customer Information</h4>
                                                    <div class="details-item">
                                                        <div class="details-label">Customer Name:</div>
                                                        <div class="details-value"><?php echo htmlspecialchars($transaction['shipping_info']['fullname']); ?></div>
                                                    </div>
                                                    <div class="details-item">
                                                        <div class="details-label">Email:</div>
                                                        <div class="details-value"><?php echo htmlspecialchars($transaction['shipping_info']['email']); ?></div>
                                                    </div>
                                                    <div class="details-item">
                                                        <div class="details-label">Phone:</div>
                                                        <div class="details-value"><?php echo htmlspecialchars($transaction['shipping_info']['phone']); ?></div>
                                                    </div>
                                                    <div class="details-item">
                                                        <div class="details-label">Address:</div>
                                                        <div class="details-value">
                                                            <?php echo htmlspecialchars($transaction['shipping_info']['address']); ?>,
                                                            <?php echo htmlspecialchars($transaction['shipping_info']['city']); ?>,
                                                            <?php echo htmlspecialchars($transaction['shipping_info']['postal_code']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="details-section">
                                                <h4>Order Items</h4>
                                                <table class="items-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Price</th>
                                                            <th>Quantity</th>
                                                            <th>Color</th>
                                                            <th>Size</th>
                                                            <th>Subtotal</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($transaction['items'] as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td><?php echo htmlspecialchars($item['color']); ?></td>
                                                            <td><?php echo htmlspecialchars($item['size']); ?></td>
                                                            <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="5" style="text-align: right; font-weight: bold;">Subtotal:</td>
                                                            <td>₱<?php echo number_format($transaction['subtotal'], 2); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="5" style="text-align: right; font-weight: bold;">Shipping Fee:</td>
                                                            <td>₱<?php echo number_format($transaction['shipping_fee'], 2); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="5" style="text-align: right; font-weight: bold;">Total Amount:</td>
                                                            <td style="font-weight: bold;">₱<?php echo number_format($transaction['total_amount'], 2); ?></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No transactions found</h3>
                        <p>Try adjusting your search or filter criteria</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
document.getElementById('exportPDF').addEventListener('click', function() {
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Add export parameter
    urlParams.set('export', 'pdf');
    
    // Redirect to same page with export parameter
    window.location.href = window.location.pathname + '?' + urlParams.toString();
});
</script>

<body>
</html>