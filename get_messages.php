<?php
// get_messages.php
session_start();
include 'db_connect.php';
require_once 'MessageHandler.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Check if conversation ID is provided
if (!isset($_GET['conversation_id'])) {
    echo json_encode(['success' => false, 'error' => 'Conversation ID required']);
    exit;
}

$conversationId = intval($_GET['conversation_id']);
$userId = $_SESSION['user_id'];

try {
    $messageHandler = new MessageHandler($conn);
    
    // Get conversation details
    $stmt = $conn->prepare("SELECT * FROM conversations WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $conversationId, $userId);
    $stmt->execute();
    $conversation = $stmt->get_result()->fetch_assoc();
    
    if (!$conversation) {
        echo json_encode(['success' => false, 'error' => 'Conversation not found']);
        exit;
    }
    
    // Get messages for this conversation
    $messages = $messageHandler->getConversationMessages($conversationId, $userId, 'user');
    
    // Mark messages as read
    $messageIds = array_column($messages, 'id');
    if (!empty($messageIds)) {
        $messageHandler->markAsRead($messageIds, 'user', $userId);
    }
    
    echo json_encode([
        'success' => true,
        'conversation' => $conversation,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>