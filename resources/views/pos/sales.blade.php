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

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 theme-filter-card" id="filtersSection">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label small fw-bold text-white-50">Time Period</label>
                    <select name="period" class="form-select border-0 bg-white-10 text-white custom-select-dark" id="periodSelect">
                        <option value="all" {{ request('period') == 'all' ? 'selected' : '' }}>All Sales</option>
                        <option value="today" {{ request('period', 'today') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ request('period') == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ request('period') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ request('period') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="this_year" {{ request('period') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="last_year" {{ request('period') == 'last_year' ? 'selected' : '' }}>Last Year</option>
                    </select>
                </div>

                <div class="col-12 col-md-5 col-lg-3">
                    <label class="form-label small fw-bold text-white-50">Custom Range</label>
                    <div class="input-group">
                        <input type="date" name="start_date" class="form-control border-0 bg-white-10 text-white" value="{{ request('start_date') }}" title="Start Date">
                        <input type="date" name="end_date" class="form-control border-0 bg-white-10 text-white" value="{{ request('end_date') }}" title="End Date">
                    </div>
                </div>

                <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label small fw-bold text-white-50">Payment Method</label>
                    <select name="payment_method" class="form-select border-0 bg-white-10 text-white custom-select-dark">
                        <option value="">All Methods</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                        <option value="cheque" {{ request('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="advance" {{ request('payment_method') == 'advance' ? 'selected' : '' }}>Advance</option>
                        <option value="airtel_money" {{ request('payment_method') == 'airtel_money' ? 'selected' : '' }}>Airtel Money</option>
                        <option value="mpamba" {{ request('payment_method') == 'mpamba' ? 'selected' : '' }}>TNM Mpamba</option>
                        <option value="national_bank" {{ request('payment_method') == 'national_bank' ? 'selected' : '' }}>National Bank</option>
                        <option value="standard_bank" {{ request('payment_method') == 'standard_bank' ? 'selected' : '' }}>Standard Bank</option>
                        <option value="other" {{ request('payment_method') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small fw-bold text-white-50">Search Records</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white-10 border-0 text-white-50"><i class="bi bi-search small"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-white-10 text-white" placeholder="Invoice / Customer" value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-12 col-md-4 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-accent w-100 shadow-sm">Apply</button>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-light border-0 bg-white-10 w-100 text-center">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Section Header -->
    <div class="d-flex align-items-center mb-3 gap-2">
        <i class="bi bi-graph-up text-primary fs-4"></i>
        <h5 class="fw-bold mb-0">Sales Summary</h5>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-2 mb-4" id="summarySection">
        @php
            $displayNames = \App\Helpers\PaymentMethods::getDisplayNames();
            $totalSalesAmount = collect($summary['payment_methods'] ?? [])->sum('total') ?? 0;
            $totalTransactions = collect($summary['payment_methods'] ?? [])->sum('count') ?? 0;
        @endphp
        
        <!-- Total Sales Card -->
        <div class="col-12 col-md-4 col-lg-2">
            <div class="summary-card-modern bg-primary text-white text-center p-3 rounded-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                <div class="small fw-medium opacity-75 mb-1">Total Sales</div>
                <h4 class="fw-bold mb-1">MWK {{ number_format($totalSalesAmount, 0) }}</h4>
                <div class="small opacity-75">{{ $totalTransactions }} transactions</div>
            </div>
        </div>

        @foreach($displayNames as $method => $displayName)
            @php
                $data = $summary['payment_methods'][$method] ?? ['count' => 0, 'total' => 0];
            @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div class="summary-card-modern bg-white border text-center p-3 rounded-3 shadow-sm h-100 d-flex flex-column justify-content-center">
                    <div class="small fw-medium text-muted mb-1">{{ $displayName }}</div>
                    <div class="fw-bold text-primary mb-1">{{ $data['count'] }}</div>
                    <div class="fw-bold text-success">MWK {{ number_format($data['total'], 0) }}</div>
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
                                    <span class="fw-medium">{{ optional($s->items->first()->product)->name ?? 'Deleted Product' }}</span>
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

.summary-card-modern {
    transition: all 0.2s ease;
    min-height: 100px;
    border: 1px solid #dee2e6 !important;
}

.summary-card-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08) !important;
}

.text-success { color: #28a745 !important; }
.text-primary { color: #0d6efd !important; }

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
    padding-top: 1rem;
    padding-bottom: 1rem;
    background-color: var(--theme-primary) !important;
    color: white !important;
}

.table tbody td {
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.btn-icon:hover {
    background-color: #f0f0f0;
}

/* Pagination Overrides */
.pagination {
    margin-bottom: 0;
}

.page-link {
    border: none;
    padding: 0.5rem 0.8rem;
    margin: 0 2px;
    border-radius: 8px !important;
    color: #6c757d;
}

.page-item.active .page-link {
    background-color: var(--theme-primary);
    color: #fff;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
}

.theme-filter-card {
    background-color: var(--theme-primary) !important;
    background-image: linear-gradient(135deg, var(--theme-primary) 0%, rgba(0,0,0,0.1) 100%);
}

    .bg-white-10 {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }

    .custom-select-dark {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff80' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    }

    .custom-select-dark option {
        background-color: #004d3d;
        color: white;
    }

    .btn-accent {
        background-color: #22c55e;
        color: white;
        border: none;
        font-weight: 600;
    }

    .btn-accent:hover {
        background-color: #16a34a;
        color: white;
    }

    @media print {
        .app-sidebar, .app-topbar, .navbar, #filtersToggle, .btn-primary, .dropdown, .card-footer, .text-center.pe-4, .theme-filter-card {
            display: none !important;
        }
        .card {
            box-shadow: none !important;
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
