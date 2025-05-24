<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: sign-in.php");
    exit;
}

// Get admin information
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_reply') {
        $message_id = intval($_POST['message_id']);
        $reply_content = trim($_POST['reply_content']);
        $user_id = intval($_POST['user_id']);
        
        if (!empty($reply_content)) {
            // Insert reply
            $stmt = $conn->prepare("INSERT INTO messages (user_id, admin_id, subject, message, sender_type, parent_message_id) VALUES (?, ?, 'Re: Your Message', ?, 'admin', ?)");
            $stmt->bind_param("iisi", $user_id, $admin_id, $reply_content, $message_id);
            
            if ($stmt->execute()) {
                // Mark original message as read
                $update_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE message_id = ?");
                $update_stmt->bind_param("i", $message_id);
                $update_stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send reply']);
            }
            exit;
        }
    }
    
    if ($action === 'mark_resolved') {
        $message_id = intval($_POST['message_id']);
        $stmt = $conn->prepare("UPDATE messages SET is_resolved = TRUE WHERE message_id = ?");
        $stmt->bind_param("i", $message_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Message marked as resolved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update message status']);
        }
        exit;
    }
    
    if ($action === 'get_conversation') {
        $message_id = intval($_POST['message_id']);
        
        // Get main message
        $stmt = $conn->prepare("
            SELECT m.*, u.fullname as user_name, u.profile_image as user_image,
                   a.fullname as admin_name, a.profile_image as admin_image
            FROM messages m 
            LEFT JOIN users u ON m.user_id = u.id 
            LEFT JOIN admins a ON m.admin_id = a.admin_id 
            WHERE m.message_id = ? OR m.parent_message_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("ii", $message_id, $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = ["m.parent_message_id IS NULL"]; // Only show main messages, not replies
$params = [];
$param_types = "";

if ($filter_status !== 'all') {
    if ($filter_status === 'unread') {
        $where_clauses[] = "m.is_read = FALSE";
    } elseif ($filter_status === 'resolved') {
        $where_clauses[] = "m.is_resolved = TRUE";
    } elseif ($filter_status === 'unresolved') {
        $where_clauses[] = "m.is_resolved = FALSE";
    }
}

if ($filter_category !== 'all') {
    $where_clauses[] = "m.category = ?";
    $params[] = $filter_category;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_clauses[] = "(m.subject LIKE ? OR m.message LIKE ? OR u.fullname LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= "sss";
}

$where_sql = implode(" AND ", $where_clauses);

// Get messages with user info
$sql = "
    SELECT m.*, u.fullname as user_name, u.email as user_email, u.profile_image as user_image,
           (SELECT COUNT(*) FROM messages replies WHERE replies.parent_message_id = m.message_id) as reply_count
    FROM messages m 
    LEFT JOIN users u ON m.user_id = u.id 
    WHERE $where_sql
    ORDER BY m.created_at DESC
    LIMIT 50
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$messages_result = $stmt->get_result();

// Get message counts for dashboard
$counts_sql = "
    SELECT 
        COUNT(*) as total_messages,
        SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_count,
        SUM(CASE WHEN is_resolved = FALSE THEN 1 ELSE 0 END) as unresolved_count,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
    FROM messages m 
    WHERE parent_message_id IS NULL
";
$counts_result = $conn->query($counts_sql);
$counts = $counts_result->fetch_assoc();

// Get quick responses
$quick_responses_sql = "SELECT * FROM quick_responses WHERE admin_id = ? AND is_active = TRUE ORDER BY category, title";
$qr_stmt = $conn->prepare($quick_responses_sql);
$qr_stmt->bind_param("i", $admin_id);
$qr_stmt->execute();
$quick_responses = $qr_stmt->get_result();

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

        /* Sidebar Styles - Same as dashboard */
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
        
        /* Main Content */
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
        
        .navbar-actions {
            display: flex;
            align-items: center;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
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

        /* Messages Content */
        .messages-container {
            padding: 30px;
        }

        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .messages-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
        }

        .message-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 120px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--secondary);
        }

        .stat-label {
            font-size: 14px;
            color: var(--grey);
            margin-top: 5px;
        }

        .message-filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            color: var(--grey);
            text-transform: uppercase;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-box {
            flex: 1;
            max-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .messages-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .message-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .message-item:hover {
            background-color: #f8f9fa;
        }

        .message-item.unread {
            background-color: #f0f8ff;
            border-left: 4px solid var(--secondary);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--accent);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .message-sender {
            font-weight: 600;
            color: var(--dark);
        }

        .message-time {
            font-size: 12px;
            color: var(--grey);
        }

        .message-subject {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .message-preview {
            color: var(--grey);
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .message-meta {
            display: flex;
            gap: 10px;
            margin-top: 10px;
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

        .message-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-reply {
            background-color: var(--secondary);
            color: white;
        }

        .btn-resolve {
            background-color: var(--success);
            color: white;
        }

        .btn-resolve:hover {
            background-color: #218838;
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
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--grey);
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .conversation-messages {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .conversation-message {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 8px;
        }

        .conversation-message.user {
            background-color: #f8f9fa;
            margin-right: 20px;
        }

        .conversation-message.admin {
            background-color: #e3f2fd;
            margin-left: 20px;
        }

        .conversation-sender {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .conversation-time {
            font-size: 12px;
            color: var(--grey);
        }

        .reply-form {
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .reply-textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-family: inherit;
        }

        .reply-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .quick-response-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-width: 200px;
        }

        .btn-send {
            background-color: var(--secondary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-send:hover {
            background-color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                width: var(--sidebar-width);
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .message-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .message-item {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .message-header {
                flex-direction: column;
                align-items: stretch;
                gap: 5px;
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
            <a href="messages_admin.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
            
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
                <span class="navbar-title">Messages</span>
            </div>
            
            <div class="navbar-actions">
                <div class="admin-profile">
                    <div class="admin-avatar-container">
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" alt="Admin" class="admin-avatar">
                    </div>
                    <div class="admin-info">
                        <span class="admin-name"><?php echo htmlspecialchars($admin['fullname']); ?></span>
                        <span class="admin-role"><?php echo htmlspecialchars($admin['role']); ?></span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Messages Content -->
        <div class="messages-container">
            <div class="messages-header">
                <h1 class="messages-title">Customer Messages</h1>
            </div>

            <!-- Message Statistics -->
            <div class="message-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $counts['total_messages']; ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $counts['unread_count']; ?></div>
                    <div class="stat-label">Unread</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $counts['unresolved_count']; ?></div>
                    <div class="stat-label">Unresolved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $counts['urgent_count']; ?></div>
                    <div class="stat-label">Urgent</div>
                </div>
            </div>

            <!-- Message Filters -->
            <div class="message-filters">
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="statusFilter" onchange="applyFilters()">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Messages</option>
                        <option value="unread" <?php echo $filter_status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                        <option value="resolved" <?php echo $filter_status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="unresolved" <?php echo $filter_status === 'unresolved' ? 'selected' : ''; ?>>Unresolved</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
                        <option value="all" <?php echo $filter_category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="general" <?php echo $filter_category === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="order_inquiry" <?php echo $filter_category === 'order_inquiry' ? 'selected' : ''; ?>>Order Inquiry</option>
                        <option value="product_inquiry" <?php echo $filter_category === 'product_inquiry' ? 'selected' : ''; ?>>Product Inquiry</option>
                        <option value="complaint" <?php echo $filter_category === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                        <option value="suggestion" <?php echo $filter_category === 'suggestion' ? 'selected' : ''; ?>>Suggestion</option>
                        <option value="technical_support" <?php echo $filter_category === 'technical_support' ? 'selected' : ''; ?>>Technical Support</option>
                    </select>
                </div>
                
                <div class="filter-group search-box">
                    <label class="filter-label">Search</label>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search messages..." 
                           value="<?php echo htmlspecialchars($search); ?>" onkeyup="handleSearch(event)">
                </div>
            </div>

            <!-- Messages List -->
            <div class="messages-list">
                <?php if ($messages_result->num_rows > 0): ?>
                    <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <div class="message-item <?php echo !$message['is_read'] ? 'unread' : ''; ?>" 
                             onclick="openMessageModal(<?php echo $message['message_id']; ?>)">
                            <div class="user-avatar">
                                <img src="<?php echo !empty($message['user_image']) ? 'uploads/profiles/' . $message['user_image'] : 'assets/images/default-avatar.png'; ?>" 
                                     alt="User Avatar">
                            </div>
                            
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-sender"><?php echo htmlspecialchars($message['user_name']); ?></span>
                                    <span class="message-time"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                                </div>
                                
                                <div class="message-subject"><?php echo htmlspecialchars($message['subject']); ?></div>
                                <div class="message-preview"><?php echo htmlspecialchars(substr($message['message'], 0, 150)) . '...'; ?></div>
                                
                                <div class="message-meta">
                                    <span class="message-badge badge-category"><?php echo ucfirst(str_replace('_', ' ', $message['category'])); ?></span>
                                    
                                    <?php if ($message['priority'] === 'urgent'): ?>
                                        <span class="message-badge badge-urgent">Urgent</span>
                                    <?php elseif ($message['priority'] === 'high'): ?>
                                        <span class="message-badge badge-priority">High Priority</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($message['is_resolved']): ?>
                                        <span class="message-badge badge-resolved">Resolved</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($message['reply_count'] > 0): ?>
                                        <span class="message-badge badge-category"><?php echo $message['reply_count']; ?> Replies</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="message-actions" onclick="event.stopPropagation()">
                                <button class="action-btn btn-reply" onclick="openMessageModal(<?php echo $message['message_id']; ?>)">
                                    <i class="fas fa-reply"></i> Reply
                                </button>
                                <?php if (!$message['is_resolved']): ?>
                                    <button class="action-btn btn-resolve" onclick="markAsResolved(<?php echo $message['message_id']; ?>)">
                                        <i class="fas fa-check"></i> Resolve
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: var(--grey);">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h3>No messages found</h3>
                        <p>There are no messages matching your current filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Message Conversation</h2>
                <button class="modal-close" onclick="closeMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="conversationMessages" class="conversation-messages">
                    <!-- Messages will be loaded here -->
                </div>
                
                <div class="reply-form">
                    <form id="replyForm" onsubmit="sendReply(event)">
                        <textarea id="replyTextarea" class="reply-textarea" placeholder="Type your reply..." required></textarea>
                        
                        <div class="reply-actions">
                            <div>
                                <label for="quickResponse" style="font-size: 12px; color: var(--grey);">Quick Response:</label>
                                <select id="quickResponse" class="quick-response-select" onchange="insertQuickResponse()">
                                    <option value="">Select template...</option>
                                    <?php while ($qr = $quick_responses->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($qr['content']); ?>" 
                                                data-category="<?php echo $qr['category']; ?>">
                                            <?php echo htmlspecialchars($qr['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-send">
                                <i class="fas fa-paper-plane"></i> Send Reply
                            </button>
                        </div>
                        
                        <input type="hidden" id="messageId" name="message_id">
                        <input type="hidden" id="userId" name="user_id">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Global variables
    let currentMessageId = null;

    // Apply filters
    function applyFilters() {
        const status = document.getElementById('statusFilter').value;
        const category = document.getElementById('categoryFilter').value;
        const search = document.getElementById('searchInput').value;
        
        const params = new URLSearchParams();
        if (status !== 'all') params.append('status', status);
        if (category !== 'all') params.append('category', category);
        if (search) params.append('search', search);
        
        window.location.href = 'messages_admin.php?' + params.toString();
    }

    // Handle search with debounce
    let searchTimeout;
    function handleSearch(event) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (event.key === 'Enter') {
                applyFilters();
            }
        }, 500);
    }

    // Open message modal
    function openMessageModal(messageId) {
        currentMessageId = messageId;
        document.getElementById('messageModal').style.display = 'block';
        loadConversation(messageId);
    }

    // Close message modal
    function closeMessageModal() {
        document.getElementById('messageModal').style.display = 'none';
        currentMessageId = null;
    }

    // Load conversation
    function loadConversation(messageId) {
        const formData = new FormData();
        formData.append('action', 'get_conversation');
        formData.append('message_id', messageId);

        fetch('messages_admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversation(data.messages);
            } else {
                alert('Failed to load conversation');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the conversation');
        });
    }

    // Display conversation
    function displayConversation(messages) {
        const container = document.getElementById('conversationMessages');
        container.innerHTML = '';

        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `conversation-message ${message.sender_type}`;
            
            const senderName = message.sender_type === 'user' ? message.user_name : message.admin_name;
            const messageDate = new Date(message.created_at).toLocaleString();
            
            messageDiv.innerHTML = `
                <div class="conversation-sender">
                    <strong>${senderName}</strong>
                    <span class="conversation-time">${messageDate}</span>
                </div>
                <div class="conversation-content">
                    ${message.subject ? `<div style="font-weight: 600; margin-bottom: 10px;">${message.subject}</div>` : ''}
                    <div>${message.message.replace(/\n/g, '<br>')}</div>
                </div>
            `;
            
            container.appendChild(messageDiv);
            
            // Set form data for the first (main) message
            if (messages.indexOf(message) === 0) {
                document.getElementById('messageId').value = message.message_id;
                document.getElementById('userId').value = message.user_id;
            }
        });

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    // Insert quick response
    function insertQuickResponse() {
        const select = document.getElementById('quickResponse');
        const textarea = document.getElementById('replyTextarea');
        
        if (select.value) {
            textarea.value = select.value;
            select.value = '';
        }
    }

    // Send reply
    function sendReply(event) {
        event.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'send_reply');
        formData.append('message_id', document.getElementById('messageId').value);
        formData.append('user_id', document.getElementById('userId').value);
        formData.append('reply_content', document.getElementById('replyTextarea').value);

        fetch('messages_admin.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('replyTextarea').value = '';
                loadConversation(currentMessageId);
                // Reload page to update message list
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(data.message || 'Failed to send reply');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending the reply');
        });
    }

    // Mark as resolved
    function markAsResolved(messageId) {
        if (confirm('Mark this message as resolved?')) {
            const formData = new FormData();
            formData.append('action', 'mark_resolved');
            formData.append('message_id', messageId);

            fetch('messages_admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to mark as resolved');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the message');
            });
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('messageModal');
        if (event.target === modal) {
            closeMessageModal();
        }
    }

    // Sidebar toggle for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebar = document.querySelector('.sidebar');
        
        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        if (sidebarClose) {
            sidebarClose.addEventListener('click', function() {
                sidebar.classList.remove('active');
            });
        }
    });
    </script>
</body>
</html>