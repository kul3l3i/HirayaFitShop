<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Reports & Analytics</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
                            <img src="https://via.placeholder.com/40x40/0071c5/ffffff?text=A" alt="Admin" class="admin-avatar">
                        </div>
                        <div class="admin-info">
                            <span class="admin-name">Admin User</span>
                            <span class="admin-role">Administrator</span>
                        </div>
                    </div>
                    
                    <div class="admin-dropdown-content" id="adminDropdownContent">
                        <div class="admin-dropdown-header">
                            <div class="admin-dropdown-avatar-container">
                                <img src="https://via.placeholder.com/50x50/0071c5/ffffff?text=A" alt="Admin" class="admin-dropdown-avatar">
                            </div>
                            <div class="admin-dropdown-info">
                                <span class="admin-dropdown-name">Admin User</span>
                                <span class="admin-dropdown-role">Administrator</span>
                            </div>
                        </div>
                        <div class="admin-dropdown-user">
                            <h4 class="admin-dropdown-user-name">Admin User</h4>
                            <p class="admin-dropdown-user-email">admin@hirafit.com</p>
                        </div>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile Settings</a>
                        <a href="change-password.php"><i class="fas fa-lock"></i> Change Password</a>
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
                    <button class="btn btn-success" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-outline" onclick="exportToCSV()">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h3 class="filter-title">Filter Reports</h3>
                <div class="filter-grid">
                    <div class="form-group">
                        <label class="form-label">Date Range</label>
                        <select class="form-control" id="dateRange">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="delivered">Delivered</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" id="paymentFilter">
                            <option value="all">All Methods</option>
                            <option value="gcash">GCash</option>
                            <option value="cod">Cash on Delivery</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Apply Filters
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
                    <div class="stat-value">₱124,500</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +12.5% from last month
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-title">Total Orders</span>
                        <div class="stat-icon success">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-value">1,247</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +8.3% from last month
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <span class="stat-title">Average Order Value</span>
                        <div class="stat-icon warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱1,598</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +5.2% from last month
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <span class="stat-title">Unique Customers</span>
                        <div class="stat-icon info">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value">892</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> +15.7% from last month
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
                            <!-- Sample data will be populated by JavaScript -->
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
                        <tbody>
                            <tr>
                                <td>Lai Rodriguez</td>
                                <td>rodriguez.elx@gmail.com</td>
                                <td>15</td>
                                <td>₱24,750</td>
                                <td>2025-05-23</td>
                            </tr>
                            <tr>
                                <td>Maria Santos</td>
                                <td>maria.santos@email.com</td>
                                <td>12</td>
                                <td>₱18,900</td>
                                <td>2025-05-22</td>
                            </tr>
                            <tr>
                                <td>John Cruz</td>
                                <td>john.cruz@email.com</td>
                                <td>8</td>
                                <td>₱12,400</td>
                                <td>2025-05-21</td>
                            </tr>
                            <tr>
                                <td>Ana Reyes</td>
                                <td>ana.reyes@email.com</td>
                                <td>10</td>
                                <td>₱16,200</td>
                                <td>2025-05-20</td>
                            </tr>
                            <tr>
                                <td>Carlos Garcia</td>
                                <td>carlos.garcia@email.com</td>
                                <td>6</td>
                                <td>₱9,300</td>
                                <td>2025-05-19</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variable to store transactions data
        let transactionsData = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactionsFromXML().then(() => {
                initializeCharts();
                populateTransactionsTable();
                setupEventListeners();
            }).catch(error => {
                console.error('Error loading transactions:', error);
                // Fallback to sample data if XML loading fails
                loadSampleData();
                initializeCharts();
                populateTransactionsTable();
                setupEventListeners();
            });
        });

        // Load transactions from XML file
        async function loadTransactionsFromXML() {
            try {
                const response = await fetch('transac.xml');
                const xmlText = await response.text();
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
                
                // Check for parsing errors
                const parserError = xmlDoc.querySelector('parsererror');
                if (parserError) {
                    throw new Error('XML parsing error: ' + parserError.textContent);
                }
                
                // Parse transactions from XML
                const transactions = xmlDoc.querySelectorAll('transaction');
                transactionsData = [];
                
                transactions.forEach(transaction => {
                    const transactionObj = {
                        transaction_id: getXMLValue(transaction, 'transaction_id'),
                        user_id: parseInt(getXMLValue(transaction, 'user_id')),
                        customer_name: getXMLValue(transaction, 'customer_name'),
                        customer_email: getXMLValue(transaction, 'customer_email'),
                        transaction_date: getXMLValue(transaction, 'transaction_date'),
                        status: getXMLValue(transaction, 'status'),
                        payment_method: getXMLValue(transaction, 'payment_method'),
                        subtotal: parseFloat(getXMLValue(transaction, 'subtotal')),
                        shipping_fee: parseFloat(getXMLValue(transaction, 'shipping_fee')),
                        total_amount: parseFloat(getXMLValue(transaction, 'total_amount')),
                        items: []
                    };
                    
                    // Parse items
                    const items = transaction.querySelectorAll('item');
                    items.forEach(item => {
                        transactionObj.items.push({
                            product_name: getXMLValue(item, 'product_name'),
                            price: parseFloat(getXMLValue(item, 'price')),
                            quantity: parseInt(getXMLValue(item, 'quantity')),
                            color: getXMLValue(item, 'color'),
                            size: getXMLValue(item, 'size')
                        });
                    });
                    
                    transactionsData.push(transactionObj);
                });
                
                console.log('Loaded', transactionsData.length, 'transactions from XML');
                
            } catch (error) {
                console.error('Error loading XML:', error);
                throw error;
            }
        }

        // Helper function to get XML element value
        function getXMLValue(parent, tagName) {
            const element = parent.querySelector(tagName);
            return element ? element.textContent.trim() : '';
        }

        // Fallback sample data (in case XML loading fails)
        function loadSampleData() {
            transactionsData = [
                {
                    transaction_id: 'TRX-683014A91A92D',
                    user_id: 2,
                    customer_name: 'Lai Rodriguez',
                    customer_email: 'rodriguez.elx@gmail.com',
                    transaction_date: '2025-05-23 08:24:41',
                    status: 'delivered',
                    payment_method: 'gcash',
                    subtotal: 1500,
                    shipping_fee: 100,
                    total_amount: 1600,
                    items: [
                        {
                            product_name: "Women's High-Waist Leggings",
                            price: 1500,
                            quantity: 1,
                            color: 'black',
                            size: 'S'
                        }
                    ]
                },
                {
                    transaction_id: 'TRX-683014A91A93E',
                    user_id: 3,
                    customer_name: 'Maria Santos',
                    customer_email: 'maria.santos@email.com',
                    transaction_date: '2025-05-22 14:30:15',
                    status: 'pending',
                    payment_method: 'cod',
                    subtotal: 2400,
                    shipping_fee: 100,
                    total_amount: 2500,
                    items: [
                        {
                            product_name: "Men's Athletic Shorts",
                            price: 1200,
                            quantity: 2,
                            color: 'blue',
                            size: 'M'
                        }
                    ]
                }
            ];
        }

        // Setup event listeners
        function setupEventListeners() {
            // Admin dropdown toggle
            const adminDropdown = document.getElementById('adminDropdown');
            const adminDropdownContent = document.getElementById('adminDropdownContent');
            
            if (adminDropdown) {
                adminDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    adminDropdown.classList.toggle('show');
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (adminDropdown && !adminDropdown.contains(e.target)) {
                    adminDropdown.classList.remove('show');
                }
            });
            
            // Sidebar toggle for responsive design
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleSidebar && sidebar) {
                toggleSidebar.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            if (sidebarClose && sidebar) {
                sidebarClose.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                });
            }
        }

        // Initialize all charts with dynamic data
        function initializeCharts() {
            initializeRevenueChart();
            initializeStatusChart();
            initializePaymentChart();
            initializeProductsChart();
        }

        // Revenue Trend Chart - calculate from actual data
        function initializeRevenueChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;
            
            // Calculate monthly revenue from transactions
            const monthlyRevenue = calculateMonthlyRevenue();
            
            new Chart(ctx.getContext('2d'), {
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
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Calculate monthly revenue from transactions
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

        // Order Status Chart - calculate from actual data
        function initializeStatusChart() {
            const ctx = document.getElementById('statusChart');
            if (!ctx) return;
            
            const statusCounts = calculateStatusCounts();
            
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Delivered', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [statusCounts.delivered, statusCounts.pending, statusCounts.cancelled],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
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
            const counts = { delivered: 0, pending: 0, cancelled: 0 };
            
            transactionsData.forEach(transaction => {
                if (counts.hasOwnProperty(transaction.status)) {
                    counts[transaction.status]++;
                }
            });
            
            return counts;
        }

        // Payment Methods Chart - calculate from actual data
        function initializePaymentChart() {
            const ctx = document.getElementById('paymentChart');
            if (!ctx) return;
            
            const paymentCounts = calculatePaymentCounts();
            
            new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: ['GCash', 'Cash on Delivery', 'Credit/Debit Card'],
                    datasets: [{
                        data: [paymentCounts.gcash, paymentCounts.cod, paymentCounts.card],
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
            const counts = { gcash: 0, cod: 0, card: 0 };
            
            transactionsData.forEach(transaction => {
                const method = transaction.payment_method.toLowerCase();
                if (method === 'gcash') counts.gcash++;
                else if (method === 'cod' || method === 'cash on delivery') counts.cod++;
                else if (method === 'card' || method === 'credit card' || method === 'debit card') counts.card++;
            });
            
            return counts;
        }

        // Top Products Chart - calculate from actual data
        function initializeProductsChart() {
            const ctx = document.getElementById('productsChart');
            if (!ctx) return;
            
            const productData = calculateTopProducts();
            
            new Chart(ctx.getContext('2d'), {
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
            
            // Sort products by quantity and get top 5
            const sortedProducts = Object.entries(productCounts)
                .sort(([,a], [,b]) => b - a)
                .slice(0, 5);
            
            return {
                labels: sortedProducts.map(([name]) => name),
                data: sortedProducts.map(([,count]) => count)
            };
        }

        // Populate transactions table with dynamic data
        function populateTransactionsTable() {
            const tbody = document.getElementById('transactionsTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = '';

            transactionsData.forEach(transaction => {
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

        // Apply filters function - now works with dynamic data
        function applyFilters() {
            const dateRange = document.getElementById('dateRange')?.value;
            const statusFilter = document.getElementById('statusFilter')?.value;
            const paymentFilter = document.getElementById('paymentFilter')?.value;

            // Filter the transactions data
            let filteredData = [...transactionsData];
            
            // Apply status filter
            if (statusFilter && statusFilter !== 'all') {
                filteredData = filteredData.filter(t => t.status === statusFilter);
            }
            
            // Apply payment method filter
            if (paymentFilter && paymentFilter !== 'all') {
                filteredData = filteredData.filter(t => t.payment_method === paymentFilter);
            }
            
            // Apply date range filter
            if (dateRange && dateRange !== 'all') {
                const now = new Date();
                let startDate = new Date();
                
                switch(dateRange) {
                    case '7days':
                        startDate.setDate(now.getDate() - 7);
                        break;
                    case '30days':
                        startDate.setDate(now.getDate() - 30);
                        break;
                    case '90days':
                        startDate.setDate(now.getDate() - 90);
                        break;
                }
                
                filteredData = filteredData.filter(t => {
                    const transactionDate = new Date(t.transaction_date);
                    return transactionDate >= startDate;
                });
            }
            
            // Update the display with filtered data
            const originalData = transactionsData;
            transactionsData = filteredData;
            
            // Reinitialize charts and table with filtered data
            initializeCharts();
            populateTransactionsTable();
            
            // Restore original data
            transactionsData = originalData;
            
            console.log('Applied filters:', { dateRange, statusFilter, paymentFilter });
            console.log('Filtered results:', filteredData.length, 'transactions');
        }

        // Export to PDF function - now uses dynamic data
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Calculate summary statistics
            const totalRevenue = transactionsData
                .filter(t => t.status === 'delivered')
                .reduce((sum, t) => sum + t.total_amount, 0);
            const totalOrders = transactionsData.length;
            const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;
            const uniqueCustomers = new Set(transactionsData.map(t => t.customer_email)).size;
            
            // Add title
            doc.setFontSize(20);
            doc.text('HirayaFit - Sales Report', 20, 20);
            
            // Add date range
            doc.setFontSize(12);
            doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 35);
            
            // Add summary statistics
            doc.setFontSize(14);
            doc.text('Summary Statistics', 20, 50);
            doc.setFontSize(10);
            doc.text(`Total Revenue: ₱${totalRevenue.toLocaleString()}`, 20, 65);
            doc.text(`Total Orders: ${totalOrders.toLocaleString()}`, 20, 75);
            doc.text(`Average Order Value: ₱${avgOrderValue.toFixed(2)}`, 20, 85);
            doc.text(`Unique Customers: ${uniqueCustomers.toLocaleString()}`, 20, 95);
            
            // Add transactions table
            doc.setFontSize(14);
            doc.text('Recent Transactions', 20, 115);
            
            // Table headers
            doc.setFontSize(8);
            let yPos = 130;
            doc.text('Transaction ID', 20, yPos);
            doc.text('Customer', 70, yPos);
            doc.text('Date', 110, yPos);
            doc.text('Status', 140, yPos);
            doc.text('Amount', 170, yPos);
            
            // Table data
            yPos += 10;
            transactionsData.forEach(transaction => {
                if (yPos > 270) { // Start new page if needed
                    doc.addPage();
                    yPos = 20;
                }
                
                doc.text(transaction.transaction_id.substring(0, 15) + '...', 20, yPos);
                doc.text(transaction.customer_name, 70, yPos);
                doc.text(new Date(transaction.transaction_date).toLocaleDateString(), 110, yPos);
                doc.text(transaction.status, 140, yPos);
                doc.text('₱' + transaction.total_amount.toLocaleString(), 170, yPos);
                yPos += 8;
            });
            
            // Save the PDF
            doc.save('hirafit-sales-report.pdf');
        }

        // Export to CSV function - now uses dynamic data
        function exportToCSV() {
            let csvContent = "Transaction ID,Customer Name,Customer Email,Date,Status,Payment Method,Subtotal,Shipping Fee,Total Amount,Items\n";
            
            transactionsData.forEach(transaction => {
                const itemsDesc = transaction.items.map(item => 
                    `${item.product_name} (${item.color}, ${item.size}) x${item.quantity}`
                ).join('; ');
                
                csvContent += `${transaction.transaction_id},${transaction.customer_name},${transaction.customer_email},${transaction.transaction_date},${transaction.status},${transaction.payment_method},${transaction.subtotal},${transaction.shipping_fee},${transaction.total_amount},"${itemsDesc}"\n`;
            });
            
            // Create and download CSV file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'hirafit-transactions.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // View all transactions function
        function viewAllTransactions() {
            alert(`Showing all ${transactionsData.length} transactions. This would navigate to a detailed transactions page with pagination and advanced filters.`);
        }
    </script>
</body>
</html>