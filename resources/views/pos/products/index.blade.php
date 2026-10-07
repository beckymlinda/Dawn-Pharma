@extends('layouts.app')

@section('title', 'Product Management - ' . setting('system_name'))

@section('content')
<div class="container-fluid py-4 products-page">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">Product Management</h4>
            <p class="text-muted small mb-0">Manage your inventory, pricing, and stock levels.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Add New Product
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-primary">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-primary-soft text-primary rounded-3 p-2">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 fw-medium">Total Products</p>
                        <h4 class="fw-bold mb-0">{{ number_format($stats['total']) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-danger">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-danger-soft text-danger rounded-3 p-2">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 fw-medium">Low Stock</p>
                        <h4 class="fw-bold mb-0 text-danger">{{ number_format($stats['low_stock']) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-success">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-success-soft text-success rounded-3 p-2">
                        <i class="bi bi-tags fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 fw-medium">Categories</p>
                        <h4 class="fw-bold mb-0">{{ number_format($stats['categories']) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white border-start border-4 border-info">
                <div class="d-flex align-items-center gap-3">
                    <div class="stats-icon bg-info-soft text-info rounded-3 p-2">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                    <div>
                        <p class="text-muted small mb-0 fw-medium">Inventory Value</p>
                        <h5 class="fw-bold mb-0">MWK {{ number_format($stats['total_value'] / 1000000, 1) }}M</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Table Container -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group search-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control bg-light border-0" id="productSearch" placeholder="Search by name, SKU, category..." value="{{ request('search') }}" autocomplete="off">
                        <button class="btn btn-light border-0 text-muted" type="button" id="clearSearch" title="Clear Search">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-7 d-flex justify-content-md-end align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-medium">Show</span>
                        <select class="form-select form-select-sm border-0 bg-light rounded-pill px-3" id="productDisplayLimit" style="width: auto;">
                            <option value="25" {{ request('limit', 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('limit', 25) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('limit', 25) == 100 ? 'selected' : '' }}>100</option>
                            <option value="all" {{ request('limit', 25) == 'all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>
                    <div class="vr mx-2 d-none d-md-block" style="height: 20px;"></div>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 border-0 bg-light" onclick="exportData()">
                        <i class="bi bi-download me-1"></i> Export
                    </button>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 border-0 bg-light" onclick="printTable()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>

        <div id="tableContainer" class="position-relative min-vh-50">
            <div id="dynamicContent">
                <!-- Table will be loaded here via AJAX -->
                @include('pos.products.table')
            </div>
            
            <!-- Loading Spinner -->
            <div id="tableLoader" class="position-absolute top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center bg-white bg-opacity-75" style="z-index: 10;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.products-page {
    background-color: #f8f9fa;
}

.stats-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-primary-soft { background-color: rgba(0, 77, 61, 0.1); }
.bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
.bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
.bg-light-warning { background-color: #fff9e6; }
.bg-light-danger { background-color: #fff5f5; }

.search-group {
    border-radius: 50px;
    overflow: hidden;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}

.btn-icon {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    transform: scale(1.1);
}

.table thead th {
    padding-top: 1rem;
    padding-bottom: 1rem;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    background-color: var(--theme-primary) !important;
    color: white !important;
}

.table tbody td {
    padding-top: 1rem;
    padding-bottom: 1rem;
    font-size: 0.9rem;
}

.badge-soft {
    padding: 0.4em 0.8em;
    font-weight: 500;
}

/* Pagination Overrides */
.pagination { margin-bottom: 0; }
.page-link {
    border: none;
    padding: 0.4rem 0.75rem;
    margin: 0 2px;
    border-radius: 8px !important;
    color: #6c757d;
    font-size: 0.85rem;
}
.page-item.active .page-link {
    background-color: var(--theme-primary);
    color: #fff;
}
</style>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    const limitSelect = document.getElementById('productDisplayLimit');
    const clearBtn = document.getElementById('clearSearch');
    const tableContainer = document.getElementById('tableContainer');
    const tableLoader = document.getElementById('tableLoader');

    let searchTimeout;

    // Search Input Event
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchProducts, 400);
    });

    // Limit Select Event
    limitSelect.addEventListener('change', fetchProducts);

    // Clear Search Event
    clearBtn.addEventListener('click', function() {
        if (searchInput.value) {
            searchInput.value = '';
            fetchProducts();
        }
    });

    // Handle Pagination Clicks (Delegated)
    tableContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.pagination a');
        if (link) {
            e.preventDefault();
            const url = new URL(link.href);
            fetchProducts(url);
        }
    });

    function fetchProducts(url = null) {
        if (!url) {
            url = new URL(window.location.href);
            const search = searchInput.value.trim();
            const limit = limitSelect.value;
            
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            
            if (limit !== '25') url.searchParams.set('limit', limit);
            else url.searchParams.delete('limit');
            
            // Reset to page 1 on new search
            url.searchParams.delete('page');
        }

        // Show loader
        tableLoader.classList.remove('d-none');
        tableLoader.classList.add('d-flex');

        // Update URL in browser without refresh
        window.history.pushState({}, '', url);

        // AJAX Fetch
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Update only the dynamic content part
            document.getElementById('dynamicContent').innerHTML = html;
            
            tableLoader.classList.add('d-none');
            tableLoader.classList.remove('d-flex');
        })
        .catch(error => {
            console.error('Error fetching products:', error);
            tableLoader.classList.add('d-none');
            tableLoader.classList.remove('d-flex');
        });
    }
});

function exportData() {
    alert('Exporting data as CSV...');
    // Logic for export
}

function printTable() {
    window.print();
}
</script>
@endsection
