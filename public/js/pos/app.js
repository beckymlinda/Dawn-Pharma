/**
 * POS Application - Main App Module
 * Handles application initialization, utilities, and global state management
 */

// Global POS Application Namespace
window.POSApp = window.POSApp || {};

// Application State
POSApp.state = {
    initialized: false,
    cart: [],
    productsData: [],
    isLoading: false,
    retryCount: 0,
    maxRetries: 3,
    retryDelay: 1000,
    cacheKey: 'pos_products_cache',
    cacheExpiry: 5 * 60 * 1000, // 5 minutes
    searchTimeout: null
};

// Application Configuration
POSApp.config = {
    apiEndpoints: {
        cartAdd: '/pos/cart/add',
        cartGet: '/pos/cart/get',
        cartUpdate: '/pos/cart/update',
        cartRemove: '/pos/cart/remove',
        cartClear: '/pos/cart/clear',
        productsRefresh: '/pos/refresh/products',
        finalize: '/pos/finalize',
        registerDetails: '/pos/register/details'
    }
};

// Utility Functions
POSApp.utils = {
    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken: function() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (!token) {
            console.error('CSRF token not found');
            return null;
        }
        return token.getAttribute('content');
    },

    /**
     * Format currency amount
     */
    formatCurrency: function(amount) {
        return 'MWK ' + parseFloat(amount || 0).toFixed(2);
    },

    /**
     * Show notification message
     */
    showNotification: function(message, type = 'info') {
        const notification = document.createElement('div');
        const isError = type === 'error' || message.includes('❌');
        
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = isError 
            ? 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; background-color: #dc3545 !important; color: white !important; border: 2px solid #c82333 !important; font-weight: 600;'
            : 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close ${isError ? 'btn-close-white' : ''}" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    },

    /**
     * Debounce function for search
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Generate unique ID
     */
    generateId: function() {
        return 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    /**
     * Format date for receipts
     */
    formatDate: function(date = new Date()) {
        return date.getDate().toString().padStart(2, '0') + '/' + 
               (date.getMonth() + 1).toString().padStart(2, '0') + '/' + 
               date.getFullYear() + ' ' + 
               date.getHours().toString().padStart(2, '0') + ':' + 
               date.getMinutes().toString().padStart(2, '0');
    }
};

// Modal Management
POSApp.modal = {
    /**
     * Show modal with proper Bootstrap 5 support
     */
    show: function(modalId) {
        console.log('showModal called with:', modalId);
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal element not found:', modalId);
            alert('Error: Modal not found');
            return;
        }

        try {
            // Special handling for register modal
            if (modalId === 'registerDetailsModal') {
                this.showRegisterModal(modal);
            } else {
                this.showStandardModal(modal);
            }
            
            console.log('Modal should be visible now');
        } catch (error) {
            console.error('Error showing modal:', error);
            alert('Error showing modal: ' + error.message);
        }
    },

    showStandardModal: function(modal) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            console.log('Using Bootstrap 5 modal');
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            console.log('Bootstrap not available, using fallback');
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modal-backdrop';
            document.body.appendChild(backdrop);
        }
    },

    showRegisterModal: function(modal) {
        console.log('Using direct method for register modal');
        
        // Remove all existing classes and add our own
        modal.className = '';
        modal.style.cssText = `
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 99999 !important;
            opacity: 1 !important;
            visibility: visible !important;
        `;
        
        // Style the modal dialog
        const dialog = modal.querySelector('.modal-dialog');
        if (dialog) {
            dialog.style.cssText = `
                position: relative !important;
                z-index: 100000 !important;
                margin: 0 !important;
                max-width: 90% !important;
                width: 800px !important;
            `;
        }
        
        // Style the modal content
        const content = modal.querySelector('.modal-content');
        if (content) {
            content.style.cssText = `
                position: relative !important;
                z-index: 100001 !important;
                background: white !important;
                border-radius: 8px !important;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3) !important;
            `;
        }
        
        console.log('Register modal displayed with direct method');
    },

    /**
     * Hide modal with proper cleanup
     */
    hide: function(modalId) {
        console.log('hideModal called with:', modalId);
        
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal element not found for hiding:', modalId);
            return;
        }

        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                console.log('Using Bootstrap 5 modal to hide');
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                } else {
                    this.fallbackHide(modal);
                }
            } else {
                this.fallbackHide(modal);
            }
            
            console.log('Modal should be hidden now');
        } catch (error) {
            console.error('Error hiding modal:', error);
        }
    },

    fallbackHide: function(modal) {
        console.log('Using fallback hide method');
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrop = document.getElementById('modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
};

// Error Handling
POSApp.error = {
    /**
     * Global error handler
     */
    handleGlobalError: function(event) {
        console.error('Global JavaScript error:', {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            error: event.error
        });
        
        // Don't show alerts for production, just log errors
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            alert('JavaScript Error: ' + event.message);
        }
    },

    /**
     * Global promise rejection handler
     */
    handleUnhandledRejection: function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        event.preventDefault();
    }
};

// Application Initialization
POSApp.init = function() {
    console.log('POS App initializing...');
    
    if (POSApp.state.initialized) {
        console.warn('POS App already initialized');
        return;
    }

    // Set up global error handlers
    window.addEventListener('error', POSApp.error.handleGlobalError);
    window.addEventListener('unhandledrejection', POSApp.error.handleUnhandledRejection);

    // Initialize modules in proper order
    if (window.POSProducts) {
        window.POSProducts.init();
    }
    
    // Initialize products data if available from blade file
    if (typeof productsData !== 'undefined' && window.POSProducts) {
        window.POSProducts.state.productsData = productsData;
        console.log('Products data initialized in POSApp:', productsData.length);
    }
    
    if (window.POSSearch) {
        window.POSSearch.init();
    }
    
    if (window.POSCart) {
        window.POSCart.init();
    }
    
    if (window.POSPayments) {
        window.POSPayments.init();
    }

    // Set up DOM event listeners
    POSApp.setupEventListeners();

    // Make modal functions globally available for inline handlers with proper context binding
    window.showModal = function(modalId) {
        return POSApp.modal.show(modalId);
    };
    window.hideModal = function(modalId) {
        return POSApp.modal.hide(modalId);
    };
    
    // Make payment functions globally available for inline handlers
    if (window.POSPayments) {
        window.updatePaymentSummary = function() {
            return POSPayments.updatePaymentSummary();
        };
        window.addPaymentMethod = function() {
            return POSPayments.addPaymentMethod();
        };
        window.removePaymentMethod = function(index) {
            return POSPayments.removePaymentMethod(index);
        };
        window.finalizePayment = function() {
            return POSPayments.finalizePayment();
        };
    }

    POSApp.state.initialized = true;
    console.log('POS App initialized successfully');
};

// Event Listeners Setup
POSApp.setupEventListeners = function() {
    // Click outside to close search dropdown
    document.addEventListener('click', function(event) {
        const searchContainer = document.querySelector('.pos-search-section');
        const dropdown = document.getElementById('searchDropdown');
        
        if (searchContainer && dropdown && !searchContainer.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });

    // DOM Ready event
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', POSApp.onDOMReady);
    } else {
        POSApp.onDOMReady();
    }
};

// DOM Ready Handler
POSApp.onDOMReady = function() {
    console.log('DOM loaded, initializing POS components...');
    
    // For POS, start with empty cart - don't load from server on page load
    // Cart will be populated as user adds products
    console.log('🛒 POS starting with empty cart - no initial load needed');
    
    // Set up modal event listeners
    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.addEventListener('show.bs.modal', function () {
            console.log('Payment modal opened, setting up...');
            if (window.POSPayments) {
                window.POSPayments.setupPaymentModal();
            }
        });
    }
    
    const registerModal = document.getElementById('registerDetailsModal');
    if (registerModal) {
        registerModal.addEventListener('show.bs.modal', function () {
            console.log('Register modal opened, loading sales...');
            if (window.POSCart) {
                window.POSCart.loadRegisterSales();
            }
        });
    }
};

// Auto-initialize when script loads
console.log('POS App script loaded, waiting for DOM...');
POSApp.init();
