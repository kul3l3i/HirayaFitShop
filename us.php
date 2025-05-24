<?php
// Start the session at the very beginning
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';

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

// Process user search/filter if submitted
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Pagination variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// SQL for counting total users with filters
$count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
if (!empty($search)) {
    $search_term = "%$search%";
    $count_sql .= " AND (fullname LIKE ? OR email LIKE ? OR username LIKE ?)";
}
if ($status_filter !== 'all') {
    $is_active = ($status_filter === 'active') ? 1 : 0;
    $count_sql .= " AND is_active = ?";
}

// Prepare and execute count query
$count_stmt = $conn->prepare($count_sql);
if (!$count_stmt) {
    die("Error preparing count statement: " . $conn->error);
}

if (!empty($search) && $status_filter !== 'all') {
    $search_term = "%$search%";
    $is_active = ($status_filter === 'active') ? 1 : 0;
    $count_stmt->bind_param("sssi", $search_term, $search_term, $search_term, $is_active);
} elseif (!empty($search)) {
    $search_term = "%$search%";
    $count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
} elseif ($status_filter !== 'all') {
    $is_active = ($status_filter === 'active') ? 1 : 0;
    $count_stmt->bind_param("i", $is_active);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// SQL for fetching users with pagination and filters
$sql = "SELECT id, fullname, email, username, is_active, last_login, created_at, profile_image FROM users WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (fullname LIKE ? OR email LIKE ? OR username LIKE ?)";
}
if ($status_filter !== 'all') {
    $sql .= " AND is_active = ?";
}
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

// Prepare and execute select query
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

if (!empty($search) && $status_filter !== 'all') {
    $search_term = "%$search%";
    $is_active = ($status_filter === 'active') ? 1 : 0;
    $stmt->bind_param("sssidd", $search_term, $search_term, $search_term, $is_active, $limit, $offset);
} elseif (!empty($search)) {
    $search_term = "%$search%";
    $stmt->bind_param("sssdd", $search_term, $search_term, $search_term, $limit, $offset);
} elseif ($status_filter !== 'all') {
    $is_active = ($status_filter === 'active') ? 1 : 0;
    $stmt->bind_param("idd", $is_active, $limit, $offset);
} else {
    $stmt->bind_param("dd", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Close the statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
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
        
        /* Users Management Styles */
        .dashboard-container {
            padding: 30px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .filter-box {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .table-container {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .table-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .table-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .export-btn {
            background-color: var(--secondary);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background-color: #005ca9;
        }
        
        .export-btn i {
            margin-right: 5px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .status-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .action-btn {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            margin-right: 5px;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .view-btn {
            color: var(--info);
        }
        
        .view-btn:hover {
            background-color: rgba(23, 162, 184, 0.1);
        }
        
        .delete-btn {
            color: var(--danger);
        }
        
        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .pagination-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .pagination {
            list-style: none;
            display: flex;
            gap: 5px;
        }
        
        .pagination li a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--dark);
            background-color: white;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .pagination li.active a {
            background-color: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        .pagination li a:hover {
            background-color: #f8f9fa;
        }
        
        .pagination li.active a:hover {
            background-color: var(--secondary);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--secondary);
        }
        
        /* Modal Styles */
        .modal-header {
            background-color: var(--primary);
            color: white;
            border-radius: 6px 6px 0 0;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-user-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--secondary);
            margin-right: 20px;
        }
        
        .modal-user-info h4 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .modal-user-info p {
            color: var(--grey);
            margin-bottom: 0;
        }
        
        .user-detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            width: 130px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .detail-value {
            flex: 1;
            color: var(--grey);
        }
        
        .btn-custom-danger {
            background-color: var(--danger);
            border-color: var(--danger);
            color: white;
        }
        
        .btn-custom-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
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
            
            .filter-box .row {
                flex-direction: column;
            }
            
            .filter-box .col-md-4 {
                margin-bottom: 10px;
            }
            
            .table-responsive {
                font-size: 14px;
            }
            
            .action-btn {
                padding: 2px;
                font-size: 14px;
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
            <a href="stock.php"><i class="fas fa-box"></i> Stock Management</a>
            
            <div class="menu-title">USERS</div>
            <a href="users.php" class="active"><i class="fas fa-users"></i> User Management</a>
            
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
                <span class="navbar-title">User Management</span>
                <div class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($admin['fullname']); ?></strong>!</div>
            </div>
            
            <div class="navbar-actions">
                <!--<a href="notifications.php" class="nav-link">
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

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <div class="page-header">
                <h1 class="page-title">User Management</h1>
            </div>
            
            <!-- Filter Box -->
            <div class="filter-box">
                <form method="GET" action="users.php" class="row align-items-end">
                    <div class="col-md-5 mb-3 mb-md-0">
                        <label for="search" class="form-label">Search Users</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email or username" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Filter Results</button>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <div class="table-actions">
                    <h2 class="table-title">Registered Users (<?php echo $total_records; ?>)</h2>
                    <button id="exportPdfBtn" class="export-btn"><i class="fas fa-file-pdf"></i> Export to PDF</button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Avatar</th>
                                <th scope="col">Full Name</th>
                                <th scope="col">Username</th>
                                <th scope="col">Email</th>
                                <th scope="col">Status</th>
                                <th scope="col">Last Login</th>
                                <th scope="col">Registered</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (count($users) > 0) :
                                $counter = ($page - 1) * $limit + 1;
                                foreach ($users as $user) : 
                                    $userImageUrl = getProfileImageUrl($user['profile_image']);
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($userImageUrl); ?>" alt="User" class="user-avatar">
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">No users found</td>
                                </tr>
                            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-user-header">
                        <img src="" alt="User" class="modal-user-avatar" id="modalUserAvatar">
                        <div class="modal-user-info">
                            <h4 id="modalUserName"></h4>
                            <p id="modalUserEmail"></p>
                        </div>
                    </div>
                    
                    <div class="user-details">
                        <div class="user-detail-row">
                            <div class="detail-label">Username:</div>
                            <div class="detail-value" id="modalUsername"></div>
                        </div>
                        <div class="user-detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value" id="modalStatus"></div>
                        </div>
                        <div class="user-detail-row">
                            <div class="detail-label">Last Login:</div>
                            <div class="detail-value" id="modalLastLogin"></div>
                        </div>
                        <div class="user-detail-row">
                            <div class="detail-label">Registered:</div>
                            <div class="detail-value" id="modalRegistered"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_user_delete.php" method="POST">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone!</p>
                        
                        <input type="hidden" name="user_id" id="deleteUserId">
                        
                        <div class="mb-3 mt-4">
                            <label for="adminPassword" class="form-label">Please enter your admin password to confirm:</label>
                            <input type="password" class="form-control" id="adminPassword" name="admin_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-custom-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
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
            
            // Admin dropdown toggle
            const adminDropdown = document.getElementById('adminDropdown');
            const adminDropdownContent = document.getElementById('adminDropdownContent');
            
            if (adminDropdown) {
                adminDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    adminDropdown.classList.toggle('show');
                });
            }
            
            // Close admin dropdown when clicking outside
            document.addEventListener('click', function() {
                if (adminDropdown && adminDropdown.classList.contains('show')) {
                    adminDropdown.classList.remove('show');
                }
            });
            
            // View User Modal
            const viewUserModal = document.getElementById('viewUserModal');
            if (viewUserModal) {
                viewUserModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const userId = button.getAttribute('data-user-id');
                    const fullname = button.getAttribute('data-fullname');
                    const username = button.getAttribute('data-username');
                    const email = button.getAttribute('data-email');
                    const status = button.getAttribute('data-status');
                    const lastLogin = button.getAttribute('data-last-login');
                    const created = button.getAttribute('data-created');
                    const image = button.getAttribute('data-image');
                    
                    document.getElementById('modalUserName').textContent = fullname;
                    document.getElementById('modalUserEmail').textContent = email;
                    document.getElementById('modalUsername').textContent = username;
                    document.getElementById('modalStatus').textContent = status;
                    document.getElementById('modalLastLogin').textContent = lastLogin;
                    document.getElementById('modalRegistered').textContent = created;
                    document.getElementById('modalUserAvatar').src = image;
                });
            }
            
            // Delete User Modal
            const deleteUserModal = document.getElementById('deleteUserModal');
            if (deleteUserModal) {
                deleteUserModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const userId = button.getAttribute('data-user-id');
                    const fullname = button.getAttribute('data-fullname');
                    
                    document.getElementById('deleteUserId').value = userId;
                    document.getElementById('deleteUserName').textContent = fullname;
                });
            }
            
            // Export to PDF functionality
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            if (exportPdfBtn) {
                exportPdfBtn.addEventListener('click', function() {
                    // Create new jsPDF instance
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();
                    
                    // Set document properties
                    doc.setProperties({
                        title: 'HirayaFit Users Report',
                        subject: 'User Management Report',
                        author: 'HirayaFit Admin',
                        creator: 'HirayaFit System'
                    });
                    
                    // Add document title
                    doc.setFontSize(20);
                    doc.text('HirayaFit Users Report', 105, 15, { align: 'center' });
                    
                    // Add document subtitle
                    doc.setFontSize(12);
                    doc.text('Generated on: ' + new Date().toLocaleString(), 105, 22, { align: 'center' });
                    
                    // Get table data
                    const table = document.getElementById('usersTable');
                    if (table) {
                        // Generate table headers and data for PDF
                        const tableHeaders = [];
                        const tableData = [];
                        
                        // Get headers (skip Avatar column)
                        const headerRow = table.querySelector('thead tr');
                        headerRow.querySelectorAll('th').forEach((th, index) => {
                            if (index !== 1) { // Skip Avatar column
                                tableHeaders.push(th.textContent);
                            }
                        });
                        
                        // Get data rows (skip Avatar column)
                        table.querySelectorAll('tbody tr').forEach(tr => {
                            const rowData = [];
                            tr.querySelectorAll('td').forEach((td, index) => {
                                if (index !== 1) { // Skip Avatar column
                                    // For status column, get text content only
                                    if (index === 5) { // Status column
                                        const statusBadge = td.querySelector('.status-badge');
                                        rowData.push(statusBadge ? statusBadge.textContent.trim() : td.textContent.trim());
                                    } 
                                    // For actions column, skip
                                    else if (index === 8) {
                                        // Skip actions column
                                    } 
                                    else {
                                        rowData.push(td.textContent.trim());
                                    }
                                }
                            });
                            
                            if (rowData.length > 0) {
                                tableData.push(rowData);
                            }
                        });
                        
                        // Remove the actions column from headers
                        tableHeaders.pop();
                        
                        // Create table using autoTable plugin
                        doc.autoTable({
                            head: [tableHeaders],
                            body: tableData,
                            startY: 30,
                            headStyles: {
                                fillColor: [17, 17, 17],
                                textColor: [255, 255, 255],
                                fontStyle: 'bold'
                            },
                            alternateRowStyles: {
                                fillColor: [245, 245, 245]
                            },
                            margin: { top: 30 },
                            styles: {
                                overflow: 'linebreak',
                                cellWidth: 'auto',
                                fontSize: 9
                            },
                            columnStyles: {
                                0: { cellWidth: 10 }, // # column
                                3: { cellWidth: 30 }, // Email column
                            }
                        });
                        
                        // Add footer
                        const pageCount = doc.internal.getNumberOfPages();
                        for (let i = 1; i <= pageCount; i++) {
                            doc.setPage(i);
                            doc.setFontSize(8);
                            doc.setTextColor(100);
                            doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.getWidth() - 20, doc.internal.pageSize.getHeight() - 10);
                            doc.text('HirayaFit © ' + new Date().getFullYear(), 20, doc.internal.pageSize.getHeight() - 10);
                        }
                        
                        // Save the PDF
                        doc.save('hirayafit-users-report-' + new Date().toISOString().slice(0, 10) + '.pdf');
                    }
                });
            }
        });
    </script>
</body>
</html>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo $status_filter !== 'all' ? '&status='.$status_filter : ''; ?>" aria-label="First">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo $status_filter !== 'all' ? '&status='.$status_filter : ''; ?>" aria-label="Previous">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($start_page + 4, $total_pages);
                        
                        if ($end_page - $start_page < 4 && $start_page > 1) {
                            $start_page = max(1, $end_page - 4);
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo $status_filter !== 'all' ? '&status='.$status_filter : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo $status_filter !== 'all' ? '&status='.$status_filter : ''; ?>" aria-label="Next">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo $status_filter !== 'all' ? '&status='.$status_filter : ''; ?>" aria-label="Last">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                                    <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="action-btn view-btn" data-bs-toggle="modal" data-bs-target="#viewUserModal" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-fullname="<?php echo htmlspecialchars($user['fullname']); ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-status="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>"
                                            data-last-login="<?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>"
                                            data-created="<?php echo date('M d, Y', strtotime($user['created_at'])); ?>"
                                            data-image="<?php echo $userImageUrl; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="action-btn delete-btn" data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-fullname="<?php echo htmlspecialchars($user['fullname']); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                    