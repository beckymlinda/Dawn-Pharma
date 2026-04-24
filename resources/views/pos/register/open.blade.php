@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Open Register</h4>
                </div>
                <div class="card-body">
                    @if ($openRegister)
                        <div class="alert alert-warning">
                            <strong>Register Already Open</strong>
                            <p>Opened at: {{ $openRegister->opened_at->format('d M Y H:i') }}</p>
                            <p>Opening Cash: MWK {{ number_format($openRegister->opening_cash, 2) }}</p>
                            <a href="{{ route('pos.create') }}" class="btn btn-primary mt-3">
                                Go to POS
                            </a>
                            <a href="{{ route('register.confirm') }}" class="btn btn-danger mt-3">
                                Close Register
                            </a>
                        </div>
                    @else
                        <form action="{{ route('register.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="opening_cash" class="form-label">Opening Cash (MWK)</label>
                                <input type="number" 
                                       class="form-control @error('opening_cash') is-invalid @enderror" 
                                       id="opening_cash" 
                                       name="opening_cash" 
                                       step="0.01" 
                                       min="0" 
                                       value="{{ old('opening_cash', 0) }}" 
                                       required>
                                @error('opening_cash')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="location_id" class="form-label">Location (Optional)</label>
                                <select class="form-control @error('location_id') is-invalid @enderror" 
                                        id="location_id" 
                                        name="location_id">
                                    <option value="">Select Location</option>
                                    @forelse (\App\Models\Location::all() as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @empty
                                        <option value="" disabled>No locations available</option>
                                    @endforelse
                                </select>
                                @error('location_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2">
                                <i class="fas fa-check"></i> Open Register
                            </button>
                        </form>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
