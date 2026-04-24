@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4" style="background-color: #1e3c72; min-height: 100vh; border-radius: 8px; padding: 20px;">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0 text-white">Dashboard</h4>
        <div class="text-white">
            <i class="bi bi-calendar3 me-2"></i>
            {{ now()->format('d-m-Y') }}
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-circle icon-circle-dark">
                        <i class="bi bi-cart3"></i>
                    </div>
                    <div>
                        <p class="text-dark small mb-0 opacity-75">Today's Sales</p>
                        <p class="text-dark fw-bold fs-4 mb-0">MWK {{ number_format($totalSales, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-circle icon-circle-light">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <p class="text-dark small mb-0 opacity-75">Total Products</p>
                        <p class="text-dark fw-bold fs-4 mb-0">{{ App\Models\Product::count() }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-circle icon-circle-white">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <p class="text-dark small mb-0 opacity-75">Low Stock</p>
                        <p class="text-dark fw-bold fs-4 mb-0">{{ App\Models\Product::where('current_stock', '<=', 10)->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon-circle icon-circle-white">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <p class="text-dark small mb-0 opacity-75">Expiry Items</p>
                        <p class="text-dark fw-bold fs-4 mb-0">{{ $expiryItems }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body">
                    <h5 class="card-title">Sales Summary</h5>
                    <div class="row">
                        <div class="col-6">
                            <p class="text-dark small mb-0 opacity-75">Net Sales</p>
                            <p class="text-dark fw-bold fs-4 mb-0">MWK {{ number_format($netSales, 2) }}</p>
                        </div>
                        <div class="col-6">
                            <p class="text-dark small mb-0 opacity-75">Invoice Due</p>
                            <p class="text-dark fw-bold fs-4 mb-0">MWK {{ number_format($invoiceDue, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body">
                    <h5 class="card-title">Purchase Summary</h5>
                    <div class="row">
                        <div class="col-6">
                            <p class="text-dark small mb-0 opacity-75">Total Purchase</p>
                            <p class="text-dark fw-bold fs-4 mb-0">MWK {{ number_format($totalPurchase, 2) }}</p>
                        </div>
                        <div class="col-6">
                            <p class="text-dark small mb-0 opacity-75">Purchase Due</p>
                            <p class="text-dark fw-bold fs-4 mb-0">MWK {{ number_format($purchaseDue, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Section -->
    <div class="row">
        <div class="col-12">
            <div class="card border-dark shadow-sm bg-white">
                <div class="card-body">
                    <h5 class="card-title">Welcome to EDUC Pharmacy Management System</h5>
                    <p class="card-text">Manage your pharmacy operations efficiently with our comprehensive system.</p>
                    <div class="row mt-4">
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-cart-check text-primary fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Point of Sale</h6>
                                    <small class="text-muted">Process sales transactions</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-box-seam text-success fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Inventory Management</h6>
                                    <small class="text-muted">Track stock levels</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-graph-up text-info fs-4 me-3"></i>
                                <div>
                                    <h6 class="mb-0">Reports & Analytics</h6>
                                    <small class="text-muted">Business insights</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.icon-circle-dark {
    background: rgba(0, 0, 0, 0.8);
    color: white;
}

.icon-circle-light {
    background: rgba(255, 255, 255, 0.9);
    color: #1e3c72;
}

.icon-circle-white {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid #1e3c72;
}

.opacity-75 {
    opacity: 0.75;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .icon-circle {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .fs-4 {
        font-size: 1.25rem !important;
    }
}
</style>

@endsection
