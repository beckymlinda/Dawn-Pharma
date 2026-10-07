@extends('layouts.app')
@section('title', 'Stock Expiry Report')

@section('content')
<div class="app-wrapper">
    <div class="app-main">
        <main class="app-content">
            
            <!-- Header & KPI Summary -->
            <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
                <div>
                    <h1 class="h4 fw-bold mb-1">Stock Expiry Report</h1>
                    <p class="text-muted small mb-0 text-uppercase tracking-wider fw-semibold">
                        Monitoring product freshness and inventory shelf life
                    </p>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm shadow-sm">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                    <a href="{{ route('reports.stock-expiry') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </a>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden">
                        <div class="card-body p-4 position-relative">
                            <div class="text-muted small fw-bold mb-1 text-uppercase">Items with Expiry</div>
                            <h2 class="fw-bold mb-0 text-dark">{{ number_format($totalItems) }}</h2>
                            <div class="small mt-2 text-primary fw-semibold">Tracked in inventory</div>
                            <i class="bi bi-box-seam position-absolute end-0 bottom-0 opacity-10 me-3 mb-2" style="font-size: 4rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden bg-danger-subtle">
                        <div class="card-body p-4 position-relative">
                            <div class="text-danger small fw-bold mb-1 text-uppercase">Expired Items</div>
                            <h2 class="fw-bold mb-0 text-danger">{{ number_format($expiredItems) }}</h2>
                            <div class="small mt-2 text-danger fw-semibold">Requires immediate removal</div>
                            <i class="bi bi-exclamation-octagon position-absolute end-0 bottom-0 opacity-10 me-3 mb-2 text-danger" style="font-size: 4rem;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden bg-warning-subtle">
                        <div class="card-body p-4 position-relative">
                            <div class="text-warning-emphasis small fw-bold mb-1 text-uppercase">Expiring in 30 Days</div>
                            <h2 class="fw-bold mb-0 text-warning-emphasis">{{ number_format($expiringSoonItems) }}</h2>
                            <div class="small mt-2 text-warning-emphasis fw-semibold">Prioritize for sale</div>
                            <i class="bi bi-hourglass-split position-absolute end-0 bottom-0 opacity-10 me-3 mb-2 text-warning" style="font-size: 4rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('reports.stock-expiry') }}" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Expiry Status</label>
                            <select name="expiry_status" class="form-select form-select-sm border-0 bg-light shadow-none">
                                <option value="all" {{ request('expiry_status') == 'all' ? 'selected' : '' }}>All Items</option>
                                <option value="expired" {{ request('expiry_status') == 'expired' ? 'selected' : '' }}>Expired Only</option>
                                <option value="expiring_soon" {{ request('expiry_status') == 'expiring_soon' ? 'selected' : '' }}>Expiring (30 Days)</option>
                                <option value="expiring_60_days" {{ request('expiry_status') == 'expiring_60_days' ? 'selected' : '' }}>Expiring (60 Days)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-light" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-light" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Product</label>
                            <select name="product_id" class="form-select form-select-sm border-0 bg-light">
                                <option value="">All Products</option>
                                @foreach($products as $id => $name)
                                    <option value="{{ $id }}" {{ request('product_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted">Supplier</label>
                            <select name="supplier_id" class="form-select form-select-sm border-0 bg-light">
                                <option value="">All Suppliers</option>
                                @foreach($suppliers as $id => $name)
                                    <option value="{{ $id }}" {{ request('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1 fw-bold shadow-sm">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <a href="{{ route('reports.stock-expiry') }}" class="btn btn-light btn-sm fw-bold border">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Table -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold h6">Expiry Inventory Details</h5>
                    <div class="small text-muted">
                        Showing {{ $expiryItems->firstItem() ?: 0 }} to {{ $expiryItems->lastItem() ?: 0 }} of {{ $expiryItems->total() }} items
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="bg-light text-muted text-uppercase x-small fw-bold">
                            <tr>
                                <th class="ps-4">Product Name</th>
                                <th>Supplier</th>
                                <th>Purchase / Expiry</th>
                                <th class="text-center">Remaining Life</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Value</th>
                                <th class="pe-4 text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiryItems as $item)
                                @php
                                    $expiryDate = $item->expiry_date ?: $item->exp_date;
                                    $today = \Carbon\Carbon::today();
                                    $daysUntilExpiry = $expiryDate ? \Carbon\Carbon::parse($expiryDate)->diffInDays($today, false) : null;
                                    
                                    $statusClass = 'success';
                                    $statusText = 'Valid';
                                    
                                    if (!$expiryDate) {
                                        $statusClass = 'secondary';
                                        $statusText = 'No Date';
                                    } elseif ($daysUntilExpiry < 0) {
                                        $statusClass = 'danger';
                                        $statusText = 'Expired';
                                    } elseif ($daysUntilExpiry <= 30) {
                                        $statusClass = 'warning';
                                        $statusText = 'Expiring Soon';
                                    }
                                @endphp
                                <tr class="{{ $statusText == 'Expired' ? 'table-danger-light' : '' }}">
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $item->product?->name ?? 'Deleted Product' }}</div>
                                        <div class="text-muted x-small">SKU: {{ $item->product?->sku ?? 'N/A' }}</div>
                                    </td>
                                    <td>{{ $item->purchase?->supplier?->name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="text-muted x-small">Purch: {{ $item->purchase?->purchase_date ? \Carbon\Carbon::parse($item->purchase->purchase_date)->format('d M, Y') : 'N/A' }}</div>
                                        <div class="fw-semibold">Exp: {{ $expiryDate ? \Carbon\Carbon::parse($expiryDate)->format('d M, Y') : 'Not Set' }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($expiryDate)
                                            <div class="progress" style="height: 6px; width: 100px; margin: 0 auto 5px;">
                                                @php 
                                                    $percent = $daysUntilExpiry < 0 ? 0 : min(100, ($daysUntilExpiry / 365) * 100);
                                                @endphp
                                                <div class="progress-bar bg-{{ $statusClass }}" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <span class="fw-bold text-{{ $statusClass }}">
                                                {{ $daysUntilExpiry < 0 ? abs($daysUntilExpiry) . ' days ago' : $daysUntilExpiry . ' days' }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($item->quantity) }}</td>
                                    <td class="text-end">{{ formatCurrency($item->quantity * $item->unit_cost) }}</td>
                                    <td class="pe-4 text-end">
                                        <span class="badge rounded-pill bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }} px-3 py-2">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="mb-2"><i class="bi bi-inbox fs-1 opacity-25"></i></div>
                                        <div>No stock expiry items found for the selected filters.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($expiryItems->hasPages())
                    <div class="card-footer bg-white py-3 border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">
                                Powered by {{ setting('powered_by') }}
                            </div>
                            <div>
                                {{ $expiryItems->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </main>
    </div>
</div>

<style>
    .bg-danger-subtle { background-color: #ffeef0 !important; }
    .bg-warning-subtle { background-color: #fff9eb !important; }
    .table-danger-light { background-color: #fff8f8; }
    .text-warning-emphasis { color: #856404 !important; }
    .x-small { font-size: 0.7rem; }
    .tracking-wider { letter-spacing: 1px; }
    
    .table thead th {
        font-size: 0.75rem;
        padding: 1rem 0.75rem;
    }
    
    .badge-subtle {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.65rem;
    }
    
    .bg-success-subtle { background-color: #e6ffed !important; border-color: #cfefdf !important; }
    .bg-danger-subtle { background-color: #ffeef0 !important; border-color: #ffdce0 !important; }
    .bg-warning-subtle { background-color: #fff9eb !important; border-color: #fff3cd !important; }
    .bg-secondary-subtle { background-color: #f6f8fa !important; border-color: #d1d5da !important; }
    
    @media print {
        .btn, form, nav, .card-footer { display: none !important; }
        .card { border: 1px solid #eee !important; box-shadow: none !important; }
        .app-main { padding: 0 !important; margin: 0 !important; }
    }
</style>
@endsection
