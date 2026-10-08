@extends('platform.layout')
@section('title', 'Payments to review')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">Proofs of payment</h1>
        <p class="text-muted small mb-0">Check each payment against your Mpamba, Airtel Money or bank statement, then approve or reject it.</p>
    </div>
    <div class="d-flex gap-2">
        @foreach(['pending' => 'Waiting (' . $pendingCount . ')', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
            <a href="?status={{ $key }}" class="btn btn-sm {{ $status === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ implode(' ', $errors->all()) }}</div>
@endif

@forelse($submissions as $s)
    <div class="card mb-3">
        <div class="card-body p-4">
            <div class="row g-3 align-items-start">
                <div class="col-lg-5">
                    <a href="{{ route('platform.tenants.show', $s->tenant) }}" class="fw-bold text-decoration-none fs-5">{{ $s->tenant->name }}</a>
                    <span class="text-muted small">/{{ $s->tenant->slug }}</span>
                    <div class="fs-4 fw-bold mt-1">{{ formatCurrency($s->amount) }}
                        <span class="fs-6 fw-normal text-muted">for {{ $s->months }} {{ $s->months == 1 ? 'month' : 'months' }}</span></div>
                    @php $expected = $s->tenant->monthlyPrice() * $s->months; @endphp
                    @if($expected > 0 && abs($expected - (float) $s->amount) >= 1)
                        <div class="small text-danger"><i class="bi bi-exclamation-triangle me-1"></i>{{ $s->tenant->planName() }} plan expects {{ formatCurrency($expected) }}</div>
                    @endif
                </div>
                <div class="col-lg-4 small">
                    <div><span class="text-muted">Paid to:</span> {{ $s->accountLabel() }}</div>
                    <div><span class="text-muted">Transaction ID:</span> <strong>{{ $s->reference ?: '—' }}</strong></div>
                    <div><span class="text-muted">Date paid:</span> {{ $s->paid_on->format('d M Y') }}</div>
                    <div><span class="text-muted">Sent by:</span> {{ $s->submitted_by_name }} · {{ $s->created_at->format('d M Y H:i') }}</div>
                    <div><span class="text-muted">Paid until now:</span> {{ $s->tenant->paid_until?->format('d M Y') ?? 'no end date' }}</div>
                    @if($s->note)<div class="mt-1"><span class="text-muted">Note:</span> {{ $s->note }}</div>@endif
                    <div class="mt-1">
                        @if($s->notified_at)
                            <span class="text-success"><i class="bi bi-envelope-check me-1"></i>Email sent {{ $s->notified_at->format('d M H:i') }}</span>
                        @else
                            <span class="text-danger" title="{{ $s->notify_error }}"><i class="bi bi-envelope-x me-1"></i>Email not sent yet{{ $s->notify_attempts ? ' (' . $s->notify_attempts . ' tries)' : '' }}</span>
                        @endif
                    </div>
                    <a href="{{ route('platform.payments.proof', $s) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-file-earmark-image me-1"></i> Open proof ({{ $s->proof_name }})
                    </a>
                    <form method="POST" action="{{ route('platform.payments.resend', $s) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-link mt-2">{{ $s->notified_at ? 'Email me again' : 'Resend email' }}</button>
                    </form>
                </div>
                <div class="col-lg-3">
                    @if($s->isPending())
                        <form method="POST" action="{{ route('platform.payments.approve', $s) }}" class="mb-2">
                            @csrf
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" name="months" min="1" max="24" value="{{ $s->months }}" class="form-control" aria-label="Months">
                                <span class="input-group-text">months</span>
                            </div>
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text">MWK</span>
                                <input type="number" name="amount" min="0" step="0.01" value="{{ (float) $s->amount }}" class="form-control" aria-label="Amount received">
                            </div>
                            <button class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg me-1"></i> Approve &amp; extend</button>
                        </form>
                        <form method="POST" action="{{ route('platform.payments.reject', $s) }}">
                            @csrf
                            <input name="review_note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Reason shown to the shop" required>
                            <button class="btn btn-outline-danger btn-sm w-100">Reject</button>
                        </form>
                    @else
                        <span class="badge {{ $s->status === 'approved' ? 'bg-success' : 'bg-danger' }} mb-1">{{ ucfirst($s->status) }}</span>
                        <div class="small text-muted">by {{ $s->reviewed_by }}, {{ $s->reviewed_at?->format('d M Y H:i') }}</div>
                        @if($s->payment)<div class="small">Extended to {{ $s->payment->period_to->format('d M Y') }}</div>@endif
                        @if($s->review_note)<div class="small text-danger">{{ $s->review_note }}</div>@endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5">Nothing here.</div></div>
@endforelse

{{ $submissions->links() }}
@endsection
