/**
 * POS Cart Module - Refactored
 * Handles all cart operations with proper state management, race condition prevention,
 * and product_id-based operations
 */

// Cart Module Namespace
window.POSCart = window.POSCart || {};

// Simplified Cart State
POSCart.state = {
    items: [],
    isLoading: false,
    justPaid: false,
    pendingOperations: new Set()
};


// Cart Initialization
POSCart.init = function() {
    console.log('🛒 POS Cart module initializing...');
    
    // Set up event delegation instead of inline handlers
    POSCart.setupEventDelegation();
    
    // Make functions globally available for legacy compatibility
    window.updateQuantity = POSCart.updateQuantity;
    window.updateCartQuantity = POSCart.updateCartQuantity;
    window.removeFromCart = POSCart.removeFromCart;
    window.clearCart = POSCart.clearCart;
    window.refreshRegisterSales = POSCart.refreshRegisterSales;
    
    // Reset payment flag on initialization
    POSCart.state.justPaid = false;
    
    // Failsafe: Clear any ghost cart on initialization
    POSCart.clearCartCompletely();
    
    console.log('✅ POS Cart module initialized successfully');
};

/**
 * Set up simple event delegation for cart interactions
 */
POSCart.setupEventDelegation = function() {
    console.log('🔧 Setting up cart event delegation...');
    
    // Remove existing listeners to prevent duplicates
    const existingHandler = window.cartClickHandler;
    if (existingHandler) {
        document.removeEventListener('click', existingHandler);
        console.log('🗑️ Removed existing click handler');
    }
    
    // Create new click handler
    window.cartClickHandler = function(event) {
        const target = event.target;
        
        // Test if any clicks are working in cart area
        if (target.closest('#cart-items-container')) {
            console.log('🎯 Click detected in cart container:', target);
        }
        
        // Remove item (only button handler now, +/- buttons removed)
        if (target.matches('.btn-remove-item') || target.closest('.btn-remove-item')) {
            event.preventDefault();
            console.log('🗑️ Remove button clicked:', target);
            const button = target.matches('.btn-remove-item') ? target : target.closest('.btn-remove-item');
            const productId = button.dataset.productId;
            console.log('🗑️ Product ID from dataset:', productId);
            if (productId) {
                console.log('🗑️ Calling removeFromCartByProductId with:', productId);
                POSCart.removeFromCartByProductId(productId);
            } else {
                console.error('🗑️ No product ID found on remove button');
            }
        }
    };
    
    // Add the click listener
    document.addEventListener('click', window.cartClickHandler);
    console.log('✅ Click listener added to document');
    
    // Single change handler for quantity inputs
    // Change events DO bubble, but use capture phase for consistency and reliability
    document.addEventListener('change', function(event) {
        console.log('🎧 Change event listener triggered on document (capture phase)');
        const target = event.target;
        
        if (target.matches('.quantity-input')) {
            console.log('🔄 Change event fired for quantity field:', target.dataset.productId, target.value);
            const productId = target.dataset.productId;
            const newQuantity = target.value;
            if (productId && newQuantity) {
                console.log('✅ Updating quantity via change event:', productId, newQuantity);
                POSCart.updateQuantityByProductId(productId, 0, newQuantity);
            }
        }
    }, true); // Use capture phase for better reliability
    
    console.log('✅ All event listeners set up successfully');
    
    // Input handler with debouncing for better typing experience
    // NOTE: Use capture phase (3rd param = true) because input events don't bubble!
    let quantityInputTimeout = null;
    document.addEventListener('input', function(event) {
        console.log('🎧 Input event listener triggered on document (capture phase)');
        const target = event.target;
        
        if (target.matches('.quantity-input')) {
            console.log('📝 Input event fired for quantity field:', target.dataset.productId, target.value);
            const productId = target.dataset.productId;
            const newQuantity = target.value.trim();
            
            // Clear previous timeout
            if (quantityInputTimeout) {
                clearTimeout(quantityInputTimeout);
            }
            
            // Validate input
            const numericValue = parseFloat(newQuantity);
            if (!newQuantity || isNaN(numericValue) || numericValue < 1) {
                console.log('⛔ Invalid input, skipping update');
                return; // Don't update for invalid input
            }
            
            console.log('✅ Valid input, setting timeout for:', productId, newQuantity);
            
            // Set new timeout for debounced update
            quantityInputTimeout = setTimeout(function() {
                console.log('⏰ Timeout fired, updating quantity:', productId, newQuantity);
                if (productId && newQuantity) {
                    POSCart.updateQuantityByProductId(productId, 0, newQuantity);
                }
            }, 800); // 800ms debounce
        }
    }, true); // USE CAPTURE PHASE - input events don't bubble!
    
    // Clear timeout when input loses focus
    document.addEventListener('blur', function(event) {
        const target = event.target;
        
        if (target.matches('.quantity-input')) {
            if (quantityInputTimeout) {
                clearTimeout(quantityInputTimeout);
                quantityInputTimeout = null;
            }
        }
    });
    
    console.log('✅ Cart event delegation set up');
};

/**
 * Direct event binding for remove button
 */
POSCart.bindDirectEvents = function() {
    console.log('🔧 Setting up direct event binding for remove buttons...');
    
    setTimeout(function() {
        const cartContainer = document.getElementById('cart-items-container');
        if (!cartContainer) {
            console.log('⚠️ Cart container not found for direct binding');
            return;
        }
        
        // Only bind remove buttons (no more +/- buttons)
        const removeButtons = cartContainer.querySelectorAll('.btn-remove-item');
        
        console.log('🔍 Found remove buttons:', removeButtons.length);
        
        removeButtons.forEach(function(button) {
            button.onclick = function() {
                const productId = this.dataset.productId;
                if (productId) POSCart.removeFromCartByProductId(productId);
            };
        });
        
        console.log('✅ Direct event binding completed');
    }, 500);
};

/**
 * Load cart from backend with race condition prevention and justPaid protection
 */
POSCart.loadCart = function() {
    console.log('🛒 Loading cart...');
    
    // Block loading if payment just completed
    if (POSCart.state.justPaid) {
        console.log('⛔ Cart load blocked - payment just completed');
        return Promise.resolve(POSCart.state.items);
    }
    
    // Prevent concurrent loads
    if (POSCart.state.isLoading) {
        console.log('⛔ Cart already loading');
        return Promise.resolve(POSCart.state.items);
    }
    
    // Check if DOM elements are ready
    if (!document.getElementById('cart-items-container')) {
        console.log('Cart container not ready, retrying in 100ms...');
        setTimeout(POSCart.loadCart, 100);
        return;
    }
    
    // Clear frontend cart state first to prevent ghost cart
    POSCart.state.items = [];
    POSCart.renderCart();
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('❌ CSRF token not found for cart loading');
        return Promise.reject(new Error('CSRF token not found'));
    }
    
    POSCart.state.isLoading = true;
    
    console.log('📡 Loading cart from backend...');
    
    return fetch(POSApp.config.apiEndpoints.cartGet, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('📦 Cart data received:', data);
        
        // Update state if not blocked by justPaid flag
        if (!POSCart.state.justPaid) {
            POSCart.state.items = data.cart || [];
            console.log('✅ Cart updated:', POSCart.state.items.length, 'items');
        }
        
        POSCart.renderCart();
        return POSCart.state.items;
    })
    .catch(function(error) {
        console.error('❌ Load cart error:', error);
        POSCart.state.items = [];
        POSCart.renderCart();
    })
    .finally(function() {
        POSCart.state.isLoading = false;
        console.log('🏁 Cart loading completed');
    });
};

/**
 * Render cart in the UI with event delegation and product_id-based controls
 */
POSCart.renderCart = function() {
    console.log('🎨 Rendering cart with', POSCart.state.items.length, 'items');
    
    try {
        const tbody = document.getElementById('cart-items-container');
        const itemCountElement = document.getElementById('itemCount');
        const cartTotalElement = document.getElementById('cartTotal');
        
        if (!tbody) {
            console.log('⚠️ Cart container not found, DOM may not be ready');
            return;
        }
    
        if (POSCart.state.items.length === 0) {
            console.log('🛒 Cart is empty, rendering empty state');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted" style="padding: 20px;">
                        <i class="bi bi-cart3" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        <div style="font-size: 1rem; font-weight: 600;">No items in cart</div>
                        <div style="font-size: 0.875rem; color: #6c757d;">Start by searching for products</div>
                    </td>
                </tr>
            `;
            POSCart.updateCartSummary(0, 0);
            return;
        }
        
        let html = '';
        let total = 0;
        let itemCount = 0;
        
        for (let i = 0; i < POSCart.state.items.length; i++) {
            const item = POSCart.state.items[i];
            console.log('📦 Processing cart item:', item);
            
            const productInfo = POSCart.extractProductInfo(item);
            const itemTotal = productInfo.sellingPrice * (item.quantity || 1);
            total += itemTotal;
            itemCount += (item.quantity || 1);
            
            html += POSCart.generateCartItemHTML(item, productInfo, i);
            console.log('✅ Generated HTML for item:', productInfo.productId, 'with quantity input data-product-id:', productInfo.productId);
        }
        
        tbody.innerHTML = html;
        POSCart.updateCartSummary(total, itemCount);
        
        console.log('✅ Cart rendered successfully - Total: MWK', total.toFixed(2), 'Items:', itemCount);
        
        // Bind direct events to quantity inputs IMMEDIATELY after rendering
        console.log('🔌 Binding direct input events to quantity inputs...');
        const quantityInputs = tbody.querySelectorAll('.quantity-input');
        console.log('🔍 Found', quantityInputs.length, 'quantity inputs');
        
        // Use a shared timeout object to track timeouts per product
        if (!POSCart._quantityTimeouts) {
            POSCart._quantityTimeouts = {};
        }
        
        quantityInputs.forEach(function(input, index) {
            const productId = input.dataset.productId;
            console.log('🔌 Binding input event to quantity input:', index, 'productId:', productId);
            
            // Bind oninput event directly with DEBOUNCING
            input.oninput = function() {
                console.log('📝 Input typed on quantity:', productId, 'value:', this.value);
                const newQuantity = this.value.trim();
                const numericValue = parseFloat(newQuantity);
                
                if (!newQuantity || isNaN(numericValue) || numericValue < 1) {
                    console.log('⛔ Invalid input, skipping');
                    return;
                }
                
                // Clear any existing timeout for this product
                if (POSCart._quantityTimeouts[productId]) {
                    clearTimeout(POSCart._quantityTimeouts[productId]);
                    console.log('🔄 Cleared previous timeout for product:', productId);
                }
                
                // Set new debounced timeout
                console.log('⏰ Setting 800ms debounce timeout for product:', productId);
                POSCart._quantityTimeouts[productId] = setTimeout(function() {
                    console.log('⏰🔴 DEBOUNCE TIMEOUT FIRED - updating quantity:', productId, 'to:', newQuantity);
                    POSCart.updateQuantityByProductId(productId, 0, newQuantity);
                    delete POSCart._quantityTimeouts[productId];
                }, 800);
            };
        });
        
        console.log('🔌 Direct input binding completed');
        
        // Bind other events (buttons) after rendering
        POSCart.bindDirectEvents();
        
    } catch (error) {
        console.error('❌ Error rendering cart:', error);
        // Don't throw error to prevent page from loading
    }
};

/**
 * Extract product information from cart item (handles different data structures)
 */
POSCart.extractProductInfo = function(item) {
    console.log('📦 Processing cart item:', item);
    
    let productName, productSku, sellingPrice, costPrice, productId;
    
    if (item.product && item.product.id) {
        productName = item.product.name || 'Unknown Product';
        productSku = item.product.sku || '';
        sellingPrice = parseFloat(item.product.selling_price || item.product.price || 0);
        costPrice = parseFloat(item.product.purchase_price || 0);
        productId = item.product.id;
        console.log('✅ Extracted from item.product:', productId);
    } else if (item.id || item.product_id) {
        productName = item.name || 'Unknown Product';
        productSku = item.sku || '';
        sellingPrice = parseFloat(item.price || item.selling_price || 0);
        costPrice = parseFloat(item.purchase_price || 0);
        productId = item.id || item.product_id;
        console.log('✅ Extracted from item properties:', productId);
    } else {
        console.error('❌ Could not extract product ID from item:', item);
        productName = 'Unknown Product';
        productSku = '';
        sellingPrice = 0;
        costPrice = 0;
        productId = '';
    }
    
    console.log('📋 Product info extracted:', {
        name: productName,
        sku: productSku,
        sellingPrice: sellingPrice,
        costPrice: costPrice,
        productId: productId
    });
    
    return {
        name: productName,
        sku: productSku,
        sellingPrice: sellingPrice,
        costPrice: costPrice,
        productId: productId
    };
};

/**
 * Generate HTML for a single cart item with event delegation
 */
POSCart.generateCartItemHTML = function(item, productInfo, index) {
    const quantity = item.quantity || 1;
    const itemTotal = productInfo.sellingPrice * quantity;
    
    // Check if this input is currently focused and get its value
    const activeElement = document.activeElement;
    const isInputFocused = activeElement && activeElement.matches('.quantity-input') && 
                          activeElement.dataset.productId === productInfo.productId;
    const currentInputValue = isInputFocused ? activeElement.value : quantity;
    
    let html = '<tr>';
    html += '<td style="padding: 12px 8px; vertical-align: middle;">';
    html += '<div><strong>' + productInfo.name + '</strong></div>';
    if (productInfo.sku) {
        html += '<div><small class="text-muted">SKU: ' + productInfo.sku + '</small></div>';
    }
    html += '</td>';
    
    // Quantity input only (removed +/- buttons)
    html += '<td class="text-center" style="padding: 12px 8px; vertical-align: middle;">';
    html += '<input type="number" class="form-control form-control-sm text-center quantity-input" value="' + currentInputValue + '" min="1" max="999999" data-product-id="' + productInfo.productId + '" style="width: 80px; margin: 0 auto;">';
    html += '</td>';
    
    html += '<td class="text-center" style="padding: 12px 8px; vertical-align: middle;">';
    html += 'MWK ' + productInfo.costPrice.toFixed(2);
    html += '</td>';
    
    html += '<td class="text-center" style="padding: 12px 8px; vertical-align: middle;">';
    html += 'MWK ' + productInfo.sellingPrice.toFixed(2);
    html += '</td>';
    
    // Remove button with event delegation
    html += '<td class="text-center" style="padding: 12px 8px; vertical-align: middle;">';
    html += '<button class="btn btn-sm btn-outline-danger btn-remove-item" data-product-id="' + productInfo.productId + '">';
    html += '<i class="bi bi-trash"></i>';
    html += '</button>';
    html += '</td>';
    
    html += '</tr>';
    
    return html;
};

/**
 * Update cart summary (item count and totals)
 */
POSCart.updateCartSummary = function(total, itemCount) {
    const itemCountElement = document.getElementById('itemCount');
    const cartTotalElement = document.getElementById('cartTotal');
    const totalPayableElement = document.getElementById('totalPayable');
    
    if (itemCountElement) itemCountElement.textContent = itemCount;
    if (cartTotalElement) cartTotalElement.textContent = POSApp.utils.formatCurrency(total);
    
    if (totalPayableElement) {
        totalPayableElement.textContent = POSApp.utils.formatCurrency(total);
        console.log('💰 Updated total payable to: MWK', total.toFixed(2));
    }
};

/**
 * Update item quantity by product ID (new method)
 */
POSCart.updateQuantityByProductId = function(productId, change, newQuantity = null) {
    console.log('🔄 Updating quantity for product:', productId, 'change:', change, 'newQuantity:', newQuantity);
    
    // Find item in cart
    const itemIndex = POSCart.findCartItemIndex(productId);
    if (itemIndex === -1) {
        console.error('❌ Product not found in cart:', productId);
        return;
    }
    
    const currentItem = POSCart.state.items[itemIndex];
    const currentQty = parseFloat(currentItem.quantity || 0);
    
    // Calculate new quantity
    let targetQty;
    if (newQuantity !== null) {
        targetQty = Math.max(1, parseFloat(newQuantity));
    } else {
        targetQty = Math.max(1, currentQty + change);
    }
    
    if (isNaN(targetQty) || targetQty < 1) {
        console.log('⛔ Invalid quantity:', targetQty);
        POSCart.renderCart();
        return;
    }
    
    // Prevent duplicate operations
    const operationKey = `update-${productId}-${targetQty}`;
    if (POSCart.state.pendingOperations.has(operationKey)) {
        console.log('⛔ Operation already pending:', operationKey);
        return;
    }
    
    POSCart.state.pendingOperations.add(operationKey);
    
    console.log('📤 Sending quantity update request:', {
        productId: productId,
        quantity: Math.round(targetQty),
        currentQuantity: currentQty
    });
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('❌ CSRF token not found');
        POSCart.state.pendingOperations.delete(operationKey);
        return;
    }
    
    console.log('📡 Sending cart update to:', POSApp.config.apiEndpoints.cartUpdate);
    
    return fetch(POSApp.config.apiEndpoints.cartUpdate, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: Math.round(targetQty)
        })
    })
    .then(function(response) {
        console.log('📡 Response status:', response.status);
        return response.json().then(function(data) {
            return {
                status: response.status,
                data: data,
                ok: response.ok
            };
        });
    })
    .then(function(result) {
        const { status, data, ok } = result;
        console.log('📦 Cart update response:', data);
        
        // Handle error responses (400, 404, etc)
        if (!ok) {
            console.error('❌ Server error (status ' + status + '):', data.error);
            POSApp.utils.showNotification('❌ ' + (data.error || 'Error updating quantity'), 'error');
            POSCart.renderCart();
            POSCart.state.pendingOperations.delete(operationKey);
            return;
        }
        
        if (data.success) {
            console.log('✅ Cart before update:', POSCart.state.items.length, 'items');
            
            // Update state only if not just paid
            if (!POSCart.state.justPaid) {
                POSCart.state.items = data.cart || [];
                console.log('✅ Cart state updated:', POSCart.state.items.length, 'items');
            } else {
                console.log('⛔ Cart state update skipped - just paid flag set');
            }
            
            POSCart.renderCart();
            
            // Show warning if present
            if (data.warning) {
                POSApp.utils.showNotification(data.warning, 'warning');
            }
        } else {
            console.error('❌ Error updating quantity:', data.error);
            POSApp.utils.showNotification('❌ ' + (data.error || 'Unknown error'), 'error');
            POSCart.renderCart();
        }
    })
    .catch(function(error) {
        console.error('❌ Update cart quantity error:', error);
        POSApp.utils.showNotification('❌ Error updating quantity. Please try again.', 'error');
        POSCart.renderCart();
    })
    .finally(function() {
        POSCart.state.pendingOperations.delete(operationKey);
    });
};

/**
 * Find cart item index by product ID
 */
POSCart.findCartItemIndex = function(productId) {
    for (let i = 0; i < POSCart.state.items.length; i++) {
        const item = POSCart.state.items[i];
        const itemId = item.product_id || item.id || (item.product && item.product.id) || '';
        if (itemId.toString() === productId.toString()) {
            return i;
        }
    }
    return -1;
};

/**
 * Legacy functions for backward compatibility (index-based)
 */
POSCart.updateQuantity = function(idx, change) {
    console.log('⚠️ Using legacy updateQuantity (index-based):', idx, change);
    
    if (idx >= 0 && idx < POSCart.state.items.length) {
        const item = POSCart.state.items[idx];
        const productId = item.product_id || item.id || (item.product && item.product.id) || '';
        if (productId) {
            return POSCart.updateQuantityByProductId(productId, change);
        }
    }
    
    console.error('❌ Invalid index or product ID not found:', idx);
};

POSCart.updateCartQuantity = function(idx, newQuantity) {
    console.log('⚠️ Using legacy updateCartQuantity (index-based):', idx, newQuantity);
    
    if (idx >= 0 && idx < POSCart.state.items.length) {
        const item = POSCart.state.items[idx];
        const productId = item.product_id || item.id || (item.product && item.product.id) || '';
        if (productId) {
            return POSCart.updateQuantityByProductId(productId, 0, newQuantity);
        }
    }
    
    console.error('❌ Invalid index or product ID not found:', idx);
};

/**
 * Remove item from cart by product ID (new method)
 */
POSCart.removeFromCartByProductId = function(productId) {
    console.log('🗑️ Removing item from cart:', productId);
    
    // Find item in cart
    const itemIndex = POSCart.findCartItemIndex(productId);
    if (itemIndex === -1) {
        console.error('❌ Product not found in cart:', productId);
        return;
    }
    
    const item = POSCart.state.items[itemIndex];
    const productName = item.name || (item.product && item.product.name) || 'Unknown Product';
    
    if (!confirm(`Remove "${productName}" from cart?`)) {
        console.log('⛔ Item removal cancelled by user');
        return;
    }
    
    // Prevent duplicate operations
    const operationKey = `remove-${productId}`;
    if (POSCart.state.pendingOperations.has(operationKey)) {
        console.log('⛔ Remove operation already pending');
        return;
    }
    
    POSCart.state.pendingOperations.add(operationKey);
    
    console.log('📤 Sending remove request for product:', productId);
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('❌ CSRF token not found');
        POSCart.state.pendingOperations.delete(operationKey);
        return;
    }
    
    console.log('📡 Sending cart remove to:', POSApp.config.apiEndpoints.cartRemove);
    
    return fetch(POSApp.config.apiEndpoints.cartRemove, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('📦 Remove response:', data);
        
        if (data.success) {
            // Update state only if not just paid
            if (!POSCart.state.justPaid) {
                POSCart.state.items = data.cart || [];
                console.log('✅ Item removed from cart');
            } else {
                console.log('⛔ Cart state update skipped - just paid flag set');
            }
            
            POSCart.renderCart();
            POSApp.utils.showNotification('Item removed from cart', 'success');
        } else {
            console.error('❌ Error removing item:', data.error);
            POSApp.utils.showNotification('Error removing item: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(function(error) {
        console.error('❌ Remove from cart error:', error);
        POSApp.utils.showNotification('Error removing item. Please try again.', 'error');
    })
    .finally(function() {
        POSCart.state.pendingOperations.delete(operationKey);
    });
};

/**
 * Failsafe: Clear cart completely
 */
POSCart.clearCartCompletely = function() {
    console.log('🧹 Clearing cart completely...');
    
    // Clear frontend state
    POSCart.state.items = [];
    POSCart.renderCart();
    
    // Clear backend cart
    const csrfToken = POSApp.utils.getCsrfToken();
    if (csrfToken) {
        fetch(POSApp.config.apiEndpoints.cartClear, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).catch(function(error) {
            console.error('❌ Clear cart error:', error);
        });
    }
};

/**
 * Legacy removeFromCart for backward compatibility (index-based)
 */
POSCart.removeFromCart = function(idx) {
    console.log('⚠️ Using legacy removeFromCart (index-based):', idx);
    
    if (idx >= 0 && idx < POSCart.state.items.length) {
        const item = POSCart.state.items[idx];
        const productId = item.product_id || item.id || (item.product && item.product.id) || '';
        if (productId) {
            return POSCart.removeFromCartByProductId(productId);
        }
    }
    
    console.error('❌ Invalid index or product ID not found:', idx);
    POSApp.utils.showNotification('Item not found in cart', 'error');
};

/**
 * Clear entire cart with enhanced state management
 */
POSCart.clearCart = function() {
    console.log('🗑️ Clear cart requested by user');
    
    if (!confirm('Are you sure you want to clear the cart?')) {
        console.log('⛔ Cart clear cancelled by user');
        return;
    }
    
    // Prevent duplicate operations
    const operationKey = 'clear-cart';
    if (POSCart.state.pendingOperations.has(operationKey)) {
        console.log('⛔ Clear operation already pending');
        return;
    }
    
    POSCart.state.pendingOperations.add(operationKey);
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('❌ CSRF token not found');
        POSCart.state.pendingOperations.delete(operationKey);
        return;
    }
    
    console.log('📤 Sending clear cart request');
    
    return fetch(POSApp.config.apiEndpoints.cartClear, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
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
        console.log('📦 Clear cart response:', data);
        
        if (data.success) {
            console.log('✅ Cart before clear:', POSCart.state.items.length, 'items');
            
            // Update state
            POSCart.state.items = data.cart || [];
            POSCart.renderCart();
            
            console.log('✅ Cart cleared successfully:', POSCart.state.items.length, 'items remaining');
            POSApp.utils.showNotification('Cart cleared', 'info');
        } else {
            console.error('❌ Error clearing cart:', data.error);
            POSApp.utils.showNotification('Error clearing cart: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(function(error) {
        console.error('❌ Clear cart error:', error);
        POSApp.utils.showNotification('Error clearing cart. Please try again.', 'error');
    })
    .finally(function() {
        POSCart.state.pendingOperations.delete(operationKey);
        console.log('🏁 Clear cart operation completed');
    });
};

/**
 * Clear cart after payment with justPaid flag protection
 */
POSCart.clearCartAfterPayment = function() {
    console.log('💳 Clearing cart after payment...');
    
    // Set justPaid flag IMMEDIATELY to block reloads
    POSCart.state.justPaid = true;
    console.log('🚩 justPaid flag set - blocking cart reloads');
    
    // Clear frontend immediately
    POSCart.state.items = [];
    POSCart.renderCart();
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('❌ CSRF token not found');
        // Reset flag after delay
        setTimeout(() => POSCart.state.justPaid = false, 3000);
        return;
    }
    
    // Clear backend cart
    return fetch(POSApp.config.apiEndpoints.cartClear, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
        return response.json();
    })
    .then(function(data) {
        console.log('📦 Backend cart cleared:', data.success);
        
        // Refresh product data
        if (window.POSProducts && window.POSProducts.refreshProductData) {
            window.POSProducts.refreshProductData();
        }
    })
    .catch(function(error) {
        console.error('❌ Clear cart error:', error);
    })
    .finally(function() {
        // Reset justPaid flag after 3 seconds
        setTimeout(() => {
            POSCart.state.justPaid = false;
            console.log('🚩 justPaid flag reset - cart reloads allowed');
        }, 3000);
    });
};


/**
 * Load register sales data with enhanced debugging
 */
POSCart.loadRegisterSales = function() {
    console.log('💰 Loading current register sales data...');
    
    const csrfToken = POSApp.utils.getCsrfToken();
    if (!csrfToken) {
        console.error('❌ CSRF token not found for register sales');
        return;
    }
    
    console.log('📡 Fetching register sales from:', POSApp.config.apiEndpoints.registerDetails);
    
    return fetch(POSApp.config.apiEndpoints.registerDetails, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(function(response) {
        console.log('📡 Register sales response status:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(function(data) {
        console.log('📦 Register sales response received:', data);
        console.log('📋 Full response structure:', JSON.stringify(data, null, 2));
        
        if (data.success) {
            // Handle different response structures
            let sales = [];
            let totalSales = 0;
            let totalTransactions = 0;
            
            if (data.register) {
                // Response has register object with different structure
                console.log('📊 Register object found:', data.register);
                
                // Extract sales from products array
                sales = data.register.products || [];
                totalSales = data.register.totals ? data.register.totals.total_sales || 0 : 0;
                totalTransactions = sales.length; // Count products as transactions
                
                console.log('✅ Using register object data');
                console.log('📈 Products (sales):', sales.length, 'items');
                console.log('💵 Total sales:', totalSales);
                console.log('🧮 Total transactions:', totalTransactions);
            } else if (data.sales) {
                // Response has direct sales array
                console.log('📊 Sales array found:', data.sales);
                sales = data.sales || [];
                totalSales = data.total_sales || 0;
                totalTransactions = data.total_transactions || 0;
                console.log('✅ Using direct sales data');
            } else {
                console.log('⚠️ No sales data found in response');
                console.log('🔍 Available keys:', Object.keys(data));
            }
            
            console.log('📊 Final processed data - Sales:', sales.length, 'Total:', totalSales, 'Transactions:', totalTransactions);
            POSCart.displayRegisterSales(sales, totalSales, totalTransactions);
        } else {
            console.error('❌ Error loading register sales:', data.error);
            POSCart.displayRegisterSalesError(data.error || 'Unknown error');
        }
    })
    .catch(function(error) {
        console.error('❌ Error loading register sales:', error);
        POSCart.displayRegisterSalesError('Failed to load register sales data');
    });
};

/**
 * Display register sales in UI with enhanced debugging
 */
POSCart.displayRegisterSales = function(sales, totalSales, totalTransactions) {
    console.log('🎨 Displaying register sales:', sales.length, 'transactions, Total:', totalSales);
    
    // Update summary cards
    const totalSalesElement = document.getElementById('registerTotalSales');
    if (totalSalesElement) {
        totalSalesElement.textContent = POSApp.utils.formatCurrency(totalSales);
        console.log('💰 Updated total sales display:', POSApp.utils.formatCurrency(totalSales));
    } else {
        console.log('⚠️ Total sales element not found');
    }
    
    const totalTransactionsElement = document.getElementById('registerTotalTransactions');
    if (totalTransactionsElement) {
        totalTransactionsElement.textContent = totalTransactions;
        console.log('🧮 Updated total transactions display:', totalTransactions);
    } else {
        console.log('⚠️ Total transactions element not found');
    }
    
    // Update sales table
    const salesTable = document.getElementById('registerSalesTable');
    if (!salesTable) {
        console.log('⚠️ Sales table element not found');
        return;
    }
    
    if (sales.length === 0) {
        console.log('📊 No sales to display, showing empty state');
        salesTable.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No sales in current register</td></tr>';
        return;
    }
    
    console.log('📊 Generating sales table HTML for', sales.length, 'items');
    
    let html = '';
    for (let i = 0; i < sales.length; i++) {
        const sale = sales[i];
        
        // Handle different data structures
        const productName = sale.name || 'Unknown Product';
        const quantity = sale.quantity || 0;
        const total = sale.total_amount || 0;
        const sku = sale.sku || '';
        const time = sale.created_at ? new Date(sale.created_at).toLocaleTimeString() : 'N/A';
        
        html += '<tr>';
        html += '<td>' + (sku || 'N/A') + '</td>';
        html += '<td>' + productName + '</td>';
        html += '<td>' + quantity + '</td>';
        html += '<td>Cash</td>'; // Default payment method since not in products array
        html += '<td class="fw-bold">' + POSApp.utils.formatCurrency(total) + '</td>';
        html += '<td>' + time + '</td>';
        html += '</tr>';
    }
    
    salesTable.innerHTML = html;
    console.log('✅ Register sales table rendered successfully');
};

/**
 * Display register sales error with enhanced debugging
 */
POSCart.displayRegisterSalesError = function(error) {
    console.log('❌ Displaying register sales error:', error);
    
    const totalSalesElement = document.getElementById('registerTotalSales');
    if (totalSalesElement) {
        totalSalesElement.textContent = 'MWK 0.00';
        console.log('💰 Reset total sales display to 0');
    }
    
    const totalTransactionsElement = document.getElementById('registerTotalTransactions');
    if (totalTransactionsElement) {
        totalTransactionsElement.textContent = '0';
        console.log('🧮 Reset total transactions display to 0');
    }
    
    const salesTable = document.getElementById('registerSalesTable');
    if (salesTable) {
        salesTable.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error: ' + error + '</td></tr>';
        console.log('📊 Displayed error in sales table');
    } else {
        console.log('⚠️ Sales table element not found for error display');
    }
};

/**
 * Refresh register sales with debugging
 */
POSCart.refreshRegisterSales = function() {
    console.log('🔄 Refresh register sales requested');
    return POSCart.loadRegisterSales();
};
