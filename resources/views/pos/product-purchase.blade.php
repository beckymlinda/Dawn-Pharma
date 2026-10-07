@extends('layouts.app')

@section('title', 'Product Purchase - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Product Purchase Management</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Purchase Form -->
    <div class="card border mb-4">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-cart-plus"></i> New Purchase Order</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('products.purchase.store') }}" method="POST" id="purchaseForm">
                @csrf
                
                <!-- Supplier and Date Information -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Supplier: <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" 
                                name="supplier_id" id="supplier_id" required>
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Purchase Date: <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm @error('purchase_date') is-invalid @enderror" 
                               name="purchase_date" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                        @error('purchase_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Reference No:</label>
                        <input type="text" class="form-control form-control-sm" 
                               name="reference_no" id="reference_no" placeholder="Auto-generated" readonly>
                        <small class="text-muted">Purchase reference number</small>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <div class="card border">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Products</h6>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="addProductRow()">
                                        <i class="bi bi-plus"></i> Add Product
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="productsContainer">
                                    <!-- Products will be added here dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Total Items:</label>
                                        <div class="form-control bg-white" id="totalItems">0</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Total Quantity:</label>
                                        <div class="form-control bg-white" id="totalQuantity">0</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Subtotal:</label>
                                        <div class="form-control bg-white" id="subtotal">MWK 0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Grand Total:</label>
                                        <div class="form-control bg-white fw-bold" id="grandTotal">MWK 0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Complete Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Purchases -->
    <div class="card border">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Purchases</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Reference No</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Products</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentPurchases as $purchase)
                        <tr>
                            <td>{{ $purchase->reference_no }}</td>
                            <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                            <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $purchase->items->count() }} items</td>
                            <td class="fw-bold">MWK {{ number_format($purchase->grand_total, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $purchase->status == 'received' ? 'success' : 'warning' }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let productRowCounter = 0;
let products = @json($products);

// Generate reference number on page load
document.addEventListener('DOMContentLoaded', function() {
    generateReferenceNumber();
    addProductRow(); // Add first product row
});

function generateReferenceNumber() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    
    document.getElementById('reference_no').value = `PUR-${year}${month}${day}-${random}`;
}

function addProductRow() {
    productRowCounter++;
    const container = document.getElementById('productsContainer');
    
    const rowDiv = document.createElement('div');
    rowDiv.className = 'row g-3 mb-2 product-row';
    rowDiv.id = `productRow${productRowCounter}`;
    
    rowDiv.innerHTML = `
        <div class="col-md-4">
            <label class="form-label fw-semibold small">Product: <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm product-select" name="products[${productRowCounter}][product_id]" required>
                <option value="">Select Product</option>
                ${products.map(product => 
                    `<option value="${product.id}" data-purchase-price="${product.purchase_price}" data-selling-price="${product.selling_price}">
                        ${product.name} - ${product.sku}
                    </option>`
                ).join('')}
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small">Quantity: <span class="text-danger">*</span></label>
            <input type="number" class="form-control form-control-sm quantity-input" 
                   name="products[${productRowCounter}][quantity]" placeholder="0" step="1" min="1" required>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small">Unit Cost:</label>
            <input type="number" class="form-control form-control-sm unit-cost-input" 
                   name="products[${productRowCounter}][unit_cost]" placeholder="0" step="1" min="0" readonly>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small">Expiry Date:</label>
            <input type="date" class="form-control form-control-sm" 
                   name="products[${productRowCounter}][expiry_date]">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small">Total:</label>
            <div class="form-control form-control-sm bg-light line-total">MWK 0.00</div>
        </div>
        <div class="col-md-12">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductRow(${productRowCounter})">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
    `;
    
    container.appendChild(rowDiv);
    
    // Add event listeners
    const row = document.getElementById(`productRow${productRowCounter}`);
    const productSelect = row.querySelector('.product-select');
    const quantityInput = row.querySelector('.quantity-input');
    const unitCostInput = row.querySelector('.unit-cost-input');
    
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const purchasePrice = selectedOption.dataset.purchasePrice || 0;
        unitCostInput.value = purchasePrice;
        calculateRowTotal(row);
        calculateTotals();
    });
    
    quantityInput.addEventListener('input', function() {
        calculateRowTotal(row);
        calculateTotals();
    });
}

function removeProductRow(rowId) {
    const row = document.getElementById(`productRow${rowId}`);
    if (row) {
        row.remove();
        calculateTotals();
    }
}

function calculateRowTotal(row) {
    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
    const unitCost = parseFloat(row.querySelector('.unit-cost-input').value) || 0;
    const total = quantity * unitCost;
    
    row.querySelector('.line-total').textContent = `MWK ${total.toFixed(2)}`;
    return total;
}

function calculateTotals() {
    const rows = document.querySelectorAll('.product-row');
    let totalItems = 0;
    let totalQuantity = 0;
    let subtotal = 0;
    
    rows.forEach(row => {
        const productSelect = row.querySelector('.product-select');
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const unitCost = parseFloat(row.querySelector('.unit-cost-input').value) || 0;
        
        if (productSelect.value) {
            totalItems++;
            totalQuantity += quantity;
            subtotal += quantity * unitCost;
        }
    });
    
    document.getElementById('totalItems').textContent = totalItems;
    document.getElementById('totalQuantity').textContent = totalQuantity.toFixed(2);
    document.getElementById('subtotal').textContent = `MWK ${subtotal.toFixed(2)}`;
    document.getElementById('grandTotal').textContent = `MWK ${subtotal.toFixed(2)}`;
}
</script>
@endsection
