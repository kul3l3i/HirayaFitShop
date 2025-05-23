<?php
session_start();
include 'db_connect.php';

// Initialize variables
$success_message = '';
$error_message = '';

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
    $admin['role'] = 'Administrator';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $new_fullname = trim($_POST['fullname']);
        $new_email = trim($_POST['email']);
        $new_username = trim($_POST['username']);
        
        // Validate inputs
        if (empty($new_fullname) || empty($new_email) || empty($new_username)) {
            $error_message = "All fields are required.";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            // Check if username/email already exists (excluding current admin)
            $check_stmt = $conn->prepare("SELECT admin_id FROM admins WHERE (username = ? OR email = ?) AND admin_id != ?");
            $check_stmt->bind_param("ssi", $new_username, $new_email, $admin_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "Username or email already exists.";
            } else {
                // Update profile
                $update_stmt = $conn->prepare("UPDATE admins SET username = ?, fullname = ?, email = ?, updated_at = CURRENT_TIMESTAMP WHERE admin_id = ?");
                $update_stmt->bind_param("sssi", $new_username, $new_fullname, $new_email, $admin_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    // Update session data
                    $admin['username'] = $new_username;
                    $admin['fullname'] = $new_fullname;
                    $admin['email'] = $new_email;
                } else {
                    $error_message = "Error updating profile.";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
   
if (isset($_POST['submit'])) {
    $admin_id = $_SESSION['admin_id']; // Make sure this session is set upon login
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check for empty fields
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } else {
        // Get current hashed password from DB
        $sql = "SELECT password FROM admins WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->bind_result($stored_password);
        $stmt->fetch();
        $stmt->close();

        // Verify current password
        if (!password_verify($current_password, $stored_password)) {
            $error_message = "Current password is incorrect.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New password and confirm password do not match.";
        } else {
            // Hash the new password and update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE admins SET password = ? WHERE admin_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $admin_id);

            if ($update_stmt->execute()) {
                $success_message = "Password changed successfully.";
            } else {
                $error_message = "Failed to change password. Please try again.";
            }

            $update_stmt->close();
        }
    }
}
    
    if (isset($_POST['upload_profile_image'])) {
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $upload_dir = 'uploads/profiles/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_tmp = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_type = $_FILES['profile_image']['type'];
            
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Only JPEG, PNG, and GIF images are allowed.";
            } elseif ($file_size > $max_size) {
                $error_message = "File size must be less than 5MB.";
            } else {
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old profile image if exists
                    if (!empty($admin['profile_image']) && file_exists($upload_dir . $admin['profile_image'])) {
                        unlink($upload_dir . $admin['profile_image']);
                    }
                    
                    // Update database
                    $img_stmt = $conn->prepare("UPDATE admins SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE admin_id = ?");
                    $img_stmt->bind_param("si", $new_filename, $admin_id);
                    
                    if ($img_stmt->execute()) {
                        $success_message = "Profile image updated successfully!";
                        $admin['profile_image'] = $new_filename;
                    } else {
                        $error_message = "Error updating profile image.";
                    }
                    $img_stmt->close();
                } else {
                    $error_message = "Error uploading file.";
                }
            }
        } else {
            $error_message = "Please select an image file.";
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
    <title>HirayaFit - Admin Settings</title>
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
        
        /* Settings Container */
        .settings-container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .settings-header {
            margin-bottom: 30px;
        }
        
        .settings-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .settings-subtitle {
            font-size: 16px;
            color: var(--grey);
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
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
        
        /* Settings Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        /* Settings Card */
        .settings-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            background: var(--primary);
            color: white;
            padding: 20px 25px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Profile Image Section */
        .profile-image-section {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .current-profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--secondary);
            margin-bottom: 15px;
        }
        
        .profile-image-info {
            font-size: 14px;
            color: var(--grey);
            margin-bottom: 15px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background-color: #fff;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
        }
        
        .form-input[readonly] {
            background-color: #f8f9fa;
            color: var(--grey);
        }
        
        /* File Input */
        .file-input-container {
            position: relative;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: var(--secondary);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        
        .file-input-label:hover {
            background: #005a9e;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a9e;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-full {
            width: 100%;
        }
        
        /* Full Width Card */
        .full-width-card {
            grid-column: 1 / -1;
        }
        
        /* Admin Info Display */
        .admin-info-display {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
        }
        
        .info-value {
            color: var(--grey);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .settings-grid {
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
            
            .settings-container {
                padding: 20px;
            }
            
            .settings-title {
                font-size: 24px;
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
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> System Settings</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="nav-left">
                <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <span class="navbar-title">Admin Settings</span>
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
                <a href="dashboard.php" class="nav-link" title="Back to Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </nav>

        <!-- Settings Content -->
        <div class="settings-container">
            <div class="settings-header">
                <h1 class="settings-title">Admin Settings</h1>
                <p class="settings-subtitle">Manage your account settings and preferences</p>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-grid">
                <!-- Profile Information Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-user"></i>
                        Profile Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-input" 
                                       value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" class="form-input" 
                                       value="<?php echo htmlspecialchars($admin['fullname']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="role">Role</label>
                                <input type="text" id="role" name="role" class="form-input" 
                                       value="<?php echo htmlspecialchars($admin['role']); ?>" readonly>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary btn-full">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Profile Image Card -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-camera"></i>
                        Profile Image
                    </div>
                    <div class="card-body">
                        <div class="profile-image-section">
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Profile Image" class="current-profile-image">
                            <div class="profile-image-info">
                                Current profile image<br>
                                <small>Recommended: 400x400px, Max 5MB</small>
                            </div>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label">Choose New Image</label>
                                <div class="file-input-container">
                                    <input type="file" name="profile_image" class="file-input" 
                                           accept="image/jpeg,image/png,image/gif" required>
                                    <label class="file-input-label">
                                        <i class="fas fa-upload"></i> Select Image
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" name="upload_profile_image" class="btn btn-primary btn-full">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password Card -->
                <div class="settings-card full-width-card">
                    <div class="card-header">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="settings-grid">
                                <div class="form-group">
                                    <label class="form-label" for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" 
                                           class="form-input" required>
                                </div>
                                
                                <div></div> <!-- Empty cell for spacing -->
                                
                                <div class="form-group">
                                    <label class="form-label" for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" 
                                           class="form-input" minlength="6" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           class="form-input" minlength="6" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-danger">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Account Information Card -->
                <div class="settings-card full-width-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        Account Information
                    </div>
                    <div class="card-body">
                        <div class="admin-info-display">
                            <div class="info-row">
                                <span class="info-label">Admin ID:</span>
                                <span class="info-value"><?php echo htmlspecialchars($admin['admin_id']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Username:</span>
                                <span class="info-value"><?php echo htmlspecialchars($admin['username']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Role:</span>
                                <span class="info-value"><?php echo htmlspecialchars($admin['role']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Account Status:</span>
                                <span class="info-value" style="color: var(--success);">
                                    <i class="fas fa-check-circle"></i> Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
        
        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        if (newPassword && confirmPassword) {
            newPassword.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        }
        
        // File input preview
        const fileInput = document.querySelector('input[name="profile_image"]');
        const fileLabel = document.querySelector('.file-input-label');
        const profileImage = document.querySelector('.current-profile-image');
        
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Update label text
                    fileLabel.innerHTML = '<i class="fas fa-check"></i> ' + file.name;
                    
                    // Preview image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImage.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    fileLabel.innerHTML = '<i class="fas fa-upload"></i> Select Image';
                }
            });
        }
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        });
        
        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        field.style.borderColor = 'var(--danger)';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#ddd';
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
        
        // Real-time input validation
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.style.borderColor = 'var(--danger)';
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
            
            input.addEventListener('input', function() {
                if (this.style.borderColor === 'rgb(220, 53, 69)') { // var(--danger) computed
                    this.style.borderColor = '#ddd';
                }
            });
        });
    });
    </script>
</body>
</html>