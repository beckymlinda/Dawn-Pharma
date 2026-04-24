@extends('layouts.app')

@section('title', 'Products - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Products</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Product
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border">
        <div class="card-body">
            <!-- Search and Display Options -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="productSearch" placeholder="Search products by name, SKU, category, brand..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 me-2">Show:</label>
                        <select class="form-select form-select-sm" id="productDisplayLimit" style="width: auto;" onchange="changeDisplayLimit(this.value)">
                            <option value="25" {{ request('limit', 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('limit', 25) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('limit', 25) == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ request('limit', 25) == 200 ? 'selected' : '' }}>200</option>
                            <option value="all" {{ request('limit', 25) == 'all' ? 'selected' : '' }}>All</option>
                        </select>
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">Clear</button>
                    </div>
                </div>
            </div>
            
            @if ($products->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Unit</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td><span class="badge bg-secondary">{{ $product->sku }}</span></td>
                                    <td>{{ $product->unit?->short_name ?? '-' }}</td>
                                    <td>{{ $product->category?->name ?? '-' }}</td>
                                    <td>{{ $product->brand?->name ?? '-' }}</td>
                                    <td>MWK {{ number_format($product->purchase_price, 2) }}</td>
                                    <td>MWK {{ number_format($product->selling_price, 2) }}</td>
                                    <td>
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No products found. <a href="{{ route('products.create') }}">Create one now!</a></p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('productSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        performSearch();
    }
});

// Auto-search on input with debounce - fixed cursor issue
let searchTimeout;
document.getElementById('productSearch').addEventListener('input', function(e) {
    // Store cursor position
    const cursorPosition = e.target.selectionStart;
    const searchValue = e.target.value;
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        // Restore cursor position after potential DOM updates
        setTimeout(function() {
            e.target.setSelectionRange(cursorPosition, cursorPosition);
        }, 0);
        performSearch();
    }, 500);
});

function performSearch() {
    const searchValue = document.getElementById('productSearch').value;
    const currentUrl = new URL(window.location);
    
    if (searchValue.trim()) {
        currentUrl.searchParams.set('search', searchValue);
    } else {
        currentUrl.searchParams.delete('search');
    }
    
    window.location.href = currentUrl.toString();
}

function changeDisplayLimit(limit) {
    const currentUrl = new URL(window.location);
    
    if (limit === 'all') {
        currentUrl.searchParams.delete('limit');
    } else {
        currentUrl.searchParams.set('limit', limit);
    }
    
    window.location.href = currentUrl.toString();
}

function clearFilters() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.delete('search');
    currentUrl.searchParams.delete('limit');
    window.location.href = currentUrl.toString();
}

// Remove auto-focus to prevent cursor issues
// document.addEventListener('DOMContentLoaded', function() {
//     document.getElementById('productSearch').focus();
// });
</script>

@endsection
