<?php
// Start the session at the very beginning
session_start();
// Include your environment-aware database connection
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

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png";
    }
}

$profileImageUrl = getProfileImageUrl($admin['profile_image']);

// Initialize variables
$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_user':
                $user_id = $_POST['user_id'];
                $admin_password = $_POST['admin_password'];
                $admin_id = $_SESSION['admin_id']; // assumes admin is logged in
            
                // Get user info BEFORE deletion
                $user_stmt = $conn->prepare("SELECT fullname, email FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_info = $user_stmt->get_result()->fetch_assoc();
            
                if (!$user_info) {
                    $message = "User not found.";
                    $messageType = 'error';
                    break;
                }
            
                // Get admin hashed password
                $password_verify = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
                $password_verify->bind_param("i", $admin_id);
                $password_verify->execute();
                $password_result = $password_verify->get_result();
                $admin_data = $password_result->fetch_assoc();
            
                if (!$admin_data) {
                    $message = "Admin not found.";
                    $messageType = 'error';
                    break;
                }
            
                // Compare hashed MD5
                if (md5($admin_password) === $admin_data['password']) {
                    // Delete the user
                    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $delete_stmt->bind_param("i", $user_id);
            
                    if ($delete_stmt->execute()) {
                        $message = "User '{$user_info['fullname']}' has been successfully deleted.";
                        $messageType = 'success';
                    } else {
                        $message = "Error deleting user: " . $conn->error;
                        $messageType = 'error';
                    }
                } else {
                    $message = "Invalid admin password. User deletion canceled.";
                    $messageType = 'error';
                }
                break;

                
            case 'activate_user':
                $user_id = $_POST['user_id'];
                $activate_stmt = $conn->prepare("UPDATE users SET is_active = TRUE, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $activate_stmt->bind_param("i", $user_id);
                
                if ($activate_stmt->execute()) {
                    $message = "User has been successfully activated.";
                    $messageType = 'success';
                } else {
                    $message = "Error activating user: " . $conn->error;
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(fullname LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= 'ssss';
}

if ($status === 'active') {
    $where_conditions[] = "is_active = TRUE";
} elseif ($status === 'inactive') {
    $where_conditions[] = "is_active = FALSE";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];

// Pagination
$users_per_page = 15;
$total_pages = ceil($total_users / $users_per_page);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $users_per_page;

// Get users with pagination
$allowed_sorts = ['id', 'fullname', 'email', 'username', 'created_at', 'updated_at'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT id, fullname, email, username, address, phone, profile_image, is_active, 
               created_at, updated_at, otp_purpose, otp_expires_at 
        FROM users 
        $where_clause 
        ORDER BY $sort $order 
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$final_params = array_merge($params, [$users_per_page, $offset]);
$final_types = $types . 'ii';

if (!empty($final_params)) {
    $stmt->bind_param($final_types, ...$final_params);
}

$stmt->execute();
$users_result = $stmt->get_result();

// Get all users for PDF export (without pagination)
$all_users_sql = "SELECT id, fullname, email, username, address, phone, profile_image, is_active, 
                         created_at, updated_at, otp_purpose, otp_expires_at 
                  FROM users 
                  $where_clause 
                  ORDER BY $sort $order";

$all_users_stmt = $conn->prepare($all_users_sql);
if (!empty($params)) {
    $all_users_stmt->bind_param($types, ...$params);
}
$all_users_stmt->execute();
$all_users_result = $all_users_stmt->get_result();

$all_users = [];
while ($user = $all_users_result->fetch_assoc()) {
    $all_users[] = $user;
}

// Function to format date
function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
}

// FIX: Updated getUserStatus function to handle null values properly
function getUserStatus($is_active, $otp_purpose, $otp_expires_at) {
    if (!$is_active) {
        return ['status' => 'Inactive', 'class' => 'status-inactive'];
    }
    
    // Check if user has pending email verification
    if ($otp_purpose === 'EMAIL_VERIFICATION' && !empty($otp_expires_at) && strtotime($otp_expires_at) > time()) {
        return ['status' => 'Pending Verification', 'class' => 'status-pending'];
    }
    
    return ['status' => 'Active', 'class' => 'status-active'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            --sidebar-width: 260px;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
            --pending: #fd7e14;
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
            background: linear-gradient(135deg, var(--primary) 0%, #1a1a1a 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }
        
        .sidebar-logo {
            font-size: 24px;
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
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .sidebar-close:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #adb5bd;
            padding: 15px 20px 10px;
            margin-top: 10px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: #e9ecef;
            text-decoration: none;
            padding: 14px 20px;
            transition: all 0.3s ease;
            font-size: 14px;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--secondary);
            border-left-color: var(--secondary);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(0, 113, 197, 0.15);
            border-left-color: var(--secondary);
            color: var(--secondary);
        }
        
        .sidebar-menu a i {
            margin-right: 12px;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 15px 25px;
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
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .toggle-sidebar:hover {
            background-color: #f8f9fa;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
        }
        
        .navbar-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 20px;
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
            gap: 15px;
        }
        
        .navbar-actions .nav-link {
            color: var(--dark);
            font-size: 18px;
            position: relative;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .navbar-actions .nav-link:hover {
            background-color: #f8f9fa;
        }
        
        .notification-count {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
        }
        
        /* Admin Profile Styles */
        .admin-profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .admin-profile:hover {
            background-color: #f8f9fa;
        }

        .admin-avatar-container {
            position: relative;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            overflow: hidden;
            border: 2px solid var(--secondary);
            margin-right: 12px;
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
            top: 55px;
            background-color: white;
            min-width: 240px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 1000;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .admin-dropdown-header {
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a1a 100%);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
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

        .admin-dropdown-content a {
            color: var(--dark);
            padding: 12px 15px;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            border-bottom: 1px solid #f5f5f5;
            transition: background-color 0.2s;
        }

        .admin-dropdown-content a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .admin-dropdown-content a.logout {
            color: var(--danger);
            border-bottom: none;
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
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .page-subtitle {
            font-size: 16px;
            color: var(--grey);
            margin-bottom: 20px;
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
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid var(--secondary);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-card-title {
            font-size: 14px;
            color: var(--grey);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }
        
        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .stat-card-change {
            font-size: 12px;
            font-weight: 500;
        }
        
        .change-positive {
            color: var(--success);
        }
        
        .change-negative {
            color: var(--danger);
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            border-left: 4px solid;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border-left-color: var(--success);
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-left-color: var(--danger);
        }
        
        /* Main Card */
        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 25px;
            border-bottom: 1px solid #f5f5f5;
            background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
        }
        
        .card-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .card-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        /* Search and Filter Controls */
        .search-filter-container {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-box {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            transition: border-color 0.2s;
            min-width: 300px;
        }
        
        .search-box:focus-within {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
        }
        
        .search-box input {
            flex: 1;
            border: none;
            padding: 12px 15px;
            background: transparent;
            outline: none;
            font-size: 14px;
        }
        
        .search-box button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0 18px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .search-box button:hover {
            background-color: #0062a8;
        }
        
        .filter-dropdown {
            position: relative;
        }
        
        .filter-btn {
            background: white;
            border: 1px solid #e9ecef;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            min-width: 140px;
            justify-content: space-between;
        }
        
        .filter-btn:hover {
            border-color: var(--secondary);
            background-color: #f8f9fa;
        }
        
        .filter-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            background: white;
            min-width: 160px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 8px;
            z-index: 10;
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        
        .filter-dropdown-content a {
            display: block;
            padding: 12px 15px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .filter-dropdown-content a:hover {
            background: #f8f9fa;
        }
        
        .filter-dropdown.show .filter-dropdown-content {
            display: block;
        }

        /* Export Button */
        .export-btn {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .export-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        /* Table Styles */
        .card-body {
            padding: 0;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th, .user-table td {
            padding: 18px 25px;
            text-align: left;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        
        .user-table th {
            background-color: #fafafa;
            font-weight: 600;
            color: var(--dark);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-table tbody tr {
            transition: background-color 0.2s;
        }
        
        .user-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 3px;
            font-size: 14px;
        }
        
        .user-email {
            font-size: 12px;
            color: var(--grey);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .status-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .status-pending {
            background-color: rgba(253, 126, 20, 0.1);
            color: var(--pending);
        }
        
        /* Action Buttons */
        .user-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .view-btn {
            color: var(--info);
        }
        
        .view-btn:hover {
            background-color: rgba(23, 162, 184, 0.1);
            transform: scale(1.1);
        }
        
        .activate-btn {
            color: var(--success);
        }
        
        .activate-btn:hover {
            background-color: rgba(40, 167, 69, 0.1);
            transform: scale(1.1);
        }
        
        .delete-btn {
            color: var(--danger);
        }
        
        .delete-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            transform: scale(1.1);
        }
        
        /* Pagination */
        .pagination-container {
            padding: 25px;
            border-top: 1px solid #f5f5f5;
            background-color: #fafafa;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            min-width: 40px;
            transition: all 0.2s;
        }
        
        .pagination a {
            background-color: white;
            color: var(--dark);
            border: 1px solid #e9ecef;
        }
        
        .pagination a:hover {
            background-color: var(--secondary);
            color: white;
            border-color: var(--secondary);
            transform: translateY(-1px);
        }
        
        .pagination span {
            background-color: var(--secondary);
            color: white;
            border: 1px solid var(--secondary);
        }
        
        .pagination .page-info {
            background: none;
            border: none;
            color: var(--grey);
            font-size: 13px;
            margin: 0 10px;
        }
        
        /* Empty State */
        .empty-message {
            padding: 60px 30px;
            text-align: center;
            color: var(--grey);
        }
        
        .empty-message i {
            font-size: 64px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-message h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .empty-message p {
            font-size: 14px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
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
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
            animation: modalFadeIn 0.3s ease-out;
            margin: 20px;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            padding: 25px;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #fafafa 0%, #ffffff 100%);
            border-radius: 12px 12px 0 0;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--grey);
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background-color: #f8f9fa;
            color: var(--dark);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #f5f5f5;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background-color: #fafafa;
            border-radius: 0 0 12px 12px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background-color: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-secondary {
            background-color: #e9ecef;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background-color: #dee2e6;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary) 0%, #0062a8 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 113, 197, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        /* User Details Modal */
        .user-profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 8px;
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
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .user-profile-username {
            font-size: 14px;
            color: var(--secondary);
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .user-profile-date {
            font-size: 12px;
            color: var(--grey);
        }
        
        .user-detail-row {
            display: flex;
            margin-bottom: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .user-detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            width: 120px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }
        
        .detail-value {
            flex: 1;
            color: var(--grey);
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }
            
            .search-box {
                min-width: 250px;
            }
            
            .user-table th:nth-child(4),
            .user-table td:nth-child(4),
            .user-table th:nth-child(5),
            .user-table td:nth-child(5) {
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
            
            .dashboard-container {
                padding: 20px 15px;
            }
            
            .card-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-filter-container {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .user-table th:nth-child(3),
            .user-table td:nth-child(3) {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .user-table th:nth-child(2),
            .user-table td:nth-child(2) {
                display: none;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .modal-content {
                margin: 10px;
            }
            
            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 20px;
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
            <a href="user-management.php" class="active"><i class="fas fa-users"></i> User Management</a>
            
            <div class="menu-title">REPORTS & SETTINGS</div>
            <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
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
                <div class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($admin['fullname']); ?></strong>!</div>
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
                    
                    <div class="admin-dropdown-content">
                        <div class="admin-dropdown-header">
                            <div class="admin-dropdown-avatar-container">
                                <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Admin" class="admin-dropdown-avatar">
                            </div>
                            <div class="admin-dropdown-info">
                                <span class="admin-dropdown-name"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                                <span class="admin-dropdown-role"><?php echo htmlspecialchars($admin['role']); ?></span>
                            </div>
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
            <div class="page-header">
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">Manage and monitor all registered users in your system</p>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <?php
            // Get user statistics
            $stats_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN is_active = TRUE THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN is_active = FALSE THEN 1 ELSE 0 END) as inactive_users,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users
                FROM users";
            $stats_result = $conn->query($stats_sql);
            $stats = $stats_result->fetch_assoc();
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Total Users</div>
                        <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--secondary) 0%, #0062a8 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-card-change change-positive">
                        <i class="fas fa-arrow-up"></i> All registered users
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Active Users</div>
                        <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="stat-card-change change-positive">
                        <i class="fas fa-arrow-up"></i> Currently active
                    </div>
                </div>
                
                <!--<div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Inactive Users</div>
                        <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['inactive_users']); ?></div>
                    <div class="stat-card-change">
                        <i class="fas fa-minus"></i> Deactivated accounts
                    </div>
                </div>-->
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">New This Month</div>
                        <div class="stat-card-icon" style="background: linear-gradient(135deg, var(--pending) 0%, #fd7e14 100%);">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['new_users']); ?></div>
                    <div class="stat-card-change change-positive">
                        <i class="fas fa-arrow-up"></i> Last 30 days
                    </div>
                </div>
            </div>
            
            <!-- Users List Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-content">
                        <h2 class="card-title">Users Directory</h2>
                        <div class="card-actions">
                            <div class="search-filter-container">
                                <form action="" method="GET" class="search-box">
                                    <input type="text" name="search" placeholder="Search users by name, email, username..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button type="submit"><i class="fas fa-search"></i></button>
                                </form>
                                
                                <div class="filter-dropdown" id="statusFilter">
                                    <button class="filter-btn" type="button">
                                        <span><?php echo $status === 'active' ? 'Active Users' : ($status === 'inactive' ? 'Inactive Users' : 'All Users'); ?></span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="filter-dropdown-content">
                                        <a href="?status=all<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">All Users</a>
                                        <a href="?status=active<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Active Users</a>
                                        <a href="?status=inactive<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Inactive Users</a>
                                    </div>
                                </div>

                                <button onclick="exportUsersPDF()" class="export-btn">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($users_result->num_rows > 0): ?>
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User Information</th>
                                    <th>Username</th>
                                    <th>Phone</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users_result->fetch_assoc()): 
                                    $userStatus = getUserStatus($user['is_active'], $user['otp_purpose'], $user['otp_expires_at']);
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                                        <td>
                                            <div class="user-info">
                                                <img src="<?php echo getProfileImageUrl($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['fullname']); ?>" class="user-avatar">
                                                <div class="user-details">
                                                    <div class="user-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong>@<?php echo htmlspecialchars($user['username']); ?></strong></td>
                                        <td><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span style="color: #ccc;">Not provided</span>'; ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $userStatus['class']; ?>">
                                                <?php echo $userStatus['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="user-actions">
                                                <button class="action-btn view-btn" onclick="openUserDetails(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (!$user['is_active']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="activate_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="action-btn activate-btn" title="Activate User" onclick="return confirm('Are you sure you want to activate this user?')">
                                                            <i class="fas fa-user-check"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="action-btn delete-btn" onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['fullname']); ?>')" title="Delete User">
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
                            <div class="pagination-container">
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<a href="?page=1&status=' . $status . (!empty($search) ? '&search='.urlencode($search) : '') . '&sort=' . $sort . '&order=' . $order . '">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="page-info">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        if ($i == $page) {
                                            echo '<span>' . $i . '</span>';
                                        } else {
                                            echo '<a href="?page=' . $i . '&status=' . $status . (!empty($search) ? '&search='.urlencode($search) : '') . '&sort=' . $sort . '&order=' . $order . '">' . $i . '</a>';
                                        }
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="page-info">...</span>';
                                        }
                                        echo '<a href="?page=' . $total_pages . '&status=' . $status . (!empty($search) ? '&search='.urlencode($search) : '') . '&sort=' . $sort . '&order=' . $order . '">' . $total_pages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <span class="page-info">
                                        Showing <?php echo (($page - 1) * $users_per_page) + 1; ?> to <?php echo min($page * $users_per_page, $total_users); ?> of <?php echo $total_users; ?> users
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="empty-message">
                            <i class="fas fa-users"></i>
                            <h3>No Users Found</h3>
                            <p><?php echo !empty($search) ? 'Try adjusting your search criteria or filters.' : 'No users have been registered yet.'; ?></p>
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
                    <img src="/placeholder.svg" alt="User" class="large-avatar" id="detailsUserAvatar">
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
                    <div class="detail-label">Last Updated:</div>
                    <div class="detail-value" id="detailsUserUpdated"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeUserDetailsModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-exclamation-triangle" style="color: var(--danger); margin-right: 10px;"></i>
                    Delete User Account
                </h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: rgba(220, 53, 69, 0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid var(--danger);">
                    <p style="margin: 0; color: var(--danger); font-weight: 500;">
                        <i class="fas fa-info-circle"></i> 
                        You are about to permanently delete the user account for <strong id="deleteUserName"></strong>.
                    </p>
                </div>
                
                <p style="margin-bottom: 15px;">This action will:</p>
                <ul style="margin-bottom: 20px; padding-left: 20px; color: var(--grey);">
                    <li>Permanently remove the user from the system</li>
                    <li>Delete all associated user data</li>
                    <li>This action cannot be undone</li>
                </ul>
                
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    
                    <div class="form-group">
                        <label for="admin_password" class="form-label">
                            <i class="fas fa-lock"></i> Enter your admin password to confirm:
                        </label>
                        <input type="password" name="admin_password" id="admin_password" class="form-control" required placeholder="Your admin password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-danger" onclick="document.getElementById('deleteForm').submit()">
                    <i class="fas fa-trash-alt"></i> Delete User
                </button>
            </div>
        </div>
    </div>

    <script>
        // Store users data for PDF export
        const allUsers = <?php echo json_encode($all_users); ?>;
        const userStats = {
            total: <?php echo $stats['total_users']; ?>,
            active: <?php echo $stats['active_users']; ?>,
            inactive: <?php echo $stats['inactive_users']; ?>,
            newThisMonth: <?php echo $stats['new_users']; ?>
        };

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
        function openUserDetails(user) {
            // Set the user details in the modal
            document.getElementById('detailsUserName').textContent = user.fullname;
            document.getElementById('detailsUserUsername').textContent = '@' + user.username;
            document.getElementById('detailsUserEmail').textContent = user.email;
            document.getElementById('detailsUserPhone').textContent = user.phone || 'Not provided';
            document.getElementById('detailsUserAddress').textContent = user.address || 'Not provided';
            document.getElementById('detailsUserJoinDate').textContent = 'Member since ' + new Date(user.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('detailsUserUpdated').textContent = new Date(user.updated_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // FIX: Set status with appropriate class using the same logic as PHP
            const statusElem = document.getElementById('detailsUserStatus');
            const userStatus = getUserStatusFromData(user.is_active, user.otp_purpose, user.otp_expires_at);
            statusElem.textContent = userStatus.status;
            statusElem.className = 'status-badge ' + userStatus.class;
            
            // Set the user avatar
            const avatarElem = document.getElementById('detailsUserAvatar');
            if (user.profile_image && user.profile_image !== 'null') {
                avatarElem.src = 'uploads/profiles/' + user.profile_image;
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
            document.getElementById('admin_password').value = '';
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
            document.getElementById('admin_password').value = '';
        }
        
        // FIX: Helper function to get user status (matches PHP logic exactly)
        function getUserStatusFromData(isActive, otpPurpose, otpExpiresAt) {
            if (!isActive) {
                return { status: 'Inactive', class: 'status-inactive' };
            }
            
            // Check if user has pending email verification
            if (otpPurpose === 'EMAIL_VERIFICATION' && otpExpiresAt && new Date(otpExpiresAt) > new Date()) {
                return { status: 'Pending Verification', class: 'status-pending' };
            }
            
            return { status: 'Active', class: 'status-active' };
        }

        // PDF Export Function
        function exportUsersPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // HirayaFit brand colors (RGB values)
    const primaryColor = [17, 17, 17];     // #111111
    const secondaryColor = [0, 113, 197];  // #0071c5
    const successColor = [40, 167, 69];    // #28a745
    const dangerColor = [220, 53, 69];     // #dc3545
    const warningColor = [255, 193, 7];    // #ffc107
    const pendingColor = [253, 126, 20];   // #fd7e14
    const greyColor = [118, 118, 118];     // #767676

    // Header
    doc.setFillColor(...primaryColor);
    doc.rect(0, 0, 210, 35, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(28);
    doc.setFont('helvetica', 'bold');
    doc.text('HirayaFit', 20, 22);

    // Subtitle
    doc.setTextColor(...secondaryColor);
    doc.setFontSize(14);
    doc.setFont('helvetica', 'normal');
    doc.text('User Management Report', 20, 45);

    // Section title
    doc.setTextColor(...primaryColor);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('User Statistics Overview', 20, 65);

    // Stat cards (3 columns)
    let yPos = 75;
    let cardWidth = 60;
    let cardHeight = 25;
    let cardGap = 15;

    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');

    // Total Users
    doc.setFillColor(...secondaryColor);
    doc.rect(20, yPos, cardWidth, cardHeight, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.text('TOTAL USERS', 22, yPos + 10);
    doc.setFontSize(16);
    doc.text(userStats.total.toString(), 22, yPos + 20);

    // Active Users
    doc.setFillColor(...successColor);
    doc.rect(20 + cardWidth + cardGap, yPos, cardWidth, cardHeight, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('ACTIVE USERS', 22 + cardWidth + cardGap, yPos + 10);
    doc.setFontSize(16);
    doc.text(userStats.active.toString(), 22 + cardWidth + cardGap, yPos + 20);

    // New This Month
    doc.setFillColor(...pendingColor);
    doc.rect(20 + 2 * (cardWidth + cardGap), yPos, cardWidth, cardHeight, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('NEW', 22 + 2 * (cardWidth + cardGap), yPos + 10);
    doc.setFontSize(16);
    doc.text(userStats.newThisMonth.toString(), 22 + 2 * (cardWidth + cardGap), yPos + 20);

    yPos += 40;

    // User Directory
    doc.setTextColor(...primaryColor);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('User Directory', 20, yPos);
    yPos += 10;

    const tableData = allUsers.map(user => {
        const userStatus = getUserStatusFromData(user.is_active, user.otp_purpose, user.otp_expires_at);
        return [
            '#' + user.id,
            user.fullname,
            user.email,
            '@' + user.username,
            user.phone || 'Not provided',
            new Date(user.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            }),
            userStatus.status
        ];
    });

    doc.autoTable({
        startY: yPos,
        head: [['ID', 'Full Name', 'Email', 'Username', 'Phone', 'Join Date', 'Status']],
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
            0: { cellWidth: 15 },
            1: { cellWidth: 35 },
            2: { cellWidth: 45 },
            3: { cellWidth: 25 },
            4: { cellWidth: 25 },
            5: { cellWidth: 25 },
            6: { cellWidth: 20 }
        },
        didParseCell: function (data) {
            if (data.column.index === 6) {
                const status = data.cell.text[0];
                if (status === 'Active') {
                    data.cell.styles.textColor = successColor;
                    data.cell.styles.fontStyle = 'bold';
                } else if (status === 'Inactive') {
                    data.cell.styles.textColor = dangerColor;
                    data.cell.styles.fontStyle = 'bold';
                } else if (status === 'Pending Verification') {
                    data.cell.styles.textColor = pendingColor;
                    data.cell.styles.fontStyle = 'bold';
                }
            }
        }
    });

    // Summary footer bar (updated: no inactive)
    const finalY = doc.lastAutoTable.finalY + 15;
    doc.setFillColor(...secondaryColor);
    doc.rect(15, finalY - 2, 180, 12, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text(`TOTAL USERS: ${userStats.total} | ACTIVE: ${userStats.active}`, 20, finalY + 5);

    // Footer text
    doc.setTextColor(...greyColor);
    doc.setFontSize(8);
    doc.setFont('helvetica', 'normal');
    doc.text('HirayaFit User Management Report', 20, 280);
    doc.text(`Generated on: ${new Date().toLocaleString()}`, 20, 285);

    // Save the PDF
    doc.save(`HirayaFit_Users_Report_${new Date().toISOString().split('T')[0]}.pdf`);
}
    </script>
</body>
</html>