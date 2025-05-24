<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: sign-in.php");
    exit;
}

// Get admin information from the database
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT admin_id, username, fullname, email, profile_image, role FROM admins WHERE admin_id = ? AND is_active = TRUE");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: sign-in.php");
    exit;
}

$admin = $result->fetch_assoc();
if (!isset($admin['role'])) {
    $admin['role'] = 'Administrator';
}

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png";
    }
}

$profileImageUrl = getProfileImageUrl($admin['profile_image']);

// Initialize variables
$error = '';
$success = '';
$conversations = []; // Initialize as empty array
$unreadCount = 0; // Initialize as 0

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $conversationId = $_POST['conversation_id'];
    $userId = $_POST['user_id'];
    $message = trim($_POST['reply_message']);
    
    if (!empty($message)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Insert the message - FIXED: Remove conversation_id from INSERT since column doesn't exist
            $msgStmt = $conn->prepare("INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message, message_type, priority, is_read) VALUES ('admin', ?, 'user', ?, ?, 'general', 'normal', FALSE)");
            $msgStmt->bind_param("iis", $admin_id, $userId, $message);
            $msgStmt->execute();
            $msgStmt->close();
            
            // Update conversation last_message_at
            $updateConvStmt = $conn->prepare("UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateConvStmt->bind_param("i", $conversationId);
            $updateConvStmt->execute();
            $updateConvStmt->close();
            
            $conn->commit();
            $success = "Reply sent successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error sending reply: " . $e->getMessage();
        }
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $conversationId = $_POST['conversation_id'];
    $newStatus = $_POST['new_status'];
    
    try {
        $updateStmt = $conn->prepare("UPDATE conversations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $conversationId);
        $updateStmt->execute();
        $success = "Status updated successfully!";
        $updateStmt->close();
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $conversationId = $_POST['conversation_id'];
    
    try {
        // FIXED: Mark messages as read based on conversation participants instead of conversation_id
        $markReadStmt = $conn->prepare("
            UPDATE messages m 
            JOIN conversations c ON c.id = ?
            SET m.is_read = TRUE 
            WHERE m.sender_type = 'user' 
            AND m.sender_id = c.user_id 
            AND m.receiver_type = 'admin'
        ");
        $markReadStmt->bind_param("i", $conversationId);
        $markReadStmt->execute();
        $markReadStmt->close();
    } catch (Exception $e) {
        $error = "Error marking messages as read: " . $e->getMessage();
    }
}

// Get all conversations for admin - FIXED query without conversation_id
try {
    $conversationsQuery = "
        SELECT 
            c.*,
            u.fullname as user_name,
            u.profile_image as user_image,
            (SELECT m.message FROM messages m 
             WHERE (
                 (m.sender_type = 'user' AND m.sender_id = c.user_id AND m.receiver_type = 'admin') OR
                 (m.sender_type = 'admin' AND m.receiver_id = c.user_id AND m.receiver_type = 'user')
             )
             ORDER BY m.created_at DESC LIMIT 1) as last_message,
            (SELECT m.created_at FROM messages m 
             WHERE (
                 (m.sender_type = 'user' AND m.sender_id = c.user_id AND m.receiver_type = 'admin') OR
                 (m.sender_type = 'admin' AND m.receiver_id = c.user_id AND m.receiver_type = 'user')
             )
             ORDER BY m.created_at DESC LIMIT 1) as last_message_at,
            (SELECT COUNT(*) FROM messages m 
             WHERE m.sender_type = 'user' 
             AND m.sender_id = c.user_id 
             AND m.receiver_type = 'admin' 
             AND m.is_read = FALSE) as unread_count
        FROM conversations c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.updated_at DESC
    ";

    $conversationsResult = $conn->query($conversationsQuery);
    if ($conversationsResult && $conversationsResult->num_rows > 0) {
        while ($row = $conversationsResult->fetch_assoc()) {
            $conversations[] = $row;
        }
    }
} catch (Exception $e) {
    $error = "Error loading conversations: " . $e->getMessage();
}

// Get selected conversation details and messages
$selectedConversation = null;
$messages = [];
if (isset($_GET['conversation_id'])) {
    $conversationId = intval($_GET['conversation_id']);
    
    try {
        // Get conversation details
        $convStmt = $conn->prepare("
            SELECT c.*, u.fullname as user_name, u.profile_image as user_image, u.email as user_email
            FROM conversations c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $convStmt->bind_param("i", $conversationId);
        $convStmt->execute();
        $convResult = $convStmt->get_result();
        
        if ($convResult->num_rows > 0) {
            $selectedConversation = $convResult->fetch_assoc();
            
            // FIXED: Get messages based on conversation participants instead of conversation_id
            $msgStmt = $conn->prepare("
                SELECT m.*, 
                       CASE 
                           WHEN m.sender_type = 'user' THEN u.fullname 
                           WHEN m.sender_type = 'admin' THEN a.fullname 
                       END as sender_name,
                       CASE 
                           WHEN m.sender_type = 'user' THEN u.profile_image 
                           WHEN m.sender_type = 'admin' THEN a.profile_image 
                       END as sender_image
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id AND m.sender_type = 'user'
                LEFT JOIN admins a ON m.sender_id = a.admin_id AND m.sender_type = 'admin'
                JOIN conversations c ON c.id = ?
                WHERE (
                    (m.sender_type = 'user' AND m.sender_id = c.user_id AND m.receiver_type = 'admin') OR
                    (m.sender_type = 'admin' AND m.receiver_id = c.user_id AND m.receiver_type = 'user')
                )
                ORDER BY m.created_at ASC
            ");
            $msgStmt->bind_param("i", $conversationId);
            $msgStmt->execute();
            $msgResult = $msgStmt->get_result();
            
            while ($row = $msgResult->fetch_assoc()) {
                $messages[] = $row;
            }
            
            $msgStmt->close();
        }
        $convStmt->close();
    } catch (Exception $e) {
        $error = "Error loading conversation: " . $e->getMessage();
    }
}

// Get total unread count - FIXED
try {
    $unreadQuery = "SELECT COUNT(*) as total FROM messages WHERE sender_type = 'user' AND is_read = FALSE";
    $unreadResult = $conn->query($unreadQuery);
    if ($unreadResult) {
        $unreadData = $unreadResult->fetch_assoc();
        $unreadCount = $unreadData['total'] ?? 0;
    }
} catch (Exception $e) {
    $error = "Error loading unread count: " . $e->getMessage();
}

$stmt->close();
?>