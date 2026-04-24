@extends('layouts.app')

@section('title', 'View Purchase - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Purchase #{{ $purchase->reference_no }}</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-warning me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Purchase Details</h5>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Reference No:</div>
                        <div class="col-sm-8">{{ $purchase->reference_no }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Supplier:</div>
                        <div class="col-sm-8">{{ $purchase->supplier?->name ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Date:</div>
                        <div class="col-sm-8">{{ $purchase->purchase_date->format('M d, Y H:i') }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Status:</div>
                        <div class="col-sm-8">
                            @switch($purchase->status)
                                @case('ordered')
                                    <span class="badge bg-info">Ordered</span>
                                    @break
                                @case('received')
                                    <span class="badge bg-success">Received</span>
                                    @break
                                @case('pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                    @break
                            @endswitch
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Payment Status:</div>
                        <div class="col-sm-8">
                            @switch($purchase->payment_status)
                                @case('paid')
                                    <span class="badge bg-success">Paid</span>
                                    @break
                                @case('due')
                                    <span class="badge bg-danger">Due</span>
                                    @break
                                @case('partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                    @break
                            @endswitch
                        </div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-sm-4 fw-semibold">Added By:</div>
                        <div class="col-sm-8">{{ $purchase->user?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Supplier Details</h5>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Email:</div>
                        <div class="col-sm-8">{{ $purchase->supplier?->email ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Phone:</div>
                        <div class="col-sm-8">{{ $purchase->supplier?->phone ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Address:</div>
                        <div class="col-sm-8">{{ $purchase->supplier?->address ?? '-' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">City:</div>
                        <div class="col-sm-8">{{ $purchase->supplier?->city ?? '-' }}</div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-sm-4 fw-semibold">Country:</div>
                        <div class="col-sm-8">{{ $purchase->supplier?->country ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border mb-4">
        <div class="card-body">
            <h5 class="card-title fw-bold mb-3">Purchase Items</h5>
            @if ($purchase->purchaseItems->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Discount %</th>
                                <th class="text-end">Tax %</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->purchaseItems as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product?->name ?? '-' }}</strong><br>
                                        <small class="text-muted">SKU: {{ $item->product?->sku ?? '-' }}</small>
                                    </td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">MWK {{ number_format($item->unit_cost, 2) }}</td>
                                    <td class="text-end">{{ $item->discount_percent ?? 0 }}%</td>
                                    <td class="text-end">{{ $item->tax_percent ?? 0 }}%</td>
                                    <td class="text-end fw-semibold">MWK {{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No items in this purchase.</p>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 offset-md-6">
            <div class="card border">
                <div class="card-body">
                    <div class="d-flex justify-content-between fw-bold mb-2">
                        <span>Grand Total:</span>
                        <span>MWK {{ number_format($purchase->grand_total, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold mb-2">
                        <span>Paid Amount:</span>
                        <span>MWK {{ number_format($purchase->paid_amount, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold text-danger">
                        <span>Amount Due:</span>
                        <span>MWK {{ number_format($purchase->grand_total - $purchase->paid_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
