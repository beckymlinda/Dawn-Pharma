@extends('layouts.app')

@section('content')
<style>
    /* Custom Design System */
    :root {
        --reg-primary: #004d3d;
        --reg-secondary: #006b54;
        --reg-accent: #f59e0b;
        --reg-bg: #f8fafc;
        --reg-card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    }

    .reg-container {
        max-width: 1200px;
        margin: 0 auto;
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .summary-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s ease;
        overflow: hidden;
        position: relative;
    }

    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .summary-card .card-body {
        padding: 1.5rem;
        z-index: 1;
        position: relative;
    }

    .card-icon {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 5rem;
        opacity: 0.1;
        transform: rotate(-15deg);
    }

    .bg-gradient-reg {
        background: linear-gradient(135deg, var(--reg-primary) 0%, var(--reg-secondary) 100%);
        color: white;
    }

    .bg-gradient-gold {
        background: linear-gradient(135deg, #b45309 0%, #d97706 100%);
        color: white;
    }

    .bg-gradient-teal {
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
        color: white;
    }

    .bg-gradient-blue {
        background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
        color: white;
    }

    .payment-method-badge {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.2s ease;
        height: 100%;
        box-shadow: var(--reg-card-shadow);
    }

    .payment-method-badge:hover {
        border-color: var(--reg-primary);
        background-color: #f0fdf4;
    }

    .table-card {
        border: none;
        border-radius: 16px;
        box-shadow: var(--reg-card-shadow);
        overflow: hidden;
    }

    .table-card .card-header {
        background-color: white;
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
    }

    .custom-table thead th {
        background-color: #f8fafc;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: #64748b;
        border-top: none;
        padding: 1rem 1.5rem;
    }

    .custom-table tbody td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        color: #334155;
    }

    .sku-pill {
        font-family: monospace;
        background: #f1f5f9;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    /* Mobile Enhancements */
    @media (max-width: 768px) {
        .reg-header {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }

        .summary-card .card-body {
            padding: 1rem;
        }

        .summary-card h3 {
            font-size: 1.25rem !important;
        }

        .custom-table thead {
            display: none;
        }

        .custom-table tbody td {
            display: block;
            width: 100%;
            text-align: right;
            padding: 0.5rem 1rem;
            border: none;
        }

        .custom-table tbody td::before {
            content: attr(data-label);
            float: left;
            font-weight: bold;
            color: #64748b;
        }

        .custom-table tbody tr {
            display: block;
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 0;
        }

        .payment-method-badge {
            padding: 0.75rem;
        }
    }
</style>

<div class="container-fluid py-4 reg-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5 reg-header">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <span class="text-primary"><i class="bi bi-wallet2 me-2"></i></span>Register Overview
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('pos.new') }}" class="text-decoration-none">POS</a></li>
                    <li class="breadcrumb-item active">Current Register</li>
                </ol>
            </nav>
            <p class="text-muted mt-2 mb-0">
                <i class="bi bi-clock me-1"></i> Opened at: <span class="fw-semibold text-dark">{{ $register->opened_at->format('M j, Y, g:i A') }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('pos.new') }}" class="btn btn-dark btn-lg px-4 rounded-pill shadow-sm transition-all">
                <i class="bi bi-arrow-left me-2"></i> Return to Terminal
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-6 col-lg-3">
            <div class="card summary-card bg-gradient-reg shadow-lg">
                <div class="card-body">
                    <i class="bi bi-currency-dollar card-icon"></i>
                    <h6 class="text-uppercase fw-bold opacity-75 mb-3 small">Total Sales</h6>
                    <h3 class="fw-bold mb-0">MWK {{ number_format($registerData['register']['totals']['total_sales'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card bg-gradient-teal shadow-lg">
                <div class="card-body">
                    <i class="bi bi-receipt card-icon"></i>
                    <h6 class="text-uppercase fw-bold opacity-75 mb-3 small">Transactions</h6>
                    <h3 class="fw-bold mb-0">{{ count($registerData['register']['products']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card bg-gradient-blue shadow-lg">
                <div class="card-body">
                    <i class="bi bi-box-seam card-icon"></i>
                    <h6 class="text-uppercase fw-bold opacity-75 mb-3 small">Items Sold</h6>
                    <h3 class="fw-bold mb-0">{{ $registerData['register']['total_quantity'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card summary-card bg-gradient-gold shadow-lg">
                <div class="card-body">
                    <i class="bi bi-cash-stack card-icon"></i>
                    <h6 class="text-uppercase fw-bold opacity-75 mb-3 small">Cash In Hand</h6>
                    <h3 class="fw-bold mb-0">MWK {{ number_format($registerData['register']['totals']['cash_in_hand'], 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments & Details Section -->
    <div class="row g-4">
        <!-- Left: Payment Methods -->
        <div class="col-12 col-xl-4">
            <div class="card table-card h-100">
                <div class="card-header d-flex align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-credit-card-2-back me-2 text-primary"></i>Payments
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3">
                        @foreach($registerData['register']['payment_methods'] as $method => $amount)
                        <div class="payment-method-badge d-flex justify-content-between align-items-center">
                            <div>
                                <span class="d-block text-muted small text-uppercase fw-bold">{{ str_replace('_', ' ', $method) }}</span>
                                <span class="h5 fw-bold mb-0 text-dark">MWK {{ number_format($amount, 2) }}</span>
                            </div>
                            <div class="bg-light p-2 rounded-circle">
                                <i class="bi bi-{{ $method == 'cash' ? 'cash' : ($method == 'card' ? 'credit-card' : 'wallet2') }} text-primary"></i>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Sales Details -->
        <div class="col-12 col-xl-8">
            <div class="card table-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-list-stars me-2 text-primary"></i>Sold Products
                    </h5>
                    <span class="badge bg-light text-dark border">{{ count($registerData['register']['products']) }} Unique Items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Product Details</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registerData['register']['products'] as $product)
                                <tr>
                                    <td data-label="Product">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark">{{ $product['name'] }}</span>
                                            <span class="sku-pill mt-1 w-fit-content">{{ $product['sku'] }}</span>
                                        </div>
                                    </td>
                                    <td data-label="Quantity" class="text-center">
                                        <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                                            {{ $product['quantity'] }} Units
                                        </span>
                                    </td>
                                    <td data-label="Total" class="text-end fw-bold text-dark">
                                        MWK {{ number_format($product['total_amount'], 2) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No sales records for this register session.
                                    </td>
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

<style>
    /* Additional Utility for the pill width on desktop */
    .w-fit-content {
        width: fit-content;
    }
    
    .bg-primary-subtle {
        background-color: rgba(0, 77, 61, 0.1) !important;
        color: #004d3d !important;
    }

    .transition-all {
        transition: all 0.3s ease;
    }
</style>
@endsection
