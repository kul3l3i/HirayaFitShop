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


// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CAPTCHA
    if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
        echo "<script>alert('Please complete the CAPTCHA verification.'); history.back();</script>";
        exit();
    }
    
    // Verify reCAPTCHA with Google
    $recaptcha_secret = "6LcLEykrAAAAAGQ5x_UFAXywn85LiqkjYOqv0Lzl"; // Replace with your secret key
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $recaptcha_url = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}";
    $recaptcha_data = json_decode(file_get_contents($recaptcha_url));
    
    if (!$recaptcha_data->success) {
        echo "<script>alert('CAPTCHA verification failed. Please try again.'); history.back();</script>";
        exit();
    }
    
    // Sanitize input
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Handle profile image upload
    $profile_image = NULL;
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            echo "<script>alert('Only JPG, JPEG, and PNG files are allowed.'); history.back();</script>";
            exit();
        }
        
        if ($_FILES['profile_image']['size'] > $max_size) {
            echo "<script>alert('File size must be less than 2MB.'); history.back();</script>";
            exit();
        }
        
        $upload_dir = 'uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $profile_image = $filename;
        } else {
            echo "<script>alert('Error uploading file.'); history.back();</script>";
            exit();
        }
    }
    
    // Password validation
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); history.back();</script>";
        exit();
    }
    
    // Password strength validation
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || 
        !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        echo "<script>alert('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.'); history.back();</script>";
        exit();
    }
    
    // Hash password
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate OTP for email verification
    $otp_code = sprintf("%06d", rand(0, 999999)); // Generate 6-digit OTP
    $otp_purpose = 'EMAIL_VERIFICATION';
    $current_time = date('Y-m-d H:i:s');
    $otp_expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $is_active = false; // Account not active until email verified
    
    // Check if username or email already exists
    $check_user_stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ? OR email = ?");
    $check_user_stmt->bind_param("ss", $username, $email);
    $check_user_stmt->execute();
    $check_user_stmt->store_result();
    
    if ($check_user_stmt->num_rows > 0) {
        echo "<script>alert('Username or Email is already in use. Please choose another.'); history.back();</script>";
    } else {
        // Insert user into database
        $insert_user_stmt = $conn->prepare("INSERT INTO users 
            (fullname, email, username, password, address, phone, profile_image, is_active, 
            otp_code, otp_purpose, otp_created_at, otp_expires_at, otp_is_used) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $otp_is_used = false;
        $is_active_int = $is_active ? 1 : 0;
        $otp_is_used_int = $otp_is_used ? 1 : 0;
        
        $insert_user_stmt->bind_param(
            "sssssssissssi",
            $fullname,
            $email,
            $username,
            $password_hashed,
            $address,
            $phone,
            $profile_image,
            $is_active_int,
            $otp_code,
            $otp_purpose,
            $current_time,
            $otp_expires_at,
            $otp_is_used_int
        );
        
        if ($insert_user_stmt->execute()) {
            // Store OTP and email in session for verification page
            $_SESSION['registration_email'] = $email;
            $_SESSION['otp_purpose'] = $otp_purpose;
            
            // Send OTP email
            if (sendVerificationEmail($email, $otp_code, $fullname)) {
                header("Location: verify_email.php");
                exit();
            } else {
                // If email fails, still create account but inform user
                $_SESSION['email_error'] = "Account created but there was an issue sending the verification email. Please contact support.";
                header("Location:sign-in.php");
                exit();
            }
        } else {
            echo "<script>alert('Error: " . $insert_user_stmt->error . "'); history.back();</script>";
        }
        
        $insert_user_stmt->close();
    }
    
    $check_user_stmt->close();
}

mysqli_close($conn);

function sendVerificationEmail($email, $otp_code, $fullname) {
    require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\Exception.php';
    require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\PHPMailer.php';
    require 'C:\xampp\htdocs\PHPMailer\PHPMailer\src\SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@hirayafit.shop';
        $mail->Password = 'Hirayafit@2025';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@hirayafit.shopnoreply@hirayafit.shop', 'HirayaFit');
        $mail->addAddress($email);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your HirayaFit Account';
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <table style='width: 100%; padding: 20px; background-color: #f4f4f4;'>
                    <tr>
                        <td style='text-align: center;'>
                            <h2 style='color: #0071c5;'>Welcome to HirayaFit, {$fullname}!</h2>
                            <p>Thank you for registering with us. To complete your registration, please use the verification code below:</p>
                            <h1 style='color: #0071c5; font-size: 36px; letter-spacing: 4px;'>{$otp_code}</h1>
                            <p>This code will expire in <strong>15 minutes</strong>.</p>
                            <p>You must verify your email to activate your account and enjoy full access to HirayaFit's features.</p>
                            <p>If you did not register for a HirayaFit account, you can safely ignore this email.</p>
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
        $mail->AltBody = "Hello {$fullname}, Your verification code for HirayaFit registration is: {$otp_code}. This code will expire in 15 minutes.";
        
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
    <title>Sign Up - HirayaFit</title>    <link rel="icon" href="images/hf.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
        
        /* Keeping all the existing styles... */
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
        
        /* Sign Up Page Specific Styles */
        .page-title {
            text-align: center;
            padding: 40px 0 20px;
            font-size: 28px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .signup-container {
            max-width: 600px;
            margin: 0 auto 60px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
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
        
        .terms-group {
            margin-bottom: 25px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .checkbox-group input {
            margin-right: 10px;
            margin-top: 3px;
        }
        
        .checkbox-group label {
            font-size: 14px;
            line-height: 1.4;
            color: var(--dark);
        }
        
        .checkbox-group a {
            color: var(--secondary);
            text-decoration: none;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        .btn-signup {
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
        
        .btn-signup:hover {
            background-color: #005fa8;
        }
        
        .social-signup {
            margin-top: 30px;
            text-align: center;
        }
        
        .social-signup p {
            position: relative;
            margin-bottom: 20px;
            color: var(--grey);
            font-size: 14px;
        }
        
        .social-signup p:before,
        .social-signup p:after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background-color: #ddd;
        }
        
        .social-signup p:before {
            left: 0;
        }
        
        .social-signup p:after {
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
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 15px;
            color: var(--dark);
        }
        
        .login-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .login-link a:hover {
            text-decoration: underline;
            color: #005fa8;
        }
        
        .password-requirements {
            margin-top: 5px;
            font-size: 12px;
            color: var(--grey);
        }
        
        .profile-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 10px;
            border: 2px solid #ddd;
        }
        
        .profile-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-preview i {
            font-size: 36px;
            color: #aaa;
        }
        
        .file-upload-label {
            display: inline-block;
            padding: 8px 16px;
            background-color: #f0f0f0;
            color: var(--dark);
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .file-upload-label:hover {
            background-color: #e0e0e0;
        }
        
        .file-upload-input {
            display: none;
        }
        
        .g-recaptcha {
            margin: 20px 0;
            display: flex;
            justify-content: center;
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
            
            .signup-container {
                padding: 20px;
                margin: 0 15px 40px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 20px;
            }
            
            .g-recaptcha {
                transform: scale(0.85);
                transform-origin: left center;
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
                            <a href="#wishlist"><i class="fas fa-heart"></i> My Wishlist</a>
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
        <a href="index.php">HOME</a>
        <a href="usershop.php.php">SHOP</a>
        <a href="men.php">MEN</a>
        <a href="women.php">WOMEN</a>
        <a href="foot.php">FOOTWEAR</a>
        <a href="acces.php">ACCESSORIES</a>
        <a href="about.php">ABOUT</a>
        <a href="contact.php">CONTACT</a>
    </nav>
    
    <!-- Sign Up Section -->
    <section>
        <h1 class="page-title">Create Your Account</h1>
        
        <div class="signup-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <!-- Profile Image Upload -->
                <div class="profile-upload">
                    <div class="profile-preview" id="profilePreview">
                        <i class="fas fa-user"></i>
                    </div>
                    <label for="profile_image" class="file-upload-label">
                        <i class="fas fa-camera"></i> Choose Profile Picture
                    </label>
                    <input type="file" name="profile_image" id="profile_image" class="file-upload-input" accept="image/jpeg,image/png,image/jpg">
                </div>
                
                <!-- Full Name -->
                <div class="form-group">
                    <label for="fullname">Full Name *</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                </div>
                
                <div class="form-row">
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                </div>
                
                <!-- Address -->
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address">
                </div>
                
                <!-- Username -->
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="form-row">
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-requirements">
                            Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <!-- Terms and Privacy Policy -->
                <div class="terms-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter">
                        <label for="newsletter">Sign up for exclusive offers and updates about HirayaFit products</label>
                    </div>
                </div>
                
                <!-- reCAPTCHA -->
                <div class="g-recaptcha" data-sitekey="6LcLEykrAAAAABaiA840EJYew_NQ7-usuZtBDdH0"></div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-signup">Create Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="sign-in.php">Sign In</a>
            </div>
            
            <div class="social-signup">
                <p>Or sign up with</p>
                <div class="social-buttons">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-apple"></i></a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Script for profile image preview -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile image preview
            const profileInput = document.getElementById('profile_image');
            const profilePreview = document.getElementById('profilePreview');
            
            profileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Clear the preview div
                        profilePreview.innerHTML = '';
                        
                        // Create and add the image element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        profilePreview.appendChild(img);
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // Account dropdown functionality
            const accountBtn = document.getElementById('accountBtn');
            const accountDropdown = document.getElementById('accountDropdown');
            
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
            
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');
            
            mobileMenuToggle.addEventListener('click', function() {
                mainNav.classList.toggle('active');
            });
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                // Password match validation
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                // Password strength validation
                const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                if (!passwordRegex.test(password)) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.');
                    return false;
                }
                
                // Terms checkbox validation
                if (!document.getElementById('terms').checked) {
                    e.preventDefault();
                    alert('You must agree to the Terms of Service and Privacy Policy.');
                    return false;
                }
                
                return true;
            });
        });
    </script>

<script src="js/cart.js"></script>
<script>console.log('After cart.js');</script>

</body>
</html>