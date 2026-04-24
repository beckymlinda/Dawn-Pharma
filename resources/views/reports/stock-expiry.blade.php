@extends('layouts.app')

@section('title', 'Stock Expiry Report')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Stock Expiry Report
                    </h5>
                    <div>
                        <a href="{{ route('reports.stock-expiry.export.csv') }}" class="btn btn-success btn-sm">
                            <i class="bi bi-file-earmark-csv me-1"></i> Export CSV
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h5 class="text-primary">{{ $totalItems }}</h5>
                                    <small class="text-muted">Total Items with Expiry</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h5 class="text-danger">{{ $expiredItems }}</h5>
                                    <small class="text-muted">Expired Items</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h5 class="text-warning">{{ $expiringSoonItems }}</h5>
                                    <small class="text-muted">Expiring in 30 Days</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" action="{{ route('reports.stock-expiry') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Expiry Status</label>
                                <select name="expiry_status" class="form-select">
                                    <option value="all" {{ request('expiry_status') == 'all' ? 'selected' : '' }}>All</option>
                                    <option value="expired" {{ request('expiry_status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                    <option value="expiring_soon" {{ request('expiry_status') == 'expiring_soon' ? 'selected' : '' }}>Expiring in 30 Days</option>
                                    <option value="expiring_60_days" {{ request('expiry_status') == 'expiring_60_days' ? 'selected' : '' }}>Expiring in 60 Days</option>
                                    <option value="expiring_90_days" {{ request('expiry_status') == 'expiring_90_days' ? 'selected' : '' }}>Expiring in 90 Days</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Product</label>
                                <select name="product_id" class="form-select">
                                    <option value="">All Products</option>
                                    @foreach($products as $id => $name)
                                        <option value="{{ $id }}" {{ request('product_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $id => $name)
                                        <option value="{{ $id }}" {{ request('supplier_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="{{ route('reports.stock-expiry') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Results Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product Name</th>
                                    <th>Supplier</th>
                                    <th>Purchase Date</th>
                                    <th>Expiry Date</th>
                                    <th>Days Until Expiry</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiryItems as $item)
                                    @php
                                        $expiryDate = $item->expiry_date ?: $item->exp_date;
                                        $daysUntilExpiry = $expiryDate ? \Carbon\Carbon::parse($expiryDate)->diffInDays(\Carbon\Carbon::today(), false) : null;
                                        $status = !$expiryDate ? 'no-date' : 
                                                 ($daysUntilExpiry < 0 ? 'expired' : 
                                                 ($daysUntilExpiry <= 30 ? 'expiring-soon' : 'valid'));
                                        $statusText = !$expiryDate ? 'No Expiry Date' : 
                                                     ($daysUntilExpiry < 0 ? 'Expired' : 
                                                     ($daysUntilExpiry <= 30 ? 'Expiring Soon' : 'Valid'));
                                    @endphp
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td>{{ $item->purchase->supplier->name ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item->purchase->purchase_date)->format('M d, Y') }}</td>
                                        <td>{{ $expiryDate ? \Carbon\Carbon::parse($expiryDate)->format('M d, Y') : 'Not Set' }}</td>
                                        <td>
                                            @if($expiryDate)
                                                <span class="badge bg-{{ $daysUntilExpiry < 0 ? 'danger' : ($daysUntilExpiry <= 30 ? 'warning' : 'success') }}">
                                                    {{ $daysUntilExpiry < 0 ? abs($daysUntilExpiry) . ' days ago' : $daysUntilExpiry . ' days' }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">No Date</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->quantity) }}</td>
                                        <td>{{ formatCurrency($item->unit_cost) }}</td>
                                        <td>{{ formatCurrency($item->quantity * $item->unit_cost) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $status == 'expired' ? 'danger' : ($status == 'expiring-soon' ? 'warning' : ($status == 'no-date' ? 'secondary' : 'success')) }}">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No stock expiry items found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $expiryItems->firstItem() }} to {{ $expiryItems->lastItem() }} of {{ $expiryItems->total() }} items
                        </div>
                        {{ $expiryItems->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
