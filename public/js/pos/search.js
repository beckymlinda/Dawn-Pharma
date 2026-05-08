/**
 * POS Search Module
 * Handles product search functionality with proper event handling and debouncing
 */

// Search Module Namespace
window.POSSearch = window.POSSearch || {};

// Search State
POSSearch.state = {
    isInitialized: false,
    searchTimeout: null,
    minSearchLength: 2,
    maxResults: 10,
    debounceDelay: 300
};

// Search Initialization
POSSearch.init = function() {
    console.log('POS Search module initializing...');
    
    if (POSSearch.state.isInitialized) {
        console.warn('POS Search module already initialized');
        return;
    }
    
    // Set up search event listeners
    POSSearch.setupEventListeners();
    
    // Make search function globally available for inline handlers
    window.filterProducts = POSSearch.filterProducts;
    
    POSSearch.state.isInitialized = true;
    console.log('POS Search module initialized');
};

/**
 * Setup search event listeners
 */
POSSearch.setupEventListeners = function() {
    const searchInput = document.getElementById('cartSearch');
    const searchDropdown = document.getElementById('searchDropdown');
    
    if (!searchInput) {
        console.error('Search input element not found');
        return;
    }
    
    if (!searchDropdown) {
        console.error('Search dropdown element not found');
        return;
    }
    
    // Remove any existing event listeners to prevent duplicates
    searchInput.removeEventListener('input', POSSearch.handleSearchInput);
    searchInput.removeEventListener('keydown', POSSearch.handleKeyDown);
    searchInput.removeEventListener('focus', POSSearch.handleSearchFocus);
    
    // Add new event listeners
    searchInput.addEventListener('input', POSSearch.handleSearchInput);
    searchInput.addEventListener('keydown', POSSearch.handleKeyDown);
    searchInput.addEventListener('focus', POSSearch.handleSearchFocus);
    
    // Handle click outside to close dropdown
    document.addEventListener('click', POSSearch.handleDocumentClick);
    
    console.log('Search event listeners set up');
};

/**
 * Handle search input with debouncing
 */
POSSearch.handleSearchInput = function(event) {
    const query = event.target.value.toLowerCase();
    
    // Clear previous timeout
    clearTimeout(POSSearch.state.searchTimeout);
    
    // Hide dropdown if query is too short
    if (query.length < POSSearch.state.minSearchLength) {
        POSSearch.hideDropdown();
        return;
    }
    
    // Debounce search
    POSSearch.state.searchTimeout = setTimeout(() => {
        POSSearch.performSearch(query);
    }, POSSearch.state.debounceDelay);
};

/**
 * Handle keyboard navigation
 */
POSSearch.handleKeyDown = function(event) {
    const dropdown = document.getElementById('searchDropdown');
    const items = dropdown.querySelectorAll('.search-result-item');
    
    if (!items.length) return;
    
    let currentIndex = -1;
    const activeItem = dropdown.querySelector('.search-result-item.active');
    if (activeItem) {
        currentIndex = Array.from(items).indexOf(activeItem);
    }
    
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            currentIndex = Math.min(currentIndex + 1, items.length - 1);
            POSSearch.highlightItem(items, currentIndex);
            break;
        case 'ArrowUp':
            event.preventDefault();
            currentIndex = Math.max(currentIndex - 1, -1);
            POSSearch.highlightItem(items, currentIndex);
            break;
        case 'Enter':
            event.preventDefault();
            if (currentIndex >= 0 && items[currentIndex]) {
                items[currentIndex].click();
            }
            break;
        case 'Escape':
            POSSearch.hideDropdown();
            break;
    }
};

/**
 * Handle search focus
 */
POSSearch.handleSearchFocus = function(event) {
    const query = event.target.value.toLowerCase();
    if (query.length >= POSSearch.state.minSearchLength) {
        POSSearch.performSearch(query);
    }
};

/**
 * Handle document clicks to close dropdown
 */
POSSearch.handleDocumentClick = function(event) {
    const searchContainer = document.querySelector('.pos-search-section');
    const dropdown = document.getElementById('searchDropdown');
    
    if (searchContainer && dropdown && !searchContainer.contains(event.target)) {
        POSSearch.hideDropdown();
    }
};

/**
 * Highlight selected item in dropdown
 */
POSSearch.highlightItem = function(items, index) {
    // Remove active class from all items
    items.forEach(item => item.classList.remove('active'));
    
    // Add active class to selected item
    if (index >= 0 && items[index]) {
        items[index].classList.add('active');
        // Scroll into view if needed
        items[index].scrollIntoView({ block: 'nearest' });
    }
};

/**
 * Main search function (compatible with existing inline handler)
 */
POSSearch.filterProducts = function() {
    console.log('filterProducts called from POSSearch');
    
    const searchInput = document.getElementById('cartSearch');
    const dropdown = document.getElementById('searchDropdown');
    
    if (!searchInput || !dropdown) {
        console.log('Search elements not found');
        return;
    }
    
    const query = searchInput.value.toLowerCase();
    POSSearch.performSearch(query);
};

/**
 * Perform actual search
 */
POSSearch.performSearch = function(query) {
    console.log('Performing search for:', query);
    
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) {
        console.error('Search dropdown not found');
        return;
    }
    
    // Check if products module is available
    if (!window.POSProducts || !window.POSProducts.state) {
        console.log('Products module not available yet');
        dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">Loading products...</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    const productsData = window.POSProducts.state.productsData;
    console.log('Products available:', productsData.length);
    
    // Show loading state
    if (productsData.length > 0 && query.length >= POSSearch.state.minSearchLength) {
        dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">Searching...</div>';
        dropdown.style.display = 'block';
    }
    
    // Handle case when no products are loaded yet
    if (productsData.length === 0) {
        console.log('Products not loaded yet');
        dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">Loading products... Please wait.</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    if (query.length < POSSearch.state.minSearchLength) {
        POSSearch.hideDropdown();
        return;
    }
    
    // Perform search
    const results = [];
    for (let i = 0; i < productsData.length; i++) {
        const product = productsData[i];
        var nameMatch = product.name && product.name.toLowerCase().indexOf(query) !== -1;
        var skuMatch = product.sku && product.sku.toLowerCase().indexOf(query) !== -1;
        if (nameMatch || skuMatch) {
            results.push(product);
        }
    }
    
    console.log('Search results:', results.length);
    
    // Display results
    if (results.length > 0) {
        POSSearch.displayResults(results);
    } else {
        dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">No products found</div>';
        dropdown.style.display = 'block';
    }
};

/**
 * Display search results
 */
POSSearch.displayResults = function(results) {
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) return;
    
    let html = '';
    const maxResults = Math.min(results.length, POSSearch.state.maxResults);
    
    for (let i = 0; i < maxResults; i++) {
        const product = results[i];
        const price = parseFloat(product.selling_price) || 0;
        const stock = product.current_stock || 0;
        
        html += '<div onclick="POSSearch.selectProduct(' + product.id + ', 1, event)" class="search-result-item" data-index="' + i + '">';
        html += '<div class="result-left">';
        html += '<div class="result-name">' + POSSearch.escapeHtml(product.name) + '</div>';
        html += '<div class="result-meta">Stock: ' + stock + '</div>';
        html += '</div>';
        html += '<div class="result-right">';
        html += '<div class="result-price">' + POSApp.utils.formatCurrency(price) + '</div>';
        html += '</div>';
        html += '</div>';
    }
    
    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
};

/**
 * Select a product from search results
 */
POSSearch.selectProduct = function(productId, quantity, event) {
    console.log('Product selected:', productId, quantity);
    
    // Prevent default if this is a keyboard event
    if (event && event.type === 'keydown') {
        event.preventDefault();
    }
    
    // Clear search
    const searchInput = document.getElementById('cartSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Hide dropdown
    POSSearch.hideDropdown();
    
    // Add to cart using products module
    if (window.POSProducts && window.POSProducts.addToCartClick) {
        window.POSProducts.addToCartClick(productId, quantity);
    } else {
        console.error('POSProducts module not available for adding to cart');
    }
};

/**
 * Hide search dropdown
 */
POSSearch.hideDropdown = function() {
    const dropdown = document.getElementById('searchDropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
};

/**
 * Escape HTML to prevent XSS
 */
POSSearch.escapeHtml = function(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

/**
 * Clear search
 */
POSSearch.clearSearch = function() {
    const searchInput = document.getElementById('cartSearch');
    if (searchInput) {
        searchInput.value = '';
    }
    POSSearch.hideDropdown();
};

/**
 * Focus search input
 */
POSSearch.focusSearch = function() {
    const searchInput = document.getElementById('cartSearch');
    if (searchInput) {
        searchInput.focus();
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', POSSearch.init);
} else {
    POSSearch.init();
}
