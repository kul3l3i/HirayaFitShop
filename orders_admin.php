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

// Load products from XML file - FIXED: Using the correct XML structure and file name
function loadProductsXml() {
    $productsFile = 'product.xml';
    if (file_exists($productsFile)) {
        return simplexml_load_file($productsFile);
    } else {
        return false;
    }
}

// Update product stock in XML file - FIXED: Updated to handle the correct XML structure
function updateProductStock($productId, $quantity) {
    $productsFile = 'product.xml';
    $products = simplexml_load_file($productsFile);
    
    if ($products === false) {
        return ['status' => 'error', 'message' => 'Products file not found'];
    }
    
    $productFound = false;
    
    // Access the products correctly within the store->products structure
    foreach ($products->products->product as $product) {
        if ((string)$product->id === (string)$productId) {
            $currentStock = (int)$product->stock;
            $newStock = max(0, $currentStock - $quantity);
            $product->stock = $newStock;
            $productFound = true;
            break;
        }
    }
    
    if (!$productFound) {
        return ['status' => 'error', 'message' => 'Product not found: ' . $productId];
    }
    
    // Save the updated XML back to file
    if ($products->asXML($productsFile)) {
        return ['status' => 'success', 'message' => 'Stock updated successfully for product ' . $productId];
    } else {
        return ['status' => 'error', 'message' => 'Failed to update stock for product ' . $productId];
    }
}

// Load orders from XML file
function loadOrdersXml() {
    $ordersFile = 'transaction.xml';
    if (file_exists($ordersFile)) {
        return simplexml_load_file($ordersFile);
    } else {
        return false;
    }
}

// Function to update order status in XML file
function updateOrderStatus($transactionId, $newStatus, $previousStatus) {
    $ordersFile = 'transaction.xml';
    $orders = loadOrdersXml();
    
    if ($orders === false) {
        return ['status' => 'error', 'message' => 'Orders file not found'];
    }
    
    $orderFound = false;
    $items = [];
    
    foreach ($orders->transaction as $transaction) {
        if ((string)$transaction->transaction_id === $transactionId) {
            // Store items information for stock management
            if (strtolower($newStatus) === 'shipped' && strtolower($previousStatus) !== 'shipped') {
                foreach ($transaction->items->item as $item) {
                    $items[] = [
                        'product_id' => (string)$item->product_id,
                        'quantity' => (int)$item->quantity
                    ];
                }
            }
            
            // Update the status
            $transaction->status = $newStatus;
            $orderFound = true;
            break;
        }
    }
    
    if (!$orderFound) {
        return ['status' => 'error', 'message' => 'Order not found'];
    }
    
    // Save the updated XML back to file
    if ($orders->asXML($ordersFile)) {
        $result = ['status' => 'success', 'message' => 'Order status updated successfully'];
        
        // Handle stock changes when changing to shipped
        if (strtolower($newStatus) === 'shipped' && strtolower($previousStatus) !== 'shipped') {
            $stockErrors = [];
            // Decrement stock when changing to shipped
            foreach ($items as $item) {
                $stockResult = updateProductStock($item['product_id'], $item['quantity']);
                if ($stockResult['status'] === 'error') {
                    $stockErrors[] = $stockResult['message'];
                }
            }
            
            if (!empty($stockErrors)) {
                $result['message'] .= ' Note: Some inventory updates failed: ' . implode('; ', $stockErrors);
                $result['status'] = 'warning';
            } else {
                $result['message'] .= ' Inventory has been updated.';
            }
        }
        
        return $result;
    } else {
        return ['status' => 'error', 'message' => 'Failed to update order status'];
    }
}

// Export orders to CSV
function exportOrdersToCSV($orders) {
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'orders_export_');
    
    // Open the file for writing
    $f = fopen($tempFile, 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fputs($f, "\xEF\xBB\xBF");
    
    // Write headers
    fputcsv($f, ['Order ID', 'Customer', 'Date', 'Total', 'Status', 'Payment Method', 'Items', 'Customer Email', 'Customer Phone', 'Shipping Address']);
    
    foreach ($orders->transaction as $order) {
        // Create item list string
        $items = [];
        if (isset($order->items->item)) {
            foreach ($order->items->item as $item) {
                $items[] = (string)$item->quantity . 'x ' . (string)$item->product_name . ' (' . (string)$item->color . ', ' . (string)$item->size . ')';
            }
        }
        $itemsStr = implode("; ", $items);
        
        // Get customer info
        $customerName = isset($order->shipping_info->fullname) ? (string)$order->shipping_info->fullname : '';
        $customerEmail = isset($order->shipping_info->email) ? (string)$order->shipping_info->email : '';
        $customerPhone = isset($order->shipping_info->phone) ? (string)$order->shipping_info->phone : '';
        
        // Get address info
        $address = isset($order->shipping_info->address) ? (string)$order->shipping_info->address : '';
        $city = isset($order->shipping_info->city) ? (string)$order->shipping_info->city : '';
        $postalCode = isset($order->shipping_info->postal_code) ? (string)$order->shipping_info->postal_code : '';
        $fullAddress = $address . ', ' . $city . ', ' . $postalCode;
        
        // Write order row
        fputcsv($f, [
            (string)$order->transaction_id,
            $customerName,
            (string)$order->transaction_date,
            (string)$order->total_amount,
            (string)$order->status,
            (string)$order->payment_method,
            $itemsStr,
            $customerEmail,
            $customerPhone,
            $fullAddress
        ]);
    }
    
    fclose($f);
    
    // Return the file path
    return $tempFile;
}

// Handle update order status request
$statusMessage = '';
$statusClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $transactionId = $_POST['transaction_id'];
    $newStatus = $_POST['new_status'];
    $previousStatus = $_POST['previous_status'];
    
    $result = updateOrderStatus($transactionId, $newStatus, $previousStatus);
    
    if ($result['status'] === 'success') {
        $statusMessage = $result['message'];
        $statusClass = 'success';
    } else if ($result['status'] === 'warning') {
        $statusMessage = $result['message'];
        $statusClass = 'warning';
    } else {
        $statusMessage = $result['message'];
        $statusClass = 'error';
    }
}

require_once('TCPDF-main/TCPDF-main/tcpdf.php');

function exportOrdersToPDF($orders) {
    $totalOrders = 0;
    $totalRevenue = 0;
    foreach ($orders->transaction as $order) {
        $totalOrders++;
        $totalRevenue += (float)$order->total_amount;
    }
    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

    $pdf = new TCPDF();
    $pdf->SetCreator('HirayaFit');
    $pdf->SetAuthor('HirayaFit');
    $pdf->SetTitle('Orders Report');
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    // HEADER SECTION
    $html = '
    <div style="text-align: center; font-family: helvetica;">
        <h2 style="margin: 0; font-size: 20px;">HIRAYAFIT</h2>
        <p style="margin: 0; font-size: 12px;">FITNESS & ATHLETIC WEAR</p>
        <h3 style="margin: 10px 0;">ORDER REPORT</h3>
        <small>Generated on: ' . date("F j, Y \\a\\t g:i A") . '</small>
    </div><br>';

    // SUMMARY SECTION
    $html .= '
    <div style="background-color: #2ecc71; color: white; padding: 12px; border-radius: 8px; font-size: 12px; margin-bottom: 10px;">
        <table width="100%" style="text-align: center;">
            <tr>
                <td><b>' . $totalOrders . '</b><br>Total Orders</td>
                <td><b>₱' . number_format($totalRevenue, 2) . '</b><br>Total Revenue</td>
                <td><b>₱' . number_format($avgOrderValue, 2) . '</b><br>Avg. Order Value</td>
            </tr>
        </table>
    </div>';

    // ORDER DETAILS
    foreach ($orders->transaction as $order) {
        $itemsHTML = '';
        $subtotal = 0;

        foreach ($order->items->item as $item) {
            $qty = (int)$item->quantity;
            $unitPrice = (float)$item->price;
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;

            $itemsHTML .= '
            <tr>
                <td>' . $item->product_name . '</td>
                <td>' . $item->color . '</td>
                <td>' . $item->size . '</td>
                <td style="text-align:right;">₱' . number_format($unitPrice, 2) . '</td>
                <td style="text-align:center;">' . $qty . '</td>
                <td style="text-align:right;">₱' . number_format($lineTotal, 2) . '</td>
            </tr>';
        }

        $shipping = isset($order->shipping_fee) ? (float)$order->shipping_fee : 100.00;
        $grandTotal = $subtotal + $shipping;

        $html .= '
        <div style="margin-top: 20px;">
            <h4 style="background-color: #8e44ad; color: white; padding: 8px; border-radius: 5px; font-size: 12px;">
                ORDER #' . $order->transaction_id . ' - ' . strtoupper($order->status) . ' | ' . date("F j, Y g:i A", strtotime($order->transaction_date)) . '
            </h4>
            <p style="font-size: 10px; margin: 5px 0;">
                <b>Customer:</b> ' . $order->shipping_info->fullname . '<br>
                <b>Email:</b> ' . $order->shipping_info->email . '<br>
                <b>Phone:</b> ' . $order->shipping_info->phone . '<br>
                <b>Payment:</b> ' . $order->payment_method . '<br>
                <b>Address:</b> ' . $order->shipping_info->address . ', ' . $order->shipping_info->city . ' ' . $order->shipping_info->postal_code . '<br>
                <b>Notes:</b> ' . ($order->shipping_info->notes ?? 'None') . '
            </p>
            <table border="1" cellpadding="5" cellspacing="0" width="100%" style="font-size:9px; border-collapse: collapse;">
                <thead style="background-color: #495057; color: white;">
                    <tr>
                        <th>Product Name</th>
                        <th>Color</th>
                        <th>Size</th>
                        <th>Unit Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHTML . '</tbody>
            </table>
            <div style="text-align: right; font-size: 10px; margin-top: 8px;">
                Subtotal: ₱' . number_format($subtotal, 2) . '<br>
                Shipping Fee: ₱' . number_format($shipping, 2) . '<br>
                <b style="color: #2980b9;">TOTAL AMOUNT: ₱' . number_format($grandTotal, 2) . '</b>
            </div>
        </div>';
    }

    $html .= '<div style="text-align: center; font-size: 8px; color: #888; margin-top: 20px;">HirayaFit E-commerce Platform - Confidential Report</div>';

    $pdf->writeHTML($html, true, false, true, false, '');

    $fileName = 'HirayaFit_Orders_Report_' . date('Y-m-d_H-i-s') . '.pdf';
    $filePath = sys_get_temp_dir() . '/' . $fileName;

    $pdf->Output($filePath, 'F');
    return $filePath;
}
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $orders = loadOrdersXml();
    if ($orders !== false) {
        $filePath = exportOrdersToPDF($orders);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        readfile($filePath);
        unlink($filePath);
        exit;
    }
}


// Get profile image URL
$profileImageUrl = getProfileImageUrl($admin['profile_image']);

// Close the statement
$stmt->close();

// Load orders
$orders = loadOrdersXml();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Order Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/orders_admin.css">
    <style>
        /* Additional Styles for Export and View */
        .order-actions .btn-export {
            background-color: #28a745;
            color: white;
        }
        
        .order-details {
            margin-bottom: 20px;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .order-meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .order-meta-label {
            font-size: 0.8em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .order-meta-value {
            font-weight: bold;
        }
        
        .customer-info, .items-list {
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .customer-info h5, .items-list h5 {
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th, .items-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .items-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .export-button {
            margin-bottom: 15px;
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        
        .export-button:hover {
            background-color: #218838;
        }
        
        .status-processing {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-shipped {
            background-color: #b8daff;
            color: #004085;
        }
        
        .status-delivered {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f5c6cb;
            color: #721c24;
        }
        
        .status-pending {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        /* Responsive modal dialog */
        @media (min-width: 768px) {
            .modal-dialog {
                max-width: 700px;
            }
        }
    </style>
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
            <a href="orders_admin.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="payment-history.php"><i class="fas fa-money-check-alt"></i> Payment History</a>
            
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
                <span class="navbar-title">Order Management</span>
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
                       
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Orders Container -->
        <div class="orders-container">
            <div class="page-header">
                <h1 class="page-title">Order Management</h1>
            </div>
            
            <?php if (!empty($statusMessage)): ?>
                <div class="alert alert-<?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($statusMessage); ?>
                </div>
            <?php endif; ?>
            
            <div class="order-actions-top" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div class="filter-group">
                    <span class="filter-label">Status:</span>
                    <select class="filter-select" id="filterStatus">
                        <option value="all">All Orders</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div class="export-container">
    <a href="orders_admin.php?export=pdf" class="export-button">
        <i class="fas fa-file-export"></i> Export All Orders (PDF)
    </a>
</div>

            </div>
            
            <div class="order-search">
                <input type="text" class="search-input" id="orderSearch" placeholder="Search orders by ID, customer name, or status...">
            </div>
            
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders !== false): ?>
                            <?php foreach ($orders->transaction as $order): ?>
                                <tr class="order-row" data-status="<?php echo strtolower((string)$order->status); ?>">
                                    <td class="order-id"><?php echo htmlspecialchars((string)$order->transaction_id); ?></td>
                                    <td>
                                        <?php if (isset($order->shipping_info->fullname)): ?>
                                            <?php echo htmlspecialchars((string)$order->shipping_info->fullname); ?>
                                        <?php else: ?>
                                            <em>Customer Info Pending</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string)$order->transaction_date); ?></td>
                                    <td>₱<?php echo number_format((float)$order->total_amount, 2); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo strtolower((string)$order->status); ?>">
                                            <?php echo htmlspecialchars(ucfirst((string)$order->status)); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <button class="btn btn-sm btn-view" onclick="viewOrder('<?php echo htmlspecialchars((string)$order->transaction_id); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-change-status" 
                                                    onclick="changeStatus('<?php echo htmlspecialchars((string)$order->transaction_id); ?>', '<?php echo htmlspecialchars((string)$order->status); ?>')">
                                                <i class="fas fa-exchange-alt"></i> Status
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination">
                <a href="#" class="pagination-link">&laquo;</a>
                <a href="#" class="pagination-link active">1</a>
                <a href="#" class="pagination-link">2</a>
                <a href="#" class="pagination-link">3</a>
                <a href="#" class="pagination-link">&raquo;</a>
            </div>
        </div>
    </div>
    
    <!-- View Order Modal -->
    <div class="modal" id="viewOrderModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Order Details</h3>
                    <button class="modal-close" onclick="closeViewModal()">&times;</button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be populated by JavaScript -->
                </div>
                <div class="modal-footer">
                    <button class="btn" onclick="closeViewModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Status Modal -->
    <div class="modal" id="changeStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Change Order Status</h3>
                    <button class="modal-close" onclick="closeStatusModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" class="status-form" id="statusForm">
                        <input type="hidden" name="transaction_id" id="statusOrderId">
                        <input type="hidden" name="previous_status" id="previousStatus">
                        
                        <div class="form-group">
                            <label for="current_status">Current Status:</label>
                            <input type="text" id="current_status" readonly class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_status">Select New Status:</label>
                            <select name="new_status" id="new_status" required class="form-control">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <p class="warning-text" id="statusWarning" style="display: none; color: #856404; background-color: rgba(255, 193, 7, 0.15); padding: 10px; border-radius: 4px;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Changing to "Shipped" will update product inventory. This action cannot be undone. Please confirm.
                        </p>
                        
                        <button type="submit" name="update_status" class="btn btn-primary" style="margin-top: 15px; background: #4e73df; color: white; border: none; padding: 10px 15px; border-radius: 4px;">Update Status</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn" onclick="closeStatusModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle admin dropdown menu
        document.getElementById('adminDropdown').addEventListener('click', function() {
            document.getElementById('adminDropdownContent').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.admin-profile') && !event.target.closest('.admin-profile')) {
                var dropdown = document.getElementById('adminDropdownContent');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
        
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        document.getElementById('sidebarClose').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
        });
        
        // Filter orders by status
        document.getElementById('filterStatus').addEventListener('change', function() {
            var status = this.value;
            var rows = document.querySelectorAll('.order-row');
            
            rows.forEach(function(row) {
                if (status === 'all' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Search orders
        document.getElementById('orderSearch').addEventListener('keyup', function() {
            var searchText = this.value.toLowerCase();
            var rows = document.querySelectorAll('.order-row');
            
            rows.forEach(function(row) {
                var rowText = row.textContent.toLowerCase();
                if (rowText.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // View Order Details
        function viewOrder(orderId) {
            // In a real application, you would fetch order details using AJAX
            // For this example, we'll use XML data directly
            var orderDetails = getOrderDetails(orderId);
            document.getElementById('orderDetailsContent').innerHTML = orderDetails;
            document.getElementById('viewOrderModal').classList.add('show');
        }
        
        function getOrderDetails(orderId) {
            // In a production environment, this would be an AJAX call
            // For this example, we'll construct the HTML directly from XML data
            
            var orders = <?php echo json_encode(($orders !== false) ? $orders->asXML() : ''); ?>;
            var xmlDoc = new DOMParser().parseFromString(orders, "text/xml");
            var transactions = xmlDoc.getElementsByTagName("transaction");
            var orderHTML = '<p>Order not found</p>';
            
            for (var i = 0; i < transactions.length; i++) {
                var transaction = transactions[i];
                var transId = transaction.getElementsByTagName("transaction_id")[0].textContent;

                if (transId === orderId) {
                    // Basic order info
                    var status = transaction.getElementsByTagName("status")[0].textContent;
                    var date = transaction.getElementsByTagName("transaction_date")[0].textContent;
                    var payment = transaction.getElementsByTagName("payment_method")[0].textContent;
                    var subtotal = parseFloat(transaction.getElementsByTagName("subtotal")[0].textContent).toFixed(2);
                    var shippingFee = parseFloat(transaction.getElementsByTagName("shipping_fee")[0].textContent || "0").toFixed(2);
                    var totalAmount = parseFloat(transaction.getElementsByTagName("total_amount")[0].textContent).toFixed(2);
                    
                    // Customer info
                    var shippingInfo = transaction.getElementsByTagName("shipping_info")[0];
                    var customerName = shippingInfo.getElementsByTagName("fullname")[0] ? shippingInfo.getElementsByTagName("fullname")[0].textContent : "Not provided";
                    var customerEmail = shippingInfo.getElementsByTagName("email")[0] ? shippingInfo.getElementsByTagName("email")[0].textContent : "Not provided";
                    var customerPhone = shippingInfo.getElementsByTagName("phone")[0] ? shippingInfo.getElementsByTagName("phone")[0].textContent : "Not provided";
                    var address = shippingInfo.getElementsByTagName("address")[0] ? shippingInfo.getElementsByTagName("address")[0].textContent : "";
                    var city = shippingInfo.getElementsByTagName("city")[0] ? shippingInfo.getElementsByTagName("city")[0].textContent : "";
                    var postalCode = shippingInfo.getElementsByTagName("postal_code")[0] ? shippingInfo.getElementsByTagName("postal_code")[0].textContent : "";
                    var shippingAddress = address + ", " + city + ", " + postalCode;
                    
                    // Order items
                    var items = transaction.getElementsByTagName("items")[0];
                    var itemNodes = items.getElementsByTagName("item");
                    
                    var itemsHTML = '<table class="items-table">' +
                                    '<thead>' +
                                    '<tr>' +
                                    '<th>Product</th>' +
                                    '<th>Size</th>' +
                                    '<th>Color</th>' +
                                    '<th>Price</th>' +
                                    '<th>Qty</th>' +
                                    '<th>Total</th>' +
                                    '</tr>' +
                                    '</thead>' +
                                    '<tbody>';
                                    
                    for (var j = 0; j < itemNodes.length; j++) {
                        var item = itemNodes[j];
                        var productName = item.getElementsByTagName("product_name")[0].textContent;
                        var size = item.getElementsByTagName("size")[0].textContent;
                        var color = item.getElementsByTagName("color")[0].textContent;
                        var price = parseFloat(item.getElementsByTagName("price")[0].textContent).toFixed(2);
                        var quantity = item.getElementsByTagName("quantity")[0].textContent;
                        var itemTotal = parseFloat(price * quantity).toFixed(2);
                        
                        itemsHTML += '<tr>' +
                                    '<td>' + productName + '</td>' +
                                    '<td>' + size + '</td>' +
                                    '<td>' + color + '</td>' +
                                    '<td>₱' + price + '</td>' +
                                    '<td>' + quantity + '</td>' +
                                    '<td>₱' + itemTotal + '</td>' +
                                    '</tr>';
                    }
                    
                    itemsHTML += '</tbody></table>';
                    
                    // Construct the final HTML
                    orderHTML = '<div class="order-details">' +
                                '<div class="order-meta">' +
                                  '<div class="order-meta-item">' +
                                    '<div class="order-meta-label">Order ID</div>' +
                                    '<div class="order-meta-value">' + orderId + '</div>' +
                                  '</div>' +
                                  '<div class="order-meta-item">' +
                                    '<div class="order-meta-label">Date</div>' +
                                    '<div class="order-meta-value">' + date + '</div>' +
                                  '</div>' +
                                  '<div class="order-meta-item">' +
                                    '<div class="order-meta-label">Status</div>' +
                                    '<div class="order-meta-value"><span class="order-status status-' + status.toLowerCase() + '">' + status + '</span></div>' +
                                  '</div>' +
                                  '<div class="order-meta-item">' +
                                    '<div class="order-meta-label">Payment Method</div>' +
                                    '<div class="order-meta-value">' + payment + '</div>' +
                                  '</div>' +
                                '</div>' +
                                
                                '<div class="customer-info">' +
                                  '<h5>Customer Information</h5>' +
                                  '<div class="customer-details">' +
                                    '<p><strong>Name:</strong> ' + customerName + '</p>' +
                                    '<p><strong>Email:</strong> ' + customerEmail + '</p>' +
                                    '<p><strong>Phone:</strong> ' + customerPhone + '</p>' +
                                    '<p><strong>Shipping Address:</strong> ' + shippingAddress + '</p>' +
                                  '</div>' +
                                '</div>' +
                                
                                '<div class="items-list">' +
                                  '<h5>Order Items</h5>' +
                                  itemsHTML +
                                '</div>' +
                                
                                '<div class="order-summary" style="background-color: #f9f9f9; padding: 15px; border-radius: 5px;">' +
                                  '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">' +
                                    '<span>Subtotal:</span>' +
                                    '<span>₱' + subtotal + '</span>' +
                                  '</div>' +
                                  '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">' +
                                    '<span>Shipping Fee:</span>' +
                                    '<span>₱' + shippingFee + '</span>' +
                                  '</div>' +
                                  '<div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e0e0e0;">' +
                                    '<span>Total:</span>' +
                                    '<span>₱' + totalAmount + '</span>' +
                                  '</div>' +
                                '</div>' +
                              '</div>';
                              
                    break;
                }
            }
            
            return orderHTML;
        }
        
        function closeViewModal() {
            document.getElementById('viewOrderModal').classList.remove('show');
        }
        
        // Change Order Status
        function changeStatus(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('current_status').value = currentStatus;
            document.getElementById('previousStatus').value = currentStatus;
            
            var statusSelect = document.getElementById('new_status');
            for (var i = 0; i < statusSelect.options.length; i++) {
                if (statusSelect.options[i].value.toLowerCase() === currentStatus.toLowerCase()) {
                    statusSelect.options[i].selected = true;
                    break;
                }
            }
            
            document.getElementById('changeStatusModal').classList.add('show');
            
            // Add warning when selecting 'shipped' status
            document.getElementById('new_status').addEventListener('change', function() {
                var warningEl = document.getElementById('statusWarning');
                if (this.value === 'shipped' && currentStatus.toLowerCase() !== 'shipped') {
                    warningEl.style.display = 'block';
                } else {
                    warningEl.style.display = 'none';
                }
            });
        }
        
        function closeStatusModal() {
            document.getElementById('changeStatusModal').classList.remove('show');
        }
        
        // Close modals when clicking outside of them
        window.addEventListener('click', function(event) {
            var viewModal = document.getElementById('viewOrderModal');
            var statusModal = document.getElementById('changeStatusModal');
            
            if (event.target === viewModal) {
                viewModal.classList.remove('show');
            }
            
            if (event.target === statusModal) {
                statusModal.classList.remove('show');
            }
        });
        
        // Initialize any UI elements that need it
        document.addEventListener('DOMContentLoaded', function() {
            // Any initialization code would go here
            
            // Make status badges visually distinct
            var statusElements = document.querySelectorAll('.order-status');
            statusElements.forEach(function(el) {
                var status = el.classList[1];
                
                // Apply different styling based on status
                switch(status) {
                    case 'status-pending':
                        el.style.backgroundColor = '#e2e3e5';
                        el.style.color = '#383d41';
                        break;
                    case 'status-processing':
                        el.style.backgroundColor = '#ffeeba';
                        el.style.color = '#856404';
                        break;
                    case 'status-shipped':
                        el.style.backgroundColor = '#b8daff';
                        el.style.color = '#004085';
                        break;
                    case 'status-delivered':
                        el.style.backgroundColor = '#c3e6cb';
                        el.style.color = '#155724';
                        break;
                    case 'status-cancelled':
                        el.style.backgroundColor = '#f5c6cb';
                        el.style.color = '#721c24';
                        break;
                }
            });
        });
    </script>
</body>
</html>