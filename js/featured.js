// Function to load top 4 featured products for homepage
function loadTopFeaturedProducts() {
    // Check if products are already loaded
    if (products.length === 0) {
        // If not, set a small timeout to wait for products to load
        setTimeout(loadTopFeaturedProducts, 500);
        return;
    }
    
    // Filter for featured products and get only the top 4
    const featuredProducts = products
        .filter(product => product.featured === 'true')
        .slice(0, 4); // Limit to top 4
    
    // Display the featured products
    displayTopFeaturedProducts(featuredProducts);
}

// Function to display top featured products in the homepage
function displayTopFeaturedProducts(featuredProducts) {
    const featuredSection = document.getElementById('homeFeatured');
    
    if (!featuredSection) {
        console.error("Home featured section not found in the DOM");
        return;
    }
    
    // Clear any existing content
    featuredSection.innerHTML = '';
    
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
    description.textContent = 'Our top recommendations for your fitness journey';
    container.appendChild(description);
    
    // Create results grid similar to search results
    const resultsGrid = document.createElement('div');
    resultsGrid.className = 'results-grid';
    container.appendChild(resultsGrid);
    
    // Add each featured product to the grid
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
        
        resultsGrid.appendChild(productCard);
    });
    
    featuredSection.appendChild(container);
    
    // Make the product cards clickable to view details
    const productCards = resultsGrid.querySelectorAll('.product-card');
    productCards.forEach(card => {
        // Make the whole card clickable except buttons
        card.addEventListener('click', function(e) {
            // If click is on a button or inside a button, don't trigger details view
            if (e.target.closest('button')) {
                return;
            }
            
            const productId = card.querySelector('.add-to-cart').getAttribute('data-id');
            viewProductDetails(productId);
        });
    });
    
    // Add event listeners to buttons
    addHomeFeaturedEventListeners();
}

// Add event listeners to top featured products buttons
function addHomeFeaturedEventListeners() {
    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('#homeFeatured .add-to-cart, #homeFeatured .add-to-cart-icon');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToCart(productId);
        });
    });
    
    // Quick view buttons
    const quickViewButtons = document.querySelectorAll('#homeFeatured .quick-view');
    quickViewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            viewProductDetails(productId); // Using the new viewProductDetails function
        });
    });
    
    // Add to wishlist buttons
    const wishlistButtons = document.querySelectorAll('#homeFeatured .add-to-wishlist');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            addToWishlist(productId);
        });
    });
}

// Update initialization to include top featured products and modal styles
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
    
    // Load top featured products after a slight delay to ensure XML is loaded
    setTimeout(() => {
        loadTopFeaturedProducts();
    }, 500);
});