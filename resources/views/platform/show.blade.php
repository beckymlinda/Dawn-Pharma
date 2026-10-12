@extends('platform.layout')
@section('title', $tenant->name)

@section('content')
<a href="{{ route('platform.tenants.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> All shops</a>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mt-2 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $tenant->name }} @include('platform._state', ['tenant' => $tenant])</h1>
        <a href="{{ $tenant->url() }}" target="_blank" rel="noopener" class="small">{{ $tenant->url() }} <i class="bi bi-box-arrow-up-right"></i></a>
    </div>
    <form method="POST" action="{{ route('platform.tenants.impersonate', $tenant) }}"
          onsubmit="return confirm('Open {{ addslashes($tenant->name) }} logged in as its owner? You will leave the admin portal; log out to come back.')">
        @csrf
        <button class="btn btn-outline-dark" title="Opens the shop logged in as its owner, for support">
            <i class="bi bi-person-badge me-1"></i> Log in as shop owner
        </button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Paid you this month</div>
        <div class="stat-value {{ $usage['paid_month'] ? 'text-success' : '' }}">{{ formatCurrency($usage['paid_month']) }}</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Paid you in total</div>
        <div class="stat-value">{{ formatCurrency($usage['paid_total']) }}</div>
        @if($usage['pending'])<div class="small text-warning fw-semibold">+ {{ formatCurrency($usage['pending']) }} pending</div>@endif
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Last used</div>
        <div class="stat-value fs-5 mt-1">{{ $usage['last_used']?->diffForHumans() ?? 'Not yet' }}</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Staff accounts / products</div>
        <div class="stat-value">{{ $usage['users'] }} <span class="fs-6 text-muted fw-normal">/ {{ number_format($usage['products']) }}</span></div>
    </div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Shop details &amp; subscription</h2>
                <form method="POST" action="{{ route('platform.tenants.update', $tenant) }}">
                    @csrf
                    @method('PUT')
                    @include('platform._shop_fields', ['tenant' => $tenant])
                    <button class="btn btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-1">Record a payment</h2>
                <p class="small text-muted mb-3">
                    Paid until <strong>{{ $tenant->paid_until?->format('d M Y') ?? 'no end date' }}</strong>.
                    @if($tenant->blockedAfter()) Blocked after {{ $tenant->blockedAfter()->format('d M Y') }} if not paid. @endif
                </p>
                <form method="POST" action="{{ route('platform.tenants.extend', $tenant) }}">
                    @csrf
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <input name="amount" type="number" min="0" step="0.01" class="form-control form-control-sm"
                                   placeholder="Amount (default {{ number_format($tenant->monthlyPrice()) }}/month)" aria-label="Amount paid">
                        </div>
                        <div class="col-6">
                            <input name="method" class="form-control form-control-sm" placeholder="Paid by, e.g. Airtel Money" maxlength="50" aria-label="Payment method">
                        </div>
                        <div class="col-12">
                            <input name="reference" class="form-control form-control-sm" placeholder="Reference / transaction ID (optional)" maxlength="255" aria-label="Reference">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach([1 => '+1 month', 3 => '+3 months', 6 => '+6 months', 12 => '+1 year'] as $months => $label)
                            <button name="months" value="{{ $months }}" class="btn btn-sm btn-outline-success">{{ $label }}</button>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Access</h2>
                @if($tenant->isSuspended())
                    <p class="small mb-3">Suspended{{ $tenant->suspended_reason ? ': ' . $tenant->suspended_reason : '' }}. Staff cannot use the system; data is kept.</p>
                    <form method="POST" action="{{ route('platform.tenants.reactivate', $tenant) }}">
                        @csrf
                        <button class="btn btn-success btn-sm">Reactivate shop</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('platform.tenants.suspend', $tenant) }}"
                          onsubmit="return confirm('Suspend {{ addslashes($tenant->name) }}? Its staff will not be able to use the system until you reactivate it.')">
                        @csrf
                        <input name="reason" class="form-control form-control-sm mb-2" placeholder="Reason shown to the shop (optional)" maxlength="255">
                        <button class="btn btn-outline-danger btn-sm">Suspend shop</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Payment history</h2>
                @forelse($payments as $payment)
                    <div class="d-flex justify-content-between small py-2 border-bottom">
                        <div>
                            <strong>{{ formatCurrency($payment->amount) }}</strong> · {{ $payment->months }} {{ $payment->months == 1 ? 'month' : 'months' }}
                            <div class="text-muted">{{ $payment->period_from->format('d M Y') }} – {{ $payment->period_to->format('d M Y') }}
                                {{ $payment->method ? '· ' . $payment->method : '' }}{{ $payment->reference ? ' · ' . $payment->reference : '' }}</div>
                        </div>
                        <div class="text-muted text-end text-nowrap">{{ $payment->paid_on->format('d M Y') }}<br>{{ $payment->recorded_by }}</div>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No payments recorded yet.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Owner accounts</h2>
                @forelse($owners as $owner)
                    @php($bag = $errors->getBag("password_{$owner->id}"))
                    <div class="small {{ $loop->last ? '' : 'pb-3 mb-3 border-bottom' }}">
                        <strong>{{ $owner->name }}</strong><br><span class="text-muted">{{ $owner->email }}</span>
                        <details class="mt-2" {{ $bag->any() ? 'open' : '' }}>
                            <summary class="text-primary" style="cursor: pointer;"><i class="bi bi-key me-1"></i>Change password</summary>
                            <form method="POST" action="{{ route('platform.tenants.password', [$tenant, $owner->id]) }}" class="mt-2"
                                  onsubmit="return confirm('Change the password for {{ addslashes($owner->name) }}? Their old password will stop working.')">
                                @csrf
                                @method('PUT')
                                <input name="password" type="password" class="form-control form-control-sm mb-2 {{ $bag->has('password') ? 'is-invalid' : '' }}"
                                       placeholder="New password (at least 8 characters)" minlength="8" required autocomplete="new-password" aria-label="New password">
                                <input name="password_confirmation" type="password" class="form-control form-control-sm mb-2 {{ $bag->has('password') ? 'is-invalid' : '' }}"
                                       placeholder="Type it again" minlength="8" required autocomplete="new-password" aria-label="Confirm new password">
                                @if($bag->has('password'))<div class="text-danger mb-2">{{ $bag->first('password') }}</div>@endif
                                <button class="btn btn-sm btn-outline-primary">Save new password</button>
                            </form>
                        </details>
                    </div>
                @empty
                    <p class="small text-muted mb-0">No owner account.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
