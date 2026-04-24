@php
    $openRegister = getOpenRegister();
@endphp

@if ($openRegister)
    <div class="card border-success mb-3">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0">
                    <i class="fas fa-check-circle"></i> Register Open
                </h6>
            </div>
            <div>
                <small>{{ $openRegister->opened_at->format('H:i') }}</small>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><small>Opening Cash</small></p>
                    <h6>MWK {{ number_format($openRegister->opening_cash, 2) }}</h6>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('register.confirm') }}" class="btn btn-sm btn-danger">
                        <i class="fas fa-power-off"></i> Close
                    </a>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-danger mb-3">
        <i class="fas fa-exclamation-circle"></i>
        <strong>No Register Open</strong>
        <a href="{{ route('register.create') }}" class="btn btn-sm btn-primary float-end">
            Open Register
        </a>
    </div>
@endif
