<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';


// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user_id'])) {
    header("Location:sign-in.php");
    exit;
}


// Initialize variables
$user = null;
$updateMessage = '';
$updateError = '';

// Prepare and execute query to get user details
$stmt = $conn->prepare("SELECT id, fullname, username, email, address, phone, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// If user exists, store their details
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // User not found in database, redirect to login
    session_destroy();
    header("Location:sign-in.php");
    exit;
}

// Handle profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Get form data
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    
    // Basic validation
    if (empty($fullname) || empty($email)) {
        $updateError = "Name and email are required fields.";
    } else {
        // Check if email already exists for another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $_SESSION['user_id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $updateError = "Email already in use by another account.";
        } else {
            // Handle profile image upload
            $profile_image = $user['profile_image']; // Default to current image
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed_ext = array("jpg", "jpeg", "png", "gif");
                $file_name = $_FILES['profile_image']['name'];
                $file_size = $_FILES['profile_image']['size'];
                $file_tmp = $_FILES['profile_image']['tmp_name'];
                $file_type = $_FILES['profile_image']['type'];
                $tmp = explode('.', $file_name);
                $file_ext = strtolower(end($tmp));
                
                // Check file extension
                if (in_array($file_ext, $allowed_ext)) {
                    // Check file size - 5MB max
                    if ($file_size <= 5242880) {
                        // Create upload directory if it doesn't exist
                        $upload_dir = "uploads/profiles/";
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Generate a unique file name
                        $new_file_name = "profile_" . $_SESSION['user_id'] . "_" . time() . "." . $file_ext;
                        $upload_path = $upload_dir . $new_file_name;
                        
                        // Upload file
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            // If old profile image exists, delete it (except default)
                            if (!empty($user['profile_image']) && file_exists("uploads/profiles/" . $user['profile_image']) && 
                                $user['profile_image'] != "default-avatar.png") {
                                unlink("uploads/profiles/" . $user['profile_image']);
                            }
                            
                            $profile_image = $new_file_name;
                        } else {
                            $updateError = "Failed to upload profile image.";
                        }
                    } else {
                        $updateError = "Profile image is too large. Maximum size is 5MB.";
                    }
                } else {
                    $updateError = "Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.";
                }
            }
            
            // If no errors, update user profile
            if (empty($updateError)) {
                $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, address = ?, phone = ?, profile_image = ? WHERE id = ?");
                $update_stmt->bind_param("sssssi", $fullname, $email, $address, $phone, $profile_image, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    $updateMessage = "Profile updated successfully!";
                    
                    // Update the user variable to reflect changes
                    $user['fullname'] = $fullname;
                    $user['email'] = $email;
                    $user['address'] = $address;
                    $user['phone'] = $phone;
                    $user['profile_image'] = $profile_image;
                } else {
                    $updateError = "Error updating profile: " . $conn->error;
                }
                
                $update_stmt->close();
            }
            
            $check_stmt->close();
        }
    }
}

// Handle password change form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get the user's current password hash
    $password_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $password_stmt->bind_param("i", $_SESSION['user_id']);
    $password_stmt->execute();
    $password_result = $password_stmt->get_result();
    $password_data = $password_result->fetch_assoc();
    
    // Verify current password
    if (password_verify($current_password, $password_data['password'])) {
        // Check if new passwords match
        if ($new_password === $confirm_password) {
            // Validate password strength
            if (strlen($new_password) >= 8) {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password in database
                $update_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pwd_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($update_pwd_stmt->execute()) {
                    $updateMessage = "Password changed successfully!";
                } else {
                    $updateError = "Error changing password: " . $conn->error;
                }
                
                $update_pwd_stmt->close();
            } else {
                $updateError = "Password must be at least 8 characters long.";
            }
        } else {
            $updateError = "New passwords do not match.";
        }
    } else {
        $updateError = "Current password is incorrect.";
    }
    
    $password_stmt->close();
}

// Close statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - HirayaFit</title>    <link rel="icon" href="images/hf.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #111111;
            --secondary: #0071c5;
            --accent: #e5e5e5;
            --light: #ffffff;
            --dark: #111111;
            --grey: #767676;
            --light-grey: #f5f5f5;
            --border-color: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-grey);
        }

        .top-bar {
            background-color: var(--primary);
            color: white;
            padding: 8px 0;
            text-align: center;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .top-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .top-bar a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .header {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: var(--secondary);
        }

        .search-bar {
            flex-grow: 1;
            max-width: 500px;
            margin: 0 30px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #e5e5e5;
            border-radius: 25px;
            background-color: #f5f5f5;
            font-size: 14px;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--grey);
        }

        .search-bar button {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--grey);
        }

        .nav-icons {
            display: flex;
            align-items: center;
        }

        .nav-icons a {
            margin-left: 20px;
            font-size: 18px;
            color: var(--dark);
            text-decoration: none;
            position: relative;
        }

        .cart-count {
            position: absolute;
            top: -6px;
            right: -6px;
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

        /* Enhanced Account Dropdown Styling */
        .account-dropdown {
            position: relative;
            display: inline-block;
        }

        .account-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 280px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            z-index: 1;
            border-radius: 8px;
            margin-top: 10px;
            overflow: hidden;
        }

        .account-dropdown-content:before {
            content: '';
            position: absolute;
            top: -8px;
            right: 10px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid white;
        }

        /* Enhanced User Profile Header */
        .user-profile-header {
            display: flex;
            align-items: center;
            padding: 16px;
            background: linear-gradient(to right, #f7f7f7, #eaeaea);
            border-bottom: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-right: 15px;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mini-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info {
            flex: 1;
        }

        .user-info h4 {
            margin: 0;
            font-size: 16px;
            color: var(--dark);
            font-weight: 600;
        }

        .user-info .username {
            display: block;
            font-size: 12px;
            color: var(--grey);
            margin-top: 2px;
        }

        .account-links {
            padding: 8px 0;
        }

        .account-links a {
            color: var(--dark);
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 400;
            margin: 0;
            transition: all 0.2s ease;
        }

        .account-links a i {
            margin-right: 10px;
            color: var(--secondary);
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .account-links a:hover {
            background-color: #f8f9fa;
            color: var(--secondary);
        }

        .account-dropdown.active .account-dropdown-content {
            display: block;
        }

        /* Main Navigation Styles */
        .main-nav {
            display: flex;
            justify-content: center;
            background-color: var(--light);
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }

        .main-nav a {
            padding: 15px 20px;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            position: relative;
        }

        .main-nav a:hover, .main-nav a.active {
            color: var(--secondary);
        }

        /* Hover underline effect */
        .main-nav a:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--secondary);
            left: 50%;
            bottom: 10px;
            transition: all 0.2s ease;
            transform: translateX(-50%);
        }

        .main-nav a:hover:after, .main-nav a.active:after {
            width: 60%;
        }
        
        /* Mobile menu button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 22px;
            cursor: pointer;
        }

        /* Sign out button styling */
        .sign-out-btn {
            border-top: 1px solid var(--border-color);
            margin-top: 5px;
        }

        .sign-out-btn a {
            color: #e74c3c !important;
        }

        .sign-out-btn a:hover {
            background-color: #fff5f5;
        }

        /* Profile Page Specific Styles */
        .profile-section {
            padding: 60px 0;
            background-color: var(--light-grey);
        }

        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .profile-header h1 {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .profile-header p {
            color: var(--grey);
            font-size: 16px;
        }

        .profile-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
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
            padding: 30px;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 30px;
            align-items: center;
        }

        .profile-avatar {
            width: 100%;
            padding-bottom: 100%;
            border-radius: 50%;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 4px solid white;
        }

        .profile-avatar img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-details {
            padding-left: 20px;
        }

        .profile-details h2 {
            margin-bottom: 15px;
            color: var(--primary);
            font-size: 24px;
        }

        .profile-details p {
            color: var(--grey);
            margin-bottom: 5px;
            font-size: 16px;
        }

        .profile-details p span {
            color: var(--primary);
            font-weight: 500;
        }

        .profile-form {
            margin-top: 20px;
        }

        .form-row {
            margin-bottom: 20px;
        }

        .form-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
        }

        .form-row input[type="text"],
        .form-row input[type="email"],
        .form-row input[type="password"],
        .form-row textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-row input:focus,
        .form-row textarea:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(0,113,197,0.1);
        }

        .form-row .profile-image-upload {
            display: flex;
            flex-direction: column;
        }

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 15px;
            overflow: hidden;
            border: 3px solid #f0f0f0;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .custom-file-upload {
            display: inline-block;
            padding: 10px 15px;
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            width: fit-content;
            transition: all 0.3s ease;
        }

        .custom-file-upload:hover {
            background-color: #e9e9e9;
        }

        .custom-file-upload i {
            margin-right: 8px;
        }

        .btn {
            display: inline-block;
            padding: 12px 22px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #005da3;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,113,197,0.3);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }

        .btn-outline:hover {
            background-color: var(--secondary);
            color: white;
        }

        .alert {
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .profile-avatar {
                width: 150px;
                padding-bottom: 150px;
                margin: 0 auto;
            }
            
            .profile-details {
                padding-left: 0;
                text-align: center;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div>FREE SHIPPING ON ORDERS OVER ₱4,000!</div>
            <div>
                <a href="#">Help</a>
                <a href="#">Order Tracker</a>
                <a href="#">Welcome, <?php echo $user['username']; ?></a>
            </div>
        </div>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="navbar">
            <a href="usershop.php" class="logo">Hiraya<span>Fit</span></a>
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search products...">
                    <button onclick="searchProducts()"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="nav-icons">
                    <!-- Enhanced Account dropdown for logged-in users -->
                    <div class="account-dropdown" id="accountDropdown">
                        <a href="#" id="accountBtn">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profile" class="mini-avatar">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                        </a>
                        <div class="account-dropdown-content" id="accountDropdownContent">
                            <div class="user-profile-header">
                                <div class="user-avatar">
                                    <img src="<?php echo !empty($user['profile_image']) ? 'uploads/profiles/'.$user['profile_image'] : 'assets/images/default-avatar.png'; ?>" alt="Profile">
                                </div>
                                <div class="user-info">
                                    <h4><?php echo $user['fullname']; ?></h4>
                                    <span class="username">@<?php echo $user['username']; ?></span>
                                </div>
                            </div>
                            <div class="account-links">
                                <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                                <a href="orders.php"><i class="fas fa-box"></i> My Orders</a>
                             
                              
                                <div class="sign-out-btn">
                                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="messagesUser.php"><i class="fas fa-envelope"></i></a>
                    <a href="cart.php" id="cartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">
                            <?php 
                            // Display cart count if available
                            echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : '0'; 
                            ?>
                        </span>
                    </a>
                </div>
                
                <button class="menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Main Navigation -->
        <nav class="main-nav" id="mainNav">
        <a   href="usershop.php" class="active">HOME</a>  
        
    </nav>
    
    </header>

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your account information and settings</p>
            </div>
            
            <?php if (!empty($updateMessage)): ?>
            <div class="alert alert-success">
                <?php echo $updateMessage; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($updateError)): ?>
            <div class="alert alert-danger">
                <?php echo $updateError; ?>
            </div>
            <?php endif; ?>
            
            <!-- Profile Information Card -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-user"></i> Personal Information
                </div>
                <div class="card-body">
                    <div class="profile-info-grid">
                        <div class="profile-avatar">
                            <img src="<?php echo !empty($user['profile_image']) ? 'uploads/profiles/'.$user['profile_image'] : 'assets/images/default-avatar.png'; ?>" alt="Profile Picture">
                        </div>
                        <div class="profile-details">
                            <h2><?php echo $user['fullname']; ?></h2>
                            <p><span>Username:</span> @<?php echo $user['username']; ?></p>
                            <p><span>Email:</span> <?php echo $user['email']; ?></p>
                            <p><span>Address:</span> <?php echo !empty($user['address']) ? $user['address'] : 'Not provided'; ?></p>
                            <p><span>Phone:</span> <?php echo !empty($user['phone']) ? $user['phone'] : 'Not provided'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Card -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-edit"></i> Edit Profile
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form">
                        <div class="form-row">
                            <label for="profile_image">Profile Picture</label>
                            <div class="profile-image-upload">
                                <div class="image-preview">
                                    <img src="<?php echo !empty($user['profile_image']) ? 'uploads/profiles/'.$user['profile_image'] : 'assets/images/default-avatar.png'; ?>" id="imagePreview" alt="Profile Preview">
                                </div>
                                <label for="profile_image" class="custom-file-upload">
                                    <i class="fas fa-camera"></i> Choose Image
                                </label>
                                <input type="file" id="profile_image" name="profile_image" style="display: none;" onchange="previewImage(this)">
                                <small style="color: var(--grey); margin-top: 8px;">Supported formats: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                                </div>
                        
                        <div class="form-row">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo $user['fullname']; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo $user['address']; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" name="update_profile" class="btn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Card -->
            <div class="profile-card">
                <div class="card-header">
                    <i class="fas fa-lock"></i> Change Password
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST" class="profile-form">
                        <div class="form-row">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" name="change_password" class="btn">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer would go here -->
    
    <script>
        // Function to preview image before upload
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Account dropdown toggle
        document.getElementById('accountBtn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('accountDropdown').classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('accountDropdown');
            if (!e.target.closest('#accountDropdown')) {
                if (dropdown.classList.contains('active')) {
                    dropdown.classList.remove('active');
                }
            }
        });
        
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.getElementById('mainNav').classList.toggle('active');
        });
        
        // Function to search products
        function searchProducts() {
            const searchQuery = document.getElementById('searchInput').value.trim();
            if (searchQuery !== '') {
                window.location.href = 'usershop.php.php?search=' + encodeURIComponent(searchQuery);
            }
        }
        
        // Search on Enter key press
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    </script>

    <script>
    // Function to dynamically generate category navigation based on XML data
function generateCategoryNav() {
    // Check if products are already loaded
    if (products.length === 0) {
        // If not, set a small timeout to wait for products to load
        setTimeout(generateCategoryNav, 500);
        return;
    }
    
    // Get all unique categories from products
    const categories = [...new Set(products.map(product => product.category))];
    
    // Get the categories container
    const categoriesContainer = document.querySelector('categories');
    if (categoriesContainer) {
        // Clear existing categories
        categoriesContainer.innerHTML = '';
        
        // Add each category to the container
        categories.forEach(category => {
            const categoryElement = document.createElement('category');
            categoryElement.textContent = category;
            categoriesContainer.appendChild(categoryElement);
        });
    }
    
    // Update the main navigation to include categories
    updateMainNavigation(categories);
}

// Function to update main navigation with categories
function updateMainNavigation(categories) {
    const mainNav = document.getElementById('mainNav');
    if (!mainNav) return;
    
    // Get all existing links that are not category links
    const staticLinks = Array.from(mainNav.querySelectorAll('a:not([data-category])'));
    
    // Clear the navigation
    mainNav.innerHTML = '';
    
    // Add back the static links
    staticLinks.forEach(link => {
        mainNav.appendChild(link.cloneNode(true));
    });
    
    // Add category links
    categories.forEach(category => {
        const categoryLink = document.createElement('a');
        categoryLink.href = 'javascript:void(0);';
        categoryLink.setAttribute('data-category', category);
        categoryLink.textContent = category.toUpperCase();
        categoryLink.addEventListener('click', function() {
            filterProductsByCategory(category);
        });
        mainNav.appendChild(categoryLink);
    });
}

// Function to filter products by category
function filterProductsByCategory(category) {
    // Filter products by the selected category
    const filteredProducts = products.filter(product => 
        product.category.toLowerCase() === category.toLowerCase()
    );
    
    // Display the filtered products
    displayCategoryResults(filteredProducts, category);
    
    // Scroll to category results
    const resultsSection = document.getElementById('categoryResults');
    if (resultsSection) {
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Display category filtered results
function displayCategoryResults(results, categoryName) {
    let resultsContainer = document.getElementById('categoryResults');
    
    // If results container doesn't exist, create it at the bottom of the page
    if (!resultsContainer) {
        // Create results container
        resultsContainer = document.createElement('section');
        resultsContainer.id = 'categoryResults';
        resultsContainer.className = 'category-results-section';
        
        // Add container to the bottom of the page, before the footer if it exists
        const footer = document.querySelector('footer');
        if (footer) {
            document.body.insertBefore(resultsContainer, footer);
        } else {
            document.body.appendChild(resultsContainer);
        }
    }
    
    // Clear previous results
    resultsContainer.innerHTML = '';
    
    // Create container for results content
    const resultsContent = document.createElement('div');
    resultsContent.className = 'container';
    resultsContainer.appendChild(resultsContent);
    
    // Add results heading
    const heading = document.createElement('h2');
    heading.textContent = categoryName;
    resultsContent.appendChild(heading);
    
    // If no results found
    if (results.length === 0) {
        const noResults = document.createElement('p');
        noResults.className = 'no-results';
        noResults.textContent = 'No products found in this category.';
        resultsContent.appendChild(noResults);
        resultsContainer.style.display = 'block';
        return;
    }
    
    // Create results grid
    const resultsGrid = document.createElement('div');
    resultsGrid.className = 'results-grid';
    resultsContent.appendChild(resultsGrid);
    
    // Add each product to the grid
    results.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        
        // Format price with commas for thousands
        const formattedPrice = parseFloat(product.price).toLocaleString();
        
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                ${product.on_sale === 'true' ? '<span class="sale-badge">Sale</span>' : ''}
                <div class="product-actions">
                    <button class="quick-view" data-id="${product.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="add-to-wishlist" data-id="${product.id}">
                        <i class="far fa-heart"></i>
                    </button>
                    <button class="add-to-cart-icon" data-id="${product.id}">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>
            <div class="product-info">
                <h3>${product.name}</h3>
                <p class="price">₱${formattedPrice}</p>
                <p class="category">${product.category}</p>
                <div class="product-rating">
                    <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                    <span class="rating-text">(${product.rating})</span>
                </div>
            
                <button class="add-to-cart" data-id="${product.id}">Add to Cart</button>
            </div>
        `;
        resultsGrid.appendChild(productCard);
    });
    
    resultsContainer.style.display = 'block';
    
    // Add event listeners to the buttons
    addCategoryResultsEventListeners();
}

// Add event listeners to category results buttons
function addCategoryResultsEventListeners() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('#categoryResults .add-to-cart, #categoryResults .add-to-cart-icon');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
    
    // Quick view buttons
    const quickViewButtons = document.querySelectorAll('#categoryResults .quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // View details buttons
    const viewDetailsButtons = document.querySelectorAll('#categoryResults .view-details');
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetails(productId);
        });
    });
    
    // Add to wishlist buttons
    const wishlistButtons = document.querySelectorAll('#categoryResults .add-to-wishlist');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToWishlist(productId);
        });
    });
}

// Updated function to show product details in a modal
function showQuickView(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Create a modal container if it doesn't exist
    let modalOverlay = document.getElementById('productDetailsModal');
    if (!modalOverlay) {
        modalOverlay = document.createElement('div');
        modalOverlay.id = 'productDetailsModal';
        modalOverlay.className = 'product-modal-overlay';
        document.body.appendChild(modalOverlay);
    }
    
    // Format price with commas for thousands
    const formattedPrice = parseFloat(product.price).toLocaleString();
    
    // Populate modal with product information
    modalOverlay.innerHTML = `
        <div class="product-modal-content">
            <button class="modal-close">&times;</button>
            
            <div class="modal-product-grid">
                <!-- Product Image Column -->
                <div class="modal-product-image">
                    <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                </div>
                
                <!-- Product Details Column -->
                <div class="modal-product-details">
                    <h2 class="modal-product-title">${product.name}</h2>
                    
                    <div class="modal-product-price">₱${formattedPrice}</div>
                    
                    <!-- Product Rating -->
                    <div class="modal-product-rating">
                        <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                        <span class="rating-text">(${product.rating} - ${product.review_count || '0'} reviews)</span>
                    </div>
                    
                    <!-- Product Description -->
                    <div class="modal-product-description">
                        <p>${product.description || 'No description available'}</p>
                    </div>
                    
                    <!-- Size Options -->
                    <div class="modal-product-size">
                        <label>Size:</label>
                        <div class="size-options">
                            ${product.sizes.map(size => `<button class="size-btn" data-size="${size}">${size}</button>`).join('')}
                        </div>
                    </div>
                    
                    <!-- Color Options -->
                    <div class="modal-product-color">
                        <label>Color:</label>
                        <div class="color-options">
                            ${product.colors.map(color => {
                                const colorCode = getColorCode(color);
                                return `<button class="color-btn" style="background-color: ${colorCode};" data-color="${color}"></button>`;
                            }).join('')}
                        </div>
                    </div>
                    
                    <!-- Quantity Selector -->
                    <div class="modal-product-quantity">
                        <label>Quantity:</label>
                        <div class="quantity-control">
                            <button class="quantity-btn minus">−</button>
                            <input type="number" class="quantity-input" value="1" min="1" max="${product.stock}">
                            <button class="quantity-btn plus">+</button>
                        </div>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <button class="modal-add-to-cart" data-id="${product.id}">ADD TO CART</button>
                </div>
            </div>
        </div>
    `;
    
    // Show the modal
    modalOverlay.style.display = 'flex';
    document.body.classList.add('modal-open');
    
    // Add event listeners for the modal
    addModalEventListeners(modalOverlay, product);
}

// Make showProductDetails use the same implementation as showQuickView
function showProductDetails(productId) {
    showQuickView(productId);
}

// Helper function to get color code from color name
function getColorCode(colorName) {
    const colorMap = {
        'Black': '#000000',
        'Navy': '#000080',
        'Blue': '#0000FF',
        'Red': '#FF0000',
        'White': '#FFFFFF',
        'Grey': '#808080',
        'Green': '#008000',
        'Yellow': '#FFFF00',
        'Purple': '#800080',
        'Orange': '#FFA500',
        'Pink': '#FFC0CB'
    };
    
    return colorMap[colorName] || colorName.toLowerCase();
}

// Add event listeners for modal functionality
function addModalEventListeners(modal, product) {
    // Close button
    const closeBtn = modal.querySelector('.modal-close');
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    });
    
    // Close on click outside the modal content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
    
    // Size buttons
    const sizeButtons = modal.querySelectorAll('.size-btn');
    sizeButtons.forEach(button => {
        button.addEventListener('click', () => {
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
    
    // Color buttons
    const colorButtons = modal.querySelectorAll('.color-btn');
    colorButtons.forEach(button => {
        button.addEventListener('click', () => {
            colorButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
    
    // Quantity buttons
    const minusBtn = modal.querySelector('.minus');
    const plusBtn = modal.querySelector('.plus');
    const quantityInput = modal.querySelector('.quantity-input');
    
    minusBtn.addEventListener('click', () => {
        if (parseInt(quantityInput.value) > 1) {
            quantityInput.value = parseInt(quantityInput.value) - 1;
        }
    });
    
    plusBtn.addEventListener('click', () => {
        if (parseInt(quantityInput.value) < parseInt(product.stock)) {
            quantityInput.value = parseInt(quantityInput.value) + 1;
        }
    });
    
    // Add to cart button
    const addToCartBtn = modal.querySelector('.modal-add-to-cart');
    addToCartBtn.addEventListener('click', () => {
        const quantity = parseInt(quantityInput.value);
        const selectedSize = modal.querySelector('.size-btn.active')?.getAttribute('data-size') || product.sizes[0];
        const selectedColor = modal.querySelector('.color-btn.active')?.getAttribute('data-color') || product.colors[0];
        
        addToCartWithOptions(product.id, quantity, selectedSize, selectedColor);
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    });
}

// Function to add to cart with specified options
function addToCartWithOptions(productId, quantity, size, color) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Get current cart from local storage or initialize empty cart
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Create unique identifier for this product with selected options
    const itemId = `${productId}-${size}-${color}`;
    
    // Check if product with same options already in cart
    const existingItemIndex = cart.findIndex(item => 
        item.id === productId && 
        item.size === size && 
        item.color === color
    );
    
    if (existingItemIndex >= 0) {
        cart[existingItemIndex].quantity += quantity;
    } else {
        cart.push({
            id: productId,
            itemId: itemId,
            name: product.name,
            price: parseFloat(product.price),
            image: product.image,
            quantity: quantity,
            size: size,
            color: color
        });
    }
    
    // Save updated cart to local storage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update cart count in the UI
    updateCartCount();
    
    // Show confirmation message
    const confirmMsg = document.createElement('div');
    confirmMsg.className = 'add-to-cart-confirmation';
    confirmMsg.innerHTML = `
        <div class="confirmation-content">
            <i class="fas fa-check-circle"></i>
            <p>${product.name} added to your cart!</p>
        </div>
    `;
    document.body.appendChild(confirmMsg);
    
    // Remove confirmation after 3 seconds
    setTimeout(() => {
        confirmMsg.classList.add('fade-out');
        setTimeout(() => {
            document.body.removeChild(confirmMsg);
        }, 500);
    }, 2500);
}

// Add necessary CSS for modal
function addProductDetailsModalStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        /* Modal Overlay */
        .product-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        /* Modal Content */
        .product-modal-content {
            background-color: #fff;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        /* Close Button */
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            z-index: 10;
        }
        
        /* Product Grid Layout */
        .modal-product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Product Image */
        .modal-product-image {
            padding: 20px;
        }
        
        .modal-product-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        /* Product Details */
        .modal-product-details {
            padding: 30px 30px 30px 0;
        }
        
        /* Product Title */
        .modal-product-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        /* Product Price */
        .modal-product-price {
            font-size: 24px;
            color: #e73c17;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        /* Product Rating */
        .modal-product-rating {
            margin-bottom: 15px;
        }
        
        .modal-product-rating .stars {
            color: #ffaa00;
        }
        
        .modal-product-rating .rating-text {
            color: #777;
            font-size: 14px;
        }
        
        /* Product Description */
        .modal-product-description {
            margin-bottom: 20px;
            color: #555;
        }
        
        /* Size and Color Sections */
        .modal-product-size,
        .modal-product-color {
            margin-bottom: 20px;
        }
        
        .modal-product-size label,
        .modal-product-color label,
        .modal-product-quantity label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        /* Size Options */
        .size-options {
            display: flex;
            gap: 10px;
        }
        
        .size-btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .size-btn.active {
            border-color: #333;
            background-color: #333;
            color: white;
        }
        
        /* Color Options */
        .color-options {
            display: flex;
            gap: 10px;
        }
        
        .color-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-btn.active {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #333;
        }
        
        /* Quantity Section */
        .modal-product-quantity {
            margin-bottom: 25px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            height: 36px;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
            text-align: center;
            font-size: 16px;
        }
        
        /* Add to Cart Button */
        .modal-add-to-cart {
            width: 100%;
            padding: 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .modal-add-to-cart:hover {
            background-color: #0b7dda;
        }
        
        /* Add to Cart Confirmation */
        .add-to-cart-confirmation {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1100;
            transition: opacity 0.5s;
        }
        
        .confirmation-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .confirmation-content i {
            font-size: 24px;
        }
        
        .fade-out {
            opacity: 0;
        }
        
        /* Modal Open Body Style */
        body.modal-open {
            overflow: hidden;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .modal-product-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-product-details {
                padding: 20px;
            }
        }
    `;
    document.head.appendChild(styleElement);
}

// Add event listeners to product cards to view details
function addViewDetailsEventListeners() {
    // For category results
    const categoryResults = document.querySelectorAll('#categoryResults .product-card');
    categoryResults.forEach(card => {
        // Make the whole card clickable except buttons
        card.addEventListener('click', function(e) {
            // If click is on a button or inside a button, don't trigger details view
            if (e.target.closest('button')) {
                return;
            }
            
            const productId = card.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // For featured products
    const featuredProductCards = document.querySelectorAll('#featured .product-card');
    featuredProductCards.forEach(card => {
        // Make the whole card clickable except buttons
        card.addEventListener('click', function(e) {
            // If click is on a button or inside a button, don't trigger details view
            if (e.target.closest('button')) {
                return;
            }
            
            const addToCartBtn = card.querySelector('.add-to-cart');
            const productId = addToCartBtn.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // For search results - use mutation observer to catch dynamically added results
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                const searchResultCards = document.querySelectorAll('#searchResults .product-card');
                searchResultCards.forEach(card => {
                    // Make the whole card clickable except buttons
                    card.addEventListener('click', function(e) {
                        // If click is on a button or inside a button, don't trigger details view
                        if (e.target.closest('button')) {
                            return;
                        }
                        
                        const addToCartBtn = card.querySelector('.add-to-cart');
                        const productId = addToCartBtn.getAttribute('data-id');
                        showQuickView(productId);
                    });
                });
            }
        });
    });
    
    // Start observing the document body for search results
    observer.observe(document.body, { childList: true, subtree: true });
}

// Update initialization function
function initializeProductModals() {
    // Add the modal styles
    addProductDetailsModalStyles();
    
    // Add click event listeners for the Quick View buttons
    const quickViewButtons = document.querySelectorAll('#categoryResults .quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // Add click event listeners for the View Details buttons
    const viewDetailsButtons = document.querySelectorAll('#categoryResults .view-details');
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetails(productId);
        });
    });
    
    // Make cards clickable for details view
    addViewDetailsEventListeners();
}

// Update document ready function to include our new initialization
document.addEventListener('DOMContentLoaded', function() {
    loadProductData();
    updateCartCount();
    
    // Add event listener for search button
    document.querySelector('.search-bar button').addEventListener('click', searchProducts);
    
    // Add event listener for Enter key in search input
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    // Load featured products after a slight delay to ensure XML is loaded
    setTimeout(() => {
        loadFeaturedProducts();
        // Initialize product modals after products are loaded
        setTimeout(initializeProductModals, 300);
    }, 500);
    
    // Generate category navigation after the products have loaded
    setTimeout(generateCategoryNav, 700);
});
</script>


</body>
</html>