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

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $conversationId = $_POST['conversation_id'];
    $userId = $_POST['user_id'];
    $message = trim($_POST['reply_message']);
    
    if (!empty($message)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Insert the message
            $msgStmt = $conn->prepare("INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, message, conversation_id, message_type, priority, is_read) VALUES ('admin', ?, 'user', ?, ?, ?, 'general', 'normal', FALSE)");
            $msgStmt->bind_param("iisi", $admin_id, $userId, $message, $conversationId);
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
        $markReadStmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE conversation_id = ? AND sender_type = 'user'");
        $markReadStmt->bind_param("i", $conversationId);
        $markReadStmt->execute();
        $markReadStmt->close();
    } catch (Exception $e) {
        $error = "Error marking messages as read: " . $e->getMessage();
    }
}

// Get all conversations for admin - Updated query to match your table structure
$conversationsQuery = "
    SELECT 
        c.*,
        u.fullname as user_name,
        u.profile_image as user_image,
        (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_at,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_type = 'user' AND is_read = FALSE) as unread_count
    FROM conversations c
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY c.updated_at DESC
";

$conversationsResult = $conn->query($conversationsQuery);
$conversations = [];
if ($conversationsResult && $conversationsResult->num_rows > 0) {
    while ($row = $conversationsResult->fetch_assoc()) {
        $conversations[] = $row;
    }
}

// Get selected conversation details and messages
$selectedConversation = null;
$messages = [];
if (isset($_GET['conversation_id'])) {
    $conversationId = intval($_GET['conversation_id']);
    
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
        
        // Get messages for this conversation
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
            WHERE m.conversation_id = ?
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
}

// Get total unread count
$unreadQuery = "SELECT COUNT(*) as total FROM messages WHERE sender_type = 'user' AND is_read = FALSE";
$unreadResult = $conn->query($unreadQuery);
$unreadCount = 0;
if ($unreadResult) {
    $unreadCount = $unreadResult->fetch_assoc()['total'];
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Basic styling for messages page */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            color: white;
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
        }

        .sidebar-logo span {
            color: #ffd700;
        }

        .sidebar-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            float: right;
            cursor: pointer;
            display: none;
        }

        @media (max-width: 767px) {
            .sidebar-close {
                display: block;
            }
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-title {
            color: rgba(255,255,255,0.6);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 20px 20px 10px;
        }

        .sidebar-menu a {
            display: block;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-menu a i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        @media (min-width: 768px) {
            .main-content {
                margin-left: 260px;
            }
        }

        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
        }

        .navbar-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            position: relative;
            color: #666;
            text-decoration: none;
            font-size: 18px;
        }

        .notification-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-dropdown {
            position: relative;
            cursor: pointer;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .admin-info {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .admin-role {
            font-size: 12px;
            color: #666;
        }

        .admin-dropdown-content {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .admin-dropdown.show .admin-dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .admin-dropdown-content a {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .admin-dropdown-content a:hover {
            background: #f8f9fa;
        }

        .messages-container {
            padding: 20px;
        }

        .messages-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .messages-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 8px;
        }

        .messages-header p {
            color: #666;
            margin-bottom: 20px;
        }

        .messages-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            color: white;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .messages-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            height: calc(100vh - 300px);
        }

        @media (max-width: 768px) {
            .messages-content {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        .conversations-panel,
        .chat-panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .panel-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h3 {
            font-size: 18px;
            color: #333;
        }

        .filter-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            color: #666;
        }

        .conversations-list {
            max-height: calc(100vh - 400px);
            overflow-y: auto;
        }

        .conversation-item {
            display: block;
            text-decoration: none;
            color: inherit;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }

        .conversation-item:hover,
        .conversation-item.active {
            background: #f8f9ff;
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .conversation-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .conversation-subject {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }

        .conversation-preview {
            font-size: 13px;
            color: #666;
            line-height: 1.4;
        }

        .unread-badge {
            background: #e74c3c;
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
        }

        .conversation-status {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-open { background: #fff3cd; color: #856404; }
        .status-in_progress { background: #d1ecf1; color: #0c5460; }
        .status-resolved { background: #d4edda; color: #155724; }
        .status-closed { background: #f8d7da; color: #721c24; }

        .priority-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }

        .priority-low { background: #e2f3ff; color: #0066cc; }
        .priority-normal { background: #f0f0f0; color: #666; }
        .priority-high { background: #ffe6cc; color: #cc6600; }
        .priority-urgent { background: #ffcccc; color: #cc0000; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state h3,
        .empty-state h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .chat-info h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }

        .chat-user {
            font-size: 14px;
            color: #666;
        }

        .chat-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .update-status-btn,
        .mark-read-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .mark-read-btn {
            background: #28a745;
        }

        .chat-messages {
            height: calc(100vh - 500px);
            overflow-y: auto;
            padding: 20px;
        }

        .message-bubble {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
            gap: 12px;
        }

        .message-bubble.admin {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .message-content {
            max-width: 70%;
            background: #f1f1f1;
            padding: 12px 16px;
            border-radius: 18px;
            border-top-left-radius: 4px;
        }

        .message-bubble.admin .message-content {
            background: #667eea;
            color: white;
            border-radius: 18px;
            border-top-right-radius: 4px;
            border-top-left-radius: 18px;
        }

        .message-text {
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .message-time {
            font-size: 12px;
            color: #888;
        }

        .message-bubble.admin .message-time {
            color: rgba(255,255,255,0.8);
        }

        .chat-input {
            padding: 20px;
            border-top: 1px solid #eee;
        }

        .input-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 12px 16px;
            resize: none;
            font-family: inherit;
            font-size: 14px;
            max-height: 120px;
        }

        .send-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            white-space: nowrap;
        }

        .send-btn:hover {
            background: #5a6fd8;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">Hiraya<span>Fit</span></a>
            <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-title">MAIN</div>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="orders_admin.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="payment-history.php"><i class="fas fa-money-check-alt"></i> Payment History</a>
            
            <div class="menu-title">INVENTORY</div>
            <a href="products.php"><i class="fas fa-tshirt"></i> Products & Categories</a>
            <a href="stock.php"><i class="fas fa-box"></i> Stock Management</a>
            
            <div class="menu-title">USERS</div>
            <a href="users.php"><i class="fas fa-users"></i> User Management</a>
            
            <div class="menu-title">COMMUNICATION</div>
            <a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
            
            <div class="menu-title">REPORTS & SETTINGS</div>
            <a href="reports.php"><i class="fas fa-file-pdf"></i> Reports & Analytics</a>
            <a href="settings.php"><i class="fas fa-cog"></i> System Settings</a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-navbar">
            <div class="nav-left">
                <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
                <span class="navbar-title">Messages</span>
            </div>
            
            <div class="navbar-actions">
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-count"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="admin-dropdown" id="adminDropdown">
                    <div class="admin-profile">
                        <div class="admin-avatar-container">
                            <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Admin" class="admin-avatar">
                        </div>
                        <div class="admin-info">
                            <span class="admin-name"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                            <span class="admin-role"><?php echo htmlspecialchars($admin['role']); ?></span>
                        </div>
                    </div>
                    
                    <div class="admin-dropdown-content" id="adminDropdownContent">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile Settings</a>
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Messages Container -->
        <div class="messages-container">
            <!-- Messages Header -->
            <div class="messages-header">
                <h1><i class="fas fa-envelope"></i> Message Center</h1>
                <p>Manage customer communications and support requests</p>
                <div class="messages-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($conversations); ?></div>
                        <div class="stat-label">Total Conversations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $unreadCount; ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count(array_filter($conversations, function($c) { return $c['status'] === 'open'; })); ?></div>
                        <div class="stat-label">Open Tickets</div>
                    </div>
                </div>
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
                        <div class="filter-dropdown">
                            <button class="filter-btn">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                    
                    <div class="conversations-list">
                        <?php if (empty($conversations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <h4>No conversations yet</h4>
                                <p>New customer messages will appear here</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <a href="?conversation_id=<?php echo $conversation['id']; ?>" 
                                   class="conversation-item <?php echo (isset($_GET['conversation_id']) && $_GET['conversation_id'] == $conversation['id']) ? 'active' : ''; ?>">
                                    <div class="conversation-header">
                                        <div class="conversation-user">
                                            <img src="<?php echo getProfileImageUrl($conversation['user_image']); ?>" 
                                                 alt="User Avatar" class="user-avatar">
                                            <div>
                                                <div class="conversation-subject">
                                                    <?php echo htmlspecialchars($conversation['user_name'] ?: 'Unknown User'); ?>
                                                </div>
                                                <div class="conversation-preview">
                                                    <?php echo htmlspecialchars(substr($conversation['last_message'] ?: $conversation['subject'], 0, 100)); ?>
                                                    <?php echo strlen($conversation['last_message'] ?: $conversation['subject']) > 100 ? '...' : ''; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $conversation['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="conversation-meta">
                                        <div>
                                            <span class="conversation-status status-<?php echo $conversation['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $conversation['status'])); ?>
                                            </span>
                                            <span class="priority-badge priority-<?php echo $conversation['priority']; ?>">
                                                <?php echo ucfirst($conversation['priority']); ?>
                                            </span>
                                        </div>
                                        <span><?php echo date('M j, Y', strtotime($conversation['last_message_at'] ?: $conversation['created_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel">
                    <?php if ($selectedConversation): ?>
                        <div class="chat-header">
                            <div class="chat-info">
                                <h3><?php echo htmlspecialchars($selectedConversation['subject']); ?></h3>
                                <div class="chat-user">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($selectedConversation['user_name']); ?>
                                    (<?php echo htmlspecialchars($selectedConversation['user_email']); ?>)
                                </div>
                            </div>
                            
                            <div class="chat-actions">
                                <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="conversation_id" value="<?php echo $selectedConversation['id']; ?>">
                                    <select name="new_status" class="status-select">
                                        <option value="open" <?php echo $selectedConversation['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo $selectedConversation['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $selectedConversation['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $selectedConversation['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <button type="submit" name="update_status" class="update-status-btn">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="conversation_id" value="<?php echo $selectedConversation['id']; ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="chat-messages" id="chatMessages">
                            <?php if (empty($messages)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comment"></i>
                                    <h4>No messages yet</h4>
                                    <p>Start the conversation by sending a message</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message-bubble <?php echo $message['sender_type']; ?>">
                                        <img src="<?php echo getProfileImageUrl($message['sender_image']); ?>" 
                                             alt="Avatar" class="message-avatar">
                                        <div class="message-content">
                                            <p class="message-text"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                            <div class="message-time">
                                                <?php echo htmlspecialchars($message['sender_name']); ?> • 
                                                <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="chat-input">
                            <form method="POST" class="input-group">
                                <input type="hidden" name="conversation_id" value="<?php echo $selectedConversation['id']; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $selectedConversation['user_id']; ?>">
                                <textarea name="reply_message" class="message-input" 
                                         placeholder="Type your reply..." required rows="1"></textarea>
                                <button type="submit" name="send_reply" class="send-btn">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the list to view messages and reply</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        document.getElementById('sidebarClose').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
        });

        // Admin dropdown functionality
        document.getElementById('adminDropdown').addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('adminDropdown').classList.remove('show');
        });

        // Auto-scroll chat messages to bottom
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Scroll to bottom on page load
        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });

        // Auto-resize textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.querySelector('.message-input');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });

                // Handle Enter key to send message (Shift+Enter for new line)
                textarea.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        const form = this.closest('form');
                        if (this.value.trim()) {
                            form.submit();
                        }
                    }
                });
            }
        });

        // Auto-refresh messages every 30 seconds
        setInterval(function() {
            if (window.location.search.includes('conversation_id=')) {
                // Only refresh if we're viewing a specific conversation
                const urlParams = new URLSearchParams(window.location.search);
                const conversationId = urlParams.get('conversation_id');
                if (conversationId) {
                    // You can implement AJAX refresh here if needed
                    // For now, we'll keep it simple and let users manually refresh
                }
            }
        }, 30000);

        // Alert auto-hide
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>