<?php
// cart_handler.php - Handles cart operations with session management
session_start();


// Include your environment-aware database connection
include 'db_connect.php';

// Initialize variables
$error = '';
$username_email = '';

// Check if user is
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Response function
function sendResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Action handler
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'get_cart':
        getCart();
        break;
    case 'update_cart':
        updateCart();
        break;
    case 'clear_cart':
        clearCart();
        break;
    default:
        sendResponse(false, 'Invalid action');
}

// Get cart from database
function getCart() {
    global $conn;
    
    if (!isUserLoggedIn()) {
        sendResponse(false, 'User not logged in');
        return;
    }
    
    $user_id = getCurrentUserId();
    
    try {
        // First check if the user has a cart
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // No cart found
            sendResponse(true, 'No cart found', ['cart' => []]);
            return;
        }
        
        $cart_row = $result->fetch_assoc();
        $cart_id = $cart_row['id'];
        
        // Get cart items
        $stmt = $conn->prepare("
            SELECT ci.*, p.name, p.image, p.price 
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = ?
        ");
        $stmt->bind_param('i', $cart_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart_items = [];
        while ($item = $result->fetch_assoc()) {
            $cart_items[] = [
                'id' => $item['product_id'],
                'name' => $item['name'],
                'price' => (float)$item['price'],
                'quantity' => (int)$item['quantity'],
                'image' => $item['image'],
                'size' => $item['size'],
                'color' => $item['color']
            ];
        }
        
        sendResponse(true, 'Cart retrieved successfully', ['cart' => $cart_items]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// Update cart in database
function updateCart() {
    global $conn;
    
    // Check if cart data is provided
    if (!isset($_POST['cart_data'])) {
        sendResponse(false, 'No cart data provided');
        return;
    }
    
    // Parse cart data
    $cart_data = json_decode($_POST['cart_data'], true);
    
    if (!is_array($cart_data)) {
        sendResponse(false, 'Invalid cart data format');
        return;
    }
    
    // If user is not logged in, store in session only
    if (!isUserLoggedIn()) {
        $_SESSION['temp_cart'] = $cart_data;
        sendResponse(true, 'Cart saved to session');
        return;
    }
    
    $user_id = getCurrentUserId();
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Check if user already has an active cart
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart_id = null;
        
        if ($result->num_rows === 0) {
            // Create a new cart
            $stmt = $conn->prepare("INSERT INTO carts (user_id, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $cart_id = $conn->insert_id;
        } else {
            $cart_row = $result->fetch_assoc();
            $cart_id = $cart_row['id'];
            
            // Clear existing cart items
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->bind_param('i', $cart_id);
            $stmt->execute();
            
            // Update the cart's updated_at timestamp
            $stmt = $conn->prepare("UPDATE carts SET updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $cart_id);
            $stmt->execute();
        }
        
        // Insert new cart items
        if (!empty($cart_data)) {
            $stmt = $conn->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity, price, size, color) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_data as $item) {
                $product_id = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                $size = isset($item['size']) ? $item['size'] : null;
                $color = isset($item['color']) ? $item['color'] : null;
                
                $stmt->bind_param('iiidss', $cart_id, $product_id, $quantity, $price, $size, $color);
                $stmt->execute();
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        sendResponse(true, 'Cart updated successfully');
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}

// Clear cart
function clearCart() {
    global $conn;
    
    if (!isUserLoggedIn()) {
        // Clear session cart
        $_SESSION['temp_cart'] = [];
        sendResponse(true, 'Session cart cleared');
        return;
    }
    
    $user_id = getCurrentUserId();
    
    try {
        // Get the user's active cart
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendResponse(true, 'No cart to clear');
            return;
        }
        
        $cart_row = $result->fetch_assoc();
        $cart_id = $cart_row['id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete cart items
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->bind_param('i', $cart_id);
        $stmt->execute();
        
        // Mark cart as inactive (or you could delete it)
        $stmt = $conn->prepare("UPDATE carts SET status = 'inactive', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $cart_id);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        sendResponse(true, 'Cart cleared successfully');
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        sendResponse(false, 'Database error: ' . $e->getMessage());
    }
}
?>