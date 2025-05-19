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
$stmt = $conn->prepare("SELECT admin_id, username, fullname, email, profile_image, role, password FROM admins WHERE admin_id = ? AND is_active = TRUE");
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

// Close the statement
$stmt->close();

// Process delete user request
$deleteError = '';
$deleteSuccess = '';

if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $admin_password = $_POST['admin_password'];
    
    // Verify admin password
    if (password_verify($admin_password, $admin['password'])) {
        // Password verified, proceed with deletion
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);
        
        if ($delete_stmt->execute()) {
            $deleteSuccess = "User has been successfully deleted.";
        } else {
            $deleteError = "Failed to delete user. Please try again.";
        }
        $delete_stmt->close();
    } else {
        $deleteError = "Incorrect admin password. User deletion cancelled.";
    }
}

// Get all users from the database with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Users per page
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchCondition = '';
$searchParams = [];
$searchTypes = '';

if (!empty($search)) {
    $searchCondition = "WHERE fullname LIKE ? OR email LIKE ? OR username LIKE ?";
    $searchValue = "%$search%";
    $searchParams = [$searchValue, $searchValue, $searchValue];
    $searchTypes = "sss";
}

// Count total users for pagination
if (!empty($searchCondition)) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users $searchCondition");
    if (!empty($searchParams)) {
        $count_stmt->bind_param($searchTypes, ...$searchParams);
    }
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);
$count_stmt->close();

// Get users with pagination and search
if (!empty($searchCondition)) {
    $users_stmt = $conn->prepare("SELECT * FROM users $searchCondition ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $searchParams[] = $limit;
    $searchParams[] = $offset;
    $users_stmt->bind_param($searchTypes . "ii", ...$searchParams);
} else {
    $users_stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $users_stmt->bind_param("ii", $limit, $offset);
}

$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = [];

while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}
$users_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HirayaFit Admin</title>
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
        
        /* Users Container */
        .users-container {
            padding: 30px;
        }
        
        .panel {
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 30px;
        }
        
        .panel-header {
            padding: 20px;
            border-bottom: 1px solid #f1f1f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .panel-body {
            padding: 20px;
        }
        
        /* Search and Filter Bar */
        .search-filter-bar {
            display: flex;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .search-box {
            flex: 1;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
        }
        
        .filter-btn {
            background-color: white;
            border: 1px solid #ddd;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .filter-btn i {
            margin-right: 5px;
        }
        
        .filter-btn:hover {
            border-color: var(--secondary);
            color: var(--secondary);
        }
        
        /* Table Styles */
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th, .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .users-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .users-table td {
            font-size: 14px;
            color: #555;
        }
        
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
            display: block;
            line-height: 1.3;
        }
        
        .user-email {
            font-size: 12px;
            color: #777;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .status-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            background: none;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .view-btn {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }
        
        .view-btn:hover {
            background-color: var(--info);
            color: white;
        }
        
        .delete-btn {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .delete-btn:hover {
            background-color: var(--danger);
            color: white;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            list-style: none;
        }
        
        .page-item {
            margin: 0 2px;
        }
        
        .page-link {
            display: block;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .page-item.active .page-link {
            background-color: var(--secondary);
            border-color: var(--secondary);
            color: white;
        }
        
        .page-link:hover {
            background-color: #f5f5f5;
        }
        
        .page-item.disabled .page-link {
            color: #aaa;
            pointer-events: none;
            background-color: #f8f8f8;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 600px;
            width: 90%;
            position: relative;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {transform: translateY(-30px); opacity: 0;}
            to {transform: translateY(0); opacity: 1;}
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f1f1;
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
            color: #999;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #f1f1f1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* User details modal */
        .user-detail-row {
            margin-bottom: 15px;
            display: flex;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark);
            width: 150px;
            display: block;
        }
        
        .detail-value {
            color: #555;
            flex: 1;
        }
        
        .user-large-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        
        .user-profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .user-profile-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-top: 10px;
        }
        
        .user-profile-username {
            color: #777;
            font-size: 14px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
            outline: none;
        }
        
        .text-danger {
            color: var(--danger);
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        .text-success {
            color: var(--success);
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #005fa3;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        /* Empty State */
        .empty-state {
            padding: 40px 20px;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-text {
            font-size: 16px;
            color: #777;
            margin-bottom: 15px;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }
            
            .search-filter-bar {
                flex-direction: column;
            }
            
            .detail-label {
                width: 120px;
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
            
            .users-table {
                display: block;
                overflow-x: auto;
            }
            
            .user-detail-row {
                flex-direction: column;
            }
            
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
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
            <a href="settings.php"><i class="fas fa-cog"></i> System Settings</a>
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
                        <a href="?logout=true" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Users Container -->
        <div class="users-container">
            <!-- Success and Error Messages -->
            <?php if (!empty($deleteSuccess)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $deleteSuccess; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($deleteError)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $deleteError; ?>
                </div>
            <?php endif; ?>
            
            <div class="panel">
                <div class="panel-header">
                    <h2 class="panel-title">Registered Users</h2>
                </div>
                <div class="panel-body">
                    <!-- Search and Filter Bar -->
                    <div class="search-filter-bar">
                        <form action="" method="GET" class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" placeholder="Search by name, email or username..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                    
                    <!-- Users Table -->
                    <?php if (count($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Username</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="user-info">
                                                    <img src="<?php echo getProfileImageUrl($user['profile_image']); ?>" alt="User" class="user-avatar">
                                                    <div>
                                                        <span class="user-name"><?php echo htmlspecialchars($user['fullname']); ?></span>
                                                        <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : "Not provided"; ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td><?php echo !empty($user['last_login']) ? date('M d, Y H:i', strtotime($user['last_login'])) : "Never"; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="action-btn view-btn" onclick="viewUserDetails(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['fullname'])); ?>', '<?php echo addslashes(htmlspecialchars($user['username'])); ?>', '<?php echo addslashes(htmlspecialchars($user['email'])); ?>', '<?php echo addslashes(htmlspecialchars($user['phone'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($user['address'] ?? '')); ?>', '<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>', '<?php echo getProfileImageUrl($user['profile_image']); ?>', '<?php echo date('M d, Y', strtotime($user['created_at'])); ?>', '<?php echo !empty($user['last_login']) ? date('M d, Y H:i', strtotime($user['last_login'])) : "Never"; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes(htmlspecialchars($user['fullname'])); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <ul class="pagination">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="empty-text">No users found</h3>
                            <?php if (!empty($search)): ?>
                                <p>No users match your search criteria.</p>
                                <a href="users.php" class="btn btn-primary">Clear Search</a>
                            <?php else: ?>
                                <p>There are no registered users in the system yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">User Details</h3>
                <button class="modal-close" onclick="closeModal('userDetailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="user-profile-header">
                    <img id="userDetailAvatar" src="" alt="User" class="user-large-avatar">
                    <h3 id="userDetailName" class="user-profile-name"></h3>
                    <span id="userDetailUsername" class="user-profile-username"></span>
                </div>
                
                <div class="user-detail-row">
                    <span class="detail-label">Email:</span>
                    <span id="userDetailEmail" class="detail-value"></span>
                </div>
                
                <div class="user-detail-row">
                    <span class="detail-label">Phone:</span>
                    <span id="userDetailPhone" class="detail-value"></span>
                </div>
                
                <div class="user-detail-row">
                    <span class="detail-label">Address:</span>
                    <span id="userDetailAddress" class="detail-value"></span>
                </div>
                
                <div class="user-detail-row">
                    <span class="detail-label">Status:</span>
                    <span id="userDetailStatus" class="detail-value"></span>
                </div>
                
                <div class="user-detail-row">
                    <span class="detail-label">Registration Date:</span>
                    <span id="userDetailRegistration" class="detail-value"></span>
                </div>
                
                <div class="user-detail-row">
                    <span class="detail-label">Last Login:</span>
                    <span id="userDetailLastLogin" class="detail-value"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('userDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Delete User</h3>
                <button class="modal-close" onclick="closeModal('deleteUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                <p>This action cannot be undone. All user data will be permanently removed from the system.</p>
                
                <form id="deleteUserForm" method="POST" action="">
                    <input type="hidden" id="deleteUserId" name="user_id" value="">
                    
                    <div class="form-group">
                        <label for="adminPassword" class="form-label">Enter your admin password to confirm:</label>
                        <input type="password" id="adminPassword" name="admin_password" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteUserModal')">Cancel</button>
                <button class="btn btn-danger" onclick="document.getElementById('deleteUserForm').submit()">Delete</button>
            </div>
        </div>
    </div>

    <script>
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
        
        // Auto-submit search form on input change
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === "Enter") {
                    this.form.submit();
                }
            });
        }
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            setTimeout(function() {
                alerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        }
    });

    // Modal functions
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto'; // Enable scrolling
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    }
    
    // View user details function
    function viewUserDetails(id, name, username, email, phone, address, status, avatar, regDate, lastLogin) {
        document.getElementById('userDetailName').textContent = name;
        document.getElementById('userDetailUsername').textContent = '@' + username;
        document.getElementById('userDetailEmail').textContent = email;
        document.getElementById('userDetailPhone').textContent = phone || 'Not provided';
        document.getElementById('userDetailAddress').textContent = address || 'Not provided';
        document.getElementById('userDetailStatus').textContent = status;
        document.getElementById('userDetailRegistration').textContent = regDate;
        document.getElementById('userDetailLastLogin').textContent = lastLogin;
        document.getElementById('userDetailAvatar').src = avatar;
        
        openModal('userDetailsModal');
    }
    
    // Delete user function
    function deleteUser(id, name) {
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('deleteUserId').value = id;
        document.getElementById('adminPassword').value = ''; // Clear the password field
        
        openModal('deleteUserModal');
    }
    </script>
</body>
</html>