<?php
// Start the session at the very beginning
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';


// Include TCPDF library - you'll need to download and include it
require_once('TCPDF-main/TCPDF-main/tcpdf.php');



// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: sign-in.php");
    exit;
}

// Function to load and parse XML transactions
function loadTransactions() {
    $xmlFile = 'transaction.xml';
    if (!file_exists($xmlFile)) {
        return [];
    }
    
    $xml = simplexml_load_file($xmlFile);
    if ($xml === false) {
        return [];
    }
    
    $transactions = [];
    foreach ($xml->transaction as $transaction) {
        $items = [];
        if (isset($transaction->items->item)) {
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
        }
        
        $shipping_info = null;
        if (isset($transaction->shipping_info)) {
            $shipping_info = [
                'fullname' => (string)$transaction->shipping_info->fullname,
                'email' => (string)$transaction->shipping_info->email,
                'phone' => (string)$transaction->shipping_info->phone,
                'address' => (string)$transaction->shipping_info->address,
                'city' => (string)$transaction->shipping_info->city,
                'postal_code' => (string)$transaction->shipping_info->postal_code,
                'notes' => (string)$transaction->shipping_info->notes
            ];
        }
        
        $transactions[] = [
            'transaction_id' => (string)$transaction->transaction_id,
            'user_id' => (int)$transaction->user_id,
            'transaction_date' => (string)$transaction->transaction_date,
            'status' => (string)$transaction->status,
            'payment_method' => (string)$transaction->payment_method,
            'subtotal' => (float)$transaction->subtotal,
            'shipping_fee' => (float)$transaction->shipping_fee,
            'total_amount' => (float)$transaction->total_amount,
            'items' => $items,
            'shipping_info' => $shipping_info
        ];
    }
    
    return $transactions;
}

// Load transactions
$transactions = loadTransactions();

// Apply filters (same as payment-history.php)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$filtered_transactions = $transactions;

if (!empty($search)) {
    $filtered_transactions = array_filter($filtered_transactions, function($transaction) use ($search) {
        return stripos($transaction['transaction_id'], $search) !== false ||
               (isset($transaction['shipping_info']) && stripos($transaction['shipping_info']['fullname'], $search) !== false) ||
               (isset($transaction['shipping_info']) && stripos($transaction['shipping_info']['email'], $search) !== false);
    });
}

if (!empty($status_filter)) {
    $filtered_transactions = array_filter($filtered_transactions, function($transaction) use ($status_filter) {
        return $transaction['status'] === $status_filter;
    });
}

if (!empty($payment_filter)) {
    $filtered_transactions = array_filter($filtered_transactions, function($transaction) use ($payment_filter) {
        return $transaction['payment_method'] === $payment_filter;
    });
}

if (!empty($date_from)) {
    $filtered_transactions = array_filter($filtered_transactions, function($transaction) use ($date_from) {
        return strtotime($transaction['transaction_date']) >= strtotime($date_from);
    });
}

if (!empty($date_to)) {
    $filtered_transactions = array_filter($filtered_transactions, function($transaction) use ($date_to) {
        return strtotime($transaction['transaction_date']) <= strtotime($date_to . ' 23:59:59');
    });
}

// Sort by transaction date (newest first)
usort($filtered_transactions, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Helper functions
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y g:i A', strtotime($date));
}

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'HirayaFit - Payment History Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
        
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Generated on: ' . date('F d, Y g:i A'), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->Ln(15);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Initialize PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('HirayaFit Admin System');
$pdf->SetAuthor('HirayaFit');
$pdf->SetTitle('Payment History Report');
$pdf->SetSubject('Payment Transactions Report');

// Set margins
$pdf->SetMargins(15, 35, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Calculate totals
$total_revenue = array_sum(array_column($filtered_transactions, 'total_amount'));
$total_transactions = count($filtered_transactions);
$completed_orders = count(array_filter($filtered_transactions, function($t) { return $t['status'] === 'delivered' || $t['status'] === 'completed'; }));
$pending_orders = count(array_filter($filtered_transactions, function($t) { return $t['status'] === 'pending'; }));

// Summary Section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L');
$pdf->Ln(5);

$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(240, 240, 240);

// Create summary table
$pdf->Cell(45, 8, 'Total Revenue:', 1, 0, 'L', true);
$pdf->Cell(45, 8, formatCurrency($total_revenue), 1, 0, 'R');
$pdf->Cell(45, 8, 'Total Transactions:', 1, 0, 'L', true);
$pdf->Cell(45, 8, $total_transactions, 1, 1, 'R');

$pdf->Cell(45, 8, 'Completed Orders:', 1, 0, 'L', true);
$pdf->Cell(45, 8, $completed_orders, 1, 0, 'R');
$pdf->Cell(45, 8, 'Pending Orders:', 1, 0, 'L', true);
$pdf->Cell(45, 8, $pending_orders, 1, 1, 'R');

$pdf->Ln(10);

// Filters Applied Section
if (!empty($search) || !empty($status_filter) || !empty($payment_filter) || !empty($date_from) || !empty($date_to)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Applied Filters:', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    if (!empty($search)) {
        $pdf->Cell(0, 6, '• Search: ' . $search, 0, 1, 'L');
    }
    if (!empty($status_filter)) {
        $pdf->Cell(0, 6, '• Status: ' . ucfirst($status_filter), 0, 1, 'L');
    }
    if (!empty($payment_filter)) {
        $pdf->Cell(0, 6, '• Payment Method: ' . ucfirst(str_replace('_', ' ', $payment_filter)), 0, 1, 'L');
    }
    if (!empty($date_from)) {
        $pdf->Cell(0, 6, '• Date From: ' . date('M d, Y', strtotime($date_from)), 0, 1, 'L');
    }
    if (!empty($date_to)) {
        $pdf->Cell(0, 6, '• Date To: ' . date('M d, Y', strtotime($date_to)), 0, 1, 'L');
    }
    $pdf->Ln(10);
}

// Transactions Table
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Transaction Details', 0, 1, 'L');
$pdf->Ln(2);

// Table header
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(50, 50, 50);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(35, 8, 'Transaction ID', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Customer', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Payment', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Amount', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Status', 1, 1, 'C', true);

// Reset text color
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 8);

// Table data
$fill = false;
foreach ($filtered_transactions as $transaction) {
    // Check if we need a new page
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
        
        // Repeat header on new page
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(50, 50, 50);
        $pdf->SetTextColor(255, 255, 255);
        
        $pdf->Cell(35, 8, 'Transaction ID', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Customer', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Payment', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Status', 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
    }
    
    $pdf->SetFillColor(245, 245, 245);
    
    // Transaction ID
    $pdf->Cell(35, 8, $transaction['transaction_id'], 1, 0, 'L', $fill);
    
    // Date
    $pdf->Cell(30, 8, date('M d, Y', strtotime($transaction['transaction_date'])), 1, 0, 'L', $fill);
    
    // Customer
    $customer_name = isset($transaction['shipping_info']) ? 
        $transaction['shipping_info']['fullname'] : 
        'User ID: ' . $transaction['user_id'];
    $pdf->Cell(40, 8, substr($customer_name, 0, 18) . (strlen($customer_name) > 18 ? '...' : ''), 1, 0, 'L', $fill);
    
    // Payment Method
    $payment_method = ucfirst(str_replace('_', ' ', $transaction['payment_method']));
    $pdf->Cell(25, 8, substr($payment_method, 0, 10) . (strlen($payment_method) > 10 ? '...' : ''), 1, 0, 'L', $fill);
    
    // Amount
    $pdf->Cell(25, 8, formatCurrency($transaction['total_amount']), 1, 0, 'R', $fill);
    
    // Status
    $pdf->Cell(25, 8, ucfirst($transaction['status']), 1, 1, 'C', $fill);
    
    $fill = !$fill;
}

// Add total row
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(155, 8, 'TOTAL:', 1, 0, 'R', true);
$pdf->Cell(25, 8, formatCurrency($total_revenue), 1, 1, 'R', true);

// Output PDF
$filename = 'payment_history_' . date('Y-m-d_H-i-s') . '.pdf';
$pdf->Output($filename, 'D'); // 'D' for download, 'I' for inline view

exit;
?>