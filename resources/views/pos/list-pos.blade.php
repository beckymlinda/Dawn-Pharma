@extends('layouts.app')

@section('content')

  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">

        <h1 class="h4 fw-bold mb-4">POS</h1>
        <div class="card border mb-4"><div class="card-body py-2"><span class="text-primary fw-semibold" style="cursor:pointer">🔽 Filters</span></div></div>

        <div class="card border">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h2 class="h5 fw-bold mb-0">List POS</h2>
              <a href="{{ route('pos.create') }}" class="btn btn-primary rounded-pill fw-semibold"><i class="bi bi-plus"></i> Add</a>
            </div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div class="d-flex align-items-center gap-2 small">
                <span>Show</span>
                <select class="form-select form-select-sm" style="width:auto"><option>25</option><option>50</option><option>100</option></select>
                <span>entries</span>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <button class="toolbar-btn">📋 Export CSV</button>
                <button class="toolbar-btn">📊 Export Excel</button>
                <button class="toolbar-btn">🖨 Print</button>
                <button class="toolbar-btn">📑 Column visibility</button>
                <button class="toolbar-btn">📄 Export PDF</button>
                <input type="text" class="form-control form-control-sm" style="width:180px" placeholder="Search ..." id="posSearch">
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-hover table-sm small mb-0">
                <thead>
                  <tr>
                    <th>Action</th><th>Date ↕</th><th>Invoice No. ↕</th><th>Customer name ↕</th><th>Contact Number ↕</th><th>Location ↕</th><th>Payment Status ↕</th><th>Payment Method ↕</th><th class="text-end">Total amount ↕</th><th class="text-end">Total paid ↕</th><th class="text-end">Sell Return Due ↕</th><th>Shipping Status ↕</th><th class="text-end">Total Item ↕</th>
                  </tr>
                </thead>
                <tbody id="pos-tbody">
                  @foreach($pos ?? [] as $p)
                    <tr>
                      <td class="position-relative">
                        <div class="d-inline-block">
                          <a href="{{ route('sales.show', $p->id) }}" class="btn btn-sm btn-link">View</a>
                        </div>
                      </td>
                      <td>{{ $p->date ? \Carbon\Carbon::parse($p->date)->format('d-m-Y') : '' }}</td>
                      <td>{{ $p->invoice_no }}</td>
                      <td>{{ $p->customer_name }}</td>
                      <td></td>
                      <td></td>
                      <td><span class="badge-paid">{{ $p->payment_status }}</span></td>
                      <td>{{ $p->payment_method }}</td>
                      <td class="text-end">MWK {{ number_format($p->total_amount, 2) }}</td>
                      <td class="text-end">MWK {{ number_format($p->total_paid, 2) }}</td>
                      <td class="text-end">MWK {{ number_format($p->return_due ?? 0, 2) }}</td>
                      <td>{{ $p->shipping_status }}</td>
                      <td class="text-end">{{ $p->total_items }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-3 small text-muted">
              <span id="pos-info">Showing 1 to {{ ($pos->count() ?? 0) }} of {{ ($pos->count() ?? 0) }} entries</span>
              <div>
                <button class="btn btn-outline-secondary btn-sm">Previous</button>
                <button class="btn btn-primary btn-sm">1</button>
                <button class="btn btn-outline-secondary btn-sm">Next</button>
              </div>
            </div>
          </div>
        </div>

      </main>
      <footer class="app-footer">Educ| Copyright © 2026 All rights reserved.</footer>
    </div>
  </div>

@endsection

