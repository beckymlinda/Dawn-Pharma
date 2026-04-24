@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Close Register</h4>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info">
                        <strong>Register Details</strong>
                        <table class="table table-sm mt-2 mb-0">
                            <tr>
                                <td>Opened At:</td>
                                <td><strong>{{ $register->opened_at->format('d M Y H:i') }}</strong></td>
                            </tr>
                            <tr>
                                <td>Opening Cash:</td>
                                <td><strong>MWK {{ number_format($register->opening_cash, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td>Time Open:</td>
                                <td><strong>{{ $register->opened_at->diffForHumans(now()) }}</strong></td>
                            </tr>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Sales by Payment Method</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Payment Method</th>
                                                <th class="text-center">Sales Count</th>
                                                <th class="text-right">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Cash Sales:</td>
                                                <td class="text-center"><strong>{{ $counts['count_cash'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_cash'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Card Sales:</td>
                                                <td class="text-center"><strong>{{ $counts['count_card'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_card'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Cheques:</td>
                                                <td class="text-center"><strong>{{ $counts['count_cheques'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_cheques'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Bank Transfer:</td>
                                                <td class="text-center"><strong>{{ $counts['count_bank_transfer'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_bank_transfer'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Advance:</td>
                                                <td class="text-center"><strong>{{ $counts['count_advance'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_advance'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Standard Bank:</td>
                                                <td class="text-center"><strong>{{ $counts['count_standard_bank'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_standard_bank'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>National Bank:</td>
                                                <td class="text-center"><strong>{{ $counts['count_national_bank'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_national_bank'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>TNM Mpamba:</td>
                                                <td class="text-center"><strong>{{ $counts['count_mpamba'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_mpamba'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Airtel Money:</td>
                                                <td class="text-center"><strong>{{ $counts['count_airtel_money'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_airtel_money'], 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Cash and Card:</td>
                                                <td class="text-center"><strong>{{ $counts['count_cash_and_card'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_cash_and_card'], 2) }}</strong></td>
                                            </tr>
                                            <tr class="table-primary">
                                                <td><strong>Total Sales:</strong></td>
                                                <td class="text-center"><strong>{{ $counts['count_total_sales'] ?? 0 }}</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_sales'], 2) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Register Summary</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Opening Cash:</td>
                                                <td class="text-right"><strong>MWK {{ number_format($register->opening_cash, 2) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Total Sales Count:</td>
                                                <td class="text-right"><strong>{{ $counts['count_total_sales'] ?? 0 }} sales</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Total Sales Amount:</td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_sales'], 2) }}</strong></td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>Expected Closing Cash:</strong></td>
                                                <td class="text-right"><strong>MWK {{ number_format($totals['total_sales'], 2) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="alert alert-warning mt-3">
                                        <strong>Note:</strong> The closing cash will be set to the total of all sales made during this register session.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <form action="{{ route('register.update', $register->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-danger w-100 py-2">
                                <i class="fas fa-times"></i> Close Register
                            </button>
                        </form>

                        <a href="{{ route('pos.create') }}" class="btn btn-secondary w-100 mt-2 py-2">
                            <i class="fas fa-arrow-left"></i> Back to POS
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
