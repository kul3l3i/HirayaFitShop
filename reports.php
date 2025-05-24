<?php


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

// Close the statement
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Reports & Analytics</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Fixed jsPDF loading with proper version and autoTable plugin -->
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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

        /* Reports Page Specific Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .export-controls {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #005a9c;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-outline {
            background-color: transparent;
            color: var(--dark);
            border: 1px solid #ddd;
        }

        .btn-outline:hover {
            background-color: #f8f9fa;
        }

        .btn-reset {
            background-color: var(--grey);
            color: white;
        }

        .btn-reset:hover {
            background-color: #5a6268;
        }

        .btn-refresh {
            background-color: var(--info);
            color: white;
        }

        .btn-refresh:hover {
            background-color: #138496;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .form-control {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--secondary);
        }

        .stat-card.success::before {
            background: var(--success);
        }

        .stat-card.warning::before {
            background: var(--warning);
        }

        .stat-card.info::before {
            background: var(--info);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 14px;
            color: var(--grey);
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .stat-icon.primary {
            background: var(--secondary);
        }

        .stat-icon.success {
            background: var(--success);
        }

        .stat-icon.warning {
            background: var(--warning);
        }

        .stat-icon.info {
            background: var(--info);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-change.positive {
            color: var(--success);
        }

        .stat-change.negative {
            color: var(--danger);
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Tables */
        .table-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .data-table td {
            font-size: 14px;
            color: var(--grey);
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-processing {
            background-color: rgb(219, 190, 228);
            color: rgb(85, 30, 122);
        }

        /* PDF Export Loading */
        .pdf-loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            text-align: center;
            display: none;
        }

        .pdf-loading.show {
            display: block;
        }

        /* Last Updated Indicator */
        .last-updated {
            font-size: 12px;
            color: var(--grey);
            margin-bottom: 15px;
            text-align: right;
        }

        .last-updated.updated {
            color: var(--success);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .charts-section {
                grid-template-columns: 1fr;
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

            .export-controls {
                width: 100%;
                justify-content: stretch;
            }

            .export-controls .btn {
                flex: 1;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading indicator */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--secondary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- PDF Loading Overlay -->
    <div class="pdf-loading" id="pdfLoading">
        <div class="spinner" style="margin: 0 auto 20px;"></div>
        <h3>Generating PDF Report...</h3>
        <p>Please wait while we prepare your report.</p>
    </div>

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
            <a href="payment-history.php"><i class="fas fa-money-check-alt"></i> Payment History</a>

            <div class="menu-title">INVENTORY</div>
            <a href="products.php"><i class="fas fa-tshirt"></i> Products & Categories</a>
            <a href="stock.php"><i class="fas fa-box"></i> Stock Management</a>

            <div class="menu-title">USERS</div>
            <a href="users.php"><i class="fas fa-users"></i> User Management</a>

            <div class="menu-title">REPORTS & SETTINGS</div>
            <a href="reports.php" class="active"><i class="fas fa-file-pdf"></i> Reports & Analytics</a>
            <a href="settings.php"><i class="fas fa-cog"></i> System Settings</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="nav-left">
                <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <span class="navbar-title">Reports & Analytics</span>
                <div class="welcome-text">Comprehensive business insights</div>
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Reports & Analytics</h1>
                <div class="export-controls">
                    <button class="btn btn-refresh" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i> Refresh Data
                    </button>
                    <button class="btn btn-success" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-outline" onclick="exportToCSV()">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                </div>
            </div>

            <!-- Last Updated Indicator -->
            <div class="last-updated" id="lastUpdated">
                Last updated: Loading...
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h3 class="filter-title">Filter Reports</h3>
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Date Range</label>
                        <select class="form-control" id="dateRange">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="delivered">Delivered</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="processing">Processing</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" id="paymentFilter">
                            <option value="all">All Methods</option>
                            <option value="gcash">GCash</option>
                            <option value="cod">Cash on Delivery</option>
                            <option value="paymaya">Paymaya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-reset" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Revenue</span>
                        <div class="stat-icon primary">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="totalRevenue">₱0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> <span id="revenueChange">0%</span> from last month
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-title">Total Orders</span>
                        <div class="stat-icon success">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="totalOrders">0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> <span id="ordersChange">0%</span> from last month
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <span class="stat-title">Average Order Value</span>
                        <div class="stat-icon warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="avgOrderValue">₱0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> <span id="avgOrderChange">0%</span> from last month
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <span class="stat-title">Unique Customers</span>
                        <div class="stat-icon info">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value" id="uniqueCustomers">0</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> <span id="customersChange">0%</span> from last month
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <h3 class="chart-title">Revenue Trend</h3>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Order Status Distribution</h3>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Payment Methods</h3>
                    <div class="chart-container">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3 class="chart-title">Top Products</h3>
                    <div class="chart-container">
                        <canvas id="productsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="table-section">
                <div class="table-header">
                    <h3 class="table-title">Recent Transactions</h3>
                    <button class="btn btn-outline" onclick="viewAllTransactions()">
                        <i class="fas fa-eye"></i> View All
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Customers Table -->
            <div class="table-section">
                <div class="table-header">
                    <h3 class="table-title">Top Customers</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Last Order</th>
                            </tr>
                        </thead>
                        <tbody id="topCustomersTableBody">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables to store data and chart instances
        let transactionsData = [];
        let originalTransactionsData = [];
        let chartInstances = {}; // Store chart instances for proper destruction
        let lastLoadTime = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function () {
            showLoading();
            loadDataWithCacheBusting();
        });

        // Main data loading function with cache busting
        async function loadDataWithCacheBusting() {
            try {
                await loadTransactionsFromXML();
                originalTransactionsData = JSON.parse(JSON.stringify(transactionsData));
                updateStatistics();
                initializeCharts();
                populateTransactionsTable();
                populateTopCustomersTable();
                setupEventListeners();
                updateLastUpdatedTime();
                hideLoading();
            } catch (error) {
                console.error('Error loading transactions:', error);
                loadSampleData();
                originalTransactionsData = JSON.parse(JSON.stringify(transactionsData));
                updateStatistics();
                initializeCharts();
                populateTransactionsTable();
                populateTopCustomersTable();
                setupEventListeners();
                updateLastUpdatedTime();
                hideLoading();
            }
        }

        // Refresh data function
        async function refreshData() {
            showLoading();
            
            // Clear any cached data
            if ('caches' in window) {
                caches.delete('transaction-cache');
            }
            
            try {
                await loadTransactionsFromXML();
                originalTransactionsData = JSON.parse(JSON.stringify(transactionsData));
                
                // Reset filters to show all data
                document.getElementById('dateRange').value = 'month';
                document.getElementById('statusFilter').value = 'all';
                document.getElementById('paymentFilter').value = 'all';
                
                updateStatistics();
                initializeCharts();
                populateTransactionsTable();
                populateTopCustomersTable();
                updateLastUpdatedTime();
                
                // Show success message
                const lastUpdatedEl = document.getElementById('lastUpdated');
                lastUpdatedEl.classList.add('updated');
                setTimeout(() => {
                    lastUpdatedEl.classList.remove('updated');
                }, 3000);
                
                hideLoading();
                console.log('Data refreshed successfully');
            } catch (error) {
                console.error('Error refreshing data:', error);
                hideLoading();
                alert('Error refreshing data. Please try again.');
            }
        }

        // Update last updated time
        function updateLastUpdatedTime() {
            lastLoadTime = new Date();
            const lastUpdatedEl = document.getElementById('lastUpdated');
            lastUpdatedEl.textContent = `Last updated: ${lastLoadTime.toLocaleString()}`;
        }

        // Show/Hide loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }

        // Show/Hide PDF loading
        function showPDFLoading() {
            document.getElementById('pdfLoading').classList.add('show');
        }

        function hidePDFLoading() {
            document.getElementById('pdfLoading').classList.remove('show');
        }

        // Load transactions from XML file with cache busting
        async function loadTransactionsFromXML() {
            try {
                console.log('Attempting to load data from transaction.xml...');

                // Add cache busting parameters
                const timestamp = new Date().getTime();
                const randomParam = Math.random().toString(36).substring(7);
                
                const possiblePaths = [
                    `transaction.xml?t=${timestamp}&r=${randomParam}`,
                    `./transaction.xml?t=${timestamp}&r=${randomParam}`,
                    `../transaction.xml?t=${timestamp}&r=${randomParam}`,
                    `data/transaction.xml?t=${timestamp}&r=${randomParam}`
                ];

                let response = null;
                let xmlText = null;

                for (const path of possiblePaths) {
                    try {
                        console.log(`Trying to load from: ${path}`);
                        
                        // Use fetch with cache-busting headers
                        response = await fetch(path, {
                            method: 'GET',
                            headers: {
                                'Cache-Control': 'no-cache, no-store, must-revalidate',
                                'Pragma': 'no-cache',
                                'Expires': '0'
                            },
                            cache: 'no-store'
                        });
                        
                        if (response.ok) {
                            xmlText = await response.text();
                            console.log(`Successfully loaded XML from: ${path}`);
                            break;
                        }
                    } catch (pathError) {
                        console.log(`Failed to load from ${path}:`, pathError.message);
                        continue;
                    }
                }

                if (!response || !response.ok || !xmlText) {
                    throw new Error(`Failed to fetch transaction.xml from any path. Status: ${response ? response.status : 'No response'}`);
                }

                console.log('XML file loaded successfully, content length:', xmlText.length);

                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlText, 'text/xml');

                const parserError = xmlDoc.querySelector('parsererror');
                if (parserError) {
                    console.error('XML parsing error:', parserError.textContent);
                    throw new Error('XML parsing error: ' + parserError.textContent);
                }

                let transactions = xmlDoc.querySelectorAll('transaction');
                if (transactions.length === 0) {
                    transactions = xmlDoc.querySelectorAll('transactions transaction');
                    if (transactions.length === 0) {
                        transactions = xmlDoc.querySelectorAll('root transaction');
                        if (transactions.length === 0) {
                            transactions = xmlDoc.querySelectorAll('data transaction');
                        }
                    }
                }

                if (transactions.length === 0) {
                    throw new Error('No transactions found in the XML file. Please check the XML structure.');
                }

                console.log(`Found ${transactions.length} transactions in XML file`);

                transactionsData = [];

                transactions.forEach((transaction, index) => {
                    try {
                        // Get shipping info first
                        const shippingInfo = transaction.querySelector('shipping_info');
                        let customerName = 'Unknown Customer';
                        let customerEmail = 'unknown@email.com';
                        
                        if (shippingInfo) {
                            customerName = getXMLValue(shippingInfo, 'fullname') || 'Unknown Customer';
                            customerEmail = getXMLValue(shippingInfo, 'email') || 'unknown@email.com';
                        }

                        const transactionObj = {
                            transaction_id: getXMLValue(transaction, 'transaction_id') || `TRX-${Date.now()}-${index}`,
                            user_id: parseInt(getXMLValue(transaction, 'user_id') || '0'),
                            customer_name: customerName,
                            customer_email: customerEmail,
                            transaction_date: getXMLValue(transaction, 'transaction_date') || new Date().toISOString(),
                            status: getXMLValue(transaction, 'status') || 'pending',
                            payment_method: getXMLValue(transaction, 'payment_method') || 'unknown',
                            subtotal: parseFloat(getXMLValue(transaction, 'subtotal') || '0'),
                            shipping_fee: parseFloat(getXMLValue(transaction, 'shipping_fee') || '0'),
                            total_amount: parseFloat(getXMLValue(transaction, 'total_amount') || '0'),
                            items: []
                        };

                        let items = transaction.querySelectorAll('item');
                        if (items.length === 0) {
                            items = transaction.querySelectorAll('items item');
                            if (items.length === 0) {
                                items = transaction.querySelectorAll('products product');
                            }
                        }

                        items.forEach((item, itemIndex) => {
                            const itemObj = {
                                product_id: parseInt(getXMLValue(item, 'product_id') || '0'),
                                product_name: getXMLValue(item, 'product_name') || 'Unknown Product',
                                price: parseFloat(getXMLValue(item, 'price') || '0'),
                                quantity: parseInt(getXMLValue(item, 'quantity') || '1'),
                                color: getXMLValue(item, 'color') || 'N/A',
                                size: getXMLValue(item, 'size') || 'N/A'
                            };

                            transactionObj.items.push(itemObj);
                        });

                        transactionsData.push(transactionObj);

                    } catch (itemError) {
                        console.error(`Error processing transaction ${index + 1}:`, itemError);
                    }
                });

                console.log('Successfully loaded', transactionsData.length, 'transactions from XML');

                if (transactionsData.length === 0) {
                    throw new Error('Failed to parse any valid transactions from the XML file');
                }

                return transactionsData;

            } catch (error) {
                console.error('Error loading transaction.xml:', error);
                throw error; // Re-throw to trigger fallback
            }
        }

        // Helper function to get XML element value
        function getXMLValue(parent, tagName) {
            try {
                let element = parent.querySelector(tagName);

                if (!element) {
                    const allElements = parent.querySelectorAll('*');
                    for (const el of allElements) {
                        if (el.tagName.toLowerCase() === tagName.toLowerCase()) {
                            element = el;
                            break;
                        }
                    }
                }

                const value = element ? element.textContent.trim() : '';
                return value;
            } catch (error) {
                console.error(`Error getting XML value for ${tagName}:`, error);
                return '';
            }
        }

        // Fallback sample data
        function loadSampleData() {
            console.log('Loading sample data as fallback...');
            
            transactionsData = [
                {
                    transaction_id: 'TRX-2024-001',
                    user_id: 1,
                    customer_name: 'John Doe',
                    customer_email: 'john.doe@email.com',
                    transaction_date: '2024-01-15T10:30:00Z',
                    status: 'delivered',
                    payment_method: 'gcash',
                    subtotal: 1500,
                    shipping_fee: 100,
                    total_amount: 1600,
                    items: [
                        {
                            product_id: 1,
                            product_name: 'HirayaFit Athletic Shirt',
                            price: 750,
                            quantity: 2,
                            color: 'Blue',
                            size: 'M'
                        }
                    ]
                },
                {
                    transaction_id: 'TRX-2024-002',
                    user_id: 2,
                    customer_name: 'Jane Smith',
                    customer_email: 'jane.smith@email.com',
                    transaction_date: '2024-01-16T14:20:00Z',
                    status: 'pending',
                    payment_method: 'cod',
                    subtotal: 2200,
                    shipping_fee: 150,
                    total_amount: 2350,
                    items: [
                        {
                            product_id: 2,
                            product_name: 'HirayaFit Leggings',
                            price: 1100,
                            quantity: 2,
                            color: 'Black',
                            size: 'S'
                        }
                    ]
                },
                {
                    transaction_id: 'TRX-2024-003',
                    user_id: 3,
                    customer_name: 'Mike Johnson',
                    customer_email: 'mike.johnson@email.com',
                    transaction_date: '2024-01-17T09:15:00Z',
                    status: 'delivered',
                    payment_method: 'paymaya',
                    subtotal: 1800,
                    shipping_fee: 120,
                    total_amount: 1920,
                    items: [
                        {
                            product_id: 3,
                            product_name: 'HirayaFit Sports Bra',
                            price: 900,
                            quantity: 2,
                            color: 'Pink',
                            size: 'M'
                        }
                    ]
                }
            ];

            return transactionsData;
        }

        // Calculate and update statistics
        function updateStatistics() {
            const totalRevenue = transactionsData
                .filter(t => t.status === 'delivered')
                .reduce((sum, t) => sum + t.total_amount, 0);

            const totalOrders = transactionsData.length;
            const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;
            const uniqueCustomers = new Set(transactionsData.map(t => t.customer_email)).size;

            document.getElementById('totalRevenue').textContent = `₱${totalRevenue.toLocaleString()}`;
            document.getElementById('totalOrders').textContent = totalOrders.toLocaleString();
            document.getElementById('avgOrderValue').textContent = `₱${avgOrderValue.toFixed(2)}`;
            document.getElementById('uniqueCustomers').textContent = uniqueCustomers.toLocaleString();

            // Sample growth percentages
            document.getElementById('revenueChange').textContent = '12.5%';
            document.getElementById('ordersChange').textContent = '8.3%';
            document.getElementById('avgOrderChange').textContent = '5.2%';
            document.getElementById('customersChange').textContent = '15.7%';
        }

        // Setup event listeners
        function setupEventListeners() {
            const adminDropdown = document.getElementById('adminDropdown');
            if (adminDropdown) {
                adminDropdown.addEventListener('click', function (e) {
                    e.stopPropagation();
                    adminDropdown.classList.toggle('show');
                });
            }

            document.addEventListener('click', function (e) {
                if (adminDropdown && !adminDropdown.contains(e.target)) {
                    adminDropdown.classList.remove('show');
                }
            });

            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.querySelector('.sidebar');

            if (toggleSidebar && sidebar) {
                toggleSidebar.addEventListener('click', function () {
                    sidebar.classList.toggle('active');
                });
            }

            if (sidebarClose && sidebar) {
                sidebarClose.addEventListener('click', function () {
                    sidebar.classList.remove('active');
                });
            }
        }

        // Initialize all charts
        function initializeCharts() {
            Object.values(chartInstances).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            chartInstances = {};

            chartInstances.revenue = initializeRevenueChart();
            chartInstances.status = initializeStatusChart();
            chartInstances.payment = initializePaymentChart();
            chartInstances.products = initializeProductsChart();
        }

        // Revenue Trend Chart
        function initializeRevenueChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return null;

            const monthlyRevenue = calculateMonthlyRevenue();

            return new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: monthlyRevenue,
                        borderColor: '#0071c5',
                        backgroundColor: 'rgba(0, 113, 197, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Calculate monthly revenue
        function calculateMonthlyRevenue() {
            const monthlyData = new Array(12).fill(0);

            transactionsData.forEach(transaction => {
                if (transaction.status === 'delivered') {
                    const date = new Date(transaction.transaction_date);
                    const month = date.getMonth();
                    monthlyData[month] += transaction.total_amount;
                }
            });

            return monthlyData;
        }

        // Order Status Chart
        function initializeStatusChart() {
            const ctx = document.getElementById('statusChart');
            if (!ctx) return null;

            const statusCounts = calculateStatusCounts();

            return new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Delivered', 'Pending', 'Cancelled', 'Processing'],
                    datasets: [{
                        data: [statusCounts.delivered, statusCounts.pending, statusCounts.cancelled, statusCounts.processing],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6f42c1'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Calculate status counts
        function calculateStatusCounts() {
            const counts = { delivered: 0, pending: 0, cancelled: 0, processing: 0 };

            transactionsData.forEach(transaction => {
                if (counts.hasOwnProperty(transaction.status)) {
                    counts[transaction.status]++;
                }
            });

            return counts;
        }

        // Payment Methods Chart
        function initializePaymentChart() {
            const ctx = document.getElementById('paymentChart');
            if (!ctx) return null;

            const paymentCounts = calculatePaymentCounts();

            return new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['GCash', 'Cash on Delivery', 'Paymaya'],
                    datasets: [{
                        data: [paymentCounts.gcash, paymentCounts.cod, paymentCounts.paymaya],
                        backgroundColor: ['#0071c5', '#17a2b8', '#6f42c1'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Calculate payment method counts
        function calculatePaymentCounts() {
            const counts = { gcash: 0, cod: 0, paymaya: 0 };

            transactionsData.forEach(transaction => {
                const method = transaction.payment_method.toLowerCase();
                if (method === 'gcash') counts.gcash++;
                else if (method === 'cod' || method === 'cash on delivery') counts.cod++;
                else if (method === 'paymaya') counts.paymaya++;
            });

            return counts;
        }

        // Top Products Chart
        function initializeProductsChart() {
            const ctx = document.getElementById('productsChart');
            if (!ctx) return null;

            const productData = calculateTopProducts();

            return new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: productData.labels,
                    datasets: [{
                        label: 'Units Sold',
                        data: productData.data,
                        backgroundColor: '#0071c5',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Calculate top products
        function calculateTopProducts() {
            const productCounts = {};

            transactionsData.forEach(transaction => {
                transaction.items.forEach(item => {
                    const productName = item.product_name;
                    if (productCounts[productName]) {
                        productCounts[productName] += item.quantity;
                    } else {
                        productCounts[productName] = item.quantity;
                    }
                });
            });

            const sortedProducts = Object.entries(productCounts)
                .sort(([, a], [, b]) => b - a)
                .slice(0, 5);

            return {
                labels: sortedProducts.map(([name]) => name),
                data: sortedProducts.map(([, count]) => count)
            };
        }

        // Populate transactions table
        function populateTransactionsTable() {
            const tbody = document.getElementById('transactionsTableBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            const sortedTransactions = [...transactionsData].sort((a, b) => {
                return new Date(b.transaction_date) - new Date(a.transaction_date);
            });

            const recentTransactions = sortedTransactions.slice(0, 10);

            recentTransactions.forEach(transaction => {
                const row = document.createElement('tr');
                const date = new Date(transaction.transaction_date).toLocaleDateString();
                const statusClass = `status-${transaction.status}`;
                const itemsCount = transaction.items.length;
                const itemsText = itemsCount === 1 ? '1 item' : `${itemsCount} items`;

                row.innerHTML = `
                    <td>${transaction.transaction_id}</td>
                    <td>${transaction.customer_name}</td>
                    <td>${date}</td>
                    <td><span class="status-badge ${statusClass}">${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}</span></td>
                    <td>${transaction.payment_method.toUpperCase()}</td>
                    <td>₱${transaction.total_amount.toLocaleString()}</td>
                    <td>${itemsText}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Populate top customers table
        function populateTopCustomersTable() {
            const tbody = document.getElementById('topCustomersTableBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            const customerStats = {};

            transactionsData.forEach(transaction => {
                const email = transaction.customer_email;

                if (!customerStats[email]) {
                    customerStats[email] = {
                        name: transaction.customer_name,
                        email: email,
                        totalOrders: 0,
                        totalSpent: 0,
                        lastOrder: new Date(0)
                    };
                }

                customerStats[email].totalOrders++;
                customerStats[email].totalSpent += transaction.total_amount;

                const orderDate = new Date(transaction.transaction_date);
                if (orderDate > customerStats[email].lastOrder) {
                    customerStats[email].lastOrder = orderDate;
                }
            });

            const sortedCustomers = Object.values(customerStats)
                .sort((a, b) => b.totalSpent - a.totalSpent)
                .slice(0, 5);

            sortedCustomers.forEach(customer => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${customer.name}</td>
                    <td>${customer.email}</td>
                    <td>${customer.totalOrders}</td>
                    <td>₱${customer.totalSpent.toLocaleString()}</td>
                    <td>${customer.lastOrder.toLocaleDateString()}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Apply filters function
        function applyFilters() {
            showLoading();

            setTimeout(() => {
                try {
                    const dateRange = document.getElementById('dateRange').value;
                    const statusFilter = document.getElementById('statusFilter').value;
                    const paymentFilter = document.getElementById('paymentFilter').value;

                    let filteredData = JSON.parse(JSON.stringify(originalTransactionsData));

                    // Apply date range filter
                    if (dateRange !== 'all') {
                        const now = new Date();
                        let startDate = new Date();

                        switch (dateRange) {
                            case 'today':
                                startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                                break;
                            case 'week':
                                startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                                break;
                            case 'month':
                                startDate = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                                break;
                            case 'quarter':
                                startDate = new Date(now.getFullYear(), now.getMonth() - 3, now.getDate());
                                break;
                            case 'year':
                                startDate = new Date(now.getFullYear() - 1, now.getMonth(), now.getDate());
                                break;
                        }

                        filteredData = filteredData.filter(t => {
                            const transactionDate = new Date(t.transaction_date);
                            return transactionDate >= startDate;
                        });
                    }

                    // Apply status filter
                    if (statusFilter && statusFilter !== 'all') {
                        filteredData = filteredData.filter(t => t.status === statusFilter);
                    }

                    // Apply payment method filter
                    if (paymentFilter && paymentFilter !== 'all') {
                        filteredData = filteredData.filter(t => t.payment_method.toLowerCase() === paymentFilter.toLowerCase());
                    }

                    transactionsData = filteredData;

                    updateStatistics();
                    initializeCharts();
                    populateTransactionsTable();
                    populateTopCustomersTable();

                    console.log('Applied filters:', { dateRange, statusFilter, paymentFilter });
                    console.log('Filtered results:', filteredData.length, 'transactions');

                } catch (error) {
                    console.error('Error applying filters:', error);
                    alert('Error applying filters. Please try again.');
                } finally {
                    hideLoading();
                }
            }, 100);
        }

        // Reset filters function
        function resetFilters() {
            document.getElementById('dateRange').value = 'month';
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('paymentFilter').value = 'all';

            transactionsData = JSON.parse(JSON.stringify(originalTransactionsData));

            updateStatistics();
            initializeCharts();
            populateTransactionsTable();
            populateTopCustomersTable();

            console.log('Filters reset, showing all data');
        }

        // FIXED Export to PDF function
        function exportToPDF() {
            try {
                // Check if jsPDF is available
                if (typeof window.jspdf === 'undefined') {
                    alert('PDF export library not loaded. Please refresh the page and try again.');
                    return;
                }

                showPDFLoading();

                // Use setTimeout to ensure loading overlay shows
                setTimeout(() => {
                    try {
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF();

                        // Brand colors
                        const primaryColor = [17, 17, 17]; // #111111
                        const secondaryColor = [0, 113, 197]; // #0071c5
                        const greyColor = [118, 118, 118]; // #767676
                        const successColor = [40, 167, 69]; // #28a745

                        // Calculate statistics
                        const totalRevenue = transactionsData
                            .filter(t => t.status === 'delivered')
                            .reduce((sum, t) => sum + t.total_amount, 0);
                        const totalOrders = transactionsData.length;
                        const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;
                        const uniqueCustomers = new Set(transactionsData.map(t => t.customer_email)).size;

                        // Add header background
                        doc.setFillColor(...primaryColor);
                        doc.rect(0, 0, 210, 40, 'F');

                        // Add logo/title
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(24);
                        doc.setFont(undefined, 'bold');
                        doc.text('HirayaFit', 20, 20);

                        doc.setTextColor(...secondaryColor);
                        doc.text('Fit', 55, 20);

                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(16);
                        doc.setFont(undefined, 'normal');
                        doc.text('Sales Report & Analytics', 20, 30);

                        // Add generation date
                        doc.setTextColor(...greyColor);
                        doc.setFontSize(10);
                        doc.text(`Generated on: ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}`, 20, 50);

                        // Add summary statistics section
                        doc.setTextColor(...primaryColor);
                        doc.setFontSize(16);
                        doc.setFont(undefined, 'bold');
                        doc.text('Executive Summary', 20, 65);

                        // Statistics cards
                        let yPos = 75;
                        const cardHeight = 25;
                        const cardWidth = 85;
                        const cardSpacing = 10;

                        // Revenue card
                        doc.setFillColor(...secondaryColor);
                        doc.rect(20, yPos, cardWidth, cardHeight, 'F');
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(10);
                        doc.text('Total Revenue', 25, yPos + 8);
                        doc.setFontSize(14);
                        doc.setFont(undefined, 'bold');
                        doc.text(`₱${totalRevenue.toLocaleString()}`, 25, yPos + 18);

                        // Orders card
                        doc.setFillColor(...successColor);
                        doc.rect(20 + cardWidth + cardSpacing, yPos, cardWidth, cardHeight, 'F');
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(10);
                        doc.setFont(undefined, 'normal');
                        doc.text('Total Orders', 25 + cardWidth + cardSpacing, yPos + 8);
                        doc.setFontSize(14);
                        doc.setFont(undefined, 'bold');
                        doc.text(totalOrders.toString(), 25 + cardWidth + cardSpacing, yPos + 18);

                        yPos += cardHeight + 15;

                        // Average Order Value card
                        doc.setFillColor(255, 193, 7); // Warning color
                        doc.rect(20, yPos, cardWidth, cardHeight, 'F');
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(10);
                        doc.setFont(undefined, 'normal');
                        doc.text('Avg Order Value', 25, yPos + 8);
                        doc.setFontSize(14);
                        doc.setFont(undefined, 'bold');
                        doc.text(`₱${avgOrderValue.toFixed(2)}`, 25, yPos + 18);

                        // Unique Customers card
                        doc.setFillColor(23, 162, 184); // Info color
                        doc.rect(20 + cardWidth + cardSpacing, yPos, cardWidth, cardHeight, 'F');
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(10);
                        doc.setFont(undefined, 'normal');
                        doc.text('Unique Customers', 25 + cardWidth + cardSpacing, yPos + 8);
                        doc.setFontSize(14);
                        doc.setFont(undefined, 'bold');
                        doc.text(uniqueCustomers.toString(), 25 + cardWidth + cardSpacing, yPos + 18);

                        yPos += cardHeight + 20;

                        // Add transactions table section
                        doc.setTextColor(...primaryColor);
                        doc.setFontSize(16);
                        doc.setFont(undefined, 'bold');
                        doc.text('Recent Transactions', 20, yPos);

                        yPos += 15;

                        // Prepare table data for autoTable
                        const tableData = [];
                        const sortedTransactions = [...transactionsData].sort((a, b) => {
                            return new Date(b.transaction_date) - new Date(a.transaction_date);
                        });

                        // Take only the most recent transactions that fit
                        const maxTransactions = Math.min(sortedTransactions.length, 20);

                        for (let i = 0; i < maxTransactions; i++) {
                            const transaction = sortedTransactions[i];
                            const date = new Date(transaction.transaction_date).toLocaleDateString();
                            const itemsCount = transaction.items.length;
                            const itemsText = itemsCount === 1 ? '1 item' : `${itemsCount} items`;

                            tableData.push([
                                transaction.transaction_id.substring(0, 15) + '...',
                                transaction.customer_name.substring(0, 20),
                                date,
                                transaction.status.toUpperCase(),
                                transaction.payment_method.toUpperCase(),
                                `₱${transaction.total_amount.toLocaleString()}`,
                                itemsText
                            ]);
                        }

                        // Use autoTable if available, otherwise create manual table
                        if (typeof doc.autoTable === 'function') {
                            doc.autoTable({
                                head: [['Transaction ID', 'Customer', 'Date', 'Status', 'Payment', 'Amount', 'Items']],
                                body: tableData,
                                startY: yPos,
                                theme: 'grid',
                                headStyles: {
                                    fillColor: primaryColor,
                                    textColor: [255, 255, 255],
                                    fontSize: 9,
                                    fontStyle: 'bold'
                                },
                                bodyStyles: {
                                    fontSize: 8,
                                    textColor: greyColor
                                },
                                alternateRowStyles: {
                                    fillColor: [252, 252, 252]
                                },
                                margin: { left: 20, right: 20 },
                                columnStyles: {
                                    0: { cellWidth: 25 },
                                    1: { cellWidth: 30 },
                                    2: { cellWidth: 20 },
                                    3: { cellWidth: 20 },
                                    4: { cellWidth: 20 },
                                    5: { cellWidth: 25 },
                                    6: { cellWidth: 15 }
                                }
                            });
                        } else {
                            // Manual table creation if autoTable is not available
                            doc.setFillColor(248, 249, 250);
                            doc.rect(20, yPos - 5, 170, 10, 'F');

                            doc.setTextColor(...primaryColor);
                            doc.setFontSize(9);
                            doc.setFont(undefined, 'bold');
                            doc.text('Transaction ID', 25, yPos);
                            doc.text('Customer', 70, yPos);
                            doc.text('Date', 110, yPos);
                            doc.text('Status', 135, yPos);
                            doc.text('Amount', 160, yPos);

                            yPos += 10;

                            doc.setFont(undefined, 'normal');
                            doc.setFontSize(8);

                            for (let i = 0; i < Math.min(tableData.length, 15); i++) {
                                const row = tableData[i];

                                if (yPos > 270) {
                                    doc.addPage();
                                    yPos = 20;
                                }

                                if (i % 2 === 0) {
                                    doc.setFillColor(252, 252, 252);
                                    doc.rect(20, yPos - 3, 170, 8, 'F');
                                }

                                doc.setTextColor(...greyColor);
                                doc.text(row[0], 25, yPos);
                                doc.text(row[1], 70, yPos);
                                doc.text(row[2], 110, yPos);
                                doc.text(row[3], 135, yPos);
                                doc.text(row[5], 160, yPos);
                                yPos += 8;
                            }
                        }

                        // Add footer to all pages
                        const pageCount = doc.internal.getNumberOfPages();
                        for (let i = 1; i <= pageCount; i++) {
                            doc.setPage(i);
                            doc.setFillColor(...primaryColor);
                            doc.rect(0, 287, 210, 10, 'F');

                            doc.setTextColor(255, 255, 255);
                            doc.setFontSize(8);
                            doc.text(`HirayaFit Sales Report - Page ${i} of ${pageCount}`, 20, 293);
                            doc.text(`© ${new Date().getFullYear()} HirayaFit. All rights reserved.`, 140, 293);
                        }

                        // Save the PDF
                        const fileName = `hirafit-sales-report-${new Date().toISOString().split('T')[0]}.pdf`;
                        doc.save(fileName);

                        hidePDFLoading();
                        
                        // Show success message
                        alert('PDF report generated successfully!');

                    } catch (pdfError) {
                        console.error('Error generating PDF:', pdfError);
                        hidePDFLoading();
                        alert('Error generating PDF report. Please try again or contact support if the issue persists.');
                    }
                }, 500);

            } catch (error) {
                console.error('Error in exportToPDF:', error);
                hidePDFLoading();
                alert('Error generating PDF report. Please try again.');
            }
        }

        // Export to CSV function
        function exportToCSV() {
            try {
                let csvContent = "Transaction ID,Customer Name,Customer Email,Date,Status,Payment Method,Subtotal,Shipping Fee,Total Amount,Items\n";

                transactionsData.forEach(transaction => {
                    const itemsDesc = transaction.items.map(item =>
                        `${item.product_name} (${item.color}, ${item.size}) x${item.quantity}`
                    ).join('; ');

                    const date = new Date(transaction.transaction_date).toLocaleDateString();

                    csvContent += `"${transaction.transaction_id}","${transaction.customer_name}","${transaction.customer_email}","${date}","${transaction.status}","${transaction.payment_method}","${transaction.subtotal}","${transaction.shipping_fee}","${transaction.total_amount}","${itemsDesc}"\n`;
                });

                // Create and download CSV file
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `hirafit-transactions-${new Date().toISOString().split('T')[0]}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                alert('CSV file downloaded successfully!');

            } catch (error) {
                console.error('Error exporting CSV:', error);
                alert('Error generating CSV file. Please try again.');
            }
        }

        // View all transactions function
        function viewAllTransactions() {
            alert(`Showing all ${transactionsData.length} transactions. This would navigate to a detailed transactions page with pagination and advanced filters.`);
        }
    </script>
</body>

</html>