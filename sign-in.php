<?php
// Start session
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = trim($_POST['username_email']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // Validate inputs
    if (empty($username_email) || empty($password)) {
        $error = "Both username/email and password are required";
    } else {
        // Check if it's a user first
        $sql = "SELECT id, username, email, password, fullname, is_active FROM users 
                WHERE (username = ? OR email = ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username_email, $username_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            // User found - process user login as before
            $user = $result->fetch_assoc();
            
            // Check if user is active
            if ($user['is_active'] == 0) {
                $error = "Your account is not active. Please verify your email.";
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['user_type'] = 'user';
                    
                    // Set cookies if remember me is checked
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32)); // Generate a secure token
                        
                        // Store token in database
                        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $token, $user['id']);
                        $stmt->execute();
                        
                        // Set cookies - expire after 30 days
                        setcookie("hirayafit_token", $token, time() + (86400 * 30), "/"); // 86400 = 1 day
                        setcookie("hirayafit_user_id", $user['id'], time() + (86400 * 30), "/");
                    }
                    
                    // Update last login time
                    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    // Redirect to user dashboard
                    header("Location: userusershop.php");
                    exit();
                } else {
                    $error = "Invalid password";
                }
            }
        } else {
            // No user found, check if it's an admin
            $sql = "SELECT admin_id, username, password, fullname, role, is_active FROM admins 
                    WHERE (username = ? OR email = ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                // Admin found
                $admin = $result->fetch_assoc();
                
                // Check if admin is active
                if ($admin['is_active'] == 0) {
                    $error = "This admin account is inactive. Please contact super admin.";
                } else {
                    // For admin with username 'admin', special case handling
                    if ($admin['username'] == 'admin' && $password == 'admin') {
                        // Direct password match for admin user
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_fullname'] = $admin['fullname'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['user_type'] = 'admin';
                        
                        // Set cookies if remember me is checked
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32)); // Generate a secure token
                            
                            // Store token in database
                            $sql = "UPDATE admins SET remember_token = ? WHERE admin_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $token, $admin['admin_id']);
                            $stmt->execute();
                            
                            // Set cookies - expire after 30 days
                            setcookie("hirayafit_admin_token", $token, time() + (86400 * 30), "/");
                            setcookie("hirayafit_admin_id", $admin['admin_id'], time() + (86400 * 30), "/");
                        }
                        
                        // Update last login time
                        $sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $admin['admin_id']);
                        $stmt->execute();
                        
                        // Redirect to admin dashboard
                        header("Location: dashboard.php");
                        exit();
                    }
                    // Regular password verification for other admin accounts
                    else if (password_verify($password, $admin['password'])) {
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_fullname'] = $admin['fullname'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['user_type'] = 'admin';
                        
                        // Set cookies if remember me is checked
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32)); // Generate a secure token
                            
                            // Store token in database
                            $sql = "UPDATE admins SET remember_token = ? WHERE admin_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $token, $admin['admin_id']);
                            $stmt->execute();
                            
                            // Set cookies - expire after 30 days
                            setcookie("hirayafit_admin_token", $token, time() + (86400 * 30), "/");
                            setcookie("hirayafit_admin_id", $admin['admin_id'], time() + (86400 * 30), "/");
                        }
                        
                        // Update last login time
                        $sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $admin['admin_id']);
                        $stmt->execute();
                        
                        // Redirect to admin dashboard
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid password";
                    }
                }
            } else {
                $error = "No account found with this username or email";
            }
        }
    }
}

// Check for remembered login
function checkRememberedLogin($conn) {
    // Check for user cookie
    if (isset($_COOKIE['hirayafit_token']) && isset($_COOKIE['hirayafit_user_id'])) {
        $token = $_COOKIE['hirayafit_token'];
        $user_id = $_COOKIE['hirayafit_user_id'];
        
        // Verify token in database
        $sql = "SELECT id, username, fullname FROM users 
                WHERE id = ? AND remember_token = ? AND is_active = TRUE";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['user_type'] = 'user';
            
            // Update last login time
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            // Redirect to user dashboard
            header("Location: userusershop.php");
            exit();
        }
    }
    
    // Check for admin cookie
    if (isset($_COOKIE['hirayafit_admin_token']) && isset($_COOKIE['hirayafit_admin_id'])) {
        $token = $_COOKIE['hirayafit_admin_token'];
        $admin_id = $_COOKIE['hirayafit_admin_id'];
        
        // Verify token in database
        $sql = "SELECT admin_id, username, fullname, role FROM admins 
                WHERE admin_id = ? AND remember_token = ? AND is_active = TRUE";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $admin_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_fullname'] = $admin['fullname'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['user_type'] = 'admin';
            
            // Update last login time
            $sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $admin['admin_id']);
            $stmt->execute();
            
            // Redirect to admin dashboard
            header("Location: dashboard.php");
            exit();
        }
    }
}

// Check if user is already logged in via session or cookie
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    // Already logged in, redirect to appropriate page
    if ($_SESSION['user_type'] == 'user') {
        header("Location: userusershop.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
} else {
    // Check for remembered login
    checkRememberedLogin($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - HirayaFit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #111111;
            --secondary: #0071c5;
            --accent: #e5e5e5;
            --light: #ffffff;
            --dark: #111111;
            --grey: #767676;
            --error: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light);
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
        
        /* Account dropdown styling */
        .account-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .account-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            z-index: 1;
            border-radius: 4px;
            margin-top: 10px;
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
        
        .account-dropdown-content a {
            color: var(--dark);
            padding: 12px 20px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            font-weight: 400;
            margin: 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .account-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        .account-dropdown-content a:hover {
            background-color: #f8f9fa;
            color: var(--secondary);
        }
        
        .account-dropdown-content h3 {
            padding: 12px 20px;
            margin: 0;
            font-size: 14px;
            color: var(--grey);
            background-color: #f8f9fa;
            border-bottom: 1px solid #f0f0f0;
            border-radius: 4px 4px 0 0;
            font-weight: 500;
        }
        
        .account-dropdown.active .account-dropdown-content {
            display: block;
        }
        
        /* Updated Navigation Styles */
        .main-nav {
            display: flex;
            justify-content: center;
            background-color: var(--light);
            border-bottom: 1px solid #f0f0f0;
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
        
        /* Menu toggle button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 22px;
            cursor: pointer;
        }
        
        /* Sign In Page Specific Styles */
        .page-title {
            text-align: center;
            padding: 40px 0 20px;
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .signin-container {
            max-width: 500px;
            margin: 0 auto 60px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: var(--secondary);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #005fa8;
            text-decoration: underline;
        }
        
        .btn-signin {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn-signin:hover {
            background-color: #005fa8;
        }
        
        .social-signin {
            margin-top: 30px;
            text-align: center;
        }
        
        .social-signin p {
            position: relative;
            margin-bottom: 20px;
            color: var(--grey);
            font-size: 14px;
        }
        
        .social-signin p:before,
        .social-signin p:after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background-color: #ddd;
        }
        
        .social-signin p:before {
            left: 0;
        }
        
        .social-signin p:after {
            right: 0;
        }
        
        .social-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 1px solid #ddd;
            color: var(--dark);
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            background-color: #f8f9fa;
            border-color: #ccc;
            color: var(--secondary);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            font-size: 15px;
            color: var(--dark);
        }
        
        .register-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            text-decoration: underline;
            color: #005fa8;
        }
        
        /* Remember me checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
        }
        
        /* Error message */
        .error-message {
            color: var(--error);
            background-color: rgba(220, 53, 69, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        /* Media queries */
        @media (max-width: 768px) {
            .top-bar .container {
                flex-direction: column;
                gap: 5px;
            }
            
            .navbar {
                flex-wrap: wrap;
            }
            
            .menu-toggle {
                display: block;
                order: 1;
            }
            
            .logo {
                order: 2;
                margin: 0 auto;
            }
            
            .nav-icons {
                order: 3;
            }
            
            .main-nav {
                display: none;
                flex-direction: column;
                align-items: center;
            }
            
            .main-nav.active {
                display: flex;
            }
            
            .signin-container {
                padding: 20px;
                margin: 0 15px 40px;
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
                <a href="#">Become a Member</a>
            </div>
        </div>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="navbar">
                <a href="index.php" class="logo">Hiraya<span>Fit</span></a>
                
                <div class="nav-icons">
                    <div class="account-dropdown" id="accountDropdown">
                        <a href="#" id="accountBtn"><i class="fas fa-user"></i></a>
                        <div class="account-dropdown-content" id="accountDropdownContent">
                            <h3>My Account</h3>
                            <a href="sign-in.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                            <a href="sign-up.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                            <a href="#orders"><i class="fas fa-box"></i> Track Orders</a>
           
                        </div>
                    </div>
                   <!-- <a href="#"><i class="fas fa-heart"></i></a>-->
                    <a href="#" id="cartBtn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                </div>
                
                <button class="menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Simplified Navigation -->
    <nav class="main-nav" id="mainNav">
        <a href="index.php" class="active">HOME</a>  
        <a href="about.php">ABOUT</a>
        <a href="contact.php">CONTACT</a>
    </nav>
    <!-- Sign In Section -->
    <section>
        <h1 class="page-title">Sign In</h1>
        
        <div class="signin-container">
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form id="signinForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="username_email">Username or Email Address</label>
                    <input type="text" id="username_email" name="username_email" class="form-control" 
                           placeholder="Enter your username or email" required
                           value="<?php echo htmlspecialchars($username_email); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember me</label>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-signin">SIGN IN</button>
                
                <div class="social-signin">
                    <p>Or sign in with</p>
                    <div class="social-buttons">
                        <a href="#" class="social-btn"><i class="fab fa-google"></i></a>
                        <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-btn"><i class="fab fa-apple"></i></a>
                    </div>
                </div>
                
                <div class="register-link">
                    Don't have an account? <a href="sign-up.php">Create one now</a>
                </div>
            </form>
        </div>
    </section>
    
    <!-- Scripts -->
    <script>
        // JavaScript for mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            const accountBtn = document.getElementById('accountBtn');
            const accountDropdown = document.getElementById('accountDropdown');
            
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
            });
            
            accountBtn.addEventListener('click', function(e) {
                e.preventDefault();
                accountDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!accountBtn.contains(e.target) && !accountDropdown.contains(e.target)) {
                    accountDropdown.classList.remove('active');
                }
            });
        });
    </script>



</body>
</html>