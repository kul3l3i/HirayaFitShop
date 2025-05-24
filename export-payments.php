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