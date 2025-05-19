
// Account dropdown toggle function
document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const accountBtn = document.getElementById('accountBtn');
    const accountDropdown = document.getElementById('accountDropdown');
    const accountDropdownContent = document.getElementById('accountDropdownContent');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mainNav = document.getElementById('mainNav');
    
    // Account dropdown toggle
    accountBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        accountDropdown.classList.toggle('active');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        // If click is outside the dropdown
        if (!accountDropdown.contains(e.target)) {
            accountDropdown.classList.remove('active');
        }
    });
    
    // Mobile menu toggle
    mobileMenuToggle.addEventListener('click', function() {
        mainNav.classList.toggle('active');
    });
    
    // Initialize cart count for demo
    document.getElementById('cartCount').textContent = '0';
});

// Search function
function searchProducts() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    if (searchTerm !== '') {
        alert('Searching for: ' + searchTerm);
        // In a real site, this would redirect to search results
    }
}

// Enter key for search
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchProducts();
    }
});
