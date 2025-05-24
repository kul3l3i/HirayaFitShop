<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Stock Management</title>
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

/* Dashboard Container */
.dashboard-container {
    padding: 30px;
}

/* Success Message */
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #c3e6cb;
    display: flex;
    align-items: center;
}

.success-message i {
    margin-right: 10px;
    font-size: 18px;
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    font-size: 24px;
    color: white;
}

.stat-icon.total {
    background: linear-gradient(135deg, var(--secondary), #0056a3);
}

.stat-icon.out {
    background: linear-gradient(135deg, var(--danger), #c82333);
}

.stat-icon.low {
    background: linear-gradient(135deg, var(--warning), #e0a800);
}

.stat-icon.value {
    background: linear-gradient(135deg, var(--success), #218838);
}

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 5px;
}

.stat-info p {
    color: var(--grey);
    font-size: 14px;
    font-weight: 500;
}

/* Controls Section */
.controls {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.controls-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.controls-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn i {
    margin-right: 6px;
}

.btn-primary {
    background-color: var(--secondary);
    color: white;
}

.btn-primary:hover {
    background-color: #0056a3;
    transform: translateY(-1px);
}

.btn-success {
    background-color: var(--success);
    color: white;
}

.btn-success:hover {
    background-color: #218838;
    transform: translateY(-1px);
}

.btn-warning {
    background-color: var(--warning);
    color: var(--dark);
}

.btn-warning:hover {
    background-color: #e0a800;
    transform: translateY(-1px);
}

.btn-info {
    background-color: var(--info);
    color: white;
}

.btn-info:hover {
    background-color: #138496;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

.btn-cancel {
    background-color: #f8f9fa;
    color: var(--dark);
    border: 1px solid #dee2e6;
}

.btn-cancel:hover {
    background-color: #e9ecef;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Filters */
.filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 8px;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
}

/* Stock Table */
.stock-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.table-header {
    padding: 25px;
    border-bottom: 1px solid #dee2e6;
}

.table-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th,
table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f3f4;
}

table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: var(--dark);
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

table td {
    color: #495057;
    font-size: 14px;
}

table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Product Info in Table */
.product-info {
    display: flex;
    align-items: center;
}

.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
    border: 1px solid #dee2e6;
}

.product-details h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
}

.product-details p {
    font-size: 12px;
    color: var(--grey);
}

/* Stock Status */
.stock-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-status.in-stock {
    background-color: #d4edda;
    color: #155724;
}

.stock-status.low-stock {
    background-color: #fff3cd;
    color: #856404;
}

.stock-status.out-of-stock {
    background-color: #f8d7da;
    color: #721c24;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Modal Styles */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-backdrop.active {
    opacity: 1;
    visibility: visible;
}

.modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.7);
    transition: transform 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-backdrop.active .modal {
    transform: scale(1);
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--grey);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background-color: #f8f9fa;
    color: var(--dark);
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

/* Product Detail Grid */
.product-detail-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.product-image-large {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.product-info-detailed {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-label {
    font-weight: 500;
    color: var(--grey);
    min-width: 120px;
}

.info-value {
    font-weight: 500;
    color: var(--dark);
    text-align: right;
}

/* Stock History */
.stock-history {
    margin-top: 25px;
}

.stock-history h4 {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--secondary);
}

.history-item {
    display: flex;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 12px;
    background-color: #f8f9fa;
}

.history-date {
    min-width: 120px;
    font-weight: 500;
    color: var(--secondary);
    font-size: 14px;
}

.history-details {
    flex: 1;
    font-size: 14px;
    line-height: 1.5;
}

.change-positive {
    color: var(--success);
    font-weight: 600;
}

.change-negative {
    color: var(--danger);
    font-weight: 600;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .product-detail-grid {
        grid-template-columns: 150px 1fr;
    }
    
    .product-image-large {
        height: 150px;
    }
}

@media (max-width: 992px) {
    .welcome-text {
        display: none;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .filters {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    
    .dashboard-container {
        padding: 20px;
    }
    
    .controls-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .product-detail-grid {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .info-value {
        text-align: left;
    }
    
    .history-item {
        flex-direction: column;
        gap: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal {
        width: 95%;
        margin: 20px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filters {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-info h3 {
        font-size: 24px;
    }
    
    .export-buttons {
        width: 100%;
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
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
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="payment-history.php"><i class="fas fa-money-check-alt"></i> Payment History</a>
            
            <div class="menu-title">INVENTORY</div>
            <a href="products.php"><i class="fas fa-tshirt"></i> Products & Categories</a>
            <a href="stock.php" class="active"><i class="fas fa-box"></i> Stock Management</a>
            
            <div class="menu-title">USERS</div>
            <a href="users.php"><i class="fas fa-users"></i> User Management</a>
            
            <div class="menu-title">REPORTS & SETTINGS</div>
            <a href="reports.php"><i class="fas fa-file-pdf"></i> Reports & Analytics</a>
            <!--<a href="settings.php"><i class="fas fa-cog"></i> System Settings</a>-->
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="nav-left">
                <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <span class="navbar-title">Stock Management</span>
                <div class="welcome-text">Welcome, <strong>John Admin</strong>!</div>
            </div>
            
            <div class="navbar-actions">
                !<--<a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>-->
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-count">5</span>
                </a>
                
                <div class="admin-dropdown" id="adminDropdown">
                    <div class="admin-profile">
                        <div class="admin-avatar-container">
                            <img src="/api/placeholder/40/40" alt="Admin" class="admin-avatar">
                        </div>
                        <div class="admin-info">
                            <span class="admin-name">John Admin</span>
                            <span class="admin-role">Administrator</span>
                        </div>
                    </div>
                    
                    <div class="admin-dropdown-content" id="adminDropdownContent">
                        <div class="admin-dropdown-header">
                            <div class="admin-dropdown-avatar-container">
                                <img src="/api/placeholder/50/50" alt="Admin" class="admin-dropdown-avatar">
                            </div>
                            <div class="admin-dropdown-info">
                                <span class="admin-dropdown-name">John Admin</span>
                                <span class="admin-dropdown-role">Administrator</span>
                            </div>
                        </div>
                        <div class="admin-dropdown-user">
                            <h4 class="admin-dropdown-user-name">John Admin</h4>
                            <p class="admin-dropdown-user-email">admin@hirayafit.com</p>
                        </div>
                        <a href="profile.php"><i class="fas fa-user"></i> Profile Settings</a>
                       
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <div class="success-message" style="display: none;">
                <i class="fas fa-check-circle"></i>
                Stock updated successfully!
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-info">
                        <h3>156</h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon out">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>12</h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon low">
                        <i class="fas fa-battery-quarter"></i>
                    </div>
                    <div class="stat-info">
                        <h3>8</h3>
                        <p>Low Stock (≤5)</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon value">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₱125,450.00</h3>
                        <p>Total Stock Value</p>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="controls">
                <div class="controls-header">
                    <h3 class="controls-title">Stock Management Controls</h3>
                    <div class="export-buttons">
                        <a href="?export=csv" class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Export Stock Report
                        </a>
                        <a href="?export=history" class="btn btn-primary">
                            <i class="fas fa-history"></i> Export Stock History
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="filters">
                    <div class="filter-group">
                        <label>Search Products</label>
                        <input type="text" name="search" placeholder="Search by name or category..." value="">
                    </div>
                    
                    <div class="filter-group">
                        <label>Stock Status</label>
                        <select name="stock_filter">
                            <option value="">All Products</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category_filter">
                            <option value="">All Categories</option>
                            <option value="T-Shirts">T-Shirts</option>
                            <option value="Hoodies">Hoodies</option>
                            <option value="Pants">Pants</option>
                            <option value="Accessories">Accessories</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <a href="stock.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Stock Table -->
            <div class="stock-table">
                <div class="table-header">
                    <h3 class="table-title">Product Stock Overview</h3>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Total Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="/api/placeholder/50/50" alt="HirayaFit Pro Tee" class="product-image">
                                        <div class="product-details">
                                            <h4>HirayaFit Pro Tee</h4>
                                            <p>ID: HF001</p>
                                        </div>
                                    </div>
                                </td>
                                <td>T-Shirts</td>
                                <td><strong>25</strong></td>
                                <td>
                                    <span class="stock-status in-stock">
                                        In Stock
                                    </span>
                                </td>
                                <td>₱899.00</td>
                                <td>₱22,475.00</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm view-details-btn" data-product-id="HF001">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="btn btn-warning btn-sm update-stock-btn" data-product-id="HF001">
                                            <i class="fas fa-edit"></i> Update Stock
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="/api/placeholder/50/50" alt="Performance Hoodie" class="product-image">
                                        <div class="product-details">
                                            <h4>Performance Hoodie</h4>
                                            <p>ID: HF002</p>
                                        </div>
                                    </div>
                                </td>
                                <td>Hoodies</td>
                                <td><strong>3</strong></td>
                                <td>
                                    <span class="stock-status low-stock">
                                        Low Stock
                                    </span>
                                </td>
                                <td>₱1,499.00</td>
                                <td>₱4,497.00</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm view-details-btn" data-product-id="HF002">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="btn btn-warning btn-sm update-stock-btn" data-product-id="HF002">
                                            <i class="fas fa-edit"></i> Update Stock
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="/api/placeholder/50/50" alt="Athletic Joggers" class="product-image">
                                        <div class="product-details">
                                            <h4>Athletic Joggers</h4>
                                            <p>ID: HF003</p>
                                        </div>
                                    </div>
                                </td>
                                <td>Pants</td>
                                <td><strong>0</strong></td>
                                <td>
                                    <span class="stock-status out-of-stock">
                                        Out of Stock
                                    </span>
                                </td>
                                <td>₱1,299.00</td>
                                <td>₱0.00</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm view-details-btn" data-product-id="HF003">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="btn btn-warning btn-sm update-stock-btn" data-product-id="HF003">
                                            <i class="fas fa-edit"></i> Update Stock
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="/api/placeholder/50/50" alt="Sports Cap" class="product-image">
                                        <div class="product-details">
                                            <h4>Sports Cap</h4>
                                            <p>ID: HF004</p>
                                        </div>
                                    </div>
                                </td>
                                <td>Accessories</td>
                                <td><strong>18</strong></td>
                                <td>
                                    <span class="stock-status in-stock">
                                        In Stock