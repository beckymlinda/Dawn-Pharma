@extends('layouts.app')
@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content" style="padding: 20px;">
        
        <h1 class="h4 fw-bold mb-3">Register Report</h1>

        <!-- Filters Section -->
        <div class="card border mb-3">
          <div class="card-body py-3 px-4">
            <button class="btn btn-link p-0 text-primary fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection" aria-expanded="true" aria-controls="filterSection">
              🔽 Filters
            </button>
          </div>
          <div class="collapse show" id="filterSection">
            <div class="card-body pt-0">
              <!-- Filter Form -->
              <form method="GET" class="row g-3">
                <div class="col-md-2">
                  <label class="form-label small fw-semibold">From Date</label>
                  <input type="date" class="form-control form-control-sm" name="from_date" value="{{ $fromDate }}">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-semibold">To Date</label>
                  <input type="date" class="form-control form-control-sm" name="to_date" value="{{ $toDate }}">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-semibold">User</label>
                  <select class="form-select form-select-sm" name="user_id">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                      <option value="{{ $user->id }}" {{ $selectedUser == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-semibold">Location</label>
                  <select class="form-select form-select-sm" name="location_id">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                      <option value="{{ $location->id }}" {{ $selectedLocation == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Search</label>
                  <input type="text" class="form-control form-control-sm" name="search" placeholder="User name or email..." value="{{ $search }}">
                </div>
                <div class="col-12" style="display: flex; gap: 10px;">
                  <button type="submit" class="btn btn-primary btn-sm">🔍 Filter</button>
                  <a href="{{ route('reports.register-report') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Main Report Card -->
        <div class="card border">
          <div class="card-body">

            <!-- Toolbar -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3" style="border-bottom: 1px solid #e0e0e0; padding-bottom: 15px;">
              <div class="d-flex align-items-center gap-2 small">
                <span>Show</span>
                <form method="GET" class="d-inline" id="perPageForm">
                  <select class="form-select form-select-sm" name="per_page" style="width:auto" onchange="document.getElementById('perPageForm').submit()">
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                  </select>
                  <input type="hidden" name="from_date" value="{{ $fromDate }}">
                  <input type="hidden" name="to_date" value="{{ $toDate }}">
                  <input type="hidden" name="user_id" value="{{ $selectedUser }}">
                  <input type="hidden" name="location_id" value="{{ $selectedLocation }}">
                  <input type="hidden" name="search" value="{{ $search }}">
                </form>
                <span>entries</span>
              </div>
              
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <a href="{{ route('reports.register-report.export', ['from_date' => $fromDate, 'to_date' => $toDate, 'user_id' => $selectedUser, 'location_id' => $selectedLocation, 'search' => $search]) }}" class="btn btn-sm btn-outline-secondary" title="Export to CSV">
                  <i class="bi bi-download"></i> Export CSV
                </a>
                <button class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()" title="Export to Excel">
                  <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Print">
                  <i class="bi bi-printer"></i> Print
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleColumnVisibility()" title="Column visibility">
                  <i class="bi bi-eye"></i> Column visibility
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="exportToPDF()" title="Export to PDF">
                  <i class="bi bi-file-pdf"></i> Export PDF
                </button>
              </div>

              <div style="min-width: 250px;">
                <input type="text" class="form-control form-control-sm" id="tableSearch" placeholder="Search..." style="max-width: 250px;">
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover table-sm small mb-0" id="registersTable">
                <thead class="table-light">
                  <tr>
                    <th class="text-nowrap">Open Time</th>
                    <th class="text-nowrap">Close Time</th>
                    <th class="text-nowrap">Location</th>
                    <th class="text-nowrap">User</th>
                    <th class="text-nowrap text-end">Total Card Slips</th>
                    <th class="text-nowrap text-end">Total cheques</th>
                    <th class="text-nowrap text-end">Total Cash</th>
                    <th class="text-nowrap text-end">Total bank transfer</th>
                    <th class="text-nowrap text-end">Total advance payment</th>
                    <th class="text-nowrap text-end">National Bank</th>
                    <th class="text-nowrap text-end">Standard Bank</th>
                    <th class="text-nowrap text-end">Airtel Money</th>
                    <th class="text-nowrap text-end">TNM Mpamba</th>
                  </tr>
                </thead>
                <tbody id="tableBody">
                  @forelse($registersData as $register)
                    <tr class="registerRow">
                      <td class="text-nowrap">{{ $register['opened_at']->format('d-m-Y h:i A') }}</td>
                      <td class="text-nowrap">{{ $register['closed_at'] ? $register['closed_at']->format('d-m-Y h:i A') : 'Open' }}</td>
                      <td class="text-nowrap">{{ $register['location_name'] }}</td>
                      <td>
                        <div style="line-height: 1.4;">
                          <strong>{{ $register['user_name'] }}</strong><br>
                          <span class="small text-muted">{{ $register['user_email'] }}</span>
                        </div>
                      </td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_card'], 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_cheques'], 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_cash'], 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_bank_transfer'], 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_advance'], 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_national_bank'] ?? 0, 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_standard_bank'] ?? 0, 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_airtel_money'] ?? 0, 2) }}</td>
                      <td class="text-end text-nowrap">{{ $currency }} {{ number_format($register['total_mpamba'] ?? 0, 2) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="13" class="text-center py-4 text-muted">No register data found for the selected filters.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            @if($registers->hasPages())
              <div class="d-flex align-items-center justify-content-between mt-3 pt-3" style="border-top: 1px solid #e0e0e0;">
                <div class="small text-muted">
                  Showing {{ $registers->firstItem() }} to {{ $registers->lastItem() }} of {{ $registers->total() }} results
                </div>
                <nav>
                  <ul class="pagination pagination-sm mb-0">
                    {{-- Previous Page Link --}}
                    @if ($registers->onFirstPage())
                      <li class="page-item disabled"><span class="page-link">&larr; Previous</span></li>
                    @else
                      <li class="page-item">
                        <a class="page-link" href="{{ $registers->previousPageUrl() }}&from_date={{ $fromDate }}&to_date={{ $toDate }}&user_id={{ $selectedUser }}&location_id={{ $selectedLocation }}&search={{ $search }}&per_page={{ $perPage }}">
                          &larr; Previous
                        </a>
                      </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($registers->getUrlRange(1, $registers->lastPage()) as $page => $url)
                      <li class="page-item {{ $page == $registers->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $url }}&from_date={{ $fromDate }}&to_date={{ $toDate }}&user_id={{ $selectedUser }}&location_id={{ $selectedLocation }}&search={{ $search }}&per_page={{ $perPage }}">
                          {{ $page }}
                        </a>
                      </li>
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($registers->hasMorePages())
                      <li class="page-item">
                        <a class="page-link" href="{{ $registers->nextPageUrl() }}&from_date={{ $fromDate }}&to_date={{ $toDate }}&user_id={{ $selectedUser }}&location_id={{ $selectedLocation }}&search={{ $search }}&per_page={{ $perPage }}">
                          Next &rarr;
                        </a>
                      </li>
                    @else
                      <li class="page-item disabled"><span class="page-link">Next &rarr;</span></li>
                    @endif
                  </ul>
                </nav>
              </div>
            @endif

          </div>
        </div>

      </main>
      <footer class="app-footer">LightSuite - V6.4 | Copyright © 2026 All rights reserved.</footer>
    </div>
  </div>

  <!-- Libraries for Export -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

  <script>
    // Table Search Filter
    document.getElementById('tableSearch').addEventListener('keyup', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const rows = document.querySelectorAll('#tableBody .registerRow');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });

    // Export to Excel
    function exportToExcel() {
      const table = document.getElementById('registersTable');
      const ws = XLSX.utils.table_to_sheet(table);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Register Report");
      XLSX.writeFile(wb, 'register-report.xlsx');
    }

    // Export to PDF
    function exportToPDF() {
      const element = document.getElementById('registersTable');
      const opt = {
        margin: 10,
        filename: 'register-report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: 'landscape', unit: 'mm', format: 'a4' }
      };
      html2pdf().set(opt).from(element).save();
    }

    // Toggle Column Visibility
    function toggleColumnVisibility() {
      const modal = document.createElement('div');
      modal.className = 'modal fade';
      modal.id = 'columnModal';
      modal.tabIndex = '-1';
      
      const columns = [
        'Open Time', 'Close Time', 'Location', 'User', 'Total Card Slips',
        'Total cheques', 'Total Cash', 'Total bank transfer', 'Total advance payment',
        'National Bank', 'Standard Bank', 'Airtel Money', 'TNM Mpamba'
      ];
      
      let checkboxes = '';
      columns.forEach((col, idx) => {
        checkboxes += `<div class="form-check">
          <input class="form-check-input" type="checkbox" id="col${idx}" checked onchange="toggleColumn(${idx})">
          <label class="form-check-label" for="col${idx}">${col}</label>
        </div>`;
      });
      
      modal.innerHTML = `<div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Column Visibility</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            ${checkboxes}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>`;
      
      document.body.appendChild(modal);
      new bootstrap.Modal(modal).show();
    }

    function toggleColumn(index) {
      const table = document.getElementById('registersTable');
      const headers = table.querySelectorAll('th');
      const rows = table.querySelectorAll('tbody tr');
      
      headers[index].style.display = headers[index].style.display === 'none' ? '' : 'none';
      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells[index]) {
          cells[index].style.display = cells[index].style.display === 'none' ? '' : 'none';
        }
      });
    }
  </script>

@endsection
