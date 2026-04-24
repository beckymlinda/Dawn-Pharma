@extends('layouts.app')

@section('title', 'Add Product - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Add New Product</h1>
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

    <div class="card border">
        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf

                <!-- Row 1: Product Name, SKU, Barcode Type -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Product Name: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" 
                               name="name" placeholder="Product Name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">SKU: <i class="bi bi-info-circle"></i></label>
                        <input type="text" class="form-control form-control-sm @error('sku') is-invalid @enderror" 
                               name="sku" placeholder="Auto-generated if empty" value="{{ old('sku') }}">
                        <small class="text-muted">Leave empty to auto-generate (PRD-00001, PRD-00002, etc.)</small>
                        @error('sku')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Purchase Price: <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm @error('purchase_price') is-invalid @enderror" 
                               name="purchase_price" placeholder="0.00" step="0.01" min="0" value="{{ old('purchase_price') }}" required>
                        @error('purchase_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Row 2: Unit, Brand, Category, Supplier -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Unit:</label>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm @error('unit_id') is-invalid @enderror" 
                                    name="unit_id">
                                <option value="">Please Select</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                        {{ $unit->name }} ({{ $unit->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('units.create') }}" class="btn btn-primary btn-sm rounded-circle" 
                               style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;text-decoration:none;">
                                +
                            </a>
                        </div>
                        @error('unit_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Brand:</label>
                        <small class="text-muted d-block mb-1">Select a brand or leave as default</small>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm @error('brand_id') is-invalid @enderror" name="brand_id">
                                <option value="">Please Select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" 
                                            @selected(old('brand_id') == $brand->id || (empty(old('brand_id')) && $firstBrand && $firstBrand->id == $brand->id))>
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                            <a href="{{ route('brands.create') }}" class="btn btn-primary btn-sm rounded-circle" 
                               style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;text-decoration:none;">
                                +
                            </a>
                        </div>
                        @error('brand_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Category:</label>
                        <small class="text-muted d-block mb-1">Select a category or leave as default</small>
                        <select class="form-select form-select-sm @error('category_id') is-invalid @enderror" name="category_id">
                            <option value="">Please Select</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" 
                                        @selected(old('category_id') == $category->id || (empty(old('category_id')) && $firstCategory && $firstCategory->id == $category->id))>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Supplier:</label>
                        <small class="text-muted d-block mb-1">Select a supplier or leave as default</small>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplierSelect">
                                <option value="">Please Select</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" 
                                            @selected(old('supplier_id') == $supplier->id || (empty(old('supplier_id')) && $firstSupplier && $firstSupplier->id == $supplier->id))>
                                            data-contact="{{ $supplier->contact_person ?? 'N/A' }}"
                                            data-phone="{{ $supplier->phone ?? 'N/A' }}"
                                            data-email="{{ $supplier->email ?? 'N/A' }}">
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary btn-sm rounded-circle" 
                                   style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;text-decoration:none;"
                                   data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                +
                            </button>
                        </div>
                        @error('supplier_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-1">
                        <label class="form-label fw-semibold small">Info:</label>
                        <div class="form-control form-control-sm bg-light" style="height: auto; padding: 0.375rem 0.75rem;">
                            <small id="supplierInfo" class="text-muted">No supplier selected</small>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Selling Price, Alert Quantity, Expiry Date -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Selling Price: <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm @error('selling_price') is-invalid @enderror" 
                               name="selling_price" id="selling_price" placeholder="0.00" step="0.01" min="0" value="{{ old('selling_price') }}" required>
                        @error('selling_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Alert Quantity: <i class="bi bi-info-circle"></i></label>
                        <input type="number" class="form-control form-control-sm @error('alert_quantity') is-invalid @enderror" 
                               name="alert_quantity" placeholder="Alert quantity" step="0.01" min="0" value="{{ old('alert_quantity') }}">
                        @error('alert_quantity')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Expiry Date: <i class="bi bi-info-circle"></i></label>
                        <input type="date" class="form-control form-control-sm @error('expiry_date') is-invalid @enderror" 
                               name="expiry_date" value="{{ old('expiry_date') }}">
                        <small class="text-muted">Product expiry date for tracking</small>
                        @error('expiry_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Profit Percentage Display -->
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <div class="card bg-light border">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold">Profit Margin:</span>
                                    <span id="profitPercentage" class="badge bg-success fs-6">0%</span>
                                </div>
                                <small class="text-muted">Automatically calculated based on Purchase and Selling Price</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchase Section -->
                <div class="row g-3 mb-3">
                    <div class="col-md-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0"><i class="bi bi-cart-plus"></i> Purchase Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Purchase Quantity:</label>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="purchase_quantity" placeholder="0" step="0.01" min="0" value="0">
                                        <small class="text-muted">Initial stock quantity</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Purchase Date:</label>
                                        <input type="date" class="form-control form-control-sm" 
                                               name="purchase_date" value="{{ date('Y-m-d') }}">
                                        <small class="text-muted">Date of purchase</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Reference No:</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="reference_no" placeholder="Auto-generated" readonly>
                                        <small class="text-muted">Purchase reference</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Total Cost:</label>
                                        <div class="form-control form-control-sm bg-light">
                                            <span id="totalPurchaseCost">MWK 0.00</span>
                                        </div>
                                        <small class="text-muted">Quantity × Purchase Price</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description and Image -->
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small">Product Description:</label>
                        <textarea class="form-control form-control-sm @error('description') is-invalid @enderror" 
                                  name="description" rows="6" placeholder="Enter product description...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Product Image:</label>
                        <div class="border rounded p-4 text-center">
                            <input type="file" class="form-control form-control-sm d-none" id="imageInput" 
                                   name="image" accept="image/*">
                            <button type="button" class="btn btn-primary btn-sm fw-semibold" onclick="document.getElementById('imageInput').click()">
                                📷 Browse..
                            </button>
                            <p class="text-muted small mt-2 mb-0">Max File size: 5MB</p>
                            <p class="text-muted small mb-0">Aspect ratio should be 1:1</p>
                            <small id="fileName" class="text-muted d-block mt-2"></small>
                        </div>
                        @error('image')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show selected filename
    document.getElementById('imageInput').addEventListener('change', function() {
        const fileName = this.files[0]?.name || '';
        document.getElementById('fileName').textContent = fileName ? `Selected: ${fileName}` : '';
    });

    // Display supplier information
    document.getElementById('supplierSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const supplierInfo = document.getElementById('supplierInfo');
        
        if (this.value) {
            const contact = selectedOption.dataset.contact || 'N/A';
            const phone = selectedOption.dataset.phone || 'N/A';
            const email = selectedOption.dataset.email || 'N/A';
            
            supplierInfo.innerHTML = `
                <div class="small">
                    <strong>Contact:</strong> ${contact}<br>
                    <strong>Phone:</strong> ${phone}<br>
                    <strong>Email:</strong> ${email}
                </div>
            `;
        } else {
            supplierInfo.innerHTML = 'No supplier selected';
        }
    });

    // Calculate profit percentage
    function calculateProfitPercentage() {
        const purchasePrice = parseFloat(document.querySelector('input[name="purchase_price"]').value) || 0;
        const sellingPrice = parseFloat(document.querySelector('input[name="selling_price"]').value) || 0;
        
        if (purchasePrice > 0 && sellingPrice > 0) {
            const profit = sellingPrice - purchasePrice;
            const profitPercentage = ((profit / purchasePrice) * 100).toFixed(2);
            const profitElement = document.getElementById('profitPercentage');
            
            // Update display
            profitElement.textContent = profitPercentage + '%';
            
            // Update badge color based on profit margin
            profitElement.className = 'badge fs-6';
            if (profitPercentage < 10) {
                profitElement.classList.add('bg-danger'); // Low margin
            } else if (profitPercentage < 20) {
                profitElement.classList.add('bg-warning'); // Medium margin
            } else {
                profitElement.classList.add('bg-success'); // Good margin
            }
        } else {
            document.getElementById('profitPercentage').textContent = '0%';
        }
        
        // Also calculate purchase total
        calculatePurchaseTotal();
    }

    // Calculate purchase total
    function calculatePurchaseTotal() {
        const purchasePrice = parseFloat(document.querySelector('input[name="purchase_price"]').value) || 0;
        const purchaseQuantity = parseFloat(document.querySelector('input[name="purchase_quantity"]').value) || 0;
        const totalCost = purchasePrice * purchaseQuantity;
        
        document.getElementById('totalPurchaseCost').textContent = 'MWK ' + totalCost.toFixed(2);
    }

    // Generate reference number
    function generateReferenceNumber() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        
        return `PUR-${year}${month}${day}-${random}`;
    }

    // Add event listeners
    document.querySelector('input[name="purchase_price"]').addEventListener('input', calculateProfitPercentage);
    document.querySelector('input[name="selling_price"]').addEventListener('input', calculateProfitPercentage);
    document.querySelector('input[name="purchase_quantity"]').addEventListener('input', calculatePurchaseTotal);

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculateProfitPercentage();
        calculatePurchaseTotal();
        
        // Generate reference number
        document.querySelector('input[name="reference_no"]').value = generateReferenceNumber();
        
        // Trigger supplier info display if supplier is pre-selected
        const supplierSelect = document.getElementById('supplierSelect');
        if (supplierSelect.value) {
            supplierSelect.dispatchEvent(new Event('change'));
        }
    });

    // Setup supplier form when modal is shown
    const addSupplierModal = document.getElementById('addSupplierModal');
    if (addSupplierModal) {
        addSupplierModal.addEventListener('shown.bs.modal', function() {
            // Handle supplier form submission
            const supplierForm = document.getElementById('addSupplierForm');
            if (supplierForm && !supplierForm.hasAttribute('data-listener-attached')) {
                supplierForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    // Show loading state
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Adding...';
                    submitBtn.disabled = true;
                    
                    // Send AJAX request to create supplier
                    fetch('/contacts/store-supplier', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Debug: Log the response data
                        console.log('Supplier creation response:', data);
                        
                        if (data.success) {
                            // Add new supplier to dropdown
                            const supplierSelect = document.getElementById('supplierSelect');
                            const newOption = document.createElement('option');
                            newOption.value = data.supplier.id;
                            newOption.textContent = data.supplier.name; // Show only supplier name
                            
                            // Debug: Log the supplier data
                            console.log('Supplier data received:', data.supplier);
                            console.log('Contact person:', data.supplier.contact_person);
                            console.log('Phone:', data.supplier.phone);
                            console.log('Email:', data.supplier.email);
                            
                            // Set data attributes for supplier info display
                            newOption.setAttribute('data-contact', data.supplier.contact_person || 'N/A');
                            newOption.setAttribute('data-phone', data.supplier.phone || 'N/A');
                            newOption.setAttribute('data-email', data.supplier.email || 'N/A');
                            
                            // Debug: Log the data attributes
                            console.log('Data attributes set:', {
                                contact: data.supplier.contact_person || 'N/A',
                                phone: data.supplier.phone || 'N/A',
                                email: data.supplier.email || 'N/A'
                            });
                            
                            // Clear any existing selection
                            supplierSelect.value = '';
                            
                            // Add the new option
                            supplierSelect.appendChild(newOption);
                            
                            // Select the new supplier
                            supplierSelect.value = data.supplier.id;
                            
                            // Trigger change event to update supplier info
                            supplierSelect.dispatchEvent(new Event('change'));
                            
                            // Debug: Log the addition
                            console.log('New supplier added to dropdown:', {
                                id: data.supplier.id,
                                name: data.supplier.name,
                                contact: data.supplier.contact_person,
                                phone: data.supplier.phone,
                                email: data.supplier.email
                            });
                            
                            // Close modal and reset form
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addSupplierModal'));
                            modal.hide();
                            this.reset();
                            
                            // Show success message using alert
                            alert('Supplier added successfully!');
                            
                        } else {
                            // Show validation errors
                            if (data.errors) {
                                Object.keys(data.errors).forEach(field => {
                                    const errorDiv = document.getElementById(field + 'Error');
                                    if (errorDiv) {
                                        errorDiv.textContent = data.errors[field][0];
                                        errorDiv.style.display = 'block';
                                    }
                                });
                            }
                            
                            // Show error message using alert
                            const errorMessage = data.message || 'Failed to add supplier. Please try again.';
                            alert(errorMessage);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to add supplier. Please try again.');
                    })
                    .finally(() => {
                        // Reset button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
                
                // Mark as listener attached to prevent duplicates
                supplierForm.setAttribute('data-listener-attached', 'true');
            }
            
            // Clear error messages on input
            const supplierFormInputs = document.querySelectorAll('#addSupplierForm input');
            if (supplierFormInputs.length > 0) {
                supplierFormInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const errorDiv = document.getElementById(this.name + 'Error');
                        if (errorDiv) {
                            errorDiv.style.display = 'none';
                        }
                    });
                });
            }
        });
    }
    
    // Helper functions for notifications
    function showSuccessMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show';
        alert.innerHTML = `
            <strong>Success!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.card'));
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }
    
    function showErrorMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            <strong>Error!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container-fluid').insertBefore(alert, document.querySelector('.card'));
    }
</script>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSupplierModalLabel">
                    <i class="bi bi-person-plus me-2"></i>Add New Supplier
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addSupplierForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold small">Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="name" required>
                            <div class="invalid-feedback" id="supplierNameError"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Contact Person</label>
                            <input type="text" class="form-control form-control-sm" name="contact_person">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Phone</label>
                            <input type="tel" class="form-control form-control-sm" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" class="form-control form-control-sm" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Address</label>
                            <input type="text" class="form-control form-control-sm" name="address">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
