<?php

session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';

require_once 'MessageHandler.php';

// Initialize variables
$error = '';
$success = '';

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$user = null;

// If user is not logged in, redirect to login page
if (!$loggedIn) {
    header("Location:sign-in.php");
    exit();
}

// Initialize message handler
$messageHandler = new MessageHandler($conn);

// Fetch user details
$stmt = $conn->prepare("SELECT id, fullname, username, email, address, phone, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $messageType = $_POST['message_type'] ?? 'general';
    $priority = $_POST['priority'] ?? 'normal';
    
    if (!empty($subject) && !empty($message)) {
        try {
            $messageHandler->sendMessage(
                'user', 
                $_SESSION['user_id'], 
                'admin', 
                1, // Send to admin ID 1 (can be modified to route differently)
                $subject, 
                $message, 
                $messageType, 
                $priority
            );
            $success = "Message sent successfully!";
        } catch (Exception $e) {
            $error = "Error sending message: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $conversationId = $_POST['conversation_id'];
    $message = trim($_POST['reply_message']);
    
    if (!empty($message)) {
        try {
            $messageHandler->sendMessage(
                'user', 
                $_SESSION['user_id'], 
                'admin', 
                1,
                '', // Empty subject for replies
                $message, 
                'general', 
                'normal',
                $conversationId
            );
            $success = "Reply sent successfully!";
        } catch (Exception $e) {
            $error = "Error sending reply: " . $e->getMessage();
        }
    }
}

// Get user's conversations
$conversations = $messageHandler->getUserConversations($_SESSION['user_id']);

// Get unread count
$unreadCount = $messageHandler->getUnreadCount('user', $_SESSION['user_id']);

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - HirayaFit</title>    <link rel="icon" href="images/logo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <style>
      :root {
    --primary: #111111;
    --secondary: #0071c5;
    --accent: #e5e5e5;
    --light: #ffffff;
    --dark: #111111;
    --grey: #767676;
}

.messages-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.messages-header {
    background: linear-gradient(135deg, var(--dark) 0%, var(--secondary) 100%);
    color: var(--light);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.messages-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.messages-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    min-height: 600px;
}

.conversations-panel {
    background: var(--light);
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid var(--accent);
}

.panel-header {
    background: var(--accent);
    padding: 1.25rem;
    border-bottom: 1px solid rgba(118, 118, 118, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h3 {
    margin: 0;
    color: var(--primary);
    font-weight: 600;
}

.new-message-btn {
    background: var(--secondary);
    color: var(--light);
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.new-message-btn:hover {
    background: #005a9f;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 113, 197, 0.3);
}

.conversations-list {
    max-height: 500px;
    overflow-y: auto;
}

.conversation-item {
    padding: 1.25rem;
    border-bottom: 1px solid rgba(229, 229, 229, 0.8);
    cursor: pointer;
    transition: all 0.2s ease;
}

.conversation-item:hover {
    background-color: rgba(229, 229, 229, 0.3);
}

.conversation-item.active {
    background-color: rgba(0, 113, 197, 0.08);
    border-left: 4px solid var(--secondary);
}

.conversation-subject {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.conversation-preview {
    color: var(--grey);
    font-size: 0.875rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.conversation-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.75rem;
    font-size: 0.75rem;
    color: var(--grey);
}

.conversation-status {
    padding: 0.25rem 0.75rem;
    border-radius: 16px;
    font-size: 0.6875rem;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.status-open { 
    background: rgba(34, 197, 94, 0.1); 
    color: #16a34a; 
    border: 1px solid rgba(34, 197, 94, 0.2);
}
.status-in_progress { 
    background: rgba(245, 158, 11, 0.1); 
    color: #d97706; 
    border: 1px solid rgba(245, 158, 11, 0.2);
}
.status-resolved { 
    background: rgba(0, 113, 197, 0.1); 
    color: var(--secondary); 
    border: 1px solid rgba(0, 113, 197, 0.2);
}
.status-closed { 
    background: rgba(118, 118, 118, 0.1); 
    color: var(--grey); 
    border: 1px solid rgba(118, 118, 118, 0.2);
}

.unread-badge {
    background: #ef4444;
    color: var(--light);
    border-radius: 50%;
    padding: 0.25rem 0.5rem;
    font-size: 0.6875rem;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.chat-panel {
    background: var(--light);
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    border: 1px solid var(--accent);
}

.chat-header {
    background: var(--accent);
    padding: 1.25rem;
    border-bottom: 1px solid rgba(118, 118, 118, 0.2);
    border-radius: 12px 12px 0 0;
}

.chat-messages {
    flex: 1;
    padding: 1.25rem;
    overflow-y: auto;
    max-height: 400px;
}

.message-bubble {
    margin-bottom: 1.25rem;
    display: flex;
    gap: 0.875rem;
}

.message-bubble.sent {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.message-content {
    max-width: 70%;
    background: var(--accent);
    padding: 0.875rem 1.125rem;
    border-radius: 20px;
    position: relative;
}

.message-bubble.sent .message-content {
    background: var(--secondary);
    color: var(--light);
}

.message-text {
    margin: 0;
    line-height: 1.5;
    font-size: 0.9375rem;
}

.message-time {
    font-size: 0.75rem;
    color: var(--grey);
    margin-top: 0.375rem;
}

.message-bubble.sent .message-time {
    color: rgba(255, 255, 255, 0.8);
}

.chat-input {
    padding: 1.25rem;
    border-top: 1px solid rgba(118, 118, 118, 0.2);
    border-radius: 0 0 12px 12px;
    background: rgba(229, 229, 229, 0.3);
}

.input-group {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.message-input {
    flex: 1;
    padding: 0.875rem 1.125rem;
    border: 1px solid rgba(118, 118, 118, 0.3);
    border-radius: 24px;
    outline: none;
    resize: none;
    font-family: inherit;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    background: var(--light);
}

.message-input:focus {
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
}

.send-btn {
    background: var(--secondary);
    color: var(--light);
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: 24px;
    cursor: pointer;
    font-size: 0.9375rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.send-btn:hover {
    background: #005a9f;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 113, 197, 0.3);
}

.empty-state {
    text-align: center;
    color: var(--grey);
    padding: 3rem 2rem;
}

.empty-state i {
    font-size: 3rem;
    color: rgba(118, 118, 118, 0.4);
    margin-bottom: 1rem;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(17, 17, 17, 0.6);
    backdrop-filter: blur(4px);
}

.modal-content {
    background-color: var(--light);
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--accent);
}

.modal-header {
    background: var(--primary);
    color: var(--light);
    padding: 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-weight: 600;
}

.close-btn {
    background: none;
    border: none;
    color: var(--light);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.modal-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--primary);
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.875rem;
    border: 1px solid rgba(118, 118, 118, 0.3);
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    background: var(--light);
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.btn-primary {
    background: var(--secondary);
    color: var(--light);
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.9375rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #005a9f;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 113, 197, 0.3);
}

.alert {
    padding: 0.875rem 1.125rem;
    border-radius: 8px;
    margin-bottom: 1.25rem;
    font-size: 0.875rem;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: #16a34a;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #dc2626;
}

/* Scrollbar Styling */
.conversations-list::-webkit-scrollbar,
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.conversations-list::-webkit-scrollbar-track,
.chat-messages::-webkit-scrollbar-track {
    background: rgba(229, 229, 229, 0.3);
}

.conversations-list::-webkit-scrollbar-thumb,
.chat-messages::-webkit-scrollbar-thumb {
    background: rgba(118, 118, 118, 0.4);
    border-radius: 3px;
}

.conversations-list::-webkit-scrollbar-thumb:hover,
.chat-messages::-webkit-scrollbar-thumb:hover {
    background: var(--grey);
}

@media (max-width: 768px) {
    .messages-container {
        padding: 0 0.5rem;
    }
    
    .messages-content {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .conversations-panel {
        margin-bottom: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .messages-header {
        padding: 1.5rem;
    }
    
    .messages-header h1 {
        font-size: 2rem;
    }
    
    .message-content {
        max-width: 85%;
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
                                    <a href="messagesUser.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
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
                    
                    <a href="messagesUser.php" class="active">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="cart-count"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
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
            <a href="usershop.php">HOME</a>
        </nav>
    </header>

    <!-- Messages Container -->
    <div class="messages-container">
        <!-- Messages Header -->
        <div class="messages-header">
            <h1><i class="fas fa-envelope"></i> Messages</h1>
            <p>Communicate with our support team</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Messages Content -->
        <div class="messages-content">
            <!-- Conversations Panel -->
            <div class="conversations-panel">
                <div class="panel-header">
                    <h3>Conversations</h3>
                    <button class="new-message-btn" onclick="openNewMessageModal()">
                        <i class="fas fa-plus"></i> New Message
                    </button>
                </div>
                
                <div class="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No conversations yet</p>
                            <small>Start a new conversation with our support team</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                            <div class="conversation-item" onclick="loadConversation(<?php echo $conversation['id']; ?>)">
                                <div class="conversation-subject">
                                    <?php echo htmlspecialchars($conversation['subject']); ?>
                                </div>
                                <div class="conversation-preview">
                                    <?php echo htmlspecialchars($conversation['last_message'] ?? 'No messages yet'); ?>
                                </div>
                                <div class="conversation-meta">
                                    <span class="conversation-status status-<?php echo $conversation['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $conversation['status'])); ?>
                                    </span>
                                    <span class="conversation-date">
                                        <?php echo date('M j, Y', strtotime($conversation['last_message_at'])); ?>
                                    </span>
                                    <?php if ($conversation['unread_count'] > 0): ?>
                                        <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Panel -->
            <div class="chat-panel">
                <div class="chat-header">
                    <h3 id="chatTitle">Select a conversation</h3>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <div class="empty-state">
                        <i class="fas fa-comment-dots"></i>
                        <p>Select a conversation to view messages</p>
                    </div>
                </div>
                
                <div class="chat-input" id="chatInput" style="display: none;">
                    <form method="POST" class="input-group">
                        <input type="hidden" name="conversation_id" id="conversationId">
                        <textarea 
                            name="reply_message" 
                            class="message-input" 
                            placeholder="Type your message..." 
                            rows="1"
                            required
                        ></textarea>
                        <button type="submit" name="send_reply" class="send-btn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div id="newMessageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Message</h3>
                <button class="close-btn" onclick="closeNewMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="message_type">Category</label>
                            <select id="message_type" name="message_type" class="form-control">
                                <option value="general">General Inquiry</option>
                                <option value="support">Technical Support</option>
                                <option value="order_inquiry">Order Inquiry</option>
                                <option value="complaint">Complaint</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" class="form-control" rows="6" required></textarea>
                    </div>
                    
                    <button type="submit" name="send_message" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentConversationId = null;
        
        function openNewMessageModal() {
            document.getElementById('newMessageModal').style.display = 'block';
        }
        
        function closeNewMessageModal() {
            document.getElementById('newMessageModal').style.display = 'none';
        }
        
        function loadConversation(conversationId) {
            currentConversationId = conversationId;
            
            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Load messages via AJAX
            fetch(`get_messages.php?conversation_id=${conversationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.messages, data.conversation);
                        document.getElementById('conversationId').value = conversationId;
                        document.getElementById('chatInput').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function displayMessages(messages, conversation) {
            const chatMessages = document.getElementById('chatMessages');
            const chatTitle = document.getElementById('chatTitle');
            
            chatTitle.textContent = conversation.subject;
            
            if (messages.length === 0) {
                chatMessages.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comment-dots"></i>
                        <p>No messages in this conversation</p>
                    </div>
                `;
                return;
            }
            
            let messagesHtml = '';
            messages.forEach(message => {
                const isCurrentUser = message.sender_type === 'user' && message.sender_id == <?php echo $_SESSION['user_id']; ?>;
                const bubbleClass = isCurrentUser ? 'message-bubble sent' : 'message-bubble';
                const avatarSrc = message.sender_image ? 
                    `uploads/profiles/${message.sender_image}` : 
                    'assets/images/default-avatar.png';
                
                messagesHtml += `
                    <div class="${bubbleClass}">
                        <img src="${avatarSrc}" alt="${message.sender_name}" class="message-avatar">
                        <div class="message-content">
                            <p class="message-text">${message.message}</p>
                            <div class="message-time">${formatDate(message.created_at)}</div>
                        </div>
                    </div>
                `;
            });
            
            chatMessages.innerHTML = messagesHtml;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            
            if (days === 0) {
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } else if (days === 1) {
                return 'Yesterday ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            } else {
                return date.toLocaleDateString();
            }
        }
        
        // Auto-resize textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.querySelector('.message-input');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('newMessageModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Account dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const accountDropdown = document.getElementById('accountDropdown');
            const accountBtn = document.getElementById('accountBtn');
            const accountDropdownContent = document.getElementById('accountDropdownContent');
            
            if (accountBtn) {
                accountBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    accountDropdown.classList.toggle('show');
                });
            }
            
            document.addEventListener('click', function(e) {
                if (accountDropdown && !accountDropdown.contains(e.target)) {
                    accountDropdown.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>