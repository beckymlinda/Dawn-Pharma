@extends('layouts.app')

@section('content')
<div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
        <header class="app-topbar" id="app-topbar"></header>
        <main class="app-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 fw-bold mb-0">Edit User</h1>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
            </div>

            <div class="card border">
                <div class="card-header bg-white">
                    <h2 class="h6 fw-semibold mb-0">User Information</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                                    <small class="text-muted">Enter new password only if you want to change it</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Roles</label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                @foreach($roles as $role)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]" 
                                           value="{{ $role->id }}" 
                                           id="role_{{ $role->id }}"
                                           {{ $user->roles->contains($role) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->display_name }}
                                        @if($role->description)
                                        <small class="text-muted d-block">{{ $role->description }}</small>
                                        @endif
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted">
                                    Created: {{ $user->created_at->format('M d, Y H:i') }} | 
                                    Updated: {{ $user->updated_at->format('M d, Y H:i') }}
                                </small>
                            </div>
                            <div>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update User</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
