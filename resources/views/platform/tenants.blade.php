@extends('platform.layout')
@section('title', 'Shops')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">Shops</h1>
        <p class="text-muted small mb-0">Every pharmacy using the system, its subscription and what it has paid you.</p>
    </div>
    <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> New shop</a>
</div>

<div class="card">
    <div class="card-body pb-0 d-flex gap-2 flex-wrap">
        @foreach(['all' => 'All', 'attention' => 'Due soon / overdue', 'suspended' => 'Suspended'] as $key => $label)
            <a href="?show={{ $key }}" class="btn btn-sm {{ $filter === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="small text-muted">
                <tr>
                    <th>Shop</th>
                    <th>Plan</th>
                    <th>Paid until</th>
                    <th class="text-end">Paid you this month</th>
                    <th class="text-end">Paid you in total</th>
                    <th class="text-end">Pending</th>
                    <th class="text-end">Staff</th>
                    <th>Last used</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    @php($u = $usage[$tenant->id])
                    <tr>
                        <td>
                            <a href="{{ route('platform.tenants.show', $tenant) }}" class="fw-semibold text-decoration-none">{{ $tenant->name }}</a>
                            <div class="small text-muted">/{{ $tenant->slug }}{{ $tenant->contact_phone ? ' · ' . $tenant->contact_phone : '' }}</div>
                        </td>
                        <td class="small">{{ $tenant->planName() }}@if($tenant->monthlyPrice())<div class="text-muted">{{ number_format($tenant->monthlyPrice()) }}/month</div>@endif</td>
                        <td class="small text-nowrap">
                            {{ $tenant->paid_until?->format('d M Y') ?? '—' }}<br>
                            @include('platform._state', ['tenant' => $tenant])
                        </td>
                        <td class="text-end small text-nowrap {{ $u['paid_month'] ? 'fw-semibold text-success' : 'text-muted' }}">{{ $u['paid_month'] ? formatCurrency($u['paid_month']) : '–' }}</td>
                        <td class="text-end small text-nowrap">{{ formatCurrency($u['paid_total']) }}</td>
                        <td class="text-end small text-nowrap">
                            @if($u['pending'])
                                <a href="{{ route('platform.payments.index') }}" class="text-warning fw-semibold text-decoration-none">{{ formatCurrency($u['pending']) }}</a>
                            @else
                                <span class="text-muted">–</span>
                            @endif
                        </td>
                        <td class="text-end small">{{ $u['users'] }}</td>
                        <td class="small text-nowrap {{ $u['last_used'] && $u['last_used']->lt(now()->subDays(7)) ? 'text-danger' : 'text-muted' }}"
                            title="Last sale made in the shop">
                            {{ $u['last_used'] ? $u['last_used']->diffForHumans() : 'not yet' }}
                        </td>
                        <td class="text-end"><a href="{{ route('platform.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-primary">Manage</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No shops here.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
