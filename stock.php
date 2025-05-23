<?php
// Start the session at the very beginning
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';



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

// Update last login time
$update_stmt = $conn->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE admin_id = ?");
$update_stmt->bind_param("i", $admin_id);
$update_stmt->execute();
$update_stmt->close();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: sign-in.php");
    exit;
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
$stmt->close();

// Load XML file
$xmlFile = 'product.xml';
if (!file_exists($xmlFile)) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><store><metadata><name>HirayaFit Store</name><version>1.0</version><currency>PHP</currency><last_updated>' . date('Y-m-d') . '</last_updated></metadata><products></products></store>');
    $xml->asXML($xmlFile);
}

$xml = simplexml_load_file($xmlFile);

// Handle stock update
if (isset($_POST['update_stock'])) {
    $productId = $_POST['product_id'];
    $newStock = $_POST['new_stock'];
    $reason = $_POST['reason'];
    
    foreach ($xml->products->product as $product) {
        if ((string)$product->id === $productId) {
            $oldStock = (int)$product->stock;
            $product->stock = $newStock;
            
            // Add to stock history
            if (!isset($xml->stock_history)) {
                $xml->addChild('stock_history');
            }
            
            $history = $xml->stock_history->addChild('entry');
            $history->addChild('product_id', $productId);
            $history->addChild('product_name', (string)$product->name);
            $history->addChild('old_stock', $oldStock);
            $history->addChild('new_stock', $newStock);
            $history->addChild('change', $newStock - $oldStock);
            $history->addChild('reason', $reason);
            $history->addChild('admin_id', $admin_id);
            $history->addChild('admin_name', $admin['fullname']);
            $history->addChild('date', date('Y-m-d H:i:s'));
            
            break;
        }
    }
    
    $xml->asXML($xmlFile);
    header("Location: stock.php?success=1");
    exit;
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Product ID',
        'Product Name',
        'Category',
        'Current Stock',
        'Stock Status',
        'Price (PHP)',
        'Total Value (PHP)',
        'Featured',
        'On Sale',
        'Last Updated'
    ]);
    
    // CSV Data
    foreach ($xml->products->product as $product) {
        $stock = (int)$product->stock;
        $price = (float)$product->price;
        $totalValue = $stock * $price;
        
        $stockStatus = 'In Stock';
        if ($stock == 0) {
            $stockStatus = 'Out of Stock';
        } elseif ($stock <= 5) {
            $stockStatus = 'Low Stock';
        }
        
        fputcsv($output, [
            (string)$product->id,
            (string)$product->name,
            (string)$product->category,
            $stock,
            $stockStatus,
            number_format($price, 2),
            number_format($totalValue, 2),
            (string)$product->featured === 'true' ? 'Yes' : 'No',
            (string)$product->on_sale === 'true' ? 'Yes' : 'No',
            date('Y-m-d H:i:s')
        ]);
    }
    
    fclose($output);
    exit;
}

// Handle Stock History Export
if (isset($_GET['export']) && $_GET['export'] === 'history') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock_history_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Date',
        'Product ID',
        'Product Name',
        'Old Stock',
        'New Stock',
        'Change',
        'Reason',
        'Admin'
    ]);
    
    // CSV Data
    if (isset($xml->stock_history)) {
        foreach ($xml->stock_history->entry as $entry) {
            fputcsv($output, [
                (string)$entry->date,
                (string)$entry->product_id,
                (string)$entry->product_name,
                (string)$entry->old_stock,
                (string)$entry->new_stock,
                (string)$entry->change,
                (string)$entry->reason,
                (string)$entry->admin_name
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Get filter and search parameters
$search = $_GET['search'] ?? '';
$stockFilter = $_GET['stock_filter'] ?? '';
$categoryFilter = $_GET['category_filter'] ?? '';

// Function to check if product matches filters
function matchesStockFilter($product, $search, $stockFilter, $categoryFilter) {
    $search = strtolower($search);
    $stock = (int)$product->stock;
    
    // Search filter
    $matchSearch = $search === '' ||
                   strpos(strtolower($product->name), $search) !== false ||
                   strpos(strtolower($product->category), $search) !== false;
    
    // Stock filter
    $matchStock = true;
    if ($stockFilter === 'out_of_stock') {
        $matchStock = $stock == 0;
    } elseif ($stockFilter === 'low_stock') {
        $matchStock = $stock > 0 && $stock <= 5;
    } elseif ($stockFilter === 'in_stock') {
        $matchStock = $stock > 5;
    }
    
    // Category filter
    $matchCategory = $categoryFilter === '' || (string)$product->category === $categoryFilter;
    
    return $matchSearch && $matchStock && $matchCategory;
}

// Get all categories
$categories = [];
foreach ($xml->products->product as $product) {
    $category = (string)$product->category;
    if (!in_array($category, $categories)) {
        $categories[] = $category;
    }
}
sort($categories);

// Calculate statistics
$totalProducts = 0;
$outOfStock = 0;
$lowStock = 0;
$totalValue = 0;

foreach ($xml->products->product as $product) {
    $totalProducts++;
    $stock = (int)$product->stock;
    $price = (float)$product->price;
    $totalValue += $stock * $price;
    
    if ($stock == 0) {
        $outOfStock++;
    } elseif ($stock <= 5) {
        $lowStock++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Stock Management</title>
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
    --warning: #ffc107;
    --success: #28a745;
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

/* Dashboard Container */
.dashboard-container {
    padding: 30px;
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
}

/* Dashboard Container */
.dashboard-container {
    padding: 30px;
}

/* Success Message */
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    border: 1px solid #c3e6cb;
    display: flex;
    align-items: center;
}

.success-message i {
    margin-right: 10px;
    font-size: 18px;
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    font-size: 24px;
    color: white;
}

.stat-icon.total {
    background: linear-gradient(135deg, var(--secondary), #0056a3);
}

.stat-icon.out {
    background: linear-gradient(135deg, var(--danger), #c82333);
}

.stat-icon.low {
    background: linear-gradient(135deg, var(--warning), #e0a800);
}

.stat-icon.value {
    background: linear-gradient(135deg, var(--success), #218838);
}

.stat-info h3 {
    font-size: 28px;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 5px;
}

.stat-info p {
    color: var(--grey);
    font-size: 14px;
    font-weight: 500;
}

/* Controls Section */
.controls {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.controls-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.controls-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.export-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn i {
    margin-right: 6px;
}

.btn-primary {
    background-color: var(--secondary);
    color: white;
}

.btn-primary:hover {
    background-color: #0056a3;
    transform: translateY(-1px);
}

.btn-success {
    background-color: var(--success);
    color: white;
}

.btn-success:hover {
    background-color: #218838;
    transform: translateY(-1px);
}

.btn-warning {
    background-color: var(--warning);
    color: var(--dark);
}

.btn-warning:hover {
    background-color: #e0a800;
    transform: translateY(-1px);
}

.btn-info {
    background-color: var(--info);
    color: white;
}

.btn-info:hover {
    background-color: #138496;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

.btn-cancel {
    background-color: #f8f9fa;
    color: var(--dark);
    border: 1px solid #dee2e6;
}

.btn-cancel:hover {
    background-color: #e9ecef;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Filters */
.filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 8px;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
}

/* Stock Table */
.stock-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.table-header {
    padding: 25px;
    border-bottom: 1px solid #dee2e6;
}

.table-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th,
table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f1f3f4;
}

table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: var(--dark);
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

table td {
    color: #495057;
    font-size: 14px;
}

table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Product Info in Table */
.product-info {
    display: flex;
    align-items: center;
}

.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
    border: 1px solid #dee2e6;
}

.product-details h4 {
    font-size: 14px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
}

.product-details p {
    font-size: 12px;
    color: var(--grey);
}

/* Stock Status */
.stock-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stock-status.in-stock {
    background-color: #d4edda;
    color: #155724;
}

.stock-status.low-stock {
    background-color: #fff3cd;
    color: #856404;
}

.stock-status.out-of-stock {
    background-color: #f8d7da;
    color: #721c24;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* Modal Styles */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal-backdrop.active {
    opacity: 1;
    visibility: visible;
}

.modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.7);
    transition: transform 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-backdrop.active .modal {
    transform: scale(1);
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--grey);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background-color: #f8f9fa;
    color: var(--dark);
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

/* Product Detail Grid */
.product-detail-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.product-image-large {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.product-info-detailed {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-label {
    font-weight: 500;
    color: var(--grey);
    min-width: 120px;
}

.info-value {
    font-weight: 500;
    color: var(--dark);
    text-align: right;
}

/* Stock History */
.stock-history {
    margin-top: 25px;
}

.stock-history h4 {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--secondary);
}

.history-item {
    display: flex;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 12px;
    background-color: #f8f9fa;
}

.history-date {
    min-width: 120px;
    font-weight: 500;
    color: var(--secondary);
    font-size: 14px;
}

.history-details {
    flex: 1;
    font-size: 14px;
    line-height: 1.5;
}

.change-positive {
    color: var(--success);
    font-weight: 600;
}

.change-negative {
    color: var(--danger);
    font-weight: 600;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 500;
    color: var(--dark);
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(0, 113, 197, 0.1);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .product-detail-grid {
        grid-template-columns: 150px 1fr;
    }
    
    .product-image-large {
        height: 150px;
    }
}

@media (max-width: 992px) {
    .welcome-text {
        display: none;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .filters {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
    
    .dashboard-container {
        padding: 20px;
    }
    
    .controls-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .product-detail-grid {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .info-value {
        text-align: left;
    }
    
    .history-item {
        flex-direction: column;
        gap: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal {
        width: 95%;
        margin: 20px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filters {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .stat-info h3 {
        font-size: 24px;
    }
    
    .export-buttons {
        width: 100%;
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
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
            <a href="stock.php" class="active"><i class="fas fa-box"></i> Stock Management</a>
            
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
                <span class="navbar-title">Stock Management</span>
                <div class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($admin['fullname']); ?></strong>!</div>
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
                       
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    Stock updated successfully!
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalProducts; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon out">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $outOfStock; ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon low">
                        <i class="fas fa-battery-quarter"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $lowStock; ?></h3>
                        <p>Low Stock (≤5)</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon value">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($totalValue, 2); ?></h3>
                        <p>Total Stock Value</p>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="controls">
                <div class="controls-header">
                    <h3 class="controls-title">Stock Management Controls</h3>
                    <div class="export-buttons">
                        <a href="?export=csv" class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Export Stock Report
                        </a>
                        <a href="?export=history" class="btn btn-primary">
                            <i class="fas fa-history"></i> Export Stock History
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="filters">
                    <div class="filter-group">
                        <label>Search Products</label>
                        <input type="text" name="search" placeholder="Search by name or category..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Stock Status</label>
                        <select name="stock_filter">
                            <option value="">All Products</option>
                            <option value="in_stock" <?php echo $stockFilter === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                            <option value="low_stock" <?php echo $stockFilter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="out_of_stock" <?php echo $stockFilter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category_filter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $categoryFilter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <a href="stock.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Stock Table -->
            <div class="stock-table">
                <div class="table-header">
                    <h3 class="table-title">Product Stock Overview</h3>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Total Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasProducts = false;
                            if (isset($xml->products->product)) {
                                foreach ($xml->products->product as $product) {
                                    if (!matchesStockFilter($product, $search, $stockFilter, $categoryFilter)) continue;
                                    $hasProducts = true;
                                    
                                    $stock = (int)$product->stock;
                                    $price = (float)$product->price;
                                    $totalValue = $stock * $price;
                                    
                                    $statusClass = 'in-stock';
                                    $statusText = 'In Stock';
                                    if ($stock == 0) {
                                        $statusClass = 'out-of-stock';
                                        $statusText = 'Out of Stock';
                                    } elseif ($stock <= 5) {
                                        $statusClass = 'low-stock';
                                        $statusText = 'Low Stock';
                                    }
                            ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="<?php echo htmlspecialchars($product->image); ?>" alt="<?php echo htmlspecialchars($product->name); ?>" class="product-image">
                                        <div class="product-details">
                                            <h4><?php echo htmlspecialchars($product->name); ?></h4>
                                            <p>ID: <?php echo htmlspecialchars($product->id); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product->category); ?></td>
                                <td><strong><?php echo $stock; ?></strong></td>
                                <td>
                                    <span class="stock-status <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($price, 2); ?></td>
                                <td>₱<?php echo number_format($totalValue, 2); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm view-details-btn" data-product-id="<?php echo htmlspecialchars($product->id); ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button class="btn btn-warning btn-sm update-stock-btn" data-product-id="<?php echo htmlspecialchars($product->id); ?>">
                                            <i class="fas fa-edit"></i> Update Stock
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            }
                            
                            if (!$hasProducts): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                    No products found matching your criteria.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal-backdrop" id="detailsModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Product Details</h2>
                <button type="button" class="modal-close" id="closeDetailsModal">&times;</button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <!-- Details will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div class="modal-backdrop" id="updateStockModal">
        <div class="modal">
            <div class="modal-header">
                <h2 class="modal-title">Update Stock</h2>
                <button type="button" class="modal-close" id="closeUpdateModal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="updateStockForm">
                    <input type="hidden" name="product_id" id="updateProductId">
                    
                    <div id="updateProductInfo" class="product-info-detailed" style="margin-bottom: 25px;">
                        <!-- Product info will be populated by JavaScript -->
                    </div>
                    
                    <div class="form-group">
                        <label for="newStock">New Stock Quantity</label>
                        <input type="number" name="new_stock" id="newStock" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Stock Change</label>
                        <select name="reason" id="reason" required>
                            <option value="">Select reason...</option>
                            <option value="New Stock Received">New Stock Received</option>
                            <option value="Stock Sold">Stock Sold</option>
                            <option value="Stock Damaged">Stock Damaged</option>
                            <option value="Stock Returned">Stock Returned</option>
                            <option value="Inventory Correction">Inventory Correction</option>
                            <option value="Manual Adjustment">Manual Adjustment</option>
                        </select>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" id="cancelUpdateModal">Cancel</button>
                        <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Admin dropdown toggle
            const adminDropdown = document.getElementById('adminDropdown');
            
            if (adminDropdown) {
                adminDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    adminDropdown.classList.toggle('show');
                });
                
                document.addEventListener('click', function(e) {
                    if (adminDropdown && !adminDropdown.contains(e.target)) {
                        adminDropdown.classList.remove('show');
                    }
                });
            }
            
            // Sidebar toggle
            const toggleSidebar = document.getElementById('toggleSidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleSidebar && sidebar) {
                toggleSidebar.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
                
                if (sidebarClose) {
                    sidebarClose.addEventListener('click', function() {
                        sidebar.classList.remove('active');
                    });
                }
            }

            // Product data
            const productData = <?php
                $productsArray = [];
                if (isset($xml->products->product)) {
                    foreach ($xml->products->product as $product) {
                        $sizes = [];
                        if (isset($product->sizes->size)) {
                            foreach ($product->sizes->size as $size) {
                                $sizes[] = (string)$size;
                            }
                        }
                        
                        $colors = [];
                        if (isset($product->colors->color)) {
                            foreach ($product->colors->color as $color) {
                                $colors[] = (string)$color;
                            }
                        }
                        
                        $productsArray[(string)$product->id] = [
                            'id' => (string)$product->id,
                            'name' => (string)$product->name,
                            'category' => (string)$product->category,
                            'price' => (string)$product->price,
                            'stock' => (string)$product->stock,
                            'description' => (string)$product->description,
                            'sizes' => $sizes,
                            'colors' => $colors,
                            'rating' => (string)$product->rating,
                            'review_count' => (string)$product->review_count,
                            'featured' => (string)$product->featured,
                            'on_sale' => (string)$product->on_sale,
                            'image' => (string)$product->image
                        ];
                    }
                }
                echo json_encode($productsArray);
            ?>;

            // Stock history data
            const stockHistory = <?php
                $historyArray = [];
                if (isset($xml->stock_history)) {
                    foreach ($xml->stock_history->entry as $entry) {
                        $productId = (string)$entry->product_id;
                        if (!isset($historyArray[$productId])) {
                            $historyArray[$productId] = [];
                        }
                        $historyArray[$productId][] = [
                            'date' => (string)$entry->date,
                            'old_stock' => (string)$entry->old_stock,
                            'new_stock' => (string)$entry->new_stock,
                            'change' => (string)$entry->change,
                            'reason' => (string)$entry->reason,
                            'admin_name' => (string)$entry->admin_name
                        ];
                    }
                    // Sort by date descending for each product
                    foreach ($historyArray as $productId => $history) {
                        usort($historyArray[$productId], function($a, $b) {
                            return strtotime($b['date']) - strtotime($a['date']);
                        });
                    }
                }
                echo json_encode($historyArray);
            ?>;

            // Modal elements
            const detailsModal = document.getElementById('detailsModal');
            const updateStockModal = document.getElementById('updateStockModal');
            const detailsModalBody = document.getElementById('detailsModalBody');
            const updateProductInfo = document.getElementById('updateProductInfo');
            const updateProductId = document.getElementById('updateProductId');
            const newStockInput = document.getElementById('newStock');

            // View Details functionality
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const product = productData[productId];
                    
                    if (product) {
                        const stock = parseInt(product.stock);
                        const price = parseFloat(product.price);
                        const totalValue = stock * price;
                        
                        let statusClass = 'in-stock';
                        let statusText = 'In Stock';
                        if (stock === 0) {
                            statusClass = 'out-of-stock';
                            statusText = 'Out of Stock';
                        } else if (stock <= 5) {
                            statusClass = 'low-stock';
                            statusText = 'Low Stock';
                        }

                        // Build stock history HTML
                        let historyHtml = '<div class="stock-history"><h4>Stock History</h4>';
                        if (stockHistory[productId] && stockHistory[productId].length > 0) {
                            stockHistory[productId].forEach(entry => {
                                const changeClass = parseInt(entry.change) >= 0 ? 'change-positive' : 'change-negative';
                                const changeSymbol = parseInt(entry.change) >= 0 ? '+' : '';
                                historyHtml += `
                                    <div class="history-item">
                                        <div class="history-date">${entry.date}</div>
                                        <div class="history-details">
                                            <strong>${entry.reason}</strong><br>
                                            Stock changed from ${entry.old_stock} to ${entry.new_stock} 
                                            (<span class="${changeClass}">${changeSymbol}${entry.change}</span>)<br>
                                            <small>By: ${entry.admin_name}</small>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            historyHtml += '<p style="text-align: center; color: #7f8c8d; padding: 20px;">No stock history available.</p>';
                        }
                        historyHtml += '</div>';

                        detailsModalBody.innerHTML = `
                            <div class="product-detail-grid">
                                <div>
                                    <img src="${product.image}" alt="${product.name}" class="product-image-large">
                                </div>
                                <div class="product-info-detailed">
                                    <div class="info-row">
                                        <span class="info-label">Product ID:</span>
                                        <span class="info-value">${product.id}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Name:</span>
                                        <span class="info-value">${product.name}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Category:</span>
                                        <span class="info-value">${product.category}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Current Stock:</span>
                                        <span class="info-value"><strong>${stock}</strong></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Stock Status:</span>
                                        <span class="stock-status ${statusClass}">${statusText}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Price:</span>
                                        <span class="info-value">₱${price.toFixed(2)}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Total Value:</span>
                                        <span class="info-value">₱${totalValue.toFixed(2)}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Sizes:</span>
                                        <span class="info-value">${product.sizes.join(', ') || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Colors:</span>
                                        <span class="info-value">${product.colors.join(', ') || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Featured:</span>
                                        <span class="info-value">${product.featured === 'true' ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">On Sale:</span>
                                        <span class="info-value">${product.on_sale === 'true' ? 'Yes' : 'No'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Rating:</span>
                                        <span class="info-value">${product.rating}/5 (${product.review_count} reviews)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Description:</span>
                            </div>
                            <p style="margin-bottom: 25px; color: #495057; line-height: 1.6;">${product.description || 'No description available.'}</p>
                            ${historyHtml}
                        `;
                        
                        detailsModal.classList.add('active');
                    }
                });
            });

            // Update Stock functionality
            document.querySelectorAll('.update-stock-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    const product = productData[productId];
                    
                    if (product) {
                        updateProductId.value = productId;
                        newStockInput.value = product.stock;
                        
                        updateProductInfo.innerHTML = `
                            <div class="product-info">
                                <img src="${product.image}" alt="${product.name}" class="product-image">
                                <div class="product-details">
                                    <h4>${product.name}</h4>
                                    <p>ID: ${product.id} | Category: ${product.category}</p>
                                    <p>Current Stock: <strong>${product.stock}</strong></p>
                                </div>
                            </div>
                        `;
                        
                        updateStockModal.classList.add('active');
                    }
                });
            });

            // Close modal functionality
            function closeModal(modal) {
                modal.classList.remove('active');
            }

            document.getElementById('closeDetailsModal').addEventListener('click', () => closeModal(detailsModal));
            document.getElementById('closeUpdateModal').addEventListener('click', () => closeModal(updateStockModal));
            document.getElementById('cancelUpdateModal').addEventListener('click', () => closeModal(updateStockModal));

            // Close modals when clicking outside
            detailsModal.addEventListener('click', function(e) {
                if (e.target === detailsModal) {
                    closeModal(detailsModal);
                }
            });

            updateStockModal.addEventListener('click', function(e) {
                if (e.target === updateStockModal) {
                    closeModal(updateStockModal);
                }
            });
        });
    </script>
</body>
</html>