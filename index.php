<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Premium Activewear</title> <link rel="icon" href="images/hf.png">
    <link rel="icon" href="images/hf.png">
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

  /* Hero Section */
  .hero {
            position: relative;
            height: 100vh;
            width: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            z-index: 1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 124, 199, 0.2) 50%, rgba(0, 0, 0, 0.8) 100%);
            z-index: 2;
        }

        .hero-content {
            position: relative;
            text-align: center;
            z-index: 3;
            padding: 0 20px;
            max-width: 800px;
            animation: fadeInUp 1.5s ease-out;
        }

        .brand-logo {
            font-size: 25px;
    font-weight: 700;
    color: var(--light);
    text-decoration: none;
    letter-spacing: -0.5px;
        }
        
        .brand-logo span {
            color: var(--secondary);
        }

        .hero h1 {
            font-size: clamp(3rem, 8vw, 6rem);
            color: white;
            margin-bottom: 30px;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 0.9;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            font-weight: 300;
            letter-spacing: 1px;
            line-height: 1.4;
            animation: fadeIn 2s ease-out 1s both;
        }

        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            animation: bounce 2s infinite;
        }

        .scroll-indicator::before {
            content: '';
            display: block;
            width: 1px;
            height: 30px;
            background: rgba(255, 255, 255, 0.5);
            margin: 0 auto 10px;
        }

        .scroll-text {
            font-size: 0.8rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* Floating Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        .floating-element {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 30%;
            right: 25%;
            animation-delay: 4s;
        }

        /* Stats Bar */
        .stats-bar {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 60px;
            z-index: 3;
            animation: fadeInUp 2s ease-out 1.5s both;
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
            margin-top: 5px;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            40% {
                transform: translateX(-50%) translateY(-10px);
            }
            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero {
                height: 100vh;
            }
            
            .stats-bar {
                gap: 30px;
                bottom: 120px;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .floating-element {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .stats-bar {
                flex-direction: column;
                gap: 20px;
            }
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


/* Featured Categories Section */
.featured-categories {
    padding: 80px 0;
    background-color: var(--light);
}

.section-title {
    text-align: center;
    font-size: 32px;
    margin-bottom: 15px;
    color: var(--primary);
    font-weight: 700;
    letter-spacing: -0.5px;
}

.section-description {
    text-align: center;
    font-size: 18px;
    color: var(--grey);
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.category-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-10px);
}

.category-image {
    position: relative;
    height: 300px;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
    color: white;
    text-align: center;
}

.category-overlay h3 {
    font-size: 20px;
    margin-bottom: 10px;
    font-weight: 600;
}

.category-btn {
    display: inline-block;
    padding: 8px 20px;
    background-color: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    transition: background-color 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.category-card:hover .category-btn {
    opacity: 1;
    transform: translateY(0);
}

.category-btn:hover {
    background-color: #005fa8;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 992px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .featured-categories {
        padding: 60px 0;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .section-description {
        font-size: 16px;
        margin-bottom: 30px;
    }
    
    .category-image {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-image {
        height: 200px;
    }
}




/* Featured Categories Section na 4 pic */
.featured-categories {
    padding: 80px 0;
    background-color: var(--light);
}

.section-title {
    text-align: center;
    font-size: 32px;
    margin-bottom: 15px;
    color: var(--primary);
    font-weight: 700;
    letter-spacing: -0.5px;
}

.section-description {
    text-align: center;
    font-size: 18px;
    color: var(--grey);
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.category-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-10px);
}

.category-image {
    position: relative;
    height: 300px;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
    color: white;
    text-align: center;
}

.category-overlay h3 {
    font-size: 20px;
    margin-bottom: 10px;
    font-weight: 600;
}

.category-btn {
    display: inline-block;
    padding: 8px 20px;
    background-color: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    transition: background-color 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.category-card:hover .category-btn {
    opacity: 1;
    transform: translateY(0);
}

.category-btn:hover {
    background-color: #005fa8;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 992px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .featured-categories {
        padding: 60px 0;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .section-description {
        font-size: 16px;
        margin-bottom: 30px;
    }
    
    .category-image {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-image {
        height: 200px;
    }
}


/* Featured Categories Section */
.featured-categories {
    padding: 80px 0;
    background-color: var(--light);
}

.section-title {
    text-align: center;
    font-size: 32px;
    margin-bottom: 15px;
    color: var(--primary);
    font-weight: 700;
    letter-spacing: -0.5px;
}

.section-description {
    text-align: center;
    font-size: 18px;
    color: var(--grey);
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.category-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-10px);
}

.category-image {
    position: relative;
    height: 300px;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
    color: white;
    text-align: center;
}

.category-overlay h3 {
    font-size: 20px;
    margin-bottom: 10px;
    font-weight: 600;
}

.category-btn {
    display: inline-block;
    padding: 8px 20px;
    background-color: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    transition: background-color 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.category-card:hover .category-btn {
    opacity: 1;
    transform: translateY(0);
}

.category-btn:hover {
    background-color: #005fa8;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 992px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .featured-categories {
        padding: 60px 0;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .section-description {
        font-size: 16px;
        margin-bottom: 30px;
    }
    
    .category-image {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-image {
        height: 200px;
    }
}




/* Featured Categories Section na 4 pic */
.featured-categories {
    padding: 80px 0;
    background-color: var(--light);
}

.section-title {
    text-align: center;
    font-size: 32px;
    margin-bottom: 15px;
    color: var(--primary);
    font-weight: 700;
    letter-spacing: -0.5px;
}

.section-description {
    text-align: center;
    font-size: 18px;
    color: var(--grey);
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.category-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: translateY(-10px);
}

.category-image {
    position: relative;
    height: 300px;
    overflow: hidden;
}

.category-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.category-card:hover .category-image img {
    transform: scale(1.1);
}

.category-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0));
    color: white;
    text-align: center;
}

.category-overlay h3 {
    font-size: 20px;
    margin-bottom: 10px;
    font-weight: 600;
}

.category-btn {
    display: inline-block;
    padding: 8px 20px;
    background-color: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    transition: background-color 0.3s ease;
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.category-card:hover .category-btn {
    opacity: 1;
    transform: translateY(0);
}

.category-btn:hover {
    background-color: #005fa8;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 992px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .featured-categories {
        padding: 60px 0;
    }
    
    .section-title {
        font-size: 28px;
    }
    
    .section-description {
        font-size: 16px;
        margin-bottom: 30px;
    }
    
    .category-image {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .category-image {
        height: 200px;
    }
}

 /* Featured Collections Section */
 .featured-collections {
    padding: 60px 0;
    background-color: var(--light);
}

.featured-collections .section-title {
    text-align: center;
    font-size: 28px;
    margin-bottom: 10px;
    color: var(--dark);
    position: relative;
}

.featured-collections .section-description {
    text-align: center;
    font-size: 16px;
    color: var(--grey);
    margin-bottom: 40px;
}

.collections-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.collection-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.collection-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.collection-image {
    position: relative;
    height: 400px;
    overflow: hidden;
}

.collection-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.collection-item:hover .collection-image img {
    transform: scale(1.05);
}

.collection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.6));
    display: flex;
    align-items: flex-end;
    padding: 30px;
}

.collection-content {
    color: white;
    max-width: 80%;
}

.collection-content h3 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.collection-content p {
    font-size: 16px;
    margin-bottom: 20px;
    opacity: 0.9;
}

.collection-content .btn {
    display: inline-block;
    padding: 12px 28px;
    background-color: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.5px;
}

.collection-content .btn:hover {
    background-color: #005fa8;
}

/* Responsive styles */
@media (max-width: 992px) {
    .collection-image {
        height: 350px;
    }
}

@media (max-width: 768px) {
    .collections-wrapper {
        grid-template-columns: 1fr;
    }
    
    .collection-image {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .collection-image {
        height: 250px;
    }
    
    .collection-content h3 {
        font-size: 20px;
    }
}


/*men women*/
 /* Featured Collections Section */
 .featured-collections {
    padding: 60px 0;
    background-color: var(--light);
}

.featured-collections .section-title {
    text-align: center;
    font-size: 28px;
    margin-bottom: 10px;
    color: var(--dark);
    position: relative;
}

.featured-collections .section-description {
    text-align: center;
    font-size: 16px;
    color: var(--grey);
    margin-bottom: 40px;
}

.collections-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.collection-item {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.collection-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.collection-image {
    position: relative;
    height: 400px;
    overflow: hidden;
}

.collection-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.collection-item:hover .collection-image img {
    transform: scale(1.05);
}

.collection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.6));
    display: flex;
    align-items: flex-end;
    padding: 30px;
}

.collection-content {
    color: white;
    max-width: 80%;
}

.collection-content h3 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 10px;
}

.collection-content p {
    font-size: 16px;
    margin-bottom: 20px;
    opacity: 0.9;
}

.collection-content .btn {
    display: inline-block;
    padding: 12px 28px;
    background-color: var(--secondary);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.5px;
}

.collection-content .btn:hover {
    background-color: #005fa8;
}

/* Responsive styles */
@media (max-width: 992px) {
    .collection-image {
        height: 350px;
    }
}

@media (max-width: 768px) {
    .collections-wrapper {
        grid-template-columns: 1fr;
    }
    
    .collection-image {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .collection-image {
        height: 250px;
    }
    
    .collection-content h3 {
        font-size: 20px;
    }
}

/*abouts*/
/*abouts contact*/
.section-title {
    text-align: center;
    margin-bottom: 40px;
}

.section-title h2 {
    font-size: 32px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 15px;
    position: relative;
    display: inline-block;
}

.section-title h2:after {
    content: '';
    position: absolute;
    width: 60px;
    height: 3px;
    background-color: var(--secondary);
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
}

.section-title p {
    font-size: 16px;
    color: #777;
    max-width: 700px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .section-title h2 {
        font-size: 28px;
    }
    
    .section-title p {
        font-size: 14px;
    }
}
/* Shop Promotion Section */
.shop-promo {
    padding: 60px 0;
    background-color: #f9f9f9;
}

.shop-promo-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.shop-promo-left {
    height: 100%;
}

.shop-promo-right {
    display: grid;
    grid-template-rows: 1fr 1fr;
    gap: 20px;
}

.promo-card {
    background-color: var(--light);
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    height: 100%;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.promo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.12);
}

.promo-image {
    height: 100%;
}

.promo-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.promo-content {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 25px;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    color: white;
}

.promo-content h3 {
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 5px;
}

.promo-content p {
    font-size: 14px;
    margin-bottom: 10px;
    opacity: 0.9;
}

.promo-link {
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    transition: color 0.3s ease;
}

.promo-link i {
    margin-left: 8px;
    transition: transform 0.3s ease;
}

.promo-link:hover {
    color: var(--secondary);
}

.promo-link:hover i {
    transform: translateX(5px);
}

.promo-highlight {
    background-color: var(--secondary);
    border-radius: 8px;
    padding: 30px;
    height: 100%;
    display: flex;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.promo-highlight:hover {
    transform: translateY(-5px);
}

.highlight-content {
    color: white;
    text-align: center;
    width: 100%;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    background-color: white;
    color: var(--secondary);
    font-size: 12px;
    font-weight: 700;
    border-radius: 4px;
    margin-bottom: 15px;
}

.highlight-content h3 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
}

.highlight-content p {
    font-size: 14px;
    margin-bottom: 20px;
    opacity: 0.9;
}

.highlight-btn {
    display: inline-block;
    padding: 10px 25px;
    background-color: white;
    color: var(--secondary);
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
}

.highlight-btn:hover {
    background-color: var(--dark);
    color: white;
}

/* Container */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Responsive styles */
@media (max-width: 992px) {
    .shop-promo-wrapper {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto;
    }
    
    .shop-promo-left {
        height: 300px;
    }
    
    .shop-promo-right {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr;
    }
}

@media (max-width: 768px) {
    .shop-promo-right {
        grid-template-columns: 1fr;
        grid-template-rows: 300px auto;
    }
    
    .promo-content h3 {
        font-size: 20px;
    }
}

@media (max-width: 480px) {
    .shop-promo-left {
        height: 250px;
    }
    
    .shop-promo-right {
        grid-template-rows: 250px auto;
    }
}


/* Features section ng mga icon*/
.features {
    background-color: white;
    padding: 60px 0; /* Added proper top and bottom padding */
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 30px;
    margin: 0 auto; /* Centers the grid if container is wider */
    max-width: 1200px; /* Optional: limits maximum width */
}

.feature-item {
    text-align: center;
    padding: 30px 20px; /* Increased padding for better spacing */
    border-radius: 8px; /* Optional: adds subtle rounded corners */
    transition: all 0.3s ease; /* Smooth transition for hover effects */
}

.feature-item:hover {
    transform: translateY(-5px); /* Optional: subtle lift effect on hover */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08); /* Optional: adds shadow on hover */
}

.feature-icon {
    font-size: 40px;
    color: var(--secondary);
    margin-bottom: 20px;
    height: 60px; /* Consistent height for icons */
    display: flex;
    align-items: center;
    justify-content: center;
}

.feature-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px; /* Increased spacing between title and text */
}

.feature-text {
    color: var(--grey);
    font-size: 14px;
    line-height: 1.6;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .features {
        padding: 40px 0; /* Reduced padding for mobile */
    }
}


/*cta namay join now*/
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
    
    <section class="hero">
        <!-- Video Background -->
        <video class="hero-video" autoplay loop muted playsinline>
            <source src="images/homevid.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        
        <div class="hero-overlay"></div>
        
        <!-- Floating Decorative Elements -->
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        
        <div class="hero-content">
            <div class="brand-logo">Hiraya<span>Fit</span></div>
            <h1>WHERE<br>PERFORMANCE<br>MEETS STYLE</h1>
            <p class="hero-subtitle">Engineered for athletes. Designed for life.<br>Experience the perfect fusion of innovation and elegance.</p>
        </div>
        
        <!-- Performance Stats 
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number">24/7</span>
                <span class="stat-label">Comfort</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">Performance</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">∞</span>
                <span class="stat-label">Style</span>
            </div>
        </div>-->
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <div class="scroll-text">Explore</div>
        </div>
    </section>

    <script>
        // Add parallax effect to video
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const video = document.querySelector('.hero-video');
            const rate = scrolled * -0.5;
            video.style.transform = `translate(-50%, calc(-50% + ${rate}px))`;
        });

        // Add dynamic text animation
        const title = document.querySelector('.hero h1');
        const words = title.innerHTML.split('<br>');
        title.innerHTML = '';
        
        words.forEach((word, index) => {
            const span = document.createElement('span');
            span.innerHTML = word;
            span.style.display = 'block';
            span.style.animation = `fadeInUp 1s ease-out ${0.5 + index * 0.3}s both`;
            title.appendChild(span);
            if (index < words.length - 1) {
                title.appendChild(document.createElement('br'));
            }
        });
    </script>
    
    
    <!-- Featured Categories Section -->
<section class="featured-categories">
    <div class="container">
        <h2 class="section-title">Shop By Category</h2>
        <p class="section-description">Find your perfect gear from our curated collections</p>
        
        <div class="categories-grid">
            <div class="category-card">
                <div class="category-image">
                    <img src="images/colmen.webp" alt="Men's Activewear">
                    <div class="category-overlay">
                        <h3>Men's Collection</h3>
                        <a href="men.php" class="category-btn">EXPLORE</a>
                    </div>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-image">
                    <img src="images/colwomens.jpg" alt="Women's Activewear">
                    <div class="category-overlay">
                        <h3>Women's Collection</h3>
                        <a href="women.php" class="category-btn">EXPLORE</a>
                    </div>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-image">
                    <img src="images/colfoot.jpg" alt="Footwear">
                    <div class="category-overlay">
                        <h3>Footwear</h3>
                        <a href="foot.php" class="category-btn">EXPLORE</a>
                    </div>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-image">
                    <img src="images/colacc.jpg" alt="Accessories">
                    <div class="category-overlay">
                        <h3>Accessories</h3>
                        <a href="acces.php" class="category-btn">EXPLORE</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Featured Collections Section -->
<section class="featured-collections">
    <div class="container">
        <h2 class="section-title">Featured Collections</h2>
        <p class="section-description">Premium activewear designed for Filipino fitness enthusiasts</p>
        
        <div class="collections-wrapper">
            <div class="collection-item men">
                <div class="collection-image">
                    <img src="images/mencol.webp" alt="Men's Featured Collection">
                    <div class="collection-overlay">
                        <div class="collection-content">
                            <h3>Performance Essentials</h3>
                            <p>Engineered for comfort and durability</p>
                        
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="collection-item women">
                <div class="collection-image">
                    <img src="images/womenf.jpg" alt="Women's Featured Collection">
                    <div class="collection-overlay">
                        <div class="collection-content">
                            <h3>Active Lifestyle</h3>
                            <p>Stylish designs for every workout</p>
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<section id="homeFeatured" class="featured-products-section">
    <!-- The top 4 featured products will be loaded here -->
  </section>

<!-- About & Contact Navigation Section -->
<section class="shop-promo">
    <div class="container">
        <div class="section-title">
            <h2>Discover Our Story</h2>
            <p>Learn more about who we are and how to connect with us</p>
        </div>
        <div class="shop-promo-wrapper">
            <div class="shop-promo-left">
                <div class="promo-card about">
                    <div class="promo-image">
                        <img src="images/abt-us.jpg" alt="About Our Company">
                    </div>
                    <div class="promo-content">
                        <h3>About Us</h3>
                        <p>Learn about our story and mission</p>
                        <a href="about.php" class="promo-link">READ MORE <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="shop-promo-right">
                <div class="promo-card contact">
                    <div class="promo-image">
                        <img src="images/contactus.jpg" alt="Contact Our Team">
                    </div>
                    <div class="promo-content">
                        <h3>Contact Us</h3>
                        <p>Get in touch with our team</p>
                        <a href="contact.php" class="promo-link">REACH OUT <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="promo-highlight">
                    <div class="highlight-content">
                        <span class="badge">LOCATIONS</span>
                        <h3>Find Us</h3>
                        <p>Visit our stores nationwide</p>
                        <a href="contact.php#map" class="highlight-btn">VIEW ALL</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


 <!-- Features Section -->
 <section class="section features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-text">On all orders over ₱4,0000</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <h3 class="feature-title">Easy Returns</h3>
                <p class="feature-text">30 days easy return policy</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="feature-title">Secure Payment</h3>
                <p class="feature-text">100% secure payment methods</p>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-text">Dedicated customer support</p>
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


<!--linking category -->
<script>
    // This script directly targets the category links and adds event listeners
// Add this script at the bottom of your page, right before the closing body tag

document.addEventListener('DOMContentLoaded', function() {
    console.log("Document loaded, initializing category links...");
    
    // Wait a bit to ensure all elements are loaded
    setTimeout(function() {
        // Direct targeting of the EXPLORE buttons in featured categories
        const exploreButtons = document.querySelectorAll('.category-overlay .category-btn');
        console.log("Found EXPLORE buttons:", exploreButtons.length);
        
        exploreButtons.forEach(button => {
            // Remove any existing click handlers
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add new click handler
            newButton.addEventListener('click', function(event) {
                event.preventDefault();
                
                const href = this.getAttribute('href');
                console.log("EXPLORE button clicked with href:", href);
                
                let category;
                switch(href) {
                    case 'men.php':
                        category = "Men's Activewear";
                        break;
                    case 'women.php':
                        category = "Women's Activewear";
                        break;
                    case 'foot.php':
                        category = "Footwear";
                        break;
                    case 'acces.php':
                        category = "Accessories";
                        break;
                    default:
                        // Try to get category from nearest h3
                        const h3 = this.closest('.category-overlay').querySelector('h3');
                        if (h3) {
                            const text = h3.textContent;
                            if (text.includes("Men")) {
                                category = "Men's Activewear";
                            } else if (text.includes("Women")) {
                                category = "Women's Activewear";
                            } else if (text.includes("Foot")) {
                                category = "Footwear";
                            } else if (text.includes("Access")) {
                                category = "Accessories";
                            } else {
                                category = text;
                            }
                        } else {
                            category = "All Products";
                        }
                }
                
                console.log("Filtering by category:", category);
                
                // Call your filter function
                if (typeof filterProductsByCategory === 'function') {
                    filterProductsByCategory(category);
                } else {
                    console.error("filterProductsByCategory function is not defined!");
                }
            });
            
            console.log("Added direct click handler to button:", newButton.textContent, "with href:", newButton.getAttribute('href'));
        });
        
        console.log("Category links initialization complete");
    }, 1000);
});

</script>

<button id="scrollUpBtn" title="Go to top">↑</button>
<script>
  // Kunin ang button
  const scrollBtn = document.getElementById("scrollUpBtn");

  // Kapag nag-scroll, i-check kung lalabas ang button
  window.onscroll = function() {
    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
      scrollBtn.style.display = "block";
    } else {
      scrollBtn.style.display = "none";
    }
  };

  // Kapag na-click, umakyat sa taas
  scrollBtn.onclick = function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };
</script>


</body>
</html>