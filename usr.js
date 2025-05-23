<!-- JavaScript for Dropdown and Mobile Menu -->
    <script src="js/home.js"></script>

    <script>console.log('After home.js');</script>

    <script>
        // Global variables
        let productData;
        let products = [];
        let loadingCompleted = false;

        // Function to load the XML data
        function loadProductData() {
            // Show loading indication
            document.getElementById("loading-status").textContent = "Loading products...";

            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (this.readyState == 4) {
                    if (this.status == 200) {
                        productData = this.responseXML;
                        parseProductData();
                        displayProducts(); // Display products after loading
                        loadingCompleted = true;
                        document.getElementById("loading-status").textContent = "Products loaded successfully!";
                    } else {
                        console.error("Failed to load XML data. Status:", this.status);
                        document.getElementById("loading-status").textContent = "Failed to load products. Please try again.";
                    }
                }
            };
            xhr.open("GET", "product.xml", true);
            xhr.send();
        }

        // Parse XML into a more usable format
        function parseProductData() {
            if (!productData) {
                console.error("No product data available to parse");
                return;
            }

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
                    featured: getElementTextContent(product, "featured") === "true",
                    on_sale: getElementTextContent(product, "on_sale") === "true"
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

        // Function to display products on the page
        function displayProducts() {
            const productContainer = document.getElementById("product-container");
            if (!productContainer) {
                console.error("Product container element not found");
                return;
            }

            // Clear existing products
            productContainer.innerHTML = "";

            if (products.length === 0) {
                productContainer.innerHTML = "<p>No products found</p>";
                return;
            }

            // Create and append product cards
            products.forEach(product => {
                const productCard = createProductCard(product);
                productContainer.appendChild(productCard);
            });
        }

        // Function to create a product card element
        function createProductCard(product) {
            const card = document.createElement("div");
            card.className = "product-card";
            card.dataset.productId = product.id;

            // Create product image
            const imgContainer = document.createElement("div");
            imgContainer.className = "product-image";

            const img = document.createElement("img");
            img.src = product.image || "placeholder.jpg";
            img.alt = product.name;
            imgContainer.appendChild(img);

            // Add sale tag if product is on sale
            if (product.on_sale) {
                const saleTag = document.createElement("span");
                saleTag.className = "sale-tag";
                saleTag.textContent = "SALE";
                imgContainer.appendChild(saleTag);
            }

            // Create product info
            const info = document.createElement("div");
            info.className = "product-info";

            const name = document.createElement("h3");
            name.textContent = product.name;

            const category = document.createElement("p");
            category.className = "product-category";
            category.textContent = product.category;

            // Create star rating element
            const ratingDiv = document.createElement("div");
            ratingDiv.className = "product-rating";

            // Create stars based on rating
            const rating = parseFloat(product.rating);
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement("i");
                if (i <= Math.floor(rating)) {
                    star.className = "fas fa-star"; // Full star
                } else if (i - 0.5 <= rating) {
                    star.className = "fas fa-star-half-alt"; // Half star
                } else {
                    star.className = "far fa-star"; // Empty star
                }
                ratingDiv.appendChild(star);
            }

            // Add rating number
            const ratingText = document.createElement("span");
            ratingText.textContent = `(${product.rating})`;
            ratingDiv.appendChild(ratingText);

            const price = document.createElement("p");
            price.className = "product-price";
            price.textContent = `₱${parseFloat(product.price).toFixed(2)}`;

            const stock = document.createElement("p");
            stock.className = "product-stock";
            stock.textContent = `Stock: ${product.stock}`;

            // Add to cart button
            const addToCartBtn = document.createElement("button");
            addToCartBtn.className = "add-to-cart-btn";
            addToCartBtn.textContent = "Add to Cart";
            addToCartBtn.addEventListener("click", (e) => {
                e.stopPropagation(); // Prevent card click event
                addToCart(product.id);
            });

            // Add elements to card
            card.appendChild(imgContainer);
            info.appendChild(name);
            info.appendChild(price);
            info.appendChild(category);
            info.appendChild(ratingDiv);
            info.appendChild(stock);
            info.appendChild(addToCartBtn);
            card.appendChild(info);

            // Add click event
            card.addEventListener("click", () => showProductDetails(product.id));

            return card;
        }

        // Function to add a product to cart
        function addToCart(productId) {
            console.log(`Adding product ${productId} to cart`);
            // Implement your cart functionality here
            alert("Product added to cart!");
        }

        // Function to display products on the page
        function displayProducts() {
            const productContainer = document.getElementById("product-container");
            if (!productContainer) {
                console.error("Product container element not found");
                return;
            }

            // Clear existing products
            productContainer.innerHTML = "";

            if (products.length === 0) {
                const noProducts = document.createElement("div");
                noProducts.className = "no-products";
                noProducts.textContent = "No products found";
                productContainer.appendChild(noProducts);
                return;
            }

            // Create and append product cards
            products.forEach(product => {
                const productCard = createProductCard(product);
                productContainer.appendChild(productCard);
            });
        }

        // Initialize products when the page loads
        document.addEventListener("DOMContentLoaded", function () {
            // Add stylesheet for Font Awesome icons
            const fontAwesome = document.createElement("link");
            fontAwesome.rel = "stylesheet";
            fontAwesome.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css";
            document.head.appendChild(fontAwesome);

            // Create container for products if it doesn't exist
            if (!document.getElementById("product-container")) {
                const container = document.createElement("div");
                container.id = "product-container";
                document.body.appendChild(container);
            }

            // Create loading status element with better styling
            if (!document.getElementById("loading-status")) {
                const loadingStatus = document.createElement("div");
                loadingStatus.id = "loading-status";
                loadingStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading products...';
                document.body.insertBefore(loadingStatus, document.getElementById("product-container"));
            }

            // Load products
            loadProductData();
        });



        /////////////////////////
        // Enhanced Search and Filter Functions for HirayaFit

        // Global variables
        let filteredProducts = [];
        let currentCategory = "all";
        let searchQuery = "";

        // Modify the existing loadProductData function to initialize filters after loading
        function loadProductData() {
            // Show loading indication
            document.getElementById("loading-status").textContent = "Loading products...";

            const xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
                if (this.readyState == 4) {
                    if (this.status == 200) {
                        productData = this.responseXML;
                        parseProductData();
                        setupFilters(); // Setup category filters after data is loaded
                        applyFiltersAndSearch(); // Apply any initial filters and display products
                        loadingCompleted = true;
                        document.getElementById("loading-status").textContent = "";
                    } else {
                        console.error("Failed to load XML data. Status:", this.status);
                        document.getElementById("loading-status").textContent = "Failed to load products. Please try again.";
                    }
                }
            };
            xhr.open("GET", "product.xml", true);
            xhr.send();
        }

        // Function to set up the category filters based on available product categories
        function setupFilters() {
            // Extract unique categories from products
            const categories = ["all"];
            products.forEach(product => {
                if (!categories.includes(product.category)) {
                    categories.push(product.category);
                }
            });

            // Get the main navigation element
            const mainNav = document.getElementById("mainNav");

            // Clear existing links except HOME
            while (mainNav.childNodes.length > 1) {
                mainNav.removeChild(mainNav.lastChild);
            }

            // Add category links to navigation
            categories.forEach(category => {
                const categoryLink = document.createElement("a");
                categoryLink.href = "#";
                categoryLink.textContent = category.toUpperCase();
                categoryLink.dataset.category = category;

                // Set active class if it's the current category
                if (category === currentCategory) {
                    categoryLink.classList.add("active");
                }

                // Add click event to filter products
                categoryLink.addEventListener("click", function (e) {
                    e.preventDefault();

                    // Remove active class from all links
                    document.querySelectorAll("#mainNav a").forEach(link => {
                        link.classList.remove("active");
                    });

                    // Add active class to clicked link
                    this.classList.add("active");

                    // Set current category and apply filters
                    currentCategory = category;
                    applyFiltersAndSearch();
                });

                mainNav.appendChild(categoryLink);
            });
        }

        // Function to search products based on query
        function searchProducts() {
            const searchInput = document.getElementById("searchInput");
            searchQuery = searchInput.value.trim().toLowerCase();
            applyFiltersAndSearch();
        }

        // Function to apply both category filters and search query
        function applyFiltersAndSearch() {
            // Start with all products
            filteredProducts = [...products];

            // Apply category filter if not "all"
            if (currentCategory !== "all") {
                filteredProducts = filteredProducts.filter(product =>
                    product.category.toLowerCase() === currentCategory.toLowerCase()
                );
            }

            // Apply search filter if there's a search query
            if (searchQuery) {
                filteredProducts = filteredProducts.filter(product =>
                    product.name.toLowerCase().includes(searchQuery) ||
                    product.description.toLowerCase().includes(searchQuery) ||
                    product.category.toLowerCase().includes(searchQuery)
                );
            }

            // Update results count
            updateResultsCount();

            // Display filtered products
            displayFilteredProducts();
        }

        // Function to update the results count
        function updateResultsCount() {
            const resultsCountElem = document.getElementById("results-count");
            if (resultsCountElem) {
                resultsCountElem.textContent = `${filteredProducts.length} products found`;
            }
        }

        // Function to display filtered products
        function displayFilteredProducts() {
            const productContainer = document.getElementById("product-container");
            if (!productContainer) {
                console.error("Product container element not found");
                return;
            }

            // Clear existing products
            productContainer.innerHTML = "";

            if (filteredProducts.length === 0) {
                const noProducts = document.createElement("div");
                noProducts.className = "no-products";
                noProducts.textContent = "No products found for your search. Try different keywords or filters.";
                productContainer.appendChild(noProducts);
                return;
            }

            // Create and append product cards
            filteredProducts.forEach(product => {
                const productCard = createProductCard(product);
                productContainer.appendChild(productCard);
            });
        }

        // Initialize search and filters when the page loads
        document.addEventListener("DOMContentLoaded", function () {
            // Original code from the pasted script...



            // Create search bar container if it doesn't exist
            if (!document.querySelector(".search-bar")) {
                createSearchBarUI();
            }

            // Create container for products if it doesn't exist
            if (!document.getElementById("product-container")) {
                const container = document.createElement("div");
                container.id = "product-container";
                document.body.appendChild(container);
            }

            // Create results count element
            if (!document.getElementById("results-count")) {
                const resultsCount = document.createElement("div");
                resultsCount.id = "results-count";
                resultsCount.className = "results-count";
                document.body.insertBefore(resultsCount, document.getElementById("product-container"));
            }

            // Create loading status element with better styling
            if (!document.getElementById("loading-status")) {
                const loadingStatus = document.createElement("div");
                loadingStatus.id = "loading-status";
                loadingStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading products...';
                document.body.insertBefore(loadingStatus, document.getElementById("product-container"));
            }

            // Load products
            loadProductData();

            // Set up event listener for search input (search as you type)
            const searchInput = document.getElementById("searchInput");
            if (searchInput) {
                searchInput.addEventListener("input", function () {
                    // Use debounce to avoid too many searches when typing quickly
                    clearTimeout(this.searchTimer);
                    this.searchTimer = setTimeout(() => {
                        searchProducts();
                    }, 300);
                });

                // Add event listener for Enter key
                searchInput.addEventListener("keyup", function (event) {
                    if (event.key === "Enter") {
                        searchProducts();
                    }
                });
            }
        });

        // Function to create the search bar UI
        function createSearchBarUI() {
            // Create search bar container
            const searchBar = document.createElement("div");
            searchBar.className = "search-bar";

            // Create search input
            const searchInput = document.createElement("input");
            searchInput.type = "text";
            searchInput.id = "searchInput";
            searchInput.placeholder = "Search products...";

            // Create search button
            const searchButton = document.createElement("button");
            searchButton.innerHTML = '<i class="fas fa-search"></i>';
            searchButton.onclick = searchProducts;

            // Append elements to search bar
            searchBar.appendChild(searchInput);
            searchBar.appendChild(searchButton);

            // Find navbar to append search bar
            const navBar = document.querySelector(".main-nav");
            if (navBar) {
                // Insert search bar before the nav
                navBar.parentNode.insertBefore(searchBar, navBar);
            } else {
                // If no navbar, add to body
                document.body.appendChild(searchBar);
            }
        }

        ///////////////////////////////////////// show details
        // Function to show product details modal
        function showProductDetails(productId) {
            // Find the product by ID
            const product = products.find(p => p.id === productId);
            if (!product) {
                console.error(`Product with ID ${productId} not found`);
                return;
            }

            // Add modal styles if they don't exist yet
            if (!document.getElementById('product-modal-styles')) {
                addProductDetailsModalStyles();
            }

            // Create modal elements
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'product-modal-overlay';
            modalOverlay.id = 'product-modal';

            const modalContent = document.createElement('div');
            modalContent.className = 'product-modal-content';

            // Close button
            const closeButton = document.createElement('button');
            closeButton.className = 'modal-close';
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', closeProductModal);

            // Product grid layout
            const productGrid = document.createElement('div');
            productGrid.className = 'modal-product-grid';

            // Product image section
            const imageSection = document.createElement('div');
            imageSection.className = 'modal-product-image';

            const productImage = document.createElement('img');
            productImage.src = product.image || 'placeholder.jpg';
            productImage.alt = product.name;
            imageSection.appendChild(productImage);

            // Product details section
            const detailsSection = document.createElement('div');
            detailsSection.className = 'modal-product-details';

            // Product title
            const title = document.createElement('h2');
            title.className = 'modal-product-title';
            title.textContent = product.name;

            // Product price
            const price = document.createElement('p');
            price.className = 'modal-product-price';
            price.textContent = `₱${parseFloat(product.price).toFixed(2)}`;

            // Product rating
            const rating = document.createElement('div');
            rating.className = 'modal-product-rating';

            const starsDiv = document.createElement('div');
            starsDiv.className = 'stars';

            // Create stars based on rating
            const ratingValue = parseFloat(product.rating);
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('i');
                if (i <= Math.floor(ratingValue)) {
                    star.className = 'fas fa-star'; // Full star
                } else if (i - 0.5 <= ratingValue) {
                    star.className = 'fas fa-star-half-alt'; // Half star
                } else {
                    star.className = 'far fa-star'; // Empty star
                }
                starsDiv.appendChild(star);
            }

            const ratingText = document.createElement('span');
            ratingText.className = 'rating-text';
            ratingText.textContent = ` (${product.rating}) ratings`;
            starsDiv.appendChild(ratingText);
            rating.appendChild(starsDiv);

            // Product description
            const description = document.createElement('div');
            description.className = 'modal-product-description';
            description.textContent = product.description;

            // Product sizes section (if available)
            const sizesSection = document.createElement('div');
            sizesSection.className = 'modal-product-size';

            if (product.sizes && product.sizes.length > 0) {
                const sizeLabel = document.createElement('label');
                sizeLabel.textContent = 'Size:';
                sizesSection.appendChild(sizeLabel);

                const sizeOptions = document.createElement('div');
                sizeOptions.className = 'size-options';

                product.sizes.forEach((size, index) => {
                    const sizeBtn = document.createElement('button');
                    sizeBtn.className = 'size-btn';
                    if (index === 0) sizeBtn.classList.add('active');
                    sizeBtn.textContent = size;
                    sizeBtn.addEventListener('click', function () {
                        // Remove active class from all buttons
                        document.querySelectorAll('.size-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        // Add active class to clicked button
                        this.classList.add('active');
                    });
                    sizeOptions.appendChild(sizeBtn);
                });

                sizesSection.appendChild(sizeOptions);
            }

            // Product colors section (if available)
            const colorsSection = document.createElement('div');
            colorsSection.className = 'modal-product-color';

            if (product.colors && product.colors.length > 0) {
                const colorLabel = document.createElement('label');
                colorLabel.textContent = 'Color:';
                colorsSection.appendChild(colorLabel);

                const colorOptions = document.createElement('div');
                colorOptions.className = 'color-options';

                product.colors.forEach((color, index) => {
                    const colorBtn = document.createElement('div');
                    colorBtn.className = 'color-btn';
                    colorBtn.style.backgroundColor = color;
                    if (index === 0) colorBtn.classList.add('active');
                    colorBtn.addEventListener('click', function () {
                        document.querySelectorAll('.color-btn').forEach(btn => {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                    });
                    colorOptions.appendChild(colorBtn);
                });

                colorsSection.appendChild(colorOptions);
            }

            // Quantity section
            const quantitySection = document.createElement('div');
            quantitySection.className = 'modal-product-quantity';

            const quantityLabel = document.createElement('label');
            quantityLabel.textContent = 'Quantity:';

            const quantityControl = document.createElement('div');
            quantityControl.className = 'quantity-control';

            const decreaseBtn = document.createElement('button');
            decreaseBtn.className = 'quantity-btn';
            decreaseBtn.textContent = '-';
            decreaseBtn.addEventListener('click', function () {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                }
            });

            const quantityInput = document.createElement('input');
            quantityInput.type = 'text';
            quantityInput.className = 'quantity-input';
            quantityInput.value = '1';
            quantityInput.min = '1';
            quantityInput.addEventListener('change', function () {
                if (this.value < 1 || isNaN(this.value)) {
                    this.value = 1;
                }
            });

            const increaseBtn = document.createElement('button');
            increaseBtn.className = 'quantity-btn';
            increaseBtn.textContent = '+';
            increaseBtn.addEventListener('click', function () {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                const stock = parseInt(product.stock);
                if (value < stock) {
                    input.value = value + 1;
                } else {
                    alert(`Sorry, only ${stock} items in stock.`);
                }
            });

            quantityControl.appendChild(decreaseBtn);
            quantityControl.appendChild(quantityInput);
            quantityControl.appendChild(increaseBtn);

            quantitySection.appendChild(quantityLabel);
            quantitySection.appendChild(quantityControl);

            // Stock information
            const stockInfo = document.createElement('p');
            stockInfo.className = 'stock-info';
            stockInfo.textContent = `In Stock: ${product.stock} items`;

            // Add to cart button
            const addToCartBtn = document.createElement('button');
            addToCartBtn.className = 'modal-add-to-cart';
            addToCartBtn.textContent = 'Add to Cart';
            addToCartBtn.addEventListener('click', function () {
                // Get selected size and color
                const selectedSize = document.querySelector('.size-btn.active')?.textContent || '';
                const selectedColor = document.querySelector('.color-btn.active')?.style.backgroundColor || '';
                const quantity = parseInt(quantityInput.value);

                // Add to cart function
                addToCartFromModal(product.id, selectedSize, selectedColor, quantity);

                // Show confirmation message
                showAddToCartConfirmation(product.name);
            });

            // Append everything to details section
            detailsSection.appendChild(title);
            detailsSection.appendChild(price);
            detailsSection.appendChild(rating);
            detailsSection.appendChild(description);
            detailsSection.appendChild(sizesSection);
            detailsSection.appendChild(colorsSection);
            detailsSection.appendChild(quantitySection);
            detailsSection.appendChild(stockInfo);
            detailsSection.appendChild(addToCartBtn);

            // Add all sections to the grid
            productGrid.appendChild(imageSection);
            productGrid.appendChild(detailsSection);

            // Add content to the modal
            modalContent.appendChild(closeButton);
            modalContent.appendChild(productGrid);
            modalOverlay.appendChild(modalContent);

            // Add the modal to the page
            document.body.appendChild(modalOverlay);

            // Add modal-open class to body to prevent scrolling
            document.body.classList.add('modal-open');

            // Show the modal
            modalOverlay.style.display = 'flex';

            // Close modal when clicking outside the content
            modalOverlay.addEventListener('click', function (e) {
                if (e.target === modalOverlay) {
                    closeProductModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeProductModal();
                }
            });
        }

        // Function to close the product modal
        function closeProductModal() {
            const modalOverlay = document.getElementById('product-modal');
            if (modalOverlay) {
                modalOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                modalOverlay.remove();
            }
        }

        // Function to add to cart from the modal
        function addToCartFromModal(productId, size, color, quantity) {
            console.log(`Adding to cart: Product ID ${productId}, Size: ${size}, Color: ${color}, Quantity: ${quantity}`);
            // Implement your cart functionality here
            // This could involve storing the selection in localStorage or making an API call
        }

        // Function to show add to cart confirmation
        function showAddToCartConfirmation(productName) {
            // Create confirmation element
            const confirmation = document.createElement('div');
            confirmation.className = 'add-to-cart-confirmation';

            const confirmationContent = document.createElement('div');
            confirmationContent.className = 'confirmation-content';

            const icon = document.createElement('i');
            icon.className = 'fas fa-check-circle';

            const message = document.createElement('p');
            message.textContent = `${productName} added to cart successfully!`;

            confirmationContent.appendChild(icon);
            confirmationContent.appendChild(message);
            confirmation.appendChild(confirmationContent);

            // Add to body
            document.body.appendChild(confirmation);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                confirmation.classList.add('fade-out');
                setTimeout(() => {
                    confirmation.remove();
                }, 500);
            }, 3000);
        }

        // Function to add modal styles to the document
        function addProductDetailsModalStyles() {
            const styleElement = document.createElement('style');
            styleElement.id = 'product-modal-styles';
            styleElement.textContent = `
     
    `;
            document.head.appendChild(styleElement);
        }



        /////////////////////////////////////////////////////
// Cart System for HirayaFit
// Global variables
let cart = [];
let cartTotal = 0;

// Initialize cart on page load
document.addEventListener("DOMContentLoaded", function () {
    // Add event listener for cart button (redirects to cart.php instead of showing modal)
    const cartBtn = document.getElementById("cartBtn");
    if (cartBtn) {
        cartBtn.addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href = "cart.php";
        });
    }
    
    // Update cart count based on XML data
    updateCartCountFromXML();
});

// Function to add product to cart from product details modal
function addToCartFromModal(productId, size, color, quantity) {
    // Get product details
    const product = products.find(p => p.id === productId);
    if (!product) {
        console.error(`Product with ID ${productId} not found`);
        return;
    }

    // Check if we have enough stock
    if (parseInt(product.stock) < quantity) {
        alert(`Sorry, only ${product.stock} items in stock.`);
        return;
    }

    // Send item directly to PHP/XML
    sendItemToCartXML({
        productId: productId,
        productName: product.name,
        image: product.image,
        price: parseFloat(product.price),
        size: size,
        color: color,
        quantity: quantity
    });

    // Show confirmation
    showAddToCartConfirmation(product.name);
}

// Function to add product to cart from product list
function addToCart(productId) {
    // Get product details
    const product = products.find(p => p.id === productId);
    if (!product) {
        console.error(`Product with ID ${productId} not found`);
        return;
    }

    // Create cart item with default values (1 quantity, default size/color)
    const cartItem = {
        productId: productId,
        productName: product.name,
        image: product.image,
        price: parseFloat(product.price),
        size: product.sizes && product.sizes.length > 0 ? product.sizes[0] : '',
        color: product.colors && product.colors.length > 0 ? product.colors[0] : '',
        quantity: 1
    };

    // Send item directly to PHP/XML
    sendItemToCartXML(cartItem);

    // Show confirmation
    showAddToCartConfirmation(product.name);
}

// Function to send item to cart.xml via PHP
function sendItemToCartXML(item) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            productId: item.productId,
            productName: item.productName,
            image: item.image,
            price: item.price,
            size: item.size,
            color: item.color,
            quantity: item.quantity
        })
    })
    .then(response => response.text())
    .then(data => {
        console.log('Server says:', data);
        // Update cart count after adding item
        updateCartCountFromXML();
    })
    .catch(error => console.error('Error saving to XML:', error));
}

// Function to update cart count from XML data
function updateCartCountFromXML() {
    // Fetch cart count from server
    fetch('get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        const cartCount = document.getElementById("cartCount");
        if (cartCount && data.count !== undefined) {
            cartCount.textContent = data.count;
        }
    })
    .catch(error => {
        console.error('Error fetching cart count:', error);
    });
}

// Function to show add to cart confirmation
function showAddToCartConfirmation(productName) {
    // Create notification element if it doesn't exist
    let notification = document.getElementById('cart-notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'cart-notification';
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.backgroundColor = '#4CAF50';
        notification.style.color = 'white';
        notification.style.padding = '12px 20px';
        notification.style.borderRadius = '4px';
        notification.style.zIndex = '1000';
        notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        notification.style.transition = 'opacity 0.5s';
        document.body.appendChild(notification);
    }
    
    // Update notification message
    notification.textContent = `${productName} added to cart`;
    notification.style.opacity = '1';
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
    }, 3000);
}


    </script>