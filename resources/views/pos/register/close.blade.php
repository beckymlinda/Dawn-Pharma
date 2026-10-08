@extends('layouts.app')

@section('title', 'Close register - ' . setting('system_name'))

@section('content')
@php
    // Blind close: cashiers count the drawer without seeing what the system expects
    $isAdmin = auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin');
    // One switch in Loss Prevention settings: when on, everyone must count the drawer and non-admins can't see the expected cash
    $countRequired = \App\Models\IntegrityEvent::cashCountRequired();
    $blind = $countRequired && !$isAdmin;
    $money = fn ($v, $prefix = 'MWK ') => $blind ? '••••••' : $prefix . number_format($v, 2);

    // The shop's payment methods (Settings → Payment methods), cheque totals are kept under "cheques"
    $methods = collect(\App\Helpers\PaymentMethods::definitions())
        ->map(fn ($m) => ['key' => $m['key'] === 'cheque' ? 'cheques' : $m['key'], 'label' => $m['label'], 'icon' => $m['icon'], 'color' => $m['color']])
        ->values()->all();
    $methods[] = ['key' => 'cash_and_card', 'label' => 'Other', 'icon' => 'bi-intersect', 'color' => '#334155'];
    // Anything else paid in this shift (e.g. a method since deleted) still gets a line
    foreach (array_keys($counts) as $countKey) {
        $key = substr($countKey, 6);
        if ($key !== 'total_sales' && !in_array($key, array_column($methods, 'key'), true)) {
            $methods[] = ['key' => $key, 'label' => ucwords(str_replace('_', ' ', $key)), 'icon' => 'bi-wallet', 'color' => '#7c3aed'];
        }
    }
    $used = collect($methods)->filter(fn ($m) => ($counts['count_' . $m['key']] ?? 0) > 0)->values();
    $methodTotal = max(1, $used->sum(fn ($m) => $totals['total_' . $m['key']] ?? 0));
    $expectedCash = ($totals['total_cash'] ?? 0) + $register->opening_cash;
    $minutesOpen = (int) $register->opened_at->diffInMinutes(now());
@endphp

<div class="close-register">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Close register</h3>
            <div class="text-muted small">
                {{ auth()->user()->name }} · opened {{ $register->opened_at->format('d M Y, H:i') }}
                · open {{ intdiv($minutesOpen, 60) }}h {{ str_pad($minutesOpen % 60, 2, '0', STR_PAD_LEFT) }}m
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pos.new') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to POS</a>
            <button type="button" onclick="window.print()" class="btn btn-outline-primary"><i class="bi bi-printer me-1"></i> Print Z-report</button>
        </div>
    </div>

    {{-- Headline figures --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="cr-stat"><div class="cr-stat-label"><i class="bi bi-wallet2"></i> Opening float</div>
                <div class="cr-stat-value">MWK {{ number_format($register->opening_cash, 2) }}</div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="cr-stat"><div class="cr-stat-label"><i class="bi bi-receipt"></i> Sales</div>
                <div class="cr-stat-value">{{ $money($totals['total_sales']) }}</div>
                <div class="cr-stat-sub">{{ $counts['count_total_sales'] ?? 0 }} {{ ($counts['count_total_sales'] ?? 0) == 1 ? 'sale' : 'sales' }}</div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="cr-stat"><div class="cr-stat-label"><i class="bi bi-cash-stack"></i> Cash sales</div>
                <div class="cr-stat-value">{{ $money($totals['total_cash'] ?? 0) }}</div></div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="cr-stat cr-stat-strong"><div class="cr-stat-label"><i class="bi bi-safe"></i> Expected in drawer</div>
                <div class="cr-stat-value">{{ $money($expectedCash) }}</div>
                <div class="cr-stat-sub">{{ $blind ? 'Hidden: count the drawer yourself' : 'Opening float + cash sales' }}</div></div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Sales by payment method --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Sales by payment method</h5>
                    @forelse($used as $m)
                        @php($amount = $totals['total_' . $m['key']] ?? 0)
                        <div class="cr-method">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold"><i class="bi {{ $m['icon'] }} me-2" style="color: {{ $m['color'] }}"></i>{{ $m['label'] }}
                                    <span class="text-muted fw-normal small ms-1">{{ $counts['count_' . $m['key']] }} {{ $counts['count_' . $m['key']] == 1 ? 'payment' : 'payments' }}</span></span>
                                <span class="fw-bold">{{ $money($amount) }}</span>
                            </div>
                            @unless($blind)
                                <div class="progress" style="height: 6px;" role="presentation">
                                    <div class="progress-bar" style="width: {{ round($amount / $methodTotal * 100, 1) }}%; background: {{ $m['color'] }};"></div>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No sales in this session.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Count & close --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1">Count &amp; close</h5>
                    <p class="text-muted small mb-3">
                        @if($countRequired)
                            Count all notes and coins in the drawer, including the opening float. Do not copy a figure from the screen.
                        @else
                            Check the drawer against the expected amount, then close the register.
                        @endif
                    </p>

                    <form action="{{ route('register.update', $register->id) }}" method="POST" id="closeRegisterForm" class="m-0">
                        @csrf
                        @method('PATCH')

                        <label for="countedCash" class="form-label fw-semibold small">
                            Cash counted in drawer @if($countRequired)<span class="text-danger">*</span>@else<span class="text-muted fw-normal">(optional)</span>@endif
                        </label>
                        <div class="input-group input-group-lg mb-2">
                            <span class="input-group-text">MWK</span>
                            <input type="number" step="1" min="0" name="counted_cash" id="countedCash" inputmode="decimal" autocomplete="off"
                                   class="form-control fw-bold @error('counted_cash') is-invalid @enderror"
                                   value="{{ old('counted_cash') }}" placeholder="0" @if($countRequired) required @endif>
                            @error('counted_cash')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @unless($blind)
                            {{-- Admins see the difference as they type --}}
                            <div class="cr-variance d-none" id="crVariance" data-expected="{{ $expectedCash }}">
                                <span id="crVarianceLabel">Difference</span><strong id="crVarianceValue"></strong>
                            </div>
                        @endunless

                        <div id="offlinePendingWarning" class="alert alert-warning alert-persistent small mt-3 mb-0 d-none" role="alert">
                            <i class="bi bi-cloud-upload me-1"></i>
                            <strong><span id="offlinePendingCount">0</span> sale(s) made while offline</strong> are still on this device.
                            The register can be closed once they are uploaded, so they are included in the Z-report.
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-dark" id="offlineSyncNow">Upload now</button>
                                <span class="small text-muted ms-2" id="offlineSyncStatus"></span>
                            </div>
                        </div>

                        <button type="submit" id="closeRegisterSubmit" class="btn cr-close-btn w-100 mt-3">
                            <i class="bi bi-lock me-1"></i> <span id="closeRegisterText">Close register</span>
                        </button>
                        <button type="button" id="closeRegisterCancel" class="btn btn-link w-100 text-muted mt-1 d-none">Cancel, keep the register open</button>
                        <div class="text-center small text-muted mt-2" id="closeRegisterHint">The Z-report is emailed when the register is closed.</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HIDDEN RECEIPT FOR PRINTING ONLY (Z-REPORT) -->
<div id="printable-receipt">
    <div class="text-center mb-3 pt-2">
        @if(brand_image_exists($settings['logo'] ?? null))
            <img src="{{ asset($settings['logo']) }}" style="max-height: 50px; margin-bottom: 5px; filter: grayscale(1) contrast(1.2);">
        @endif
        <h2 class="fw-bold mb-1" style="font-size:22px">{{ $settings['system_name'] ?? 'REGISTER SUMMARY' }}</h2>
        <div style="font-size: 16px; font-weight: bold;">Z-REPORT</div>
    </div>
    
    <div style="font-size:15px; font-family: monospace;" class="mb-3">
        <div class="mb-1"><strong>Cashier:</strong> {{ auth()->user()->name ?? 'Unknown' }}</div>
        <div class="mb-1"><strong>Register ID:</strong> #{{ $register->id }}</div>
        <div class="mb-1"><strong>Time Opened:</strong> <br>{{ $register->opened_at->format('d M Y, H:i') }}</div>
        <div class="mb-1"><strong>Time Closed:</strong> <br>{{ now()->format('d M Y, H:i') }}</div>
    </div>
    
    <div style="border-top: 1px dashed #000; margin-bottom: 10px;"></div>
    <div class="d-flex justify-content-between fw-bold mb-2" style="font-size:16px;">
        <span>OPENING CASH:</span>
        <span>MWK {{ number_format($register->opening_cash, 2) }}</span>
    </div>
    <div style="border-top: 1px dashed #000; margin-bottom: 15px;"></div>

    <div class="text-center mb-2" style="font-weight:bold; font-size:16px; text-decoration: underline;">PAYMENT METHODS USED</div>
    
    <table class="receipt-table" style="font-size:15px; font-family: monospace; margin-bottom:15px; width: 100%;">
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align:left; padding-bottom:5px;">Method</th>
                <th style="text-align:center; padding-bottom:5px;">Count</th>
                <th style="text-align:right; padding-bottom:5px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($methods as $method)
                @if(($counts['count_'.$method['key']] ?? 0) > 0)
                <tr>
                    <td style="text-align:left;">{{ $method['label'] }}</td>
                    <td style="text-align:center;">{{ $counts['count_'.$method['key']] }}</td>
                    <td style="text-align:right;">{{ $money($totals['total_'.$method['key']], '') }}</td>
                </tr>
                @endif
            @endforeach
            @if(($counts['count_total_sales'] ?? 0) == 0)
            <tr>
                <td colspan="3" style="text-align:center; font-style:italic; padding-top:10px;">No sales recorded</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div style="border-top: 1px solid #000; margin-bottom: 5px;"></div>
    <div class="d-flex justify-content-between fw-bold mb-2" style="font-size:16px;">
        <span>TOTAL SALES:</span>
        <span>{{ $money($totals['total_sales']) }}</span>
    </div>
    
    <div style="border-top: 2px solid #000; margin-bottom: 10px; margin-top: 15px;"></div>
    <div class="d-flex justify-content-between fw-bold mb-1" style="font-size:18px;">
        <span>TOTAL IN DRAWER:</span>
        <span>{{ $money(($totals['total_cash'] ?? 0) + $register->opening_cash) }}</span>
    </div>
    <div style="font-size:12px; color:#555; text-align:right; margin-bottom: 20px;">
        (Opening Cash + Cash Sales)
    </div>

    <div class="text-center mt-4">
        <p style="font-weight: bold; font-size: 16px; margin-top: 15px;">*** END OF REPORT ***</p>
    </div>
</div>


{{-- Sales made offline on this device must be uploaded before the shift is closed --}}
<meta name="pos-user" content="{{ auth()->id() }}">
<script src="{{ asset('js/pos/offline.js') }}?v={{ filemtime(public_path('js/pos/offline.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('closeRegisterForm');
    var submit = document.getElementById('closeRegisterSubmit');
    var text = document.getElementById('closeRegisterText');
    var hint = document.getElementById('closeRegisterHint');
    var cancel = document.getElementById('closeRegisterCancel');
    var warning = document.getElementById('offlinePendingWarning');
    var status = document.getElementById('offlineSyncStatus');
    var counted = document.getElementById('countedCash');
    var armed = false, sending = false, pending = 0;

    // Offline sales still on this device block closing
    function refreshGuard() {
        pending = POSOffline.pendingCount();
        document.getElementById('offlinePendingCount').textContent = pending;
        warning.classList.toggle('d-none', pending === 0);
        status.textContent = pending && POSOffline.state.lastError ? POSOffline.state.lastError : '';
        if (!sending) submit.disabled = pending > 0;
    }
    document.getElementById('offlineSyncNow').addEventListener('click', function () {
        status.textContent = 'Uploading…';
        POSOffline.sync().then(refreshGuard);
    });
    POSOffline.sync().then(refreshGuard);
    refreshGuard();
    setInterval(refreshGuard, 3000);

    // Difference between counted and expected cash (admins only; not shown in blind mode)
    var variance = document.getElementById('crVariance');
    if (variance && counted) {
        counted.addEventListener('input', function () {
            if (counted.value === '') { variance.classList.add('d-none'); return; }
            var diff = Math.round((parseFloat(counted.value) || 0) - parseFloat(variance.dataset.expected));
            variance.classList.remove('d-none', 'is-short', 'is-over', 'is-even');
            variance.classList.add(diff < 0 ? 'is-short' : diff > 0 ? 'is-over' : 'is-even');
            document.getElementById('crVarianceLabel').textContent = diff < 0 ? 'Short by' : diff > 0 ? 'Over by' : 'Matches the expected amount';
            document.getElementById('crVarianceValue').textContent = diff === 0 ? '' : 'MWK ' + Math.abs(diff).toLocaleString('en-US');
        });
    }

    // Two taps to close: the first asks for confirmation, the second closes
    function disarm() {
        armed = false;
        submit.classList.remove('is-armed');
        text.textContent = 'Close register';
        hint.textContent = 'The Z-report is emailed when the register is closed.';
        cancel.classList.add('d-none');
    }
    // Stays armed until closed or cancelled: no timer, so a slow second tap still closes
    cancel.addEventListener('click', disarm);
    form.addEventListener('submit', function (e) {
        if (sending || pending > 0) { e.preventDefault(); return; }
        if (!armed) {
            e.preventDefault();
            armed = true;
            submit.classList.add('is-armed');
            text.textContent = 'Yes, close the register';
            hint.textContent = 'This ends the shift. Sales cannot be added to it afterwards.';
            cancel.classList.remove('d-none');
            return;
        }
        sending = true;
        cancel.classList.add('d-none');
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Closing register…';
        hint.textContent = 'Saving and sending the Z-report. Please wait.';
    });

    if (counted && !counted.value) counted.focus();
});
</script>

<style>
    .close-register { max-width: 1150px; margin: 0 auto; }
    .cr-stat { background: #fff; border-radius: 14px; padding: 1rem 1.15rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); height: 100%; }
    .cr-stat-label { color: #64748b; font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; display: flex; gap: .4rem; align-items: center; }
    .cr-stat-value { font-size: clamp(1.1rem, 1.7vw, 1.45rem); font-weight: 800; color: #0f172a; white-space: nowrap; margin-top: .25rem; }
    .cr-stat-sub { color: #64748b; font-size: .8rem; }
    .cr-stat-strong { background: linear-gradient(135deg, var(--theme-primary, #004d3d), var(--theme-secondary, #006b54)); }
    .cr-stat-strong .cr-stat-label, .cr-stat-strong .cr-stat-sub { color: rgba(255, 255, 255, .8); }
    .cr-stat-strong .cr-stat-value { color: #fff; }
    .cr-method { padding: .65rem 0; border-bottom: 1px solid #f1f5f9; }
    .cr-method:last-child { border-bottom: 0; }
    .cr-variance { display: flex; justify-content: space-between; align-items: center; border-radius: 10px; padding: .55rem .85rem; font-size: .9rem; }
    .cr-variance.is-short { background: #fef2f2; color: #b91c1c; }
    .cr-variance.is-over { background: #fffbeb; color: #92400e; }
    .cr-variance.is-even { background: #ecfdf5; color: #047857; }
    .cr-close-btn, .cr-close-btn:hover, .cr-close-btn:focus, .cr-close-btn:active {
        background: #b91c1c !important; border-color: #b91c1c !important; color: #fff !important;
        font-weight: 700; padding: .8rem 1rem; border-radius: 12px;
    }
    .cr-close-btn:hover:not(:disabled) { filter: brightness(1.1); }
    .cr-close-btn.is-armed, .cr-close-btn.is-armed:hover { background: #7f1d1d !important; border-color: #7f1d1d !important; box-shadow: 0 0 0 4px rgba(185, 28, 28, .25); }
    .cr-close-btn:disabled { background: #94a3b8 !important; border-color: #94a3b8 !important; }

    /* Print: only the Z-report */
    @media print {
        @page { margin: 0; }
        body * { visibility: hidden; }
        body { background: white; margin: 0; padding: 0; }
        #printable-receipt, #printable-receipt * { visibility: visible; }
        #printable-receipt { position: absolute; left: 0; top: 0; width: 320px !important; margin: 0; padding: 20px 10px; font-family: monospace; font-size: 14px; line-height: 1.4; color: #000; }
        .receipt-table { width: 100%; border-collapse: collapse; }
        .receipt-table td, .receipt-table th { padding: 4px 0; }
    }
    @media screen { #printable-receipt { display: none; } }
</style>
@endsection
