@extends('layouts.app')
@section('content')

<!-- Chart.js for Visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="app-wrapper">
  <aside class="app-sidebar" id="app-sidebar"></aside>
  <div class="app-main">
    <header class="app-topbar" id="app-topbar"></header>
    <main class="app-content bg-light" style="padding: 1.5rem;">
      
      <!-- Header & Filters -->
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
          <h1 class="h3 fw-bold mb-1 text-dark">Pharmacy Financial Dashboard</h1>
          <p class="text-muted small mb-0">
            <i class="bi bi-calendar3"></i> {{ $fromDateLabel }} - {{ $toDateLabel }}
          </p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <form method="GET" class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm shadow-sm" name="date_range" id="dateRangeSelect" style="width:auto; border-radius: 8px;">
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
            <input type="date" class="form-control form-control-sm shadow-sm" name="from_date" value="{{ $fromDateDisplay }}" style="border-radius: 8px;">
            <span class="text-muted small">to</span>
            <input type="date" class="form-control form-control-sm shadow-sm" name="to_date" value="{{ $toDateDisplay }}" style="border-radius: 8px;">
            <button type="submit" class="btn btn-primary btn-sm fw-semibold shadow-sm" style="border-radius: 8px;">Apply</button>
          </form>
          <button onclick="window.print()" class="btn btn-outline-secondary btn-sm shadow-sm" style="border-radius: 8px;">
            <i class="bi bi-printer"></i> Print
          </button>
        </div>
      </div>

      <!-- Traffic Light Logic -->
      @php
        $netProfitStatus = $netMargin >= 15 ? 'success' : ($netMargin >= 5 ? 'warning' : 'danger');
        $grossMarginStatus = $grossMargin >= 30 ? 'success' : ($grossMargin >= 20 ? 'warning' : 'danger');
      @endphp

      <!-- TOP ROW: HIGH-RISK DATA & CORE KPIS (3 Columns) -->
      <div class="row g-4 mb-4">
        
        <!-- Net Profit (Prioritized Visual Element) -->
        <div class="col-xl-4 col-md-6">
          <div class="card border-0 shadow-lg h-100 bg-{{ $netProfitStatus }} text-white" style="border-radius: 16px; position: relative; overflow: hidden;">

            <div class="card-body p-4 position-relative z-1">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-uppercase fw-bold opacity-75 small tracking-wider">Net Profit</span>
                @if($deltas['net_profit'] != 0)
                  <div class="badge bg-white text-{{ $netProfitStatus }} rounded-pill px-3 py-2 fw-bold shadow-sm">
                    <i class="bi bi-arrow-{{ $deltas['net_profit'] > 0 ? 'up' : 'down' }}"></i> {{ abs($deltas['net_profit']) }}% vs Prior
                  </div>
                @endif
              </div>
              <h1 class="display-5 fw-bolder mb-1">{{ $currency }} {{ number_format($netProfit, 2) }}</h1>
              <div class="d-flex align-items-center gap-3 mt-4 opacity-75">
                <div>
                  <span class="d-block small text-uppercase">Net Margin</span>
                  <span class="fw-bold fs-5">{{ $netMargin }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Total Revenue -->
        <div class="col-xl-4 col-md-6">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle">
                      <i class="bi bi-cash-stack"></i>
                    </div>
                    <span class="text-muted fw-bold text-uppercase small">Total Revenue</span>
                  </div>
                  @if($deltas['revenue'] != 0)
                    <span class="badge bg-{{ $deltas['revenue'] > 0 ? 'success' : 'danger' }}-subtle text-{{ $deltas['revenue'] > 0 ? 'success' : 'danger' }} rounded-pill">
                      <i class="bi bi-arrow-{{ $deltas['revenue'] > 0 ? 'up' : 'down' }}"></i> {{ abs($deltas['revenue']) }}%
                    </span>
                  @endif
                </div>
                <h2 class="fw-bold text-dark mb-1">{{ $currency }} {{ number_format($totalSales, 2) }}</h2>
                <div class="text-muted small">Generated across <span class="fw-bold">{{ $totalTransactions }}</span> transactions</div>
              </div>
              <div class="mt-4 p-3 bg-light rounded-3">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted small fw-medium">Average Transaction Value</span>
                  <span class="fw-bold text-dark">{{ $currency }} {{ number_format($totalTransactions > 0 ? $totalSales / $totalTransactions : 0, 2) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Gross Margin & Expenses -->
        <div class="col-xl-4 col-md-12">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border-left: 5px solid var(--bs-{{ $grossMarginStatus }}) !important;">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-bold text-uppercase small">Gross Profit</span>
                <span class="badge bg-{{ $grossMarginStatus }} text-white rounded-pill px-3">{{ $grossMargin }}% Margin</span>
              </div>
              <h2 class="fw-bold text-dark mb-4">{{ $currency }} {{ number_format($grossProfit, 2) }}</h2>
              
              <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="text-muted">Cost of Goods Sold</span>
                  <span class="fw-bold text-danger">{{ $currency }} {{ number_format($costOfGoodsSold, 0) }}</span>
                </div>
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-danger" style="width: {{ $totalSales > 0 ? ($costOfGoodsSold/$totalSales)*100 : 0 }}%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- MIDDLE ROW: CHARTS -->
      <div class="row g-4 mb-4">
        <!-- Seasonality Line Chart -->
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-graph-up text-primary me-2"></i>6-Month Revenue vs COGS Trend</h5>
              <div style="height: 300px;">
                <canvas id="seasonalityChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Payment Methods Horizontal Bar -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-credit-card text-primary me-2"></i>Revenue by Payment Method</h5>
              <div style="height: 300px;">
                <canvas id="paymentMethodChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BOTTOM ROW: INVENTORY INTELLIGENCE & INSIGHTS (3 Columns) -->
      <div class="row g-4">
        <!-- Stock Health -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
              <h5 class="fw-bold text-dark"><i class="bi bi-boxes text-primary me-2"></i>Stock Health Intelligence</h5>
            </div>
            <div class="card-body p-4">
              <!-- Overstock Warning -->
              @php 
                $stockRisk = ($totalSales > 0 && $closingStockCost > ($totalSales * 3)); 
              @endphp
              <div class="alert alert-{{ $stockRisk ? 'danger' : 'success' }} border-0 shadow-sm rounded-3 mb-4 d-flex align-items-start">
                <i class="bi bi-{{ $stockRisk ? 'exclamation-triangle-fill' : 'check-circle-fill' }} fs-4 me-3 mt-1"></i>
                <div>
                  <h6 class="fw-bold mb-1">{{ $stockRisk ? 'High Overstock Risk Detected' : 'Healthy Inventory Levels' }}</h6>
                  <p class="small mb-0">Closing stock is <strong>{{ number_format($totalSales > 0 ? $closingStockRetail / $totalSales : 0, 1) }}x</strong> your period revenue. 
                  @if($stockRisk) This locks up cash flow significantly. @endif</p>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-3">
                <span class="text-muted fw-medium small">Closing Stock (Retail)</span>
                <span class="fw-bold text-dark fs-5">{{ $currency }} {{ number_format($closingStockRetail, 0) }}</span>
              </div>
              
              <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                <div>
                  <span class="text-muted fw-medium small d-block">Inventory Turnover Ratio</span>
                  <span class="x-small text-muted">(COGS / Avg Inv)</span>
                </div>
                <span class="fw-bold text-{{ $inventoryTurnover < 0.5 ? 'danger' : 'success' }} fs-5">
                  {{ number_format($inventoryTurnover, 2) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Categories Margin -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
              <h5 class="fw-bold text-dark"><i class="bi bi-tags text-primary me-2"></i>Top Margins by Category</h5>
            </div>
            <div class="card-body p-4">
              <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0">
                  <tbody>
                    @forelse($categoryMargins as $cat => $data)
                      <tr class="border-bottom">
                        <td class="ps-0 py-3">
                          <span class="fw-medium text-dark d-block">{{ $cat }}</span>
                          <span class="small text-muted">Rev: {{ $currency }} {{ number_format($data['revenue'], 0) }}</span>
                        </td>
                        <td class="text-end pe-0 py-3">
                          <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-bold">
                            {{ $data['margin'] }}%
                          </span>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="2" class="text-center text-muted py-4">No category data available</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Slow Moving Items -->
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border-top: 5px solid var(--bs-danger) !important;">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass-bottom text-danger me-2"></i>Slow Moving Items</h5>
              <span class="badge bg-danger rounded-pill">0 Sales in 30 Days</span>
            </div>
            <div class="card-body p-4">
              <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th class="small text-muted fw-medium border-0">Product</th>
                      <th class="text-end small text-muted fw-medium border-0">Qty Stuck</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($slowMovingProducts as $item)
                      <tr>
                        <td class="py-2">
                          <span class="d-block fw-medium text-dark small text-truncate" style="max-width: 180px;" title="{{ $item->name }}">{{ $item->name }}</span>
                        </td>
                        <td class="text-end py-2">
                          <span class="badge bg-light text-danger border fw-bold">{{ $item->current_stock }}</span>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="2" class="text-center text-muted py-4 small">No slow moving items found.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- DETAILED FINANCIAL STATEMENT -->
      <div class="card border-0 shadow-sm mt-4 mb-4" style="border-radius: 16px;">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
          <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Detailed Profit & Loss Statement</h5>
          <p class="text-muted small mb-0">Granular breakdown of inventory valuations and transaction metrics.</p>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <!-- Left Side Ledger (Assets & Expenses) -->
            <div class="col-lg-6">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <tbody>
                    @foreach($leftItems as $item)
                      <tr class="border-bottom">
                        <td class="ps-0 py-3">
                          <span class="fw-semibold text-dark d-block">{{ $item['label'] }}</span>
                          @if(isset($item['sub']))
                            <span class="x-small text-muted">{{ $item['sub'] }}</span>
                          @endif
                        </td>
                        <td class="text-end pe-0 py-3">
                          <span class="fw-bold text-dark">{{ $currency }} {{ number_format($item['value'], 2) }}</span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
            
            <!-- Right Side Ledger (Revenue & Profits) -->
            <div class="col-lg-6">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <tbody>
                    @foreach($rightItems as $item)
                      <tr class="border-bottom">
                        <td class="ps-0 py-3">
                          <span class="fw-semibold text-dark d-block">{{ $item['label'] }}</span>
                          @if(isset($item['sub']))
                            <span class="x-small text-muted">{{ $item['sub'] }}</span>
                          @endif
                        </td>
                        <td class="text-end pe-0 py-3">
                          <span class="fw-bold @if(in_array($item['label'], ['Gross Profit', 'Net Profit'])) text-{{ $item['value'] >= 0 ? 'success' : 'danger' }} @else text-dark @endif">
                            {{ $currency }} {{ number_format($item['value'], 2) }}
                          </span>
                        </td>
                      </tr>
                    @endforeach
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
  // Filter Form Logic
  document.getElementById('dateRangeSelect').addEventListener('change', function() {
    if (this.value === 'custom') {
      document.getElementById('customDateForm').style.display = 'flex';
    } else {
      this.form.submit();
    }
  });

  @if($dateRange === 'custom')
    document.getElementById('customDateForm').style.display = 'flex';
  @endif

  // --- CHART CONFIGURATIONS ---
  
  // 1. Seasonality Line Chart
  const seasonalityCtx = document.getElementById('seasonalityChart').getContext('2d');
  const seasonalityData = @json($seasonalityTrend);
  
  new Chart(seasonalityCtx, {
      type: 'line',
      data: {
          labels: seasonalityData.map(d => d.month),
          datasets: [
              {
                  label: 'Revenue',
                  data: seasonalityData.map(d => d.revenue),
                  borderColor: '#0d6efd', // Primary
                  backgroundColor: 'rgba(13, 110, 253, 0.1)',
                  borderWidth: 3,
                  tension: 0.4,
                  fill: true,
                  pointBackgroundColor: '#0d6efd'
              },
              {
                  label: 'Cost of Goods Sold',
                  data: seasonalityData.map(d => d.expenses),
                  borderColor: '#dc3545', // Danger
                  backgroundColor: 'rgba(220, 53, 69, 0.0)',
                  borderWidth: 2,
                  tension: 0.4,
                  borderDash: [5, 5],
                  pointBackgroundColor: '#dc3545'
              }
          ]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
              mode: 'index',
              intersect: false,
          },
          plugins: {
              legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } },
              tooltip: {
                  callbacks: {
                      label: function(context) {
                          let label = context.dataset.label || '';
                          if (label) { label += ': MWK '; }
                          if (context.parsed.y !== null) {
                              label += new Intl.NumberFormat('en-US').format(context.parsed.y);
                          }
                          return label;
                      }
                  }
              }
          },
          scales: {
              y: { 
                  beginAtZero: true, 
                  grid: { color: '#f1f5f9' },
                  ticks: {
                      callback: function(value) {
                          if (value >= 1000000) return (value / 1000000) + 'M';
                          if (value >= 1000) return (value / 1000) + 'k';
                          return value;
                      }
                  }
              },
              x: { grid: { display: false } }
          }
      }
  });

  // 2. Payment Method Horizontal Bar Chart
  const paymentCtx = document.getElementById('paymentMethodChart').getContext('2d');
  const paymentData = @json($paymentBreakdown);
  
  new Chart(paymentCtx, {
      type: 'bar',
      data: {
          labels: paymentData.map(d => d.method.replace(/_/g, ' ').toUpperCase()),
          datasets: [{
              label: 'Revenue',
              data: paymentData.map(d => d.total),
              backgroundColor: [
                  '#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6f42c1', '#198754'
              ],
              borderRadius: 6
          }]
      },
      options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { display: false },
              tooltip: {
                  callbacks: {
                      label: function(context) {
                          return 'MWK ' + new Intl.NumberFormat('en-US').format(context.parsed.x);
                      }
                  }
              }
          },
          scales: {
              x: { 
                  grid: { color: '#f1f5f9' },
                  ticks: {
                      callback: function(value) {
                          if (value >= 1000000) return (value / 1000000) + 'M';
                          if (value >= 1000) return (value / 1000) + 'k';
                          return value;
                      }
                  }
              },
              y: { grid: { display: false } }
          }
      }
  });
</script>

<style>
  /* Utilities & Enhancements */
  .tracking-wider { letter-spacing: 0.05em; }
  .x-small { font-size: 0.75rem; }
  
  /* Background colors for traffic light */
  .bg-success { background-color: #10b981 !important; } /* Emerald */
  .bg-warning { background-color: #f59e0b !important; } /* Amber */
  .bg-danger { background-color: #ef4444 !important; }  /* Red */
  
  .text-success { color: #10b981 !important; }
  .text-warning { color: #f59e0b !important; }
  .text-danger { color: #ef4444 !important; }
  
  .bg-success-subtle { background-color: rgba(16, 185, 129, 0.15) !important; }
  .bg-danger-subtle { background-color: rgba(239, 68, 68, 0.15) !important; }
  
  .border-success { border-color: #10b981 !important; }
  .border-warning { border-color: #f59e0b !important; }
  .border-danger { border-color: #ef4444 !important; }
  
  /* Hide scrollbar for tables */
  .table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
  .table-responsive::-webkit-scrollbar-track { background: transparent; }
  .table-responsive::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
  .table-responsive:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
  
  @media print {
    .app-sidebar, .app-topbar, .app-footer, form, button { display: none !important; }
    .app-main { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; break-inside: avoid; }
    .col-xl-4 { width: 33.33333333%; float: left; }
    .col-lg-8 { width: 66.66666667%; float: left; }
    .col-lg-4 { width: 33.33333333%; float: left; }
    canvas { max-width: 100% !important; }
  }
</style>

@endsection
