@extends('layouts.app')

@section('title', 'Purchases - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Purchases</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Purchase
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border">
        <div class="card-body">
            @if ($purchases->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Reference No</th>
                                <th>Supplier</th>
                                <th>Status</th>
                                <th>Payment Status</th>
                                <th class="text-end">Grand Total</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->purchase_date->format('M d, Y') }}</td>
                                    <td><span class="badge bg-secondary">{{ $purchase->reference_no }}</span></td>
                                    <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                                    <td>
                                        @switch($purchase->status)
                                            @case('ordered')
                                                <span class="badge bg-info">Ordered</span>
                                                @break
                                            @case('received')
                                                <span class="badge bg-success">Received</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning text-dark">Pending</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($purchase->payment_status)
                                            @case('paid')
                                                <span class="badge bg-success">Paid</span>
                                                @break
                                            @case('due')
                                                <span class="badge bg-danger">Due</span>
                                                @break
                                            @case('partial')
                                                <span class="badge bg-warning text-dark">Partial</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-end fw-semibold">MWK {{ number_format($purchase->grand_total, 2) }}</td>
                                    <td>{{ $purchase->user?->name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Are you sure? This will reverse stock updates.')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $purchases->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No purchases found. <a href="{{ route('purchases.create') }}">Create one now!</a></p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
