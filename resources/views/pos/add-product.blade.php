@extends('layouts.app')
@section('title', 'Add Product - EDUC Pharmacy')

@section('content')
<div class="app-wrapper">
    <div class="app-main">
        <main class="app-content p-4">
            
            <!-- Header Section -->
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="h4 fw-bold mb-1">Add New Product</h1>
                    <p class="text-muted small mb-0 text-uppercase tracking-wider fw-semibold">
                        Register a new item in your pharmacy inventory
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('products.download-template') }}" class="btn btn-outline-success btn-sm shadow-sm px-3">
                        <i class="bi bi-file-earmark-excel me-1"></i> Download Template
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                        <i class="bi bi-cloud-upload me-1"></i> Bulk Upload
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>

            @if (session('error_details'))
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <strong class="small">Some rows had issues:</strong>
                    </div>
                    <ul class="mb-0 x-small fw-semibold ps-3">
                        @foreach (session('error_details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong class="small">Please fix the following errors:</strong>
                    </div>
                    <ul class="mb-0 x-small fw-semibold ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
                @csrf

                <div class="row g-4">
                    <!-- Left Column: General Info & Pricing -->
                    <div class="col-lg-8">
                        
                        <!-- General Information Card -->
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold h6 text-primary"><i class="bi bi-info-circle me-2"></i>General Information</h5>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               name="name" placeholder="e.g. Paracetamol 500mg" value="{{ old('name') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">SKU (Stock Keeping Unit)</label>
                                        <div class="input-group">
                                            <span class="input-group-text border-0 bg-light"><i class="bi bi-upc-scan"></i></span>
                                            <input type="text" class="form-control border-0 bg-light shadow-none" 
                                                   name="sku" placeholder="Auto-generated if empty" value="{{ old('sku') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Category</label>
                                        <div class="d-flex gap-2">
                                            <select class="form-select border-0 bg-light shadow-none" name="category_id">
                                                <option value="">No Category</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id || (empty(old('category_id')) && $firstCategory && $firstCategory->id == $category->id))>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <a href="{{ route('categories.index') }}" class="btn btn-light btn-sm d-flex align-items-center px-3 border shadow-sm">+</a>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Brand / Manufacturer</label>
                                        <div class="d-flex gap-2">
                                            <select class="form-select border-0 bg-light shadow-none" name="brand_id">
                                                <option value="">No Brand</option>
                                                @foreach ($brands as $brand)
                                                    <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id || (empty(old('brand_id')) && $firstBrand && $firstBrand->id == $brand->id))>
                                                        {{ $brand->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <a href="{{ route('brands.index') }}" class="btn btn-light btn-sm d-flex align-items-center px-3 border shadow-sm">+</a>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Unit of Measure</label>
                                        <select class="form-select border-0 bg-light shadow-none" name="unit_id">
                                            <option value="">Select Unit</option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                                    {{ $unit->name }} ({{ $unit->short_name }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Margin Card -->
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold h6 text-primary"><i class="bi bi-cash-stack me-2"></i>Pricing & Margins</h5>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Purchase Price <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border-0 bg-light">MWK</span>
                                            <input type="number" class="form-control border-0 bg-light shadow-none fw-bold" 
                                                   name="purchase_price" step="1" min="0" value="{{ old('purchase_price') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Selling Price <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border-0 bg-light">MWK</span>
                                            <input type="number" class="form-control border-0 bg-light shadow-none fw-bold text-primary" 
                                                   name="selling_price" step="1" min="0" value="{{ old('selling_price') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="p-3 rounded-3 bg-light d-flex align-items-center justify-content-between border">
                                            <div>
                                                <div class="small fw-bold text-muted text-uppercase x-small mb-1">Estimated Profit Margin</div>
                                                <div class="h5 mb-0 fw-bold" id="profitDisplay">MWK 0.00</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="badge rounded-pill px-3 py-2 fs-6 bg-success" id="marginBadge">0%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold h6 text-primary"><i class="bi bi-card-text me-2"></i>Product Description</h5>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <textarea class="form-control border-0 bg-light shadow-none" name="description" rows="4" placeholder="Enter clinical notes, usage instructions, or composition details...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Inventory & Supplier -->
                    <div class="col-lg-4">
                        
                        <!-- Initial Inventory Card -->
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden border-top border-4 border-primary">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold h6 text-primary"><i class="bi bi-box-seam me-2"></i>Initial Stock</h5>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Opening Quantity</label>
                                        <input type="number" class="form-control border-0 bg-light shadow-none fw-bold" 
                                               name="purchase_quantity" step="1" min="0" value="{{ old('purchase_quantity', 0) }}">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Alert Quantity</label>
                                        <input type="number" class="form-control border-0 bg-light shadow-none" 
                                               name="alert_quantity" step="1" min="0" value="{{ old('alert_quantity', 10) }}">
                                        <small class="text-muted x-small">System will alert when stock falls below this</small>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Expiry Date</label>
                                        <input type="date" class="form-control border-0 bg-light shadow-none" 
                                               name="expiry_date" value="{{ old('expiry_date') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supplier Card -->
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold h6 text-primary"><i class="bi bi-truck me-2"></i>Supplier</h5>
                                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                    <i class="bi bi-plus-lg"></i> New
                                </button>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <select class="form-select border-0 bg-light shadow-none mb-3" name="supplier_id" id="supplierSelect">
                                    <option value="">Select Supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" 
                                                data-contact="{{ $supplier->contact_person ?? 'N/A' }}"
                                                data-phone="{{ $supplier->phone ?? 'N/A' }}"
                                                data-email="{{ $supplier->email ?? 'N/A' }}"
                                                @selected(old('supplier_id') == $supplier->id || (empty(old('supplier_id')) && $firstSupplier && $firstSupplier->id == $supplier->id))>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                <div id="supplierPreview" class="p-3 rounded-3 bg-light border" style="display: none;">
                                    <div class="small fw-bold text-dark mb-1" id="previewName">Supplier Name</div>
                                    <div class="text-muted x-small mb-1"><i class="bi bi-person me-1"></i> <span id="previewContact">Contact</span></div>
                                    <div class="text-muted x-small"><i class="bi bi-telephone me-1"></i> <span id="previewPhone">Phone</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Image Card -->
                        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="mb-0 fw-bold h6 text-primary"><i class="bi bi-image me-2"></i>Product Image</h5>
                            </div>
                            <div class="card-body p-4 pt-0">
                                <div class="image-upload-wrapper border-2 border-dashed rounded-3 p-4 text-center bg-light">
                                    <input type="file" name="image" id="imageInput" class="d-none" accept="image/*">
                                    <div id="imagePlaceholder">
                                        <i class="bi bi-cloud-arrow-up fs-1 text-muted opacity-50 mb-2 d-block"></i>
                                        <button type="button" class="btn btn-outline-primary btn-sm px-4" onclick="document.getElementById('imageInput').click()">Browse Image</button>
                                        <p class="x-small text-muted mt-2 mb-0">JPEG, PNG up to 5MB</p>
                                    </div>
                                    <div id="imagePreview" class="d-none">
                                        <img src="" class="img-fluid rounded-3 mb-2 shadow-sm" style="max-height: 150px;">
                                        <button type="button" class="btn btn-danger btn-sm px-3" onclick="resetImage()">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="sticky-bottom bg-white border-top p-3 shadow-lg d-flex justify-content-end gap-2 mt-4 mx-n4 mb-n4 rounded-bottom">
                    <button type="button" onclick="history.back()" class="btn btn-light border px-4 fw-bold">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                        <i class="bi bi-check-circle me-2"></i>Save Product
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-primary text-white">
                <h5 class="modal-title fw-bold h6"><i class="bi bi-person-plus me-2"></i>Add Quick Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="addSupplierForm">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Supplier Name *</label>
                            <input type="text" class="form-control border-0 bg-light" name="name" required placeholder="e.g. MedLab Pharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Contact Person</label>
                            <input type="text" class="form-control border-0 bg-light" name="contact_person" placeholder="e.g. John Doe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Phone Number</label>
                            <input type="tel" class="form-control border-0 bg-light" name="phone" placeholder="+265...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Email Address</label>
                            <input type="email" class="form-control border-0 bg-light" name="email" placeholder="supplier@example.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow">Create Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-success text-white">
                <h5 class="modal-title fw-bold h6"><i class="bi bi-cloud-upload me-2"></i>Bulk Product Upload</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('products.bulk-upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 small mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Upload a CSV file with your product data. Download the template to see the required format.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Select CSV File</label>
                        <input type="file" class="form-control border-0 bg-light" name="upload_file" accept=".csv" required>
                        <div class="form-text x-small">Maximum file size: 5MB</div>
                    </div>

                    <div class="p-3 rounded-3 bg-light border mt-4">
                        <h6 class="x-small fw-bold text-uppercase text-muted mb-2">Instructions:</h6>
                        <ul class="x-small text-muted mb-0 ps-3">
                            <li>Ensure all required fields are filled.</li>
                            <li>Dates are flexible (e.g., 2027-12-31 or 12/31/2027).</li>
                            <li>Foreign keys (Category, Brand, etc.) will be matched by name.</li>
                            <li>Existing products (matched by SKU or Name) will be skipped.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold shadow">Start Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Processing Overlay (Reliable CSS Version) -->
<div id="processingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999999; backdrop-filter: blur(5px); flex-direction: column; align-items: center; justify-content: center; color: white;">
    <div style="background: white; padding: 3rem; border-radius: 20px; text-align: center; color: #333; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-width: 450px;">
        <div class="spinner-border text-primary mb-4" style="width: 3.5rem; height: 3.5rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="fw-bold mb-2">Processing Your Request</h4>
        <p class="text-muted small mb-4">Please wait while we sync your inventory. This may take a few minutes for large files.</p>
        <div class="progress rounded-pill overflow-hidden" style="height: 10px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.7rem; }
    .tracking-wider { letter-spacing: 1px; }
    .border-dashed { border-style: dashed !important; }
    .sticky-bottom { position: sticky; bottom: 0; z-index: 1020; }
    
    .form-control:focus, .form-select:focus {
        background-color: #fff !important;
        border: 1px solid #0d6efd !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1) !important;
    }

    /* Style the date input consistent with others */
    input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 0.5;
        cursor: pointer;
    }
</style>

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pInput = document.querySelector('input[name="purchase_price"]');
        const sInput = document.querySelector('input[name="selling_price"]');
        const profitDisplay = document.getElementById('profitDisplay');
        const marginBadge = document.getElementById('marginBadge');
        
        function updateMargins() {
            const p = parseFloat(pInput.value) || 0;
            const s = parseFloat(sInput.value) || 0;
            const profit = s - p;
            const margin = p > 0 ? (profit / p) * 100 : 0;
            
            profitDisplay.textContent = 'MWK ' + profit.toLocaleString(undefined, {minimumFractionDigits: 2});
            marginBadge.textContent = margin.toFixed(1) + '%';
            
            marginBadge.className = 'badge rounded-pill px-3 py-2 fs-6 ' + 
                (margin < 10 ? 'bg-danger' : (margin < 25 ? 'bg-warning text-dark' : 'bg-success'));
        }

        pInput.addEventListener('input', updateMargins);
        sInput.addEventListener('input', updateMargins);
        updateMargins();

        // Supplier Preview Logic
        const supplierSelect = document.getElementById('supplierSelect');
        const supplierPreview = document.getElementById('supplierPreview');
        const previewName = document.getElementById('previewName');
        const previewContact = document.getElementById('previewContact');
        const previewPhone = document.getElementById('previewPhone');

        function updateSupplierPreview() {
            const opt = supplierSelect.options[supplierSelect.selectedIndex];
            if (supplierSelect.value) {
                previewName.textContent = opt.text;
                previewContact.textContent = opt.dataset.contact || 'N/A';
                previewPhone.textContent = opt.dataset.phone || 'N/A';
                supplierPreview.style.display = 'block';
            } else {
                supplierPreview.style.display = 'none';
            }
        }

        supplierSelect.addEventListener('change', updateSupplierPreview);
        updateSupplierPreview();

        // Image Preview Logic
        const imageInput = document.getElementById('imageInput');
        const imagePlaceholder = document.getElementById('imagePlaceholder');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = imagePreview.querySelector('img');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePlaceholder.classList.add('d-none');
                    imagePreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });

        window.resetImage = function() {
            imageInput.value = '';
            imagePlaceholder.classList.remove('d-none');
            imagePreview.classList.add('d-none');
        };

        // Modal Form Handling
        const supplierForm = document.getElementById('addSupplierForm');
        supplierForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            btn.disabled = true;

            const formData = new FormData(this);
            fetch('{{ route('contacts.store.supplier') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const newOpt = new Option(data.supplier.name, data.supplier.id);
                    newOpt.dataset.contact = data.supplier.contact_person || 'N/A';
                    newOpt.dataset.phone = data.supplier.phone || 'N/A';
                    newOpt.dataset.email = data.supplier.email || 'N/A';
                    
                    supplierSelect.add(newOpt);
                    supplierSelect.value = data.supplier.id;
                    updateSupplierPreview();
                    
                    bootstrap.Modal.getInstance(document.getElementById('addSupplierModal')).hide();
                    supplierForm.reset();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => alert('An unexpected error occurred.'))
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

        // Bulk Upload Processing Modal
        const bulkUploadForm = document.querySelector('form[action="{{ route('products.bulk-upload') }}"]');
        if (bulkUploadForm) {
            bulkUploadForm.addEventListener('submit', function() {
                // Hide the upload modal first
                const uploadModal = bootstrap.Modal.getInstance(document.getElementById('bulkUploadModal'));
                if (uploadModal) uploadModal.hide();
                
                // Show the processing overlay
                const procModal = document.getElementById('processingModal');
                if (procModal) {
                    procModal.style.display = 'flex';
                }
            });
        }
    });
</script>
@endsection
@endsection
