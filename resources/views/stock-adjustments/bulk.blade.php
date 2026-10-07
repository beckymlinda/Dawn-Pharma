@extends('layouts.app')

@section('title', 'Bulk Stock Upload')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Bulk Stock Upload</h4>
        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Adjustments
        </a>
    </div>

    @if (session('error_details'))
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong class="small">Some rows had issues:</strong>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach (session('error_details') as $detail)
                    <li>{{ $detail }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><span class="badge bg-primary me-2">1</span>Download the template</h6>
                    <p class="text-muted small">
                        The template lists all products currently in the system with their current stock.
                        Fill in <strong>Quantity to Add</strong> for the products you are restocking and leave the rest blank.
                    </p>
                    <a href="{{ route('stock-adjustments.bulk.template') }}" class="btn btn-outline-success mb-4">
                        <i class="bi bi-file-earmark-excel me-1"></i> Download Template
                    </a>

                    <h6 class="fw-bold mb-3"><span class="badge bg-primary me-2">2</span>Upload the filled template</h6>
                    <form action="{{ route('stock-adjustments.bulk.upload') }}" method="POST" enctype="multipart/form-data" id="bulkStockForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="upload_file" accept=".csv,.txt" required>
                            <div class="form-text">Maximum file size: 5MB. Save as CSV if editing in Excel.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Default Reason</label>
                            <input type="text" class="form-control" name="default_reason" maxlength="150"
                                   value="{{ old('default_reason') }}" placeholder="Bulk stock upload">
                            <div class="form-text">Used for rows where the Reason column is empty.</div>
                        </div>
                        <button type="submit" class="btn btn-primary" id="bulkStockSubmit">
                            <i class="bi bi-cloud-upload me-1"></i> Upload Stock
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card bg-light border">
                <div class="card-body small">
                    <h6 class="fw-bold text-uppercase text-muted small mb-2">How it works</h6>
                    <ul class="text-muted mb-0 ps-3">
                        <li>Stock is only <strong>added</strong> to products that already exist, matched by SKU (or Product Name if SKU is blank).</li>
                        <li>No new products are created. Unknown products are listed as errors; add them under Products first.</li>
                        <li>If a product appears more than once in the file, only the first row is used.</li>
                        <li>Uploading the same data again is rejected, so stock is never added twice.</li>
                        <li>Rows with an empty or zero quantity are ignored.</li>
                        <li>New prices and expiry date are optional; leave them blank to keep the current values.</li>
                        <li>"Current Stock" in the template is for reference only and is not imported.</li>
                        <li>All rows are applied together. If something fails, no stock is changed.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('bulkStockForm').addEventListener('submit', function () {
    const btn = document.getElementById('bulkStockSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';
});
</script>
@endsection
