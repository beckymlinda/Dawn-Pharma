@extends('layouts.app')

@section('content')
<style>
    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .container-fluid {
            padding: 0.5rem !important;
        }
        
        .header-actions {
            flex-direction: column !important;
            gap: 0.5rem !important;
            align-items: stretch !important;
        }
        
        .header-actions .btn {
            width: 100% !important;
        }
        
        .summary-card {
            margin-bottom: 0.75rem !important;
        }
        
        .payment-method-item {
            margin-bottom: 0.5rem !important;
            padding: 0.75rem !important;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem !important;
            vertical-align: middle !important;
        }
        
        .table th {
            font-size: 0.75rem;
        }
        
        .mobile-stack {
            display: flex !important;
            flex-direction: column !important;
        }
        
        .mobile-text-small {
            font-size: 0.75rem !important;
        }
    }
    
    @media (max-width: 576px) {
        .summary-card .card-body {
            padding: 0.75rem !important;
        }
        
        .summary-card h3 {
            font-size: 1.25rem !important;
        }
        
        .summary-card h5 {
            font-size: 0.875rem !important;
        }
        
        .table {
            font-size: 0.8rem !important;
        }
        
        .table th,
        .table td {
            padding: 0.375rem 0.25rem !important;
        }
        
        .badge {
            font-size: 0.7rem !important;
            padding: 0.25rem 0.5rem !important;
        }
    }
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 header-actions">
        <div>
            <h2 class="h4 mb-1">
                <i class="bi bi-cash-stack"></i> Current Register Details
            </h2>
            <p class="text-muted mb-0 mobile-text-small">
                Register opened: {{ $register->opened_at->format('M j, Y, g:i A') }}
            </p>
        </div>
        <div>
            <a href="{{ route('pos.new') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to POS
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-primary text-white summary-card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-2">Total Sales</h5>
                    <h3 class="fw-bold mb-0">MWK {{ number_format($registerData['register']['totals']['total_sales'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-success text-white summary-card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-2">Transactions</h5>
                    <h3 class="fw-bold mb-0">{{ count($registerData['register']['products']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-info text-white summary-card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-2">Items Sold</h5>
                    <h3 class="fw-bold mb-0">{{ $registerData['register']['total_quantity'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-warning text-white summary-card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-2">Total Sales</h5>
                    <h3 class="fw-bold mb-0">MWK {{ number_format($registerData['register']['totals']['cash_in_hand'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card"></i> Payment Methods Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($registerData['register']['payment_methods'] as $method => $amount)
                        <div class="col-12 col-sm-6 col-md-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded payment-method-item">
                                <span class="text-dark fw-semibold">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
                                <span class="text-dark fw-bold">MWK {{ number_format($amount, 2) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-cart-check"></i> Sales Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead style="background-color: #000000 !important;">
                                <tr>
                                    <th style="color: #ffffff !important; font-weight: bold;">SKU</th>
                                    <th style="color: #ffffff !important; font-weight: bold;">Product</th>
                                    <th style="color: #ffffff !important; font-weight: bold;" class="text-center">Qty</th>
                                    <th style="color: #ffffff !important; font-weight: bold;" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($registerData['register']['products']) > 0)
                                    @foreach($registerData['register']['products'] as $product)
                                    <tr>
                                        <td class="mobile-text-small">{{ $product['sku'] }}</td>
                                        <td>
                                            <div class="mobile-stack">
                                                <strong class="d-block d-md-none">{{ $product['name'] }}</strong>
                                                <span class="d-none d-md-block">{{ $product['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $product['quantity'] }}</span>
                                        </td>
                                        <td class="text-end fw-bold">MWK {{ number_format($product['total_amount'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No sales in current register</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
