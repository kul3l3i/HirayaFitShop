<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your environment-aware database connection
include 'db_connect.php';

// Initialize variables
$error = '';
$username_email = '';
// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$user = null;

// If user is not logged in, redirect to login page
if (!$loggedIn) {
    header("Location: sign-in.php");
    exit();
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

// Load all user transactions from XML
function loadUserTransactions($userId) {
    $transactions = [];
    
    if (file_exists('transaction.xml')) {
        $xml = simplexml_load_file('transaction.xml');
        
        foreach ($xml->transaction as $transaction) {
            if ((int) $transaction->user_id == $userId) {
                $transactions[] = [
                    'transaction_id' => (string) $transaction->transaction_id,
                    'transaction_date' => (string) $transaction->transaction_date,
                    'status' => (string) $transaction->status,
                    'subtotal' => (float) $transaction->subtotal,
                    'shipping_fee' => (float) $transaction->shipping_fee,
                    'total_amount' => (float) $transaction->total_amount,
                    'payment_method' => isset($transaction->payment_method) ? (string) $transaction->payment_method : '',
                    'shipping_info' => isset($transaction->shipping_info) ? parseShippingInfo($transaction->shipping_info) : null,
                    'items' => parseTransactionItems($transaction->items),
                    'item_count' => count($transaction->items->item)
                ];
            }
        }
        
        // Sort transactions by date (newest first)
        usort($transactions, function($a, $b) {
            return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
        });
    }
    
    return $transactions;
}

function parseShippingInfo($shippingNode) {
    if (!$shippingNode) {
        return null;
    }
    
    return [
        'fullname' => (string) $shippingNode->fullname,
        'email' => (string) $shippingNode->email,
        'phone' => (string) $shippingNode->phone,
        'address' => (string) $shippingNode->address,
        'city' => (string) $shippingNode->city,
        'postal_code' => (string) $shippingNode->postal_code,
        'notes' => (string) $shippingNode->notes
    ];
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

// Function to format payment method display
function formatPaymentMethod($paymentMethod) {
    switch ($paymentMethod) {
        case 'cod':
            return 'Cash on Delivery';
        case 'gcash':
            return 'GCash';
        case 'paymaya':
            return 'PayMaya';
        case 'bank_transfer':
            return 'Bank Transfer';
        default:
            return ucfirst($paymentMethod);
    }
}

// Function to format order status with appropriate colors
function formatOrderStatus($status) {
    switch ($status) {
        case 'pending':
            return '<span class="status-badge pending">Pending</span>';
        case 'processing':
            return '<span class="status-badge processing">Processing</span>';
        case 'shipped':
            return '<span class="status-badge shipped">Shipped</span>';
        case 'delivered':
            return '<span class="status-badge delivered">Delivered</span>';
        case 'cancelled':
            return '<span class="status-badge cancelled">Cancelled</span>';
        default:
            return '<span class="status-badge">' . ucfirst($status) . '</span>';
    }
}

// Load all user transactions
$transactions = loadUserTransactions($_SESSION['user_id']);

// Check if viewing a specific order
$viewingOrder = false;
$currentOrder = null;

if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $orderID = $_GET['order_id'];
    
    // Find the specific order
    foreach ($transactions as $transaction) {
        if ($transaction['transaction_id'] == $orderID) {
            $currentOrder = $transaction;
            $viewingOrder = true;
            break;
        }
    }
}

// Calculate expected delivery days based on status
function getExpectedDelivery($status, $orderDate) {
    switch ($status) {
        case 'pending':
        case 'processing':
            // 5-7 business days from order date
            $minDate = date('F d, Y', strtotime($orderDate . ' +5 weekday'));
            $maxDate = date('F d, Y', strtotime($orderDate . ' +7 weekday'));
            return $minDate . ' - ' . $maxDate;
        case 'shipped':
            // 1-3 business days from now
            $minDate = date('F d, Y', strtotime('+1 weekday'));
            $maxDate = date('F d, Y', strtotime('+3 weekday'));
            return $minDate . ' - ' . $maxDate;
        case 'delivered':
            return 'Delivered';
        case 'cancelled':
            return 'Cancelled';
        default:
            return 'Processing';
    }
}

// Get progress value based on status
function getProgressValue($status) {
    switch ($status) {
        case 'pending':
            return 25;
        case 'processing':
            return 50;
        case 'shipped':
            return 75;
        case 'delivered':
            return 100;
        case 'cancelled':
            return 0;
        default:
            return 25;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - HirayaFit</title>    <link rel="icon" href="images/logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <link rel="stylesheet" href="style/orders.css">
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
                    <a href="sign-up.php">Register</a>
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
            <a href="usershop.php" class="logo">Hiraya<span>Fit</span></a>

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
                                    <a href="orders.php" class="active"><i class="fas fa-box"></i> My Orders</a>
        
                                   
                                
                                    <div class="sign-out-btn">
                                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Login link for non-logged-in users -->
                        <a href="sign-in.php"><i class="fas fa-user-circle"></i></a>
                    <?php endif; ?>
                    <a href="messagesUser.php"><i class="fas fa-envelope"></i></a>

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

    <!-- Orders Section -->
    <section class="orders-section">
        <div class="container">
            <div class="page-header">
                <h1>My Orders</h1>
                <p>Track and manage your orders</p>
            </div>

            <?php if (empty($transactions)): ?>
                <!-- No Orders View -->
                <div class="no-orders">
                    <div class="no-orders-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h2>No Orders Yet</h2>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
                    <a   href="usershop.php" class="btn-start-shopping">Start Shopping</a>
                </div>
            <?php elseif ($viewingOrder && $currentOrder): ?>
                <!-- Order Detail View -->
                <div class="order-detail-container">
                    <div class="order-detail-header">
                        <div class="back-to-orders">
                            <a href="orders.php"><i class="fas fa-arrow-left"></i> Back to Orders</a>
                        </div>
                        <div class="order-header-info">
                            <h2>Order #<?php echo substr($currentOrder['transaction_id'], 0, 8); ?></h2>
                            <div class="order-meta">
                                <span>Placed on <?php echo date('F d, Y', strtotime($currentOrder['transaction_date'])); ?></span>
                                <div class="order-status-indicator">
                                    <?php echo formatOrderStatus($currentOrder['status']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-progress">
                        <div class="order-track">
                            <div class="order-track-step <?php echo ($currentOrder['status'] != 'cancelled') ? 'active' : ''; ?>">
                                <div class="order-track-status">
                                    <span class="order-track-status-dot"></span>
                                    <span class="order-track-status-line"></span>
                                </div>
                                <div class="order-track-text">
                                    <p class="order-track-text-stat">Order Placed</p>
                                    <p class="order-track-text-sub"><?php echo date('M d, Y', strtotime($currentOrder['transaction_date'])); ?></p>
                                </div>
                            </div>
                            <div class="order-track-step <?php echo (in_array($currentOrder['status'], ['processing', 'shipped', 'delivered'])) ? 'active' : ''; ?>">
                                <div class="order-track-status">
                                    <span class="order-track-status-dot"></span>
                                    <span class="order-track-status-line"></span>
                                </div>
                                <div class="order-track-text">
                                    <p class="order-track-text-stat">Processing</p>
                                    <p class="order-track-text-sub"><?php echo ($currentOrder['status'] != 'pending' && $currentOrder['status'] != 'cancelled') ? date('M d, Y', strtotime($currentOrder['transaction_date'] . ' +1 day')) : '-'; ?></p>
                                </div>
                            </div>
                            <div class="order-track-step <?php echo (in_array($currentOrder['status'], ['shipped', 'delivered'])) ? 'active' : ''; ?>">
                                <div class="order-track-status">
                                    <span class="order-track-status-dot"></span>
                                    <span class="order-track-status-line"></span>
                                </div>
                                <div class="order-track-text">
                                    <p class="order-track-text-stat">Shipped</p>
                                    <p class="order-track-text-sub"><?php echo ($currentOrder['status'] == 'shipped' || $currentOrder['status'] == 'delivered') ? date('M d, Y', strtotime($currentOrder['transaction_date'] . ' +3 day')) : '-'; ?></p>
                                </div>
                            </div>
                            <div class="order-track-step <?php echo ($currentOrder['status'] == 'delivered') ? 'active' : ''; ?>">
                                <div class="order-track-status">
                                    <span class="order-track-status-dot"></span>
                                </div>
                                <div class="order-track-text">
                                    <p class="order-track-text-stat">Delivered</p>
                                    <p class="order-track-text-sub"><?php echo ($currentOrder['status'] == 'delivered') ? date('M d, Y', strtotime($currentOrder['transaction_date'] . ' +5 day')) : '-'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-details-grid">
                        <div class="order-details-main">
                            <div class="detail-section">
                                <h3>Items in Your Order</h3>
                                <div class="order-items-list">
                                    <?php foreach ($currentOrder['items'] as $item): ?>
                                        <div class="order-item-detail">
                                            <div class="item-image placeholder"></div>
                                            <div class="item-info">
                                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                                <div class="item-meta">
                                                    <span>Color: <?php echo htmlspecialchars($item['color']); ?></span>
                                                    <span>Size: <?php echo htmlspecialchars($item['size']); ?></span>
                                                </div>
                                                <div class="item-price">
                                                    <span class="price">₱<?php echo number_format($item['price'], 2); ?></span>
                                                    <span class="quantity">Qty: <?php echo $item['quantity']; ?></span>
                                                </div>
                                            </div>
                                            <div class="item-subtotal">
                                                ₱<?php echo number_format($item['subtotal'], 2); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($currentOrder['status'] == 'delivered'): ?>
                                <div class="review-products-section">
                                    <h3>Review Your Purchase</h3>
                                    <p>How was your experience? Share your thoughts to help others make better shopping decisions.</p>
                                    <button class="btn-write-review"><i class="fas fa-star"></i> Write a Review</button>
                                </div>
                            <?php endif; ?>

                            <?php if ($currentOrder['status'] == 'pending'): ?>
                                <div class="cancel-order-section">
                                    <h3>Need to Cancel?</h3>
                                    <p>You can cancel your order before it's processed. Once shipped, cancellation won't be possible.</p>
                                    <button class="btn-cancel-order"><i class="fas fa-times-circle"></i> Cancel Order</button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="order-details-sidebar">
                            <div class="sidebar-section">
                                <h3>Order Summary</h3>
                                <div class="order-summary-detail">
                                    <div class="summary-row">
                                        <span>Subtotal</span>
                                        <span>₱<?php echo number_format($currentOrder['subtotal'], 2); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Shipping</span>
                                        <span>
                                            <?php if ($currentOrder['shipping_fee'] > 0): ?>
                                                ₱<?php echo number_format($currentOrder['shipping_fee'], 2); ?>
                                            <?php else: ?>
                                                FREE
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="summary-total">
                                        <span>Total</span>
                                        <span>₱<?php echo number_format($currentOrder['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="sidebar-section">
                                <h3>Shipping Information</h3>
                                <div class="shipping-details">
                                    <?php if ($currentOrder['shipping_info']): ?>
                                        <p><strong><?php echo htmlspecialchars($currentOrder['shipping_info']['fullname']); ?></strong></p>
                                        <p><?php echo htmlspecialchars($currentOrder['shipping_info']['address']); ?></p>
                                        <p><?php echo htmlspecialchars($currentOrder['shipping_info']['city']) . ' ' . htmlspecialchars($currentOrder['shipping_info']['postal_code']); ?></p>
                                        <p>Phone: <?php echo htmlspecialchars($currentOrder['shipping_info']['phone']); ?></p>
                                        <?php if (!empty($currentOrder['shipping_info']['notes'])): ?>
                                            <p class="shipping-notes">Notes: <?php echo htmlspecialchars($currentOrder['shipping_info']['notes']); ?></p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p>Shipping details not available</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="sidebar-section">
                                <h3>Payment Method</h3>
                                <div class="payment-details">
                                    <p><?php echo formatPaymentMethod($currentOrder['payment_method'] ?? 'N/A'); ?></p>
                                </div>
                            </div>

                            <div class="sidebar-section">
                                <h3>Need Help?</h3>
                                <div class="help-options">
                                    <a href="#" class="help-link"><i class="fas fa-question-circle"></i> Contact Support</a>
                                    <a href="#" class="help-link"><i class="fas fa-file-alt"></i> Return Policy</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Orders List View -->
                <div class="orders-filter">
                    <div class="filter-options">
                        <a href="?filter=all" class="<?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'active' : ''; ?>">All Orders</a>
                        <a href="?filter=processing" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'processing') ? 'active' : ''; ?>">Processing</a>
                        <a href="?filter=shipped" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'shipped') ? 'active' : ''; ?>">Shipped</a>
                        <a href="?filter=delivered" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'delivered') ? 'active' : ''; ?>">Delivered</a>
                        <a href="?filter=cancelled" class="<?php echo (isset($_GET['filter']) && $_GET['filter'] == 'cancelled') ? 'active' : ''; ?>">Cancelled</a>
                    </div>
                    <div class="search-orders">
                        <input type="text" placeholder="Search orders" id="orderSearchInput">
                        <button id="orderSearchBtn"><i class="fas fa-search"></i></button>
                    </div>
                </div>

                <div class="orders-list">
                    <?php 
                    // Filter transactions if filter is set
                    $filteredTransactions = $transactions;
                    if (isset($_GET['filter']) && $_GET['filter'] != 'all') {
                        $filter = $_GET['filter'];
                        $filteredTransactions = array_filter($transactions, function($transaction) use ($filter) {
                            return $transaction['status'] == $filter;
                        });
                    }
                    
                    if (empty($filteredTransactions)): 
                    ?>
                        <div class="no-filtered-orders">
                            <p>No orders found with the selected filter. <a href="?filter=all">View all orders</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($filteredTransactions as $transaction): ?>
                            <div class="order-card" data-order-id="<?php echo $transaction['transaction_id']; ?>">
                                <div class="order-header">
                                    <div class="order-date">
                                        <span><i class="far fa-calendar-alt"></i> Ordered on <?php echo date('F d, Y', strtotime($transaction['transaction_date'])); ?></span>
                                    </div>
                                    <div class="order-status">
                                        <?php echo formatOrderStatus($transaction['status']); ?>
                                    </div>
                                </div>
                                <div class="order-content">
                                    <div class="order-products">
                                        <?php 
                                        // Display first item details
                                        $firstItem = $transaction['items'][0];
                                        ?>
                                        <div class="product-thumbnail placeholder"></div>
                                        <div class="product-details">
                                            <h3><?php echo htmlspecialchars($firstItem['product_name']); ?></h3>
                                            <p>Color: <?php echo htmlspecialchars($firstItem['color']); ?>, Size: <?php echo htmlspecialchars($firstItem['size']); ?></p>
                                            <?php if (count($transaction['items']) > 1): ?>
                                                <p class="additional-items">+<?php echo count($transaction['items']) - 1; ?> more items</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="order-summary-info">
                                        <div class="order-amount">
                                            <span class="label">Total:</span>
                                            <span class="amount">₱<?php echo number_format($transaction['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="order-delivery">
                                            <span class="label">Delivery:</span>
                                            <span class="delivery-date"><?php echo getExpectedDelivery($transaction['status'], $transaction['transaction_date']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="order-actions">
                                    <a href="orders.php?order_id=<?php echo $transaction['transaction_id']; ?>" class="btn-view-order">View Order Details</a>
                                    <?php if ($transaction['status'] == 'delivered'): ?>
                                        <button class="btn-buy-again"><i class="fas fa-redo-alt"></i> Buy Again</button>
                                    <?php endif; ?>
                                    <?php if ($transaction['status'] == 'pending'): ?>
                                        <button class="btn-cancel-order-small"><i class="fas fa-times"></i> Cancel</button>
                                    <?php endif; ?>
                                </div>
                                <div class="order-progress-bar">
                                    <div class="progress" style="width: <?php echo getProgressValue($transaction['status']); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer would go here -->

    <script>
        // Account dropdown functionality
        document.addEventListener('DOMContentLoaded', function () {
            const accountBtn = document.getElementById('accountBtn');
            const accountDropdown = document.getElementById('accountDropdown');

            if (accountBtn) {
                accountBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    accountDropdown.classList.toggle('active');
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (accountDropdown && !accountDropdown.contains(e.target) && e.target !== accountBtn) {
                    accountDropdown.classList.remove('active');
                }
            });

            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mainNav = document.getElementById('mainNav');

            if (mobileMenuToggle && mainNav) {
                mobileMenuToggle.addEventListener('click', function () {
                    mainNav.classList.toggle('active');
                });
            }
        });
        // Search orders functionality
        const orderSearchInput = document.getElementById('orderSearchInput');
        const orderSearchBtn = document.getElementById('orderSearchBtn');

        if (orderSearchBtn && orderSearchInput) {
            orderSearchBtn.addEventListener('click', searchOrders);
            orderSearchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    searchOrders();
                }
            });
        }

        function searchOrders() {
            const searchQuery = orderSearchInput.value.toLowerCase();
            const orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach(card => {
                const orderText = card.textContent.toLowerCase();
                if (orderText.includes(searchQuery)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Cancel order confirmation
        const cancelButtons = document.querySelectorAll('.btn-cancel-order, .btn-cancel-order-small');
        
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                    alert('Order cancellation request sent. You will receive a confirmation by email shortly.');
                    // In a real implementation, you would send an AJAX request to a server endpoint to cancel the order
                }
            });
        });

        // Buy again functionality
        const buyAgainButtons = document.querySelectorAll('.btn-buy-again');
        
        buyAgainButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.closest('.order-card').getAttribute('data-order-id');
                alert('Adding items to cart...');
                // In a real implementation, you would send an AJAX request to add the items from this order to the cart
            });
        });

        // Review functionality - modal would be implemented here
        const reviewButtons = document.querySelectorAll('.btn-write-review');
        
        reviewButtons.forEach(button => {
            button.addEventListener('click', function() {
                alert('Review feature coming soon!');
                // In a real implementation, you would show a modal with a review form
            });
        });
    </script>
</body>

</html>