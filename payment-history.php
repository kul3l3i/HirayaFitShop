<?php
// Start the session at the very beginning
// Start the session at the very beginning
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';





// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
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
    session_destroy();
    header("Location: sign-in.php");
    exit;
}

$admin = $result->fetch_assoc();
if (!isset($admin['role'])) {
    $admin['role'] = 'Administrator';
}

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png";
    }
}

$profileImageUrl = getProfileImageUrl($admin['profile_image']);
$stmt->close();

// Function to load and parse XML transactions
function loadTransactions() {
    $xmlFile = 'transaction.xml'; // Update path as needed
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
    
    // Sort by transaction date (newest first)
    usort($transactions, function($a, $b) {
        return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
    });
    
    return $transactions;
}

// Load transactions
$transactions = loadTransactions();

// Filter transactions based on search and filters
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

// Pagination
$items_per_page = 10;
$total_items = count($filtered_transactions);
$total_pages = ceil($total_items / $items_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;
$paged_transactions = array_slice($filtered_transactions, $offset, $items_per_page);

// Calculate statistics
$total_revenue = array_sum(array_column($transactions, 'total_amount'));
$completed_orders = count(array_filter($transactions, function($t) { return $t['status'] === 'delivered'; }));
$pending_orders = count(array_filter($transactions, function($t) { return $t['status'] === 'pending'; }));

// Helper functions
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y g:i A', strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'badge-warning',
        'processing' => 'badge-info',
        'shipped' => 'badge-info',
        'delivered' => 'badge-success',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger'
    ];
    $class = isset($badges[$status]) ? $badges[$status] : 'badge-secondary';
    return "<span class='badge {$class}'>" . ucfirst($status) . "</span>";
}

function getPaymentMethodIcon($method) {
    $icons = [
        'gcash' => 'fas fa-mobile-alt',
        'paymaya' => 'fas fa-credit-card',
        'credit_card' => 'fas fa-credit-card',
        'bank_transfer' => 'fas fa-university',
        'cod' => 'fas fa-money-bill-wave',
        'pending' => 'fas fa-clock'
    ];
    return isset($icons[$method]) ? $icons[$method] : 'fas fa-money-check';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- jsPDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
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
            --purple: #6f42c1;
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

        /* Dashboard Container */
        .dashboard-container {
            padding: 30px;
        }

        /* Statistics Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 20px;
            color: white;
        }

        .stat-icon.revenue { background-color: var(--success); }
        .stat-icon.completed { background-color: var(--info); }
        .stat-icon.pending { background-color: var(--warning); }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .stat-info p {
            color: var(--grey);
            font-size: 14px;
        }

        /* Filters Section */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Transactions Table */
        .transactions-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .export-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .table-container {
            overflow-x: auto;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .transactions-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .transactions-table td {
            font-size: 14px;
            vertical-align: middle;
        }

        .transactions-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Badges */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* Action Buttons */
        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.view {
            background-color: var(--secondary);
            color: white;
        }

        .action-btn.export {
            background-color: var(--success);
            color: white;
        }

        /* Pagination */
        .pagination-container {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
        }

        .pagination-info {
            font-size: 14px;
            color: var(--grey);
        }

        .pagination {
            display: flex;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            color: var(--dark);
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: #f8f9fa;
        }

        .pagination .current {
            background-color: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--secondary), #005a9c);
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }

        .close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 20px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h4 {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: 600;
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 8px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 12px;
        }

        .detail-label {
            font-size: 12px;
            color: var(--grey);
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 14px;
            color: var(--dark);
            font-weight: 500;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .items-table th {
            background-color: var(--dark);
            color: white;
            text-align: left;
            padding: 12px 15px;
            font-size: 14px;
            font-weight: 600;
        }

        .items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            background-color: white;
        }

        .items-table tbody tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .items-table tfoot td {
            background-color: #e9ecef;
            font-weight: 600;
            border-top: 2px solid var(--secondary);
        }

        .shipping-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-50px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
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

            .filters-row {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 2% auto;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .export-buttons {
                flex-direction: column;
                width: 100%;
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
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
                        <a href="profileAdmin.php"><i class="fas fa-user"></i> Profile Settings</a>
                       
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Statistics Cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo formatCurrency($total_revenue); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $completed_orders; ?></h3>
                        <p>Completed Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Transaction ID, Customer Name, Email">
                        </div>
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="payment">Payment Method</label>
                            <select id="payment" name="payment">
                                <option value="">All Methods</option>
                                <option value="gcash" <?php echo $payment_filter === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                                <option value="paymaya" <?php echo $payment_filter === 'paymaya' ? 'selected' : ''; ?>>PayMaya</option>
                                <option value="credit_card" <?php echo $payment_filter === 'credit_card' ? 'selected' : ''; ?>>Credit Card</option>
                                <option value="bank_transfer" <?php echo $payment_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="cod" <?php echo $payment_filter === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date_from">Date From</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="date_to">Date To</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="payment-history.php" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="transactions-section">
                <div class="section-header">
                    <h2 class="section-title">Payment Transactions</h2>
                    <div class="export-buttons">
                        <button onclick="exportAllTransactionsPDF()" class="btn btn-success">
                            <i class="fas fa-file-pdf"></i> Export All PDF
                        </button>
                        <button onclick="exportFilteredTransactionsPDF()" class="btn btn-warning">
                            <i class="fas fa-filter"></i> Export Filtered PDF
                        </button>
                        <button onclick="exportSummaryPDF()" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> Export Summary PDF
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="transactions-table">
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
                            <?php if (empty($paged_transactions)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                        <i class="fas fa-receipt fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                                        <p>No transactions found.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($paged_transactions as $index => $transaction): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($transaction['transaction_id']); ?></strong>
                                        </td>
                                        <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                        <td>
                                            <?php if (isset($transaction['shipping_info'])): ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($transaction['shipping_info']['fullname']); ?></strong><br>
                                                    <small style="color: #666;"><?php echo htmlspecialchars($transaction['shipping_info']['email']); ?></small>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #666;">User ID: <?php echo $transaction['user_id']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="<?php echo getPaymentMethodIcon($transaction['payment_method']); ?>"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?>
                                        </td>
                                        <td><strong><?php echo formatCurrency($transaction['total_amount']); ?></strong></td>
                                        <td><?php echo getStatusBadge($transaction['status']); ?></td>
                                        <td>
                                            <button class="action-btn view" onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="action-btn export" onclick="exportSingleTransactionPDF(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                                                <i class="fas fa-download"></i> PDF
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> entries
                        </div>
                        <div class="pagination">
                            <?php
                            $query_params = $_GET;
                            
                            if ($current_page > 1):
                                $query_params['page'] = $current_page - 1;
                            ?>
                                <a href="?<?php echo http_build_query($query_params); ?>">« Previous</a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                                $query_params['page'] = $i;
                                if ($i == $current_page):
                            ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php
                            if ($current_page < $total_pages):
                                $query_params['page'] = $current_page + 1;
                            ?>
                                <a href="?<?php echo http_build_query($query_params); ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Transaction Details</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <script>
    // Store transactions data for PDF export
    const allTransactions = <?php echo json_encode($transactions); ?>;
    const filteredTransactions = <?php echo json_encode($filtered_transactions); ?>;
    const totalRevenue = <?php echo $total_revenue; ?>;
    const completedOrders = <?php echo $completed_orders; ?>;
    const pendingOrders = <?php echo $pending_orders; ?>;

    document.addEventListener('DOMContentLoaded', function() {
        // Admin dropdown toggle
        const adminDropdown = document.getElementById('adminDropdown');
        const adminDropdownContent = document.getElementById('adminDropdownContent');
        
        adminDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            adminDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!adminDropdown.contains(e.target)) {
                adminDropdown.classList.remove('show');
            }
        });
        
        // Sidebar toggle for responsive design
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebar = document.querySelector('.sidebar');
        
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    });

    function viewTransaction(transaction) {
        const modal = document.getElementById('transactionModal');
        const modalBody = document.getElementById('modalBody');
        
        // Format date
        const date = new Date(transaction.transaction_date);
        const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Build transaction information section
        let content = `
            <div class="detail-section">
                <h4><i class="fas fa-receipt"></i> Transaction Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Transaction ID</span>
                        <span class="detail-value">${transaction.transaction_id}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">${formattedDate}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="badge badge-${getStatusClass(transaction.status)}">
                                ${transaction.status.toUpperCase()}
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value">
                            <i class="${getPaymentIcon(transaction.payment_method)}"></i>
                            ${transaction.payment_method.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        // Add customer information if available
        if (transaction.shipping_info) {
            content += `
                <div class="detail-section">
                    <h4><i class="fas fa-user"></i> Customer Information</h4>
                    <div class="shipping-info">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">Full Name</span>
                                <span class="detail-value">${transaction.shipping_info.fullname}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">${transaction.shipping_info.email}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value">${transaction.shipping_info.phone}</span>
                            </div>
                        </div>
                        <div class="detail-item" style="margin-top: 15px;">
                            <span class="detail-label">Shipping Address</span>
                            <span class="detail-value">
                                ${transaction.shipping_info.address}<br>
                                ${transaction.shipping_info.city}, ${transaction.shipping_info.postal_code}
                            </span>
                        </div>
                        ${transaction.shipping_info.notes ? `
                            <div class="detail-item" style="margin-top: 15px;">
                                <span class="detail-label">Notes</span>
                                <span class="detail-value">${transaction.shipping_info.notes}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        // Add order items section
        content += `
            <div class="detail-section">
                <h4><i class="fas fa-shopping-cart"></i> Order Items</h4>
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
        `;
        
        // Add items to table
        transaction.items.forEach(item => {
            content += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>₱${parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td>${item.quantity}</td>
                    <td>${item.color}</td>
                    <td>${item.size}</td>
                    <td>₱${parseFloat(item.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });
        
        // Add totals to table
        content += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold;">Subtotal:</td>
                            <td>₱${parseFloat(transaction.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold;">Shipping Fee:</td>
                            <td>₱${parseFloat(transaction.shipping_fee).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold; color: var(--secondary);">Total Amount:</td>
                            <td style="font-weight: bold; color: var(--secondary);">₱${parseFloat(transaction.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        
        modalBody.innerHTML = content;
        modal.style.display = 'block';
    }

    function getStatusClass(status) {
        const statusClasses = {
            'pending': 'warning',
            'processing': 'info',
            'shipped': 'info',
            'delivered': 'success',
            'completed': 'success',
            'cancelled': 'danger'
        };
        return statusClasses[status] || 'secondary';
    }

    function getPaymentIcon(method) {
        const icons = {
            'gcash': 'fas fa-mobile-alt',
            'paymaya': 'fas fa-credit-card',
            'credit_card': 'fas fa-credit-card',
            'bank_transfer': 'fas fa-university',
            'cod': 'fas fa-money-bill-wave',
            'pending': 'fas fa-clock'
        };
        return icons[method] || 'fas fa-money-check';
    }

    function closeModal() {
        document.getElementById('transactionModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('transactionModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // PDF Export Functions
    function formatNumber(amount) {
        return parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function exportSingleTransactionPDF(transaction) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // HirayaFit brand colors (RGB values)
        const primaryColor = [17, 17, 17];     // #111111
        const secondaryColor = [0, 113, 197];  // #0071c5
        const accentColor = [229, 229, 229];   // #e5e5e5
        const successColor = [40, 167, 69];    // #28a745
        const greyColor = [118, 118, 118];     // #767676

        // Header with brand colors
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 35, 'F');
        
        // Company logo/name
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(28);
        doc.setFont('helvetica', 'bold');
        doc.text('HirayaFit', 20, 22);
        
        // Subtitle
        doc.setTextColor(...secondaryColor);
        doc.setFontSize(14);
        doc.setFont('helvetica', 'normal');
        doc.text('Transaction Receipt', 20, 45);

        // Transaction details section
        doc.setTextColor(...primaryColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Transaction Details', 20, 65);

        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        let yPos = 75;
        
        doc.text(`Transaction ID: ${transaction.transaction_id}`, 20, yPos);
        yPos += 8;
        doc.text(`Date: ${formatDate(transaction.transaction_date)}`, 20, yPos);
        yPos += 8;
        doc.text(`Status: ${transaction.status.toUpperCase()}`, 20, yPos);
        yPos += 8;
        doc.text(`Payment Method: ${transaction.payment_method.replace('_', ' ').toUpperCase()}`, 20, yPos);
        yPos += 15;

        // Customer information
        if (transaction.shipping_info) {
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('Customer Information', 20, yPos);
            yPos += 10;

            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            doc.text(`Name: ${transaction.shipping_info.fullname}`, 20, yPos);
            yPos += 8;
            doc.text(`Email: ${transaction.shipping_info.email}`, 20, yPos);
            yPos += 8;
            doc.text(`Phone: ${transaction.shipping_info.phone}`, 20, yPos);
            yPos += 8;
            doc.text(`Address: ${transaction.shipping_info.address}`, 20, yPos);
            yPos += 8;
            doc.text(`City: ${transaction.shipping_info.city}, ${transaction.shipping_info.postal_code}`, 20, yPos);
            yPos += 15;
        }

        // Items table
        if (transaction.items && transaction.items.length > 0) {
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('Order Items', 20, yPos);
            yPos += 10;

            const tableData = transaction.items.map(item => [
                item.product_name,
                item.color,
                item.size,
                formatNumber(item.price),
                item.quantity.toString(),
                formatNumber(item.subtotal)
            ]);

            doc.autoTable({
                startY: yPos,
                head: [['Product', 'Color', 'Size', 'Price', 'Qty', 'Subtotal']],
                body: tableData,
                theme: 'grid',
                headStyles: {
                    fillColor: primaryColor,
                    textColor: [255, 255, 255],
                    fontSize: 10,
                    fontStyle: 'bold'
                },
                bodyStyles: {
                    fontSize: 9,
                    textColor: primaryColor
                },
                alternateRowStyles: {
                    fillColor: [248, 249, 250]
                },
                margin: { left: 20, right: 20 }
            });

            yPos = doc.lastAutoTable.finalY + 15;
        }

        // Payment summary
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Payment Summary', 20, yPos);
        yPos += 10;

        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        doc.text(`Subtotal: ${formatNumber(transaction.subtotal)}`, 20, yPos);
        yPos += 8;
        doc.text(`Shipping Fee: ${formatNumber(transaction.shipping_fee)}`, 20, yPos);
        yPos += 10;

        // Total with highlight
        doc.setFillColor(...successColor);
        doc.rect(15, yPos - 5, 180, 15, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.text(`TOTAL AMOUNT: ${formatNumber(transaction.total_amount)}`, 20, yPos + 3);

        // Footer
        doc.setTextColor(...greyColor);
        doc.setFontSize(9);
        doc.setFont('helvetica', 'normal');
        doc.text('Thank you for choosing HirayaFit!', 20, 270);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 20, 280);

        // Save PDF
        doc.save(`HirayaFit_Transaction_${transaction.transaction_id}.pdf`);
    }

    function exportAllTransactionsPDF() {
        exportTransactionsPDF(allTransactions, 'All_Transactions');
    }

    function exportFilteredTransactionsPDF() {
        exportTransactionsPDF(filteredTransactions, 'Filtered_Transactions');
    }

    function exportTransactionsPDF(transactions, filename) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // HirayaFit brand colors
        const primaryColor = [17, 17, 17];
        const secondaryColor = [0, 113, 197];
        const successColor = [40, 167, 69];
        const greyColor = [118, 118, 118];

        // Header
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 35, 'F');
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(28);
        doc.setFont('helvetica', 'bold');
        doc.text('HirayaFit', 20, 22);
        
        doc.setTextColor(...secondaryColor);
        doc.setFontSize(14);
        doc.text('Payment History Report', 20, 45);

        // Summary Statistics
        const totalRevenue = transactions.reduce((sum, t) => sum + parseFloat(t.total_amount), 0);
        const completedCount = transactions.filter(t => t.status === 'delivered' || t.status === 'completed').length;
        const pendingCount = transactions.filter(t => t.status === 'pending').length;

        doc.setTextColor(...primaryColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Summary Statistics', 20, 65);

        doc.setFontSize(11);
        doc.setFont('helvetica', 'normal');
        doc.text(`Total Transactions: ${transactions.length}`, 20, 75);
        doc.text(`Total Revenue: ${formatNumber(totalRevenue)}`, 20, 83);
        doc.text(`Completed Orders: ${completedCount}`, 20, 91);
        doc.text(`Pending Orders: ${pendingCount}`, 20, 99);
        doc.text(`Report Generated: ${new Date().toLocaleString()}`, 20, 107);

        // Transactions Table
        const tableData = transactions.map(transaction => [
            transaction.transaction_id,
            formatDate(transaction.transaction_date),
            transaction.shipping_info ? transaction.shipping_info.fullname : `User ${transaction.user_id}`,
            transaction.payment_method.replace('_', ' ').toUpperCase(),
            formatNumber(transaction.total_amount),
            transaction.status.toUpperCase()
        ]);

        doc.autoTable({
            startY: 120,
            head: [['Transaction ID', 'Date', 'Customer', 'Payment', 'Amount', 'Status']],
            body: tableData,
            theme: 'grid',
            headStyles: {
                fillColor: primaryColor,
                textColor: [255, 255, 255],
                fontSize: 9,
                fontStyle: 'bold'
            },
            bodyStyles: {
                fontSize: 8,
                textColor: primaryColor
            },
            alternateRowStyles: {
                fillColor: [248, 249, 250]
            },
            margin: { left: 20, right: 20 },
            columnStyles: {
                0: { cellWidth: 30 },
                1: { cellWidth: 35 },
                2: { cellWidth: 40 },
                3: { cellWidth: 25 },
                4: { cellWidth: 25 },
                5: { cellWidth: 20 }
            }
        });

        // Total Summary at bottom
        const finalY = doc.lastAutoTable.finalY + 15;
        doc.setFillColor(...successColor);
        doc.rect(15, finalY - 2, 180, 12, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.text(`TOTAL REVENUE: ${formatNumber(totalRevenue)}`, 20, finalY + 5);

        // Footer
        doc.setTextColor(...greyColor);
        doc.setFontSize(8);
        doc.setFont('helvetica', 'normal');
        doc.text('HirayaFit Payment History Report', 20, 280);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 20, 285);

        // Save PDF
        doc.save(`HirayaFit_${filename}_${new Date().toISOString().split('T')[0]}.pdf`);
    }

    function exportSummaryPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // HirayaFit brand colors
        const primaryColor = [17, 17, 17];
        const secondaryColor = [0, 113, 197];
        const successColor = [40, 167, 69];
        const warningColor = [255, 193, 7];
        const infoColor = [23, 162, 184];
        const greyColor = [118, 118, 118];

        // Header
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 35, 'F');
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(28);
        doc.setFont('helvetica', 'bold');
        doc.text('HirayaFit', 20, 22);
        
        doc.setTextColor(...secondaryColor);
        doc.setFontSize(14);
        doc.text('Payment Summary Report', 20, 45);

        // Key Metrics
        doc.setTextColor(...primaryColor);
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');
        doc.text('Key Performance Metrics', 20, 65);

        // Revenue Box
        doc.setFillColor(...successColor);
        doc.rect(20, 75, 80, 30, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('TOTAL REVENUE', 25, 85);
        doc.setFontSize(16);
        doc.text(formatNumber(totalRevenue), 25, 95);

        // Completed Orders Box
        doc.setFillColor(...infoColor);
        doc.rect(110, 75, 80, 30, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('COMPLETED ORDERS', 115, 85);
        doc.setFontSize(16);
        doc.text(completedOrders.toString(), 115, 95);

        // Pending Orders Box
        doc.setFillColor(...warningColor);
        doc.rect(20, 115, 80, 30, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('PENDING ORDERS', 25, 125);
        doc.setFontSize(16);
        doc.text(pendingOrders.toString(), 25, 135);

        // Total Transactions Box
        doc.setFillColor(...secondaryColor);
        doc.rect(110, 115, 80, 30, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('TOTAL TRANSACTIONS', 115, 125);
        doc.setFontSize(16);
        doc.text(allTransactions.length.toString(), 115, 135);

        // Payment Methods Breakdown
        doc.setTextColor(...primaryColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Payment Methods Breakdown', 20, 165);

        const paymentMethods = {};
        allTransactions.forEach(t => {
            const method = t.payment_method.replace('_', ' ').toUpperCase();
            paymentMethods[method] = (paymentMethods[method] || 0) + 1;
        });

        let yPos = 175;
        Object.entries(paymentMethods).forEach(([method, count]) => {
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            doc.text(`${method}: ${count} transactions`, 25, yPos);
            yPos += 8;
        });

        // Status Breakdown
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Order Status Breakdown', 20, yPos + 10);
        yPos += 20;

        const statusCounts = {};
        allTransactions.forEach(t => {
            const status = t.status.toUpperCase();
            statusCounts[status] = (statusCounts[status] || 0) + 1;
        });

        Object.entries(statusCounts).forEach(([status, count]) => {
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            doc.text(`${status}: ${count} orders`, 25, yPos);
            yPos += 8;
        });

        // Footer
        doc.setTextColor(...greyColor);
        doc.setFontSize(8);
        doc.setFont('helvetica', 'normal');
        doc.text('HirayaFit Payment Summary Report', 20, 270);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 20, 280);

        // Save PDF
        doc.save(`HirayaFit_Summary_Report_${new Date().toISOString().split('T')[0]}.pdf`);
    }
    </script>
</body>
</html>