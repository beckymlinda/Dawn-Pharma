@extends('layouts.app')

{{-- Helpers used below (declared first: they are conditional, so not hoisted) --}}
@php
if (!function_exists('getTypeColor')) {
    function getTypeColor($type) {
        $colors = [
            'Purchase' => 'success',
            'Sale' => 'danger',
            'Opening Stock' => 'primary',
            'Stock Adjustment' => 'warning',
            'Stock Transfer' => 'info'
        ];
        return $colors[$type] ?? 'secondary';
    }
}
@endphp
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Product Header -->
            <div class="card border mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
                            <p class="text-muted mb-0">SKU: {{ $product->sku }}</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border">
                        <div class="card-body text-center">
                            <p class="text-muted small mb-1">Quantities In</p>
                            <p class="h5 fw-bold text-success">{{ number_format($quantitiesIn, 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border">
                        <div class="card-body text-center">
                            <p class="text-muted small mb-1">Quantities Out</p>
                            <p class="h5 fw-bold text-danger">{{ number_format($quantitiesOut, 2) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border">
                        <div class="card-body text-center">
                            <p class="text-muted small mb-1">Current Stock</p>
                            <p class="h5 fw-bold text-primary">{{ number_format($currentStock, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border mb-3">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm">
                                <option>All Types</option>
                                <option>Purchase</option>
                                <option>Sale</option>
                                <option>Opening Stock</option>
                                <option>Stock Adjustment</option>
                                <option>Stock Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" placeholder="From Date">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control form-control-sm" placeholder="To Date">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary btn-sm w-100">Filter</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock History Table -->
            <div class="card border">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm small mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Quantity change</th>
                                    <th>New Quantity</th>
                                    <th>Date</th>
                                    <th>Reference No</th>
                                    <th>Customer/Supplier</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($histories as $history)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ getTypeColor($history->type) }}">
                                                {{ $history->type }}
                                            </span>
                                        </td>
                                        <td class="text-{{ $history->quantity_change > 0 ? 'success' : 'danger' }}">
                                            {{ $history->quantity_change > 0 ? '+' : '' }}{{ number_format($history->quantity_change, 2) }}
                                        </td>
                                        <td>{{ number_format($history->new_quantity, 2) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($history->date)->format('M d, Y') }}</td>
                                        <td>{{ $history->reference_no ?? '-' }}</td>
                                        <td>{{ $history->customer_supplier ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No stock history found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
