@extends('layouts.app')
@section('content')

<div class="app-wrapper">
  <aside class="app-sidebar" id="app-sidebar"></aside>
  <div class="app-main">
    <header class="app-topbar" id="app-topbar"></header>
    <main class="app-content">
      
      <!-- Header & Filters -->
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
          <h1 class="h4 fw-bold mb-1">Profit & Loss Report</h1>
          <p class="text-muted small mb-0">
            <i class="bi bi-calendar3"></i> {{ $fromDateLabel }} - {{ $toDateLabel }}
          </p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <form method="GET" class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm shadow-sm" name="date_range" id="dateRangeSelect" style="width:auto">
              <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
              <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
              <option value="this_week" {{ $dateRange === 'this_week' ? 'selected' : '' }}>This Week</option>
              <option value="last_7_days" {{ $dateRange === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
              <option value="last_30_days" {{ $dateRange === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
              <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
              <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
              <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
              <option value="custom" {{ $dateRange === 'custom' ? 'selected' : '' }}>Custom Range</option>
            </select>
          </form>

          <form method="GET" id="customDateForm" class="d-flex align-items-center gap-2" style="{{ $dateRange === 'custom' ? '' : 'display: none !important;' }}">
            <input type="hidden" name="date_range" value="custom">
            <input type="date" class="form-control form-control-sm shadow-sm" name="from_date" value="{{ $fromDateDisplay }}" style="width: auto;">
            <span class="text-muted small">to</span>
            <input type="date" class="form-control form-control-sm shadow-sm" name="to_date" value="{{ $toDateDisplay }}" style="width: auto;">
            <button type="submit" class="btn btn-primary btn-sm fw-semibold shadow-sm">Apply</button>
          </form>

          <button onclick="window.print()" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="bi bi-printer"></i> Print
          </button>
        </div>
      </div>

      <!-- KPI Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card border-0 shadow-sm overflow-hidden h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="bg-primary-subtle p-2 rounded-3 me-3 text-primary">
                  <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <div class="small text-muted fw-semibold">Total Revenue</div>
              </div>
              <h3 class="fw-bold mb-0">{{ $currency }} {{ number_format($totalSales, 2) }}</h3>
              <div class="small text-muted mt-2">{{ $totalTransactions }} transactions</div>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <div class="card border-0 shadow-sm overflow-hidden h-100">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="bg-danger-subtle p-2 rounded-3 me-3 text-danger">
                  <i class="bi bi-cart-dash fs-4"></i>
                </div>
                <div class="small text-muted fw-semibold">Cost of Goods</div>
              </div>
              <h3 class="fw-bold mb-0 text-danger">{{ $currency }} {{ number_format($costOfGoodsSold, 2) }}</h3>
              <div class="small text-muted mt-2">{{ $totalItemsSold }} units sold</div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card border-0 shadow-sm overflow-hidden h-100 {{ $grossProfit >= 0 ? 'border-start border-success border-4' : 'border-start border-danger border-4' }}">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="{{ $grossProfit >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} p-2 rounded-3 me-3">
                  <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
                <div class="small text-muted fw-semibold">Gross Profit</div>
              </div>
              <h3 class="fw-bold mb-0 {{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">
                {{ $currency }} {{ number_format($grossProfit, 2) }}
              </h3>
              <div class="badge {{ $grossProfit >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} mt-2">
                {{ $grossMargin }}% Margin
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card border-0 shadow-sm overflow-hidden h-100 {{ $netProfit >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }}">
            <div class="card-body">
              <div class="d-flex align-items-center mb-2">
                <div class="bg-white bg-opacity-25 p-2 rounded-3 me-3 text-white">
                  <i class="bi bi-wallet2 fs-4"></i>
                </div>
                <div class="small fw-semibold opacity-75 text-white">Net Profit</div>
              </div>
              <h3 class="fw-bold mb-0">{{ $currency }} {{ number_format($netProfit, 2) }}</h3>
              <div class="small opacity-75 mt-2">After all expenses</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <!-- Stock Valuation -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
              <h6 class="fw-bold mb-0">Stock Valuation</h6>
            </div>
            <div class="card-body py-0">
              <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0">
                  <tbody>
                    <tr>
                      <td class="ps-0"><span class="text-muted">Total Stock Units</span></td>
                      <td class="text-end fw-bold">{{ number_format($totalStockUnits) }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0"><span class="text-muted">Closing Stock (Cost Price)</span></td>
                      <td class="text-end fw-bold">{{ $currency }} {{ number_format($closingStockCost, 2) }}</td>
                    </tr>
                    <tr>
                      <td class="ps-0"><span class="text-muted">Closing Stock (Retail Price)</span></td>
                      <td class="text-end fw-bold">{{ $currency }} {{ number_format($closingStockRetail, 2) }}</td>
                    </tr>
                    <tr class="border-top">
                      <td class="ps-0"><span class="text-muted">Opening Stock (Cost)</span></td>
                      <td class="text-end">{{ $currency }} {{ number_format($openingStockCost, 2) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-0 py-3">
              <h6 class="fw-bold mb-0">Payment Method Breakdown</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th class="small fw-bold">Method</th>
                      <th class="text-center small fw-bold">Count</th>
                      <th class="text-end small fw-bold">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($paymentBreakdown as $payment)
                      <tr>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $payment['method'])) }}</span></td>
                        <td class="text-center">{{ $payment['count'] }}</td>
                        <td class="text-end fw-semibold">{{ $currency }} {{ number_format($payment['total'], 2) }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="3" class="text-center text-muted py-3">No payments recorded</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Top Profitable Products -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
              <h6 class="fw-bold mb-0 text-success"><i class="bi bi-star-fill me-2"></i>Top Profitable Products</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="small fw-bold">Product</th>
                      <th class="text-center small fw-bold">Qty</th>
                      <th class="text-end small fw-bold">Profit</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($topProducts as $p)
                      <tr>
                        <td class="small">{{ $p['name'] }}</td>
                        <td class="text-center small">{{ number_format($p['qty']) }}</td>
                        <td class="text-end small fw-bold text-success">{{ $currency }} {{ number_format($p['profit'], 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="3" class="text-center py-4 text-muted">No sales data</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Least Profitable / Loss Products -->
        <div class="col-md-6">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
              <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Low Margin / Loss Products</h6>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="small fw-bold">Product</th>
                      <th class="text-center small fw-bold">Qty</th>
                      <th class="text-end small fw-bold">Profit/Loss</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($lossProducts as $p)
                      <tr>
                        <td class="small">{{ $p['name'] }}</td>
                        <td class="text-center small">{{ number_format($p['qty']) }}</td>
                        <td class="text-end small fw-bold text-danger">{{ $currency }} {{ number_format($p['profit'], 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="3" class="text-center py-4 text-muted small">No products with loss or low margin</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

    </main>
    <footer class="app-footer"> EDUC | Copyright © 2026 All rights reserved.</footer>
  </div>
</div>

<script>
  document.getElementById('dateRangeSelect').addEventListener('change', function() {
    if (this.value === 'custom') {
      document.getElementById('customDateForm').style.display = 'flex';
      // If we are currently on the first form, don't submit yet, wait for user to pick dates
    } else {
      this.form.submit();
    }
  });

  // Handle case where custom was already selected but form not visible
  @if($dateRange === 'custom')
    document.getElementById('customDateForm').style.display = 'flex';
  @endif
</script>

<style>
  .bg-primary-subtle { background-color: #e7f1ff; }
  .bg-success-subtle { background-color: #e1f7e3; }
  .bg-danger-subtle { background-color: #fde7e9; }
  .text-primary { color: #0d6efd !important; }
  .text-success { color: #198754 !important; }
  .text-danger { color: #dc3545 !important; }
  
  .card { border-radius: 12px; }
  .card-header h6 { font-size: 0.9rem; letter-spacing: 0.5px; }
  
  @media print {
    .app-sidebar, .app-topbar, .app-footer, form, button { display: none !important; }
    .app-main { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
  }
</style>

@endsection
