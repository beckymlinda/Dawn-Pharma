@extends('layouts.app')

@section('content')
@php
    // Blind close: cashiers must count the drawer without seeing what the system expects
    $isAdmin = auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin');
    // One switch in Loss Prevention settings: when on, everyone must count the drawer and non-admins can't see the expected cash
    $countRequired = \App\Models\IntegrityEvent::cashCountRequired();
    $blind = $countRequired && !$isAdmin;
    $money = fn ($v, $prefix = 'MWK ') => $blind ? '••••••' : $prefix . number_format($v, 2);
@endphp
<style>
    :root {
        --brand-blue: #0d6efd;
        --brand-green: #28a745;
        --brand-danger: #dc3545;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    .close-register-container {
        max-width: 1000px;
        margin: 2rem auto;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .stat-card {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s ease, shadow 0.3s ease;
        overflow: hidden;
        background: white;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .summary-table {
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .summary-table tr {
        background: #f8f9fa;
        border-radius: 10px;
        transition: background 0.2s;
    }

    .summary-table tr:hover {
        background: #f1f3f5;
    }

    .summary-table td, .summary-table th {
        padding: 1rem;
        border: none;
    }

    .summary-table tr td:first-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }

    .summary-table tr td:last-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    .method-badge {
        font-weight: 600;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-close-register {
        background: var(--brand-danger);
        border: none;
        padding: 1rem;
        font-size: 1.1rem;
        border-radius: 12px;
        transition: all 0.3s;
    }

    .btn-close-register:hover {
        background: #bd2130;
        transform: scale(1.02);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }

    /* Print styles */
    @media print {
        @page { margin: 0; }
        body * {
            visibility: hidden;
        }
        body {
            background: white;
            margin: 0;
            padding: 0;
        }
        .close-register-container, .navbar, .sidebar {
            display: none !important;
        }
        #printable-receipt, #printable-receipt * {
            visibility: visible;
        }
        #printable-receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 320px !important;
            margin: 0;
            padding: 20px 10px;
            font-family: monospace;
            font-size: 14px;
            line-height: 1.4;
            color: #000;
        }
        .receipt-table { width: 100%; border-collapse: collapse; }
        .receipt-table td, .receipt-table th { padding: 4px 0; }
    }
    
    /* Hide receipt on screen */
    @media screen {
        #printable-receipt {
            display: none;
        }
    }
</style>

<div class="close-register-container px-3">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Close Register</h2>
            <p class="text-muted mb-0">Review sales and finalize your session for <span class="fw-bold">{{ auth()->user()->name }}</span></p>
        </div>
        <button onclick="window.print()" class="btn btn-primary shadow-sm px-4 py-2 rounded-pill fw-bold">
            <i class="bi bi-printer me-2"></i> Print Z-Report
        </button>
    </div>

    <!-- Quick Stats Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="stat-icon bg-primary text-white">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="text-muted small fw-bold text-uppercase">Opening Cash</div>
                    <div class="h3 fw-bold mb-0">MWK {{ number_format($register->opening_cash, 2) }}</div>
                    <div class="text-muted mt-2 small">Started at {{ $register->opened_at->format('H:i') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="stat-icon bg-success text-white">
                        <i class="bi bi-cart-check"></i>
                    </div>
                    <div class="text-muted small fw-bold text-uppercase">Total Sales</div>
                    <div class="h3 fw-bold mb-0">{{ $money($totals['total_sales']) }}</div>
                    <div class="text-success mt-2 small">
                        <i class="bi bi-arrow-up-short"></i> {{ $counts['count_total_sales'] ?? 0 }} Transactions
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body">
                    <div class="stat-icon bg-info text-white">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="text-muted small fw-bold text-uppercase">Time Open</div>
                    <div class="h3 fw-bold mb-0">{{ (int)$register->opened_at->diffInHours(now()) }}h {{ (int)($register->opened_at->diffInMinutes(now()) % 60) }}m</div>
                    <div class="text-muted mt-2 small">{{ $register->opened_at->format('d M Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Detailed Breakdown -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0">Sales Breakdown</h5>
                </div>
                <div class="card-body">
                    <table class="table summary-table mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase">
                                <th>Method</th>
                                <th class="text-center">Count</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $methods = [
                                    ['key' => 'cash', 'label' => 'Cash', 'icon' => 'bi-cash-stack', 'color' => 'text-success'],
                                    ['key' => 'card', 'label' => 'Card', 'icon' => 'bi-credit-card', 'color' => 'text-primary'],
                                    ['key' => 'airtel_money', 'label' => 'Airtel Money', 'icon' => 'bi-phone', 'color' => 'text-danger'],
                                    ['key' => 'mpamba', 'label' => 'TNM Mpamba', 'icon' => 'bi-phone-fill', 'color' => 'text-success'],
                                    ['key' => 'bank_transfer', 'label' => 'Bank Transfer', 'icon' => 'bi-bank', 'color' => 'text-info'],
                                    ['key' => 'standard_bank', 'label' => 'Standard Bank', 'icon' => 'bi-building', 'color' => 'text-primary'],
                                    ['key' => 'national_bank', 'label' => 'National Bank', 'icon' => 'bi-building-fill', 'color' => 'text-primary'],
                                    ['key' => 'cheques', 'label' => 'Cheques', 'icon' => 'bi-file-earmark-check', 'color' => 'text-secondary'],
                                    ['key' => 'advance', 'label' => 'Advance', 'icon' => 'bi-hourglass-split', 'color' => 'text-warning'],
                                    ['key' => 'cash_and_card', 'label' => 'Split Payment', 'icon' => 'bi-intersect', 'color' => 'text-dark'],
                                ];
                            @endphp

                            @foreach($methods as $method)
                                @if(($counts['count_'.$method['key']] ?? 0) > 0)
                                <tr>
                                    <td>
                                        <div class="method-badge">
                                            <i class="bi {{ $method['icon'] }} {{ $method['color'] }} fs-5"></i>
                                            <span>{{ $method['label'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold">{{ $counts['count_'.$method['key']] }}</td>
                                    <td class="text-end fw-bold">{{ $money($totals['total_'.$method['key']]) }}</td>
                                </tr>
                                @endif
                            @endforeach

                            @if(($counts['count_total_sales'] ?? 0) == 0)
                            <tr>
                                <td colspan="3" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                    <span class="text-muted">No sales recorded for this session.</span>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Closing Actions -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 bg-light mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Expected Balance</h5>
                    
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                        <span class="text-muted">Opening Cash</span>
                        <span class="fw-bold">MWK {{ number_format($register->opening_cash, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 border-bottom pb-3">
                        <span class="text-muted">Cash Sales Only</span>
                        <span class="fw-bold">{{ $money($totals['total_cash'] ?? 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between p-3 bg-white rounded-3 shadow-sm mb-4">
                        <span class="fw-bold text-dark fs-5">Expected in Drawer</span>
                        <span class="fw-bold text-primary fs-5">{{ $money(($totals['total_cash'] ?? 0) + $register->opening_cash) }}</span>
                    </div>

                    <div class="alert alert-warning border-0 shadow-sm small mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        @if($countRequired)
                            Count all the cash in your drawer. You will enter the amount when you close the register.
                        @else
                            Confirm the amount in your drawer matches the expected total before closing.
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="button" class="btn btn-close-register text-white w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#confirmCloseModal">
                    <i class="bi bi-check2-circle me-2"></i> Confirm & Close Register
                </button>
                <a href="{{ route('pos.new') }}" class="btn btn-outline-secondary border-0 py-2">
                    <i class="bi bi-arrow-left me-2"></i> Back to POS
                </a>
            </div>
        </div>
    </div>
</div>

<!-- HIDDEN RECEIPT FOR PRINTING ONLY (Z-REPORT) -->
<div id="printable-receipt">
    <div class="text-center mb-3 pt-2">
        @if(isset($settings['logo']))
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

<!-- Confirm Close Register Modal -->
<div class="modal fade" id="confirmCloseModal" tabindex="-1" aria-labelledby="confirmCloseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('register.update', $register->id) }}" method="POST" class="m-0">
                @csrf
                @method('PATCH')
                <div class="modal-body text-center py-4">
                    <div class="text-danger mb-3" style="font-size: 3.5rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Close Register?</h4>
                    <p class="text-muted px-3 mb-3">This will end your session and finalize all sales records for this shift.</p>

                    @if($countRequired)
                    <div class="text-start px-3">
                        <label for="countedCash" class="form-label fw-semibold">Cash counted in drawer (MWK) <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="0" name="counted_cash" id="countedCash"
                               class="form-control form-control-lg @error('counted_cash') is-invalid @enderror"
                               value="{{ old('counted_cash') }}" placeholder="0" required>
                        @error('counted_cash')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Include the opening float. Count notes and coins; do not copy a figure from the screen.</div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center gap-2 pb-4 pt-0">
                    <button type="button" class="btn btn-light px-4 py-2 rounded-pill fw-semibold" data-bs-dismiss="modal">
                        No, Cancel
                    </button>
                    <button type="submit" class="btn btn-danger px-4 py-2 rounded-pill fw-semibold">
                        Yes, Close Register
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@if($errors->has('counted_cash'))
<script>
    // Re-open the close dialog so the cashier can enter the counted cash
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('confirmCloseModal')).show();
    });
</script>
@endif
@endsection
