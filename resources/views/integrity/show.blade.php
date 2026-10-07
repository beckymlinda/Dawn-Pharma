@extends('layouts.app')

@section('title', 'Loss Prevention Event')

@php
    $sevClass = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
    $d = $event->details ?? [];
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="fw-bold mb-0">
            <span class="badge bg-{{ $sevClass[$event->severity] }} text-capitalize me-2">{{ $event->severity }}</span>{{ $event->label }}
        </h4>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('integrity.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <p class="mb-2">{{ $event->description }}</p>
                    <p class="text-muted small mb-0">{{ \App\Models\IntegrityEvent::TYPES[$event->type][1] ?? '' }}</p>
                </div>
            </div>

            {{-- Before / after for edited sales --}}
            @if(isset($d['before'], $d['after']))
                <div class="row g-3">
                    @foreach(['before' => 'Before edit', 'after' => 'After edit'] as $key => $title)
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-white fw-bold">{{ $title }}
                                    <span class="float-end">{{ formatCurrency($d[$key]['total_amount']) }}</span>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        @foreach($d[$key]['items'] as $item)
                                            <tr>
                                                <td>{{ $item['name'] }}</td>
                                                <td class="text-end">{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }} × {{ formatCurrency($item['price']) }}</td>
                                                <td class="text-end">{{ formatCurrency($item['subtotal']) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-light">
                                            <td colspan="2">Payment: <strong class="text-capitalize">{{ str_replace('_', ' ', $d[$key]['payment_method']) }}</strong></td>
                                            <td class="text-end small">
                                                @foreach($d[$key]['payments'] as $p)
                                                    {{ ucwords(str_replace('_', ' ', $p['method'])) }} {{ formatCurrency($p['amount']) }}<br>
                                                @endforeach
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            {{-- Deleted sale snapshot / cleared cart --}}
            @elseif(!empty($d['items']))
                <div class="card">
                    <div class="card-header bg-white fw-bold">
                        {{ $event->type === 'cart_cleared' ? 'Items in the cart' : 'Sale contents at the time' }}
                        @isset($d['total_amount'])<span class="float-end">{{ formatCurrency($d['total_amount']) }}</span>@endisset
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                            @foreach($d['items'] as $item)
                                <tr>
                                    <td>{{ $item['name'] ?? '' }}</td>
                                    <td class="text-end">{{ $item['quantity'] ?? '' }}</td>
                                    <td class="text-end">{{ formatCurrency($item['price'] ?? 0) }}</td>
                                    <td class="text-end">{{ formatCurrency($item['subtotal'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    @if(!empty($d['payments']) || !empty($d['sold_by']) || !empty($d['created_at']))
                        <div class="card-footer bg-white small text-muted">
                            @if(!empty($d['created_at'])) Sold {{ $d['created_at'] }} @endif
                            @if(!empty($d['sold_by'])) by {{ $d['sold_by'] }} @endif
                            @if(!empty($d['payments']))
                                · Paid:
                                @foreach($d['payments'] as $p) {{ ucwords(str_replace('_', ' ', $p['method'])) }} {{ formatCurrency($p['amount']) }}@if(!$loop->last), @endif @endforeach
                            @endif
                        </div>
                    @endif
                </div>

            {{-- Anything else: show as a simple list --}}
            @elseif(!empty($d))
                <div class="card">
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            @foreach($d as $key => $value)
                                @continue(is_array($value))
                                <tr>
                                    <td class="text-muted text-capitalize" style="width:40%">{{ str_replace('_', ' ', $key) }}</td>
                                    <td class="fw-semibold">{{ is_numeric($value) && !in_array($key, ['quantity', 'previous_quantity', 'new_quantity', 'removed_quantity']) ? formatCurrency($value) : $value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">When</td><td class="fw-semibold">{{ $event->created_at->format('D, d M Y H:i:s') }}</td></tr>
                        <tr><td class="text-muted">Staff</td><td class="fw-semibold">{{ $event->user->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Money at risk</td><td class="fw-semibold">{{ $event->amount > 0 ? formatCurrency($event->amount) : '—' }}</td></tr>
                        @if($event->invoice_no)
                            <tr><td class="text-muted">Invoice</td><td class="fw-semibold">
                                @if($event->sale_id && \App\Models\Sale::whereKey($event->sale_id)->exists())
                                    <a href="{{ route('sales.show', $event->sale_id) }}">{{ $event->invoice_no }}</a>
                                @else
                                    {{ $event->invoice_no }} <span class="text-danger">(no longer exists)</span>
                                @endif
                            </td></tr>
                        @endif
                        @if($event->register)
                            <tr><td class="text-muted">Register</td><td class="fw-semibold">#{{ $event->register->id }} ({{ $event->register->user->name ?? '' }})</td></tr>
                        @endif
                        @if($event->product)
                            <tr><td class="text-muted">Product</td><td class="fw-semibold"><a href="{{ route('reports.products.show', $event->product) }}">{{ $event->product->name }}</a></td></tr>
                        @endif
                        <tr><td class="text-muted">IP address</td><td>{{ $event->ip_address ?? '—' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Review</h6>
                    @if($event->reviewed)
                        <p class="small mb-2">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                            Reviewed by {{ $event->reviewer->name ?? 'Unknown' }} on {{ optional($event->reviewed_at)->format('d M Y H:i') }}
                        </p>
                        @if($event->review_note)
                            <p class="small bg-light rounded p-2">{{ $event->review_note }}</p>
                        @endif
                        <form method="POST" action="{{ route('integrity.review', $event) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-secondary">Re-open</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('integrity.review', $event) }}">
                            @csrf @method('PATCH')
                            <textarea name="review_note" class="form-control form-control-sm mb-2" rows="3" maxlength="500"
                                      placeholder="What did you find? e.g. customer returned item, cashier explained..."></textarea>
                            <button class="btn btn-sm btn-success"><i class="bi bi-check2 me-1"></i> Mark as reviewed</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
