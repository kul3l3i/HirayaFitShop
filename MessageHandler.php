<?php
class MessageHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Send a new message
     */
    public function sendMessage($senderType, $senderId, $receiverType, $receiverId, $subject, $message, $messageType = 'general', $priority = 'normal', $conversationId = null) {
        try {
            $this->conn->begin_transaction();
            
            // If no conversation ID provided, create or find existing conversation
            if (!$conversationId && $senderType === 'user') {
                $conversationId = $this->getOrCreateConversation($senderId, $subject, $messageType, $priority);
            }
            
            // Insert the message
            $stmt = $this->conn->prepare("INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, subject, message, message_type, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissssss", $senderType, $senderId, $receiverType, $receiverId, $subject, $message, $messageType, $priority);
            $stmt->execute();
            
            $messageId = $this->conn->insert_id;
            
            // Update conversation's last message time
            if ($conversationId) {
                $this->updateConversationLastMessage($conversationId);
            }
            
            $this->conn->commit();
            return $messageId;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    /**
     * Get or create a conversation
     */
    private function getOrCreateConversation($userId, $subject, $category = 'general', $priority = 'normal') {
        // Check if there's an existing open conversation with similar subject
        $stmt = $this->conn->prepare("SELECT id FROM conversations WHERE user_id = ? AND status IN ('open', 'in_progress') AND subject = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param("is", $userId, $subject);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc()['id'];
        }
        
        // Create new conversation
        $stmt = $this->conn->prepare("INSERT INTO conversations (user_id, subject, category, priority) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $subject, $category, $priority);
        $stmt->execute();
        
        return $this->conn->insert_id;
    }
    
    /**
     * Update conversation's last message timestamp
     */
    private function updateConversationLastMessage($conversationId) {
        $stmt = $this->conn->prepare("UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $conversationId);
        $stmt->execute();
    }
    
    /**
     * Get conversations for a user
     */
    public function getUserConversations($userId, $limit = 20, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT c.*, 
                   a.fullname as admin_name,
                   a.profile_image as admin_image,
                   (SELECT COUNT(*) FROM messages m WHERE 
                    ((m.sender_type = 'admin' AND m.receiver_type = 'user' AND m.receiver_id = c.user_id) OR
                     (m.sender_type = 'user' AND m.sender_id = c.user_id AND m.receiver_type = 'admin'))
                    AND m.is_read = FALSE AND m.receiver_id = ?) as unread_count,
                   (SELECT m.message FROM messages m WHERE 
                    ((m.sender_type = 'admin' AND m.receiver_type = 'user' AND m.receiver_id = c.user_id) OR
                     (m.sender_type = 'user' AND m.sender_id = c.user_id AND m.receiver_type = 'admin'))
                    ORDER BY m.created_at DESC LIMIT 1) as last_message
            FROM conversations c
            LEFT JOIN admins a ON c.admin_id = a.admin_id
            WHERE c.user_id = ?
            ORDER BY c.last_message_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("iiii", $userId, $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get conversations for admin
     */
    public function getAdminConversations($adminId = null, $limit = 20, $offset = 0) {
        if ($adminId) {
            // Get conversations assigned to specific admin
            $stmt = $this->conn->prepare("
                SELECT c.*, 
                       u.fullname as user_name,
                       u.profile_image as user_image,
                       u.email as user_email,
                       (SELECT COUNT(*) FROM messages m WHERE 
                        ((m.sender_type = 'user' AND m.receiver_type = 'admin' AND m.receiver_id = ?) OR
                         (m.sender_type = 'admin' AND m.sender_id = ? AND m.receiver_type = 'user'))
                        AND m.is_read = FALSE AND m.receiver_id = ?) as unread_count,
                       (SELECT m.message FROM messages m WHERE 
                        ((m.sender_type = 'user' AND m.receiver_type = 'admin') OR
                         (m.sender_type = 'admin' AND m.receiver_type = 'user'))
                        ORDER BY m.created_at DESC LIMIT 1) as last_message
                FROM conversations c
                JOIN users u ON c.user_id = u.id
                WHERE c.admin_id = ?
                ORDER BY c.last_message_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("iiiiii", $adminId, $adminId, $adminId, $adminId, $limit, $offset);
        } else {
            // Get all conversations (for super admin or unassigned)
            $stmt = $this->conn->prepare("
                SELECT c.*, 
                       u.fullname as user_name,
                       u.profile_image as user_image,
                       u.email as user_email,
                       a.fullname as admin_name,
                       (SELECT COUNT(*) FROM messages m WHERE 
                        m.sender_type = 'user' AND m.receiver_type = 'admin' 
                        AND m.is_read = FALSE) as unread_count,
                       (SELECT m.message FROM messages m WHERE 
                        ((m.sender_type = 'user' AND m.receiver_type = 'admin') OR
                         (m.sender_type = 'admin' AND m.receiver_type = 'user'))
                        ORDER BY m.created_at DESC LIMIT 1) as last_message
                FROM conversations c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN admins a ON c.admin_id = a.admin_id
                ORDER BY c.last_message_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get messages in a conversation
     */
    public function getConversationMessages($conversationId, $userId, $userType, $limit = 50, $offset = 0) {
        $stmt = $this->conn->prepare("
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
            LEFT JOIN users u ON m.sender_type = 'user' AND m.sender_id = u.id
            LEFT JOIN admins a ON m.sender_type = 'admin' AND m.sender_id = a.admin_id
            WHERE ((m.sender_type = ? AND m.sender_id = ?) OR (m.receiver_type = ? AND m.receiver_id = ?))
            ORDER BY m.created_at ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("sissii", $userType, $userId, $userType, $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Mark messages as read
     */
    public function markAsRead($messageIds, $readerType, $readerId) {
        if (empty($messageIds)) return;
        
        $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
        $stmt = $this->conn->prepare("UPDATE messages SET is_read = TRUE WHERE id IN ($placeholders) AND receiver_type = ? AND receiver_id = ?");
        
        $types = str_repeat('i', count($messageIds)) . 'si';
        $params = array_merge($messageIds, [$readerType, $readerId]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        // Also insert into read status table for detailed tracking
        foreach ($messageIds as $messageId) {
            $this->recordReadStatus($messageId, $readerType, $readerId);
        }
    }
    
    /**
     * Record read status
     */
    private function recordReadStatus($messageId, $readerType, $readerId) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO message_read_status (message_id, reader_type, reader_id) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $messageId, $readerType, $readerId);
        $stmt->execute();
    }
    
    /**
     * Get unread message count
     */
    public function getUnreadCount($userType, $userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_type = ? AND receiver_id = ? AND is_read = FALSE");
        $stmt->bind_param("si", $userType, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    /**
     * Assign conversation to admin
     */
    public function assignConversation($conversationId, $adminId) {
        $stmt = $this->conn->prepare("UPDATE conversations SET admin_id = ?, status = 'in_progress' WHERE id = ?");
        $stmt->bind_param("ii", $adminId, $conversationId);
        return $stmt->execute();
    }
    
    /**
     * Update conversation status
     */
    public function updateConversationStatus($conversationId, $status) {
        $stmt = $this->conn->prepare("UPDATE conversations SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $conversationId);
        return $stmt->execute();
    }
    
    /**
     * Search messages
     */
    public function searchMessages($userType, $userId, $searchTerm, $limit = 20) {
        $searchTerm = "%$searchTerm%";
        $stmt = $this->conn->prepare("
            SELECT m.*, 
                   CASE 
                       WHEN m.sender_type = 'user' THEN u.fullname
                       WHEN m.sender_type = 'admin' THEN a.fullname
                   END as sender_name
            FROM messages m
            LEFT JOIN users u ON m.sender_type = 'user' AND m.sender_id = u.id
            LEFT JOIN admins a ON m.sender_type = 'admin' AND m.sender_id = a.admin_id
            WHERE ((m.sender_type = ? AND m.sender_id = ?) OR (m.receiver_type = ? AND m.receiver_id = ?))
            AND (m.subject LIKE ? OR m.message LIKE ?)
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("sisssssi", $userType, $userId, $userType, $userId, $searchTerm, $searchTerm, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>