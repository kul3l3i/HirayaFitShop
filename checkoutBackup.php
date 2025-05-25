<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$user = null;

// If user is not logged in, redirect to login page
if (!$loggedIn) {
    header("Location:sign-in.php");
    exit();
}

// Check if there's an active transaction
if (!isset($_SESSION['current_transaction'])) {
    header("Location: cart.php");
    exit();
}

$transactionId = $_SESSION['current_transaction'];

// Database connection
$db_host = 'localhost';
$db_user = 'u801377270_hiraya_2025'; // Change to your database username
$db_pass = 'Hiraya_2025'; // Change to your database password
$db_name = 'u801377270_hiraya_2025'; // Replace with your actual database name

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$stmt = $conn->prepare("SELECT id, fullname, username, email, address, phone, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// If user exists, store their details
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

$stmt->close();
$conn->close();

// Load transaction details from XML
function loadTransactionDetails($transactionId) {
    if (file_exists('transaction.xml')) {
        $xml = simplexml_load_file('transaction.xml');
        
        foreach ($xml->transaction as $transaction) {
            if ((string) $transaction->transaction_id == $transactionId && (int) $transaction->user_id == $_SESSION['user_id']) {
                return [
                    'transaction_id' => (string) $transaction->transaction_id,
                    'transaction_date' => (string) $transaction->transaction_date,
                    'status' => (string) $transaction->status,
                    'subtotal' => (float) $transaction->subtotal,
                    'shipping_fee' => (float) $transaction->shipping_fee,
                    'total_amount' => (float) $transaction->total_amount,
                    'items' => parseTransactionItems($transaction->items)
                ];
            }
        }
    }
    
    return null;
}

function parseTransactionItems($itemsNode) {
    $items = [];
    
    foreach ($itemsNode->item as $item) {
        $items[] = [
            'product_id' => (string) $item->product_id,
            'product_name' => (string) $item->product_name,
            'price' => (float) $item->price,
            'quantity' => (int) $item->quantity,
            'color' => (string) $item->color,
            'size' => (string) $item->size,
            'subtotal' => (float) $item->subtotal
        ];
    }
    
    return $items;
}

// Load the current transaction
$transaction = loadTransactionDetails($transactionId);

if (!$transaction) {
    // Transaction not found, redirect to cart
    header("Location: cart.php");
    exit();
}

// Process order submission
if (isset($_POST['place_order'])) {
    // Validate required fields
    $errors = [];
    
    if (empty($_POST['fullname'])) $errors[] = "Full name is required";
    if (empty($_POST['email'])) $errors[] = "Email is required";
    if (empty($_POST['phone'])) $errors[] = "Phone number is required";
    if (empty($_POST['address'])) $errors[] = "Address is required";
    if (empty($_POST['city'])) $errors[] = "City is required";
    if (empty($_POST['postal_code'])) $errors[] = "Postal code is required";
    if (empty($_POST['payment_method'])) $errors[] = "Payment method is required";
    
    // If no errors, update transaction and proceed
    if (empty($errors)) {
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $city = $_POST['city'];
        $postal_code = $_POST['postal_code'];
        $payment_method = $_POST['payment_method'];
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        // Update transaction XML
        $xml = simplexml_load_file('transaction.xml');
        
        foreach ($xml->transaction as $txn) {
            if ((string) $txn->transaction_id == $transactionId && (int) $txn->user_id == $_SESSION['user_id']) {
                $txn->status = 'processing';
                $txn->payment_method = $payment_method;
                
                // Add shipping information
                if (!isset($txn->shipping_info)) {
                    $shipping = $txn->addChild('shipping_info');
                    $shipping->addChild('fullname', $fullname);
                    $shipping->addChild('email', $email);
                    $shipping->addChild('phone', $phone);
                    $shipping->addChild('address', $address);
                    $shipping->addChild('city', $city);
                    $shipping->addChild('postal_code', $postal_code);
                    $shipping->addChild('notes', $notes);
                }
                
                break;
            }
        }
        
        $xml->asXML('transaction.xml');
        
        // Remove checkout items from cart
        if (isset($_SESSION['checkout_items']) && file_exists('cart.xml')) {
            $cartXml = simplexml_load_file('cart.xml');
            $itemsToRemove = [];
            
            // Find all items to remove
            $i = 0;
            foreach ($cartXml->item as $item) {
                if (in_array((string) $item->id, $_SESSION['checkout_items']) && (int) $item->user_id == $_SESSION['user_id']) {
                    $itemsToRemove[] = $i;
                }
                $i++;
            }
            
            // Remove items in reverse order to avoid index issues
            rsort($itemsToRemove);
            foreach ($itemsToRemove as $index) {
                unset($cartXml->item[$index]);
            }
            
            $cartXml->asXML('cart.xml');
            
            // Update cart count in session
            if (isset($_SESSION['cart_count'])) {
                $_SESSION['cart_count'] = max(0, $_SESSION['cart_count'] - count($itemsToRemove));
            }
        }
        
        // Store transaction ID for order confirmation
        $_SESSION['completed_transaction'] = $transactionId;
        
        // Clear checkout session variables
        unset($_SESSION['current_transaction']);
        unset($_SESSION['checkout_items']);
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - HirayaFit</title>    <link rel="icon" href="images/hf.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <link rel="stylesheet" href="style/checkout.css">
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
                    <a href="login.php">Sign In</a>
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
                        <!-- Account dropdown for logged-in users -->
                        <div class="account-dropdown" id="accountDropdown">
                            <a href="#" id="accountBtn">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profile"
                                        class="mini-avatar">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                            </a>
                            <div class="account-dropdown-content" id="accountDropdownContent">
                                <div class="user-profile-header">
                                    <div class="user-avatar">
                                        <img src="<?php echo !empty($user['profile_image']) ? 'uploads/profiles/' . $user['profile_image'] : 'assets/images/default-avatar.png'; ?>"
                                            alt="Profile">
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
                            <?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : 0; ?>
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
            <a   href="usershop.php">HOME</a>
        </nav>
    </header>

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            <div class="checkout-header">
                <h1>Checkout</h1>
                <div class="checkout-steps">
                    <div class="step completed">
                        <span class="step-number">1</span>
                        <span class="step-text">Shopping Cart</span>
                    </div>
                    <div class="step active">
                        <span class="step-number">2</span>
                        <span class="step-text">Checkout</span>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <span class="step-text">Order Confirmation</span>
                    </div>
                </div>
            </div>

            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="checkout-content">
                <div class="checkout-form-container">
                    <form action="checkout.php" method="post" id="checkoutForm">
                        <div class="checkout-form">
                            <div class="form-section">
                                <h2>Shipping Information</h2>
                                <div class="form-group">
                                    <label for="fullname">Full Name *</label>
                                    <input type="text" id="fullname" name="fullname" required
                                        value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email Address *</label>
                                        <input type="email" id="email" name="email" required
                                            value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Phone Number *</label>
                                        <input type="tel" id="phone" name="phone" required
                                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address *</label>
                                    <input type="text" id="address" name="address" required
                                        value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">City *</label>
                                        <input type="text" id="city" name="city" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="postal_code">Postal Code *</label>
                                        <input type="text" id="postal_code" name="postal_code" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Order Notes (Optional)</label>
                                    <textarea id="notes" name="notes" placeholder="Special instructions for delivery"></textarea>
                                </div>
                            </div>

                            <div class="form-section payment-section">
                                <h2>Payment Method</h2>
                                <div class="payment-options">
                                    <div class="payment-option">
                                        <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                        <label for="cod">
                                            <span class="radio-custom"></span>
                                            <span class="payment-icon"><i class="fas fa-money-bill-wave"></i></span>
                                            <span class="payment-text">Cash on Delivery</span>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" id="gcash" name="payment_method" value="gcash">
                                        <label for="gcash">
                                            <span class="radio-custom"></span>
                                            <span class="payment-icon"><img src="assets/images/gcash-logo.png" alt="GCash"></span>
                                            <span class="payment-text">GCash</span>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" id="paymaya" name="payment_method" value="paymaya">
                                        <label for="paymaya">
                                            <span class="radio-custom"></span>
                                            <span class="payment-icon"><img src="assets/images/paymaya-logo.png" alt="PayMaya"></span>
                                            <span class="payment-text">PayMaya</span>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                        <label for="bank_transfer">
                                            <span class="radio-custom"></span>
                                            <span class="payment-icon"><i class="fas fa-university"></i></span>
                                            <span class="payment-text">Bank Transfer</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="payment-details" id="paymentDetails">
                                    <div class="payment-detail-content" id="gcashDetails" style="display: none;">
                                        <div class="e-wallet-details">
                                            <h3>GCash Payment Instructions</h3>
                                            <p>Please send your payment to:</p>
                                            <div class="wallet-info">
                                                <div class="qr-code">
                                                    <img src="assets/images/gcash-qr.png" alt="GCash QR Code">
                                                </div>
                                                <div class="wallet-account">
                                                    <p><strong>Account Name:</strong> HirayaFit Store</p>
                                                    <p><strong>Account Number:</strong> 0917-123-4567</p>
                                                    <p>After sending payment, please take a screenshot of your receipt and send it to our email: payments@hirayafit.com</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="payment-detail-content" id="paymayaDetails" style="display: none;">
                                        <div class="e-wallet-details">
                                            <h3>PayMaya Payment Instructions</h3>
                                            <p>Please send your payment to:</p>
                                            <div class="wallet-info">
                                                <div class="qr-code">
                                                    <img src="assets/images/paymaya-qr.png" alt="PayMaya QR Code">
                                                </div>
                                                <div class="wallet-account">
                                                    <p><strong>Account Name:</strong> HirayaFit Store</p>
                                                    <p><strong>Account Number:</strong> 0918-765-4321</p>
                                                    <p>After sending payment, please take a screenshot of your receipt and send it to our email: payments@hirayafit.com</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="payment-detail-content" id="bankDetails" style="display: none;">
                                        <div class="bank-details">
                                            <h3>Bank Transfer Instructions</h3>
                                            <p>Please transfer the total amount to:</p>
                                            <p><strong>Bank:</strong> BDO (Banco de Oro)</p>
                                            <p><strong>Account Name:</strong> HirayaFit Corporation</p>
                                            <p><strong>Account Number:</strong> 1234-5678-9012</p>
                                            <p>After making the transfer, please take a screenshot of your receipt and send it to our email: payments@hirayafit.com</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkout-actions">
                                    <button type="submit" name="place_order" class="btn-place-order">Place Order</button>
                                    <a href="cart.php" class="btn-back-to-cart">Back to Cart</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="order-items">
                        <?php foreach ($transaction['items'] as $item): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <div class="item-specs">
                                        <span>Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                        <span>Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                    </div>
                                    <div class="item-price-qty">
                                        <span>₱<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?></span>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    ₱<?php echo number_format($item['subtotal'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₱<?php echo number_format($transaction['subtotal'], 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>
                                <?php if ($transaction['shipping_fee'] > 0): ?>
                                    ₱<?php echo number_format($transaction['shipping_fee'], 2); ?>
                                <?php else: ?>
                                    FREE
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span>₱<?php echo number_format($transaction['total_amount'], 2); ?></span>
                        </div>
                    </div>

                    <?php if ($transaction['shipping_fee'] == 0): ?>
                        <div class="free-shipping-badge">
                            <i class="fas fa-truck"></i> FREE SHIPPING APPLIED
                        </div>
                    <?php endif; ?>
                    
                    <div class="transaction-info">
                        <p><strong>Transaction ID:</strong> <?php echo $transaction['transaction_id']; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($transaction['transaction_date'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer would go here -->

    <script>
        // Toggle mobile menu
        document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            document.getElementById('mainNav').classList.toggle('active');
        });

        // Toggle account dropdown
        const accountBtn = document.getElementById('accountBtn');
        const accountDropdownContent = document.getElementById('accountDropdownContent');

        if (accountBtn) {
            accountBtn.addEventListener('click', function(e) {
                e.preventDefault();
                accountDropdownContent.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            window.addEventListener('click', function(e) {
                if (!e.target.matches('#accountBtn') && !e.target.closest('#accountDropdownContent')) {
                    if (accountDropdownContent.classList.contains('show')) {
                        accountDropdownContent.classList.remove('show');
                    }
                }
            });
        }

        // Show payment details based on selected payment method
        const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
        paymentOptions.forEach(option => {
            option.addEventListener('change', function() {
                const gcashDetails = document.getElementById('gcashDetails');
                const paymayaDetails = document.getElementById('paymayaDetails');
                const bankDetails = document.getElementById('bankDetails');

                // Hide all payment details first
                gcashDetails.style.display = 'none';
                paymayaDetails.style.display = 'none';
                bankDetails.style.display = 'none';

                // Show the selected payment method's details
                if (this.value === 'gcash') {
                    gcashDetails.style.display = 'block';
                } else if (this.value === 'paymaya') {
                    paymayaDetails.style.display = 'block';
                } else if (this.value === 'bank_transfer') {
                    bankDetails.style.display = 'block';
                }
            });
        });
    </script>
</body>

</html>