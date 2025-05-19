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