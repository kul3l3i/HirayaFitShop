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

// Process user deletion if confirmed

$deleteMessage = '';
$deleteSuccess = false;

if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $admin_password = $_POST['admin_password'];

    // Example admin ID (replace with session or secure method)
    $admin_id = 1;

    // Verify admin password
    $password_verify = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
    $password_verify->bind_param("i", $admin_id);
    $password_verify->execute();
    $password_result = $password_verify->get_result();
    $admin_data = $password_result->fetch_assoc();

    if (password_verify($admin_password, $admin_data['password'])) {
        // Password is correct, deactivate the user
        $delete_stmt = $conn->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
        $delete_stmt->bind_param("i", $user_id);

        if ($delete_stmt->execute()) {
            $deleteSuccess = true;
            $deleteMessage = "User has been successfully deactivated.";
        } else {
            $deleteMessage = "Error deactivating user: " . $conn->error;
        }
        $delete_stmt->close();
    } else {
        $deleteMessage = "Invalid admin password. User deletion canceled.";
    }
    $password_verify->close();
}

// Display alert message if set
if (!empty($deleteMessage)) {
    echo "<script>alert(" . json_encode($deleteMessage) . ");</script>";
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

// Prepare the WHERE clause based on search and filter
$where_clause = "1=1"; // Always true condition to start with

if (!empty($search)) {
    $search = "%$search%";
    $where_clause .= " AND (fullname LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
}

if ($status === 'active') {
    $where_clause .= " AND is_active = TRUE";
} elseif ($status === 'inactive') {
    $where_clause .= " AND is_active = FALSE";
}

// Prepare SQL for counting total users
$count_sql = "SELECT COUNT(*) as total FROM users WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);

// Bind search parameters if needed
if (!empty($search)) {
    $count_stmt->bind_param("ssss", $search, $search, $search, $search);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Pagination settings
$users_per_page = 10;
$total_pages = ceil($total_users / $users_per_page);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $users_per_page;

// Prepare SQL for fetching users with pagination
$sql = "SELECT id, fullname, email, username, address, phone, profile_image, is_active, last_login, created_at 
        FROM users 
        WHERE $where_clause 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Add parameter types and values
$types = '';
$params = [];

if (!empty($search)) {
    $types .= 'ssss';
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$types .= 'ii';
$params[] = $users_per_page;
$params[] = $offset;

// Convert params array to references
$bind_params = [];
foreach ($params as $key => $value) {
    $bind_params[$key] = &$params[$key];
}

// Bind parameters dynamically
if (!empty($params)) {
    array_unshift($bind_params, $types);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$stmt->execute();
$users_result = $stmt->get_result();

// Close the statement
$stmt->close();

// Function to format date
function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
}
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
        
        /* User Management Styles */
        .dashboard-container {
            padding: 30px;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .page-subtitle {
            font-size: 14px;
            color: var(--grey);
            margin-bottom: 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .search-filter-container {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            width: 100%;
        }

        @media (min-width: 768px) {
            .search-filter-container {
                margin-top: 0;
                width: auto;
            }
        }
        
        .search-box {
            display: flex;
            background: #f8f9fa;
            border-radius: 4px;
            overflow: hidden;
            width: 100%;
            max-width: 300px;
        }
        
        .search-box input {
            flex: 1;
            border: none;
            padding: 10px 15px;
            background: transparent;
            outline: none;
            font-size: 14px;
        }
        
        .search-box button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0 15px;
            cursor: pointer;
        }
        
        .filter-dropdown {
            position: relative;
        }
        
        .filter-btn {
            background: #f8f9fa;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .filter-btn i {
            margin-left: 5px;
        }
        
        .filter-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 40px;
            background: white;
            min-width: 150px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 10;
        }
        
        .filter-dropdown-content a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
        }
        
        .filter-dropdown-content a:hover {
            background: #f8f9fa;
        }
        
        .filter-dropdown.show .filter-dropdown-content {
            display: block;
        }
        
        .card-body {
            padding: 0;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th, .user-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        
        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .status-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-name-email {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
        }
        
        .user-email {
            font-size: 12px;
            color: var(--grey);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .pagination a {
            background-color: #f8f9fa;
            color: var(--dark);
        }
        
        .pagination a:hover {
            background-color: #e9ecef;
        }
        
        .pagination span {
            background-color: var(--secondary);
            color: white;
        }
        
        .empty-message {
            padding: 30px;
            text-align: center;
            color: var(--grey);
            font-size: 16px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow: auto;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            position: relative;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--grey);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #f5f5f5;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-secondary {
            background-color: #e9ecef;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #dee2e6;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0062a8;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.2rem rgba(0, 113, 197, 0.25);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        /* User Details Modal */
        .user-detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            width: 120px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .detail-value {
            flex: 1;
            color: var(--grey);
        }
        
        .user-profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .large-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid var(--secondary);
        }
        
        .user-profile-info {
            flex: 1;
        }
        
        .user-profile-name {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .user-profile-username {
            font-size: 14px;
            color: var(--secondary);
            margin-bottom: 5px;
        }
        
        .user-profile-date {
            font-size: 12px;
            color: var(--grey);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }
            
            .user-table th:nth-child(3),
            .user-table td:nth-child(3) {
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
            
            .user-table th:nth-child(4),
            .user-table td:nth-child(4),
            .user-table th:nth-child(5),
            .user-table td:nth-child(5) {
                display: none;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-filter-container {
                margin-top: 15px;
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-container {
                padding: 20px 15px;
            }
            
            .user-table th:nth-child(2),
            .user-table td:nth-child(2) {
                display: none;
            }
            
            .pagination {
                flex-wrap: wrap;
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

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">View and manage all registered users in the system.</p>
            
            <?php if (!empty($deleteMessage)): ?>
                <div class="alert <?php echo $deleteSuccess ? 'alert-success' : 'alert-danger'; ?>">
                    <?php echo htmlspecialchars($deleteMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Users List Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Users List</h2>
                    <div class="search-filter-container">
                        <form action="" method="GET" class="search-box">
                            <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                        
                        <div class="filter-dropdown" id="statusFilter">
                            <button class="filter-btn">
                                <?php echo $status === 'active' ? 'Active Users' : ($status === 'inactive' ? 'Inactive Users' : 'All Users'); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="filter-dropdown-content">
                                <a href="?status=all<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">All Users</a>
                                <a href="?status=active<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Active Users</a>
                                <a href="?status=inactive<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Inactive Users</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($users_result->num_rows > 0): ?>
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Image</th>
                                    <th>Username</th>
                                    <th>Phone</th>
                                    <th>Last Login</th>
                                    <th>Joined Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center;">
                                                <img src="<?php echo getProfileImageUrl($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['fullname']); ?>" class="user-avatar">
                                                <div class="user-name-email" style="margin-left: 10px;">
                                                    <div class="user-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '-'; ?></td>
                                        <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-actions">
                                                <button class="action-btn view-btn" onclick="openUserDetails(<?php echo $user['id']; ?>, '<?php echo addslashes($user['fullname']); ?>', '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo addslashes($user['address'] ?? ''); ?>', '<?php echo addslashes($user['phone'] ?? ''); ?>', '<?php echo addslashes($user['profile_image'] ?? ''); ?>', '<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>', '<?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?>', '<?php echo formatDate($user['created_at']); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($user['is_active']): ?>
                                                    <button class="action-btn delete-btn" onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['fullname']); ?>')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<a href="?page=1&status=' . $status . (!empty($search) ? '&search='.urlencode($search) : '') . '">1</a>';
                                    if ($start_page > 2) {
                                        echo '<span>...</span>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    if ($i == $page) {
                                        echo '<span>' . $i . '</span>';
                                    } else {
                                        echo '<a href="?page=' . $i . '&status=' . $status . (!empty($search) ? '&search='.urlencode($search) : '') . '">' . $i . '</a>';
                                    }
                                }
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span>...</span>';
                                    }
                                    echo '<a href="?page=' . $total_pages . '&status=' . $status . (!empty($search) ? '&search='.urlencode($search) : '') . '">' . $total_pages . '</a>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-message">
                            <i class="fas fa-users" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
                            <p>No users found. <?php echo !empty($search) ? 'Try a different search term.' : ''; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Details Modal -->
    <div class="modal" id="userDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">User Details</h3>
                <button class="modal-close" onclick="closeUserDetailsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="user-profile-header">
                    <img src="" alt="User" class="large-avatar" id="detailsUserAvatar">
                    <div class="user-profile-info">
                        <h2 class="user-profile-name" id="detailsUserName"></h2>
                        <div class="user-profile-username" id="detailsUserUsername"></div>
                        <div class="user-profile-date" id="detailsUserJoinDate"></div>
                    </div>
                </div>
                
                <div class="user-detail-row">
                    <div class="detail-label">Email:</div>
                    <div class="detail-value" id="detailsUserEmail"></div>
                </div>
                
                <div class="user-detail-row">
                    <div class="detail-label">Phone:</div>
                    <div class="detail-value" id="detailsUserPhone"></div>
                </div>
                
                <div class="user-detail-row">
                    <div class="detail-label">Address:</div>
                    <div class="detail-value" id="detailsUserAddress"></div>
                </div>
                
                <div class="user-detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge" id="detailsUserStatus"></span>
                    </div>
                </div>
                
                <div class="user-detail-row">
                    <div class="detail-label">Last Login:</div>
                    <div class="detail-value" id="detailsUserLastLogin"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeUserDetailsModal()">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Deactivate User</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate the user <strong id="deleteUserName"></strong>?</p>
                <p>This will prevent the user from logging in, but their data will be preserved in the system.</p>
                
                <form action="" method="POST" id="deleteForm">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
                    <div class="form-group">
                        <label for="admin_password" class="form-label">Enter your admin password to confirm:</label>
                        <input type="password" name="admin_password" id="admin_password" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" name= "delete_user" onclick="document.getElementById('deleteForm').submit()">Deactivate User</button>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar functionality
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        document.getElementById('sidebarClose').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
        });
        
        // Admin dropdown functionality
        document.getElementById('adminDropdown').addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('show');
        });
        
        // Filter dropdown functionality
        document.getElementById('statusFilter').addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('show');
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('adminDropdown').classList.remove('show');
            document.getElementById('statusFilter').classList.remove('show');
        });
        
        // User details modal functionality
        function openUserDetails(id, name, username, email, address, phone, image, status, lastLogin, joinDate) {
            // Set the user details in the modal
            document.getElementById('detailsUserName').textContent = name;
            document.getElementById('detailsUserUsername').textContent = '@' + username;
            document.getElementById('detailsUserEmail').textContent = email;
            document.getElementById('detailsUserPhone').textContent = phone || 'Not provided';
            document.getElementById('detailsUserAddress').textContent = address || 'Not provided';
            
            // Set status with appropriate class
            const statusElem = document.getElementById('detailsUserStatus');
            statusElem.textContent = status;
            statusElem.className = 'status-badge ' + (status === 'Active' ? 'status-active' : 'status-inactive');
            
            document.getElementById('detailsUserLastLogin').textContent = lastLogin;
            document.getElementById('detailsUserJoinDate').textContent = 'Member since ' + joinDate;
            
            // Set the user avatar
            const avatarElem = document.getElementById('detailsUserAvatar');
            if (image && image !== 'null') {
                avatarElem.src = 'uploads/profiles/' + image;
            } else {
                avatarElem.src = 'assets/images/default-avatar.png';
            }
            
            // Show the modal
            document.getElementById('userDetailsModal').classList.add('show');
        }
        
        function closeUserDetailsModal() {
            document.getElementById('userDetailsModal').classList.remove('show');
        }
        
        // Delete user modal functionality
        function openDeleteModal(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.getElementById('admin_password').value = '';
        }
    </script>
</body>
</html>