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

// If user is logged in, fetch their information
if ($loggedIn) {
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
    $cartItems = [];
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return $cartItems;
    }
    
    $userId = $_SESSION['user_id'];

    if (file_exists('cart.xml')) {
        $xml = simplexml_load_file('cart.xml');
        
        if ($xml) {
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - HirayaFit</title>    <link rel="icon" href="images/logo.png">
    <link rel="icon" href="images/logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
   <style>
    /* Cart Page Styles */
:root {
    --primary: #111111;
    --secondary: #0071c5;
    --accent: #e5e5e5;
    --light: #ffffff;
    --dark: #111111;
    --grey: #767676;
    --light-grey: #f5f5f5;
    --border-color: #e0e0e0;
    --sale-color: #0071c5;
    --price-color: #e63946;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --shadow-light: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-medium: 0 4px 16px rgba(0, 0, 0, 0.12);
    --shadow-heavy: 0 8px 32px rgba(0, 0, 0, 0.16);
    --border-radius: 12px;
    --border-radius-small: 8px;
    --transition: all 0.3s ease;
  }
  
  /* Cart Section */
  .cart-section {
    padding: 40px 0 80px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    min-height: calc(100vh - 200px);
  }
  
  .cart-section .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
  }
  
  .cart-section h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 40px;
    text-align: center;
    position: relative;
  }
  
  .cart-section h1::after {
    content: "";
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--secondary), var(--primary));
    border-radius: 2px;
  }
  
  /* Empty Cart State */
  .empty-cart {
    text-align: center;
    padding: 80px 20px;
    background: var(--light);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    max-width: 600px;
    margin: 0 auto;
  }
  
  .empty-cart i {
    font-size: 4rem;
    color: var(--grey);
    margin-bottom: 30px;
    opacity: 0.7;
  }
  
  .empty-cart h2 {
    font-size: 1.8rem;
    color: var(--dark);
    margin-bottom: 15px;
    font-weight: 600;
  }
  
  .empty-cart p {
    color: var(--grey);
    font-size: 1.1rem;
    margin-bottom: 30px;
    line-height: 1.6;
  }
  
  .btn-primary {
    display: inline-block;
    background: linear-gradient(135deg, var(--secondary), #005da6);
    color: var(--light);
    padding: 15px 30px;
    border-radius: var(--border-radius-small);
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: var(--transition);
    box-shadow: var(--shadow-light);
    border: none;
    cursor: pointer;
  }
  
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
    background: linear-gradient(135deg, #005da6, var(--secondary));
  }
  
  /* Cart Layout */
  .cart-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 40px;
    margin-top: 20px;
  }
  
  /* Cart Header */
  .cart-header {
    background: var(--light);
    padding: 25px 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border: 1px solid var(--border-color);
  }
  
  .select-all {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .select-all input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--secondary);
    cursor: pointer;
  }
  
  .select-all label {
    font-weight: 600;
    color: var(--dark);
    cursor: pointer;
    font-size: 1.1rem;
  }
  
  .cart-actions {
    display: flex;
    gap: 15px;
  }
  
  .btn-outline-danger {
    background: transparent;
    color: var(--danger-color);
    border: 2px solid var(--danger-color);
    padding: 10px 20px;
    border-radius: var(--border-radius-small);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  
  .btn-outline-danger:hover {
    background: var(--danger-color);
    color: var(--light);
    transform: translateY(-1px);
    box-shadow: var(--shadow-light);
  }
  
  /* Cart Items Container */
  .cart-items {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  
  /* Individual Cart Item */
  .cart-item {
    background: var(--light);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-light);
    border: 1px solid var(--border-color);
    padding: 25px;
    display: grid;
    grid-template-columns: auto 120px 1fr auto auto auto auto;
    gap: 25px;
    align-items: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
  }
  
  .cart-item::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--secondary);
    opacity: 0;
    transition: var(--transition);
  }
  
  .cart-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
  }
  
  .cart-item:hover::before {
    opacity: 1;
  }
  
  /* Item Selection */
  .item-select {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .item-checkbox {
    width: 20px;
    height: 20px;
    accent-color: var(--secondary);
    cursor: pointer;
  }
  
  /* Item Image */
  .item-image {
    width: 120px;
    height: 120px;
    border-radius: var(--border-radius-small);
    overflow: hidden;
    box-shadow: var(--shadow-light);
  }
  
  .item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
  }
  
  .cart-item:hover .item-image img {
    transform: scale(1.05);
  }
  
  /* Item Details */
  .item-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  
  .item-details h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin: 0;
    line-height: 1.4;
  }
  
  .item-variation {
    display: flex;
    gap: 15px;
    margin: 5px 0;
  }
  
  .item-variation span {
    font-size: 0.9rem;
    color: var(--grey);
    background: var(--light-grey);
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
  }
  
  .item-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--price-color);
    margin-top: 8px;
  }
  
  /* Quantity Controls */
  .item-quantity {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
  }
  
  .quantity-control {
    display: flex;
    align-items: center;
    background: var(--light-grey);
    border-radius: var(--border-radius-small);
    overflow: hidden;
    box-shadow: var(--shadow-light);
  }
  
  .quantity-btn {
    width: 40px;
    height: 40px;
    background: var(--light);
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .quantity-btn:hover {
    background: var(--secondary);
    color: var(--light);
  }
  
  .quantity-input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    background: var(--light);
    color: var(--dark);
  }
  
  .update-btn {
    background: var(--secondary);
    color: var(--light);
    border: none;
    padding: 8px 12px;
    border-radius: var(--border-radius-small);
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.9rem;
  }
  
  .update-btn:hover {
    background: var(--primary);
    transform: translateY(-1px);
  }
  
  /* Item Subtotal */
  .item-subtotal {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark);
    text-align: center;
  }
  
  /* Item Actions */
  .item-actions {
    display: flex;
    justify-content: center;
  }
  
  .remove-btn {
    background: transparent;
    border: 2px solid var(--danger-color);
    color: var(--danger-color);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }
  
  .remove-btn:hover {
    background: var(--danger-color);
    color: var(--light);
    transform: scale(1.1);
  }
  
  /* Cart Summary */
  .cart-summary {
    background: var(--light);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-medium);
    padding: 30px;
    height: fit-content;
    position: sticky;
    top: 100px;
    border: 1px solid var(--border-color);
  }
  
  .cart-summary h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 25px;
    text-align: center;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-grey);
  }
  
  .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--light-grey);
    font-size: 1rem;
  }
  
  .summary-row:last-of-type {
    border-bottom: none;
  }
  
  .summary-row span:first-child {
    color: var(--grey);
    font-weight: 500;
  }
  
  .summary-row span:last-child {
    font-weight: 600;
    color: var(--dark);
  }
  
  .summary-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    margin-top: 15px;
    border-top: 2px solid var(--border-color);
    font-size: 1.2rem;
    font-weight: 700;
  }
  
  .total-amount {
    color: var(--price-color);
    font-size: 1.4rem;
  }
  
  /* Free Shipping Notice */
  .free-shipping-notice {
    background: linear-gradient(135deg, var(--success-color), #20c997);
    color: var(--light);
    padding: 15px;
    border-radius: var(--border-radius-small);
    margin: 15px 0;
    text-align: center;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }
  
  .free-shipping-notice i {
    font-size: 1.2rem;
  }
  
  /* Shipping Threshold */
  .shipping-threshold {
    margin: 20px 0;
    padding: 20px;
    background: var(--light-grey);
    border-radius: var(--border-radius-small);
    border: 1px solid var(--border-color);
  }
  
  .threshold-bar {
    width: 100%;
    height: 8px;
    background: var(--accent);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
  }
  
  .threshold-progress {
    height: 100%;
    background: linear-gradient(90deg, var(--secondary), var(--success-color));
    transition: width 0.5s ease;
    border-radius: 4px;
  }
  
  .shipping-threshold p {
    font-size: 0.9rem;
    color: var(--grey);
    text-align: center;
    margin: 0;
    font-weight: 500;
  }
  
  .shipping-threshold span {
    color: var(--secondary);
    font-weight: 700;
  }
  
  /* Checkout Button */
  .btn-checkout {
    width: 100%;
    background: linear-gradient(135deg, var(--secondary), #005da6);
    color: var(--light);
    border: none;
    padding: 18px;
    border-radius: var(--border-radius-small);
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    margin: 20px 0 15px;
    box-shadow: var(--shadow-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .btn-checkout:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: var(--shadow-medium);
    background: linear-gradient(135deg, #005da6, var(--secondary));
  }
  
  .btn-checkout:disabled {
    background: var(--grey);
    cursor: not-allowed;
    opacity: 0.6;
  }
  
  .btn-continue-shopping {
    display: block;
    text-align: center;
    color: var(--secondary);
    text-decoration: none;
    font-weight: 600;
    padding: 12px;
    border: 2px solid var(--secondary);
    border-radius: var(--border-radius-small);
    transition: var(--transition);
  }
  
  .btn-continue-shopping:hover {
    background: var(--secondary);
    color: var(--light);
  }
  
  /* Responsive Design */
  @media (max-width: 1200px) {
    .cart-layout {
      grid-template-columns: 1fr 350px;
      gap: 30px;
    }
  
    .cart-item {
      grid-template-columns: auto 100px 1fr auto auto auto;
      gap: 20px;
      padding: 20px;
    }
  
    .item-image {
      width: 100px;
      height: 100px;
    }
  }
  
  @media (max-width: 992px) {
    .cart-layout {
      grid-template-columns: 1fr;
      gap: 30px;
    }
  
    .cart-summary {
      position: static;
      order: -1;
    }
  
    .cart-item {
      grid-template-columns: auto 80px 1fr auto auto;
      gap: 15px;
      padding: 20px;
    }
  
    .item-image {
      width: 80px;
      height: 80px;
    }
  
    .item-details h3 {
      font-size: 1.1rem;
    }
  
    .item-price {
      font-size: 1.2rem;
    }
  }
  
  @media (max-width: 768px) {
    .cart-section {
      padding: 20px 0 40px;
    }
  
    .cart-section h1 {
      font-size: 2rem;
      margin-bottom: 30px;
    }
  
    .cart-header {
      flex-direction: column;
      gap: 15px;
      padding: 20px;
    }
  
    .cart-item {
      grid-template-columns: 1fr;
      gap: 20px;
      text-align: center;
    }
  
    .item-image {
      width: 120px;
      height: 120px;
      margin: 0 auto;
    }
  
    .item-variation {
      justify-content: center;
    }
  
    .quantity-control {
      margin: 0 auto;
    }
  
    .cart-summary {
      padding: 25px;
    }
  
    .empty-cart {
      padding: 60px 20px;
    }
  
    .empty-cart i {
      font-size: 3rem;
    }
  
    .empty-cart h2 {
      font-size: 1.5rem;
    }
  }
  
  @media (max-width: 480px) {
    .cart-section .container {
      padding: 0 15px;
    }
  
    .cart-section h1 {
      font-size: 1.8rem;
    }
  
    .cart-header {
      padding: 15px;
    }
  
    .cart-item {
      padding: 15px;
    }
  
    .cart-summary {
      padding: 20px;
    }
  
    .btn-checkout {
      padding: 15px;
      font-size: 1rem;
    }
  }
  
  /* Animation for cart updates */
  @keyframes cartItemUpdate {
    0% {
      transform: scale(1);
    }
    50% {
      transform: scale(1.02);
    }
    100% {
      transform: scale(1);
    }
  }
  
  .cart-item.updating {
    animation: cartItemUpdate 0.3s ease;
  }
  
  /* Loading states */
  .loading {
    opacity: 0.6;
    pointer-events: none;
  }
  
  .loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid var(--light-grey);
    border-top: 2px solid var(--secondary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  
  @keyframes spin {
    0% {
      transform: translate(-50%, -50%) rotate(0deg);
    }
    100% {
      transform: translate(-50%, -50%) rotate(360deg);
    }
  }
  
  /* Success states */
  .success-message {
    background: var(--success-color);
    color: var(--light);
    padding: 15px;
    border-radius: var(--border-radius-small);
    margin: 15px 0;
    text-align: center;
    font-weight: 600;
    animation: slideDown 0.3s ease;
  }
  
  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
/* Footer Styles */
:root {
    --primary: #111111;
    --secondary: #0071c5;
    --accent: #e5e5e5;
    --light: #ffffff;
    --dark: #111111;
    --grey: #767676;
    --light-grey: #f5f5f5;
    --border-color: #e0e0e0;
    --sale-color: #0071c5;
    --price-color: #e63946;
  }
  
  .footer {
    background-color: var(--primary);
    color: var(--light);
    padding: 60px 0 30px;
    margin-top: 60px;
  }
  
  .footer .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
  }
  
  .footer-columns {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
    margin-bottom: 40px;
  }
  
  .footer-column h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 12px;
  }
  
  .footer-column h3::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background-color: var(--secondary);
    border-radius: 2px;
  }
  
  .footer-column ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  
  .footer-column ul li {
    margin-bottom: 12px;
  }
  
  .footer-column ul li a {
    color: var(--accent);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
    display: inline-block;
  }
  
  .footer-column ul li a:hover {
    color: var(--secondary);
    transform: translateX(3px);
  }
  
  /* Social Links */
  .social-links {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
  }
  
  .social-links a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: var(--light);
    transition: all 0.3s ease;
  }
  
  .social-links a:hover {
    background-color: var(--secondary);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 113, 197, 0.3);
  }
  
  /* Newsletter */
  .newsletter {
    margin-top: 20px;
  }
  
  .newsletter h4 {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 15px;
  }
  
  .newsletter form {
    display: flex;
    height: 45px;
  }
  
  .newsletter input {
    flex: 1;
    padding: 0 15px;
    border: none;
    border-radius: 4px 0 0 4px;
    font-size: 14px;
    background-color: rgba(255, 255, 255, 0.1);
    color: var(--light);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-right: none;
  }
  
  .newsletter input::placeholder {
    color: rgba(255, 255, 255, 0.6);
  }
  
  .newsletter input:focus {
    outline: none;
    background-color: rgba(255, 255, 255, 0.15);
  }
  
  .newsletter button {
    padding: 0 20px;
    background-color: var(--secondary);
    color: var(--light);
    border: none;
    border-radius: 0 4px 4px 0;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  
  .newsletter button:hover {
    background-color: #005da6;
  }
  
  /* Footer Bottom */
  .footer-bottom {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 14px;
  }
  
  .footer-bottom p {
    margin: 0;
    color: rgba(255, 255, 255, 0.7);
  }
  
  .footer-links {
    display: flex;
    gap: 20px;
  }
  
  .footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.3s ease;
  }
  
  .footer-links a:hover {
    color: var(--secondary);
  }
  
  .payment-methods {
    display: flex;
    gap: 10px;
    font-size: 24px;
  }
  
  .payment-methods i {
    color: rgba(255, 255, 255, 0.7);
    transition: color 0.3s ease;
  }
  
  .payment-methods i:hover {
    color: var(--light);
  }
  
  /* Responsive Design */
  @media (max-width: 992px) {
    .footer-columns {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 768px) {
    .footer {
      padding: 40px 0 20px;
    }
    
    .footer-columns {
      grid-template-columns: 1fr;
      gap: 30px;
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

                   
                    <a href="cart.php" id="cartBtn" class="active">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cartCount">
                            <?php 
        // Count items in cart.xml directly
        $cartCount = 0;
        if (file_exists('cart.xml')) {
            $cartXml = simplexml_load_file('cart.xml');
            if ($cartXml) {
                // Only count items for the current user if logged in
                if ($loggedIn) {
                    foreach ($cartXml->item as $item) {
                        if ((int)$item->user_id == $_SESSION['user_id']) {
                            $cartCount++;
                        }
                    }
                } else {
                    $cartCount = count($cartXml->item);
                }
            }
        }
        echo $cartCount;
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
        <nav class="main-nav" id="mainNav">
            <a href="usershop.php">HOME</a>
           
        </nav>
    </header>

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items to your cart yet. Start shopping to fill it up!</p>
                    <a href="usershop.php" class="btn-primary">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-layout">
                    <div class="cart-main">
                        <form action="cart.php" method="post" id="cartForm">
                            <div class="cart-header">
                                <div class="select-all">
                                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                    <label for="selectAll">Select All Items</label>
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
                                                <span><i class="fas fa-palette"></i> <?php echo htmlspecialchars($item['color']); ?></span>
                                                <span><i class="fas fa-ruler"></i> <?php echo htmlspecialchars($item['size']); ?></span>
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

                                            <button class="update-btn" type="button" onclick="updateCartItem(this)">
                                                <i class="fas fa-sync-alt"></i> Update
                                            </button>

                                            <form action="cart.php" method="post" class="update-quantity-form"
                                                id="update-form-<?php echo $item['id']; ?>" style="display: none;">
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
                    </div>

                    <div class="cart-summary">
                        <h2><i class="fas fa-receipt"></i> Order Summary</h2>
                        
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
                        
                        <button type="button" id="checkout-btn" class="btn-checkout" onclick="proceedToCheckout()" disabled>
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </button>
                        
                        <a href="usershop.php" class="btn-continue-shopping">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
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

        // Add this to the existing <script> section at the bottom of the file
function updateCartCount() {
    fetch('get_cart_count.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to fetch cart count');
        }
        return response.json();
    })
    .then(data => {
        const cartCount = document.getElementById("cartCount");
        if (cartCount) {
            if (data.count !== undefined) {
                cartCount.textContent = data.count;
                console.log('Cart count updated to:', data.count);
            } else {
                console.error('Cart count data is undefined');
                cartCount.textContent = '0';
            }
        }
    })
    .catch(error => {
        console.error('Error fetching cart count:', error);
    });
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
            
            // Update cart count
            updateCartCount();
        }

        // Initialize order summary on page load
        document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
    // Update cart count on page load
    updateCartCount();
});
    </script>
</body>

</html>
