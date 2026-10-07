@extends('layouts.app')

@section('title', 'Units - ' . setting('system_name'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h4 fw-bold">Units</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('units.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Unit
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
            @if ($units->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Short Name</th>
                                <th>Allow Decimal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $unit)
                                <tr>
                                    <td>{{ $unit->id }}</td>
                                    <td>{{ $unit->name }}</td>
                                    <td><span class="badge bg-secondary">{{ $unit->short_name }}</span></td>
                                    <td>
                                        @if ($unit->allow_decimal)
                                            <i class="bi bi-check-circle text-success"></i> Yes
                                        @else
                                            <i class="bi bi-x-circle text-danger"></i> No
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('units.destroy', $unit) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this unit?')">
                                                <i class="bi bi-trash"></i> Delete
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
                    {{ $units->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No units found. <a href="{{ route('units.create') }}">Create one now!</a></p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
