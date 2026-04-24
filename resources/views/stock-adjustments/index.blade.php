@extends('layouts.app')

@section('title', 'Stock Adjustments')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Stock Adjustments</h4>
        @can('create_stock_adjustment')
            <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Adjustment
            </a>
        @endcan
    </div>

    <!-- Search Bar -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" 
                       class="form-control" 
                       id="adjustmentSearch" 
                       placeholder="Search..." 
                       value="{{ request('search') }}"
                       onkeypress="if(event.key === 'Enter') { performSearch(); }">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="filterType" onchange="performSearch()">
                <option value="">All Fields</option>
                <option value="product" {{ request('filter') == 'product' ? 'selected' : '' }}>Product Name/SKU</option>
                <option value="reason" {{ request('filter') == 'reason' ? 'selected' : '' }}>Reason</option>
                <option value="user" {{ request('filter') == 'user' ? 'selected' : '' }}>User</option>
                <option value="type" {{ request('filter') == 'type' ? 'selected' : '' }}>Adjustment Type</option>
            </select>
        </div>
        <div class="col-md-5">
            <button type="button" class="btn btn-outline-secondary me-2" onclick="clearSearch()">
                <i class="bi bi-x-circle me-1"></i> Clear
            </button>
            <button type="button" class="btn btn-primary" onclick="performSearch()">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity Change</th>
                            <th>Previous Qty</th>
                            <th>New Qty</th>
                            <th>Price Change</th>
                            <th>Reason</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adjustment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($adjustment->created_at)->format('M d, Y H:i') }}</td>
                                <td>
                                    @if($adjustment->product)
                                        <strong>{{ $adjustment->product->name }}</strong>
                                        <br>
                                        <small class="text-muted">SKU: {{ $adjustment->product->sku }}</small>
                                    @else
                                        <span class="text-danger">Product Deleted</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $adjustment->adjustment_type === 'add' ? 'success' : 'danger' }}">
                                        {{ $adjustment->adjustment_type_text }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold {{ $adjustment->adjustment_type === 'add' ? 'text-success' : 'text-danger' }}">
                                        {{ $adjustment->quantity_change }}
                                    </span>
                                </td>
                                <td>{{ number_format($adjustment->previous_quantity) }}</td>
                                <td>{{ number_format($adjustment->new_quantity) }}</td>
                                <td>
                                    @if($adjustment->previous_price != $adjustment->new_price)
                                        <span class="badge bg-info">
                                            {{ formatCurrency($adjustment->previous_price) }} → {{ formatCurrency($adjustment->new_price) }}
                                        </span>
                                    @else
                                        <span class="text-muted">No change</span>
                                    @endif
                                </td>
                                <td>{{ $adjustment->reason }}</td>
                                <td>{{ $adjustment->user->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                    No stock adjustments found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $adjustments->firstItem() }} to {{ $adjustments->lastItem() }} of {{ $adjustments->total() }} entries
                </div>
                {{ $adjustments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Search functionality for stock adjustments
function performSearch() {
    const searchValue = document.getElementById('adjustmentSearch').value.trim();
    const filterType = document.getElementById('filterType').value;
    const currentUrl = new URL(window.location);
    
    if (searchValue) {
        currentUrl.searchParams.set('search', searchValue);
    } else {
        currentUrl.searchParams.delete('search');
    }
    
    if (filterType) {
        currentUrl.searchParams.set('filter', filterType);
    } else {
        currentUrl.searchParams.delete('filter');
    }
    
    window.location.href = currentUrl.toString();
}

// Clear search function
function clearSearch() {
    document.getElementById('adjustmentSearch').value = '';
    document.getElementById('filterType').value = '';
    performSearch();
}

// Auto-search on input with debounce (like POS)
let searchTimeout;
document.getElementById('adjustmentSearch').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch();
    }, 500); // 500ms debounce
});

// Handle Enter key
document.getElementById('adjustmentSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        performSearch();
    }
});
</script>
@endsection
