<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Premium Activewear</title>    <link rel="icon" href="images/hf.png">
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
       /* About Page Specific Styles */
.page-header {
    position: relative;
    height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    overflow: hidden;
}

.page-header img {
    position: absolute;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 1;
}

.header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 2;
}

.header-content {
    position: relative;
    width: 100%;
    text-align: center;
    z-index: 3;
    padding: 0 20px;
}

.page-header h1 {
    font-size: 2.8rem;
    margin-bottom: 20px;
    font-weight: 700;
    letter-spacing: -0.5px;
    text-transform: uppercase;
}

.page-header p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    line-height: 1.6;
    opacity: 0.9;
    text-transform: uppercase;
}

.breadcrumbs {
    background-color: #f5f5f5;
    padding: 10px 0;
}
        .breadcrumbs .container {
            display: flex;
            align-items: center;
        }
         
        .breadcrumbs a, .breadcrumbs span {
            font-size: 14px;
            color: #777;
            text-decoration: none;
        }
         
        .breadcrumbs a:hover {
            color: var(--secondary);
        }
         
        .breadcrumbs .separator {
            margin: 0 10px;
            color: #ccc;
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

       /* Search Results Section */
.search-results-section {
    display: none;
    padding: 60px 0;
    background-color: #f9f9f9;
    margin-top: 40px;
    border-top: 1px solid #e0e0e0;
}

.search-results-section .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.search-results-section h2 {
    text-align: center;
    font-size: 28px;
    margin-bottom: 30px;
    color: #333;
    position: relative;
}

.search-results-section h2:after {
    content: "";
    display: block;
    width: 60px;
    height: 3px;
    background-color: #3498db;
    margin: 15px auto 0;
}

.no-results {
    text-align: center;
    font-size: 18px;
    color: #777;
    padding: 40px 0;
}

/* Results Grid */
.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

/* Product Card */
.product-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* Product Image Container */
.product-image {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

/* Sale Badge */
.sale-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: #e74c3c;
    color: white;
    padding: 5px 10px;
    font-size: 12px;
    font-weight: bold;
    border-radius: 4px;
    text-transform: uppercase;
}

/* Product Info */
.product-info {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-info h3 {
    font-size: 18px;
    margin: 0 0 10px;
    color: #333;
    line-height: 1.4;
}

.product-info .price {
    font-weight: bold;
    font-size: 20px;
    color: #e74c3c;
    margin: 0 0 8px;
}

.product-info .category {
    color: #7f8c8d;
    font-size: 14px;
    margin: 0 0 15px;
}

/* Star Rating */
.product-rating {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.stars {
    color: #f39c12;
    margin-right: 5px;
}

.rating-text {
    color: #7f8c8d;
    font-size: 14px;
}

/* Add to Cart Button */
.add-to-cart {
    margin-top: auto;
    padding: 12px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
    text-transform: uppercase;
    font-size: 14px;
}

.add-to-cart:hover {
    background-color: #2980b9;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .results-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .product-image {
        height: 180px;
    }
}

@media (max-width: 480px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .product-image {
        height: 200px;
    }
}

/* Sale Badge */
.sale-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background-color: #e74c3c;
    color: white;
    padding: 5px 12px;
    font-size: 13px;
    font-weight: bold;
    border-radius: 4px;
    text-transform: uppercase;
    z-index: 2;
}

/* Product Info */
.product-info {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-info h3 {
    font-size: 18px;
    margin: 0 0 10px;
    color: #333;
    font-weight: 600;
    line-height: 1.4;
}

.product-info .price {
    font-weight: bold;
    font-size: 20px;
    color: #e74c3c;
    margin: 0 0 12px;
}

/* Star Rating */
.product-rating {
    margin-bottom: 18px;
    display: flex;
    align-items: center;
}

.stars {
    color: #f39c12;
    margin-right: 5px;
}

.rating-text {
    color: #7f8c8d;
    font-size: 14px;
}

/* Add to Cart Button */
.add-to-cart {
    margin-top: auto;
    padding: 12px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
    text-transform: uppercase;
    font-size: 14px;
    letter-spacing: 0.5px;
}

.add-to-cart:hover {
    background-color: #2980b9;
}

/* Quick View Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 50px auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 1000px;
    position: relative;
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from {opacity: 0; transform: translateY(-20px);}
    to {opacity: 1; transform: translateY(0);}
}

.close-modal {
    color: #aaa;
    float: right;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 20px;
    top: 10px;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #333;
}

/* Quick View Layout */
.quick-view-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.quick-view-image {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f9f9f9;
    border-radius: 10px;
    overflow: hidden;
}

.quick-view-image img {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
}

.quick-view-details h2 {
    font-size: 24px;
    margin: 0 0 15px;
    color: #333;
    font-weight: 600;
}

.quick-view-details .price {
    font-size: 26px;
    font-weight: bold;
    color: #e74c3c;
    margin-bottom: 15px;
}

.quick-view-details .description {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
}

/* Product Options */
.product-options {
    margin-bottom: 20px;
}

.size-options, .color-options {
    margin-bottom: 15px;
}

.product-options h4 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #333;
}

.option-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.size-btn {
    padding: 8px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.size-btn:hover, .size-btn.selected {
    background-color: #3498db;
    color: white;
    border-color: #3498db;
}

.color-btn {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 1px solid #ddd;
    cursor: pointer;
    position: relative;
    transition: transform 0.2s ease;
    color: transparent;
    overflow: hidden;
    font-size: 0;
}

.color-btn:hover, .color-btn.selected {
    transform: scale(1.1);
    box-shadow: 0 0 0 2px #3498db;
}

/* Quantity Section */
.quantity-section {
    margin-bottom: 25px;
}

.quantity-control {
    display: flex;
    align-items: center;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: 1px solid #ddd;
    background: white;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.quantity-btn.minus {
    border-radius: 5px 0 0 5px;
}

.quantity-btn.plus {
    border-radius: 0 5px 5px 0;
}

.quantity-btn:hover {
    background-color: #f4f4f4;
}

.quantity-input {
    width: 50px;
    height: 35px;
    border: 1px solid #ddd;
    border-left: none;
    border-right: none;
    text-align: center;
    font-size: 16px;
}

/* Remove arrows from number input */
.quantity-input::-webkit-outer-spin-button,
.quantity-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}


.add-to-cart-modal {
    padding: 15px 25px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: background-color 0.3s ease;
    width: 100%;
}

.add-to-cart-modal:hover {
    background-color: #2980b9;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
}

@media (max-width: 768px) {
    #featured {
        padding: 50px 0;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .section-description {
        font-size: 16px;
        margin-bottom: 30px;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .product-image {
        height: 200px;
    }
    
    .quick-view-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-view-image {
        margin-bottom: 20px;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
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
                <a href="#" class="logo">Hiraya<span>Fit</span></a>
                
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search products...">
                    <button onclick="searchProducts()">Search</button>
                </div>
                
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
    
    <nav class="main-nav" id="mainNav">
        <a href="index.php">HOME</a>
        <a href="usershop.php.php">SHOP</a>
        <a href="men.php" class="active">MEN</a>
        <a href="women.php">WOMEN</a>
        <a href="foot.php">FOOTWEAR</a>
        <a href="acces.php">ACCESSORIES</a>
        <a href="about.php">ABOUT</a>
        <a href="contact.php">CONTACT</a>
    </nav>
    
   <!-- Page Header -->
<section class="page-header">
    <img src="images/banner.jpg" alt="Men's Collection Header Image">
    <div class="header-overlay"></div>
    <div class="container header-content">
        <h1>MEN</h1>
        <p>HIGH-PERFORMANCE GEAR FOR ULTIMATE TRAINING</p>
    </div>
</section>
    
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <div class="container">
            <a href="index.php">Home</a>
            <span class="separator">></span>
            <span>Men</span>
        </div>
    </div>

    <!-- In each page file (men.php, women.php, etc.) -->
<div class="container">
    <!-- This section will be populated by the JavaScript -->
    <section id="categoryProducts" class="product-section">
        <!-- Products will be inserted here -->
    </section>
</div>

   <!-- Place these at the bottom of your body tag -->
<script>
    // Check if scripts are being loaded
    console.log('Script tags are being processed');
</script>

<script src="js/home.js"></script>
<script>console.log('After home.js');</script>

<script src="js/search.js"></script>
<script>console.log('After search.js');</script>

<script src="js/featured.js"></script>
<script>console.log('After featured.js');</script>

<script src="js/details.js"></script>
<script>console.log('After details.js');</script>

<script src="js/cart.js"></script>
<script>console.log('After cart.js');</script>

<script src="js/categoryy.js"></script>
<script>console.log('After categoryy.js');</script>


  
</body>
</html>