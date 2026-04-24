/**
 * POS Products Module
 * Handles product search, filtering, and management
 */

// Products Module Namespace
window.POSProducts = window.POSProducts || {};

// Products State
POSProducts.state = {
    productsData: [],
    isLoading: false,
    retryCount: 0,
    maxRetries: 3,
    retryDelay: 1000,
    cacheKey: 'pos_products_cache',
    cacheExpiry: 5 * 60 * 1000, // 5 minutes
    searchTimeout: null
};

// Products Initialization
POSProducts.init = function() {
    console.log('POS Products module initializing...');
    
    // Make functions globally available for onclick handlers
    window.filterProducts = POSProducts.filterProducts;
    window.addToCartClickDelayed = POSProducts.addToCartClickDelayed;
    window.addToCartClick = POSProducts.addToCartClick;
    window.loadProducts = POSProducts.loadProducts;
    
    // Initialize products data from server if available
    if (typeof productsData !== 'undefined' && productsData.length > 0) {
        POSProducts.state.productsData = productsData;
        console.log('Products loaded from server in POSProducts module:', POSProducts.state.productsData.length);
    } else {
        console.log('Products data not available yet, will be initialized later');
    }
    
    console.log('POS Products module initialized');
};

// Function to initialize products data after DOM is ready
POSProducts.initializeProductsData = function(data) {
    if (data && data.length > 0) {
        POSProducts.state.productsData = data;
        console.log('Products data initialized in POSProducts:', POSProducts.state.productsData.length);
    }
};

/**
 * Filter products based on search query
 */
POSProducts.filterProducts = function() {
    console.log('filterProducts called');
    
    const searchInput = document.getElementById('cartSearch');
    const dropdown = document.getElementById('searchDropdown');
    
    if (!searchInput || !dropdown) {
        console.log('Elements not found');
        return;
    }
    
    const query = searchInput.value.toLowerCase();
    console.log('Query:', query);
    console.log('Products available:', POSProducts.state.productsData.length);
    
    // Show loading state for slow networks if products are loaded
    if (POSProducts.state.productsData.length > 0 && query.length >= 2) {
        dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">Searching...</div>';
        dropdown.style.display = 'block';
    }
    
    // Allow search even if products are still loading (for better UX)
    if (POSProducts.state.productsData.length === 0) {
        console.log('Products not loaded yet, but allowing search for better UX');
        dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">Loading products... Please wait.</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    if (query.length < 2) {
        dropdown.style.display = 'none';
        return;
    }
    
    // Debounce search for better performance
    clearTimeout(POSProducts.state.searchTimeout);
    POSProducts.state.searchTimeout = setTimeout(function() {
        const results = [];
        for (let i = 0; i < POSProducts.state.productsData.length; i++) {
            const product = POSProducts.state.productsData[i];
            if (product.name && product.name.toLowerCase().indexOf(query) !== -1) {
                results.push(product);
            }
        }
        
        console.log('Results:', results.length);
    
        if (results.length > 0) {
            let html = '';
            for (let i = 0; i < Math.min(results.length, 10); i++) {
                const product = results[i];
                const price = parseFloat(product.selling_price) || 0;
                html += '<div onclick="addToCartClickDelayed(' + product.id + ', 1, event)" class="search-result-item">';
                html += '<div class="result-left">';
                html += '<div class="result-name">' + product.name + '</div>';
                html += '<div class="result-meta">Stock: ' + (product.current_stock || 0) + '</div>';
                html += '</div>';
                html += '<div class="result-right">';
                html += '<div class="result-price">' + POSApp.utils.formatCurrency(price) + '</div>';
                html += '</div>';
                html += '</div>';
            }
            
            dropdown.innerHTML = html;
            dropdown.style.display = 'block';
        } else {
            dropdown.innerHTML = '<div style="padding: 12px; text-align: center; color: #666;">No products found</div>';
            dropdown.style.display = 'block';
        }
    }, 300);
};

/**
 * Load products from server with caching and retry logic
 */
POSProducts.loadProducts = function() {
    if (POSProducts.state.isLoading) {
        console.log('Already loading products, skipping...');
        return;
    }
    
    POSProducts.state.isLoading = true;
    console.log('Loading products...');
    
    // Try to load from cache first
    try {
        const cachedData = localStorage.getItem(POSProducts.state.cacheKey);
        if (cachedData) {
            const parsed = JSON.parse(cachedData);
            if (Date.now() - parsed.timestamp < POSProducts.state.cacheExpiry) {
                console.log('Loading products from cache:', parsed.products.length);
                POSProducts.state.productsData = parsed.products;
                POSProducts.updateProductLoadingState(true);
                POSProducts.state.isLoading = false;
                return;
            }
        }
    } catch (error) {
        console.warn('LocalStorage not available or corrupted:', error);
        // Continue with network request if localStorage fails
    }
    
    POSProducts.updateProductLoadingState(false);
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found for product loading');
        POSProducts.state.isLoading = false;
        return;
    }
    
    // Add timeout for slow networks
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
    
    fetch(POSApp.config.apiEndpoints.productsRefresh, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Cache-Control': 'no-cache'
        },
        signal: controller.signal
    })
    .then(function(response) {
        clearTimeout(timeoutId);
        console.log('Product load response status:', response.status);
        
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('Product load response:', data);
        
        if (data.success && data.products) {
            POSProducts.state.productsData = data.products;
            console.log('Products loaded successfully:', POSProducts.state.productsData.length);
            
            // Cache the results
            try {
                localStorage.setItem(POSProducts.state.cacheKey, JSON.stringify({
                    products: POSProducts.state.productsData,
                    timestamp: Date.now()
                }));
            } catch (error) {
                console.warn('Failed to cache products in localStorage:', error);
                // Continue without caching if localStorage fails
            }
            
            POSProducts.updateProductLoadingState(true);
            POSProducts.state.retryCount = 0; // Reset retry count on success
        } else {
            console.error('Product load failed:', data.error || 'Unknown error');
            POSProducts.updateProductLoadingState(false);
            POSProducts.retryLoad();
        }
    })
    .catch(function(error) {
        clearTimeout(timeoutId);
        console.error('Error loading products:', error);
        POSProducts.updateProductLoadingState(false);
        
        // Don't retry if it's an abort (timeout)
        if (error.name !== 'AbortError') {
            POSProducts.retryLoad();
        }
    })
    .finally(function() {
        POSProducts.state.isLoading = false;
    });
};

/**
 * Retry loading products with exponential backoff
 */
POSProducts.retryLoad = function() {
    if (POSProducts.state.retryCount < POSProducts.state.maxRetries) {
        POSProducts.state.retryCount++;
        console.log(`Retrying product load... Attempt ${POSProducts.state.retryCount}/${POSProducts.state.maxRetries}`);
        
        // Exponential backoff
        const delay = POSProducts.state.retryDelay * Math.pow(2, POSProducts.state.retryCount - 1);
        setTimeout(POSProducts.loadProducts, delay);
    } else {
        console.error('Max retries reached. Please refresh the page.');
        POSProducts.showNetworkError();
    }
};

/**
 * Update product loading state UI
 */
POSProducts.updateProductLoadingState = function(loaded) {
    const searchInput = document.getElementById('cartSearch');
    const loadingIndicator = document.getElementById('productLoadingIndicator');
    
    if (loadingIndicator) {
        if (loaded) {
            loadingIndicator.style.display = 'none';
            searchInput.disabled = false;
            searchInput.placeholder = 'Search products...';
        } else {
            loadingIndicator.style.display = 'block';
            searchInput.disabled = true;
            searchInput.placeholder = 'Loading products...';
        }
    }
};

/**
 * Show network error message
 */
POSProducts.showNetworkError = function() {
    const searchContainer = document.querySelector('.search-container');
    if (searchContainer) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-warning';
        errorDiv.innerHTML = `
            <strong>Network Error:</strong> Unable to load products. 
            <button onclick="loadProducts()" class="btn btn-sm btn-primary ms-2">Retry</button>
        `;
        searchContainer.appendChild(errorDiv);
    }
};

/**
 * Add product to cart with delay to prevent automatic clicks
 */
POSProducts.addToCartClickDelayed = function(productId, quantity, event) {
    console.log('Delayed addToCartClick called:', productId, quantity);
    console.log('Event type:', event ? event.type : 'No event object');
    console.log('Event target:', event ? event.target : 'No event object');
    console.log('Time since page load:', Date.now() - performance.timing.navigationStart);
    
    // Prevent automatic clicks that happen too quickly
    const timeSincePageLoad = Date.now() - performance.timing.navigationStart;
    if (timeSincePageLoad < 500) {
        console.warn('Blocking automatic click - too soon after page load');
        return;
    }
    
    // Check if this is a real user click (has mouse coordinates)
    if (event && event.clientX === 0 && event.clientY === 0) {
        console.warn('Blocking automatic click - no mouse coordinates');
        return;
    }
    
    // If it passes checks, proceed with normal addToCartClick
    POSProducts.addToCartClick(productId, quantity);
};

/**
 * Add product to cart
 */
POSProducts.addToCartClick = function(productId, quantity) {
    console.log('Adding to cart:', productId, quantity);
    console.log('Call stack trace:', new Error().stack);
    console.log('Event type:', event ? event.type : 'No event object');
    console.log('Event target:', event ? event.target : 'No event object');
    
    const searchInput = document.getElementById('cartSearch');
    const dropdown = document.getElementById('searchDropdown');
    
    if (searchInput) searchInput.value = '';
    if (dropdown) dropdown.style.display = 'none';
    
    // Get CSRF token first
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('CSRF token not found');
        alert('Error: Security token not found');
        return;
    }
    
    const requestData = {
        product_id: productId,
        quantity: quantity
    };
    
    console.log('📤 Sending add to cart request:', requestData);
    console.log('📡 API endpoint:', POSApp.config.apiEndpoints.cartAdd);
    console.log('🔍 Request body:', JSON.stringify(requestData, null, 2));
    console.log('🔍 Request headers:', {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken ? 'present' : 'missing',
        'X-Requested-With': 'XMLHttpRequest'
    });
    
    fetch(POSApp.config.apiEndpoints.cartAdd, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(requestData)
    })
    .then(function(response) {
        if (!response.ok) {
            return response.json().then(function(errorData) {
                const serverMessage = errorData && (errorData.error || errorData.message)
                    ? (errorData.error || errorData.message)
                    : null;

                if (serverMessage) {
                    throw new Error(serverMessage);
                }

                throw new Error('Request failed with status ' + response.status + ' ' + response.statusText);
            }).catch(function(parseError) {
                throw new Error('Request failed with status ' + response.status + ' ' + response.statusText + (parseError && parseError.message ? ' (' + parseError.message + ')' : ''));
            });
        }
        return response.json();
    })
    .then(function(data) {
        console.log('Cart API response:', data);
        
        if (data.success) {
            if (data.cart && Array.isArray(data.cart)) {
                if (window.POSCart) {
                    window.POSCart.state.items = data.cart;
                }
            } else {
                console.warn('Cart data is not in expected format:', data.cart);
                if (window.POSCart) {
                    window.POSCart.state.items = [];
                }
            }
            
            console.log('Updated cart array:', window.POSCart ? window.POSCart.state.items : 'Cart module not loaded');
            
            if (window.POSCart) {
                window.POSCart.renderCart();
            }
            
            const product = POSProducts.state.productsData.find(function(p) { return p.id == productId; });
            if (product) {
                POSApp.utils.showNotification(product.name + ' added to cart', 'success');
            }
            
            // Show warning if present (for low stock items)
            if (data.warning) {
                POSApp.utils.showNotification(data.warning, 'warning');
            }
            
            console.log('Product added to cart successfully:', data);
        } else {
            console.error('Error adding to cart:', data.error);
            const errorMessage = data.error || 'Unknown error';
            const alertType = errorMessage.toLowerCase().includes('out of stock') ? 'warning' : 'danger';
            POSApp.utils.showNotification(errorMessage, alertType);
        }
    })
    .catch(function(error) {
        console.error('Add to cart error:', error);
        const errorMessage = error.message || 'Error adding product to cart.';
        const alertType = errorMessage.toLowerCase().includes('out of stock') ? 'warning' : 'danger';
        POSApp.utils.showNotification(errorMessage, alertType);
    });
};

/**
 * Refresh product data after sale
 */
POSProducts.refreshProductData = function() {
    return new Promise(function(resolve, reject) {
        try {
            console.log('Starting product data refresh...');
            
            fetch(POSApp.config.apiEndpoints.productsRefresh, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to refresh products');
                }
                
                console.log('Before refresh - productsData length:', POSProducts.state.productsData.length);
                
                const oldProduct = POSProducts.state.productsData.find(function(p) { return p.name && p.name.toLowerCase().includes('amox'); });
                const newProduct = data.products.find(function(p) { return p.name && p.name.toLowerCase().includes('amox'); });
                
                console.log('Old product stock:', oldProduct ? oldProduct.current_stock : 'Not found');
                console.log('New product stock:', newProduct ? newProduct.current_stock : 'Not found');
                
                POSProducts.state.productsData.length = 0;
                for (let k = 0; k < data.products.length; k++) {
                    POSProducts.state.productsData.push(data.products[k]);
                }
                
                console.log('After refresh - productsData length:', POSProducts.state.productsData.length);
                console.log('Product data refreshed successfully');
                
                const updatedProduct = POSProducts.state.productsData.find(function(p) { return p.name && p.name.toLowerCase().includes('amox'); });
                console.log('Updated product stock:', updatedProduct ? updatedProduct.current_stock : 'Not found');
                
                resolve(true);
            })
            .catch(function(error) {
                console.error('Error refreshing product data:', error);
                reject(error);
            });
        } catch (e) {
            console.error('Error in refreshProductData:', e);
            reject(e);
        }
    });
};
