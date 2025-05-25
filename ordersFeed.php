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
try {
    $stmt = $conn->prepare("SELECT id, fullname, username, email, address, phone, profile_image FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    // If user exists, store their details
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        // User not found in database, destroy session and redirect
        session_destroy();
        header("Location: sign-in.php?error=user_not_found");
        exit();
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Database error in orders.php: " . $e->getMessage());
    $error = "Database connection error. Please try again later.";
}

$conn->close();

// Function to safely load XML file
function loadXMLFile($filename) {
    if (!file_exists($filename)) {
        return false;
    }
    
    if (!is_readable($filename)) {
        error_log("XML file is not readable: " . $filename);
        return false;
    }
    
    // Disable external entity loading for security
    libxml_disable_entity_loader(true);
    
    try {
        $xmlContent = file_get_contents($filename);
        if ($xmlContent === false) {
            error_log("Failed to read XML file: " . $filename);
            return false;
        }
        
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            error_log("Failed to parse XML file: " . $filename);
            return false;
        }
        
        return $xml;
    } catch (Exception $e) {
        error_log("Exception loading XML file {$filename}: " . $e->getMessage());
        return false;
    }
}

// Load all user transactions from XML
function loadUserTransactions($userId) {
    $transactions = [];
    
    $xml = loadXMLFile('transaction.xml');
    if ($xml === false) {
        return $transactions; // Return empty array if file doesn't exist or can't be loaded
    }
    
    if (!isset($xml->transaction)) {
        return $transactions; // No transactions in XML
    }
    
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
                'items' => parseTransactionItems($transaction->items ?? new SimpleXMLElement('<items></items>')),
                'item_count' => isset($transaction->items->item) ? count($transaction->items->item) : 0
            ];
        }
    }
    
    // Sort transactions by date (newest first)
    usort($transactions, function($a, $b) {
        return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
    });
    
    return $transactions;
}

function parseShippingInfo($shippingNode) {
    if (!$shippingNode) {
        return null;
    }
    
    return [
        'fullname' => (string) ($shippingNode->fullname ?? ''),
        'email' => (string) ($shippingNode->email ?? ''),
        'phone' => (string) ($shippingNode->phone ?? ''),
        'address' => (string) ($shippingNode->address ?? ''),
        'city' => (string) ($shippingNode->city ?? ''),
        'postal_code' => (string) ($shippingNode->postal_code ?? ''),
        'notes' => (string) ($shippingNode->notes ?? '')
    ];
}

function parseTransactionItems($itemsNode) {
    $items = [];
    
    if (!$itemsNode || !isset($itemsNode->item)) {
        return $items;
    }
    
    foreach ($itemsNode->item as $item) {
        $items[] = [
            'product_id' => (string) ($item->product_id ?? ''),
            'product_name' => (string) ($item->product_name ?? ''),
            'price' => (float) ($item->price ?? 0),
            'quantity' => (int) ($item->quantity ?? 1),
            'color' => (string) ($item->color ?? ''),
            'size' => (string) ($item->size ?? ''),
            'subtotal' => (float) ($item->subtotal ?? 0)
        ];
    }
    
    return $items;
}

// Function to format payment method display
function formatPaymentMethod($paymentMethod) {
    $methods = [
        'cod' => 'Cash on Delivery',
        'gcash' => 'GCash',
        'paymaya' => 'PayMaya',
        'bank_transfer' => 'Bank Transfer'
    ];
    
    return $methods[$paymentMethod] ?? ucfirst(str_replace('_', ' ', $paymentMethod));
}

// Function to format order status with appropriate colors
function formatOrderStatus($status) {
    $statusMap = [
        'pending' => '<span class="status-badge pending">Pending</span>',
        'processing' => '<span class="status-badge processing">Processing</span>',
        'shipped' => '<span class="status-badge shipped">Shipped</span>',
        'delivered' => '<span class="status-badge delivered">Delivered</span>',
        'cancelled' => '<span class="status-badge cancelled">Cancelled</span>'
    ];
    
    return $statusMap[$status] ?? '<span class="status-badge">' . htmlspecialchars(ucfirst($status)) . '</span>';
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
    $progressMap = [
        'pending' => 25,
        'processing' => 50,
        'shipped' => 75,
        'delivered' => 100,
        'cancelled' => 0
    ];
    
    return $progressMap[$status] ?? 25;
}

// Function to load reviews from XML
function loadReviews() {
    $reviews = [];
    
    $xml = loadXMLFile('reviews.xml');
    if ($xml === false) {
        return $reviews;
    }
    
    if (isset($xml->review)) {
        foreach ($xml->review as $review) {
            $reviews[] = [
                'review_id' => (string) ($review->review_id ?? ''),
                'user_id' => (int) ($review->user_id ?? 0),
                'transaction_id' => (string) ($review->transaction_id ?? ''),
                'product_id' => (string) ($review->product_id ?? ''),
                'rating' => (int) ($review->rating ?? 0),
                'review_text' => (string) ($review->review_text ?? ''),
                'review_date' => (string) ($review->review_date ?? ''),
                'username' => (string) ($review->username ?? '')
            ];
        }
    }
    
    return $reviews;
}

// Function to save review to XML
function saveReview($userId, $transactionId, $productId, $rating, $reviewText, $username) {
    $reviewId = 'REV_' . time() . '_' . rand(1000, 9999);
    $reviewDate = date('Y-m-d H:i:s');
    $xmlFile = 'reviews.xml';
    
    // Load existing XML or create new one
    $xml = loadXMLFile($xmlFile);
    if ($xml === false) {
        // Create new XML structure
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><reviews></reviews>');
    }
    
    // Add new review
    $review = $xml->addChild('review');
    $review->addChild('review_id', htmlspecialchars($reviewId, ENT_XML1, 'UTF-8'));
    $review->addChild('user_id', intval($userId));
    $review->addChild('transaction_id', htmlspecialchars($transactionId, ENT_XML1, 'UTF-8'));
    $review->addChild('product_id', htmlspecialchars($productId, ENT_XML1, 'UTF-8'));
    $review->addChild('rating', intval($rating));
    $review->addChild('review_text', htmlspecialchars($reviewText, ENT_XML1, 'UTF-8'));
    $review->addChild('review_date', $reviewDate);
    $review->addChild('username', htmlspecialchars($username, ENT_XML1, 'UTF-8'));
    
    // Save XML file
    try {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        
        // Attempt to save the file
        $result = $dom->save($xmlFile);
        
        if ($result === false) {
            error_log("Failed to save reviews XML file: " . $xmlFile);
            return false;
        }
        
        // Verify the file was written correctly
        if (!file_exists($xmlFile) || filesize($xmlFile) == 0) {
            error_log("Reviews XML file appears to be empty after save: " . $xmlFile);
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Exception while saving review: " . $e->getMessage());
        return false;
    }
}

// Function to check if user has already reviewed a product from a specific transaction
function hasUserReviewed($userId, $transactionId, $productId) {
    $reviews = loadReviews();
    
    foreach ($reviews as $review) {
        if ($review['user_id'] == $userId && 
            $review['transaction_id'] == $transactionId && 
            $review['product_id'] == $productId) {
            return true;
        }
    }
    
    return false;
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Load all user transactions
$transactions = [];
if ($user) {
    $transactions = loadUserTransactions($_SESSION['user_id']);
}

// Check if viewing a specific order
$viewingOrder = false;
$currentOrder = null;

if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $orderID = htmlspecialchars($_GET['order_id'], ENT_QUOTES, 'UTF-8');
    
    // Find the specific order
    foreach ($transactions as $transaction) {
        if ($transaction['transaction_id'] == $orderID) {
            $currentOrder = $transaction;
            $viewingOrder = true;
            break;
        }
    }
    
    // If order not found, redirect to orders page
    if (!$viewingOrder) {
        header("Location: orders.php?error=order_not_found");
        exit();
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $response = ['success' => false, 'message' => ''];
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $transactionId = trim($_POST['transaction_id'] ?? '');
        $productId = trim($_POST['product_id'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $reviewText = trim($_POST['review_text'] ?? '');
        
        // Input validation
        if (empty($transactionId) || empty($productId)) {
            $response['message'] = 'Invalid transaction or product information.';
        } elseif ($rating < 1 || $rating > 5) {
            $response['message'] = 'Please select a rating between 1 and 5 stars.';
        } elseif (empty($reviewText)) {
            $response['message'] = 'Please write a review.';
        } elseif (strlen($reviewText) > 500) {
            $response['message'] = 'Review text cannot exceed 500 characters.';
        } elseif (hasUserReviewed($_SESSION['user_id'], $transactionId, $productId)) {
            $response['message'] = 'You have already reviewed this product.';
        } else {
            // Verify that the user actually purchased this product in this transaction
            $validPurchase = false;
            foreach ($transactions as $transaction) {
                if ($transaction['transaction_id'] == $transactionId) {
                    foreach ($transaction['items'] as $item) {
                        if ($item['product_id'] == $productId) {
                            $validPurchase = true;
                            break 2;
                        }
                    }
                }
            }
            
            if (!$validPurchase) {
                $response['message'] = 'You can only review products you have purchased.';
            } else {
                // Save the review
                if (saveReview($_SESSION['user_id'], $transactionId, $productId, $rating, $reviewText, $user['username'])) {
                    $response['success'] = true;
                    $response['message'] = 'Thank you for your review!';
                } else {
                    $response['message'] = 'Failed to save review. Please try again.';
                }
            }
        }
    }
    
    // Return JSON response for AJAX
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Set flash message for regular form submission
    $_SESSION['review_message'] = $response['message'];
    $_SESSION['review_success'] = $response['success'];
    
    // Redirect to prevent resubmission
    $redirectUrl = !empty($transactionId) ? "orders.php?order_id=" . urlencode($transactionId) : "orders.php";
    header("Location: " . $redirectUrl);
    exit;
}

// Generate CSRF token for forms
$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - HirayaFit</title>    <link rel="icon" href="images/hf.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <link rel="stylesheet" href="style/orders.css">
    <style>
        /* Review Modal Styles */
        .review-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .review-modal-overlay.show {
            display: flex;
            opacity: 1;
        }

        .review-modal {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .review-modal-overlay.show .review-modal {
            transform: scale(1);
        }

        .review-modal-header {
            padding: 24px 24px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 24px;
        }

        .review-modal-header h2 {
            margin: 0 0 8px;
            color: #333;
            font-size: 24px;
        }

        .review-modal-header p {
            margin: 0 0 16px;
            color: #666;
            font-size: 14px;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background-color: #f5f5f5;
            color: #333;
        }

        .review-modal-body {
            padding: 0 24px 24px;
        }

        .product-review-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9f9f9;
        }

        .product-review-item:last-child {
            margin-bottom: 0;
        }

        .product-info {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #ddd;
        }

        .product-image {
            width: 60px;
            height: 60px;
            background: #ddd;
            border-radius: 8px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .product-details h4 {
            margin: 0 0 4px;
            color: #333;
            font-size: 16px;
        }

        .product-details p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .rating-section {
            margin-bottom: 16px;
        }

        .rating-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .star-rating {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
        }

        .star {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .star:hover,
        .star.active {
            color: #ffd700;
        }

        .rating-text {
            font-size: 12px;
            color: #666;
            margin-left: 8px;
        }

        .review-text-section {
            margin-bottom: 20px;
        }

        .review-text-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .review-textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.2s ease;
            box-sizing: border-box;
        }

        .review-textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
        }

        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .existing-review {
            background: #e8f5e8;
            border-color: #4caf50;
            position: relative;
        }

        .existing-review::before {
            content: '✓';
            position: absolute;
            top: 12px;
            right: 12px;
            background: #4caf50;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .existing-review-content {
            margin-top: 16px;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .existing-rating {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .existing-rating .star {
            font-size: 16px;
            cursor: default;
        }

        .existing-review-text {
            color: #333;
            font-size: 14px;
            line-height: 1.4;
        }

        .review-date {
            color: #666;
            font-size: 12px;
            margin-top: 8px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid #eee;
        }

        .btn-cancel {
            padding: 10px 20px;
            border: 1px solid #ddd;
            background: white;
            color: #666;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #f5f5f5;
            border-color: #999;
        }

        .btn-submit-reviews {
            padding: 10px 24px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }

        .btn-submit-reviews:hover {
            background: #0056b3;
        }

        .btn-submit-reviews:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .review-modal {
                margin: 20px;
                max-height: calc(100vh - 40px);
            }
            
            .review-modal-header,
            .review-modal-body {
                padding-left: 16px;
                padding-right: 16px;
            }
        }

        /* Demo styles for the button */
        .demo-container {
            padding: 50px;
            text-align: center;
        }

        .btn-write-review {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-write-review:hover {
            background: #0056b3;
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
                                    <a href="orders.php" class="active"><i class="fas fa-box"></i> My Orders</a>
        
                                    <a href="settings.php"><i class="fas fa-cog"></i> Account Settings</a>
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


<!-- Add this modal HTML before the closing body tag -->

<!-- Review Modal -->
<div class="review-modal-overlay" id="reviewModal">
    <div class="review-modal">
        <div class="review-modal-header">
            <h2>Write a Review</h2>
            <p>Share your experience with your purchased items</p>
            <button class="modal-close" onclick="closeReviewModal()">×</button>
        </div>
        <div class="review-modal-body">
            <div id="reviewAlert"></div>
            <form id="reviewForm" method="POST">
                <input type="hidden" name="ajax" value="1">
                <div id="reviewProductsContainer">
                    <!-- Products will be dynamically loaded here -->
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeReviewModal()">Cancel</button>
                    <button type="submit" class="btn-submit-reviews" id="submitReviewsBtn">
                        Submit Reviews
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Review Modal JavaScript
let currentOrderForReview = null;

// Open review modal
function openReviewModal(orderData) {
    currentOrderForReview = orderData;
    const modal = document.getElementById('reviewModal');
    const container = document.getElementById('reviewProductsContainer');
    
    // Clear previous content
    container.innerHTML = '';
    
    // Add each product for review
    orderData.items.forEach(item => {
        const hasExistingReview = orderData.reviews && orderData.reviews[item.product_id];
        
        const productReviewHtml = `
            <div class="product-review-item ${hasExistingReview ? 'existing-review' : ''}" data-product-id="${item.product_id}">
                <div class="product-info">
                    <div class="product-image">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="product-details">
                        <h4>${escapeHtml(item.product_name)}</h4>
                        <p>Color: ${escapeHtml(item.color)}, Size: ${escapeHtml(item.size)}</p>
                    </div>
                </div>
                
                ${hasExistingReview ? `
                    <div class="existing-review-content">
                        <div class="existing-rating">
                            ${generateStarDisplay(orderData.reviews[item.product_id].rating)}
                            <span class="rating-text">${orderData.reviews[item.product_id].rating}/5 stars</span>
                        </div>
                        <div class="existing-review-text">${escapeHtml(orderData.reviews[item.product_id].review_text)}</div>
                        <div class="review-date">Reviewed on ${formatDate(orderData.reviews[item.product_id].review_date)}</div>
                    </div>
                ` : `
                    <div class="rating-section">
                        <label>Rating *</label>
                        <div class="star-rating" data-product-id="${item.product_id}">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <span class="rating-text" id="ratingText_${item.product_id}">Select a rating</span>
                        <input type="hidden" name="ratings[${item.product_id}]" id="rating_${item.product_id}" value="0">
                        <input type="hidden" name="transaction_id" value="${orderData.transaction_id}">
                    </div>
                    
                    <div class="review-text-section">
                        <label for="review_${item.product_id}">Your Review *</label>
                        <textarea 
                            class="review-textarea" 
                            id="review_${item.product_id}" 
                            name="reviews[${item.product_id}]" 
                            placeholder="Share your experience with this product..."
                            maxlength="500"
                            required
                        ></textarea>
                        <div class="char-counter">
                            <span id="charCount_${item.product_id}">0</span>/500 characters
                        </div>
                    </div>
                `}
            </div>
        `;
        
        container.innerHTML += productReviewHtml;
    });
    
    // Show modal
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Initialize star ratings and character counters for new reviews
    initializeReviewControls();
}

// Close review modal
function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    modal.classList.remove('show');
    document.body.style.overflow = '';
    
    // Clear form
    document.getElementById('reviewForm').reset();
    document.getElementById('reviewAlert').innerHTML = '';
}

// Initialize review controls
function initializeReviewControls() {
    // Star rating functionality
    document.querySelectorAll('.star-rating').forEach(ratingContainer => {
        const productId = ratingContainer.getAttribute('data-product-id');
        const stars = ratingContainer.querySelectorAll('.star');
        const ratingInput = document.getElementById(`rating_${productId}`);
        const ratingText = document.getElementById(`ratingText_${productId}`);
        
        stars.forEach((star, index) => {
            star.addEventListener('mouseover', () => {
                highlightStars(stars, index + 1);
            });
            
            star.addEventListener('mouseout', () => {
                const currentRating = parseInt(ratingInput.value) || 0;
                highlightStars(stars, currentRating);
            });
            
            star.addEventListener('click', () => {
                const rating = index + 1;
                ratingInput.value = rating;
                highlightStars(stars, rating);
                updateRatingText(ratingText, rating);
            });
        });
    });
    
    // Character counter functionality
    document.querySelectorAll('.review-textarea').forEach(textarea => {
        const productId = textarea.id.split('_')[1];
        const charCounter = document.getElementById(`charCount_${productId}`);
        
        textarea.addEventListener('input', () => {
            const count = textarea.value.length;
            charCounter.textContent = count;
            
            if (count > 450) {
                charCounter.style.color = '#ff6b6b';
            } else {
                charCounter.style.color = '#666';
            }
        });
    });
}

// Highlight stars
function highlightStars(stars, rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

// Update rating text
function updateRatingText(textElement, rating) {
    const ratingTexts = {
        1: '1/5 stars - Poor',
        2: '2/5 stars - Fair', 
        3: '3/5 stars - Good',
        4: '4/5 stars - Very Good',
        5: '5/5 stars - Excellent'
    };
    textElement.textContent = ratingTexts[rating] || 'Select a rating';
}

// Generate star display for existing reviews
function generateStarDisplay(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<span class="star ${i <= rating ? 'active' : ''}"">★</span>`;
    }
    return stars;
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Handle form submission
document.getElementById('reviewForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitReviewsBtn');
    const alertContainer = document.getElementById('reviewAlert');
    
    // Validate form
    let isValid = true;
    let errorMessage = '';
    
    // Check each product that doesn't have an existing review
    document.querySelectorAll('.product-review-item:not(.existing-review)').forEach(item => {
        const productId = item.getAttribute('data-product-id');
        const rating = document.getElementById(`rating_${productId}`).value;
        const reviewText = document.getElementById(`review_${productId}`).value.trim();
        
        if (parseInt(rating) === 0) {
            isValid = false;
            errorMessage = 'Please provide a rating for all products.';
        }
        
        if (reviewText === '') {
            isValid = false;
            errorMessage = 'Please write a review for all products.';
        }
    });
    
    if (!isValid) {
        showAlert(alertContainer, errorMessage, 'error');
        return;
    }
    
    // Show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading-spinner"></span>Submitting...';
    
    try {
        // Submit each review separately
        const reviewPromises = [];
        
        document.querySelectorAll('.product-review-item:not(.existing-review)').forEach(item => {
            const productId = item.getAttribute('data-product-id');
            const rating = document.getElementById(`rating_${productId}`).value;
            const reviewText = document.getElementById(`review_${productId}`).value.trim();
            
            const formData = new FormData();
            formData.append('submit_review', '1');
            formData.append('ajax', '1');
            formData.append('transaction_id', currentOrderForReview.transaction_id);
            formData.append('product_id', productId);
            formData.append('rating', rating);
            formData.append('review_text', reviewText);
            
            reviewPromises.push(
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
            );
        });
        
        const results = await Promise.all(reviewPromises);
        
        // Check if all reviews were successful
        const allSuccessful = results.every(result => result.success);
        
        if (allSuccessful) {
            showAlert(alertContainer, 'Thank you for your reviews! They have been submitted successfully.', 'success');
            
            // Close modal after 2 seconds
            setTimeout(() => {
                closeReviewModal();
                // Refresh page to show updated review status
                window.location.reload();
            }, 2000);
        } else {
            const failedReviews = results.filter(result => !result.success);
            showAlert(alertContainer, `Some reviews failed to submit: ${failedReviews[0].message}`, 'error');
        }
        
    } catch (error) {
        showAlert(alertContainer, 'An error occurred while submitting your reviews. Please try again.', 'error');
        console.error('Review submission error:', error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Reviews';
    }
});

// Show alert
function showAlert(container, message, type) {
    container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Update the existing review button click handler
document.addEventListener('DOMContentLoaded', function() {
    // Replace the existing review button functionality
    const reviewButtons = document.querySelectorAll('.btn-write-review');
    
    reviewButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get current order data
            const orderData = {
                transaction_id: '<?php echo $currentOrder["transaction_id"] ?? ""; ?>',
                items: <?php echo json_encode($currentOrder["items"] ?? []); ?>,
                reviews: <?php echo json_encode($orderReviews ?? []); ?>
            };
            
            if (orderData.transaction_id && orderData.items.length > 0) {
                openReviewModal(orderData);
            } else {
                alert('Unable to load order information for reviews.');
            }
        });
    });
});

// Close modal when clicking outside
document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReviewModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('reviewModal').classList.contains('show')) {
        closeReviewModal();
    }
});
</script>
</body>

</html>