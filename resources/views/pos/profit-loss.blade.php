@extends('layouts.app')
@section('title', 'Profit & Loss - ' . setting('system_name'))
@section('content')

<!-- Chart.js for Visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
  $money = fn ($v, $d = 0) => $currency . ' ' . number_format((float) $v, $d);
  $netProfitStatus = $netProfit < 0 ? 'danger' : ($netMargin >= 15 ? 'success' : ($netMargin >= 5 ? 'warning' : 'danger'));
  $grossMarginStatus = $grossMargin >= 30 ? 'success' : ($grossMargin >= 20 ? 'warning' : 'danger');
  $canRecordExpenses = auth()->user()->hasPermission('add_expense') || auth()->user()->hasPermission('access_all_expenses');
  $expensesUrl = route('expenses.index', ['from' => $fromDateDisplay, 'to' => $toDateDisplay]);
@endphp

{{-- Change against the previous period; null means there is nothing to compare with --}}
@php
  $delta = function ($value, $onDark = false) use ($comparisonLabel) {
      if ($value === null) {
          return '<span class="small ' . ($onDark ? 'opacity-75' : 'text-muted') . '">No earlier data to compare</span>';
      }
      $up = $value >= 0;
      $cls = $onDark ? 'bg-white text-dark' : ($up ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger');
      return '<span class="badge rounded-pill ' . $cls . '" title="' . e($comparisonLabel) . '"><i class="bi bi-arrow-' . ($up ? 'up' : 'down') . '"></i> '
          . abs($value) . '%</span> <span class="small ' . ($onDark ? 'opacity-75' : 'text-muted') . '">' . e($comparisonLabel) . '</span>';
  };
@endphp

<div class="container-fluid py-4 pl-report">

  <!-- Header & Filters -->
  <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h1 class="h3 fw-bold mb-1 text-dark">Profit &amp; Loss</h1>
      <p class="text-muted small mb-0"><i class="bi bi-calendar3"></i> {{ $fromDateLabel }} – {{ $toDateLabel }} · {{ setting('system_name') }}</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="GET" class="d-flex align-items-center gap-2">
        <label for="dateRangeSelect" class="visually-hidden">Period</label>
        <select class="form-select form-select-sm shadow-sm" name="date_range" id="dateRangeSelect" style="width:auto; border-radius: 8px;">
          @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week', 'last_7_days' => 'Last 7 Days', 'last_30_days' => 'Last 30 Days', 'this_month' => 'This Month', 'last_month' => 'Last Month', 'this_year' => 'This Year', 'last_year' => 'Last Year', 'custom' => 'Custom Range'] as $key => $label)
            <option value="{{ $key }}" @selected($dateRange === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </form>

      <form method="GET" id="customDateForm" class="align-items-center gap-2" style="display: {{ $dateRange === 'custom' ? 'flex' : 'none' }};">
        <input type="hidden" name="date_range" value="custom">
        <input type="date" class="form-control form-control-sm shadow-sm" name="from_date" value="{{ $fromDateDisplay }}" aria-label="From" style="border-radius: 8px;">
        <span class="text-muted small">to</span>
        <input type="date" class="form-control form-control-sm shadow-sm" name="to_date" value="{{ $toDateDisplay }}" aria-label="To" style="border-radius: 8px;">
        <button type="submit" class="btn btn-primary btn-sm fw-semibold shadow-sm" style="border-radius: 8px;">Apply</button>
      </form>
      <button onclick="window.print()" class="btn btn-outline-secondary btn-sm shadow-sm" style="border-radius: 8px;">
        <i class="bi bi-printer"></i> Print
      </button>
    </div>
  </div>

  @if($operatingExpenses == 0)
    <div class="alert alert-info alert-persistent border-0 shadow-sm d-flex align-items-center gap-3 mb-4" role="status">
      <i class="bi bi-info-circle fs-4"></i>
      <div class="flex-grow-1">
        <strong>No expenses recorded for this period.</strong>
        Net profit below does not yet include running costs such as rent, wages and electricity.
      </div>
      @if($canRecordExpenses)
        <a href="{{ $expensesUrl }}" class="btn btn-sm btn-primary text-nowrap">Record expenses</a>
      @endif
    </div>
  @endif

  <!-- KPI ROW -->
  <div class="row g-3 g-xl-4 mb-4">
    <!-- Net Profit -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow h-100 bg-{{ $netProfitStatus }} text-white" style="border-radius: 16px;">
        <div class="card-body p-4">
          <div class="text-uppercase fw-bold opacity-75 small mb-2">Net Profit</div>
          <div class="pl-kpi fw-bolder mb-1">{{ $money($netProfit) }}</div>
          <div class="mb-3 opacity-75 small">{{ $netMargin }}% of sales kept as profit</div>
          {!! $delta($deltas['net_profit'], true) !!}
        </div>
      </div>
    </div>

    <!-- Revenue -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <div class="text-muted fw-bold text-uppercase small mb-2"><i class="bi bi-cash-stack text-primary me-1"></i> Sales</div>
          <div class="pl-kpi fw-bold text-dark mb-1">{{ $money($totalSales) }}</div>
          <div class="text-muted small mb-3">
            {{ number_format($totalTransactions) }} {{ $totalTransactions == 1 ? 'sale' : 'sales' }}
            · average {{ $money($totalTransactions > 0 ? $totalSales / $totalTransactions : 0) }}
          </div>
          {!! $delta($deltas['revenue']) !!}
        </div>
      </div>
    </div>

    <!-- Gross Profit -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border-left: 5px solid var(--bs-{{ $grossMarginStatus }}) !important;">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted fw-bold text-uppercase small">Gross Profit</span>
            <span class="badge bg-{{ $grossMarginStatus }} rounded-pill">{{ $grossMargin }}% margin</span>
          </div>
          <div class="pl-kpi fw-bold text-dark mb-1">{{ $money($grossProfit) }}</div>
          <div class="text-muted small mb-3">Sales minus what the goods cost you</div>
          {!! $delta($deltas['gross_profit']) !!}
        </div>
      </div>
    </div>

    <!-- Costs & Losses -->
    <div class="col-xl-3 col-md-6">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <div class="text-muted fw-bold text-uppercase small mb-2"><i class="bi bi-wallet2 text-danger me-1"></i> Expenses &amp; Losses</div>
          <div class="pl-kpi fw-bold text-danger mb-2">{{ $money($operatingExpenses + $stockWrittenOff) }}</div>
          <div class="d-flex justify-content-between small"><span class="text-muted">Running costs</span><span class="fw-semibold">{{ $money($operatingExpenses) }}</span></div>
          <div class="d-flex justify-content-between small mb-2"><span class="text-muted">Stock written off</span><span class="fw-semibold">{{ $money($stockWrittenOff) }}</span></div>
          @if($canRecordExpenses)
            <a href="{{ $expensesUrl }}" class="small">View or record expenses <i class="bi bi-arrow-right"></i></a>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- STOCK VALUE: start and end of the period, at purchase and at selling price -->
  <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
    <div class="card-body p-3 p-md-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-primary me-2"></i>Stock value</h5>
        <span class="text-muted small">Quantities on each date × each product's purchase / selling price</span>
      </div>
      <div class="row g-3">
        <div class="col-6 col-lg-3">
          <div class="pl-stock-tile">
            <div class="text-muted small">Opening stock <span class="fw-semibold">by purchase price</span></div>
            <div class="pl-stock-value">{{ $money($openingStockCost) }}</div>
            <div class="text-muted x-small">on {{ $fromDateLabel }}</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="pl-stock-tile">
            <div class="text-muted small">Opening stock <span class="fw-semibold">by selling price</span></div>
            <div class="pl-stock-value">{{ $money($openingStockRetail) }}</div>
            <div class="text-muted x-small">on {{ $fromDateLabel }}</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="pl-stock-tile">
            <div class="text-muted small">Closing stock <span class="fw-semibold">by purchase price</span></div>
            <div class="pl-stock-value">{{ $money($closingStockCost) }}</div>
            <div class="text-muted x-small">{{ $toDate->isFuture() ? 'today' : 'on ' . $toDateLabel }}</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="pl-stock-tile">
            <div class="text-muted small">Closing stock <span class="fw-semibold">by selling price</span></div>
            <div class="pl-stock-value">{{ $money($closingStockRetail) }}</div>
            <div class="text-success x-small fw-semibold">{{ $money($closingStockRetail - $closingStockCost) }} profit if all sold</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- STATEMENT + TREND -->
  <div class="row g-3 g-xl-4 mb-4">
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text text-primary me-2"></i>Profit &amp; Loss Statement</h5>
          <table class="table table-sm align-middle mb-0 pl-statement">
            <tbody>
              <tr><td>Sales revenue <span class="text-muted small">({{ number_format($totalTransactions) }} sales)</span></td><td class="text-end fw-semibold">{{ $money($totalSales, 2) }}</td></tr>
              <tr><td class="ps-3">Less: cost of goods sold</td><td class="text-end text-danger">− {{ $money($costOfGoodsSold, 2) }}</td></tr>
              <tr class="pl-subtotal"><td>Gross profit <span class="text-muted small">({{ $grossMargin }}%)</span></td><td class="text-end">{{ $money($grossProfit, 2) }}</td></tr>

              <tr><td class="ps-3">Less: stock written off</td><td class="text-end text-danger">− {{ $money($stockWrittenOff, 2) }}</td></tr>
              @foreach($writeOffsByReason as $reason => $amount)
                <tr class="pl-detail"><td class="ps-4 text-muted small">{{ $reason }}</td><td class="text-end text-muted small">{{ $money($amount, 2) }}</td></tr>
              @endforeach

              <tr><td class="ps-3">Less: running costs</td><td class="text-end text-danger">− {{ $money($operatingExpenses, 2) }}</td></tr>
              @foreach($expensesByCategory as $category => $amount)
                <tr class="pl-detail"><td class="ps-4 text-muted small">{{ $category }}</td><td class="text-end text-muted small">{{ $money($amount, 2) }}</td></tr>
              @endforeach

              <tr class="pl-total"><td>Net profit <span class="small fw-normal">({{ $netMargin }}%)</span></td>
                <td class="text-end text-{{ $netProfit >= 0 ? 'success' : 'danger' }}">{{ $money($netProfit, 2) }}</td></tr>
            </tbody>
          </table>
          <p class="text-muted small mt-3 mb-0">
            Cost of goods uses the purchase price recorded at the time of each sale. Written-off stock is valued at its purchase price.
          </p>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-graph-up text-primary me-2"></i>Last 6 months</h5>
          <div style="height: 320px;">
            <canvas id="seasonalityChart" aria-label="Sales, costs and net profit for the last six months" role="img"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- PRODUCTS + PAYMENTS -->
  <div class="row g-3 g-xl-4 mb-4">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-trophy text-warning me-2"></i>Most profitable products</h5>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="small text-muted fw-medium">Product</th>
                  <th class="small text-muted fw-medium text-end">Sold</th>
                  <th class="small text-muted fw-medium text-end">Sales</th>
                  <th class="small text-muted fw-medium text-end">Profit</th>
                  <th class="small text-muted fw-medium text-end">Margin</th>
                </tr>
              </thead>
              <tbody>
                @forelse($topProducts as $product)
                  <tr>
                    <td class="small fw-medium text-truncate" style="max-width: 220px;" title="{{ $product['name'] }}">{{ $product['name'] }}</td>
                    <td class="small text-end">{{ number_format($product['qty']) }}</td>
                    <td class="small text-end">{{ $money($product['revenue']) }}</td>
                    <td class="small text-end fw-semibold text-{{ $product['profit'] >= 0 ? 'success' : 'danger' }}">{{ $money($product['profit']) }}</td>
                    <td class="small text-end">{{ $product['margin'] }}%</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted py-4 small">No sales in this period.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-credit-card text-primary me-2"></i>Money received by method</h5>
          <p class="text-muted small mb-3">Split payments are counted per method; change given back is excluded.</p>
          @if(count($paymentBreakdown))
            <div style="height: {{ max(140, count($paymentBreakdown) * 46) }}px;">
              <canvas id="paymentMethodChart" aria-label="Money received by payment method" role="img"></canvas>
            </div>
            <table class="table table-sm mb-0 mt-2">
              @foreach($paymentBreakdown as $row)
                <tr>
                  <td class="small">{{ $row['method'] }}</td>
                  <td class="small text-end text-muted">{{ $row['count'] }} {{ $row['count'] == 1 ? 'payment' : 'payments' }}</td>
                  <td class="small text-end fw-semibold">{{ $money($row['total']) }}</td>
                </tr>
              @endforeach
            </table>
          @else
            <p class="text-muted small mb-0">No payments in this period.</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- CATEGORIES + STOCK + SLOW MOVERS -->
  <div class="row g-3 g-xl-4">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-tags text-primary me-2"></i>Profit by category</h5>
          @forelse($categoryMargins as $cat => $data)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div>
                <div class="fw-medium small">{{ $cat }}</div>
                <div class="text-muted x-small">Sales {{ $money($data['revenue']) }}</div>
              </div>
              <div class="text-end">
                <div class="fw-semibold small text-{{ $data['profit'] >= 0 ? 'success' : 'danger' }}">{{ $money($data['profit']) }}</div>
                <div class="text-muted x-small">{{ $data['margin'] }}% margin</div>
              </div>
            </div>
          @empty
            <p class="text-muted small mb-0">No sales in this period.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-boxes text-primary me-2"></i>Stock movement <span class="text-muted small fw-normal">(at cost)</span></h5>
          <table class="table table-sm mb-3 pl-statement">
            <tr><td class="small">At start <span class="text-muted">({{ \Carbon\Carbon::parse($fromDateDisplay)->format('d M') }})</span></td><td class="small text-end text-nowrap">{{ $money($openingStockCost) }}</td></tr>
            <tr><td class="small ps-3">+ Received</td><td class="small text-end text-nowrap">{{ $money($stockReceived) }}</td></tr>
            <tr><td class="small ps-3">− Sold (at cost)</td><td class="small text-end text-nowrap">{{ $money($costOfGoodsSold) }}</td></tr>
            <tr><td class="small ps-3">− Written off</td><td class="small text-end text-nowrap">{{ $money($stockWrittenOff) }}</td></tr>
            @if($otherStockChanges != 0)
              <tr><td class="small ps-3" title="Purchase price updates, stock found in counts, deleted sales">{{ $otherStockChanges > 0 ? '+' : '−' }} Other changes</td>
                  <td class="small text-end text-nowrap">{{ $money(abs($otherStockChanges)) }}</td></tr>
            @endif
            <tr class="pl-subtotal"><td class="small">At end <span class="text-muted fw-normal">({{ \Carbon\Carbon::parse($toDateDisplay)->format('d M') }})</span></td><td class="small text-end text-nowrap">{{ $money($closingStockCost) }}</td></tr>
          </table>
          @if($totalPurchase > 0)
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Supplier invoices this period</span><span class="fw-semibold">{{ $money($totalPurchase) }}</span></div>
          @endif
          <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Value at selling price</span><span class="fw-semibold">{{ $money($closingStockRetail) }}</span></div>
          <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Units in stock</span><span class="fw-semibold">{{ number_format($totalStockUnits) }}</span></div>
          <div class="d-flex justify-content-between small" title="Cost of goods sold divided by average stock value">
            <span class="text-muted">Stock turnover this period</span><span class="fw-semibold">{{ number_format($inventoryTurnover, 2) }}×</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
        <div class="card-body p-4">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass-bottom text-danger me-2"></i>Not selling</h5>
            <span class="badge bg-danger-subtle text-danger rounded-pill">No sales in 30 days</span>
          </div>
          <p class="text-muted small mb-3">{{ $money($slowMovingValue) }} of cash is tied up in stock that has not sold in 30 days.</p>
          <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
            <table class="table table-sm align-middle mb-0">
              <tbody>
                @forelse($slowMovingProducts as $item)
                  <tr>
                    <td class="small text-truncate" style="max-width: 170px;" title="{{ $item->name }}">{{ $item->name }}</td>
                    <td class="small text-end text-muted">{{ number_format($item->current_stock) }} units</td>
                    <td class="small text-end fw-semibold">{{ $money($item->current_stock * $item->purchase_price) }}</td>
                  </tr>
                @empty
                  <tr><td class="text-center text-muted py-4 small">Everything in stock has sold in the last 30 days.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Period selector: everything except "Custom Range" applies straight away
  document.getElementById('dateRangeSelect').addEventListener('change', function () {
    if (this.value === 'custom') {
      document.getElementById('customDateForm').style.display = 'flex';
    } else {
      this.form.submit();
    }
  });

  const shortMoney = (value) => {
    const abs = Math.abs(value);
    if (abs >= 1000000) return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (abs >= 1000) return Math.round(value / 1000) + 'k';
    return value;
  };
  const fullMoney = (value) => 'MWK ' + new Intl.NumberFormat('en-US').format(Math.round(value));

  // 1. Last six months: sales, all costs, net profit
  const seasonalityData = @json($seasonalityTrend);
  new Chart(document.getElementById('seasonalityChart'), {
    data: {
      labels: seasonalityData.map(d => d.month),
      datasets: [
        { type: 'bar', label: 'Sales', data: seasonalityData.map(d => d.revenue), backgroundColor: 'rgba(13, 110, 253, 0.75)', borderRadius: 6, order: 2 },
        { type: 'bar', label: 'Costs (goods, losses, expenses)', data: seasonalityData.map(d => d.expenses), backgroundColor: 'rgba(220, 53, 69, 0.55)', borderRadius: 6, order: 3 },
        { type: 'line', label: 'Net profit', data: seasonalityData.map(d => d.net_profit), borderColor: '#10b981', backgroundColor: '#10b981', borderWidth: 3, tension: 0.3, pointRadius: 4, order: 1 }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } },
        tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + fullMoney(c.parsed.y) } }
      },
      scales: {
        y: { grid: { color: '#f1f5f9' }, ticks: { callback: shortMoney } },
        x: { grid: { display: false } }
      }
    }
  });

  // 2. Money received per payment method
  const paymentData = @json($paymentBreakdown);
  const paymentCanvas = document.getElementById('paymentMethodChart');
  if (paymentCanvas) {
    new Chart(paymentCanvas, {
      type: 'bar',
      data: {
        labels: paymentData.map(d => d.method),
        datasets: [{ label: 'Received', data: paymentData.map(d => d.total),
          backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6f42c1', '#198754', '#0dcaf0', '#6c757d'], borderRadius: 6 }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => fullMoney(c.parsed.x) } } },
        scales: { x: { grid: { color: '#f1f5f9' }, ticks: { callback: shortMoney } }, y: { grid: { display: false } } }
      }
    });
  }
</script>

<style>
  .x-small { font-size: 0.75rem; }
  .pl-kpi { font-size: clamp(1.35rem, 1.75vw, 2rem); line-height: 1.2; white-space: nowrap; }
  .pl-stock-tile { background: #f8fafc; border: 1px solid #eef2f7; border-radius: 12px; padding: .85rem 1rem; height: 100%; }
  .pl-stock-value { font-size: clamp(1.05rem, 1.35vw, 1.4rem); font-weight: 700; color: #0f172a; white-space: nowrap; margin: .15rem 0; }
  .bg-success { background-color: #10b981 !important; }
  .bg-warning { background-color: #f59e0b !important; }
  .bg-danger { background-color: #ef4444 !important; }
  .text-success { color: #059669 !important; }
  .text-danger { color: #dc2626 !important; }
  .bg-success-subtle { background-color: rgba(16, 185, 129, 0.15) !important; }
  .bg-danger-subtle { background-color: rgba(239, 68, 68, 0.15) !important; }

  .pl-statement td { border-color: #f1f5f9; padding-top: .45rem; padding-bottom: .45rem; }
  .pl-statement .pl-detail td { padding-top: .15rem; padding-bottom: .15rem; border: 0; }
  .pl-statement .pl-subtotal td { font-weight: 700; border-top: 1px solid #cbd5e1; }
  .pl-statement .pl-total td { font-weight: 800; font-size: 1.05rem; border-top: 2px solid #0f172a; border-bottom: 0; }

  @media print {
    #sidebar-wrapper, .navbar, form, button, .alert .btn, a.small { display: none !important; }
    #page-content-wrapper, .content-container { height: auto !important; overflow: visible !important; padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; break-inside: avoid; }
    .pl-report .row > [class*="col-"] { width: 100% !important; }
    canvas { max-width: 100% !important; }
  }
</style>

@endsection
