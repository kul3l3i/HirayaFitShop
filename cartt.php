<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: sign-in.php");
    exit();
}

// Get the current user's ID
$user_id = $_SESSION['user'];

// Initialize the carts array in session if it doesn't exist
if (!isset($_SESSION['carts'])) {
    $_SESSION['carts'] = [];
}

// Initialize this specific user's cart if it doesn't exist
if (!isset($_SESSION['carts'][$user_id])) {
    $_SESSION['carts'][$user_id] = [];
}

// Process checkout function
function processCheckout() {
    global $user_id;
    
    if (!isset($_SESSION['user']) || empty($_SESSION['carts'][$user_id])) {
        return false;
    }
    
    // Generate a unique order ID
    $order_id = uniqid('order_');
    
    // Store current cart for reference after payment
    $_SESSION['pending_orders'][$user_id] = [
        'order_id' => $order_id,
        'items' => $_SESSION['carts'][$user_id],
        'total' => 0, // Will be calculated below
        'status' => 'pending',
        'timestamp' => time()
    ];
    
    // Calculate total (needed for GCash)
    $xmlPath = 'pastry.xml';
    $total = 0;
    
    if (file_exists($xmlPath)) {
        $file = simplexml_load_file($xmlPath);
        foreach ($_SESSION['carts'][$user_id] as $id => $item) {
            foreach ($file->pastry as $pastry) {
                if ((string)$pastry['id'] == $id) {
                    $total += floatval($pastry->price) * $item['quantity'];
                    break;
                }
            }
        }
    }
    
    $_SESSION['pending_orders'][$user_id]['total'] = $total;
    
    // Prepare GCash payment link (this is a placeholder - you'll need to replace with your actual GCash API integration)
    $gcash_link = "https://gcash.com/payme?merchant=lacroissanterie&amount={$total}&reference={$order_id}&return_url=" . 
                  urlencode("https://yourdomain.com/cart.php?payment_status=success&order_id={$order_id}");
    
    return $gcash_link;
}

// Complete order function - updates stock when payment is completed
function completeOrder($order_id) {
    global $user_id;
    
    if (!isset($_SESSION['pending_orders'][$user_id]) || $_SESSION['pending_orders'][$user_id]['order_id'] != $order_id) {
        return false;
    }
    
    $xmlPath = 'pastry.xml';
    
    if (!file_exists($xmlPath)) {
        return false;
    }
    
    // Load XML file
    $xml = simplexml_load_file($xmlPath);
    $updated = false;
    
    // Update stock for each item in the order
    foreach ($_SESSION['pending_orders'][$user_id]['items'] as $id => $item) {
        foreach ($xml->pastry as $pastry) {
            if ((string)$pastry['id'] == $id) {
                // Update stock (make sure stock element exists)
                if (isset($pastry->stock)) {
                    $current_stock = (int)$pastry->stock;
                    $new_stock = max(0, $current_stock - $item['quantity']);
                    $pastry->stock = $new_stock;
                    $updated = true;
                }
                break;
            }
        }
    }
    
    // Save updated XML if changes were made
    if ($updated) {
        $xml->asXML($xmlPath);
    }
    
    // Mark order as completed
    if (!isset($_SESSION['last_orders'])) {
        $_SESSION['last_orders'] = [];
    }
    
    $_SESSION['last_orders'][$user_id] = $_SESSION['pending_orders'][$user_id];
    $_SESSION['last_orders'][$user_id]['status'] = 'completed';
    
    // Clear cart and pending order
    $_SESSION['carts'][$user_id] = [];
    unset($_SESSION['pending_orders'][$user_id]);
    
    return true;
}

// Check if returning from GCash payment
if (isset($_GET['payment_status']) && $_GET['payment_status'] == 'success' && isset($_GET['order_id'])) {
    $order_completed = completeOrder($_GET['order_id']);
    
    if ($order_completed) {
        $_SESSION['checkout_success'] = true;
    }
}

// Load data from XML file
$xmlPath = 'pastry.xml';
$pastries = [];

if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    foreach ($file->pastry as $row) {
        // Add ID if it doesn't exist
        if (!isset($row['id'])) {
            $row->addAttribute('id', uniqid());
        }
        $pastries[(string)$row['id']] = $row;
    }
}

// Process checkout if submitted
if (isset($_POST['process_checkout'])) {
    $gcash_link = processCheckout();
    if ($gcash_link) {
        header("Location: $gcash_link");
        exit();
    }
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantity
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $_SESSION['carts'][$user_id][$id]['quantity'] = $quantity;
            } else {
                unset($_SESSION['carts'][$user_id][$id]);
            }
        }
        // Redirect to prevent form resubmission
        header("Location: cart.php?updated=1");
        exit();
    }
    
    // Remove item from cart
    if (isset($_POST['remove_item'])) {
        $remove_id = $_POST['remove_id'];
        if (isset($_SESSION['carts'][$user_id][$remove_id])) {
            unset($_SESSION['carts'][$user_id][$remove_id]);
        }
        // Redirect to prevent form resubmission
        header("Location: cart.php?removed=1");
        exit();
    }
    
    // Clear cart
    if (isset($_POST['clear_cart'])) {
        $_SESSION['carts'][$user_id] = [];
        // Redirect to prevent form resubmission
        header("Location: cart.php?cleared=1");
        exit();
    }
}

// Calculate total items and amount
$totalItems = 0;
$totalAmount = 0;

foreach ($_SESSION['carts'][$user_id] as $id => $item) {
    $totalItems += $item['quantity'];
    if (isset($pastries[$id])) {
        $totalAmount += $item['quantity'] * floatval($pastries[$id]->price);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - La Croissanterie</title>
    <link rel="stylesheet" href="cart.css">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    
    <nav>
      <ul class="main-nav">
        <li><a href="homepage.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="menu2.php">Menu</a></li>
        <li><a href="cart.php">Cart <span class="cart-badge" id="cartCount"><?php echo $totalItems; ?></span></a></li>
      </ul>
    </nav>
    
    <div class="profile-dropdown">
      <div class="dropdown-toggle" id="profileDropdown">
        <div class="profile-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <span class="profile-name"><?php echo htmlspecialchars($_SESSION['fname']); ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </div>
      <div class="dropdown-menu" id="profileMenu">
        <a href="profile.php">My Profile</a>
        <div class="dropdown-divider"></div>
        <a href="#" id="logoutBtn">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="container">
    <h1 class="page-title">Shopping Cart</h1>
    
    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">
        Cart has been updated successfully!
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['removed'])): ?>
    <div class="alert alert-success">
        Item has been removed from your cart!
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['cleared'])): ?>
    <div class="alert alert-warning">
        Your cart has been cleared!
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['checkout_success'])): ?>
    <div class="alert alert-success">
        Your order has been successfully processed! Thank you for your purchase.
    </div>
    <?php unset($_SESSION['checkout_success']); ?>
    <?php endif; ?>
    
    <?php if (empty($_SESSION['carts'][$user_id])): ?>
        <div class="cart-container">
            <div class="cart-empty">
                <p>Your cart is empty</p>
                <a href="menu2.php" class="btn">Continue Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <form action="cart.php" method="post">
            <div class="cart-grid">
                <div class="cart-container">
                    <div class="cart-header">
                        <div class="header-image">Image</div>
                        <div class="header-details">Product</div>
                        <div class="header-price">Price</div>
                        <div class="header-quantity">Quantity</div>
                        <div class="header-subtotal">Subtotal</div>
                    </div>
                    
                    <?php foreach ($_SESSION['carts'][$user_id] as $id => $item): ?>
                        <?php if (isset($pastries[$id])): ?>
                            <div class="cart-item">
                                <div>
                                    <img src="<?php echo htmlspecialchars($pastries[$id]->image); ?>" alt="<?php echo htmlspecialchars($pastries[$id]->name); ?>" class="item-image">
                                </div>
                                <div class="item-details">
                                    <h3 class="item-name"><?php echo htmlspecialchars($pastries[$id]->name); ?></h3>
                                    <div class="item-category"><?php echo htmlspecialchars($pastries[$id]->producttype); ?></div>
                                </div>
                                <div class="item-price">₱<?php echo number_format(floatval($pastries[$id]->price), 2); ?></div>
                                <div class="item-quantity">
                                    <input type="number" name="quantity[<?php echo $id; ?>]" value="<?php echo $item['quantity']; ?>" min="0" class="quantity-input">
                                </div>
                                <div class="item-total">₱<?php echo number_format(floatval($pastries[$id]->price) * $item['quantity'], 2); ?></div>
                                <div class="item-remove">
                                    <button type="submit" name="remove_item" class="remove-btn" onclick="document.querySelector('input[name=remove_id]').value='<?php echo $id; ?>'">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <input type="hidden" name="remove_id" value="">
                    
                    <div class="cart-actions">
                        <a href="menu2.php" class="continue-shopping">Continue Shopping</a>
                        <div>
                            <button type="submit" name="clear_cart" class="clear-cart">Clear Cart</button>
                            <button type="submit" name="update_cart" class="update-cart">Update Cart</button>
                        </div>
                    </div>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-header">
                        <h3>Order Summary</h3>
                    </div>
                    <div class="summary-content">
                        <div class="summary-row">
                            <span>Total Items:</span>
                            <span><?php echo $totalItems; ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span class="summary-total">₱<?php echo number_format($totalAmount, 2); ?></span>
                        </div>
                        <?php if ($totalItems > 0): ?>
                            <button type="submit" name="process_checkout" class="checkout-btn">Proceed to Checkout</button>
                        <?php else: ?>
                            <button disabled class="checkout-btn" style="opacity: 0.5;">Proceed to Checkout</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3 class="footer-title">La Croissanterie</h3>
            <p>Bringing you the finest pastries and treats since 2010. Our commitment is to quality, freshness, and delicious flavors.</p>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Quick Links</h3>
            <ul class="footer-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="menu2.php">Menu</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Contact Info</h3>
            <ul class="footer-links">
                <li>123 Bakery Lane, Manila</li>
                <li>Phone: (02) 8123-4567</li>
                <li>Email: info@lacroissanterie.com</li>
                <li>Hours: Mon-Sat: 7am - 7pm</li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Follow Us</h3>
            <ul class="footer-links">
                <li><a href="#">Facebook</a></li>
                <li><a href="#">Instagram</a></li>
                <li><a href="#">Twitter</a></li>
                <li><a href="#">Pinterest</a></li>
            </ul>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> La Croissanterie. All rights reserved.</p>
        </div>
    </div>
</div>

<!-- Logout confirmation modal -->
<div class="modal" id="logoutModal">
    <div class="modal-content logout-modal-content">
        <span class="modal-close">&times;</span>
        <div class="logout-modal-body">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout from your account?</p>
            <div class="logout-modal-buttons">
                <button class="cancel-btn" id="cancelLogout">Cancel</button>
                <a href="homepage.php" class="confirm-btn">Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Profile dropdown toggle
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');

    profileDropdown.addEventListener('click', () => {
        profileMenu.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    window.addEventListener('click', (event) => {
        if (!event.target.closest('.profile-dropdown')) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Logout modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const modalClose = document.querySelector('.modal-close');
    const cancelLogout = document.getElementById('cancelLogout');

    logoutBtn.addEventListener('click', () => {
        logoutModal.classList.add('show');
    });

    function closeModal() {
        logoutModal.classList.remove('show');
    }

    modalClose.addEventListener('click', closeModal);
    cancelLogout.addEventListener('click', closeModal);

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === logoutModal) {
            closeModal();
        }
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
</script>
</body>
</html>