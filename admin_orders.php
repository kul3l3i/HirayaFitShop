<?php
// Start the session at the very beginning
session_start();

// Database connection configuration
$db_host = 'localhost';
$db_user = 'root'; // Change to your DB username
$db_pass = '';     // Change to your DB password
$db_name = 'hirayafitdb'; // Change to your DB name

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Load transactions XML file
$transactionsFile = 'data/transactions.xml';
$transactions = [];

if (file_exists($transactionsFile)) {
    $transactionsXml = simplexml_load_file($transactionsFile);
    
    if ($transactionsXml) {
        foreach ($transactionsXml->transaction as $transaction) {
            $transactions[] = $transaction;
        }
    }
}

// Load products XML file
$productsFile = 'data/products.xml';
$products = [];

if (file_exists($productsFile)) {
    $productsXml = simplexml_load_file($productsFile);
    
    if ($productsXml) {
        foreach ($productsXml->product as $product) {
            $products[(string)$product->id] = $product;
        }
    }
}

// Handle status change
if (isset($_POST['update_status'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_status = $_POST['new_status'];
    $old_status = $_POST['old_status'];
    
    // Load XML file for update
    $xml = simplexml_load_file($transactionsFile);
    
    // Find the transaction and update its status
    foreach ($xml->transaction as $transaction) {
        if ((string)$transaction->transaction_id == $transaction_id) {
            $transaction->status = $new_status;
            
            // If the new status is "shipped" and old status was not "shipped", update product stock
            if ($new_status == "shipped" && $old_status != "shipped") {
                // Get items in this transaction
                foreach ($transaction->items->item as $item) {
                    $product_id = (string)$item->product_id;
                    $quantity = (int)$item->quantity;
                    
                    // Load products XML
                    $productsXml = simplexml_load_file($productsFile);
                    
                    // Find the product and update stock
                    foreach ($productsXml->product as $product) {
                        if ((string)$product->id == $product_id) {
                            $current_stock = (int)$product->stock;
                            $new_stock = max(0, $current_stock - $quantity);
                            $product->stock = $new_stock;
                            break;
                        }
                    }
                    
                    // Save product XML
                    $productsXml->asXML($productsFile);
                }
            }
            
            break;
        }
    }
    
    // Save the updated XML back to file
    $xml->asXML($transactionsFile);
    
    // Redirect to refresh data
    header("Location: orders_admin.php?status_updated=1");
    exit;
}

// Get filter values
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Close the statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Order Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        
        /* Orders Container */
        .orders-container {
            padding: 30px;
        }
        
        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        .btn-primary:hover {
            background-color: #005fa7;
        }
        
        .btn-export {
            background-color: var(--success);
            color: white;
            border-color: var(--success);
        }
        
        .btn-export:hover {
            background-color: #218838;
        }
        
        .btn i {
            margin-right: 6px;
        }
        
        /* Filters */
        .filters {
            background-color: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .filters-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            transition: border-color 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            outline: 0;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Orders Table */
        .orders-table-wrapper {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 1px solid #dee2e6;
        }
        
        .orders-table td {
            padding: 12px 15px;
            font-size: 14px;
            color: #212529;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .orders-table tr:last-child td {
            border-bottom: none;
        }
        
        .orders-table tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-view {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #005fa7;
        }
        
        /* Order Details Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
            z-index: 2000;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            border-radius: 6px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: modalShow 0.3s ease-out;
        }
        
        @keyframes modalShow {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .order-detail-section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .detail-label {
            width: 150px;
            font-weight: 500;
            color: #495057;
        }
        
        .detail-value {
            flex: 1;
            color: #212529;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .order-items-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-items-table td {
            padding: 8px 10px;
            font-size: 14px;
            color: #212529;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-total {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .status-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        
        .status-form-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .select-status {
            padding: 8px 12px;
            width: 200px;
            border-radius: 4px;
            border: 1px solid #ced4da;
            font-size: 14px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Alert */
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }
            
            .filters-form {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-group {
                width: 100%;
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
                gap: 10px;
            }
            
            .orders-table-wrapper {
                overflow-x: auto;
            }
            
            .orders-table {
                min-width: 800px;
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
                        <a href="change-password.php"><i class="fas fa-lock"></i> Change Password</a>
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Orders Content -->
        <div class="orders-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Order Management</h1>
                <div class="header-actions">
                    <a href="export_orders.php" class="btn btn-export"><i class="fas fa-file-export"></i> Export Orders</a>
                </div>
            </div>
            
            <?php if (isset($_GET['status_updated']) && $_GET['status_updated'] == 1): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Order status has been successfully updated.