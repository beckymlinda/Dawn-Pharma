@extends('platform.layout')
@section('title', 'Income')

@section('content')
@php($monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'])
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0">Income</h1>
        <p class="text-muted small mb-0">What each pharmacy has paid you, month by month (by date paid).</p>
    </div>
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="year" class="small text-muted">Year</label>
        <select id="year" name="year" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 text-white" style="background: linear-gradient(135deg, #065f46, #10b981);"><div class="card-body">
            <div class="small opacity-75">Total income {{ $year }}</div>
            <div class="stat-value" style="font-size: clamp(1.4rem, 2vw, 1.9rem);">{{ formatCurrency($yearTotal) }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Average per month</div>
            <div class="stat-value">{{ formatCurrency($monthsCounted ? $yearTotal / $monthsCounted : 0) }}</div>
            <div class="small text-muted">{{ $monthsCounted ? 'over ' . $monthsCounted . ' ' . ($monthsCounted == 1 ? 'month' : 'months') . ' since the first payment' : 'no income yet' }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <a href="{{ route('platform.payments.index') }}" class="text-decoration-none text-reset">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Pending approval (not yet counted)</div>
            <div class="stat-value {{ $pendingTotal ? 'text-warning' : '' }}">{{ formatCurrency($pendingTotal) }}</div>
        </div></div>
        </a>
    </div>
</div>

{{-- Pharmacy x month --}}
<div class="card mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 170px;">Pharmacy</th>
                    @foreach($monthNames as $m => $name)
                        <th class="text-end {{ $m === $month ? 'table-success' : '' }}">
                            <a href="?year={{ $year }}&month={{ $m }}#month-payments" class="text-reset text-decoration-none">{{ $name }}</a>
                        </th>
                    @endforeach
                    <th class="text-end">Total {{ $year }}</th>
                    <th class="text-end">Pending</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $row)
                    <tr>
                        <td>
                            <a href="{{ route('platform.tenants.show', $row['tenant']) }}" class="fw-semibold text-decoration-none">{{ $row['tenant']->name }}</a>
                            <div class="text-muted">{{ $row['tenant']->planName() }}</div>
                        </td>
                        @foreach($monthNames as $m => $name)
                            @php($amount = $row['months'][$m] ?? 0)
                            <td class="text-end text-nowrap {{ $m === $month ? 'table-success' : '' }} {{ $amount ? 'fw-semibold' : 'text-muted' }}">
                                {{ $amount ? number_format($amount) : '–' }}
                            </td>
                        @endforeach
                        <td class="text-end fw-bold text-nowrap">{{ formatCurrency($row['total']) }}</td>
                        <td class="text-end text-nowrap">
                            @if($row['pending'])
                                <a href="{{ route('platform.payments.index') }}" class="text-warning fw-semibold text-decoration-none">{{ number_format($row['pending']) }}</a>
                            @else
                                <span class="text-muted">–</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="15" class="text-center text-muted py-4">No shops yet.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>Total</td>
                    @foreach($monthNames as $m => $name)
                        <td class="text-end text-nowrap {{ $m === $month ? 'table-success' : '' }}">{{ $monthTotals[$m] ? number_format($monthTotals[$m]) : '–' }}</td>
                    @endforeach
                    <td class="text-end text-nowrap">{{ formatCurrency($yearTotal) }}</td>
                    <td class="text-end text-nowrap">{{ $pendingTotal ? number_format($pendingTotal) : '–' }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="card-body py-2 small text-muted">Amounts in MWK. Click a month to list its payments.</div>
</div>

{{-- Payments of the selected month --}}
<div class="card" id="month-payments">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-baseline mb-3">
            <h2 class="h6 fw-bold mb-0">Payments in {{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</h2>
            <span class="fw-bold">{{ formatCurrency($monthTotals[$month]) }}</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 small">
                <thead class="text-muted"><tr><th>Date paid</th><th>Pharmacy</th><th>For</th><th>Method</th><th>Reference</th><th>Recorded by</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse($monthPayments as $payment)
                        <tr>
                            <td class="text-nowrap">{{ $payment->paid_on->format('d M Y') }}</td>
                            <td class="fw-semibold">{{ $payment->tenant->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $payment->months }} {{ $payment->months == 1 ? 'month' : 'months' }}<div class="text-muted">{{ $payment->period_from->format('d M') }} – {{ $payment->period_to->format('d M Y') }}</div></td>
                            <td>{{ $payment->method ?? '—' }}</td>
                            <td class="text-muted">{{ $payment->reference ?? '—' }}</td>
                            <td class="text-muted">{{ $payment->recorded_by }}</td>
                            <td class="text-end fw-semibold text-nowrap">{{ formatCurrency($payment->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No payments in this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
