<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your environment-aware database connection
include 'db_connect.php';

// Initialize variables
$error = '';
$username_email = '';
// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$user = null;

// If user is not logged in, redirect to login page
if (!$loggedIn) {
    header("Location: login.php");
    exit();
}



// Fetch user details
$stmt = $conn->prepare("SELECT id, fullname, username, email, address, phone, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// If user exists, store their details
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - HirayaFit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <link rel="stylesheet" href="style/messagesUser.css">
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
                                    <a href="orders.php" class="active"><i class="fas fa-box"></i> My Orders</a>
        
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

                    <a href="messagesUser.php"><i class="fas fa-envelope"></i></a>
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
            <a   href="usershop.php">HOME</a>
        </nav>
    </header>

    <?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php';

// Check if user is logged in
$loggedIn = isset($_SESSION['user_id']);
$user = null;

if (!$loggedIn) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$stmt = $conn->prepare("SELECT id, fullname, username, email, address, phone, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_message') {
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $category = $_POST['category'] ?? 'general';
        $priority = $_POST['priority'] ?? 'medium';
        
        if (!empty($subject) && !empty($message)) {
            $stmt = $conn->prepare("INSERT INTO messages (user_id, subject, message, sender_type, category, priority) VALUES (?, ?, ?, 'user', ?, ?)");
            $stmt->bind_param("issss", $_SESSION['user_id'], $subject, $message, $category, $priority);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
            exit;
        }
    }
    
    if ($action === 'get_conversation') {
        $message_id = intval($_POST['message_id']);
        
        $stmt = $conn->prepare("
            SELECT m.*, u.fullname as user_name, u.profile_image as user_image,
                   a.fullname as admin_name, a.profile_image as admin_image
            FROM messages m 
            LEFT JOIN users u ON m.user_id = u.id 
            LEFT JOIN admins a ON m.admin_id = a.admin_id 
            WHERE (m.message_id = ? OR m.parent_message_id = ?) AND m.user_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iii", $message_id, $message_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Mark messages as read
        $update_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE (message_id = ? OR parent_message_id = ?) AND sender_type = 'admin'");
        $update_stmt->bind_param("ii", $message_id, $message_id);
        $update_stmt->execute();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }
    
    if ($action === 'send_reply') {
        $message_id = intval($_POST['message_id']);
        $reply_content = trim($_POST['reply_content']);
        
        if (!empty($reply_content)) {
            $stmt = $conn->prepare("INSERT INTO messages (user_id, subject, message, sender_type, parent_message_id) VALUES (?, 'Re: Your Reply', ?, 'user', ?)");
            $stmt->bind_param("isi", $_SESSION['user_id'], $reply_content, $message_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send reply']);
            }
            exit;
        }
    }
}

// Get user's messages
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_clauses = ["m.user_id = ?", "m.parent_message_id IS NULL"];
$params = [$_SESSION['user_id']];
$param_types = "i";

if ($filter_status === 'unread') {
    $where_clauses[] = "EXISTS (SELECT 1 FROM messages replies WHERE replies.parent_message_id = m.message_id AND replies.sender_type = 'admin' AND replies.is_read = FALSE)";
} elseif ($filter_status === 'resolved') {
    $where_clauses[] = "m.is_resolved = TRUE";
} elseif ($filter_status === 'unresolved') {
    $where_clauses[] = "m.is_resolved = FALSE";
}

if (!empty($search)) {
    $where_clauses[] = "(m.subject LIKE ? OR m.message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $param_types .= "ss";
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "
    SELECT m.*, 
           (SELECT COUNT(*) FROM messages replies WHERE replies.parent_message_id = m.message_id) as reply_count,
           (SELECT COUNT(*) FROM messages replies WHERE replies.parent_message_id = m.message_id AND replies.sender_type = 'admin' AND replies.is_read = FALSE) as unread_replies
    FROM messages m 
    WHERE $where_sql
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$messages_result = $stmt->get_result();

// Get message counts
$counts_sql = "
    SELECT 
        COUNT(*) as total_messages,
        SUM(CASE WHEN is_resolved = FALSE THEN 1 ELSE 0 END) as unresolved_count,
        (SELECT COUNT(*) FROM messages replies WHERE replies.user_id = ? AND replies.sender_type = 'admin' AND replies.is_read = FALSE) as unread_count
    FROM messages m 
    WHERE user_id = ? AND parent_message_id IS NULL
";
$counts_stmt = $conn->prepare($counts_sql);
$counts_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$counts_stmt->execute();
$counts = $counts_stmt->get_result()->fetch_assoc();

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - HirayaFit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/usershop.css">
    <style>
        /* Additional styles for messages page */
        .messages-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .messages-title {
            font-size: 28px;
            font-weight: 700;
            color: #111;
        }

        .new-message-btn {
            background: linear-gradient(135deg, #0071c5, #005a9f);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .new-message-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 113, 197, 0.3);
        }

        .message-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #0071c5;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #0071c5;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
        }

        .message-filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }

        .filter-select, .search-input {
            padding: 10px 15px;
            border: 2px solid #eee;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-select:focus, .search-input:focus {
            outline: none;
            border-color: #0071c5;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
        }

        .messages-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .message-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .message-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }

        .message-item.has-unread {
            background: linear-gradient(90deg, #f0f8ff, #ffffff);
            border-left: 4px solid #0071c5;
        }

        .message-status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #28a745;
            margin-right: 10px;
        }

        .message-status-indicator.unresolved {
            background-color: #ffc107;
        }

        .message-status-indicator.new {
            background-color: #0071c5;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .message-subject {
            font-weight: 600;
            color: #111;
            font-size: 16px;
        }

        .message-time {
            font-size: 12px;
            color: #666;
        }

        .message-preview {
            color: #666;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .message-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .message-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-category {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .badge-priority {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .badge-urgent {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .badge-resolved {
            background-color: #e8f5e8;
            color: #2e7d32;
        }

        .badge-replies {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-unread {
            background-color: #ff5722;
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 25px;
            background: linear-gradient(135deg, #0071c5, #005a9f);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #111;
            margin-bottom: 8px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #0071c5;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0071c5, #005a9f);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 113, 197, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Conversation View */
        .conversation-container {
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .conversation-message {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 10px;
            position: relative;
        }

        .conversation-message.user {
            background: #e3f2fd;
            border-left: 4px solid #0071c5;
            margin-left: 20px;
        }

        .conversation-message.admin {
            background: #f5f5f5;
            border-left: 4px solid #28a745;
            margin-right: 20px;
        }

        .message-sender {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            color: #111;
        }

        .message-text {
            color: #333;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .message-timestamp {
            font-size: 12px;
            color: #666;
            text-align: right;
        }

        .reply-section {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .messages-container {
                padding: 10px;
            }

            .messages-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .message-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                width: 100%;
            }

            .form-row {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .message-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .message-meta {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'userheaderGuest.php'; ?>

    <div class="messages-container">
        <!-- Header -->
        <div class="messages-header">
            <h1 class="messages-title">
                <i class="fas fa-envelope"></i> My Messages
            </h1>
            <button class="new-message-btn" onclick="openNewMessageModal()">
                <i class="fas fa-plus"></i> New Message
            </button>
        </div>

        <!-- Message Statistics -->
        <div class="message-stats">
            <div class="stat-card">
                <div class="stat-number"><?= $counts['total_messages'] ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $counts['unresolved_count'] ?></div>
                <div class="stat-label">Unresolved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $counts['unread_count'] ?></div>
                <div class="stat-label">Unread Replies</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="message-filters">
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select class="filter-select" id="statusFilter" onchange="filterMessages()">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Messages</option>
                    <option value="unread" <?= $filter_status === 'unread' ? 'selected' : '' ?>>Unread</option>
                    <option value="unresolved" <?= $filter_status === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
                    <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                </select>
            </div>
            <div class="filter-group search-box">
                <label class="filter-label">Search</label>
                <input type="text" class="search-input" id="searchInput" placeholder="Search messages..." 
                       value="<?= htmlspecialchars($search) ?>" onkeyup="debounceSearch()">
            </div>
        </div>

        <!-- Messages List -->
        <div class="messages-list">
            <?php if ($messages_result->num_rows > 0): ?>
                <?php while ($message = $messages_result->fetch_assoc()): ?>
                    <div class="message-item <?= $message['unread_replies'] > 0 ? 'has-unread' : '' ?>" 
                         onclick="openConversation(<?= $message['message_id'] ?>)">
                        
                        <div class="message-status-indicator <?= $message['is_resolved'] ? '' : 'unresolved' ?>"></div>
                        
                        <div class="message-content">
                            <div class="message-header">
                                <div class="message-subject"><?= htmlspecialchars($message['subject']) ?></div>
                                <div class="message-time"><?= date('M j, Y g:i A', strtotime($message['created_at'])) ?></div>
                            </div>
                            
                            <div class="message-preview">
                                <?= substr(htmlspecialchars($message['message']), 0, 120) ?>...
                            </div>
                            
                            <div class="message-meta">
                                <?php if ($message['category']): ?>
                                    <span class="message-badge badge-category"><?= ucfirst($message['category']) ?></span>
                                <?php endif; ?>
                                
                                <?php if ($message['priority'] === 'urgent'): ?>
                                    <span class="message-badge badge-urgent">Urgent</span>
                                <?php elseif ($message['priority'] === 'high'): ?>
                                    <span class="message-badge badge-priority">High</span>
                                <?php endif; ?>
                                
                                <?php if ($message['is_resolved']): ?>
                                    <span class="message-badge badge-resolved">Resolved</span>
                                <?php endif; ?>
                                
                                <?php if ($message['reply_count'] > 0): ?>
                                    <span class="message-badge badge-replies"><?= $message['reply_count'] ?> replies</span>
                                <?php endif; ?>
                                
                                <?php if ($message['unread_replies'] > 0): ?>
                                    <span class="message-badge badge-unread"><?= $message['unread_replies'] ?> new</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No messages found</h3>
                    <p>You haven't sent any messages yet. Click "New Message" to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- New Message Modal -->
    <div id="newMessageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Send New Message</h2>
                <button class="modal-close" onclick="closeNewMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="newMessageForm">
                    <div class="form-group">
                        <label class="form-label">Subject <span style="color: red;">*</span></label>
                        <input type="text" class="form-input" name="subject" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="general">General</option>
                                <option value="technical">Technical Support</option>
                                <option value="billing">Billing</option>
                                <option value="complaint">Complaint</option>
                                <option value="feedback">Feedback</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message <span style="color: red;">*</span></label>
                        <textarea class="form-textarea" name="message" required 
                                placeholder="Please describe your message in detail..."></textarea>
                    </div>
                    
                    <div style="text-align: right;">
                        <button type="button" class="btn-secondary" onclick="closeNewMessageModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Conversation Modal -->
    <div id="conversationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Conversation</h2>
                <button class="modal-close" onclick="closeConversationModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="conversationContent">
                    <div class="loading">
                        <i class="fas fa-spinner"></i> Loading conversation...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;
        let currentConversationId = null;

        // Filter messages
        function filterMessages() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchInput').value;
            
            const params = new URLSearchParams();
            if (status !== 'all') params.append('status', status);
            if (search) params.append('search', search);
            
            window.location.href = '?' + params.toString();
        }

        // Debounced search
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(filterMessages, 500);
        }

        // New Message Modal
        function openNewMessageModal() {
            document.getElementById('newMessageModal').style.display = 'block';
        }

        function closeNewMessageModal() {
            document.getElementById('newMessageModal').style.display = 'none';
            document.getElementById('newMessageForm').reset();
        }

        // Conversation Modal
        function openConversation(messageId) {
            currentConversationId = messageId;
            document.getElementById('conversationModal').style.display = 'block';
            loadConversation(messageId);
        }

        function closeConversationModal() {
            document.getElementById('conversationModal').style.display = 'none';
            currentConversationId = null;
        }

        // Load conversation
        function loadConversation(messageId) {
            const formData = new FormData();
            formData.append('action', 'get_conversation');
            formData.append('message_id', messageId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayConversation(data.messages);
                } else {
                    document.getElementById('conversationContent').innerHTML = 
                        '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error loading conversation</h3></div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('conversationContent').innerHTML = 
                    '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error loading conversation</h3></div>';
            });
        }

        // Display conversation
        function displayConversation(messages) {
            let html = '<div class="conversation-container">';
            
            messages.forEach(message => {
                const isUser = message.sender_type === 'user';
                const senderName = isUser ? message.user_name || 'You' : message.admin_name || 'Admin';
                const messageClass = isUser ? 'user' : 'admin';
                
                html += `
                    <div class="conversation-message ${messageClass}">
                        <div class="message-sender">
                            <i class="fas fa-${isUser ? 'user' : 'user-shield'}"></i> ${senderName}
                        </div>
                        <div class="message-text">${message.message.replace(/\n/g, '<br>')}</div>
                        <div class="message-timestamp">${formatDate(message.created_at)}</div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Add reply section
            html += `
                <div class="reply-section">
                    <form id="replyForm" onsubmit="sendReply(event)">
                        <div class="form-group">
                            <label class="form-label">Your Reply</label>
                            <textarea class="form-textarea" name="reply_content" placeholder="Type your reply here..." required></textarea>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-reply"></i> Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
            document.getElementById('conversationContent').innerHTML = html;
            
            // Scroll to bottom
            const container = document.querySelector('.conversation-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Send reply
        function sendReply(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            formData.append('action', 'send_reply');
            formData.append('message_id', currentConversationId);
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the form
                    event.target.reset();
                    // Reload the conversation
                    loadConversation(currentConversationId);
                    // Show success message
                    showNotification('Reply sent successfully!', 'success');
                } else {
                    showNotification(data.message || 'Failed to send reply', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error sending reply', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        // Send new message
        document.getElementById('newMessageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'send_message');
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Message sent successfully!', 'success');
                    closeNewMessageModal();
                    // Reload the page to show the new message
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Failed to send message', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error sending message', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Utility functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add notification styles if not already added
            if (!document.getElementById('notification-styles')) {
                const style = document.createElement('style');
                style.id = 'notification-styles';
                style.textContent = `
                    .notification {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        padding: 15px 20px;
                        border-radius: 8px;
                        color: white;
                        font-weight: 600;
                        z-index: 9999;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        animation: slideIn 0.3s ease-out;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                    }
                    .notification-success { background: #28a745; }
                    .notification-error { background: #dc3545; }
                    .notification-info { background: #007bff; }
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(notification);
            
            // Remove notification after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const newMessageModal = document.getElementById('newMessageModal');
            const conversationModal = document.getElementById('conversationModal');
            
            if (event.target === newMessageModal) {
                closeNewMessageModal();
            }
            if (event.target === conversationModal) {
                closeConversationModal();
            }
        }

        // Handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeNewMessageModal();
                closeConversationModal();
            }
        });

        // Auto-refresh unread count every 30 seconds
        setInterval(function() {
            fetch('', {
                method: 'POST',
                body: new URLSearchParams({ action: 'get_unread_count' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.count > 0) {
                    // Update any unread indicators in the UI
                    const title = document.title;
                    if (!title.startsWith('(')) {
                        document.title = `(${data.count}) ${title}`;
                    }
                }
            })
            .catch(error => console.error('Error checking unread count:', error));
        }, 30000);

        // Initialize tooltips and other UI enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to buttons
            const buttons = document.querySelectorAll('button, .btn');
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    if (this.type !== 'submit') return;
                    
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    this.disabled = true;
                    
                    // Re-enable after 5 seconds as fallback
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 5000);
                });
            });

            // Add smooth scrolling for better UX
            const messageItems = document.querySelectorAll('.message-item');
            messageItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(8px)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });

            // Auto-focus search input when typing
            document.addEventListener('keydown', function(event) {
                if (event.target.tagName !== 'INPUT' && event.target.tagName !== 'TEXTAREA' && 
                    !event.ctrlKey && !event.metaKey && event.key.match(/[a-zA-Z0-9]/)) {
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.value = event.key;
                    }
                }
            });
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>