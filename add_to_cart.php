<?php
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);

// Get product info from JS
$productId = $data['productId'] ?? 'UNKNOWN_ID';
$productName = $data['productName'] ?? 'UNKNOWN_NAME';
$image = $data['image'] ?? 'UNKNOWN_IMAGE';
$price = $data['price'] ?? 0;
$size = $data['size'] ?? 'UNKNOWN_SIZE';
$color = $data['color'] ?? 'UNKNOWN_COLOR';
$quantity = $data['quantity'] ?? 1;
$timestamp = date('Y-m-d H:i:s');

// Add a flag to determine if we should replace or add quantity
$replaceQuantity = $data['replaceQuantity'] ?? false;

$xmlFile = 'cart.xml';
if (file_exists($xmlFile)) {
    $xml = simplexml_load_file($xmlFile);
} else {
    $xml = new SimpleXMLElement('<cart></cart>');
}

// Flag to check if item exists
$itemUpdated = false;

// Generate a unique ID for the item if it's new
$itemId = uniqid('item_');

// Debug information
error_log("Processing item: User ID: $userId, Product ID: $productId, Size: $size, Color: $color, Quantity: $quantity, Replace: " . ($replaceQuantity ? 'Yes' : 'No'));

foreach ($xml->item as $item) {
    // Strict comparison with explicit casting to string
    if ((string)$item->user_id === (string)$userId &&
        (string)$item->product_id === (string)$productId &&
        (string)$item->size === (string)$size &&
        (string)$item->color === (string)$color) {

        // Update quantity based on the flag
        if ($replaceQuantity) {
            // Replace the quantity with the new value
            $item->quantity = (int)$quantity;
        } else {
            // Add the new quantity to the existing quantity
            $item->quantity = (int)$item->quantity + (int)$quantity;
        }
        
        $item->price = (float)$price; // Update price if needed
        $item->timestamp = $timestamp; // Update timestamp
        $itemUpdated = true;
        error_log("Item found and updated. New quantity: " . (string)$item->quantity);
        break;
    }
}

if (!$itemUpdated) {
    // Append new item
    $item = $xml->addChild('item');
    $item->addChild('id', $itemId); // Add a unique ID
    $item->addChild('user_id', $userId);
    $item->addChild('product_id', $productId);
    $item->addChild('product_name', $productName);
    $item->addChild('image', $image);
    $item->addChild('price', $price);
    $item->addChild('color', $color);
    $item->addChild('size', $size);
    $item->addChild('quantity', $quantity);
    $item->addChild('timestamp', $timestamp);
    error_log("New item added to cart with ID: $itemId");
}

// Save XML
$xml->asXML($xmlFile);

echo $itemUpdated ? "Cart item updated." : "New item added to cart.";
?>