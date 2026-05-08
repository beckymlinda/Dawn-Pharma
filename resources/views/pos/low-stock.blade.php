@extends('layouts.app')
@section('title', 'Low Stock Report')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Low Stock Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Low Stock</li>
            </ol>
        </nav>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-2 small text-muted">
                    <span>Show</span>
                    <form method="GET" class="d-inline" id="perPageForm">
                        <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="document.getElementById('perPageForm').submit()">
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </form>
                    <span>entries</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Alert Quantity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td class="fw-semibold text-primary">{{ $product->sku }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-danger">
                                        {{ number_format($product->current_stock, 2) }} {{ $product->unit->name ?? '' }}
                                    </span>
                                </td>
                                <td>{{ number_format($product->alert_quantity, 2) }}</td>
                                <td>
                                    @if($product->current_stock <= 0)
                                        <span class="badge bg-dark">Out of Stock</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('products.purchase.create') }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-plus-circle me-1"></i> Restock
                                    </a>
                                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle fs-1 d-block mb-3"></i>
                                    All stock levels are healthy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="d-flex align-items-center justify-content-between mt-4">
                    <div class="small text-muted">
                        Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} items
                    </div>
                    {{ $products->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
