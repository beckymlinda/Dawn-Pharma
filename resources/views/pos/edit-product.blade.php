@extends('layouts.app')

@section('title', 'Edit Product - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Edit Product</h1>
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
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <!-- Row 1: Product Name, SKU, Barcode Type -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Product Name: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" 
                               name="name" placeholder="Product Name" value="{{ old('name', $product->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">SKU: <i class="bi bi-info-circle"></i></label>
                        <input type="text" class="form-control form-control-sm @error('sku') is-invalid @enderror" 
                               name="sku" placeholder="SKU" value="{{ old('sku', $product->sku) }}">
                        @error('sku')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Purchase Price: <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm @error('purchase_price') is-invalid @enderror" 
                               name="purchase_price" placeholder="0.00" step="0.01" min="0" value="{{ old('purchase_price', $product->purchase_price) }}" required>
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
                                    <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>
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
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm @error('brand_id') is-invalid @enderror" name="brand_id">
                                <option value="">Please Select</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>
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
                        <select class="form-select form-select-sm @error('category_id') is-invalid @enderror" name="category_id">
                            <option value="">Please Select</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
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
                        <select class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" name="supplier_id" id="supplierSelect">
                            <option value="">Please Select</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" 
                                        @selected(old('supplier_id', $product->supplier_id) == $supplier->id)
                                        data-contact="{{ $supplier->contact_person ?? 'N/A' }}"
                                        data-phone="{{ $supplier->phone ?? 'N/A' }}"
                                        data-email="{{ $supplier->email ?? 'N/A' }}">
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
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
                               name="selling_price" id="selling_price" placeholder="0.00" step="0.01" min="0" value="{{ old('selling_price', $product->selling_price) }}" required>
                        @error('selling_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Alert Quantity: <i class="bi bi-info-circle"></i></label>
                        <input type="number" class="form-control form-control-sm @error('alert_quantity') is-invalid @enderror" 
                               name="alert_quantity" placeholder="Alert quantity" step="0.01" min="0" value="{{ old('alert_quantity', $product->alert_quantity) }}">
                        @error('alert_quantity')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Expiry Date: <i class="bi bi-info-circle"></i></label>
                        <input type="date" class="form-control form-control-sm @error('expiry_date') is-invalid @enderror" 
                               name="expiry_date" value="{{ old('expiry_date', $product->expiry_date) }}">
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

                <!-- Description and Image -->
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small">Product Description:</label>
                        <textarea class="form-control form-control-sm @error('description') is-invalid @enderror" 
                                  name="description" rows="6" placeholder="Enter product description...">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">Product Image:</label>
                        @if ($product->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-width: 200px;">
                            </div>
                        @endif
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
                        <i class="bi bi-check-circle"></i> Update Product
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
    }

    // Add event listeners
    document.querySelector('input[name="purchase_price"]').addEventListener('input', calculateProfitPercentage);
    document.querySelector('input[name="selling_price"]').addEventListener('input', calculateProfitPercentage);

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculateProfitPercentage();
        
        // Trigger supplier info display if supplier is pre-selected
        const supplierSelect = document.getElementById('supplierSelect');
        if (supplierSelect.value) {
            supplierSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endsection
