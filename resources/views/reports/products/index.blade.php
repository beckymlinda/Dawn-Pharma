@extends('layouts.app')

@section('title', 'Product Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Product Report</h4>
            <p class="text-muted small mb-0">Select a product to see its sales, purchases and stock history.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.products.index') }}" class="row g-2 mb-3" id="productSearchForm">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="search" id="productSearch" class="form-control" value="{{ request('search') }}"
                       placeholder="Search by product name, SKU or category..." autocomplete="off" autofocus>
            </div>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Search
            </button>
            <button type="button" class="btn btn-outline-secondary" id="clearSearch">
                <i class="bi bi-x-circle me-1"></i> Clear
            </button>
        </div>
    </form>

    <div class="card" id="productResults">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-end">Selling Price</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Total Sold</th>
                            <th class="text-end">Total Purchased</th>
                            <th>Last Sold</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr style="cursor: pointer;" onclick="window.location='{{ route('reports.products.show', $product) }}'">
                                <td>
                                    <strong>{{ $product->name }}</strong><br>
                                    <small class="text-muted">SKU: {{ $product->sku }}</small>
                                </td>
                                <td>{{ $product->category->name ?? '—' }}</td>
                                <td class="text-end">{{ formatCurrency($product->selling_price) }}</td>
                                <td class="text-end">
                                    <span class="fw-bold {{ ($product->current_stock ?? 0) <= ($product->alert_quantity ?? 0) ? 'text-danger' : '' }}">
                                        {{ number_format($product->current_stock ?? 0) }}
                                    </span>
                                    <small class="text-muted">{{ $product->unit->short_name ?? '' }}</small>
                                </td>
                                <td class="text-end">{{ number_format($product->total_sold ?? 0) }}</td>
                                <td class="text-end">{{ number_format($product->total_purchased ?? 0) }}</td>
                                <td>
                                    {{ $product->last_sold_at ? \Carbon\Carbon::parse($product->last_sold_at)->format('M d, Y') : '—' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('reports.products.show', $product) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-box-seam fs-1 d-block mb-2"></i>
                                    No products found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
                </div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const form = document.getElementById('productSearchForm');
    const input = document.getElementById('productSearch');
    let timer, controller;

    // Fetch the filtered list and swap only the results card, so the input keeps focus
    function search() {
        const url = new URL(form.action);
        const term = input.value.trim();
        if (term) url.searchParams.set('search', term);

        if (controller) controller.abort();
        controller = new AbortController();

        document.getElementById('productResults').style.opacity = '0.5';
        fetch(url, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const fresh = new DOMParser().parseFromString(html, 'text/html').getElementById('productResults');
                if (fresh) document.getElementById('productResults').replaceWith(fresh);
                history.replaceState(null, '', url);
            })
            .catch(err => { if (err.name !== 'AbortError') form.submit(); });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(search, 300);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearTimeout(timer);
        search();
    });

    document.getElementById('clearSearch').addEventListener('click', function () {
        input.value = '';
        search();
        input.focus();
    });

    // Put the cursor at the end of any existing search text
    input.setSelectionRange(input.value.length, input.value.length);
})();
</script>
@endsection
