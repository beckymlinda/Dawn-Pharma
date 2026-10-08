@extends('layouts.app')

@section('title', 'Billing - ' . setting('system_name'))

@section('content')
@php
    $state = $tenant->billingState();
    $days = $tenant->daysLeft();
    $tone = match ($state) {
        'expired', 'suspended', 'grace' => 'danger',
        'due_soon' => 'warning',
        default => 'success',
    };
    $price = $tenant->monthlyPrice();
    $pending = $submissions->where('status', 'pending');
@endphp
<div class="container-fluid py-4">
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Billing</h3>
        <p class="text-muted small mb-0">Your subscription to the system: how long it is paid for, where to pay, and proof of payment.</p>
    </div>

    @if(!$tenant->canBeUsed())
        <div class="alert alert-danger alert-persistent" role="alert">
            <i class="bi bi-lock me-2"></i><strong>The system is locked for your shop.</strong>
            Pay using one of the accounts below and upload the proof. Access returns as soon as it is approved.
        </div>
    @endif

    <div class="row g-4">
        {{-- Days left --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 billing-status billing-{{ $tone }}">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    @if($tenant->isSuspended())
                        <div class="fs-4 fw-bold">Suspended</div>
                        <p class="mb-0">{{ $tenant->suspended_reason ?: 'Contact your provider.' }}</p>
                    @elseif($tenant->paid_until === null)
                        <i class="bi bi-infinity display-4 mb-2"></i>
                        <div class="fs-4 fw-bold">No end date</div>
                        <p class="text-muted mb-0">Your subscription does not expire.</p>
                    @elseif($state === 'grace')
                        <div class="text-uppercase small fw-bold mb-1">Overdue</div>
                        <div class="billing-days">{{ abs($days) }}</div>
                        <div class="fw-semibold mb-3">{{ abs($days) === 1 ? 'day' : 'days' }} since your subscription ended</div>
                        <p class="mb-0">The system will <strong>stop working after {{ $tenant->blockedAfter()->format('d M Y') }}</strong> unless it is renewed.</p>
                    @elseif($state === 'expired')
                        <div class="fs-4 fw-bold">Subscription ended</div>
                        <p class="mb-0">It ended on {{ $tenant->paid_until->format('d M Y') }}.</p>
                    @else
                        <div class="text-uppercase small fw-bold mb-1">{{ $days === 0 ? 'Last day' : 'Time left' }}</div>
                        <div class="billing-days">{{ $days }}</div>
                        <div class="fw-semibold mb-3">{{ $days === 1 ? 'day' : 'days' }} left</div>
                        <p class="mb-0">Paid until <strong>{{ $tenant->paid_until->format('l, d M Y') }}</strong>.</p>
                        @if($state === 'due_soon')
                            <p class="mb-0 mt-2 fw-semibold">Please renew soon to avoid interruption.</p>
                        @endif
                    @endif
                    <hr class="my-3 opacity-25">
                    <div class="small">
                        <strong>{{ $tenant->planName() }}</strong> plan{{ $price > 0 ? ' · ' . formatCurrency($price) . ' per month' : '' }}
                        @if($tenant->paid_until && $tenant->grace_days)<br>{{ $tenant->grace_days }} days grace after the due date @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Where to pay --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2"></i>Where to pay</h6>
                    <p class="small text-muted mb-3">Use any of these accounts. Put your shop code <strong>{{ $tenant->slug }}</strong> as the payment reference if you can.</p>
                    <div class="row g-3">
                        @foreach($accounts as $key => $account)
                            <div class="col-md-4">
                                <div class="pay-account h-100">
                                    <div class="small text-muted"><i class="bi {{ $account['icon'] ?? 'bi-cash' }} me-1"></i>{{ $account['label'] }}</div>
                                    <div class="fs-5 fw-bold text-nowrap">{{ $account['number'] }}</div>
                                    @if($account['name'])<div class="small">{{ $account['name'] }}</div>@endif
                                    <button type="button" class="btn btn-link btn-sm p-0 mt-1 copy-number" data-number="{{ preg_replace('/\s+/', '', $account['number']) }}">
                                        <i class="bi bi-clipboard"></i> Copy number
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($contact['phone'] || $contact['email'])
                        <p class="small text-muted mt-3 mb-0">Questions?
                            @if($contact['phone'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a>@endif
                            @if($contact['email']) · <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>@endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-0">
        {{-- Upload proof --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" id="upload-proof">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1"><i class="bi bi-upload me-2"></i>Upload proof of payment</h6>
                    <p class="small text-muted mb-3">After paying, send a screenshot of the confirmation SMS or a photo/PDF of the bank slip.</p>
                    @if($pending->isNotEmpty())
                        <div class="alert alert-info alert-persistent small py-2">
                            <i class="bi bi-hourglass-split me-1"></i> You already sent proof on {{ $pending->first()->created_at->format('d M Y') }}; it is waiting for approval.
                        </div>
                    @endif
                    <form method="POST" action="{{ route('billing.submit') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="months" class="form-label small fw-semibold">Paying for</label>
                                <select id="months" name="months" class="form-select @error('months') is-invalid @enderror" required>
                                    @foreach([1 => '1 month', 3 => '3 months', 6 => '6 months', 12 => '12 months'] as $m => $label)
                                        <option value="{{ $m }}" data-amount="{{ $price * $m }}" @selected((int) old('months', 1) === $m)>{{ $label }}{{ $price ? ' – ' . formatCurrency($price * $m) : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="amount" class="form-label small fw-semibold">Amount paid (MWK)</label>
                                <input id="amount" name="amount" type="number" min="1" step="0.01" inputmode="decimal" required
                                       class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $price ?: '') }}">
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="account" class="form-label small fw-semibold">Paid to</label>
                                <select id="account" name="account" class="form-select @error('account') is-invalid @enderror" required>
                                    <option value="" disabled @selected(!old('account'))>Choose…</option>
                                    @foreach($accounts as $key => $account)
                                        <option value="{{ $key }}" @selected(old('account') === $key)>{{ $account['label'] }} – {{ $account['number'] }}</option>
                                    @endforeach
                                </select>
                                @error('account')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="paid_on" class="form-label small fw-semibold">Date paid</label>
                                <input id="paid_on" name="paid_on" type="date" max="{{ now()->toDateString() }}" required
                                       class="form-control @error('paid_on') is-invalid @enderror" value="{{ old('paid_on', now()->toDateString()) }}">
                                @error('paid_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="reference" class="form-label small fw-semibold">Transaction ID <span class="text-muted fw-normal">(from the SMS or slip)</span></label>
                                <input id="reference" name="reference" maxlength="100" class="form-control @error('reference') is-invalid @enderror"
                                       value="{{ old('reference') }}" placeholder="e.g. CI241008.1523.H12345">
                            </div>
                            <div class="col-12">
                                <label for="proof" class="form-label small fw-semibold">Screenshot, photo or PDF</label>
                                <input id="proof" name="proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required
                                       class="form-control @error('proof') is-invalid @enderror">
                                <div class="form-text">JPG, PNG, WEBP or PDF, up to 5 MB.</div>
                                @error('proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="note" class="form-label small fw-semibold">Note <span class="text-muted fw-normal">(optional)</span></label>
                                <input id="note" name="note" maxlength="500" class="form-control" value="{{ old('note') }}">
                            </div>
                        </div>
                        <button class="btn btn-success mt-3 px-4" id="sendProofBtn"><i class="bi bi-send me-1"></i> Send proof of payment</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Status of proofs + payments --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-hourglass-split me-2"></i>Your proofs of payment</h6>
                    @forelse($submissions as $submission)
                        <div class="d-flex justify-content-between align-items-start py-2 border-bottom small">
                            <div>
                                <strong>{{ formatCurrency($submission->amount) }}</strong> · {{ $submission->months }} {{ $submission->months == 1 ? 'month' : 'months' }}
                                · {{ $submission->accountLabel() }}
                                <div class="text-muted">Sent {{ $submission->created_at->format('d M Y H:i') }} by {{ $submission->submitted_by_name }}
                                    · <a href="{{ route('billing.proof', $submission->id) }}" target="_blank" rel="noopener">view proof</a></div>
                                @if($submission->status === 'rejected' && $submission->review_note)
                                    <div class="text-danger mt-1"><i class="bi bi-chat-left-text me-1"></i>{{ $submission->review_note }}</div>
                                @endif
                            </div>
                            @php
                                [$cls, $label] = match ($submission->status) {
                                    'approved' => ['bg-success', 'Approved'],
                                    'rejected' => ['bg-danger', 'Not approved'],
                                    default => ['bg-warning text-dark', 'Waiting for approval'],
                                };
                            @endphp
                            <span class="badge {{ $cls }} rounded-pill ms-2 text-nowrap">{{ $label }}</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">None sent yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Payments received</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 small">
                            <thead class="table-light text-muted">
                                <tr><th>Paid on</th><th>Period covered</th><th>Method</th><th class="text-end">Amount</th></tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $payment)
                                    <tr>
                                        <td class="text-nowrap">{{ $payment->paid_on->format('d M Y') }}</td>
                                        <td class="text-nowrap">{{ $payment->period_from->format('d M Y') }} – {{ $payment->period_to->format('d M Y') }}</td>
                                        <td>{{ $payment->method ?? '—' }}@if($payment->reference)<div class="text-muted">{{ $payment->reference }}</div>@endif</td>
                                        <td class="text-end fw-semibold text-nowrap">{{ formatCurrency($payment->amount) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .billing-days { font-size: 4.5rem; font-weight: 800; line-height: 1; }
    .billing-status.billing-success { background: #ecfdf5; color: #065f46; }
    .billing-status.billing-warning { background: #fffbeb; color: #92400e; }
    .billing-status.billing-danger { background: #fef2f2; color: #991b1b; }
    .billing-status .text-muted { color: inherit !important; opacity: .75; }
    .pay-account { border: 1px solid #e2e8f0; border-radius: 12px; padding: .9rem 1rem; background: #f8fafc; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Amount follows the chosen number of months (still editable)
    var months = document.getElementById('months');
    var amount = document.getElementById('amount');
    months.addEventListener('change', function () {
        var expected = Number(months.selectedOptions[0].dataset.amount || 0);
        if (expected > 0) amount.value = expected;
    });

    document.querySelectorAll('.copy-number').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard && navigator.clipboard.writeText(btn.dataset.number).then(function () {
                btn.innerHTML = '<i class="bi bi-check2"></i> Copied';
                setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy number'; }, 1500);
            });
        });
    });

    // Avoid double sending on slow connections
    document.getElementById('sendProofBtn').closest('form').addEventListener('submit', function () {
        var btn = document.getElementById('sendProofBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';
    });
});
</script>
@endsection
