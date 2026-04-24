@extends('layouts.app')

@section('title', 'Recent Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Recent Transactions
                        </h5>
                        <div>
                            <a href="{{ route('pos.new') }}" class="btn btn-light btn-sm">
                                <i class="bi bi-arrow-left me-1"></i> Back to POS
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice #</th>
                                    <th class="d-none d-md-table-cell">Products</th>
                                    <th class="text-center">Qty</th>
                                    <th class="d-none d-lg-table-cell">Date & Time</th>
                                    <th class="d-none d-md-table-cell">Customer</th>
                                    <th class="d-none d-lg-table-cell">Payment</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($sales->isEmpty())
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            No recent transactions found
                                        </td>
                                    </tr>
                                @else
                                    @foreach($sales as $sale)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">{{ $sale->invoice_no }}</span>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                @if($sale->items && $sale->items->count() > 0)
                                                    <div class="small">
                                                        {{ $sale->items->take(2)->map(function($item) {
                                                            return $item->product ? $item->product->name : $item->name;
                                                        })->implode(', ') }}
                                                        @if($sale->items->count() > 2)
                                                            <span class="text-muted">+{{ $sale->items->count() - 2 }} more</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">No items</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    {{ $sale->items ? $sale->items->sum('quantity') : 0 }}
                                                </span>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <small>
                                                    {{ $sale->created_at ? $sale->created_at->format('d/m/Y H:i') : '' }}
                                                </small>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <small>{{ $sale->customer_name ?? 'Walk-In Customer' }}</small>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <span class="badge bg-success">{{ $sale->payment_method ?? 'Cash' }}</span>
                                            </td>
                                            <td class="text-end fw-bold">
                                                MWK {{ number_format($sale->total_amount ?? 0, 2) }}
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-outline-primary" title="View">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-outline-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if($sales->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $sales->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-refresh every 30 seconds
setInterval(function() {
    window.location.reload();
}, 30000);
</script>
@endsection
