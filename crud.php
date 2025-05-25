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

// Extract unique categories from products
$existingCategories = [];
foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->product to $xml->products->product
    $category = (string)$product->category;
    if (!empty($category) && !in_array($category, $existingCategories)) {
        $existingCategories[] = $category;
    }
}
sort($existingCategories);

// Handle Category Add
if (isset($_POST['add_category'])) {
    $newCategory = trim($_POST['new_category']);
    if (!empty($newCategory) && !in_array($newCategory, $existingCategories)) {
        $existingCategories[] = $newCategory;
        sort($existingCategories);
        // We don't actually update the XML here since categories are stored with products
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
    header("Location: admin.php");
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
    header("Location: admin.php");
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

// Get all product categories for filtering
$allCategories = [];
foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->products->product to $xml->products->product
    $category = (string)$product->category;
    if (!empty($category) && !in_array($category, $allCategories)) {
        $allCategories[] = $category;
    }
}
sort($allCategories);

// Function to check if product matches search/filter criteria
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
<html>
<head>
    <title>HirayaFit Store Admin</title>    <link rel="icon" href="images/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        img {
            width: 80px;
            height: auto;
        }
        form {
            margin-top: 20px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="file"] {
            margin-bottom: 10px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-form input, .search-form select {
            margin-bottom: 0;
        }
        .search-form button {
            white-space: nowrap;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .action-links a {
            margin-right: 10px;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
        }
        .tab.active {
            background-color: white;
            border-color: #ddd;
            border-bottom-color: white;
            margin-bottom: -1px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .error-message {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>HirayaFit Store Admin Panel</h1>
        
        <div class="tabs">
            <div class="tab <?php echo $activeTab === 'products' ? 'active' : ''; ?>" onclick="location.href='?tab=products'">Products</div>
            <div class="tab <?php echo $activeTab === 'categories' ? 'active' : ''; ?>" onclick="location.href='?tab=categories'">Categories</div>
        </div>
        
        <!-- PRODUCTS TAB -->
        <div class="tab-content <?php echo $activeTab === 'products' ? 'active' : ''; ?>" id="products-tab">
            <h2>Manage Products</h2>
            
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
                        <a href="?tab=products&edit_product=<?= $product->id ?>">Edit</a>
                        <a href="?tab=products&delete_product=<?= $product->id ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; } ?>
            </table>

            <!-- Add/Edit Product Form -->
            <h2><?= $editProduct ? 'Edit Product' : 'Add New Product' ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="id" value="<?= $editProduct->id ?>">
                <?php endif; ?>
                
                <label>Name:</label>
                <input type="text" name="name" value="<?= $editProduct->name ?? '' ?>" required>
                
                <label>Category:</label>
                <select name="category" required>
                    <?php foreach ($existingCategories as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($editProduct && $editProduct->category == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label>Price (PHP):</label>
                <input type="number" name="price" value="<?= $editProduct->price ?? '' ?>" min="0" step="0.01" required>
                
                <label>Stock:</label>
                <input type="number" name="stock" value="<?= $editProduct->stock ?? '0' ?>" min="0" required>
                
                <label>Description:</label>
                <textarea name="description" rows="3"><?= $editProduct->description ?? '' ?></textarea>
                
                <label>Sizes (comma separated):</label>
                <?php
                $sizeString = '';
                if ($editProduct && isset($editProduct->sizes->size)) {
                    $sizes = [];
                    foreach ($editProduct->sizes->size as $size) {
                        $sizes[] = (string)$size;
                    }
                    $sizeString = implode(', ', $sizes);
                }
                ?>
                <input type="text" name="sizes" value="<?= $sizeString ?>" placeholder="S, M, L, XL">
                
                <label>Colors (comma separated):</label>
                <?php
                $colorString = '';
                if ($editProduct && isset($editProduct->colors->color)) {
                    $colors = [];
                    foreach ($editProduct->colors->color as $color) {
                        $colors[] = (string)$color;
                    }
                    $colorString = implode(', ', $colors);
                }
                ?>
                <input type="text" name="colors" value="<?= $colorString ?>" placeholder="Black, White, Blue">
                
                <label>Rating:</label>
                <input type="number" name="rating" value="<?= $editProduct->rating ?? '4.0' ?>" min="0" max="5" step="0.1">
                
                <label>Review Count:</label>
                <input type="number" name="review_count" value="<?= $editProduct->review_count ?? '0' ?>" min="0">
                
                <label>Featured:</label>
                <select name="featured">
                    <option value="true" <?= ($editProduct && $editProduct->featured == 'true') ? 'selected' : '' ?>>Yes</option>
                    <option value="false" <?= ($editProduct && $editProduct->featured == 'false') ? 'selected' : '' ?>>No</option>
                </select>
                
                <label>On Sale:</label>
                <select name="on_sale">
                    <option value="true" <?= ($editProduct && $editProduct->on_sale == 'true') ? 'selected' : '' ?>>Yes</option>
                    <option value="false" <?= ($editProduct && $editProduct->on_sale == 'false') ? 'selected' : '' ?>>No</option>
                </select>
                
                <label><?= $editProduct ? 'Change' : 'Upload' ?> Image:</label>
                <input type="file" name="image_file" <?= $editProduct ? '' : 'required' ?>>
                <?php if ($editProduct && $editProduct->image): ?>
                    <p>Current image: <img src="<?= $editProduct->image ?>" alt="<?= $editProduct->name ?>" style="width: 100px; height: auto;"></p>
                <?php endif; ?>
                
                <button type="submit" name="<?= $editProduct ? 'update_product' : 'add_product' ?>">
                    <?= $editProduct ? 'Update Product' : 'Add Product' ?>
                </button>
            </form>
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
                    foreach ($xml->products->product as $product) { // Fixed: Changed from $xml->products->product to $xml->products->product
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
                                <a href="?tab=categories&delete_category=<?= urlencode($category) ?>" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
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
                <label>Category Name:</label>
                <input type="text" name="new_category" required>
                <button type="submit" name="add_category">Add Category</button>
            </form>
        </div>
    </div>
</body>
</html>