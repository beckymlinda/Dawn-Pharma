@extends('layouts.app')

@section('title', 'Edit Purchase - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Edit Purchase</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Purchases
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border">
        <div class="card-body">
            <form action="{{ route('purchases.update', $purchase) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <!-- Purchase Header -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Supplier: <span class="text-danger">*</span></label>
                        <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id" required>
                            <option value="">Select a supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase->supplier_id) == $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Reference No: <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('reference_no') is-invalid @enderror" 
                               name="reference_no" placeholder="Reference No" value="{{ old('reference_no', $purchase->reference_no) }}" required>
                        @error('reference_no')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Purchase Date: <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('purchase_date') is-invalid @enderror" 
                               name="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d\TH:i')) }}" required>
                        @error('purchase_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status: <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" name="status" required>
                            <option value="pending" @selected(old('status', $purchase->status) == 'pending')>Pending</option>
                            <option value="ordered" @selected(old('status', $purchase->status) == 'ordered')>Ordered</option>
                            <option value="received" @selected(old('status', $purchase->status) == 'received')>Received</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Status: <span class="text-danger">*</span></label>
                        <select class="form-select @error('payment_status') is-invalid @enderror" name="payment_status" required>
                            <option value="due" @selected(old('payment_status', $purchase->payment_status) == 'due')>Due</option>
                            <option value="partial" @selected(old('payment_status', $purchase->payment_status) == 'partial')>Partial</option>
                            <option value="paid" @selected(old('payment_status', $purchase->payment_status) == 'paid')>Paid</option>
                        </select>
                        @error('payment_status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Purchase Items (Read-only) -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3">Purchase Items</h5>
                    <p class="text-muted small mb-3">To modify items, please delete and recreate the purchase.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th style="width: 100px;">Quantity</th>
                                    <th style="width: 120px;">Unit Cost</th>
                                    <th style="width: 100px;">Discount %</th>
                                    <th style="width: 80px;">Tax %</th>
                                    <th style="width: 120px;">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->purchaseItems as $item)
                                    <tr>
                                        <td>{{ $item->product?->name ?? '-' }} ({{ $item->product?->sku ?? '-' }})</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>MWK {{ number_format($item->unit_cost, 2) }}</td>
                                        <td>{{ $item->discount_percent ?? 0 }}%</td>
                                        <td>{{ $item->tax_percent ?? 0 }}%</td>
                                        <td class="fw-semibold">MWK {{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grand Total -->
                <div class="row mb-4">
                    <div class="col-md-6 offset-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Grand Total:</span>
                                    <span>MWK {{ number_format($purchase->grand_total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Update Purchase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
