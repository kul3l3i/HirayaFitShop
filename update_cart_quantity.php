<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$productId = $data['productId'];
$size = $data['size'];
$color = $data['color'];
$newQuantity = (int)$data['quantity'];
$newPrice = (float)$data['price'];

$xmlFile = 'cart.xml';
if (!file_exists($xmlFile)) {
    echo "Cart XML not found.";
    exit;
}

$xml = simplexml_load_file($xmlFile);
$updated = false;

foreach ($xml->item as $item) {
    if ((string)$item->user_id === (string)$userId &&
        (string)$item->product_id === $productId &&
        (string)$item->size === $size &&
        (string)$item->color === $color) {
        
        $item->quantity = $newQuantity;
        $item->price = $newPrice;
        $updated = true;
        break;
    }
}


if ($updated) {
    $xml->asXML($xmlFile);
    echo "Quantity and price updated in XML.";
} else {
    echo "Item not found.";
}
?>
