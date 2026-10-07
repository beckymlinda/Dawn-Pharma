@extends('layouts.app')

@section('title', 'Edit Brand - ' . setting('system_name'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Edit Brand</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('brands.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Brands
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

    <div class="card border" style="max-width: 700px;">
        <div class="card-body">
            <form action="{{ route('brands.update', $brand) }}" method="POST" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Brand Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           name="name" placeholder="Brand name" value="{{ old('name', $brand->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Note:</label>
                    <textarea class="form-control @error('note') is-invalid @enderror" 
                              name="note" rows="5" placeholder="Enter any additional notes about this brand...">{{ old('note', $brand->note) }}</textarea>
                    @error('note')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Update Brand
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
