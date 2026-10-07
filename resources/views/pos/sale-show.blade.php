@extends('layouts.app')
@section('title', 'Sale Details')
@section('content')

<div class="container-fluid py-4 bg-light min-vh-100">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <!-- Header Actions -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('sales.index') }}" class="btn btn-light border shadow-sm rounded-pill">
                        <i class="bi bi-arrow-left"></i> Back to Sales
                    </a>
                    <h3 class="mb-0 fw-bold text-dark">Invoice <span class="text-primary">#{{ $sale->invoice_no }}</span></h3>
                    @if($sale->is_edited)
                        <span class="badge bg-warning text-dark rounded-pill shadow-sm"><i class="bi bi-pencil-fill me-1"></i> Edited</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary rounded-pill shadow-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-primary rounded-pill shadow-sm">
                        <i class="bi bi-pencil"></i> Edit Sale
                    </a>
                    <button class="btn btn-danger rounded-pill shadow-sm" onclick="confirmDelete({{ $sale->id }})">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            </div>

            <!-- Main Invoice Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-bottom">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h5 class="fw-bold mb-1 text-muted text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Customer Information</h5>
                            <p class="mb-0 fs-5 fw-medium text-dark">
                                <i class="bi bi-person-circle text-primary me-2"></i>{{ $sale->customer_name ?: 'Walk-in Customer' }}
                            </p>
                        </div>
                        <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                            <h5 class="fw-bold mb-1 text-muted text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">Transaction Date</h5>
                            <p class="mb-0 fs-5 fw-medium text-dark">
                                <i class="bi bi-calendar-check text-primary me-2"></i>{{ $sale->created_at->format('d M, Y') }} <span class="text-muted fs-6 ms-1">{{ $sale->created_at->format('H:i') }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-light">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded-3 border shadow-sm h-100">
                                <h6 class="text-muted fw-bold mb-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Payment Details</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-secondary">Status</span>
                                    <span class="badge bg-{{ strtolower($sale->payment_status) === 'paid' ? 'success' : 'warning' }} rounded-pill px-3">
                                        {{ ucfirst($sale->payment_status) }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-secondary">Method</span>
                                    <span class="fw-medium text-dark">
                                        @if($sale->payment_method === 'split')
                                            @if($sale->payments && $sale->payments->count() > 0)
                                                @foreach($sale->payments as $payment)
                                                    <span class="d-block text-end">{{ ucfirst($payment->method) }}: MWK {{ number_format($payment->amount, 2) }}</span>
                                                @endforeach
                                            @else
                                                Split Payment
                                            @endif
                                        @else
                                            {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-primary text-white p-4 rounded-3 shadow-sm h-100 d-flex flex-column justify-content-center align-items-center position-relative overflow-hidden">

                                <h6 class="fw-medium text-white-50 mb-1 text-uppercase" style="letter-spacing: 1px; font-size: 0.85rem;">Total Amount</h6>
                                <h2 class="fw-bold mb-0 display-6">MWK {{ number_format($sale->total_amount, 2) }}</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-white text-muted small text-uppercase fw-bold border-bottom">
                            <tr>
                                <th class="ps-4 py-3">Product</th>
                                <th class="text-center py-3">Qty</th>
                                <th class="text-end py-3">Unit Price</th>
                                <th class="text-end pe-4 py-3">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($sale->items as $item)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="bi bi-box-seam text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-medium text-dark">{{ $item->product?->name ?? 'Deleted Product' }}</h6>
                                                @if(isset($item->product->sku))
                                                    <small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <span class="badge bg-light text-dark border px-3 rounded-pill">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-end text-muted py-3">MWK {{ number_format($item->price, 2) }}</td>
                                    <td class="text-end pe-4 py-3 fw-bold text-dark">MWK {{ number_format($item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-white border-top">
                            <tr>
                                <td colspan="3" class="text-end py-3 text-muted fw-bold">Total</td>
                                <td class="text-end pe-4 py-3 fw-bold text-primary fs-5">MWK {{ number_format($sale->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Edit History Section -->
            @if($sale->is_edited && $sale->edit_log)
                @php
                    $editLog = is_string($sale->edit_log) ? json_decode($sale->edit_log, true) : $sale->edit_log;
                @endphp
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden border-start border-warning border-4">
                    <div class="card-header bg-white p-4 border-bottom d-flex align-items-center">
                        <div class="bg-warning-soft text-warning rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Modification History</h5>
                            <small class="text-muted">Edited on {{ $sale->edited_at->format('d M, Y H:i') }}</small>
                        </div>
                    </div>
                    
                    <div class="card-body p-4 bg-light">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="bg-white p-3 rounded-3 border shadow-sm">
                                    <h6 class="text-muted fw-bold border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-minus text-secondary me-2"></i>Original Record</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary small">Total Amount:</span>
                                        <span class="fw-medium">MWK {{ number_format($editLog['original_total'], 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary small">Payment Method:</span>
                                        <span class="fw-medium text-end">
                                            @if($editLog['original_payment_method'] === 'split' && isset($editLog['original_payments']) && count($editLog['original_payments']) > 0)
                                                @foreach($editLog['original_payments'] as $payment)
                                                    <span class="d-block">{{ ucfirst($payment['method']) }}: MWK {{ number_format($payment['amount'], 2) }}</span>
                                                @endforeach
                                            @else
                                                {{ ucfirst($editLog['original_payment_method']) }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary small">Items Count:</span>
                                        <span class="fw-medium">{{ count($editLog['original_items']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-white p-3 rounded-3 border shadow-sm">
                                    <h6 class="text-primary fw-bold border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-plus me-2"></i>Updated Record</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary small">New Total:</span>
                                        <span class="fw-bold text-primary">MWK {{ number_format($editLog['new_total'], 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary small">Payment Method:</span>
                                        <span class="fw-medium text-end">
                                            @if($editLog['new_payment_method'] === 'split' && isset($editLog['new_payments']) && count($editLog['new_payments']) > 0)
                                                @foreach($editLog['new_payments'] as $payment)
                                                    <span class="d-block">{{ ucfirst($payment['method']) }}: MWK {{ number_format($payment['amount'], 2) }}</span>
                                                @endforeach
                                            @else
                                                {{ ucfirst($editLog['new_payment_method']) }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary small">Items Count:</span>
                                        <span class="fw-medium">{{ count($editLog['new_items']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($editLog['total_change'] != 0)
                            <div class="alert alert-{{ $editLog['total_change'] > 0 ? 'success' : 'danger' }} d-flex align-items-center mt-4 mb-0 shadow-sm border-0" role="alert">
                                <i class="bi bi-{{ $editLog['total_change'] > 0 ? 'arrow-up-circle-fill' : 'arrow-down-circle-fill' }} fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-0 fw-bold">Financial Adjustment</h6>
                                    <span class="small">The total amount was {{ $editLog['total_change'] > 0 ? 'increased' : 'decreased' }} by <strong>MWK {{ number_format(abs($editLog['total_change']), 2) }}</strong>.</span>
                                </div>
                            </div>
                        @endif

                        @if(!empty($editLog['history']))
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3 text-dark">Edit History</h6>
                                <div class="bg-white rounded-3 border shadow-sm">
                                    @foreach(array_reverse($editLog['history']) as $h)
                                        <div class="px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }} small">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $h['by'] ?? 'Unknown' }}</strong>
                                                <span class="text-muted">{{ \Carbon\Carbon::parse($h['at'])->format('d M Y H:i') }} · {{ $h['via'] ?? '' }}</span>
                                            </div>
                                            <div>MWK {{ number_format($h['from_total'], 2) }} → MWK {{ number_format($h['to_total'], 2) }}</div>
                                            <div class="text-muted"><i class="bi bi-chat-left-text me-1"></i>{{ $h['reason'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(isset($editLog['original_items']) && isset($editLog['new_items']))
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3 text-dark">Detailed Item Changes</h6>
                                <div class="table-responsive bg-white rounded-3 border shadow-sm">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="bg-light text-muted" style="font-size: 0.8rem;">
                                            <tr>
                                                <th class="ps-3 py-2">Product</th>
                                                <th class="text-center py-2">Orig. Qty</th>
                                                <th class="text-center py-2">New Qty</th>
                                                <th class="text-end py-2">Orig. Price</th>
                                                <th class="text-end pe-3 py-2">New Price</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-top-0" style="font-size: 0.9rem;">
                                            @php
                                                $originalItems = collect($editLog['original_items'])->keyBy('product_id');
                                                $newItems = collect($editLog['new_items'])->keyBy('product_id');
                                                $allProducts = $originalItems->keys()->merge($newItems->keys())->unique();
                                            @endphp
                                            
                                            @foreach($allProducts as $productId)
                                                @php
                                                    $original = $originalItems->get($productId);
                                                    $new = $newItems->get($productId);
                                                    $productName = $original['product_name'] ?? $new['product_name'] ?? 'Unknown';
                                                    
                                                    // Handle different possible key names in the log structure
                                                    $originalQty = $original['quantity'] ?? $original['original_quantity'] ?? 0;
                                                    $newQty = $new['quantity'] ?? $new['new_quantity'] ?? 0;
                                                    $originalPrice = $original['price'] ?? $original['original_price'] ?? 0;
                                                    $newPrice = $new['price'] ?? $new['new_price'] ?? 0;
                                                    
                                                    $hasChange = $originalQty != $newQty || $originalPrice != $newPrice;
                                                @endphp
                                                
                                                <tr class="{{ $hasChange ? 'table-warning border-warning border-start border-4' : '' }}">
                                                    <td class="ps-3 py-2 fw-medium">{{ $productName }}</td>
                                                    <td class="text-center py-2 {{ $originalQty != $newQty ? 'text-decoration-line-through text-muted' : '' }}">{{ $originalQty }}</td>
                                                    <td class="text-center py-2 fw-bold {{ $originalQty != $newQty ? 'text-danger' : '' }}">{{ $newQty }}</td>
                                                    <td class="text-end py-2 {{ $originalPrice != $newPrice ? 'text-decoration-line-through text-muted' : '' }}">MWK {{ number_format($originalPrice, 2) }}</td>
                                                    <td class="text-end pe-3 py-2 fw-bold {{ $originalPrice != $newPrice ? 'text-danger' : '' }}">MWK {{ number_format($newPrice, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            
            <div class="text-center mt-4">
                <p class="text-muted small">Generated by <span class="fw-medium text-dark">EDUC PHARMACY POS</span></p>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-warning-soft {
        background-color: rgba(255, 193, 7, 0.15);
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        .container-fluid, .container-fluid * {
            visibility: visible;
        }
        .container-fluid {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background-color: white !important;
        }
        .btn, .badge {
            display: none !important;
        }
        .shadow-sm {
            box-shadow: none !important;
        }
        .border {
            border-color: #ddd !important;
        }
        .bg-light {
            background-color: #fff !important;
        }
        .bg-primary {
            background-color: #0d6efd !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>

<script>
function confirmDelete(saleId) {
    if (confirm('Are you sure you want to delete this sale? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/sales/${saleId}`;
        form.style.display = 'none';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken.getAttribute('content');
            form.appendChild(csrfInput);
        }
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

@endsection
