@extends('layouts.app')
@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">

        <h1 class="h4 fw-bold mb-3">Sales</h1>
        
        <!-- Toggle Buttons -->
        <div class="d-flex gap-2 mb-3">
          <button onclick="toggleFilters()" class="btn btn-outline-secondary btn-sm" id="filtersToggle">
            <i class="bi bi-funnel"></i> Filters
          </button>
          <button onclick="toggleSummary()" class="btn btn-outline-info btn-sm" id="summaryToggle">
            <i class="bi bi-bar-chart"></i> Summary
          </button>
        </div>
        
        <!-- Filters Section -->
        <div class="card border mb-3" id="filtersSection">
          <div class="card-body py-2">
            <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
              <!-- Date Range -->
              <div class="col-md-2">
                <label class="form-label small">Date Range</label>
                <div class="input-group">
                  <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') ?: now()->setTimezone('Africa/Blantyre')->format('Y-m-d') }}">
                  <span class="input-group-text">to</span>
                  <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') ?: now()->setTimezone('Africa/Blantyre')->format('Y-m-d') }}">
                </div>
              </div>
              
              <!-- Payment Method -->
              <div class="col-md-2">
                <label class="form-label small">Payment Method</label>
                <select name="payment_method" class="form-select form-select-sm">
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
              
              <!-- Payment Status -->
              <div class="col-md-2">
                <label class="form-label small">Payment Status</label>
                <select name="payment_status" class="form-select form-select-sm">
                  <option value="">All Status</option>
                  <option value="Paid" {{ request('payment_status') == 'Paid' ? 'selected' : '' }}>Paid</option>
                  <option value="Due" {{ request('payment_status') == 'Due' ? 'selected' : '' }}>Due</option>
                </select>
              </div>
              
              <!-- User Filter -->
              <div class="col-md-2">
                <label class="form-label small">User</label>
                <select name="user_id" class="form-select form-select-sm">
                  <option value="">All Users</option>
                  @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                  @endforeach
                </select>
              </div>
              
              <!-- Register Filter -->
              <div class="col-md-2">
                <label class="form-label small">Register</label>
                <select name="register_id" class="form-select form-select-sm">
                  <option value="">All Registers</option>
                  @foreach($registers ?? [] as $register)
                    <option value="{{ $register->id }}" {{ request('register_id') == $register->id ? 'selected' : '' }}>
                      {{ $register->user->name }} - {{ $register->opened_at->format('M j, H:i') }}
                    </option>
                  @endforeach
                </select>
              </div>
              
              <!-- Search -->
              <div class="col-md-2">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice, Customer..." value="{{ request('search') }}">
              </div>
              
              <!-- Buttons -->
              <div class="col-md-2">
                <div class="d-flex gap-2">
                  <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                  <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm">Clear</a>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Summary Section - Compact -->
        @if($summary ?? false)
        <div class="card border mb-3" id="summarySection">
          <div class="card-header bg-light py-2">
            <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Sales Summary</h6>
          </div>
          <div class="card-body py-2">
            @if(request('payment_method'))
              <!-- Show filtered payment method summary -->
              <div class="row align-items-center">
                <div class="col-md-6">
                  <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2">{{ ucfirst(request('payment_method')) }}</span>
                    <span class="text-muted">Filtered results</span>
                  </div>
                </div>
                <div class="col-md-6 text-end">
                  <span class="h5 mb-0">{{ $summary['filtered_count'] ?? 0 }}</span>
                  <small class="text-muted ms-2">transactions</small>
                  <span class="h6 text-success ms-3">MWK {{ number_format($summary['filtered_total'] ?? 0, 2) }}</span>
                  <small class="text-muted ms-2">total</small>
                </div>
              </div>
            @else
              <!-- Show all payment methods summary - Compact with Total -->
              <div class="row">
                <!-- Total Sales Card -->
                <div class="col-md-2 text-center">
                  <div class="border rounded p-2 bg-primary text-white">
                    <div class="small">Total Sales</div>
                    <div class="small">MWK {{ number_format(collect($summary['payment_methods'] ?? [])->sum('total') ?? 0, 0) }}</div>
                  </div>
                </div>
                
                @foreach($summary['payment_methods'] ?? [] as $method => $data)
                <div class="col-md-2 text-center">
                  <div class="border rounded p-2">
                    <div class="small text-muted">{{ ucfirst($method) }}</div>
                    <div class="h6 mb-0 text-primary">{{ $data['count'] ?? 0 }}</div>
                    <div class="small text-success">MWK {{ number_format($data['total'] ?? 0, 0) }}</div>
                  </div>
                </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
        @endif

        <!-- Sales Table - No Scroll -->
        <div class="card border">
          <div class="card-header d-flex align-items-center justify-content-between bg-white">
            <h2 class="h6 fw-semibold mb-0">All sales</h2>
            <div class="d-flex gap-2">
              <button onclick="exportCSV()" class="btn btn-outline-primary btn-sm">📋 CSV</button>
              <button onclick="exportExcel()" class="btn btn-outline-success btn-sm">📊 Excel</button>
              <button onclick="printTable()" class="btn btn-outline-info btn-sm">🖨 Print</button>
              <button onclick="exportPDF()" class="btn btn-outline-danger btn-sm">📄 PDF</button>
            </div>
          </div>

          <div class="card-body border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2 small">
                <span>Show</span>
                <select class="form-select form-select-sm" style="width:auto" id="entriesSelect">
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
                <span>entries</span>
              </div>
              <input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Search ..." id="quickSearch">
            </div>
          </div>

          <!-- Regular Table Container - No Scroll -->
          <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 small" id="salesTable">
              <thead class="table-light">
                <tr>
                  <th>Action</th>
                  <th>Date ⇅</th>
                  <th>Product Name ⇅</th>
                  <th>Quantity ⇅</th>
                  <th>Customer name ⇅</th>
                  <th>Payment Status</th>
                  <th>Payment Method</th>
                  <th class="text-end">Total amount ⇅</th>
                </tr>
              </thead>
              <tbody>
                @foreach($sales ?? [] as $s)
                  <tr>
                    <td><a href="{{ route('sales.show', $s->id) }}" class="badge-paid">Actions ▾</a></td>
                    <td>{{ $s->created_at->format('d-m-Y H:i') }}</td>
                    <td>
                      @if($s->items && $s->items->count() > 0)
                        {{ $s->items->take(2)->pluck('product.name')->implode(', ') }}
                        @if($s->items->count() > 2)...
                        @endif
                      @else
                        Unknown Products
                      @endif
                    </td>
                    <td class="text-center">{{ $s->total_items }}</td>
                    <td>{{ $s->customer_name }}</td>
                    <td><span class="badge-paid">{{ $s->payment_status }}</span></td>
                    <td>{{ $s->payment_method }}</td>
                    <td class="text-end">MWK {{ number_format($s->total_amount,2) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          
          <div class="card-footer bg-white d-flex align-items-center justify-content-between small text-muted">
            <span id="sales-info">Showing {{ $sales->firstItem() }} to {{ $sales->lastItem() }} of {{ $sales->total() }} entries</span>
            <div>{!! $sales->links() !!}</div>
          </div>
        </div>

      </main>
      
      <footer class="app-footer">Educ| Copyright © 2026 All rights reserved.</footer>
    </div>
  </div>
  
  <script>
      // Prevent results from disappearing
      let currentFilters = {
        start_date: '{{ request("start_date") }}',
        end_date: '{{ request("end_date") }}',
        payment_method: '{{ request("payment_method") }}',
        payment_status: '{{ request("payment_status") }}',
        user_id: '{{ request("user_id") }}',
        search: '{{ request("search") }}'
      };
      
      // Toggle functions
      function toggleFilters() {
        const filtersSection = document.getElementById('filtersSection');
        const filtersToggle = document.getElementById('filtersToggle');
        
        if (filtersSection.style.display === 'none') {
          filtersSection.style.display = 'block';
          filtersToggle.innerHTML = '<i class="bi bi-funnel"></i> Filters';
          filtersToggle.classList.remove('btn-outline-primary');
          filtersToggle.classList.add('btn-outline-secondary');
        } else {
          filtersSection.style.display = 'none';
          filtersToggle.innerHTML = '<i class="bi bi-funnel-fill"></i> Show Filters';
          filtersToggle.classList.remove('btn-outline-secondary');
          filtersToggle.classList.add('btn-outline-primary');
        }
      }
      
      function toggleSummary() {
        const summarySection = document.getElementById('summarySection');
        const summaryToggle = document.getElementById('summaryToggle');
        
        if (summarySection.style.display === 'none') {
          summarySection.style.display = 'block';
          summaryToggle.innerHTML = '<i class="bi bi-bar-chart"></i> Summary';
          summaryToggle.classList.remove('btn-outline-primary');
          summaryToggle.classList.add('btn-outline-info');
        } else {
          summarySection.style.display = 'none';
          summaryToggle.innerHTML = '<i class="bi bi-bar-chart-fill"></i> Show Summary';
          summaryToggle.classList.remove('btn-outline-info');
          summaryToggle.classList.add('btn-outline-primary');
        }
      }
      
      // Quick search functionality
      document.getElementById('quickSearch').addEventListener('input', function(e) {
        const searchValue = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#salesTable tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchValue) ? '' : 'none';
        });
      });
      
      // Export functions
      function exportCSV() {
        const table = document.getElementById('salesTable');
        let csv = [];
        
        // Get headers
        const headers = [];
        table.querySelectorAll('thead th').forEach(th => {
          headers.push(th.textContent.trim());
        });
        csv.push(headers.join(','));
        
        // Get data
        table.querySelectorAll('tbody tr').forEach(tr => {
          const row = [];
          tr.querySelectorAll('td').forEach(td => {
            row.push(td.textContent.trim());
          });
          csv.push(row.join(','));
        });
        
        // Download CSV
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'sales_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
      }
      
      function exportExcel() {
        // Simple Excel export using CSV format (can be enhanced with proper Excel library)
        exportCSV();
      }
      
      function exportPDF() {
        window.print();
      }
      
      function printTable() {
        window.print();
      }
      
            
      // Update entries info
      document.getElementById('sales-info').textContent = `Showing 1 to {{ ($sales->count() ?? 0) }} of {{ ($sales->count() ?? 0) }} entries`;
      
      // Keep filters persistent
      document.addEventListener('DOMContentLoaded', function() {
        // Store current state
        localStorage.setItem('salesFilters', JSON.stringify(currentFilters));
      });
  </script>

@endsection
