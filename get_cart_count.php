<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Function to count items in cart.xml
function getCartItemCount() {
    $count = 0;
    
    // Check if cart.xml exists
    if (file_exists('cart.xml')) {
        // Load the XML file
        $xml = simplexml_load_file('cart.xml');
        
        if ($xml) {
            // Count the number of item elements
            $count = count($xml->item);
        }
    }
    
    return $count;
}

// Get the count and return as JSON
$count = getCartItemCount();
echo json_encode(['count' => $count]);
?>
