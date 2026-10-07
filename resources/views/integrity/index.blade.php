@extends('layouts.app')

@section('title', 'Loss Prevention')

@php
    $sevClass = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-shield-exclamation me-2"></i>Loss Prevention</h4>
            <p class="text-muted small mb-0">
                Actions that commonly hide theft: deleted or edited sales, price overrides, voids, cash shortages and stock write-offs.
                One event is not proof of theft; look for patterns and repeat offenders.
            </p>
        </div>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#lpSettings">
            <i class="bi bi-sliders me-1"></i> Settings
        </button>
    </div>

    <!-- Settings -->
    <div class="collapse mb-4" id="lpSettings">
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('integrity.settings') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small fw-semibold">Opening time</label>
                    <input type="time" name="lp_open_time" class="form-control" value="{{ $settings['lp_open_time'] }}" required>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label small fw-semibold">Closing time</label>
                    <input type="time" name="lp_close_time" class="form-control" value="{{ $settings['lp_close_time'] }}" required>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label small fw-semibold">Cash difference allowed (MWK)</label>
                    <input type="number" step="1" min="0" name="lp_cash_tolerance" class="form-control" value="{{ $settings['lp_cash_tolerance'] }}" required>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="blindClose" name="lp_blind_close" value="1" {{ $settings['lp_blind_close'] === '1' ? 'checked' : '' }}>
                        <label class="form-check-label small fw-semibold" for="blindClose">Cash count when closing register</label>
                    </div>
                    <div class="form-text mt-0">On: cashiers must count the drawer (without seeing the expected amount) and shortages are flagged. Off: the register closes without a cash count.</div>
                </div>
                <div class="col-lg-2 d-grid">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div></div>
    </div>

    <!-- Period -->
    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}">
        </div>
        <div class="col-auto">
            <label class="form-label small fw-semibold mb-1">To</label>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i> Apply</button>
        </div>
    </form>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        @foreach([
            ['Needs review', number_format($kpis['needs_review']), 'High / critical events not yet reviewed', 'bi-flag-fill', $kpis['needs_review'] ? 'danger' : 'success'],
            ['Deleted sales', formatCurrency($kpis['deleted_value']), $kpis['deleted_count'] . ' sale(s) deleted', 'bi-trash3-fill', $kpis['deleted_count'] ? 'danger' : 'secondary'],
            ['Cash shortages', formatCurrency($kpis['shortage_value']), 'Counted less than expected at close', 'bi-cash-stack', $kpis['shortage_value'] > 0 ? 'warning' : 'secondary'],
            ['Edits & overrides', formatCurrency($kpis['edit_value']), 'Lost to edited sales and low prices', 'bi-pencil-square', $kpis['edit_value'] > 0 ? 'warning' : 'secondary'],
            ['Voids', number_format($kpis['void_count']), 'Items removed / carts cleared', 'bi-x-octagon', 'secondary'],
        ] as [$label, $value, $hint, $icon, $color])
            <div class="col-6 col-lg">
                <div class="card h-100 border-0 shadow-sm border-start border-4 border-{{ $color }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="text-muted small fw-bold text-uppercase">{{ $label }}</div>
                            <i class="bi {{ $icon }} text-{{ $color }}"></i>
                        </div>
                        <div class="h5 fw-bold mb-0 mt-1">{{ $value }}</div>
                        <small class="text-muted">{{ $hint }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Cashier risk ranking -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Staff Risk Ranking</h6>
                <small class="text-muted">
                    Score: critical 10, high 5, medium 2, low 0.5 per event. "Per 100 sales" compares staff fairly.
                    Team average: {{ number_format($avgRate, 1) }} per 100 sales.
                </small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Staff</th>
                            <th class="text-end">Sales</th>
                            <th class="text-end">Deleted</th>
                            <th class="text-end">Edited</th>
                            <th class="text-end">Price overrides</th>
                            <th class="text-end">Voids</th>
                            <th class="text-end">Cash short</th>
                            <th class="text-end">Stock removed</th>
                            <th class="text-end">Other</th>
                            <th class="text-end">Score</th>
                            <th class="text-end">Per 100 sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashiers as $c)
                            <tr class="{{ $c->flag ? 'table-danger' : '' }}">
                                <td>
                                    <a href="{{ route('integrity.index', request()->except('user', 'page') + ['user' => $c->user->id]) }}#events" class="fw-semibold text-decoration-none">{{ $c->user->name }}</a>
                                    @if($c->flag)
                                        <span class="badge bg-danger ms-1">Investigate</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($c->sales_count) }}<br><small class="text-muted">{{ formatCurrency($c->sales_total) }}</small></td>
                                <td class="text-end {{ $c->deleted ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ (int) $c->deleted }}@if($c->deleted)<br><small>{{ formatCurrency($c->deleted_value) }}</small>@endif
                                </td>
                                <td class="text-end {{ $c->edited ? 'fw-bold' : 'text-muted' }}">
                                    {{ (int) $c->edited }}@if($c->edited_value > 0)<br><small class="text-danger">−{{ formatCurrency($c->edited_value) }}</small>@endif
                                </td>
                                <td class="text-end {{ $c->price_overrides ? 'text-danger fw-bold' : 'text-muted' }}">{{ (int) $c->price_overrides }}</td>
                                <td class="text-end {{ $c->voids ? '' : 'text-muted' }}">{{ (int) $c->voids }}</td>
                                <td class="text-end {{ $c->cash_short > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $c->cash_short > 0 ? formatCurrency($c->cash_short) : '—' }}</td>
                                <td class="text-end {{ $c->stock_removed ? '' : 'text-muted' }}">{{ (int) $c->stock_removed }}</td>
                                <td class="text-end text-muted">{{ (int) $c->other }}</td>
                                <td class="text-end fw-bold">{{ $c->score }}</td>
                                <td class="text-end">{{ $c->score_per_100 ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-4">No sales or events in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Event feed -->
    <div class="card" id="events">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Events</h6>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                    <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                    <select name="user" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">All staff</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">All types</option>
                        @foreach(\App\Models\IntegrityEvent::TYPES as $key => [$label])
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="severity" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">All severities</option>
                        @foreach(['critical', 'high', 'medium', 'low'] as $s)
                            <option value="{{ $s }}" @selected(request('severity') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">Reviewed and open</option>
                        <option value="open" @selected(request('status') === 'open')>Open only</option>
                    </select>
                    @if(request()->hasAny(['user', 'type', 'severity', 'status']))
                        <a href="{{ route('integrity.index', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}#events" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>When</th>
                            <th>Severity</th>
                            <th>Event</th>
                            <th>Details</th>
                            <th class="text-end">At risk</th>
                            <th>Staff</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                            <tr class="{{ $event->reviewed ? 'opacity-50' : '' }}" style="cursor:pointer" onclick="window.location='{{ route('integrity.show', $event) }}'">
                                <td class="text-nowrap small">{{ $event->created_at->format('M d, H:i') }}</td>
                                <td><span class="badge bg-{{ $sevClass[$event->severity] }} text-capitalize">{{ $event->severity }}</span></td>
                                <td class="fw-semibold small text-nowrap">{{ $event->label }}</td>
                                <td class="small">{{ $event->description }}</td>
                                <td class="text-end small text-nowrap">{{ $event->amount > 0 ? formatCurrency($event->amount) : '—' }}</td>
                                <td class="small text-nowrap">{{ $event->user->name ?? '—' }}</td>
                                <td class="text-end">
                                    @if($event->reviewed)
                                        <i class="bi bi-check-circle-fill text-success" title="Reviewed"></i>
                                    @else
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>No events found for this period.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $events->fragment('events')->links() }}
        </div>
    </div>
</div>
@endsection
