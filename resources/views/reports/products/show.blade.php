@extends('layouts.app')

@section('title', 'Product Report - ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
            <p class="text-muted small mb-0">
                SKU: {{ $product->sku }}
                @if($product->category) · {{ $product->category->name }} @endif
                @if($product->brand) · {{ $product->brand->name }} @endif
                @if($product->supplier) · Supplier: {{ $product->supplier->name }} @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <a href="{{ route('reports.products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Products
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Current Stock</div>
                <h4 class="fw-bold mb-0">{{ number_format($product->current_stock ?? 0) }}</h4>
                <small class="text-muted">Selling at {{ formatCurrency($product->selling_price) }}</small>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Sold</div>
                <h4 class="fw-bold mb-0 text-success">{{ number_format($salesTotals->qty) }}</h4>
                <small class="text-muted">{{ $salesTotals->count }} sale(s) · {{ formatCurrency($salesTotals->amount) }}</small>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Purchased</div>
                <h4 class="fw-bold mb-0 text-primary">{{ rtrim(rtrim(number_format($purchaseTotals->qty, 2), '0'), '.') }}</h4>
                <small class="text-muted">{{ $purchaseTotals->count }} purchase(s) · {{ formatCurrency($purchaseTotals->amount) }}</small>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="text-muted small fw-bold text-uppercase">Expiry Date</div>
                <h4 class="fw-bold mb-0">{{ $product->expiry_date ? \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') : '—' }}</h4>
                <small class="text-muted">Alert at {{ number_format($product->alert_quantity ?? 0) }}</small>
            </div></div>
        </div>
    </div>

    <!-- Date filter (applies to all tabs) -->
    <form method="GET" action="{{ route('reports.products.show', $product) }}" class="row g-2 align-items-end mb-3" id="filterForm">
        <input type="hidden" name="tab" id="tabInput" value="{{ $activeTab }}">
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
        </div>
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filter</button>
            @if(request('from') || request('to'))
                <a href="{{ route('reports.products.show', $product) }}?tab={{ $activeTab }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-header bg-white border-bottom-0 pb-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                @foreach([
                    'sales' => ['bi-cart-check', 'Sales', $sales->total()],
                    'purchases' => ['bi-truck', 'Purchases', $purchases->total()],
                    'adjustments' => ['bi-arrow-left-right', 'Stock Adjustments', $adjustments->total()],
                    'movements' => ['bi-clock-history', 'Stock Movements', $movements->total()],
                    'daily' => ['bi-calendar3', 'Daily Stock', count($dailyStock)],
                    'activity' => ['bi-person-lines-fill', 'Activity', $activities->total()],
                ] as $key => [$icon, $label, $count])
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === $key ? 'active' : '' }}" data-bs-toggle="tab"
                                data-bs-target="#tab-{{ $key }}" data-tab="{{ $key }}" type="button" role="tab">
                            <i class="bi {{ $icon }} me-1"></i> {{ $label }}
                            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $count }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body tab-content">
            <!-- Sales -->
            <div class="tab-pane fade {{ $activeTab === 'sales' ? 'show active' : '' }}" id="tab-sales" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Payment</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Subtotal</th>
                                <th>Sold By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sales->getCollection()->groupBy(fn ($i) => \Carbon\Carbon::parse($i->sold_at)->toDateString()) as $day => $items)
                                <tr class="table-secondary">
                                    <td colspan="4" class="fw-bold">{{ \Carbon\Carbon::parse($day)->format('l, M d, Y') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($salesPerDay[$day]->qty ?? 0) }}</td>
                                    <td></td>
                                    <td class="text-end fw-bold">{{ formatCurrency($salesPerDay[$day]->amount ?? 0) }}</td>
                                    <td class="small text-muted">day total</td>
                                </tr>
                                @foreach($items as $item)
                                    @php($seller = $item->sale?->user ?? $item->sale?->register?->user)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($item->sold_at)->format('H:i') }}</td>
                                        <td>
                                            @if($item->sale)
                                                <a href="{{ route('sales.show', $item->sale) }}">{{ $item->sale->invoice_no }}</a>
                                            @endif
                                        </td>
                                        <td>{{ $item->sale->customer_name ?? 'Walk-In Customer' }}</td>
                                        <td class="text-capitalize">{{ str_replace('_', ' ', $item->sale->payment_method ?? '—') }}</td>
                                        <td class="text-end">{{ number_format($item->quantity) }}</td>
                                        <td class="text-end">{{ formatCurrency($item->price) }}</td>
                                        <td class="text-end">{{ formatCurrency($item->subtotal) }}</td>
                                        <td>
                                            <i class="bi bi-person me-1"></i>{{ $seller->name ?? 'Unknown' }}
                                            @if($item->sale?->is_edited)
                                                <br><small class="text-warning-emphasis">
                                                    Edited by {{ $item->sale->editor->name ?? 'Unknown' }}
                                                    {{ $item->sale->edited_at ? 'on ' . $item->sale->edited_at->format('M d, H:i') : '' }}
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No sales found for this product</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $sales->links() }}
            </div>

            <!-- Purchases (stock-in adjustments and first stock are listed as purchases) -->
            <div class="tab-pane fade {{ $activeTab === 'purchases' ? 'show active' : '' }}" id="tab-purchases" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Line Total</th>
                                <th>Expiry</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases->getCollection()->groupBy(fn ($r) => $r->at->toDateString()) as $day => $rows)
                                <tr class="table-secondary">
                                    <td colspan="3" class="fw-bold">{{ \Carbon\Carbon::parse($day)->format('l, M d, Y') }}</td>
                                    <td class="text-end fw-bold">{{ rtrim(rtrim(number_format($purchasesPerDay[$day]->qty ?? 0, 2), '0'), '.') }}</td>
                                    <td></td>
                                    <td class="text-end fw-bold">{{ formatCurrency($purchasesPerDay[$day]->amount ?? 0) }}</td>
                                    <td colspan="2" class="small text-muted">day total</td>
                                </tr>
                                @foreach($rows as $row)
                                    <tr>
                                        <td>
                                            @if($row->purchase)
                                                <a href="{{ route('purchases.show', $row->purchase) }}">{{ $row->reference }}</a>
                                            @else
                                                {{ $row->kind === 'adjustment' ? 'Stock in' : 'First stock' }}
                                                @if($row->reference)<br><small class="text-muted">{{ $row->reference }}</small>@endif
                                            @endif
                                        </td>
                                        <td>{{ $row->supplier ?? '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $row->status === 'received' ? 'success' : 'warning' }} text-capitalize">
                                                {{ $row->status ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ rtrim(rtrim(number_format($row->quantity, 2), '0'), '.') }}</td>
                                        <td class="text-end">{{ formatCurrency($row->unit_cost) }}</td>
                                        <td class="text-end">{{ formatCurrency($row->line_total) }}</td>
                                        <td>{{ $row->expiry ? \Carbon\Carbon::parse($row->expiry)->format('M d, Y') : '—' }}</td>
                                        <td><i class="bi bi-person me-1"></i>{{ $row->by ?? 'Unknown' }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No purchases found for this product</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $purchases->links() }}
            </div>

            <!-- Stock Adjustments -->
            <div class="tab-pane fade {{ $activeTab === 'adjustments' ? 'show active' : '' }}" id="tab-adjustments" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th class="text-end">Qty Change</th>
                                <th class="text-end">Previous → New</th>
                                <th>Price Change</th>
                                <th>Reason</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adjustment)
                                <tr>
                                    <td>{{ $adjustment->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $adjustment->adjustment_type === 'add' ? 'success' : 'danger' }}">
                                            {{ $adjustment->adjustment_type_text }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold {{ $adjustment->adjustment_type === 'add' ? 'text-success' : 'text-danger' }}">
                                        {{ $adjustment->quantity_change }}
                                    </td>
                                    <td class="text-end">{{ number_format($adjustment->previous_quantity) }} → {{ number_format($adjustment->new_quantity) }}</td>
                                    <td>
                                        @if($adjustment->previous_price != $adjustment->new_price)
                                            {{ formatCurrency($adjustment->previous_price) }} → {{ formatCurrency($adjustment->new_price) }}
                                        @else
                                            <span class="text-muted">No change</span>
                                        @endif
                                    </td>
                                    <td>{{ $adjustment->reason }}</td>
                                    <td><i class="bi bi-person me-1"></i>{{ $adjustment->user->name ?? 'Unknown' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No stock adjustments found for this product</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $adjustments->links() }}
            </div>

            <!-- Stock Movements -->
            <div class="tab-pane fade {{ $activeTab === 'movements' ? 'show active' : '' }}" id="tab-movements" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th class="text-end">Qty Change</th>
                                <th class="text-end">Stock After</th>
                                <th>Reference</th>
                                <th>Customer / Supplier</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('M d, Y H:i') }}</td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis">{{ $movement->type }}</span></td>
                                    <td class="text-end fw-bold {{ $movement->quantity_change >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity_change >= 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}
                                    </td>
                                    <td class="text-end">{{ number_format($movement->new_quantity) }}</td>
                                    <td>{{ $movement->reference_no ?? '—' }}</td>
                                    <td>{{ $movement->customer_supplier ?? '—' }}</td>
                                    <td>
                                        @if($movement->user)
                                            <i class="bi bi-person me-1"></i>{{ $movement->user->name }}
                                        @else
                                            <span class="text-muted">Not recorded</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No stock movements found for this product</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $movements->links() }}
            </div>

            <!-- Daily stock -->
            @php($qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.'))
            <div class="tab-pane fade {{ $activeTab === 'daily' ? 'show active' : '' }}" id="tab-daily" role="tabpanel">
                <p class="small text-muted mb-2">
                    Stock at the start (opening) and end (closing) of each day.
                    {{ request('from') || request('to') ? 'Days in the selected period' : 'The last 30 days' }}, newest first; use From / To above for other days.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Opening Stock</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Sold</th>
                                <th class="text-end">Adjusted</th>
                                <th class="text-end">Closing Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyStock as $day => $row)
                                @php($quiet = !$row['received'] && !$row['sold'] && !$row['adjusted'])
                                <tr class="{{ $quiet ? 'text-muted' : '' }}">
                                    <td>{{ \Carbon\Carbon::parse($day)->format('D, M d, Y') }}{{ \Carbon\Carbon::parse($day)->isToday() ? ' (today)' : '' }}</td>
                                    <td class="text-end">{{ $qty($row['opening']) }}</td>
                                    <td class="text-end {{ $row['received'] ? 'text-success' : '' }}">{{ $row['received'] ? '+' . $qty($row['received']) : '—' }}</td>
                                    <td class="text-end {{ $row['sold'] ? 'text-danger' : '' }}">{{ $row['sold'] ? '−' . $qty($row['sold']) : '—' }}</td>
                                    <td class="text-end">{{ $row['adjusted'] ? ($row['adjusted'] > 0 ? '+' : '−') . $qty(abs($row['adjusted'])) : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ $qty($row['closing']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No days in the selected period</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Activity -->
            <div class="tab-pane fade {{ $activeTab === 'activity' ? 'show active' : '' }}" id="tab-activity" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>By</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                                <tr>
                                    <td>{{ $activity->created_at->format('M d, Y H:i') }}</td>
                                    <td><span class="badge bg-secondary text-capitalize">{{ $activity->action }}</span></td>
                                    <td>{{ $activity->description }}</td>
                                    <td><i class="bi bi-person me-1"></i>{{ $activity->user->name ?? 'Unknown' }}</td>
                                    <td class="text-muted small">{{ $activity->ip_address }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No activity recorded for this product</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $activities->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Keep the selected tab in the URL so filters, refresh and back keep you on it
document.querySelectorAll('[data-bs-toggle="tab"][data-tab]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function () {
        const tab = btn.dataset.tab;
        document.getElementById('tabInput').value = tab;
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        history.replaceState(null, '', url);
    });
});
</script>
@endsection
