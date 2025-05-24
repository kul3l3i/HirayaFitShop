<?php
session_start();
include 'db_connect.php';
require_once 'MessageHandler.php';

// Initialize variables
$error = '';
$success = '';

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

// Set default role if not present
if (!isset($admin['role'])) {
    $admin['role'] = 'Administrator';
}

// Initialize message handler
$messageHandler = new MessageHandler($conn);

// Handle message status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $conversationId = $_POST['conversation_id'];
    $newStatus = $_POST['new_status'];
    
    try {
        $updateStmt = $conn->prepare("UPDATE message_conversations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $conversationId);
        $updateStmt->execute();
        $success = "Conversation status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $conversationId = $_POST['conversation_id'];
    $message = trim($_POST['reply_message']);
    
    if (!empty($message)) {
        try {
            // Get conversation details to reply to the correct user
            $convStmt = $conn->prepare("SELECT user_id FROM message_conversations WHERE id = ?");
            $convStmt->bind_param("i", $conversationId);
            $convStmt->execute();
            $convResult = $convStmt->get_result();
            $conversation = $convResult->fetch_assoc();
            
            if ($conversation) {
                $messageHandler->sendMessage(
                    'admin', 
                    $admin_id, 
                    'user', 
                    $conversation['user_id'],
                    '', // Empty subject for replies
                    $message, 
                    'general', 
                    'normal',
                    $conversationId
                );
                $success = "Reply sent successfully!";
            }
        } catch (Exception $e) {
            $error = "Error sending reply: " . $e->getMessage();
        }
    }
}

// Get all conversations for admin
$conversationsQuery = "
    SELECT 
        mc.*,
        u.fullname as user_name,
        u.email as user_email,
        u.profile_image as user_image,
        (SELECT message FROM messages WHERE conversation_id = mc.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = mc.id ORDER BY created_at DESC LIMIT 1) as last_message_at,
        (SELECT COUNT(*) FROM messages WHERE conversation_id = mc.id AND sender_type = 'user' AND is_read = FALSE) as unread_count
    FROM message_conversations mc
    LEFT JOIN users u ON mc.user_id = u.id
    ORDER BY mc.updated_at DESC
";

$conversationsResult = $conn->query($conversationsQuery);
$conversations = [];
while ($row = $conversationsResult->fetch_assoc()) {
    $conversations[] = $row;
}

// Get total unread count for admin
$unreadQuery = "
    SELECT COUNT(*) as total_unread 
    FROM messages m 
    JOIN message_conversations mc ON m.conversation_id = mc.id 
    WHERE m.sender_type = 'user' AND m.is_read = FALSE
";
$unreadResult = $conn->query($unreadQuery);
$unreadCount = $unreadResult->fetch_assoc()['total_unread'];

// Function to get profile image URL
function getProfileImageUrl($profileImage) {
    if (!empty($profileImage) && file_exists("uploads/profiles/" . $profileImage)) {
        return "uploads/profiles/" . $profileImage;
    } else {
        return "assets/images/default-avatar.png";
    }
}

$profileImageUrl = getProfileImageUrl($admin['profile_image']);
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
        :root {
            --primary: #111111;
            --secondary: #0071c5;
            --accent: #e5e5e5;
            --light: #ffffff;
            --dark: #111111;
            --grey: #767676;
            --sidebar-width: 250px;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            font-size: 22px;
            font-weight: 700;
            color: var(--light);
            text-decoration: none;
            letter-spacing: -0.5px;
        }
        
        .sidebar-logo span {
            color: var(--secondary);
        }
        
        .sidebar-close {
            color: white;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            display: none;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #adb5bd;
            padding: 10px 20px;
            margin-top: 10px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: #e9ecef;
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: var(--secondary);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.08);
            border-left: 3px solid var(--secondary);
            color: var(--secondary);
        }
        
        .sidebar-menu a i {
            margin-right: 10px;
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        /* Top Navigation */
        .top-navbar {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            color: var(--dark);
            font-size: 20px;
            cursor: pointer;
            display: none;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
        }
        
        .navbar-title {
            font-weight: 600;
            color: var(--dark);
            font-size: 18px;
            margin-right: 20px;
        }
        
        .welcome-text {
            font-size: 14px;
            color: var(--grey);
        }
        
        .welcome-text strong {
            color: var(--dark);
            font-weight: 600;
        }
        
        .navbar-actions {
            display: flex;
            align-items: center;
        }
        
        .navbar-actions .nav-link {
            color: var(--dark);
            font-size: 18px;
            margin-right: 20px;
            position: relative;
        }
        
        .notification-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Admin Profile Styles */
        .admin-profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
        }

        .admin-avatar-container {
            position: relative;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            overflow: hidden;
            border: 2px solid var(--secondary);
            margin-right: 10px;
        }

        .admin-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-info {
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-weight: 600;
            font-size: 14px;
            display: block;
            line-height: 1.2;
        }

        .admin-role {
            font-size: 12px;
            color: var(--secondary);
            display: block;
        }

        .admin-dropdown {
            position: relative;
        }

        .admin-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 45px;
            background-color: white;
            min-width: 220px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 1000;
            border-radius: 4px;
            overflow: hidden;
        }

        .admin-dropdown-header {
            background-color: var(--dark);
            color: white;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-dropdown-avatar-container {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            overflow: hidden;
            border: 2px solid var(--secondary);
            margin-right: 15px;
        }

        .admin-dropdown-avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-dropdown-info {
            display: flex;
            flex-direction: column;
        }

        .admin-dropdown-name {
            font-weight: 600;
            font-size: 16px;
        }

        .admin-dropdown-role {
            font-size: 12px;
            color: var(--secondary);
        }

        .admin-dropdown-user {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .admin-dropdown-user-name {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }

        .admin-dropdown-user-email {
            color: #6c757d;
            font-size: 14px;
        }

        .admin-dropdown-content a {
            color: var(--dark);
            padding: 12px 15px;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
            border-bottom: 1px solid #f5f5f5;
        }

        .admin-dropdown-content a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .admin-dropdown-content a.logout {
            color: var(--danger);
        }

        .admin-dropdown-content a:hover {
            background-color: #f8f9fa;
        }

        .admin-dropdown.show .admin-dropdown-content {
            display: block;
        }
        
        /* Messages Container */
        .messages-container {
            padding: 30px;
        }
        
        .messages-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .messages-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
        }
        
        .messages-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            min-height: 600px;
        }
        
        .conversations-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .panel-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-header h3 {
            margin: 0;
            color: #333;
        }
        
        .conversations-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f1f1;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .conversation-item:hover {
            background-color: #f8f9fa;
        }
        
        .conversation-item.active {
            background-color: #e3f2fd;
            border-left: 4px solid var(--secondary);
        }
        
        .conversation-subject {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .conversation-user {
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .conversation-user i {
            margin-right: 5px;
        }
        
        .conversation-preview {
            color: #666;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #888;
        }
        
        .conversation-status {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .status-open { background: #d4edda; color: #155724; }
        .status-in_progress { background: #fff3cd; color: #856404; }
        .status-resolved { background: #d1ecf1; color: #0c5460; }
        .status-closed { background: #f8d7da; color: #721c24; }
        
        .unread-badge {
            background: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .chat-panel {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .update-status-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .update-status-btn:hover {
            background: #005a9e;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            max-height: 400px;
        }
        
        .message-bubble {
            margin-bottom: 1rem;
            display: flex;
            gap: 0.75rem;
        }
        
        .message-bubble.sent {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .message-content {
            max-width: 70%;
            background: #f1f3f4;
            padding: 0.75rem;
            border-radius: 18px;
        }
        
        .message-bubble.sent .message-content {
            background: var(--secondary);
            color: white;
        }
        
        .message-text {
            margin: 0;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #888;
            margin-top: 0.25rem;
        }
        
        .message-bubble.sent .message-time {
            color: rgba(255,255,255,0.8);
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .message-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            resize: none;
            font-family: inherit;
        }
        
        .send-btn {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .send-btn:hover {
            background: #005a9e;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .welcome-text {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                width: var(--sidebar-width);
                transform: translateX(0);
            }
            
            .sidebar-close {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
            
            .navbar-title {
                display: none;
            }
            
            .messages-content {
                grid-template-columns: 1fr;
            }
            
            .conversations-panel {
                margin-bottom: 1rem;
            }
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
                <div class="welcome-text">Customer <strong>Support Center</strong></div>
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
                        <div class="admin-dropdown-header">
                            <div class="admin-dropdown-avatar-container">
                                <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Admin" class="admin-dropdown-avatar">
                            </div>
                            <div class="admin-dropdown-info">
                                <span class="admin-dropdown-name"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                                <span class="admin-dropdown-role"><?php echo htmlspecialchars($admin['role']); ?></span>
                            </div>
                        </div>
                        <div class="admin-dropdown-user">
                            <h4 class="admin-dropdown-user-name"><?php echo htmlspecialchars($admin['fullname']); ?></h4>
                            <p class="admin-dropdown-user-email"><?php echo htmlspecialchars($admin['email']); ?></p>
                        </div>
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
                <h1><i class="fas fa-envelope"></i> Customer Messages</h1>
                <p>Manage customer inquiries and support requests</p>
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
                        <h3>Customer Conversations (<?php echo count($conversations); ?>)</h3>
                        <?php if ($unreadCount > 0): ?>
                            <span class="unread-badge"><?php echo $unreadCount; ?> unread</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="conversations-list">
                        <?php if (empty($conversations)): ?>
                            <div class="empty-state">
                                <i class="fas fa-comments"></i>
                                <p>No conversations yet</p>
                                <small>Customer messages will appear here</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conversation): ?>
                                <div class="conversation-item" onclick="loadConversation(<?php echo $conversation['id']; ?>)" data-conversation-id="<?php echo $conversation['id']; ?>">
                                    <div class="conversation-subject">
                                        <?php echo htmlspecialchars($conversation['subject']); ?>
                                    </div>
                                    <div class="conversation-user">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($conversation['user_name']); ?> (<?php echo htmlspecialchars($conversation['user_email']); ?>)
                                    </div>
                                    <div class="conversation-preview">
                                        <?php echo htmlspecialchars($conversation['last_message'] ?? 'No messages yet'); ?>
                                    </div>
                                    <div class="conversation-meta">
                                        <span class="conversation-status status-<?php echo $conversation['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $conversation['status'])); ?>
                                        </span>
                                        <span class="conversation-date">
                                            <?php echo $conversation['last_message_at'] ? date('M j, Y', strtotime($conversation['last_message_at'])) : date('M j, Y', strtotime($conversation['created_at'])); ?>
                                        </span>
                                        <?php if ($conversation['unread_count'] > 0): ?>
                                            <span class=