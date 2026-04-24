@extends('layouts.app')
@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <h1 class="h4 fw-bold mb-0">Profit / Loss Report</h1>
          <div class="d-flex align-items-center gap-2">
            <form method="GET" class="d-flex align-items-center gap-2">
              <select class="form-select form-select-sm" name="date_range" style="width:auto" onchange="this.form.submit()">
                <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="last_7_days" {{ $dateRange === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="last_30_days" {{ $dateRange === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_month_last_year" {{ $dateRange === 'this_month_last_year' ? 'selected' : '' }}>This Month Last Year</option>
                <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_year" {{ $dateRange === 'last_year' ? 'selected' : '' }}>Last Year</option>
                <option value="custom" {{ $dateRange === 'custom' ? 'selected' : '' }}>Custom Range</option>
              </select>
            </form>

            <!-- Custom date range form (hidden by default) -->
            <form method="GET" id="customDateForm" class="d-flex align-items-center gap-2">
              <input type="hidden" name="date_range" value="custom">
              <input type="date" class="form-control form-control-sm" name="from_date" value="{{ $fromDate }}" style="width: auto;">
              <span class="text-muted">to</span>
              <input type="date" class="form-control form-control-sm" name="to_date" value="{{ $toDate }}" style="width: auto;">
              <button type="submit" class="btn btn-primary btn-sm fw-semibold">Filter</button>
            </form>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-md-6">
            <div class="card border">
              <div class="card-body" id="left-items">
                @foreach($leftItems as $key => $item)
                  <div class="d-flex justify-content-between py-2 {{ $key < count($leftItems)-1 ? 'border-bottom' : '' }}">
                    <div>
                      <span class="fw-semibold">{{ $item['label'] }}</span>
                      @if(isset($item['sub']))
                        <span class="text-muted small">{{ $item['sub'] }}</span>
                      @endif
                    </div>
                    <span>{{ $currency }} {{ number_format($item['value'], 2) }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="card border">
              <div class="card-body" id="right-items">
                @foreach($rightItems as $key => $item)
                  <div class="d-flex justify-content-between py-2 {{ $key < count($rightItems)-1 ? 'border-bottom' : '' }}">
                    <div>
                      <span class="fw-semibold">{{ $item['label'] }}</span>
                      @if(isset($item['sub']))
                        <span class="text-muted small">{{ $item['sub'] }}</span>
                      @endif
                    </div>
                    <span>{{ $currency }} {{ number_format($item['value'], 2) }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </main>
      <footer class="app-footer">  EDUC | Copyright © 2026 All rights reserved.</footer>
    </div>
  </div>

  <script>
    // Show/hide custom date form based on selection
    document.querySelector('[name="date_range"]').addEventListener('change', function() {
      const customForm = document.getElementById('customDateForm');
      if (this.value === 'custom') {
        customForm.style.display = 'flex';
      } else {
        customForm.style.display = 'none';
      }
    });

    // Show custom form if it's currently selected
    if (document.querySelector('[name="date_range"]').value === 'custom') {
      document.getElementById('customDateForm').style.display = 'flex';
    } else {
      document.getElementById('customDateForm').style.display = 'none';
    }
  </script>

@endsection
