<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['item_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$itemId = $input['item_id'];
$newQuantity = (int)$input['quantity'];
$userId = $_SESSION['user_id'];

// Validate quantity
if ($newQuantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

// Check if cart.xml exists
if (!file_exists('cart.xml')) {
    echo json_encode(['success' => false, 'message' => 'Cart file not found']);
    exit();
}

// Load XML
$xml = simplexml_load_file('cart.xml');
if (!$xml) {
    echo json_encode(['success' => false, 'message' => 'Failed to load cart']);
    exit();
}

// Find and update the item
$itemFound = false;
$newSubtotal = 0;

foreach ($xml->item as $item) {
    if ((string)$item->id == $itemId && (int)$item->user_id == $userId) {
        $item->quantity = $newQuantity;
        $newSubtotal = (float)$item->price * $newQuantity;
        $itemFound = true;
        break;
    }
}

if (!$itemFound) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit();
}

// Save the XML
if ($xml->asXML('cart.xml')) {
    echo json_encode([
        'success' => true, 
        'message' => 'Cart updated successfully',
        'new_subtotal' => number_format($newSubtotal, 2),
        'new_quantity' => $newQuantity
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save cart']);
}
?>
