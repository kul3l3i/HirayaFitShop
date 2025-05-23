<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Reports & Analytics</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
        
        .navbar-actions {
            display: flex;
            align-items: center;
        }

        /* Dashboard Container */
        .dashboard-container {
            padding: 30px;
        }

        /* Reports Header */
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .reports-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .export-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #005a9e;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        /* Date Filter */
        .date-filter {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filter-row {
            display: flex;
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark);
        }

        .filter-group input, .filter-group select {
            width: 100%;
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
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--secondary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.danger {
            border-left-color: var(--danger);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .stat-title {
            font-size: 14px;
            color: var(--grey);
            font-weight: 500;
        }

        .stat-icon {
            font-size: 20px;
            color: var(--secondary);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .stat-change {
            font-size: 12px;
            margin-top: 5px;
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
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            text-align: center;
        }

        /* Tables */
        .reports-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            padding: 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-delivered {
            background-color: #d1edff;
            color: #0071c5;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Loading Spinner */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--secondary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

            .charts-section {
                grid-template-columns: 1fr;
            }

            .filter-row {
                flex-direction: column;
                gap: 15px;
            }

            .reports-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
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
            </div>
            
            <div class="navbar-actions">
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </a>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Reports Header -->
            <div class="reports-header">
                <h1 class="reports-title">Reports & Analytics</h1>
                <div class="export-buttons">
                    <button class="btn btn-success" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i>
                        Export to PDF
                    </button>
                    <button class="btn btn-primary" onclick="refreshData()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh Data
                    </button>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="date-filter">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" onchange="filterTransactions()">
                    </div>
                    <div class="filter-group">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" onchange="filterTransactions()">
                    </div>
                    <div class="filter-group">
                        <label for="statusFilter">Status</label>
                        <select id="statusFilter" onchange="filterTransactions()">
                            <option value="all">All Status</option>
                            <option value="delivered">Delivered</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="paymentFilter">Payment Method</label>
                        <select id="paymentFilter" onchange="filterTransactions()">
                            <option value="all">All Methods</option>
                            <option value="gcash">GCash</option>
                            <option value="cod">Cash on Delivery</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="loading" style="display: none;">
                <div class="spinner"></div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-header">
                        <span class="stat-title">Total Revenue</span>
                        <i class="fas fa-dollar-sign stat-icon"></i>
                    </div>
                    <div class="stat-value" id="totalRevenue">₱0</div>
                    <div class="stat-change positive" id="revenueChange">+0% from last month</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-header">
                        <span class="stat-title">Total Orders</span>
                        <i class="fas fa-shopping-cart stat-icon"></i>
                    </div>
                    <div class="stat-value" id="totalOrders">0</div>
                    <div class="stat-change positive" id="ordersChange">+0% from last month</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-header">
                        <span class="stat-title">Average Order Value</span>
                        <i class="fas fa-chart-line stat-icon"></i>
                    </div>
                    <div class="stat-value" id="avgOrderValue">₱0</div>
                    <div class="stat-change positive" id="avgChange">+0% from last month</div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-header">
                        <span class="stat-title">Delivery Rate</span>
                        <i class="fas fa-truck stat-icon"></i>
                    </div>
                    <div class="stat-value" id="deliveryRate">0%</div>
                    <div class="stat-change positive" id="deliveryChange">+0% from last month</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-container">
                    <h3 class="chart-title">Revenue Trend (Last 30 Days)</h3>
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3 class="chart-title">Payment Methods</h3>
                    <canvas id="paymentChart" width="300" height="300"></canvas>
                </div>
            </div>

            <!-- Recent Transactions Table -->
            <div class="reports-table">
                <div class="table-header">
                    <h3 class="table-title">Recent Transactions</h3>
                </div>
                <div class="table-responsive">
                    <table id="transactionsTable">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <!-- Dynamic content will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Products Table -->
            <div class="reports-table">
                <div class="table-header">
                    <h3 class="table-title">Top Selling Products</h3>
                </div>
                <div class="table-responsive">
                    <table id="productsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                                <th>Average Price</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Dynamic content will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let allTransactions = [];
        let filteredTransactions = [];
        let revenueChart = null;
        let paymentChart = null;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadTransactionData();
            initializeDateFilters();
            
            // Sidebar toggle functionality
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                });
            }
        });

        // Initialize date filters with current month
        function initializeDateFilters() {
            const now = new Date();
            const firstDayOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('startDate').value = firstDayOfMonth.toISOString().split('T')[0];
            document.getElementById('endDate').value = lastDayOfMonth.toISOString().split('T')[0];
        }

        // Load transaction data from XML
        async function loadTransactionData() {
            try {
                showLoading(true);
                
                // Sample data for demonstration - in real implementation, this would load from your XML file
                const sampleTransactions = [
                    {
                        transaction_id: 'TRX-683014A91A92D',
                        user_id: '2',
                        transaction_date: '2025-05-23 08:24:41',
                        status: 'delivered',
                        payment_method: 'gcash',
                        subtotal: 1500,
                        shipping_fee: 100,
                        total_amount: 1600,
                        items: [{
                            product_id: '2006',
                            product_name: "Women's High-Waist Leggings",
                            price: 1500,
                            quantity: 1,
                            color: 'black',
                            size: 'S',
                            subtotal: 1500
                        }],
                        shipping_info: {
                            fullname: 'Lai Rodriguez',
                            email: 'rodriguez.elx@gmail.com',
                            phone: '9633945919',
                            address: 'Caingin San Rafael Bulacan',
                            city: 'San Rafael',
                            postal_code: '3008'
                        }
                    },
                    {
                        transaction_id: 'TRX-683014A91A93E',
                        user_id: '3',
                        transaction_date: '2025-05-22 14:15:22',
                        status: 'pending',
                        payment_method: 'cod',
                        subtotal: 2500,
                        shipping_fee: 100,
                        total_amount: 2600,
                        items: [{
                            product_id: '2007',
                            product_name: "Men's Compression Shorts",
                            price: 1250,
                            quantity: 2,
                            color: 'blue',
                            size: 'M',
                            subtotal: 2500
                        }],
                        shipping_info: {
                            fullname: 'John Doe',
                            email: 'john.doe@gmail.com',
                            phone: '9123456789',
                            address: 'Manila City',
                            city: 'Manila',
                            postal_code: '1000'
                        }
                    },
                    {
                        transaction_id: 'TRX-683014A91A94F',
                        user_id: '4',
                        transaction_date: '2025-05-21 10:30:15',
                        status: 'delivered',
                        payment_method: 'gcash',
                        subtotal: 3200,
                        shipping_fee: 100,
                        total_amount: 3300,
                        items: [{
                            product_id: '2008',
                            product_name: "Athletic Sports Bra",
                            price: 800,
                            quantity: 4,
                            color: 'pink',
                            size: 'L',
                            subtotal: 3200
                        }],
                        shipping_info: {
                            fullname: 'Maria Santos',
                            email: 'maria.santos@gmail.com',
                            phone: '9876543210',
                            address: 'Quezon City',
                            city: 'Quezon City',
                            postal_code: '1100'
                        }
                    }
                ];

                allTransactions = sampleTransactions;
                filteredTransactions = [...allTransactions];
                
                updateStatistics();
                updateCharts();
                updateTransactionsTable();
                updateProductsTable();
                
                showLoading(false);
            } catch (error) {
                console.error('Error loading transaction data:', error);
                showLoading(false);
            }
        }

        // Show/hide loading indicator
        function showLoading(show) {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const statsGrid = document.getElementById('statsGrid');
            
            if (show) {
                loadingIndicator.style.display = 'flex';
                statsGrid.style.opacity = '0.5';
            } else {
                loadingIndicator.style.display = 'none';
                statsGrid.style.opacity = '1';
            }
        }

        // Filter transactions based on date range and filters
        function filterTransactions() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const paymentFilter = document.getElementById('paymentFilter').value;

            filteredTransactions = allTransactions.filter(transaction => {
                const transactionDate = new Date(transaction.transaction_date).toISOString().split('T')[0];
                
                // Date filter
                const dateMatch = (!startDate || transactionDate >= startDate) && 
                                 (!endDate || transactionDate <= endDate);
                
                // Status filter
                const statusMatch = statusFilter === 'all' || transaction.status === statusFilter;
                
                // Payment method filter
                const paymentMatch = paymentFilter === 'all' || transaction.payment_method === paymentFilter;
                
                return dateMatch && statusMatch && paymentMatch;
            });

            updateStatistics();
            updateCharts();
            updateTransactionsTable();
            updateProductsTable();
        }

        // Update statistics cards
        function updateStatistics() {
            const totalRevenue = filteredTransactions.reduce((sum, t) => sum + parseFloat(t.total_amount), 0);
            const totalOrders = filteredTransactions.length;
            const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;
            const deliveredOrders = filteredTransactions.filter(t => t.status === 'delivered').length;
            const deliveryRate = totalOrders > 0 ? (deliveredOrders / totalOrders) * 100 : 0;

            document.getElementById('totalRevenue').textContent = `₱${totalRevenue.toLocaleString()}`;
            document.getElementById('totalOrders').textContent = totalOrders.toLocaleString();
            document.getElementById('avgOrderValue').textContent = `₱${avgOrderValue.toFixed(2)}`;
            document.getElementById('deliveryRate').textContent = `${deliveryRate.toFixed(1)}%`;
        }

        // Update charts
        function updateCharts() {
            updateRevenueChart();
            updatePaymentChart();
        }

        // Update revenue trend chart
        function updateRevenueChart() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Group transactions by date
            const dailyRevenue = {};
            filteredTransactions.forEach(transaction => {
                const date = transaction.transaction_date.split(' ')[0];
                dailyRevenue[date] = (dailyRevenue[date] || 0) + parseFloat(transaction.total_amount);
            });

            const dates = Object.keys(dailyRevenue).sort();
            const revenues = dates.map(date => dailyRevenue[date]);

            if (revenueChart) {
                revenueChart.destroy();
            }

            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: revenues,
                        borderColor: '#0071c5',
                        backgroundColor: 'rgba(0, 113, 197, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Update payment methods chart
        function updatePaymentChart() {
            const ctx = document.getElementById('paymentChart').getContext('2d');
            
            // Group by payment method
            const paymentMethods = {};
            filteredTransactions.forEach(transaction => {
                const method = transaction.payment_method;
                paymentMethods[method] = (paymentMethods[method] || 0) + 1;
            });

            const labels = Object.keys(paymentMethods);
            const data = Object.values(paymentMethods);
            const colors = ['#0071c5', '#28a745', '#ffc107', '#dc3545', '#17a2b8'];

            if (paymentChart) {
                paymentChart.destroy();
            }

            paymentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels.map(l => l.toUpperCase()),
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, labels.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Update transactions table
        function updateTransactionsTable() {
            const tbody = document.getElementById('transactionsTableBody');
            tbody.innerHTML = '';

            // Sort by date (most recent first)
            const sortedTransactions = [...filteredTransactions].sort((a, b) => 
                new Date(b.transaction_date) - new Date(a.transaction_date)
            );

            sortedTransactions.slice(0, 20).forEach(transaction => {
                const row = document.createElement('tr');
                const date = new Date(transaction.transaction_date);
                const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                
                row.innerHTML = `
                    <td>${transaction.transaction_id}</td>
                    <td>${formattedDate}</td>
                    <td>${transaction.shipping_info.fullname}</td>
                    <td><span class="status-badge status-${transaction.status}">${transaction.status}</span></td>
                    <td>${transaction.payment_method.toUpperCase()}</td>
                    <td>₱${parseFloat(transaction.total_amount).toLocaleString()}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Update products table
        function updateProductsTable() {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';

            // Group products by name
            const productStats = {};
            filteredTransactions.forEach(transaction => {
                transaction.items.forEach(item => {
                    const productName = item.product_name;
                    if (!productStats[productName]) {
                        productStats[productName] = {
                            name: productName,
                            totalQuantity: 0,
                            totalRevenue: 0,
                            orders: 0
                        };
                    }
                    productStats[productName].totalQuantity += parseInt(item.quantity);
                    productStats[productName].totalRevenue += parseFloat(item.subtotal);
                    productStats[productName].orders++;
                });
            });

            // Sort by quantity sold
            const sortedProducts = Object.values(productStats).sort((a, b) => b.totalQuantity - a.totalQuantity);

            sortedProducts.slice(0, 10).forEach(product => {
                const avgPrice = product.totalRevenue / product.totalQuantity;
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td>${product.name}</td>
                    <td>${product.totalQuantity}</td>
                    <td>₱${product.totalRevenue.toLocaleString()}</td>
                    <td>₱${avgPrice.toFixed(2)}</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Refresh data
        function refreshData() {
            loadTransactionData();
        }

        // Export to PDF
        async function exportToPDF() {
            try {
                // Show loading
                const exportBtn = document.querySelector('.btn-success');
                const originalText = exportBtn.innerHTML;
                exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                exportBtn.disabled = true;

                // Create PDF content
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                
                // Add title
                pdf.setFontSize(20);
                pdf.setFont(undefined, 'bold');
                pdf.text('HirayaFit - Sales Report', 20, 20);
                
                // Add date range
                pdf.setFontSize(12);
                pdf.setFont(undefined, 'normal');
                const startDate = document.getElementById('startDate').value || 'All time';
                const endDate = document.getElementById('endDate').value || 'Present';
                pdf.text(`Report Period: ${startDate} to ${endDate}`, 20, 30);
                
                // Add statistics
                pdf.setFontSize(14);
                pdf.setFont(undefined, 'bold');
                pdf.text('Summary Statistics', 20, 45);
                
                pdf.setFontSize(11);
                pdf.setFont(undefined, 'normal');
                const totalRevenue = filteredTransactions.reduce((sum, t) => sum + parseFloat(t.total_amount), 0);
                const totalOrders = filteredTransactions.length;
                const avgOrderValue = totalOrders > 0 ? totalRevenue / totalOrders : 0;
                const deliveredOrders = filteredTransactions.filter(t => t.status === 'delivered').length;
                const deliveryRate = totalOrders > 0 ? (deliveredOrders / totalOrders) * 100 : 0;
                
                pdf.text(`Total Revenue: ₱${totalRevenue.toLocaleString()}`, 20, 55);
                pdf.text(`Total Orders: ${totalOrders}`, 20, 62);
                pdf.text(`Average Order Value: ₱${avgOrderValue.toFixed(2)}`, 20, 69);
                pdf.text(`Delivery Rate: ${deliveryRate.toFixed(1)}%`, 20, 76);
                
                // Add transactions table
                pdf.setFontSize(14);
                pdf.setFont(undefined, 'bold');
                pdf.text('Recent Transactions', 20, 90);
                
                // Table headers
                pdf.setFontSize(10);
                pdf.setFont(undefined, 'bold');
                pdf.text('Transaction ID', 20, 100);
                pdf.text('Date', 60, 100);
                pdf.text('Customer', 100, 100);
                pdf.text('Status', 140, 100);
                pdf.text('Amount', 170, 100);
                
                // Table data
                pdf.setFont(undefined, 'normal');
                let yPosition = 110;
                const sortedTransactions = [...filteredTransactions].sort((a, b) => 
                    new Date(b.transaction_date) - new Date(a.transaction_date)
                );
                
                sortedTransactions.slice(0, 15).forEach((transaction, index) => {
                    if (yPosition > 250) {
                        pdf.addPage();
                        yPosition = 20;
                    }
                    
                    const date = new Date(transaction.transaction_date).toLocaleDateString();
                    pdf.text(transaction.transaction_id.substring(0, 15), 20, yPosition);
                    pdf.text(date, 60, yPosition);
                    pdf.text(transaction.shipping_info.fullname.substring(0, 20), 100, yPosition);
                    pdf.text(transaction.status, 140, yPosition);
                    pdf.text(`₱${parseFloat(transaction.total_amount).toLocaleString()}`, 170, yPosition);
                    yPosition += 7;
                });
                
                // Add page number
                const pageCount = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.text(`Page ${i} of ${pageCount}`, pdf.internal.pageSize.width - 30, pdf.internal.pageSize.height - 10);
                    pdf.text(`Generated on ${new Date().toLocaleString()}`, 20, pdf.internal.pageSize.height - 10);
                }
                
                // Save the PDF
                const fileName = `HirayaFit_Report_${new Date().toISOString().split('T')[0]}.pdf`;
                pdf.save(fileName);
                
                // Reset button
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                
                // Reset button
                const exportBtn = document.querySelector('.btn-success');
                exportBtn.innerHTML = '<i class="fas fa-file-pdf"></i> Export to PDF';
                exportBtn.disabled = false;
            }
        }

        // Sample XML loading function (you would replace this with actual XML parsing)
        async function loadFromXML() {
            try {
                // In a real implementation, you would fetch and parse your transactions.xml file
                // const response = await fetch('transactions.xml');
                // const xmlText = await response.text();
                // const parser = new DOMParser();
                // const xmlDoc = parser.parseFromString(xmlText, 'text/xml');
                
                // Parse XML and convert to JavaScript objects
                // This is where you'd implement your XML parsing logic
                
                console.log('XML loading functionality would be implemented here');
                
            } catch (error) {
                console.error('Error loading XML:', error);
            }
        }
    </script>
</body>
</html>