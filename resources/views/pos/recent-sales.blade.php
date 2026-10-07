@extends('layouts.app')

@section('title', 'Recent Transactions')

@section('content')
<style>
    :root {
        --brand-blue: #1E3A8A; /* Deep blue */
        --brand-blue-light: #eff6ff;
        --brand-green: #10B981; /* Apple green */
        --brand-green-light: #ecfdf5;
        --glass-bg: rgba(255, 255, 255, 0.95);
    }
    
    body {
        background-color: #f8fafc;
    }

    .recent-transactions-container {
        padding: 2rem 1rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: var(--brand-blue);
        padding: 1.5rem 2rem;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(30, 58, 138, 0.15);
        color: white;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title i {
        color: var(--brand-green);
    }

    .btn-back {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1.25rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-back:hover {
        background: white;
        color: var(--brand-blue);
        transform: translateY(-2px);
    }

    .modern-card {
        background: var(--glass-bg);
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        backdrop-filter: blur(10px);
        overflow: hidden;
    }

    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table th {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .modern-table td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        transition: all 0.2s ease;
    }

    .modern-table tbody tr {
        transition: all 0.2s ease;
    }

    .modern-table tbody tr:hover {
        background: #f8fafc;
        transform: scale(1.001);
    }

    .modern-table tbody tr:hover td {
        color: #0f172a;
    }

    /* Badges */
    .badge-modern {
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.025em;
    }

    .badge-invoice {
        background: var(--brand-blue-light);
        color: var(--brand-blue);
        border: 1px solid rgba(30, 58, 138, 0.2);
    }

    .badge-qty {
        background: #f3f4f6;
        color: #4b5563;
        font-size: 0.85rem;
        padding: 0.3rem 0.7rem;
    }

    .badge-payment {
        background: var(--brand-green-light);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    /* Actions */
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        color: #64748b;
        background: transparent;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }

    .action-btn.view:hover {
        background: var(--brand-blue-light);
        color: var(--brand-blue);
    }

    .action-btn.edit:hover {
        background: #fef3c7;
        color: #d97706;
    }

    .action-btn.delete:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    .total-amount {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        color: var(--brand-blue);
        font-size: 1.1rem;
    }

    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }

    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: #64748b;
        font-size: 1.1rem;
    }

    /* Pagination */
    .pagination-container {
        padding: 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: center;
    }
</style>

<div class="recent-transactions-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-clock-history"></i>
            Recent Transactions
        </h1>
        <a href="{{ route('pos.new') }}" class="btn-back">
            <i class="bi bi-arrow-left me-2"></i> Back to POS
        </a>
    </div>

    <div class="modern-card">
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th class="d-none d-md-table-cell">Products</th>
                        <th class="text-center">Qty</th>
                        <th class="d-none d-lg-table-cell">Date & Time</th>
                        <th class="d-none d-md-table-cell">Customer</th>
                        <th class="d-none d-lg-table-cell">Payment</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if($sales->isEmpty())
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-receipt"></i>
                                    <p>No recent transactions found</p>
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($sales as $sale)
                            <tr>
                                <td>
                                    <span class="badge-modern badge-invoice">#{{ $sale->invoice_no }}</span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    @if($sale->items && $sale->items->count() > 0)
                                        <div style="font-weight: 500; color: #334155;">
                                            {{ $sale->items->take(2)->map(function($item) {
                                                return $item->product ? $item->product->name : $item->name;
                                            })->implode(', ') }}
                                            @if($sale->items->count() > 2)
                                                <span style="color: #94a3b8; font-size: 0.85em; font-weight: normal; margin-left: 4px;">
                                                    +{{ $sale->items->count() - 2 }} more
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color: #94a3b8; font-style: italic;">No items</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge-modern badge-qty">
                                        {{ $sale->items ? $sale->items->sum('quantity') : 0 }}
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell" style="color: #64748b; font-size: 0.9rem;">
                                    <i class="bi bi-calendar3 me-1" style="opacity: 0.5;"></i>
                                    {{ $sale->created_at ? $sale->created_at->format('d M Y, H:i') : '' }}
                                </td>
                                <td class="d-none d-md-table-cell" style="font-weight: 500;">
                                    {{ $sale->customer_name ?? 'Walk-In Customer' }}
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    @if($sale->payment_method === 'split')
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge-modern badge-payment mb-1" style="width: fit-content;">Split Payment</span>
                                            @if($sale->payments && $sale->payments->count() > 0)
                                                <small style="font-size: 0.75rem; color: #64748b;">
                                                    @foreach($sale->payments as $payment)
                                                        {{ ucwords(str_replace('_', ' ', $payment->method)) }}
                                                        ({{ number_format($payment->amount, 0) }}){{ !$loop->last ? ', ' : '' }}
                                                    @endforeach
                                                </small>
                                            @endif
                                        </div>
                                    @else
                                        @php
                                            $methodName = str_replace('_', ' ', $sale->payment_method ?? 'Cash');
                                            $methodName = ucwords($methodName);
                                        @endphp
                                        <span class="badge-modern badge-payment">
                                            {{ $methodName }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end total-amount">
                                    MWK {{ number_format($sale->total_amount ?? 0, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('sales.show', $sale->id) }}" class="action-btn view" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('sales.edit', $sale->id) }}" class="action-btn edit" title="Edit Sale">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('sales.destroy', $sale->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Are you sure you want to delete this transaction? This action will reverse inventory.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn delete" title="Delete Sale">
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
            <div class="pagination-container">
                {{ $sales->links() }}
            </div>
        @endif
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
