@extends('layouts.app')
@section('title', 'Sales History')

@section('content')
<div class="container-fluid py-4 sales-modern">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">Sales History</h4>
            <p class="text-muted small mb-0">Monitor and manage all your transactions in one place.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pos.new') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> New Sale
            </a>
        </div>
    </div>

    <!-- Filters & Summary Toggle -->
    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-white shadow-sm btn-sm border px-3" id="btnFilters">
            <i class="bi bi-funnel me-1"></i> Filters
        </button>
        <button class="btn btn-white shadow-sm btn-sm border px-3 text-primary" id="btnSummary">
            <i class="bi bi-graph-up me-1"></i> Summary
        </button>
    </div>

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm rounded-3 mb-4" id="filtersSection">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label x-small fw-bold text-muted mb-1">Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="start_date" class="form-control border bg-light" value="{{ request('start_date') }}">
                        <span class="input-group-text bg-light border-start-0 border-end-0 small">to</span>
                        <input type="date" name="end_date" class="form-control border bg-light" value="{{ request('end_date') }}">
                    </div>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">Period</label>
                    <select name="period" class="form-select form-select-sm border bg-light">
                        <option value="all" {{ request('period', 'all') == 'all' ? 'selected' : '' }}>All Sales</option>
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('period') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ request('period') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ request('period') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_year" {{ request('period') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="last_year" {{ request('period') == 'last_year' ? 'selected' : '' }}>Last Year</option>
                        <option value="custom" {{ request('start_date') || request('end_date') ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">Payment Method</label>
                    <select name="payment_method" class="form-select form-select-sm border bg-light">
                        <option value="">All Methods</option>
                        @foreach(\App\Helpers\PaymentMethods::getDisplayNames() as $value => $label)
                            <option value="{{ $value }}" {{ request('payment_method') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label x-small fw-bold text-muted mb-1">User</label>
                    <select name="user_id" class="form-select form-select-sm border bg-light">
                        <option value="">All Users</option>
                        @foreach($users ?? [] as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label x-small fw-bold text-muted mb-1">Search</label>
                    <div class="d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm border bg-light" placeholder="Invoice, Customer..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary btn-sm px-3">Filter</button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm px-3">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Summary Header -->
    <div class="mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-graph-up text-primary"></i>
        <h6 class="fw-bold mb-0">Sales Summary</h6>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="row g-2 mb-4" id="summarySection">
        @php
            $paymentMethodsList = \App\Helpers\PaymentMethods::getDisplayNames();
            $totalSalesAmount = 0;
            $totalTransactions = 0;
            
            foreach($summary['payment_methods'] ?? [] as $data) {
                $totalSalesAmount += $data['total'];
                $totalTransactions += $data['count'];
            }
        @endphp
        
        <!-- Total Sales Card (Highlighted) -->
        <div class="col-12 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white h-100 summary-tile-card">
                <div class="card-body p-2 text-center d-flex flex-column justify-content-center">
                    <div class="x-small opacity-75 mb-1">Total Sales</div>
                    <h5 class="fw-bold mb-0">MWK {{ number_format($totalSalesAmount, 0) }}</h5>
                    <div class="x-small opacity-75 mt-1">{{ $totalTransactions }} transactions</div>
                </div>
            </div>
        </div>

        @foreach($paymentMethodsList as $method => $label)
            @php
                $data = $summary['payment_methods'][$method] ?? ['total' => 0, 'count' => 0];
            @endphp
            <div class="col-6 col-md-3 col-lg-2">
                <div class="card border shadow-sm rounded-3 bg-white h-100 summary-tile-card">
                    <div class="card-body p-2 text-center d-flex flex-column justify-content-center">
                        <div class="x-small text-muted mb-1">{{ $label }}</div>
                        <div class="fw-bold text-primary mb-0">{{ $data['count'] }}</div>
                        <h6 class="fw-bold mb-0 text-success">MWK {{ number_format($data['total'], 0) }}</h6>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0">Transaction Records</h5>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportCSV()"><i class="bi bi-filetype-csv me-2 text-primary"></i>CSV</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportExcel()"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportPDF()"><i class="bi bi-file-pdf me-2 text-danger"></i>PDF</a></li>
                    </ul>
                </div>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="salesTable">
                <thead class="bg-light text-muted small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th class="text-center">Items</th>
                        <th>Payment</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-center pe-4">Action</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($sales ?? [] as $s)
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#{{ $s->invoice_no ?? 'INV-'.str_pad($s->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="small text-muted">
                            <i class="bi bi-calendar-event me-1"></i> {{ $s->created_at->format('d M, Y') }}<br>
                            <i class="bi bi-clock me-1"></i> {{ $s->created_at->format('H:i') }}
                        </td>
                        <td class="fw-medium">{{ $s->customer_name ?: 'Walk-in Customer' }}</td>
                        <td>
                            <div class="text-truncate" style="max-width: 200px;" title="{{ $s->items->pluck('product.name')->implode(', ') }}">
                                @if($s->items && $s->items->count() > 0)
                                    <span class="fw-medium">{{ $s->items->first()->product->name }}</span>
                                    @if($s->items->count() > 1)
                                        <span class="text-muted small">+{{ $s->items->count() - 1 }} more</span>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark border">{{ $s->total_items }}</span></td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="badge {{ $s->payment_status == 'paid' ? 'bg-success-soft text-success' : 'bg-warning-soft text-warning' }} rounded-pill mb-1">
                                    {{ ucfirst($s->payment_status) }}
                                </span>
                                <small class="text-muted text-uppercase" style="font-size: 10px;">{{ str_replace('_', ' ', $s->payment_method) }}</small>
                            </div>
                        </td>
                        <td class="text-end fw-bold">MWK {{ number_format($s->total_amount, 2) }}</td>
                        <td class="text-center pe-4">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon border-0 bg-transparent" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                    <li><a class="dropdown-item" href="{{ route('sales.show', $s->id) }}"><i class="bi bi-eye me-2 text-primary"></i> View Details</a></li>
                                    <li><a class="dropdown-item" href="{{ route('sales.edit', $s->id) }}"><i class="bi bi-pencil me-2 text-warning"></i> Edit Sale</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Void Sale</button></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <img src="https://illustrations.popsy.co/gray/not-found.svg" alt="No data" style="height: 150px;" class="mb-3 opacity-50">
                            <h6 class="text-muted">No transactions found matching your criteria.</h6>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div class="card-footer bg-white py-3 border-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <p class="text-muted small mb-0">
                    Showing <strong>{{ $sales->firstItem() }}</strong> to <strong>{{ $sales->lastItem() }}</strong> of <strong>{{ $sales->total() }}</strong> records
                </p>
                <div>
                    {{ $sales->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.sales-modern {
    background-color: #f8f9fa;
}

.x-small {
    font-size: 0.7rem;
}

.summary-tile-card {
    transition: all 0.2s ease;
    min-height: 85px;
}

.summary-tile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
}

.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
.bg-primary-soft { background-color: rgba(0, 77, 61, 0.1); }

.btn-white {
    background-color: #fff;
    color: #333;
}

.btn-white:hover {
    background-color: #f8f9fa;
}

.table thead th {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.table tbody td {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}

/* Pagination Overrides */
.pagination {
    margin-bottom: 0;
}

.page-link {
    border: none;
    padding: 0.4rem 0.7rem;
    margin: 0 2px;
    border-radius: 6px !important;
    color: #6c757d;
}

.page-item.active .page-link {
    background-color: #004d3d;
    color: #fff;
}

@media print {
    .app-sidebar, .app-topbar, .navbar, .btn, form, #btnFilters, #btnSummary {
        display: none !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

@endsection

@section('scripts')
<script>
function exportCSV() {
    const table = document.getElementById('salesTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length - 1; j++) { // Skip action column
            row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
        }
        csv.push(row.join(','));
    }
    
    const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "sales_history_" + new Date().toLocaleDateString() + ".csv");
    document.body.appendChild(link);
    link.click();
}

function exportExcel() {
    // Basic Excel fallback to CSV
    exportCSV();
}

function exportPDF() {
    window.print();
}
</script>
@endsection
