<?php
// Add this to your existing PHP code after the filtering logic

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Include TCPDF library
  
    require_once('TCPDF-main/TCPDF-main/tcpdf.php');
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Admin System');
    $pdf->SetTitle('Transaction Report');
    $pdf->SetSubject('Transaction Report');
    $pdf->SetKeywords('Transaction, Report, PDF');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, 'Transaction Report', 'Generated on: ' . date('Y-m-d H:i:s'));
    
    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font for content
    $pdf->SetFont('helvetica', '', 10);
    
    // Build HTML content for PDF
    $html = '<h2 style="text-align: center; color: #333;">Transaction Report</h2>';
    
    // Add filter information
    if (!empty($searchTerm) || !empty($filterStatus) || !empty($filterPaymentMethod) || (!empty($startDate) && !empty($endDate))) {
        $html .= '<div style="background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #dee2e6;">';
        $html .= '<h4 style="margin-top: 0;">Applied Filters:</h4>';
        
        if (!empty($searchTerm)) {
            $html .= '<p><strong>Search:</strong> ' . htmlspecialchars($searchTerm) . '</p>';
        }
        if (!empty($filterStatus)) {
            $html .= '<p><strong>Status:</strong> ' . htmlspecialchars($filterStatus) . '</p>';
        }
        if (!empty($filterPaymentMethod)) {
            $html .= '<p><strong>Payment Method:</strong> ' . htmlspecialchars($filterPaymentMethod) . '</p>';
        }
        if (!empty($startDate) && !empty($endDate)) {
            $html .= '<p><strong>Date Range:</strong> ' . htmlspecialchars($startDate) . ' to ' . htmlspecialchars($endDate) . '</p>';
        }
        
        $html .= '</div>';
    }
    
    // Add summary
    $totalTransactions = count($filteredTransactions);
    $totalAmount = array_sum(array_column($filteredTransactions, 'total_amount'));
    
    $html .= '<div style="background-color: #e9ecef; padding: 10px; margin-bottom: 20px;">';
    $html .= '<h4 style="margin-top: 0;">Summary:</h4>';
    $html .= '<p><strong>Total Transactions:</strong> ' . $totalTransactions . '</p>';
    $html .= '<p><strong>Total Amount:</strong> ₱' . number_format($totalAmount, 2) . '</p>';
    $html .= '</div>';
    
    // Create transactions table
    if (!empty($filteredTransactions)) {
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #007bff; color: white;">';
        $html .= '<th style="width: 15%;"><strong>Transaction ID</strong></th>';
        $html .= '<th style="width: 20%;"><strong>Customer</strong></th>';
        $html .= '<th style="width: 15%;"><strong>Date</strong></th>';
        $html .= '<th style="width: 12%;"><strong>Status</strong></th>';
        $html .= '<th style="width: 15%;"><strong>Payment Method</strong></th>';
        $html .= '<th style="width: 13%;"><strong>Total Amount</strong></th>';
        $html .= '<th style="width: 10%;"><strong>Items</strong></th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($filteredTransactions as $transaction) {
            // Format date
            $formattedDate = date('Y-m-d H:i', strtotime($transaction['transaction_date']));
            
            // Status color
            $statusColor = '';
            switch (strtolower($transaction['status'])) {
                case 'completed':
                    $statusColor = 'color: green;';
                    break;
                case 'pending':
                    $statusColor = 'color: orange;';
                    break;
                case 'cancelled':
                    $statusColor = 'color: red;';
                    break;
                default:
                    $statusColor = 'color: black;';
            }
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($transaction['transaction_id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['shipping_info']['fullname']) . '<br><small>' . htmlspecialchars($transaction['shipping_info']['email']) . '</small></td>';
            $html .= '<td>' . $formattedDate . '</td>';
            $html .= '<td style="' . $statusColor . '">' . htmlspecialchars($transaction['status']) . '</td>';
            $html .= '<td>' . htmlspecialchars($transaction['payment_method']) . '</td>';
            $html .= '<td>₱' . number_format($transaction['total_amount'], 2) . '</td>';
            $html .= '<td>' . count($transaction['items']) . ' item(s)</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Add detailed items if there are transactions
        $html .= '<br><h3>Transaction Details</h3>';
        
        foreach ($filteredTransactions as $transaction) {
            $html .= '<div style="margin-bottom: 20px; border: 1px solid #ccc; padding: 10px;">';
            $html .= '<h4>Transaction: ' . htmlspecialchars($transaction['transaction_id']) . '</h4>';
            
            // Shipping Information
            $html .= '<div style="margin-bottom: 10px;">';
            $html .= '<strong>Shipping Information:</strong><br>';
            $html .= 'Name: ' . htmlspecialchars($transaction['shipping_info']['fullname']) . '<br>';
            $html .= 'Email: ' . htmlspecialchars($transaction['shipping_info']['email']) . '<br>';
            $html .= 'Phone: ' . htmlspecialchars($transaction['shipping_info']['phone']) . '<br>';
            $html .= 'Address: ' . htmlspecialchars($transaction['shipping_info']['address']) . ', ' . htmlspecialchars($transaction['shipping_info']['city']) . ' ' . htmlspecialchars($transaction['shipping_info']['postal_code']) . '<br>';
            if (!empty($transaction['shipping_info']['notes'])) {
                $html .= 'Notes: ' . htmlspecialchars($transaction['shipping_info']['notes']) . '<br>';
            }
            $html .= '</div>';
            
            // Items
            $html .= '<strong>Items:</strong><br>';
            $html .= '<table border="1" cellpadding="3" cellspacing="0" style="width: 100%; margin-top: 5px;">';
            $html .= '<tr style="background-color: #f8f9fa;">';
            $html .= '<th>Product</th><th>Color</th><th>Size</th><th>Price</th><th>Qty</th><th>Subtotal</th>';
            $html .= '</tr>';
            
            foreach ($transaction['items'] as $item) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['product_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($item['color']) . '</td>';
                $html .= '<td>' . htmlspecialchars($item['size']) . '</td>';
                $html .= '<td>₱' . number_format($item['price'], 2) . '</td>';
                $html .= '<td>' . $item['quantity'] . '</td>';
                $html .= '<td>₱' . number_format($item['subtotal'], 2) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</table>';
            
            // Totals
            $html .= '<div style="text-align: right; margin-top: 10px;">';
            $html .= '<strong>Subtotal:</strong> ₱' . number_format($transaction['subtotal'], 2) . '<br>';
            $html .= '<strong>Shipping Fee:</strong> ₱' . number_format($transaction['shipping_fee'], 2) . '<br>';
            $html .= '<strong>Total Amount:</strong> ₱' . number_format($transaction['total_amount'], 2);
            $html .= '</div>';
            
            $html .= '</div>';
        }
    } else {
        $html .= '<p style="text-align: center; font-style: italic;">No transactions found matching the selected criteria.</p>';
    }
    
    // Write HTML to PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Generate filename
    $filename = 'transaction_report_' . date('Y-m-d_H-i-s') . '.pdf';
    
    // Output PDF
    $pdf->Output($filename, 'D'); // 'D' for download
    exit;
}
?>