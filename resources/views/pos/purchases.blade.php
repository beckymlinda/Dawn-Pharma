@extends('layouts.app')
@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">
        <h1 class="h4 fw-bold mb-3">Purchases</h1>
        <div class="card border mb-3"><div class="card-body py-2"><button class="btn btn-link text-primary fw-semibold text-decoration-none p-0">🔽 Filters</button></div></div>
        <div class="card border">
          <div class="card-header d-flex align-items-center justify-content-between bg-white">
            <h2 class="h6 fw-semibold mb-0">All Purchases</h2>
            <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-plus"></i> Add</a>
          </div>
          <div class="card-body border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2 small">
                <span>Show</span><select class="form-select form-select-sm" style="width:auto"><option>25</option></select><span>entries</span>
                <button class="toolbar-btn">📋 Export CSV</button><button class="toolbar-btn">📊 Export Excel</button><button class="toolbar-btn">🖨 Print</button><button class="toolbar-btn">👁 Column visibility</button><button class="toolbar-btn">📄 Export PDF</button>
              </div>
              <input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Search ...">
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-sm small mb-0">
              <thead class="table-light">
                <tr><th>Action</th><th>Date ⇅</th><th>Reference No ⇅</th><th>Location ⇅</th><th>Supplier ⇅</th><th>Purchase Status ⇅</th><th>Payment Status ⇅</th><th class="text-end">Grand Total ⇅</th><th class="text-end">Payment due ℹ</th><th>Added By ⇅</th></tr>
              </thead>
              <tbody>
                @forelse($purchases as $purchase)
                  <tr>
                    <td>
                      <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                          <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <li><a class="dropdown-item" href="{{ route('purchases.show', $purchase) }}"><i class="bi bi-eye me-2"></i>View</a></li>
                          <li><a class="dropdown-item" href="{{ route('purchases.edit', $purchase) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                          <li><hr class="dropdown-divider"></li>
                          <li><a class="dropdown-item text-danger" href="#" onclick="deletePurchase({{ $purchase->id }})"><i class="bi bi-trash me-2"></i>Delete</a></li>
                        </ul>
                      </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('M d, Y') }}</td>
                    <td>{{ $purchase->reference_no }}</td>
                    <td>{{ $purchase->business_location ?? setting('system_name') }}</td>
                    <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                    <td><span class="badge bg-{{ getStatusColor($purchase->status) }}">{{ ucfirst($purchase->status) }}</span></td>
                    <td><span class="badge bg-{{ getPaymentStatusColor($purchase->payment_status) }}">{{ ucfirst($purchase->payment_status) }}</span></td>
                    <td class="text-end">MWK {{ number_format($purchase->grand_total, 2) }}</td>
                    <td class="text-end">
                      @if($purchase->payment_status == 'due')
                        MWK {{ number_format($purchase->grand_total, 2) }}
                      @elseif($purchase->payment_status == 'partial')
                        MWK {{ number_format($purchase->grand_total - ($purchase->paid_amount ?? 0), 2) }}
                      @else
                        MWK 0.00
                      @endif
                    </td>
                    <td>{{ $purchase->user->name ?? 'System' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="10" class="text-center text-muted py-5">No data available in table</td></tr>
                @endforelse
                <tr class="table-light">
                  <td colspan="4"></td>
                  <td class="text-end fw-semibold" colspan="2">Total:</td>
                  <td></td>
                  <td class="text-end fw-semibold">MWK {{ number_format($purchases->sum('grand_total'), 2) }}</td>
                  <td class="text-end small text-muted" colspan="2">
                    Purchase Due - MWK {{ number_format($purchases->where('payment_status', 'due')->sum('grand_total'), 2) }}<br>
                    Purchase Return - MWK 0.00
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer bg-white d-flex align-items-center justify-content-between small text-muted">
            <span>Showing {{ $purchases->firstItem() ?? 0 }} to {{ $purchases->lastItem() ?? 0 }} of {{ $purchases->total() }} entries</span>
            <div>{!! $purchases->links() !!}</div>
          </div>
        </div>
      </main>
      <footer class="app-footer">LightSuite - V6.4 | Copyright © 2026 All rights reserved.</footer>
    </div>
  </div>

@php
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'ordered' => 'info',
        'received' => 'success',
        'completed' => 'primary'
    ];
    return $colors[$status] ?? 'secondary';
}

function getPaymentStatusColor($status) {
    $colors = [
        'due' => 'danger',
        'partial' => 'warning',
        'paid' => 'success'
    ];
    return $colors[$status] ?? 'secondary';
}
@endphp

<script>
function deletePurchase(id) {
    if (confirm('Are you sure you want to delete this purchase?')) {
        // Implement delete functionality
        console.log('Delete purchase:', id);
    }
}
</script>
@endsection
