@extends('layouts.app')
@section('content')
  <div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
      <header class="app-topbar" id="app-topbar"></header>
      <main class="app-content">
        <h1 class="h4 fw-bold mb-4">Stock Report</h1>
        <div class="card border mb-3"><div class="card-body py-2"><span class="text-primary fw-semibold">🔽 Filters</span></div></div>

        <!-- Summary -->
        <div class="card border mb-3">
          <div class="card-body">
            <div class="row">
              <div class="col-md-3"><p class="text-muted small mb-1">Closing stock (By purchase price)</p><p class="h5 fw-bold">MWK {{ number_format($totalPurchaseValue, 2) }}</p></div>
              <div class="col-md-3"><p class="text-muted small mb-1">Closing stock (By sale price)</p><p class="h5 fw-bold">MWK {{ number_format($totalSaleValue, 2) }}</p></div>
              <div class="col-md-3"><p class="text-muted small mb-1">Potential profit</p><p class="h5 fw-bold">MWK {{ number_format($potentialProfit, 2) }}</p></div>
              <div class="col-md-3"><p class="text-muted small mb-1">Profit Margin %</p><p class="h5 fw-bold">{{ number_format($profitMargin, 2) }}</p></div>
            </div>
          </div>
        </div>

        <div class="card border">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div class="d-flex align-items-center gap-2 small text-muted">
                <span>Show</span>
                <form method="GET" class="d-inline" id="perPageForm">
                  <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="document.getElementById('perPageForm').submit()">
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                  </select>
                </form>
                <span>entries</span>
              </div>
              <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('stock.report.export.csv') }}" class="btn btn-sm btn-outline-secondary">📄 Export CSV</a>
                <a href="{{ route('stock.report.export.excel') }}" class="btn btn-sm btn-outline-secondary">📊 Export Excel</a>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">🖨 Print</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleColumnVisibility()">📋 Column visibility</button>
                <a href="{{ route('stock.report.export.pdf') }}" class="btn btn-sm btn-outline-secondary">📑 Export PDF</a>
              </div>
              <input type="text" class="form-control form-control-sm" style="width:200px" placeholder="Search ...">
            </div>
            <div class="table-responsive">
              <table class="table table-hover table-sm small mb-0">
                <thead><tr><th>Action</th><th>SKU</th><th>Product</th><th>Variation</th><th>Category</th><th>Location</th><th>Unit Selling Price</th><th>Current stock</th><th>Current Stock Value (By purchase price)</th><th>Current Stock Value (By sale price)</th></tr></thead>
                <tbody>
                  @forelse ($products as $product)
                    <tr>
                      <td><a href="{{ route('stock.history.product', $product->id) }}" class="btn btn-outline-primary btn-sm" style="font-size:11px" target="_blank">🔄 Product stock history</a></td>
                      <td>{{ $product->sku }}</td>
                      <td>{{ $product->name }}</td>
                      <td>{{ $product->product_type ?? '' }}</td>
                      <td>{{ $product->category->name ?? 'N/A' }}</td>
                      <td>{{ $product->business_location }}</td>
                      <td>MWK {{ number_format($product->selling_price, 2) }}</td>
                      <td>{{ number_format($product->current_stock, 2) }} {{ $product->unit->name ?? 'Each' }}</td>
                      <td>MWK {{ number_format($product->current_stock * $product->purchase_price, 2) }}</td>
                      <td>MWK {{ number_format($product->current_stock * $product->selling_price, 2) }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="10" class="text-center text-muted py-4">No products found</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
              <div class="d-flex align-items-center justify-content-between mt-3 pt-3 border-top">
                <div class="small text-muted">
                  Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} results
                </div>
                <div>
                  {!! $products->appends(request()->query())->links() !!}
                </div>
              </div>
            @endif
          </div>
        </div>
      </main>
      <footer class="app-footer">LightSuite - V6.4 | Copyright 2026 All rights reserved.</footer>
    </div>
  </div>

  <script>
    function toggleColumnVisibility() {
      alert('Column visibility feature coming soon!');
    }
  </script>
@endsection