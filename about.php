<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="images/hf.png">
    <title>About Us - HirayaFit</title>   <link rel="icon" href="images/hf.png">
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

        /*about*/
        
        .about-section {
            padding: 80px 0;
        }
        
        .about-section .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .about-content h2 {
            font-size: 32px;
            margin-bottom: 25px;
            color: var(--primary);
            position: relative;
        }
        
        .about-content h2:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 60px;
            height: 3px;
            background-color: var(--secondary);
        }
        
        .about-content p {
            font-size: 16px;
            line-height: 1.7;
            color: #666;
            margin-bottom: 20px;
        }
        
        .about-image {
            position: relative;
        }
        
        .about-image img {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .about-stats {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 10px;
        }
        
        .stat-title {
            font-size: 16px;
            color: #777;
        }
        
        .mission-section {
            background-color: #f9f9f9;
            padding: 80px 0;
        }
        
        .mission-container {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .mission-container h2 {
            font-size: 32px;
            margin-bottom: 25px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }
        
        .mission-container h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            width: 60px;
            height: 3px;
            background-color: var(--secondary);
            transform: translateX(-50%);
        }
        
        .mission-container p {
            font-size: 18px;
            line-height: 1.7;
            color: #666;
            margin-bottom: 30px;
        }
        
        .mission-values {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 50px;
        }
        
        .value-item {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .value-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .value-icon {
            font-size: 36px;
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        .value-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .value-description {
            font-size: 15px;
            line-height: 1.6;
            color: #777;
        }
        
        .team-section {
            padding: 80px 0;
        }
        
        .team-container {
            text-align: center;
        }
        
        .team-container h2 {
            font-size: 32px;
            margin-bottom: 25px;
            color: var(--primary);
            position: relative;
            display: inline-block;
        }
        
        .team-container h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            width: 60px;
            height: 3px;
            background-color: var(--secondary);
            transform: translateX(-50%);
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 50px;
        }
        
        .team-member {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-10px);
        }
        
        .member-image {
            height: 250px;
            overflow: hidden;
        }
        
        .member-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .team-member:hover .member-image img {
            transform: scale(1.1);
        }
        
        .member-info {
            padding: 20px;
        }
        
        .member-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .member-position {
            font-size: 14px;
            color: var(--secondary);
            margin-bottom: 15px;
        }
        
        .member-social {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .member-social a {
            color: #777;
            transition: color 0.3s ease;
        }
        
        .member-social a:hover {
            color: var(--secondary);
        }
        
        .cta-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url("/api/placeholder/1600/500") no-repeat center center;
            background-size: cover;
            padding: 100px 0;
            text-align: center;
            color: white;
        }
        
        .cta-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .cta-container h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .cta-container p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn:hover {
            background-color: #005fa8;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid white;
            margin-left: 15px;
        }
        
        .btn-outline:hover {
            background-color: white;
            color: var(--primary);
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
            .about-section .container {
                grid-template-columns: 1fr;
            }
            
            .mission-values {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-bar {
                max-width: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .about-stats {
                grid-template-columns: 1fr;
            }
            
            .mission-values {
                grid-template-columns: 1fr;
            }
            
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
    
    <!-- Navigation -->
    <!-- Simplified Navigation -->
    <nav class="main-nav" id="mainNav">
        <a href="index.php">HOME</a>  
        <a href="about.php" class="active">ABOUT</a>
        <a href="contact.php">CONTACT</a>
    </nav>
    
   <!-- Page Header -->
<section class="page-header">
    <img src="images/banner.jpg" alt="About Us Header Image">
    <div class="header-overlay"></div>
    <div class="container header-content">
        <h1>ABOUT US</h1>
        <p>COMMITTED TO EXCELLENCE IN SPORTS INNOVATION</p>
    </div>
</section>
    
    <!-- Breadcrumbs -->
    <div class="breadcrumbs">
        <div class="container">
            <a href="index.php">Home</a>
            <span class="separator">></span>
            <span>About Us</span>
        </div>
    </div>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <h2>Our Story</h2>
                <p>Founded in 2018, HirayaFit was born from a passion for creating performance activewear that doesn't compromise on style or sustainability. The name "Hiraya" comes from an ancient Filipino word that means "the fruit of one's hopes, dreams, and aspirations" – perfectly embodying our mission to help people achieve their fitness goals while looking and feeling their best.</p>
                <p>What began as a small startup with just three designs has now grown into a comprehensive collection of premium activewear trusted by fitness enthusiasts and athletes around the world. Our founder, Leiumar Sayco, a former professional athlete and fitness trainer, noticed a gap in the market for activewear that was both functional and fashionable, and set out to create solutions that would empower people in their fitness journeys.</p>
                <p>Today, HirayaFit continues to innovate with cutting-edge fabrics and thoughtful designs that support active lifestyles while maintaining our commitment to ethical manufacturing and environmental responsibility.</p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-number">1M+</div>
                        <div class="stat-title">Happy Customers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">300+</div>
                        <div class="stat-title">Product Designs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">42</div>
                        <div class="stat-title">Countries Shipped To</div>
                    </div>
                </div>
            </div>
            
            <div class="about-image">
                <img src="images/leilaikim.jpg" alt="HirayaFit Team">
            </div>
        </div>
    </section>
    
    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container mission-container">
            <h2>Our Mission & Values</h2>
            <p>At HirayaFit, we believe that the right activewear can transform how you feel during workouts and beyond. Our mission is to create innovative, high-performance clothing that inspires confidence, supports movement, and promotes sustainability in the fitness industry.</p>
            
            <div class="mission-values">
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3 class="value-title">Performance</h3>
                    <p class="value-description">We rigorously test all our products to ensure they meet the demands of intense workouts while providing maximum comfort and mobility.</p>
                </div>
                
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3 class="value-title">Sustainability</h3>
                    <p class="value-description">We're committed to reducing our environmental footprint by using recycled materials and implementing eco-friendly manufacturing processes.</p>
                </div>
                
                <div class="value-item">
                    <div class="value-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 class="value-title">Design</h3>
                    <p class="value-description">We believe that function and style should coexist, creating activewear that transitions seamlessly from workouts to casual wear.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Team Section -->
    <section class="team-section">
        <div class="container team-container">
            <h2>Meet Our Team</h2>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <img src="images/leiumarsayco.jpg" alt="Leiumar Sayco">
                    </div>
                    <div class="member-info">
                        <h3 class="member-name">Leiumar Sayco</h3>
                        <div class="member-position">Founder & CEO</div>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="/api/placeholder/400/400" alt="Elaiza">
                    </div>
                    <div class="member-info">
                        <h3 class="member-name">Elaiza Mae Rodriguez</h3>
                        <div class="member-position">Head of Design</div>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="/api/placeholder/400/400" alt="Kim">
                    </div>
                    <div class="member-info">
                        <h3 class="member-name">Kim Pascual</h3>
                        <div class="member-position">Product Development</div>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="/api/placeholder/400/400" alt="Regiie">
                    </div>
                    <div class="member-info">
                        <h3 class="member-name">Loraine Castro</h3>
                        <div class="member-position">Marketing Director</div>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container cta-container">
            <h2>Join the HirayaFit Community</h2>
            <p>Become part of our growing community of fitness enthusiasts who believe in the power of great activewear to transform their fitness journey.</p>
            
            <a href="sign-up.php" class="btn btn-outline">Join Now</a>
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
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">Size Guide</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
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
     <!-- Script for mobile menu and account dropdown functionality -->
     <script>
    // Check if scripts are being loaded
    console.log('Script tags are being processed');
</script>

<script src="js/home.js"></script>
<script>console.log('After home.js');</script>

<script src="js/search.js"></script>
<script>console.log('After search.js');</script>


<script src="js/cart.js"></script>
<script>console.log('After cart.js');</script>
    


<!--Nav Filter -->
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
   <style>
    /*product results*/
/* Category Results Section Styles */
:root {
    --primary: #111111;
    --secondary: #0071c5;
    --accent: #e5e5e5;
    --light: #ffffff;
    --dark: #111111;
    --grey: #767676;
}
/* Search Results Section */
.search-results-section {
display: block;
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

/* Results Grid - Modified to show exactly 4 items per row */
.results-grid {
display: grid;
grid-template-columns: repeat(4, 1fr);
gap: 20px;
}

.category-results-section {
    padding: 80px 0;
    background-color: var(--accent);
    display: none;
}

.category-results-section h2 {
    text-align: center;
font-size: 28px;
margin-bottom: 30px;
color: #333;
position: relative;
}

.category-results-section h2:after {
    content: "";
display: block;
width: 60px;
height: 3px;
background-color:  var(--secondary);
margin: 15px auto 0;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    margin-top: 40px;
    max-width: 1280px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

/* Responsive grid adjustments */
@media (max-width: 1200px) {
    .results-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 900px) {
    .results-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
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

.sale-badge {
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

.product-actions {
    position: absolute;
    bottom: -50px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 12px;
    padding: 12px;
    background-color: rgba(255, 255, 255, 0.95);
    transition: bottom 0.3s ease;
}

.product-card:hover .product-actions {
    bottom: 0;
}

.product-actions button {
    background-color: white;
    border: 1px solid #eee;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
}

.product-actions button:hover {
    background-color: var(--secondary);
    border-color: var(--secondary);
    color: var(--light);
    transform: translateY(-2px);
}

.product-info {
    padding: 22px;
    text-align: center;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.product-info h3 {
    margin-top: 0;
    margin-bottom: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary);
    line-height: 1.4;
}

.price {
    color: #e74c3c;
    font-weight: bold;
    font-size: 1.25rem;
    margin-bottom: 12px;
}

.category {
    color: var(--grey);
    font-size: 0.85rem;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-rating {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-bottom: 18px;
}

.stars {
    color: #ffaa00;
    font-size: 0.9rem;
}

.rating-text {
    color: var(--grey);
    font-size: 0.85rem;
}

.product-buttons {
    margin-top: auto;
}

.add-to-cart, .view-details {
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

.view-details {
    background-color: #f5f5f5;
    color: #333;
    margin-bottom: 8px;
    border: 1px solid #eee;
}

.add-to-cart:hover {
    background-color: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 113, 197, 0.3);
}

.view-details:hover {
    background-color: #ebebeb;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.no-results {
    text-align: center;
    font-size: 1.3rem;
    color: #666;
    padding: 40px 0;
    width: 100%;
    grid-column: 1 / -1;
}

/* Modal Styles for Quick View */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.75);
    z-index: 1000;
    overflow-y: auto;
    backdrop-filter: blur(5px);
}

.modal-content {
    position: relative;
    background-color: white;
    margin: 5% auto;
    padding: 0;
    width: 90%;
    max-width: 1100px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
}

.close-modal {
    position: absolute;
    top: 18px;
    right: 22px;
    font-size: 28px;
    font-weight: bold;
    color: #333;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-modal:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: var(--secondary);
}

.quick-view-container {
    padding: 30px;
}

.quick-view-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

@media (max-width: 900px) {
    .quick-view-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

.quick-view-image {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
}

.quick-view-image img {
    max-width: 100%;
    max-height: 500px;
    object-fit: contain;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.quick-view-details h2 {
    margin-top: 0;
    font-size: 2rem;
    color: #333;
    line-height: 1.3;
}

.quick-view-details .quick-price {
    font-size: 1.5rem;
    color: var(--secondary);
    font-weight: bold;
    margin: 15px 0;
}

.quick-view-details .description {
    color: #555;
    line-height: 1.7;
    margin: 20px 0;
    font-size: 1rem;
}

.product-options {
    margin: 25px 0;
}

.product-options h4 {
    margin-bottom: 12px;
    color: #333;
    font-size: 1.1rem;
}

.option-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.size-btn {
    padding: 10px 15px;
    border: 1px solid #ddd;
    background-color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 4px;
    font-weight: 500;
}

.size-btn:hover, .size-btn.active {
    background-color: var(--primary);
    color: var(--light);
    border-color: var(--primary);
}

.color-btn {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: 2px solid #ddd;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    text-indent: -9999px;
    transition: all 0.2s ease;
}

.color-btn:hover, .color-btn.active {
    border: 3px solid var(--primary);
    transform: scale(1.1);
}

.quantity-section {
    margin: 25px 0;
}

.quantity-section h4 {
    margin-bottom: 12px;
    color: #333;
    font-size: 1.1rem;
}

.quantity-control {
    display: flex;
    align-items: center;
    max-width: 140px;
    border-radius: 5px;
    overflow: hidden;
    border: 1px solid #ddd;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    background-color: #f5f5f5;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.quantity-btn:hover {
    background-color: #e0e0e0;
}

.quantity-input {
    width: 60px;
    height: 40px;
    text-align: center;
    border: none;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    font-size: 1rem;
}

.add-to-cart-modal {
    background-color: var(--secondary);
    color: var(--light);
    border: none;
    padding: 14px 25px;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 25px;
    width: 100%;
    max-width: 350px;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.add-to-cart-modal:hover {
    background-color: #005da3; /* Slightly darker variant of secondary */
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 113, 197, 0.3);
}

.add-to-cart-modal i {
    font-size: 1.2rem;
}

/* Navigation Styles for Category Links */
.main-nav a[data-category] {
    position: relative;
}

.main-nav a[data-category]:after {
    content: '';
    position: absolute;
    width: 0;
    height: 3px;
    bottom: -3px;
    left: 50%;
    background-color: var(--secondary);
    transition: all 0.3s ease;
    transform: translateX(-50%);
}

.main-nav a[data-category]:hover:after {
    width: 100%;
}

.main-nav a[data-category].active {
    color: var(--secondary);
    font-weight: 600;
}

.main-nav a[data-category].active:after {
    width: 100%;
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
 </body>
 </html>