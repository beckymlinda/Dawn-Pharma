@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="container-fluid py-4 dashboard-modern">
    <!-- Hero Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-banner p-3 p-md-4 rounded-4 shadow-sm text-white mb-2 mb-md-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1 fs-4">Hello, {{ Auth::user()->name }}!</h2>
                    <p class="mb-0 opacity-75 small">Here is how {{ setting('system_name') }} is doing today.</p>
                </div>
                <div class="d-flex gap-2">
                    <div class="date-chip px-3 py-2 rounded-pill bg-white bg-opacity-25 border border-white border-opacity-25 d-none d-sm-block">
                        <i class="bi bi-calendar3 me-2"></i>
                        {{ now()->format('d M, Y') }}
                    </div>
                    <a href="{{ route('pos.new') }}" class="btn btn-light rounded-pill px-4 shadow-sm">
                        <i class="bi bi-cart-plus me-2"></i> New Sale
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Stats Row (2 x 2 on phones) -->
    <div class="row g-3 g-md-4 mb-4">

        <!-- Today's Sales -->
        <div class="col-6 col-lg-3">
            <a href="{{ route('sales.index', ['period' => 'today']) }}" class="text-decoration-none h-100 d-block">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white shadow-sm border-0 h-100 clickable-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box bg-success-soft rounded-3 p-3">
                            <i class="bi bi-cash-coin fs-4 text-success"></i>
                        </div>
                    </div>
                    <p class="text-muted mb-1 small fw-medium">{{ $seesAllSales ? "Today's Sales" : 'Your Sales Today' }}</p>
                    <h3 class="fw-bold mb-0 fs-4">{{ formatCurrency($todaySalesTotal) }}</h3>
                    <div class="small text-muted">{{ $todaySalesCount }} {{ $todaySalesCount == 1 ? 'sale' : 'sales' }}</div>
                </div>
            </a>
        </div>

        <!-- Total Products -->
        <div class="col-6 col-lg-3">
            <div class="stat-card p-3 p-md-4 rounded-4 bg-white shadow-sm border-0 h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="icon-box bg-info-soft rounded-3 p-3">
                        <i class="bi bi-box-seam fs-4 text-info"></i>
                    </div>
                </div>
                <p class="text-muted mb-1 small fw-medium">Total Products</p>
                <h3 class="fw-bold mb-0">{{ App\Models\Product::count() }}</h3>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="col-6 col-lg-3">
            <a href="{{ route('reports.low-stock') }}" class="text-decoration-none h-100 d-block">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white shadow-sm border-0 h-100 clickable-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box {{ $lowStockItems > 0 ? 'bg-danger-soft' : 'bg-success-soft' }} rounded-3 p-3">
                            <i class="bi bi-exclamation-triangle fs-4 {{ $lowStockItems > 0 ? 'text-danger' : 'text-success' }}"></i>
                        </div>
                        <span class="btn-view text-primary small fw-bold d-none d-md-inline">View List <i class="bi bi-arrow-right"></i></span>
                    </div>
                    <p class="text-muted mb-1 small fw-medium">Low Stock Items</p>
                    <h3 class="fw-bold mb-0 {{ $lowStockItems > 0 ? 'text-danger' : '' }}">{{ $lowStockItems }}</h3>
                </div>
            </a>
        </div>

        <!-- Expiry Items -->
        <div class="col-6 col-lg-3">
            <a href="{{ route('reports.stock-expiry') }}" class="text-decoration-none h-100 d-block">
                <div class="stat-card p-3 p-md-4 rounded-4 bg-white shadow-sm border-0 h-100 clickable-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-box {{ $expiryItems > 0 ? 'bg-warning-soft' : 'bg-success-soft' }} rounded-3 p-3">
                            <i class="bi bi-clock-history fs-4 {{ $expiryItems > 0 ? 'text-warning' : 'text-success' }}"></i>
                        </div>
                        <span class="btn-view text-primary small fw-bold d-none d-md-inline">View List <i class="bi bi-arrow-right"></i></span>
                    </div>
                    <p class="text-muted mb-1 small fw-medium">Expiring Soon</p>
                    <h3 class="fw-bold mb-0 {{ $expiryItems > 0 ? 'text-warning' : '' }}">{{ $expiryItems }}</h3>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Sales Analytics Graph -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Sales Analytics</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Last 30 Days
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div style="height: 300px;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Summary & Activity -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Revenue Overview</h5>
                    <div class="d-flex flex-column gap-3">

                        <div class="summary-item p-3 rounded-3 bg-light border-start border-4 border-info">
                            <p class="text-muted small mb-1 fw-medium">Invoice Due</p>
                            <h4 class="fw-bold mb-0 text-dark">MWK {{ number_format($invoiceDue, 0) }}</h4>
                        </div>
                        <div class="summary-item p-3 rounded-3 bg-light border-start border-4 border-warning">
                            <p class="text-muted small mb-1 fw-medium">Total Purchase</p>
                            <h4 class="fw-bold mb-0 text-dark">MWK {{ number_format($totalPurchase, 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden pos-cta">
                <div class="card-body p-4 position-relative z-1">
                    <h5 class="fw-bold mb-2">Ready to sell?</h5>
                    <p class="small mb-4 opacity-75">Open the POS terminal to process new transactions quickly.</p>
                    <a href="{{ route('pos.new') }}" class="btn btn-light w-100 rounded-3 fw-bold">Open POS Terminal</a>
                </div>
                <i class="bi bi-qr-code position-absolute bottom-0 end-0 opacity-10" style="font-size: 8rem; transform: translate(20%, 20%);"></i>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Dashboard Styles */
.dashboard-modern {
    background-color: #f8f9fa;
}

.welcome-banner {
    background: var(--theme-secondary);
    position: relative;
    overflow: hidden;
}

.welcome-banner::after {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.stat-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05) !important;
}

.clickable-card {
    cursor: pointer;
}

.clickable-card .btn-view {
    opacity: 0;
    transition: all 0.3s ease;
    transform: translateX(10px);
}

.clickable-card:hover .btn-view {
    opacity: 1;
    transform: translateX(0);
}

/* Soft Backgrounds for Icons/Badges */
.bg-primary-soft { background-color: rgba(0, 77, 61, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }

.text-primary { color: var(--theme-primary) !important; }

.icon-box {
    width: 54px;
    height: 54px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.summary-item {
    transition: transform 0.2s ease;
}

.summary-item:hover {
    transform: scale(1.02);
}

.pos-cta {
    background: linear-gradient(135deg, var(--theme-secondary) 0%, var(--theme-primary) 100%);
}

@media (max-width: 768px) {
    .welcome-banner {
        text-align: center;
    }
    .welcome-banner .d-flex {
        justify-content: center !important;
        width: 100%;
    }
}
</style>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Gradient for the line area
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(30, 60, 114, 0.3)');
    gradient.addColorStop(1, 'rgba(30, 60, 114, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [{
                label: 'Sales (MWK)',
                data: @json($data),
                borderColor: '#1e3c72',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#1e3c72',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e3c72',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'MWK ' + new Intl.NumberFormat().format(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0,0,0,0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000) return value/1000 + 'k';
                            return value;
                        },
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
