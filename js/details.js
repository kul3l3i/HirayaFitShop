 // Function to view product details in a modal like the screenshot
 function viewProductDetails(productId) {
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
                        <span class="rating-text">(${product.rating} - ${product.review_count || 'undefined'} reviews)</span>
                    </div>
                    
                    <!-- Product Description -->
                    <div class="modal-product-description">
                        <p>${product.description || 'Breathable, sweat-wicking compression shirt for workouts'}</p>
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
            viewProductDetails(productId);
        });
    });
    
    // For search results
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
                        viewProductDetails(productId);
                    });
                });
            }
        });
    });
    
    // Start observing the document body for search results
    observer.observe(document.body, { childList: true, subtree: true });
}

// Update initialization to include view details listeners and styles
document.addEventListener('DOMContentLoaded', function() {
    loadProductData();
    updateCartCount();
    
    // Add the modal styles
    addProductDetailsModalStyles();
    
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
        // Add view details event listeners after featured products are loaded
        setTimeout(addViewDetailsEventListeners, 100);
    }, 500);
});