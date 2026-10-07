@extends('layouts.app')
@section('content')

<div class="app-wrapper">
  <aside class="app-sidebar" id="app-sidebar"></aside>
  <div class="app-main">
    <header class="app-topbar" id="app-topbar"></header>
    <main class="app-content" style="padding: 20px;">
      
      <!-- Header & Filters -->
      <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
          <h1 class="h4 fw-bold mb-1">Register Report</h1>
          <p class="text-muted small mb-0">Track register activity, sales totals, and payment breakdowns.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <form method="GET" class="d-flex align-items-center gap-2" id="filterForm">
            <select class="form-select form-select-sm shadow-sm" name="date_range" id="dateRangeSelect" style="width:auto">
              <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
              <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
              <option value="this_week" {{ $dateRange === 'this_week' ? 'selected' : '' }}>This Week</option>
              <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
              <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
              <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
              <option value="all_time" {{ $dateRange === 'all_time' ? 'selected' : '' }}>All Time</option>
              <option value="custom" {{ $dateRange === 'custom' ? 'selected' : '' }}>Custom Range</option>
            </select>
            
            <select class="form-select form-select-sm shadow-sm" name="user_id" onchange="this.form.submit()" style="width:auto">
              <option value="">All Users</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" {{ $selectedUser == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
              @endforeach
            </select>

            <button type="button" onclick="toggleFilters()" class="btn btn-outline-primary btn-sm shadow-sm">
              <i class="bi bi-funnel"></i> More Filters
            </button>
          </form>
        </div>
      </div>

      <!-- Extended Filters (Hidden by default) -->
      <div class="card border-0 shadow-sm mb-4" id="extendedFilters" style="{{ $dateRange === 'custom' || $search || $selectedLocation ? '' : 'display: none;' }}">
        <div class="card-body">
          <form method="GET" class="row g-3">
            <input type="hidden" name="date_range" value="{{ $dateRange }}">
            <input type="hidden" name="user_id" value="{{ $selectedUser }}">
            
            <div class="col-md-3" id="customDateRange" style="{{ $dateRange === 'custom' ? '' : 'display: none;' }}">
              <label class="form-label small fw-bold">Date Range</label>
              <div class="input-group">
                <input type="date" class="form-control form-control-sm" name="from_date" value="{{ $fromDate }}">
                <span class="input-group-text small">to</span>
                <input type="date" class="form-control form-control-sm" name="to_date" value="{{ $toDate }}">
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-bold">Location</label>
              <select class="form-select form-select-sm" name="location_id">
                <option value="">All Locations</option>
                @foreach($locations as $location)
                  <option value="{{ $location->id }}" {{ $selectedLocation == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-bold">Search</label>
              <input type="text" class="form-control form-control-sm" name="search" placeholder="User name or email..." value="{{ $search }}">
            </div>

            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Apply Filters</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Summary KPI Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card border-0 shadow-sm bg-primary text-white h-100">
            <div class="card-body">
              <div class="small opacity-75 fw-bold mb-1 text-uppercase">Total Sales</div>
              <h3 class="fw-bold mb-0">{{ $currency }} {{ number_format($summaryTotals['grand_total'], 2) }}</h3>
              <div class="small mt-2 opacity-75">{{ $registers->total() }} total registers</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small fw-bold mb-1 text-uppercase">Total Cash</div>
              <h3 class="fw-bold mb-0 text-success">{{ $currency }} {{ number_format($summaryTotals['total_cash'], 2) }}</h3>
              <div class="small mt-2 text-muted">{{ round(($summaryTotals['total_cash'] / ($summaryTotals['grand_total'] ?: 1)) * 100) }}% of total</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small fw-bold mb-1 text-uppercase">Mobile Money</div>
              <h3 class="fw-bold mb-0 text-primary">{{ $currency }} {{ number_format($summaryTotals['total_airtel_money'] + $summaryTotals['total_mpamba'], 2) }}</h3>
              <div class="small mt-2 text-muted">Airtel Money & TNM Mpamba</div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="text-muted small fw-bold mb-1 text-uppercase">Bank & Cards</div>
              <h3 class="fw-bold mb-0 text-info">{{ $currency }} {{ number_format($summaryTotals['total_card'] + $summaryTotals['total_national_bank'] + $summaryTotals['total_standard_bank'] + $summaryTotals['total_bank_transfer'], 2) }}</h3>
              <div class="small mt-2 text-muted">Transfers and swipe</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Report Card -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
          <h2 class="h6 fw-bold mb-0">Register Activity Log</h2>
          <div class="d-flex gap-2">
             <a href="{{ route('reports.register-report.export', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-download"></i> CSV
            </a>
            <button class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
              <i class="bi bi-file-earmark-excel"></i> Excel
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
              <i class="bi bi-printer"></i> Print
            </button>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="registersTable">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">User / Location</th>
                  <th>Status / Timing</th>
                  <th class="text-end">Cash Sales</th>
                  <th class="text-end">Mobile Money</th>
                  <th class="text-end">Bank/Card</th>
                  <th class="text-end">Grand Total</th>
                  <th class="text-center pe-4">Action</th>
                </tr>
              </thead>
              <tbody>
                @forelse($registersData as $register)
                  <tr class="registerRow">
                    <td class="ps-4">
                      <div class="fw-bold">{{ $register['user_name'] }}</div>
                      <div class="text-muted small"><i class="bi bi-geo-alt"></i> {{ $register['location_name'] }}</div>
                    </td>
                    <td>
                      @if($register['closed_at'])
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 mb-1">Closed</span>
                      @else
                        <span class="badge bg-success-subtle text-success border px-2 py-1 mb-1">Active</span>
                      @endif
                      <div class="text-muted x-small">
                        Open: {{ $register['opened_at']->format('d M, H:i') }}<br>
                        {{ $register['closed_at'] ? 'Close: ' . $register['closed_at']->format('d M, H:i') : '' }}
                      </div>
                    </td>
                    <td class="text-end fw-semibold text-success">{{ $currency }} {{ number_format($register['total_cash'], 2) }}</td>
                    <td class="text-end">
                      <div class="fw-semibold">{{ $currency }} {{ number_format($register['total_airtel_money'] + $register['total_mpamba'], 2) }}</div>
                      <div class="x-small text-muted">AM: {{ number_format($register['total_airtel_money'],0) }} | Mp: {{ number_format($register['total_mpamba'],0) }}</div>
                    </td>
                    <td class="text-end">
                      <div class="fw-semibold">{{ $currency }} {{ number_format($register['total_card'] + $register['total_national_bank'] + $register['total_standard_bank'] + $register['total_bank_transfer'], 2) }}</div>
                      <div class="x-small text-muted">Swipe/Transfer</div>
                    </td>
                    <td class="text-end pe-4">
                      @php 
                        $registerTotal = $register['total_cash'] + 
                                       $register['total_card'] + 
                                       $register['total_cheques'] + 
                                       $register['total_bank_transfer'] + 
                                       $register['total_advance'] + 
                                       $register['total_national_bank'] + 
                                       $register['total_standard_bank'] + 
                                       $register['total_airtel_money'] + 
                                       $register['total_mpamba'];
                      @endphp
                      <div class="fw-bold text-primary">{{ $currency }} {{ number_format($registerTotal, 2) }}</div>
                    </td>
                    <td class="text-center pe-4">
                      <a href="{{ route('pos.register.show', ['id' => $register['id'], 'from_report' => 1]) }}" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="bi bi-eye"></i> View
                      </a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-5">
                      <div class="text-muted mb-2">No register data found for the selected period.</div>
                      <a href="{{ route('reports.register-report', ['date_range' => 'all_time']) }}" class="btn btn-sm btn-outline-primary">Show All Time</a>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        @if($registers->hasPages())
          <div class="card-footer bg-white py-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="small text-muted">
                Showing {{ $registers->firstItem() }} to {{ $registers->lastItem() }} of {{ $registers->total() }} results
              </div>
              <nav>
                {!! $registers->appends(request()->query())->links() !!}
              </nav>
            </div>
          </div>
        @endif
      </div>

    </main>
    <footer class="app-footer"> EDUC | Copyright © 2026 All rights reserved.</footer>
  </div>
</div>

<!-- Libraries for Export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>

<script>
  function toggleFilters() {
    const filters = document.getElementById('extendedFilters');
    filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
  }

  document.getElementById('dateRangeSelect').addEventListener('change', function() {
    if (this.value === 'custom') {
      document.getElementById('extendedFilters').style.display = 'block';
      document.getElementById('customDateRange').style.display = 'block';
    } else {
      document.getElementById('filterForm').submit();
    }
  });

  // Export to Excel
  function exportToExcel() {
    const table = document.getElementById('registersTable');
    const ws = XLSX.utils.table_to_sheet(table);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Register Report");
    XLSX.writeFile(wb, 'register-report-' + new Date().toISOString().split('T')[0] + '.xlsx');
  }
</script>

<style>
  .bg-success-subtle { background-color: #e1f7e3; }
  .bg-secondary-subtle { background-color: #f8f9fa; }
  .text-success { color: #198754 !important; }
  .text-primary { color: #0d6efd !important; }
  .x-small { font-size: 0.75rem; }
  
  .table thead th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    color: #6c757d;
    padding: 1rem 0.75rem;
  }
  
  .table tbody td {
    padding: 1rem 0.75rem;
  }
  
  .registerRow:hover {
    background-color: #f8f9ff !important;
  }
  
  @media print {
    .app-sidebar, .app-topbar, .app-footer, .btn, form, nav { display: none !important; }
    .app-main { margin: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
  }
</style>

@endsection
