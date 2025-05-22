<?php
session_start();


// Database connection
$db_host = 'localhost';
$db_user = 'u801377270_hiraya_2025'; // Change to your database username
$db_pass = 'Hiraya_2025'; // Change to your database password
$db_name = 'u801377270_hiraya_2025'; // Change to your database name

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$error_message = "";
$success_message = "";

// Step 1: Request Password Reset
if (isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } else {
        // Check if email exists in the database
        $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate OTP
            $otp_code = sprintf("%06d", rand(0, 999999));
            $otp_purpose = 'PASSWORD_RESET';
            $current_time = date('Y-m-d H:i:s');
            $otp_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Update the user record with OTP information
            $update_stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_purpose = ?, otp_created_at = ?, otp_expires_at = ?, otp_is_used = 0 WHERE id = ?");
            $update_stmt->bind_param("ssssi", $otp_code, $otp_purpose, $current_time, $otp_expires_at, $user['id']);
            
            if ($update_stmt->execute()) {
                // Send password reset email with OTP
                if (sendResetEmail($email, $otp_code, $user['fullname'])) {
                    // Store the email in session for the next step
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['otp_purpose'] = $otp_purpose;
                    
                    $success_message = "A verification code has been sent to your email. Please check your inbox and enter the code to reset your password.";
                } else {
                    $error_message = "Failed to send verification email. Please try again.";
                }
            } else {
                $error_message = "Something went wrong. Please try again later.";
            }
            
            $update_stmt->close();
        } else {
            // For security reasons, don't reveal if the email exists or not
            $success_message = "If your email is registered with us, you will receive a password reset code.";
        }
        
        $stmt->close();
    }
}

// Step 2: Verify OTP and show password reset form
if (isset($_POST['verify_otp'])) {
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_purpose'])) {
        header("Location: forgot-password.php");
        exit();
    }
    
    $email = $_SESSION['reset_email'];
    $otp_purpose = $_SESSION['otp_purpose'];
    
    // Collect and combine OTP digits
    $otp_digits = array();
    for ($i = 1; $i <= 6; $i++) {
        if (isset($_POST["otp_digit_$i"])) {
            $otp_digits[] = $_POST["otp_digit_$i"];
        } else {
            $error_message = "Please enter all OTP digits.";
            break;
        }
    }
    
    if (count($otp_digits) == 6) {
        $entered_otp = implode("", $otp_digits);
        
        // Validate OTP in database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND otp_code = ? AND otp_purpose = ? AND otp_is_used = 0");
        $stmt->bind_param("sss", $email, $entered_otp, $otp_purpose);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if OTP has expired
            $check_stmt = $conn->prepare("SELECT otp_expires_at FROM users WHERE id = ?");
            $check_stmt->bind_param("i", $user['id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $user_data = $check_result->fetch_assoc();
            
            $current_time = date('Y-m-d H:i:s');
            $otp_expires_at = $user_data['otp_expires_at'];
            
            if ($current_time > $otp_expires_at) {
                $error_message = "The verification code has expired. Please request a new one.";
            } else {
                // Store the user ID in session for the password reset step
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['is_otp_verified'] = true;
            }
            
            $check_stmt->close();
        } else {
            $error_message = "Invalid verification code. Please try again.";
        }
        
        $stmt->close();
    }
}

// Step 3: Reset Password
if (isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['is_otp_verified']) || $_SESSION['is_otp_verified'] !== true) {
        header("Location: forgot-password.php");
        exit();
    }
    
    $user_id = $_SESSION['reset_user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and mark OTP as used
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, otp_is_used = 1 WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            // Clear all reset session variables
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_purpose']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['is_otp_verified']);
            
            $success_message = "Your password has been successfully reset. You can now login with your new password.";
        } else {
            $error_message = "Failed to reset password. Please try again later.";
        }
        
        $update_stmt->close();
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    if (!isset($_SESSION['reset_email']) || !isset($_SESSION['otp_purpose'])) {
        header("Location: forgot-password.php");
        exit();
    }
    
    $email = $_SESSION['reset_email'];
    $otp_purpose = $_SESSION['otp_purpose'];
    
    // Generate new OTP
    $new_otp = sprintf("%06d", rand(0, 999999));
    $current_time = date('Y-m-d H:i:s');
    $otp_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Update OTP in database
    $update_stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_created_at = ?, otp_expires_at = ?, otp_is_used = 0 WHERE email = ? AND otp_purpose = ?");
    $update_stmt->bind_param("sssss", $new_otp, $current_time, $otp_expires_at, $email, $otp_purpose);
    
    if ($update_stmt->execute()) {
        // Get user's name for the email
        $name_stmt = $conn->prepare("SELECT fullname FROM users WHERE email = ?");
        $name_stmt->bind_param("s", $email);
        $name_stmt->execute();
        $name_result = $name_stmt->get_result();
        $user_data = $name_result->fetch_assoc();
        $fullname = $user_data['fullname'];
        $name_stmt->close();
        
        // Send new OTP
        if (sendResetEmail($email, $new_otp, $fullname)) {
            $success_message = "A new verification code has been sent to your email.";
        } else {
            $error_message = "Error sending verification email. Please try again.";
        }
    } else {
        $error_message = "Error generating new verification code. Please try again.";
    }
    
    $update_stmt->close();
}

mysqli_close($conn);

// Function to send password reset email
function sendResetEmail($email, $otp_code, $fullname) {
    require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\Exception.php';
    require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\PHPMailer.php';
    require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'leiumarsayco@gmail.com';
        $mail->Password = 'vjqugnnzpdldnlon';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('no-reply@hirayafit.com', 'HirayaFit');
        $mail->addAddress($email);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your HirayaFit Password';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <table style='width: 100%; padding: 20px; background-color: #f4f4f4;'>
                    <tr>
                        <td style='text-align: center;'>
                            <h2 style='color: #0071c5;'>Password Reset Request</h2>
                            <p>Hello {$fullname},</p>
                            <p>We received a request to reset your HirayaFit account password. Please use the verification code below to complete your password reset:</p>
                            <h1 style='color: #0071c5; font-size: 36px; letter-spacing: 4px;'>{$otp_code}</h1>
                            <p>This code will expire in <strong>15 minutes</strong>.</p>
                            <p>If you did not request a password reset, you can safely ignore this email.</p>
                            <br>
                            <p style='font-size: 14px;'>Best regards,<br>HirayaFit Team</p>
                            <hr style='border: 0; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #888;'>This is an automated message. Please do not reply directly to this email.</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
        ";
        $mail->AltBody = "Hello {$fullname}, Your verification code for HirayaFit password reset is: {$otp_code}. This code will expire in 15 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - HirayaFit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #111111;
            --secondary: #0071c5;
            --accent: #e5e5e5;
            --light: #ffffff;
            --dark: #111111;
            --grey: #767676;
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
        
        /* Top Bar and Header Styles */
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
        
        /* Navigation Styles */
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
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 22px;
            cursor: pointer;
        }
        
        /* Forgot Password Specific Styles */
        .page-title {
            text-align: center;
            padding: 40px 0 20px;
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-container {
            max-width: 500px;
            margin: 0 auto 60px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .icon {
            font-size: 60px;
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        .form-message {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(0,113,197,0.2);
            outline: none;
        }
        
        .submit-btn {
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
        }
        
        .submit-btn:hover {
            background-color: #005fa8;
        }
        
        .back-link {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 15px;
        }
        
        .back-link:hover {
            color: #005fa8;
            text-decoration: underline;
        }
        
        /* OTP Verification Styles */
        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 25px 0;
        }
        
        .otp-input {
            width: 50px;
            height: 55px;
            font-size: 24px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .otp-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 2px rgba(0,113,197,0.2);
        }
        
        .resend-section {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .resend-text {
            font-size: 14px;
            color: var(--grey);
        }
        
        .resend-btn {
            background: none;
            border: none;
            color: var(--secondary);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            text-decoration: underline;
        }
        
        .resend-btn:hover {
            color: #005fa8;
        }
        
        /* Password Requirements */
        .password-requirements {
            margin-top: 10px;
            font-size: 13px;
            color: var(--grey);
            text-align: left;
        }
        
        .password-requirements ul {
            padding-left: 20px;
            margin-top: 5px;
        }
        
        .success-message {
            color: #28a745;
            font-weight: 500;
            margin: 20px 0;
            padding: 10px;
            background-color: rgba(40, 167, 69, 0.1);
            border-radius: 5px;
        }
        
        .error-message {
            color: #dc3545;
            font-weight: 500;
            margin: 20px 0;
            padding: 10px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
        }
        
        .login-link {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 30px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login-link:hover {
            background-color: #333;
        }
        
        /* Password visibility toggle */
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--grey);
        }
        
        /* Responsive styles */
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
            
            .form-container {
                padding: 20px;
                margin: 0 15px 40px;
            }
            
            .otp-inputs {
                gap: 5px;
            }
            
            .otp-input {
                width: 40px;
                height: 45px;
                font-size: 20px;
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
                <a href="index.html" class="logo">Hiraya<span>Fit</span></a>
                
                <div class="nav-icons">
                    <div class="account-dropdown" id="accountDropdown">
                        <a href="#" id="accountBtn"><i class="fas fa-user"></i></a>
                        <div class="account-dropdown-content" id="accountDropdownContent">
                            <h3>My Account</h3>
                            <a href="sign-in.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
                            <a href="sign-up.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                            <a href="#orders"><i class="fas fa-box"></i> Track Orders</a>
                            <a href="#wishlist"><i class="fas fa-heart"></i> My Wishlist</a>
                        </div>
                    </div>
                    <a href="#"><i class="fas fa-heart"></i></a>
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
    
    <!-- Navigation -->
    <nav class="main-nav" id="mainNav">
        <a href="index.html">HOME</a>
        <a href="shop.html">SHOP</a>
        <a href="men.html">MEN</a>
        <a href="women.html">WOMEN</a>
        <a href="foot.html">FOOTWEAR</a>
        <a href="acces.html">ACCESSORIES</a>
        <a href="#about">ABOUT</a>
        <a href="#contact">CONTACT</a>
    </nav>
    
    <!-- Forgot Password Section -->
    <section>
        <h1 class="page-title">Forgot Password</h1>
        
        <div class="form-container">
            <?php if (!empty($success_message) && (!isset($_SESSION['reset_email']) || (isset($_SESSION['is_otp_verified']) && $_SESSION['is_otp_verified'] === true && isset($_POST['reset_password'])))): ?>
                <!-- Final Success Message (After Password Reset) -->
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
                <a href="sign-in.php" class="login-link">Sign In Now</a>
                
            <?php elseif (isset($_SESSION['is_otp_verified']) && $_SESSION['is_otp_verified'] === true): ?>
                <!-- Password Reset Form (Step 3) -->
                <div class="icon">
                    <i class="fas fa-key"></i>
                </div>
                <p class="form-message">
                    Create a new password for your account.
                </p>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="resetPasswordForm">
                    <div class="form-group password-field">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required>
                        <span class="password-toggle" id="newPasswordToggle"><i class="far fa-eye"></i></span>
                    </div>
                    
                    <div class="password-requirements">
                        <p>Password must contain:</p>
                        <ul>
                            <li>At least 8 characters</li>
                            <li>Letters and numbers</li>
                            <li>At least one special character</li>
                        </ul>
                    </div>
                    
                    <div class="form-group password-field">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <span class="password-toggle" id="confirmPasswordToggle"><i class="far fa-eye"></i></span>
                    </div>
                    
                    <button type="submit" name="reset_password" class="submit-btn">Reset Password</button>
                </form>
                
            <?php elseif (isset($_SESSION['reset_email']) && isset($_SESSION['otp_purpose'])): ?>
                <!-- OTP Verification Form (Step 2) -->
                <div class="icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <p class="form-message">
                    We've sent a verification code to <span style="font-weight: 500; color: var(--secondary);"><?php echo htmlspecialchars($_SESSION['reset_email']); ?></span>. 
                    Please enter the 6-digit code below to continue.
                </p>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="otp-inputs">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <input type="text" class="otp-input" name="otp_digit_<?php echo $i; ?>" maxlength="1" required 
                                   pattern="[0-9]" inputmode="numeric" autocomplete="off" 
                                   data-index="<?php echo $i; ?>">
                        <?php endfor; ?>
                    </div>
                    
                    <button type="submit" name="verify_otp" class="submit-btn">Verify Code</button>
                </form>
                
                <div class="resend-section">
                    <span class="resend-text">Didn't receive the code?</span>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="resend_otp" class="resend-btn">Resend Code</button>
                    </form>
                </div>
                
                <a href="forgot-password.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Start Over
                </a>
                
            <?php else: ?>
                <!-- Email Request Form (Step 1) -->
                <div class="icon">
                    <i class="fas fa-unlock-alt"></i>
                </div>
                <p class="form-message">
                    Enter your registered email address and we'll send you a verification code to reset your password.
                </p>
                
                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    
                    <button type="submit" name="request_reset" class="submit-btn">Send Reset Code</button>
                </form>
                
                <a href="sign-in.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Sign In
                </a>
            <?php endif; ?>
        </div>
    </section>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Account dropdown functionality
            const accountBtn = document.getElementById('accountBtn');
            const accountDropdown = document.getElementById('accountDropdown');
            
            if (accountBtn) {
                accountBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    accountDropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!accountDropdown.contains(e.target) && !accountBtn.contains(e.target)) {
                        accountDropdown.classList.remove('active');
                    }
                });
            }
            
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    mainNav.classList.toggle('active');
                });
            }
            
            // Password visibility toggle
            const passwordToggles = document.querySelectorAll('.password-toggle');
            
            passwordToggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // OTP input handling
            const otpInputs = document.querySelectorAll('.otp-input');
            
            if (otpInputs.length > 0) {
                otpInputs.forEach(function(input) {
                    // Auto-focus next input field
                    input.addEventListener('input', function() {
                        if (this.value.length === 1) {
                            const nextIndex = parseInt(this.dataset.index) + 1;
                            if (nextIndex <= 6) {
                                document.querySelector(`.otp-input[data-index="${nextIndex}"]`).focus();
                            }
                        }
                    });
                    
                    // Allow backspace to focus previous input
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Backspace' && this.value.length === 0) {
                            const prevIndex = parseInt(this.dataset.index) - 1;
                            if (prevIndex >= 1) {
                                const prevInput = document.querySelector(`.otp-input[data-index="${prevIndex}"]`);
                                prevInput.focus();
                                prevInput.value = '';
                            }
                        }
                    });
    
                    // Only allow numbers
                    input.addEventListener('keypress', function(e) {
                        if (!/^\d$/.test(e.key)) {
                            e.preventDefault();
                        }
                    });
    
                    // Handle paste event
                    input.addEventListener('paste', function(e) {
                        e.preventDefault();
                        const paste = e.clipboardData.getData('text');
                        
                        // If it's a 6-digit number
                        if (/^\d{6}$/.test(paste)) {
                            // Distribute across inputs
                            otpInputs.forEach((input, index) => {
                                input.value = paste[index];
                            });
                        }
                    });
                });
                
                // Auto-focus first input on page load
                otpInputs[0].focus();
            }
            
            // Password validation
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            
            if (resetPasswordForm) {
                resetPasswordForm.addEventListener('submit', function(event) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    let isValid = true;
                    let errorMessage = '';
                    
                    // Check password length
                    if (newPassword.length < 8) {
                        errorMessage = 'Password must be at least 8 characters long.';
                        isValid = false;
                    }
                    // Check for letters and numbers
                    else if (!/^(?=.*[A-Za-z])(?=.*\d)/.test(newPassword)) {
                        errorMessage = 'Password must contain both letters and numbers.';
                        isValid = false;
                    }
                    // Check for special character
                    else if (!/[^A-Za-z0-9]/.test(newPassword)) {
                        errorMessage = 'Password must contain at least one special character.';
                        isValid = false;
                    }
                    // Check passwords match
                    else if (newPassword !== confirmPassword) {
                        errorMessage = 'Passwords do not match.';
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        event.preventDefault();
                        const errorDiv = document.querySelector('.error-message');
                        
                        if (errorDiv) {
                            errorDiv.textContent = errorMessage;
                        } else {
                            const newErrorDiv = document.createElement('div');
                            newErrorDiv.className = 'error-message';
                            newErrorDiv.textContent = errorMessage;
                            
                            const submitBtn = document.querySelector('.submit-btn');
                            resetPasswordForm.insertBefore(newErrorDiv, submitBtn);
                        }
                    }
                });
            }
        });
    </script>

    <script src="js/cart.js"></script>
    
</body>
</html>