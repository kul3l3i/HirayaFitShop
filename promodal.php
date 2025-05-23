<?php
// Start the session at the very beginning
session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';

session_start();
include 'db_connect.php';
// Initialize variables
$error = '';
$username_email = '';

// Check if the user is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
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
    // Admin not found or not active, destroy session and redirect to login
    session_destroy();
    header("Location: sign-in.php");
    exit;
}

// Get admin details
$admin = $result->fetch_assoc();

// Set default role if not present
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
        return "assets/images/default-avatar.png"; // Default image path
    }
}

// Get profile image URL
$profileImageUrl = getProfileImageUrl($admin['profile_image']);

// Close the statement
$stmt->close();
?>

<?php
$xmlFile = 'product.xml';

// Create file if it doesn't exist
if (!file_exists($xmlFile)) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><store><metadata><name>HirayaFit Store</name><version>1.0</version><currency>PHP</currency><last_updated>' . date('Y-m-d') . '</last_updated></metadata><products></products></store>');
    $xml->asXML($xmlFile);
}

$xml = simplexml_load_file($xmlFile);

// Create uploads directory if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0755);
}

// Make sure <categories> section exists
if (!isset($xml->categories)) {
    $xml->addChild('categories');
}

// Get existing categories
$existingCategories = [];
foreach ($xml->categories->category as $cat) {
    $existingCategories[] = (string)$cat;
}

$message = "";

// Handle category add
if (isset($_POST['add_category'])) {
    $newCategory = trim($_POST['new_category']);

    if (!empty($newCategory) && !in_array($newCategory, $existingCategories)) {
        $xml->categories->addChild('category', $newCategory);
        $xml->asXML($xmlFile);
        $message = "<p style='color: green; margin-top: 10px;'>✔️ Category <strong>" . htmlspecialchars($newCategory) . "</strong> added.</p>";
    } else {
        $message = "<p style='color: red; margin-top: 10px;'>⚠️ Category already exists or is empty.</p>";
    }
}
// Handle Category Delete
if (isset($_GET['delete_category'])) {
    $categoryToDelete = $_GET['delete_category'];
    $categoryInUse = false;
    
    // Check if any product uses this category
    foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->product to $xml->products->product
        if ((string)$product->category === $categoryToDelete) {
            $categoryInUse = true;
            break;
        }
    }
    
    if (!$categoryInUse) {
        $key = array_search($categoryToDelete, $existingCategories);
        if ($key !== false) {
            unset($existingCategories[$key]);
            $existingCategories = array_values($existingCategories); // Re-index array
        }
    } else {
        $errorMessage = "Cannot delete category '$categoryToDelete' because it's in use by products.";
    }
}

// ADD PRODUCT
if (isset($_POST['add_product'])) {
    $imgPath = '';
    if (!empty($_FILES['image_file']['name'])) {
        $imgName = basename($_FILES['image_file']['name']);
        $imgTmp = $_FILES['image_file']['tmp_name'];
        $imgPath = 'uploads/' . $imgName;
        move_uploaded_file($imgTmp, $imgPath);
    }

    // Find the highest ID value to increment it
    $highestId = 2000; // Start at 2000 since your examples are 2001+
    foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->product to $xml->products->product
        $id = (int)$product->id;
        if ($id > $highestId) {
            $highestId = $id;
        }
    }
    $newId = $highestId + 1;

    // Create new product in the products node
    $new = $xml->products->addChild('product'); // Fixed: Add product to products node
    $new->addChild('id', $newId);
    $new->addChild('name', $_POST['name']);
    $new->addChild('category', $_POST['category']);
    $new->addChild('price', $_POST['price']);
    $new->addChild('currency', 'PHP');
    $new->addChild('description', $_POST['description']);
    $new->addChild('image', $imgPath);
    $new->addChild('stock', $_POST['stock']);
    
    $sizes = $new->addChild('sizes');
    if (!empty($_POST['sizes'])) {
        $sizeArray = explode(',', $_POST['sizes']);
        foreach ($sizeArray as $sizeValue) {
            $sizeValue = trim($sizeValue);
            if (!empty($sizeValue)) {
                $sizes->addChild('size', $sizeValue);
            }
        }
    }
    
    $colors = $new->addChild('colors');
    if (!empty($_POST['colors'])) {
        $colorArray = explode(',', $_POST['colors']);
        foreach ($colorArray as $colorValue) {
            $colorValue = trim($colorValue);
            if (!empty($colorValue)) {
                $colors->addChild('color', $colorValue);
            }
        }
    }
    
    $new->addChild('rating', $_POST['rating']);
    $new->addChild('review_count', $_POST['review_count']);
    $new->addChild('featured', $_POST['featured']);
    $new->addChild('on_sale', $_POST['on_sale']);
    
    $xml->asXML($xmlFile);
    header("Location: products.php");
    exit;
}

// DELETE PRODUCT
if (isset($_GET['delete_product'])) {
    $deleteId = $_GET['delete_product'];
    $index = 0;
    foreach ($xml->products->product as $product) {
        if ((string)$product->id === $deleteId) {
            unset($xml->products->product[$index]);
            break;
        }
        $index++;
    }
    $xml->asXML($xmlFile);
    header("Location: products.php");
    exit;
}

// EDIT MODE
$editProduct = null;
if (isset($_GET['edit_product'])) {
    foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->products->product to $xml->products->product
        if ((string)$product->id === $_GET['edit_product']) {
            $editProduct = $product;
            break;
        }
    }
}

// UPDATE PRODUCT
if (isset($_POST['update_product'])) {
    foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->products->product to $xml->products->product
        if ((string)$product->id === $_POST['id']) {
            $product->name = $_POST['name'];
            $product->category = $_POST['category'];
            $product->price = $_POST['price'];
            $product->description = $_POST['description'];
            $product->stock = $_POST['stock'];
            $product->rating = $_POST['rating'];
            $product->review_count = $_POST['review_count'];
            $product->featured = $_POST['featured'];
            $product->on_sale = $_POST['on_sale'];

            if (!empty($_FILES['image_file']['name'])) {
                $imgName = basename($_FILES['image_file']['name']);
                $imgTmp = $_FILES['image_file']['tmp_name'];
                $imgPath = 'uploads/' . $imgName;
                move_uploaded_file($imgTmp, $imgPath);
                $product->image = $imgPath;
            }
            
            // Update sizes
            unset($product->sizes);
            $sizes = $product->addChild('sizes');
            if (!empty($_POST['sizes'])) {
                $sizeArray = explode(',', $_POST['sizes']);
                foreach ($sizeArray as $sizeValue) {
                    $sizeValue = trim($sizeValue);
                    if (!empty($sizeValue)) {
                        $sizes->addChild('size', $sizeValue);
                    }
                }
            }
            
            // Update colors
            unset($product->colors);
            $colors = $product->addChild('colors');
            if (!empty($_POST['colors'])) {
                $colorArray = explode(',', $_POST['colors']);
                foreach ($colorArray as $colorValue) {
                    $colorValue = trim($colorValue);
                    if (!empty($colorValue)) {
                        $colors->addChild('color', $colorValue);
                    }
                }
            }
            
            break;
        }
    }
    $xml->asXML($xmlFile);
    header("Location: admin.php");
    exit;
}

// Collect all unique categories from <categories> and <products>
$allCategories = [];

// Get from <categories>
if (isset($xml->categories)) {
    foreach ($xml->categories->category as $cat) {
        $category = (string)$cat;
        if (!in_array($category, $allCategories)) {
            $allCategories[] = $category;
        }
    }
}

// Also get from products (in case there's a category used in a product but not manually listed)
foreach ($xml->products->product as $product) {
    $category = (string)$product->category;
    if (!in_array($category, $allCategories)) {
        $allCategories[] = $category;
    }
}

sort($allCategories); // For dropdown sorting, if used

// Filter-matching function
function matches($product, $search, $filter) {
    $search = strtolower($search);
    $matchSearch = $search === '' ||
                   strpos(strtolower($product->name), $search) !== false ||
                   strpos(strtolower($product->category), $search) !== false ||
                   strpos(strtolower($product->description), $search) !== false;

    $matchFilter = $filter === '' || $product->category == $filter;

    return $matchSearch && $matchFilter;
}

// Set active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'products';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HirayaFit - Admin Dashboard</title>
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

        /* Tab Navigation Styles */
.tabs {
    display: flex;
    background-color: var(--light);
    border-radius: 5px 5px 0 0;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.tab {
    padding: 12px 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border-bottom: 2px solid transparent;
    color: var(--grey);
}

.tab:hover {
    color: var(--secondary);
    background-color: rgba(0, 113, 197, 0.05);
}

.tab.active {
    color: var(--secondary);
    border-bottom: 2px solid var(--secondary);
    background-color: rgba(0, 113, 197, 0.08);
}

/* Tab Content Styles */
.tab-content {
    display: none;
    background-color: var(--light);
    border-radius: 5px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.tab-content.active {
    display: block;
}

.tab-content h2 {
    font-size: 20px;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

/* Search Form Styles */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.search-form input[type="text"],
.search-form select {
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    flex-grow: 1;
    font-size: 14px;
}

.search-form button {
    background-color: var(--secondary);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 10px 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.search-form button:hover {
    background-color: #005fa3;
}

.search-form a {
    color: var(--grey);
    text-decoration: none;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.search-form a:hover {
    background-color: #f5f5f5;
    color: var(--dark);
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 30px;
    background-color: var(--light);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border-radius: 5px;
    overflow: hidden;
}

table th {
    background-color: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: var(--dark);
    border-bottom: 2px solid #e9ecef;
}

table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    color: var(--grey);
    vertical-align: middle;
}

table tr:last-child td {
    border-bottom: none;
}

table tr:hover {
    background-color: rgba(0, 113, 197, 0.03);
}

table img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.action-links a {
    display: inline-block;
    margin-right: 10px;
    color: var(--secondary);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.action-links a:last-child {
    color: var(--danger);
}

.action-links a:hover {
    text-decoration: underline;
}

/* Form Styles */
form:not(.search-form) {
    background-color: var(--light);
    padding: 25px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

form:not(.search-form) label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark);
}

form:not(.search-form) input[type="text"],
form:not(.search-form) input[type="number"],
form:not(.search-form) select,
form:not(.search-form) textarea {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 14px;
    transition: border 0.2s ease;
}

form:not(.search-form) input[type="file"] {
    margin-bottom: 20px;
}

form:not(.search-form) input:focus,
form:not(.search-form) select:focus,
form:not(.search-form) textarea:focus {
    border-color: var(--secondary);
    outline: none;
}

form:not(.search-form) button {
    background-color: var(--secondary);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 12px 20px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    display: block;
    width: 100%;
    max-width: 200px;
    margin-top: 10px;
}

form:not(.search-form) button:hover {
    background-color: #005fa3;
}

/* Error Message Styles */
.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

/* Success Message Styles */
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
}

/* Responsive Styles */
@media (max-width: 992px) {
    table {
        display: block;
        overflow-x: auto;
    }
    
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form input,
    .search-form select,
    .search-form button,
    .search-form a {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .tabs {
        flex-direction: column;
    }
    
    .tab {
        border-bottom: 1px solid #eee;
    }
    
    .tab.active {
        border-bottom: 1px solid #eee;
        border-left: 3px solid var(--secondary);
    }
}

/* Modal Styles */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: none;
    z-index: 1000;
    overflow-y: auto;
}

.modal-backdrop.active {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 50px 20px;
}

.modal {
    background-color: var(--light);
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 700px;
    position: relative;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
}

.modal-backdrop.active .modal {
    opacity: 1;
    transform: translateY(0);
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: var(--dark);
}

.modal-close {
    background: none;
    border: none;
    font-size: 22px;
    color: var(--grey);
    cursor: pointer;
    transition: all 0.2s ease;
}

.modal-close:hover {
    color: var(--danger);
}

.modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-footer button {
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}

.modal-footer .btn-cancel {
    background-color: #f8f9fa;
    color: var(--grey);
    border: 1px solid #e0e0e0;
}

.modal-footer .btn-cancel:hover {
    background-color: #e9ecef;
}

.modal-footer .btn-primary {
    background-color: var(--secondary);
    color: white;
    border: none;
}

.modal-footer .btn-primary:hover {
    background-color: #005fa3;
}

/* Form inside modal */
.modal form {
    box-shadow: none;
    padding: 0;
    margin: 0;
}

.modal form label {
    margin-top: 15px;
}

.modal form button {
    margin: 20px 0 5px;
}

/* Button styles for table */
.btn {
    display: inline-block;
    padding: 6px 12px;
    margin-right: 5px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: var(--secondary);
    color: white;
    border: none;
}

.btn-primary:hover {
    background-color: #005fa3;
    text-decoration: none;
}

.btn-danger {
    background-color: var(--danger);
    color: white;
    border: none;
}

.btn-danger:hover {
    background-color: #c82333;
    text-decoration: none;
}

.btn-add {
    margin-bottom: 20px;
    padding: 10px 15px;
}

/* Current image preview in modal */
.image-preview {
    margin: 10px 0;
}

.image-preview img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
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
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="payment-history.php"><i class="fas fa-money-check-alt"></i> Payment History</a>
            
            <div class="menu-title">INVENTORY</div>
            <a href="products.php" class="active"><i class="fas fa-tshirt"></i> Products & Categories</a>
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
                <span class="navbar-title">Manage Products and Categories</span>
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
                        <!--<a href="change-password.php"><i class="fas fa-lock"></i> Change Password</a>-->
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="dashboard-container">
        <div class="tabs">
            <div class="tab <?php echo $activeTab === 'products' ? 'active' : ''; ?>" onclick="location.href='?tab=products'">Products</div>
            <div class="tab <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" onclick="location.href='?tab=categories'">Categories</div>
        </div>
        
        <!-- PRODUCTS TAB -->
   <!-- PRODUCTS TAB -->
<div class="tab-content <?php echo $activeTab === 'products' ? 'active' : ''; ?>" id="products-tab">
    <h2>Manage Products</h2>
    
    <!-- Success/Error Messages -->
    <?php if (isset($successMessage)): ?>
        <div class="success-message"><?= $successMessage ?></div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
        <div class="error-message"><?= $errorMessage ?></div>
    <?php endif; ?>
    
    <!-- Search & Filter Form -->
    <form method="GET" class="search-form">
        <input type="hidden" name="tab" value="products">
        <input type="text" name="search" placeholder="Search name or description..." value="<?= $_GET['search'] ?? '' ?>">
        <select name="filter">
            <option value="">All Categories</option>
            <?php foreach ($allCategories as $cat): ?>
                <option value="<?= $cat ?>" <?= (isset($_GET['filter']) && $_GET['filter'] == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
        <a href="?tab=products">Reset</a>
    </form>

    <!-- Add Product Button -->
    <button class="btn btn-primary btn-add" id="openAddProductModal">
        <i class="fas fa-plus"></i> Add New Product
    </button>

    <!-- Products Table -->
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Image</th>
            <th>Featured</th>
            <th>On Sale</th>
            <th>Actions</th>
        </tr>
        <?php
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? '';
        
        if (isset($xml->products->product)) {
            foreach ($xml->products->product as $product):
                if (!matches($product, $search, $filter)) continue;
        ?>
        <tr>
            <td><?= $product->id ?></td>
            <td><?= $product->name ?></td>
            <td><?= $product->category ?></td>
            <td><?= $product->currency ?> <?= $product->price ?></td>
            <td><?= $product->stock ?></td>
            <td><img src="<?= $product->image ?>" alt="<?= $product->name ?>"></td>
            <td><?= $product->featured == 'true' ? 'Yes' : 'No' ?></td>
            <td><?= $product->on_sale == 'true' ? 'Yes' : 'No' ?></td>
            <td class="action-links">
                <a href="#" class="edit-product-btn" data-id="<?= $product->id ?>">Edit</a>
                <a href="?tab=products&delete_product=<?= $product->id ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; } ?>
    </table>
</div>

<!-- Product Modal -->
<div class="modal-backdrop" id="productModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Product</h2>
            <button type="button" class="modal-close" id="closeProductModal">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="id" id="product_id" value="">
                
                <label>Name:</label>
                <input type="text" name="name" id="product_name" required>
                
                <label>Category:</label>
                <select name="category" id="product_category" required>
                    <?php foreach ($existingCategories as $cat): ?>
                        <option value="<?= $cat ?>"><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label>Price (PHP):</label>
                <input type="number" name="price" id="product_price" min="0" step="0.01" required>
                
                <label>Stock:</label>
                <input type="number" name="stock" id="product_stock" min="0" required>
                
                <label>Description:</label>
                <textarea name="description" id="product_description" rows="3"></textarea>
                
                <label>Sizes (comma separated):</label>
                <input type="text" name="sizes" id="product_sizes" placeholder="S, M, L, XL">
                
                <label>Colors (comma separated):</label>
                <input type="text" name="colors" id="product_colors" placeholder="Black, White, Blue">
                
                <label>Rating:</label>
                <input type="number" name="rating" id="product_rating" min="0" max="5" step="0.1" value="4.0">
                
                <label>Review Count:</label>
                <input type="number" name="review_count" id="product_review_count" min="0" value="0">
                
                <label>Featured:</label>
                <select name="featured" id="product_featured">
                    <option value="true">Yes</option>
                    <option value="false" selected>No</option>
                </select>
                
                <label>On Sale:</label>
                <select name="on_sale" id="product_on_sale">
                    <option value="true">Yes</option>
                    <option value="false" selected>No</option>
                </select>
                
                <label id="imageLabel">Upload Image:</label>
                <input type="file" name="image_file" id="product_image">
                
                <div class="image-preview" id="imagePreview"></div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" id="cancelProductModal">Cancel</button>
                    <button type="submit" name="submit_product" class="btn-primary" id="submitProductBtn">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CATEGORIES TAB -->
<div class="tab-content <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" id="categories-tab">
    <h2>Manage Categories</h2>
    
    <?php if (isset($errorMessage)): ?>
        <div class="error-message"><?= $errorMessage ?></div>
    <?php endif; ?>
    
    <!-- Categories Table -->
    <table>
        <tr>
            <th>Category Name</th>
            <th>Product Count</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($existingCategories as $category): ?>
            <?php
            // Count products in this category
            $productCount = 0;
            foreach ($xml->products->product as $product) {
                if ((string)$product->category === $category) {
                    $productCount++;
                }
            }
            ?>
            <tr>
                <td><?= $category ?></td>
                <td><?= $productCount ?></td>
                <td>
                    <?php if ($productCount == 0): ?>
                        <a href="?tab=categories&delete_category=<?= urlencode($category) ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                    <?php else: ?>
                        <span title="Cannot delete categories with products">Delete (<?= $productCount ?> products)</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <!-- Add Category Form -->
    <h2>Add New Category</h2>
<form method="POST">
    <label>Category Name:</label><br>
    <input type="text" name="new_category" required><br><br>
    <button type="submit" name="add_category">Add Category</button>
</form>

<?php if (!empty($message)) echo $message; ?>


    <script>
   document.addEventListener('DOMContentLoaded', function() {
    // Admin dropdown toggle
    const adminDropdown = document.getElementById('adminDropdown');
    const adminDropdownContent = document.getElementById('adminDropdownContent');
    
    if (adminDropdown) {
        adminDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
            adminDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (adminDropdown && !adminDropdown.contains(e.target)) {
                adminDropdown.classList.remove('show');
            }
        });
    }
    
    // Sidebar toggle for responsive design
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
    
    // Modal functionality
    const productModal = document.getElementById('productModal');
    const openAddProductModal = document.getElementById('openAddProductModal');
    const closeProductModal = document.getElementById('closeProductModal');
    const cancelProductModal = document.getElementById('cancelProductModal');
    const productForm = document.getElementById('productForm');
    const modalTitle = document.getElementById('modalTitle');
    const submitProductBtn = document.getElementById('submitProductBtn');
    const imageLabel = document.getElementById('imageLabel');
    const imagePreview = document.getElementById('imagePreview');
    
    // Product data for edit
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

    // Open Add Product Modal
    if (openAddProductModal) {
        openAddProductModal.addEventListener('click', function() {
            // Reset form for adding
            productForm.reset();
            document.getElementById('product_id').value = '';
            modalTitle.textContent = 'Add New Product';
            submitProductBtn.textContent = 'Add Product';
            submitProductBtn.name = 'add_product';
            imageLabel.textContent = 'Upload Image:';
            document.getElementById('product_image').required = true;
            imagePreview.innerHTML = '';
            
            // Show modal
            productModal.classList.add('active');
        });
    }
    
    // Edit Product button click event
    const editProductBtns = document.querySelectorAll('.edit-product-btn');
    editProductBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const product = productData[productId];
            
            if (product) {
                // Fill form with product data
                document.getElementById('product_id').value = product.id;
                document.getElementById('product_name').value = product.name;
                document.getElementById('product_category').value = product.category;
                document.getElementById('product_price').value = product.price;
                document.getElementById('product_stock').value = product.stock;
                document.getElementById('product_description').value = product.description;
                document.getElementById('product_sizes').value = product.sizes.join(', ');
                document.getElementById('product_colors').value = product.colors.join(', ');
                document.getElementById('product_rating').value = product.rating;
                document.getElementById('product_review_count').value = product.review_count;
                document.getElementById('product_featured').value = product.featured;
                document.getElementById('product_on_sale').value = product.on_sale;
                
                // Show current image
                imagePreview.innerHTML = product.image ? 
                    `<p>Current image:</p><img src="${product.image}" alt="${product.name}">` : '';
                
                // Update modal title and button
                modalTitle.textContent = 'Edit Product';
                submitProductBtn.textContent = 'Update Product';
                submitProductBtn.name = 'update_product';
                imageLabel.textContent = 'Change Image:';
                document.getElementById('product_image').required = false;
                
                // Show modal
                productModal.classList.add('active');
            }
        });
    });
    
    // Close Modal Events
    if (closeProductModal) {
        closeProductModal.addEventListener('click', function() {
            productModal.classList.remove('active');
        });
    }
    
    if (cancelProductModal) {
        cancelProductModal.addEventListener('click', function() {
            productModal.classList.remove('active');
        });
    }
    
    // Close modal when clicking outside
    productModal.addEventListener('click', function(e) {
        if (e.target === productModal) {
            productModal.classList.remove('active');
        }
    });
    
    // Form submission
    if (productForm) {
        productForm.addEventListener('submit', function() {
            // You can add form validation here if needed
            // This will still submit the form to your PHP backend
        });
    }
});
    </script>
</body>
</html>