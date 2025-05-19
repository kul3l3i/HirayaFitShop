<?php
session_start();

// Check if user is logged in, redirect to login if not
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Get the current user's ID
$user_id = $_SESSION['user'];

if (!isset($_SESSION['carts'])) {
    $_SESSION['carts'] = [];
}

// Initialize this specific user's cart if it doesn't exist
if (!isset($_SESSION['carts'][$user_id])) {
    $_SESSION['carts'][$user_id] = [];
}

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Ensure quantity is at least 1
    $quantity = max(1, $quantity);
    
    // Add to cart or update quantity if already in cart for this specific user
    if (isset($_SESSION['carts'][$user_id][$product_id])) {
        $_SESSION['carts'][$user_id][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['carts'][$user_id][$product_id] = [
            'quantity' => $quantity
        ];
    }
    
    // Redirect to prevent form resubmission
    header("Location: menu2.php?added=1");
    exit();
}

// Load data from XML file
$xmlPath = 'pastry.xml';
$pastries = [];

if (file_exists($xmlPath)) {
    $file = simplexml_load_file($xmlPath);
    foreach ($file->pastry as $row) {
        // Add ID if it doesn't exist
        if (!isset($row['id'])) {
            $row->addAttribute('id', uniqid());
        }
        $pastries[] = $row;
    }
}

// Extract unique categories for menu
$categories = array_unique(array_map(function($p) { return (string)$p->producttype; }, $pastries));

// Calculate total items in cart for this specific user
$totalItems = 0;
if (isset($_SESSION['carts'][$user_id])) {
    foreach ($_SESSION['carts'][$user_id] as $item) {
        $totalItems += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Menu - La Croissanterie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10 3C10 2.44772 10.4477 2 11 2H13C13.5523 2 14 2.44772 14 3V10.5858L15.2929 9.29289C15.6834 8.90237 16.3166 8.90237 16.7071 9.29289C17.0976 9.68342 17.0976 10.3166 16.7071 10.7071L12.7071 14.7071C12.3166 15.0976 11.6834 15.0976 11.2929 14.7071L7.29289 10.7071C6.90237 10.3166 6.90237 9.68342 7.29289 9.29289C7.68342 8.90237 8.31658 8.90237 8.70711 9.29289L10 10.5858V3Z"></path>
        <path d="M3 14C3 12.8954 3.89543 12 5 12H19C20.1046 12 21 12.8954 21 14V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V14Z"></path>
      </svg>
      <span class="logo-text">La Croissanterie</span>
    </div>
    
    <nav>
      <ul class="main-nav">
        <li><a href="homepage.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="menu2.php">Menu</a></li>
        <li><a href="cart.php">Cart <span class="cart-badge" id="cartCount"><?php echo $totalItems; ?></span></a></li>
      </ul>
    </nav>
    
    <div class="profile-dropdown">
      <div class="dropdown-toggle" id="profileDropdown">
        <div class="profile-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <span class="profile-name"><?php echo htmlspecialchars($_SESSION['fname']); ?></span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </div>
      <div class="dropdown-menu" id="profileMenu">
        <a href="profile.php">My Profile</a>
        <div class="dropdown-divider"></div>
        <a href="homepage.php" id="logoutBtn">Logout</a>
      </div>
    </div>
  </div>
</header>

<div class="container">
    <h1 class="page-title">Our Menu</h1>
    
    <?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">
        Product has been added to your cart!
    </div>
    <?php endif; ?>
    
    <!-- Category Menu - Dynamic display based on categories from XML -->
    <div class="category-menu" id="categoryMenu">
        <button class="category-btn active" data-category="all">All Products</button>
        <?php foreach ($categories as $category): ?>
            <button class="category-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                <?php echo htmlspecialchars($category); ?>
            </button>
        <?php endforeach; ?>
    </div>
    
    <div class="controls">
        <div class="search-box">
            <span class="search-icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input type="text" class="search-input" id="tagSearch" placeholder="Search by tag...">
        </div>
    </div>
    
    <!-- Product Grid -->
    <div class="product-grid" id="productGrid">
        <!-- Products will be loaded by JavaScript -->
    </div>
    
    <!-- Pagination -->
    <div class="pagination" id="pagination">
        <!-- Pagination buttons will be added by JavaScript -->
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal" id="productModal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div class="modal-body">
            <div class="modal-image-container">
                <img src="" alt="" class="modal-image" id="modalImage">
            </div>
            <div class="modal-details">
                <h2 class="modal-product-name" id="modalName"></h2>
                <div class="modal-product-price" id="modalPrice"></div>
                <div class="modal-product-tag" id="modalTag"></div>
                <p class="modal-product-description" id="modalDescription"></p>
                <form action="menu2.php" method="post" class="modal-form">
                    <input type="hidden" name="product_id" id="modalProductId">
                    <div class="quantity-control">
                        <label for="modalQuantity">Quantity:</label>
                        <input type="number" name="quantity" id="modalQuantity" value="1" min="1" class="quantity-input">
                    </div>
                    <div class="modal-buttons">
                        <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal" id="logoutModal">
  <div class="modal-content logout-modal-content">
    <span class="modal-close" id="closeLogoutModal">&times;</span>
    <div class="logout-modal-body">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to logout?</p>
      <div class="logout-modal-buttons">
        <button class="cancel-btn" id="cancelLogout">Cancel</button>
        <button class="confirm-btn" id="confirmLogout">Logout</button>
      </div>
    </div>
  </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <h3 class="footer-title">La Croissanterie</h3>
            <p>Authentic French pastries baked fresh daily with premium ingredients.</p>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Quick Links</h3>
            <ul class="footer-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="menu2.php">Menu</a></li>
                <li><a href="cart.php">Cart</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Contact Us</h3>
            <ul class="footer-links">
                <li>123 Bakery Street, Manila</li>
                <li>Phone: (02) 8123-4567</li>
                <li>Email: info@lacroissanterie.com</li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3 class="footer-title">Opening Hours</h3>
            <ul class="footer-links">
                <li>Monday - Friday: 7am - 8pm</li>
                <li>Saturday: 8am - 9pm</li>
                <li>Sunday: 8am - 7pm</li>
            </ul>
        </div>
        
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> La Croissanterie. All rights reserved.
        </div>
    </div>
</footer>

<script>
    // Convert PHP array to JavaScript array
    const products = [
        <?php foreach ($pastries as $item): ?>
        {
            id: "<?php echo $item['id']; ?>",
            name: "<?php echo htmlspecialchars($item->name); ?>",
            price: <?php echo floatval($item->price); ?>,
            description: "<?php echo htmlspecialchars($item->description ?? ''); ?>",
            image: "<?php echo htmlspecialchars($item->image); ?>",
            category: "<?php echo htmlspecialchars($item->producttype); ?>",
            tag: "<?php echo htmlspecialchars($item->producttag ?? ''); ?>"
        },
        <?php endforeach; ?>
    ];
    
    // DOM elements
    const productGrid = document.getElementById('productGrid');
    const pagination = document.getElementById('pagination');
    const categoryButtons = document.querySelectorAll('.category-btn');
    const tagSearch = document.getElementById('tagSearch');
    const productModal = document.getElementById('productModal');
    const modalClose = document.querySelector('.modal-close');
    const modalImage = document.getElementById('modalImage');
    const modalName = document.getElementById('modalName');
    const modalPrice = document.getElementById('modalPrice');
    const modalTag = document.getElementById('modalTag');
    const modalDescription = document.getElementById('modalDescription');
    const modalProductId = document.getElementById('modalProductId');
    
    // Pagination settings
    const productsPerPage = 6;
    let currentPage = 1;
    let filteredProducts = [...products];
    
    // Add event listeners to category buttons
    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
            
            filterProducts();
        });
    });
    
    // Add event listener to search input
    tagSearch.addEventListener('input', filterProducts);
    
    // Close modal when clicking on the X button
    modalClose.addEventListener('click', () => {
        productModal.classList.remove('show');
        document.body.style.overflow = 'auto';
    });
    
    // Close modal when clicking outside the modal content
    productModal.addEventListener('click', (e) => {
        if (e.target === productModal) {
            productModal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Open modal with product details
    function openProductModal(product) {
        modalImage.src = product.image;
        modalImage.alt = product.name;
        modalName.textContent = product.name;
        modalPrice.textContent = `₱${product.price.toFixed(2)}`;
        modalTag.textContent = product.tag;
        modalDescription.textContent = product.description;
        modalProductId.value = product.id;
        
        productModal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    }
    
    // Filter products based on selected category and search term
    function filterProducts() {
        const activeCategory = document.querySelector('.category-btn.active').dataset.category;
        const searchTerm = tagSearch.value.toLowerCase();
        
        filteredProducts = products.filter(product => {
            const matchesCategory = activeCategory === 'all' || product.category === activeCategory;
            const matchesSearch = product.tag.toLowerCase().includes(searchTerm) || 
                                  product.name.toLowerCase().includes(searchTerm);
            return matchesCategory && matchesSearch;
        });
        
        // Reset to first page and render
        currentPage = 1;
        renderProducts();
    }
    
    // Render products for current page
    function renderProducts() {
        // Calculate start and end indices
        const startIndex = (currentPage - 1) * productsPerPage;
        const endIndex = Math.min(startIndex + productsPerPage, filteredProducts.length);
        const currentProducts = filteredProducts.slice(startIndex, endIndex);
        
        // Clear the product grid
        productGrid.innerHTML = '';
        
        // If no products found
        if (currentProducts.length === 0) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.textContent = 'No products found matching your criteria.';
            productGrid.appendChild(noResults);
            
            // Clear pagination
            pagination.innerHTML = '';
            return;
        }
        
        // Add products to the grid
        currentProducts.forEach(product => {
            const card = document.createElement('div');
            card.className = 'product-card';
            
            card.innerHTML = `
                <img src="${product.image}" alt="${product.name}" class="product-image">
<div class="product-info">
    <h3 class="product-name">${product.name}</h3>
    <div class="product-price">₱${product.price.toFixed(2)}</div>
    <div class="product-tag">${product.tag}</div>
    <p class="product-description">${product.description}</p>
    <form action="menu2.php" method="post">
        <input type="hidden" name="product_id" value="${product.id}">
        <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
    </form>
</div>
            `;
            
            // Add event listeners for opening the product modal
            const productImage = card.querySelector('.product-image');
            const productName = card.querySelector('.product-name');
            const productDesc = card.querySelector('.product-description');
            
            productImage.addEventListener('click', () => openProductModal(product));
            productName.addEventListener('click', () => openProductModal(product));
            productDesc.addEventListener('click', () => openProductModal(product));
            
            productGrid.appendChild(card);
        });
        
        // Render pagination
        renderPagination();
    }
    
    // Render pagination controls
    function renderPagination() {
        const totalPages = Math.ceil(filteredProducts.length / productsPerPage);
        
        // Clear pagination
        pagination.innerHTML = '';
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn';
        prevBtn.innerHTML = '&laquo;';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderProducts();
            }
        });
        pagination.appendChild(prevBtn);
        
        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'page-btn';
            if (i === currentPage) {
                pageBtn.classList.add('active');
            }
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                renderProducts();
            });
            pagination.appendChild(pageBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn';
        nextBtn.innerHTML = '&raquo;';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderProducts();
            }
        });
        pagination.appendChild(nextBtn);
    }
    
    // Initialize profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    const profileMenu = document.getElementById('profileMenu');
    
    profileDropdown.addEventListener('click', () => {
        profileMenu.classList.toggle('show');
    });
    
    // Close dropdown when clicking outside
    window.addEventListener('click', (e) => {
        if (!e.target.closest('.profile-dropdown')) {
            profileMenu.classList.remove('show');
        }
    });
    
    // Initialize logout modal functionality
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutModal = document.getElementById('logoutModal');
    const closeLogoutModal = document.getElementById('closeLogoutModal');
    const cancelLogout = document.getElementById('cancelLogout');
    const confirmLogout = document.getElementById('confirmLogout');
    
    logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        logoutModal.classList.add('show');
    });
    
    closeLogoutModal.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });
    
    cancelLogout.addEventListener('click', () => {
        logoutModal.classList.remove('show');
    });
    
    confirmLogout.addEventListener('click', () => {
        window.location.href = 'homepage.php';
    });
    
    // Close modal when clicking outside
    logoutModal.addEventListener('click', (e) => {
        if (e.target === logoutModal) {
            logoutModal.classList.remove('show');
        }
    });
    
    // Initial rendering
    renderProducts();
    
    // Auto-hide alert after 3 seconds
    const alertBox = document.querySelector('.alert');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 500);
        }, 3000);
    }
</script>
</body>
</html>