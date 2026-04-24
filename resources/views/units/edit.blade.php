@extends('layouts.app')

@section('title', 'Edit Unit - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Edit Unit</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('units.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Units
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border" style="max-width: 600px;">
        <div class="card-body">
            <form action="{{ route('units.update', $unit) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           name="name" placeholder="Unit name" value="{{ old('name', $unit->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Short Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('short_name') is-invalid @enderror" 
                           name="short_name" placeholder="e.g., pcs, kg, L" value="{{ old('short_name', $unit->short_name) }}" required>
                    @error('short_name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="allowDecimal" name="allow_decimal" 
                           value="1" @checked(old('allow_decimal', $unit->allow_decimal))>
                    <label class="form-check-label fw-semibold" for="allowDecimal">
                        Allow Decimal Quantity
                    </label>
                    <small class="text-muted d-block mt-1">Check this if this unit can have decimal values (e.g., 2.5 kg)</small>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Update Unit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
