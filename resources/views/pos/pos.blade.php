@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern POS - EDUC Pharmacy</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ isset($settings['favicon']) ? asset($settings['favicon']) : asset('assets/images/logo.jpeg') }}">
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
<link href="{{ asset('css/pos.css') }}" rel="stylesheet">

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
      <a href="{{ route('pos.recent.sales') }}" class="pos-btn" style="padding: 0.32rem 0.65rem; font-size: 0.78rem;">
        <i class="bi bi-clock-history"></i> Recent
      </a>
            <a href="{{ route('pos.register.details') }}" class="pos-btn pos-btn-primary" style="padding: 0.32rem 0.65rem; font-size: 0.78rem;">
        <i class="bi bi-cash-stack"></i> Register
      </a>
      <button id="barcodeToggleBtn" class="pos-btn barcode-toggle-btn" onclick="POSBarcodeScanner.toggle()" title="Toggle Barcode Scanner Mode" style="padding: 0.32rem 0.65rem; font-size: 0.78rem;">
        <i class="bi bi-upc-scan"></i> <span id="barcodeBtnText">Scanner</span>
      </button>
      <a href="{{ route('register.confirm') }}" class="pos-btn text-white" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
        <i class="bi bi-power"></i> Close
      </a>
    </div>
  </header>

  <!-- Main Content - Single Column Layout -->
  <main class="pos-main" style="padding: 0.9rem 0.9rem 2.5rem 0.9rem; gap: 0.9rem; height: calc(100vh - 72px);">
    <div style="display: flex; flex-direction: column; gap: 0.9rem; height: 100%; min-height: 0;">
      
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
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: center;">Quantity</th>
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: center;">Cost Price</th>
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: center;">Selling Price</th>
                <th style="padding: 12px 8px; font-weight: 600; text-transform: uppercase; font-size: 12px; color: #495057; text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody id="cart-items-container">
              <tr>
                <td colspan="5" class="text-center text-muted" style="padding: 20px;">
                  <i class="bi bi-cart3" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                  <div style="font-size: 1rem; font-weight: 600;">No items in cart</div>
                  <div style="font-size: 0.875rem; color: #6c757d;">Start by searching for products</div>
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
              <button class="action-btn action-btn-pay" onclick="showModal('paymentModal')" style="flex: 2; min-width: 140px; padding: 0.65rem; font-size: 0.95rem; font-weight: bold; border-radius: 10px; box-shadow: 0 3px 8px rgba(34, 197, 94, 0.3); transition: all 0.2s;">
                <i class="bi bi-credit-card-fill"></i> Process Payment
              </button>
            </div>
        </div>
      </section>
    </div>
  </main>
</div>


<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-success text-white py-2">
        <h5 class="modal-title fw-bold">Complete Payment</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-2">
        <!-- Payment Methods List -->
        <div id="paymentMethodsList" class="mb-3">
          <!-- First Payment Method (Always Visible) -->
          <div class="payment-method-item border rounded p-3 mb-2" data-payment-index="0">
            <div class="row g-3">
              <div class="col-md-5">
                <label class="form-label fw-semibold small">Payment Method:*</label>
                <select class="form-select form-select-sm payment-method-select" data-index="0">
                  <option value="">Select Payment Method</option>
                  <option value="cash">Cash</option>
                  <option value="card">Card</option>
                  <option value="cheque">Cheque</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="airtel_money">Airtel Money</option>
                  <option value="mpamba">TNM Mpamba</option>
                  <option value="national_bank">National Bank</option>
                  <option value="standard_bank">Standard Bank</option>
                  <option value="advance">Advance</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small">Amount:*</label>
                <input type="number" class="form-control form-control-sm payment-amount" data-index="0" step="0.01" placeholder="0.00">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold small">&nbsp;</label>
                <div>
                  <button type="button" class="btn btn-sm btn-outline-danger remove-payment" data-index="0" style="display: none;">
                    <i class="bi bi-trash"></i> Remove
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Payment Method Button -->
        <div class="text-center mb-3">
          <button type="button" class="btn btn-outline-primary" onclick="addPaymentMethod()">
            <i class="bi bi-plus-circle"></i> Add Payment Method
          </button>
        </div>

        <!-- Payment Summary -->
        <div class="card border-0 shadow-sm mt-3" style="background: linear-gradient(to right, #f8fafc, #f1f5f9);">
          <div class="card-body">
            <h6 class="card-title fw-bold text-secondary mb-3">Payment Summary</h6>
            <div class="row mb-3">
              <div class="col-md-6">
                <small class="text-muted fw-semibold">Total Paid:</small>
                <h4 class="text-success fw-bold mb-0">MWK <span id="totalPaid">0.00</span></h4>
              </div>
              <div class="col-md-6">
                <small class="text-muted fw-semibold">Remaining Balance:</small>
                <h4 class="text-danger fw-bold mb-0">MWK <span id="remainingBalance">0.00</span></h4>
              </div>
            </div>
            
            <div class="row mt-3 border-top pt-3" id="cashDetailsSection" style="background-color: #e0f2fe; border-radius: 8px; padding: 10px;">
              <div class="col-md-5">
                <label class="form-label text-dark fw-bold" style="font-size: 1.1rem;">Amount Given:</label>
                <input type="number" id="amountGiven" class="form-control form-control-lg border-primary shadow-sm" placeholder="0.00" step="0.01" oninput="updatePaymentSummary()" style="height: 60px; font-size: 1.5rem; font-weight: bold; width: 90%;">
              </div>
              <div class="col-md-7 d-flex flex-column justify-content-center">
                <label class="form-label text-dark fw-bold" style="font-size: 1.1rem;">Change To Give:</label>
                <h2 class="text-primary fw-bold mb-0" style="font-size: 2.5rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);">MWK <span id="changeAmount">0.00</span></h2>
              </div>
            </div>
          </div>
        </div>


        <!-- Balance Alert -->
        <div id="balanceAlert" class="alert alert-warning" style="display: none;">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Payment Incomplete!</strong> Total paid (<span id="totalPaidAlert">0.00</span>) does not match the required amount (<span id="requiredAmountAlert">0.00</span>). Please add more payment methods or adjust amounts.
        </div>
      </div>
      <div class="modal-footer">

        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="finalizePayment()">
          <i class="bi bi-check-circle"></i> Complete Payment
        </button>
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
    var systemSettings = @json($settings ?? []);
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

    // Session Keep-Alive to prevent 419 Page Expired
    setInterval(() => {
        fetch('/keepalive', {
            method: 'GET',
            credentials: 'same-origin'
        }).then(response => {
            if (!response.ok) {
                throw new Error('Keepalive response not ok: ' + response.status);
            }
            console.log('Session keepalive successful');
        }).catch(error => console.error('Keepalive failed', error));
    }, 300000); // Refresh every 5 minutes
</script>

<!-- Global fetch interceptor for Laravel CSRF and JSON headers -->
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
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
<script src="{{ asset('js/pos/app.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/pos/search.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/pos/products.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/pos/cart.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/pos/payments.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/pos/barcode.js') }}?v={{ time() }}"></script>


<!-- Additional cart styles moved to external pos.css -->
