@extends('layouts.app')

@section('title', 'Expenses - ' . setting('system_name'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">Expenses</h3>
            <p class="text-muted small mb-0">Running costs such as rent, wages and electricity. They are taken off your profit in the
                @if(auth()->user()->hasPermission('view_profit_loss_report'))
                    <a href="{{ route('reports.profit-loss', ['date_range' => 'custom', 'from_date' => $from->toDateString(), 'to_date' => $to->toDateString()]) }}">Profit &amp; Loss report</a>.
                @else
                    Profit &amp; Loss report.
                @endif
            </p>
        </div>
        <form method="GET" class="d-flex align-items-end gap-2 flex-wrap">
            <div>
                <label class="form-label small text-muted mb-1" for="from">From</label>
                <input type="date" id="from" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label small text-muted mb-1" for="to">To</label>
                <input type="date" id="to" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label small text-muted mb-1" for="filterCategory">Category</label>
                <select id="filterCategory" name="category" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-sm btn-outline-primary">Show</button>
        </form>
    </div>

    <div class="row g-4">
        {{-- Add an expense --}}
        @if(auth()->user()->hasPermission('add_expense'))
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Record an expense</h6>
                    <form method="POST" action="{{ route('expenses.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="expense_date" class="form-label small fw-semibold">Date</label>
                            <input type="date" id="expense_date" name="expense_date" max="{{ now()->toDateString() }}"
                                   value="{{ old('expense_date', now()->toDateString()) }}"
                                   class="form-control @error('expense_date') is-invalid @enderror" required>
                            @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label small fw-semibold">Category</label>
                            <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="" disabled @selected(!old('category'))>Choose…</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label small fw-semibold">Amount (MWK)</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" inputmode="decimal"
                                   value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label small fw-semibold">Paid with <span class="text-muted fw-normal">(optional)</span></label>
                            <select id="payment_method" name="payment_method" class="form-select">
                                <option value="">—</option>
                                @foreach($paymentMethods as $key => $label)
                                    <option value="{{ $key }}" @selected(old('payment_method') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="description" class="form-label small fw-semibold">Note <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" id="description" name="description" maxlength="255" value="{{ old('description') }}"
                                   class="form-control" placeholder="e.g. October rent">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Save expense</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <div class="{{ auth()->user()->hasPermission('add_expense') ? 'col-lg-8' : 'col-12' }}">
            {{-- Totals by category --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-baseline mb-3">
                        <h6 class="fw-bold mb-0">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</h6>
                        <div class="text-end">
                            <div class="small text-muted">Total expenses</div>
                            <div class="fs-4 fw-bold text-danger">{{ formatCurrency($total) }}</div>
                        </div>
                    </div>
                    @forelse($byCategory as $row)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $row->category }} <span class="text-muted">({{ $row->count }})</span></span>
                                <span class="fw-semibold">{{ formatCurrency($row->total) }}</span>
                            </div>
                            <div class="progress" style="height: 6px;" role="presentation">
                                <div class="progress-bar bg-danger" style="width: {{ $total > 0 ? round($row->total / $total * 100, 1) : 0 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No expenses recorded for this period.</p>
                    @endforelse
                </div>
            </div>

            {{-- List --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-muted">Date</th>
                                <th class="small text-muted">Category</th>
                                <th class="small text-muted">Note</th>
                                <th class="small text-muted">Recorded by</th>
                                <th class="small text-muted text-end">Amount</th>
                                @if(auth()->user()->hasPermission('delete_expense'))<th class="small text-muted"><span class="visually-hidden">Delete</span></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $expense)
                                <tr>
                                    <td class="text-nowrap small">{{ $expense->expense_date->format('d M Y') }}</td>
                                    <td class="small">{{ $expense->category }}</td>
                                    <td class="small text-muted">{{ $expense->description }}</td>
                                    <td class="small text-muted">{{ $expense->user->name ?? '—' }}</td>
                                    <td class="text-end fw-semibold text-nowrap">{{ formatCurrency($expense->amount) }}</td>
                                    @if(auth()->user()->hasPermission('delete_expense'))
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                              onsubmit="return confirm('Delete this expense of {{ formatCurrency($expense->amount) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" aria-label="Delete expense"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No expenses in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($expenses->hasPages())
                    <div class="p-3">{{ $expenses->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
