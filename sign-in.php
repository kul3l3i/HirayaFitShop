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
            // User found
            $user = $result->fetch_assoc();

            if ($user['is_active'] == 0) {
                $error = "Your account is not active. Please verify your email.";
            } else {
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['user_type'] = 'user';

                    // Set cookies if remember me is checked
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $token, $user['id']);
                        $stmt->execute();

                        setcookie("hirayafit_token", $token, time() + (86400 * 30), "/");
                        setcookie("hirayafit_user_id", $user['id'], time() + (86400 * 30), "/");
                    }

                    // Update last login
                    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();

                    header("Location: usershop.php");
                    exit();
                } else {
                    $error = "Invalid password";
                }
            }
        } else {
            // No user found, check admin
            $sql = "SELECT admin_id, username, email, password, fullname, role, is_active FROM admins 
                    WHERE (username = ? OR email = ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username_email, $username_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $admin = $result->fetch_assoc();

                if ($admin['is_active'] == 0) {
                    $error = "This admin account is inactive. Please contact super admin.";
                } else {
                    // ✅ MD5 check for admin passwords
                    if (md5($password) === $admin['password']) {
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_fullname'] = $admin['fullname'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['user_type'] = 'admin';

                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32));
                            $sql = "UPDATE admins SET remember_token = ? WHERE admin_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("si", $token, $admin['admin_id']);
                            $stmt->execute();

                            setcookie("hirayafit_admin_token", $token, time() + (86400 * 30), "/");
                            setcookie("hirayafit_admin_id", $admin['admin_id'], time() + (86400 * 30), "/");
                        }

                        $sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $admin['admin_id']);
                        $stmt->execute();

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
    if (isset($_COOKIE['hirayafit_token']) && isset($_COOKIE['hirayafit_user_id'])) {
        $token = $_COOKIE['hirayafit_token'];
        $user_id = $_COOKIE['hirayafit_user_id'];

        $sql = "SELECT id, username, fullname FROM users 
                WHERE id = ? AND remember_token = ? AND is_active = TRUE";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['user_type'] = 'user';

            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();

            header("Location: usershop.php");
            exit();
        }
    }

    if (isset($_COOKIE['hirayafit_admin_token']) && isset($_COOKIE['hirayafit_admin_id'])) {
        $token = $_COOKIE['hirayafit_admin_token'];
        $admin_id = $_COOKIE['hirayafit_admin_id'];

        $sql = "SELECT admin_id, username, fullname, role FROM admins 
                WHERE admin_id = ? AND remember_token = ? AND is_active = TRUE";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $admin_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();

            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_fullname'] = $admin['fullname'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['user_type'] = 'admin';

            $sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $admin['admin_id']);
            $stmt->execute();

            header("Location: dashboard.php");
            exit();
        }
    }
}

// Already logged in?
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    if ($_SESSION['user_type'] == 'user') {
        header("Location: usershop.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
} else {
    checkRememberedLogin($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - HirayaFit</title>  <link rel="icon" href="images/hf.png">
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

        footer {
    background-color: var(--primary);
    color: white;
    padding: 60px 0 20px;
}

.footer-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
}

.footer-column h3 {
    font-size: 18px;
    margin-bottom: 20px;
    position: relative;
}

.footer-column h3:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -8px;
    width: 40px;
    height: 2px;
    background-color: var(--secondary);
}

.footer-column p {
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 20px;
    opacity: 0.8;
}

.footer-links {
    list-style: none;
}

.footer-links li {
    margin-bottom: 10px;
}

.footer-links a {
    color: #ccc;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: var(--secondary);
}

.footer-social {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.footer-social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background-color: rgba(255,255,255,0.1);
    color: white;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.footer-social a:hover {
    background-color: var(--secondary);
}

.footer-contact {
    margin-top: 20px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 15px;
}

.contact-icon {
    margin-right: 10px;
    color: var(--secondary);
}

.contact-text {
    font-size: 14px;
    opacity: 0.8;
}

.copyright {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    text-align: center;
    font-size: 14px;
    opacity: 0.7;
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

/* Media queries for responsive design */
@media (max-width: 992px) {
    
    
    .footer-container {
        grid-template-columns: repeat(2, 1fr);
    }
    

}

@media (max-width: 768px) {
    .top-bar .container {
        flex-direction: column;
        gap: 5px;
    }
    
    .search-bar {
        max-width: none;
        margin: 10px 0;
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
    

}

@media (max-width: 576px) {
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-container {
        grid-template-columns: 1fr;
    }
    
    .btn-outline {
        margin-left: 0;
        margin-top: 15px;
        display: block;
        max-width: 200px;
        margin: 15px auto 0;
    }
}


        /* Scroll to top button styles */
        #scrollUpBtn {
            display: none; /* Nakatago sa umpisa */
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 99;
            font-size: 18px;
            border: none;
            outline: none;
            background-color: var(--secondary);
            color: var(--light);
            cursor: pointer;
            padding: 15px;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        #scrollUpBtn:hover {
            background-color: var(--primary);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            #scrollUpBtn {
                bottom: 20px;
                right: 20px;
                font-size: 16px;
                padding: 12px;
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
    
    <!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-container">
            <div class="footer-column">
                <h3>About HirayaFit</h3>
                <p>We create premium activewear designed to inspire confidence and support your fitness goals while promoting sustainability.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-pinterest"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
        
                    <li><a href="#">Men's Activewear</a></li>
                    <li><a href="#">Women's Activewear</a></li>
                    <li><a href="#">Footwear</a></li>
                    <li><a href="#">Accessories</a></li>
                    <li><a href="#">Sale Items</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul class="footer-links">
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="faqs.php">FAQs</a></li>
                    <li><a href="shipping.php">Shipping & Returns</a></li>
                    <li><a href="size-guide.php">Size Guide</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h3>Contact Info</h3>
                <div class="footer-contact">
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-text">123 Fitness Street, Active City, AC 12345</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact-text">+1 (555) 123-4567</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-text">support@hirayafit.com</div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-clock"></i></div>
                        <div class="contact-text">Mon-Fri: 9am - 6pm EST</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            &copy; 2025 HirayaFit. All Rights Reserved.
        </div>
    </div>
</footer>

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