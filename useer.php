<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$user = null;

// If user is logged in, fetch their information
if ($loggedIn) {
    session_start();
    include 'db_connect.php';
    // Initialize variables
    $error = '';
    $username_email = '';
    

    // Prepare and execute query to get user details
    $stmt = $conn->prepare("SELECT id, fullname, username, email, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // If user exists, store their details
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Premium Activewear</title>
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .main-nav a:hover,
        .main-nav a.active {
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

        .main-nav a:hover:after,
        .main-nav a.active:after {
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

        /* Media queries for responsive design */
        @media (max-width: 992px) {
            .search-bar {
                max-width: 300px;
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

            .search-bar {
                order: 4;
                width: 100%;
                margin-top: 10px;
            }

            .main-nav {
                display: none;
                flex-direction: column;
                align-items: center;
            }

            .main-nav.active {
                display: flex;
            }

            .account-dropdown-content {
                position: fixed;
                top: 60px;
                right: 15px;
                width: calc(100% - 30px);
                max-width: 300px;
            }
        }

        /* Results count */
        .results-count {
            text-align: center;
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }


        /* Updated HirayaFit Product Styles - Matched with search results section */
        :root {
            --primary: #111111;
            --secondary: #0071c5;
            --accent: #e5e5e5;
            --light: #ffffff;
            --dark: #111111;
            --grey: #767676;
            --sale-color: #0071c5;
            --price-color: #e63946;
        }

        /* Product Container Styling - Matched with results-grid */
        #product-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Responsive grid adjustments */
        @media (max-width: 1200px) {
            #product-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            #product-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            #product-container {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Status */
        #loading-status {
            text-align: center;
            font-size: 18px;
            color: #777;
            padding: 40px 0;
        }

        .product-card {
            background: var(--light);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
        }

        .product-image {
            position: relative;
            height: 240px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }


        /* Sale Tag Styling */
        .sale-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--secondary);
            color: var(--light);
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.8rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Product Info Section */
        .product-info {
            padding: 22px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-info h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            line-height: 1.3;
            text-align: center;
        }

        .product-category {
            font-size: 13px;
            color: var(--grey);
            margin: 0 0 12px 0;
            text-transform: uppercase;
            font-weight: 500;
            text-align: center;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--price-color);
            margin: 0 0 8px 0;
            text-align: center;
        }

        .product-stock {
            font-size: 12px;
            color: var(--grey);
            margin: 0;
            text-align: center;
        }

        /* Action buttons */
        .product-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        /* Add to Cart Button */
        .add-to-cart-btn {
            display: block;
            background-color: var(--primary);
            color: var(--light);
            border: none;
            padding: 12px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 12px;
            width: 100%;
            letter-spacing: 0.5px;
        }

        .add-to-cart-btn:hover {
            background-color: var(--secondary);
        }

        /* Star Rating */
        .product-rating {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
            color: #ffc107;
        }

        .product-rating span {
            font-size: 12px;
            color: var(--grey);
            margin-left: 5px;
        }

        /* When no products found */
        .no-products {
            grid-column: span 4;
            text-align: center;
            font-size: 18px;
            color: #777;
            padding: 40px 0;
        }

        /* Section styling from search results */
        .product-section {
            padding: 60px 0;
            background-color: #f9f9f9;
            margin-top: 40px;
            border-top: 1px solid #e0e0e0;
        }

        .product-section h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 30px;
            color: #333;
            position: relative;
        }

        .product-section h2:after {
            content: "";
            display: block;
            width: 60px;
            height: 3px;
            background-color: var(--secondary);
            margin: 15px auto 0;
        }



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
            animation: modalFadeIn 0.3s ease-out;
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
            color: #555;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #000;
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
            border-radius: 4px;
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
            font-weight: bold;
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
            font-size: 18px;
        }

        .modal-product-rating .rating-text {
            color: #777;
            font-size: 14px;
        }

        /* Product Description */
        .modal-product-description {
            margin-bottom: 20px;
            color: #555;
            line-height: 1.6;
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
            color: #333;
        }

        /* Size Options */
        .size-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            border-radius: 4px;
        }

        .size-btn:hover {
            border-color: #999;
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

        .color-btn:hover {
            transform: scale(1.1);
        }

        .color-btn.active {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #333;
        }

        /* Quantity Section */
        .modal-product-quantity {
            margin-bottom: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            max-width: 120px;
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
            transition: background-color 0.2s;
        }

        .quantity-btn:hover {
            background-color: #e5e5e5;
        }

        .quantity-input {
            width: 50px;
            height: 36px;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
            text-align: center;
            font-size: 16px;
        }

        /* Stock info */
        .stock-info {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
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
            max-width: 300px;
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
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1100;
            transition: opacity 0.5s;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .confirmation-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .confirmation-content i {
            font-size: 24px;
            color: white;
        }

        .confirmation-content p {
            margin: 0;
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

            .modal-product-content {
                width: 95%;
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
                <?php if (!$loggedIn): ?>
                    <a href="sign-in.php">Sign In</a>
                    <a href="register.php">Register</a>
                <?php else: ?>
                    <a href="#">Welcome, <?php echo $user['username']; ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="navbar">
                <a href="index.php" class="logo">Hiraya<span>Fit</span></a>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search products...">
                    <button onclick="searchProducts()"><i class="fas fa-search"></i></button>
                </div>
                <div class="nav-icons">
                    <?php if ($loggedIn): ?>
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
                                <a href="wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a>
                                <a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a>
                                <div class="sign-out-btn">
                                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <!-- Login link for non-logged-in users -->
                        <a href="login.php"><i class="fas fa-user-circle"></i></a>
                    <?php endif; ?>

                    <!--<a href="wishlist.php"><i class="fas fa-heart"></i></a>-->
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
        <!-- Simplified Navigation -->
        <nav class="main-nav" id="mainNav">
            <a href="" class="active">HOME</a>

        </nav>


    </header>

    <!-- JavaScript for Dropdown and Mobile Menu -->
    <script src="js/home.js"></script>

    <script>console.log('After home.js');</script>

    <script>
        // Global variables
        let productData;
        let products = [];
        let loadingCompleted = false;

        // Function to load the XML data
        function loadProductData() {
            // Show loading indication
            document.getElementById("loading-status").textContent = "Loading products...";

            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (this.readyState == 4) {
                    if (this.status == 200) {
                        productData = this.responseXML;
                        parseProductData();
                        displayProducts(); // Display products after loading
                        loadingCompleted = true;
                        document.getElementById("loading-status").textContent = "Products loaded successfully!";
                    } else {
                        console.error("Failed to load XML data. Status:", this.status);
                        document.getElementById("loading-status").textContent = "Failed to load products. Please try again.";
                    }
                }
            };
            xhr.open("GET", "product.xml", true);
            xhr.send();
        }

        // Parse XML into a more usable format
        function parseProductData() {
            if (!productData) {
                console.error("No product data available to parse");
                return;
            }

            const productElements = productData.getElementsByTagName("product");
            products = [];

            for (let i = 0; i < productElements.length; i++) {
                const product = productElements[i];
                const productObj = {
                    id: getElementTextContent(product, "id"),
                    name: getElementTextContent(product, "name"),
                    category: getElementTextContent(product, "category"),
                    price: getElementTextContent(product, "price"),
                    description: getElementTextContent(product, "description"),
                    image: getElementTextContent(product, "image"),
                    stock: getElementTextContent(product, "stock"),
                    rating: getElementTextContent(product, "rating"),
                    featured: getElementTextContent(product, "featured") === "true",
                    on_sale: getElementTextContent(product, "on_sale") === "true"
                };

                // Get sizes
                const sizeElements = product.getElementsByTagName("size");
                productObj.sizes = [];
                for (let j = 0; j < sizeElements.length; j++) {
                    productObj.sizes.push(sizeElements[j].textContent);
                }

                // Get colors
                const colorElements = product.getElementsByTagName("color");
                productObj.colors = [];
                for (let j = 0; j < colorElements.length; j++) {
                    productObj.colors.push(colorElements[j].textContent);
                }

                products.push(productObj);
            }

            console.log("Products loaded:", products.length);
        }

        // Helper function to get text content of an element
        function getElementTextContent(parent, tagName) {
            const elements = parent.getElementsByTagName(tagName);
            if (elements.length > 0) {
                return elements[0].textContent;
            }
            return "";
        }

        // Function to display products on the page
        function displayProducts() {
            const productContainer = document.getElementById("product-container");
            if (!productContainer) {
                console.error("Product container element not found");
                return;
            }

            // Clear existing products
            productContainer.innerHTML = "";

            if (products.length === 0) {
                productContainer.innerHTML = "<p>No products found</p>";
                return;
            }

            // Create and append product cards
            products.forEach(product => {
                const productCard = createProductCard(product);
                productContainer.appendChild(productCard);
            });
        }

        // Function to create a product card element
        function createProductCard(product) {
            const card = document.createElement("div");
            card.className = "product-card";
            card.dataset.productId = product.id;

            // Create product image
            const imgContainer = document.createElement("div");
            imgContainer.className = "product-image";

            const img = document.createElement("img");
            img.src = product.image || "placeholder.jpg";
            img.alt = product.name;
            imgContainer.appendChild(img);

            // Add sale tag if product is on sale
            if (product.on_sale) {
                const saleTag = document.createElement("span");
                saleTag.className = "sale-tag";
                saleTag.textContent = "SALE";
                imgContainer.appendChild(saleTag);
            }

            // Create product info
            const info = document.createElement("div");
            info.className = "product-info";

            const name = document.createElement("h3");
            name.textContent = product.name;

            const category = document.createElement("p");
            category.className = "product-category";
            category.textContent = product.category;

            // Create star rating element
            const ratingDiv = document.createElement("div");
            ratingDiv.className = "product-rating";

            // Create stars based on rating
            const rating = parseFloat(product.rating);
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement("i");
                if (i <= Math.floor(rating)) {
                    star.className = "fas fa-star"; // Full star
                } else if (i - 0.5 <= rating) {
                    star.className = "fas fa-star-half-alt"; // Half star
                } else {
                    star.className = "far fa-star"; // Empty star
                }
                ratingDiv.appendChild(star);
            }

            // Add rating number
            const ratingText = document.createElement("span");
            ratingText.textContent = `(${product.rating})`;
            ratingDiv.appendChild(ratingText);

            const price = document.createElement("p");
            price.className = "product-price";
            price.textContent = `₱${parseFloat(product.price).toFixed(2)}`;

            const stock = document.createElement("p");
            stock.className = "product-stock";
            stock.textContent = `Stock: ${product.stock}`;

            // Add to cart button
            const addToCartBtn = document.createElement("button");
            addToCartBtn.className = "add-to-cart-btn";
            addToCartBtn.textContent = "Add to Cart";
            addToCartBtn.addEventListener("click", (e) => {
                e.stopPropagation(); // Prevent card click event
                addToCart(product.id);
            });

            // Add elements to card
            card.appendChild(imgContainer);
            info.appendChild(name);
            info.appendChild(price);
            info.appendChild(category);
            info.appendChild(ratingDiv);
            info.appendChild(stock);
            info.appendChild(addToCartBtn);
            card.appendChild(info);

            // Add click event
            card.addEventListener("click", () => showProductDetails(product.id));

            return card;
        }

        // Function to add a product to cart
        function addToCart(productId) {
            console.log(`Adding product ${productId} to cart`);
            // Implement your cart functionality here
            alert("Product added to cart!");
        }

        // Function to display products on the page
        function displayProducts() {
            const productContainer = document.getElementById("product-container");
            if (!productContainer) {
                console.error("Product container element not found");
                return;
            }

            // Clear existing products
            productContainer.innerHTML = "";

            if (products.length === 0) {
                const noProducts = document.createElement("div");
                noProducts.className = "no-products";
                noProducts.textContent = "No products found";
                productContainer.appendChild(noProducts);
                return;
            }

            // Create and append product cards
            products.forEach(product => {
                const productCard = createProductCard(product);
                productContainer.appendChild(productCard);
            });
        }

        // Initialize products when the page loads
        document.addEventListener("DOMContentLoaded", function () {
            // Add stylesheet for Font Awesome icons
            const fontAwesome = document.createElement("link");
            fontAwesome.rel = "stylesheet";
            fontAwesome.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css";
            document.head.appendChild(fontAwesome);

            // Create container for products if it doesn't exist
            if (!document.getElementById("product-container")) {
                const container = document.createElement("div");
                container.id = "product-container";
                document.body.appendChild(container);
            }

            // Create loading status element with better styling
            if (!document.getElementById("loading-status")) {
                const loadingStatus = document.createElement("div");
                loadingStatus.id = "loading-status";
                loadingStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading products...';
                document.body.insertBefore(loadingStatus, document.getElementById("product-container"));
            }

            // Load products
            loadProductData();
        });



        /////////////////////////
        // Enhanced Search and Filter Functions for HirayaFit

        // Global variables
        let filteredProducts = [];
        let currentCategory = "all";
        let searchQuery = "";

        // Modify the existing loadProductData function to initialize filters after loading
        function loadProductData() {
            // Show loading indication
            document.getElementById("loading-status").textContent = "Loading products...";

            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (this.readyState == 4) {
                    if (this.status == 200) {
                        productData = this.responseXML;
                        parseProductData();
                        setupFilters(); // Setup category filters after data is loaded
                        applyFiltersAndSearch(); // Apply any initial filters and display products
                        loadingCompleted = true;
                        document.getElementById("loading-status").textContent = "";
                    } else {
                        console.error("Failed to load XML data. Status:", this.status);
                        document.getElementById("loading-status").textContent = "Failed to load products. Please try again.";
                    }
                }
            };
            xhr.open("GET", "product.xml", true);
            xhr.send();
        }

        // Function to set up the category filters based on available product categories
        function setupFilters() {
            // Extract unique categories from products
            const categories = ["all"];
            products.forEach(product => {
                if (!categories.includes(product.category)) {
                    categories.push(product.category);
                }
            });

            // Get the main navigation element
            const mainNav = document.getElementById("mainNav");

            // Clear existing links except HOME
            while (mainNav.childNodes.length > 1) {
                mainNav.removeChild(mainNav.lastChild);
            }

            // Add category links to navigation
            categories.forEach(category => {
                const categoryLink = document.createElement("a");
                categoryLink.href = "#";
                categoryLink.textContent = category.toUpperCase();
                categoryLink.dataset.category = category;

                // Set active class if it's the current category
                if (category === currentCategory) {
                    categoryLink.classList.add("active");
                }

                // Add click event to filter products
                categoryLink.addEventListener("click", function (e) {
                    e.preventDefault();

                    // Remove active class from all links
                    document.querySelectorAll("#mainNav a").forEach(link => {
                        link.classList.remove("active");
                    });

                    // Add active class to clicked link
                    this.classList.add("active");

                    // Set current category and apply filters
                    currentCategory = category;
                    applyFiltersAndSearch();
                });

                mainNav.appendChild(categoryLink);
            });
        }

        // Function to search products based on query
        function searchProducts() {
            const searchInput = document.getElementById("searchInput");
            searchQuery = searchInput.value.trim().toLowerCase();
            applyFiltersAndSearch();
        }

        // Function to apply both category filters and search query
        function applyFiltersAndSearch() {
            // Start with all products
            filteredProducts = [...products];

            // Apply category filter if not "all"
            if (currentCategory !== "all") {
                filteredProducts = filteredProducts.filter(product =>
                    product.category.toLowerCase() === currentCategory.toLowerCase()
                );
            }

            // Apply search filter if there's a search query
            if (searchQuery) {
                filteredProducts = filteredProducts.filter(product =>
                    product.name.toLowerCase().includes(searchQuery) ||
                    product.description.toLowerCase().includes(searchQuery) ||
                    product.category.toLowerCase().includes(searchQuery)
                );
            }

            // Update results count
            updateResultsCount();

            // Display filtered products
            displayFilteredProducts();
        }

        // Function to update the results count
        function updateResultsCount() {
            const resultsCountElem = document.getElementById("results-count");
            if (resultsCountElem) {
                resultsCountElem.textContent = `${filteredProducts.length} products found`;
            }
        }

        // Function to display filtered products
        function displayFilteredProducts() {
            const productContainer = document.getElementById("product-container");
            if (!productContainer) {
                console.error("Product container element not found");
                return;
            }

            // Clear existing products
            productContainer.innerHTML = "";

            if (filteredProducts.length === 0) {
                const noProducts = document.createElement("div");
                noProducts.className = "no-products";
                noProducts.textContent = "No products found for your search. Try different keywords or filters.";
                productContainer.appendChild(noProducts);
                return;
            }

            // Create and append product cards
            filteredProducts.forEach(product => {
                const productCard = createProductCard(product);
                productContainer.appendChild(productCard);
            });
        }

        // Initialize search and filters when the page loads
        document.addEventListener("DOMContentLoaded", function () {
            // Original code from the pasted script...



            // Create search bar container if it doesn't exist
            if (!document.querySelector(".search-bar")) {
                createSearchBarUI();
            }

            // Create container for products if it doesn't exist
            if (!document.getElementById("product-container")) {
                const container = document.createElement("div");
                container.id = "product-container";
                document.body.appendChild(container);
            }

            // Create results count element
            if (!document.getElementById("results-count")) {
                const resultsCount = document.createElement("div");
                resultsCount.id = "results-count";
                resultsCount.className = "results-count";
                document.body.insertBefore(resultsCount, document.getElementById("product-container"));
            }

            // Create loading status element with better styling
            if (!document.getElementById("loading-status")) {
                const loadingStatus = document.createElement("div");
                loadingStatus.id = "loading-status";
                loadingStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading products...';
                document.body.insertBefore(loadingStatus, document.getElementById("product-container"));
            }

            // Load products
            loadProductData();

            // Set up event listener for search input (search as you type)
            const searchInput = document.getElementById("searchInput");
            if (searchInput) {
                searchInput.addEventListener("input", function () {
                    // Use debounce to avoid too many searches when typing quickly
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        searchProducts();
                    }, 300);
                });

                // Add event listener for Enter key
                searchInput.addEventListener("keyup", function (event) {
                    if (event.key === "Enter") {
                        searchProducts();
                    }
                });
            }
        });

        // Function to create the search bar UI
        function createSearchBarUI() {
            // Create search bar container
            const searchBar = document.createElement("div");
            searchBar.className = "search-bar";

            // Create search input
            const searchInput = document.createElement("input");
            searchInput.type = "text";
            searchInput.id = "searchInput";
            searchInput.placeholder = "Search products...";

            // Create search button
            const searchButton = document.createElement("button");
            searchButton.innerHTML = '<i class="fas fa-search"></i>';
            searchButton.onclick = searchProducts;

            // Append elements to search bar
            searchBar.appendChild(searchInput);
            searchBar.appendChild(searchButton);

            // Find navbar to append search bar
            const navBar = document.querySelector(".main-nav");
            if (navBar) {
                // Insert search bar before the nav
                navBar.parentNode.insertBefore(searchBar, navBar);
            } else {
                // If no navbar, add to body
                document.body.appendChild(searchBar);
            }
        }

        ///////////////////////////////////////// show details
        // Function to show product details modal
        function showProductDetails(productId) {
            // Find the product by ID
            const product = products.find(p => p.id === productId);
            if (!product) {
                console.error(`Product with ID ${productId} not found`);
                return;
            }

            // Add modal styles if they don't exist yet
            if (!document.getElementById('product-modal-styles')) {
                addProductDetailsModalStyles();
            }

            // Create modal elements
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'product-modal-overlay';
            modalOverlay.id = 'product-modal';

            const modalContent = document.createElement('div');
            modalContent.className = 'product-modal-content';

            // Close button
            const closeButton = document.createElement('button');
            closeButton.className = 'modal-close';
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', closeProductModal);

            // Product grid layout
            const productGrid = document.createElement('div');
            productGrid.className = 'modal-product-grid';

            // Product image section
            const imageSection = document.createElement('div');
            imageSection.className = 'modal-product-image';

            const productImage = document.createElement('img');
            productImage.src = product.image || 'placeholder.jpg';
            productImage.alt = product.name;
            imageSection.appendChild(productImage);

            // Product details section
            const detailsSection = document.createElement('div');
            detailsSection.className = 'modal-product-details';

            // Product title
            const title = document.createElement('h2');
            title.className = 'modal-product-title';
            title.textContent = product.name;

            // Product price
            const price = document.createElement('p');
            price.className = 'modal-product-price';
            price.textContent = `₱${parseFloat(product.price).toFixed(2)}`;

            // Product rating
            const rating = document.createElement('div');
            rating.className = 'modal-product-rating';

            const starsDiv = document.createElement('div');
            starsDiv.className = 'stars';

            // Create stars based on rating
            const ratingValue = parseFloat(product.rating);
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('i');
                if (i <= Math.floor(ratingValue)) {
                    star.className = 'fas fa-star'; // Full star
                } else if (i - 0.5 <= ratingValue) {
                    star.className = 'fas fa-star-half-alt'; // Half star
                } else {
                    star.className = 'far fa-star'; // Empty star
                }
                starsDiv.appendChild(star);
            }

            const ratingText = document.createElement('span');
            ratingText.className = 'rating-text';
            ratingText.textContent = ` (${product.rating}) ratings`;
            starsDiv.appendChild(ratingText);
            rating.appendChild(starsDiv);

            // Product description
            const description = document.createElement('div');
            description.className = 'modal-product-description';
            description.textContent = product.description;

            // Product sizes section (if available)
            const sizesSection = document.createElement('div');
            sizesSection.className = 'modal-product-size';

            if (product.sizes && product.sizes.length > 0) {
                const sizeLabel = document.createElement('label');
                sizeLabel.textContent = 'Size:';
                sizesSection.appendChild(sizeLabel);

                const sizeOptions = document.createElement('div');
                sizeOptions.className = 'size-options';

                product.sizes.forEach((size, index) => {
                    const sizeBtn = document.createElement('button');
                    sizeBtn.className = 'size-btn';
                    if (index === 0) sizeBtn.classList.add('active');
                    sizeBtn.textContent = size;
                    sizeBtn.addEventListener('click', function () {
                        // Remove active class from all buttons
                        document.querySelectorAll('.size-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        // Add active class to clicked button
                        this.classList.add('active');
                    });
                    sizeOptions.appendChild(sizeBtn);
                });

                sizesSection.appendChild(sizeOptions);
            }

            // Product colors section (if available)
            const colorsSection = document.createElement('div');
            colorsSection.className = 'modal-product-color';

            if (product.colors && product.colors.length > 0) {
                const colorLabel = document.createElement('label');
                colorLabel.textContent = 'Color:';
                colorsSection.appendChild(colorLabel);

                const colorOptions = document.createElement('div');
                colorOptions.className = 'color-options';

                product.colors.forEach((color, index) => {
                    const colorBtn = document.createElement('div');
                    colorBtn.className = 'color-btn';
                    colorBtn.style.backgroundColor = color;
                    if (index === 0) colorBtn.classList.add('active');
                    colorBtn.addEventListener('click', function () {
                        document.querySelectorAll('.color-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                    });
                    colorOptions.appendChild(colorBtn);
                });

                colorsSection.appendChild(colorOptions);
            }

            // Quantity section
            const quantitySection = document.createElement('div');
            quantitySection.className = 'modal-product-quantity';

            const quantityLabel = document.createElement('label');
            quantityLabel.textContent = 'Quantity:';

            const quantityControl = document.createElement('div');
            quantityControl.className = 'quantity-control';

            const decreaseBtn = document.createElement('button');
            decreaseBtn.className = 'quantity-btn';
            decreaseBtn.textContent = '-';
            decreaseBtn.addEventListener('click', function () {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });

            const quantityInput = document.createElement('input');
            quantityInput.type = 'text';
            quantityInput.className = 'quantity-input';
            quantityInput.value = '1';
            quantityInput.min = '1';
            quantityInput.addEventListener('change', function () {
                if (this.value < 1 || isNaN(this.value)) {
                    this.value = 1;
                }
            });

            const increaseBtn = document.createElement('button');
            increaseBtn.className = 'quantity-btn';
            increaseBtn.textContent = '+';
            increaseBtn.addEventListener('click', function () {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                const stock = parseInt(product.stock);
                if (value < stock) {
                    input.value = value + 1;
                } else {
                    alert(`Sorry, only ${stock} items in stock.`);
                }
            });

            quantityControl.appendChild(decreaseBtn);
            quantityControl.appendChild(quantityInput);
            quantityControl.appendChild(increaseBtn);

            quantitySection.appendChild(quantityLabel);
            quantitySection.appendChild(quantityControl);

            // Stock information
            const stockInfo = document.createElement('p');
            stockInfo.className = 'stock-info';
            stockInfo.textContent = `In Stock: ${product.stock} items`;

            // Add to cart button
            const addToCartBtn = document.createElement('button');
            addToCartBtn.className = 'modal-add-to-cart';
            addToCartBtn.textContent = 'Add to Cart';
            addToCartBtn.addEventListener('click', function () {
                // Get selected size and color
                const selectedSize = document.querySelector('.size-btn.active')?.textContent || '';
                const selectedColor = document.querySelector('.color-btn.active')?.style.backgroundColor || '';
                const quantity = parseInt(quantityInput.value);

                // Add to cart function
                addToCartFromModal(product.id, selectedSize, selectedColor, quantity);

                // Show confirmation message
                showAddToCartConfirmation(product.name);
            });

            // Append everything to details section
            detailsSection.appendChild(title);
            detailsSection.appendChild(price);
            detailsSection.appendChild(rating);
            detailsSection.appendChild(description);
            detailsSection.appendChild(sizesSection);
            detailsSection.appendChild(colorsSection);
            detailsSection.appendChild(quantitySection);
            detailsSection.appendChild(stockInfo);
            detailsSection.appendChild(addToCartBtn);

            // Add all sections to the grid
            productGrid.appendChild(imageSection);
            productGrid.appendChild(detailsSection);

            // Add content to the modal
            modalContent.appendChild(closeButton);
            modalContent.appendChild(productGrid);
            modalOverlay.appendChild(modalContent);

            // Add the modal to the page
            document.body.appendChild(modalOverlay);

            // Add modal-open class to body to prevent scrolling
            document.body.classList.add('modal-open');

            // Show the modal
            modalOverlay.style.display = 'flex';

            // Close modal when clicking outside the content
            modalOverlay.addEventListener('click', function (e) {
                if (e.target === modalOverlay) {
                    closeProductModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeProductModal();
                }
            });
        }

        // Function to close the product modal
        function closeProductModal() {
            const modalOverlay = document.getElementById('product-modal');
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                modalOverlay.remove();
            }
        }

        // Function to add to cart from the modal
        function addToCartFromModal(productId, size, color, quantity) {
            console.log(`Adding to cart: Product ID ${productId}, Size: ${size}, Color: ${color}, Quantity: ${quantity}`);
            // Implement your cart functionality here
            // This could involve storing the selection in localStorage or making an API call
        }

        // Function to show add to cart confirmation
        function showAddToCartConfirmation(productName) {
            // Create confirmation element
            const confirmation = document.createElement('div');
            confirmation.className = 'add-to-cart-confirmation';

            const confirmationContent = document.createElement('div');
            confirmationContent.className = 'confirmation-content';

            const icon = document.createElement('i');
            icon.className = 'fas fa-check-circle';

            const message = document.createElement('p');
            message.textContent = `${productName} added to cart successfully!`;

            confirmationContent.appendChild(icon);
            confirmationContent.appendChild(message);
            confirmation.appendChild(confirmationContent);

            // Add to body
            document.body.appendChild(confirmation);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                confirmation.classList.add('fade-out');
                setTimeout(() => {
                    confirmation.remove();
                }, 500);
            }, 3000);
        }

        // Function to add modal styles to the document
        function addProductDetailsModalStyles() {
            const styleElement = document.createElement('style');
            styleElement.id = 'product-modal-styles';
            styleElement.textContent = `
     
    `;
            document.head.appendChild(styleElement);
        }



        /////////////////////////////////////////////////////
        // Cart System for HirayaFit
        // Global variables
        let cart = [];
        let cartTotal = 0;

        // Initialize cart on page load
        document.addEventListener("DOMContentLoaded", function () {
            // Load cart from localStorage
            loadCart();

            // Update cart count display
            updateCartCount();

            // Add event listener for cart button
            const cartBtn = document.getElementById("cartBtn");
            if (cartBtn) {
                cartBtn.addEventListener("click", function (e) {
                    e.preventDefault();
                    showCartModal();
                });
            }
        });

        // Function to add product to cart from product details modal
        function addToCartFromModal(productId, size, color, quantity) {
            // Get product details
            const product = products.find(p => p.id === productId);
            if (!product) {
                console.error(`Product with ID ${productId} not found`);
                return;
            }

            // Check if we have enough stock
            if (parseInt(product.stock) < quantity) {
                alert(`Sorry, only ${product.stock} items in stock.`);
                return;
            }

            // Create cart item
            const cartItem = {
                id: productId,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                size: size,
                color: color,
                quantity: quantity,
                subtotal: parseFloat(product.price) * quantity
            };

            // Check if item already exists in cart (same product, size, and color)
            const existingItemIndex = cart.findIndex(item =>
                item.id === productId &&
                item.size === size &&
                item.color === color
            );

            if (existingItemIndex !== -1) {
                // Update quantity if item exists
                const newQuantity = cart[existingItemIndex].quantity + quantity;
                if (newQuantity > parseInt(product.stock)) {
                    alert(`Sorry, cannot add more. Only ${product.stock} items in stock.`);
                    return;
                }
                cart[existingItemIndex].quantity = newQuantity;
                cart[existingItemIndex].subtotal = parseFloat(product.price) * newQuantity;
            } else {
                // Add new item to cart
                cart.push(cartItem);
            }

            // Save cart to localStorage
            saveCart();

            // Update cart count
            updateCartCount();

            // Show confirmation
            showAddToCartConfirmation(product.name);
        }

        // Function to add product to cart from product list
        function addToCart(productId) {
            // Get product details
            const product = products.find(p => p.id === productId);
            if (!product) {
                console.error(`Product with ID ${productId} not found`);
                return;
            }

            // Create cart item with default values (1 quantity, no size/color specified)
            const cartItem = {
                id: productId,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                size: product.sizes && product.sizes.length > 0 ? product.sizes[0] : '',
                color: product.colors && product.colors.length > 0 ? product.colors[0] : '',
                quantity: 1,
                subtotal: parseFloat(product.price)
            };

            // Check if item already exists in cart
            const existingItemIndex = cart.findIndex(item =>
                item.id === productId &&
                item.size === cartItem.size &&
                item.color === cartItem.color
            );

            if (existingItemIndex !== -1) {
                // Update quantity if item exists
                const newQuantity = cart[existingItemIndex].quantity + 1;
                if (newQuantity > parseInt(product.stock)) {
                    alert(`Sorry, cannot add more. Only ${product.stock} items in stock.`);
                    return;
                }
                cart[existingItemIndex].quantity = newQuantity;
                cart[existingItemIndex].subtotal = parseFloat(product.price) * newQuantity;
            } else {
                // Add new item to cart
                cart.push(cartItem);
            }

            // Save cart to localStorage
            saveCart();

            // Update cart count
            updateCartCount();

            // Show confirmation
            showAddToCartConfirmation(product.name);
        }

        function saveCart() {
            // Save to localStorage
            localStorage.setItem('hirayafit_cart', JSON.stringify(cart));

            // Recalculate total
            calculateCartTotal();

            // Send each item to PHP
            cart.forEach(item => {
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        productId: item.id,
                        productName: item.name,
                        image: item.image,
                        price: item.price,
                        size: item.size,
                        color: item.color,
                        quantity: item.quantity
                    })
                })
                    .then(response => response.text())
                    .then(data => console.log('Server says:', data))
                    .catch(error => console.error('Error saving to XML:', error));
            });
        }

        // Function to load cart from localStorage
        function loadCart() {
            const savedCart = localStorage.getItem('hirayafit_cart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                calculateCartTotal();
            }
        }

        // Function to calculate cart total
        function calculateCartTotal() {
            cartTotal = cart.reduce((total, item) => total + item.subtotal, 0);
        }

        // Function to update cart count display
        function updateCartCount() {
            const cartCount = document.getElementById("cartCount");
            if (cartCount) {
                const itemCount = cart.reduce((total, item) => total + item.quantity, 0);
                cartCount.textContent = itemCount;

                // Also update any PHP session variables if needed
                if (typeof updateCartSession === "function") {
                    updateCartSession(itemCount);
                }
            }
        }

        // Function to show cart modal
        function showCartModal() {
            // Add modal styles if they don't exist yet
            if (!document.getElementById('cart-modal-styles')) {
                addCartModalStyles();
            }

            // Create modal elements
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'cart-modal-overlay';
            modalOverlay.id = 'cart-modal';

            const modalContent = document.createElement('div');
            modalContent.className = 'cart-modal-content';

            // Close button
            const closeButton = document.createElement('button');
            closeButton.className = 'modal-close';
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', closeCartModal);

            // Cart header
            const cartHeader = document.createElement('div');
            cartHeader.className = 'cart-header';

            const cartTitle = document.createElement('h2');
            cartTitle.textContent = 'Your Shopping Cart';

            cartHeader.appendChild(cartTitle);

            // Cart items container
            const cartItemsContainer = document.createElement('div');
            cartItemsContainer.className = 'cart-items';

            if (cart.length === 0) {
                const emptyCart = document.createElement('div');
                emptyCart.className = 'empty-cart';

                const emptyIcon = document.createElement('i');
                emptyIcon.className = 'fas fa-shopping-cart';

                const emptyText = document.createElement('p');
                emptyText.textContent = 'Your cart is empty';

                const shopButton = document.createElement('button');
                shopButton.className = 'continue-shopping';
                shopButton.textContent = 'Continue Shopping';
                shopButton.addEventListener('click', closeCartModal);

                emptyCart.appendChild(emptyIcon);
                emptyCart.appendChild(emptyText);
                emptyCart.appendChild(shopButton);

                cartItemsContainer.appendChild(emptyCart);
            } else {
                // Create cart items
                cart.forEach((item, index) => {
                    const cartItem = createCartItemElement(item, index);
                    cartItemsContainer.appendChild(cartItem);
                });
            }

            // Cart footer with total and checkout button
            const cartFooter = document.createElement('div');
            cartFooter.className = 'cart-footer';

            const cartTotal = document.createElement('div');
            cartTotal.className = 'cart-total';
            cartTotal.innerHTML = `<span>Total:</span> <span class="total-amount">₱${calculateCartTotal().toFixed(2)}</span>`;

            const checkoutButton = document.createElement('button');
            checkoutButton.className = 'checkout-button';
            checkoutButton.textContent = 'Proceed to Checkout';
            checkoutButton.addEventListener('click', proceedToCheckout);

            // Add a "Continue Shopping" button
            const continueShoppingBtn = document.createElement('button');
            continueShoppingBtn.className = 'continue-shopping';
            continueShoppingBtn.textContent = 'Continue Shopping';
            continueShoppingBtn.addEventListener('click', closeCartModal);

            cartFooter.appendChild(cartTotal);
            if (cart.length > 0) {
                cartFooter.appendChild(checkoutButton);
            }
            cartFooter.appendChild(continueShoppingBtn);

            // Add everything to the modal
            modalContent.appendChild(closeButton);
            modalContent.appendChild(cartHeader);
            modalContent.appendChild(cartItemsContainer);
            modalContent.appendChild(cartFooter);
            modalOverlay.appendChild(modalContent);

            // Add the modal to the page
            document.body.appendChild(modalOverlay);

            // Add modal-open class to body to prevent scrolling
            document.body.classList.add('modal-open');

            // Show the modal
            modalOverlay.style.display = 'flex';

            // Close modal when clicking outside the content
            modalOverlay.addEventListener('click', function (e) {
                if (e.target === modalOverlay) {
                    closeCartModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeCartModal();
                }
            });
        }

        // Function to create a cart item element
        function createCartItemElement(item, index) {
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.dataset.index = index;

            // Item image
            const itemImage = document.createElement('div');
            itemImage.className = 'item-image';

            const img = document.createElement('img');
            img.src = item.image || 'placeholder.jpg';
            img.alt = item.name;
            itemImage.appendChild(img);

            // Item details
            const itemDetails = document.createElement('div');
            itemDetails.className = 'item-details';

            const itemName = document.createElement('h3');
            itemName.className = 'item-name';
            itemName.textContent = item.name;

            const itemAttrs = document.createElement('div');
            itemAttrs.className = 'item-attrs';

            if (item.size) {
                const sizeSpan = document.createElement('span');
                sizeSpan.className = 'item-size';
                sizeSpan.textContent = `Size: ${item.size}`;
                itemAttrs.appendChild(sizeSpan);
            }

            if (item.color) {
                const colorSpan = document.createElement('span');
                colorSpan.className = 'item-color';
                colorSpan.textContent = `Color: ${item.color}`;
                itemAttrs.appendChild(colorSpan);
            }

            const itemPrice = document.createElement('div');
            itemPrice.className = 'item-price';
            itemPrice.textContent = `₱${item.price.toFixed(2)}`;

            // Item quantity
            const itemQuantity = document.createElement('div');
            itemQuantity.className = 'item-quantity';

            const decreaseBtn = document.createElement('button');
            decreaseBtn.className = 'quantity-btn';
            decreaseBtn.textContent = '-';
            decreaseBtn.addEventListener('click', function () {
                updateCartItemQuantity(index, -1);
            });

            const quantityInput = document.createElement('input');
            quantityInput.type = 'text';
            quantityInput.className = 'quantity-input';
            quantityInput.value = item.quantity;
            quantityInput.addEventListener('change', function () {
                const newQuantity = parseInt(this.value);
                if (newQuantity < 1 || isNaN(newQuantity)) {
                    this.value = 1;
                    updateCartItemQuantity(index, 0, 1);
                } else {
                    updateCartItemQuantity(index, 0, newQuantity);
                }
            });

            const increaseBtn = document.createElement('button');
            increaseBtn.className = 'quantity-btn';
            increaseBtn.textContent = '+';
            increaseBtn.addEventListener('click', function () {
                updateCartItemQuantity(index, 1);
            });

            itemQuantity.appendChild(decreaseBtn);
            itemQuantity.appendChild(quantityInput);
            itemQuantity.appendChild(increaseBtn);

            // Item subtotal
            const itemSubtotal = document.createElement('div');
            itemSubtotal.className = 'item-subtotal';
            itemSubtotal.textContent = `₱${item.subtotal.toFixed(2)}`;

            // Remove button
            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-item';
            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
            removeBtn.addEventListener('click', function () {
                removeCartItem(index);
            });

            // Append all elements
            itemDetails.appendChild(itemName);
            itemDetails.appendChild(itemAttrs);
            itemDetails.appendChild(itemPrice);

            cartItem.appendChild(itemImage);
            cartItem.appendChild(itemDetails);
            cartItem.appendChild(itemQuantity);
            cartItem.appendChild(itemSubtotal);
            cartItem.appendChild(removeBtn);

            return cartItem;
        }

        // Function to update cart item quantity
        function updateCartItemQuantity(index, change, newQuantity = null) {
            if (index < 0 || index >= cart.length) return;

            const item = cart[index];
            const product = products.find(p => p.id === item.id);

            if (!product) {
                console.error(`Product with ID ${item.id} not found`);
                return;
            }

            // If newQuantity is provided, use it; otherwise calculate based on change
            let updatedQuantity = newQuantity !== null ? newQuantity : item.quantity + change;

            // Ensure quantity is within bounds (1 to max stock)
            if (updatedQuantity < 1) {
                updatedQuantity = 1;
            } else if (updatedQuantity > parseInt(product.stock)) {
                alert(`Sorry, only ${product.stock} items in stock.`);
                updatedQuantity = parseInt(product.stock);
            }

            // Update the cart item
            item.quantity = updatedQuantity;
            item.subtotal = item.price * updatedQuantity;

            // Update the DOM
            const cartModal = document.getElementById('cart-modal');
            if (cartModal) {
                const quantityInput = cartModal.querySelector(`.cart-item[data-index="${index}"] .quantity-input`);
                if (quantityInput) {
                    quantityInput.value = updatedQuantity;
                }

                const subtotalElement = cartModal.querySelector(`.cart-item[data-index="${index}"] .item-subtotal`);
                if (subtotalElement) {
                    subtotalElement.textContent = `₱${item.subtotal.toFixed(2)}`;
                }

                // Update total
                const totalElement = cartModal.querySelector('.total-amount');
                if (totalElement) {
                    totalElement.textContent = `₱${calculateCartTotal().toFixed(2)}`;
                }
            }

            // Save cart
            saveCart();
            updateCartCount();
        }

        // Function to remove item from cart
        function removeCartItem(index) {
            if (index < 0 || index >= cart.length) return;

            // Remove the item
            cart.splice(index, 1);

            // Save cart
            saveCart();
            updateCartCount();

            // Refresh cart modal
            closeCartModal();
            showCartModal();
        }

        // Function to close cart modal
        function closeCartModal() {
            const modalOverlay = document.getElementById('cart-modal');
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                modalOverlay.remove();
            }
        }

        // Function to proceed to checkout
        function proceedToCheckout() {
            // Check if user is logged in
            if (!isUserLoggedIn()) {
                // Redirect to login page
                alert("Please login to continue with checkout");
                window.location.href = "sign-in.php";
                return;
            }

            // Redirect to checkout page
            window.location.href = "checkout.php";
        }

        // Function to check if user is logged in
        function isUserLoggedIn() {
            // This is a placeholder function
            // In a real implementation, this would check PHP session or other authentication mechanism
            // For now, we'll use a simple check for demonstration
            // In a real scenario, this would be handled server-side in PHP
            return document.body.classList.contains('logged-in') || sessionStorage.getItem('user_logged_in') === 'true';
        }

        // Function to add cart modal styles
     function addCartModalStyles() {
            const styleElement = document.createElement('style');
            styleElement.id = 'cart-modal-styles';
            styleElement.textContent = `
        .cart-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .cart-modal-content {
            background-color: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 20px;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        .cart-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .cart-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .cart-items {
            margin-bottom: 20px;
            max-height: 50vh;
            overflow-y: auto;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-cart i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 10px;
        }
        
        .empty-cart p {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 80px 2fr 1fr 1fr auto;
            grid-gap: 10px;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .item-image img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .item-name {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        
        .item-attrs {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .item-attrs span {
            margin-right: 10px;
        }
        
        .item-price {
            font-size: 14px;
            color: #333;
        }
        
        .item-quantity {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: none;
            cursor: pointer;
        }
        
        .quantity-input {
            width: 40px;
            height: 30px;
            border: 1px solid #ddd;
            text-align: center;
            margin: 0 5px;
        }
        
        .item-subtotal {
            font-weight: bold;
        }
        
        .remove-item {
            background: none;
            border: none;
            color: #ff4d4d;
            cursor: pointer;
            font-size: 16px;
        }
        
        .cart-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .cart-total {
            font-size: 18px;
            font-weight: bold;
        }
        
        .total-amount {
            color: #e91e63;
        }
        
        .checkout-button {
            background-color: #e91e63;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .continue-shopping {
            background-color: #333;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            margin-left: 10px;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 70px 1fr;
                grid-template-rows: auto auto auto;
                grid-gap: 5px;
                padding: 15px 0;
            }
            
            .item-image {
                grid-row: 1 / 4;
            }
            
            .item-details {
                grid-column: 2;
                grid-row: 1;
            }
            
            .item-quantity {
                grid-column: 2;
                grid-row: 2;
            }
            
            .item-subtotal, .remove-item {
                grid-column: 2;
                grid-row: 3;
                display: inline-block;
                margin-right: 10px;
            }
            
            .cart-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .checkout-button, .continue-shopping {
                width: 100%;
                margin-top: 10px;
                margin-left: 0;
            }
        }
    `;
            document.head.appendChild(styleElement);
        }

        // Function to calculate cart total and return the value
        function calculateCartTotal() {
            cartTotal = cart.reduce((total, item) => total + item.subtotal, 0);
            return cartTotal;
        }

        // Function to communicate with PHP backend
        function updateCartSession(itemCount) {
            // This function would normally use fetch or XMLHttpRequest to update PHP session
            // For demonstration purposes, we're just storing in sessionStorage
            sessionStorage.setItem('cart_count', itemCount);

            // In a real implementation, you would have an AJAX call like this:
            /*
            fetch('update_cart_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_count: itemCount,
                    cart_items: cart
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Cart session updated:', data);
            })
            .catch(error => {
                console.error('Error updating cart session:', error);
            });
            */
        }
    </script>





</body>

</html>