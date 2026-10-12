<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - {{ setting('system_name') }}</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="session-auth" content="{{ auth()->check() ? '1' : '0' }}">
    <meta name="pos-user" content="{{ auth()->id() }}">
    <script src="{{ asset('js/session-guard.js') }}?v={{ filemtime(public_path('js/session-guard.js')) }}"></script>
    <!-- Favicon -->
    <link rel="icon" href="{{ brand_favicon_url() }}">
    <style>
        :root {
            --theme-primary: {{ $settings['primary_color'] ?? '#004d3d' }};
            --theme-secondary: {{ $settings['secondary_color'] ?? '#006b54' }};
        }
        
        /* Layout & Header */
        .pos-header { background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%) !important; }
        .pos-wrapper { background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%) !important; }
        
        /* Buttons */
        .pos-btn-primary { background-color: var(--theme-secondary) !important; border-color: var(--theme-secondary) !important; }
        .pos-btn-primary:hover { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; opacity: 0.9; }
        .action-btn-pay { background: linear-gradient(135deg, var(--theme-secondary) 0%, var(--theme-primary) 100%) !important; color: white !important; border: none !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important; }
        .action-btn-pay:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2) !important; }
        .btn-primary { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
        .btn-success { background-color: var(--theme-secondary) !important; border-color: var(--theme-secondary) !important; }
        
        /* Search & Focus */
        .search-input-wrapper:focus-within { border-color: var(--theme-primary) !important; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05) !important; }
        .search-icon.active { color: var(--theme-primary) !important; }
        
        /* Cart & Text */
        .text-primary { color: var(--theme-primary) !important; }
        .stat-value { color: var(--theme-primary) !important; }
        #totalPayable { color: var(--theme-primary) !important; font-weight: 800; }
        .item-name { color: var(--theme-primary) !important; }
        .item-total-value { color: var(--theme-secondary) !important; }
        .total-row.final { color: var(--theme-primary) !important; }
        
        /* Modal */
        .modal-header.bg-success { background-color: var(--theme-primary) !important; }
        .modal-header.bg-primary { background-color: var(--theme-primary) !important; }
        .text-success { color: var(--theme-secondary) !important; }
        
        /* Custom overrides */
        #barcodeBtnText { font-weight: 600; }
        .barcode-pulse { background: var(--theme-secondary) !important; }
    </style>
</head>
<body>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Link to external POS CSS -->
<link href="{{ asset('css/pos.css') }}?v={{ filemtime(public_path('css/pos.css')) }}" rel="stylesheet">

<!-- Modern POS Design -->
<div id="pos-screen" class="pos-wrapper">
  <!-- Compact Header -->
  <header class="pos-header" style="padding: 0.6rem 0.9rem; flex-wrap: wrap; gap: 0.5rem;">
    <div class="pos-header-left" style="flex: 1; min-width: 160px;">
      <a href="{{ route('dashboard') }}" class="text-white text-decoration-none me-2" style="font-size: 0.9rem;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <h1 class="pos-title" style="font-size: 0.95rem; margin: 0;">POS</h1>
    </div>
    <div class="pos-header-right" style="display: flex; gap: 0.45rem; flex-wrap: wrap;">
      <a href="{{ route('pos.recent.sales') }}" class="pos-btn" title="Recent sales" style="padding: 0.32rem 0.65rem; font-size: 0.78rem;">
        <i class="bi bi-clock-history" aria-hidden="true"></i> <span class="pos-btn-label">Recent</span>
      </a>
            <a href="{{ route('pos.register.details') }}" class="pos-btn pos-btn-primary" title="Register details" style="padding: 0.32rem 0.65rem; font-size: 0.78rem;">
        <i class="bi bi-cash-stack" aria-hidden="true"></i> <span class="pos-btn-label">Register</span>
      </a>
      <button id="barcodeToggleBtn" class="pos-btn barcode-toggle-btn" onclick="POSBarcodeScanner.toggle()" title="Toggle Barcode Scanner Mode" style="padding: 0.32rem 0.65rem; font-size: 0.78rem;">
        <i class="bi bi-upc-scan" aria-hidden="true"></i> <span id="barcodeBtnText" class="pos-btn-label">Scanner</span>
      </button>
      <button type="button" id="offlineStatusBadge" class="pos-btn text-white" title="Connection status" style="padding: 0.32rem 0.65rem; font-size: 0.78rem; background: #16a34a; border: none;">Online</button>
      <a href="{{ route('register.confirm') }}" class="pos-btn text-white" title="Close register" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
        <i class="bi bi-power" aria-hidden="true"></i> <span class="pos-btn-label">Close</span>
      </a>
    </div>
  </header>

  <!-- Main Content: products on the left, cart on the right (stacked on phones) -->
  <main class="pos-main pos-main-split">
    <div class="pos-left">
      
      <!-- Search Section -->
      <section class="pos-search-section" style="padding: 0.9rem; flex-shrink: 0;">
        <!-- Barcode Scanner Status Bar -->
        <div id="barcodeScannerStatus" class="barcode-status-bar" style="display: none;">
          <div class="barcode-status-inner">
            <span class="barcode-pulse"></span>
            <i class="bi bi-upc-scan"></i>
            <span>Barcode Scanner Active — point your scanner at a barcode or type a SKU and press Enter</span>
          </div>
          <button class="barcode-status-close" onclick="POSBarcodeScanner.deactivate()" title="Disable Scanner Mode">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <div class="search-container">
          <div class="search-input-wrapper" id="searchInputWrapper">
            <i class="bi bi-search search-icon" id="searchModeIcon"></i>
            <input 
              type="text" 
              id="cartSearch" 
              class="search-input" 
              placeholder="Search products..."
              autocomplete="off">
            <!-- Hidden barcode input always active when scanner mode is on -->
            <input
              type="text"
              id="barcodeInput"
              class="barcode-hidden-input"
              autocomplete="off"
              tabindex="-1"
              placeholder=""
              aria-label="Barcode scanner input">
            <div id="productLoadingIndicator" style="display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">
              <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
          <div id="searchDropdown" class="search-dropdown" style="display: none;">
            <!-- Search results will appear here -->
          </div>
        </div>
      </section>

      <!-- Product tiles: tap to add (filtered by the search box and category) -->
      <section class="pos-products-section" aria-label="Products">
        <div class="pos-category-chips" id="posCategoryChips" role="tablist" aria-label="Categories"></div>
        <div class="pos-product-grid" id="posProductGrid"></div>
      </section>
    </div>

      <!-- Cart Section -->
      <section class="pos-cart-section" style="padding: 1rem; flex: 1; display: flex; flex-direction: column;">
        <div class="cart-header" style="padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 0.75rem;">
          <div class="customer-selector" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-person"></i>
            <select class="customer-select" id="customerSelect" style="min-width: 120px; padding: 0.4rem 0.6rem; font-size: 0.8rem;">
              <option value="">Walk-In Customer</option>
            </select>
          </div>
          <div class="cart-stats" style="display: flex; gap: 1rem;">
            <div class="stat-item" style="text-align: right;">
              <div class="stat-label" style="font-size: 0.7rem;">Items</div>
              <div class="stat-value" id="itemCount" style="font-size: 1rem;">0</div>
            </div>
            <div class="stat-item" style="text-align: right;">
              <div class="stat-label" style="font-size: 0.7rem;">Total</div>
              <div class="stat-value" id="cartTotal" style="font-size: 1rem;">MWK 0.00</div>
            </div>
          </div>
        </div>

        <div class="cart-items" id="cart-tbody" style="flex: 1; overflow-y: auto; min-height: 160px; max-height: calc(100vh - 320px);">
          <table id="cartTable" style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: left;">Product</th>
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: center; width: 92px;">Qty</th>
                                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: right;">Total</th>
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: center; width: 56px;"><span class="visually-hidden">Remove</span></th>
              </tr>
            </thead>
            <tbody id="cart-items-container">
              <tr>
                <td colspan="4" class="text-center text-muted" style="padding: 20px;">
                  <i class="bi bi-cart3" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                  <div style="font-size: 1rem; font-weight: 600;">No items in cart</div>
                  <div style="font-size: 0.875rem; color: #6c757d;">Tap a product or search to add it</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="cart-footer" style="border-top: 1px solid #e2e8f0; padding-top: 0.65rem; flex-shrink: 0;">
          <div class="total-section" style="margin-bottom: 0.75rem;">
            <div class="total-row final" style="padding: 0.4rem 0; margin-top: 0.4rem;">
              <span style="font-size: 0.95rem;">Total Payable</span>
              <span id="totalPayable" style="font-size: 1.05rem;">MWK 0.00</span>
            </div>
          </div>
          <div class="action-buttons" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
              <button class="action-btn action-btn-cancel text-danger" onclick="clearCart()" style="flex: 1; min-width: 110px; padding: 0.65rem; font-size: 0.88rem; background-color: #fff1f2; border: 1px solid #fecdd3; font-weight: bold; border-radius: 10px; transition: all 0.2s;">
                <i class="bi bi-trash3-fill"></i> Cancel Sale
              </button>
              <button class="action-btn action-btn-pay" id="processPaymentBtn" onclick="showModal('paymentModal')" style="flex: 2; min-width: 140px; padding: 0.65rem; font-size: 0.95rem; font-weight: bold; border-radius: 10px; box-shadow: 0 3px 8px rgba(34, 197, 94, 0.3); transition: all 0.2s;">
                <i class="bi bi-credit-card-fill"></i> Process Payment
              </button>
            </div>
        </div>
      </section>
  </main>
</div>


<!-- Payment Modal: amount due on the left, how the customer pays on the right -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content pay-modal">
      <div class="row g-0 h-100">

        <!-- Amount due + what is being bought -->
        <div class="col-md-5 pay-summary">
          <div class="d-flex justify-content-between align-items-start">
            <div class="pay-summary-label">Amount due</div>
            <button type="button" class="btn-close btn-close-white d-md-none" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="pay-due" id="payDue">MWK 0.00</div>
          <div class="pay-summary-meta" id="payItemCount">0 items</div>
          <div class="pay-items" id="payItems" aria-label="Items in this sale"></div>
        </div>

        <!-- Payment -->
        <div class="col-md-7 pay-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <h5 class="modal-title fw-bold mb-0 me-auto" id="paymentModalTitle">Payment</h5>
            <!-- Always visible at the top: pay with more than one method -->
            <button type="button" class="btn btn-sm pay-split-toggle" id="paySplitToggle">
              <i class="bi bi-diagram-2 me-1"></i> Split payment
            </button>
            <button type="button" class="btn-close d-none d-md-block" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="pay-scroll">
          <!-- Single payment -->
          <div id="paySingle">
            <div class="pay-label">Payment method</div>
            <div class="pay-methods" id="payMethods" role="radiogroup" aria-label="Payment method"></div>

            <!-- Cash: what the customer hands over, and the change -->
            <div id="payCashSection" class="mt-2">
              <label for="amountGiven" class="pay-label">Cash received</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text">MWK</span>
                <input type="number" id="amountGiven" class="form-control pay-cash-input" min="0" step="1" inputmode="decimal" placeholder="Exact amount" autocomplete="off">
              </div>
              <div class="pay-quick" id="payQuickCash" aria-label="Quick amounts"></div>
            </div>

            <div id="payOtherNote" class="pay-note mt-2 d-none">
              <i class="bi bi-info-circle me-1"></i> <span>Confirm the payment was received before completing the sale.</span>
            </div>

          </div>

          <!-- Split payment -->
          <div id="paySplit" class="d-none">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="pay-label mb-0">Split payment</div>
              <button type="button" class="btn btn-link btn-sm px-0" id="paySingleToggle">Use one method</button>
            </div>
            <div id="paymentMethodsList"></div>
            <div class="small text-muted mb-2">Lower the cash to what was paid in cash; the rest moves to the next line. Then choose its method.</div>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="payAddLine"><i class="bi bi-plus-lg me-1"></i> Add another method</button>
          </div>
          </div>

          <div class="pay-footer">
          <!-- Result: change / still to pay -->
          <div class="pay-result" id="payResult" aria-live="polite">
            <div>
              <div class="pay-result-label" id="payResultLabel">Change</div>
              <div class="pay-result-value" id="payResultValue">MWK 0.00</div>
            </div>
            <div class="text-end small" id="payPaidLine"></div>
          </div>

          <button type="button" class="btn pay-complete w-100" id="payCompleteBtn" onclick="finalizePayment()">
            <i class="bi bi-check2-circle me-1"></i> <span id="payCompleteText">Complete sale</span>
          </button>
          <div class="text-center small text-muted mt-2 d-none d-md-block pay-hint">Press <kbd>Enter</kbd> to complete · <kbd>Esc</kbd> to go back</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title fw-bold">Sales Details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body" id="receipt-print-area" style="font-family:monospace;font-size:12px"></div>
  <div class="modal-footer">
    <button class="btn btn-secondary btn-sm fw-semibold" data-bs-dismiss="modal" onclick="clearCartAfterPayment()">Close</button>
    <button class="btn btn-primary btn-sm fw-semibold" onclick="printReceipt()">🖨 Print</button>
  </div></div></div></div>

<!-- Register Details Modal -->
<div class="modal fade" id="registerDetailsModal" tabindex="-1" data-bs-backdrop="false">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-cash-stack"></i> Current Register Sales
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="card bg-light">
              <div class="card-body text-center">
                <h5 class="card-title text-primary mb-1">Total Sales</h5>
                <h3 class="fw-bold" id="registerTotalSales">MWK 0.00</h3>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card bg-light">
              <div class="card-body text-center">
                <h5 class="card-title text-success mb-1">Total Transactions</h5>
                <h3 class="fw-bold" id="registerTotalTransactions">0</h3>
              </div>
            </div>
          </div>
        </div>
        
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th>Invoice #</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Payment Method</th>
                <th>Total</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody id="registerSalesTable">
              <tr>
                <td colspan="6" class="text-center text-muted">Loading current register sales...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="refreshRegisterSales()">
          <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Product Loading Overlay (Reliable CSS Version) -->
<div id="productLoadingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; backdrop-filter: blur(5px); flex-direction: column; align-items: center; justify-content: center; color: white;">
    <div style="background: white; padding: 3rem; border-radius: 20px; text-align: center; color: #333; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-width: 450px;">
        <div class="spinner-border text-primary mb-4" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="fw-bold mb-2">Syncing Pharmacy Inventory</h4>
        <p class="text-muted mb-0">Please wait while we prepare your products. This ensures you have the latest stock data.</p>
    </div>
</div>

<!-- Include all the modals and JavaScript from original file -->
<!-- Table styles moved to external pos.css -->

<script>
    // Pass initial products data to the modular JavaScript
    var productsData = @json($products ?? []);
    // Server time of these stock levels (offline.js compares it with figures saved on this device)
    var productsStockAt = {{ now()->getTimestampMs() }};
    // Only the details printed on receipts; loss-prevention thresholds etc. stay on the server
    @php($receiptSettings = \Illuminate\Support\Arr::only(business_settings(), ['system_name', 'location', 'telephone', 'email', 'tax_number', 'receipt_footer', 'powered_by']) + ['receipt_logo' => brand_receipt_logo()])
    var systemSettings = @json($receiptSettings);
    console.log('Products loaded from server:', productsData.length);
    
    // Initialize POSProducts with the loaded data
    window.addEventListener('DOMContentLoaded', function() {
        if (window.POSProducts && window.POSProducts.state && productsData.length > 0) {
            window.POSProducts.state.productsData = productsData;
            console.log('Products data initialized in POSProducts module:', window.POSProducts.state.productsData.length);
        }
    });
    
    // Make payment functions available immediately for inline handlers
    window.finalizePayment = function() {
        if (window.POSPayments && window.POSPayments.finalizePayment) {
            return window.POSPayments.finalizePayment();
        } else {
            console.error('POSPayments module not loaded yet');
            alert('Payment system not ready. Please try again.');
        }
    };
    
    window.clearCart = function() {
        if (window.POSCart && window.POSCart.clearCart) {
            return window.POSCart.clearCart();
        } else {
            console.error('POSCart module not loaded');
            alert('Cart system not ready. Please refresh the page.');
        }
    };
    
    window.updatePaymentSummary = function() {
        if (window.POSPayments && window.POSPayments.updatePaymentSummary) {
            return window.POSPayments.updatePaymentSummary();
        }
    };
    
    window.addPaymentMethod = function() {
        if (window.POSPayments && window.POSPayments.addPaymentMethod) {
            return window.POSPayments.addPaymentMethod();
        }
    };
    
    window.removePaymentMethod = function(index) {
        if (window.POSPayments && window.POSPayments.removePaymentMethod) {
            return window.POSPayments.removePaymentMethod(index);
        }
    };
    
    // The filterProducts function is now handled by POSProducts module
    
    // The loadProducts function is now handled by POSProducts module
    // Error handling and initialization are now handled by POSApp module
        
    // Cart functions are now handled by POSCart and POSProducts modules

    // Cart loading is now handled by POSCart module

    // Cart rendering is now handled by POSCart module

    // Cart management functions are now handled by POSCart module

    // Modal functions are now handled by POSApp.modal module

    // Payment modal setup is now handled by POSPayments module

    // Payment summary update is now handled by POSPayments module

    // Payment functions are now handled by POSPayments module

    // Receipt generation and printing are now handled by POSPayments module

    // Session keep-alive and CSRF token refresh are handled by public/js/session-guard.js
</script>

<!-- Global fetch interceptor for Laravel CSRF and JSON headers -->
<script>
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    // Read the token at request time: session-guard.js keeps the meta tag up to date
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    options.headers = {
        ...(options.headers || {}),
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    };
    if (!options.credentials) {
        options.credentials = 'same-origin';
    }
    return originalFetch(url, options);
};
</script>

<!-- Load Modular POS JavaScript Files -->
<script src="{{ asset('js/pos/app.js') }}?v={{ filemtime(public_path('js/pos/app.js')) }}"></script>
<script src="{{ asset('js/pos/search.js') }}?v={{ filemtime(public_path('js/pos/search.js')) }}"></script>
<script src="{{ asset('js/pos/products.js') }}?v={{ filemtime(public_path('js/pos/products.js')) }}"></script>
<script src="{{ asset('js/pos/cart.js') }}?v={{ filemtime(public_path('js/pos/cart.js')) }}"></script>
<script>window.POS_PAYMENT_METHODS = @json(\App\Helpers\PaymentMethods::forPos());</script>
<script src="{{ asset('js/pos/payments.js') }}?v={{ filemtime(public_path('js/pos/payments.js')) }}"></script>
<script src="{{ asset('js/pos/barcode.js') }}?v={{ filemtime(public_path('js/pos/barcode.js')) }}"></script>
<script src="{{ asset('js/pos/grid.js') }}?v={{ filemtime(public_path('js/pos/grid.js')) }}"></script>
<script src="{{ asset('js/pos/offline.js') }}?v={{ filemtime(public_path('js/pos/offline.js')) }}"></script>
<script>
    // Lets the POS page open again without internet (see public/sw.js)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function (e) { console.warn('Offline page cache unavailable:', e); });
    }
</script>


<!-- Additional cart styles moved to external pos.css -->
