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
    header("Location: login.php");
    exit();
}

// If user is logged in, fetch their information
if ($loggedIn) {
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

// Function to load cart items from XML file
function loadCartItems()
{
    $userId = $_SESSION['user_id'];
    $cartItems = [];

    if (file_exists('cart.xml')) {
        $xml = simplexml_load_file('cart.xml');

        foreach ($xml->item as $item) {
            if ((int) $item->user_id == $userId) {
                $cartItems[] = [
                    'id' => (string) $item->id,
                    'product_id' => (string) $item->product_id,
                    'user_id' => (string) $item->user_id,
                    'product_name' => (string) $item->product_name,
                    'price' => (float) $item->price,
                    'quantity' => (int) $item->quantity,
                    'color' => (string) $item->color,
                    'size' => (string) $item->size,
                    'image' => (string) $item->image
                ];
            }
        }
    }

    return $cartItems;
}

// Process remove item request
if (isset($_POST['remove_item']) && isset($_POST['item_id'])) {
    $itemIdToRemove = $_POST['item_id'];

    if (file_exists('cart.xml')) {
        $xml = simplexml_load_file('cart.xml');

        $i = 0;
        $indexToRemove = -1;

        foreach ($xml->item as $item) {
            if ((string) $item->id == $itemIdToRemove && (int) $item->user_id == $_SESSION['user_id']) {
                $indexToRemove = $i;
                break;
            }
            $i++;
        }

        if ($indexToRemove >= 0) {
            unset($xml->item[$indexToRemove]);
            $xml->asXML('cart.xml');

            // Update cart count in session
            if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0) {
                $_SESSION['cart_count']--;
            }

            // Redirect to prevent form resubmission
            header("Location: cart.php?removed=true");
            exit();
        }
    }
}

// Process multiple items deletion
if (isset($_POST['remove_selected']) && isset($_POST['selected_items'])) {
    $selectedItems = $_POST['selected_items'];

    if (file_exists('cart.xml') && !empty($selectedItems)) {
        $xml = simplexml_load_file('cart.xml');
        $itemsToRemove = [];

        // Find all items to remove
        $i = 0;
        foreach ($xml->item as $item) {
            if (in_array((string) $item->id, $selectedItems) && (int) $item->user_id == $_SESSION['user_id']) {
                $itemsToRemove[] = $i;
            }
            $i++;
        }

        // Remove items in reverse order to avoid index issues
        rsort($itemsToRemove);
        foreach ($itemsToRemove as $index) {
            unset($xml->item[$index]);
        }

        $xml->asXML('cart.xml');

        // Update cart count in session
        if (isset($_SESSION['cart_count'])) {
            $_SESSION['cart_count'] = max(0, $_SESSION['cart_count'] - count($itemsToRemove));
        }

        // Redirect to prevent form resubmission
        header("Location: cart.php?removed=true");
        exit();
    }
}

// Process update quantity request
if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
    $itemId = $_POST['item_id'];
    $newQuantity = (int) $_POST['quantity'];

    if ($newQuantity > 0 && file_exists('cart.xml')) {
        $xml = simplexml_load_file('cart.xml');

        foreach ($xml->item as $item) {
            if ((string) $item->id == $itemId && (int) $item->user_id == $_SESSION['user_id']) {
                $item->quantity = $newQuantity;
                break;
            }
        }

        $xml->asXML('cart.xml');

        // Redirect to prevent form resubmission
        header("Location: cart.php?updated=true");
        exit();
    }
}

// Process checkout
if (isset($_POST['checkout']) && isset($_POST['selected_for_checkout'])) {
    $selectedItems = $_POST['selected_for_checkout'];

    if (!empty($selectedItems) && file_exists('cart.xml')) {
        $xml = simplexml_load_file('cart.xml');
        $checkoutItems = [];
        $totalAmount = 0;

        // Find items for checkout and calculate total
        foreach ($xml->item as $item) {
            if (in_array((string) $item->id, $selectedItems) && (int) $item->user_id == $_SESSION['user_id']) {
                $itemTotal = (float) $item->price * (int) $item->quantity;
                $totalAmount += $itemTotal;

                $checkoutItems[] = [
                    'id' => (string) $item->id,
                    'product_id' => (string) $item->product_id,
                    'product_name' => (string) $item->product_name,
                    'price' => (float) $item->price,
                    'quantity' => (int) $item->quantity,
                    'color' => (string) $item->color,
                    'size' => (string) $item->size,
                    'subtotal' => $itemTotal
                ];
            }
        }

        // Add shipping fee if applicable
        $shippingFee = ($totalAmount >= 4000) ? 0 : 100;
        $finalTotal = $totalAmount + $shippingFee;

        // Generate transaction ID
        $transactionId = 'TRX-' . strtoupper(uniqid());

        // Create transaction record in transaction.xml
        if (!file_exists('transaction.xml')) {
            $transactionXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><transactions></transactions>');
        } else {
            $transactionXml = simplexml_load_file('transaction.xml');
        }

        $transaction = $transactionXml->addChild('transaction');
        $transaction->addChild('transaction_id', $transactionId);
        $transaction->addChild('user_id', $_SESSION['user_id']);
        $transaction->addChild('transaction_date', date('Y-m-d H:i:s'));
        $transaction->addChild('status', 'pending');
        $transaction->addChild('payment_method', 'pending');
        $transaction->addChild('subtotal', $totalAmount);
        $transaction->addChild('shipping_fee', $shippingFee);
        $transaction->addChild('total_amount', $finalTotal);

        $items = $transaction->addChild('items');
        foreach ($checkoutItems as $item) {
            $itemNode = $items->addChild('item');
            $itemNode->addChild('product_id', $item['product_id']);
            $itemNode->addChild('product_name', $item['product_name']);
            $itemNode->addChild('price', $item['price']);
            $itemNode->addChild('quantity', $item['quantity']);
            $itemNode->addChild('color', $item['color']);
            $itemNode->addChild('size', $item['size']);
            $itemNode->addChild('subtotal', $item['subtotal']);
        }

        $transactionXml->asXML('transaction.xml');

        // Store transaction ID in session for checkout page
        $_SESSION['current_transaction'] = $transactionId;
        $_SESSION['checkout_items'] = $selectedItems;

        // Redirect to checkout page
        header("Location: checkout.php");
        exit();
    }
}

// Load cart items
$cartItems = loadCartItems();

// Calculate totals for all items
$allSubtotal = 0;
$allTotalItems = 0;

foreach ($cartItems as $item) {
    $allSubtotal += $item['price'] * $item['quantity'];
    $allTotalItems += $item['quantity'];
}

// Calculate totals for selected items (will be updated via JavaScript)
$selectedSubtotal = 0;
$selectedTotalItems = 0;
$shippingFee = 100; // Default shipping fee (₱100)

// Free shipping for orders over ₱4,000 (will be updated via JavaScript)
if ($selectedSubtotal >= 4000) {
    $shippingFee = 0;
}

$selectedTotal = $selectedSubtotal + $shippingFee;

// Update session cart count
$_SESSION['cart_count'] = count($cartItems);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - HirayaFit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <link rel="stylesheet" href="style/cart.css">
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

                    <a href="wishlist.php"><i class="fas fa-heart"></i></a>
                    <a href="cart.php" id="cartBtn" class="active">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">
                            <?php echo $allTotalItems; ?>
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
            <a href="usershop.php">HOME</a>
           
        </nav>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <h1>Your Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="shop.php" class="btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form action="cart.php" method="post" id="cartForm">
                    <div class="cart-header">
                        <div class="select-all">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                            <label for="selectAll">Select All</label>
                        </div>
                        <div class="cart-actions">
                            <button type="submit" name="remove_selected" class="btn-outline-danger"
                                onclick="return confirm('Are you sure you want to remove selected items?')">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </div>
                    </div>

                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-id="<?php echo $item['product_id']; ?>"
                                data-size="<?php echo $item['size']; ?>" data-color="<?php echo $item['color']; ?>">

                                <div class="item-select">
                                    <input type="checkbox" name="selected_items[]" value="<?php echo $item['id']; ?>"
                                        class="item-checkbox" onchange="updateOrderSummary()">
                                    <input type="checkbox" name="selected_for_checkout[]" value="<?php echo $item['id']; ?>"
                                        class="checkout-checkbox" style="display: none;">
                                </div>

                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>

                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                    <div class="item-variation">
                                        <span>Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                        <span>Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                    </div>
                                    <div class="item-price price-column">₱<?php echo number_format($item['price'], 2); ?></div>
                                </div>

                                <div class="item-quantity">
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn minus"
                                            onclick="decrementQuantity('<?php echo $item['id']; ?>')">-</button>
                                        <input type="text" id="quantity-<?php echo $item['id']; ?>"
                                            name="quantity-<?php echo $item['id']; ?>" class="quantity-input"
                                            value="<?php echo $item['quantity']; ?>" readonly>
                                        <button type="button" class="quantity-btn plus"
                                            onclick="incrementQuantity('<?php echo $item['id']; ?>')">+</button>
                                    </div>

                                    <div class="update-column">
                                        <button class="update-btn" type="button" onclick="updateCartItem(this)">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>

                                    <form action="cart.php" method="post" class="update-quantity-form"
                                        id="update-form-<?php echo $item['id']; ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="quantity" id="update-quantity-<?php echo $item['id']; ?>"
                                            value="<?php echo $item['quantity']; ?>">
                                        <input type="hidden" name="update_quantity" value="1">
                                    </form>
                                </div>

                                <div class="item-subtotal subtotal-column" data-price="<?php echo $item['price']; ?>"
                                    data-quantity="<?php echo $item['quantity']; ?>" data-id="<?php echo $item['id']; ?>">
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>

                                <div class="item-actions">
                                    <form action="cart.php" method="post" class="remove-item-form">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="remove-btn"
                                            onclick="return confirm('Are you sure you want to remove this item?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </div>
                </form>

                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal (<span id="selected-items-count">0</span> items)</span>
                        <span id="selected-subtotal">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping Fee</span>
                        <span id="shipping-fee">₱100.00</span>
                    </div>
                    <div class="free-shipping-notice" id="free-shipping-notice" style="display: none;">
                        <i class="fas fa-truck"></i> You've qualified for FREE shipping!
                    </div>
                    <div class="shipping-threshold" id="shipping-threshold">
                        <div class="threshold-bar">
                            <div class="threshold-progress" id="threshold-progress" style="width: 0%"></div>
                        </div>
                        <p>Add <span id="amount-for-free-shipping">₱4,000.00</span> more to qualify for FREE shipping</p>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="total-amount" id="total-amount">₱0.00</span>
                    </div>
                    <button type="button" id="checkout-btn" class="btn-checkout" onclick="proceedToCheckout()"
                        disabled>Proceed to Checkout</button>
                    <a href="shop.php" class="btn-continue-shopping">Continue Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>About HirayaFit</h3>
                    <ul>
                        <li><a href="#">Our Story</a></li>
                        <li><a href="#">Sustainability</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Connect With Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="newsletter">
                        <h4>Subscribe to our newsletter</h4>
                        <form>
                            <input type="email" placeholder="Enter your email">
                            <button type="submit">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 HirayaFit. All Rights Reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-cc-amex"></i>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/home.js"></script>
<script>console.log('After home.js');</script>


    <script>
        document.querySelectorAll('.cart-item .update-button').forEach(button => {
            button.addEventListener('click', () => updateCartItem(button));
        });

        // Toggle mobile menu
        document.getElementById('mobileMenuToggle').addEventListener('click', function () {
            document.getElementById('mainNav').classList.toggle('active');
        });

        // Toggle account dropdown
        const accountBtn = document.getElementById('accountBtn');
        const accountDropdownContent = document.getElementById('accountDropdownContent');

        if (accountBtn) {
            accountBtn.addEventListener('click', function (e) {
                e.preventDefault();
                accountDropdownContent.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            window.addEventListener('click', function (e) {
                if (!e.target.matches('#accountBtn') && !e.target.closest('#accountDropdownContent')) {
                    if (accountDropdownContent.classList.contains('show')) {
                        accountDropdownContent.classList.remove('show');
                    }
                }
            });
        }

        // Cart functions
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAll');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');

            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });

            updateOrderSummary();
        }

        function decrementQuantity(itemId) {
            const quantityInput = document.getElementById('quantity-' + itemId);
            let quantity = parseInt(quantityInput.value);

            if (quantity > 1) {
                quantity--;
                quantityInput.value = quantity;
                document.getElementById('update-quantity-' + itemId).value = quantity;
            }
        }

        function incrementQuantity(itemId) {
            const quantityInput = document.getElementById('quantity-' + itemId);
            let quantity = parseInt(quantityInput.value);

            quantity++;
            quantityInput.value = quantity;
            document.getElementById('update-quantity-' + itemId).value = quantity;
        }
        function updateCartItem(button) {
            const itemElement = button.closest('.cart-item');
            const productId = itemElement.getAttribute('data-id');
            const size = itemElement.getAttribute('data-size');
            const color = itemElement.getAttribute('data-color');

            const quantityInput = itemElement.querySelector('.quantity-input');
            const quantity = parseInt(quantityInput.value);

            const priceText = itemElement.querySelector('.price-column').innerText.replace(/[₱,]/g, '');
            const price = parseFloat(priceText);

            const subtotal = (quantity * price).toFixed(2);
            itemElement.querySelector('.subtotal-column').innerText = `₱${subtotal}`;

            fetch('update_cart_quantity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    productId: productId,
                    size: size,
                    color: color,
                    quantity: quantity,
                    price: price
                })
            })
                .then(response => response.text())
                .then(data => {
                    console.log(data);
                    alert("Cart updated!");
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Failed to update cart.");
                });
        }
        function updateQuantity(itemId) {
            document.getElementById('update-form-' + itemId).submit();
        }

        // Update order summary based on selected items
        function updateOrderSummary() {
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const checkoutCheckboxes = document.querySelectorAll('.checkout-checkbox');
            const subtotalElements = document.querySelectorAll('.item-subtotal');

            let selectedItemsCount = 0;
            let selectedSubtotal = 0;
            let shippingFee = 100;

            // Update checkout checkboxes to match item checkboxes
            itemCheckboxes.forEach((checkbox, index) => {
                checkoutCheckboxes[index].checked = checkbox.checked;

                if (checkbox.checked) {
                    const itemId = checkbox.value;
                    const subtotalElement = document.querySelector(`.item-subtotal[data-id="${itemId}"]`);

                    if (subtotalElement) {
                        const price = parseFloat(subtotalElement.getAttribute('data-price'));
                        const quantity = parseInt(subtotalElement.getAttribute('data-quantity'));

                        selectedItemsCount += quantity;
                        selectedSubtotal += price * quantity;
                    }
                }
            });

            // Free shipping if subtotal >= 4000
            if (selectedSubtotal >= 4000) {
                shippingFee = 0;
                document.getElementById('free-shipping-notice').style.display = 'block';
                document.getElementById('shipping-threshold').style.display = 'none';
            } else {
                document.getElementById('free-shipping-notice').style.display = 'none';
                document.getElementById('shipping-threshold').style.display = 'block';

                // Update threshold bar
                const thresholdPercentage = Math.min(100, (selectedSubtotal / 4000) * 100);
                document.getElementById('threshold-progress').style.width = `${thresholdPercentage}%`;

                // Update amount needed for free shipping
                const amountForFreeShipping = 4000 - selectedSubtotal;
                document.getElementById('amount-for-free-shipping').textContent = `₱${amountForFreeShipping.toFixed(2)}`;
            }

            // Update total
            const total = selectedSubtotal + shippingFee;

            // Update display
            document.getElementById('selected-items-count').textContent = selectedItemsCount;
            document.getElementById('selected-subtotal').textContent = `₱${selectedSubtotal.toFixed(2)}`;
            document.getElementById('shipping-fee').textContent = shippingFee > 0 ? `₱${shippingFee.toFixed(2)}` : 'FREE';
            document.getElementById('total-amount').textContent = `₱${total.toFixed(2)}`;

            // Enable/disable checkout button
            const checkoutBtn = document.getElementById('checkout-btn');
            checkoutBtn.disabled = selectedItemsCount === 0;
        }

        // Submit the form for checkout
        function proceedToCheckout() {
            // Create a new form
            const checkoutForm = document.createElement('form');
            checkoutForm.method = 'post';
            checkoutForm.action = 'cart.php';

            // Add a checkout input
            const checkoutInput = document.createElement('input');
            checkoutInput.type = 'hidden';
            checkoutInput.name = 'checkout';
            checkoutInput.value = '1';
            checkoutForm.appendChild(checkoutInput);

            // Add selected items
            const checkoutCheckboxes = document.querySelectorAll('.checkout-checkbox:checked');
            checkoutCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_for_checkout[]';
                input.value = checkbox.value;
                checkoutForm.appendChild(input);
            });

            // Submit the form
            document.body.appendChild(checkoutForm);
            checkoutForm.submit();
        }

        // Initialize order summary on page load
        document.addEventListener('DOMContentLoaded', updateOrderSummary);
    </script>
</body>

</html>