@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - EDUC PHARMACY</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
</head>
<body>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Link to external POS CSS -->
<link href="{{ asset('css/pos.css') }}" rel="stylesheet">

<!-- Modern POS Design -->
<div id="pos-screen" class="pos-wrapper">
  <!-- Compact Header -->
  <header class="pos-header" style="padding: 0.75rem 1rem; flex-wrap: wrap; gap: 0.5rem;">
    <div class="pos-header-left" style="flex: 1; min-width: 200px;">
      <a href="{{ route('dashboard') }}" class="text-white text-decoration-none me-3">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <h1 class="pos-title" style="font-size: 1rem; margin: 0;">POS</h1>
    </div>
    <div class="pos-header-right" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="{{ route('pos.recent.sales') }}" class="pos-btn pos-btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
        <i class="bi bi-clock-history"></i> Recent Transactions
      </a>
            <a href="{{ route('pos.register.details') }}" class="pos-btn pos-btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
        <i class="bi bi-cash-stack"></i> Register
      </a>
      <button id="barcodeToggleBtn" class="pos-btn pos-btn-success barcode-toggle-btn" onclick="POSBarcodeScanner.toggle()" title="Toggle Barcode Scanner Mode" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
        <i class="bi bi-upc-scan"></i> <span id="barcodeBtnText">Scanner</span>
      </button>
      <a href="{{ route('register.confirm') }}" class="pos-btn pos-btn-success text-white" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
        <i class="bi bi-power"></i> Close
      </a>
    </div>
  </header>

  <!-- Main Content - Single Column Layout -->
  <main class="pos-main" style="padding: 1rem; gap: 1rem; height: calc(100vh - 80px);">
    <div style="display: flex; flex-direction: column; gap: 1rem; height: 100%;">
      
      <!-- Search Section -->
      <section class="pos-search-section" style="padding: 1rem; flex-shrink: 0;">
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

        <div class="cart-items" id="cart-tbody" style="flex: 1; overflow-y: auto; min-height: 200px; max-height: calc(100vh - 400px);">
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

        <div class="cart-footer" style="border-top: 1px solid #e2e8f0; padding-top: 0.75rem; flex-shrink: 0;">
          <div class="total-section" style="margin-bottom: 1rem;">
            <div class="total-row final" style="padding: 0.5rem 0; margin-top: 0.5rem;">
              <span style="font-size: 1rem;">Total Payable</span>
              <span id="totalPayable" style="font-size: 1.125rem;">MWK 0.00</span>
            </div>
          </div>
          <div class="action-buttons" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
              <button class="action-btn action-btn-cancel" onclick="clearCart()" style="flex: 1; min-width: 120px; padding: 0.75rem; font-size: 0.9rem;">
                <i class="bi bi-x-circle"></i> Cancel
              </button>
              <button class="action-btn action-btn-pay" onclick="showModal('paymentModal')" style="flex: 1; min-width: 120px; padding: 0.75rem; font-size: 0.9rem;">
                <i class="bi bi-credit-card"></i> Pay
              </button>
            </div>
        </div>
      </section>
    </div>
  </main>
</div>


<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold">Complete Payment</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          <strong>Total Payable:</strong> MWK <span id="modalTotalAmount">0.00</span>
        </div>
        
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
        <div class="card bg-light">
          <div class="card-body">
            <h6 class="card-title">Payment Summary</h6>
            <div class="row">
              <div class="col-md-6">
                <small class="text-muted">Total Paid:</small>
                <h5 class="text-primary">MWK <span id="totalPaid">0.00</span></h5>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Remaining Balance:</small>
                <h5 class="text-danger">MWK <span id="remainingBalance">0.00</span></h5>
              </div>
            </div>
          </div>
        </div>

        <!-- Customer Notes -->
        <div class="mb-3 mt-3">
          <label class="form-label fw-semibold small">Customer Notes</label>
          <textarea class="form-control form-control-sm" id="paymentNotes" rows="2" placeholder="Add any notes about this transaction..."></textarea>
        </div>

        <!-- Balance Alert -->
        <div id="balanceAlert" class="alert alert-warning" style="display: none;">
          <i class="bi bi-exclamation-triangle"></i>
          <strong>Payment Incomplete!</strong> Total paid (<span id="totalPaidAlert">0.00</span>) does not match the required amount (<span id="requiredAmountAlert">0.00</span>). Please add more payment methods or adjust amounts.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" onclick="clearCart()">
          <i class="bi bi-trash"></i> Clear Cart
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="finalizePayment()">
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
                <h5 class="card-title text-primary mb-1">Total Transactions</h5>
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

<!-- Include all the modals and JavaScript from original file -->
<!-- Table styles moved to external pos.css -->

<script>
    // Pass initial products data to the modular JavaScript
    var productsData = @json($products ?? []);
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
    setInterval(function() {
        fetch('/dashboard', { 
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(response => console.log('Session refreshed'))
          .catch(error => console.error('Session refresh failed', error));
    }, 15 * 60 * 1000); // Refresh every 15 minutes
</script>

<!-- Load Modular POS JavaScript Files -->
<script src="{{ asset('js/pos/app.js') }}"></script>
<script src="{{ asset('js/pos/search.js') }}"></script>
<script src="{{ asset('js/pos/products.js') }}"></script>
<script src="{{ asset('js/pos/cart.js') }}"></script>
<script src="{{ asset('js/pos/payments.js') }}"></script>
<script src="{{ asset('js/pos/barcode.js') }}"></script>
@endverbatim

<!-- Additional cart styles moved to external pos.css -->
