// Function to show the shopping cart modal
function showShoppingCart() {
    // Get current cart from local storage
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    
    // Create modal container if it doesn't exist
    let cartModal = document.getElementById('shoppingCartModal');
    if (!cartModal) {
        cartModal = document.createElement('div');
        cartModal.id = 'shoppingCartModal';
        cartModal.className = 'cart-modal-overlay';
        document.body.appendChild(cartModal);
    }
    
    // Calculate cart totals
    const cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    const itemCount = cart.reduce((count, item) => count + item.quantity, 0);
    
    // Check if user is logged in
    const isLoggedIn = localStorage.getItem('loggedIn') === 'true';
    
    // Generate HTML for cart modal
    cartModal.innerHTML = `
        <div class="cart-modal-content">
            <div class="cart-header">
                <h2>Shopping Cart (${itemCount} ${itemCount === 1 ? 'item' : 'items'})</h2>
                <button class="cart-close">&times;</button>
            </div>
            
            ${cart.length === 0 ? `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <button class="continue-shopping">Continue Shopping</button>
                </div>
            ` : `
                <div class="cart-items-container">
                    <div class="cart-selection-header">
                        <label class="select-all-container">
                            <input type="checkbox" id="selectAllItems" class="select-all-checkbox">
                            <span class="select-all-text">Select All</span>
                        </label>
                        <button class="delete-selected" disabled>Delete Selected</button>
                    </div>
                    
                    <div class="cart-items">
                        ${cart.map((item, index) => `
                            <div class="cart-item" data-index="${index}">
                                <div class="item-selection">
                                    <input type="checkbox" class="item-checkbox">
                                </div>
                                <div class="item-image">
                                    <img src="${item.image}" alt="${item.name}" onerror="this.src='images/placeholder.jpg'">
                                </div>
                                <div class="item-details">
                                    <h3 class="item-name">${item.name}</h3>
                                    <div class="item-options">
                                        ${item.size ? `<span class="item-size">Size: ${item.size}</span>` : ''}
                                        ${item.color ? `<span class="item-color">Color: ${item.color}</span>` : ''}
                                    </div>
                                    <div class="item-price">₱${parseFloat(item.price).toLocaleString()}</div>
                                    
                                    <div class="item-actions">
                                        <div class="quantity-control">
                                            <button class="quantity-btn item-minus" data-index="${index}">−</button>
                                            <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-index="${index}">
                                            <button class="quantity-btn item-plus" data-index="${index}">+</button>
                                        </div>
                                        <button class="item-remove" data-index="${index}">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="item-subtotal">
                                    ₱${(item.price * item.quantity).toLocaleString()}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="cart-footer">
                    <div class="cart-totals">
                        <div class="subtotal-row">
                            <span>Subtotal:</span>
                            <span class="cart-subtotal">₱${cartTotal.toLocaleString()}</span>
                        </div>
                        <div class="shipping-row">
                            <span>Shipping:</span>
                            <span class="cart-shipping">${cartTotal >= 1000 ? 'FREE' : '₱150.00'}</span>
                        </div>
                        <div class="total-row">
                            <span>Total:</span>
                            <span class="cart-total">₱${(cartTotal + (cartTotal >= 1000 ? 0 : 150)).toLocaleString()}</span>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <button class="continue-shopping">Continue Shopping</button>
                        <a href="sign-in.php"><button class="checkout-button">Proceed to Checkout</button></a>
                    </div>
                </div>
                
                ${!isLoggedIn ? `
                <div class="login-notice">
                    <p>Please <a href="sign-in.php" class="login-link">sign in</a> or <a href="sign-up.php" class="signup-link">register</a> to proceed with your purchase.</p>
                </div>
                ` : ''}
            `}
        </div>
    `;
    
    // Show the modal
    cartModal.style.display = 'flex';
    document.body.classList.add('modal-open');
    
    // Add event listeners
    addCartModalEventListeners(cartModal, cart, isLoggedIn);
}

// Add event listeners for cart modal functionality
function addCartModalEventListeners(modal, cart, isLoggedIn) {
    // Close button
    const closeBtn = modal.querySelector('.cart-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
    }
    
    // Close on click outside the modal content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
    
    // Continue shopping button
    const continueShoppingBtns = modal.querySelectorAll('.continue-shopping');
    continueShoppingBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        });
    });
    
    // If cart is empty, no need for further event listeners
    if (cart.length === 0) return;
    
    // Quantity controls
    const minusBtns = modal.querySelectorAll('.item-minus');
    const plusBtns = modal.querySelectorAll('.item-plus');
    const quantityInputs = modal.querySelectorAll('.quantity-input');
    
    minusBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(btn.getAttribute('data-index'));
            if (cart[index].quantity > 1) {
                cart[index].quantity--;
                updateCart(cart);
                updateCartUI(modal, cart);
            }
        });
    });
    
    plusBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const index = parseInt(btn.getAttribute('data-index'));
            cart[index].quantity++;
            updateCart(cart);
            updateCartUI(modal, cart);
        });
    });
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', () => {
            const index = parseInt(input.getAttribute('data-index'));
            const newQuantity = parseInt(input.value);
            
            if (newQuantity < 1) {
                input.value = 1;
                cart[index].quantity = 1;
            } else {
                cart[index].quantity = newQuantity;
            }
            
            updateCart(cart);
            updateCartUI(modal, cart);
        });
    });
    
    // Individual item remove buttons
    const removeButtons = modal.querySelectorAll('.item-remove');
    removeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const index = parseInt(button.getAttribute('data-index'));
            cart.splice(index, 1);
            updateCart(cart);
            
            // If cart is now empty, refresh the entire modal
            if (cart.length === 0) {
                showShoppingCart();
            } else {
                updateCartUI(modal, cart);
            }
        });
    });
    
    // Checkboxes for multiple selection
    const itemCheckboxes = modal.querySelectorAll('.item-checkbox');
    const selectAllCheckbox = modal.querySelector('#selectAllItems');
    const deleteSelectedBtn = modal.querySelector('.delete-selected');
    
    // Item checkboxes
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            updateDeleteButtonState();
            
            // Check if all items are selected
            const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        });
    });
    
    // Select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            const isChecked = selectAllCheckbox.checked;
            
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            updateDeleteButtonState();
        });
    }
    
    // Delete selected button
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', () => {
            // Get indices of checked items (in reverse order to avoid index shifting)
            const indices = [];
            itemCheckboxes.forEach((checkbox, i) => {
                if (checkbox.checked) {
                    indices.unshift(i); // Add to beginning of array to delete from end first
                }
            });
            
            // Remove selected items
            indices.forEach(index => {
                cart.splice(index, 1);
            });
            
            updateCart(cart);
            
            // If cart is now empty, refresh the entire modal
            if (cart.length === 0) {
                showShoppingCart();
            } else {
                updateCartUI(modal, cart);
            }
        });
    }
    
    // Function to update delete button state
    function updateDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        
        if (deleteSelectedBtn) {
            deleteSelectedBtn.disabled = !anyChecked;
        }
    }
    
    // Checkout button
    const checkoutBtn = modal.querySelector('.checkout-button');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            if (isLoggedIn) {
                // If user is logged in, proceed to checkout
                window.location.href = 'checkout.html';
            } else {
                // If user is not logged in, redirect to sign in page
                window.location.href = 'sign-in.php?redirect=checkout';
            }
        });
    }
    
    // Direct login and signup links
    const loginLink = modal.querySelector('.login-link');
    if (loginLink) {
        loginLink.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'sign-in.php?redirect=checkout';
        });
    }
    
    const signupLink = modal.querySelector('.signup-link');
    if (signupLink) {
        signupLink.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = 'sign-up.php?redirect=checkout';
        });
    }
}

// Update cart data in localStorage
function updateCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

// Update cart UI without refreshing the entire modal
function updateCartUI(modal, cart) {
    // Update item count in header
    const itemCount = cart.reduce((count, item) => count + item.quantity, 0);
    const cartHeader = modal.querySelector('.cart-header h2');
    if (cartHeader) {
        cartHeader.textContent = `Shopping Cart (${itemCount} ${itemCount === 1 ? 'item' : 'items'})`;
    }
    
    // Update each item subtotal
    cart.forEach((item, index) => {
        const itemSubtotal = modal.querySelector(`.cart-item[data-index="${index}"] .item-subtotal`);
        if (itemSubtotal) {
            itemSubtotal.textContent = `₱${(item.price * item.quantity).toLocaleString()}`;
        }
        
        const quantityInput = modal.querySelector(`.cart-item[data-index="${index}"] .quantity-input`);
        if (quantityInput) {
            quantityInput.value = item.quantity;
        }
    });
    
    // Update totals in footer
    const cartTotal = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    
    const subtotalElement = modal.querySelector('.cart-subtotal');
    if (subtotalElement) {
        subtotalElement.textContent = `₱${cartTotal.toLocaleString()}`;
    }
    
    const shippingElement = modal.querySelector('.cart-shipping');
    if (shippingElement) {
        shippingElement.textContent = cartTotal >= 1000 ? 'FREE' : '₱150.00';
    }
    
    const totalElement = modal.querySelector('.cart-total');
    if (totalElement) {
        totalElement.textContent = `₱${(cartTotal + (cartTotal >= 1000 ? 0 : 150)).toLocaleString()}`;
    }
}

// Check if user is logged in
function isUserLoggedIn() {
    return localStorage.getItem('loggedIn') === 'true';
}

// Add styles for cart modal
function addCartModalStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        /* Cart Modal Overlay */
        .cart-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: flex-start;
            overflow-y: auto;
            padding: 30px 0;
        }
        
        /* Cart Modal Content */
        .cart-modal-content {
            background-color: #fff;
            width: 90%;
            max-width: 1000px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        /* Cart Header */
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .cart-header h2 {
            font-size: 20px;
            margin: 0;
        }
        
        .cart-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Empty Cart */
        .empty-cart {
            padding: 50px 20px;
            text-align: center;
        }
        
        .empty-cart i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .empty-cart p {
            font-size: 18px;
            color: #777;
            margin-bottom: 20px;
        }
        
        /* Cart Items Container */
        .cart-items-container {
            padding: 0;
            max-height: 50vh;
            overflow-y: auto;
        }
        
        /* Cart Selection Header */
        .cart-selection-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        
        .select-all-container {
            display: flex;
            align-items: center;
        }
        
        .select-all-checkbox {
            margin-right: 10px;
        }
        
        .delete-selected {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            opacity: 1;
            transition: opacity 0.2s;
        }
        
        .delete-selected:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Cart Items */
        .cart-items {
            padding: 0;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .item-selection {
            flex: 0 0 30px;
        }
        
        .item-image {
            flex: 0 0 80px;
            margin-right: 15px;
        }
        
        .item-image img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-size: 16px;
            margin: 0 0 5px 0;
        }
        
        .item-options {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }
        
        .item-size, .item-color {
            margin-right: 15px;
        }
        
        .item-price {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .item-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .item-remove {
            background: none;
            border: none;
            color: #777;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .item-remove i {
            margin-right: 5px;
        }
        
        .item-remove:hover {
            color: #f44336;
        }
        
        .item-subtotal {
            flex: 0 0 100px;
            text-align: right;
            font-weight: bold;
            font-size: 16px;
        }
        
        /* Quantity Control in Cart */
        .quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-btn {
            width: 28px;
            height: 28px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 40px;
            height: 28px;
            border: 1px solid #ddd;
            border-left: none;
            border-right: none;
            text-align: center;
            font-size: 14px;
        }
        
        /* Cart Footer */
        .cart-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            background-color: #f9f9f9;
        }
        
        .cart-totals {
            margin-bottom: 20px;
        }
        
        .subtotal-row, .shipping-row, .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .total-row {
            font-size: 18px;
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .continue-shopping, .checkout-button {
            flex: 1;
            padding: 12px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
        }
        
        .continue-shopping {
            background-color: white;
            border: 1px solid #ddd;
            color: #333;
        }
        
        .checkout-button {
            background-color: #4CAF50;
            border: none;
            color: white;
        }
        
        /* Login Notice */
        .login-notice {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 14px;
        }
        
        .login-notice a {
            color: #4CAF50;
            font-weight: bold;
            text-decoration: none;
        }
        
        .login-notice a:hover {
            text-decoration: underline;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .cart-item {
                flex-wrap: wrap;
            }
            
            .item-selection {
                flex: 0 0 30px;
            }
            
            .item-image {
                flex: 0 0 60px;
            }
            
            .item-details {
                flex: 1;
            }
            
            .item-subtotal {
                flex: 0 0 100%;
                text-align: right;
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px dashed #eee;
            }
            
            .cart-actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .cart-selection-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .item-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .item-remove {
                margin-top: 10px;
            }
        }
    `;
    document.head.appendChild(styleElement);
}

// Update initialization to include cart icon click event
document.addEventListener('DOMContentLoaded', function() {
    loadProductData();
    updateCartCount();
    
    // Add the cart modal styles
    addCartModalStyles();
    
    // Add event listener for search button
    document.querySelector('.search-bar button').addEventListener('click', searchProducts);
    
    // Add event listener for Enter key in search input
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    
    // Add event listener for cart icon
    const cartIcon = document.querySelector('.cart-icon, #cartIcon, a[href*="cart"], header .fa-shopping-cart');
    if (cartIcon) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            showShoppingCart();
        });
    }
    
    // Load featured products after a slight delay to ensure XML is loaded
    setTimeout(loadFeaturedProducts, 500);
});