 // Load and parse XML data
 let productData;
 let products = [];
 
 // Function to load the XML data
 function loadProductData() {
     const xhr = new XMLHttpRequest();
     xhr.onreadystatechange = function() {
         if (this.readyState == 4 && this.status == 200) {
             productData = this.responseXML;
             parseProductData();
         }
     };
     xhr.open("GET", "product.xml", true);
     xhr.send();
 }
 
 // Parse XML into a more usable format
 function parseProductData() {
     if (!productData) return;
     
     const productElements = productData.getElementsByTagName("product");
     products = [];
     
     for (let i = 0; i < productElements.length; i++) {
         const product = productElements[i];
         const productObj = {
             id: getElementTextContent(product, "id"),
             name: getElementTextContent(product, "name"),
             category: getElementTextContent(product, "category"),
             price: getElementTextContent(product, "price"),
             description: getElementTextContent(product, "description"),
             image: getElementTextContent(product, "image"),
             stock: getElementTextContent(product, "stock"),
             rating: getElementTextContent(product, "rating"),
             featured: getElementTextContent(product, "featured"),
             on_sale: getElementTextContent(product, "on_sale")
         };
         
         // Get sizes
         const sizeElements = product.getElementsByTagName("size");
         productObj.sizes = [];
         for (let j = 0; j < sizeElements.length; j++) {
             productObj.sizes.push(sizeElements[j].textContent);
         }
         
         // Get colors
         const colorElements = product.getElementsByTagName("color");
         productObj.colors = [];
         for (let j = 0; j < colorElements.length; j++) {
             productObj.colors.push(colorElements[j].textContent);
         }
         
         products.push(productObj);
     }
     
     console.log("Products loaded:", products.length);
 }
 
 // Helper function to get text content of an element
 function getElementTextContent(parent, tagName) {
     const elements = parent.getElementsByTagName(tagName);
     if (elements.length > 0) {
         return elements[0].textContent;
     }
     return "";
 }
 

 ////////////
 // Search function that looks through the products array
// Search function that looks through the products array
function searchProducts() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    if (!searchInput.trim()) {
        displaySearchResults([]);
        return;
    }
    
    const results = products.filter(product => {
        // Search in various fields
        return (
            product.name.toLowerCase().includes(searchInput) ||
            product.category.toLowerCase().includes(searchInput) ||
            product.description.toLowerCase().includes(searchInput) ||
            product.colors.some(color => color.toLowerCase().includes(searchInput))
        );
    });
    
    displaySearchResults(results);
    
    // Scroll to search results
    const resultsSection = document.getElementById('searchResults');
    if (resultsSection) {
        resultsSection.scrollIntoView({ behavior: 'smooth' });
    }
}

// Display search results - Updated to match category results style
function displaySearchResults(results) {
    let resultsContainer = document.getElementById('searchResults');
    
    // If results container doesn't exist, create it at the bottom of the page
    if (!resultsContainer) {
        // Create results container
        resultsContainer = document.createElement('section');
        resultsContainer.id = 'searchResults';
        resultsContainer.className = 'search-results-section';
        
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
    heading.textContent = 'Search Results';
    resultsContent.appendChild(heading);
    
    // If no results found
    if (results.length === 0) {
        const noResults = document.createElement('p');
        noResults.className = 'no-results';
        noResults.textContent = 'No products found.';
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
        resultsGrid.appendChild(productCard);
    });
    
    resultsContainer.style.display = 'block';
    
    // Add event listeners to the buttons
    addSearchResultsEventListeners();
}

// Add event listeners to search results buttons
function addSearchResultsEventListeners() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('#searchResults .add-to-cart, #searchResults .add-to-cart-icon');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
    
    // Quick view buttons
    const quickViewButtons = document.querySelectorAll('#searchResults .quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showQuickView(productId);
        });
    });
    
    // Add to wishlist buttons
    const wishlistButtons = document.querySelectorAll('#searchResults .add-to-wishlist');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToWishlist(productId);
        });
    });
}
 // Generate star rating HTML
 function generateStars(rating) {
     const fullStars = Math.floor(rating);
     const halfStar = rating % 1 >= 0.5;
     const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
     
     let starsHTML = '';
     
     // Full stars
     for (let i = 0; i < fullStars; i++) {
         starsHTML += '<i class="fas fa-star"></i>';
     }
     
     // Half star if needed
     if (halfStar) {
         starsHTML += '<i class="fas fa-star-half-alt"></i>';
     }
     
     // Empty stars
     for (let i = 0; i < emptyStars; i++) {
         starsHTML += '<i class="far fa-star"></i>';
     }
     
     return starsHTML;
 }
 
 // Function to add a product to the cart
 function addToCart(productId) {
     // Find the product in our data
     const product = products.find(p => p.id === productId);
     if (!product) return;
     
     // Get current cart from local storage or initialize empty cart
     let cart = JSON.parse(localStorage.getItem('cart')) || [];
     
     // Check if product already in cart
     const existingItem = cart.find(item => item.id === productId);
     
     if (existingItem) {
         existingItem.quantity++;
     } else {
         cart.push({
             id: productId,
             name: product.name,
             price: parseFloat(product.price),
             image: product.image,
             quantity: 1
         });
     }
     
     // Save updated cart to local storage
     localStorage.setItem('cart', JSON.stringify(cart));
     
     // Update cart count in the UI
     updateCartCount();
     
     // Show confirmation message
     alert(`${product.name} added to your cart!`);
 }
 
 // Update the cart count in the UI
 function updateCartCount() {
     const cart = JSON.parse(localStorage.getItem('cart')) || [];
     const count = cart.reduce((total, item) => total + item.quantity, 0);
     document.getElementById('cartCount').textContent = count;
 }
 
 // Initialize on page load
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
 });
 
 // Function to load featured products
 function loadFeaturedProducts() {
     // Check if products are already loaded
     if (products.length === 0) {
         // If not, set a small timeout to wait for products to load
         setTimeout(loadFeaturedProducts, 500);
         return;
     }
     
     // Filter for featured products
     const featuredProducts = products.filter(product => product.featured === 'true');
     
     // Display featured products
     displayFeaturedProducts(featuredProducts);
 }
 
 // Function to display featured products
 function displayFeaturedProducts(featuredProducts) {
     const featuredSection = document.getElementById('featured');
     
     if (!featuredSection) {
         console.error("Featured section not found in the DOM");
         return;
     }
     
     // Create container for featured products
     const container = document.createElement('div');
     container.className = 'container';
     
     // Add section heading
     const heading = document.createElement('h2');
     heading.className = 'section-title';
     heading.textContent = 'Featured Products';
     container.appendChild(heading);
     
     // Add section description
     const description = document.createElement('p');
     description.className = 'section-description';
     description.textContent = 'Discover our top picks for your fitness journey';
     container.appendChild(description);
     
     // Create products grid
     const productsGrid = document.createElement('div');
     productsGrid.className = 'products-grid';
     
     // Add featured products
     featuredProducts.forEach(product => {
         // Format price with commas for thousands
         const formattedPrice = parseFloat(product.price).toLocaleString();
         
         const productCard = document.createElement('div');
         productCard.className = 'product-card';
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
                 <div class="product-rating">
                     <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                     <span class="rating-text">(${product.rating})</span>
                 </div>
                 <button class="add-to-cart" data-id="${product.id}">Add to Cart</button>
             </div>
         `;
         
         productsGrid.appendChild(productCard);
     });
     
     container.appendChild(productsGrid);
     featuredSection.appendChild(container);
     
     // Add event listeners to buttons
     addFeaturedProductsEventListeners();
 }
 
 // Add event listeners to featured products buttons
 function addFeaturedProductsEventListeners() {
     // Add to cart buttons
     const addToCartButtons = document.querySelectorAll('#featured .add-to-cart, #featured .add-to-cart-icon');
     addToCartButtons.forEach(button => {
         button.addEventListener('click', function() {
             const productId = this.getAttribute('data-id');
             addToCart(productId);
         });
     });
     
     // Quick view buttons
     const quickViewButtons = document.querySelectorAll('#featured .quick-view');
     quickViewButtons.forEach(button => {
         button.addEventListener('click', function() {
             const productId = this.getAttribute('data-id');
             showQuickView(productId);
         });
     });
     
     // Add to wishlist buttons
     const wishlistButtons = document.querySelectorAll('#featured .add-to-wishlist');
     wishlistButtons.forEach(button => {
         button.addEventListener('click', function() {
             const productId = this.getAttribute('data-id');
             addToWishlist(productId);
         });
     });
 }
 
 // Function to show quick view modal
 function showQuickView(productId) {
     const product = products.find(p => p.id === productId);
     if (!product) return;
     
     // Create modal if it doesn't exist
     let modal = document.getElementById('quickViewModal');
     if (!modal) {
         modal = document.createElement('div');
         modal.id = 'quickViewModal';
         modal.className = 'modal';
         modal.innerHTML = `
             <div class="modal-content">
                 <span class="close-modal">&times;</span>
                 <div class="quick-view-container"></div>
             </div>
         `;
         document.body.appendChild(modal);
         
         // Add event listener to close button
         const closeBtn = modal.querySelector('.close-modal');
         closeBtn.addEventListener('click', () => {
             modal.style.display = 'none';
         });
         
         // Close modal when clicking outside
         window.addEventListener('click', (event) => {
             if (event.target === modal) {
                 modal.style.display = 'none';
             }
         });
     }
     
     // Format price with commas for thousands
     const formattedPrice = parseFloat(product.price).toLocaleString();
     
     // Populate modal with product info
     const container = modal.querySelector('.quick-view-container');
     container.innerHTML = `
         <div class="quick-view-grid">
             <div class="quick-view-image">
                 <img src="${product.image}" alt="${product.name}" onerror="this.src='images/placeholder.jpg'">
             </div>
             <div class="quick-view-details">
                 <h2>${product.name}</h2>
                 <p class="price">₱${formattedPrice}</p>
                 <div class="product-rating">
                     <span class="stars">${generateStars(parseFloat(product.rating))}</span>
                     <span class="rating-text">(${product.rating} - ${product.review_count} reviews)</span>
                 </div>
                 <p class="description">${product.description}</p>
                 
                 <div class="product-options">
                     <div class="size-options">
                         <h4>Size:</h4>
                         <div class="option-buttons">
                             ${product.sizes.maap(size => `<button class="size-btn">${size}</button>`).join('')}
                         </div>
                     </div>
                     
                     <div class="color-options">
                         <h4>Color:</h4>
                         <div class="option-buttons">
                             ${product.colors.map(color => `<button class="color-btn" style="background-color: ${color.toLowerCase()}">${color}</button>`).join('')}
                         </div>
                     </div>
                 </div>
                 
                 <div class="quantity-section">
                     <h4>Quantity:</h4>
                     <div class="quantity-control">
                         <button class="quantity-btn minus">−</button>
                         <input type="number" class="quantity-input" value="1" min="1" max="${product.stock}">
                         <button class="quantity-btn plus">+</button>
                     </div>
                 </div>
                 
                 <button class="add-to-cart-modal" data-id="${product.id}">Add to Cart</button>
             </div>
         </div>
     `;
     
     // Show modal
     modal.style.display = 'block';
     
     // Add event listeners for quantity buttons
     const minusBtn = container.querySelector('.minus');
     const plusBtn = container.querySelector('.plus');
     const quantityInput = container.querySelector('.quantity-input');
     
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
     
     // Add event listener for Add to Cart button in modal
     const addToCartBtn = container.querySelector('.add-to-cart-modal');
     addToCartBtn.addEventListener('click', () => {
         const quantity = parseInt(quantityInput.value);
         addToCartWithQuantity(product.id, quantity);
         modal.style.display = 'none';
     });
 }
 
 // Function to add to cart with specified quantity
 function addToCartWithQuantity(productId, quantity) {
     const product = products.find(p => p.id === productId);
     if (!product) return;
     
     // Get current cart from local storage or initialize empty cart
     let cart = JSON.parse(localStorage.getItem('cart')) || [];
     
     // Check if product already in cart
     const existingItem = cart.find(item => item.id === productId);
     
     if (existingItem) {
         existingItem.quantity += quantity;
     } else {
         cart.push({
             id: productId,
             name: product.name,
             price: parseFloat(product.price),
             image: product.image,
             quantity: quantity
         });
     }
     
     // Save updated cart to local storage
     localStorage.setItem('cart', JSON.stringify(cart));
     
     // Update cart count in the UI
     updateCartCount();
     
     // Show confirmation message
     alert(`${quantity} ${product.name}${quantity > 1 ? 's' : ''} added to your cart!`);
 }
 
 // Function to add to wishlist
 function addToWishlist(productId) {
     const product = products.find(p => p.id === productId);
     if (!product) return;
     
     // Get current wishlist from local storage or initialize empty wishlist
     let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
     
     // Check if product already in wishlist
     const existingItem = wishlist.find(item => item.id === productId);
     
     if (!existingItem) {
         wishlist.push({
             id: productId,
             name: product.name,
             price: parseFloat(product.price),
             image: product.image
         });
         
         // Save updated wishlist to local storage
         localStorage.setItem('wishlist', JSON.stringify(wishlist));
         
         // Show confirmation message
         alert(`${product.name} added to your wishlist!`);
     } else {
         alert(`${product.name} is already in your wishlist!`);
     }
     
     // Update wishlist count if you have one
     // updateWishlistCount();
 }
 
 // Update initialization to include featured products
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
     setTimeout(loadFeaturedProducts, 500);
 });