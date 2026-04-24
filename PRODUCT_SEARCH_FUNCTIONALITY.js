// Product Search and Display Limit Functionality
// Add this to the existing script section in pos.blade.php

// Product search functionality
function setupProductSearch() {
    const productSearchInput = document.getElementById('productSearch');
    const displayLimitSelect = document.getElementById('productDisplayLimit');
    
    if (productSearchInput) {
        productSearchInput.addEventListener('input', function() {
            filterProductsDisplay();
        });
    }
    
    if (displayLimitSelect) {
        displayLimitSelect.addEventListener('change', function() {
            filterProductsDisplay();
        });
    }
}

// Filter products based on search and display limit
function filterProductsDisplay() {
    const productSearchInput = document.getElementById('productSearch');
    const displayLimitSelect = document.getElementById('productDisplayLimit');
    const searchQuery = productSearchInput ? productSearchInput.value.toLowerCase().trim() : '';
    const displayLimit = displayLimitSelect ? displayLimitSelect.value : 'all';
    
    let filteredProducts = productsData;
    
    // Apply search filter
    if (searchQuery) {
        filteredProducts = productsData.filter(product => 
            (product.name && product.name.toLowerCase().includes(searchQuery)) || 
            (product.sku && product.sku.toLowerCase().includes(searchQuery)) ||
            (product.description && product.description.toLowerCase().includes(searchQuery))
        );
    }
    
    // Apply display limit
    if (displayLimit !== 'all') {
        const limit = parseInt(displayLimit);
        filteredProducts = filteredProducts.slice(0, limit);
    }
    
    // Apply current category/brand filter if active
    if (currentFilter !== 'all') {
        if (currentFilter.startsWith('category-')) {
            const categoryId = currentFilter.replace('category-', '');
            filteredProducts = filteredProducts.filter(p => p.category_id == categoryId);
        } else if (currentFilter.startsWith('brand-')) {
            const brandId = currentFilter.replace('brand-', '');
            filteredProducts = filteredProducts.filter(p => p.brand_id == brandId);
        }
    }
    
    renderProductGrid(filteredProducts);
}

// Update the existing renderProductGrid function to handle display limits
function updateRenderProductGrid() {
    const originalRenderProductGrid = window.renderProductGrid;
    
    window.renderProductGrid = function(list) {
        const displayLimitSelect = document.getElementById('productDisplayLimit');
        const displayLimit = displayLimitSelect ? displayLimitSelect.value : 'all';
        
        let productsToShow = list || productsData;
        
        // Apply display limit if not 'all'
        if (displayLimit !== 'all' && productsToShow.length > parseInt(displayLimit)) {
            productsToShow = productsToShow.slice(0, parseInt(displayLimit));
        }
        
        // Call original render function with limited list
        return originalRenderProductGrid.call(this, productsToShow);
    };
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupProductSearch();
    updateRenderProductGrid();
});

// Add CSS styles dynamically
const productControlsCSS = `
<style>
.pos-product-controls{
    background:#f8f9fa;
    padding:10px;
    border-radius:6px;
    margin-bottom:10px;
    border:1px solid #e5e7eb;
}

.pos-product-controls .input-group-text{
    background:#e5e7eb;
    border:1px solid #d1d5db;
    color:#6b7280;
}

.pos-product-controls .form-control{
    border:1px solid #d1d5db;
}

.pos-product-controls .form-control:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 2px rgba(37,99,235,0.1);
}

.pos-product-controls .form-select-sm{
    font-size:0.875rem;
}
</style>
`;

// Inject CSS into page
document.head.insertAdjacentHTML('beforeend', productControlsCSS);
