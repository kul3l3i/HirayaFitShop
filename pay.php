<?php
// Start the session at the very beginning
session_start();

// Database connection configuration
$db_host = 'localhost';
$db_user = 'u801377270_hiraya_2025'; // Change to your DB username
$db_pass = 'Hiraya_2025';     // Change to your DB password
$db_name = 'u801377270_hiraya_2025'; // Change to your DB name

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
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

// Load and parse XML transactions
function loadTransactions() {
    $transactions = [];
    if (file_exists('transaction.xml')) {
        $xmlContent = file_get_contents('transaction.xml');
        $xml = simplexml_load_string($xmlContent);
        
        if ($xml && $xml->transaction) {
            foreach ($xml->transaction as $transaction) {
                $transactionData = [
                    'transaction_id' => (string)$transaction->transaction_id,
                    'user_id' => (string)$transaction->user_id,
                    'transaction_date' => (string)$transaction->transaction_date,
                    'status' => (string)$transaction->status,
                    'payment_method' => (string)$transaction->payment_method,
                    'subtotal' => (float)$transaction->subtotal,
                    'shipping_fee' => (float)$transaction->shipping_fee,
                    'total_amount' => (float)$transaction->total_amount,
                    'items' => [],
                    'shipping_info' => null
                ];
                
                // Parse items
                if (isset($transaction->items->item)) {
                    foreach ($transaction->items->item as $item) {
                        $transactionData['items'][] = [
                            'product_id' => (string)$item->product_id,
                            'product_name' => (string)$item->product_name,
                            'price' => (float)$item->price,
                            'quantity' => (int)$item->quantity,
                            'color' => (string)$item->color,
                            'size' => (string)$item->size,
                            'subtotal' => (float)$item->subtotal
                        ];
                    }
                }
                
                // Parse shipping info
                if (isset($transaction->shipping_info)) {
                    $transactionData['shipping_info'] = [
                        'fullname' => (string)$transaction->shipping_info->fullname,
                        'email' => (string)$transaction->shipping_info->email,
                        'phone' => (string)$transaction->shipping_info->phone,
                        'address' => (string)$transaction->shipping_info->address,
                        'city' => (string)$transaction->shipping_info->city,
                        'postal_code' => (string)$transaction->shipping_info->postal_code,
                        'notes' => (string)$transaction->shipping_info->notes
                    ];
                }
                
                $transactions[] = $transactionData;
            }
        }
    }
    return $transactions;
}

// Filter ONLY delivered orders (completed transactions)
$allTransactions = loadTransactions();
$completedTransactions = array_filter($allTransactions, function($transaction) {
    return $transaction['status'] === 'delivered';
});

// Sort by date (newest first)
usort($completedTransactions, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payment_history_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'Transaction ID',
        'Date',
        'Customer Name',
        'Customer Email',
        'Payment Method',
        'Items',
        'Subtotal',
        'Shipping Fee',
        'Total Amount',
        'Status'
    ]);
    
    // CSV data
    foreach ($completedTransactions as $transaction) {
        $itemsStr = '';
        foreach ($transaction['items'] as $item) {
            $itemsStr .= $item['product_name'] . ' (Qty: ' . $item['quantity'] . ', Color: ' . $item['color'] . ', Size: ' . $item['size'] . '); ';
        }
        
        fputcsv($output, [
            $transaction['transaction_id'],
            date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])),
            $transaction['shipping_info']['fullname'] ?? 'N/A',
            $transaction['shipping_info']['email'] ?? 'N/A',
            ucfirst($transaction['payment_method']),
            rtrim($itemsStr, '; '),
            '₱' . number_format($transaction['subtotal'], 2),
            '₱' . number_format($transaction['shipping_fee'], 2),
            '₱' . number_format($transaction['total_amount'], 2),
            ucfirst($transaction['status'])
        ]);
    }
    
    fclose($output);
    exit;
}

// Calculate summary statistics
$totalRevenue = array_sum(array_column($completedTransactions, 'total_amount'));
$totalOrders = count($completedTransactions);
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

// Payment method breakdown
$paymentMethods = [];
foreach ($completedTransactions as $transaction) {
    $method = ucfirst($transaction['payment_method']);
    if (!isset($paymentMethods[$method])) {
        $paymentMethods[$method] = ['count' => 0, 'amount' => 0];
    }
    $paymentMethods[$method]['count']++;
    $paymentMethods[$method]['amount'] += $transaction['total_amount'];
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - HirayaFit Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/payment.css">
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
            <a href="payment-history.php" class="active"><i class="fas fa-money-check-alt"></i> Payment History</a>
            
            <div class="menu-title">INVENTORY</div>
            <a href="products.php"><i class="fas fa-tshirt"></i> Products & Categories</a>
            <a href="stock.php"><i class="fas fa-box"></i> Stock Management</a>
            
            <div class="menu-title">USERS</div>
            <a href="users.php"><i class="fas fa-users"></i> User Management</a>
            
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
                <span class="navbar-title">Payment History</span>
            </div>
            
            <div class="navbar-actions">
                <a href="notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </a>
                <a href="messages.php" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-count">5</span>
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
                        <a href="change-password.php"><i class="fas fa-lock"></i> Change Password</a>
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Payment History</h1>
                <div class="header-actions">
                    <a href="?export=csv" class="btn btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Revenue</h3>
                    <div class="value">
                        <span class="currency">₱</span><?php echo number_format($totalRevenue, 2); ?>
                    </div>
                </div>
                <div class="summary-card">
                    <h3>Delivered Orders</h3>
                    <div class="value"><?php echo number_format($totalOrders); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Average Order Value</h3>
                    <div class="value">
                        <span class="currency">₱</span><?php echo number_format($avgOrderValue, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Payment Methods Summary -->
            <?php if (!empty($paymentMethods)): ?>
            <div class="payment-methods">
                <h3>Payment Methods Breakdown</h3>
                <div class="payment-method-list">
                    <?php foreach ($paymentMethods as $method => $data): ?>
                    <div class="payment-method-item">
                        <div class="payment-method-name"><?php echo htmlspecialchars($method); ?></div>
                        <div class="payment-method-stats">
                            <div class="payment-method-amount">₱<?php echo number_format($data['amount'], 2); ?></div>
                            <div class="payment-method-count"><?php echo $data['count']; ?> orders</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Transactions Table -->
            <div class="transactions-section">
                <div class="section-header">
                    <h3 class="section-title">Delivered Transactions</h3>
                    <span class="text-muted"><?php echo count($completedTransactions); ?> delivered transactions</span>
                </div>
                
                <div class="table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Payment Method</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedTransactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['transaction_id']); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?><br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($transaction['transaction_date'])); ?></small>
                                </td>
                                <td>
                                    <?php if ($transaction['shipping_info']): ?>
                                        <strong><?php echo htmlspecialchars($transaction['shipping_info']['fullname']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($transaction['shipping_info']['email']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="payment-method-badge">
                                        <?php echo ucfirst(htmlspecialchars($transaction['payment_method'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo count($transaction['items']); ?> item(s)<br>
                                    <small class="text-muted">
                                        <?php 
                                        $itemNames = array_slice(array_column($transaction['items'], 'product_name'), 0, 2);
                                        echo htmlspecialchars(implode(', ', $itemNames));
                                        if (count($transaction['items']) > 2) {
                                            echo '...';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <strong>₱<?php echo number_format($transaction['total_amount'], 2); ?></strong><br>
                                    <small class="text-muted">
                                        Subtotal: ₱<?php echo number_format($transaction['subtotal'], 2); ?><br>
                                        Shipping: ₱<?php echo number_format($transaction['shipping_fee'], 2); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($transaction['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($transaction['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="view-details-btn" onclick="viewTransaction('<?php echo htmlspecialchars($transaction['transaction_id']); ?>')">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($completedTransactions)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                    <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i><br>
                                    No delivered transactions found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Transaction Details</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Transaction details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Admin dropdown toggle
        const adminDropdown = document.getElementById('adminDropdown');
        const adminDropdownContent = document.getElementById('adminDropdownContent');
        
        adminDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            adminDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!adminDropdown.contains(e.target)) {
                adminDropdown.classList.remove('show');
            }
        });
        
        // Sidebar toggle for responsive design
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebarClose = document.getElementById('sidebarClose');
        const sidebar = document.querySelector('.sidebar');
        
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });

        // Modal functionality
        const modal = document.getElementById('transactionModal');
        const span = document.getElementsByClassName('close')[0];

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    });

    // Transaction data for JavaScript access
    const transactions = <?php echo json_encode($completedTransactions); ?>;

    function viewTransaction(transactionId) {
        const transaction = transactions.find(t => t.transaction_id === transactionId);
        if (!transaction) return;

        const modalBody = document.getElementById('modalBody');
        
        let itemsHtml = '';
        transaction.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${item.product_name}</td>
                    <td>${item.color}</td>
                    <td>${item.size}</td>
                    <td>${item.quantity}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    <td>₱${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `;
        });

        const shippingInfo = transaction.shipping_info || {};
        
        modalBody.innerHTML = `
            <div class="detail-section">
                <h4><i class="fas fa-receipt"></i> Transaction Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Transaction ID</span>
                        <span class="detail-value">${transaction.transaction_id}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Date & Time</span>
                        <span class="detail-value">${new Date(transaction.transaction_date).toLocaleString()}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="status-badge status-${transaction.status}">
                                ${transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)}
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value">
                            <span class="payment-method-badge">
                                ${transaction.payment_method.charAt(0).toUpperCase() + transaction.payment_method.slice(1)}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            ${shippingInfo.fullname ? `
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> Customer Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value">${shippingInfo.fullname}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">${shippingInfo.email}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value">${shippingInfo.phone}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Address</span>
                        <span class="detail-value">${shippingInfo.address}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">City</span>
                        <span class="detail-value">${shippingInfo.city}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Postal Code</span>
                        <span class="detail-value">${shippingInfo.postal_code}</span>
                    </div>
                    ${shippingInfo.notes ? `
                    <div class="detail-item" style="grid-column: 1 / -1;">
                        <span class="detail-label">Notes</span>
                        <span class="detail-value">${shippingInfo.notes}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            <div class="detail-section">
                <h4><i class="fas fa-shopping-bag"></i> Order Items</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Color</th>
                            <th>Size</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>
            </div>

            <div class="detail-section">
                <h4><i class="fas fa-calculator"></i> Payment Summary</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Subtotal</span>
                        <span class="detail-value">₱${parseFloat(transaction.subtotal).toFixed(2)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Shipping Fee</span>
                        <span class="detail-value">₱${parseFloat(transaction.shipping_fee).toFixed(2)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value" style="font-size: 18px; font-weight: 700; color: var(--secondary);">
                            ₱${parseFloat(transaction.total_amount).toFixed(2)}
                        </span>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('transactionModal').style.display = 'block';
    }
    </script>

    <style>
    .text-muted {
        color: #6c757d;
    }
    </style>
</body>
</html>