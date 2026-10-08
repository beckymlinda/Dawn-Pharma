@extends('platform.layout')
@section('title', 'Dashboard')

@section('content')
<div class="mb-4">
    <h1 class="h3 fw-bold mb-0">Dashboard</h1>
    <p class="text-muted small mb-0">{{ now()->format('l, d F Y') }}</p>
</div>

{{-- Your money --}}
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('platform.income') }}" class="text-decoration-none text-reset">
        <div class="card h-100 text-white" style="background: linear-gradient(135deg, #065f46, #10b981);">
            <div class="card-body">
                <div class="small opacity-75">Your income this month</div>
                <div class="stat-value text-nowrap" style="font-size: clamp(1.4rem, 2vw, 1.9rem);">{{ formatCurrency($income['this_month']) }}</div>
                <div class="small opacity-75">{{ $income['payments_this_month'] }} {{ $income['payments_this_month'] == 1 ? 'payment' : 'payments' }} · {{ now()->format('F') }}</div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Last month</div>
            <div class="stat-value">{{ formatCurrency($income['last_month']) }}</div>
            <div class="small text-muted">{{ now()->subMonthNoOverflow()->format('F') }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">This year</div>
            <div class="stat-value">{{ formatCurrency($income['this_year']) }}</div>
            <div class="small text-muted">Expected per month: {{ formatCurrency($income['expected_monthly']) }}</div>
        </div></div>
    </div>
    <div class="col-md-6 col-xl-3">
        <a href="{{ route('platform.payments.index') }}" class="text-decoration-none text-reset">
        <div class="card h-100 {{ $pending['count'] ? 'border border-warning' : '' }}"><div class="card-body">
            <div class="text-muted small">Pending approval</div>
            <div class="stat-value {{ $pending['count'] ? 'text-warning' : '' }}">{{ formatCurrency($pending['amount']) }}</div>
            <div class="small {{ $pending['count'] ? 'fw-semibold' : 'text-muted' }}">
                {{ $pending['count'] ? $pending['count'] . ' ' . ($pending['count'] == 1 ? 'proof' : 'proofs') . ' to review →' : 'Nothing waiting' }}
            </div>
        </div></div>
        </a>
    </div>
</div>

{{-- Shops at a glance --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Shops', $shops['total'], 'bi-shop', 'text-dark', 'all'],
        ['Working', $shops['working'], 'bi-check-circle', 'text-success', 'all'],
        ['Due within ' . config('tenancy.warn_days_before') . ' days', $shops['due_soon'], 'bi-hourglass-split', $shops['due_soon'] ? 'text-warning' : 'text-muted', 'attention'],
        ['Overdue / blocked', $shops['overdue'], 'bi-exclamation-octagon', $shops['overdue'] ? 'text-danger' : 'text-muted', 'attention'],
        ['Suspended', $shops['suspended'], 'bi-pause-circle', 'text-muted', 'suspended'],
    ] as [$label, $value, $icon, $class, $filter])
        <div class="col-6 col-md">
            <a href="{{ route('platform.tenants.index', ['show' => $filter]) }}" class="text-decoration-none text-reset">
                <div class="card h-100"><div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 small text-muted"><i class="bi {{ $icon }} {{ $class }}"></i> {{ $label }}</div>
                    <div class="fs-4 fw-bold {{ $class }}">{{ $value }}</div>
                </div></div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    {{-- Who needs to pay --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-bell me-2"></i>Subscriptions ending or overdue</h2>
                    <a href="{{ route('platform.tenants.index', ['show' => 'attention']) }}" class="small">All</a>
                </div>
                @forelse($attention as $tenant)
                    <a href="{{ route('platform.tenants.show', $tenant) }}" class="d-flex justify-content-between align-items-center py-2 border-bottom text-decoration-none text-reset">
                        <div>
                            <div class="fw-semibold">{{ $tenant->name }}</div>
                            <div class="small text-muted">{{ $tenant->planName() }} · {{ $tenant->paid_until?->format('d M Y') }}{{ $tenant->contact_phone ? ' · ' . $tenant->contact_phone : '' }}</div>
                        </div>
                        @include('platform._state', ['tenant' => $tenant])
                    </a>
                @empty
                    <p class="text-muted small mb-0"><i class="bi bi-check2-circle text-success me-1"></i>All subscriptions are up to date.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Latest money in --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0"><i class="bi bi-cash-coin me-2"></i>Latest payments</h2>
                    <a href="{{ route('platform.income') }}" class="small">Income by shop</a>
                </div>
                @forelse($recentPayments as $payment)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $payment->tenant->name ?? '—' }}</div>
                            <div class="small text-muted">{{ $payment->paid_on->format('d M Y') }} · {{ $payment->months }} {{ $payment->months == 1 ? 'month' : 'months' }}{{ $payment->method ? ' · ' . $payment->method : '' }}</div>
                        </div>
                        <div class="fw-bold text-success text-nowrap">{{ formatCurrency($payment->amount) }}</div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
