@extends('layouts.app')

@section('title', 'Create Category - EDUC Pharmacy')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Add New Category</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Categories
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
            <form action="{{ route('categories.store') }}" method="POST" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           name="name" placeholder="Category name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Code:</label>
                    <input type="text" class="form-control @error('category_code') is-invalid @enderror" 
                           name="category_code" placeholder="e.g., CAT-001" value="{{ old('category_code') }}">
                    @error('category_code')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Description:</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              name="description" rows="5" placeholder="Enter category description...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check-circle"></i> Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
