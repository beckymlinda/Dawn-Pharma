@extends('layouts.app')

@section('title', 'New Stock Adjustment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">New Stock Adjustment</h4>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Adjustments
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('stock-adjustments.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-lg-6 col-md-12 mb-3">
                                <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <input type="text" 
                                           class="form-control" 
                                           id="productSearch" 
                                           placeholder="Search and select product..." 
                                           autocomplete="off"
                                           required>
                                    <div class="position-absolute w-100 bg-white border rounded shadow-sm mt-1" 
                                         id="productDropdown" 
                                         style="max-height: 200px; overflow-y: auto; display: none; z-index: 1000;">
                                        <!-- Product options will be populated here -->
                                    </div>
                                    <input type="hidden" id="selectedProductId" name="product_id" required>
                                    <div class="invalid-feedback d-block" id="productError">Please select a product</div>
                                </div>
                                @error('product_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6 col-md-12 mb-3">
                                <label class="form-label fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                                <select class="form-select" name="adjustment_type" id="adjustmentType" required>
                                    <option value="add">Stock In (Add)</option>
                                    <option value="subtract">Stock Out (Subtract)</option>
                                </select>
                                @error('adjustment_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                            <div class="row">
                            <div class="col-lg-4 col-md-12 mb-3">
                                <label class="form-label fw-semibold">Quantity (Optional)</label>
                                <input type="number" class="form-control" name="quantity" 
                                       step="1" min="0" placeholder="Leave empty to update prices only">
                                @error('quantity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-4 col-md-12 mb-3">
                                <label class="form-label fw-semibold">New Selling Price (Optional)</label>
                                <input type="number" class="form-control" name="new_selling_price" 
                                       step="0.01" min="0" placeholder="Leave empty to keep current price">
                                @error('new_selling_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-4 col-md-12 mb-3">
                                <label class="form-label fw-semibold">New Purchase Price (Optional)</label>
                                <input type="number" class="form-control" name="new_purchase_price" 
                                       step="0.01" min="0" placeholder="Leave empty to keep current price">
                                @error('new_purchase_price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-12 mb-3">
                                <label class="form-label fw-semibold">New Expiry Date (Optional)</label>
                                <input type="date" class="form-control" name="new_expiry_date" 
                                       min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                                @error('new_expiry_date')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-lg-6 col-md-12 mb-3">
                                <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                                <select class="form-select" name="reason" required>
                                    <option value="new_stock" @selected(old('reason', 'new_stock') === 'new_stock')>New Stock</option>
                                    <option value="damaged" @selected(old('reason') === 'damaged')>Damaged</option>
                                    <option value="expired" @selected(old('reason') === 'expired')>Expired</option>
                                    <option value="customer_return" @selected(old('reason') === 'customer_return')>Customer Return</option>
                                </select>
                                @error('reason')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                            <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary flex-fill flex-sm-grow-0">
                                <i class="bi bi-x-lg me-1"></i> <span class="d-none d-sm-inline">Cancel</span>
                            </a>
                            <button type="submit" class="btn btn-primary flex-fill flex-sm-grow-0">
                                <i class="bi bi-check-lg me-1"></i> <span class="d-none d-sm-inline">Process Adjustment</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-2"></i>Stock Adjustment Info
                    </h6>
                    <div class="small">
                        <p class="mb-2">
                            <strong>Stock In:</strong> Adds quantity to current stock
                        </p>
                        <p class="mb-2">
                            <strong>Stock Out:</strong> Removes quantity from current stock
                        </p>
                        <p class="mb-2">
                            <strong>New Stock:</strong> Will be automatically calculated based on adjustment type and quantity
                        </p>
                        <p class="mb-2">
                            <strong>Price Update:</strong> Optional - updates product selling and purchase prices
                        </p>
                        <p class="mb-0">
                            <strong>Expiry Date:</strong> Optional - for tracking purposes
                        </p>
                    </div>
                </div>
            </div>

            <div class="card mt-3 border-warning">
                <div class="card-body">
                    <h6 class="fw-bold mb-2 text-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>Important
                    </h6>
                    <div class="small text-muted">
                        <p class="mb-0">
                            Stock adjustments are logged and cannot be undone. Make sure to enter accurate information and provide a clear reason for the adjustment.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Product data from server
const products = @json($products);
const preselectedProductId = @json($selectedProductId ?? null);

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing integrated search dropdown');
    
    const productSearch = document.getElementById('productSearch');
    const productDropdown = document.getElementById('productDropdown');
    const selectedProductId = document.getElementById('selectedProductId');
    const productError = document.getElementById('productError');
    
    if (!productSearch || !productDropdown || !selectedProductId) {
        console.error('Required elements not found');
        return;
    }
    
    // Populate dropdown with products
    function populateDropdown(products) {
        productDropdown.innerHTML = '';
        
        products.forEach(product => {
            const option = document.createElement('div');
            option.className = 'p-2 border-bottom cursor-pointer hover-bg-light';
            option.style.cursor = 'pointer';
            option.innerHTML = `
                <div class="fw-semibold">${product.name}</div>
                <div class="small text-muted">SKU: ${product.sku} | Stock: ${product.current_stock} | SP: MWK ${product.selling_price}</div>
            `;
            
            option.addEventListener('click', function() {
                selectProduct(product);
            });
            
            productDropdown.appendChild(option);
        });
    }
    
    // Select product function
    function selectProduct(product) {
        productSearch.value = `${product.name} - ${product.sku}`;
        selectedProductId.value = product.id;
        productDropdown.style.display = 'none';
        productError.style.display = 'none';
        
        // Update stock info display
        updateStockInfo(product);
        
        console.log('Product selected:', product.name);
    }
    
    // Update stock info display
    function updateStockInfo(product) {
        // You can update any stock info displays here if needed
        console.log('Stock info updated for:', product.name, 'Current stock:', product.current_stock);
    }
    
    // Filter products based on search
    function filterProducts(searchTerm) {
        const filtered = products.filter(product => {
            const term = searchTerm.toLowerCase();
            return product.name.toLowerCase().includes(term) || 
                   product.sku.toLowerCase().includes(term);
        });
        
        populateDropdown(filtered);
        
        if (filtered.length === 0) {
            productDropdown.innerHTML = '<div class="p-3 text-muted">No products found</div>';
        }
    }
    
    // Show/hide dropdown
    productSearch.addEventListener('focus', function() {
        productDropdown.style.display = 'block';
        if (this.value.trim() === '') {
            populateDropdown(products);
        } else {
            filterProducts(this.value);
        }
    });
    
    // Search functionality
    productSearch.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        if (searchTerm === '') {
            populateDropdown(products);
        } else {
            filterProducts(searchTerm);
        }
        
        productDropdown.style.display = 'block';
        selectedProductId.value = ''; // Clear selection when typing
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!productSearch.contains(event.target) && !productDropdown.contains(event.target)) {
            productDropdown.style.display = 'none';
        }
    });

    // Auto-select a product if the page opened with a preselected product ID
    if (preselectedProductId) {
        const matchingProduct = products.find(p => p.id === parseInt(preselectedProductId));
        if (matchingProduct) {
            selectProduct(matchingProduct);
        }
    }
    
    // Validate on form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (!selectedProductId.value) {
            e.preventDefault();
            productError.style.display = 'block';
            productSearch.focus();
        }
    });
    
    // Clear search function (for external use)
    window.clearProductSearch = function() {
        productSearch.value = '';
        selectedProductId.value = '';
        productDropdown.style.display = 'none';
        productError.style.display = 'none';
        console.log('Product search cleared');
    };
    
    // Initialize
    populateDropdown(products);
    console.log('Integrated search dropdown initialized successfully');
});
</script>
@endsection
