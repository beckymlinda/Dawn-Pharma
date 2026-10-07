@extends('layouts.app')

@section('title', 'Edit Sale ' . $sale->invoice_no)

@php
    $shiftClosed = (bool) optional($sale->register)->closed_at;
    $soldBy = $sale->user?->name ?? $sale->register?->user?->name ?? 'Unknown';
@endphp

@section('content')
<style>
    .se-card { border: 0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 4px 12px rgba(0,0,0,.04); }
    .se-meta dt { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; font-weight: 600; }
    .se-meta dd { margin: 0; font-weight: 600; }
    .se-items th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; font-weight: 600; white-space: nowrap; }
    .se-items td { vertical-align: middle; }
    .se-qty { display: inline-flex; align-items: center; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
    .se-qty button { border: 0; background: #f8f9fa; width: 32px; height: 34px; font-size: 1.1rem; line-height: 1; }
    .se-qty button:hover { background: #e9ecef; }
    .se-qty input { width: 56px; height: 34px; border: 0; text-align: center; font-weight: 600; -moz-appearance: textfield; }
    .se-qty input::-webkit-outer-spin-button, .se-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .se-row-removed td { background: #fff5f5; }
    .se-row-removed .se-name { text-decoration: line-through; color: #9ca3af; }
    .se-row-new td { background: #f0fdf4; }
    .se-change { font-size: .78rem; white-space: nowrap; }
    .se-search-results { position: absolute; z-index: 20; left: 0; right: 0; top: 100%; max-height: 280px; overflow-y: auto; background: #fff; border: 1px solid #dee2e6; border-radius: 0 0 8px 8px; box-shadow: 0 8px 20px rgba(0,0,0,.08); }
    .se-search-results button { display: flex; justify-content: space-between; width: 100%; border: 0; background: none; text-align: left; padding: .5rem .75rem; }
    .se-search-results button:hover, .se-search-results button.active { background: #f1f5f9; }
    .se-sticky { position: sticky; top: 1rem; }
    .se-diff { border-radius: 10px; padding: .75rem 1rem; }
</style>

<div class="container-fluid py-3" style="max-width: 1280px;">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1">Edit Sale <span class="text-primary">{{ $sale->invoice_no }}</span></h4>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge {{ $shiftClosed ? 'bg-warning text-dark' : 'bg-success' }}">
                    <i class="bi {{ $shiftClosed ? 'bi-lock' : 'bi-unlock' }} me-1"></i>{{ $shiftClosed ? 'Shift closed' : 'Shift open' }}
                </span>
                @if($sale->is_edited)
                    <span class="badge bg-secondary"><i class="bi bi-pencil me-1"></i>Edited before</span>
                @endif
            </div>
        </div>
        <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-1"></i> Cancel
        </a>
    </div>

    {{-- Errors: one clear box --}}
    @if($errors->any() || session('error'))
        <div class="alert alert-danger d-flex gap-3 align-items-start shadow-sm" id="serverErrors">
            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
            <div>
                <div class="fw-bold mb-1">The sale was not saved</div>
                <ul class="mb-0 ps-3">
                    @if(session('error'))<li>{{ session('error') }}</li>@endif
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Sale details --}}
    <div class="card se-card mb-3">
        <div class="card-body">
            <dl class="row se-meta mb-0 g-3">
                <div class="col-6 col-md-3"><dt>Sold on</dt><dd>{{ $sale->created_at->format('d M Y, H:i') }}</dd></div>
                <div class="col-6 col-md-3"><dt>Sold by</dt><dd>{{ $soldBy }}</dd></div>
                <div class="col-6 col-md-3"><dt>Customer</dt><dd>{{ $sale->customer_name ?: 'Walk-In Customer' }}</dd></div>
                <div class="col-6 col-md-3"><dt>Original total</dt><dd>MWK {{ number_format($original->sum(fn ($i) => $i['quantity'] * $i['price'])) }}</dd></div>
            </dl>
            @if($shiftClosed)
                <div class="alert alert-warning small mb-0 mt-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This shift was closed on {{ $sale->register->closed_at->format('d M Y H:i') }}. Changing this sale changes that shift's totals and is flagged in Loss Prevention.
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('sales.update', $sale->id) }}" id="editSaleForm" novalidate>
        @csrf
        @method('PUT')
        <div id="hiddenFields"></div>

        <div class="row g-3">
            {{-- Items --}}
            <div class="col-lg-8">
                <div class="card se-card">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold mb-0"><i class="bi bi-bag me-2 text-primary"></i>Items</h6>
                            <small class="text-muted">
                                @if($isSupervisor)
                                    You can change quantities and prices.
                                @else
                                    <i class="bi bi-lock me-1"></i>Prices are fixed. Only a manager can change them.
                                @endif
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table se-items mb-2">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Unit price</th>
                                        <th class="text-center">Was</th>
                                        <th class="text-center">Now</th>
                                        <th class="text-end">Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows"></tbody>
                            </table>
                        </div>

                        {{-- Add a product --}}
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-plus-lg"></i></span>
                                <input type="text" id="productSearch" class="form-control" placeholder="Add a product: type a name or SKU" autocomplete="off">
                            </div>
                            <div class="se-search-results d-none" id="searchResults"></div>
                        </div>

                        {{-- What will happen to stock --}}
                        <div id="stockSummary" class="mt-3 small"></div>
                    </div>
                </div>
            </div>

            {{-- Summary, payment, reason, save --}}
            <div class="col-lg-4">
                <div class="se-sticky">
                    <div class="card se-card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between text-muted mb-1">
                                <span>Original total</span><span id="originalTotal"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-baseline mb-3">
                                <span class="fw-bold">New total</span><span class="fs-4 fw-bold" id="newTotal"></span>
                            </div>
                            <div class="se-diff" id="diffBox"></div>
                        </div>
                    </div>

                    <div class="card se-card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i class="bi bi-credit-card me-2 text-primary"></i>Payment</h6>
                                <button type="button" class="btn btn-link btn-sm p-0" id="addPayment"><i class="bi bi-plus"></i> Split payment</button>
                            </div>
                            <div id="paymentRows"></div>
                            <div class="small mt-2" id="paymentStatus"></div>
                        </div>
                    </div>

                    <div class="card se-card">
                        <div class="card-body">
                            <label for="editReason" class="form-label fw-bold">Reason for change <span class="text-danger">*</span></label>
                            <textarea id="editReason" class="form-control" rows="2" maxlength="255"
                                      placeholder="e.g. Customer returned 1 item">{{ old('edit_reason') }}</textarea>
                            <div class="form-text">Recorded with your name in Loss Prevention.</div>

                            <button type="submit" class="btn btn-success w-100 mt-3 py-2 fw-bold" id="saveBtn">
                                <i class="bi bi-check2-circle me-1"></i> Save changes
                            </button>
                            <ul class="small text-danger mt-2 mb-0 ps-3" id="blockers"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const PRODUCTS = @json($products);
    const ORIGINAL = @json($original);
    const METHODS = @json($methods);
    const CAN_EDIT_PRICE = @json($isSupervisor);

    const byId = Object.fromEntries(PRODUCTS.map(p => [p.id, p]));
    const originalQty = Object.fromEntries(ORIGINAL.map(i => [i.product_id, i.quantity]));
    const originalTotal = ORIGINAL.reduce((s, i) => s + i.quantity * i.price, 0);

    // ---- State: the single source of truth for what will be saved ----
    const state = {
        items: @json($items).map(i => ({ product_id: i.product_id, quantity: i.quantity, price: i.price })),
        payments: @json($payments).map(p => ({ method: p.method, amount: p.amount })),
        paymentsTouched: {{ old('payments') ? 'true' : 'false' }},
    };
    // Items that were on the sale but are now gone stay visible as "removed" so the change is obvious
    ORIGINAL.forEach(o => { if (!state.items.some(i => i.product_id === o.product_id)) state.items.push({ product_id: o.product_id, quantity: 0, price: o.price }); });

    const money = n => 'MWK ' + Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
    const num = n => Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const total = () => state.items.reduce((s, i) => s + i.quantity * i.price, 0);
    const paid = () => state.payments.reduce((s, p) => s + (Number(p.amount) || 0), 0);

    // ---- Items ----
    function renderItems() {
        const rows = state.items.map((item, idx) => {
            const p = byId[item.product_id] || { name: 'Product #' + item.product_id, sku: '', available: 0, list_price: item.price };
            const was = originalQty[item.product_id] || 0;
            const removed = item.quantity === 0;
            const isNew = !was;
            const over = item.quantity > p.available;
            const diff = item.quantity - was;
            let change = '';
            if (removed) change = `<span class="text-danger se-change"><i class="bi bi-arrow-return-left"></i> ${num(was)} back to stock</span>`;
            else if (diff < 0) change = `<span class="text-danger se-change"><i class="bi bi-arrow-return-left"></i> ${num(-diff)} back to stock</span>`;
            else if (diff > 0) change = `<span class="text-success se-change"><i class="bi bi-box-arrow-right"></i> ${num(diff)} more from stock</span>`;

            const priceCell = CAN_EDIT_PRICE && !removed
                ? `<input type="number" class="form-control form-control-sm text-end ms-auto" style="width:110px" min="0" step="1" value="${item.price}" data-price="${idx}">
                   ${item.price < p.list_price ? `<div class="text-warning se-change mt-1">below ${money(p.list_price)}</div>` : ''}`
                : money(item.price);

            const qtyCell = removed
                ? `<span class="text-muted">0</span>`
                : `<div class="se-qty">
                       <button type="button" data-dec="${idx}" aria-label="Decrease">−</button>
                       <input type="number" min="1" step="1" value="${item.quantity}" data-qty="${idx}" aria-label="Quantity">
                       <button type="button" data-inc="${idx}" aria-label="Increase">+</button>
                   </div>
                   ${over ? `<div class="text-danger se-change mt-1">only ${num(p.available)} available</div>` : ''}`;

            const action = removed
                ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-undo="${idx}" title="Put back"><i class="bi bi-arrow-counterclockwise"></i></button>`
                : `<button type="button" class="btn btn-sm btn-outline-danger" data-remove="${idx}" title="Remove"><i class="bi bi-trash"></i></button>`;

            return `<tr class="${removed ? 'se-row-removed' : (isNew ? 'se-row-new' : '')}">
                <td><div class="se-name fw-semibold">${esc(p.name)}</div>
                    <div class="small text-muted">${esc(p.sku || '')}${isNew ? ' · <span class="text-success fw-semibold">added</span>' : ''}</div>
                    ${change}</td>
                <td class="text-end">${priceCell}</td>
                <td class="text-center text-muted">${was ? num(was) : '–'}</td>
                <td class="text-center">${qtyCell}</td>
                <td class="text-end fw-semibold">${removed ? '<span class="text-muted">–</span>' : money(item.quantity * item.price)}</td>
                <td class="text-end">${action}</td>
            </tr>`;
        });
        document.getElementById('itemRows').innerHTML = rows.join('');
        renderStockSummary();
    }

    function renderStockSummary() {
        const lines = state.items.map(i => {
            const p = byId[i.product_id]; const was = originalQty[i.product_id] || 0; const d = i.quantity - was;
            if (!d) return null;
            return d < 0
                ? `<li><strong>${esc(p?.name)}</strong>: ${num(-d)} returned to stock</li>`
                : `<li><strong>${esc(p?.name)}</strong>: ${num(d)} taken from stock</li>`;
        }).filter(Boolean);
        document.getElementById('stockSummary').innerHTML = lines.length
            ? `<div class="bg-light rounded-3 p-3"><div class="fw-semibold mb-1"><i class="bi bi-box-seam me-1"></i>Stock after saving</div><ul class="mb-0 ps-3">${lines.join('')}</ul></div>`
            : '';
    }

    function setQty(idx, qty) {
        qty = Math.max(0, Math.round(Number(qty) || 0));
        state.items[idx].quantity = qty;
        // Drop rows that were only added in this edit and are now zero
        if (qty === 0 && !originalQty[state.items[idx].product_id]) state.items.splice(idx, 1);
        update();
    }

    document.getElementById('itemRows').addEventListener('click', e => {
        const t = e.target.closest('button'); if (!t) return;
        if (t.dataset.inc !== undefined) setQty(+t.dataset.inc, state.items[+t.dataset.inc].quantity + 1);
        if (t.dataset.dec !== undefined) { const i = +t.dataset.dec; if (state.items[i].quantity > 1) setQty(i, state.items[i].quantity - 1); }
        if (t.dataset.remove !== undefined) setQty(+t.dataset.remove, 0);
        if (t.dataset.undo !== undefined) setQty(+t.dataset.undo, originalQty[state.items[+t.dataset.undo].product_id] || 1);
    });
    document.getElementById('itemRows').addEventListener('change', e => {
        if (e.target.dataset.qty !== undefined) setQty(+e.target.dataset.qty, Math.max(1, Number(e.target.value) || 1));
        if (e.target.dataset.price !== undefined) { state.items[+e.target.dataset.price].price = Math.max(0, Math.round(Number(e.target.value) || 0)); update(); }
    });

    // ---- Product search ----
    const search = document.getElementById('productSearch');
    const results = document.getElementById('searchResults');
    let matches = [], active = 0;
    function renderResults() {
        const q = search.value.trim().toLowerCase();
        if (!q) { results.classList.add('d-none'); return; }
        matches = PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q)).slice(0, 12);
        active = 0;
        results.innerHTML = matches.length
            ? matches.map((p, i) => `<button type="button" data-add="${p.id}" class="${i === 0 ? 'active' : ''}">
                    <span><span class="fw-semibold">${esc(p.name)}</span> <span class="text-muted small">${esc(p.sku || '')}</span></span>
                    <span class="small text-muted">${money(p.price)} · ${num(p.available)} available</span></button>`).join('')
            : '<div class="p-3 text-muted small">No product found</div>';
        results.classList.remove('d-none');
    }
    function addProduct(id) {
        const p = byId[id]; if (!p) return;
        const existing = state.items.findIndex(i => i.product_id === p.id);
        if (existing >= 0) state.items[existing].quantity = Math.max(1, state.items[existing].quantity + 1);
        else state.items.push({ product_id: p.id, quantity: 1, price: p.price });
        search.value = ''; results.classList.add('d-none');
        update();
    }
    search.addEventListener('input', renderResults);
    search.addEventListener('keydown', e => {
        if (results.classList.contains('d-none') || !matches.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            active = (active + (e.key === 'ArrowDown' ? 1 : matches.length - 1)) % matches.length;
            [...results.querySelectorAll('button')].forEach((b, i) => b.classList.toggle('active', i === active));
        }
        if (e.key === 'Enter') { e.preventDefault(); addProduct(matches[active].id); }
        if (e.key === 'Escape') results.classList.add('d-none');
    });
    results.addEventListener('click', e => { const b = e.target.closest('[data-add]'); if (b) addProduct(+b.dataset.add); });
    document.addEventListener('click', e => { if (!e.target.closest('.position-relative')) results.classList.add('d-none'); });

    // ---- Payments ----
    function renderPayments() {
        const options = m => Object.entries(METHODS).map(([k, v]) => `<option value="${k}" ${k === m ? 'selected' : ''}>${esc(v)}</option>`).join('');
        document.getElementById('paymentRows').innerHTML = state.payments.map((p, i) => `
            <div class="d-flex gap-2 mb-2">
                <select class="form-select form-select-sm" data-method="${i}">${options(p.method)}</select>
                <input type="number" class="form-control form-control-sm text-end" style="max-width:120px" min="1" step="1" value="${p.amount}" data-amount="${i}">
                ${state.payments.length > 1 ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-drop="${i}" title="Remove"><i class="bi bi-x"></i></button>` : ''}
            </div>`).join('');
    }
    document.getElementById('paymentRows').addEventListener('change', e => {
        if (e.target.dataset.method !== undefined) state.payments[+e.target.dataset.method].method = e.target.value;
        if (e.target.dataset.amount !== undefined) { state.payments[+e.target.dataset.amount].amount = Math.max(0, Math.round(Number(e.target.value) || 0)); state.paymentsTouched = true; }
        update(false);
    });
    document.getElementById('paymentRows').addEventListener('click', e => {
        const b = e.target.closest('[data-drop]'); if (!b) return;
        state.payments.splice(+b.dataset.drop, 1); update();
    });
    document.getElementById('addPayment').addEventListener('click', () => {
        const remaining = Math.max(0, total() - paid());
        state.payments.push({ method: 'cash', amount: remaining }); state.paymentsTouched = true; update();
    });

    // ---- Summary and save rules ----
    function changed() {
        const now = state.items.filter(i => i.quantity > 0).map(i => `${i.product_id}:${i.quantity}:${i.price}`).sort().join('|');
        const before = ORIGINAL.map(i => `${i.product_id}:${i.quantity}:${i.price}`).sort().join('|');
        return now !== before;
    }

    function update(rerenderPayments = true) {
        const t = total();
        // With one payment, it simply follows the new total
        if (state.payments.length === 1 && !state.paymentsTouched) state.payments[0].amount = Math.round(t * 100) / 100;
        renderItems();
        if (rerenderPayments) renderPayments();

        document.getElementById('originalTotal').textContent = money(originalTotal);
        document.getElementById('newTotal').textContent = money(t);
        const d = t - originalTotal, box = document.getElementById('diffBox');
        if (Math.abs(d) < 0.01) { box.className = 'se-diff bg-light text-muted'; box.innerHTML = '<i class="bi bi-dash-circle me-1"></i>Total unchanged'; }
        else if (d < 0) { box.className = 'se-diff bg-danger-subtle text-danger-emphasis'; box.innerHTML = `<div class="small">Refund to customer</div><div class="fs-5 fw-bold">${money(-d)}</div>`; }
        else { box.className = 'se-diff bg-success-subtle text-success-emphasis'; box.innerHTML = `<div class="small">Collect from customer</div><div class="fs-5 fw-bold">${money(d)}</div>`; }

        const gap = t - paid(), status = document.getElementById('paymentStatus');
        status.innerHTML = Math.abs(gap) < 0.01
            ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Payments match the new total</span>'
            : `<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Payments are ${money(Math.abs(gap))} ${gap > 0 ? 'short' : 'over'}</span>`;

        // Tell the user exactly why Save is not possible yet
        const blockers = [];
        if (!state.items.some(i => i.quantity > 0)) blockers.push('Keep at least one item. To cancel the whole sale, ask a manager to delete it.');
        if (!changed() && !paymentsChanged()) blockers.push('Nothing has been changed yet.');
        state.items.forEach(i => { const p = byId[i.product_id]; if (p && i.quantity > p.available) blockers.push(`Only ${num(p.available)} ${p.name} available.`); });
        if (Math.abs(gap) >= 0.01) blockers.push(`Payments must add up to ${money(t)}.`);
        if (document.getElementById('editReason').value.trim().length < 5) blockers.push('Enter a reason (at least 5 characters).');
        document.getElementById('blockers').innerHTML = blockers.map(b => `<li>${esc(b)}</li>`).join('');
        document.getElementById('saveBtn').disabled = blockers.length > 0;
    }

    const originalPayments = @json($payments).map(p => `${p.method}:${p.amount}`).sort().join('|');
    function paymentsChanged() { return state.payments.map(p => `${p.method}:${p.amount}`).sort().join('|') !== originalPayments; }

    document.getElementById('editReason').addEventListener('input', () => update(false));

    // Build the submitted fields from the state, so what you see is exactly what is saved
    document.getElementById('editSaleForm').addEventListener('submit', e => {
        update(false);
        if (document.getElementById('saveBtn').disabled) { e.preventDefault(); return; }
        const fields = [];
        state.items.filter(i => i.quantity > 0).forEach((i, n) => {
            fields.push([`items[${n}][product_id]`, i.product_id], [`items[${n}][quantity]`, i.quantity], [`items[${n}][price]`, i.price]);
        });
        state.payments.forEach((p, n) => fields.push([`payments[${n}][method]`, p.method], [`payments[${n}][amount]`, p.amount]));
        fields.push(['edit_reason', document.getElementById('editReason').value.trim()]);
        document.getElementById('hiddenFields').innerHTML = fields.map(([k, v]) => `<input type="hidden" name="${esc(k)}" value="${esc(v)}">`).join('');
        const btn = document.getElementById('saveBtn');
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
    });

    update();
    if (document.getElementById('serverErrors')) document.getElementById('serverErrors').scrollIntoView({ block: 'center' });
})();
</script>
@endsection
