// Updated category filtering function to match search results style
function setupCategoryNavigation() {
    // Get all navigation links
    const navLinks = document.querySelectorAll('.main-nav a');
    
    // Add click event listeners to each nav link
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Get category from link text or href
            let category;
            
            // Extract category from href (filename without extension)
            if (link.getAttribute('href')) {
                category = link.getAttribute('href').split('.')[0];
            }
            
            // Skip for about and contact pages - don't load products
            if (category === 'about' || category === 'contact' || category === 'index' || category === 'home') {
                return; // Let default navigation happen without filtering products
            }
            
            // Prevent default navigation
            e.preventDefault();
            
            // Handle special case for accessories shorthand
            if (category === 'acces') {
                category = 'accessories';
            }
            
            // Filter products by the selected category
            filterByCategory(category);
            
            // Update active state in navigation
            navLinks.forEach(navLink => navLink.classList.remove('active'));
            link.classList.add('active');
            
            // Update browser history (optional - for proper back button support)
            history.pushState({category: category}, '', link.getAttribute('href'));
        });
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.category) {
            // Skip filtering products for non-product pages
            if (event.state.category === 'about' || event.state.category === 'contact' || 
                event.state.category === 'index' || event.state.category === 'home') {
                // Hide any product sections that might be visible
                hideProductSections();
                return;
            }
            
            filterByCategory(event.state.category);
            
            // Update active state in navigation
            const navLinks = document.querySelectorAll('.main-nav a');
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes(event.state.category)) {
                    link.classList.add('active');
                }
            });
        }
    });
}

// Function to hide product sections
function hideProductSections() {
    // Hide featured section if visible
    const featuredSection = document.getElementById('featured');
    if (featuredSection) {
        featuredSection.style.display = 'none';
    }
    
    // Hide category products section if visible
    const categorySection = document.getElementById('categoryProducts');
    if (categorySection) {
        categorySection.style.display = 'none';
    }
    
    // Hide search results if visible
    const searchResults = document.getElementById('searchResults');
    if (searchResults) {
        searchResults.style.display = 'none';
    }
}

// Filter products by category - Updated to match search results style
function filterByCategory(category) {
    // Skip filtering for non-product pages
    if (category === 'about' || category === 'contact' || category === 'home' || category === 'index') {
        hideProductSections();
        return;
    }
    
    // Check if products are loaded
    if (products.length === 0) {
        // If not loaded yet, set a timeout to try again
        setTimeout(() => filterByCategory(category), 500);
        return;
    }
    
    // Get category display name for heading
    let displayCategory = category.charAt(0).toUpperCase() + category.slice(1).toLowerCase();
    
    // Clear any existing search results
    const searchResults = document.getElementById('searchResults');
    if (searchResults) {
        searchResults.style.display = 'none';
    }
    
    // Filter products based on category
    let filteredProducts = [];
    
    switch(category.toLowerCase()) {
        case 'shop':
            // On shop page, show all products
            filteredProducts = [...products];
            displayCategory = 'All Products';
            break;
            
        case 'men':
            // Filter for men's products
            filteredProducts = products.filter(product => {
                const productData = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
                return productData.includes('men') && !productData.includes('women');
            });
            displayCategory = 'Men\'s Products';
            break;
            
        case 'women':
            // Filter for women's products
            filteredProducts = products.filter(product => {
                const productData = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
                return productData.includes('women');
            });
            displayCategory = 'Women\'s Products';
            break;
            
        case 'foot':
        case 'footwear':
            // Filter for footwear products
            filteredProducts = products.filter(product => {
                const productData = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
                return productData.includes('shoe') || 
                       productData.includes('footwear') || 
                       productData.includes('sneaker') || 
                       productData.includes('boot') ||
                       productData.includes('sandal');
            });
            displayCategory = 'Footwear';
            break;
            
        case 'acces':
        case 'accessories':
            // Filter for accessories
            filteredProducts = products.filter(product => {
                const productData = (product.name + ' ' + product.category + ' ' + product.description).toLowerCase();
                return productData.includes('accessory') || 
                       productData.includes('accessories') ||
                       productData.includes('bag') ||
                       productData.includes('hat') ||
                       productData.includes('glove') ||
                       productData.includes('belt') ||
                       productData.includes('watch');
            });
            displayCategory = 'Accessories';
            break;
            
        default:
            // Default to all products if category not recognized
            filteredProducts = [...products];
            displayCategory = 'Products';
    }
    
    // Display the filtered products using the search results style
    displayCategoryProducts(filteredProducts, displayCategory);
}

// Function to display category filtered products - Updated to match search results style
function displayCategoryProducts(filteredProducts, categoryName) {
    // Hide featured section if visible
    const featuredSection = document.getElementById('featured');
    if (featuredSection) {
        featuredSection.style.display = 'none';
    }
    
    // Create or get the product container section - using search-results-section class for styling
    let productSection = document.getElementById('categoryProducts');
    
    // If section doesn't exist, create it
    if (!productSection) {
        productSection = document.createElement('section');
        productSection.id = 'categoryProducts';
        productSection.className = 'search-results-section'; // Use the search results styling
        
        // Add it to the page before the footer or at the end of body
        const footer = document.querySelector('footer');
        if (footer) {
            document.body.insertBefore(productSection, footer);
        } else {
            document.body.appendChild(productSection);
        }
    }
    
    // Clear previous results
    productSection.innerHTML = '';
    
    // Create container for products
    const container = document.createElement('div');
    container.className = 'container';
    productSection.appendChild(container);
    
    // Add section heading with styling from search results
    const heading = document.createElement('h2');
    heading.textContent = categoryName;
    container.appendChild(heading);
    
    // If no products found
    if (filteredProducts.length === 0) {
        const noProducts = document.createElement('p');
        noProducts.className = 'no-results'; // Use search results class
        noProducts.textContent = 'No products found in this category.';
        container.appendChild(noProducts);
        productSection.style.display = 'block';
        return;
    }
    
    // Create products grid with search results class
    const productsGrid = document.createElement('div');
    productsGrid.className = 'results-grid'; // Use search results class
    container.appendChild(productsGrid);
    
    // Add each product to the grid
    filteredProducts.forEach(product => {
        // Format price with commas for thousands
        const formattedPrice = parseFloat(product.price).toLocaleString();
        
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                ${product.on_sale === 'true' ? '<span class="sale-badge">SALE</span>' : ''}
            </div>
            <div class="product-info">
                <h3>${product.name}</h3>
                <p class="price">₱${formattedPrice}</p>
                <div class="product-rating">
                    <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                    <span class="rating-text">(${product.rating})</span>
                </div>
                <button class="add-to-cart" data-id="${product.id}">ADD TO CART</button>
            </div>
        `;
        
        productsGrid.appendChild(productCard);
    });
    
    // Make sure the section is visible
    productSection.style.display = 'block';
    
    // Scroll to the product section
    productSection.scrollIntoView({ behavior: 'smooth' });
    
    // Add event listeners to buttons
    addCategoryProductsEventListeners();
}

// Add event listeners to category products buttons
function addCategoryProductsEventListeners() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('#categoryProducts .add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
}

// Update initialization to include navigation setup
document.addEventListener('DOMContentLoaded', function() {
    loadProductData();
    updateCartCount();
    
    // Setup navigation filtering
    setupCategoryNavigation();
    
    // Add event listener for search button
    const searchButton = document.querySelector('.search-bar button');
    if (searchButton) {
        searchButton.addEventListener('click', searchProducts);
    }
    
    // Add event listener for Enter key in search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
    
    // Check which page we're on and trigger appropriate filtering
    const currentPath = window.location.pathname;
    const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    let currentPage = filename.split('.')[0];
    
    // If no filename is found, default to index/home
    if (!currentPage || currentPage === '') {
        currentPage = 'index';
    }
    
    // Set initial state for browser history
    history.replaceState({category: currentPage}, '', filename);
    
    // Only load filtered products if we're on a product-related page
    if (currentPage === 'shop' || 
        currentPage === 'men' || 
        currentPage === 'women' || 
        currentPage === 'footwear' || 
        currentPage === 'foot' || 
        currentPage === 'accessories' || 
        currentPage === 'acces') {
        
        // Special case for accessories shorthand
        if (currentPage === 'acces') currentPage = 'accessories';
        
        // Delay filtering to ensure XML data is loaded
        setTimeout(() => filterByCategory(currentPage), 600);
    } 
    // For home page, potentially load featured products if needed
    else if (currentPage === 'index') {
        // Load only featured products for homepage
        setTimeout(loadFeaturedProducts, 500);
    }
    // For about, contact or other pages - don't load any products
    else {
        hideProductSections();
    }
});

////////////////////////////////////////////////////////////////////////////////////////
// Function to display category filtered products - Updated to match search results style
function displayCategoryProducts(filteredProducts, categoryName) {
    // Hide featured section if visible
    const featuredSection = document.getElementById('featured');
    if (featuredSection) {
        featuredSection.style.display = 'none';
    }
    
    // Create or get the product container section - using search-results-section class for styling
    let productSection = document.getElementById('categoryProducts');
    
    // If section doesn't exist, create it
    if (!productSection) {
        productSection = document.createElement('section');
        productSection.id = 'categoryProducts';
        productSection.className = 'search-results-section'; // Use the search results styling
        
        // Add it to the page before the footer or at the end of body
        const footer = document.querySelector('footer');
        if (footer) {
            document.body.insertBefore(productSection, footer);
        } else {
            document.body.appendChild(productSection);
        }
    }
    
    // Clear previous results
    productSection.innerHTML = '';
    
    // Create container for products
    const container = document.createElement('div');
    container.className = 'container';
    productSection.appendChild(container);
    
    // Add section heading with styling from search results
    const heading = document.createElement('h2');
    heading.textContent = categoryName;
    container.appendChild(heading);
    
    // If no products found
    if (filteredProducts.length === 0) {
        const noProducts = document.createElement('p');
        noProducts.className = 'no-results'; // Use search results class
        noProducts.textContent = 'No products found in this category.';
        container.appendChild(noProducts);
        productSection.style.display = 'block';
        return;
    }
    
    // Create products grid with search results class
    const productsGrid = document.createElement('div');
    productsGrid.className = 'results-grid'; // Use search results class
    container.appendChild(productsGrid);
    
    // Add each product to the grid
    filteredProducts.forEach(product => {
        // Format price with commas for thousands
        const formattedPrice = parseFloat(product.price).toLocaleString();
        
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.dataset.productId = product.id; // Add product ID as data attribute
        productCard.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                ${product.on_sale === 'true' ? '<span class="sale-badge">SALE</span>' : ''}
            </div>
            <div class="product-info">
                <h3>${product.name}</h3>
                <p class="price">₱${formattedPrice}</p>
                <div class="product-rating">
                    <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                    <span class="rating-text">(${product.rating})</span>
                </div>
                <button class="add-to-cart" data-id="${product.id}">ADD TO CART</button>
            </div>
        `;
        
        productsGrid.appendChild(productCard);
    });
    
    // Make sure the section is visible
    productSection.style.display = 'block';
    
    // Scroll to the product section
    productSection.scrollIntoView({ behavior: 'smooth' });
    
    // Add event listeners to buttons and product cards
    addCategoryProductsEventListeners();
}

// Add event listeners to category products buttons and cards
function addCategoryProductsEventListeners() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('#categoryProducts .add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the card click
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
    
    // Make entire product card clickable to open quick view
    const productCards = document.querySelectorAll('#categoryProducts .product-card');
    productCards.forEach(card => {
        card.addEventListener('click', function() {
            const productId = this.dataset.productId;
            showQuickView(productId);
        });
    });
}

// Function to show quick view modal
function showQuickView(productId) {
    // Find the product in our products array
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Format price with commas for thousands
    const formattedPrice = parseFloat(product.price).toLocaleString();
    
    // Create modal overlay if doesn't exist
    let modalOverlay = document.getElementById('productModalOverlay');
    if (!modalOverlay) {
        modalOverlay = document.createElement('div');
        modalOverlay.id = 'productModalOverlay';
        modalOverlay.className = 'product-modal-overlay';
        document.body.appendChild(modalOverlay);
    }
    
    // Create modal content
    modalOverlay.innerHTML = `
        <div class="product-modal-content">
            <button class="modal-close">&times;</button>
            <div class="modal-product-grid">
                <div class="modal-product-image">
                    <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
                </div>
                <div class="modal-product-details">
                    <h2 class="modal-product-title">${product.name}</h2>
                    <p class="modal-product-price">₱${formattedPrice}</p>
                    <div class="modal-product-rating">
                        <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                        <span class="rating-text">(${product.rating} - undefined reviews)</span>
                    </div>
                    <p class="modal-product-description">
                        ${product.description || 'Breathable, sweat-wicking compression shirt for workouts'}
                    </p>
                    <div class="modal-product-size">
                        <label>Size:</label>
                        <div class="size-options">
                            <button class="size-btn" data-size="S">S</button>
                            <button class="size-btn" data-size="M">M</button>
                            <button class="size-btn" data-size="L">L</button>
                            <button class="size-btn" data-size="XL">XL</button>
                        </div>
                    </div>
                    <div class="modal-product-color">
                        <label>Color:</label>
                        <div class="color-options">
                            <button class="color-btn" data-color="black" style="background-color: black;"></button>
                            <button class="color-btn" data-color="blue" style="background-color: blue;"></button>
                            <button class="color-btn" data-color="red" style="background-color: red;"></button>
                        </div>
                    </div>
                    <div class="modal-product-quantity">
                        <label>Quantity:</label>
                        <div class="quantity-control">
                            <button class="quantity-btn quantity-decrease">-</button>
                            <input type="text" class="quantity-input" value="1" min="1" max="99">
                            <button class="quantity-btn quantity-increase">+</button>
                        </div>
                    </div>
                    <button class="modal-add-to-cart" data-id="${product.id}">ADD TO CART</button>
                </div>
            </div>
        </div>
    `;
    
    // Display the modal
    modalOverlay.style.display = 'flex';
    document.body.classList.add('modal-open');
    
    // Add event listeners for the modal
    setupModalEventListeners(modalOverlay);
}

// Setup event listeners for the modal
function setupModalEventListeners(modalOverlay) {
    // Close button
    const closeButton = modalOverlay.querySelector('.modal-close');
    closeButton.addEventListener('click', closeModal);
    
    // Size buttons
    const sizeButtons = modalOverlay.querySelectorAll('.size-btn');
    sizeButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all size buttons
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
        });
    });
    
    // Color buttons
    const colorButtons = modalOverlay.querySelectorAll('.color-btn');
    colorButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all color buttons
            colorButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
        });
    });
    
    // Quantity decrease button
    const decreaseBtn = modalOverlay.querySelector('.quantity-decrease');
    decreaseBtn.addEventListener('click', function() {
        const input = modalOverlay.querySelector('.quantity-input');
        let value = parseInt(input.value);
        if (value > 1) {
            input.value = value - 1;
        }
    });
    
    // Quantity increase button
    const increaseBtn = modalOverlay.querySelector('.quantity-increase');
    increaseBtn.addEventListener('click', function() {
        const input = modalOverlay.querySelector('.quantity-input');
        let value = parseInt(input.value);
        if (value < 99) {
            input.value = value + 1;
        }
    });
    
    // Add to cart button
    const addToCartButton = modalOverlay.querySelector('.modal-add-to-cart');
    addToCartButton.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        const quantity = parseInt(modalOverlay.querySelector('.quantity-input').value);
        
        // Get selected size and color
        const selectedSize = modalOverlay.querySelector('.size-btn.active')?.getAttribute('data-size') || 'M';
        const selectedColor = modalOverlay.querySelector('.color-btn.active')?.getAttribute('data-color') || 'black';
        
        // Add to cart with options
        addToCart(productId, quantity, selectedSize, selectedColor);
        
        // Close modal after adding to cart
        closeModal();
        
        // Show confirmation message
        showAddToCartConfirmation();
    });
    
    // Close modal when clicking outside the content
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
    
    // Select first size and color option by default
    if (sizeButtons.length > 0) sizeButtons[0].classList.add('active');
    if (colorButtons.length > 0) colorButtons[0].classList.add('active');
}

// Function to close the modal
function closeModal() {
    const modalOverlay = document.getElementById('productModalOverlay');
    if (modalOverlay) {
        modalOverlay.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

// Function to show add to cart confirmation
function showAddToCartConfirmation() {
    // Create confirmation element
    let confirmation = document.querySelector('.add-to-cart-confirmation');
    
    if (!confirmation) {
        confirmation = document.createElement('div');
        confirmation.className = 'add-to-cart-confirmation';
        document.body.appendChild(confirmation);
    }
    
    // Set content
    confirmation.innerHTML = `
        <div class="confirmation-content">
            <i class="fas fa-check-circle"></i>
            <span>Product added to cart successfully!</span>
        </div>
    `;
    
    // Display confirmation
    confirmation.style.opacity = '1';
    confirmation.style.display = 'block';
    
    // Hide after 3 seconds
    setTimeout(() => {
        confirmation.classList.add('fade-out');
        setTimeout(() => {
            confirmation.style.display = 'none';
            confirmation.classList.remove('fade-out');
        }, 500);
    }, 3000);
}

// Modify the addToCart function to handle options
function addToCart(productId, quantity = 1, size = null, color = null) {
    // Find the product
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Get cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Check if product already in cart with same options
    const existingItemIndex = cart.findIndex(item => 
        item.id === productId && 
        item.size === size && 
        item.color === color
    );
    
    if (existingItemIndex > -1) {
        // Update quantity if product already in cart
        cart[existingItemIndex].quantity += quantity;
    } else {
        // Add new product to cart
        cart.push({
            id: productId,
            name: product.name,
            price: product.price,
            image: product.image,
            quantity: quantity,
            size: size,
            color: color
        });
    }
    
    // Save updated cart to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update cart count in UI
    updateCartCount();
}

// Function to update cart count badge
function updateCartCount() {
    const cartCountElement = document.querySelector('.cart-count');
    if (!cartCountElement) return;
    
    // Get cart from localStorage
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Calculate total items
    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
    
    // Update UI
    cartCountElement.textContent = totalItems;
    
    // Show/hide based on count
    if (totalItems > 0) {
        cartCountElement.style.display = 'flex';
    } else {
        cartCountElement.style.display = 'none';
    }
}

// Add CSS for the modal to the page
function addModalStyles() {
    // Check if the styles are already added
    if (document.getElementById('modalStyles')) return;
    
    const styleElement = document.createElement('style');
    styleElement.id = 'modalStyles';
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
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .modal-add-to-cart:hover {
            background-color: #2980b9;
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

// Update DOMContentLoaded to include the modal styles
document.addEventListener('DOMContentLoaded', function() {
    loadProductData();
    updateCartCount();
    
    // Add modal styles
    addModalStyles();
    
    // Setup navigation filtering
    setupCategoryNavigation();
    
    // Add event listener for search button
    const searchButton = document.querySelector('.search-bar button');
    if (searchButton) {
        searchButton.addEventListener('click', searchProducts);
    }
    
    // Add event listener for Enter key in search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
    
    // Check which page we're on and trigger appropriate filtering
    const currentPath = window.location.pathname;
    const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    let currentPage = filename.split('.')[0];
    
    // Set default category based on current page
    if (currentPage) {
        // Set initial state for browser history
        history.replaceState({category: currentPage}, '', filename);
        
        // Special cases
        if (currentPage === 'index') currentPage = 'home';
        if (currentPage === 'acces') currentPage = 'accessories';
        
        // Delay filtering to ensure XML data is loaded
        setTimeout(() => filterByCategory(currentPage), 600);
    } else {
        // If no specific page, load featured products
        setTimeout(loadFeaturedProducts, 500);
    }
});




