@extends('layouts.app')

@section('title', 'Settings · Payment methods')

@section('content')
<div class="container-fluid">
    @include('settings._tabs')

    <div class="row g-4">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('settings.payment-methods.update') }}" id="methodsForm" class="settings-card">
                @csrf
                <div class="settings-card-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h6><i class="bi bi-credit-card me-2 text-primary"></i>Payment methods</h6>
                        <p class="text-muted small mb-0">Only methods that are <strong>on</strong> are offered at the till, on expenses and when editing a sale.
                            The first five are shown as buttons on the payment screen; the rest are under "More".</p>
                    </div>
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis" id="pmOnCount"></span>
                </div>
                <div class="settings-card-body">
                    <ul class="pm-list" id="pmList">
                        @foreach($methods as $m)
                            @php($isCash = $m['key'] === \App\Helpers\PaymentMethods::ALWAYS_ON)
                            <li class="pm-row {{ $m['enabled'] ? '' : 'is-off' }}" data-key="{{ $m['key'] }}">
                                <input type="hidden" data-field="key" value="{{ $m['key'] }}">
                                <span class="pm-order">
                                    <button type="button" class="pm-move" data-dir="-1" aria-label="Move {{ $m['label'] }} up"><i class="bi bi-chevron-up"></i></button>
                                    <button type="button" class="pm-move" data-dir="1" aria-label="Move {{ $m['label'] }} down"><i class="bi bi-chevron-down"></i></button>
                                </span>
                                <span class="pm-icon" style="--pm: {{ $m['color'] }}"><i class="bi {{ $m['icon'] }}"></i></span>
                                <span class="pm-main">
                                    <input type="text" class="form-control form-control-sm pm-name" data-field="label" value="{{ old('methods.' . $loop->index . '.label', $m['label']) }}"
                                           maxlength="40" required aria-label="Name of {{ $m['label'] }}">
                                    <span class="pm-tags">
                                        <span class="pm-tile-tag">Button</span>
                                        @if(!$m['builtin'])<span class="pm-tag">Added</span>@endif
                                        @if(in_array($m['key'], $used, true))<span class="pm-tag">Used on sales</span>@endif
                                        @if($isCash)<span class="pm-tag">Always on (cash drawer)</span>@endif
                                    </span>
                                </span>
                                <span class="form-check form-switch m-0 pm-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" data-field="enabled" value="1"
                                           {{ $m['enabled'] ? 'checked' : '' }} {{ $isCash ? 'checked disabled' : '' }} aria-label="{{ $m['label'] }} on or off">
                                    @if($isCash)<input type="hidden" data-field="enabled" value="1">@endif
                                </span>
                                @if(!$m['builtin'] && !in_array($m['key'], $used, true))
                                    <button type="submit" form="pmDelete-{{ $m['key'] }}" class="btn btn-sm btn-outline-danger pm-delete" title="Delete {{ $m['label'] }}"><i class="bi bi-trash"></i></button>
                                @else
                                    <span class="pm-delete"></span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i> Save payment methods</button>
                    </div>
                </div>
            </form>
            @foreach($methods as $m)
                @if(!$m['builtin'] && !in_array($m['key'], $used, true))
                    <form method="POST" action="{{ route('settings.payment-methods.destroy', $m['key']) }}" id="pmDelete-{{ $m['key'] }}" class="d-none"
                          onsubmit="return confirm('Delete {{ $m['label'] }}?');">
                        @csrf @method('DELETE')
                    </form>
                @endif
            @endforeach
        </div>

        <div class="col-xl-4">
            <form method="POST" action="{{ route('settings.payment-methods.store') }}" class="settings-card">
                @csrf
                <div class="settings-card-head">
                    <h6><i class="bi bi-plus-circle me-2 text-primary"></i>Add a payment method</h6>
                    <p class="text-muted small mb-0">For example FDH Bank, NBS Bank, Insurance or Staff account.</p>
                </div>
                <div class="settings-card-body">
                    <input type="text" name="label" class="form-control mb-3 @error('label') is-invalid @enderror" maxlength="40"
                           placeholder="Name of the payment method" value="{{ old('label') }}" required>
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-plus-lg me-1"></i> Add method</button>
                </div>
            </form>
            <div class="settings-card mt-4">
                <div class="settings-card-body small text-muted">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-info-circle me-1"></i> Good to know</div>
                    Switching a method off hides it for new sales only. Past sales keep it, and reports still show it by name.
                    Methods already used on a sale can be switched off but not deleted.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .pm-list { list-style: none; margin: 0; padding: 0; }
    .pm-row { display: flex; align-items: center; gap: .75rem; padding: .6rem .5rem; border-bottom: 1px solid #f1f5f9; }
    .pm-row:last-child { border-bottom: 0; }
    .pm-row.is-off .pm-icon, .pm-row.is-off .pm-name { opacity: .5; }
    .pm-order { display: flex; flex-direction: column; }
    .pm-move { border: 0; background: none; color: #94a3b8; line-height: 1; padding: 1px 4px; border-radius: 4px; }
    .pm-move:hover { color: var(--theme-primary); background: #f1f5f9; }
    .pm-move:disabled { visibility: hidden; }
    .pm-icon { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; flex-shrink: 0;
               color: var(--pm); background: color-mix(in srgb, var(--pm) 12%, #fff); font-size: 1.1rem; }
    .pm-main { flex: 1; min-width: 0; }
    .pm-name { max-width: 280px; font-weight: 600; }
    .pm-tags { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .25rem; }
    .pm-tag, .pm-tile-tag { font-size: .68rem; padding: .05rem .45rem; border-radius: 999px; background: #f1f5f9; color: #64748b; }
    .pm-tile-tag { display: none; background: #ecfdf5; color: #047857; }
    .pm-row.is-tile .pm-tile-tag { display: inline; }
    .pm-switch .form-check-input { width: 2.6em; height: 1.4em; cursor: pointer; }
    .pm-delete { width: 34px; flex-shrink: 0; }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('pmList');
    var form = document.getElementById('methodsForm');

    // Order arrows, "Button" tags on the first five methods that are on, and the count
    function refresh() {
        var rows = Array.prototype.slice.call(list.children), on = 0;
        rows.forEach(function (row, i) {
            var enabled = row.querySelector('input[type=checkbox]').checked;
            row.classList.toggle('is-off', !enabled);
            row.classList.toggle('is-tile', enabled && on < 5);
            if (enabled) on++;
            row.querySelector('[data-dir="-1"]').disabled = i === 0;
            row.querySelector('[data-dir="1"]').disabled = i === rows.length - 1;
        });
        document.getElementById('pmOnCount').textContent = on + ' on';
    }

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('.pm-move');
        if (!btn) return;
        var row = btn.closest('.pm-row');
        if (btn.dataset.dir === '-1' && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
        if (btn.dataset.dir === '1' && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
        btn.focus();
        refresh();
    });
    list.addEventListener('change', refresh);

    // Name the fields in the order shown, so the saved order is what the user sees
    form.addEventListener('submit', function () {
        Array.prototype.forEach.call(list.children, function (row, i) {
            row.querySelectorAll('[data-field]').forEach(function (input) {
                input.name = 'methods[' + i + '][' + input.dataset.field + ']';
            });
        });
    });

    refresh();
});
</script>
@endsection
