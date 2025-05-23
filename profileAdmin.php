<?php
session_start();
include 'db_connect.php';

// Initialize variables
$error = '';
$success = '';
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

// Set default role if not present
if (!isset($admin['role'])) {
    $admin['role'] = 'super_admin';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle profile picture upload
    if (isset($_POST['upload_picture']) && isset($_FILES['profile_image'])) {
        $upload_dir = "uploads/profiles/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['profile_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old profile image if exists
                    if (!empty($admin['profile_image']) && file_exists($upload_dir . $admin['profile_image'])) {
                        unlink($upload_dir . $admin['profile_image']);
                    }
                    
                    // Update database
                    $update_stmt = $conn->prepare("UPDATE admins SET profile_image = ? WHERE admin_id = ?");
                    $update_stmt->bind_param("si", $new_filename, $admin_id);
                    
                    if ($update_stmt->execute()) {
                        $admin['profile_image'] = $new_filename;
                        $success = "Profile picture updated successfully!";
                    } else {
                        $error = "Failed to update profile picture in database.";
                    }
                    $update_stmt->close();
                } else {
                    $error = "Failed to upload file.";
                }
            } else {
                $error = "Invalid file type or size. Please upload a JPEG, PNG, or GIF image under 5MB.";
            }
        } else {
            $error = "File upload error occurred.";
        }
    }
    
    // Handle profile information update
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $bio = trim($_POST['bio']);
        
        // Validation
        if (empty($fullname) || empty($username) || empty($email)) {
            $error = "Full name, username, and email are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if username or email already exists (excluding current admin)
            $check_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE (username = ? OR email = ?) AND admin_id != ?");
            $check_stmt->bind_param("ssi", $username, $email, $admin_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                // Update profile information
                $update_stmt = $conn->prepare("UPDATE admins SET fullname = ?, username = ?, email = ?, phone = ?, address = ?, bio = ? WHERE admin_id = ?");
                $update_stmt->bind_param("ssssssi", $fullname, $username, $email, $phone, $address, $bio, $admin_id);
                
                if ($update_stmt->execute()) {
                    $admin['fullname'] = $fullname;
                    $admin['username'] = $username;
                    $admin['email'] = $email;
                    $admin['phone'] = $phone;
                    $admin['address'] = $address;
                    $admin['bio'] = $bio;
                    $success = "Profile information updated successfully!";
                } else {
                    $error = "Failed to update profile information.";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $verify_stmt = $conn->prepare("SELECT password FROM admins WHERE admin_id = ?");
            $verify_stmt->bind_param("i", $admin_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $admin_data = $verify_result->fetch_assoc();
            
            if (password_verify($current_password, $admin_data['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $admin_id);
                
                if ($update_stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }
                $update_stmt->close();
            } else {
                $error = "Current password is incorrect.";
            }
            $verify_stmt->close();
        }
    }
}

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png";
    }
}

$profileImageUrl = getProfileImageUrl($admin['profile_image']);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - HirayaFit Admin</title>
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
        
        .breadcrumb {
            font-size: 14px;
            color: var(--grey);
        }
        
        .breadcrumb a {
            color: var(--secondary);
            text-decoration: none;
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
        
        /* Profile Settings Container */
        .profile-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .profile-image-section {
            text-align: center;
        }
        
        .profile-image-container {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid var(--secondary);
            margin: 0 auto 15px;
        }
        
        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-image-overlay:hover {
            background: rgba(0,0,0,0.9);
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-name {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .profile-role {
            font-size: 16px;
            color: var(--secondary);
            margin-bottom: 10px;
        }
        
        .profile-email {
            font-size: 14px;
            color: var(--grey);
        }
        
        .profile-tabs {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab-navigation {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tab-button {
            background: none;
            border: none;
            padding: 15px 25px;
            font-size: 14px;
            font-weight: 500;
            color: var(--grey);
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: var(--secondary);
            border-bottom-color: var(--secondary);
            background: white;
        }
        
        .tab-button:hover {
            color: var(--secondary);
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f3f4;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(0, 113, 197, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #005a9f;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--secondary);
            border: 1px solid var(--secondary);
        }
        
        .btn-outline:hover {
            background-color: var(--secondary);
            color: white;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            border-color: var(--secondary);
            background: #f0f8ff;
        }
        
        .file-upload-area.dragover {
            border-color: var(--secondary);
            background: #e3f2fd;
        }
        
        .upload-icon {
            font-size: 48px;
            color: var(--grey);
            margin-bottom: 15px;
        }
        
        .upload-text {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .upload-subtext {
            font-size: 14px;
            color: var(--grey);
        }
        
        .hidden {
            display: none;
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
            
            .form-row {
                flex-direction: column;
            }
            
            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .tab-navigation {
                flex-wrap: wrap;
            }
            
            .tab-button {
                flex: 1;
                min-width: 120px;
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
                <span class="navbar-title">Profile Settings</span>
                <div class="breadcrumb">
                    <a href="dashboard.php">Dashboard</a> / Profile Settings
                </div>
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
                        <a href="profileAdmin.php" class="active"><i class="fas fa-user"></i> Profile Settings</a>
                       
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Profile Settings Content -->
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-header-content">
                    <div class="profile-image-section">
                        <div class="profile-image-container">
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile" class="profile-image" id="profilePreview">
                            <div class="profile-image-overlay" onclick="document.getElementById('profileImageInput').click()">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name"><?php echo htmlspecialchars($admin['fullname']); ?></h1>
                        <p class="profile-role"><?php echo htmlspecialchars($admin['role']); ?></p>
                        <p class="profile-email"><?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Tabs -->
            <div class="profile-tabs">
                <div class="tab-navigation">
                    <button class="tab-button active" data-tab="profile">
                        <i class="fas fa-user"></i> Profile Information
                    </button>
                    <button class="tab-button" data-tab="picture">
                        <i class="fas fa-camera"></i> Profile Picture
                    </button>
                    <button class="tab-button" data-tab="password">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                    <!-- <button class="tab-button" data-tab="preferences">
                        <i class="fas fa-cog"></i> Preferences
                    </button> -->
                </div>

                <!-- Profile Information Tab -->
                <div class="tab-content">
                    <div class="tab-pane active" id="profile">
                        <form method="POST" action="">
                            <div class="form-section">
                                <h3 class="section-title">Basic Information</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="fullname">Full Name *</label>
                                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($admin['fullname']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="username">Username *</label>
                                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address *</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <!-- <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" placeholder="+1 (555) 123-4567"> -->
                                    </div>
                                </div>
                                
                                <!-- <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" placeholder="Enter your full address"><?php echo htmlspecialchars($admin['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="bio">Bio</label>
                                    <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($admin['bio'] ?? ''); ?></textarea>
                                </div> -->
                            </div>
                            
                            <div class="form-section">
                                <h3 class="section-title">Role Information</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <input type="text" value="<?php echo htmlspecialchars($admin['role']); ?>" readonly style="background-color: #f8f9fa;">
                                        <small style="color: #6c757d; font-size: 12px;">Role cannot be changed.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Admin ID</label>
                                        <input type="text" value="<?php echo htmlspecialchars($admin['admin_id']); ?>" readonly style="background-color: #f8f9fa;">
                                        <small style="color: #6c757d; font-size: 12px;">Admin ID cannot be changed.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Profile Picture Tab -->
                    <div class="tab-pane" id="picture">
                        <div class="form-section">
                            <h3 class="section-title">Profile Picture</h3>
                            
                            <div style="display: flex; gap: 30px; align-items: flex-start;">
                                <div style="text-align: center;">
                                    <div class="profile-image-container" style="margin-bottom: 15px;">
                                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Current Profile" class="profile-image" id="currentProfileImage">
                                    </div>
                                    <p style="font-size: 14px; color: #6c757d;">Current Picture</p>
                                </div>
                                
                                <div style="flex: 1;">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="file-upload-area" id="fileUploadArea">
                                            <div class="upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <div class="upload-text">Click to upload or drag and drop</div>
                                            <div class="upload-subtext">PNG, JPG, GIF up to 5MB</div>
                                            <input type="file" id="profileImageInput" name="profile_image" accept="image/*" class="hidden">
                                        </div>
                                        
                                        <div style="margin-top: 20px;">
                                            <button type="submit" name="upload_picture" class="btn btn-primary">
                                                <i class="fas fa-upload"></i> Upload New Picture
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                                        <h4 style="margin-bottom: 10px; font-size: 14px;">Image Requirements:</h4>
                                        <ul style="font-size: 13px; color: #6c757d; margin-left: 20px;">
                                            <li>Maximum file size: 5MB</li>
                                            <li>Supported formats: JPEG, PNG, GIF</li>
                                            <li>Recommended size: 400x400 pixels</li>
                                            <li>Square images work best</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Change Password Tab -->
                    <div class="tab-pane" id="password">
                        <form method="POST" action="">
                            <div class="form-section">
                                <h3 class="section-title">Change Password</h3>
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password *</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">New Password *</label>
                                        <input type="password" id="new_password" name="new_password" required minlength="6">
                                        <small style="color: #6c757d; font-size: 12px;">Minimum 6 characters</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password *</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                                    <h4 style="margin-bottom: 10px; font-size: 14px; color: #856404;"><i class="fas fa-exclamation-triangle"></i> Password Requirements:</h4>
                                    <ul style="font-size: 13px; color: #856404; margin-left: 20px;">
                                        <li>At least 6 characters long</li>
                                        <li>Include both letters and numbers for better security</li>
                                        <li>Avoid using personal information</li>
                                        <li>Use a unique password not used elsewhere</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Preferences Tab -->
                    <!-- <div class="tab-pane" id="preferences">
                        <div class="form-section">
                            <h3 class="section-title">Account Preferences</h3>
                            
                            <div class="form-group">
                                <label>Email Notifications</label>
                                <div style="margin-top: 10px;">
                                    <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: normal;">
                                        <input type="checkbox" style="margin-right: 10px;" checked>
                                        Receive order notifications
                                    </label>
                                    <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: normal;">
                                        <input type="checkbox" style="margin-right: 10px;" checked>
                                        Receive system alerts
                                    </label>
                                    <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: normal;">
                                        <input type="checkbox" style="margin-right: 10px;">
                                        Receive marketing emails
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone" name="timezone">
                                        <option value="UTC">UTC (Coordinated Universal Time)</option>
                                        <option value="America/New_York">Eastern Time (ET)</option>
                                        <option value="America/Chicago">Central Time (CT)</option>
                                        <option value="America/Denver">Mountain Time (MT)</option>
                                        <option value="America/Los_Angeles" selected>Pacific Time (PT)</option>
                                        <option value="Europe/London">Greenwich Mean Time (GMT)</option>
                                        <option value="Asia/Manila">Philippine Time (PHT)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="language">Language</label>
                                    <select id="language" name="language">
                                        <option value="en" selected>English</option>
                                        <option value="es">Español</option>
                                        <option value="fr">Français</option>
                                        <option value="de">Deutsch</option>
                                        <option value="tl">Tagalog</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Dashboard Theme</label>
                                <div style="margin-top: 10px;">
                                    <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: normal;">
                                        <input type="radio" name="theme" value="light" style="margin-right: 10px;" checked>
                                        Light Theme
                                    </label>
                                    <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: normal;">
                                        <input type="radio" name="theme" value="dark" style="margin-right: 10px;">
                                        Dark Theme
                                    </label>
                                    <label style="display: flex; align-items: center; margin-bottom: 10px; font-weight: normal;">
                                        <input type="radio" name="theme" value="auto" style="margin-right: 10px;">
                                        Auto (System Default)
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </div>
                        
                        <div class="form-section" style="border-top: 1px solid #dee2e6; padding-top: 30px; margin-top: 30px;">
                            <h3 class="section-title" style="color: var(--danger);">Danger Zone</h3>
                            
                            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 20px;">
                                <h4 style="color: #721c24; margin-bottom: 10px;">Deactivate Account</h4>
                                <p style="color: #721c24; font-size: 14px; margin-bottom: 15px;">
                                    Deactivating your account will disable your access to the admin panel. This action can be reversed by a system administrator.
                                </p>
                                <button type="button" class="btn btn-danger" onclick="confirm('Are you sure you want to deactivate your account? This action will log you out immediately.') && alert('Contact system administrator to reactivate your account.')">
                                    <i class="fas fa-user-times"></i> Deactivate Account
                                </button>
                            </div>
                        </div>
                    </div> -->
                </div>
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
        
        // Tab functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Remove active class from all buttons and panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Add active class to clicked button and corresponding pane
                this.classList.add('active');
                document.getElementById(targetTab).classList.add('active');
            });
        });
        
        // File upload functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const profileImageInput = document.getElementById('profileImageInput');
        const profilePreview = document.getElementById('profilePreview');
        const currentProfileImage = document.getElementById('currentProfileImage');
        
        // Click to upload
        fileUploadArea.addEventListener('click', function() {
            profileImageInput.click();
        });
        
        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                profileImageInput.files = files;
                handleFileSelect(files[0]);
            }
        });
        
        // File input change
        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                handleFileSelect(this.files[0]);
            }
        });
        
        function handleFileSelect(file) {
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, or GIF).');
                return;
            }
            
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB.');
                return;
            }
            
            // Preview image
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
                currentProfileImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
        
        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        });
    });
    </script>
</body>
</html>