   // Function to dynamically generate category navigation based on XML data
   function generateCategoryNav() {
    // Check if products are already loaded
    if (products.length === 0) {
        // If not, set a small timeout to wait for products to load
        setTimeout(generateCategoryNav, 500);
        return;
    }
    
    // Get all unique categories from products
    const categories = [...new Set(products.map(product => product.category))];
    
    // Get the categories container
    const categoriesContainer = document.querySelector('categories');
    if (categoriesContainer) {
        // Clear existing categories
        categoriesContainer.innerHTML = '';
        
        // Add each category to the container
        categories.forEach(category => {
            const categoryElement = document.createElement('category');
            categoryElement.textContent = category;
            categoriesContainer.appendChild(categoryElement);
        });
    }
    
    // Update the main navigation to include categories
    updateMainNavigation(categories);
}

// Function to update main navigation with categories
function updateMainNavigation(categories) {
    const mainNav = document.getElementById('mainNav');
    if (!mainNav) return;
    
    // Get all existing links that are not category links
    const staticLinks = Array.from(mainNav.querySelectorAll('a:not([data-category])'));
    
    // Clear the navigation
    mainNav.innerHTML = '';
    
    // Add back the static links
    staticLinks.forEach(link => {
        mainNav.appendChild(link.cloneNode(true));
    });
    
    // Add category links
    categories.forEach(category => {
        const categoryLink = document.createElement('a');
        categoryLink.href = 'javascript:void(0);';
        categoryLink.setAttribute('data-category', category);
        categoryLink.textContent = category.toUpperCase();
        categoryLink.addEventListener('click', function() {
            filterProductsByCategory(category);
        });
        mainNav.appendChild(categoryLink);
    });
}

// Function to filter products by category
function filterProductsByCategory(category) {
    // Filter products by the selected category
    const filteredProducts = products.filter(product => 
        product.category.toLowerCase() === category.toLowerCase()
    );
    
    // Display the filtered products
    displayCategoryResults(filteredProducts, category);
    
    // Scroll to category results
    const resultsSection = document.getElementById('categoryResults');
    if (resultsSection) {
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Display category filtered results
function displayCategoryResults(results, categoryName) {
    let resultsContainer = document.getElementById('categoryResults');
    
    // If results container doesn't exist, create it at the bottom of the page
    if (!resultsContainer) {
        // Create results container
        resultsContainer = document.createElement('section');
        resultsContainer.id = 'categoryResults';
        resultsContainer.className = 'category-results-section';
        
        // Add container to the bottom of the page, before the footer if it exists
        const footer = document.querySelector('footer');
        if (footer) {
            document.body.insertBefore(resultsContainer, footer);
        } else {
            document.body.appendChild(resultsContainer);
        }
    }
    
    // Clear previous results
    resultsContainer.innerHTML = '';
    
    // Create container for results content
    const resultsContent = document.createElement('div');
    resultsContent.className = 'container';
    resultsContainer.appendChild(resultsContent);
    
    // Add results heading
    const heading = document.createElement('h2');
    heading.textContent = categoryName;
    resultsContent.appendChild(heading);
    
    // If no results found
    if (results.length === 0) {
        const noResults = document.createElement('p');
        noResults.className = 'no-results';
        noResults.textContent = 'No products found in this category.';
        resultsContent.appendChild(noResults);
        resultsContainer.style.display = 'block';
        return;
    }
    
    // Create results grid
    const resultsGrid = document.createElement('div');
    resultsGrid.className = 'results-grid';
    resultsContent.appendChild(resultsGrid);
    
    // Add each product to the grid
    results.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        
        // Format price with commas for thousands
        const formattedPrice = parseFloat(product.price).toLocaleString();
        
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                ${product.on_sale === 'true' ? '<span class="sale-badge">Sale</span>' : ''}
              
            </div>
            <div class="product-info">
                <h3>${product.name}</h3>
                <p class="price">₱${formattedPrice}</p>
                <p class="category">${product.category}</p>
                <div class="product-rating">
                    <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                    <span class="rating-text">(${product.rating})</span>
                </div>
            
                <button class="add-to-cart" data-id="${product.id}">Add to Cart</button>
            </div>
        `;
        resultsGrid.appendChild(productCard);
    });
    
    resultsContainer.style.display = 'block';
    
    // Add event listeners to the buttons
    addCategoryResultsEventListeners();
}

// Add event listeners to category results buttons
function addCategoryResultsEventListeners() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('#categoryResults .add-to-cart, #categoryResults .add-to-cart-icon');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
    
    // Quick view buttons
    const quickViewButtons = document.querySelectorAll('#categoryResults .quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // View details buttons
    const viewDetailsButtons = document.querySelectorAll('#categoryResults .view-details');
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetails(productId);
        });
    });
    
    // Add to wishlist buttons
    const wishlistButtons = document.querySelectorAll('#categoryResults .add-to-wishlist');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToWishlist(productId);
        });
    });
}

// Updated function to show product details in a modal
function showQuickView(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Create a modal container if it doesn't exist
    let modalOverlay = document.getElementById('productDetailsModal');
    if (!modalOverlay) {
        modalOverlay = document.createElement('div');
        modalOverlay.id = 'productDetailsModal';
        modalOverlay.className = 'product-modal-overlay';
        document.body.appendChild(modalOverlay);
    }
    
    // Format price with commas for thousands
    const formattedPrice = parseFloat(product.price).toLocaleString();
    
    // Populate modal with product information
    modalOverlay.innerHTML = `
        <div class="product-modal-content">
            <button class="modal-close">&times;</button>
            
            <div class="modal-product-grid">
                <!-- Product Image Column -->
                <div class="modal-product-image">
                    <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                </div>
                
                <!-- Product Details Column -->
                <div class="modal-product-details">
                    <h2 class="modal-product-title">${product.name}</h2>
                    
                    <div class="modal-product-price">₱${formattedPrice}</div>
                    
                    <!-- Product Rating -->
                    <div class="modal-product-rating">
                        <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                        <span class="rating-text">(${product.rating} - ${product.review_count || '0'} reviews)</span>
                    </div>
                    
                    <!-- Product Description -->
                    <div class="modal-product-description">
                        <p>${product.description || 'No description available'}</p>
                    </div>
                    
                    <!-- Size Options -->
                    <div class="modal-product-size">
                        <label>Size:</label>
                        <div class="size-options">
                            ${product.sizes.map(size => `<button class="size-btn" data-size="${size}">${size}</button>`).join('')}
                        </div>
                    </div>
                    
                    <!-- Color Options -->
                    <div class="modal-product-color">
                        <label>Color:</label>
                        <div class="color-options">
                            ${product.colors.map(color => {
                                const colorCode = getColorCode(color);
                                return `<button class="color-btn" style="background-color: ${colorCode};" data-color="${color}"></button>`;
                            }).join('')}
                        </div>
                    </div>
                    
                    <!-- Quantity Selector -->
                    <div class="modal-product-quantity">
                        <label>Quantity:</label>
                        <div class="quantity-control">
                            <button class="quantity-btn minus">−</button>
                            <input type="number" class="quantity-input" value="1" min="1" max="${product.stock}">
                            <button class="quantity-btn plus">+</button>
                        </div>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <button class="modal-add-to-cart" data-id="${product.id}">ADD TO CART</button>
                </div>
            </div>
        </div>
    `;
    
    // Show the modal
    modalOverlay.style.display = 'flex';
    document.body.classList.add('modal-open');
    
    // Add event listeners for the modal
    addModalEventListeners(modalOverlay, product);
}

// Make showProductDetails use the same implementation as showQuickView
function showProductDetails(productId) {
    showQuickView(productId);
}

// Helper function to get color code from color name
function getColorCode(colorName) {
    const colorMap = {
        'Black': '#000000',
        'Navy': '#000080',
        'Blue': '#0000FF',
        'Red': '#FF0000',
        'White': '#FFFFFF',
        'Grey': '#808080',
        'Green': '#008000',
        'Yellow': '#FFFF00',
        'Purple': '#800080',
        'Orange': '#FFA500',
        'Pink': '#FFC0CB'
    };
    
    return colorMap[colorName] || colorName.toLowerCase();
}

// Add event listeners for modal functionality
function addModalEventListeners(modal, product) {
    // Close button
    const closeBtn = modal.querySelector('.modal-close');
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    });
    
    // Close on click outside the modal content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
    
    // Size buttons
    const sizeButtons = modal.querySelectorAll('.size-btn');
    sizeButtons.forEach(button => {
        button.addEventListener('click', () => {
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
    
    // Color buttons
    const colorButtons = modal.querySelectorAll('.color-btn');
    colorButtons.forEach(button => {
        button.addEventListener('click', () => {
            colorButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        });
    });
    
    // Quantity buttons
    const minusBtn = modal.querySelector('.minus');
    const plusBtn = modal.querySelector('.plus');
    const quantityInput = modal.querySelector('.quantity-input');
    
    minusBtn.addEventListener('click', () => {
        if (parseInt(quantityInput.value) > 1) {
            quantityInput.value = parseInt(quantityInput.value) - 1;
        }
    });
    
    plusBtn.addEventListener('click', () => {
        if (parseInt(quantityInput.value) < parseInt(product.stock)) {
            quantityInput.value = parseInt(quantityInput.value) + 1;
        }
    });
    
    // Add to cart button
    const addToCartBtn = modal.querySelector('.modal-add-to-cart');
    addToCartBtn.addEventListener('click', () => {
        const quantity = parseInt(quantityInput.value);
        const selectedSize = modal.querySelector('.size-btn.active')?.getAttribute('data-size') || product.sizes[0];
        const selectedColor = modal.querySelector('.color-btn.active')?.getAttribute('data-color') || product.colors[0];
        
        addToCartWithOptions(product.id, quantity, selectedSize, selectedColor);
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    });
}

// Function to add to cart with specified options
function addToCartWithOptions(productId, quantity, size, color) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Get current cart from local storage or initialize empty cart
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Create unique identifier for this product with selected options
    const itemId = `${productId}-${size}-${color}`;
    
    // Check if product with same options already in cart
    const existingItemIndex = cart.findIndex(item => 
        item.id === productId && 
        item.size === size && 
        item.color === color
    );
    
    if (existingItemIndex >= 0) {
        cart[existingItemIndex].quantity += quantity;
    } else {
        cart.push({
            id: productId,
            itemId: itemId,
            name: product.name,
            price: parseFloat(product.price),
            image: product.image,
            quantity: quantity,
            size: size,
            color: color
        });
    }
    
    // Save updated cart to local storage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update cart count in the UI
    updateCartCount();
    
    // Show confirmation message
    const confirmMsg = document.createElement('div');
    confirmMsg.className = 'add-to-cart-confirmation';
    confirmMsg.innerHTML = `
        <div class="confirmation-content">
            <i class="fas fa-check-circle"></i>
            <p>${product.name} added to your cart!</p>
        </div>
    `;
    document.body.appendChild(confirmMsg);
    
    // Remove confirmation after 3 seconds
    setTimeout(() => {
        confirmMsg.classList.add('fade-out');
        setTimeout(() => {
            document.body.removeChild(confirmMsg);
        }, 500);
    }, 2500);
}

// Add necessary CSS for modal
function addProductDetailsModalStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        /* Modal Overlay */
        .product-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        /* Modal Content */
        .product-modal-content {
            background-color: #fff;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        /* Close Button */
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            z-index: 10;
        }
        
        /* Product Grid Layout */
        .modal-product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        /* Product Image */
        .modal-product-image {
            padding: 20px;
        }
        
        .modal-product-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        /* Product Details */
        .modal-product-details {
            padding: 30px 30px 30px 0;
        }
        
        /* Product Title */
        .modal-product-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        /* Product Price */
        .modal-product-price {
            font-size: 24px;
            color: #e73c17;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        /* Product Rating */
        .modal-product-rating {
            margin-bottom: 15px;
        }
        
        .modal-product-rating .stars {
            color: #ffaa00;
        }
        
        .modal-product-rating .rating-text {
            color: #777;
            font-size: 14px;
        }
        
        /* Product Description */
        .modal-product-description {
            margin-bottom: 20px;
            color: #555;
        }
        
        /* Size and Color Sections */
        .modal-product-size,
        .modal-product-color {
            margin-bottom: 20px;
        }
        
        .modal-product-size label,
        .modal-product-color label,
        .modal-product-quantity label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        /* Size Options */
        .size-options {
            display: flex;
            gap: 10px;
        }
        
        .size-btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .size-btn.active {
            border-color: #333;
            background-color: #333;
            color: white;
        }
        
        /* Color Options */
        .color-options {
            display: flex;
            gap: 10px;
        }
        
        .color-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-btn.active {
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #333;
        }
        
        /* Quantity Section */
        .modal-product-quantity {
            margin-bottom: 25px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            width: 36px;
            height: 36px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            height: 36px;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
            text-align: center;
            font-size: 16px;
        }
        
        /* Add to Cart Button */
        .modal-add-to-cart {
            width: 100%;
            padding: 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .modal-add-to-cart:hover {
            background-color: #0b7dda;
        }
        
        /* Add to Cart Confirmation */
        .add-to-cart-confirmation {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1100;
            transition: opacity 0.5s;
        }
        
        .confirmation-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .confirmation-content i {
            font-size: 24px;
        }
        
        .fade-out {
            opacity: 0;
        }
        
        /* Modal Open Body Style */
        body.modal-open {
            overflow: hidden;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .modal-product-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-product-details {
                padding: 20px;
            }
        }
    `;
    document.head.appendChild(styleElement);
}

// Add event listeners to product cards to view details
function addViewDetailsEventListeners() {
    // For category results
    const categoryResults = document.querySelectorAll('#categoryResults .product-card');
    categoryResults.forEach(card => {
        // Make the whole card clickable except buttons
        card.addEventListener('click', function(e) {
            // If click is on a button or inside a button, don't trigger details view
            if (e.target.closest('button')) {
                return;
            }
            
            const productId = card.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // For featured products
    const featuredProductCards = document.querySelectorAll('#featured .product-card');
    featuredProductCards.forEach(card => {
        // Make the whole card clickable except buttons
        card.addEventListener('click', function(e) {
            // If click is on a button or inside a button, don't trigger details view
            if (e.target.closest('button')) {
                return;
            }
            
            const addToCartBtn = card.querySelector('.add-to-cart');
            const productId = addToCartBtn.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // For search results - use mutation observer to catch dynamically added results
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                const searchResultCards = document.querySelectorAll('#searchResults .product-card');
                searchResultCards.forEach(card => {
                    // Make the whole card clickable except buttons
                    card.addEventListener('click', function(e) {
                        // If click is on a button or inside a button, don't trigger details view
                        if (e.target.closest('button')) {
                            return;
                        }
                        
                        const addToCartBtn = card.querySelector('.add-to-cart');
                        const productId = addToCartBtn.getAttribute('data-id');
                        showQuickView(productId);
                    });
                });
            }
        });
    });
    
    // Start observing the document body for search results
    observer.observe(document.body, { childList: true, subtree: true });
}

// Update initialization function
function initializeProductModals() {
    // Add the modal styles
    addProductDetailsModalStyles();
    
    // Add click event listeners for the Quick View buttons
    const quickViewButtons = document.querySelectorAll('#categoryResults .quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // Add click event listeners for the View Details buttons
    const viewDetailsButtons = document.querySelectorAll('#categoryResults .view-details');
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetails(productId);
        });
    });
    
    // Make cards clickable for details view
    addViewDetailsEventListeners();
}

// Update document ready function to include our new initialization
document.addEventListener('DOMContentLoaded', function() {
    loadProductData();
    updateCartCount();
    
    // Add event listener for search button
    document.querySelector('.search-bar button').addEventListener('click', searchProducts);
    
    // Add event listener for Enter key in search input
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    // Load featured products after a slight delay to ensure XML is loaded
    setTimeout(() => {
        loadFeaturedProducts();
        // Initialize product modals after products are loaded
        setTimeout(initializeProductModals, 300);
    }, 500);
    
    // Generate category navigation after the products have loaded
    setTimeout(generateCategoryNav, 700);
});


///// all products home /////////////////////////////////////////////////
// Function to display all products on the home page
function displayAllProducts() {
    // Check if products are loaded
    if (products.length === 0) {
        setTimeout(displayAllProducts, 500);
        return;
    }
    
    // Check if all products section already exists
    let allProductsSection = document.getElementById('allProductsSection');
    
    // If the section doesn't exist, create it
    if (!allProductsSection) {
        // Create section element
        allProductsSection = document.createElement('section');
        allProductsSection.id = 'allProductsSection';
        allProductsSection.className = 'all-products-section';
        
        // Create container
        const container = document.createElement('div');
        container.className = 'container';
        allProductsSection.appendChild(container);
        
        // Add heading
        const heading = document.createElement('h2');
        heading.textContent = 'All Products';
        container.appendChild(heading);
        
        // Create products grid
        const productsGrid = document.createElement('div');
        productsGrid.className = 'products-grid';
        container.appendChild(productsGrid);
        
        // Add products to grid
        products.forEach(product => {
            // Format price with commas
            const formattedPrice = parseFloat(product.price).toLocaleString();
            
            const productCard = document.createElement('div');
            productCard.className = 'product-card';
            productCard.setAttribute('data-id', product.id);
            
            productCard.innerHTML = `
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                    ${product.on_sale === 'true' ? '<span class="sale-badge">Sale</span>' : ''}
                    <div class="product-actions">
                        <button class="quick-view" data-id="${product.id}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="add-to-wishlist" data-id="${product.id}">
                            <i class="far fa-heart"></i>
                        </button>
                        <button class="add-to-cart-icon" data-id="${product.id}">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                <div class="product-info">
                    <h3>${product.name}</h3>
                    <p class="price">₱${formattedPrice}</p>
                    <p class="category">${product.category}</p>
                    <div class="product-rating">
                        <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                        <span class="rating-text">(${product.rating})</span>
                    </div>
                    <button class="add-to-cart" data-id="${product.id}">Add to Cart</button>
                </div>
            `;
            
            productsGrid.appendChild(productCard);
        });
        
        // Add the section to the page - after featured products, before footer
        const featuredSection = document.getElementById('featured');
        const footer = document.querySelector('footer');
        
        if (footer) {
            document.body.insertBefore(allProductsSection, footer);
        } else {
            document.body.appendChild(allProductsSection);
        }
        
        // Add event listeners to buttons
        addAllProductsEventListeners(allProductsSection);
    }
}

// Add event listeners to buttons in all products section
function addAllProductsEventListeners(section) {
    // Add to cart buttons
    const addToCartButtons = section.querySelectorAll('.add-to-cart, .add-to-cart-icon');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
    
    // Quick view buttons
    const quickViewButtons = section.querySelectorAll('.quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // Wishlist buttons
    const wishlistButtons = section.querySelectorAll('.add-to-wishlist');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.getAttribute('data-id');
            addToWishlist(productId);
        });
    });
    
    // Make product cards clickable
    const productCards = section.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('button')) {
                const productId = this.getAttribute('data-id');
                showQuickView(productId);
            }
        });
    });
}

// Add styles for all products section
function addAllProductsStyles() {
    if (!document.getElementById('allProductsStyles')) {
        const style = document.createElement('style');
        style.id = 'allProductsStyles';
        style.textContent = `
            .all-products-section {
                padding: 40px 0;
                background-color: #f9f9f9;
            }
            
            .all-products-section .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 15px;
            }
            
            .all-products-section h2 {
                text-align: center;
                margin-bottom: 30px;
                font-size: 28px;
                color: #333;
            }
            
            .products-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 25px;
            }
            
            @media (max-width: 768px) {
                .products-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
            
            @media (max-width: 480px) {
                .products-grid {
                    grid-template-columns: 1fr;
                }
            }
        `;
        document.head.appendChild(style);
    }
}

// Function to handle home navigation
function setupHomeNavigation() {
    // Get the home link from main navigation
    const homeLink = document.querySelector('#mainNav a.active');
    
    // If home link is found and it's active
    if (homeLink && homeLink.textContent.trim() === 'HOME') {
        // Load all products when on home page
        displayAllProducts();
    }
    
    // Add click event to all navigation links
    const navLinks = document.querySelectorAll('#mainNav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // If this is the HOME link
            if (this.textContent.trim() === 'HOME') {
                // Remove active class from all links
                navLinks.forEach(nav => nav.classList.remove('active'));
                // Add active class to this link
                this.classList.add('active');
                
                // Hide category results if visible
                const categoryResults = document.getElementById('categoryResults');
                if (categoryResults) {
                    categoryResults.style.display = 'none';
                }
                
                // Hide search results if visible
                const searchResults = document.getElementById('searchResults');
                if (searchResults) {
                    searchResults.style.display = 'none';
                }
                
                // Show all products
                displayAllProducts();
                
                // Show featured products section
                const featuredSection = document.getElementById('featured');
                if (featuredSection) {
                    featuredSection.style.display = 'block';
                }
            }
        });
    });
}

// Update the document ready function to include our new functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add styles for all products section
    addAllProductsStyles();
    
    // Load product data and other initial setup
    loadProductData();
    updateCartCount();
    
    // Set up navigation handling
    setTimeout(setupHomeNavigation, 1000);
    
    // Other existing initialization code...
    document.querySelector('.search-bar button').addEventListener('click', searchProducts);
    
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    setTimeout(() => {
        loadFeaturedProducts();
        setTimeout(initializeProductModals, 300);
    }, 500);
    
    setTimeout(generateCategoryNav, 700);
});