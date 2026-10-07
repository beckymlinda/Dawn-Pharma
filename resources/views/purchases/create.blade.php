@extends('layouts.app')

@section('title', 'Create Purchase - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Add New Purchase</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Purchases
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

    <div class="card border">
        <div class="card-body">
            <form action="{{ route('purchases.store') }}" method="POST" novalidate id="purchaseForm">
                @csrf

                <!-- Purchase Header -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Supplier: <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" required>
                                <option value="">Select a supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        @error('supplier_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Reference No:</label>
                        <input type="text" class="form-control @error('reference_no') is-invalid @enderror" 
                               name="reference_no" placeholder="Auto-generated if empty" value="{{ old('reference_no') }}">
                        @error('reference_no')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Purchase Date: <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('purchase_date') is-invalid @enderror" 
                               name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('purchase_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status: <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="pending" @selected(old('status') == 'pending')>Pending</option>
                            <option value="ordered" @selected(old('status') == 'ordered')>Ordered</option>
                            <option value="received" @selected(old('status') == 'received')>Received</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Status: <span class="text-danger">*</span></label>
                        <select class="form-select @error('payment_status') is-invalid @enderror" name="payment_status" required>
                            <option value="due" @selected(old('payment_status') == 'due')>Due</option>
                            <option value="partial" @selected(old('payment_status') == 'partial')>Partial</option>
                            <option value="paid" @selected(old('payment_status') == 'paid')>Paid</option>
                        </select>
                        @error('payment_status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Products Table -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Purchase Items</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="productTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 100px;">Quantity</th>
                                    <th style="width: 120px;">Unit Cost</th>
                                    <th style="width: 120px;">Expiry Date</th>
                                    <th style="width: 100px;">Discount %</th>
                                    <th style="width: 80px;">Tax %</th>
                                    <th style="width: 120px;">Line Total</th>
                                    <th style="width: 60px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="productRows">
                                <tr class="product-row">
                                    <td>
                                        <select class="form-select form-select-sm product-select" name="products[]" required>
                                            <option value="">Select Product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm quantity" name="quantities[]" 
                                               placeholder="0" step="1" min="1" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm unit-cost" name="unit_costs[]" 
                                               placeholder="0" step="1" min="0" value="0" required>
                                    </td>
                                    <td>
                                        <input type="date" class="form-control form-control-sm expiry-date" name="expiry_dates[]" 
                                               min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm discount" name="discounts[]" 
                                               placeholder="0" step="1" min="0" max="100" value="0">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm tax" name="taxes[]" 
                                               placeholder="0" step="1" min="0" max="100" value="0">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm line-total" readonly placeholder="0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger remove-row" style="display:none;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="addProductBtn">
                        <i class="bi bi-plus-circle"></i> Add Product
                    </button>
                </div>

                <!-- Grand Total -->
                <div class="row mb-4">
                    <div class="col-md-6 offset-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Grand Total:</span>
                                    <span id="grandTotal">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Create Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productTable = document.getElementById('productRows');
    const grandTotalEl = document.getElementById('grandTotal');
    const addBtn = document.getElementById('addProductBtn');

    // Add product row
    addBtn.addEventListener('click', function() {
        const newRow = productTable.querySelector('.product-row').cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(el => {
            el.value = el.type === 'text' && el.classList.contains('line-total') ? '0.00' : el.type === 'number' ? '0' : '';
        });
        newRow.querySelector('.remove-row').style.display = 'block';
        productTable.appendChild(newRow);
        attachRowListeners(newRow);
    });

    // Attach listeners to row
    function attachRowListeners(row) {
        const inputs = row.querySelectorAll('.quantity, .unit-cost, .discount, .tax');
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                calculateLineTotal(row);
                calculateGrandTotal();
            });
        });

        const removeBtn = row.querySelector('.remove-row');
        removeBtn.addEventListener('click', function() {
            row.remove();
            calculateGrandTotal();
        });
    }

    // Calculate line total
    function calculateLineTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitCost = parseFloat(row.querySelector('.unit-cost').value) || 0;
        const discountPercent = parseFloat(row.querySelector('.discount').value) || 0;
        const taxPercent = parseFloat(row.querySelector('.tax').value) || 0;

        const subtotal = quantity * unitCost;
        const discountAmount = (subtotal * discountPercent) / 100;
        const taxableAmount = subtotal - discountAmount;
        const taxAmount = (taxableAmount * taxPercent) / 100;
        const lineTotal = taxableAmount + taxAmount;

        row.querySelector('.line-total').value = lineTotal.toFixed(2);
    }

    // Calculate grand total
    function calculateGrandTotal() {
        const lineTotals = document.querySelectorAll('.line-total');
        let total = 0;
        lineTotals.forEach(el => {
            total += parseFloat(el.value) || 0;
        });
        grandTotalEl.textContent = '$' + total.toFixed(2);
    }

    // Attach listeners to initial row
    productTable.querySelectorAll('.product-row').forEach(row => {
        attachRowListeners(row);
    });

    // Calculate on initial load
    calculateGrandTotal();
});
</script>

<!-- Supplier Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="supplierForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Number</label>
                                <input type="text" class="form-control" name="tax_number">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSupplier">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierForm = document.getElementById('supplierForm');
    const saveSupplierBtn = document.getElementById('saveSupplier');
    const supplierSelect = document.querySelector('select[name="supplier_id"]');

    saveSupplierBtn.addEventListener('click', function() {
        const formData = new FormData(supplierForm);
        
        fetch('/contacts/store-supplier', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add new supplier to dropdown
                const newOption = new Option(data.supplier.name, data.supplier.id);
                supplierSelect.add(newOption);
                supplierSelect.value = data.supplier.id;
                
                // Close modal and reset form
                bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();
                supplierForm.reset();
                
                // Show success message
                alert('Supplier added successfully!');
            } else {
                alert('Error adding supplier: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding supplier');
        });
    });
});
</script>
@endsection
